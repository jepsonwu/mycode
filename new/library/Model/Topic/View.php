<?php
/**
 * 观点
 * @author Mark
 *
 */
class Model_Topic_View extends Zend_Db_Table
{
	protected $_name = 'topic_views';
	protected $_primary = 'ViewID';
	
	const NEW_ANONYMOUS_KEY = "NEW_ANONYMOUS_INFO";
	
	public static function getFollowedViewKey()
	{
		return 'QueueFollowedView';
	}
	
	/**
	 * 新增观点
	 * @param int $memberID
	 * @param int $topicID
	 * @param int $viewContent
	 */
	public function addView($memberID,$topicID,$viewContent,$isAnonymous=0,$anonymousUserName='',$anonymousAvatar='',$AllTopicIDs='',$parentID=0,$isHand=1)
	{
		$data = array(
							'MemberID'=>$memberID,
							'TopicID'=>$topicID,
							'ViewContent'=>$viewContent,
							'CheckStatus'=>1,
							'IsAnonymous'=>$isAnonymous,
							'AnonymousUserName'=>$anonymousUserName,
							'AnonymousAvatar'=>$anonymousAvatar,
							'AllTopicIDs'=>$AllTopicIDs,
							'ParentID'=>$parentID
					);
		$newViewID = $this->insert($data);
		if($newViewID > 0){
			$topicModel = new Model_Topic_Topic();
			$topicModel->increaseViewNum($topicID);
			$topicModel->updateLastViewTime($topicID);
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'viewNum:TopicID:'.$topicID.':Date:'.date('Y-m');
			$redisObj->ZINCRBY($key,1,$memberID);
			$redisObj->EXPIRE($key,35*86400);
			//保存在redis队列里
			if($isAnonymous !=1 && $isHand == 1){
				$cacheKey = self::getFollowedViewKey();
				$value = 'publish-'.$newViewID.'-'.$memberID;
				$redisObj->rpush($cacheKey,$value);
				
				$key = 'Friends:View:MemberID'.$memberID;
				$redisObj->zadd($key,$newViewID,$newViewID);
				
				$lastViewID = Model_Member::staticData($memberID,'lastFriendViewID');
				if($newViewID == intval($lastViewID) + 1){
					//标注上次查阅位置
					Model_Member::staticData($memberID,'lastFriendViewID',$newViewID);
				}
			}
		}
		return $newViewID;
	}
	
	/**
	 *  增加回复数
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increaseReplyNum($viewID,$increament = 1)
	{
		$where = array('ViewID = ?'=>$viewID);
		if($increament < 0){
			$where['ReplyNum > ?'] = 0;
		}
		return $this->update(array('ReplyNum'=>new Zend_Db_Expr("ReplyNum + ".$increament)),$where);
	}
	
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increasePraiseNum($viewID,$increament = 1)
	{
		return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('ViewID = ?'=>$viewID));
	}
	
	/**
	 *  是否已赞过
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function isPraised($viewID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Praise:MemberID:'.$memberID;
		$score = $redisObj->zscore($cacheKey,$viewID);
		return $score ? 1 : 0;
	}
	
	/**
	 *  获取赞的数量
	 * @param int $viewID
	 */
	public function getPraisedNum($viewID)
	{
		$select = $this->select();
		$praiseNum = $select->from($this->_name,'PraiseNum')->where('ViewID = ?',$viewID)->query()->fetchColumn();
		return $praiseNum ? $praiseNum : 0;
	}
	
	/**
	 * 获取观点信息
	 */
	public function getViewInfo($viewID)
	{
		return $this->select()->where('ViewID = ?',$viewID)->query()->fetch();
	}
	
	/**
	 * 获取某人的观点数量
	 */
	public function getViewCount($memberID)
	{
		$select = $this->select()->from($this->_name,'count(1) as num');
		$select->where('MemberID = ?',$memberID);
		$select->where('CheckStatus = ?',1);
		$select->where('ParentID = ?',0);
		$row = $select->query()->fetch();
		return $row['num'];
	}
	
	/**
	 *  是否有最新观点
	 * @param int $memberID
	 * @param int $lastViewID
	 */
	public function hasNewFollowedMemberViews($memberID)
	{
		$lastViewID = Model_Member::staticData($memberID,'lastFollowedMemberViewID');
		$counts = 0;
		if($lastViewID !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'Followed:View:MemberID'.$memberID;
			$counts = $redisObj->zcount($key,'('.$lastViewID,'+inf');
		}
		return $counts ? $counts : 0;
	}
	
	/**
	 *  好友是否有最新观点
	 * @param int $memberID
	 * @param int $lastViewID
	 */
	public function hasNewFriendViews($memberID)
	{
		$lastViewID = Model_Member::staticData($memberID,'lastFriendViewID');
		$counts = 0;
		if($lastViewID !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'Friends:View:MemberID'.$memberID;
			$counts = $redisObj->zcount($key,'('.$lastViewID,'+inf');
		}
		return $counts ? $counts : 0;
	}
	
	public function hasNewFollowedTopicViews($memberID)
	{
		$lastViewID = Model_Member::staticData($memberID,'lastFollowedTopicViewID');
		$topicModel = new Model_Topic_Topic();
		$followArr = $topicModel->getFollowedTopics($memberID,false);
		$info = array();
		if(!empty($followArr) && !empty($lastViewID)){
			$info = $this->select()->from($this->_name,array('ViewID'))->where('TopicID in (?)',$followArr)->where('MemberID != ?',$memberID)
			->where('CheckStatus = ?',1)->where('ViewID > ?',$lastViewID)->where('IsAnonymous = 0')->order('ViewID desc')->limit(1)->query()->fetch();
		}
		return empty($info) ? 0 : 1;
	}
	
	/**
	 * 财主首页获取我关注的人的观点
	 * @param int $memberID
	 * @param unknown $lastTime
	 * @param int $pagesize
	 * @author Jeff 2015-07-13
	 */
	public function followedMemberViews($memberID,$lastViewID,$pagesize,$lastBarNum,$isNew=0)
	{
		$viewInfo = array();
		$redisObj = DM_Module_Redis::getInstance();
		$key = 'Followed:View:MemberID'.$memberID;
		$viewArr = $redisObj->zRevRangeByScore($key,'('.$lastViewID,'-inf',array('limit' => array(0,$pagesize)));
		if($lastViewID == '+inf'){
			$maxPosition = reset($viewArr);
			if($maxPosition){
				Model_Member::staticData($memberID,'lastFollowedMemberViewID',$maxPosition);
			}
		}		
		$viewInfo = array();
		$result = array();
		$maxNum = $lastBarNum;
		if(!empty($viewArr)){
			$select = $this->select();
			$select->from($this->_name,array('ContentType'=>new Zend_Db_Expr(2),'ViewID','TopicID','MemberID','PraiseNum','ReplyNum','CreateTime','ViewContent'))->where('ViewID in (?)',$viewArr)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1);
			$select->order("ViewID desc");
			$viewInfo = $select->query()->fetchAll();
			$memberModel = new DM_Model_Account_Members();
			$viewImageModel = new Model_Topic_ViewImage();
			$topicModel = new Model_Topic_Topic();
			$memberNoteModel = new Model_MemberNotes();
			if(!empty($viewInfo)){
				$adsModel = new Model_Ads();	
				$adsCount = intval(count($viewInfo) / 5)+1;
				$adsType = 1;
				if($isNew){//新话题首页
					$adsType = 5;
				}
				$ads = $adsModel->getAdsList($lastBarNum,$adsCount,$adsType);
				$adsArr = $ads['ads'];
				$maxNum = $ads['maxNum'];
				foreach($viewInfo as $key=>&$val){
					if(!empty($adsArr)){
						if($key % 5 === 0 && !($isNew ==1 && $key == 0 && $lastBarNum == 0)){
							if(!empty($adsArr[intval($key / 5)])){
								$result[] = $adsArr[intval($key / 5)];
							}
					 	}
					}
					$topicInfo = $topicModel->getTopicInfo($val['TopicID'],null);
					$val['TopicName'] = $topicInfo['TopicName'];
					$val['IsPraised'] = $this->isPraised($val['ViewID'], $memberID);
					$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['Images'] = $viewImageModel->getImages($val['ViewID']);
					$result[] = $val;
				}
			}
		}
		return array('lastBarNum'=>$maxNum,'Rows'=>$result);
	}
	
	/**
	 * 转化时间格式
	 * @param unknown $date
	 * @author Jeff 2015-07-13
	 */
	static  function changeDateStyle($date)
	{
		$year = date('Y',time());
		$originyear = date('Y',strtotime($date));
		$day = strtotime(date('Y-m-d',time()));
		$day2 = strtotime(date('Y-m-d',strtotime($date)));
		if($year != $originyear){
			$createTime = date('Y-m-d',strtotime($date));
		}elseif($day-$day2>=3*86400){
			$createTime = date('m-d',strtotime($date));
		}elseif($day-$day2 == 2*86400){
			$createTime = '前天 ';
		}elseif($day-$day2 == 86400){
			$createTime = '昨天' ;
		}elseif(time()-strtotime($date)>3600){
			$createTime = floor((time()-strtotime($date))/3600).'小时前';
		}else{
			$createTime = floor((time()-strtotime($date))/60);
			if($createTime>0){
				$createTime = $createTime.'分钟前';
			}else{
				$createTime = '刚刚';
			}
		}
		return $createTime;
	}
	
	/**
	 * 定时任务执行分发观点
	 * @author Jeff 2015-07-14
	 */
	public function handViews()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$viewKey = self::getFollowedViewKey();
// 		$viewCount = $redisObj->lsize($viewKey);
// 		if(!empty($viewCount)){
			while(true){
				$value = $redisObj->lpop($viewKey);
				if(empty($value)){
					usleep(500000);//0.5秒
		    		$s = date('s');
		    		if( $s >= 58){
		    			break;
		    		}
		    		continue;
				}
				$arr = explode('-',$value);
				if($arr[0] == 'publish'){//发布观点事件
					$viewID = $arr[1];
					$ownerID = $arr[2];
					
// 					//查询当前观点发布者的粉丝，把观点分发给粉丝
// 					$cacheKey = 'User:Fans:MemberID:'.$ownerID;
// 					$followArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
// 					if(empty($followArr)){
// 						$followArr = array($ownerID);
// 					}else{
// 						//$followArr[] = $ownerID;
// 						array_unshift($followArr,$ownerID);
// 					}
// 					foreach($followArr as $memberID){
// 						$key = 'Followed:View:MemberID'.$memberID;
// 						$redisObj->zadd($key,$viewID,$viewID);
// 					}

					//查询当前观点发布者的好友
					//$friendsKey = 'User:Friends:MemberID:'.$ownerID;
					$friendsKey = Model_IM_Friend::getFriendCacheKey($ownerID);
					$friendsArr = $redisObj->zRevRangeByScore($friendsKey,'+inf','-inf');
					if(empty($friendsArr)){
						$friendsArr = array($ownerID);
					}else{
						array_unshift($friendsArr,$ownerID);
					}
					foreach($friendsArr as $memberID){
						$f_key = 'Friends:View:MemberID'.$memberID;
						$redisObj->zadd($f_key,$viewID,$viewID);
					}

				}elseif($arr[0] == 'delete'){//删除观点事件
// 					$viewID = $arr[1];
// 					$ownerID = $arr[2];
// 					//查询当前观点发布者的粉丝，把观点分发给粉丝
// 					$cacheKey = 'User:Fans:MemberID:'.$ownerID;
// 					$followArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
// 					if(empty($followArr)){
// 						$followArr = array($ownerID);
// 					}else{
// 						//$followArr[] = $ownerID;
// 						array_unshift($followArr,$ownerID);
// 					}

// 					foreach($followArr as $memberID){
// 						$key = 'Followed:View:MemberID'.$memberID;
// 						$redisObj->zrem($key,$viewID);
// 					}

					//查询当前观点发布者的好友
					$friendsKey = Model_IM_Friend::getFriendCacheKey($ownerID);
					$friendsArr = $redisObj->zRevRangeByScore($friendsKey,'+inf','-inf');
					if(empty($friendsArr)){
						$friendsArr = array($ownerID);
					}else{
						array_unshift($friendsArr,$ownerID);
					}

					foreach($friendsArr as $memberID){
						$f_key = 'Friends:View:MemberID'.$memberID;
						$redisObj->zrem($f_key,$viewID);
					}

				}elseif($arr[0] == 'follow'){//关注事件
// 					$viewID = $arr[1];
// 					$ownerID = $arr[2];//观点所有者
// 					$memberID = $arr[3];
// 					//查询当前观点发布者的粉丝，把观点分发给粉丝
// 					$key = 'Followed:View:MemberID'.$memberID;
// 					$redisObj->zadd($key,$viewID,$viewID);
				}elseif($arr[0] == 'unFollow'){//取消关注事件
// 					$memberID = $arr[1];//被关注的人
// 					$ownerID = $arr[2];//关注者
// 					$viewArr = $this->select()->from($this->_name,array('ViewID'))->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1)->query()->fetchAll();
// 					if(!empty($viewArr)){
// 						$key = 'Followed:View:MemberID'.$ownerID;
// 						foreach($viewArr as $val){
// 							$redisObj->zrem($key,$val['ViewID']);
// 						}
// 					}
				}elseif ($arr[0]== 'friend') {//添加好友事件
					//$viewID = $arr[1];
					$ownerID = $arr[1];//观点所有者
					$memberID = $arr[2];
					//查询当前观点发布者的好友，把观点分发给好友
					$viewArr = $this->select()->from($this->_name,array('ViewID'))->where('MemberID = ?',$ownerID)->where('CheckStatus = ?',1)->where('IsAnonymous = ?',0)->order('ViewID desc')->limit(3)->query()->fetchAll();					
					if(!empty($viewArr)){
						foreach ($viewArr as $val) {
							$f_key = 'Friends:View:MemberID'.$memberID;
							$redisObj->zadd($f_key,$val['ViewID'],$val['ViewID']);
						}
					}
				}elseif ($arr[0]== 'unFriend') {
					$memberID = $arr[1];
					$ownerID = $arr[2];
					$viewArr = $this->select()->from($this->_name,array('ViewID'))->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1)->query()->fetchAll();
					if(!empty($viewArr)){
						$f_key = 'Friends:View:MemberID'.$ownerID;
						foreach($viewArr as $val){
							$redisObj->zrem($f_key,$val['ViewID']);
						}
					}
				}
			}
// 		}
	}

	/**
	 *  获取热门观点
	 * @param int $memberID
	 */
	public function getHotViews($isGetInfo = true)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'HotViews';
		$viewIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		if($isGetInfo == true){
			$result = array();
			if(!empty($viewIDArr)){
				$select = $this->select()->setIntegrityCheck(false);
				$select->from($this->_name,array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','TopicID'))->where('ViewID in (?)',$viewIDArr)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1);
				$select->joinLeft('topics as t','t.TopicID = tv.TopicID','TopicName')->where('t.CheckStatus =?',1);
				$select->order(new Zend_Db_Expr("field(ViewID,".implode(',',$viewIDArr).")"));
				$result = $select->query()->fetchAll();
			}
			return $result;
		}
		return $viewIDArr;
	}

	/**
	 * 获取观点评分总和($isToday=1 表示最近24小时内观点评分总和)
	 */
	public function getViewScore($infoID,$type,$isToday=0)
	{
		$result = array();
		$select = $this->select()->from($this->_name,'SUM(Liveness) as score')->where('CheckStatus = ?',1)->where('IsAnonymous= ?',0);
		if($type == 'member'){
			$select = $select->where('MemberID = ?',$infoID);
		}elseif($type == 'topic'){
			$select = $select->where('TopicID = ?',$infoID);
			if($isToday){
				$time = time()-86400;
				$select = $select->where('UNIX_TIMESTAMP(CreateTime) > ? ',$time);
			}
		}
		$result = $select->query()->fetch();
		return empty($result['score'])?0:$result['score'];
	}
	
	/**
	 * @param int $memberID
	 * 获取某个人参与的话题数量
	 */
	public function getTopicNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(distinct(TopicID)) as num')->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1)->query()->fetch();
		return $info['num'];
	}

	/*
	 *根据id获取观点信息（今日话题讨论）
	 */
	public function getTopicVoteInfo($viewIDArr,$period,$memberID)
	{
		if(!empty($viewIDArr)){
			$select = $this->select()->setIntegrityCheck(false);
			$select->from($this->_name.' as view',array('ViewID','TopicID','MemberID','ViewContent'))->where('view.ViewID in (?)',$viewIDArr)->where('view.CheckStatus = ?',1);
			$select->joinLeft('topic_vote as vote','view.ViewID = vote.ViewID',array('TopicVoteID','VoteCount'))->where('vote.period = ?',$period);
			$select->order('vote.VoteCount desc');
			$viewInfo = $select->query()->fetchAll();

			$memberModel = new DM_Model_Account_Members();
			$topicModel = new Model_Topic_Topic();
			$voteListModel = new Model_Topic_VoteList();
			if(!empty($viewInfo)){
				foreach($viewInfo as $key=>&$item){
					$topicInfo = $topicModel->getTopicInfo($item['TopicID'],null);
					$item['TopicName'] = $topicInfo['TopicName'];					
					$item['Avatar'] = $memberModel->getMemberAvatar($item['MemberID']);
					$item['UserName'] = $memberModel->getMemberInfoCache($item['MemberID'],'UserName');
					$isVoted = $voteListModel->isVoted($item['TopicVoteID'],$memberID);
					$item['IsVoted'] = $isVoted ? 1 : 0;
				}
			}
		}
		return $viewInfo;
	}



	/*
	 *获取好友观点
	 */
	public function getFriendsViewList($memberID,$lastViewID,$pagesize,$isGetInfo = true)
	{
		$viewInfo = array();
		$redisObj = DM_Module_Redis::getInstance();
		$key = 'Friends:View:MemberID'.$memberID;
		$viewArr = $redisObj->zRevRangeByScore($key,'('.$lastViewID,'-inf',array('limit' => array(0,$pagesize)));
		if($lastViewID == '+inf'){
			$maxPosition = reset($viewArr);
			if($maxPosition){
				Model_Member::staticData($memberID,'lastFriendViewID',$maxPosition);
			}
		}
		if($isGetInfo == true){		
			$viewInfo = array();
			if(!empty($viewArr)){
				$select = $this->select()->setIntegrityCheck(false);
				$select->from($this->_name.' as tv',array('ViewID','TopicID','MemberID','PraiseNum','ReplyNum','CreateTime','ViewContent'))->where('tv.ViewID in (?)',$viewArr)->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous != ?',1);
				$select->joinLeft('topics as t','t.TopicID = tv.TopicID','')->where('t.CheckStatus = ?',1)->where('t.IsAnonymous != ?',1);
				$select->order("tv.ViewID desc");
				$viewInfo = $select->query()->fetchAll();
				if(!empty($viewInfo)){
					$memberModel = new DM_Model_Account_Members();
					$viewImageModel = new Model_Topic_ViewImage();
					$topicModel = new Model_Topic_Topic();
					$memberNoteModel = new Model_MemberNotes();
					
					foreach($viewInfo as $key=>&$val){
						$topicInfo = $topicModel->getTopicInfo($val['TopicID'],null);
						$val['TopicName'] = $topicInfo['TopicName'];
						$val['IsPraised'] = $this->isPraised($val['ViewID'], $memberID);
						$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
						$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
						$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
						$val['Images'] = $viewImageModel->getImages($val['ViewID']);
					}
				}
			}
			return $viewInfo;
		}
		return $viewArr;
	}
	
	/**
	 * 获取某人被屏蔽的观点数量
	 */
	public function getHideViewNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(1) as num')->where('MemberID = ?',$memberID)->where('CheckStatus > ?',1)->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * 获取某人观点被举报的次数
	 */
	public function getReportNum($memberID)
	{
		$info = $this->select()->from($this->_name,'sum(ReportNum) as num')->where('MemberID = ?',$memberID)->where('CheckStatus = ?',1)->query()->fetch();
		return $info['num'];
	}

	/*
	 *获取某话题下最新观点
	 */
	public function getNewestViewInfo($topicID)
	{
		return $info = $this->select()->from($this->_name)->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1)->order('ViewID desc')->limit(1)->query()->fetch();
	}

	/*
	 *某话题下是否有新观点
	 */
	public function hasNewTopicView($topicID,$memberID)
	{
		$lastViewIDKey = 'topicLastViewID:'.$topicID;
		$lastViewID = Model_Member::staticData($memberID,$lastViewIDKey);
		$info = array();
		if(!empty($topicID) && $lastViewID >=0){
			$info = $this->select()->from($this->_name,array('ViewID'))->where('TopicID = ?',$topicID)
			->where('CheckStatus = ?',1)->where('ViewID > ?',$lastViewID)->order('ViewID desc')->limit(1)->query()->fetch();
		}
		return empty($info) ? 0 : 1;
	}


	/*
	 *历史好友观点处理
	 */
	public function historyFriendViewList()
	{
		$memberModel = new Model_Member();
		$friendModel = new Model_IM_Friend();
		$redisObj = DM_Module_Redis::getInstance();
		$memberInfo = $memberModel->getAllMembers();
		$memberArr = array();
		if(!empty($memberInfo)){
			foreach ($memberInfo as $item) {
				$memberArr[] = $item['MemberID'];
			}
		}
		//$memberArr = array_column($memberInfo,'MemberID');
		if(!empty($memberArr)){
			foreach ($memberArr as $memberID) {
				$friendsInfo = $friendModel->getFriendInfo($memberID);
				if(empty($friendsInfo)){
					$friendArr = array($memberID);
				}else{
					$friendArr = array();
					foreach ($friendsInfo as $item) {
						$friendArr[] = $item['FriendID'];
					}
					array_unshift($friendArr, $memberID);
				}
				$viewArr = $this->select()->from($this->_name,array('ViewID'))->where('MemberID in (?)',$friendArr)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1)->order('ViewID desc')->query()->fetchAll();
				if(!empty($viewArr)){
					foreach ($viewArr as $item) {
						//保存在redis队列里
						$f_key = 'Friends:View:MemberID'.$memberID;
						$redisObj->zadd($f_key,$item['ViewID'],$item['ViewID']);
					}
				}
			}
			return true;
		}
	}
	
	/**
	 * 保存最新的匿名爆料
	 */
	public static function newAnonymouslInfo($info = NULL)
	{
		if(!is_null($info)){
			DM_Module_Redis::getInstance()->hMSet(self::NEW_ANONYMOUS_KEY,$info);
		}
		return DM_Module_Redis::getInstance()->hGetAll(self::NEW_ANONYMOUS_KEY);
	}
	
	/**
	 *  是否有匿名爆料
	 * @param int $memberID
	 */
	public function hasNewAnonymous($memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$lastIdKey = 'LAST_ANONYMOUS_INFO_ID';
		$lastID = Model_Member::staticData($memberID,$lastIdKey);
	
		$viewInfo = $redisObj->hGetAll(self::NEW_ANONYMOUS_KEY);
		$viewID = $viewInfo['ViewID'];
	
		if(empty($viewID)){
			$info = $this->select()->from($this->_name,'*')->where('IsAnonymous = 1')->order('ViewID desc')->limit(1)->query()->fetch();
			if(!empty($info)){
				$viewID = $info['ViewID'];
				self::newAnonymouslInfo($info);
			}
		}
	
		if(intval($viewID) > intval($lastID)){
			//Model_Member::staticData($memberID,$lastIdKey,$viewID);
			return true;
		}
		return false;
	}
	
	/**
	 *  更新上次请求ID
	 * @param int $memberID
	 * @param int $viewID
	 */
	public static function updateLastIDCache($memberID,$viewID)
	{
		$lastIdKey = 'LAST_ANONYMOUS_INFO_ID';
		$redisObj = DM_Module_Redis::getInstance();
		$lastID = Model_Member::staticData($memberID,$lastIdKey);
		if($viewID > $lastID){
			Model_Member::staticData($memberID,$lastIdKey,$viewID);
		}
	}
}