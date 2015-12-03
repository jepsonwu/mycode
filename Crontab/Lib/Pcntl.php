<?php
namespace Lib;

use Lib\Ipc\Shmop;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:48
 */
class Pcntl
{
	public static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Fork 进程
	 * @param $commond
	 * @param $argv
	 * @return int|string
	 * @throws \Exception
	 */
	public function fork($commond, array $argv)
	{
		$pid = pcntl_fork();
		switch ($pid) {
			case -1:
				$errno = pcntl_get_last_error();
				Log::Log("Fork process failed,commond:{$commond},argv:" . json_encode($argv) .
					",errno:{$errno},errstr:" . pcntl_strerror($errno), Log::LOG_WARNING);
				break;
			//子进程
			case 0:
				//todo 设置用户ID
				if ($commond != "/app/php5/bin/php")
					call_user_func_array(array($this, "test"), array());
				else
					pcntl_exec($commond, $argv);
				break;
			default:
				return $pid;
				break;
		}

		return "";
	}

	function timer($func, $timeouts){


		echo "enter timer\n";
		$base = event_base_new();
		$event = event_new();


		event_set($event, 0, EV_TIMEOUT, $func);
		event_base_set($event, $base);
		event_add($event, $timeouts);


		event_base_loop($base);
	}

	public function test()
	{
		while (true) {

		}
	}
}