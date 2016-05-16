<?php
/**
 * 理财号
 * @author Jeff
 *
 */
class Api_ColumnController extends Action_Api
{
	public function init()
	{
		parent::init();
		$actionArr = array('all-article-list','hot-topics','enroll-activity','activity-list','get-all-activity');
		if(!in_array($this->_getParam('action'),$actionArr)){
			$this->isLoginOutput();
		}
	}
	
	/**
	 * 财猪首页(所有文章列表)
	 */
	public function allArticleListAction()
	{
		try{
			$page = $this->_getParam('page',1);
			$memberID = 0;
			$pagesize = intval($this->_getParam('pagesize',30));
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$articleModel = new Model_Column_Article();
			$results = $articleModel->allArticles($memberID,$page,$pagesize,$lastBarNum);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 理财号首页（我关注人发表的文章）
	 */
	public function followMemberArticleAction()
	{
		try{
			$lastArticleID = $this->_getParam('lastArticleID',0);
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$articleModel = new Model_Column_Article();
			$results = $articleModel->followedMemberArticle($memberID,$lastArticleID,$pagesize,$lastBarNum);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 关注页面（我关注理财号的文章）
	 */
	public function subscribeListAction()
	{
		try{
			$lastArticleID = $this->_getParam('lastArticleID',0);
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_getParam('pagesize',30));
			$articleModel = new Model_Column_Article();
			$results = $articleModel->followedColumnArticle($memberID,$lastArticleID,$pagesize);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 头条
	 */
	public function topArticleAction()
	{
		try{
			$pageIndex= $this->_getParam('page', 1);
			$pageSize = $this->_getParam('pagesize', 30);
			$isAds = intval($this->_getParam('isAds',0));//0 不带广告 1带广告
			$lastBarNum = intval($this->_getParam('lastBarNum',0));
			$articleModel = new Model_Column_Article();
			$results = $articleModel->getTopArticle($pageIndex,$pageSize,$this->memberInfo->MemberID,$isAds,$lastBarNum);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
		
	/**
	 * 某个频道下的文章
	 */
	public function channelArticleAction()
	{
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('pagesize', 30);
		$channelID = intval($this->_request->getParam('channelID',0));
		if($channelID <= 0){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
		$channelFocusModel = new Model_ChannelFocus();
		$focusID = $channelFocusModel->getChannelFocusID($channelID);
		$results = array();
		$total = 0;
		if(count($focusID) >0 ){
			$viewModel = new Model_Column_Article();
			$select = $viewModel->select()->setIntegrityCheck(false);
			$fields = array('AID'=>new Zend_Db_Expr("DISTINCT(tv.AID)"),'MemberID','ColumnID','Title','Cover','PublishTime','CreateTime');
			$select->from('column_article as tv',$fields)->where('tv.Status = ?',1);
			$select->joinLeft('column_article_focus as tf','tv.AID = tf.ArticleID','')->where('tf.FocusID in (?)',$focusID);
				
		
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(DISTINCT tv.AID) AS total FROM', $countSql);
		
			//总条数
			$total = $viewModel->getAdapter()->fetchOne($countSql);
				
			$select->order('tv.AID desc')->limitPage($pageIndex, $pageSize);
			$results = $select->query()->fetchAll();
		
			if(!empty($results)){
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				$columnModel = new Model_Column_Column();
				foreach($results as &$v){
					$v['CreateTime'] = $v['PublishTime'];//老版本中返回的CreateTime有问题
					$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
					$v['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $v['MemberID']);
					$v['ColumnTitle'] = $columnModel->getColumnInfoCache($v['ColumnID'],'Title');
				}
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
	}
		
// 	/**
// 	 * 全部理财号页
// 	 */
// 	public function listAction()
// 	{
// 		$pageIndex= $this->_getParam('page', 1);
// 		$pageSize = $this->_getParam('pagesize', 30);
	
// 		$memberID = $this->memberInfo->MemberID;
// 		$fields = array('ColumnID','Title','Avatar','Description');
// 		$columnModel = new Model_Column_Column();
// 		$select = $columnModel->select()->from('column',$fields)->where('MemberID != ?',$memberID)->where('CheckStatus = ?',1);
	
// 		//获取sql
// 		$countSql = $select->__toString();
// 		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
	
// 		//总条数
// 		$total = $columnModel->getAdapter()->fetchOne($countSql);
	
// 		$results = $select->order('ColumnID desc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
// 		if(!empty($results)){
// 			foreach ($results as &$val){
// 				$val['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID, $val['ColumnID']);
// 			}
// 		}
// 		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
// 	}
	
	/**
	 * 关注理财号
	 */
	public function subscribeAction()
	{
		try{
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				throw new Exception('参数无效!');
			}
			$cloumnModel = new Model_Column_Column();
			$cloumnInfo = $cloumnModel->getColumnInfo($columnID,1);
			if(empty($cloumnInfo)){
				throw new Exception('该理财号已不存在!');
			}
			
			$model = new Model_Column_MemberSubscribe();
			$model->addSubscribe($columnID, $this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'关注成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消关注
	 */
	public function unSubscribeAction()
	{
		try{
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				throw new Exception('参数无效!');
			}
			$cloumnModel = new Model_Column_Column();
			$cloumnInfo = $cloumnModel->getColumnInfo($columnID,1);
			if(empty($cloumnInfo)){
				throw new Exception('该理财号已不存在!');
			}
			$model = new Model_Column_MemberSubscribe();
			$model->unSubscribe($columnID, $this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'取消关注成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取某人关注的理财号
	 */
	public function mySubscribeListAction()
	{
		try{
			$lastTime= $this->_getParam('lastTime', '+inf');
			$pageSize = intval($this->_getParam('pagesize', 30));
			$memberID = intval($this->_getParam('memberID',0));
			if(!$memberID){
				$memberID = $this->memberInfo->MemberID;
			}
			$columnModel = new Model_Column_Column();
			$results = $columnModel->getSubscribeColumns($memberID,$lastTime,$pageSize,$this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取理财号下文章列表
	 */
	public function articleListAction()
	{
		try{
			$lastID= $this->_getParam('lastID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
			}
			$fields = array('AID','MemberID','Title','Cover','PublishTime');
			$articleModel = new Model_Column_Article();
			$select = $articleModel->select()->from('column_article',$fields)->where('ColumnID = ?',$columnID)->where('Status = ?',1);
			if($lastID>0){
				$select->where('AID < ?',$lastID);
			}
			$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();
			
			if(!empty($results)){
				$columnModel = new Model_Column_Column();
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['MemberID']);
					$val['ColumnID'] = $columnID;
					$val['ColumnTitle'] = $columnModel->getColumnInfoCache($columnID,'Title');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 理财号下活动列表
	 */
	public function activityListAction()
	{
		try{
            $cur_member_id = isset($this->memberInfo->MemberID)?$this->memberInfo->MemberID:0;
			$lastID= $this->_getParam('lastID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
			}
			//$memberID = $this->memberInfo->MemberID;
			$fields = array('AID','MemberID','Title','Cover','StartTime','EndTime','LimitTime','Province','City','DetailAdress','EnrollNum','CreateTime');
			$activityModel = new Model_Column_Activity();
			$select = $activityModel->select()->from('column_activity',$fields)->where('ColumnID = ?',$columnID)->where('Status = ?',1);
				
			if($lastID>0){
				$select->where('AID < ?',$lastID);
			}	
			$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();
				
			if(!empty($results)){
				$columnModel = new Model_Column_Column();
				$columnInfo = $columnModel->getColumnInfo($columnID);
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($cur_member_id, $val['MemberID']);
					$val['ColumnID'] = $columnID;
					$val['ColumnTitle'] = $columnModel->getColumnInfoCache($columnID,'Title');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取某一理财号信息
	 */
	public function columnInfoAction()
	{
		try{
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
			}
			$model = new Model_Column_Column();
			$info = $model->getColumnInfo($columnID,1);
			if(empty($info)){
				$this->returnJson(parent::STATUS_FAILURE,'该理财号已不存在！');
			}
			$authenticateModel =new Model_Authenticate();
			$qualificationModel = new Model_Qualification();
			$subject='';
			$qualification='';
			$bestName = '';
			$authenticateType = 0;
			$authenticateInfo = $authenticateModel->getInfoByMemberID($info['MemberID'],1);
			if(!empty($authenticateInfo)){
				$authenticateType = $authenticateInfo['AuthenticateType'];
				if($authenticateType == 1){
					$first = mb_substr($authenticateInfo['OperatorName'], 0, 1, 'utf-8');
					$subject = $first.str_repeat('*', mb_strlen($authenticateInfo['OperatorName'], 'utf-8') - 1);
				}elseif($authenticateType == 2){
					$subject = $authenticateInfo['OperatorName'];
					$qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);

					if(empty($qualificationInfo)){
						$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1,'FinancialQualificationID desc',array('FinancialQualificationType'));
					}
					if(!empty($qualificationInfo)){
						$qualification = $qualificationInfo['FinancialQualificationType'];
					}
				}elseif($authenticateType==3){
					$subject = $authenticateInfo['BusinessName'];
				}elseif($authenticateType==4){
					$subject = $authenticateInfo['OrganizationName'];
				}
			}
			
			if($authenticateType < 2 ){
				$bestModel = new Model_Best_Best();
				$best_info = $bestModel->getNewBestInfo($info['MemberID']);
				if(!empty($best_info)){
					$bestName = $best_info['Name'];
				}
			}
			$focusModel = new Model_Column_ColumnFocus();
			$info['Focus'] = $focusModel->getInfo($columnID,null,array('ColumnFocusID as FocusID'));
			$info['AuthenticateType'] = $authenticateType;
			$info['Subject'] = $subject;
			$info['Qualification'] = $qualification;
			$info['BestName'] = $bestName;
			$info['IsSubscribe'] = $model->isSubscribeColumn($this->memberInfo->MemberID, $columnID);
			$this->returnJson(parent::STATUS_OK,'',$info);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 最新理财号
	 */
	public function newestColumnListAction()
	{
		$columnModel = new Model_Column_Column();
		$memberID = $this->memberInfo->MemberID;
		$results = $columnModel->getNewestColumn();
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID, $val['ColumnID']);
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}
	
	/**
	 * 最热理财号
	 */
	public function hotColumnListAction()
	{
		$columnModel = new Model_Column_Column();
		$memberID = $this->memberInfo->MemberID;
		$results = $columnModel->getHotColumn();
		if(!empty($results)){
			foreach ($results as &$val){
				$val['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID, $val['ColumnID']);
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
	}
	
	/**
	 * 对文章点赞
	 */
	public function praiseAction()
	{
		try{
			$articleID = intval($this->_getParam('articleID',0));
			if($articleID<1){
				throw new Exception('参数错误!'); 
			}
			$articleModel = new Model_Column_Article();
			$articleModel->addPraise($articleID, $this->memberInfo->MemberID);
			$praiseNum = $articleModel->getPraisedNum($articleID);
			$this->returnJson(parent::STATUS_OK,'已赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
			
		}
	}
	
	/**
	 * 取消对文章点赞
	 */
	public function unPraiseAction()
	{
		try{
			$articleID = intval($this->_getParam('articleID',0));
			if($articleID<1){
				throw new Exception('参数错误!');
			}
			$articleModel = new Model_Column_Article();
			$articleModel->unPraise($articleID, $this->memberInfo->MemberID);
			$praiseNum = $articleModel->getPraisedNum($articleID);
			$this->returnJson(parent::STATUS_OK,'已取消赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
				
		}
	}
	
/**
	 * 分享回调
	 */
	public function shareCallAction()
	{
		try{
			$infoID = intval($this->_request->getParam('infoID',0));
			if($infoID <= 0){
				throw new Exception('参数无效!');
			}
			$type = intval($this->_request->getParam('type',0));
			if(empty($type) || !in_array($type,array(1,2)))
			{
				throw new Exception('参数无效!');
			}
			if($type==1){
				$articleModel = new Model_Column_Article();
				$articleModel->update(array('ShareNum'=>new Zend_Db_Expr('ShareNum + 1')),array('AID = ?'=>$infoID));
			}else{
				$activityModel = new Model_Column_Activity();
				$activityModel->update(array('ShareNum'=>new Zend_Db_Expr('ShareNum + 1')),array('AID = ?'=>$infoID));
			}
			$this->returnJson(parent::STATUS_OK);			
		}catch(Exception $e){
			return $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 今日热议话题
	 */
	public function hotTopicsAction()
	{
		try{
			$type = intval($this->_getParam('type',1));
			$topicModel = new Model_Topic_Topic();
			
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}else{
				$memberID = 0;
			}
			
			$results = $topicModel->getCoumnHotTopics($type);
			if(!empty($results)){
				foreach ($results as &$val){
					if($memberID){
						$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
					}else{
						$val['IsFollowed'] = 0;
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			return $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 我发表的文章
	 */
	public function myArticlesAction()
	{
		try{
			$lastID= $this->_getParam('lastID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$memberID = $this->_getParam('memberID',0);
			if(!$memberID){
				$memberID = $this->memberInfo->MemberID;
			}
			$fields = array('AID','MemberID','ColumnID','Title','Cover','PublishTime');
			$articleModel = new Model_Column_Article();
			$select = $articleModel->select()->from('column_article',$fields)->where('MemberID = ?',$memberID)->where('Status = ?',1);
				

			if($lastID>0){
				$select->where('AID < ?',$lastID);
			}
			$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();
				
			if(!empty($results)){
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				$columnModel = new Model_Column_Column();
				foreach($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['MemberID']);
					$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 报名某个活动
	 */
	public function enrollActivityAction()
	{
		try{
			$num = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}else{
				$memberID = intval($this->_getParam('memberID'));
				if($memberID<1){
					throw new Exception('参数错误!',parent::STATUS_FAILURE);
				}
			}
			$activityID = intval($this->_getParam('activityID',0));
			if($activityID<1){
				throw new Exception('活动无效!',parent::STATUS_FAILURE);
			}
			$realName = trim($this->_getParam('realName',''));
			$mobile = trim($this->_getParam('mobile',''));
			if(empty($realName)||empty($mobile)){
				throw new Exception('姓名或手机号不能为空！',parent::STATUS_FAILURE);
			}
			if (!$mobile || !DM_Helper_Validator::checkmobile($mobile) || !Model_Member::checkMobileFormat($mobile)){
				throw new Exception('请输入正确的手机号！',parent::STATUS_FAILURE);
			}
			$money = $this->_getParam('money',0);
			$payPwd = trim($this->_getParam('payPassword',''));
			$model = new Model_Column_ActivityEnroll();
			$activityModel = new Model_Column_Activity();
			$fileds = array('AID','MemberID','Title','StartTime','EndTime','Province','City','DetailAdress','LimitNum',
			'EnrollNum','IsCharge','Cost');
			$info = $activityModel->getActvityInfo($activityID,$fileds);
			if($info['EnrollNum']>=$info['LimitNum']){
				throw new Exception('报名人数已满！',parent::STATUS_FAILURE);
			}
			$rollInfo = $model->getEnrollInfo($activityID,$memberID);
			if(!empty($rollInfo)){
				throw new Exception('您已报名，请不要重复报名！',parent::STATUS_FAILURE);
			}
			if($info['IsCharge']==1){
				$walletModel = new Model_Wallet_Wallet();
				$re = $walletModel->payValidation($memberID);
				if($re && empty($payPwd)){
					throw new Exception('请输入支付密码!',parent::STATUS_FAILURE);
				}
				if(!empty($payPwd)){
					$check = $walletModel->checkPayPasswordAction($memberID,$payPwd);
					if($check['flag']<0){
						throw new Exception("支付密码验证失败", $check['flag']);
					}
				}
				$fundsModel = new DM_Model_Table_Finance_Funds();
				$blance = $fundsModel->getMemberBalance($memberID);
				if($blance < $money){
					throw new Exception("零用钱余额不足，请充值！", parent::STATUS_BALANCE_NOT_ENOUGH);
				}
				$giftModel = new Model_Gift();
				$giftModel->payGift($activityID,$this->memberInfo->MemberID,0,0,$money,$info['MemberID'],4,1,$realName,$mobile);
			}else{
				$model->enroll($activityID,$memberID,$realName,$mobile);
			}
			$info['EnrollNum'] = $info['EnrollNum']+1;
			$this->returnJson(parent::STATUS_OK,'报名成功',array('Rows'=>$info));
		}catch(Exception $e){
			$this->returnJson($e->getCode(),$e->getMessage());
		}
	}
	
	/**
	 * 频道下的理财号列表
	 */
	public function channelColumnListAction()
	{
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('pagesize', 30);
		$channelID = intval($this->_request->getParam('channelID',0));
		if($channelID <= 0){
			$this->returnJson(parent::STATUS_FAILURE,'频道ID不能为空！');
		}
		$channelFocusModel = new Model_ChannelFocus();
		$focusID = $channelFocusModel->getChannelFocusID($channelID);
		$result = array();
		$total = 0;
		if(count($focusID) >0 ){
			$columnModel = new Model_Column_Column();
			$select = $columnModel->select()->setIntegrityCheck(false);
			$select->from('column as t',array('ColumnID'=>new Zend_Db_Expr("DISTINCT(t.ColumnID)"),'Title','Avatar','SubscribeNum','ArticleNum','Description'))->where('t.CheckStatus = ?',1);
			$select->joinLeft('column_focus as tf','t.ColumnID = tf.ColumnID','')->where('tf.FocusID in (?)',$focusID);
	
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(DISTINCT t.ColumnID) AS total FROM', $countSql);
	
			//总条数
			$total = $columnModel->getAdapter()->fetchOne($countSql);
	
			$select->order('t.ColumnID desc')->limitPage($pageIndex, $pageSize);
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				foreach ($result as &$val){
					$val['IsSubscribe'] = $columnModel->isSubscribeColumn($this->memberInfo->MemberID, $val['ColumnID']);
					
				}
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$result));
	}
	
	/**
	 * 搜索理财号或者文章
	 */
	public function searchListAction()
	{
		try{
			$searchType = intval($this->_getParam('searchType',0));
			$keyWords = trim($this->_getParam('keyWords',''));
			$pageSize = intval($this->_getParam('pagesize',30));
			$lastID = intval($this->_getParam('lastID',999999999));
			if(empty($keyWords)){
				throw new Exception('关键字不能为空！');
			}
			if(!$searchType || !in_array($searchType, array(1,2))){
				throw new Exception('请选择正确的搜索类型！');
			}
			$columnModel = new Model_Column_Column();
			if($searchType==1){//搜理财号
				$memberID = $this->memberInfo->MemberID;
				$fields = array('ColumnID','Title','Avatar','SubscribeNum','ArticleNum','Description');
				
				$select = $columnModel->select()->from('column',$fields)->where('CheckStatus = ?',1)
						->where('Title like ?','%'.$keyWords.'%')->where('ColumnID < ?',$lastID);
		
				$results = $select->order('ColumnID desc')->limit($pageSize)->query()->fetchAll();
				if(!empty($results)){
					foreach ($results as &$val){
						$val['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID, $val['ColumnID']);
					}
				}
			}else{//搜文章
				$fields = array('AID','MemberID','ColumnID','Title','Cover','PublishTime');
				$articleModel = new Model_Column_Article();
				$select = $articleModel->select()->from('column_article',$fields)->where('Status = ?',1)->where('Title like ?','%'.$keyWords.'%')
				->where('AID < ?',$lastID);
				$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();
				if(!empty($results)){
					$memberModel = new DM_Model_Account_Members();
					$memberNoteModel = new Model_MemberNotes();
					foreach($results as &$val){
						$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
						$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['MemberID']);
						$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 活动扫描签到
	 */
	public function activitySignAction()
	{
		try{
			$activityID = intval($this->_getParam('activityID',0));
			$memberID = $this->memberInfo->MemberID;
			if($activityID<1){
				throw new Exception('活动不存在！');
			}
			$enrollModel = new Model_Column_ActivityEnroll();
			$info = $enrollModel->getEnrollInfo($activityID, $memberID);
			if(!empty($info)){
				if($info['IsSign']==1){
					$signStatus = 3;//已签到
				}else{
					$re = $enrollModel->update(array('IsSign'=>1), array('ActivityID = ?'=>$activityID,'MemberID =?'=>$memberID));
					if($re){
						$signStatus = 1;//签到成功
					}else{
						$signStatus = 2;//签到失败
					}
				}
			}else{//未参与活动
				$signStatus = 0;
			}
			$this->returnJson(parent::STATUS_OK,'',array('SignStatus'=>$signStatus));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 理财号主页
	 */
	public function columnListAction()
	{
		try{
			$columnID = intval($this->_getParam('columnID',0));
			if($columnID<1){
				throw new Exception('该理财号不存在！');
			}
			$result = array();
			$columnModel = new Model_Column_Column();
			$columnInfo = $columnModel->getColumnInfo($columnID,1);
			if(empty($columnInfo)){
				throw new Exception('该理财号不存在！');
			}
			$isReadNews = intval($this->_getParam('isReadNews',0));
			$limit = 5;
			$articleModel = new Model_Column_Article();
			$activityModel = new Model_Column_Activity();
			$articleInfo = $articleModel->getNewArticle($columnID,$limit);
			$activityinfo = $activityModel->getNewActivity($columnID,$limit);
			if(!empty($articleInfo) || !empty($activityinfo)){
				$result = array_merge($activityinfo,$articleInfo);
				foreach($result as $key=>$value){
					$result[$key]['PublishTime'] = strtotime($value['PublishTime']);
					$time[$key] =strtotime($value['PublishTime']);
				}
				array_multisort($time,SORT_NUMERIC,SORT_ASC,$result);
				if(count($result)>$limit){
					$result = array_slice($result,-$limit);
				}
				foreach ($result as &$value)
				{
					$value['PublishTime'] = date('Y-m-d H:i:s',$value['PublishTime']);
				}
			}

			$authenticateType = 0; //认证类型（1：个人，2：理财师，3：企业，4：机构）
			$authenticateModel =new Model_Authenticate();
			$authenticateInfo = $authenticateModel->getInfoByMemberID($columnInfo['MemberID'],1);
			if(!empty($authenticateInfo)){
				$authenticateType = $authenticateInfo['AuthenticateType'];
			}
			if($isReadNews){
				$redisObj = DM_Module_Redis::getInstance();
				$cacheKey = 'lastTime:Column'.$columnID.':MemberID'.$this->memberInfo->MemberID;
				$redisObj->set($cacheKey,time());
			}
			
			$this->returnJson(parent::STATUS_OK,'', array('AuthenticateType'=>$authenticateType,'Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 我关注的理财号消息(已废弃)
	 */
	/*public function columnNewsAction()
	{
		$lastTime = Model_Member::staticData($this->memberInfo->MemberID,'lastColumnNewsTime');
		if(empty($lastTime)){
			$lastTime = 0;
		}
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($this->memberInfo->MemberID);
		$columnArr = $redisObj->zRevRangeByScore($cacheKey, '+inf','-inf');
		$articleModel = new Model_Column_Article();
		$activityModel = New Model_Column_Activity();
		$info = array();
		$newsTitle = '';
		$publishTime = '';
		if(!empty($columnArr)){
			$info = $articleModel->getMessageArticle($columnArr,$lastTime);
			if(empty($info)){
				$info = $activityModel->getMessageActivity($columnArr,$lastTime);
			}
			foreach($columnArr as $k=>$columnID){
				$redisInfo = Model_Column_Column::staticData($columnID);
				if(empty($redisInfo)){
					continue;
				}
				$arr[$k] = $redisInfo;
				$time[$k] = $redisInfo['publishTime'];
			}
			array_multisort($time,SORT_NUMERIC,SORT_ASC,$arr);
			$result = end($arr);
			if(!empty($result)){
				if($result['type']==1){
					$titleInfo= $articleModel->getArticleInfo($result['AID']);
				}else{
					$titleInfo = $activityModel->getActvityInfo($result['AID']);
				}
				$newsTitle = $titleInfo['Title'];
				$publishTime = date('Y-m-d H:i:s',$result['publishTime']);
			}
		}
		Model_Member::staticData($this->memberInfo->MemberID,'lastColumnNewsTime',time());
		$isShowPoint = empty($info)?0:1;
		$this->returnJson(parent::STATUS_OK,'', array('IsShowPoint'=>$isShowPoint,'ContentTitle'=>$newsTitle,'PublishTime'=>$publishTime));
	}*/
	
	/**
	 * 我关注的理财号对应的是否有新的内容
	 */
	public function columnListNewsAction()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($this->memberInfo->MemberID);
		$columnArr = $redisObj->zRevRangeByScore($cacheKey, '+inf','-inf');
		$columnModel = new Model_Column_Column();
		$articleModel = new Model_Column_Article();
		$activityModel = New Model_Column_Activity();
		$result = array();
		if(!empty($columnArr)){
			foreach($columnArr as $k=>$columnID){
				$redisInfo = Model_Column_Column::staticData($columnID);
				if(empty($redisInfo)){
					continue;
				}
				$cacheKey = 'lastTime:Column'.$columnID.':MemberID'.$this->memberInfo->MemberID;
				$lastTime = $redisObj->GET($cacheKey);
				if(empty($lastTime)){
					$lastTime = 0;
				}
				if($redisInfo['publishTime']>$lastTime){
					$columnInfo = $columnModel->getColumnInfo($columnID,1);
					if($redisInfo['type']==1){
						$titleInfo= $articleModel->getArticleInfo($redisInfo['AID']);
					}else{
						$titleInfo = $activityModel->getActvityInfo($redisInfo['AID']);
					}
					if(!empty($titleInfo)){
						$newsTitle = $titleInfo['Title'];
						$result[$k]['ColumnID'] = $columnID;
						$result[$k]['ColumnName'] = $columnInfo['Title'];
						$result[$k]['Avatar'] = $columnInfo['Avatar'];
						$result[$k]['PublishTime'] = date('Y-m-d H:i:s',$redisInfo['publishTime']);
						$result[$k]['ContentTitle'] = $newsTitle;
					}
				}
			}
			if(!empty($result)){
				$result = array_values($result);
			}
		}
		$this->returnJson(parent::STATUS_OK,'', array('Rows'=>$result)); 
	}
	
	/**
	 * 删除我关注的理财号消息
	 */
	public function deleteColumnNewsAction()
	{
		$columnID = $this->_getParam('columnID','');
		$redisObj = DM_Module_Redis::getInstance();
		if(!empty($columnID)){
			if($columnID == 'all'){
				//查询我关注的理财号
				$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($this->memberInfo->MemberID);
				$columnArr = $redisObj->zRevRangeByScore($cacheKey, '+inf','-inf');
				if(!empty($columnArr)){
					foreach($columnArr as $val){
						$cacheKey = 'lastTime:Column'.$val.':MemberID'.$this->memberInfo->MemberID;
						$redisObj->set($cacheKey,time());
					}
				}
			}else{
				$cacheKey = 'lastTime:Column'.$columnID.':MemberID'.$this->memberInfo->MemberID;
				$redisObj->set($cacheKey,time());
			}
		}
		$this->returnJson(parent::STATUS_OK,'');
	}
	
	/**
	 * 搜索我关注的理财号
	 */
	public function searchSubscribeColumnsAction()
	{
		$keyWords = trim($this->_getParam('keyWords'),'');
		$memberID = $this->memberInfo->MemberID;
		$pageIndex= $this->_getParam('page', 1);
		$pagesize = intval($this->_getParam('pagesize',10));
		if(empty($keyWords)){
			$this->returnJson(parent::STATUS_FAILURE,'关键字不能为空');
		}
		$fields = array('ColumnID','Title','Avatar','SubscribeNum','ArticleNum','Description');
		$subcribeModel = new Model_Column_MemberSubscribe();
		$select = $subcribeModel->select()->setIntegrityCheck(false);
		$select->from('column_member_subscribe as cs',null);
		$select->joinInner('column as c', 'c.ColumnID = cs.ColumnID',$fields);
		$select->where('cs.MemberID = ? ',$memberID);
		$select->where("c.Title like ?",'%'.$keyWords.'%');
		$countSql = $select->__toString();

		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		//总条数
		$total = $subcribeModel->getAdapter()->fetchOne($countSql);
		$results = $select->order('cs.SID desc')->limitPage($pageIndex,$pagesize)->query()->fetchAll();
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
	}
	
	/**
	 * 读取理财号消息是否成功
	 */
	public function readColumnNewsAction()
	{
		Model_Member::staticData($this->memberInfo->MemberID,'lastColumnNewsTime',time());
		$this->returnJson(parent::STATUS_OK,'');
	}
	
	/**
	 * 获取所有活动列表
	 */
	public function getAllActivityAction()
	{
		try{
            $cur_member_id = isset($this->memberInfo->MemberID)?$this->memberInfo->MemberID:0;
			$lastID = intval($this->_getParam('lastID',0));
			$pagesize = intval($this->_getParam('pagesize',30));
			$fields = array('AID','MemberID','ColumnID','Title','Cover','StartTime','EndTime','LimitTime','Province','City','DetailAdress','EnrollNum','CreateTime');
			$activityModel = new Model_Column_Activity();
			$select = $activityModel->select()->from('column_activity',$fields)->where('Status = ?',1);
			if($lastID>0){
				$select->where('AID < ?',$lastID);
			}
			$results = $select->order('AID desc')->limit($pagesize)->query()->fetchAll();
			if(!empty($results)){
				$memberModel = new DM_Model_Account_Members();
				foreach ($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				}
			}
			
			if(!empty($results)){
				$columnModel = new Model_Column_Column();
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($cur_member_id, $val['MemberID']);
					$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}

	/**
	 * 搜索活动
	 */
	public function searchActivityAction()
	{
		$pagesize = intval($this->_getParam('pagesize',10));
		$page = intval($this->_getParam('page',1));
		$keyWords = trim($this->_request->getParam('keyWords', ''));
		if ($keyWords !== '0' && empty($keyWords)) {
			$this->returnJson(parent::STATUS_FAILURE, '关键字不能为空！');
		}
		
		$activityModel = new Model_Column_Activity();
		$select = $activityModel->select()->from('column_activity',array('AID','MemberID','ColumnID','Title','CreateTime','EndTime','Cover'))->where('UNIX_TIMESTAMP(EndTime) > ?',time())
		->where('Status = ?',1)->where('Title like ?','%'.$keyWords.'%');
		$result1 = $select->order('EndTime ASC')->query()->fetchAll();
		
		$select = $activityModel->select()->from('column_activity',array('AID','MemberID','ColumnID','Title','CreateTime','EndTime','Cover'))
		->where('UNIX_TIMESTAMP(EndTime) <= ?',time())->where('Status = ?',1)->where('Title like ?','%'.$keyWords.'%');
		$result2 = $select->order('EndTime Desc')->query()->fetchAll();
		$result = array_merge($result1,$result2);
		$totalNum = count($result);
		$tempArr=array_chunk($result,$pagesize);
		$rows = isset($tempArr[$page-1])?$tempArr[$page-1]:array();
		if(!empty($rows)){
			$columnModel = new Model_Column_Column();
			foreach($rows as &$val){
				$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
			}
			$keywordsModel = new Model_Topic_SearchKeyWords();
			$keywordsModel->recordKeyWords('activity', $keyWords);
		}
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$totalNum,'Rows'=>$rows));
	}
}