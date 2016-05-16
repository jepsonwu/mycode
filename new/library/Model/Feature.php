<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-14
 * Time: 上午10:11
 */
class Model_Feature extends Zend_Db_Table
{
	protected $_name = 'feature_members';
	protected $_primary = 'FID';

	public function getInfoByID($fid, $fields = null)
	{
		$select = $this->select();
		if (is_null($fields))
			$select->from($this->_name);
		else
			$select->from($this->_name, is_string($fields) ? explode(",", $fields) : $fields);
		$select->where("FID = ?", $fid);

		$result = $this->_db->fetchRow($select);
		return $result;
	}
}