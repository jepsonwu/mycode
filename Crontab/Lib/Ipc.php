<?php
namespace Lib;
/**
 *进程通信 操作类
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:13
 */
class Ipc
{
	//资源句柄
	private $handler = "";

	/**
	 * @param $type (shmop
	 * @param array $option
	 * @return mixed
	 */
	static public function getInstance($type = '', $option = array())
	{
		static $_instances = array();
		$uuid = md5($type . serialize($option));

		if (!isset($_instances[$uuid])) {
			$obj = new Ipc();
			$_instances[$uuid] = $obj;
			$obj->handler = $obj->connent($type, $option);
		}

		return $_instances[$uuid];
	}

	/**
	 * @param string $type
	 * @param $option
	 * @return string
	 * @throws \Exception
	 */
	private function connent($type = '', $option)
	{
		empty($type) && $type = "shmop";
		$class = strpos($type, "\\") !== false ? $type : 'Lib\\Ipc\\' . ucwords(strtolower($type));

		$cache = "";
		if (class_exists($class))
			$cache = new $class($option);
		else
			throw new \Exception("{$class} is not exists", 1001);//todo log set_exception_handler()

		return $cache;
	}

	public function read($key)
	{
		return $this->handler->read($key);
	}

	public function write($key, $value, $size)
	{
		return $this->handler->write($key, $value, $size);
	}

	public function delete($key)
	{
		return $this->handler->delete($key);
	}

	public function clear()
	{

	}

	public function __call($method, $args)
	{
		if (method_exists($this->handler, $method))
			return call_user_func_array(array($this->handler, $method), $args);
		else
			throw new \Exception("{$method} is not exists", 1001);
	}
}