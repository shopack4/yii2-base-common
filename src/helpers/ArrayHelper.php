<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
	public static function findFirst($array, $callback, $checkRecursiveCallback=null)
	{
		foreach ($array as $k => $v)
		{
			if ($checkRecursiveCallback && call_user_func($checkRecursiveCallback, $v, $k))
			{
				$found = static::findFirst($v, $callback, $checkRecursiveCallback);
				if ($found)
					return $found;
			}
			else
			{
				if (call_user_func($callback, $v, $k))
					return $v;
			}
		}
		return null;
	}

	public static function mergeRecursive($a, $b)
	{
		$args = func_get_args();
		// $res = array_shift($args);
		return array_merge_recursive($args);
	}

	public static function FilterRecursive($array)
	{
		$original = $array;
		$array = array_filter($array);
		$array = array_map(function ($e) {
			return is_array($e) ? static::FilterRecursive($e) : $e;
		}, $array);
		return $original === $array ? $array : static::FilterRecursive($array);
	}

	public static function getValueDeep($array, $key, $default = null)
	{
		foreach ($array as $k => $v)
		{
			if ($k == $key)
				return $v;

			if (is_array($v))
			{
				$f = static::getValueDeep($v, $key, $default);
				if ($f !== null)
					return $f;
			}
		}
		return $default;
	}

	public static function ltrimKey(&$arr, $chars, $deepLevel=1)
	{
		if (empty($arr))
			return;

		$l = strlen($chars);
		$a = [];
		foreach ($arr as $k => $v)
		{
			if (($deepLevel > 1) && is_array($v))
				static::ltrimKey($v, $chars, $deepLevel-1);

			if (substr($k, 0, $l) == $chars)
				$a[substr($k, $l)] = $v;
			else
				$a[$k] = $v;
		}
		$arr = $a;
	}

	public static function parseCommands($arr)
	{
		$command = null;
		$params = [];
		foreach ($arr as $key => $val)
		{
			if ($command === null)
			{
				if ($key === 0)
				{
					$command = $val;
				}
				else
				{
					$command = $key;
					$params[$key] = $val;
				}
			}
			else
			{
				if (is_numeric($key))
				{
					$params[$val] = true;
				}
				else
				{
					$params[$key] = $val;
				}
			}
		}
		return [$command, $params];
	}

	public static function merge_recursive_distinct()
	{
		$args = func_get_args();
		return static::merge_recursive_distinct_arrays($args);
	}
	public static function merge_recursive_distinct_arrays($arrays)
	{
		$result = [];
		foreach ($arrays as $array)
		{
			foreach ($array as $key => $value)
			{
				if (is_array($value) && isset($result[$key]) && is_array($result[$key]))
					$result[$key] = static::merge_recursive_distinct_arrays([$result[$key], $value]);
				else if (is_numeric($key))
				{
					if (!in_array($value, $result))
						$result[] = $value;
				}
				else
					$result[$key] = $value;
			}
		}
		return $result;
	}
			// if (is_array($value) && isset($result[$key]) && is_array($result[$key]))
			// {
				// $result[$key] = static::merge_recursive_distinct($result[$key], $value);
			// }
			// else
			// {
				// $result[$key] = $value;
			// }

	public static function multiDimentionalConcat($array)
	{
		if (empty($array))
			return [];
		$out = [];
		$el0 = array_shift($array);
		foreach($el0 as $item)
		{
			if (empty($array))
				$out[] = [$item];
			else
			{
				$next = static::multiDimentionalConcat($array);
				foreach ($next as $n)
				{
					$out[] = array_merge([$item], $n);
				}
			}
		}
		return $out;
	}

	public static function implodeKeyVal($seperator, $array, $keydelimiter=': ')
	{
		$out = [];
		foreach ($array as $k => $v)
		{
			if (empty($v))
				continue;

			if (is_array($v))
				$v = self::implodeKeyVal($seperator, $v, $keydelimiter);
			if (is_numeric($k))
				$out[] = $v;
			else
				$out[] = "{$k}{$keydelimiter}{$v}";
		}
		return implode($seperator, $out);
	}

}
