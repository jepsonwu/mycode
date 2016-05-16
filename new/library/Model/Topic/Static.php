<?php
/**
 * 话题
 *
 * @author jeff
 *
 */
class Model_Topic_Static extends Zend_Db_Table
{
	protected $_name = 'topic_static';
	protected $_primary = 'SID';

	public function add($data)
	{
		$re = $this->select()->from($this->_name,$this->_primary)->where("CreateDate = ? ",date("Y-m-d"))->query()->fetch();
		if(empty($re)){
			$this->insert($data);
		}else{
			$this->update($data, array('SID = ?'=>$re['SID']));
		}
		return true;
	}
}