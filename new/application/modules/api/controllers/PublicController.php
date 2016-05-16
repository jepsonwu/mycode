<?php

use Qiniu\json_decode;
/**
 *  公共页面
 * @author Mark
 *
 */
class Api_PublicController extends Action_Api
{
	public function init()
	{
		parent::init();
		header('Content-type: text/html');
	}

	/**
	 * 观点详情页
	 *
	 * @throws Exception
	 */
	public function viewDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$viewID = $this->_request->getParam('viewID', 0);
			if (empty($viewID)) {
				throw new Exception('观点ID有误！');
			}
			$fields = array('ViewID', 'MemberID', 'ViewContent', 'PraiseNum', 'ReplyNum', 'ShareNum', 'CreateTime','IsAnonymous','AnonymousUserName','AnonymousAvatar');

			$viewModel = new Model_Topic_View();
			$memberModel = new DM_Model_Account_Members();
			$viewImageModel = new Model_Topic_ViewImage();

// 			$select = $viewModel->select()->from('topic_views', $fields)->where('ViewID = ?', $viewID);
// 			$viewInfo = $select->query()->fetch();
			$select = $viewModel->select()->setIntegrityCheck(false)->from('topic_views',$fields);
			$select->joinLeft('topics', 'topic_views.TopicID = topics.TopicID',array('TopicName','BackImage'))->where('ViewID = ?',$viewID)->where('topic_views.CheckStatus = ?',1);
			$viewInfo = $select->query()->fetch();
			if (empty($viewInfo)) {
				throw new Exception('未查询到观点详情！');
			}

			$giftMember = array();
			$praiseMember = array();
			$Images = $viewImageModel->getImages($viewInfo['ViewID']);
			$viewInfo['TimeStamp'] = time();
			$viewInfo['CreateTime'] = Model_Topic_View::changeDateStyle($viewInfo['CreateTime']);

			$replyModel = new Model_Topic_Reply();
			$replies = $replyModel->getList($viewID, 0, 5);
			if(!empty($replies)){
				foreach($replies as &$reply){
					$reply['CreateTime'] = Model_Topic_View::changeDateStyle($reply['CreateTime']);
				}
			}

			if($viewInfo['IsAnonymous'] == 1){
				$userName = $viewInfo['AnonymousUserName'];
				$avatar = $viewInfo['AnonymousAvatar'];
				unset($viewInfo['MemberID']);
			}else{
				$userName = $memberModel->getMemberInfoCache($viewInfo['MemberID'], 'UserName');
				$avatar = $memberModel->getMemberAvatar($viewInfo['MemberID']);
			
				$giftModel = new Model_Topic_ViewGift();
				$giftArr = $giftModel->getExpensiveGift($viewID,5);
				$giftMember = array();
				if(!empty($giftArr)){
					foreach($giftArr as $k=>$val){
						$giftMember[$k]['Avatar'] = $memberModel->getMemberAvatar($val['GiftMemberID']);
						$giftMember[$k]['MemberID'] = $val['GiftMemberID'];
					}
				}

				$praiseModel = new Model_Topic_Praise();
				$info = $praiseModel->getPraiseMember($viewID);
				$praiseMember = array();
				if(!empty($info)){
					foreach($info as $k=>$row){
						$praiseMember[$k]['Avatar'] = $memberModel->getMemberAvatar($row['MemberID']);
						$praiseMember[$k]['MemberID'] = $row['MemberID'];
					}
				}
			}
			//unset($viewInfo['IsAnonymous']);
			unset($viewInfo['AnonymousUserName']);
			unset($viewInfo['AnonymousAvatar']);
			$viewInfo['Avatar'] = $avatar;
			$viewInfo['UserName'] = $userName;
			//var_dump($viewInfo);exit;
			//$Images = empty($Images)?array():$Images; 
			$this->view->viewInfo = $viewInfo;
			$this->view->replies = json_encode($replies);
			$this->view->giftMember = json_encode($giftMember);
			$this->view->praiseMember = json_encode($praiseMember);
			$this->view->viewImages = json_encode($Images);

		} catch (Exception $e) {
			exit($e->getMessage());
		}
		echo $this->view->render('public/view-detail.phtml');
	}

	/**
	 * 会员主页
	 */
	public function memberPageAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$mid = intval($this->_request->getParam('mid', 0));
			if (empty($mid)) {
				throw new Exception('参数错误！');
			}

			$memberModel = new DM_Model_Account_Members();
			$memberFollowModel = new Model_MemberFollow();
			$memberNoteModel = new Model_MemberNotes();
			$minfo = $memberModel->getById($mid);
			if (empty($minfo)) {
				throw new Exception('该会员不存在！');
			}
			
			// $memberID = $this->memberInfo->MemberID;
			// $relationCode = $memberFollowModel->getRelation($minfo['MemberID'], $memberID);
			// $noteName = '';
			// if($relationCode ==3 || $relationCode ==1){
			// 	$noteName = $memberNoteModel->getNoteName($memberID, $minfo['MemberID']);
			// }

			//关注点
			$memberFocusModel = new Model_MemberFocus();
			$focusInfo = $memberFocusModel->getFocusInfo($mid, null, 'FocusID');

			//加入的群组
			$groupMemberModel = new Model_IM_GroupMember();
			$joinedGroups = $groupMemberModel->getJoinedGroups($mid);

			//关注话题的数量
			$topicModel = new Model_Topic_Topic();
			$followCount = $topicModel->getFollowedTopicsCount($mid);

			//说说的数量
			$shuoshuoModel = new Model_Shuoshuo();
			$shuoshuoCount = $shuoshuoModel->getShuosCount($mid);

			//观点的数量
			$viewModel = new Model_Topic_View();
			$viewCount = $viewModel->getViewCount($mid);

            //获取正在讨论的话题名称
            $discussingTopicName = $topicModel->getDiscussingTopicName($mid);

            //帐号主题和资质
            $authenticateModel =new Model_Authenticate();
            $qualificationModel = new Model_Qualification();
            $subject='';
            $qualification='';
            //$authenticateType = 1;
            //$authenticateStatus=-1;
            //$qualificationStatus =-1;
            $authenticateInfo = $authenticateModel->getInfoByMemberID($mid);
            if(!empty($authenticateInfo) && $authenticateInfo['Status']==1){
                $authenticateType = $authenticateInfo['AuthenticateType'];
                //$authenticateStatus = $authenticateInfo['Status'];
                if($authenticateType == 1){
                    $subject = $authenticateInfo['OperatorName'];
                }elseif($authenticateType == 2){
                    $subject = $authenticateInfo['OperatorName'];
                    $qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1);
                    if(!empty($qualificationInfo)){
                        $qualification=$qualificationInfo['FinancialQualificationType'];
                        //$qualificationStatus= $qualificationInfo['CheckStatus'];
                    }
                }elseif($authenticateType==3){
                    $subject = $authenticateInfo['BusinessName'];
                }elseif($authenticateType==4){
                    $subject = $authenticateInfo['OrganizationName'];
                }
            }




			$this->view->focusInfo = $focusInfo;
			$this->view->minfo = $minfo;
			$this->view->joinedGroup = $joinedGroups;
			$this->view->followCount = $followCount;
			$this->view->shuoshuoCount = $shuoshuoCount;
			$this->view->viewCount = $viewCount;
			$this->view->discussingTopicName = $discussingTopicName;
			$this->view->subject = $subject;
			$this->view->qualification = $qualification;
			//$this->view->noteName = $noteName;


		} catch (Exception $e) {
			exit($e->getMessage());
		}

		$this->view->mid = $mid;
		echo $this->view->render('public/home.phtml');
	}


	/**
	 * 群信息页面
	 */
	public function groupInfoAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$groupID = $this->_request->getParam('groupID', '');
			if (empty($groupID)) {
				throw new Exception('参数错误！');
			}

			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if (empty($groupInfo)) {
				throw new Exception('群组不存在！');
			}

		} catch (Exception $e) {
			exit($e->getMessage());
		}

		$groupMemberModel = new Model_IM_GroupMember();
		$gMembers = $groupMemberModel->getGroupMembers($groupID, 9);

		$this->view->groupID = $groupID;
		$this->view->groupInfo = $groupInfo;
		$this->view->gMembers = $gMembers;

		echo $this->view->render('public/group.phtml');
	}

	/**
	 * 下载页面
	 */
	public function downLoadAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('public/caizhu-app-down.phtml');
	}

	/**
	 * 财猪账号主体认证协议
	 */
	public function topicPublishAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('public/topic-publish-protocol.phtml');
	}

	/*
	* 财猪理财号介绍 -单页 - 160407
	*/
	public function columnRecommendationAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('public/column-recommendation.phtml');
	}

	/**
	 * 财猪App - 达人邀请
	 */
	public function topmanAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		$bestModel = new Model_Best_Best();
		$bid = $this->_request->getParam("bID");
		if ($bid) {
			$best_info = $bestModel->getByID($bid, "Status");
			$best_info && $this->view->best_status = current($best_info);
		} else {
			$this->view->best_status = 1;
		}
		echo $this->view->render('public/topman.phtml');
	}

	/**
	 * 财猪App - 取消达人邀请
	 */
	public function cancelTopmanAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		$bestModel = new Model_Best_Best();
		$bid = $this->_request->getParam("bID");
		if ($bid) {
			$best_info = $bestModel->getByID(current(explode(",", trim($bid, ","))), "Status");
			$best_info && $this->view->best_status = current($best_info);
		} else {
			$this->view->best_status = 3;
		}
		echo $this->view->render('public/cancel-topman.phtml');
	}

	/**
	* 理财号-达人详情
	*/
	public function topmanDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('public/topman-detail.phtml');
	}

	/**
	* 理财号-理财师详情
	*/
	public function cfpDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('public/cfp-detail.phtml');
	}

	/**
	 * 测试cookie
	 */
	public function testSelfAction()
	{
		var_dump($_COOKIE);
		//if($this->isLogin()){
		var_dump($this->memberInfo);
// 		}else{
// 			echo 'No loginInfo';
// 		}
		exit;
	}

	// public function viewListAction()
	// {
	// 	echo 'Comming soon !';
	// 	exit;
	// }

	/**
	 * 专栏文章详情页面
	 */
	public function articleDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$memberID = 0;
			$isPreview = intval($this->_getParam('isPreview', 0));
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			$articleID = $this->_request->getParam('articleID', 0);
			if (empty($articleID)) {
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			if ($isPreview && !$memberID) {
				$redirectUrl = $this->getFullHost() . '/api/public/scan';
				$this->_redirect($redirectUrl);
				//throw new Exception('请使用财猪里的扫一扫进行预览！');
			}
			$fields = array('AID', 'MemberID', 'ColumnID', 'Title', 'Cover', 'Author', 'Content', 'PraiseNum', 'PublishTime', 'ArticleLink', 'CreateTime','IsCharge','Cost','ReadNum');

			$articleModel = new Model_Column_Article();
			$select = $articleModel->select()->from('column_article', $fields)->where('AID = ?', $articleID)->where('Status > ?', 0);
			$detailInfo = $select->query()->fetch();
			if (empty($detailInfo)) {
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			//增加阅读数量
			$columnModel = new Model_Column_Column();
			if ($memberID != $detailInfo['MemberID'] && !$isPreview) {
				$articleModel->increaseReadNum($articleID);
				$columnModel->increaseReadNum($detailInfo['ColumnID']);
			}
			$memberModel = new DM_Model_Account_Members();
			$detailInfo['IsPraise'] = $memberID > 0 ? $articleModel->isPraised($articleID, $memberID) : 0;
			$columnInfo = $columnModel->getColumnInfoCache($detailInfo['ColumnID']);
			$detailInfo['ColumnAvatar'] = $columnInfo['Avatar'];
			$detailInfo['ColumnTitle'] = $columnInfo['Title'];
			$detailInfo['ColumnDescription'] = $columnInfo['Description'];
			$detailInfo['ReadNum'] = $detailInfo['ReadNum'] + 1;
			$noteName = '';
			if ($memberID > 0) {
				$memberNoteModel = new Model_MemberNotes();
				$noteName = $memberNoteModel->getNoteName($memberID, $detailInfo['MemberID']);
			}
			//判断是否要显示查看全部按钮
			$isViewAll = 0;
			if($detailInfo['IsCharge'] && !$isPreview){
				if($memberID && $memberID!=$detailInfo['MemberID']){
					$giftModel = new Model_Column_ArticleGift();
					$info = $giftModel->isPay($articleID,$memberID);
					if(empty($info)){
						$isViewAll = 1;
						$detailInfo['Content'] = mb_substr(DM_Helper_Utility::DeleteHtml($detailInfo['Content']),0,200,'utf-8');
					}
				}
				if(!$memberID){
					$isViewAll = 1;
					$detailInfo['Content'] = mb_substr(DM_Helper_Utility::DeleteHtml($detailInfo['Content']),0,200,'utf-8');
				}
			}
			
			//
			if ($detailInfo['PublishTime'] == '0000-00-00 00:00:00') {
				$detailInfo['PublishTime'] = $detailInfo['CreateTime'];
			}
			$detailInfo['PublishTime'] = Model_Topic_View::changeDateStyle($detailInfo['PublishTime']);
			$detailInfo['UserName'] = empty($noteName) ? $memberModel->getMemberInfoCache($detailInfo['MemberID'], 'UserName') : $noteName;
			$detailInfo['IsSubscribe'] = $memberID > 0 ? $columnModel->isSubscribeColumn($this->memberInfo->MemberID, $detailInfo['ColumnID']) : 0;
			
			$giftModel = new Model_Column_ArticleGift();
			$giftArr = $giftModel->getExpensiveGift($articleID,5);
			$avatarArr = array();
			if(!empty($giftArr)){
				foreach($giftArr as $k=>$val){
					$avatarArr[$k]['Avatar'] = $memberModel->getMemberAvatar($val['GiftMemberID']);
					$avatarArr[$k]['MemberID'] = $val['GiftMemberID'];
				}
			}
			$this->view->detailInfo = $detailInfo;
			$this->view->avatarArr = json_encode($avatarArr);
			//获取该文章的相关联文章(分享到外部的样式)
			$relationArticles = array();
			if(!$memberID && !$isPreview){
				$focusModel = new Model_Column_ArticleFocus();
				$focusArr = $focusModel->getInfo($articleID);
				$focusIDArr = array();
				foreach($focusArr as $val){
					$focusIDArr = $val['FocusID'];
				}
				$relationArticles = $focusModel->getRelationArticles($focusIDArr);
				
			}
			$this->view->relationArticles = json_encode($relationArticles);
			//获取打赏人数量
			$sendGiftNum = $giftModel->getSendGiftNum($articleID);
			$this->view->sendGiftNum = $sendGiftNum;
			$this->view->isViewAll = $isViewAll;
		} catch (Exception $e) {
			exit($e->getMessage());
		}
		echo $this->view->render('public/article-detail.phtml');
	}

	/**
	 * 专栏活动详情页面
	 */
	public function activityDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$memberID = 0;
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			$activityID = $this->_request->getParam('activityID', 0);
			if(empty($activityID)){
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			$isPreview = intval($this->_getParam('isPreview', 0));
			if ($isPreview && !$memberID) {
				$redirectUrl = $this->getFullHost() . '/api/public/scan';
				$this->_redirect($redirectUrl);
			}
			$fields = array('AID', 'MemberID', 'ColumnID', 'Title', 'Cover', 'StartTime', 'EndTime', 'LimitTime', 'Province', 'City', 'DetailAdress', 'EnrollNum', 'LimitNum', 'Content', 'CreateTime','IsCharge','Cost','Status');

			$activityModel = new Model_Column_Activity();
			$select = $activityModel->select()->from('column_activity', $fields)->where('AID = ?', $activityID);
			$detailInfo = $select->query()->fetch();
			if ($detailInfo['Status']==0) {
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			$columnModel = new Model_Column_Column();
			//阅读数加1
			if ($memberID != $detailInfo['MemberID'] && !$isPreview) {
				$activityModel->increaseReadNum($activityID);
				
				$columnModel->increaseReadNum($detailInfo['ColumnID']);
			}
			$memberModel = new DM_Model_Account_Members();
			$noteName = '';
			if ($memberID > 0) {
				$memberNoteModel = new Model_MemberNotes();
				$noteName = $memberNoteModel->getNoteName($memberID, $detailInfo['MemberID']);
			}
			$detailInfo['UserName'] = empty($noteName) ? $memberModel->getMemberInfoCache($detailInfo['MemberID'], 'UserName') : $noteName;
			$avatar = $memberModel->getMemberAvatar($detailInfo['MemberID']);
			$detailInfo['Avatar'] = empty($avatar) ? 'http://img.caizhu.com/default.png' : $avatar;
			$detailInfo['EnrollStatus'] = 1;
			$model = new Model_Column_ActivityEnroll();
			$info = $model->getEnrollInfo($activityID, $memberID);
			if (!empty($info)) {
				$detailInfo['EnrollStatus'] = 0;
			}
			if (strtotime($detailInfo['LimitTime']) < time()) {
				$detailInfo['EnrollStatus'] = 2;//报名截至
			} elseif ($detailInfo['EnrollNum'] >= $detailInfo['LimitNum']) {
				$detailInfo['EnrollStatus'] = 3;//报名人数已满
			}
			$detailInfo['week'] = $this->transition($detailInfo['StartTime']);
			$detailInfo['StartTime'] = date('Y-m-d H:i', strtotime($detailInfo['StartTime']));
			$detailInfo['EndTime'] = date('m-d H:i', strtotime($detailInfo['EndTime']));
			$detailInfo['LimitTime'] = date('Y-m-d H:i', strtotime($detailInfo['LimitTime']));
			$columnInfo = $columnModel->getColumnInfoCache($detailInfo['ColumnID']);
			$detailInfo['ColumnTitle'] = $columnInfo['Title'];
			$detailInfo['ColumnAvatar'] = $columnInfo['Avatar'];
			$detailInfo['Description'] = $columnInfo['Description'];
			$detailInfo['CreateTime'] = Model_Topic_View::changeDateStyle($detailInfo['CreateTime']);
			
			$detailInfo['IsSubscribe'] = 0;
			if($memberID > 0){
				$detailInfo['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID,$detailInfo['ColumnID']);
			}
			
			$this->view->detailInfo = $detailInfo;

			$columnFocusModel = new Model_Column_ColumnFocus();
			$columnFocusInfo = $columnFocusModel->getInfo($detailInfo["ColumnID"],null);
			$columnFocusInfo = json_encode($columnFocusInfo);
			$this->view->cloumnFocusInfo = $columnFocusInfo;

		} catch (Exception $e) {
			exit($e->getMessage());
		}
		echo $this->view->render('public/activity-detail.phtml');
	}

	/**
	 * 讲堂列表
	 */
	public function lectureListAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {

			$fields = array('videoID', 'LectureID', 'ImageUrl', 'ImagTitle');
			$videoModel = new Model_Lecture_Video();
			$select = $videoModel->select()->from('lecture_video', $fields)->where('Status = ?', 1);
			$videoList = $select->order('videoID desc')->query()->fetchAll();
			if (!empty($videoList)) {
				if ($this->isLogin()) {
					$lastID = $videoList[0]['videoID'];
					Model_Member::staticData($this->memberInfo->MemberID, 'lastVideoID', $lastID);
				}
			}
			$this->view->dataList = $videoList;

		} catch (Exception $e) {
			exit($e->getMessage());
		}
		echo $this->view->render('public/lecture-list.phtml');
	}

	/**
	 * 讲堂详细
	 */
	public function lectureDetailAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$videoID = intval($this->_getParam('videoID', 0));
			if(!$videoID){
				$videoID = intval($this->_getParam('lectureID', 0));
			}
			if ($videoID < 1) {
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			$fields = array('videoID', 'ImageUrl', 'VideoUrl', 'ImagTitle', 'PraiseNum', 'CommentNum', 'PlayNum', 'CreateTime');
			$videoModel = new Model_Lecture_Video();
			$select = $videoModel->select()->from('lecture_video', $fields)->where('Status = ?', 1)->where('VideoID = ?', $videoID);
			$videoDetail = $select->query()->fetch();
			$videoDetail['CreateTime'] = Model_Topic_View::changeDateStyle($videoDetail['CreateTime']);
			//增加视频播放数量
			$videoModel->increasePlayNum($videoID);
			$memberID = 0;
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			$videoDetail['IsPraise'] = $memberID > 0 ? $videoModel->isPraised($videoID, $memberID) : 0;

			$this->view->videoDetail = $videoDetail;
		} catch (Exception $e) {
			exit($e->getMessage());
		}
		echo $this->view->render('public/lecture-detail.phtml');
	}

	/**
	 * 专栏主页
	 */
	public function columnListAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		try {
			$columnID = intval($this->_getParam('columnID', 0));
			if ($columnID < 1) {
				$redirectUrl = $this->getFullHost() . '/api/public/empty';
				$this->_redirect($redirectUrl);
			}
			$memberID = 0;
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			$model = new Model_Column_Column();
			$columnInfo = $model->getColumnInfo($columnID);
			$columnInfo['IsSubscribe'] = $model->isSubscribeColumn($memberID, $columnID);
			$this->view->columnInfo = $columnInfo;
		} catch (Exception $e) {
			exit($e->getMessage());
		}
		$this->view->columnID = $columnID;
		echo $this->view->render('public/column-list.phtml');
	}

	/**
	 * 获取专栏主页H5页面文章列表
	 */
	public function getArticleListAction()
	{
		try {
			$lastID = $this->_getParam('lastID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$columnID = intval($this->_getParam('columnID', 0));
			if ($columnID < 1) {
				$this->returnJson(parent::STATUS_FAILURE, '参数错误！');
			}
			$memberID = 0;
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			$fields = array('AID', 'MemberID', 'Title', 'Cover', 'PublishTime');
			$articleModel = new Model_Column_Article();
			$select = $articleModel->select()->from('column_article', $fields)->where('ColumnID = ?', $columnID)->where('Status = ?', 1);

			if ($lastID > 0) {
				$select->where('AID < ?', $lastID);
			}
			$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();

			if (!empty($results)) {
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach ($results as &$val) {
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'], 'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['PublishTime'] = Model_Topic_View::changeDateStyle($val['PublishTime']);
				}
			}
			$this->returnJson(parent::STATUS_OK, '', array('Rows' => $results));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * 获取专栏主页H5页面活动列表
	 */
	public function getActivityListAction()
	{
		try {
			$lastID = $this->_getParam('lastID', 0);
			$pageSize = $this->_getParam('pagesize', 30);
			$columnID = intval($this->_getParam('columnID', 0));
			if ($columnID < 1) {
				$this->returnJson(parent::STATUS_FAILURE, '参数错误！');
			}
			//$memberID = $this->memberInfo->MemberID;
			$fields = array('AID', 'MemberID', 'Title', 'Cover', 'StartTime', 'EndTime', 'Province', 'City', 'EnrollNum', 'CreateTime');
			$activityModel = new Model_Column_Activity();
			$select = $activityModel->select()->from('column_activity', $fields)->where('ColumnID = ?', $columnID)->where('Status = ?', 1);

			if ($lastID > 0) {
				$select->where('AID < ?', $lastID);
			}
			$results = $select->order('AID desc')->limit($pageSize)->query()->fetchAll();
			$memberID = 0;
			if ($this->isLogin()) {
				$memberID = $this->memberInfo->MemberID;
			}
			if (!empty($results)) {
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach ($results as &$val) {
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'], 'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['CreateTime'] = Model_Topic_View::changeDateStyle($val['CreateTime']);
					$val['StartTime'] = date('m-d H:i', strtotime($val['StartTime']));
					$val['EndTime'] = date('m-d H:i', strtotime($val['EndTime']));
				}
			}
			$this->returnJson(parent::STATUS_OK, '', array('Rows' => $results));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * h5页面视频评论列表
	 */
	public function commentListAction()
	{
		try {
			$lastID = intval($this->_getParam('lastID', 0));
			$videoID = intval($this->_getParam('videoID', 0));
			$pagesize = intval($this->_getParam('pagesize', 20));
			$commentModel = new Model_Lecture_Comment();
			$result = $commentModel->getComments($lastID, $videoID, $pagesize);
			if (!empty($result)) {
				$memberModel = new DM_Model_Account_Members();
				foreach ($result as &$val) {
					$val['CreateTime'] = Model_Topic_View::changeDateStyle($val['CreateTime']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'], 'UserName');
					$avater = $memberModel->getMemberAvatar($val['MemberID']);
					$val['Avater'] = empty($avater) ? 'http://img.caizhu.com/default.png' : $avater;
				}
			}
			$this->returnJson(parent::STATUS_OK, '', array('Rows' => $result));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * 错误页面
	 */
	public function emptyAction()
	{
		echo $this->view->render('public/empty.phtml');
	}

	/**
	 * 扫描二维码签到
	 */
	public function activitySignAction()
	{
		$activityID = intval($this->_getParam('activityID', 0));
		if ($activityID < 1) {
			$redirectUrl = $this->getFullHost() . '/api/public/empty';
			$this->_redirect($redirectUrl);
		}
		$memberID = 0;
		if ($this->isLogin()) {
			$memberID = $this->memberInfo->MemberID;
		}
		if (!$memberID) {
			$redirectUrl = $this->getFullHost() . '/api/public/scan';
			$this->_redirect($redirectUrl);
		}
		$enrollModel = new Model_Column_ActivityEnroll();
		$info = $enrollModel->getEnrollInfo($activityID, $memberID);
		if (!empty($info)) {
			if ($info['IsSign'] == 1) {
				$signStatus = 2;//已签到
				$msg = '您已签到';
			} else {
				$re = $enrollModel->update(array('IsSign' => 1), array('ActivityID = ?' => $activityID, 'MemberID =?' => $memberID));
				if ($re) {
					$signStatus = 3;//签到成功
					$msg = '签到成功';
				} else {
					$signStatus = 4;//签到失败
					$msg = '签到失败';
				}
			}
		} else {//未参与活动
			$signStatus = 1;
			$msg = '您未参与该活动';
		}
		$this->view->signStatus = $signStatus;
		$this->view->msg = $msg;
		echo $this->view->render('public/activity-sign.phtml');
	}

	/**
	 * 预览错误页面
	 */
	public function scanAction()
	{
		echo $this->view->render('public/scan.phtml');
	}

	function transition($date)
	{
		$datearr = explode("-", $date);     //将传来的时间使用“-”分割成数组
		$year = $datearr[0];       //获取年份
		$month = sprintf('%02d', $datearr[1]);  //获取月份
		$day = sprintf('%02d', $datearr[2]);      //获取日期
		$hour = $minute = $second = 0;   //默认时分秒均为0
		$dayofweek = mktime($hour, $minute, $second, $month, $day, $year);    //将时间转换成时间戳
		$shuchu = date("w", $dayofweek);      //获取星期值
		$weekarray = array("周日", "周一", "周二", "周三", "周四", "周五", "周六");
		return $weekarray[$shuchu];
	}
	
	/**
	 * 理财号详情页面
	 */
	public function columnDetailAction()
	{
		$columnID = intval($this->_getParam('columnID',0));
		if($columnID<1){
			$redirectUrl = $this->getFullHost().'/api/public/empty';
			$this->_redirect($redirectUrl);
		}
		$memberID = 0;
		if($this->isLogin()){
			$memberID = $this->memberInfo->MemberID;
		}
		$model = new Model_Column_Column();
		$info = $model->getColumnInfo($columnID);
		if(empty($info)){
			$redirectUrl = $this->getFullHost().'/api/public/empty';
			$this->_redirect($redirectUrl);
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
				$subject = $authenticateInfo['OperatorName'];
			}elseif($authenticateType == 2){
				$subject = $authenticateInfo['OperatorName'];
				$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1,'FinancialQualificationID desc',array('FinancialQualificationType'));
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
		$info['IsSubscribe'] = $model->isSubscribeColumn($memberID, $columnID);
		$this->view->columnInfo = $info;
		echo $this->view->render('public/column-detail.phtml');
	}

	public function viewListAction()
	{
		try {

			$topicID = $this->_request->getParam('topicID',0);

			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}

			$topicModel = new Model_Topic_Topic();
			$topicInfo = $topicModel->getTopicInfo($topicID);

			if ($topicID < 1 || empty($topicInfo)) {
				$this->returnJson(parent::STATUS_FAILURE, '参数错误！');
			}
			//var_dump($topicInfo);exit;
			
			$topicInfo['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $topicID);
			$memberModel = new DM_Model_Account_Members();
			$topicInfo['UserName'] = $memberModel->getMemberInfoCache($topicInfo['MemberID'],'UserName');
			$topicInfo['Avatar'] = $memberModel->getMemberAvatar($topicInfo['MemberID']);

			// $viewModel = new Model_Topic_View();
			// $fields = array('ViewID','MemberID','CreateTime','ViewContent','PraiseNum','ReplyNum');						
			// $select = $viewModel->select()->from('topic_views',$fields)->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1);					
			// if($lastViewID > 0){
			// 	$select->where('ViewID < ?',$lastViewID);
			// }			
			// $select->order('ViewID desc')->limit($pageSize);						
			// $results = $select->query()->fetchAll();

			// //获取用户名和头像及观点图片
			// if(!empty($results)){				
			// 	$memberModel = new DM_Model_Account_Members();
			// 	$viewImageModel = new Model_Topic_ViewImage();
			// 	$memberNoteModel = new Model_MemberNotes();
			// 	foreach($results as &$v){
			// 		$v['Avatar'] = $memberModel->getMemberAvatar($v['MemberID']);
			// 		$v['UserName'] = $memberModel->getMemberInfoCache($v['MemberID'],'UserName');
			// 		$v['NoteName'] = $memberID>0?$memberNoteModel->getNoteName($memberID, $v['MemberID']):'';
			// 		$v['IsPraised'] = $memberID>0?$viewModel->isPraised($v['ViewID'], $memberID):0;
			// 		$v['Images'] = $viewImageModel->getImages($v['ViewID']);
			// 	}
			// }
			if($memberID){
				$topicModel->addRecentUse($topicID, $memberID);
			}
			$this->view->topicInfo = $topicInfo;
			//$this->view->viewList = $results;
			//$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results,'TimeStamp'=>time()));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
		echo $this->view->render('public/view-list.phtml');
	}
	
	/**
	 * 获取某个文章的全部内容
	 */
	public function getAllContentAction()
	{
		$articleID = intval($this->_getParam('articleID',0));
		if($articleID<1){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
		$giftModel = new Model_Column_ArticleGift();
// 		$info = $giftModel->isPay($articleID,$this->memberInfo->MemberID);
// 		if(empty($info)){
// 			$this->returnJson(-101,'未付费！');
// 		}
		$articleModel = new Model_Column_Article();
		$result = $articleModel->select()->from('column_article',array('Content'))->where('AID = ?',$articleID)->where('Status = ?',1)->query()->fetch();
		if(empty($result)){
			$this->returnJson(parent::STATUS_FAILURE,'该文章已不存在！');
		}
		$this->returnJson(parent::STATUS_OK,'',array('allContents'=>$result['Content']));
	}
	
	/**
	 * 创建理财号指引页面
	 */
	public function createColumnAction(){
		echo $this->view->render('public/empty.phtml');
	}


	/**
	 * 问财首页
	*/
	public function counselAction()
	{
		echo $this->view->render('public/counsel.phtml');
	}


	/**
	 * 问财主题
	*/
	public function counselThemeAction()
	{
		echo $this->view->render('public/counsel-theme.phtml');
	}

	/**
	 * 课程分享页
	 */
	public function lessonAction()
	{
		echo $this->view->render('public/empty.phtml');
	}
	
	/**
	 * 课时分享页
	 */
	public function lessonClassAction()
	{
		echo $this->view->render('public/empty.phtml');
	}
	
	/**
	 * 课时列表分享页
	 */
	public function lessonClassListAction()
	{
		echo $this->view->render('public/empty.phtml');
	}
	
	/**
	 * 名人堂分享页
	 */
	public function famousAction()
	{
		echo $this->view->render('public/empty.phtml');
	}
	
	/**
	 * 财猪课堂视频播放
	 */
	public function lessonClassVideoAction()
	{
		$classID = intval($this->_request->getParam('classID',0));
		$lessonClassModel = new Model_LessonClass();
		$info = $lessonClassModel->getClassInfo($classID);
		if(empty($info) || $info['LessonType'] != 2){
			//exit('不存在该课时');
		}else{
			$this->view->videoUrl = $info['ClassLink'];
		}
		echo $this->view->render('public/lesson-class-video.phtml');
	}
}