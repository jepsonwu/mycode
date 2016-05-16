<?php
/**
 * 课时
 * 
 * @author kitty
 *
 */
class Model_LessonClass extends Zend_Db_Table
{
	protected $_name = 'lesson_class';
	protected $_primary = 'ClassID';

	/**
	 *  获取课时信息
	 * @param int $classID
	 * @return array
	 */
	public function getClassInfo($classID)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from(array('lc'=>'lesson_class'),array('ClassID','ClassTitle','ClassPic','ClassLink','IsNative'));
		$select->joinInner(array('l'=>'lessons'), "l.LessonID = lc.LessonID",array('LessonID','LessonTitle','LessonType'));
		$row = $select->where('ClassID = ?',$classID)->query()->fetch();
		if(!empty($row)){
			if($row['LessonType'] == 1){
				$classDetailModel = new Model_LessonClassDetail();
				$row['Content'] = $classDetailModel->getContentByClassID($classID);
			}else{
				$row['Content'] = array();
			}
		}
		return $row ? $row : array();
	}
	
	/**
	 *  获取课程下面的课时列表(课程目录)
	 * @param int $lessonID
	 */
	public function getClassByLesson($lessonID)
	{
		$select = $this->select();
		$select->from($this->_name,array('ClassID','ClassTitle','ClassPic','ClassLink','IsNative'));
		$row = $select->where('LessonID = ?',$lessonID)->order('ClassID asc')->where('Status = 1')->query()->fetchAll();
		return $row ? $row : array();
	}
}