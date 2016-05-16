<?php
/**
 * 专栏活动
 *
 * @author Jeff
 *
 */
class Model_Lecture_Comment extends Zend_Db_Table
{
	protected $_name = 'lecture_comment';
	protected $_primary = 'CommentID';
	
	/**
	 * 添加评论
	 */
	public function addComment($memberID,$videoID,$content)
	{
		$data = array('VideoID'=>$videoID,'MemberID'=>$memberID,'Content'=>$content);
		$insertID = $this->insert($data);
		if($insertID){
			$videoModel = new Model_Lecture_Video();
			$videoModel->increaseCommentNum($videoID);
		}
		return true;
	}
	
	/**
	 * 评论列表
	 */
	public function getComments($lastID,$videoID,$pagesize)
	{
		$select = $this->select()->from($this->_name,array('CommentID','MemberID','Content','CreateTime'))->where('VideoID = ?',$videoID)
		->where('Status =?',1);
		if($lastID>0){
			$select = $select->where('CommentID < ?',$lastID);
		}
		$result = $select->order('CommentID desc')->limit($pagesize)->query()->fetchAll();
		return empty($result)?array():$result;
	}
}