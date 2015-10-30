<?php
namespace Task\Core;

use Lib\Ipc;

/**
 * 核心任务
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-28
 * Time: 上午10:12
 */
class Core
{
	/**
	 * 获取当前可执行任务
	 */
	public function GetTask()
	{
		echo "aaa";
	}

	public function CrontabServer()
	{
		//实力化进程通信
		$ipc = Ipc::getInstance();

		while (true) {

		}

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
	}
}