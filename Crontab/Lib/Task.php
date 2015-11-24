<?php
namespace Lib;

/**
 *
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
		1 => 'Mon', 2 => 'Tues', 3 => 'Wed', 4 => 'Thur', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
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
	 * 解析任务
	 * @return array
	 */
	public function getTask()
	{
		$return = array();
		$task_list = include_once COMMON_PATH . "TaskList.php";

		if ($task_list) {
			//过滤
			$week_reg = "([0]?[1-7]|" . implode("|", $this->week_en) . ")";
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
				$this->task_exec_total_time = Ipc::getInstance()->read(strtotime("last year"));
				!$this->task_exec_total_time && $this->task_exec_total_time = Conf::getInstance()->getConfig("TASK_EXEC_TOTAL_TIME");
				$this->task_exec_total_time = $this->task_exec_total_time * 60;

				foreach ($task_list as $task) {
					$task = preg_split("/[\s]+/", $task);

					//拼接日期值 注意几个临界值 递归
					$exec_date = $this->GetDateString(array($this->today['year']), $task[4], "mon");
					//todo 星期就是日 根据月得到指定的日即可
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[3], "mday");
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[2], "hours");
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[1], "min");

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
					//缓存任务名称
					foreach ($exec_date as $key) {
						$return[$key][] = json_encode(array($exec_commond, $exec_argvs));
					}
				}

				print_r($return);
				echo count($return);
				exit;
			}
		}

		return $return;
	}

	/**
	 * 根据规则获取时间格式值
	 * @param $exec_date
	 * @param $rule
	 * @param $type
	 * @return array
	 */
	private function GetDateString($exec_date, $rule, $type)
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

		//返回值
		$return = array();
		$date = "";
		switch ($rule) {
			//*代表每  除了所有都为*的时候
			case "*":
				foreach ($exec_date as $val)
					for ($i = 1; $i <= $total; $i++)
						if ($date = $this->FilterDateString("{$val}{$sep}{$i}", $type))
							$return[] = $date;
				break;
			case is_numeric($rule):
				foreach ($exec_date as $val)
					if ($date = $this->FilterDateString("{$val}{$sep}{$rule}", $type))
						$return[] = $date;
				break;
			case strpos($rule, ",") !== false:
				$rule = explode(",", $rule);
				foreach ($rule as $val)
					foreach ($exec_date as $val_e)
						if ($date = $this->FilterDateString("{$val_e}{$sep}{$val}", $type))
							$return[] = $date;
				break;
			case strpos($rule, "-") !== false:
				$rule = explode("-", $rule);
				if ($rule[0] <= $rule[1]) {
					for ($i = $rule[0]; $i <= $rule[1]; $i++)
						foreach ($exec_date as $val_e)
							if ($date = $this->FilterDateString("{$val_e}{$sep}{$i}", $type))
								$return[] = $date;
				}
				break;
			case strpos($rule, "/") != false:
				$rule = intval(substr($rule, strpos($rule, "/") + 1));
				//todo 这里到底是从0还是1开始
				for ($i = 1; $i <= $total; $i = $i + $rule)
					foreach ($exec_date as $val_e)
						if ($date = $this->FilterDateString("{$val_e}{$sep}{$i}", $type))
							$return[] = $date;
				break;
		}

		return $return;
	}


	/**
	 * 当时间大于设置的最大获取可执行任务时长时不记
	 * 当时间小于当前获取任务列表开始时间不记
	 * @param $date
	 * @return string
	 */
	private function FilterDateString($date, $type)
	{
		$tmp = "";
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

		//最大可获取时长
		if (strtotime($tmp) - $this->task_start_time['min'] > $this->task_exec_total_time)
			return "";

		return $date;
	}

//	private function GetDateString($exec_date, $rule, $type)
//	{
//		//过滤英文
//		$filter="{$type}";
//		if()
//			$rule = preg_replace(array_map(function ($val) {
//				return "/{$val}/";
//			}, $this->mon_en), array_keys($this->mon_en), $rule);
//
//		$return = array();
//		switch ($rule) {
//			case "*":
//				$return = array("{$exec_date}-{$this->today['mon']}");
//				break;
//			case is_numeric($rule):
//				$return = array("{$exec_date}-{$rule}");
//				break;
//			case strpos($rule, ",") !== false:
//				$rule = explode(",", $rule);
//				foreach ($rule as $val)
//					$return[] = "{$exec_date}-{$val}";
//				break;
//			case strpos($rule, "-") !== false:
//				$rule = explode("-", $rule);
//				if ($rule[0] <= $rule[1]) {
//					for ($i = $rule[0]; $i <= $rule[1]; $i++)
//						$return[] = "{$exec_date}-{$i}";
//				}
//				break;
//			case strpos($rule, "/") != false:
//				$rule = intval(substr($rule, strpos($rule, "/") + 1));
//				for ($i = 1; $i <= 12; $i = $i + $rule)
//					$return[] = "{$exec_date}-{$i}";
//				break;
//		}
//
//		return $return;
//	}
}
