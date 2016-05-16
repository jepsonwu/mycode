<?php
/**
 *  专栏标签
 *
 * @author Jeff
 *
 */
class Model_Column_ColumnFocus extends Zend_Db_Table
{
	protected $_name = 'column_focus';
	protected $_primary = 'ColumnFocusID';

	public function addFocus($columnID,$focusID)
	{
		$hasExists = $this->getInfo($columnID,$focusID);
		if(empty($hasExists)){
			$data = array('ColumnID'=>$columnID,'FocusID'=>$focusID);
			$newFocusID = $this->insert($data);
		}
		return true;
	}

	/**
	 *  获取信息
	 * @param int $topicID
	 * @param int $focusID
	 */
	public function getInfo($columnID,$focusID=null,$fields="*")
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as cf',$fields)->where('cf.ColumnID = ?',$columnID);
		$select->joinLeft('focus as f','f.FocusID = cf.FocusID','FocusName');
		if(!is_null($focusID)){
			$select->where('cf.FocusID = ?',$focusID);
			return $select->query()->fetch();
		}
		return $select->query()->fetchAll();
	}

}