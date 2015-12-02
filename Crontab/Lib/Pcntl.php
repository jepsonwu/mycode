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
				Log::Log("Fork process field.commond:{$commond},argv:" . json_encode($argv), Log::LOG_ERROR);
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
	 *
	 * @param $name
	 * @return int|string
	 * @throws \Exception
	 */
	public function coreFork($name)
	{
		return $this->fork(PHP_EXEC, array("sapi.php", "Core/Core/{$name}"));
	}
}