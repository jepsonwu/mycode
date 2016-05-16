<?php
include_once dirname(__FILE__).'/Abstract.php';

class Web_ActivityController extends Action_Web {
	
	public function init()
	{
		parent::init();
		$this->hasColumn();
		//header('Content-type: text/html');
	}
/**
	 * 活动列表页面
	 */
	public function indexAction()
	{
		$this->view->headTitle("财猪 - 活动");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$type = intval($this->_getParam('type',1));
		$columnID = $this->columnID;
		$memberID = $this->memberInfo->MemberID;
		$rowcount = 0;
		$activityModel = new Model_Column_Activity();
		$select = $activityModel->select()->from('column_activity',array('AID','Title','StartTime',
						'Province','City','EnrollNum','IsCharge',new Zend_Db_Expr("IF(StartTime < NOW() AND EndTime > NOW(),1,0) as IsGoing")))
        ->where('ColumnID = ?',$columnID)->where('Status = ?',1);
		if($type==1){
			$select->where('EndTime > ?',date('Y-m-d H:i:s'));
		}else{
			$select->where('EndTime < ?',date('Y-m-d H:i:s'));
		}
		$select = $select->order('AID desc');
		//echo $select->__toString();exit;
		$runningTotal = $activityModel->getCount($columnID,1);
		$endTotal = $activityModel->getCount($columnID,2);
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);

		$activityInfo = $select->order('AID desc')->limitPage($page, $pageSize)->query()->fetchAll();
		$this->view->runningTotal = $runningTotal;
		$this->view->endTotal = $endTotal;
		$this->view->activityInfo = $paginator;
	}
	
	/**
	 * 查看活动信息
	 */
	public function detailAction()
	{
		$this->view->headTitle("财猪 - 活动基本信息页");
		$activityID = intval($this->_getParam('activityID',0));
		if($activityID<1){
			$this->returnJson(parent::STATUS_FAILURE,'该活动不存在！');
		}
		$activityModel = new Model_Column_Activity();
		$select = $activityModel->select()->from('column_activity',array('AID','MemberID','Title','CreateTime','StartTime','EndTime','LimitTime','ReadNum','ShareNum','EnrollNum','Province','City','DetailAdress','IsUsername','IsMobile','Cover','Content','SignQrcode'))
		->where('AID = ?',$activityID)->where('Status = ?',1);
		$dataList = $select->query()->fetch();
		$memberModel = new DM_Model_Account_Members();
		if(!empty($dataList)){
			$dataList['UserName'] = $memberModel->getMemberInfoCache($dataList['MemberID'],'UserName');
			$dataList['StartTime'] = date('m-d H:i',strtotime($dataList['StartTime']));
			$dataList['EndTime'] = date('m-d H:i',strtotime($dataList['EndTime']));
			$dataList['LimitTime'] = date('m-d H:i',strtotime($dataList['LimitTime']));
		}
		$this->view->dataList = $dataList;
	}
	

	/**
	 * 删除活动
	 */
	public function deleteAction()
	{
		$activityID = intval($this->_getParam('activityID',0));
		if($activityID<1){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
		$isSend = intval($this->_getParam('isSend',0));
		$activityModel = new Model_Column_Activity();
		//获取当前报名的人，发通知告知
		$enrollModel = new Model_Column_ActivityEnroll();
		$enrollInfo = $enrollModel->getEnrollMembers($activityID);
		if(!empty($enrollInfo) && $isSend){
			$url =  $this->getFullHost() ."/api/public/activity-detail/activityID/".$activityID;
			$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
			$content = "您报名的活动已取消，点击查看详情！";
			$ext = array(
					"share_chat_msg_type" => 104,
					"share_chat_msg_type_desc" => $content,
					"share_chat_msg_type_id" => $url,
					"share_chat_msg_type_image" => "http://img.caizhu.com/caizhu-log-180_180.png",
					"share_chat_msg_type_name" => "活动取消通知",
					"share_chat_msg_type_title" => "活动取消通知",
					"share_chat_msg_type_url" => $url,
			);
			$easeModel = new Model_IM_Easemob();
			foreach ($enrollInfo as $val){//给每一个用户发消息
				
				$easeModel->yy_hxSend(array($val['MemberID']), '活动取消通知', 'txt', 'users', $ext, $sysMemberID);
			}
		}
		$activityModel->update(array('Status'=>0), array('AID = ?'=>$activityID));
		$this->returnJson(parent::STATUS_OK,'删除成功！');
	}
	
	/**
	 * 报名管理
	 */
	public function enrollManageAction()
	{
// 		$page = intval($this->_getParam('page',1));
// 		$pageSize = intval($this->_getParam('pageSize',2));
		$this->view->headTitle("财猪 - 活动报名管理");
		$activityID = intval($this->_getParam('activityID',0));
		if($activityID<1){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
		$redisObj = DM_Module_Redis::getInstance();
		$rowcount = 0;
		$memberID = $this->memberInfo->MemberID;
		$lastLoginTime = $redisObj->get('lastLoginTime:Member:'.$memberID);
		$enrollModel = new Model_Column_ActivityEnroll();
		
		$select = $enrollModel->select()->from('column_activity_enroll',array('MemberID','RealName','Mobile','CreateTime','IsSign'))
		->where('ActivityID = ?',$activityID)->order('EnrollID desc');
		
// 		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
// 		if($rowcount){
// 			$adapter->setRowCount(intval($rowcount));
// 		}
// 		$paginator = new Zend_Paginator($adapter);
		
// 		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		$dataList = $select->order('EnrollID desc')->query()->fetchAll();
		//总条数
		$total = $enrollModel->getAdapter()->fetchOne($countSql);
		if(!empty($dataList)){
			$memberModel = new DM_Model_Account_Members();
			$activityModel = new Model_Column_Activity();
			$activityinfo = $activityModel->getActvityInfo($activityID);
			foreach($dataList as &$val){
				$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				$avatar =$memberModel->getMemberAvatar($val['MemberID']);
				$val['Avatar'] = empty($avatar)?'http://img.caizhu.com/default_tx.png':$avatar;
				$val['IsNew']=0;
				$val['CreateTime'] = date('m-d H:i',strtotime($val['CreateTime']));
				if($lastLoginTime<strtotime($val['CreateTime'])){
					$val['IsNew']=1;
				}
				$val['activityName'] = $activityinfo['Title'];
			}
		}
		$this->view->activityID = $activityID;
		$this->view->dataList = $dataList;
		$this->view->total = $total;
	}
	
	/**
	 * 创建活动
	 */
	public function addAction()
	{
		$this->view->headTitle("财猪 - 创建活动");
		$activityID = intval($this->_getParam('activityID',0));
		if($activityID>1){
			$activityModel = new Model_Column_Activity();
			$info = $activityModel->getActvityInfo($activityID);
			$this->view->info = $info;
		}
		
		$activityModel = new Model_Column_Activity();
		$select = $activityModel->select()->from('column_activity',array('AID','Title','CreateTime'))
		->where('MemberID = ?',$this->memberInfo->MemberID)->where('columnID = ?',$this->columnID)->where('Status = ?',2);
		$draftInfo = $select->order('AID desc')->limit(10)->query()->fetchAll();
		$this->view->draftInfo = $draftInfo;
	}
	
	/**
	 * 创建活动
	 */
	public function createAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$columnID = $this->columnID;
		$activityID = intval($this->_getParam('activityID',0));
		$title = trim($this->_getParam('title',''));
		$startTime = trim($this->_getParam('startTime','0000-00-00 00:00:00'));
		$endTime = trim($this->_getParam('endTime','0000-00-00 00:00:00'));
		$province = trim($this->_getParam('province',''));
		$city = trim($this->_getParam('city',''));
		$detailAdress = trim($this->_getParam('area',''));
		$limitNum = intval($this->_getParam('limitMember',''));
		$coverUrl = trim($this->_getParam('cover',''));
		$content = trim($this->_getParam('content',''));
		$limitTime = trim($this->_getParam('limitTime',''));
		$isUsername = intval($this->_getParam('isUsername',1));
		$isMobile = intval($this->_getParam('isMobile',1));
		$status = intval($this->_getParam('formType',1));//保存 1正常保存，2保存草稿箱，3预览暂存
		$isCharge = intval($this->_getParam('isCharge',0));//1收费 0 不收费
		$cost = $this->_getParam('cost',0);
		$isNormal = intval($this->_getParam('isNormal',0));//0正常保存 1不正常保存
		$increaseNum = 0;
		if(!$isNormal){	
			if(empty($title)){
				$this->returnJson(parent::STATUS_FAILURE,'标题不能为空！');
			}
			if(mb_strlen($title,'utf-8')>50){
				$this->returnJson(parent::STATUS_FAILURE,'活动名称最多50个字符！');
			}
			if(empty($coverUrl)){
				$this->returnJson(parent::STATUS_FAILURE,'请上传封面！');
			}
			if(empty($startTime)||empty($endTime)){
				$this->returnJson(parent::STATUS_FAILURE,'开始时间和结束时间不能为空！');
			}
			if($limitNum<1){
				$this->returnJson(parent::STATUS_FAILURE,'人数限制必须大于1！');
			}
			if(empty($content)){
				$this->returnJson(parent::STATUS_FAILURE,'活动详情不能为空！');
			}
			if(empty($limitTime)){
				$this->returnJson(parent::STATUS_FAILURE,'报名截至时间不能为空！');
			}
			if(strtotime($endTime)<strtotime($startTime)){
				$this->returnJson(parent::STATUS_FAILURE,'活动结束时间不能晚于开始时间！');
			}
			if(strtotime($limitTime)>strtotime($startTime)){
				$this->returnJson(parent::STATUS_FAILURE,'活动报名截止时间不能晚于开始时间！');
			}
		}else{
			if(empty($content)){
				$this->returnJson(parent::STATUS_FAILURE,'活动详情不能为空！');
			}
			if(empty($title)){
				$num = $this->getDraftNum(2,$memberID);
				$title = "草稿".$num;
				$increaseNum = 1;
			}
		}
		$activityModel = new Model_Column_Activity();
		$paramArr = array(
				'MemberID'=>$memberID,
				'ColumnID'=>$columnID,
				'Title'=>$title,
				'StartTime'=>$startTime,
				'EndTime'=>$endTime,
				'Province'=>$province,
				'City'=>$city,
				'DetailAdress'=>$detailAdress,
				'LimitNum'=>$limitNum,
				'Cover'=>$coverUrl,
				'Content'=>$content,
				'LimitTime'=>$limitTime,
				'IsUsername'=>$isUsername,
				'IsMobile'=>$isMobile,
				'Status'=>$status,
				'CreateTime'=>date('Y-m-d H:i:s'),
				'IsCharge'=>$isCharge,
				'Cost'=>$cost
		);
		$db = $activityModel->getAdapter();
		$db->beginTransaction();
		if($activityID){
			if($status == 1){
				$re = $activityModel->delete(array('AID = ?'=>$activityID));
				if(!$re){
					$db->rollBack();
					$this->returnJson(parent::STATUS_FAILURE,'失败！');
				}
				$activityID= $activityModel->insert($paramArr);
				if(!$activityID){
					$db->rollBack();
					$this->returnJson(parent::STATUS_FAILURE,'失败！');
				}
				$isInsert = 1;
			}else{
				if($status == 3){
					unset($paramArr['Status']);
				}
				$activityModel->update($paramArr, array('AID = ?'=>$activityID));
				$isInsert = 0;
			}
		}else{
			$activityID= $activityModel->insert($paramArr);
			$isInsert = 1;
		}
		if($isInsert>0){
			//生成二维码
			$import_path = APPLICATION_PATH.'/../public/upload/';
			$filename = $activityID.'Activity'.time().'.png';
			$url = $this->_fullHost."/api/public/activity-detail?activityID=".$activityID."&isPreview=1";
			DM_Module_QRcode::png($url,$import_path.$filename,"L",'6',0);
			$qiniu = new Model_Qiniu();
			$token = $qiniu->getUploadToken();
			$uploadMgr = $qiniu->getUploadManager();
			$file = realpath($import_path.$filename);
			list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
			if ($err !== null) {
				var_dump($err);
			} else {
				$qrcodeUrl = "http://img.caizhu.com/".$ret['hash'];
				unlink($file);
			}
			
			$filename2 = $activityID.'Sign'.time().'.png';
			$url = $this->_fullHost."/api/public/activity-sign?activityID=".$activityID."&caizhunotshowright=1";
			DM_Module_QRcode::png($url,$import_path.$filename2,"H",'6',0);
			$file2 = realpath($import_path.$filename2);
			$logo = "http://img.caizhu.com/caizhu_logo.png";
			if ($logo !== FALSE) {
				$QR = imagecreatefromstring(file_get_contents($file2));
				$logo = imagecreatefromstring(file_get_contents($logo));
				$QR_width = imagesx($QR);//二维码图片宽度
				$QR_height = imagesy($QR);//二维码图片高度
				$logo_width = imagesx($logo);//logo图片宽度
				$logo_height = imagesy($logo);//logo图片高度
				$logo_qr_width = $QR_width / 5;
				$scale = $logo_width/$logo_qr_width;
				$logo_qr_height = $logo_height/$scale;
				$from_width = ($QR_width - $logo_qr_width) / 2;
				//重新组合图片并调整大小
				imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
				$logo_qr_height, $logo_width, $logo_height);
				imagepng($QR, $file2);
			}
			list($ret2, $err2) = $uploadMgr->putFile($token['token'], null, $file2);
			if ($err2 !== null) {
				var_dump($err2);
			} else {
				$signQrcode = "http://img.caizhu.com/".$ret2['hash'];
				unlink($file2);
			}
			$activityModel->update(array('QrcodeUrl'=>$qrcodeUrl,'SignQrcode'=>$signQrcode), array('AID = ?'=>$activityID));
		}
		else{
			$info = $activityModel->getActvityInfo($activityID);
			$qrcodeUrl = $info['QrcodeUrl'];
		}
		if($status==1){
			$field = array('type'=>2,'AID'=>$activityID,'publishTime'=>time());
			Model_Column_Column::staticData($columnID,$field,-1);
		}
		//把草稿数加1
		if($increaseNum){
			$this->getDraftNum(2,$memberID,1);
		}
		$db->commit();
		$data = array('activityID'=>$activityID,'qrcodeUrl'=>$qrcodeUrl);
		$this->returnJson(parent::STATUS_OK,'创建成功！',$data);
	}
	
	/**
	 * 获取活动信息
	 */
	public function getActivityInfoAction()
	{
		$activityID = intval($this->_getParam('activityID',0));
		if($activityID<1){
			$this->returnJson(parent::STATUS_FAILURE,'活动ID不能为空！');
		}
		$activityModel = new Model_Column_Activity();
		$info = $activityModel->getActvityInfo($activityID);
		$this->returnJson(parent::STATUS_OK,'成功',$info);
	}
	
	//下载图片函数
// 	function downImageAction(){

// 		$file_name = 'http://img.caizhu.com/FvZvMsR4XYKLS0OuqdtI-lFIWMwq';
// 		$fileName = 'hd'.date('Ymd').'.png';
// 		$mime = 'image/force-download';
// 		header('Pragma: public'); // required
// 		header('Expires: 0'); // no cache
// 		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
// 		header('Cache-Control: private',false);
// 		header('Content-Type: '.$mime);
// 		header('Content-Disposition: attachment; filename="'.$fileName.'"');
// 		header('Content-Transfer-Encoding: binary');
// 		header('Connection: close');
// 		readfile($file_name); // push it out
// 		exit();
// 	}
	
}