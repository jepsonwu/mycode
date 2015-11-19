<?php
namespace Lib;
/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:18
 */

class Log
{
	//日志级别
	const LOG_EXIT = "Exit";
	const LOG_ERROR = "Error";
	const LOG_WARNING = "Warning";
	const LOG_INFO = "Info";

	/**
	 * 错误日志
	 * 日志级别
	 * Exit 程序终止
	 * Error 错误
	 * Warning 警告
	 * Info 信息
	 * @param $msg
	 * @param int|string $type
	 */
	static function Log($msg, $type = self::LOG_ERROR)
	{
		$info = "Time:" . date("Y-m-d H:i:s") . "\n{$type}:{$msg}\n";
		if ($type != LOG_INFO) {
			$debug_info = debug_backtrace();

			$info .= "File:" . substr($debug_info[1]['file'], strrpos($debug_info[1]['file'], "/") + 1, -4);
			$info .= ",Function:{$debug_info[1]['function']},Line:{$debug_info[0]['line']}\n\n\n";
		}

		echo $info;
		$type == self::LOG_EXIT && exit();
	}
}