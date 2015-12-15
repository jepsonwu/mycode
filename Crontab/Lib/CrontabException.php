<?php
use Lib\Log;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:17
 */
class CrontabException
{
	public function __construct()
	{
		//异常处理机制
		set_exception_handler(array($this, "ExceHandler"));

		//错误处理机制
		defined("IS_DAEMON") && set_error_handler(array($this, "ErrorHandler"));
	}

	public function ExceHandler($exception)
	{
		Log::Log($exception->getMessage(), Log::LOG_EXIT);
	}

	public function ErrorHandler($errno, $errstr, $errfile, $errline)
	{
		$info = "Time:" . date("Y-m-d H:i:s") . "\n" . $this->FriendErrorType($errno) . ":{$errstr}\n";
		$info .= "File:{$errfile},line:{$errline}\n";
		$info .= "\n";
		file_put_contents(LOG_PATH . "error.log", $info, FILE_APPEND);
	}

	private function FriendErrorType($type)
	{
		switch ($type) {
			case E_ERROR: // 1 //
				return 'ERROR';
			case E_WARNING: // 2 //
				return 'WARNING';
			case E_PARSE: // 4 //
				return 'PARSE';
			case E_NOTICE: // 8 //
				return 'NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'CORE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'USER_DEPRECATED';
		}
		return "";
	}
}
