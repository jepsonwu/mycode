<?php
/**
 *  课时内容
 * @author Mark
 *
 */
class Model_LessonClassDetail extends Zend_Db_Table
{
	protected $_name = 'lesson_class_details';
	protected $_primary = 'DetailID';
	
	/**
	 *  获取内容
	 * @param int $classID
	 */
	public function getContentByClassID($classID)
	{
		$select = $this->select();
		$fields = array('DetailType','Content','FontColor','IsBold','ImgWidth','ImgHeight');
		$res = $select->from($this->_name,$fields)->where('ClassID = ?',$classID)->query()->fetchAll();
		return $res ? $res : array();
	}
}