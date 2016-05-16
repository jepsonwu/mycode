<?php
/**
 *  话题关注
 *  
 * @author Mark
 *
 */
class Model_Topic_Follow extends Zend_Db_Table
{
	protected $_name = 'topic_follows';
	protected $_primary = 'FollowID';
	
	public static function getFollowKey($memberID)
	{
		return 'Follow:MemberID:'.$memberID;
	}
	
	public function addFollow($topicID,$memberID)
	{
		$hasExists = $this->getInfo($topicID,$memberID);
		if(empty($hasExists)){
			$data = array('TopicID'=>$topicID,'MemberID'=>$memberID);
			$newFollowID = $this->insert($data);
			if($newFollowID > 0){
				//增加关注数
				$topicModel = new Model_Topic_Topic();
				$topicModel->increaseFollowNum($topicID);
				
				//保存会员关注信息至Redis
				$redisObj = DM_Module_Redis::getInstance();
				$cacheKey = self::getFollowKey($memberID);
				$redisObj->zadd($cacheKey,time(),$topicID);
			}
		}
		return true;
	}
	
	/**
	 *  获取信息
	 * @param int $topicID
	 * @param int $memberID
	 */
	private function getInfo($topicID,$memberID)
	{
		$info = $this->select()->where('MemberID = ?',$memberID)->where('TopicID = ?',$topicID)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 *  取消关注
	 * @param int $topicID
	 * @param int $memberID
	 */
	public function unFollow($topicID,$memberID)
	{
		$ret = $this->delete(array('MemberID = ?'=>$memberID,'TopicID = ?'=>$topicID));
		if($ret > 0){
			$topicModel = new Model_Topic_Topic();
			$topicModel->increaseFollowNum($topicID,-1);
			
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = self::getFollowKey($memberID);
			$redisObj->zrem($cacheKey,$topicID);
		}
	}


	/**
	 *  获取用户已关注话题ID
	 * @param int $memberID
	 */
	public function getFollowIDArr($memberID)
	{
		$db = $this->getAdapter();
		$topicIDArr = $db->fetchCol("SELECT TopicID FROM topic_follows WHERE `MemberID` = :MemberID",array("MemberID" => $memberID));
		return $topicIDArr ? $topicIDArr : array();
	}
}