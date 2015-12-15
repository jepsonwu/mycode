<?php
use Lib\Conf;
use Lib\Log;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-9
 * Time: 下午4:05
 */

/**
 * 运行单个任务
 */
class Cli
{
	private $_conf;

	/**
	 * 开启服务
	 */
	public function Start()
	{
		//init
		$this->Init();
		//运行
		$this->Run();
	}

	/**
	 * 初始化
	 */
	private function Init()
	{
		//用户
		if (posix_getuid() !== 0)
			exit("Not allowed user,must be root");

		//模式
		php_sapi_name() != "cli" && exit("Not allowed mode");

		//php 版本控制
		version_compare(phpversion(), "5.4.0", "<") && exit("Php version is to low");

		//时区
		date_default_timezone_set("PRC");

		//多字节设置
		mb_internal_encoding("UTF-8");

		//错误级别设置
		error_reporting(-1);

		//定义基本常量
		!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");
		!defined("COMMON_PATH") && define("COMMON_PATH", BASE_PATH . "Common/");
		!defined("LIB_PATH") && define("LIB_PATH", BASE_PATH . "Lib/");
		!defined("LOG_PATH") && define("LOG_PATH", BASE_PATH . "Log/");
		!defined("CORE_PATH") && define("CORE_PATH", BASE_PATH . "Core/");

		//autoload
		require_once BASE_PATH . "Lib/Autoload.php";
		new Autoload();

		//配置文件
		$conf_file = COMMON_PATH . "Application.php";
		if (is_file($conf_file) && is_readable($conf_file))
			$this->_conf = Conf::getInstance(include($conf_file));
		else
			exit("Configure file is not found");

		//异常处理机制
		new CrontabException();
	}

	private function Run()
	{
		$info = ",info:" . implode(" ", $_SERVER['argv']);
		$_SERVER['argc'] < 2 && Log::Log("Task is not found{$info}", Log::LOG_ERROR);

		//控制器
		$action = substr($_SERVER['argv'][1], strrpos($_SERVER['argv'][1], "/") + 1);
		$controller = substr($_SERVER['argv'][1], 0, strrpos($_SERVER['argv'][1], "/"));
		$controller = $this->_conf['APPLICATION'] . "/{$controller}";

		!is_file("{$controller}.php") && Log::Log("Task controller is not found{$info}", Log::LOG_ERROR);
		define("CONTROLLER", substr($controller, strrpos($controller, "/") + 1));

		//方法
		$controller = "\\" . str_replace("/", "\\", $controller);
		$class = new ReflectionClass($controller);
		$obj = $class->newInstance();

		//定义常量
		!method_exists($obj, $action) && Log::Log("Task action is not found{$info}", Log::LOG_ERROR);
		define("ACTION", $action);

		//参数
		if (isset($_SERVER['argv'][2])) {
			$param = explode("/", $_SERVER['argv'][2]);
			if ($param)
				for ($i = 0; $i < count($param); $i = $i + 2)
					isset($param[$i]) && isset($param[$i + 1]) && $_REQUEST[$param[$i]] = $param[$i + 1];
		}

		//运行
		$invoke = $class->getMethod($action);
		$invoke->invoke($obj);
	}
}