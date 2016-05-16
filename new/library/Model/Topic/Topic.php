<?php
/**
 * 话题
 * 
 * @author Mark
 *
 */
class Model_Topic_Topic extends Zend_Db_Table
{
	protected $_name = 'topics';
	protected $_primary = 'TopicID';
	
	/**
	 * 增加观点数量
	 */
	public function increaseViewNum($topicID,$increment = 1)
	{
		$where = array('TopicID = ?'=>$topicID);
		if($increment < 0){
			$where['ViewNum > ?'] = 0;
		}
		return $this->update(array('ViewNum'=>new Zend_Db_Expr("ViewNum + ".$increment)),$where);
	}
	
	/**
	 *  增加关注数量
	 * @param int $topicID
	 * @param int $increment
	 */
	public function increaseFollowNum($topicID,$increment = 1)
	{
		return $this->update(array('FollowNum'=>new Zend_Db_Expr("FollowNum + ".$increment)),array('TopicID = ?'=>$topicID));
	}

	/**
	 *  修改话题使用时间
	 * @param int $topicID
	 */
	public function updateLastViewTime($topicID)
	{
		return $this->update(array('LastViewTime'=>date('Y-m-d H:i:s',time())),array('TopicID = ?'=>$topicID));
	}
	
	
	/**
	 *  获取已关注的话题
	 * @param int $memberID
	 */
	public function getFollowedTopics($memberID, $isGetInfo = true)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Topic_Follow::getFollowKey($memberID);
		$followArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		if(empty($followArr)){
			$followModel = new Model_Topic_Follow();
			$followArr = $followModel->getFollowIDArr($memberID);
		}
		if($isGetInfo == true){
			$result = array();
			if(!empty($followArr)){
				$select = $this->select();
				$select->from($this->_name,array('TopicID','MemberID','TopicName','FollowNum','ViewNum','BackImage','IsAnonymous'))->where('TopicID in (?)',$followArr)
						->where('IsAnonymous = 0')->where('CheckStatus = ?',1);
				//$select->order(new Zend_Db_Expr("field(TopicID,".implode(',',$followArr).")"));
				$select->order("LastViewTime desc");
				$result = $select->query()->fetchAll();
			}
			return $result;
		}
		return $followArr;
	}
	
	/**
	 *  获取关注话题数量
	 * @param int $memberID
	 */
	public function getFollowedTopicsCount($memberID)
	{
// 		$redisObj = DM_Module_Redis::getInstance();
// 		$cacheKey = Model_Topic_Follow::getFollowKey($memberID);
// 		$count = $redisObj->zcard($cacheKey);
// 		return $count ? $count : 0;

		$results = $this->getFollowedTopics($memberID);
		return count($results);
	}
	
	/**
	 *  是否已关注
	 * @param int $memberID
	 * @param int $topicID
	 */
	public function isFollowedTopic($memberID,$topicID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Topic_Follow::getFollowKey($memberID);
		$score = $redisObj->zscore($cacheKey,$topicID);
		return $score ? 1 : 0;
	}
	
	/**
	 *  获取最近使用的话题 
	 * @param int $memberID
	 */
	public function getRecentUseTopics($memberID,$limit = 8)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Recent:TopicUsed:MemberID:'.$memberID;
		$recentArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		$result = array();
		if(!empty($recentArr)){
			$select = $this->select();
			$select->from($this->_name,array('TopicID','TopicName','FollowNum','ViewNum','BackImage','IsAnonymous'))->where('TopicID in (?)',$recentArr)
					 ->where('IsAnonymous = 0')->where('CheckStatus = ?',1)->limit($limit);
			$select->order(new Zend_Db_Expr("field(TopicID,".implode(',',$recentArr).")"));
			$result = $select->query()->fetchAll();
		}
		return $result;
	}
	
	/**
	 *  最近使用的话题 最多保存16个
	 * @param int $topicID
	 * @param int $memberID
	 */
	public function addRecentUse($topicID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Recent:TopicUsed:MemberID:'.$memberID;
		$redisObj->zadd($cacheKey,time(),$topicID);
		$allCount = $redisObj->zcard($cacheKey);
		if($allCount > 8){
			$redisObj->zRemRangeByRank($cacheKey,0,$allCount - 8 - 1);
		}
		return true;
	}

	/**
	 *  当天是否创建话题
	 * @param int $memberID
	 */
	public function isCreateTopicToday($memberID)
	{
		$info = $this->select()->where('MemberID = ?',$memberID)->where("DATE_FORMAT(CreateTime,'%Y-%m-%d')= ?",date('Y-m-d'))->query()->fetch();
		return !empty($info )? 1 : 0;
	}

	/**
	 *  创建所有话题数量
	 * @param int $memberID
	 */
	public function createTopicNumAll($memberID)
	{
		$return = $this->select()->where('MemberID = ?',$memberID)->query()->fetchAll();
		return count($return)>0? count($return) : 0;
	}
	
	/*
	 *是否存在审核中的话题
	 */
	public function isExistCheckingTopic($memberID)
	{
		$return = $this->select()->where('MemberID = ?',$memberID)->where('CheckStatus = ?',0)->query()->fetchAll();
		return count($return)>0?1:0;
	}

	/**
	 *  检测话题的唯一性
	 * @param int $topicID
	 * @param int $memberID
	 */
	public function hasExist($topicName,$isGetInfo = null)
	{
		$info = $this->select()->where('TopicName = ?',$topicName)->where('CheckStatus = ?',1)->query()->fetch();
		if(!empty($isGetInfo)){
			return $info;
		}else{
			return !empty($info)? 1 : 0;
		}
	}
	
	/**
	 * 获取话题信息
	 */
	public function getTopicInfo($topicID,$checkStatus = 1)
	{
		$select = $this->select()->where('TopicID = ?',$topicID);
		
		if(!is_null($checkStatus)){
			$select->where('CheckStatus = ?',1);
		}
		
		$info = $select ->query()->fetch();
		return $info;
	}

	/**
	 * 获取最近发表观点的话题列表
	 */
	public function getRecentPublishTopics($memberID)
	{
		$viewModel = new Model_Topic_View();
		$viewDb = $viewModel->getAdapter();
		$topicIDArr = $viewDb->fetchCol("SELECT DISTINCT TopicID FROM topic_views WHERE `MemberID` = :MemberID AND `CheckStatus` = 1 ORDER BY `CreateTime` desc LIMIT 5",array("MemberID" => $memberID));
		//var_dump($topicIDArr);exit;
		$result = array();
		if(!empty($topicIDArr)){
			$select = $this->select();
			$select->from($this->_name,array('TopicID','TopicName','FollowNum','ViewNum','BackImage','IsAnonymous'))->where('TopicID in (?)',$topicIDArr)->where('CheckStatus = ?',1)->where('IsAnonymous = 0');
			$select->order(new Zend_Db_Expr("field(TopicID,".implode(',',$topicIDArr).")"));
			$result = $select->query()->fetchAll();
		}
		return $result;
	}


	/**
	 * 获取最新话题
	 */
	public function getNewestTopics()
	{
		$select = $this->select();
		$select->from($this->_name,array('TopicID','TopicName','FollowNum','ViewNum','BackImage'))->where('CheckStatus = ?',1)->where('IsAnonymous = 0')->limit(30);
		$select->order("CheckTime desc");
		$result = $select->query()->fetchAll();

		return !empty($result) ? $result : array();
	}


	/**
	 * 获取最热话题
	 */
	public function getHotTopics($isGetInfo = true)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'HotTopic';
		$topicIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		if($isGetInfo == true){
			$result = array();
			if(!empty($topicIDArr)){
				$select = $this->select();
				$select->from($this->_name,array('TopicID','TopicName','FollowNum','ViewNum','BackImage'))->where('TopicID in (?)',$topicIDArr)->where('CheckStatus = ?',1);
				$select->where('IsAnonymous = 0')->order(new Zend_Db_Expr("field(TopicID,".implode(',',$topicIDArr).")"));
				$result = $select->query()->fetchAll();
			}
			return $result;
		}
		return $topicIDArr;
	}
	
	/**
	 * 今日热议话题
	 * @param unknown $type
	 */
	public function getCoumnHotTopics($type)
	{
		$limit = $type==1?5:10;
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'TodayHotTopic';
		$topicIDArr = $redisObj->ZRANGEBYSCORE($cacheKey,'-inf','+inf',array('limit' => array(0,$limit)));
		$result = array();
		if(!empty($topicIDArr)){
			$select = $this->select();
			$select->from($this->_name,array('TopicID','TopicName','FollowNum','ViewNum','BackImage'))->where('TopicID in (?)',$topicIDArr)->where('CheckStatus = ?',1);
			$select->order(new Zend_Db_Expr("field(TopicID,".implode(',',$topicIDArr).")"));
			$result = $select->query()->fetchAll();
		}
		return $result;
	}

	/*
	 *设置话题首字母
	 */
	public function setCapitalChar()
	{
		$select = $this->select();
		$results = $select->from($this->_name,array('TopicID','TopicName'))->query()->fetchAll();

		$pinyinModel = new Model_Pinyin();
		if(count($results)>0){
			foreach ($results as $topic) {
				$capitalChar = $pinyinModel->initial($topic['TopicName']);
				$this->update(array('CapitalChar' => strtoupper($capitalChar)),array('TopicID = ?'=>$topic['TopicID']));
			}
		}
		return true;
	}
	
	/**
	 * 话题统计相关
	 */
	public function topicStatic()
	{
		//新增关注量
		$followTopic = new Model_Topic_Follow();
		$select = $followTopic->select();
		$select->from('topic_follows','count(1) as num')->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$result = $select->query()->fetch();
		$newFollowNum = $result['num'];
		
		//新增话题数量
		$select= $this->select()->from($this->_name,'count(1) as num')->where('CheckStatus = ?',1)->where("date_format(CheckTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$re = $select->query()->fetch();
		$newTopicNum = $re['num'];
		
		//新增观点数量
		$viewModel = new Model_Topic_View();
		$select = $viewModel->select();
		$select->from('topic_views','count(1) as num')->where('CheckStatus = ?',1)->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$re = $select->query()->fetch();
		$newViewNum = $re['num'];
		
		//新增评论回复
		$replyModel = new Model_Topic_Reply();
		$select = $replyModel->select();
		$select->from('view_replies','count(1) as num')->where('Status = ?',1)->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$re = $select->query()->fetch();
		$newReplyNum = $re['num'];
		
		//新增分享数
		$redisObj = DM_Module_Redis::getInstance();
		$shareKey = 'shareNum:date'.date('Y-m-d');
		$num = $redisObj->get($shareKey);
		$newShareNum = $num ? $num:0;
		
		$topicStatic = new Model_Topic_Static();
		$data = array('NewFollowNum'=>$newFollowNum,'NewTopicNum'=>$newTopicNum,'NewViewNum'=>$newViewNum,
						'NewReplyNum'=>$newReplyNum,'NewShareNum'=>$newShareNum,'CreateDate'=>date('Y-m-d'));
		$topicStatic->add($data);
	}
	
	/**
	 * 获取话题总数
	 */
	public function getTotalTopicsCount()
	{
		$select= $this->select()->from($this->_name,'count(1) as num')->where('CheckStatus = ?',1);
		$re = $select->query()->fetch();
		return $re['num'];
	}


	/*
	 获取正在讨论的话题名称
	 */
	public function getDiscussingTopicName($memberID)
	{
		$viewModel = new Model_Topic_View();
		$viewInfo = $viewModel->select()->from('topic_views')->where('MemberID = ?',$memberID)->where('IsAnonymous != ?',1)->where('CheckStatus = ?',1)->order('CreateTime desc')->limit(1)->query()->fetch();
		$topicInfo = array();
		if(!empty($viewInfo)){
			$select= $this->select()->from($this->_name,'TopicName')->where('TopicID = ?',$viewInfo['TopicID'])->where('CheckStatus = ?',1);
			$topicInfo = $select->query()->fetch();
		}
		return !empty($topicInfo)?$topicInfo['TopicName']:'';
	}
	
	/**
	 * 获取所属话题（多个话题）
	 * @param unknown $allTopicIDs
	 */
	public function getAllTopics($allTopicIDs)
	{
		$topicArr = explode(',',$allTopicIDs);
		$info = $this->select()->from($this->_name,array('TopicID','TopicName'))->where('TopicID in (?)',$topicArr)->query()->fetchAll();
		return $info;
	}
	
	public function getMemberList($memberArr,$memberID)
	{
		$memberList = array();
		if(!empty($memberArr)){
			$memberModel = new DM_Model_Account_Members();
			$focusModel = new Model_MemberFocus();
			$memberFollowModel = new Model_MemberFollow();
			
			$bestModel = new Model_Best_Best();
			$authenticateModel =new Model_Authenticate();
			$qualificationModel = new Model_Qualification();
			$memberNoteModel = new Model_MemberNotes();
			$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
			$select = $memberModel->select();
			$select->from('members',array('MemberID','UserName','Avatar','IsBest'))->where('MemberID != ?',$sysMemberID)->where('MemberID in (?)',$memberArr)->where('Status=?',1);
			$memberList = $select->order(new Zend_Db_Expr("field(MemberID,".implode(',',$memberArr).")"))->query()->fetchAll();
			foreach($memberList as &$v){
				$v['NoteName'] = $memberNoteModel->getNoteName($memberID, $v['MemberID']);
				$v['Focus'] = $focusModel->getFocusInfo($v['MemberID'],null,'FocusID');
				$v['RelationCode'] = $memberFollowModel->getRelation($v['MemberID'], $memberID);
				
				$authenticateInfo = $authenticateModel->getInfoByMemberID($v['MemberID'],1);
				$v['Qualification']	 = array();
				if(!empty($authenticateInfo) && $authenticateInfo['AuthenticateType']==2){
					
					$qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);
					
					if(empty($qualificationInfo)){					
						$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
					}
					if(!empty($qualificationInfo)){
						$v['Qualification'] = array($qualificationInfo);
					}
				}
				$bestInfo = $bestModel->getBestInfoByMemberID(array($v['MemberID']), array(2,3));
				$bestTitleArr = array();
				if(!empty($bestInfo)){
					$bestTitleArr = $bestInfo[$v['MemberID']];
				}				
				$v['BestTitle'] = !empty($bestTitleArr)?$bestTitleArr:array();
				$v['IsAuthentication'] = empty($authenticateInfo)?0:1;
				$v['AuthenticateType'] = empty($authenticateInfo)?0:$authenticateInfo['AuthenticateType'];
			}
		}
		return $memberList;
	}
	
	/*
	 *获取匿名话题
	 */
	public function getAnonymousTopicInfo()
	{
		$select = $this->select();
		$select->from($this->_name,array('TopicID','MemberID','TopicName','FollowNum','ViewNum','BackImage'))->where('CheckStatus = ?',1)->where('IsAnonymous = ?',1);
		$select->order("CreateTime desc");
		$result = $select->query()->fetchAll();
		return !empty($result)?$result:array();		
	}
}