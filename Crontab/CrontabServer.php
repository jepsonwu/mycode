<?php
use Lib\Ipc\Shmop;
use Lib\Log;
use Lib\Conf;
use Lib\Pcntl;
use Lib\Task;

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

	//运行模式
	private $is_daemon = false;

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
		//基础检测
		$this->Check();
		//获取参数
		$this->Params();
		//init
		$this->Init();
		//是否在运行
		$this->IsRuning();
		//系统资源检测
		$this->SystemCheck();
		//注册信号处理函数
		$this->RegisterSignal();
		//运行
		$this->Run();
	}

	/**
	 * sapi入口
	 */
	public function Sapi()
	{
		//基础检测
		$this->Check();
		//init
		$this->Init();
		//run
		$this->SapiRun();
	}

	/**
	 * 基本检测
	 */
	private function Check()
	{
		//模式
		php_sapi_name() != "cli" && exit("Not allowed mode");

		//扩展
		foreach (array("pcntl", "posix", "sysvmsg", "shmop") as $exten)
			!extension_loaded($exten) && exit("{$exten} extension is not found");

		//操作系统  目前只支持linux  有很多命令需要处理
		$posix_uname = posix_uname();
		!in_array($posix_uname['sysname'], array("Linux")) &&
		exit("{$posix_uname['sysname']} operating system is not supported");

		//php 版本控制
		version_compare(phpversion(), "5.4.0", "<") && exit("Php version is to low");
	}

	/**
	 * 初始化
	 */
	private function Init()
	{
		//时区
		date_default_timezone_set("PRC");

		//多字节设置
		mb_internal_encoding("UTF-8");

		//错误级别设置
		if ($this->is_daemon)
			error_reporting(0);
		else
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
		new Crontab_Exception();

		//daemon
		$this->is_daemon && $this->RestStd();

		//pid file
		$this->server_pid_file = LOG_PATH . "pid";
	}

	/**
	 * 开启服务所需的资源检查
	 */
	private function SystemCheck()
	{
		//用户
		if (posix_getuid() !== 0)
			Log::Log("Not allowed user,must be root", Log::LOG_EXIT);

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
	 * 运行单个任务
	 */
	private function SapiRun()
	{
		$info = ",info:" . implode(" ", $_SERVER['argv']);
		$_SERVER['argc'] < 2 && Log::Log("Task is not found{$info}", Log::LOG_ERROR);

		//控制器
		$action = substr($_SERVER['argv'][1], strrpos($_SERVER['argv'][1], "/") + 1);
		$controller = substr($_SERVER['argv'][1], 0, strrpos($_SERVER['argv'][1], "/"));
		$controller = "{$controller}";

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

		//todo 额外参数

		//运行
		$invoke = $class->getMethod($action);
		$invoke->invoke($obj);
	}

	/**
	 * 运行服务
	 */
	private function Run()
	{
		//记录开始时间
		Shmop::getInstance()->write($this->server_start_time_key, time());

		//回收内存
		$this->FreeMemory();

		//第一次获取任务
		Pcntl::getInstance()->coreFork("taskPush");

		//开启服务 是否后台模式
		Log::Log("Start crontab server", Log::LOG_INFO);
		if ($this->is_daemon) {
			$pid = Pcntl::getInstance()->coreFork("crontabServer");

			if ($pid)
				$this->SaveServerPid($pid);
			else
				Log::Log("Start crontab server failed", Log::LOG_EXIT);
		} else {
			//获取终端命令
			$crontab = new Core\Core();
			$crontab->crontabServer();
		}
	}

	/**
	 * 是否在运行
	 */
	private function IsRuning()
	{
		$pid = $this->GetServerPid();
		if ($pid && posix_kill($pid, 0))
			Log::Log("Start crontab server failed,server is runing", Log::LOG_EXIT);
	}

	/**
	 * 注册信号处理函数
	 */
	private function RegisterSignal()
	{
		pcntl_signal(SIGHUP, array($this, "SignalHandler"));
		pcntl_signal(SIGINT, array($this, "SignalHandler"));
		//pcntl_signal(SIGQUIT, array($this, "SignalHandler"));
	}

	/**
	 * 信号处理函数
	 * @param $signal
	 */
	private function SignalHandler($signal)
	{
		switch ($signal) {
			//用户终端正常或非正常结束时发出 退出登陆
			case SIGHUP:
				file_put_contents(LOG_PATH . "log.txt", "sighup");
				echo "sighup";
				break;
			//程序终止信号 Ctrl-C INTR字符
//			case SIGINT:
//				file_put_contents(LOG_PATH . "log.txt", "sigint");
//				echo "sigint";
//				break;
			//程序终止信号 Ctrl-\ QUIT字符
			case SIGQUIT:
				echo "sigquit";
				break;
			//程序终止信号  Ctrl-Z SUSP字符
			case SIGTSTP:
				break;
			//留给用户使用的信号
			case SIGUSR1:
				break;
			case SIGUSR2:
				break;
			//时钟定时信号
			case SIGALRM:
				break;
			//子进程结束信号  避免僵尸进程
			case SIGCHLD:
				break;
			//当重终端读取数据时
			case SIGTTIN:
				break;

			//子进程信号
			//停止进程
			case SIGSTOP:
				break;
			//开始一个停止的进程
			case SIGCONT:
				break;
		}
	}

	/**
	 * 回收内存
	 */
	private function FreeMemory()
	{
		Task::getInstance()->clean();
	}

	/**
	 * 保存服务PID
	 * @param $pid
	 * sys_get_temp_dir
	 */
	private function SaveServerPid($pid)
	{
		$fp = fopen($this->server_pid_file, "w");
		fwrite($fp, $pid);
		fclose($fp);
	}

	/**
	 * 获取服务PID
	 * @return bool|string
	 */
	private function GetServerPid()
	{
		if (is_file($this->server_pid_file)) {
			$fp = fopen($this->server_pid_file, "r");
			$pid = fread($fp, filesize(LOG_PATH . "pid"));
			fclose($fp);

			return intval($pid);
		}

		return false;
	}

	/**
	 * 停止服务
	 * 清除所有待执行的任务
	 * 回收内存
	 */
	private function Stop()
	{
		$this->Init();

		//关闭服务
		$pid = $this->GetServerPid();
		if ($pid) {
			//关闭主进程
			posix_kill($pid, SIGKILL);   //todo SIGTERM ？？ 和SIGKILL的区别

			//回收内存
			$this->FreeMemory();

			//todo 关闭子进程
			echo "Stop crontab server success";
		} else {
			echo "Stop crontab server failed";
		}
	}

	/**
	 * 重起
	 */
	private function ReStart()
	{
		$this->Stop();
		//系统资源检测
		$this->SystemCheck();
		//运行
		$this->Run();
	}

	/**
	 * 重新载入配置
	 * 重新载入任务列表和事件
	 */
	private function ReLoad()
	{

	}

	/**
	 *输出状态
	 * 进程信息 运行时长  内存 任务数
	 * 后期统计
	 */
	private function Status()
	{
		//todo 感觉好别扭阿
		$this->Init();

		//检查进程状态
		$pid = $this->GetServerPid();
		if ($pid && posix_kill($pid, 0)) {
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
	 */
	private function TaskEvent($pid, $event)
	{
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
		$this->is_daemon = true;
	}

	/**
	 * 重定向标准输出
	 * todo 凌晨重定向到不同文件夹  重置
	 * $str = fread(STDIN, 100);
	 * echo $str;
	 */
	private function RestStd()
	{
		global $STDERR, $STDOUT;//一定要定义成全局变量

		$dir = LOG_PATH . date("Ymd") . "/";
		!is_dir($dir) && mkdir($dir, 0744, true);

		$file = $dir . "log.txt";
		$fp = fopen($file, "a");
		if ($fp) {
			fclose($fp);
			fclose(STDOUT);
			fclose(STDERR);

			$STDOUT = fopen($file, "a");  //这里必须重新打开
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
					isset($_SERVER['argv'][2]) &&
					$_SERVER['argv'][2] == "-d" &&
					$this->IsDaemon();
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
}