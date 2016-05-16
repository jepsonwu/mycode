<?php
/**
 * 话题
 * @author Mark
 *
 */
class Api_TopicController extends Action_Api
{
	public function init()
	{
		parent::init();
		$actionArr = array('liveness-member-list','group-list','detail');
		if(!in_array($this->_getParam('action'),$actionArr)){
			$this->isLoginOutput();
			$this->checkDeny();
		}
	}
	
	
	/**
	 * 获取话题总数
	 */
	public function getTotalTopicAction()
	{
		$topicModel = new Model_Topic_Topic();
		$select = $topicModel->select()->from('topics',new Zend_Db_Expr('COUNT(1) as total'));
		$total = $select->where('CheckStatus = ?',1)->query()->fetchColumn();
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total));
	}
	
	/**
	 * 话题列表页
	 */
	public function listAction()
	{
		$pageIndex= $this->_getParam('page', 1);
		$pageSize = $this->_getParam('pagesize', 20);
		
		$memberID = $this->memberInfo->MemberID;
		
		$fields = array('TopicID','TopicName','FollowNum','ViewNum','BackImage');
		$topicModel = new Model_Topic_Topic();
		$select = $topicModel->select()->from('topics',$fields)->where('CheckStatus = ?',1);
		
		
		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
		//总条数
		$total = $topicModel->getAdapter()->fetchOne($countSql);
		
		$results = $select->order('SortWeight desc')->order('TopicID asc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
			}
		}
		
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
	}

	
	/**
	 * Ta 关注的话题列表
	 */
	public function otherFollowListAction()
	{
		$memberID = $this->memberInfo->MemberID;
		try{
			$otherMemberID = intval($this->_request->getParam('otherMemberID',0));
			if(empty($otherMemberID)){
				throw new Exception('会员ID不能为空！');
			}
			$topicModel = new Model_Topic_Topic();
			$results = $topicModel->getFollowedTopics($otherMemberID);
			if(!empty($results)){
				foreach ($results as &$val){
					$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}


	/*
	 *话题首页-我关注的话题
	 */
	public function myFollowedTopicAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$topicModel = new Model_Topic_Topic();
			$topicIDArr = $topicModel->getFollowedTopics($memberID,false);
			$isExistFollowed = 0;
			$results= array();
			if(count($topicIDArr)>0){
				$select = $topicModel->select();
				$select->from('topics',array('TopicID','TopicName'))->where('TopicID in (?)',$topicIDArr)->where('CheckStatus = ?',1);
				$select->order("Liveness desc")->limit(3);
				$results = $select->query()->fetchAll();
				if(count($results)>0){
					$isExistFollowed = 1;
				}				
			}
			$this->returnJson(parent::STATUS_OK,'',array('isExistFollowed'=>$isExistFollowed,'Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}


	/**
	 * 我关注的话题列表
	 */
	public function myFollowListAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$topicModel = new Model_Topic_Topic();
			$viewModel = new Model_Topic_View();
			$results = $topicModel->getFollowedTopics($memberID);
			$followArr = array_column($results,'TopicID');
			$lastViewID = Model_Member::staticData($memberID,'lastFollowedTopicViewID');
			if(!empty($followArr) && $lastViewID >=0){	
				$info = $viewModel->select()->from('topic_views',array('ViewID'))->where('TopicID in (?)',$followArr)
				->where('CheckStatus = ?',1)->where('ViewID > ?',$lastViewID)->where('IsAnonymous = ?',0)->order('ViewID desc')->limit(1)->query()->fetch();
				if(!empty($info)){
					Model_Member::staticData($memberID,'lastFollowedTopicViewID',$info['ViewID']);
				}
			}

			if(!empty($results)){
				$memberModel = new DM_Model_Account_Members();
				foreach ($results as &$item) {
					$item['isShowPoint'] = $viewModel->hasNewTopicView($item['TopicID'],$memberID);;
					$item['Avatar'] = '';
					$info = $viewModel->getNewestViewInfo($item['TopicID']);
					if(!empty($info)){
						if($info['IsAnonymous']==1){
							$item['Avatar'] = $info['AnonymousAvatar'];
						}else{
							$item['Avatar'] = $memberModel->getMemberAvatar($info['MemberID']);
						}
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}
	
	/**
	 * 最近的话题列表
	 */
	public function recentTopicListAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$topicModel = new Model_Topic_Topic();
		
		$publish = $topicModel->getRecentPublishTopics($memberID);
		$browse = $topicModel->getRecentUseTopics($memberID,5);
		$results = array('Publish'=>$publish,'Browse'=>$browse);
		
		$this->returnJson(parent::STATUS_OK,'',$results);
	}

	/**
	 * 最新话题列表
	 */
	public function newestTopicListAction()
	{
		$topicModel = new Model_Topic_Topic();
		$memberID = $this->memberInfo->MemberID;
		$results = $topicModel->getNewestTopics();
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}

	/**
	 * 最热话题列表
	 */
	public function hotTopicListAction()
	{
		$topicModel = new Model_Topic_Topic();
		$memberID = $this->memberInfo->MemberID;
		$results = $topicModel->getHotTopics();
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}


	/**
	 * 查询话题信息
	 */
	public function detailAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);
			if(empty($topicID)){
				throw new Exception('未选择指定话题！');
			}
			
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}	
			$fields = array('TopicID','TopicName','FollowNum','ViewNum','BackImage','CreateTime','MemberID','IsAnonymous');
			
			$topicModel = new Model_Topic_Topic();
			$select = $topicModel->select()->from('topics',$fields);
			$topicInfo = $select->where('topicID = ?',$topicID)->query()->fetch();
			if(empty($topicInfo)){
				throw new Exception('不存在该话题！');
			}
			
			$topicInfo['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $topicID);
			$memberModel = new DM_Model_Account_Members();
			$topicInfo['UserName'] = $memberModel->getMemberInfoCache($topicInfo['MemberID'],'UserName');
			if($topicInfo['IsAnonymous'] == 1){
				$topicInfo['AnonymousUserName'] = $memberModel->getAnonymousUserName($memberID);
				$topicInfo['AnonymousAvatar'] = $memberModel->getAnonymousAvatar($memberID);
			}

			$IsSign = 0;
			if($memberID > 0){
				//查询今天有没有签到
				$signModel = new Model_Topic_Sign();
				$info = $signModel->getTodaySign($topicID,$memberID);
				if(!empty($info)){
					$IsSign = 1;
				}
			}
			
			$topicInfo['IsSign'] = $IsSign;
			
			$memberNoteModel = new Model_MemberNotes();
			$topicInfo['NoteName'] = $memberNoteModel->getNoteName($memberID, $topicInfo['MemberID']);
			
			$this->returnJson(parent::STATUS_OK,'',$topicInfo);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 搜索页列表
	 */
	public function switchTopicAction()
	{
		$memberID = $this->memberInfo->MemberID;
		
		$fields = array('TopicID','TopicName','FollowNum','ViewNum','BackImage');
		$topicModel = new Model_Topic_Topic();
		
		$followed = $topicModel->getFollowedTopics($memberID);
		$recent = $topicModel->getRecentUseTopics($memberID);
		$results = array('Follows'=>$followed,'Recent'=>$recent);
		
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}
	
	/**
	 * 关注话题
	 */
	public function followAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);
			if($topicID <= 0){
				throw new Exception('话题参数无效!');
			}
			
			$memberID = $this->memberInfo->MemberID;
			$followModel = new Model_Topic_Follow();
			$followModel->addFollow($topicID, $memberID);
			$this->returnJson(parent::STATUS_OK,'已关注！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消关注
	 */
	public function unFollowAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);
			if($topicID < 0){
				throw new Exception('话题参数无效!');
			}
				
			$memberID = $this->memberInfo->MemberID;
			$followModel = new Model_Topic_Follow();
			$followModel->unFollow($topicID, $memberID);
			$this->returnJson(parent::STATUS_OK,'已取消关注！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}


	/*
	 *搜索
	 */
	// public function searchTopicAction()
	// {
	// 	$pageIndex= $this->_getParam('page', 1);
	// 	$pageSize = $this->_getParam('pagesize', 20);
	// 	$memberID = $this->memberInfo->MemberID;

	// 	$topicName = trim($this->_request->getParam('topicName',''));
	// 	if(empty($topicName)){
	// 		$this->returnJson(parent::STATUS_FAILURE,'话题名称不能为空！');
	// 	}

	// 	$fields = array('TopicID','TopicName','FollowNum','ViewNum','BackImage');
	// 	$topicModel = new Model_Topic_Topic();
	// 	$select = $topicModel->select()->from('topics',$fields);
	// 	if(!empty($topicName)){
	// 		$select->where('TopicName like ?','%'.$topicName.'%');
	// 	}

	// 	//获取sql
	// 	$countSql = $select->__toString();
	// 	$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
	// 	//总条数
	// 	$total = $topicModel->getAdapter()->fetchOne($countSql);
	// 	$isCreatable = 1;
	// 	$results = $select->order('SortWeight desc')->order('TopicID asc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
	// 	if(!empty($results)){
	// 		foreach ($results as &$val){
	// 			$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
	// 			if($val['TopicName']==$topicName){
	// 				$isCreatable = 0;
	// 			}
	// 		}
	// 	}
	// 	$isCreateTopicToday = $topicModel->isCreateTopicToday($memberID);
	// 	$createTopicNumAll = $topicModel->createTopicNumAll($memberID);
	// 	if($isCreateTopicToday ==1 || $createTopicNumAll >10){
	// 		$isCreatable = 0;
	// 	}

	// 	$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results,'isCreatable'=>$isCreatable));

	// }

	/*
	 *检测
	 */
	public function checkTopicAction()
	{
		$topicName = trim($this->_request->getParam('topicName',''));
		$backImage = trim($this->_request->getParam('backImage',''));

		if(empty($topicName)){
			$this->returnJson(parent::STATUS_FAILURE,'话题名称不能为空！');
		}

		if(empty($backImage)){
			$this->returnJson(parent::STATUS_FAILURE,'请上传话题头像！');
		}

		$topicModel = new Model_Topic_Topic();
		if($topicModel->hasExist($topicName)){
			$this->returnJson(parent::STATUS_FAILURE,'话题名称已存在！');
		}

		$this->returnJson(parent::STATUS_OK,'');

	}

	/*
	 *选择标签
	 */
	public function addAction()
	{
		$topicName = trim($this->_request->getParam('topicName',''));
		$backImage = trim($this->_request->getParam('backImage',''));
		$memberID = $this->memberInfo->MemberID;

		if(empty($topicName)){
			$this->returnJson(parent::STATUS_FAILURE,'话题名称不能为空！');
		}
		if(empty($backImage)){
			$this->returnJson(parent::STATUS_FAILURE,'请上传话题头像！');
		}

		$topicModel = new Model_Topic_Topic();
		if($topicModel->hasExist($topicName)){
			$this->returnJson(parent::STATUS_FAILURE,'话题名称已存在！');
		}

		// $isCreateTopicToday = $topicModel->isCreateTopicToday($memberID);
		// if($isCreateTopicToday == 1){
		// 	$this->returnJson(parent::STATUS_FAILURE,'您今天已经创建话题了，明天再来吧！');
		// }
		// $createTopicNumAll = $topicModel->createTopicNumAll($memberID);
		// if($createTopicNumAll >10){
		// 	$this->returnJson(parent::STATUS_FAILURE,'您已累计创建10个话题，达到创建话题上限！');
		// }
		
		$isExistCheckingTopic=$topicModel->isExistCheckingTopic($memberID);
		if($isExistCheckingTopic == 1){
			$this->returnJson(parent::STATUS_FAILURE,'您有一个话题正在审核中，请在审核通过后创建！');
		}

		$pinyinModel = new Model_Pinyin();
		$capitalChar = $pinyinModel->initial($topicName);
		$paramArr = array(
				'TopicName'=>$topicName,
				'CapitalChar'=>strtoupper($capitalChar),
				'BackImage'=>$backImage,
				'MemberID'=>$memberID
			);
		$topicID= $topicModel->insert($paramArr);
		if($topicID > 0 ){
	    	$focusID = trim($this->_request->getParam('focusID',''));
	    	$focusIDArr = explode(',',$focusID);
	    	if(empty($focusIDArr)){
	    	 	$this->returnJson(parent::STATUS_FAILURE,'请选择标签！');
	    	}
	    	$focusModel = new Model_Topic_Focus();
	    	foreach ($focusIDArr as $value) {
	    		$focusModel->addFocus($topicID,$value);
	    	}
			$this->returnJson(parent::STATUS_OK,'创建成功，工作人员会在2个工作日内审核！');
		}else{
			$this->returnJson(parent::STATUS_FAILURE,'创建失败！');
		}
	}

	/*
	 *话题-活跃用户
	 */
	public function livenessMemberListAction()
	{
		try{

			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			$topicID = $this->_request->getParam('topicID',0);			
			if($topicID <=0 ){
				$this->returnJson(parent::STATUS_FAILURE, '话题ID不能为空！');
			}

			$topicModel = new Model_Topic_Topic();
			$topic = $topicModel->getTopicInfo($topicID);
			if(empty($topic)){
				$this->returnJson(parent::STATUS_TOPIC_NOTEXIST, '该话题不存在！');
			}
		
			$viewModel = new Model_Topic_View();					
			$select = $viewModel->select()->from('topic_views',array('MemberID','SUM(Liveness) as score'))->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1);					
			$select->group('MemberID');
			$select->order('score desc')->limit(50);						
			$results = $select->query()->fetchAll();

			//获取用户详情
			$memberList = array();
			if(!empty($results)){
				$memberIDArr = array_column($results,'MemberID');
				//var_dump($memberIDArr);exit;			
				$memberModel = new DM_Model_Account_Members();
				$focusModel = new Model_MemberFocus();
				$memberFollowModel = new Model_MemberFollow();

				$bestModel = new Model_Best_Best();
				$authenticateModel =new Model_Authenticate();
				$qualificationModel = new Model_Qualification();

	            $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
	            $select = $memberModel->select();
	            $select->from('members',array('MemberID','UserName','Avatar','IsBest'))->where('MemberID != ?',$sysMemberID)->where('MemberID in (?)',$memberIDArr)->where('Status=?',1);
	            $memberList = $select->order(new Zend_Db_Expr("field(MemberID,".implode(',',$memberIDArr).")"))->query()->fetchAll();
				foreach($memberList as &$v){
					$v['Focus'] = $focusModel->getFocusInfo($v['MemberID'],null,'FocusID');
					$v['RelationCode'] = $memberFollowModel->getRelation($v['MemberID'], $memberID);


					$v['Qualification'] = array();
					$authenticateInfo = $authenticateModel->getInfoByMemberID($v['MemberID'],1);
					if(!empty($authenticateInfo) && $authenticateInfo['AuthenticateType']==2){
						$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],3,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
						 $v['Qualification'] = $qualificationInfo;
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

			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$memberList));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/*
	 *话题-群组
	 */
	public function groupListAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);			
			if($topicID <=0 ){
				$this->returnJson(parent::STATUS_FAILURE, '话题ID不能为空！');
			}

			$topicModel = new Model_Topic_Topic();
			$topic = $topicModel->getTopicInfo($topicID);
			if(empty($topic)){
				$this->returnJson(parent::STATUS_TOPIC_NOTEXIST, '该话题不存在！');
			}

			$topicFocusModel =new Model_Topic_Focus();
			$topicFocusArr = $topicFocusModel->getInfo($topicID);
			if(!empty($topicFocusArr)){
				$focusIDArr = array_column($topicFocusArr,'FocusID');
				if(count($focusIDArr)>0){
					$groupMdoel = new Model_IM_Group();
					$groupFocusModel = new Model_IM_GroupFocus();			
					$groupArr= $groupFocusModel->select()->from('group_focus','DISTINCT(GroupID)')->where('FocusID in (?)',$focusIDArr)->query()->fetchAll();
				}
			}

			$groupList = array();
			if(!empty($groupArr)){
				$groupIDArr = array_column($groupArr,'GroupID');
				$groupList = $groupMdoel->select()->from('group',array('AID','GroupID','GroupName','GroupAvatar','OwnerID'))->where('GroupID in (?)',$groupIDArr)
				                        ->where('Status = 1')->where('IsPublic = 1')->order('Liveness desc')->limit(50)->query()->fetchAll();
				if(!empty($groupList)){
					foreach($groupList as &$info){
						$info['Focus'] = $groupFocusModel->getFocusInfo($info['GroupID'],null,'FocusID');
					}
				}
			}

			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$groupList));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 是否可以创建话题
	 */
	public function isCreateTopicAction()
	{
		$isCreateTopic = 1;
		$topicModel = new Model_Topic_Topic();
		$isExistCheckingTopic=$topicModel->isExistCheckingTopic($this->memberInfo->MemberID);
		if($isExistCheckingTopic){
			$isCreateTopic = 0;
		}
		$this->returnJson(parent::STATUS_OK,'',array('IsCreateTopic'=>$isCreateTopic));
	}

	/*
	 *获取匿名
	 */
	public function changeAnonymousAction()
	{
		$memberModel = new DM_Model_Account_Members();
		$changeAnother = $this->_request->getParam('changeAnother',true);
		$memberID = 0;
		if($this->isLogin()){
			$memberID = $this->memberInfo->MemberID;
		}
		$anonymousUserName = $memberModel->getAnonymousUserName($memberID,$changeAnother);
		$anonymousAvatar = $memberModel->getAnonymousAvatar($memberID,$changeAnother);

		$this->returnJson(parent::STATUS_OK,'',array('AnonymousUserName'=>$anonymousUserName,'AnonymousAvatar'=>$anonymousAvatar));

	}
	
	/**
	 * 推荐话题列表
	 */
	public function recommendTopicListAction()
	{
		$topicModel = new Model_Topic_Topic();
		$memberID = $this->memberInfo->MemberID;
		$focusModel = new Model_MemberFocus();
		$focusArr = $focusModel->getFocusID($memberID);
		$select = $topicModel->select()->setIntegrityCheck(false);
		$fields = array('DISTINCT(t.TopicID)','TopicName','FollowNum','ViewNum','BackImage');	
		$select->from('topics as t',$fields)->where('t.CheckStatus = ?',1)->where('t.IsAnonymous = 0');
		$select->joinLeft('topic_focus as tf','t.TopicID = tf.TopicID','')->where('tf.FocusID in (?)',$focusArr);
		$select->order('Liveness desc')->order('t.TopicID desc');
		$results = $select->limit(50)->query()->fetchAll();	
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}
	
	/**
	 * 在某一话题下签到
	 */
	public function topicSignAction()
	{
		try{
			$topicID = intval($this->_getParam('topicID',0));
			if($topicID<1){
				throw new Exception('参数错误！');
			}
			//查询今天有没有签到
			$signModel = new Model_Topic_Sign();
			$info = $signModel->getTodaySign($topicID,$this->memberInfo->MemberID);
			if(!empty($info)){
				throw new Exception('您今天已在该话题下签到！');
			}
			//查询昨天有没有签到
			$yesterdayInfo = $signModel->getYesterdaySign($topicID,$this->memberInfo->MemberID);
			$serialNum = empty($yesterdayInfo) ? 1 : ($yesterdayInfo['SerialNum']+1);
			$data = array(
				'MemberID'=>$this->memberInfo->MemberID,
				'TopicID'=>$topicID,
				'SerialNum'=>$serialNum
			);
			$signModel->insert($data);
			$this->returnJson(parent::STATUS_OK,'签到成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 某话题下明星列表
	 */
	public function topicStarListAction()
	{
		try{
			$topicID = intval($this->_getParam('topicID',0));
			if($topicID<1){
				throw new Exception('参数错误！');
			}
			$memberID = $this->memberInfo->MemberID;
			$redisObj = DM_Module_Redis::getInstance();
			$gitNumKey = 'gitNum:TopicID:'.$topicID.':Date:'.date('Y-m');
			$payAmountkey = 'payAmount:TopicID:'.$topicID.':Date:'.date('Y-m');
			$viewNumkey = 'viewNum:TopicID:'.$topicID.':Date:'.date('Y-m');
			//红人
			$redArr = $redisObj->ZREVRANGE($gitNumKey,0,4);
			$topicModel = new Model_Topic_Topic();
			$redList = $topicModel->getMemberList($redArr,$memberID);
			//牛人
			$bestArr = $redisObj->ZREVRANGE($viewNumkey,0,4);
			$bestMemberList = $topicModel->getMemberList($bestArr,$memberID);
			
			//土豪
			$richArr = $redisObj->ZREVRANGE($payAmountkey,0,4);
			$richMemberList = $topicModel->getMemberList($redArr,$memberID);
			//铁粉
			$signModel = new Model_Topic_Sign();
			$fanArr = $signModel->getFanList($topicID);
			$fanList = $topicModel->getMemberList($fanArr, $memberID);
			$this->returnJson(parent::STATUS_OK,'',array('RedList'=>$redList,'BestList'=>$bestMemberList,'RichList'=>$richMemberList,'FanList'=>$fanList));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取话题主页文字广告
	 */
	public function getWordsAdsAction(){
		$moduleType = intval($this->_getParam('showType',1));
		$model = new Model_WordsAds();
		$results = $model->getAdsList($moduleType);
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results ? $results : array()));
	}
}