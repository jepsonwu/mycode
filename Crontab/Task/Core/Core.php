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
	 *
	 */
	public function GetTask()
	{
		$str = "test data";
		$ipc = Ipc::getInstance();
		$res = $ipc->write(1446431328, $str, strlen($str));
		var_dump("write data:" . $res);
		exit;
	}


	private function ParseRule()
	{
		//过滤

		//todo 解析计算出以分为单位的时间戳
	}

	public function CrontabServer()
	{
		//实力化进程通信
		$ipc = Ipc::getInstance();
		var_dump($ipc);

		while (true) {

			$data = $ipc->read(1446431328);
			if ($data)
				echo "read data:" . $data;

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