<?php
/**
 * 课堂模块
 * 
 * @author kitty
 *
 */
class Model_LessonModule extends Zend_Db_Table
{
	protected $_name = 'lesson_modules';
	protected $_primary = 'ModuleID';


	/*
	 *获取课堂模块
	 */
	public function getAllModule($status = 1,$fields = '*')
	{
		$select= $this->select()->from($this->_name,$fields);
		if($status == 1){
			$select->where('Status = ?',$status);
		}
		$moduleList = $select->order('DisOrder desc')->order('ModuleID desc')->query()->fetchAll();
		return !empty($moduleList)?$moduleList:array();
	}
	
	/**
	 *  获取模块信息
	 * @param int $moduleID
	 */
	public function getModuleInfo($moduleID,$fields = '*')
	{
		$select = $this->select();
		$info = $select->from($this->_name,$fields)->where('ModuleID = ?',$moduleID)->query()->fetch();
		return $info ? $info : array();
	}
}