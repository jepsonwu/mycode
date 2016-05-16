<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-27
 * Time: 下午12:59
 */
class Model_Best_BestTitle extends Zend_Db_Table
{
	protected $_name = 'best_title';
	protected $_primary = 'TID';

	/**
	 * 获取所有头衔
	 * @return mixed
	 */
	public function getAllTitle($type = "fetchPairs")
	{
		$select = $this->select();
		$select->from($this->_name, array("TID", "Name"));
		$select->where("Status = ?", 1);

		$result = $this->_db->$type($select);
		return $result;
	}

	/**
	 * 通过ID查找头衔
	 * @param $tid
	 * @param array $field
	 * @return array|mixed
	 */
	public function getInfoByID($tid, $field = null)
	{
		$select = $this->select();
		if (is_null($field))
			$select->from($this->_name);
		else
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field);

		$select->where("TID in(?)", is_array($tid) ? $tid : array($tid));

		$result = $this->_db->fetchAll($select);

		return empty($result) ? array() : (count($result) == 1 ? current($result) : $result);
	}
}