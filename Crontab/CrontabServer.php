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
	private $is_demo = false;

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

		//获取参数
		$this->Params();

		//开启服务 是否后台模式
		if ($this->is_demo) {
			//fork
		} else {
			$this->Run();
		}
	}

	/**
	 * 接收参数
	 * 第一个参数
	 * start|stop|restart|reload|status -h|-v|-V pid(子进程ID)
	 * 第二个参数
	 * -d stop|restart|status
	 */
	private function Params()
	{
		if (isset($_SERVER['argv'][1])) {
			switch ($_SERVER['argv'][1]) {
				case "start":
					//demo
					if (isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] == '-d')
						$this->is_demo = true;
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

	private function Init()
	{
		//autoload
		require_once "./Autoloader.php";

		//定义基本常量
		!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");
	}

	private function Run()
	{
		//初始化应用
		$this->Init();

		while (true) {

		}
//		$time = time();
//		$str = "sadfasdfs";
//		$ipc = Ipc::getInstance();
//		var_dump($ipc->write($time, $str, strlen($str)));
//		var_dump($ipc->read($time));
//		var_dump($ipc->delete($time));
//
//		var_dump($ipc->read($time));
//		//开始时间
//		!defined("START_TIME") && define("START_TIME", time());

		//一分钟轮询一次
//		$i = 0;
//		do {
//			//fork 任务进程  非阻塞模式 通过信号方式处理执行结果
//
//			$pid = pcntl_fork();
//			switch ($pid) {
//				case -1:
//					exit("fork error");
//					break;
//				case 0:
//					pcntl_exec("/usr/bin/php", array("/data0/hapigou/index.php", "Crontab/CrontabServer/GetTask"));
//					break;
//				default:
//					pcntl_waitpid($pid, $status);
//					var_dump($status);
//					break;
//			}
//			//睡眠
//			//sleep(20);
//			exit;
//			$i++;
//		} while ($i < 5);
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
	}
}