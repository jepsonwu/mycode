<?php
namespace Lib;
/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-26
 * Time: 下午4:14
 */
class String
{
	/**
	 * 字符串转int
	 * @param $string
	 * @return int
	 */
	static public function stringToInt($string)
	{
		$int = 0;
		for ($i = 0; $i < strlen($string); $i++)
			$int += ord($string{$i});

		return $int + 4000000000;
	}
}