<?php
class Web_ColumnController extends Action_Web {
	
	public function init()
	{
		parent::init();
		if($this->action_name!='add'){
			$this->hasColumn();
		}
		//header('Content-type: text/html');
	}
	
	/**
	 * 理财号首页
	 */
	public function indexAction()
	{
		$this->view->headTitle("财猪 - 理财号首页");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$rowcount = 0;
		$noticeModel = new Model_Column_Notice();
		$select = $noticeModel->select()->from('column_notice',array('NoticeID','Title','CreateTime'))
		->where('Status = ?',1)->where('MemberID = 0 or MemberID = ?',$this->memberInfo->MemberID)->where('Type = ?',1)->order('NoticeID desc');
		
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		if(!empty($paginator)){
			foreach($paginator as &$val){
				$val['IsNew'] = 0;
				$val['date'] = date('Y.m.d',strtotime($val['CreateTime']));
				$val['time'] = date('H:i',strtotime($val['CreateTime']));
				if($this->lastLoginTime<strtotime($val['CreateTime'])){
					$val['IsNew'] = 1;
				}
			}
		}
		$key = 'lastLoginTime:Member:'.$this->memberInfo->MemberID;
		$redisObj = DM_Module_Redis::getInstance();
		$redisObj->set($key,time());
		$this->view->noticeInfo = $paginator;
	}
	
	/**
	 * 创建理财号
	 */
	public function addAction()
	{
		Zend_Layout::getMvcInstance()->disableLayout();
		$this->view->headTitle("财猪 - 创建理财号");
		$focusModel = new Model_Focus();
		$fieldsArr = array('FocusID','FocusName');
		$select = $focusModel->select()->from('focus',$fieldsArr)->where('IsTopicFocus = ?',1);
		$focusArr = $select->query()->fetchAll();
		$columnModel = new Model_Column_Column();
		$columnID = $this->columnID;
		$columnInfo = $columnModel->getMyColumnInfo($this->memberInfo->MemberID);
		$fModel = new Model_Column_ColumnFocus();
		$selectFocus = array();
		if(!empty($columnInfo)){
			$focusID = $fModel->getInfo($columnInfo['ColumnID']);
			foreach($focusID as $value){
				$selectFocus[] = $value['FocusID'];
			}
		}
		$authID = intval($this->_getParam('authID',0));
		$quaID = intval($this->_getParam('quaID',0));
		if($this->_request->isPost()){
			try{
				$flag =0;
				if(!empty($columnInfo)  && ($columnInfo['CheckStatus'] ==1 ||$columnInfo['CheckStatus'] ==0) ){
					$flag = 1;
					$msg = "您的理财号正在审核中，或已经审核通过！";
				}else{
					$title = trim($this->_getParam('title',''));
					if(empty($title)){
						throw new Exception('请输理财号栏名称！');
					}
					//$len = mb_strlen($title,'utf-8');
					if(!DM_Helper_Validator::checkUsername($title)){
						throw new Exception('名称为2-10个字母、数字、汉字组成的字符，不能全为数字');
					}
					$sensitiveModel = new DM_Model_Account_Sensitive();
		            $sensitive = $sensitiveModel->getInfo($title);
		
		            $filter = $sensitiveModel->filter($title);
		            if (!empty($sensitive) || $filter ==1) {
		                $this->returnJson(parent::STATUS_FAILURE,'请不要使用敏感词汇注册！');
		            }
					$focusID = trim($this->_getParam('label',''));
					if(empty($focusID)){
						throw new Exception('请选择理财号标签！');
					}
					$focusArr = explode(",", $focusID);
					if(count($focusArr)>3){
						throw new Exception('最多只能选择3个理财号标签！');
					}
					$description = str_replace(array("\r\n","\r","\n"), ' ', trim($this->_getParam('description',''))); 
					if(empty($description)){
						throw new Exception('请输入理财号简介！');
					}
					if(mb_strlen($description,'utf-8')>50 || mb_strlen($description,'utf-8')<10)
					{
						//echo 333;exit;
						throw new Exception('描述10-50个字符');
					}
					$avatarUrl = trim($this->_getParam('avatar',''));
					if(empty($avatarUrl)){
						throw new Exception('请上传理财号头像！');
					}
					$mModel = new DM_Model_Account_Members();
					$info = $mModel->getByUsername($title);
					if(!empty($info) && $info['UserName'] != $this->memberInfo->UserName)
					{
						throw new Exception('该理财号已被占用');
					}
					$cInfo = $columnModel->hasTitle($title,$columnID);
					if(!empty($cInfo)){
						throw new Exception('该理财号已被占用');
					}
					
					$paramArr = array(
							'Title'=>$title,
							'Avatar'=>$avatarUrl,
							'Description'=>$description,
							'MemberID'=>$this->memberInfo->MemberID,
							'CheckStatus'=>0
					);
					if(!$columnID){
						$columnID= $columnModel->insert($paramArr);
						$flag = 1;
						$msg = '创建成功！';
					}else{
						// if($columnInfo['CheckStatus'] ==1){
						// 	throw new Exception('理财号已审核通过不能修改！');
						// }
						$columnModel->update($paramArr, array('ColumnID = ?'=>$columnID));
						$fModel->delete(array('ColumnID = ?'=>$columnID));
						$flag =1;
						$msg = '编辑成功！';
					}
					
					if($columnID > 0 ){
						$focusModel = new Model_Column_ColumnFocus();
						foreach ($focusArr as $value) {
							$focusModel->addFocus($columnID,$value);
						}
					}
				}



				$authID = intval($this->_getParam('authID',0));
				$quaID = intval($this->_getParam('quaID',0));

				$authenticateModel = new Model_Authenticate();
				$qualificationModel = new Model_Qualification();
				$authenticateTempModel = new Model_AuthenticateTemp();
				$qualificationTempModel = new Model_QualificationTemp();
				if($authID >0){
					$authTempInfo = $authenticateTempModel->getInfoByID($authID);
					if(!empty($authTempInfo)){
						if($authTempInfo['AuthenticateType']==2 && $quaID >0){
							$tempQuaInfo = $qualificationTempModel->getInfoByID($quaID);
							if(!empty($tempQuaInfo)){
								$quaParam = array(
						            		'FinancialQualificationImage'=>$tempQuaInfo['FinancialQualificationImage'],
						            		'FinancialQualificationType'=>$tempQuaInfo['FinancialQualificationType'],
						            		'QualificationGetTime'=>$tempQuaInfo['QualificationGetTime'],
						            		'CheckStatus' => $tempQuaInfo['CheckStatus'],
										);
							}
						}

						if($authTempInfo['AuthenticateType']==3){
							$authenticateParam = array(
									'BusinessName'=> $authTempInfo['BusinessName'],
									'FoundedTime'=>$authTempInfo['FoundedTime'],
									'Province'=> $authTempInfo['Province'],
									'City'=>$authTempInfo['City'],
									'Address'=> $authTempInfo['Address'],
									'BusinessLicenseNumber'=> $authTempInfo['BusinessLicenseNumber'],
									'BusinessLicenseImage'=>$authTempInfo['BusinessLicenseImage']
								);
						}

						if($authTempInfo['AuthenticateType']==4){
							$authenticateParam = array(
									'OrganizationName'=> $authTempInfo['OrganizationName'],
									'FoundedTime'=>$authTempInfo['FoundedTime'],
									'Province'=> $authTempInfo['Province'],
									'City'=>$authTempInfo['City'],
									'Address'=> $authTempInfo['Address'],
									'OrganizationCode'=> $authTempInfo['OrganizationCode'],
									'OrganizationImage'=>$authTempInfo['OrganizationImage']
								);
						}


						$authenticateParam['AuthenticateType'] = $authTempInfo['AuthenticateType'];
						$authenticateParam['MemberID'] = $authTempInfo['MemberID'];
						$authenticateParam['OperatorName'] = $authTempInfo['OperatorName'];
						$authenticateParam['IDCard'] = $authTempInfo['IDCard'];
						$authenticateParam['IDPhoto'] = $authTempInfo['IDPhoto'];
						$authenticateParam['MobileNumber'] = $authTempInfo['MobileNumber'];
						$authenticateParam['Status'] = $authTempInfo['Status'];
							
					}
					

					$authInfo = $authenticateModel->getInfoByMemberID($this->memberInfo->MemberID);
					if($this->memberInfo->MemberID == $authTempInfo['MemberID']){
						if(empty($authInfo)){
							$authenticateID = $authenticateModel->add($authenticateParam);
							if($authTempInfo['AuthenticateType'] == 2){
								$quaParam['AuthenticateID'] = $authenticateID;
								$quaInfo = $qualificationModel->getInfoByqualificationID($authenticateID,1);
								if(!empty($quaInfo)){
									$qualificationModel->update($quaParam,array('FinancialQualificationID = ?'=>$quaInfo['FinancialQualificationID']));
								}else{
									$qualificationModel->insert($quaParam);
								}
								$qualificationTempModel->delete(array('FinancialQualificationID = ?'=>$quaID));
							}
							
						}else{
							$authenticateModel->edit($authenticateParam,$authInfo['AuthenticateID']);
							if($authTempInfo['AuthenticateType'] == 2){
								$quaParam['AuthenticateID'] = $authInfo['AuthenticateID'];
								$quaInfo = $qualificationModel->getInfoByqualificationID($authInfo['AuthenticateID'],1);
								if(!empty($quaInfo)){
									$qualificationModel->update($quaParam,array('FinancialQualificationID = ?'=>$quaInfo['FinancialQualificationID']));
								}else{
									$qualificationModel->insert($quaParam);
								}

								$qualificationTempModel->delete(array('FinancialQualificationID = ?'=>$quaID));
							}

						}
						$authenticateTempModel->delete(array('AuthenticateID = ?'=>$authID));
							
					}
							
				}

				$this->returnJson($flag,$msg,$authID);
			}catch(Exception $e){
				$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
			}
		}
		$this->view->columnInfo = $columnInfo;
		$this->view->focusID = $selectFocus;
		$this->view->focusArr = $focusArr;
		$this->view->authID = $authID;
		$this->view->quaID = $quaID;
	}
	
	/**
	 * 公告通知详情
	 */
	public function noticeDetailAction(){
		$this->view->headTitle("财猪 - 公告详情页");
		$noticeID = intval($this->_getParam('noticeID',0));
		if($noticeID<1){
			$this->returnJson(self::STATUS_FAILURE,'该公告不存在！');
		}
		$noticeModel = new Model_Column_Notice();
		$select = $noticeModel->select()->from('column_notice',array('Title','Content','CreateTime'))
		->where('NoticeID = ?',$noticeID);
		$noticeInfo = $select->query()->fetch();
		$noticeInfo['CreateTime'] = date('Y.m.d H :i',strtotime($noticeInfo['CreateTime']));

		$this->view->noticeInfo = $noticeInfo;
	}
	
	/**
	 * 用户管理
	 */
	public function userManageAction()
	{
		$this->view->headTitle("财猪 - 用户管理");
		$memberID = $this->memberInfo->MemberID;
		$page = intval($this->_getParam('page',1));
		$pagesize = intval($this->_getParam('pagesize',10));
		$columnID = $this->columnID;
		$rowcount = 0;
		$redisObj = DM_Module_Redis::getInstance();
		$lastLoginTime = $redisObj->get('lastLoginTime:MemberID:'.$memberID);
		$key = Model_Column_MemberSubscribe::getSubscribeKey($columnID);
		$memberArr = $redisObj->zRevRangeByScore($key,'+inf','-inf');
		if(empty($memberArr)){
			$memberArr = array(0);
		}
		$memberModel = new DM_Model_Account_Members();
		$select = $memberModel->select()->from('members',array('UserName','Avatar','Signature','City'))->where('MemberID in (?)',$memberArr);
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pagesize);

		if(!empty($paginator)){
			foreach($paginator as &$val)
			{
				$val['Avatar'] = empty($val['Avatar'])?'http://img.caizhu.com/default_tx.png':$val['Avatar'];
				$val['IsNew']=0;
				$score = $redisObj->ZSCORE($key,$val);
				if($lastLoginTime<$score){
					$val['IsNew']=1;
				}
			}
		}
		$this->view->total = count($memberArr);
		$this->view->dataList = $paginator;
	}
    
    /**
     * 
     */
    public function plannerInfoAction(){
        
    }
}