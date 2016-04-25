<?php
namespace Core;

use Lib\Conf;
use Lib\Log;
use Lib\String;
use Lib\Ipc\Queue;
use Lib\Ipc\Shmop;

/**
 * 任务解析类
 * 遵循linux ctontab规则
 * 采用sysvmsg存储
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-24
 * Time: 下午12:51
 */
class Task
{
	public static $instance = null;

	private $_conf;

	/**
	 * 当前的日期信息
	 * @var array
	 */
	private $today = array();

	private $week_en = array(
		1 => 'Mon', 2 => 'Tues', 3 => 'Wed', 4 => 'Thur', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'
	);

	private $mon_en = array(
		1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
		7 => 'Jul', 8 => 'Aug', 9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
	);

	//记录任务的起止时间 包含月|日|时|分
	private $task_start_time = array(
		"mon" => "",
		"mday" => "",
		"hours" => "",
		"min" => ""
	);

	//标签
	public static $multi_process = 'p';
	public static $multi_thread = 't';
	private $php_task = 'sp';//php 任务
	private $shell_task = 'ss';//shell 任务

	//每次获取的可执行任务时长
	private $task_exec_total_time = 3600;

	//任务列表key的key
	private $task_list_storage_key = "TASK LIST STORAGE KEY";

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct()
	{
		$this->_conf = Conf::getInstance(array());
	}

	/**
	 * 获取当前待执行任务队列总数
	 * @return array
	 */
	public function getCount()
	{
		return Queue::getInstance()->getCount();
	}

	/**
	 * 清空任务队列
	 */
	public function clean()
	{
		Queue::getInstance()->clean();
	}

	/**
	 * 任务出队列
	 * @param $type
	 * @return mixed
	 */
	public function pull($type)
	{
		return Queue::getInstance()->read($type);
	}

	/**
	 * 解析任务 入队列
	 * @return array
	 */
	public function push()
	{
		$this->clearTaskList();
		$task_list = $this->getTaskList();
		if (empty($task_list))
			$task_list = $this->readTaskList();

		//解析
		if ($task_list) {
			//获取现在的时间信息
			$this->today = getdate();

			//每一次计算的起始时间 精确到分
			$this->task_start_time = array(
				"mon" => strtotime("{$this->today['year']}-{$this->today['mon']}"),
				"mday" => strtotime("{$this->today['year']}-{$this->today['mon']}-{$this->today['mday']}"),
				"hours" => strtotime("{$this->today['year']}-{$this->today['mon']}-{$this->today['mday']} {$this->today['hours']}:00:00"),
				"min" => floor(time() / 60) * 60
			);

			//需要入队列的时间=>任务,任务键值对
			$wait_push = array();

			foreach ($task_list as $key => $task) {
				//拼接日期值 注意几个临界值 递归
				$exec_date = $this->getDateString(array($this->today['year']), $task[7], "mon");

				//日 星期
				!empty($exec_date) && $exec_date_week = $this->getDateStringByWeek($exec_date, $task[8]);
				!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[6], "mday");
				!empty($exec_date_week) && $exec_date = array_unique(array_merge($exec_date, $exec_date_week));

				!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[5], "hours");
				!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[4], "min");

				//入队列任务执行时间和内存key
				foreach ($exec_date as $time)
					$wait_push[$time][] = $key;

			}

			//入队列
			ksort($wait_push);
			$is_clear = true;//这里排序时间戳 采取回收机制保证队列的单一性 只要碰到队列没有数据则以后不再处理
			foreach ($wait_push as $key => $task) {
				$is_clear && !Queue::getInstance()->read($key) && $is_clear = false;

				Queue::getInstance()->write($task, $key);
			}
		}

		return true;
	}

	/**
	 * 从文件读取任务
	 * @return array|mixed
	 */
	private function readTaskList()
	{
		$task_file = COMMON_PATH . $this->_conf["TASK_LIST"] . ".php";
		if (is_file($task_file) && is_readable($task_file)) {
			//这里不能用include_once 一直共享task_list 变量
			$task_list = (array)include $task_file;

			$task_list = $this->filterTaskList($task_list);
			if ($task_list) {
				$tasks = array();
				//存储
				$gid_info = $uid_info = array();
				foreach ($task_list as $val) {
					$task = preg_split("/[\s]+/", $val);

					//处理以下进程数和线程数大于设置值的情况
					switch ($task[0]{0}) {
						case self::$multi_process:
							if (substr($task[0], 1) > $this->_conf["MULTI_PROCESS"]) {
								$task[0] = self::$multi_process . $this->_conf["MULTI_PROCESS"];
								Log::Log("Task multi process is greater than the set value,{$val}", Log::LOG_WARNING);
							}
							break;
						case self::$multi_thread:
							if (substr($task[0], 1) > $this->_conf["MULTI_THREAD"]) {
								$task[0] = self::$multi_thread . $this->_conf["MULTI_THREAD"];
								Log::Log("Task multi thread is greater than the set value,{$val}", Log::LOG_WARNING);
							}
							break;
					}

					//处理用户组和用户错误的任务 1 2
					!isset($gid_info[$task[1]]) && $gid_info[$task[1]] = posix_getgrnam($task[1]);
					if (!$gid_info[$task[1]]) {
						Log::Log("Task group is invalid,{$val}", Log::LOG_ERROR, false);
						continue;
					}

					!isset($uid_info[$task[2]]) && $uid_info[$task[2]] = posix_getpwnam($task[2]);
					if ($uid_info[$task[2]] && $uid_info[$task[2]]['gid'] == $gid_info[$task[1]]['gid']) {
						$task[1] = $gid_info[$task[1]]['gid'];
						$task[2] = $uid_info[$task[2]]['uid'];
					} else {
						Log::Log("Task user is invalid,{$val}", Log::LOG_ERROR, false);
						continue;
					}

					//处理执行命令错误的任务 3
					switch ($task[3]) {
						case $this->php_task:
							$task[3] = PHP_BINARY;
							$task[9] = BASE_PATH . "cli.php {$task[9]}";
							break;
						case $this->shell_task:
							$task[3] = '/bin/bash';
							$task[9] = $this->_conf['APPLICATION'] . "/{$task[9]}";
							break;
						default:
							break;
					}

					$key = $this->saveTask($task);
					$tasks[$key] = $task;
				}

				//存储任务列表key
				Shmop::getInstance()->write($this->task_list_storage_key, array_keys($tasks));
				return $tasks;
			}
		} else {
			Log::Log("Task list configure file is not found", Log::LOG_ERROR, false);
		}

		return array();
	}

	/**
	 * 过滤配置文件任务列表
	 * 任务格式
	 * [进程数|线程数|*(不填)](进程p表示,线程t表示,如果大于配置最大值,均以最大值为准) [用户组] [用户]
	 * [执行命令|sp(框架PHP)|ss(框架shell)] [时间格式] [执行程序] [参数](参数用/分割键值)
	 * @param $task_list
	 * @return array
	 */
	private function filterTaskList($task_list)
	{
		//过滤
		$week_reg = "([0]?[0-6]|" . implode("|", $this->week_en) . ")";
		$month_reg = "(1[0-2]|[0]?[1-9]|" . implode("|", $this->mon_en) . ")";

		$task_list_return = preg_grep(
			"/^(\*|([" . self::$multi_process . "|" . self::$multi_thread . "][\d]+))[\s]+" .
			"([\w]+)[\s]+" .
			"([\w]+)[\s]+" .
			"(" . $this->php_task . "|" . $this->shell_task . "|[\/|\w]+)[\s]+" .
			"(?:\b|\*\/|[0-5]?[0-9]+[-|,])[0-5]?[0-9]+[\s]+" .
			"(\*|(?:\b|\*\/|(2[0-3]|[0-1]?\d)[-|,])(2[0-3]|[0-1]?\d))[\s]+" .
			"(\*|(?:\b|\*\/|(3[0-1]|[0-2]?[1-9]|10|20)[-|,])(3[0-1]|[0-2]?[1-9]|10|20))[\s]+" .
			"(\*|(?:\b|\*\/|{$month_reg}[-|,]){$month_reg})[\s]+" .
			"(\*|(?:\b|\*\/|{$week_reg}[-|,]){$week_reg})[\s]+" .
			"[\/|\w|\.]+" .
			"[\s]*[\/|\w]*$/", $task_list);

		//这里返回不匹配的值记录日志
		if (count($task_list_return) < count($task_list))
			Log::Log("Some tasks is not in conformity with the specification.\n{\n"
				. implode("\n", array_diff_key($task_list, $task_list_return)) . "\n}", Log::LOG_ERROR, false);

		return $task_list_return;
	}

	/**
	 * 刷新任务列表
	 * reload
	 */
	public function flushTaskList()
	{
		$this->clearTaskList();
		$this->readTaskList();
	}

	/**
	 * 清除任务列表
	 * stop
	 * restart
	 */
	public function clearTaskList()
	{
		$task_list_key = Shmop::getInstance()->read($this->task_list_storage_key);
		if ($task_list_key && is_array($task_list_key)) {
			foreach ($task_list_key as $key)
				Shmop::getInstance()->delete($key);

			Shmop::getInstance()->delete($this->task_list_storage_key);
		}

		return true;
	}


	/**
	 * 获取可执行任务列表
	 * @return array
	 */
	private function getTaskList()
	{
		$task_list = array();

		$task_list_key = Shmop::getInstance()->read($this->task_list_storage_key);
		if ($task_list_key && is_array($task_list_key)) {
			foreach ($task_list_key as $key) {
				$task = $this->getTask($key);
				$task && $task_list[$key] = $task;
			}
		}

		return $task_list;
	}

	/**
	 * 保存任务到内存
	 * 返回内存key
	 * @param $task
	 * @return int
	 */
	private function saveTask($task)
	{
		$key = String::stringToInt(serialize($task));
		if (!Shmop::getInstance()->write($key, $task)) {
			Log::Log("task save field,task:" . json_encode($task), Log::LOG_ERROR);
			return false;
		}

		Log::Log("task save success,key:{$key},task:" . json_encode($task), Log::LOG_INFO);
		return $key;
	}

	/**
	 * 根据key读取单个任务
	 * @param $key
	 * @return bool|string
	 */
	public function getTask($key)
	{
		return Shmop::getInstance()->read($key);
	}

	/**
	 * 根据规则获取时间格式值
	 * @param $exec_date
	 * @param $rule
	 * @param $type
	 * @return array
	 */
	private function getDateString($exec_date, $rule, $type)
	{
		//替换英文
		$filter = "{$type}_en";
		if (isset($this->{$filter})) {
			$rule = preg_replace(array_map(function ($val) {
				return "/{$val}/";
			}, $this->$filter), array_keys($this->$filter), $rule);
		}

		//分隔符
		$sep = "";
		$total = "";
		switch ($type) {
			case "mon":
				$sep = "-";
				$total = 12;
				break;
			case "mday":
				$sep = "-";
				$total = 31;
				break;
			case "hours":
				$sep = " ";
				$total = 24;
				break;
			case "min":
				$sep = ":";
				$total = 60;
				break;
		}

		$return = array();
		try {
			//返回值
			switch ($rule) {
				//*代表每  除了所有都为*的时候
				case "*":
					foreach ($exec_date as $val)
						for ($i = 1; $i <= $total; $i++)
							if ($date = $this->filterDateString("{$val}{$sep}{$i}", $type))
								$return[] = ($type == 'min') ? strtotime($date) : $date;
					break;
				case is_numeric($rule):
					foreach ($exec_date as $val)
						if ($date = $this->filterDateString("{$val}{$sep}{$rule}", $type))
							$return[] = ($type == 'min') ? strtotime($date) : $date;
					break;
				case strpos($rule, ",") !== false:
					$rule = array_unique(explode(",", $rule));
					//调换以下大小的顺序
					sort($rule);
					foreach ($exec_date as $val_e)
						foreach ($rule as $val)
							if ($date = $this->filterDateString("{$val_e}{$sep}{$val}", $type))
								$return[] = ($type == 'min') ? strtotime($date) : $date;
					break;
				case strpos($rule, "-") !== false:
					$rule = array_unique(explode("-", $rule));
					sort($rule);
					if (count($rule) == 2) {
						foreach ($exec_date as $val_e)
							for ($i = $rule[0]; $i <= $rule[1]; $i++)
								if ($date = $this->filterDateString("{$val_e}{$sep}{$i}", $type))
									$return[] = ($type == 'min') ? strtotime($date) : $date;
					}
					break;
				case strpos($rule, "/") != false:
					$rule = intval(substr($rule, strpos($rule, "/") + 1));
					//这里从1开始
					//注意 exec_date要放外面循环,因为放里面会大乱顺序
					foreach ($exec_date as $val_e)
						for ($i = 1; $i <= $total; $i = $i + $rule)
							if ($date = $this->filterDateString("{$val_e}{$sep}{$i}", $type))
								$return[] = ($type == 'min') ? strtotime($date) : $date;
					break;
			}
		} catch (\Exception $e) {
		}

		return $return;
	}

	/**
	 * 通过星期获取可执行时间
	 * @param $exec_date
	 * @param $rule
	 * @return array
	 */
	private function getDateStringByWeek($exec_date, $rule)
	{
		//替换英文
		$rule = preg_replace(array_map(function ($val) {
			return "/{$val}/";
		}, $this->week_en), array_keys($this->week_en), $rule);

		//处理规则
		$return = array();
		try {
			switch ($rule) {
				case "*":
					return array();
					break;
				case is_numeric($rule):
					$rule = array($rule);
					break;
				case strpos($rule, ",") !== false:
					$rule = array_unique(explode(",", $rule));
					sort($rule);
					break;
				case strpos($rule, "-") !== false:
					$rule = array_unique(explode("-", $rule));
					sort($rule);
					if (count($rule) == 2) {
						for ($i = $rule[0] + 1; $i < $rule[1]; $i++)
							$rule[] = $i;
						sort($rule);
					}
					break;
			}

			//处理结果
			foreach ($exec_date as $val)
				for ($i = 1; $i <= 31; $i++) {
					$date_info = getdate(strtotime("{$val}-{$i}"));

					if (in_array($date_info['wday'], $rule)) {
						if ($date = $this->filterDateString("{$val}-{$i}", "mday"))
							$return[] = $date;
					}
				}
		} catch (\Exception $e) {
		}

		return $return;
	}

	/**
	 * 当时间大于设置的最大获取可执行任务时长时不记
	 * 当时间小于当前获取任务列表开始时间不记
	 * @param $date
	 * @param $type
	 * @return string
	 * @throws \Exception
	 */
	private function filterDateString($date, $type)
	{
		switch ($type) {
			case "hours":
				$tmp = $date . ":00:00";
				break;
			case "min":
				$tmp = $date . ":00";
				break;
			default:
				$tmp = $date;
		}
		if (strtotime($tmp) < $this->task_start_time[$type])
			return "";

		//最大可获取时长  采用throw机制可以减少循环次数
		if (strtotime($tmp) - $this->task_start_time['min'] > $this->task_exec_total_time)
			throw new \Exception();

		return $date;
	}
}
