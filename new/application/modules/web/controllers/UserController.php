<?php
class Web_UserController extends Action_Web {
	
	public function init()
	{
		parent::init();
		$this->hasColumn();
		//header('Content-type: text/html');
	}
	
	/**
	 * 帐号设置
	 */
	public function setAction()
	{
		$this->view->headTitle("财猪 - 设置");
		$columnModel = new	Model_Column_Column();
		$focusModel = new Model_Column_ColumnFocus();
		$authenticateModel = new Model_Authenticate();
		$qualificationModel = new Model_Qualification();
		$memberID = $this->memberInfo->MemberID;

		$columnInfo = $columnModel->getMyColumnInfo($memberID);
		$focusArr = array();
		if(!empty($columnInfo)){
			$focusArr = $focusModel->getInfo($columnInfo['ColumnID']);
		}
		
		$avatarEditable = 1;
		$descriptionEditable = 1;
		$tempDate = date('Y-m-d H:i:s',strtotime('-1 month'));
		if($columnInfo['AvatarUpdateTime'] >$tempDate){
			$avatarEditable = 0;
		}

		if($columnInfo['DescriptionUpdateTime'] >$tempDate){
			$descriptionEditable = 0;
		}

		$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
		$qualificationArr = array();
		$isset = 0;
		if(!empty($authenticateInfo)){
			$MobileNumber = $authenticateInfo['MobileNumber'];
			$AuthenticateType = $authenticateInfo['AuthenticateType'];
			$AuthenticateID = $authenticateInfo['AuthenticateID'];
			if($authenticateInfo['AuthenticateType']==1){
				$Subject = $authenticateInfo['OperatorName'];
			}elseif($authenticateInfo['AuthenticateType']==2){
				$Subject = $authenticateInfo['OperatorName'];
	            $qualificationArr = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],null,1);
	            foreach ($qualificationArr as $item) {
	            	if($item['IsDisplayInPersonalProfile']==1){
						$isset = $item['FinancialQualificationID'];
	            	}
	            }
			}elseif ($authenticateInfo['AuthenticateType']==3) {
				$Subject = $authenticateInfo['BusinessName'];
			}elseif($authenticateInfo['AuthenticateType']==4){
				$Subject = $authenticateInfo['OrganizationName'];
			}
		}

		$this->view->columnInfo=$columnInfo;
		$this->view->focus = $focusArr;
		$this->view->UserName = $this->memberInfo->UserName;
		$this->view->Subject = isset($Subject)?$Subject:'';
		$this->view->AuthenticateType =isset($AuthenticateType)?$AuthenticateType:1;
		$this->view->MobileNumber = isset($MobileNumber)?$MobileNumber:'';
		$this->view->AuthenticateID = isset($AuthenticateID)?$AuthenticateID:0;
		$this->view->qualificationArr = $qualificationArr;
		$this->view->avatarEditable = $avatarEditable;
		$this->view->descriptionEditable = $descriptionEditable;
		$this->view->isset = $isset;
	}

	//帐号详情
	public function accountDetailAction()
	{
		$this->view->headTitle("财猪 - 帐号详情");
        $memberID = $this->memberInfo->MemberID;
        $authenticateModel =new Model_Authenticate();
        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1);
        $accountDetail=array();
        if(!empty($authenticateInfo)){
        	$accountDetail['AuthenticateType']= $authenticateInfo['AuthenticateType'];
            $accountDetail['Direction'] = $authenticateInfo['Direction'];
            $accountDetail['DataTime'] = $authenticateInfo['DataTime'];
             if($authenticateInfo['AuthenticateType']== 1 || $authenticateInfo['AuthenticateType']== 2 ){
                $accountDetail['OperatorName'] = $authenticateInfo['OperatorName'];
                $accountDetail['IDCard'] = $authenticateInfo['IDCard'];
            }elseif($authenticateInfo['AuthenticateType']== 3){
                $accountDetail['BusinessName'] = $authenticateInfo['BusinessName'];
                $accountDetail['BusinessLicenseNumber'] = $authenticateInfo['BusinessLicenseNumber'];
                $accountDetail['FoundedTime'] = $authenticateInfo['FoundedTime'];                
            }elseif($authenticateInfo['AuthenticateType']== 4){
                $accountDetail['OrganizationName'] = $authenticateInfo['OrganizationName'];
                $accountDetail['OrganizationCode'] = $authenticateInfo['OrganizationCode'];
                $accountDetail['FoundedTime'] = $authenticateInfo['FoundedTime'];
            }
        }
        $this->view->accountDetail=$accountDetail;
	}

	//添加资质
	public function addQualificationAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
	        $authenticateModel =new Model_Authenticate();
	        $qualificationModel = new Model_Qualification();
	        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
	        if(empty($authenticateInfo) || $authenticateInfo['Status']!=1){
				$this->returnJson(false, "认证通过之后才能添加资质！");
	        }
	        $authId = $this->_getParam("authId",0);

		 	if($authId!=$authenticateInfo['AuthenticateID']){
				$this->returnJson(false, "参数错误！");
			}
		

		 	
		 	$pic = $this->_getParam("pic",'');
		 	if(empty($pic)){
				$this->returnJson(false, "请上传资格证图片！");
		 	}

		 	$type = $this->_getParam("type",'');
		 	if($type=='custom'){
				$type=$this->_getParam("defined",'');
			}

		 	$year = $this->_getParam("year",'');
		 	$month = $this->_getParam("month",'');
		 	$date = date('Y-m-d',mktime(0,0,0,$month,1,$year));
			
			if(empty($type)){
				$this->returnJson(false, "请选择资格证类型！");
			}
			$info = $qualificationModel->isExist($authId,$type);
			if(!empty($info)){
				if($info['CheckStatus']==1){
					$this->returnJson(false, "该资质已经添加！");
				}else{
					$this->returnJson(false, "该资质处于待审核阶段，请耐心等待！");
				}

			}

			if(empty($date)){
				$this->returnJson(false, "请选择资格证获得时间！");
			}

		 	$addParam = array(
			 		'AuthenticateID'=>$authId,
			 		'FinancialQualificationImage'=>$pic,
			 		'FinancialQualificationType'=>$type,
			 		'QualificationGetTime'=>$date
		 		);

		  //   $qualificationArr = $qualificationModel->getInfoByqualificationID($authId,null,1);
		  //   if(count($qualificationArr)>=3){
				// $this->returnJson(false, "认证资质已达到上线！");
		  //   }
		    $qualificationModel->insert($addParam);
		    $this->returnJson(true, "添加成功,请等待审核！");
		}catch (Exception $e) {
				$this->returnJson(0, $e->getMessage());
			}
	}

	//修改头像
	public function updateAvatarAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$columnModel =new Model_Column_Column();
		$columnInfo = $columnModel->getMyColumnInfo($memberID);
		$columnID = $this->_getParam("columnID",0);
		
		$avatar = $this->_getParam("avatar",'');
		if(empty($avatar)){
			$this->returnJson(false, "请上传专栏头像！");
		}

	 	if($columnID!=$columnInfo['ColumnID']){
			$this->returnJson(false, "参数错误！");
		}

		$tempDate = date('Y-m-d H:i:s',strtotime('-1 month'));
		if($columnInfo['AvatarUpdateTime'] >$tempDate){
			$this->returnJson(false, "一个月只能修改一次！");
		}
		$columnModel->update(array('Avatar'=>$avatar,'AvatarUpdateTime'=>date('Y-m-d H:i:s',time())),array('ColumnID = ?'=>$columnID));
		$redisObj = DM_Module_Redis::getInstance();
		$key = 'columnInfo:columnID'.$columnID;
		$redisObj->DEl($key);
		$this->returnJson(1, "修改成功！");

	}


	/**
     * 设置个人资料页显示的资质
     */
    public function setQualificationAction()
    {

		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$memberID = $this->memberInfo->MemberID;
			$qualificationID = intval($this->_getParam('qualificationID',0));

	        if($qualificationID <= 0){
	            $this->returnJson(0,'请选择一个资质！');
	        }

    		$qualificationModel =  new Model_Qualification();
			$qualificationInfo = $qualificationModel->getInfoByID($qualificationID);
			if(empty($qualificationInfo) || $qualificationInfo['CheckStatus'] !=1){
				$this->returnJson(0,'您选择的资质不存在！');
			}
		
	    	$authenticateModel = new Model_Authenticate();
	    	$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1,'AuthenticateID');
	    	//var_dump($authenticateInfo);exit;
	        $qualificationModel->update(array('IsDisplayInPersonalProfile'=>1), array('FinancialQualificationID = ?'=>$qualificationID));
	        $qualificationModel->update(array('IsDisplayInPersonalProfile'=>0), array('FinancialQualificationID != ?'=>$qualificationID,'AuthenticateID =?'=>$authenticateInfo['AuthenticateID']));
	        $financialInfoModel = new Model_Financial_FinancialPlannerInfo();
	        $financialInfo = $financialInfoModel->getFinancialInfoByMemberID($memberID);
	        if(!empty($financialInfo)){
				$financialInfoModel->update(array('QualificationType'=>$qualificationInfo['FinancialQualificationType']),array('FPID = ?'=>$financialInfo['FPID']));
	        }

	        $this->returnJson(1); 
	    }
    }

	/*
	 *获取理财师扩展资料
	 */
	public function getFinancialInformationAction()
	{
		$memberID = $this->memberInfo->MemberID;
        $financialInfoModel = new Model_Financial_FinancialPlannerInfo();
		$authenticateModel =new Model_Authenticate();
		$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
        if(empty($authenticateInfo) || $authenticateInfo['Status']!=1 || $authenticateInfo['AuthenticateType'] !=2){
			$this->returnJson(false, "您还没有通过理财师认证或者您的帐号主体不是理财师！");
        }

		$financialInfo = $financialInfoModel->getFinancialInfoByMemberID($memberID);
		if(!empty($financialInfo)){
			$regionModel = new Model_Region();
            $name = $select = $regionModel->select()->from("region", 'Name')->where('Code = ?',$financialInfo['City'])->query()->fetchColumn();
            $financialInfo['CityName'] = !empty($name)?$name:'';
		}
		if(empty($financialInfo)){
			$financialInfo = array();
		}
		$this->returnJson(parent::STATUS_OK,'',$financialInfo);
	}


	/*
	 *编辑理财师扩展资料
	 */
	public function editFinancialInformationAction()
	{
        if($this->_request->isPost()){
        	$this->_helper->viewRenderer->setNoRender();
        	$memberID = $this->memberInfo->MemberID;
	        $authenticateModel =new Model_Authenticate();
	        $qualificationModel =  new Model_Qualification();
	        $columnModel = new Model_Column_Column();
	        $financialInfoModel = new Model_Financial_FinancialPlannerInfo();
			try{
				$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
		        if(empty($authenticateInfo) || $authenticateInfo['Status']!=1 || $authenticateInfo['AuthenticateType'] !=2){
		        	throw new Exception("请在通过理财师认证之后再来完善资料！");
		        }

		        $qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);
		        if(empty($qualificationInfo)){
		        	$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
		        }

				$qualificationType ='';
		        if(!empty($qualificationInfo)){
					$qualificationType = $qualificationInfo['FinancialQualificationType'];
		        }

				$columnInfo = $columnModel->getMyColumnInfo($memberID,1);
				if(empty($columnInfo)){
					throw new Exception("请在理财号通过之后再来完善资料！");
				}
				//$FPID = intval($this->_getParam("FPID",0));
				$Photo = trim($this->_getParam("Photo",''));
				$City = trim($this->_getParam("City",''));
				$Hobby = trim($this->_getParam("Hobby",''));
				$Character = trim($this->_getParam("Character",''));
				$Speciality = trim($this->_getParam("Speciality",''));
				$Profiles = trim($this->_getParam("Profiles",''));
				$Link = trim($this->_getParam("Link",''));
				$Institution = trim($this->_getParam("Institution",''));
				$Job = trim($this->_getParam("Job",''));
				$Seniority = intval($this->_getParam("Seniority",0));

				if(empty($Photo)){
					throw new Exception("形象照不能为空！");
				}
				$City = rtrim($City,',');
				if(empty($City)){
					throw new Exception("请选择常驻城市！");
				}

	            $updateArr = array(
	            		'MemberID' => $memberID,
		            	'Photo'=>$Photo,
		            	'City'=>$City,
		            	'Hobby'=>$Hobby,
		            	'Character'=>$Character,
		            	'Speciality'=>$Speciality,
		            	'Profiles'=>$Profiles,
		            	'Link'=>$Link,
		            	'Institution'=>$Institution,
		            	'Job'=>$Job,
		            	'Seniority'=>$Seniority,
		            	'QualificationType' => $qualificationType,
		            	'AuthenticateID'=>$authenticateInfo['AuthenticateID'],

	            	);
				$financialInfo = $financialInfoModel->getFinancialInfoByMemberID($memberID);
				if(empty($financialInfo)){
					$financialInfoModel->insert($updateArr);
				}else{
					$financialInfoModel->update($updateArr,array('FPID = ?'=>$financialInfo['FPID']));
				}
				$this->returnJson(1, "修改成功！");     
			}catch (Exception $e) {
				$this->returnJson(0, $e->getMessage());
			}
        }
    }

	//修改简介
	public function updateDescriptionAction()
	{

		//var_dump($columnInfo);exit;
		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$memberID = $this->memberInfo->MemberID;
			$columnModel =new Model_Column_Column();
			$columnInfo = $columnModel->getMyColumnInfo($memberID);

			$columnID = $this->_getParam("columnID",0);		
			$description = $this->_getParam("description",'');

			if(empty($description)){
				$this->returnJson(false, "简介内容不能为空！");
			}

		 	if($columnID!=$columnInfo['ColumnID']){
				$this->returnJson(false, "参数错误！");
			}

			$tempDate = date('Y-m-d H:i:s',strtotime('-1 month'));
			if($columnInfo['DescriptionUpdateTime'] >$tempDate){
				$this->returnJson(false, "一个月只能修改一次！");
			}
			$columnModel->update(array('Description'=>$description,'DescriptionUpdateTime'=>date('Y-m-d H:i:s',time())),array('ColumnID = ?'=>$columnID));
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'columnInfo:columnID'.$columnID;
			$redisObj->DEl($key);
			$this->returnJson(1, "修改成功！");
		}
	}
    
    /*
	理财师资料
	*/
	public function informationAction()
	{
		$this->view->headTitle("财猪 - 理财师资料");
	}
}