<?php
class DM_Model_Table_Levels extends  DM_Model_Table
{
	protected $_name = 'levels';
	
	protected $_primary = 'LevelID';
	
	public function setLevel($id,$info)
	{
		$where = $this->getAdapter()->quoteInto('level_id = ?', $id);
		return $this->_db->update($this->_name, array('level_name'=>$info['level_name'],'level_desc'=>$info['level_desc']),$where);
	}
	
	public function getArrayInfo()
	{
		$select = $this->_db->select();
		$select->from($this->_name,array('level_id','cn_name'));
		$res = $this->_db->fetchPairs($select);
		return $res;
	}
	
	public function checkExist($info)
	{
		$select = $this->_db->select();
		$select->from($this->_name,'count(*) as isrepeat');
		foreach ($info as $info_k=>$info_v)
		{
			$select->where("$info_k = ?",$info_v);
		}
	
		return $this->_db->fetchOne($select);
	}
}