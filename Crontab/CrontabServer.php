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

	//多进程数
	private $multi_process = 1;

	//php exec
	private $php_exec = "";

	//server pid shmop key
	private $server_pid_key = "server_pid_key";

	/**
	 * 开启服务
	 */
	public function Start()
	{
		//必要的处理
		$this->Necessary();
		//获取参数
		$this->Params();
		//init
		$this->Init();
		//neccssary
		$this->ServerNecc();
		//运行
		$this->Run();
	}

	public function Sapi()
	{
		//必要的处理
		$this->Necessary();
		//解析
		$this->SapiParse();
		//init
		$this->Init();
		//run
		$this->SapiRun();
	}

	/**
	 * sapi 解析
	 */
	private function SapiParse()
	{
		//解析路径

		//解析参数
	}

	/**
	 * 必要的处理
	 */
	private function Necessary()
	{
		//模式
		php_sapi_name() != "cli" && exit("Not allowed mode");

		//扩展
		foreach (array("pcntl", "posix") as $exten)
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

		//set_include_path

		//多字节设置
		mb_internal_encoding("UTF-8");

		//错误级别设置
		error_reporting(E_ALL ^ E_NOTICE);

		//定义基本常量
		!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");
		!defined("COMMON_PATH") && define("COMMON_PATH", BASE_PATH . "/Common/");
		!defined("LIB_PATH") && define("LIB_PATH", BASE_PATH . "/Lib/");
		!defined("LOG_PATH") && define("LOG_PATH", BASE_PATH . "/Log/");
		!defined("CORE_PATH") && define("CORE_PATH", BASE_PATH . "/Core/");

		//autoload
		require_once BASE_PATH . "Lib/Autoload.php";
		new Autoload();

		//配置文件
		Conf::getInstance()->setConfig(include(COMMON_PATH . "Conf.php"));

		//异常处理机制
		new Crontab_Exception();

		//daemon
		$this->is_daemon && $this->RestStd();

		//php exec
		$this->php_exec = Conf::getInstance()->getConfig("PHP_EXEC");
		//文件是否可执行
		define("PHP_EXEC", $this->php_exec);
	}

	/**
	 * 服务必要的处理
	 */
	private function ServerNecc()
	{
		//用户
		if (posix_getuid() !== 0)
			Log::Log("Not allowed user,must be root", Log::LOG_EXIT);

		//进程可用内存
		exec("free|awk -F' ' '{print $7}'", $memory_avaliable);
		if ($memory_avaliable[1] < Conf::getInstance()->getConfig("MEMORY_AVALIABLE") * 1024 * 1024)
			Log::Log("There is out of memory", Log::LOG_EXIT);

		//进程可用共享内存 todo 不准确
//		if (C("DEFAULT_IPC_TYPE") == "shmop") {
//			exec("cat /proc/sys/kernel/shmmax", $shmmax);
//			exec("free|awk -F' ' '{print $5}'", $shused);
//
//			if (($shmmax[0] - $shused[1] * 1024) < (C("SHMMAX") + 2) * 1024 * 1024 * 1024)
//				exit("There is out of shmop");
//		}

		//多进程数处理
		exec("cat /proc/cpuinfo |grep processor|wc -l", $multi_process);
		$multi_process_value = Conf::getInstance()->getConfig("MULTI_PROCESS");
		if ($multi_process_value) {
			if ($multi_process_value > $multi_process[0])
				Log::Log("Total number of multi-process set beyond the server CPU logic", Log::LOG_WARNING);
			$this->multi_process = $multi_process_value;
		} else {
			$this->multi_process = $multi_process[0];
		}

		//设置可获取任务列表最大时长
		$task_exec_total_time = Conf::getInstance()->getConfig("TASK_EXEC_TOTAL_TIME");
		//Shmop::getInstance()->delete("task_exec_total_time"); todo 两次指定的大小不一样便无法写进去
		Shmop::getInstance()->write("task_exec_total_time", $task_exec_total_time);
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
		$controller = "Core/{$controller}";

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
		//获取任务
		Task::getInstance()->clean();
		Pcntl::getInstance()->coreFork("taskPush");

		//开启服务 是否后台模式
		Log::Log("Start crontab server", Log::LOG_INFO);
		if ($this->is_daemon) {
			//fork todo 记录PID
			$pid = Pcntl::getInstance()->coreFork("crontabServer");
			if ($pid) {
				$this->SaveServerPid($pid);
			} else {
				Log::Log("Start crontab server failed", Log::LOG_ERROR);
			}
		} else {
			//获取终端命令
			$crontab = new Core\Core\Core();
			$crontab->crontabServer();
		}
	}

	/**
	 * 保存服务PID
	 * @param $pid
	 */
	private function SaveServerPid($pid)
	{
		if (!Shmop::getInstance()->write($this->server_pid_key, $pid))
			Log::Log("Save server pid failed,pid:{$pid}", Log::LOG_ERROR);
	}

	/**
	 * 获取服务PID
	 * @return bool|string
	 */
	private function GetServerPid()
	{
		return Shmop::getInstance()->read($this->server_pid_key);
	}

	/**
	 * 停止服务
	 * 清除所有待执行的任务
	 * 回收内存
	 */
	private function Stop()
	{
		//清除任务
		Task::getInstance()->clean();

		//回收内存 todo

		//关闭服务
		$pid = $this->GetServerPid();
		if ($pid) {
			posix_kill($pid, 9);
			//todo 是否要关闭子进程
			Log::Log("Stop crontab server success", Log::LOG_INFO);
		} else {
			Log::Log("Stop crontab server failed", Log::LOG_INFO);
		}
	}

	/**
	 * 重起
	 */
	private function ReStart()
	{
		$this->Stop();
		$this->Start();
	}

	/**
	 * 重新载入配置和任务列表
	 */
	private function ReLoad()
	{

	}

	/**
	 *输出状态
	 */
	private function Status()
	{

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
	 * 重定向标准输出 凌晨重定向
	 * $str = fread(STDIN, 100);
	 * echo $str;
	 */
	private function RestStd()
	{
		$dir = LOG_PATH . date("Ymd") . "/";
		!is_dir($dir) && mkdir($dir, 0744, true);

		$file = $dir . Conf::getInstance()->getConfig("LOG_FILE");
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
					isset($_SERVER['argv'][2]) &&
					$_SERVER['argv'][2] == "-d" &&
					$this->IsDaemon();
					break;
				case "stop":
					$this->Stop();
					break;
				case "restart":
					$this->ReStart();
					break;
				case "reload":
					$this->ReLoad();
					break;
				case "status":
					$this->Status();
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