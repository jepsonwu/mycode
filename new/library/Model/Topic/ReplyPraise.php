<?php
/**
 *  回复点赞
 *  
 * @author kitty
 *
 */
class Model_Topic_ReplyPraise extends Zend_Db_Table
{
	protected $_name = 'reply_praise';
	protected $_primary = 'PraiseID';
	
	/**
	 *  赞
	 * @param int $replyID
	 * @param int $memberID
	 */
	public function addPraise($replyID,$memberID)
	{
		$hasExistsInfo = $this->getInfo($replyID, $memberID);
		if(empty($hasExistsInfo)){
			$data = array('ReplyID'=>$replyID,'MemberID'=>$memberID);
			$newPraiseID = $this->insert($data);
			if($newPraiseID > 0) {
				//增加赞数
				$replyModel = new Model_Topic_Reply();
				$replyModel->increasePraiseNum($replyID);
				//保存会员赞信息至Redis
				$redisObj = DM_Module_Redis::getInstance();
				$cacheKey = 'ReplyPraise:MemberID:'.$memberID;
				$redisObj->zadd($cacheKey,time(),$replyID);
			}
		}
		return true;
	}
	
	
	/**
	 *  取消赞
	 * @param int $replyID
	 * @param int $memberID
	 */
	public function unPraise($replyID,$memberID)
	{
		$ret = $this->delete(array('MemberID = ?'=>$memberID,'ReplyID = ?'=>$replyID));
		if($ret > 0){
			$replyModel = new Model_Topic_Reply();
			$replyModel->increasePraiseNum($replyID,-1);
			
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'ReplyPraise:MemberID:'.$memberID;
			$redisObj->zrem($cacheKey,$replyID);
		}
	}
	
	/**
	 * 获取赞的记录
	 * @param int $replyID
	 * @param int $memberID
	 */
	private function getInfo($replyID,$memberID)
	{
		$info = $this->select()->where('MemberID = ?',$memberID)->where('ReplyID = ?',$replyID)->query()->fetch();
		return $info;
	}
	
	// /**
	//  * 获取最新点赞的人
	//  */
	// public function getPraiseMember($replyID)
	// {
	// 	$info = $this->select($this->_name,array('MemberID'))->where('ViewID = ?',$viewID)->order('PraiseID desc')->limit(3)->query()->fetchALl();
	// 	return $info;
	// }
}