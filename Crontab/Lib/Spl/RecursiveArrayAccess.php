<?php

namespace Lib\Spl;
/**
 * 多维对象数组操作
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-15
 * Time: 下午4:14
 */
class RecursiveArrayAccess implements \ArrayAccess, \Countable, \Iterator
{
	/**
	 * count
	 * @var int
	 */
	private $count = 0;

	private $valid = false;

	private $data = array();

	/**
	 *
	 * @param array $conf
	 */
	protected function __construct(array $conf)
	{
		foreach ($conf as $key => $value) $this[$key] = $value;
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value)
	{
		is_array($value) && $value = new self($value);
		$this->data[$key] = $value;
		$this->count++;
	}

	/**
	 * @param mixed $key
	 * @return string
	 */
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}

	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetUnset($key)
	{
		if (isset($this->data[$key])) {
			unset($this->data[$key]);
			$this->count--;
		}

		return false;
	}

	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return $this->count;
	}

	public function __set($key, $value)
	{
		$this->offsetSet($key, $value);
	}

	/**
	 * @param $key
	 * @return string
	 */
	public function __get($key)
	{
		return $this->offsetGet($key);
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function __unset($key)
	{
		return $this->offsetUnset($key);
	}

	/**
	 *
	 */
	public function __clone()
	{

	}

	public function current()
	{
		return current($this->data);
	}

	public function key()
	{
		return key($this->data);
	}

	public function rewind()
	{
		$this->valid = (false !== reset($this->data));
	}

	public function next()
	{
		$this->valid = (false !== next($this->data));
	}

	public function valid()
	{
		return $this->valid;
	}
}