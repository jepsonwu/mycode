<?php
/**
 *  课程
 * @author Mark
 *
 */
class Model_Lesson extends Zend_Db_Table
{
	protected $_name = 'lessons';
	protected $_primary = 'LessonID';
	
	/**
	 *  获取课程信息
	 * @param int $lessonID
	 */
	public function getInfo($lessonID)
	{
		$select = $this->select()->setIntegrityCheck(false)->from(array('l'=>$this->_name));
		$select->joinInner(array('lm'=>'lesson_modules'), 'lm.ModuleID = l.ModuleID',array('ModuleName'));
		return $select->where('LessonID = ?',$lessonID)->query()->fetch();
	}
	
	
	/**
	 *  增加阅读数
	 * @param int $lessonID
	 * @param int $increament
	 */
	public function increaseViewCount($lessonID,$increament = 1)
	{
		return $this->update(array('ViewCount'=>new Zend_Db_Expr("ViewCount + ".$increament)), array('LessonID = ?'=>$lessonID));
	}
}