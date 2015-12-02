<?php
namespace Core\Core;

use Lib\Log;
use Lib\Pcntl;
use Lib\Task;

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
	 * todo 闹钟实现任务自动入队列
	 */
	public function taskPush()
	{
		$start_time = microtime(true);
		Task::getInstance()->push();
		Log::Log("getTask success,task count:" .
			Task::getInstance()->getCount() . ",used time:" . sprintf("%0.4f", microtime(true) - $start_time), Log::LOG_INFO);
	}


	/**
	 * crontab 服务
	 */
	public function crontabServer()
	{
		//fork 进程 一分钟论寻
		$start_time = floor(time() / 60) * 60;
		$this->execTask($start_time);

		do {
			if (time() - $start_time >= 60) {
				$start_time += 60;
				$this->execTask($start_time);
			}
		} while (true);
	}

	/**
	 * 执行任务
	 * todo 多进程实现 多线程实现
	 * @param $type
	 */
	private function execTask($type)
	{
		$tasks = Task::getInstance()->pull($type);
		if ($tasks) {
			foreach ($tasks as $key) {
				$task = Task::getInstance()->getTask($key);
				if ($task) {
					//log
					Log::Log("Start exec task,key:{$key}", Log::LOG_INFO);

					//todo 记录PID和key 命令行操作任务
					$pid = Pcntl::getInstance()->fork($task[0], $task[1]);
				} else {
					//错误日志
					Log::Log("Task get field,key:{$key}", Log::LOG_ERROR);
				}
			}
		}

	}
}