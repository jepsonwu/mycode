<?php

/**
 * 专栏活动
 *
 * @author Jeff
 *
 */
class Model_Lecture_Video extends Zend_Db_Table
{
	protected $_name = 'lecture_video';
	protected $_primary = 'VideoID';

	
	/**
	 * 给文章点赞
	 * @param unknown $articleID
	 * @param unknown $memberID
	 */
	public function addPraise($videoID, $memberID)
	{
		if(!$this->isPraised($videoID, $memberID)){
			//增加赞数
			$this->increasePraiseNum($videoID);
			//保存会员赞信息至Redis
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'Video:Praise:MemberID:'.$memberID;
			$redisObj->zadd($cacheKey,time(),$videoID);
		}
	}
	
	public function unPraise($videoID, $memberID)
	{
		$this->increasePraiseNum($videoID,-1);
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Video:Praise:MemberID:'.$memberID;
		$redisObj->zrem($cacheKey,$videoID);
		return true;
	}
	
	/**
	 *  获取赞的数量
	 * @param int $viewID
	 */
	public function getPraisedNum($videoID)
	{
		$select = $this->select();
		$praiseNum = $select->from($this->_name,'PraiseNum')->where('VideoID = ?',$videoID)->query()->fetchColumn();
		return $praiseNum ? $praiseNum : 0;
	}
	
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increasePraiseNum($videoID,$increament = 1)
	{
		return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('VideoID = ?'=>$videoID));
	}
	
	/**
	 *  是否已赞过
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function isPraised($videoID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Video:Praise:MemberID:'.$memberID;
		$score = $redisObj->zscore($cacheKey,$videoID);
		return $score ? 1 : 0;
	}
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increaseCommentNum($videoID,$increament = 1)
	{
		return $this->update(array('CommentNum'=>new Zend_Db_Expr("CommentNum + ".$increament)),array('VideoID = ?'=>$videoID));
	}
	
	/**
	 * 是否有新的视频
	 */
	public function hasNewVideos($memberID)
	{
		$lastVideoID = Model_Member::staticData($memberID,'lastVideoID');
		$lastVideoID = empty($lastVideoID)?0:$lastVideoID;
		$info = $this->select()->from($this->_name,array('VideoID'))
			->where('Status = ?',1)->where('VideoID > ?',$lastVideoID)->limit(1)->query()->fetch();
		return empty($info) ? 0 : 1;
	}
	
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increasePlayNum($videoID,$increament = 1)
	{
		return $this->update(array('PlayNum'=>new Zend_Db_Expr("PlayNum + ".$increament)),array('VideoID = ?'=>$videoID));
	}
	
	
}