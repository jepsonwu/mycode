<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-14
 * Time: 上午10:11
 */
class Model_FeatureGroup extends Zend_Db_Table
{
	protected $_name = 'feature_group';
	protected $_primary = 'GID';

	/**
	 * 返回有效的功能列表
	 * @return array
	 */
	public function getFeatures()
	{
		$select = $this->select()->setIntegrityCheck(false);

		$select->from($this->_name . " as g", array("g.GID", "g.Name"));
		$select->joinLeft("feature_members as m", "g.GID=m.GID", array("m.Name as Feature", "m.Controller", "m.Action"));
		$select->where("g.Status =?", 1);
		$select->where("m.Status =?", 1);

		$result = $this->_db->fetchAll($select);

		return empty($result) ? array() : $result;
	}

	public function getGroups()
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name, array("GID", "Name"));
		$select->where("Status =?", 1);

		$result = $this->_db->fetchPairs($select);
		return empty($result) ? array() : $result;
	}

	public function getInfoByID($gid, $fields = null)
	{
		$select = $this->select();
		if (is_null($fields))
			$select->from($this->_name);
		else
			$select->from($this->_name, is_string($fields) ? explode(",", $fields) : $fields);
		$select->where("GID = ?", $gid);

		$result = $this->_db->fetchRow($select);
		return $result;
	}
}