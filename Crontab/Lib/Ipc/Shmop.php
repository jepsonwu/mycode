<?php
namespace Lib\Ipc;

use Lib\Ipc\IpcAbstract;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:13
 */
!extension_loaded("shmop") && exit("shmop extension is not loaded");

class Shmop extends IpcAbstract
{
	private $_option = array(
		"mode" => 0666,
	);

	public function __construct($option)
	{
		$this->_option = array_merge($this->_option, $option);
	}

	/**
	 * @param $key
	 * @return bool|string
	 */
	public function read($key)
	{
		$shmid = shmop_open($key, "a", 0, 0);
		if ($shmid)
			return shmop_read($shmid, 0, shmop_size($shmid));

		return false;
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return bool|int
	 */
	public function write($key = '', $value, $size)
	{
		empty($key) && $key = ftok(__FILE__, "t");
		$shmid = shmop_open($key, "c", $this->_option['mode'], $size);

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
		$shmid = shmop_open($key, "a", 0, 0);
		$return = false;

		$shmid && $return = shmop_delete($shmid);
		$return && shmop_close($shmid);

		return $return;
	}
}