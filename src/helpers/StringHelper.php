<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\helpers;

use Yii;

class StringHelper extends \yii\helpers\StringHelper
{
	public static function fixPersianCharacters($str)
	{
		if (empty($str))
			return $str;

		$map = [
			// '۰' => '0',
			// '۱' => '1',
			// '۲' => '2',
			// '۳' => '3',
			// '۴' => '4',
			// '۵' => '5',
			// '۶' => '6',
			// '۷' => '7',
			// '۸' => '8',
			// '۹' => '9',
			'{}' => '‌', //ZWNJ
			'>>' => '»',
			'<<' => '«',
			'ى'	=> 'ی',
			// 'ئ'	=> 'ی',
			'ي' => 'ی',
			'ك' => 'ک',
		];

		foreach ($map as $k => $v) {
			$str = str_replace($k, $v, $str);
		}

		return $str;
	}

	// public static function startsWith($haystack, $needle)
	// {
		// return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
	// }
	// public static function endsWith($haystack, $needle)
	// {
		// return substr_compare($haystack, $needle, -strlen($needle)) === 0;
	// }

	public static function strrtrim($message, $strip)
	{
		// break message apart by strip string
		$lines = explode($strip, $message);
		$last  = '';
		// pop off empty strings at the end
		do {
			$last = array_pop($lines);
		} while (empty($last) && (count($lines)));
		// re-assemble what remains
		return implode($strip, array_merge($lines, array($last)));
	}

	public static function generateRandomId($length=32, $prefix='a')
	{
		if (empty($prefix))
			return preg_replace('/[^a-z0-9_]/i', '_', Yii::$app->security->generateRandomString($length));

		return $prefix . preg_replace('/[^a-z0-9_]/i', '_', Yii::$app->security->generateRandomString($length - strlen($prefix)));
	}

	public static function convertToJsVarName($varName)
	{
		if (empty($varName))
			return null;

		return preg_replace('/[^a-z0-9_]/i', '_', $varName);
	}

}
