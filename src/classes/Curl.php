<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\classes;

use Yii;
use yii\web\ServerErrorHttpException;

class Curl {
  // 'GET,HEAD  users'      => 'user/index'   : return a list/overview/options of users
  // 'GET,HEAD  users/<id>' => 'user/view'    : return the details/overview/options of a user
  // 'POST      users'      => 'user/create'  : create a new user
  // 'PUT,PATCH users/<id>' => 'user/update'  : update a user
  // 'DELETE    users/<id>' => 'user/delete'  : delete a user
  // '          users/<id>' => 'user/options' : process all unhandled verbs of a user
  // '          users'      => 'user/options' : process all unhandled verbs of user collection

  const METHOD_GET        = 'GET';
  const METHOD_HEAD       = 'HEAD';
  const METHOD_POST       = 'POST';
  const METHOD_PUT        = 'PUT';
  const METHOD_PATCH      = 'PATCH';
  const METHOD_DELETE     = 'DELETE';
  const METHOD_OPTIONS    = 'OPTIONS';

  public $method;
  // public $api;
  public $url = null;
  public $urlParams = null;
  public $bodyParams = null;
  public $formFiles = null;
  public $options = null;
  private $isLocalApiServer = false;

  static function start($method, $url) : Curl
  {
    $Curl = new static();
    return $Curl->setAddress($method, $url);
  }

  function setAddress($method, $url) : Curl
  {
    if ((str_starts_with($url, 'http://') == false)
      && (str_starts_with($url, 'https://') == false)
    ) {
      if (empty(Yii::$app->params['apiServerAddress']))
        throw new ServerErrorHttpException('apiServerAddress is not defined');

      $this->isLocalApiServer = true;

      if (str_starts_with($url, '/'))
        $url = ltrim($url, '/');

      $apiServerAddress = Yii::$app->params['apiServerAddress'];
      if (str_ends_with($apiServerAddress, '/'))
        $apiServerAddress = rtrim($apiServerAddress, '/');

      $url = $apiServerAddress . '/' . $url;
    }

    $this->method = $method;
    $this->url = $url;
    // $this->baseUrl = $baseUrl;

    // if (str_starts_with($api, '/'))
    //   $api = ltrim($api, '/');

    // if (empty($this->baseUrl))
    //   $this->baseUrl = Yii::$app->params['apiServerAddress'];

    // if (str_ends_with($this->baseUrl, '/') == false)
    //   $this->baseUrl = $this->baseUrl . '/';

    return $this;
  }

  function setUrlParams($urlParams) : Curl {
    $this->urlParams = $urlParams;
    return $this;
  }

  function setBodParams($bodyParams) : Curl {
    $this->bodyParams = $bodyParams;
    return $this;
  }

  function setFormFiles($formFiles) : Curl {
    $this->formFiles = $formFiles;
    return $this;
  }

  function setOptions($options) : Curl {
    $this->options = $options;
    return $this;
  }

  function execute() {
    //curl object
    $CurlObject = curl_init();
    curl_setopt($CurlObject, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($CurlObject, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($CurlObject, CURLOPT_TIMEOUT, YII_ENV_DEV ? 60*5 : 10);

    if ($this->options && count($this->options)) {
      foreach ($this->options as $k => $v) {
        curl_setopt($CurlObject, $k, $v);
      }
    }

    //Url
    $Url = $this->url; //baseUrl . $this->api;

    //justForMe
    if ($this->isLocalApiServer && Yii::$app->isJustForMe)
      $this->urlParams['justForMe'] = 1;

    //Url params
    if (empty($this->urlParams) == false) {
      $UrlParamsParts = [];

      foreach ($this->urlParams as $k => $v) {
        if (is_array($v))
          $UrlParamsParts[] = $k . '=' . implode(',', $v);
        else if ($v == '')
          $UrlParamsParts[] = $k;
        else
          $UrlParamsParts[] = $k . '=' . (is_numeric($v) ? (int)$v : $v);
      }

      if (empty($UrlParamsParts) == false)
        $Url = $Url . '?' . implode('&', $UrlParamsParts);
    }

    curl_setopt($CurlObject, CURLOPT_URL, $Url);

    $postFields = $this->bodyParams;

    //body params
    if (empty($this->bodyParams) == false
      && (($this->method == self::METHOD_POST) //create
        || ($this->method == self::METHOD_PUT) //update
        || ($this->method == self::METHOD_PATCH) //update
    )) {
      $postFields = array_merge($postFields, $this->bodyParams);
    }

    //form files
    if (empty($this->formFiles) == false) {
      if (($this->method != self::METHOD_POST) //create
        && ($this->method != self::METHOD_PUT) //update
        && ($this->method != self::METHOD_PATCH) //update
      )
        throw new ServerErrorHttpException('form files only allowed with post,put or patch methods.');

      foreach ($this->formFiles as $fileParamID => $fileData) {
        $cfile = curl_file_create($fileData['tempFileName'], null, $fileData['fileName'] ?? null);

        $postFields = array_merge($postFields, [
          $fileParamID => $cfile,
        ]);
      }
    }

    if (empty($postFields) == false) {
      curl_setopt($CurlObject, CURLOPT_POST, true); //count($postFields));
      curl_setopt($CurlObject, CURLOPT_POSTFIELDS, $postFields);
    }

    //---------
    switch ($this->method) {
      case self::METHOD_GET:
      case self::METHOD_HEAD:
      case self::METHOD_PUT:
      case self::METHOD_PATCH:
      case self::METHOD_DELETE:
      case self::METHOD_OPTIONS:
        curl_setopt($CurlObject, CURLOPT_CUSTOMREQUEST, $this->method);
        break;

      case self::METHOD_POST:
        if (empty($postFields))
          curl_setopt($CurlObject, CURLOPT_POST, true);
        break;
    }

    //headers
    $headers = [];

    if ($this->isLocalApiServer) {
      $headers[] = 'Accept: application/json';
      // $headers[] = 'Content-Type: application/json';

      if (Yii::$app->request->headers->has('Authorization'))
        $headers[] = 'Authorization: ' . Yii::$app->request->headers->get('Authorization');
      else if (method_exists(Yii::$app->user, 'getJwtByCookie')) {
        // if (Yii::$app->request->cookies->has('token'))
        // $headers[] = 'Authorization Bearer ' . Yii::$app->request->cookies->get('token');
        $jwt = Yii::$app->user->getJwtByCookie();
        if ($jwt !== null) {
          $headers[] = 'Authorization: Bearer ' . $jwt;
        }
      }
    }

    $b = curl_setopt($CurlObject, CURLOPT_HTTPHEADER, $headers);

    //execute
    $response = curl_exec($CurlObject);
    // die('>>' . var_dump($res) . '<<');

    if ($response === false) {
      $statusCode = (-1) * curl_errno($CurlObject);
      $response = [
        'message' => curl_error($CurlObject),
      ];
    } else {
      $statusCode = curl_getinfo($CurlObject, CURLINFO_RESPONSE_CODE);

      //json null
      if (strcasecmp($response, 'null') == 0)
        $response = null;

      //convert $response string to json array
      if (empty($response) == false) {
        $org = $response;
        $response = json_decode($response, true);
        if ($response === null) {
          $response = [
            'message' => $org,
          ];
        }
      }
    }

    //close connection
    curl_close($CurlObject);

    return [$statusCode, $response];
  }

}
