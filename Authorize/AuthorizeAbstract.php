<?php

/**
 * 加密抽象类
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午3:00
 */
abstract class DM_Authorize_AuthorizeAbstract
{
	/**
	 * 过滤键数组
	 * @var array
	 */
	protected $_filter_key = array("sign");

	/**
	 * 创建加密数据
	 * @param $data
	 * @param bool $is_urlencode
	 * @return string
	 */
	protected function createData($data, $is_urlencode = false)
	{
		$param = "";

		foreach ($data as $key => $value) {
			$param .= $key . "=" . $is_urlencode ? urlencode($value) : $value . "&";
		}

		$param = trim($param, "&");

		//转义字符

		return $param;
	}

	/**
	 * 过滤不需要加密的数据
	 * @param $data
	 * @return array
	 */
	protected function filterData($data)
	{
		return array_diff_key($data, $this->_filter_key);
	}

	/**
	 * [sort_param 按字典排序参数]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	protected function sortData($data)
	{
		ksort($data);

		return $data;
	}
}