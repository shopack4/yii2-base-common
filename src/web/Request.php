<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\web;

class Request extends \yii\web\Request
{
	public function init()
	{
		parent::init();

		//just for non POST requests:
		if (empty($this->parsers['multipart/form-data']))
			$this->parsers['multipart/form-data'] = \yii\web\MultipartFormDataParser::class;
	}

	/**
	 * $key: string|array
	 */
	public static function GetOrPost($key, $def=null)
	{
		// php 7:
		// return $_POST[$key] ?? $_GET[$key] ?? $def;

		if (!is_array($key))
			$key = [$key];

		foreach ($key as $k)
		{
			if (isset($_POST[$k]))
				return $_POST[$k];

			if (isset($_GET[$k]))
				return $_GET[$k];
		}

		return $def;
	}

}
