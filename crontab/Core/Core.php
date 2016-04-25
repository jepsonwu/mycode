<?php
namespace Core;

use Lib\Log;
use Lib\Timer;

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
	 * 设置当前进程的优先级
	 */
	public function taskPush()
	{
		$start_time = microtime(true);
		Task::getInstance()->push();
		//设置任务刷新闹钟 3000000000 todo 定的不好就且了佛 ctrl-c退出
		//Core::getInstance()->setTimer("taskTimer", 10000000);

		Log::Log("getTask success,task count:" .
			Task::getInstance()->getCount() . ",used time:" . sprintf("%0.4f", microtime(true) - $start_time), Log::LOG_INFO);
	}

	/**
	 * 刷新任务闹钟 50分钟刷新一次
	 */
	public function taskTimer()
	{
		$this->coreFork("taskPush");
	}

	/**
	 * 重定向标准输出
	 */
	public function restStd()
	{
		global $STDERR, $STDOUT;//一定要定义成全局变量

		$dir = LOG_PATH . date("Ymd") . "/";
		!is_dir($dir) && mkdir($dir, 0744, true);

		$file = $dir . "task_log.txt";
		$fp = fopen($file, "a");
		if ($fp) {
			fclose($fp);
			fclose(STDOUT);
			fclose(STDERR);

			$STDOUT = fopen($file, "a");  //这里必须重新打开
			$STDERR = fopen($file, "a");
		}

		//设置重定向闹钟 凌晨
		$this->setTimer("restStd", 86400 - time() % 86400);
	}

	/**
	 * 设置闹钟
	 * @param $name string core 函数名
	 * @param $timeout int 微妙
	 */
	public function setTimer($name, $timeout)
	{
		$this->coreFork(array(Timer::getInstance(), "timer"), array(array($this, $name), $timeout));
	}

	/**
	 * crontab 服务
	 */
	public function crontabServer()
	{
		//fork 进程 一分钟论寻
		$start_time = floor(time() / 60) * 60;
		$this->execTask($start_time);

		//todo 用闹钟实现
		do {
			if (time() - $start_time >= 60) {
				$start_time += 60;
				$this->execTask($start_time);
			}
		} while (true);
	}

	/**
	 * 多进程执行
	 * todo php 开启zts 无法安装phpredis扩展
	 */
	public function execThread($total, $callback)
	{
		$return_data = array();

		//新建线程
		$thread_arr = array();
		for ($i = 1; $i <= $total; $i++) {
			$thread_arr[$i] = new \CrontabThread($i);
			$thread_arr[$i]->start();
		}

		//处理结果
		foreach ($thread_arr as $key => $val) {
			while ($thread_arr[$key]->isRunning()) {
				usleep(10);
			}

			if ($thread_arr[$key]->join()) {
				$return_data[$key] = $thread_arr[$key]->data;
			}
		}

		//交给回调函数处理
		call_user_func_array(array(), $return_data);
	}

	/**
	 * 核心进程fork
	 * @param $name
	 * @param array $argv
	 * @return int|string
	 */
	public function coreFork($name, $argv = array())
	{
		is_string($name) && $name = array($this, $name);
		$pid = pcntl_fork();
		switch ($pid) {
			case -1:
				$errno = pcntl_get_last_error();
				Log::Log("Fork process failed,commond:{$name[1]},argv:" . json_encode($argv) . ",errno:{$errno},errstr:"
					. pcntl_strerror($errno), Log::LOG_WARNING);
				break;
			//子进程
			case 0:
				call_user_func_array($name, $argv);
				exit;//这里需要对每个子进程退出 不然会一直执行下去后面的代码
				break;
			default:
				Log::Log("Fork process successful,commond:{$name[1]},argv:" . json_encode($argv) . ",pid:{$pid}", Log::LOG_INFO);
				return $pid;
				break;
		}

		return "";
	}

	/**
	 * 执行任务
	 * 支持多进程 只向子进程传递process_id参数  不对结果进行处理
	 * 支持多线程
	 * @param $type
	 */
	private function execTask($type)
	{
		$tasks = Task::getInstance()->pull($type);

		if ($tasks) {
			foreach ($tasks as $key) {
				$task = Task::getInstance()->getTask($key);

				if ($task) {
					$pid = pcntl_fork();
					switch ($pid) {
						case -1:
							$errno = pcntl_get_last_error();
							Log::Log("Fork process failed,commond:{$task[0]},argv:" . json_encode($task[1]) .
								",errno:{$errno},errstr:" . pcntl_strerror($errno), Log::LOG_WARNING);
							break;
						//子进程
						case 0:
							//设置用户组 用户
							posix_setgid($task[1]);
							posix_setuid($task[2]);

							//多进程和多线程
							switch ($task[0]{0}) {
								case Task::$multi_process:
									for ($i = 1; $i <= substr($task[0], 1); $i++)
										pcntl_exec($task[3], array($task[9], isset($task[10]) ? "{$task[10]}/process_id/{$i}" : "process_id/{$i}"));
									break;
								case Task::$multi_thread:
									//todo 只支持框架任务  一个任务  一个回调函数处理结果
									break;
								case "*":
									$exec_argv = explode(" ", $task[9]);
									isset($task[10]) && $exec_argv[] = $task[10];
									pcntl_exec($task[3], $exec_argv);
									break;
							}
							break;
						default:
							Log::Log("Start exec task,key:{$key},pid:{$pid}", Log::LOG_INFO);
							break;
					}
				} else {
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
		//清空任务列表
		Task::getInstance()->clearTaskList();
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