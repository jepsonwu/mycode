<?php
namespace Lib\Ipc;

/**
 * system v shmop
 * 支持传字符串键名
 * todo sem 信号量实现原子性
 * serialize 转换
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:13
 */
use Lib\Conf;
use Lib\String;

class Shmop
{
	static public $instance = null;

	private $option = array();
	private $conf;

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

		if (!isset($this->option['mode']))
			$this->option['mode'] = $this->conf["SYSHM_MODE"];
	}

	/**
	 * @param $key
	 * @return bool|string
	 */
	public function read($key)
	{
		!is_int($key) && $key = String::stringToInt($key);
		@$shmid = shmop_open($key, "a", 0, 0);

		if ($shmid) {
			$return = shmop_read($shmid, 0, shmop_size($shmid));
			if ($return)
				return unserialize($return);
		}

		return false;
	}

	/**
	 * @param string $key
	 * @param $value mixed
	 * @return bool|int
	 */
	public function write($key, $value)
	{
		!is_int($key) && $key = String::stringToInt($key);
		$value = serialize($value);

		@$shmid = shmop_open($key, "c", $this->option['mode'], strlen($value));

		if ($shmid)
			return shmop_write($shmid, $value, 0);

		return false;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function delete($key)
	{
		!is_int($key) && $key = String::stringToInt($key);
		@$shmid = shmop_open($key, "a", 0, 0);
		$return = false;

		$shmid && $return = shmop_delete($shmid);
		$return && shmop_close($shmid);

		return $return;
	}
}