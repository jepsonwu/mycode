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
	private $today = array();

	private $week_en = array(
		1 => 'Mon', 2 => 'Tues', 3 => 'Wed', 4 => 'Thur', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
	);

	private $mon_en = array(
		1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
		7 => 'Jul', 8 => 'Aug', 9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
	);

	/**
	 * 获取当前可执行任务
	 *
	 */
	public function GetTask()
	{
		$this->ParseRule();

		$str = "test data";
		$ipc = Ipc::getInstance();
		$res = $ipc->write(1446431328, $str, strlen($str));
		var_dump("write data:" . $res);
		exit;
	}


	/**
	 * 解析任务
	 * @return array
	 */
	private function ParseRule()
	{
		$return = array();
		$task_list = include_once COMMON_PATH . "TaskList.php";

		if ($task_list) {
			//每一次计算的起始时间 精确到分
			$task_start_time = floor(time() / 60) * 60;

			//每一次准备计算的时长 1小时
			$task_total_time = 60;

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
				$this->today = getdate();

				foreach ($task_list as $task) {
					$task = preg_split("/[\s]+/", $task);

					//拼接日期值 注意几个临界值 递归
					$exec_date = $this->GetDateString(array($this->today['year']), $task[4], "mon");
					//todo 星期就是日 根据月得到指定的日即可
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[3], "mday");
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[2], "hours");
					!empty($exec_date) && $exec_date = $this->GetDateString($exec_date, $task[1], "min");

					//构建任务
					switch ($task[0]) {
						case "s":
							$exec_commond = PHP_EXEC;
							break;
						default:
							$exec_commond = $task[0];
					}

					//缓存任务名称
					foreach ($exec_date as $key) {
						$return[$key][] = json_encode(array($exec_commond, array("sapi.php", $task[6], isset($task[7]) ? $task[7] : "")));
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
		//过滤英文
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
		switch ($rule) {
			//*代表每  除了所有都为*的时候
			//todo 当时间大于当前很多时不计算机制
			//todo 小于当前时间不记
			case "*":
				foreach ($exec_date as $val)
					$return[] = "{$val}{$sep}{$this->today[$type]}";
				break;
			case is_numeric($rule):
				foreach ($exec_date as $val)
					$return[] = "{$val}{$sep}{$rule}";
				break;
			case strpos($rule, ",") !== false:
				$rule = explode(",", $rule);
				foreach ($rule as $val)
					foreach ($exec_date as $val_e)
						$return[] = "{$val_e}{$sep}{$val}";
				break;
			case strpos($rule, "-") !== false:
				$rule = explode("-", $rule);
				if ($rule[0] <= $rule[1]) {
					for ($i = $rule[0]; $i <= $rule[1]; $i++)
						foreach ($exec_date as $val_e)
							$return[] = "{$val_e}{$sep}{$i}";
				}
				break;
			case strpos($rule, "/") != false:
				$rule = intval(substr($rule, strpos($rule, "/") + 1));
				for ($i = 1; $i <= $total; $i = $i + $rule)
					foreach ($exec_date as $val_e)
						$return[] = "{$val_e}{$sep}{$i}";
				break;
		}

		return $return;
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

	/**
	 * todo 回收内存
	 */
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