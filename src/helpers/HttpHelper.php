<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\helpers;

use Yii;
use shopack\base\common\classes\Curl;

class HttpHelper
{
  const METHOD_GET     = 'GET';
  const METHOD_HEAD    = 'HEAD';
  const METHOD_POST    = 'POST';
  const METHOD_PUT     = 'PUT';
  const METHOD_PATCH   = 'PATCH';
  const METHOD_DELETE  = 'DELETE';
  const METHOD_OPTIONS = 'OPTIONS';

  static function callApi(
    $url,
    $method = Curl::METHOD_GET,
    $urlParams = [],
    $bodyParams = [],
    $formFiles = [],
    $options = []
  ) {
    $curl = Curl::start($method, $url)
      ->setUrlParams($urlParams)
      ->setBodParams($bodyParams)
      ->setFormFiles($formFiles)
      ->setOptions($options)
    ;

    list ($resultStatus, $response) = $curl->execute();
    $resultData = [];

    /*
      $response:
      {
        "name": "Unauthorized",
        "message": "{\"0\":\"THE_WAITING_TIME_HAS_NOT_ELAPSED\",\"ttl\":67,\"remained\":\"1:7\"}",
        "code": 0,
        "status": 401,
        "type": "yii\\web\\HttpException"
      }

      out:
        $status = 401
        $result = [
          "message": "THE_WAITING_TIME_HAS_NOT_ELAPSED"
          "ttl": 120,
          "remained": "2:0"
        ]
    */
    if ($resultStatus < 200 || $resultStatus >= 300) {
      if (isset($response['message'])) {
        $json = json_decode($response['message'], true);
        if ($json === null)
          $resultData = [
            'message' => $response['message']
          ];
        else
          $resultData = [
            'message' => $json
          ];
      } else {
        $resultData = [
          'message' => 'UNKNOWN_ERROR',
        ];
      }
    }

    /*
      $response:
      {
        "message": {
          "0": "CODE_SENT",
          "ttl": 120,
          "remained": "2:0"
        }
      }

      out:
        $status = 200 .. 299
        $result = [
          "message": "CODE_SENT"
          "ttl": 120,
          "remained": "2:0"
        ]
    */
    else if (isset($response['message'])) {
      $message = (array)$response['message'];
      unset($response['message']);

      $resultData = [
        'message' => array_shift($message),
      ];

      if (empty($message == false))
        $resultData = array_merge($resultData, $message);

      if (empty($response) == false)
        $resultData = array_merge($resultData, $response);
    }

    /*
      $response:
      {
        totalCount: 100,
        rows: [
          {
            "gtwID": 1,
            "gtwName": "asanak 1",
          },
          {
            "gtwID": 2,
            "gtwName": "asanak 2",
          },
        ]
      }

      out:
        $status = 200 .. 299
        $result = [
          'totalCount': 100,
          'rows': [
            {
              "gtwID": 1,
              "gtwName": "asanak 1",
            },
            {
              "gtwID": 2,
              "gtwName": "asanak 2",
            },
          ],
        ],
      ]
    */
    else if (isset($response['rows'])) {
      $resultData = $response;
    }

    else if (isset($response['data'])) {
      $resultData = $response;
    }

    else
      $resultData = $response;

    return [$resultStatus, $resultData];
  }

}

  // public static function formatResultMessage($message)
  // {
  //   $fnFormat = function($message) {
  //     if (is_array($message) == false)
  //       $message = json_decode($message, true);

  //     if (is_array($message)) {
  //       $messageText = array_shift($message);
  //       return [Yii::t('aaa', $messageText, $message), $message];
  //     }

  //     return [$message, []];
  //   };

  //   $resultStatus = 200;
  //   $resultMessage = '';
  //   $resultInfo = [];

  //   if (isset($message['error']['message'])) {
  //     $resultStatus = $message['error']['status'] ?? -1;
  //     [$resultMessage, $resultInfo] = $fnFormat($message['error']['message']);
  //   }
  //   else if (isset($message['message'])) {
  //     $resultStatus = $message['status'] ?? 200;
  //     [$resultMessage, $resultInfo] = $fnFormat($message['message']);
  //   }
  //   else
  //     $resultMessage = $message;

  //   return [$resultStatus, $resultMessage, $resultInfo];
  // }
