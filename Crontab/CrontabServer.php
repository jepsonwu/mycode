<?php
use Lib\Ipc;

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
	private $is_deaemon = false;

	//多进程数
	private $multi_process = 1;

	//php exec
	private $php_exec = "";

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

	}

	/**
	 * 必要的处理
	 */
	private function Necessary()
	{
		//模式
		if (php_sapi_name() != "cli")
			exit("Not allowed mode");

		//扩展
		foreach (array("pcntl", "posix") as $exten)
			!extension_loaded($exten) && exit("{$exten} extension is not found");

		//操作系统  目前只支持linux  有很多命令需要处理
		$posix_uname = posix_uname();
		if (!in_array($posix_uname['sysname'], array("Linux")))
			exit("{$posix_uname['sysname']} operating system is not supported");

	}

	/**
	 * 初始化
	 */
	private function Init()
	{
		//autoload
		require_once "./Autoloader.php";

		//时区
		date_default_timezone_set("PRC");

		//多字节设置
		mb_internal_encoding("UTF-8");

		//错误级别设置
		error_reporting(E_ALL ^ E_NOTICE);

		//定义基本常量
		!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");
		!defined("COMMON_PATH") && define("COMMON_PATH", BASE_PATH . "/Common/");
		!defined("LIB_PATH") && define("LIB_PATH", BASE_PATH . "/Lib/");
		!defined("LOG_PATH") && define("LOG_PATH", BASE_PATH . "/Log/");
		!defined("TASK_PATH") && define("TASK_PATH", BASE_PATH . "/Task/");

		//function
		require_once COMMON_PATH . "Function.php";

		//配置文件
		C(include(COMMON_PATH . "Conf.php"));

		//设置异常处理
		//set_exception_handler("ExceHandler");

		//daemon
		$this->is_deaemon && $this->RestStd();

		//php exec
		$this->php_exec = C("PHP_EXEC");
	}

	/**
	 * 服务必要的处理
	 */
	private function ServerNecc()
	{
		//用户
		if (posix_getuid() !== 0)
			exit("Not allowed user,must be root");

		//进程可用内存
		exec("free|awk -F' ' '{print $7}'", $memory_avaliable);
		if ($memory_avaliable[1] < C("MEMORY_AVALIABLE") * 1024 * 1024)
			exit("There is out of memory");

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
		$multi_process_value = C("MULTI_PROCESS");
		if ($multi_process_value) {
			if ($multi_process_value > $multi_process[0])
				Logs("Total number of multi-process set beyond the server CPU logic", LOG_WARNING);
			$this->multi_process = $multi_process_value;
		} else {
			$this->multi_process = $multi_process[0];
		}

	}

	/**
	 * 运行单个任务
	 */
	private function SapiRun()
	{

	}

	/**
	 * 运行服务
	 */
	private function Run()
	{
		//获取任务
		Fork($this->php_exec, array("sapi.php", "Core/Core/GetTask", "id/1"));

		//开启服务 是否后台模式
		if ($this->is_deaemon) {
			//fork
		} else {
			//获取终端命令
			$this->Run();
		}
		//time
		define("START_TIME", time());

		//记录日志
		$this->Log("start server", LOG_INFO);
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

		$file = $dir . C("LOG_FILE");
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
					isset($_SERVER['argv'][2]) &&
					$_SERVER['argv'][2] == "-d" &&
					$this->is_deaemon = true;
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
					exit("Crontab version:" . $this->version . "\n");
					break;
				case "-V":
					exit(file_get_contents("README.md"));
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