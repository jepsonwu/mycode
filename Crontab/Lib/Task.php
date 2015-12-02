<?php
namespace Lib;

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

	private $task_exec_total_time = 0;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 获取当前任务总数
	 * @return array
	 */
	public function getCount()
	{
		return Queue::getInstance()->getCount();
	}

	/**
	 * 清空队列
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
		$return = array();
		//todo 缓存 只有当人为刷新的时候才会更新
		$task_list = include_once COMMON_PATH . "TaskList.php";

		if ($task_list) {
			//过滤
			$week_reg = "([0]?[0-6]|" . implode("|", $this->week_en) . ")";
			$month_reg = "(1[0-2]|[0]?[1-9]|" . implode("|", $this->mon_en) . ")";
			$task_list = preg_grep(
				"/^(s|[\/|\w]+)[\s]+" .
				"(?:\b|\*\/|[0-5]?[0-9]+[-|,])[0-5]?[0-9]+[\s]+" .
				"(\*|(?:\b|\*\/|(2[0-3]|[0-1]?\d)[-|,])(2[0-3]|[0-1]?\d))[\s]+" .
				"(\*|(?:\b|\*\/|(3[0-1]|[0-2]?[1-9]|10|20)[-|,])(3[0-1]|[0-2]?[1-9]|10|20))[\s]+" .
				"(\*|(?:\b|\*\/|{$month_reg}[-|,]){$month_reg})[\s]+" .
				"(\*|(?:\b|\*\/|{$week_reg}[-|,]){$week_reg})[\s]+" .
				"[\/|\w|\.]+" .
				"[\s]*[\/|\w]*$/", $task_list);

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

				//最大可执行时长
				$this->task_exec_total_time = Shmop::getInstance()->read("task_exec_total_time");
				!$this->task_exec_total_time && $this->task_exec_total_time = Conf::getInstance()->getConfig("TASK_EXEC_TOTAL_TIME");
				$this->task_exec_total_time = $this->task_exec_total_time * 60;

				//需要入队列的时间=>任务,任务键值对
				$wait_push = array();

				foreach ($task_list as $task) {
					$task = preg_split("/[\s]+/", $task);

					//拼接日期值 注意几个临界值 递归
					$exec_date = $this->getDateString(array($this->today['year']), $task[4], "mon");

					//日 星期
					!empty($exec_date) && $exec_date_week = $this->getDateStringByWeek($exec_date, $task[5]);
					!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[3], "mday");
					!empty($exec_date_week) && $exec_date = array_unique(array_merge($exec_date, $exec_date_week));

					!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[2], "hours");
					!empty($exec_date) && $exec_date = $this->getDateString($exec_date, $task[1], "min");

					//构建任务
					$exec_argvs = array();
					switch ($task[0]) {
						case "s":
							$exec_commond = PHP_EXEC;
							$exec_argvs[] = "sapi.php";
							break;
						default:
							$exec_commond = $task[0];
					}

					$exec_argvs[] = $task[6];
					isset($task[7]) && $exec_argvs[] = $task[7];

					//入队列任务执行时间和内存key todo 这里会一直追加 回收机制
					$task = $this->saveTask(array($exec_commond, $exec_argvs));
					if ($task)
						foreach ($exec_date as $key)
							$wait_push[strtotime($key)][] = $task;

				}

				//入队列
				foreach ($wait_push as $key => $task)
					Queue::getInstance()->write($task, $key);
			}
		}

		return $return;
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
	 * 读取单个任务
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
		if ($this->$filter) {
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
								$return[] = $date;
					break;
				case is_numeric($rule):
					foreach ($exec_date as $val)
						if ($date = $this->filterDateString("{$val}{$sep}{$rule}", $type))
							$return[] = $date;
					break;
				case strpos($rule, ",") !== false:
					$rule = array_unique(explode(",", $rule));
					//调换以下大小的顺序
					sort($rule);
					foreach ($exec_date as $val_e)
						foreach ($rule as $val)
							if ($date = $this->filterDateString("{$val_e}{$sep}{$val}", $type))
								$return[] = $date;
					break;
				case strpos($rule, "-") !== false:
					$rule = array_unique(explode("-", $rule));
					sort($rule);
					if (count($rule) == 2) {
						foreach ($exec_date as $val_e)
							for ($i = $rule[0]; $i <= $rule[1]; $i++)
								if ($date = $this->filterDateString("{$val_e}{$sep}{$i}", $type))
									$return[] = $date;
					}
					break;
				case strpos($rule, "/") != false:
					$rule = intval(substr($rule, strpos($rule, "/") + 1));
					//这里从1开始
					//注意 exec_date要放外面循环,因为放里面会大乱顺序
					foreach ($exec_date as $val_e)
						for ($i = 1; $i <= $total; $i = $i + $rule)
							if ($date = $this->filterDateString("{$val_e}{$sep}{$i}", $type))
								$return[] = $date;
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
