<?php
/**
 *  观点
 * @author Mark
 *
 */
class Api_ViewController extends Action_Api
{
	public function init()
	{
		parent::init();
		$actionArr = array('topic-view-list','detail','reply-list','get-banner-ads','topic-newest-view-list','topic-hot-view-list');
		if(!in_array($this->_getParam('action'),$actionArr)){
			$this->isLoginOutput();
			$this->checkDeny();
		}
		//$this->isLoginOutput();
		
	}
	
	
	/**
	 * 发表观点
	 */
	public function addAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$topicID = trim($this->_request->getParam('topicID',array()));
			if(empty($topicID)){
				throw new Exception('未选择指定话题！');
			}
			$viewContent = trim($this->_request->getParam('viewContent'));
			if(empty($viewContent)){
				throw new Exception('观点内容不能为空！');
			}
				
			if(mb_strlen($viewContent,'UTF-8') > 5000){
				throw new Exception('观点内容不能超过5000个字符！');
			}
				
			$topicArr = explode(',',$topicID);
			if(count($topicArr)>3){
				throw new Exception('最多选择3个话题！');
			}
			$topicModel = new Model_Topic_Topic();
			$memberModel = new DM_Model_Account_Members();
			$viewModel = new Model_Topic_View();
				
			foreach($topicArr as $k=>$tID){
				$topicInfo = $topicModel->getTopicInfo($tID);
				if(empty($topicInfo)){
					throw new Exception('您选择的话题不存在，或审核未通过！');
				}else{
					$anonymousUserName='';
					$anonymousAvatar ='';
					$isAnonymous = $topicInfo['IsAnonymous'];
					if($isAnonymous==1){
						$anonymousUserName = $memberModel->getAnonymousUserName($memberID);
						$anonymousAvatar = $memberModel->getAnonymousAvatar($memberID);
					}
				}
				if($k == 0){
					$viewID = $viewModel->addView($memberID, $tID, $viewContent,$isAnonymous,$anonymousUserName,$anonymousAvatar,$topicID,0,1);
				}else{
					//此观点不分发给好友
					$viewModel->addView($memberID, $tID, $viewContent,$isAnonymous,$anonymousUserName,$anonymousAvatar,$topicID,$viewID,0);
				}
			}

			$fields = array('ViewID','MemberID','ViewContent','PraiseNum','ReplyNum','ShareNum','CreateTime','IsAnonymous','AnonymousUserName','AnonymousAvatar');
				
			$select = $viewModel->select()->from('topic_views',$fields)->where('ViewID = ?',$viewID);
			$viewInfo = $select->query()->fetch();

			if($viewInfo['IsAnonymous']== 1){
				$userName = $viewInfo['AnonymousUserName'];
				$avatar   = $viewInfo['AnonymousAvatar'];
				unset($viewInfo['MemberID']);
				$viewModel->newAnonymouslInfo($viewInfo);
				$viewModel->updateLastIDCache($memberID,$viewID);
			}else{
				$userName = $memberModel->getMemberInfoCache($viewInfo['MemberID'],'UserName');
				$avatar = $memberModel->getMemberAvatar($viewInfo['MemberID']);
			}
			unset($viewInfo['IsAnonymous']);
			unset($viewInfo['AnonymousUserName']);
			unset($viewInfo['AnonymousAvatar']);
			$viewInfo['IsPraised'] = $viewModel->isPraised($viewID, $this->memberInfo->MemberID);
			$viewInfo['Avatar'] = $avatar;
			$viewInfo['UserName'] = $userName;
			$viewInfo['TimeStamp'] = time();
			
			$this->returnJson(parent::STATUS_OK,'发布成功！',$viewInfo);
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 保存观点图片
	 */
	public function saveImagesAction()
	{
		try{
			$images = trim($this->_request->getParam('images',''));
			$viewID = intval($this->_request->getParam('viewID',0));
			if(empty($images)){
				throw new Exception('图片不能为空！');
			}
			
			if(empty($viewID)){
				throw new Exception('观点ID错误');
			}
			
			$imagesArr = explode(',', $images);
			if(empty($imagesArr)){
				throw new Exception('图片不能为空！');
			}else{
				$viewImageModel = new Model_Topic_ViewImage();
				foreach($imagesArr as $uri){
					$viewImageModel->addImage($viewID, $uri);
				}
			}
			$this->returnJson(parent::STATUS_OK,'');			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 首页和热门观点列表 
	 */
	public function listAction()
	{
		try{			
			$viewType = $this->_request->getParam('viewType','');
			$viewTypeArr = array('index','hot');
			if(!in_array($viewType, $viewTypeArr)){
				$this->returnJson(parent::STATUS_FAILURE, '参数错误！');
			}
			$total = 0;
			$lastViewID= $this->_getParam('lastViewID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$page = intval($this->_getParam('page',1));
			$version = $this->_getParam('currentVersion','1.0.0');
			$fields = array('ContentType'=>new Zend_Db_Expr(2),'ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','TopicID','AllTopicIDs');
			
			$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$select->from('topic_views as tv',$fields)->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous != ?',1)->where('ParentID = ?',0);
			$select->joinLeft('topics as t','t.TopicID = tv.TopicID','TopicName');

			if($viewType == 'index'){    //首页观点列表
				$memberID = $this->memberInfo->MemberID;
				$topicModel = new Model_Topic_Topic();
				$followArr = $topicModel->getFollowedTopics($memberID,false);
				if(empty($followArr)){
					$followArr= array(0);
				}
				$select->where('tv.TopicID in (?)',$followArr);
				if($lastViewID > 0){
					$select->where('tv.ViewID < ?',$lastViewID);
				}
				$select->order('ViewID desc')->limit($pageSize);

			}elseif($viewType == 'hot'){	//热门观点列表	
				if(version_compare($version,'2.4.3',"<")){
					$viewIDArr = $viewModel->getHotViews(false);
					$viewIDs = array();
					$tempArr=array_chunk($viewIDArr,$pageSize);
					foreach ($tempArr as $key => $item) {
						if($key==0){
							$viewIDs[$key]=$item;
						}else{
							$viewIDs[end($tempArr[$key-1])]=$item;
						}
					}
					if(empty($viewIDs[$lastViewID])){
						$viewIDs[$lastViewID]=array(0);
					}
					$select->where('tv.ViewID in (?)',$viewIDs[$lastViewID]);
					$select->order(new Zend_Db_Expr("field(ViewID,".implode(',',$viewIDs[$lastViewID]).")"));
				}else{
					//获取sql
					$countSql = $select->__toString();
					$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
						
					//总条数
					$total = $viewModel->getAdapter()->fetchOne($countSql);
					$select->order('tv.Liveness desc')->order('tv.ViewID desc')->limitPage($page, $pageSize);
				}
			}
		
				

			
			$viewInfo = $select->query()->fetchAll();
			$results = array();
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$maxBarNum = $lastBarNum;
			$topicModel = new Model_Topic_Topic();
			if(!empty($viewInfo)){
				//$replyModel = new Model_Topic_Reply();
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				$adsArr = array();
				if($viewType == 'index'){
					// if($lastViewID==0){//记录获取的最新观点ID
					// 	Model_Member::staticData($memberID,'lastFollowedTopicViewID',$viewInfo[0]['ViewID']);
					// }
					$adsModel = new Model_Ads();
					$adsCount = intval(count($viewInfo) / 5)+1;
					$ads = $adsModel->getAdsList($lastBarNum,$adsCount,2);
					$adsArr = $ads['ads'];
					$maxBarNum = $ads['maxNum'];
				}
				foreach($viewInfo as $key=>&$v){
					if(!empty($adsArr)){
						if($key % 5 === 0){
							if(!empty($adsArr[intval($key / 5)])){
								$results[] = $adsArr[intval($key / 5)];
							}
					 	}
					}
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $v['MemberID']);
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['IsPraised'] = $viewModel->isPraised($v['ViewID'], $this->memberInfo->MemberID);
					//$v['Replies'] = $replyModel->getList($v['ViewID'],0,5);
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
					
					if(empty($v['AllTopicIDs'])){
						$v['AllTopicIDs'] = $v['TopicID'];
					}
					$v['TopicArr'] = $topicModel->getAllTopics($v['AllTopicIDs']);
					$results[] = $v;
				}
				
			}
			
			// $topicModel->addRecentUse($topicID, $this->memberInfo->MemberID);
			$followedTopicNum = $topicModel->getFollowedTopicsCount($this->memberInfo->MemberID);
			$totalTopicNum = $topicModel->getTotalTopicsCount();
			$myViewNum = $viewModel->getViewCount($this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'',array('followedTopicNum'=>$followedTopicNum,'totalTopicNum'=>$totalTopicNum,'myViewNum'=>$myViewNum,'Rows'=>$results,'TimeStamp'=>time(),'lastBarNum'=>$maxBarNum,'TotlaNum'=>$total));
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 新话题订阅页面
	 */
	public function newListAction()
	{
		try{
		
			$lastViewID= $this->_getParam('lastViewID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
				
			$fields = array('ContentType'=>new Zend_Db_Expr(2),'ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','TopicID','AllTopicIDs');
				
			$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$select->from('topic_views as tv',$fields)->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous != ?',1)->where('tv.ParentID = ?',0);
			$select->joinLeft('topics as t','t.TopicID = tv.TopicID','TopicName');
		
			$memberID = $this->memberInfo->MemberID;
			$topicModel = new Model_Topic_Topic();
			$followArr = $topicModel->getFollowedTopics($memberID,false);
			if(empty($followArr)){
				$followArr= array(0);
			}
			$select->where('tv.TopicID in (?)',$followArr);
			if($lastViewID > 0){
				$select->where('tv.ViewID < ?',$lastViewID);
			}
			$select->order('ViewID desc')->limit($pageSize);
		
			$viewInfo = $select->query()->fetchAll();
			$results = array();
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$maxBarNum = $lastBarNum;
			if(!empty($viewInfo)){
				//$replyModel = new Model_Topic_Reply();
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				$adsArr = array();

				// if($lastViewID==0){//记录获取的最新观点ID
				// 	Model_Member::staticData($memberID,'lastFollowedTopicViewID',$viewInfo[0]['ViewID']);
				// }
				$adsModel = new Model_Ads();
				$adsCount = intval(count($viewInfo) / 5)+1;
				$ads = $adsModel->getAdsList($lastBarNum,$adsCount,6);
				$adsArr = $ads['ads'];
				$maxBarNum = $ads['maxNum'];

				foreach($viewInfo as $key=>&$v){
					if(!empty($adsArr)){
						if($key % 5 === 0){
							if(!empty($adsArr[intval($key / 5)])){
								$results[] = $adsArr[intval($key / 5)];
							}
						}
					}
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $v['MemberID']);
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['IsPraised'] = $viewModel->isPraised($v['ViewID'], $this->memberInfo->MemberID);
					//$v['Replies'] = $replyModel->getList($v['ViewID'],0,5);
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
					$v['TopicArr'] = $topicModel->getAllTopics($v['AllTopicIDs']);
					$results[] = $v;
				}
		
			}
			$topicModel = new Model_Topic_Topic();
			// $topicModel->addRecentUse($topicID, $this->memberInfo->MemberID);
			$followedTopicNum = $topicModel->getFollowedTopicsCount($this->memberInfo->MemberID);
			$totalTopicNum = $topicModel->getTotalTopicsCount();
			$this->returnJson(parent::STATUS_OK,'',array('followedTopicNum'=>$followedTopicNum,'totalTopicNum'=>$totalTopicNum,'Rows'=>$results,'TimeStamp'=>time(),'lastBarNum'=>$maxBarNum));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/*
	 *话题-最新观点列表
	 */
	public function topicNewestViewListAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);			

			$topicModel = new Model_Topic_Topic();
			$topic = $topicModel->getTopicInfo($topicID);

			if ($topicID < 1 || empty($topic)) {
				$this->returnJson(parent::STATUS_FAILURE, '参数错误！');
			}


			$lastViewID= $this->_getParam('lastViewID', 0);
			$pageSize = $this->_getParam('pagesize', 10);
			
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}			

			$viewModel = new Model_Topic_View();
			$fields = array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','IsAnonymous','AnonymousUserName','AnonymousAvatar');						
			$select = $viewModel->select()->from('topic_views',$fields)->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1);					
			if($lastViewID > 0){
				$select->where('ViewID < ?',$lastViewID);
			}			
			$select->order('ViewID desc')->limit($pageSize);						
			$results = $select->query()->fetchAll();

			//获取用户名和头像及观点图片
			if(!empty($results)){	

				if($lastViewID==0){//记录获取的最新观点ID
					$lastViewIDKey = 'topicLastViewID:'.$topicID;
					Model_Member::staticData($memberID,$lastViewIDKey,$results[0]['ViewID']);
				}	
						
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$v){
					//$v['NoteName'] = $memberID>0?$memberNoteModel->getNoteName($memberID, $v['MemberID']):'';
					$v['IsPraised'] = $memberID>0?$viewModel->isPraised($v['ViewID'], $memberID):0;
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
					if($v['IsAnonymous'] ==1){
						$userName = $v['AnonymousUserName'];
						$avatar = $v['AnonymousAvatar'];
						$noteName = '';
						if($v['MemberID'] == $memberID){
							$v['IsMine'] = 1;
						}else{
							$v['IsMine'] = 0;
						}
						unset($v['MemberID']);
					}else{
						$userName = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
						$avatar = $memberModel->getMemberAvatar($v['MemberID']);
						$noteName = $memberID>0?$memberNoteModel->getNoteName($memberID, $v['MemberID']):'';
					}
					//unset($v['IsAnonymous']);
					unset($v['AnonymousUserName']);
					unset($v['AnonymousAvatar']);
					$v['Avatar'] = $avatar;
					$v['UserName'] = $userName;
					$v['NoteName'] = $noteName;
				}
			}
			if($memberID){
				$topicModel->addRecentUse($topicID, $memberID);
			}

			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time()));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/*
	 *话题-热门观点列表
	 */
	public function topicHotViewListAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);

			$pageIndex= $this->_getParam('page', 0);
			$pageSize = $this->_getParam('pagesize', 10);

			if($topicID <=0 ){
				$this->returnJson(parent::STATUS_FAILURE, '话题ID不能为空！');
			}

			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			
			$topicModel = new Model_Topic_Topic();
			$topic = $topicModel->getTopicInfo($topicID);
			if(empty($topic)){
				$this->returnJson(parent::STATUS_TOPIC_NOTEXIST, '该话题不存在！');
			}

			$viewModel = new Model_Topic_View();
			$fields = array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum');						
			$select = $viewModel->select()->from('topic_views',$fields)->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1);					
			

			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
			//总条数
			$total = $viewModel->getAdapter()->fetchOne($countSql);
			$results = $select->order('Liveness desc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();

			//获取用户名和头像及观点图片
			if(!empty($results)){				
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$v){
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberID>0?$memberNoteModel->getNoteName($memberID, $v['MemberID']):'';
					$v['IsPraised'] = $memberID>0?$viewModel->isPraised($v['ViewID'], $memberID):0;
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
				}
			}

			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time(),'page'=>$pageIndex));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}

	}

	/**
	 * 某一话题观点列表
	 */
	public function topicViewListAction()
	{
		try{
			$topicID = $this->_request->getParam('topicID',0);			
			if($topicID <=0 ){
				$this->returnJson(parent::STATUS_FAILURE, '话题ID不能为空！');
			}

			// $keyWords = trim($this->_request->getParam('keyWords',''));
			// if(empty($keyWords)){
			// 	throw new Exception('关键字不能未空');
			// }
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}			
			$pageIndex= $this->_getParam('page', 1);
			$pageSize = $this->_getParam('pagesize', 10);


			$topicModel = new Model_Topic_Topic();
			$topic = $topicModel->getTopicInfo($topicID);
			if(empty($topic)){
				$this->returnJson(parent::STATUS_TOPIC_NOTEXIST, '该话题不存在！');
			}
			$isFollowed = $memberID>0 ? $topicModel->isFollowedTopic($memberID,$topicID):0;
			$topicInfo = array('TopicID'=>$topic['TopicID'],'TopicName'=>$topic['TopicName'],'BackImage'=>$topic['BackImage'],'FollowNum'=>$topic['FollowNum'],'ViewNum'=>$topic['ViewNum'],'IsFollowed'=>$isFollowed);

			$viewModel = new Model_Topic_View();
			$fields = array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum');						
			$select = $viewModel->select()->from('topic_views',$fields)->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1);					
	
			// if(!empty($keyWords)){
			// 	$select->where('ViewContent like ?','%'.$keyWords.'%');
			// }

// 			//获取sql
// 			$countSql = $select->__toString();
// 			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
// 			//总条数
// 			$total = $viewModel->getAdapter()->fetchOne($countSql);
						
			$select->order('ViewID desc')->limitPage($pageIndex, $pageSize);			
			$results = $select->query()->fetchAll();

			//获取用户名和头像及观点图片
			if(!empty($results)){				
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$v){
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberID>0?$memberNoteModel->getNoteName($memberID, $v['MemberID']):'';
					$v['IsPraised'] = $memberID>0?$viewModel->isPraised($v['ViewID'], $memberID):0;
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
				}
				//$replyModel = new Model_Topic_Reply();
				// if(!empty($keyWords) && $pageIndex == 1){
				// 	$keywordsModel = new Model_Topic_SearchKeyWords();
				// 	$keywordsModel->recordKeyWords($topicID, $keyWords);
				// }
			}
			if($memberID){
				$topicModel->addRecentUse($topicID, $memberID);
			}

			$this->returnJson(parent::STATUS_OK,'',array('TopicInfo'=>$topicInfo,'Rows'=>$results,'TimeStamp'=>time()));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 他人观点列表
	 */
	public function otherViewListAction()
	{
		try{
			$memberID = $this->_request->getParam('memberID',0);
			if($memberID <= 0 ){
				$this->returnJson(parent::STATUS_FAILURE, '用户ID不能为空！');
			}
						
			//$pageIndex= $this->_getParam('page', 1);
			$lastViewID= $this->_getParam('lastViewID', 0);
			$pageSize = $this->_getParam('pagesize', 10);

			$fields = array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','AllTopicIDs');	

			$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$select->from('topic_views',$fields)->where('topic_views.MemberID = ?',$memberID)->where('topic_views.CheckStatus = ?',1)->where('topic_views.IsAnonymous != ?',1)->where('topic_views.ParentID = ?',0);
			$select->joinLeft('topics','topics.TopicID=topic_views.TopicID',array('TopicID','TopicName'));//->where('topics.CheckStatus = ?',1);
			

// 			//获取sql
// 			$countSql = $select->__toString();
// 			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
// 			//总条数
// 			$total = $viewModel->getAdapter()->fetchOne($countSql);

			if($lastViewID > 0){
				$select->where('topic_views.ViewID < ?',$lastViewID);
			}
			
			$select->order('ViewID desc')->limit($pageSize);
						
			$results = $select->query()->fetchAll();

			//获取用户名、头像及观点图片
			if(!empty($results)){				
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				$topicModel = new Model_Topic_Topic();
				foreach($results as &$v){
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $v['MemberID']);
					$v['IsPraised'] = $viewModel->isPraised($v['ViewID'], $this->memberInfo->MemberID);
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
					$v['TopicArr'] = $topicModel->getAllTopics($v['AllTopicIDs']);
				}
				//$replyModel = new Model_Topic_Reply();
				// if(!empty($keyWords) && $pageIndex == 1){
				// 	$keywordsModel = new Model_Topic_SearchKeyWords();
				// 	$keywordsModel->recordKeyWords($topicID, $keyWords);
				// }
			}
			$this->returnJson(parent::STATUS_OK,'', array('Rows'=>$results,'TimeStamp'=>time()));
				
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
		
	
	/**
	 * 获取观点详情
	 */
	public function detailAction()
	{
		try{
			$viewID = $this->_request->getParam('viewID',0);
			if(empty($viewID)){
				throw new Exception('观点ID有误！');
			}
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			$fields = array('ViewID','TopicID','MemberID','ViewContent','PraiseNum','ReplyNum','ShareNum','CreateTime','IsAnonymous','AnonymousUserName','AnonymousAvatar','AllTopicIDs');
				
			$viewModel = new Model_Topic_View();
			$memberModel = new DM_Model_Account_Members();
			$viewImageModel = new Model_Topic_ViewImage();
			$memberNoteModel = new Model_MemberNotes();
			
			$select = $viewModel->select()->setIntegrityCheck(false)->from('topic_views',$fields);
			$select->joinLeft('topics', 'topic_views.TopicID = topics.TopicID',array('TopicName','BackImage'))->where('ViewID = ?',$viewID)->where('topic_views.CheckStatus = ?',1);
			$viewInfo = $select->query()->fetch();
			if(empty($viewInfo)){
				throw new Exception('该内容已删除');
			}
			$viewInfo['IsPraised'] = $memberID>0 ? $viewModel->isPraised($viewID, $memberID):0;
			$viewInfo['Images'] = $viewImageModel->getImages($viewInfo['ViewID']);
			$viewInfo['TimeStamp'] = time();
			
			//$viewInfo['Avatar'] = $memberModel->getMemberAvatar($viewInfo['MemberID']);
			//$viewInfo['UserName'] = $memberModel->getMemberInfoCache($viewInfo['MemberID'],'UserName');
			//$viewInfo['NoteName'] = $memberID>0?$memberNoteModel->getNoteName($memberID, $viewInfo['MemberID']):'';
			$giftMember = array();
			$praiseMember = array();
			$noteName = '';
			
			if($viewInfo['IsAnonymous']==1){
				$userName = $viewInfo['AnonymousUserName'];
				$avatar = $viewInfo['AnonymousAvatar'];
				if($viewInfo['MemberID'] == $memberID){
					$viewInfo['IsMine'] = 1;
				}else{
					$viewInfo['IsMine'] = 0;
				}
				unset($viewInfo['MemberID']);
			}else{
				$avatar = $memberModel->getMemberAvatar($viewInfo['MemberID']);
				$userName = $memberModel->getMemberInfoCache($viewInfo['MemberID'],'UserName');
				$noteName = $memberID>0?$memberNoteModel->getNoteName($memberID, $viewInfo['MemberID']):'';

				$giftModel = new Model_Topic_ViewGift();
				$giftArr = $giftModel->getExpensiveGift($viewID,5);
				//$giftMember = array();
				if(!empty($giftArr)){
					foreach($giftArr as $k=>$val){
						$giftMember[$k]['Avatar'] = $memberModel->getMemberAvatar($val['GiftMemberID']);
						$giftMember[$k]['MemberID'] = $val['GiftMemberID'];
					}
				}

				$praiseModel = new Model_Topic_Praise();
				$info = $praiseModel->getPraiseMember($viewID);
				//$praiseMember = array();
				if(!empty($info)){
					foreach($info as $k=>$row){
						$praiseMember[$k]['Avatar'] = $memberModel->getMemberAvatar($row['MemberID']);
					}
				}
			}
			//unset($viewInfo['IsAnonymous']);
			unset($viewInfo['AnonymousUserName']);
			unset($viewInfo['AnonymousAvatar']);
			$viewInfo['Avatar'] = $avatar;
			$viewInfo['UserName'] = $userName;
			$viewInfo['NoteName'] = $noteName;
			$viewInfo['GiftMember'] = $giftMember;	
			$viewInfo['PraiseMember'] = $praiseMember;
			$topicModel = new Model_Topic_Topic();
			if(empty($viewInfo['AllTopicIDs'])){
				$viewInfo['AllTopicIDs'] = $viewInfo['TopicID'];
			}
			$viewInfo['TopicArr'] = $topicModel->getAllTopics($viewInfo['AllTopicIDs']);

			$this->returnJson(parent::STATUS_OK,'',$viewInfo);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 回复观点
	 */
	public function replyAction()
	{
		try{
			$viewID = $this->_request->getParam('viewID',0);
			if(empty($viewID)){
				throw new Exception('观点ID有误！');	
			}
			
			$replyContent = trim($this->_request->getParam('replyContent',''));
			if(empty($replyContent)){
				throw new Exception('回复内容不能为空！');
			}
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			if(empty($viewInfo) || $viewInfo['CheckStatus'] != 1){
				throw new Exception('该观点不存在!');
			}
			
			$memberModel = new DM_Model_Account_Members();
			$memberID = $this->memberInfo->MemberID;
			$isAnonymous = 0;
			$anonymousUserName = '';
			$anonymousAvatar = '';
			if($viewInfo['IsAnonymous']==1){
				$isAnonymous = 1;
				$anonymousUserName = $memberModel->getAnonymousUserName($memberID);
				$anonymousAvatar = $memberModel->getAnonymousAvatar($memberID);
			}
			
			$replyMemberID = intval($this->_getParam('replyMemberID',0));
			$relationID = 0;
			$replyModel = new Model_Topic_Reply();
			if($replyMemberID <= 0 ){
				$relationID = intval($this->_getParam('replyID',0));
				if($relationID > 0){
					$replyInfo = $replyModel->getReplyInfo($relationID);
					if (!empty($replyInfo)) {
						$replyMemberID = $replyInfo['MemberID'];
					}
				}
			}

			$replyID = $replyModel->addRely($viewID, $memberID, $replyContent,$replyMemberID,$relationID,$isAnonymous,$anonymousUserName,$anonymousAvatar);
			$replyInfo = $replyModel->getReplyDeatail($replyID,$this->memberInfo->MemberID);
			$replyInfo['TimeStamp'] = time();
			$this->returnJson(parent::STATUS_OK,'评论成功',$replyInfo);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 回复列表
	 */
	public function replyListAction()
	{
		try{
			$viewID = $this->_request->getParam('viewID',0);
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			if(empty($viewID) || empty($viewInfo)){
				throw new Exception('观点ID有误！');
			}
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			$pagesize = intval($this->_request->getParam('pagesize',10));
			$lastReplyID = intval($this->_request->getParam('lastReplyID',0));
			$replyModel = new Model_Topic_Reply();
			$replies = $replyModel->getList($viewID,$lastReplyID,$pagesize,$memberID);
			if(!empty($replies)){
				foreach ($replies as &$item) {
					$item['IsPraised'] = $memberID>0?$replyModel->isPraised($item['ReplyID'], $memberID):0;
					if($item['IsAnonymous'] == 1){
						if($item['MemberID'] == $memberID){
							$item['IsMine'] = 1;
						}else{
							$item['IsMine'] = 0;
						}
						unset($item['MemberID']);
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$replies,'TimeStamp'=>time()));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 赞
	 */
	public function praiseAction()
	{
		try{
			$viewID = intval($this->_request->getParam('viewID',0));
			if($viewID <= 0){
				throw new Exception('观点参数无效!');
			}
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			if(empty($viewInfo)||$viewInfo['CheckStatus']!=1){
				throw new Exception('该观点不存在!');
			}
			$memberID = $this->memberInfo->MemberID;
			$praiseModel = new Model_Topic_Praise();
			$praiseModel->addPraise($viewID, $memberID);
			$praiseNum = $viewModel->getPraisedNum($viewID);
			
			$info = $praiseModel->getPraiseMember($viewID);
			$praiseMember = array();
			
			if($viewInfo['IsAnonymous'] == 0){
				$memberModel = new DM_Model_Account_Members();
				if(!empty($info)){
					foreach($info as $k=>$row){
						$praiseMember[$k]['Avatar'] = $memberModel->getMemberAvatar($row['MemberID']);
					}
				}
			}
			
			$this->returnJson(parent::STATUS_OK,'已赞！',array('PraiseNum'=>$praiseNum,'PraiseMember'=>$praiseMember));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消赞
	 */
	public function unPraiseAction()
	{
		try{
			$viewID = intval($this->_request->getParam('viewID',0));
			if($viewID <= 0){
				throw new Exception('观点参数无效!');
			}
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			if(empty($viewInfo)||$viewInfo['CheckStatus']!=1){
				throw new Exception('该观点不存在!');
			}
			$memberID = $this->memberInfo->MemberID;
			$praiseModel = new Model_Topic_Praise();
			$praiseModel->unPraise($viewID, $memberID);
			
			
			$praiseNum = $viewModel->getPraisedNum($viewID);
			$memberModel = new DM_Model_Account_Members();
			$info = $praiseModel->getPraiseMember($viewID);
			
			$praiseMember = array();
			
			if($viewInfo['IsAnonymous'] == 0){
				if(!empty($info)){
					foreach($info as $k=>$row){
						$praiseMember[$k]['Avatar'] = $memberModel->getMemberAvatar($row['MemberID']);
					}
				}
			}
			
			$this->returnJson(parent::STATUS_OK,'已取消赞',array('PraiseNum'=>$praiseNum,'PraiseMember'=>$praiseMember));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 分享回调
	 */
	public function shareCallAction()
	{
		try{
			$viewID = intval($this->_request->getParam('viewID',0));
			if($viewID <= 0){
				throw new Exception('观点参数无效!');
			}
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			if(empty($viewInfo)||$viewInfo['CheckStatus']!=1){
				throw new Exception('该观点不存在!');
			}
			$viewModel->update(array('ShareNum'=>new Zend_Db_Expr('ShareNum + 1')),array('ViewID = ?'=>$viewID));
			$redisObj = DM_Module_Redis::getInstance();
			$shareKey = 'shareNum:date'.date('Y-m-d');
			$redisObj->INCR($shareKey);
            $redisObj->EXPIRE($shareKey,86400);
			$this->returnJson(parent::STATUS_OK);			
		}catch(Exception $e){
			return $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/*
	 *我的观点列表
	 */
	public function myViewsAction()
	{

		try{
			$memberID = $this->memberInfo->MemberID;
			if($memberID < 0){
				throw new Exception('登录状态无效！');
			}

			$lastViewID = $this->_getParam('lastViewID',0);

			$pageSize = $this->_getParam('pagesize', 10);
			
			$fields = array('ViewID','TopicID','ViewContent','PraiseNum','ReplyNum','ShareNum','CreateTime','MemberID','IsAnonymous','AnonymousUserName','AnonymousAvatar','AllTopicIDs');
			
			$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false)->from('topic_views as tv',$fields)->where('tv.MemberID = ?',$memberID)->where('tv.CheckStatus = ?',1)->where('tv.ParentID = ?',0);
		    $select->joinLeft('topics as t', 'tv.TopicID = t.TopicID ','TopicName');
			if($lastViewID > 0){
				$select->where('tv.ViewID < ?',$lastViewID);
			}
			$select->order('tv.ViewID desc')->limit($pageSize);
			
			$results = $select->query()->fetchAll();
			
			//获取最新5条回复
			if(!empty($results)){
				$replyModel = new Model_Topic_Reply();
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$topicModel = new Model_Topic_Topic();
				foreach($results as &$v){
					$v['NoteName'] = '';
					$v['IsPraised'] = $viewModel->isPraised($v['ViewID'], $this->memberInfo->MemberID);
					//$v['Replies'] = $replyModel->getList($v['ViewID'],0,5);
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
					if($v['IsAnonymous'] == 1){
						$userName = $v['AnonymousUserName'];
						$avatar = $v['AnonymousAvatar'];
						unset($v['MemberID']);
					}else{
						$userName = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
						$avatar = $memberModel->getMemberAvatar($v['MemberID']);
					}
					unset($v['AnonymousUserName']);
					unset($v['AnonymousAvatar']);
					$v['UserName'] = $userName;
					$v['Avatar'] = $avatar;
					
					if(empty($v['AllTopicIDs'])){
						$v['AllTopicIDs'] = $v['TopicID'];
					}
					$v['TopicArr'] = $topicModel->getAllTopics($v['AllTopicIDs']);
				}
				
			}
			
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time()));
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/*
	 *删除我的观点
	 */
	public function deleteAction()
	{

		try{
			$viewID = intval($this->_request->getParam('viewID',0));
			if($viewID <= 0){
				throw new Exception('观点ID有误！');
			}
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);

			$retCount = $viewModel->update(array('CheckStatus'=>2),array('ViewID = ?'=>$viewID));
			if($retCount > 0){
				$topicModel = new Model_Topic_Topic();
				$topicModel->increaseViewNum($viewInfo['TopicID'],-1);
				//删除redis里保存的数据
				$redisObj = DM_Module_Redis::getInstance();
				$value = 'delete-'.$viewID.'-'.$this->memberInfo->MemberID;
				$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
			}
			$this->returnJson(parent::STATUS_OK,'已删除！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 财猪首页我关注的人的观点 已废弃
	 */
	public function followedMemberViewsAction()
	{
		try{
			$lastViewID = $this->_getParam('lastViewID','+inf');
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$viewModel = new Model_Topic_View();
			$results = $viewModel->followedMemberViews($memberID,$lastViewID,$pagesize,$lastBarNum);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 新话题首页我关注的人的观点 已废弃
	 */
	public function newFollowedMemberViewsAction()
	{
		try{
			$lastViewID = $this->_getParam('lastViewID','+inf');
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$viewModel = new Model_Topic_View();
			$results = $viewModel->followedMemberViews($memberID,$lastViewID,$pagesize,$lastBarNum,1);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除观点评论
	 */
	public function delCommentAction()
	{
		try{
			$commentID = intval($this->_request->getParam('commentID',0));
			if($commentID <= 0){
				throw new Exception('参数错误！');
			}
			$replyModel = new Model_Topic_Reply();
			$viewModel = new Model_Topic_View();
			$info = $replyModel->getReplyInfo($commentID,$this->memberInfo->MemberID);
			if(empty($info)){
				throw new Exception('无权删除');
			}
			$viewID = $info['ViewID'];
			$re = $replyModel->update(array('Status'=>0),array('ReplyID = ?'=>$commentID));
			if($re){
// 				$num = new Zend_Db_Expr("`ReplyNum` - 1");
// 				$viewModel->update(array('ReplyNum'=>$num), array('ViewID = ?'=>$viewID));
					$viewModel->increaseReplyNum($viewID,-1);
			}
			$this->returnJson(parent::STATUS_OK,'已删除！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	

	/*
	 *频道下观点列表
	 */
	public function channelViewsAction()
	{
		$lastViewID = $this->_getParam('lastViewID',0);
		$pageSize = $this->_getParam('pagesize', 20);

		//$pageIndex = $this->_getParam('page', 1);
		//$pageSize = $this->_getParam('pagesize', 20);
		//
		$channelID = intval($this->_request->getParam('channelID',0));
		if($channelID <= 0){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
        $channelFocusModel = new Model_ChannelFocus();
        $focusID = $channelFocusModel->getChannelFocusID($channelID);
        $results = array();
        $total = 0;
        if(count($focusID) >0 ){
        	$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$fields = array('ViewID'=>new Zend_Db_Expr("DISTINCT(tv.ViewID)"),'MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum','TopicID');	
			$select->from('topic_views as tv',$fields)->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous != ?',1);
			$select->joinLeft('topics as t','t.TopicID = tv.TopicID','TopicName');
			$select->joinLeft('topic_focus as tf','t.TopicID = tf.TopicID','')->where('tf.FocusID in (?)',$focusID);
			

			//获取sql
			//$countSql = $select->__toString();
			//$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(DISTINCT tv.ViewID) AS total FROM', $countSql);

			//总条数
			//$total = $viewModel->getAdapter()->fetchOne($countSql);	
			
			if($lastViewID > 0){
				$select->where('tv.ViewID < ?',$lastViewID);
			}

			$select->order('tv.ViewID desc')->limit($pageSize);
			$results = $select->query()->fetchAll();

			if(!empty($results)){				
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$v){
					$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $v['MemberID']);
					$v['IsPraised'] = $viewModel->isPraised($v['ViewID'], $this->memberInfo->MemberID);
					$v['Images'] = $viewImageModel->getImages($v['ViewID']);
				}
			}
        }

        $this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time()));
	}

	/**
	 * 获取轮播广告
	 */
	public function getBannerAdsAction()
	{
		try{
			$showType = intval($this->_getParam('showType',0));
			if(!in_array($showType, array(3,4,5,7,8,9))){
				Throw new Exception('参数错误');
			}
			$requestObj = DM_Controller_Front::getInstance()->getHttpRequest();
			$platform = $requestObj->getParam('platform','0');
			$adsModel = new Model_Ads();
			$result = $adsModel->bannerAds($showType,$platform);
			if($showType == 9 && !empty($result)){
				$result = array($result[0]);
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}

	}


	/**
	 * 话题首页-好友的观点列表
	 */
	public function getFriendsViewListAction()
	{
		try{
			$lastViewID = $this->_getParam('lastViewID','+inf');
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$viewModel = new Model_Topic_View();
			$friendViewArr = $viewModel->getFriendsViewList($memberID,$lastViewID,$pagesize,false);
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$maxBarNum = $lastBarNum;
			// if(empty($friendViewArr)){
			// 	$friendModel = new Model_IM_Friend();
			// 	$friendsInfo = $friendModel->getFriendInfo($memberID);
			// 	if(empty($friendsInfo)){
			// 		$friendArr = array($memberID);
			// 	}else{
			// 		$friendArr = array_column($friendsInfo,'FriendID');
			// 		$friendArr = array_unshift($friendArr, $memberID);
			// 	}
			// }
			
			$results = array();
			$friendViewList = array();

			if(!empty($friendViewArr)){
				$select = $viewModel->select()->setIntegrityCheck(false);

				$select->from('topic_views as tv',array('ContentType'=>new Zend_Db_Expr(2),'ViewID','TopicID','MemberID','PraiseNum','ReplyNum','CreateTime','ViewContent','AllTopicIDs'))
							->where('tv.ViewID in (?)',$friendViewArr)
							->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous != ?',1)->where('tv.ParentID = ?',0);
				// if(!empty($friendViewArr)){
				// 	$select->where('tv.ViewID in (?)',$friendViewArr);
				// }else{
				// 	$select->where('tv.MemberID in (?)',$friendArr);
				// }
				$select->joinLeft('topics as t','t.TopicID = tv.TopicID','')->where('t.CheckStatus = ?',1)->where('t.IsAnonymous != ?',1);
				
				if($lastViewID > 0){
					$select->where('tv.ViewID < ?',$lastViewID);
				}
				$select->order('tv.ViewID desc')->limit($pagesize);

				$friendViewList = $select->query()->fetchAll();
			}

			if(!empty($friendViewList)){
								
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$topicModel = new Model_Topic_Topic();
				$memberNoteModel = new Model_MemberNotes();
				$adsArr = array();
				
				$adsModel = new Model_Ads();
				$adsCount = intval(count($friendViewList) / 5)+1;
				$ads = $adsModel->getAdsList($lastBarNum,$adsCount,5);
				$adsArr = $ads['ads'];
				$maxBarNum = $ads['maxNum'];

				foreach($friendViewList as $key=>&$val){
					if(!empty($adsArr)){
						if($key % 5 === 0 && $key != 0){
							if(!empty($adsArr[intval($key / 5)])){
								$results[] = $adsArr[intval($key / 5)];
							}
						}
					}
					$topicInfo = $topicModel->getTopicInfo($val['TopicID'],null);
					$val['TopicName'] = $topicInfo['TopicName'];
					$val['IsPraised'] = $viewModel->isPraised($val['ViewID'], $memberID);
					$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['Images'] = $viewImageModel->getImages($val['ViewID']);
					if(empty($val['AllTopicIDs'])){
						$val['AllTopicIDs'] = $val['TopicID'];
					}
					$val['TopicArr'] = $topicModel->getAllTopics($val['AllTopicIDs']);
					$results[] = $val;
				}		
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time(),'lastBarNum'=>$maxBarNum));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/*
	 *获取匿名观点列表
	 */
	public function getAnonymousViewListAction()
	{
		try{
			$lastViewID = $this->_getParam('lastViewID','+inf');
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$viewModel = new Model_Topic_View();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$select->from('topic_views as tv',array('ViewID','TopicID','MemberID','PraiseNum','ReplyNum','CreateTime','ViewContent','IsAnonymous','AnonymousUserName','AnonymousAvatar'))->where('tv.CheckStatus = ?',1)->where('tv.IsAnonymous = ?',1);
			$select->joinLeft('topics as t','t.TopicID = tv.TopicID','')->where('t.CheckStatus = ?',1)->where('t.IsAnonymous = ?',1);

			if($lastViewID > 0){
				$select->where('tv.ViewID < ?',$lastViewID);
			}
			$select->order('tv.ViewID desc')->limit($pagesize);
		
			//$select->order("ViewID desc");
			$results = $select->query()->fetchAll();
			$memberModel = new DM_Model_Account_Members();
			$viewImageModel = new Model_Topic_ViewImage();
			$topicModel = new Model_Topic_Topic();
			$memberNoteModel = new Model_MemberNotes();
			if(!empty($results)){
				foreach($results as $key=>&$val){
					$topicInfo = $topicModel->getTopicInfo($val['TopicID'],null);
					$val['TopicName'] = $topicInfo['TopicName'];
					$val['IsPraised'] = $viewModel->isPraised($val['ViewID'], $memberID);
					$val['Images'] = $viewImageModel->getImages($val['ViewID']);
					if($val['MemberID'] == $memberID){
					// 	$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
					// 	$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					// 	$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					 	$val['IsMine'] = 1;
					}else{
					// 	$val['Avatar'] = $val['AnonymousAvatar'];
					// 	$val['UserName'] = $val['AnonymousUserName'];
					// 	$val['NoteName'] ='';
						$val['IsMine']= 0;
					} 
					$val['Avatar'] = $val['AnonymousAvatar'];
					$val['UserName'] = $val['AnonymousUserName'];
					$val['NoteName'] ='';
					unset($val['MemberID']);
					unset($val['AnonymousUserName']);
					unset($val['AnonymousAvatar']);
				}
				$viewModel->updateLastIDCache($memberID, $results[0]['ViewID']);
			}

			$AnonymousUserName = $memberModel->getAnonymousUserName($memberID);
			$AnonymousAvatar = $memberModel->getAnonymousAvatar($memberID);
			$tempInfo = $topicModel->getAnonymousTopicInfo();
			$anonymousTopicInfo = $tempInfo[0];
			
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'AnonymousTopicInfo'=>$anonymousTopicInfo,'AnonymousUserName'=>$AnonymousUserName,'AnonymousAvatar'=>$AnonymousAvatar,'TimeStamp'=>time()));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}


	/**
	 * 回复点赞
	 */
	public function replyPraiseAction()
	{
		try{
			$replyID = intval($this->_request->getParam('replyID',0));
			if($replyID <= 0){
				throw new Exception('参数错误!');
			}
			$replyModel = new Model_Topic_Reply();
			$replyInfo = $replyModel->getReplyInfo($replyID);
			if(empty($replyInfo)||$replyInfo['Status']!=1){
				throw new Exception('该评论不存在!');
			}
			$memberID = $this->memberInfo->MemberID;
			$praiseModel = new Model_Topic_ReplyPraise();
			$praiseModel->addPraise($replyID, $memberID);
			$praiseNum = $replyModel->getPraisedNum($replyID);
			$this->returnJson(parent::STATUS_OK,'已赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消回复点赞
	 */
	public function replyUnpraiseAction()
	{
		try{
			$replyID = intval($this->_request->getParam('replyID',0));
			if($replyID <= 0){
				throw new Exception('参数错误!');
			}
			$replyModel = new Model_Topic_Reply();
			$replyInfo = $replyModel->getReplyInfo($replyID);
			if(empty($replyInfo)||$replyInfo['Status']!=1){
				throw new Exception('该评论不存在!');
			}
			$memberID = $this->memberInfo->MemberID;
			$praiseModel = new Model_Topic_ReplyPraise();
			$praiseModel->unPraise($replyID, $memberID);
			
			$praiseNum = $replyModel->getPraisedNum($replyID);
			
			$this->returnJson(parent::STATUS_OK,'已取消赞',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取好友最新发布观点的数量及对应头像
	 */
	public function newPublishViewFriendsAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$viewModel = new Model_Topic_View();
			$memberModel = new DM_Model_Account_Members();
			$counts = $viewModel->hasNewFriendViews($memberID);
			$result =array();
			if($counts>0){
				$lastViewID = Model_Member::staticData($memberID,'lastFriendViewID');
				if($lastViewID !== false){
					$redisObj = DM_Module_Redis::getInstance();
					$key = 'Friends:View:MemberID'.$memberID;
					$viewArr = $redisObj->zRevRangeByScore($key,'+inf','('.$lastViewID);
					$select = $viewModel->select();
					$select->from('topic_views',array('distinct(MemberID)'))->where('ViewID in (?)',$viewArr)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1)->where('MemberID != ?',$memberID);
					$select->order("ViewID desc");
					$viewInfo = $select->query()->fetchAll();
					$counts = count($viewInfo);
					
					
					$select = $viewModel->select();
					$select->from('topic_views',array('MemberID'))->where('ViewID in (?)',$viewArr)->where('CheckStatus = ?',1)->where('IsAnonymous != ?',1)->where('MemberID != ?',$memberID);
					$select->order("ViewID desc")->limit(100);
					$viewInfo = $select->query()->fetchAll();
					
					$tmpMemberIds = array();
					
					if(!empty($viewInfo)){
						$idCounts = 0;
						foreach($viewInfo as $item){
							if(in_array($item['MemberID'], $tmpMemberIds)){
								continue;
							}else{
								$tmpMemberIds[] = $item['MemberID'];
								$result[] = $item;
								$idCounts ++;
							}
							if($idCounts >= 5){
								break;
							}
						}
					}

					if(!empty($result)){
						foreach($result as &$val){
							$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
						}
					}
				}	
			}
			$this->returnJson(parent::STATUS_OK,'',array('Count'=>$counts,'Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 邀请某人评论某个观点（不保存记录，当作消息发给好友）
	 */
	public function inviteCommentViewAction()
	{
		try{
			$friendID = trim($this->_getParam('friendID',''));
			$viewID = intval($this->_getParam('viewID',0));
			$messageCotent = trim($this->_getParam('messageContent',''));
			
			$friendIDArr = explode(',', $friendID);
			
			if(count($friendIDArr)<1 || $viewID<1){
				throw new Exception('参数错误！');
			}
			
			if(empty($messageCotent)){
				throw new Exception('留言内容不能为空！');
			}
			if(mb_strlen($messageCotent,'UTF-8') > 100){
				throw new Exception('留言内容不能超过100个字符！');
			}
			$viewModel = new Model_Topic_View();
			$memberModel = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
// 			$viewInfo = $viewModel->getViewInfo($viewID);
// 			if($viewInfo['IsAnonymous']==1){//匿名观点
// 				$avatar = $viewInfo['AnonymousAvatar'];
// 				$userName = $viewInfo['AnonymousUserName'];
// 				$noteName = '';
// 			}else{

// 			foreach ($friendIDArr as $friendID){
// 				$avatar = $memberModel->getMemberAvatar($this->memberInfo->MemberID);
// 				$userName = $memberModel->getMemberInfoCache($this->memberInfo->MemberID,'UserName');
// 				$noteName = $memberNoteModel->getNoteName($friendID, $this->memberInfo->MemberID);
				
//			}
			//SOCKET TO DO 发消息给好友
				$avatar = $memberModel->getMemberAvatar($this->memberInfo->MemberID);
				$data = array(
						"Type" => 1,
						"Time" => date("Y-m-d H:i:s", time()),
						'MemberID'=>$this->memberInfo->MemberID,
						'Avatar'=>$avatar,
						'ViewID'=>$viewID,
						"Message" => $messageCotent,
						"Ext" => ''
				);
				$sendData = array("CZSubAction" => "view", "data" => $data);
				$result = DM_Socket_Notice::getInstance()->push($friendIDArr,$sendData);
				//print_r($sendData);exit;
			
			$this->returnJson(parent::STATUS_OK,'邀请发送成功');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
}