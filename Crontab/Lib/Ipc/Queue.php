<?php
namespace Lib\Ipc;

/**
 * system v queue
 * flags:0 返回第一个 >0 返回匹配类型的第一个  <0返回绝对值匹配类型的第一个 不建议使用
 * option:MSG_IPC_NOWAIT 设置参数防止像上面情况下的阻塞发生
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 上午10:57
 */
use Lib\Conf;

class Queue
{
	/**
	 * 生成新队列配置参数
	 * @var array
	 */
	private $option = array(
		"key" => "123456789",  //可以自定义key
		//uid
		//gid
	);

	private $option_map = array(
		"max" => "msg_qbytes",
		"uid" => "msg_perm.uid",
		"gid" => "msg_perm.gid"
	);

	private $seg = null;

	private $conf;

	static public $instance = null;

	static public function getInstance($option = array())
	{
		if (is_null(self::$instance)) {
			self::$instance = new self($option);
		}

		return self::$instance;
	}

	public function __construct($option)
	{
		$this->option = array_merge($this->option, $option);
		$this->conf = Conf::getInstance(array());

		if (!isset($this->option['max']))
			$this->option['max'] = $this->conf["SYSVMSG_MAX_SIZE"];

		if (!isset($this->option['mode']))
			$this->option['mode'] = $this->conf["SYSVMSG_MODE"];

		if (!isset($this->option['single_max']))
			$this->option['single_max'] = $this->conf["SYSVMSG_SINGLE_MAX_SIZE"];

		//生成seg
		$this->seg = msg_get_queue($this->option['key'], $this->option['mode']);
		if (!$this->seg = msg_get_queue($this->option['key'], $this->option['mode']))
			throw new \Exception("System v queue get field");

		//设置参数
		foreach ($this->option_map as $key => $val)
			if (isset($this->option[$key]))
				if (!msg_set_queue($this->seg, array($val => $this->option[$key])))
					throw new \Exception("System v queue set option field,option:{$val}");
	}

	/**
	 * 出队列
	 * 不阻塞   无法同时设置MSG_NOERROR 选项截取多出的数据
	 * @param int $type 获取类型 默认为0 第一个
	 * @return mixed
	 */
	public function read($type = 0)
	{
		if (msg_receive($this->seg, $type, $re_type, $this->option['single_max'], $data, true, MSG_IPC_NOWAIT, $error_code))
			return $data;
		else
			return false;
	}

	/**
	 * 入队列
	 * @param $value
	 * @param int $type 存储类型  默认为1
	 * @return bool
	 */
	public function write($value, $type = 1)
	{
		$value = serialize($value);

		if (strlen($value) <= $this->option['single_max'])
			if (msg_send($this->seg, $type, $value, false, false, $error_code))
				return true;

		return false;
	}

	/**
	 * 删除队列
	 * @return bool
	 */
	public function clean()
	{
		self::$instance = null;
		return msg_remove_queue($this->seg);
	}

	/**
	 * 查看状态
	 * @param null $key
	 * @return array
	 */
	public function status($key = null)
	{
		$return = msg_stat_queue($this->seg);

		if (!is_null($key) && isset($return[$key]))
			return $return[$key];

		return $return;
	}

	/**
	 * 获取队列总数
	 * @return array
	 */
	public function getCount()
	{
		return $this->status("msg_qnum");
	}
}