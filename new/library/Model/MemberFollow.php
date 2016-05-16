<?php
/**
 *
 * @author Jeff
 *
 */
class Model_MemberFollow extends Zend_Db_Table
{
	protected $_name = 'member_follow';
	protected $_primary = 'FollowID';
	
	/**
	 * 添加关注
	 * @param int $ownerID 关注者
	 * @param int $memberID 被关注者
	 * @return boolean
	 */
	public function addFollow($memberID,$ownerID,$content=NUll)
	{
		$hasExists = $this->getInfo($memberID,$ownerID);
		if(empty($hasExists)){
			if(empty($content)){
				$data = array('MemberID'=>$ownerID,'FollowedMemberID'=>$memberID);
			}else{
				$data = array('MemberID'=>$ownerID,'FollowedMemberID'=>$memberID,'Content' => $content);
			}
			$newFollowID = $this->insert($data);
			if($newFollowID > 0){
				//保存会员关注信息至Redis
 				$redisObj = DM_Module_Redis::getInstance();
				//关注数加1
				$followStatisticKey = 'Statistic:Member'.$ownerID;
				$redisObj->hincrby($followStatisticKey,'FollowedCount',1);
				//粉丝数加1
				$fansStatisticKey = 'Statistic:Member'.$memberID;
				$redisObj->hincrby($fansStatisticKey,'FansCount',1);
				//关注
				$followCacheKey = 'User:Follow:MemberID:'.$ownerID;
				$redisObj->zadd($followCacheKey,time(),$memberID);
				//粉丝
				$fansCacheKey = 'User:Fans:MemberID:'.$memberID;
				$redisObj->zadd($fansCacheKey,time(),$ownerID);
				//关注 默认加3条数据到队列里
				$viewModel = new Model_Topic_View();
				$select = $viewModel->select()->from('topic_views',array('ViewID'))->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1);
				$viewArr = $select->order('ViewID desc')->limit(3)->query()->fetchAll();
				if(!empty($viewArr)){
					foreach($viewArr as $val){
						$value = 'follow-'.$val['ViewID'].'-'.$memberID.'-'.$ownerID;
						$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
					}
				}
				//判断对方有没有关注我
				$isFriend = $this->isFollowed($ownerID,$memberID);
				if($isFriend){//是好友关系 加入好友关系表
					$friendModel = new Model_IM_Friend();
					$friendModel->addFriendsEachOther($memberID, $ownerID);
				}
			}
		}
		return true;
	}
	
	/**
	 *  获取信息
	 * @param int $ownerID
	 * @param int $memberID
	 */
	private function getInfo($memberID,$ownerID)
	{
		$info = $this->select()->where('MemberID = ?',$ownerID)->where('FollowedMemberID = ?',$memberID)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 *  取消关注
	 * @param int $topicID
	 * @param int $memberID
	 */
	public function unFollow($memberID,$ownerID)
	{
		$ret = $this->delete(array('MemberID = ?'=>$ownerID,'FollowedMemberID = ?'=>$memberID));
		if($ret > 0){
			$redisObj = DM_Module_Redis::getInstance();
			//关注数减1
			$followStatisticKey = 'Statistic:Member'.$ownerID;
			$redisObj->hincrby($followStatisticKey,'FollowedCount',-1);
			//粉丝数减1
			$fansStatisticKey = 'Statistic:Member'.$memberID;
			$redisObj->hincrby($fansStatisticKey,'FansCount',-1);
			//取消关注关系
			$followcacheKey = 'User:Follow:MemberID:'.$ownerID;
			$redisObj->zrem($followcacheKey,$memberID);
			//取消粉丝关系
			$fansCacheKey = 'User:Fans:MemberID:'.$memberID;
			$redisObj->zrem($fansCacheKey,$ownerID);
			//消息队列（财猪首页查询我关注的人的观点时用到）
			$value = 'unFollow-'.$memberID.'-'.$ownerID;
			$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
			$friendModel = new Model_IM_Friend();
			$friendModel->deleteFriendsEachOther($memberID, $ownerID);
		}
	}
	
	/**
	 *  获取我关注的用户
	 * @param int $memberID
	 */
	public function getFollowedMembers($memberID,$lastTime,$pageSize)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'User:Follow:MemberID:'.$memberID;
// 		//分页获取我关注的用户（含有好友的）
// 		$followArr = $redisObj->zRevRangeByScore($cacheKey,'('.$lastTime,'-inf',array('limit' => array(0,$pageSize)));
// 		$result = array();
// 		$lastTime = 0;
		
// 		if(!empty($followArr)){
// 			$lastID=end($followArr);
// 			$lastTime = $redisObj->zscore($cacheKey,$lastID);
// 			$memberModel = new DM_Model_Account_Members();
// 			$result = array();
// 			$memberNoteModel = new Model_MemberNotes();
// 			foreach($followArr as $k=>$val){
// 				$result[$k]['MemberID'] = $val;
// 				$result[$k]['UserName'] = $memberModel->getMemberInfoCache($val,'UserName');
// 				$result[$k]['Avatar'] = $memberModel->getMemberAvatar($val);
// 				$result[$k]['RelationCode'] = $this->getRelation($val,$memberID);
// 				$result[$k]['NoteName'] = $memberNoteModel->getNoteName($memberID,$val);
// 			}
// 		}
// 		$num = $this->getStatistic($memberID);
// 		$totalnum = empty($num['FollowedCount'])?0:$num['FollowedCount'];
// 		return array('Total'=>$totalnum,'lastTime'=>$lastTime,'Rows'=>$result);
		$meberIDs = array();
		$result = array();
		$totalnum = 0;
		//新关注列表（去除好友的）
		if($lastTime == 0){
			$redisObj->zInter('User:Friends:MemberID:'.$memberID, array('User:Fans:MemberID:'.$memberID, $cacheKey));
			$friends = $redisObj->zRange('User:Friends:MemberID:'.$memberID, 0, -1);
			$follows = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
			$followArr = array_values(array_diff($follows, $friends));
			if(!empty($followArr)){
				$totalnum = count($followArr);
				$tempArr=array_chunk($followArr,$pageSize);
				foreach ($tempArr as $key => $item) {
					if($key==0){
						$meberIDs[$key]=$item;
					}else{
						$score = $redisObj->ZSCORE($cacheKey,end($tempArr[$key-1]));
						$meberIDs[$score]=$item;
					}
				}
				$redisObj->set('New:Follow:MemberID:'.$memberID,json_encode($meberIDs));
				$redisObj->set('New:Follow:Num:MemberID:'.$memberID,$totalnum);
			}
		}else{
			$meberIDs = json_decode($redisObj->get('New:Follow:MemberID:'.$memberID),true);
			$totalnum = $redisObj->get('New:Follow:Num:MemberID:'.$memberID);
		}
		$memberModel = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
		if(!empty($meberIDs[$lastTime])){
			foreach($meberIDs[$lastTime] as $k=>$val){
				$result[$k]['MemberID'] = $val;
				$result[$k]['UserName'] = $memberModel->getMemberInfoCache($val,'UserName');
				$result[$k]['Avatar'] = $memberModel->getMemberAvatar($val);
				$result[$k]['RelationCode'] = $this->getRelation($val,$memberID);
				$result[$k]['NoteName'] = $memberNoteModel->getNoteName($memberID,$val);
			}
			$lastTime = $redisObj->ZSCORE($cacheKey,end($meberIDs[$lastTime]));
		}
		return array('Total'=>$totalnum,'lastTime'=>$lastTime,'Rows'=>$result);
	}
	
	/**
	 *  是否已关注
	 * @param int $memberID
	 * @param int $topicID
	 */
	public function isFollowed($memberID,$ownerID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'User:Follow:MemberID:'.$ownerID;
		$score = $redisObj->zscore($cacheKey,$memberID);
		return $score ? 1 : 0;
	}
	
	/**
	 *  是否有新粉丝
	 * @param int $memberID
	 * @param int $lastTime
	 */
	public function hasNewFans($memberID)
	{
		$lastTime = Model_Member::staticData($memberID,'maxFansListTime');
		$counts = 0;
		if($lastTime !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'User:Fans:MemberID:'.$memberID;
			$NewFans = $redisObj->zRevRangeByScore($cacheKey,'+inf','('.$lastTime);
			if(!empty($NewFans)){
				foreach($NewFans as $k=>$val){
					//是否是好友关系
					$isFollwed = $this->isFollowed($val, $memberID);
					if($isFollwed){
						unset($NewFans[$k]);
					}
				}
			}
			$counts = count($NewFans);
			//$counts = $redisObj->zcount($cacheKey,'('.$lastTime,'+inf');
		}
		return $counts ? $counts : 0;
	}
	
	/**
	 * 获取我的粉丝
	 * @param int $memberID
	 * @param unknown $lastTime
	 * @param int $pageSize
	 */
	public function getFansList($memberID,$lastTime,$pageSize)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'User:Fans:MemberID:'.$memberID;
		//粉丝列表（含好友）
// 		$arr = $redisObj->ZREVRANGE($cacheKey,0,0);
// 		$lastMaxPosition = 0;
// 		if(!empty($arr)){
// 			$lastMaxPosition=$redisObj->zscore($cacheKey,$arr[0]);
// 		}
// 		//分页获取我的粉丝
// 		$followArr = $redisObj->zRevRangeByScore($cacheKey,'('.$lastTime,'-inf',array('limit' => array(0,$pageSize)));
// 		$result = array();
// 		$lastTime = 0;
// 		if(!empty($followArr)){
// 			//标识最近粉丝时间
// 			if($lastTime == '+inf'){
// 				$maxFansID = reset($followArr);
// 				if($maxFansID){
// 					$maxFansTime = $redisObj->zscore($cacheKey,$maxFansID);
// 					Model_Member::staticData($memberID,'maxFansListTime',$maxFansTime);
// 				}
// 			}
			
// 			$lastID=end($followArr);
// 			$lastTime = $redisObj->zscore($cacheKey,$lastID);
			
// 			$memberModel = new DM_Model_Account_Members();
// 			$viewModel = new Model_Topic_View();
// 			$shuoshuoModel = new Model_Shuoshuo();
// 			$result = array();
// 			foreach($followArr as $k=>$val){
// 				$result[$k]['MemberID'] = $val;
// 				$result[$k]['UserName'] = $memberModel->getMemberInfoCache($val,'UserName');
// 				$result[$k]['Avatar'] = $memberModel->getMemberAvatar($val);
// 				$result[$k]['ViewCount'] = $viewModel->getViewCount($val);
// 				$result[$k]['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($val);
// 				$result[$k]['RelationCode'] = $this->getRelation($val,$memberID);
// 				$result[$k]['Content'] = $this->getInfo($memberID, $val)['Content'];
// 			}
// 		}
// 		$num = $this->getStatistic($memberID);
// 		$totalnum = empty($num['FansCount'])?0:$num['FansCount'];
// 		return array('Total'=>$totalnum,'lastTime'=>$lastTime,'Rows'=>$result);
		$meberIDs = array();
		$result = array();
		$totalnum = 0;
		//粉丝列表（去除好友）
		if($lastTime == 0){
			$redisObj->zInter('User:Friends:MemberID:'.$memberID, array('User:Follow:MemberID:'.$memberID, $cacheKey));
			$friends = $redisObj->zRange('User:Friends:MemberID:'.$memberID, 0, -1);
			$fans = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
			if(!empty($fans)){
				$maxFansID = reset($fans);
				if($maxFansID){
					$maxFansTime = $redisObj->zscore($cacheKey,$maxFansID);
					Model_Member::staticData($memberID,'maxFansListTime',$maxFansTime);
				}
			}
			$fanArr = array_values(array_diff($fans, $friends));
			if(!empty($fanArr)){
				$totalnum = count($fanArr);
				$tempArr=array_chunk($fanArr,$pageSize);
				foreach ($tempArr as $key => $item){
					if($key==0){
						$meberIDs[$key]=$item;
					}else{
						$score = $redisObj->ZSCORE($cacheKey,end($tempArr[$key-1]));
						$meberIDs[$score]=$item;
					}
				}
				$redisObj->set('New:Fans:MemberID:'.$memberID,json_encode($meberIDs));
				$redisObj->set('New:Fans:Num:MemberID:'.$memberID,$totalnum);
			}
		}else{
			$meberIDs = json_decode($redisObj->get('New:Fans:MemberID:'.$memberID),true);
			$totalnum = $redisObj->get('New:Fans:Num:MemberID:'.$memberID);
		}
		$memberModel = new DM_Model_Account_Members();
		$viewModel = new Model_Topic_View();
		$shuoshuoModel = new Model_Shuoshuo();
		$authenticateModel =new Model_Authenticate();
		$bestModel = new Model_Best_Best();
		if(!empty($meberIDs[$lastTime])){
			foreach($meberIDs[$lastTime] as $k=>$val){
				$result[$k]['MemberID'] = $val;
				$result[$k]['UserName'] = $memberModel->getMemberInfoCache($val,'UserName');
				$result[$k]['Avatar'] = $memberModel->getMemberAvatar($val);
				$result[$k]['ViewCount'] = $viewModel->getViewCount($val);
				$result[$k]['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($val);
				$result[$k]['RelationCode'] = $this->getRelation($val,$memberID);
				$result[$k]['Content'] = $this->getInfo($memberID, $val)['Content'];
				$best_info = $bestModel->getNewBestInfo($val);
				$result[$k]['IsBest'] = empty($best_info)?0:1;
				$authenticateInfo = $authenticateModel->getInfoByMemberID($val,1);
				$result[$k]['AuthenticateType'] = empty($authenticateInfo)?0:$authenticateInfo['AuthenticateType'];
			}
			$lastTime = $redisObj->ZSCORE($cacheKey,end($meberIDs[$lastTime]));
		}
		return array('Total'=>$totalnum,'lastTime'=>$lastTime,'Rows'=>$result);
	}
	
	/**
	 * 获取关注数，粉丝数
	 * @param int $memberID
	 */
	public function getStatistic($memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$StatisticKey = 'Statistic:Member'.$memberID;
		$result = $redisObj->hmget($StatisticKey,array('FansCount','FollowedCount'));
		return $result;
	}
	
	/**
	 *  会员之间关系
	 *  -1 : 自身  0: 无关系  1：已关注  2：粉丝   3：好友   4:好友申请待通过
	 * @param int $memberID
	 * @param int $currentMemberID
	 */
	public function getRelation($memberID, $currentMemberID)
	{
		$relationCode = 0;
		$isFollow = 0;
		$isFans = 0;
		if($memberID == $currentMemberID){
			$relationCode = -1;
		} else {
			if($this->isFollowed($memberID, $currentMemberID)){
				$relationCode = 1;
			}
			
			if($this->isFollowed($currentMemberID, $memberID)){
				$relationCode = 2;
			}
			$model = new Model_IM_Friend();
			if($model->isFriend($memberID, $currentMemberID)){
				$relationCode = 3;
			}
			$requestObj = DM_Controller_Front::getInstance()->getHttpRequest();
			$currentVersion = $requestObj->getParam('currentVersion','0');
			if(version_compare($currentVersion,'2.2.3',">=")){
				$applyModel = new Model_FriendApply();
				if($applyModel->isSendApply($memberID, $currentMemberID))
				{
					$relationCode = 4;
				}
			}
		}
		return $relationCode;
	}
}