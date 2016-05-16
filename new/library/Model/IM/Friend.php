<?php
class Model_IM_Friend extends Zend_Db_Table
{
	protected $_name = 'friends';
	protected $_primary = 'ID';

	/**
	 * 好友关系缓存key
	 * @param unknown $memberID
	 */
	public static function getFriendCacheKey($memberID)
	{
		return 'NewFriend:MemberID:'.$memberID;
	}
	/**
	 *  添加好友
	 * @param string $friendID
	 * @param int $memberID
	 */
	public function addFriendsEachOther($friendID,$memberID)
	{
		$db = $this->getAdapter();
		$sql = "insert into friends(MemberID,FriendID) values(:MemberID,:FriendID) on duplicate key update AddTime = '".date('Y-m-d H:i:s')."'";
		$db->query($sql,array('MemberID'=>$memberID,'FriendID'=>$friendID));
		$db->query($sql,array('MemberID'=>$friendID,'FriendID'=>$memberID));
		$modelMemberFollow = new Model_MemberFollow();
		$relationCode = $modelMemberFollow->getRelation($friendID,$memberID);
		if($relationCode !=-1 ||$relationCode !=3){
			//好友关系加入缓存
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = self::getFriendCacheKey($memberID);
			$redisObj->zadd($cacheKey,time(),$friendID);
			
			$cacheKey2 = self::getFriendCacheKey($friendID);
			$redisObj->zadd($cacheKey2,time(),$memberID);

			//好友 默认加3条数据到队列里
			// $viewModel = new Model_Topic_View();
			// $viewArr1 = $viewModel->select()->from('topic_views',array('ViewID'))->where('MemberID = ?',$friendID)->where('CheckStatus = ?',1)->order('ViewID desc')->limit(3)->query()->fetchAll();
			// $viewArr2 = $viewModel->select()->from('topic_views',array('ViewID'))->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1)->order('ViewID desc')->limit(3)->query()->fetchAll();
			// if(!empty($viewArr1)){
			// 	foreach($viewArr1 as $val){
			// 		$value = 'friend-'.$val['ViewID'].'-'.$friendID.'-'.$memberID;
			// 		$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
			// 	}
			// }
			// if(!empty($viewArr2)){
			// 	foreach($viewArr2 as $val){
			// 		$value = 'friend-'.$val['ViewID'].'-'.$memberID.'-'.$friendID;
			// 		$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
			// 	}
			// }
			$value1 = 'friend-'.$friendID.'-'.$memberID;
			$value2 = 'friend-'.$memberID.'-'.$friendID;
			$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value1);
			$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value2);

		}
		return true;
	}
	
	/**
	 *  删除好友
	 * @param int $friendID
	 * @param int $memberID
	 */
	public function deleteFriendsEachOther($friendID,$memberID)
	{
		$this->delete(array('FriendID = ?'=>$friendID,'MemberID = ?'=>$memberID));
		$this->delete(array('FriendID = ?'=>$memberID,'MemberID = ?'=>$friendID));
		$redisObj = DM_Module_Redis::getInstance();

		$value1 = 'unFriend-'.$friendID.'-'.$memberID;
		$value2 = 'unFriend-'.$memberID.'-'.$friendID;
		$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value1);
		$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value2);
		//财友圈分发事件
		$redisObj->rpush(Model_Shuoshuo::getRedisListKey(),$value1);
		$redisObj->rpush(Model_Shuoshuo::getRedisListKey(),$value2);

		//删除好友缓存关系
		$cacheKey = self::getFriendCacheKey($memberID);
		$redisObj->zrem($cacheKey,$friendID);
		
		$cacheKey2 = self::getFriendCacheKey($friendID);
		$redisObj->zrem($cacheKey2,$memberID);
		return true;
	}
	
	/**
	 *获取好友信息 
	 */
	public function getFriendInfo($memberID){
		if( !$memberID ) {
			throw new Exception('会员ID不能为空');
		}
		$info = $this->select()->from($this->_name,array('FriendID'))->where('MemberID = ?',$memberID)->query()->fetchAll();
		return $info ? $info : array();
	}
	
	/**
	 * 旧版本的好友关系同步成关注关系(已废弃)
	 */
	public function syncFriendRelation()
	{
		$friendArr = $this->select()->from($this->_name,array('MemberID','FriendID','AddTime'))->query()->fetchAll();
		if(!empty($friendArr)){
			$redisObj = DM_Module_Redis::getInstance();
			//同步关注关系表
			$model = new Model_MemberFollow();
			$db = $model->getAdapter();
			$sql = "insert into member_follow(MemberID,FollowedMemberID,CreateTime) select MemberID,FriendID,AddTime from friends";
			$db->query($sql);
			foreach($friendArr as $val){
				$time = strtotime($val['AddTime']);
				//关注数加1
				$followStatisticKey = 'Statistic:Member'.$val['MemberID'];
				$redisObj->hincrby($followStatisticKey,'FollowedCount',1);
				//粉丝数加1
				$fansStatisticKey = 'Statistic:Member'.$val['MemberID'];
				$redisObj->hincrby($fansStatisticKey,'FansCount',1);
				//关注
				$followCacheKey = 'User:Follow:MemberID:'.$val['MemberID'];
				$redisObj->zadd($followCacheKey,$time,$val['FriendID']);
				//粉丝
				$fansCacheKey = 'User:Fans:MemberID:'.$val['MemberID'];
				$redisObj->zadd($fansCacheKey,$time,$val['FriendID']);
			}
		}
	}
	
	/**
	 * 关注关系同步成好友申请关系(一次执行)
	 */
	public function newSyncFriendRelation()
	{
		$applyModel = new Model_FriendApply();
		$relationMode = new Model_FriendApplyRelation();
		$memberFollowModel = new Model_MemberFollow();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $memberFollowModel->select();
			$select->from('member_follow',array('FollowID','MemberID','FollowedMemberID','Content','CreateTime'))->where('FollowID > ?',$lastID);
			$rows = $select->order('FollowID asc')->limit($limit)->query()->fetchAll();	
			if(!empty($rows)){
				foreach ($rows as $val){
					//判断是否是单向关注关系
					$relationCode = $memberFollowModel->getRelation($val['FollowedMemberID'], $val['MemberID']);
					if($relationCode == 1){
						$insertID = $applyModel->insert(array('ApplyMemberID'=>$val['MemberID'],'AcceptMemberID'=>$val['FollowedMemberID'],'LastUpdateTime'=>$val['CreateTime']));
						if($insertID>0){
							$relationMode->insert(array('ApplyID'=>$insertID,'ApplyMemberID'=>$val['MemberID'],'Content'=>$val['Content'],'CreateTime'=>$val['CreateTime']));
						}
					}
				}
			}
				
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['FollowID'];
		}
	}
	
	/**
	 * 获取好友数量
	 * @param unknown $memberID
	 * @return mixed
	 */
	public function getFriendCount($memberID){
		$info = $this->select()->from($this->_name,'count(1) as num')->where('MemberID = ?',$memberID)->query()->fetch();
		return $info['num'];
	}
	
	/**
	 *  是否已好友关系
	 * @param int $memberID
	 * @param int $topicID
	 */
	public function isFriend($memberID,$ownerID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = self::getFriendCacheKey($ownerID);
		$score = $redisObj->zscore($cacheKey,$memberID);
		return $score ? 1 : 0;
	}
	
	/**
	 * 好友关系同步到redis中(执行一次)
	 */
	public function syncFriendCache()
	{
		$memberFollowModel = new Model_MemberFollow();
		$lastID = 0;
		$limit = 1000;
		$redisObj = DM_Module_Redis::getInstance();
		while(true){
			$select = $this->select();
			$select->from($this->_name,array('ID','MemberID','FriendID','AddTime'))->where('ID > ?',$lastID);
			$rows = $select->order('ID asc')->limit($limit)->query()->fetchAll();
			if(!empty($rows)){
				foreach ($rows as $val){
					$cacheKey = self::getFriendCacheKey($val['MemberID']);
					$time = strtotime($val['AddTime']);
					$redisObj->zadd($cacheKey,$time,$val['FriendID']);
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['ID'];
		}
	}
	
}