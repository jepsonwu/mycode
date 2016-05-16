<?php

class Member_AuthenticateController extends Action_Member
{
	public function init()
	{
		parent::init();
		//$this->isLoginOutput();
	}
	
    /*
     * (non-PHPdoc)
     * @see Zend_Controller_Action::__call()
     */
     public function __call($method,$params)
     {
         $this->_helper->viewRenderer->setNoRender();
         $fileName = substr($method,0,-6);
         if(in_array($fileName, array('person','financial','enterprise','organization'))){
             $tmpfileName = '/authenticate/'.$fileName.'.phtml';
             echo $this->view->render($tmpfileName);
         }
     }

	public function indexAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		echo "test";
	}
	//发送认证验证码
	public function sendCodeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$mobileNumber = trim($this->_getParam("MobileNumber",''));
		if (empty($mobileNumber) || !DM_Helper_Validator::checkmobile($mobileNumber) || !Model_Member::checkMobileFormat($mobileNumber)){
            $this->returnJson(parent::STATUS_FAILURE,'请输入正确的手机号！');
        }
        $VerifyModel = new DM_Model_Table_MemberVerifys();

        $count = $VerifyModel->getSendCount($mobileNumber);
        if($count >= 3 ){
            $this->returnJson(parent::STATUS_FAILURE,'同一手机号一天只能发送3次！');
        }
        $member_id=$this->memberInfo->MemberID;
        $code = $VerifyModel->createVerify(9,$mobileNumber, $member_id);

        if (!$code) {
            return false;
        }
   
        $message = "您好，您正在进行身份认证操作，手机验证码为：".$code->VerifyCode;
        $result = DM_Module_EDM_Phone::send($mobileNumber,$message);       
        return $result;  
	}

	/*
	 *获取认证信息
	 */
	public function getAuthenticateInfoAction()
	{
		$this->_helper->viewRenderer->setNoRender();
        
        $memberModel = new DM_Model_Account_Members();
        $bestModel = new Model_Best_Best();
        $memberID = $this->memberInfo->MemberID;
        $memberModel->deleteCache($memberID);
		$memberInfo = $memberModel->getMemberInfoCache($memberID, array('RealName','IDCard','IsBest'));
		
		$bestCount = $bestModel->countBestByMemberID($memberID,array(2,3));
		
        $authenticateModel = new Model_Authenticate();
        //$qualificationModel = new Model_Qualification();
        $authenticateType = $this->_getParam("AuthenticateType",1);

        if($authenticateType == 1 || $authenticateType == 2){
        	$field = "AuthenticateID,OperatorName,IDCard,MobileNumber,Status,Remark";
        }elseif($authenticateType == 3){
			$field = "AuthenticateID,BusinessName,FoundedTime,Province,City,Area,Address,BusinessLicenseNumber,OperatorName,IDCard,MobileNumber,Status,Remark";
        }elseif($authenticateType == 4){
        	$field = "AuthenticateID,OrganizationName,FoundedTime,Province,City,Area,Address,OrganizationCode,OperatorName,IDCard,MobileNumber,Status,Remark";
        }
        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,null,$field);

        if(!empty($memberInfo['RealName'])){
            $authenticateInfo['OperatorName'] = $memberInfo['RealName'];
        }
        if(!empty($memberInfo['IDCard'])){
            $authenticateInfo['IDCard'] = $memberInfo['IDCard'];
        }

		$this->returnJson(parent::STATUS_OK,'',array('BestCount'=>$bestCount,'AuthenticateInfo' => $authenticateInfo));
	
	}



	// /*
	//  *获取认证信息
	//  */
	// public function getColumnInfoAction()
	// {
	// 	$this->_helper->viewRenderer->setNoRender();      
 //        $memberID = $this->memberInfo->MemberID;
	// 	$focusModel = new Model_Focus();
	// 	$fieldsArr = array('FocusID','FocusName');
	// 	$select = $focusModel->select()->from('focus',$fieldsArr)->where('IsTopicFocus = ?',1);
	// 	$focusArr = $select->query()->fetchAll();

	// 	$columnModel = new Model_Column_Column();
	// 	$fModel = new Model_Column_ColumnFocus();
	// 	$columnField = "ColumnID,Title,Avatar,Description,CheckStatus";
	// 	$columnInfo = $columnModel->getMyColumnInfo($memberID,0,$columnField);

	// 	$selectFocus = array();
	// 	if(!empty($columnInfo)){
	// 		$focusID = $fModel->getInfo($columnInfo['ColumnID']);
	// 		foreach($focusID as $value){
	// 			$selectFocus[] = $value['FocusID'];
	// 		}
	// 	}
	// 	foreach ($focusArr as &$item) {
	// 		if(in_array($item['FocusID'], $selectFocus)){
	// 			$item['IsSelected']=1;
	// 		}else{
	// 			$item['IsSelected']=0;
	// 		}

	// 	}	
	// 	$this->returnJson(parent::STATUS_OK,'',array('ColumnInfo'=>$columnInfo,'FocusArr'=>$focusArr));
	// }

	/*
	 *提交认证信息
	 */
	public function addAuthAction()
	{
		$this->_helper->viewRenderer->setNoRender();
        if($this->_request->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			try {
				$memberID = $this->memberInfo->MemberID;
				$memberModel = new DM_Model_Account_Members();
				$authenticateModel = new Model_Authenticate();
				$qualificationModel = new Model_Qualification();
	        	$columnModel = new Model_Column_Column();
	        	$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
		        $columnStatus =-1;
		        $columnInfo = $columnModel->getMyColumnInfo($memberID);
		        if(!empty($columnInfo)){
		        	$columnStatus = $columnInfo['CheckStatus'];
		        }

		        if( !empty($authenticateInfo) && in_array($authenticateInfo['Status'], array(0,1))){
		        	$this->returnJson(1,"您的认证信息审核中，或已审核通过！",array('authID'=>0,'quaID'=>0,'columnStatus'=>$columnStatus));
		        	//throw new Exception("您的认证信息审核中，或已审核通过！");
		        }      
				//认证信息
				if(empty($authenticateInfo) || $authenticateInfo['Status']==2){
					$authenticateParam = array();
		            $authenticateType = intval($this->_getParam("AuthenticateType",1));
		            if(!in_array($authenticateType, array(1,2,3,4))){
		            	throw new Exception("认证类型错误！");
		            }

		            $OperatorName = trim($this->_getParam("OperatorName",''));
					$IDCard = trim($this->_getParam("IDCard",''));
					$IDPhoto = trim($this->_getParam("IDPhoto",''));
					$MobileNumber = trim($this->_getParam("MobileNumber",''));
		        	$code = trim($this->_getParam("code",''));
		        	
		        	if($authenticateType !=1){
						$bestModel = new Model_Best_Best();
						$bestCount = $bestModel->countBestByMemberID($memberID,array(2,3));
						if($bestCount>0){
							throw new Exception("请先联系客服撤销达人头衔！");
						}
		        	}

					if($authenticateType ==3){
			            $BusinessName = trim($this->_getParam("BusinessName",''));
			        	$FoundedTime = trim($this->_getParam("FoundedTime",''));			        	
			        	$location_p = trim($this->_getParam("location-p",''));
						$location_c = trim($this->_getParam("location-c",''));
						$Address = trim($this->_getParam("Address",''));
						$BusinessLicenseNumber = trim($this->_getParam("BusinessLicenseNumber",''));
			            $BusinessLicenseImage = trim($this->_getParam("BusinessLicenseImage",''));
            			
            			if(empty($BusinessName)){
        					throw new Exception("请输入企业名称！");
						}
						if(empty($FoundedTime) || $FoundedTime=='0000-00-00'){
							throw new Exception("请选择企业成立日期！");
						}
						if(empty($BusinessLicenseNumber)){
							throw new Exception("请输入营业执照注册号！");
						}
						$businessCount = $authenticateModel->getCountByBusinessLicenseNumber($BusinessLicenseNumber);
						if($businessCount >=5){
							throw new Exception("该营业执照认证数量已达到上限！");
						}
						if(empty($BusinessLicenseImage)){
							throw new Exception("请上传营业执照扫描件！");
						}
						$authenticateParam = array(
								'BusinessName'=> $BusinessName,
								'FoundedTime'=>$FoundedTime,
								'Province'=> $location_p,
								'City'=>$location_c,
								'Address'=> $Address,
								'BusinessLicenseNumber'=> $BusinessLicenseNumber,
								'BusinessLicenseImage'=>$BusinessLicenseImage,
								'CityCode'=>DM_Module_Region::getCityCode($location_c,!empty($location_p) ? $location_p : '')
							);
					}
					if($authenticateType ==4){
			            $OrganizationName = trim($this->_getParam("OrganizationName",''));
			        	$FoundedTime = trim($this->_getParam("FoundedTime",''));
			        	$location_p = trim($this->_getParam("location-p",''));
						$location_c = trim($this->_getParam("location-c",''));
						$Address = trim($this->_getParam("Address",''));
						$OrganizationCode = trim($this->_getParam("OrganizationCode",''));
			            $OrganizationImage = trim($this->_getParam("OrganizationImage",''));

            			if(empty($OrganizationName)){
            				throw new Exception("请输入机构名称！");
						}					
						if(empty($FoundedTime) || $FoundedTime=='0000-00-00'){
            				throw new Exception("请选择机构成立日期！");
						}
						if(empty($OrganizationCode)){
            				throw new Exception("请输入组织机构代码！");
						}
						$organizationCount = $authenticateModel->getCountByOrganizationCode($OrganizationCode);
						if($organizationCount >=5){
            				throw new Exception("该组织机构代码认证数量已达到上限！");
						}						
						if(empty($OrganizationImage)){
            				throw new Exception("请上传组织机构代码扫描件！");
						}
						$authenticateParam = array(
								'OrganizationName'=> $OrganizationName,
								'FoundedTime'=>$FoundedTime,
								'Province'=> $location_p,
								'City'=>$location_c,
								'Address'=> $Address,
								'OrganizationCode'=> $OrganizationCode,
								'OrganizationImage'=>$OrganizationImage,
								'CityCode'=>DM_Module_Region::getCityCode($location_c,!empty($location_p) ? $location_p : '')
							);
					}

					if(empty($OperatorName)){
						throw new Exception("请输入姓名！");
					}
	        		$memberInfo = $memberModel->getMemberInfoCache($memberID, array('RealName','IDCard'));
					if(!empty($memberInfo['RealName']) && $OperatorName!=$memberInfo['RealName']){
						throw new Exception("您输入的姓名请和真实姓名保持一致！");
					}

					if(empty($IDCard)){
						throw new Exception("请输入身份证号码！");
					}
					if(!empty($memberInfo['IDCard']) && $IDCard != $memberInfo['IDCard']){
						throw new Exception("您输入的身份证号请和真实身份证号保持一致！");
					}

					$IDCount = $authenticateModel->getCountByIDCard($IDCard);
					if($IDCount >=5){
						throw new Exception("该身份证认证数量已达到上限！");
					}

					if(empty($IDPhoto)){
						throw new Exception("请上传身份证照片！");
					}

			        if (empty($MobileNumber) || !DM_Helper_Validator::checkmobile($MobileNumber) || !Model_Member::checkMobileFormat($MobileNumber)){
						throw new Exception("请输入正确的手机号！");
			        }

			        if($authenticateType ==2){
						$FinancialQualificationImage = trim($this->_getParam("FinancialQualificationImage",''));
						$tmp_Type = trim($this->_getParam("FinancialQualificationType",''));

						if($tmp_Type=="custom"){
							$FinancialQualificationType =trim($this->_getParam("customType",''));
						}else{
							$FinancialQualificationType =$tmp_Type;
						}

						$year = trim($this->_getParam("year",''));
						$month = trim($this->_getParam("month",''));

						$QualificationGetTime = date('Y-m-d',mktime(0,0,0,$month,1,$year));
			        	//$qualificationGetTime = trim($this->_getParam("qualificationGetTime",''));
						
						if(empty($FinancialQualificationImage)){
							throw new Exception("请上传资格证扫描件！");
						}

						if(empty($FinancialQualificationType)){
							throw new Exception("请选择资格证类型！");
						}

						if(empty($QualificationGetTime)){
							throw new Exception("请选择资格证获得时间！");
						}

			            $qualificationParam = array(
			            		'FinancialQualificationImage'=>$FinancialQualificationImage,
			            		'FinancialQualificationType'=>$FinancialQualificationType,
			            		'QualificationGetTime'=>$QualificationGetTime,
			            		'CheckStatus' => 0,
			            	);
					}

					$authenticateParam['AuthenticateType'] = $authenticateType;
					$authenticateParam['MemberID'] = $memberID;
					$authenticateParam['OperatorName'] = $OperatorName;
					$authenticateParam['IDCard'] = $IDCard;
					$authenticateParam['IDPhoto'] = $IDPhoto;
					$authenticateParam['MobileNumber'] = $MobileNumber;
					$authenticateParam['Status'] = 0;

	    			if(empty($code)){
	    				throw new Exception("请输入验证码！");
					}else{
			            $verifyTable = new DM_Model_Table_MemberVerifys();
			            $paramArray = array('VerifyCode'=>$code,'IdentifyID'=>$MobileNumber,'VerifyType'=>9,'MemberID'=>$memberID,'Status'=>'Pending');
			            $verifyinfo = $verifyTable->getVerify($paramArray);

			            if(!$verifyinfo){
			            	throw new Exception("验证码错误，请重新输入！");
			            }

			            if(time() > strtotime($verifyinfo['ExpiredTime'])){
			            	throw new Exception("验证码已过期！");
			            }
			            if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
			            	throw new Exception("验证码验证失败！");
			            }
			        }	        
				}
				if($columnStatus == 0||$columnStatus == 1){
					if(empty($authenticateInfo)){
						if($authID = $authenticateModel->add($authenticateParam)){
							if($authenticateType == 2){
								$qualificateInfo = $qualificationModel->getInfoByqualificationID($authID,1);
								$qualificationParam['AuthenticateID'] = $authID;
								if(!empty($qualificateInfo)){
									$qualificationModel->update($qualificationParam,array('FinancialQualificationID = ?'=>$qualificateInfo['FinancialQualificationID']));
								}else{
									$qualificationModel->insert($qualificationParam);
								}
							}

						}
					}elseif(!empty($authenticateInfo) && $authenticateInfo['Status']==2){
						if($authenticateInfo['FailuresNum'] >=3){
			            	throw new Exception("身份认证失败次数超过三次，无法再次认证！");
			            }
		                if($authenticateModel->edit($authenticateParam,$authenticateInfo['AuthenticateID'])){
		                	if($authenticateType == 2){
		                		$qualificationParam['AuthenticateID'] = $authenticateInfo['AuthenticateID'];
		                		$qualificateInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'], 1);
		                		if(!empty($qualificateInfo)){
		                			$qualificationModel->update($qualificationParam,array('FinancialQualificationID = ?'=>$qualificateInfo['FinancialQualificationID']));				                	
		                		}else{
		                			$qualificationModel->insert($qualificationParam);
		                		}
							}         	
		                }       
		            }
		            $this->returnJson(1,'',array('authID'=>0,'quaID'=>0,'columnStatus'=>$columnStatus));
				}else{

					$authenticateTempModel = new Model_AuthenticateTemp();
					$qualificationTempModel = new Model_QualificationTemp();
					$authenticateTempModel->getAdapter()->beginTransaction();
					
					$authID =0;
					$quaID = 0;
					$flag = 0;
					$msg = '主体信息提交失败！';
		            if(empty($authenticateInfo)||$authenticateInfo['Status']==2){
			            if(!empty($authenticateInfo) && $authenticateInfo['FailuresNum'] >=3){
			            	throw new Exception("身份认证失败次数超过三次，无法再次认证！");
			            }

		                if($authID = $authenticateTempModel->add($authenticateParam)){
		                	$flag = 1;
		                	if($authenticateType == 2){
	    	                	$qualificationParam['AuthenticateID'] = $authID;
	                			if($quaID = $qualificationTempModel->insert($qualificationParam)){
									$authenticateTempModel->getAdapter()->commit();
	                			}else{
	                				$flag = 0;
	                				$msg = '资质信息提交失败！';
	                				$authenticateTempModel->getAdapter()->rollBack();
	                			}
		                	}else{
		                		$authenticateTempModel->getAdapter()->commit();
		                	}
		                }
		            }
				}

                $this->returnJson($flag,$msg,array('authID'=>$authID,'quaID'=>$quaID,'columnStatus'=>$columnStatus));
			}catch (Exception $e) {
				$this->returnJson(0, $e->getMessage());
			}
        }
	}



	/*
	 *创建理财号
	 */
	public function addColumnAction()
	{

		$this->_helper->viewRenderer->setNoRender();
        if($this->_request->isPost() ||1){
			//$this->_helper->viewRenderer->setNoRender();
			try {
				$memberID = $this->memberInfo->MemberID;
				$memberModel = new DM_Model_Account_Members();
				$authenticateModel = new Model_Authenticate();
				$qualificationModel = new Model_Qualification();
	        	$columnModel = new Model_Column_Column();
	        	$focusModel = new Model_Column_ColumnFocus();
	        	$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
		        $columnInfo = $columnModel->getMyColumnInfo($memberID);

		        if(!empty($columnInfo) && !empty($columnInfo) && in_array($authenticateInfo['Status'], array(0,1)) && in_array($columnInfo['CheckStatus'], array(0,1))){
		        	throw new Exception("您的理财号状态为审核中，或已审核通过！");
		        }      
				//认证信息
				if(empty($authenticateInfo) || $authenticateInfo['Status']==2){
					$authenticateParam = array();
		            $authenticateType = intval($this->_getParam("authenticateType",1));
		            if(!in_array($authenticateType, array(1,2,3,4))){
		            	throw new Exception("认证类型错误！");
		            }

		            $operatorName = trim($this->_getParam("operatorName",''));
					$IDCard = trim($this->_getParam("idCard",''));
					$IDPhoto = trim($this->_getParam("idPhoto",''));
					$mobileNumber = trim($this->_getParam("mobileNumber",''));
		        	$code = trim($this->_getParam("code",''));
		        	
		        	if($authenticateType !=1){
						$bestModel = new Model_Best_Best();
						$bestCount = $bestModel->countBestByMemberID($memberID,array(2,3));
						if($bestCount>0){
							throw new Exception("请先联系客服撤销达人头衔！");
						}
		        	}

					if($authenticateType ==3){
			            $businessName = trim($this->_getParam("businessName",''));
			        	$FoundedTime = trim($this->_getParam("foundedTime",''));			        	
			        	$location_p = trim($this->_getParam("location-p",''));
						$location_c = trim($this->_getParam("location-c",''));
						$address = trim($this->_getParam("address",''));
						$businessLicenseNumber = trim($this->_getParam("businessLicenseNumber",''));
			            $BusinessLicenseImage = trim($this->_getParam("businessLicenseImage",''));
            			
            			if(empty($businessName)){
        					throw new Exception("请输入企业名称！");
						}
						if(empty($FoundedTime) || $FoundedTime=='0000-00-00'){
							throw new Exception("请选择企业成立日期！");
						}
						if(empty($businessLicenseNumber)){
							throw new Exception("请输入营业执照注册号！");
						}
						$businessCount = $authenticateModel->getCountByBusinessLicenseNumber($businessLicenseNumber);
						if($businessCount >=5){
							throw new Exception("该营业执照认证数量已达到上限！");
						}
						if(empty($BusinessLicenseImage)){
							throw new Exception("请上传营业执照扫描件！");
						}
						$authenticateParam = array(
								'BusinessName'=> $businessName,
								'FoundedTime'=>$FoundedTime,
								'Province'=> $location_p,
								'City'=>$location_c,
								'Address'=> $address,
								'BusinessLicenseNumber'=> $businessLicenseNumber,
								'BusinessLicenseImage'=>$BusinessLicenseImage,
								'CityCode'=>DM_Module_Region::getCityCode($location_c,!empty($location_p) ? $location_p : '')
							);
					}
					if($authenticateType ==4){
			            $organizationName = trim($this->_getParam("organizationName",''));
			        	$FoundedTime = trim($this->_getParam("foundedTime",''));
			        	$location_p = trim($this->_getParam("location-p",''));
						$location_c = trim($this->_getParam("location-c",''));
						$address = trim($this->_getParam("address",''));
						$organizationCode = trim($this->_getParam("organizationCode",''));
			            $OrganizationImage = trim($this->_getParam("organizationImage",''));

            			if(empty($organizationName)){
            				throw new Exception("请输入机构名称！");
						}					
						if(empty($FoundedTime) || $FoundedTime=='0000-00-00'){
            				throw new Exception("请选择机构成立日期！");
						}
						if(empty($organizationCode)){
            				throw new Exception("请输入组织机构代码！");
						}
						$organizationCount = $authenticateModel->getCountByOrganizationCode($organizationCode);
						if($organizationCount >=5){
            				throw new Exception("该组织机构代码认证数量已达到上限！");
						}						
						if(empty($OrganizationImage)){
            				throw new Exception("请上传组织机构代码扫描件！");
						}
						$authenticateParam = array(
								'OrganizationName'=> $organizationName,
								'FoundedTime'=>$FoundedTime,
								'Province'=> $location_p,
								'City'=>$location_c,
								'Address'=> $address,
								'OrganizationCode'=> $organizationCode,
								'OrganizationImage'=>$OrganizationImage,
								'CityCode'=>DM_Module_Region::getCityCode($location_c,!empty($location_p) ? $location_p : '')
							);
					}

					if(empty($operatorName)){
						throw new Exception("请输入姓名！");
					}
	        		$memberInfo = $memberModel->getMemberInfoCache($memberID, array('RealName','IDCard'));
					if(!empty($memberInfo['RealName']) && $operatorName!=$memberInfo['RealName']){
						throw new Exception("您输入的姓名请和真实姓名保持一致！");
					}

					if(empty($IDCard)){
						throw new Exception("请输入身份证号码！");
					}
					if(!empty($memberInfo['IDCard']) && $IDCard != $memberInfo['IDCard']){
						throw new Exception("您输入的身份证号请和真实身份证号保持一致！");
					}

					$IDCount = $authenticateModel->getCountByIDCard($IDCard);
					if($IDCount >=5){
						throw new Exception("该身份证认证数量已达到上限！");
					}

					if(empty($IDPhoto)){
						throw new Exception("请上传身份证照片！");
					}

			        if (empty($mobileNumber) || !DM_Helper_Validator::checkmobile($mobileNumber) || !Model_Member::checkMobileFormat($mobileNumber)){
						throw new Exception("请输入正确的手机号！");
			        }

			        if($authenticateType ==2){
						$FinancialQualificationImage = trim($this->_getParam("qualificationImage",''));
						$tmp_Type = trim($this->_getParam("qualificationType",''));

						if($tmp_Type=="custom"){
							$qualificationType =trim($this->_getParam("customType",''));
						}else{
							$qualificationType =$tmp_Type;
						}

						// $year = trim($this->_getParam("year",''));
						// $month = trim($this->_getParam("month",''));

						// $qualificationGetTime = date('Y-m-d',mktime(0,0,0,$month,1,$year));
			        	$qualificationGetTime = trim($this->_getParam("qualificationGetTime",''));
						
						if(empty($FinancialQualificationImage)){
							throw new Exception("请上传资格证扫描件！");
						}

						if(empty($qualificationType)){
							throw new Exception("请选择资格证类型！");
						}

						if(empty($qualificationGetTime)){
							throw new Exception("请选择资格证获得时间！");
						}

			            $qualificationParam = array(
			            		'FinancialQualificationImage'=>$FinancialQualificationImage,
			            		'FinancialQualificationType'=>$qualificationType,
			            		'QualificationGetTime'=>$qualificationGetTime,
			            		'CheckStatus' => 0,
			            	);
					}

					$authenticateParam['AuthenticateType'] = $authenticateType;
					$authenticateParam['MemberID'] = $memberID;
					$authenticateParam['OperatorName'] = $operatorName;
					$authenticateParam['IDCard'] = $IDCard;
					$authenticateParam['IDPhoto'] = $IDPhoto;
					$authenticateParam['MobileNumber'] = $mobileNumber;
					$authenticateParam['Status'] = 0;

	    			if(empty($code)){
	    				throw new Exception("请输入验证码！");
					}else{
			            $verifyTable = new DM_Model_Table_MemberVerifys();
			            $paramArray = array('VerifyCode'=>$code,'IdentifyID'=>$mobileNumber,'VerifyType'=>9,'MemberID'=>$memberID,'Status'=>'Pending');
			            $verifyinfo = $verifyTable->getVerify($paramArray);

			            if(!$verifyinfo){
			            	throw new Exception("验证码错误，请重新输入！");
			            }

			            if(time() > strtotime($verifyinfo['ExpiredTime'])){
			            	throw new Exception("验证码已过期！");
			            }
			            if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
			            	throw new Exception("验证码验证失败！");
			            }
			        }	        
				}
			       	
		        //理财号信息
	    		if(empty($columnInfo) || $columnInfo['CheckStatus']==2){
	    			$columnParam = array();
					$title = trim($this->_getParam('title',''));
					$avatarUrl = trim($this->_getParam('avatar',''));
					$focusID = trim($this->_getParam('focusID',''));
					$description = str_replace(array("\r\n","\r","\n"), ' ', trim($this->_getParam('description','')));


	    			if(empty($title) || !DM_Helper_Validator::checkUsername($title)){
	    				throw new Exception("请输入正确的理财号名称！");
					}
					$sensitiveModel = new DM_Model_Account_Sensitive();
		            $sensitive = $sensitiveModel->getInfo($title);
			
		            $filter = $sensitiveModel->filter($title);
		            if (!empty($sensitive) || $filter ==1) {
	    				throw new Exception("请不要使用敏感词汇创建理财号！");
		            }

					$info = $memberModel->getByUsername($title);
					$cInfo = $columnModel->hasTitle($title);
					if((!empty($info) && $info['UserName'] != $this->memberInfo->UserName) || !empty($cInfo))
					{
						throw new Exception("该理财号已被占用！");
					}

					if(empty($avatarUrl)){
						throw new Exception("请上传理财号头像！");
					}

					if(empty($focusID)){
						throw new Exception("请选择理财号标签！");
					}
					$focusArr = explode(",", $focusID);
					if(count($focusArr)>3){
						throw new Exception("最多只能选择3个理财号标签！");
					}

					if(mb_strlen($description,'utf-8')>50 || mb_strlen($description,'utf-8')<10)
					{
						throw new Exception("请认真填写理财号简介，内容为10-50个字符！");
					}

					$columnParam = array(
								'Title'=>$title,
								'Avatar'=>$avatarUrl,
								'Description'=>$description,
								'MemberID'=>$memberID,
								'CheckStatus'=>0
						);
	    		}
			
		
		       	$flag = 0;
		        $msg = '创建失败！';
		        $auth = 0;     
	            
	            if(empty($authenticateInfo)){
					$authenticateModel->getAdapter()->beginTransaction();	
	                if($authID = $authenticateModel->add($authenticateParam)){
	                	if($authenticateType == 2){
    	                	$qualificationParam['AuthenticateID'] = $authID;
                			$qualificationModel->insert($qualificationParam);
	                	}
	                	$auth = 1;
	                	$flag = 1;
	                	$msg='提交成功,请耐心等待审核！';
	                }
	            }elseif($authenticateInfo['Status']==2){
					$authenticateModel->getAdapter()->beginTransaction();

		            if($authenticateInfo['FailuresNum'] >=3){
		            	throw new Exception("身份认证失败次数超过三次，无法再次认证！");
		            }
		            if($authenticateModel->edit($authenticateParam,$authenticateInfo['AuthenticateID'])){
		            	if($authenticateType == 2){
		            		$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1);
		            		if(!empty($qualificationInfo)){
								$qualificationModel->update($qualificationParam,array('FinancialQualificationID = ?'=>$qualificationInfo['FinancialQualificationID']));
		            		}else{
		            			$qualificationParam['AuthenticateID']= $authenticateInfo['AuthenticateID'];
		            			$qualificationModel->insert($qualificationParam);
		            		}
		            	}
						$auth = 1;
		            	$flag=1;
                		$msg='提交成功,请耐心等待审核！';
		            }
	            }


				if(empty($columnInfo)){
					if($columnID = $columnModel->insert($columnParam)){
						foreach ($focusArr as $value) {
							$focusModel->addFocus($columnID,$value);
						}
						$flag=1;
	                	$msg='提交成功,请耐心等待审核！';
	                	if($auth == 1){
							$authenticateModel->getAdapter()->commit();
	                	}
					}else{
						$flag = 0;
				        $msg = '创建失败！';
				        if($auth == 1){
							$authenticateModel->getAdapter()->rollBack();
				        }
					}				
	            }elseif($columnInfo['CheckStatus'] == 2){
            		if($columnModel->update($columnParam, array('ColumnID = ?'=>$columnInfo['ColumnID']))){
		 				$focusModel->delete(array('ColumnID = ?'=>$columnInfo['ColumnID']));
		 				foreach ($focusArr as $value) {
							$focusModel->addFocus($columnInfo['ColumnID'],$value);
						}
						$flag=1;
	                	$msg='提交成功,请耐心等待审核！';
				        if($auth == 1){
							$authenticateModel->getAdapter()->commit();
				        }
            		}else{
				       	$flag = 0;
				        $msg = '创建失败！';
				        if($auth == 1){
							$authenticateModel->getAdapter()->rollBack();
				        }
            		} 	

	            }else{
			        if($auth == 1){
						$authenticateModel->getAdapter()->commit();
			        }
	            }
                $this->returnJson($flag,$msg);
			}catch (Exception $e) {
				$this->returnJson(0, $e->getMessage());
			}
        }
	}
    
}