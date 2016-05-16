<?php
/**
 * 专题详情
 * 
 * @author kitty
 *
 */
class Model_SpecialContent extends Zend_Db_Table
{
	protected $_name = 'special_contents';
	protected $_primary = 'ContentID';


	/*
	 *根据类型和关联id获取专题详情
	 */
	public function getByTypeID($type,$typeID)
	{
		if($type ==0 || $typeID ==0){
			return 0;
		}
		$info = $this->select()->where('ContentType = ?',$type)->where('ContentTypeID = ?',$typeID)->query()->fetch();
		return !empty($info)? 1 : 0;
	}
}