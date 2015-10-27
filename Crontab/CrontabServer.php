<?php
use Lib\Ipc;

/**
 *crontab server
 * 1.事件监听，包括服务和进程事件监听
 * 2.任务多进程
 * 3.进程间通信(system v:shmop 共享内存段<大小和权限限制>,queue 队列,sem 信号量)
 * 4.日志记录
 * 5.用户权限
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:07
 */
class CrontabServer
{
	//版本号
	private $version = 1.0;

	//运行模式
	private $is_deaemon = false;

	//任务栈
	private $tasks = array();

	//当前可执行任务

	//任务栈自动更新时间 凌晨
	private $tasks_update_time = "00:00";

	/**
	 * 开启服务
	 */
	public function Start()
	{
		//模式判断
		if (php_sapi_name() != "cli")
			exit("the mode must be cli");

		//判断扩展
		foreach (array("pcntl", "posix") as $exten) {
			!extension_loaded($exten) && exit("{$exten} extension is not loaded");
		}

		//用户判断

		//获取参数
		$this->Params();

		//开启服务 是否后台模式
		if ($this->is_deaemon) {
			//fork todo 
		} else {
			//获取终端命令
			$this->Run();
		}
	}

	private function Init()
	{
		//autoload
		require_once "./Autoloader.php";

		//时区
		date_default_timezone_set("PRC");

		//多字节设置
		mb_internal_encoding("UTF-8");

		//定义基本常量
		!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");
		!defined("COMMON_PATH") && define("COMMON_PATH", __DIR__ . "/Common/");

		//function
		require_once COMMON_PATH . "Function.php";

		//配置文件
		C(include(COMMON_PATH . "Conf.php"));

		//设置异常处理
		set_exception_handler("ExceHandler");
	}

	private function Run()
	{
		//time
		define("START_TIME", time());

		//初始化应用
		$this->Init();

		$this->RestStd();

		//实力化进程通信
		$ipc = Ipc::getInstance();

		//fork进程 解析task
		$pid = pcntl_fork();
		switch ($pid) {
			case -1:
				exit("fork error");
				break;
			case 0:
				pcntl_exec("/usr/bin/php", array("/data0/hapigou/index.php", "Crontab/CrontabServer/GetTask"));
				break;
			default:
				pcntl_waitpid($pid, $status);
				var_dump($status);
				break;
		}

		//获取列表
		var_dump($ipc->read(START_TIME));
		exit;
		$time = time();
		$str = "sadfasdfs";
		var_dump($ipc->write($time, $str, strlen($str)));
		var_dump($ipc->read($time));
		var_dump($ipc->delete($time));


		//一分钟轮询一次
		$i = 0;
		do {
			//fork 任务进程  非阻塞模式 通过信号方式处理执行结果

			$pid = pcntl_fork();
			switch ($pid) {
				case -1:
					exit("fork error");
					break;
				case 0:
					pcntl_exec("/usr/bin/php", array("/data0/hapigou/index.php", "Crontab/CrontabServer/GetTask"));
					break;
				default:
					pcntl_waitpid($pid, $status);
					var_dump($status);
					break;
			}
			//睡眠
			//sleep(20);
			exit;
			$i++;
		} while ($i < 5);
	}

	/**
	 * 获取当前可执行任务
	 */
	public function GetTask()
	{
		echo "aaa";
	}

	/**
	 * 判断是否可以刷新任务栈
	 * 1.服务启动或者重起
	 * 2.服务自动更新
	 * 3.人为更新
	 */
	private function IsRefreshStack()
	{

	}

	/*
	 * 任务出栈
	 */
	private function PopStack()
	{

	}

	/**
	 * 任务入栈
	 */
	private function PushStack()
	{

	}

	/**
	 * fork 任务进程
	 */
	private function ForkTask()
	{

	}

	/**
	 * 错误日志
	 * @param $msg
	 * @param string $type
	 */
	private function Error($msg, $type = "ERROR")
	{
		//$info=debug_backtrace();

		echo $type . $msg;
	}

	/**
	 * 重定向标准输出
	 * $str = fread(STDIN, 100);
	 * echo $str;
	 */
	private function RestStd()
	{
		$file = BASE_PATH . "Log/" . C("LOG_FILE");
		if (is_file($file) && is_writable($file)) {
			fclose(STDOUT);
			fclose(STDERR);

			$STDOUT = fopen($file, "a");
			$STDERR = fopen($file, "a");
		}
	}

	/**
	 * 接收参数
	 * 第一个参数
	 * start|stop|restart|reload|status -h|-v|-V pid(子进程ID) -e
	 * 第二个参数
	 * -d stop|restart|status
	 */
	private function Params()
	{
		if (isset($_SERVER['argv'][1])) {
			switch ($_SERVER['argv'][1]) {
				case "start":
					//demo
					if (isset($_SERVER['argv'][2])) {
						switch ($_SERVER['argv'][2]) {
							case "-d":
								$this->is_deaemon = true;
								break;
							case "task":
								$this->GetTask();
								break;
						}
					}
					break;
				case "stop":
					break;
				case "restart":
					break;
				case "reload":
					break;
				case "status":
					break;
				case "-v":
					echo $this->version . "\n";
					exit;
					break;
				case "-V"://cat readme
					break;
				case "-h":
					$this->Help();
					break;
				case is_numeric($_SERVER['argv'][1]);//子进程事件
					//判断进程有效性

					//事件
					if (isset($_SERVER['argv'][2])) {
						switch ($_SERVER['argv'][2]) {
							case "stop":
								break;
							case "restart":
								break;
							case "status":
								break;
							default:
								$this->Help();
								break;
						}
					}
					break;
				default:
					$this->Help();
					break;
			}
		}
	}

	/**
	 * 输出帮助
	 */
	private function Help()
	{
		echo "Usage: php start.php [options]\n
	start   start server\n
		-d  by demo\n
	stop    stop server\n
	restart restart server\n
	reload  reload server tasklist\n
	start   show status about server\n
	-v  show version\n
	-V  show more information\n
	-h  show help\n
	pid process pid\n
		stop    stop process\n
		restart restart process\n
		status  show status about process\n";
		exit;
	}
}