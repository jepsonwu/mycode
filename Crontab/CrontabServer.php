<?php
use Lib\Ipc;
/**
 *crontab server
 * 1.支持任务多进程
 * 2.主服务事件监听
 * 3.子进程事件监听
 * 4.日志记录
 * 5.进程间通信 shmop 共享内存段 system v
 * 6.用户权限
 * 7.内存共享  注意权限和大小限制
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:07
 */
class CrontabServer
{
	//任务栈
	private $tasks = array();

	//当前可执行任务
//	private
	//任务栈自动更新时间 凌晨
	private $tasks_update_time = "00:00";

	/**
	 * 主执行程序
	 * fork 监听进程  监听任务列表人为更新，任务事件
	 * fork 更新任务栈
	 * fork 任务
	 * 处理任务
	 */
	static public function Run()
	{
		Ipc::getInstance();
		exit;
		$shm_key = ftok(__FILE__, "t");
		var_dump($shm_key);
		$shmid = shmop_open($shm_key, "c", 0666, 100);
		var_dump($shmid);

		if ($shmid) {
			shmop_write($shmid, "hello dsf", 0);

			$str = shmop_read($shmid, 0, shmop_size($shmid));
			var_dump($str);

			shmop_delete($shmid);
			shmop_close($shmid);
		}
//		//开始时间
//		!defined("START_TIME") && define("START_TIME", time());
//
//		//一分钟轮询一次
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
//					pcntl_exec("/usr/bin/php",array("/data0/hapigou/index.php","Crontab/CrontabServer/GetTask"));
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


}