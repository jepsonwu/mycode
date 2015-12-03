<?php
namespace Core;

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
	public static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 获取当前可执行任务
	 * todo 闹钟实现任务自动入队列
	 * 设置当前进程的优先级
	 */
	public function taskPush()
	{
		$start_time = microtime(true);
		Task::getInstance()->push();
		Log::Log("getTask success,task count:" .
			Task::getInstance()->getCount() . ",used time:" . sprintf("%0.4f", microtime(true) - $start_time), Log::LOG_INFO);
	}

	public function test()
	{
		while (true) {

		}
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
	 * 核心进程fork
	 * @param $name
	 * @return int|string
	 * @throws \Exception
	 */
	public function coreFork($name)
	{
		return Pcntl::getInstance()->fork(PHP_BINARY, array("sapi.php", "Core/Core/{$name}"));
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
					//todo 记录PID和key 命令行操作任务
					$pid = Pcntl::getInstance()->fork($task[0], $task[1]);
					//log
					Log::Log("Start exec task,key:{$key},pid:{$pid}", Log::LOG_INFO);
				} else {
					//错误日志
					Log::Log("Task get field,key:{$key}", Log::LOG_ERROR);
				}
			}
		}

	}

	/**
	 * 回收内存
	 */
	public function freeMemory()
	{
		//清空任务队列
		Task::getInstance()->clean();
	}

	/**
	 * 子进程结束信号处理函数
	 * 正常退出 信号中断 信号停止
	 */
	public function childFinished()
	{
		//-1表示发生错误 todo 第三个参数无法确认该系统是否可以使用 wait(3)
		$pid = pcntl_waitpid(-1, $status, WUNTRACED);
		if ($pid != -1) {
			//检查是否正常退出
			if (pcntl_wifexited($status)) {
				//todo 计算子进程耗时等等
				Log::Log("Child process execute successful,pid:{$pid},return code:" . pcntl_wexitstatus($status), Log::LOG_INFO);
			} elseif (pcntl_wifsignaled($status)) {
				Log::Log("Child process execute failed by signal,pid:{$pid},signal:" . pcntl_wtermsig($status), Log::LOG_WARNING);
			} elseif (pcntl_wifstopped($status)) {
				Log::Log("Child process is stopped,pid:{$pid},signal:" . pcntl_wstopsig($status), Log::LOG_INFO);
			} else {
				Log::Log("There is no clear event happened which child process,pid:{$pid}", Log::LOG_WARNING);
			}
		} else {
			Log::Log("Child process not return signal", Log::LOG_WARNING);
		}
	}
}