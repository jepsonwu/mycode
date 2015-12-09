<?php
use Lib\Ipc\Shmop;
use Lib\Log;
use Lib\Conf;
use Core\Core;

/**
 *crontab server
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:07
 */
class CrontabServer
{
	//版本号
	private $version = 1.0;

	//默认最大可执行多进程数
	private $multi_process = 4;

	//默认需要的内存
	private $memory_available = 2;

	//server pid file
	private $server_pid_file = "";

	//server start time key
	private $server_start_time_key = "Crontab Server Start Time";

	/**
	 * 开启服务
	 */
	public function Start()
	{
		//init
		$this->Init();
		//获取参数
		$this->Params();
		//系统资源检测
		$this->SystemCheck();
		//注册信号处理函数
		$this->RegisterServerSignal();
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

		//扩展
		foreach (array("pcntl", "posix", "sysvmsg", "shmop", "libevent") as $exten)
			!extension_loaded($exten) && exit("{$exten} extension is not found");

		//操作系统  目前只支持linux  有很多命令需要处理
		//todo 内核检测
		$posix_uname = posix_uname();
		!in_array($posix_uname['sysname'], array("Linux")) &&
		exit("{$posix_uname['sysname']} operating system is not supported");

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
		Conf::getInstance()->setConfig(include(COMMON_PATH . "Conf.php"));

		//异常处理机制
		new CrontabException();

		//pid file
		$this->server_pid_file = LOG_PATH . "pid";
	}

	/**
	 * 开启服务所需的资源检查
	 */
	private function SystemCheck()
	{
		//进程可用内存
		exec("free|awk -F' ' '{print $7}'", $sys_memory_available);
		$memory_available = intval(Conf::getInstance()->getConfig("MEMORY_AVAILABLE"));
		$memory_available > 0 && $this->memory_available = $memory_available;
		if ($sys_memory_available[1] < $this->memory_available * 1024 * 1024)
			Log::Log("There is out of memory", Log::LOG_EXIT);

		//todo how to check available shared memory on linux

		//多进程数处理
		exec("cat /proc/cpuinfo |grep processor|wc -l", $multi_process);
		$multi_process_define = intval(Conf::getInstance()->getConfig("MULTI_PROCESS"));
		$multi_process_define > 0 && $this->multi_process = $multi_process_define;
		if ($multi_process[0] < $this->multi_process)
			Log::Log("Total number of multi-process set beyond the server CPU logic", Log::LOG_WARNING);

	}

	/**
	 * 注册信号处理机制
	 */
	private function RegisterServerSignal()
	{
		\Core\Signal::getInstance()->registerServerSignal();
	}

	/**
	 * 是否在运行
	 */
	private function IsRuning()
	{
		$pid = $this->GetServerPid();
		if ($pid && posix_kill($pid, 0))
			return $pid;

		return false;
	}

	/**
	 * 运行服务
	 */
	private function Run()
	{
		//记录开始时间
		Shmop::getInstance()->write($this->server_start_time_key, time());

		//第一次获取任务
		Core::getInstance()->coreFork("taskPush");
		exit;
		//开启服务 是否后台模式
		Log::Log("Start crontab server,pid:" . posix_getpid(), Log::LOG_INFO);
		if (defined("IS_DAEMON")) {
			$pid = Core::getInstance()->coreFork("crontabServer");

			if ($pid)
				$this->SaveServerPid($pid);
			else
				Log::Log("Start crontab server failed", Log::LOG_EXIT);
		} else {
			//获取终端命令
			$crontab = new Core();
			$crontab->crontabServer();
		}
	}

	/**
	 * 保存服务PID
	 * @param $pid
	 */
	private function SaveServerPid($pid)
	{
		file_put_contents($this->server_pid_file, $pid);
	}

	/**
	 * 获取服务PID
	 * @return bool|string
	 */
	private function GetServerPid()
	{
		if (is_file($this->server_pid_file)) {
			$pid = file_get_contents($this->server_pid_file);

			return intval($pid);
		}

		return false;
	}

	/**
	 * 停止服务
	 * @return bool
	 */
	private function Stop()
	{
		$pid = $this->GetServerPid();
		if ($pid) {
			//这里如果没成功是否要发送sigkill信号
			posix_kill($pid, SIGINT);
			echo "Stop crontab server success";

			return true;
		} else {
			echo "Stop crontab server failed";
		}

		return false;
	}

	/**
	 * 重启
	 */
	private function ReStart()
	{
		if ($this->Stop()) {
			//系统资源检测
			$this->SystemCheck();
			//注册信号处理函数
			$this->RegisterServerSignal();
			//设置闹钟
			$this->SetAlarm();
			//运行
			$this->Run();
		}
	}

	/**
	 * 重新载入配置
	 * 重新载入任务列表和事件
	 */
	private function ReLoad()
	{

	}

	/**
	 * 输出状态
	 * 进程信息 运行时长  内存 任务数
	 * 后期统计
	 */
	private function Status()
	{
		//检查进程状态
		$pid = $this->IsRuning();
		if ($pid) {
			$status = "Server is runing,pid:{$pid}\n";

			//总运行时间
			$start_time = Shmop::getInstance()->read($this->server_start_time_key);
			$start_time = time() - $start_time;
			//todo 根据时间格式成天，时，分
			$status .= "Server runing total time:{$start_time}\n";

			//服务器内存

			//待执行的任务数
			$task_count = \Lib\Ipc\Queue::getInstance()->getCount();
			$status .= "Waiting to exec task:{$task_count}\n";
			//正在执行的子进程数
		} else
			$status = "Server is not runing\n";

		echo $status;
		exit;
	}

	/**
	 * 子进程事件
	 * todo 调整优先级
	 *
	 */
	private function TaskEvent($pid, $event)
	{
		//判断是否属于自己的子进程
		switch ($event) {
			case "stop":
				break;
			case "restart":
				break;
			case "status":
				break;
		}
	}

	/**
	 * 守护进程
	 */
	private function IsDaemon()
	{
		!defined("IS_DAEMON") && define("IS_DAEMON", true);
		Core::getInstance()->restStd();
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
					if ($this->IsRuning())
						exit("Server is runing");

					isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] == "-d" && $this->IsDaemon();
					break;
				case "stop":
					$this->Stop();
					exit;
					break;
				case "restart":
					$this->ReStart();
					exit;
					break;
				case "reload":
					$this->ReLoad();
					exit;
					break;
				case "status":
					$this->Status();
					exit;
					break;
				case "-v":
					exit("Crontab version:" . $this->version . "\n");
					break;
				case "-V":
					exit(file_get_contents("README.md"));
					break;
				case "-h":
					$this->Help();
					break;
				case is_numeric($_SERVER['argv'][1]);//子进程事件
					if (isset($_SERVER['argv'][2])) {
						switch ($_SERVER['argv'][2]) {
							case "stop":
							case "restart":
							case "status":
								$this->TaskEvent($_SERVER['argv'][1], $_SERVER['argv'][2]);
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
		} else {
			$this->Help();
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


	//	/**
//	 * 运行单个任务
//	 */
//	private function SapiRun()
//	{
//		$info = ",info:" . implode(" ", $_SERVER['argv']);
//		$_SERVER['argc'] < 2 && Log::Log("Task is not found{$info}", Log::LOG_ERROR);
//
//		//控制器
//		$action = substr($_SERVER['argv'][1], strrpos($_SERVER['argv'][1], "/") + 1);
//		$controller = substr($_SERVER['argv'][1], 0, strrpos($_SERVER['argv'][1], "/"));
//		$controller = "{$controller}";
//
//		!is_file("{$controller}.php") && Log::Log("Task controller is not found{$info}", Log::LOG_ERROR);
//		define("CONTROLLER", substr($controller, strrpos($controller, "/") + 1));
//
//		//方法
//		$controller = "\\" . str_replace("/", "\\", $controller);
//		$class = new ReflectionClass($controller);
//		$obj = $class->newInstance();
//
//		//定义常量
//		!method_exists($obj, $action) && Log::Log("Task action is not found{$info}", Log::LOG_ERROR);
//		define("ACTION", $action);
//
//		//参数
//		if (isset($_SERVER['argv'][2])) {
//			$param = explode("/", $_SERVER['argv'][2]);
//			if ($param)
//				for ($i = 0; $i < count($param); $i = $i + 2)
//					isset($param[$i]) && isset($param[$i + 1]) && $_REQUEST[$param[$i]] = $param[$i + 1];
//		}
//
//		//运行
//		$invoke = $class->getMethod($action);
//		$invoke->invoke($obj);
//	}
}