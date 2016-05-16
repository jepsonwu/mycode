<?php
/**
 *  文章评论
 *
 * @author Jeff
 *
 */
class Model_Column_ArticleComment extends Zend_Db_Table
{
	protected $_name = 'column_article_comment';
	protected $_primary = 'CommentID';

	/**
	 * 获取评论列表
	 * @param unknown $memberID
	 * @param unknown $articleID
	 */
	public function getCommentList($memberID,$articleID,$lastID,$pagesize)
	{
		$select = $this->select()->from($this->_name,array('CommentID','CommentContent','MemberID','CreateTime'))
		->where('ArticleID = ?',$articleID)->where('MemberID = ?',$memberID)->where('RelationCommentID = ?',0);
		if($lastID>0){
			$select->where('CommentID < ?',$lastID);
		}
		$info = $select->order('CommentID desc')->limit($pagesize)->query()->fetchAll();
		if(!empty($info)){
			$memberModel = new DM_Model_Account_Members();
			foreach($info as &$val){
				$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
			}
		}
		return $info;
	}
	
	/**
	 * 获取作者对某条评论的回复
	 */
	public function getAuthorReply($CommentID)
	{
		$select = $this->select()->from($this->_name,array('CommentContent','CreateTime'))->where('RelationCommentID = ?',$CommentID)
		->where('Status = ?',1);
		$replyList = $select->query()->fetchAll();
		if(!empty($replyList)){
			foreach($replyList as &$row){
				$row['ReplyCotent'] = $row['CommentContent'];
				$row['CreateTime'] = Model_Topic_View::changeDateStyle($row['CreateTime']);
			}
		}
		return $replyList;

	}
	
	/**
	 * 给文章点赞
	 * @param unknown $articleID
	 * @param unknown $memberID
	 */
	public function addPraise($commentID, $memberID)
	{
		if(!$this->isPraised($commentID, $memberID)){
			//增加赞数
			$this->increasePraiseNum($commentID);
			//保存会员赞信息至Redis
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'Article:Comment:Praise:MemberID:'.$memberID;
			$redisObj->zadd($cacheKey,time(),$commentID);
		}
	}
	
	public function unPraise($commentID, $memberID)
	{
		if($this->isPraised($commentID, $memberID)){
			$this->increasePraiseNum($commentID,-1);
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'Article:Comment:Praise:MemberID:'.$memberID;
			$redisObj->zrem($cacheKey,$commentID);
		}
		return true;
	}
	
	/**
	 *  获取赞的数量
	 * @param int $viewID
	 */
	public function getPraisedNum($commentID)
	{
		$select = $this->select();
		$praiseNum = $select->from($this->_name,'PraiseNum')->where('CommentID = ?',$commentID)->query()->fetchColumn();
		return $praiseNum ? $praiseNum : 0;
	}
	
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increasePraiseNum($commentID,$increament = 1)
	{
		return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('CommentID = ?'=>$commentID));
	}
	
	/**
	 *  是否已赞过
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function isPraised($commentID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Article:Comment:Praise:MemberID:'.$memberID;
		$score = $redisObj->zscore($cacheKey,$commentID);
		return $score ? 1 : 0;
	}
	
	/**
	 * 获取评论信息
	 * @param unknown $CommentID
	 */
	public function getCommentInfo($CommentID)
	{
		$info = $this->select()->from($this->_name,array('MemberID','ArticleID'))->where('CommentID = ?',$CommentID)->query()->fetch();
		return $info;
	}
}