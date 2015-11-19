<?php
namespace Lib;

use Lib\Log;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:17
 */
class Exception extends \Exception
{
	public function __construct()
	{
		set_exception_handler(array($this, "ExceHandler"));

		//错误处理机制
		//set_error_handler
	}

	public function ExceHandler($exception = null)
	{
		Log::Log($exception->getMessage(), Log::LOG_ERROR);
	}
}
