<?php

/**
 * md5
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午3:00
 */
class DM_Authorize_Md5 extends DM_Authorize_AuthorizeAbstract
{
	public static $instance = null;

	static public function getInstance()
	{
		is_null(self::$instance) &&
		self::$instance = new self();

		return self::$instance;
	}

	/**
	 * 验证签名
	 * @param $data
	 * @param $key
	 * @param $sign
	 * @return bool
	 */
	public function verify($data, $key, $sign)
	{
		$self_sign = md5($this->createData($this->sortData($this->filterData($data))) . $key);
		return $sign === $self_sign;
	}

	/**
	 * 签名
	 * @param $data
	 * @param $key
	 * @return string
	 */
	public function sign($data, $key)
	{
		return md5($this->createData($this->sortData($this->filterData($data))) . $key);
	}
}