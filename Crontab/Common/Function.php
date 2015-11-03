<?php
/**
 * 公共函数库
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:53
 */
//日志级别
const LOG_EXIT = "Exit";
const LOG_ERROR = "Error";
const LOG_WARNING = "Warning";
const LOG_INFO = "Info";

/**
 * Fork 进程
 * @param $commond
 * @param $argv
 * @return int
 */
function Fork($commond, $argv)
{
	$pid = pcntl_fork();
	switch ($pid) {
		case -1:
			Logs("Fork process field.commond:{$commond},argv:" . json_encode($argv));
			break;
		case 0:
			pcntl_exec($commond, $argv);
			break;
		default:
			pcntl_waitpid($pid, $status);
			return $pid;
			break;
	}

	return "";
}

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
function Logs($msg, $type = LOG_ERROR)
{
	$info = "Time:" . date("Y-m-d H:i:s") . "\n{$type}:{$msg}\n";
	if ($type != LOG_INFO) {
		$debug_info = debug_backtrace();
		$info .= "Function:{$debug_info[1]['function']},Line:{$debug_info[1]['line']}\n\n\n";
	}

	echo $info;
	$type == LOG_EXIT && exit();
}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
function C($name = null, $value = null, $default = null)
{
	static $_config = array();
	// 无参数时获取所有
	if (empty($name)) {
		return $_config;
	}
	// 优先执行设置获取或赋值
	if (is_string($name)) {
		if (!strpos($name, '.')) {
			$name = strtoupper($name);
			if (is_null($value))
				return isset($_config[$name]) ? $_config[$name] : $default;
			$_config[$name] = $value;
			return true;
		}
		// 二维数组设置和获取支持
		$name = explode('.', $name);
		$name[0] = strtoupper($name[0]);
		if (is_null($value))
			return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
		$_config[$name[0]][$name[1]] = $value;
		return true;
	}
	// 批量设置
	if (is_array($name)) {
		$_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
		return true;
	}
	return null; // 避免非法参数
}

function ExceHandler($exception)
{
	echo $exception->getMessage();
}