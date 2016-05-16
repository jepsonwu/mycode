<?php

/**
 *
* @author Mark
*
*/
class Api_SystemController extends Action_Api
{
	/**
	 * 获取上传token
	 */
	public function getUploadTokenAction()
	{
		$this->isLoginOutput();
		$qiniu = new Model_Qiniu();
		$tokenInfo = $qiniu->getUploadToken();

		$this->returnJson(parent::STATUS_OK, '', array('UploadToken' => $tokenInfo['token'], 'ValidSeconds' => $tokenInfo['createTime'] + 1500 - time(), 'ImgDomain' => "http://img.caizhu.com/"));
	}


	/**
	 * 猜你喜欢
	 */
	public function getMayLikeAction()
	{
		$this->isLoginOutput();
		try {
			$memberID = $this->memberInfo->MemberID;
			$getType = $this->_request->getParam('getType', 'friend');
			if (empty($getType)) {
				throw new Exception('请选择类型');
			}

			if (!in_array($getType, array('friend', 'group', 'topic'))) {
				throw new Exception('类型错误！');
			}

			if ($getType == 'friend') {
				$memberModel = new DM_Model_Account_Members();
				$res = $memberModel->select()->from('members', array('MemberID', 'UserName', 'Avatar', 'Signature'))
				->where('MemberID != ?', $memberID)->where("IMUserName != ''")->where("UserName != ''")->order('MemberID desc')->limit(8)->query()->fetchAll();

			} elseif ($getType == 'group') {
				$groupModel = new Model_IM_Group();
				$groupFocusModel = new Model_IM_GroupFocus();
				$select = $groupModel->select();
				$select->from('group', array('GroupID', 'GroupName', 'GroupAvatar'))->where("Status = 1")->where('IsPublic = 1')
				->where('OwnerID != ?', $this->memberInfo->MemberID)->order('AID desc')->limit(8);
				$res = $select->query()->fetchAll();
				if (!empty($res)) {
					foreach ($res as &$val) {
						$val['Focus'] = $groupFocusModel->getFocusInfo($val['GroupID'], null, 'FocusID');
					}
				}
			} elseif ($getType == 'topic') {
				$topicModel = new Model_Topic_Topic();
				$select = $topicModel->select()->from('topics', array('TopicID', 'TopicName', 'FollowNum', 'ViewNum', 'BackImage'));
				$res = $select->order('SortWeight desc')->limit(8)->query()->fetchAll();
				if (!empty($res)) {
					foreach ($res as &$val) {
						$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
					}
				}
			}
			$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * check 参数配置
	 * @var array
	 */
	protected $checkConf = array(
			array("currentVersion", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
			array("platform", "1,2", "参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
			array("idfa", "require", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE),
			array("deviceID", "require", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE),
	);

	/**
	 * 版本检测
	*/
	public function checkAction()
	{
		if (isset($this->memberInfo->MemberID) && $this->memberInfo->MemberID > 0) {
			$memberID = $this->memberInfo->MemberID;
			//记录某个用户最近24小时内打开app的次数
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'openAppNum:MemberID:' . $memberID . ':date:' . date('Y-m-d');
			$redisObj->INCR($key);
			$time = strtotime(date('Y-m-d', strtotime('+1 day'))) - time() + 7200;
			$redisObj->EXPIRE($key, $time);

			//是否是第一次打开
			$actNUmKey = 'ActiveNum:date:' . date('Y-m-d');
			$isActived = Model_Member::staticData($memberID, 'IsAlreadyActived');
			if (empty($isActived)) {
				Model_Member::staticData($memberID, 'IsAlreadyActived', 1);
				$redisObj->INCR($actNUmKey);
				$redisObj->EXPIRE($actNUmKey, $time);
			}
			//记录今天开启过app应用的人数
			$num = $redisObj->get($key);
			$uKey = 'OpenUserNum:date:' . date('Y-m-d');
			if ($num == 1) {
				$redisObj->INCR($uKey);
				$redisObj->EXPIRE($uKey, $time);
			}
			//记录今天打开app的总次数
			$tkey = 'OpenTotalNum:date:' . date('Y-m-d');
			$redisObj->INCR($tkey);
			$redisObj->EXPIRE($tkey, $time);

		} else {
			$memberID = 0;
		}

		//记录设备登陆信息
		if (isset($this->_param['deviceID'])) {
			$summary = new Model_Opensummary();
			$logs = new Model_Openlogs();
			$info = $summary->getInfo($this->_param['deviceID']);
			if (isset($info) && $info) {
				$return = $summary->update(array('MemberID' => $memberID, 'OpenNum' => $info['OpenNum'] + 1),
						array('OpenSummaryID = ?' => $info['OpenSummaryID']));
			} else {
				$return = $summary->insert(array('DeviceNo' => $this->_param['deviceID'], 'MemberID' => $memberID, 'OpenNum' => 1));
			}
			if ($return) {
				$logs->insert(array('DeviceNo' => $this->_param['deviceID'], 'MemberID' => $memberID));
			}
		}


		if (isset($this->_param['idfa'])) {
			$IdfaModel = new Model_PartnerIdfa();
			$OwnerModel = new Model_OwnerIdfa();
			$OwnerModel->add($memberID, $this->_param['idfa']);
		}

		//最低支持版本
		$return = array(
				'downLoadUrl' => '',
				'changeLog' => '',
				'title' => '',
		);

		$version_model = new Model_System_Version();
		$version_info = $version_model->find($this->_param['platform'])->toArray();
		if ($version_info) {
			$version_info = $version_info[0];

			$return['newVersion'] = $version_info['CurrentVersion'];
			$return['buttonText'] = $version_info['Button'];
			$return['changeLog'] = $version_info['Info'];
			$return['downLoadUrl'] = $version_info['Url'];
			
			if($this->_param['platform'] == 2){
				$return['title'] = '发现新版本'.$return['newVersion'];
			}
			
			//更新设置
			$return['updateType'] = version_compare($this->_param['currentVersion'], $version_info['CurrentVersion'], ">=") ? 0 :
			(version_compare($this->_param['currentVersion'], $version_info['MinVersion'], "<") ? 2 : $version_info['UpdateType']);
			
			if($this->_param['platform'] == 2){
				if(version_compare($this->_param['currentVersion'], '2.4.2',"<")){
					$return['updateType'] = $return['updateType'] > 0 ? $return['updateType'] - 1 : 0;
				}
			}
		}

		parent::succReturn($return);
	}

	/**
	 * 消息举报功能
	 * @author johnny 2015-07-01
	 */
	public function messageReportAction()
	{
		parent::init();
		$this->isLoginOutput();

		$infoType = intval($this->_request->getParam('infoType', 1));
		$memberID = (int)$this->_request->getParam('memberID',0);
		if ($infoType < 6 && !$memberID) {
			//$this->returnJson(parent::STATUS_FAILURE, '被举报者不能为空');
		}

		if (!$byMemberID = $this->memberInfo->MemberID) {
			$this->returnJson(parent::STATUS_FAILURE, '举报者不能为空');
		}
		if (!$messageContent = trim($this->_request->getParam('content', ''))) {
			if ($infoType == 1) {
				$this->returnJson(parent::STATUS_FAILURE, '消息内容不能为空');
			}
		}

		if (!$reason = trim($this->_request->getParam('reason', ''))) {
			$this->returnJson(parent::STATUS_FAILURE, '举报原因不能为空');
		}

		if($infoType == 11){
			$groupModel = new Model_IM_Group();
			$tmpInfoID = $this->_request->getParam('infoID', 0);
			if(strlen($tmpInfoID) >= 8){
				$groupInfo = $groupModel->getInfo($tmpInfoID,0,false);
				if(!empty($groupInfo)){
					$this->_request->setParam('infoID', $groupInfo['AID']);
				}
			}
		}
		
		$infoID = intval($this->_request->getParam('infoID', 0));
		if (($infoType != 1 && $infoID <= 0) && ($infoType != 10)) {
			$this->returnJson(parent::STATUS_FAILURE, '信息ID不能为空');
		}

		$reasonDetail = trim($this->_getParam('reasonDetail', ''));
		$imageUrl = trim($this->_getParam('imageUrl', ''));

		try {
			$modelMessageReport = new Model_IM_MessageReport();
			if ($modelMessageReport->Report2DB($memberID, $byMemberID, $messageContent, $reason, $infoType, $infoID, $reasonDetail, $imageUrl)) {
				$this->returnJson(parent::STATUS_OK, '举报成功');
			} else {
				$this->returnJson(parent::STATUS_FAILURE, '举报失败');
			}
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * 搜索相关接口
	 * @author Jeff 2015-07-14
	 */
	public function searchListAction()
	{
		$this->isLoginOutput();
		$keyWords = trim($this->_request->getParam('keyWords', ''));
		$searchType = trim($this->_request->getParam('searchType', ''));
		$pagesize = intval($this->_request->getParam('pagesize', 30));
		$memberID = $this->memberInfo->MemberID;
		if (empty($searchType)) {
			$this->returnJson(parent::STATUS_FAILURE, '请选择搜索类型！');
		}
		if (!in_array($searchType, array('member', 'group', 'topic', 'view','column','article'))) {
			$this->returnJson(parent::STATUS_FAILURE, '搜索类型参数错误！');
		}
		if ($keyWords !== '0' && empty($keyWords)) {
			$this->returnJson(parent::STATUS_FAILURE, '关键字不能为空！');
		}

		$lastID = intval($this->_request->getParam('lastID', '999999999'));
		$columnModel = new Model_Column_Column();
		$keywordsModel = new Model_Topic_SearchKeyWords();
		try {
			$memberModel = new DM_Model_Account_Members();
			if ($searchType == 'member') {//搜索人
				if (strlen($keyWords) == 11 && is_numeric($keyWords)) {
					$select = $memberModel->select()->from('members', array('MemberID', 'UserName', 'Avatar','IsBest'));
					$res = $select->where('MemberID < ? ', $lastID)->where('UserName != ""')->where('IMUserName != ""')->where("IsMobileSearchable = 1")->where("MobileNumber = ?", $keyWords)->where('MemberID != ?', $memberID)->order('MemberID desc')->limit($pagesize)->query()->fetchAll();
				} else {
					$select = $memberModel->select()->from('members', array('MemberID', 'UserName', 'Avatar','IsBest'));
					$res = $select->where('MemberID < ? ', $lastID)->where('IMUserName != ""')->where("IsAccountSearchable = 1")->where("UserName like ?", '%' . $keyWords . '%')->where('MemberID != ?', $memberID)->order('MemberID desc')->limit($pagesize)->query()->fetchAll();
				}
				if (!empty($res)) {
					//$viewModel = new Model_Topic_View();
					$followModel = new Model_MemberFollow();
					//$shuoshuoModel = new Model_Shuoshuo();
					$focusModel = new Model_MemberFocus();
					$bestModel = new Model_Best_Best();
					$authenticateModel =new Model_Authenticate();
					//$authenticateInfo = array();
					$qualificationModel = new Model_Qualification();
					foreach ($res as &$val) {
// 						$val['ViewCount'] = $viewModel->getViewCount($val['MemberID']);
// 						$val['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($val['MemberID']);
						$val['RelationCode'] = $followModel->getRelation($val['MemberID'], $memberID);
						$val['Focus'] = $focusModel->getFocusInfo($val['MemberID'], null, 'FocusID');
						//$val['IsBest'] = empty($best_info)?0:1;
						$val['Qualification'] = array();
						$authenticateInfo = $authenticateModel->getInfoByMemberID($val['MemberID'],1);
						if(!empty($authenticateInfo) && $authenticateInfo['AuthenticateType']==2){
							$qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);
							if(empty($qualificationInfo)){
		                        $qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1);
		                    }
							//$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],3,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
							 $val['Qualification'] = !empty($qualificationInfo)? array($qualificationInfo) : array();
						}
						$bestInfo = $bestModel->getBestInfoByMemberID(array($val['MemberID']), array(2,3));
						$bestTitleArr = array();
						if(!empty($bestInfo)){
							$bestTitleArr = $bestInfo[$val['MemberID']];
						}
						$val['BestTitle'] = !empty($bestTitleArr)?$bestTitleArr:array();
						
						$val['IsAuthentication'] = empty($authenticateInfo)?0:1;
						$val['AuthenticateType'] = empty($authenticateInfo)?0:$authenticateInfo['AuthenticateType'];
					}

				 	$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				//print_r($res);exit;
				$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));

			} elseif ($searchType == 'group') {//搜索群组
				$groupMdoel = new Model_IM_Group();
				$select = $groupMdoel->select();
				if(is_numeric($keyWords)){
					$res = $select->from('group', array('AID', 'GroupID', 'GroupName', 'GroupAvatar', 'NowUserCount as MemberCount','OwnerID'))->where('AID < ?', $lastID)
					->where('Status = 1')->where('IsPublic = 1')->where('AID = ? ', $keyWords)->order('AID desc')->limit($pagesize)->query()->fetchAll();
				}else{
					$res = $select->from('group', array('AID', 'GroupID', 'GroupName', 'GroupAvatar', 'NowUserCount as MemberCount','OwnerID'))->where('AID < ?', $lastID)
					->where('Status = 1')->where('IsPublic = 1')->where('GroupName like ? ', '%' . $keyWords . '%')->order('AID desc')->limit($pagesize)->query()->fetchAll();
				}
				if (!empty($res)) {
					$groupMemberModel = new Model_IM_GroupMember();
					$groupFocusModel = new Model_IM_GroupFocus();
					foreach ($res as &$info) {
						$info['Focus'] = $groupFocusModel->getFocusInfo($info['GroupID'], null, 'FocusID');
					}
					$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));

			} elseif ($searchType == 'topic') {//搜索话题
				$fields = array('TopicID', 'TopicName', 'FollowNum', 'ViewNum', 'BackImage','IsAnonymous');
				$topicModel = new Model_Topic_Topic();
				$select = $topicModel->select()->from('topics', $fields)->where('TopicID < ?', $lastID)->where('TopicName like ?', '%' . $keyWords . '%')->where('CheckStatus = ?', 1)->where('IsAnonymous = 0');
				$isCreatable = 1;
				$res = $select->order('TopicID desc')->limit($pagesize)->query()->fetchAll();
				if (!empty($res)) {
					foreach ($res as &$val) {
						$val['IsFollowed'] = $topicModel->isFollowedTopic($memberID, $val['TopicID']);
						if ($val['TopicName'] == $keyWords) {
							$isCreatable = 0;
						}
					}
					$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				$isExistCheckingTopic=$topicModel->isExistCheckingTopic($memberID);
				if($isExistCheckingTopic == 1){
					$isCreatable = 0;
				}
				$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res, 'isCreatable' => $isCreatable));
			} elseif ($searchType == 'view') {//搜索观点
				$fields = array('ViewID', 'ViewContent', 'CreateTime', 'MemberID','IsAnonymous','AnonymousUserName','AnonymousAvatar');
				$viewModel = new Model_Topic_View();
				$select = $viewModel->select()->from('topic_views', $fields)->where('ViewID < ?', $lastID)->where('CheckStatus = ?', 1)->where('ViewContent like ?', '%' . $keyWords . '%');
				$res = $select->order('ViewID desc')->limit($pagesize)->query()->fetchAll();
				if (!empty($res)) {
					foreach ($res as &$val) {
						if($val['IsAnonymous'] == 1){
							$val['UserName'] = $val['AnonymousUserName'];
							$val['Avatar'] = $val['AnonymousAvatar'];
						}else{
							$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
							$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'], 'UserName');
						}
						unset($val['AnonymousUserName']);
						unset($val['AnonymousAvatar']);
					}
					$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));
			}elseif($searchType == 'column'){
				$fields = array('ColumnID','Title','Avatar','SubscribeNum','ArticleNum','Description');
				
				$select = $columnModel->select()->from('column',$fields)->where('CheckStatus = ?',1)
				->where('Title like ?','%'.$keyWords.'%')->where('ColumnID < ?',$lastID);
				
				$res = $select->order('ColumnID desc')->limit($pagesize)->query()->fetchAll();
				if(!empty($res)){
					foreach ($res as &$val){
						$val['IsSubscribe'] = $columnModel->isSubscribeColumn($memberID, $val['ColumnID']);
					}
					$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));
			}elseif($searchType == 'article'){
				$fields = array('AID','MemberID','ColumnID','Title','Cover','PublishTime');
				$articleModel = new Model_Column_Article();
				$select = $articleModel->select()->from('column_article',$fields)->where('Status = ?',1)->where('Title like ?','%'.$keyWords.'%')
				->where('AID < ?',$lastID);
				$results = $select->order('AID desc')->limit($pagesize)->query()->fetchAll();
				if(!empty($results)){
					$memberModel = new DM_Model_Account_Members();
					$memberNoteModel = new Model_MemberNotes();
					foreach($results as &$val){
						$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
						$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['MemberID']);
						$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
					}
					$keywordsModel->recordKeyWords($searchType, $keyWords);
				}
				$this->returnJson(parent::STATUS_OK, '', array('Rows' =>$results));
			}
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
	
	/**
	 * 提交扫描结果
	 */
	public function submitScanCodeAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$codeStr = trim($this->_request->getParam('codeStr',''));
		$secretStr = trim($this->_request->getParam('secretStr',''));
		try{
			if(empty($codeStr) || strlen($codeStr) != 30 || empty($secretStr) || strlen($secretStr) != 32){
				throw new Exception('参数错误');
			}
	
			if($secretStr != md5($memberID.$codeStr)){
				throw new Exception('参数错误');
			}
			$qrcodeModel = new DM_Model_Account_QrCodes();
			$codeInfo = $qrcodeModel->getInfoByCode($codeStr,array('MemberID'=>0));
			if(empty($codeInfo)){
				throw new Exception('二维码错误');
			}
			$qrcodeModel->update(array('MemberID'=>$memberID),array('CodeID = ?'=>$codeInfo['CodeID']));
			$this->returnJson(parent::STATUS_OK,'扫码成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 同意登录
	 */
	public function agreeToLoginAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$codeStr = trim($this->_request->getParam('codeStr',''));
		try{
	
			if(empty($codeStr) || strlen($codeStr) != 30){
				throw new Exception('参数错误');
			}
	
			$qrcodeModel = new DM_Model_Account_QrCodes();
			$codeInfo = $qrcodeModel->getInfoByCode($codeStr,array('MemberID'=>$memberID));
			if(empty($codeInfo)){
				throw new Exception('二维码错误');
			}
			
			if($codeInfo['ValidEndSconds'] < time()){
				$this->returnJson(-1001,'已过期');	
			}
			
			$qrcodeModel->update(array('IsAgree'=>1),array('CodeID = ?'=>$codeInfo['CodeID']));
			$this->returnJson(parent::STATUS_OK,'确认登录成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * WEB端是否已登录
	 */
	public function isWebLoginAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;
		$isLogin = 0;
		$sessKey = DM_Model_Account_Members::staticData($member_id,'WebLoginSessKey');
		if(!empty($sessKey)){
			$sessionRedis = DM_Module_Redis::getInstance('session');
			$ttl = $sessionRedis->ttl('PHPREDIS_SESSION:'.$sessKey);
			if($ttl > 0){
				$isLogin = 1;
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('IsLogin'=>$isLogin));
	}
	
	/**
	 * web端退出
	 */
	public function logoutWebAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;
		$sessKey = DM_Model_Account_Members::staticData($member_id,'WebLoginSessKey');
		if(!empty($sessKey)){
			$sessionRedis = DM_Module_Redis::getInstance('session');
			session_write_close();
			$sessionRedis->delete('PHPREDIS_SESSION:'.$sessKey);
			DM_Model_Account_Members::staticData($member_id,'WebLoginSessKey','');
		}
		$this->returnJson(parent::STATUS_OK,'退出成功！');
	}
	
	
	/**
	 * 获取配置
	 */
	public function getConfigAction()
	{
		$config = DM_Controller_Front::getInstance()->getConfig();
		$arr = array(
			'WebSocketServer'=>$config->websocket->ClientUrl,
		);
		$this->returnJson(parent::STATUS_OK,'',$arr);
	}
	
	/**
	 * 获取活动强推
	 */
	public function getActivityPushAction()
	{
		$this->isLoginOutput();
		$forceActivityModel = new Model_ForceActivity();
		$row = $forceActivityModel->getAvaliable($this->memberInfo->MemberID);
		$this->returnJson(parent::STATUS_OK,'',$row);
	}
}