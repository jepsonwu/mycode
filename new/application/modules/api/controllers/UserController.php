<?php
/**
 * Api 接口
 *
 * 用户接口
 *
 * @author Mark
 *
 */
class Api_UserController extends Action_Api
{
    /**
     * 用户注册第一步--发送验证码
     *
     * @Privilege user::register 注册
     */
    public function getRegCodeAction()
    {
    	if(APPLICATION_ENV == 'development'){
    		$this->returnJson(parent::STATUS_OK,'验证码发送成功！');
    	}
        $mobile = trim($this->_getParam('mobile', ''));
        if (!$mobile || !DM_Helper_Validator::checkmobile($mobile) || !Model_Member::checkMobileFormat($mobile)){
            $this->returnJson(parent::STATUS_FAILURE,'请输入正确的手机号！');
        }
        $memberModel = new DM_Model_Account_Members();
        $memberInfo = $memberModel->getByMobile($mobile);
        if (!empty($memberInfo)) {
            $this->returnJson(parent::STATUS_FAILURE,'该手机号码已被注册，请换一个！');
        }
        $result = $this->sendRegCodeCall($mobile);
        //$result = '{"flag":true,"msg":"d37791fa24e526cfde1169ed945d8a9e"}'
        if(strpos($result, 'true') !== false){
           $this->returnJson(parent::STATUS_OK,'验证码发送成功！');
        }else{
             $this->returnJson(parent::STATUS_FAILURE,$result);
        }

    }


    /**
     *
     * 参数username、password、email、mobile等
     *
     * @Privilege user::register 注册
     */
    public function registerAction()
    {
        $db=$this->getDb('udb');
        $this->_request->setParam('mode', 'mobile');
        try {
        		$inviteCode = $this->_request->getParam('inviteCode','');
        		if(empty($inviteCode)){
        			//throw new Exception('邀请码不能为空');
        		}

            $db->beginTransaction();

            $password = trim($this->_getParam('password',''));
            $isEncrypt = $this->_getParam('isEncrypt',1);//密码是否加密过
            if($isEncrypt){
	            $aesModel = new Model_CryptAES();
	            $password = $aesModel->decrypt($password);
            }else{
            	$guess = trim($this->_getParam('guess',''));
            	$fromPlatform = trim($this->_getParam('fromPlatform',''));
            }
            $this->_request->setParam('password',$password);
            $user=$this->registerCall(true,false);

            $memberID = $user->MemberID;
            	//更新FromSystem
            $memberModel = new DM_Model_Account_Members();
            $memberModel->update(array('FromSystem'=>1),array('MemberID = ?'=>$memberID));
            $db->commit();

            $memberInfo = $memberModel->getById($memberID);
            $memberModel->registerIMUser($memberInfo);
            $data['MemberID'] = $memberID;
            /*
            $data['UserName'] = $memberInfo['UserName'];

            $data['NeedFillName'] = 1;
            $data['IMUserName'] = $memberInfo['IMUserName'];
            $data['IMPassword'] = $memberInfo['IMPassword'];
            $data['MobileNumber'] = $memberInfo['MobileNumber'];
            $data['Province'] = $memberInfo['Province'];
            $data['City'] = $memberInfo['City'];
            $data['Signature'] = $memberInfo['Signature'];
            $data['Avatar'] = $memberInfo['Avatar'];
            $data['Focus'] = array();
            */

            $this->addExtraInfo($data);
            //统计每天注册数量
             if($memberID>0){
             	$redisObj = DM_Module_Redis::getInstance();
             	$key = 'RegisterNum:date:'.date('Y-m-d');
             	$redisObj->INCR($key);
             	$time = strtotime(date('Y-m-d',strtotime('+1 day')))-time()+7200;
             	$redisObj->EXPIRE($key,$time);
             	//保存猜股票涨跌的结果
//              	if(isset($guess) && !empty($guess)){
//              		$stockModel = new Model_StockGuess();
//              		$stockModel->addInfo($memberID, $guess);
//              	}
             	//扫码注册送流量活动
             	
             	$mobile = trim($this->_getParam('mobile', ''));
             	$flowModel = new Model_FlowActivity();
             	$style = $flowModel->getMobileStyle($mobile);
             	$flowSize = $style == 2?20:10;
             	$flowModel->addInfo($memberID,$mobile,$flowSize,2);
             	
             }

            $this->returnJson(parent::STATUS_OK,'注册成功',$data);

        }catch (Exception $e){
            $db->rollBack();
            $result=array('msg'=>$e->getMessage());
            $this->returnResult($result);
        }
    }

    /**
     * 用户登录
     *
     * 通过user( 邮箱或已验证手机)、password登录。
     *
     * @Privilege user::login 用户登录
     */
    public function loginAction()
    {
    	$code = trim($this->_getParam('code', ''));
    	if(empty($code)){
    		$version = $this->_request->getParam('currentVersion','1.0.0');
    		$platform = intval($this->_request->getParam('platform',1));
    		if(($platform === 1 && version_compare($version, '1.1.1') >= 0) || ($platform === 2 && version_compare($version, '1.1.1') >= 0)){
    			$this->checkAccountAction();
    		}
    	}

    	$password = trim($this->_getParam('password',''));
    	$aesModel = new Model_CryptAES();
    	$password = $aesModel->decrypt($password);
    	$this->_request->setParam('password',$password);
    	$this->_request->setParam('user', $this->_request->getParam('account',''));
        $result = $this->loginCall(true);
        if($result['flag'] == 1){
            $memberID = $result['data']['MemberID'];
            $memberModel = new DM_Model_Account_Members();
            //$memberFocusModel = new Model_MemberFocus();

            $memberInfo = $memberModel->getById($memberID);
            $memberModel->registerIMUser($memberInfo);
			/*  
            $result['data']['UserName'] = $memberInfo['UserName'];
            $result['data']['MemberID'] = $memberID;

            
            $result['data']['NeedFillName'] = 0;
            $result['data']['IMUserName'] = $memberInfo['IMUserName'];
            $result['data']['IMPassword'] = $memberInfo['IMPassword'];
            $result['data']['MobileNumber'] = $memberInfo['MobileNumber'];
            
            $result['data']['Province'] = $memberInfo['Province'];
            $result['data']['City'] = $memberInfo['City'];
            $result['data']['Signature'] = $memberInfo['Signature'];
            $result['data']['Avatar'] = $memberInfo['Avatar'];
            $result['data']['Focus'] = $memberFocusModel->getFocusInfo($memberID,null,'FocusID');
            
            if(empty($memberInfo['UserName'])){
            	$result['data']['NeedFillName'] = 1;
            }else{
            	$focusModel = new Model_MemberFocus();
            	if(!$focusModel->hasFocusInfo($memberID)){
            		$result['data']['NeedFillName'] = 1;
            	}
            }
            */
           $this->addExtraInfo($result['data']);
        }
        $this->returnResult($result);
    }


    /**
     * 会员信息返回字段
     * @param [type] &$info [description]
     */
    private function addExtraInfo(&$info)
    {
        $memberModel = new DM_Model_Account_Members();
        $memberFocusModel = new Model_MemberFocus();
        $memberID = $info['MemberID'];
        $memberInfo = $memberModel->getById($memberID);
        $info['UserName'] = $memberInfo['UserName'];
        $info['NeedFillName'] = 0;
        $info['Email'] = $memberInfo['Email'];
        $info['MobileNumber'] = $memberInfo['MobileVerifyStatus'] == 'Verified' ? $memberInfo['MobileNumber'] : '';
        $info['IMUserName'] = $memberInfo['IMUserName'];
        $info['IMPassword'] = $memberInfo['IMPassword'];
        $info['Province'] = $memberInfo['Province'];
        $info['City'] = $memberInfo['City'];
        $info['Signature'] = $memberInfo['Signature'];
        $info['Avatar'] = $memberModel->getMemberAvatar($memberID);
        $focus = $memberFocusModel->getFocusInfo($memberID,null,'FocusID');
        $info['Focus'] = count($focus)>0 ? $focus : array();
        $info['Gender'] = $memberInfo['Gender'];
        $info['Cover'] = $memberInfo['Cover'];
        $info['IsProtect'] = $memberInfo['IsProtect'];
        $info['IsAccountSearchable'] = $memberInfo['IsAccountSearchable'];
        $info['IsMobileSearchable'] = $memberInfo['IsMobileSearchable'];
        $info['IsShowContactList'] = $memberInfo['IsShowContactList'];

        $info['RealName'] = $memberInfo['RealName'];
        $info['IDCard'] = !empty($memberInfo['IDCard']) ? substr($memberInfo['IDCard'],0,3).'*************'.substr($memberInfo['IDCard'], -2) : '';
        
        if(empty($memberInfo['UserName'])){
            $info['NeedFillName'] = 1;
        }else{
            //$focusModel = new Model_MemberFocus();
            if(!$memberFocusModel->hasFocusInfo($memberID)){
                $info['NeedFillName'] = 1;
            }
        }
        $authenticateType = 0;
        $authenticateModel =new Model_Authenticate();
        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1);
        if(!empty($authenticateInfo)){
        	$authenticateType = $authenticateInfo['AuthenticateType'];
        }
        $info['AuthenticateType'] = $authenticateType;  //认证类型（1：个人，2：理财师，3：企业，4：机构）
    }


    /**
     * 检测是否在常用设备登陆
     */
    private function checkAccountAction()
    {
        // $this->isPostOutput();
        $user = trim($this->_getParam('account', ''));
        $this->_request->setParam('user', $user);
        $password = trim($this->_getParam('password',''));
        $deviceID = trim($this->_getParam('deviceID',''));


        $aesModel = new Model_CryptAES();
        $password = $aesModel->decrypt($password);

        if(empty($user)){
            $this->returnJson(parent::STATUS_FAILURE, '请输入帐号名！');
        }
        if(empty($password)){
            $this->returnJson(parent::STATUS_FAILURE, '请输入密码！');
        }
//         if(empty($deviceID)){
//             $this->returnJson(parent::STATUS_FAILURE, '未传设备号！');
//         }

        $userModel=new DM_Model_Account_Members();
        if (DM_Helper_Validator::isEmail($user)){
            $userInfo=$userModel->getByEmail($user);
            //$this->returnJson(parent::STATUS_FAILURE, '邮箱不允许登录，请使用帐号名！');
        }elseif(DM_Helper_Validator::checkmobile($user)){
           $userInfo= $userModel->getByMobile($user);
           //$this->returnJson(parent::STATUS_FAILURE, '手机号不允许登录，请使用帐号名！');
        }elseif(DM_Helper_Validator::checkUsername($user)){
            $userInfo= $userModel->getByUsername($user);
        }else{
            $userInfo = array();
            $userInfo= $userModel->getByUsername($user);
        }

        if(empty($userInfo)){
            $this->returnJson(parent::STATUS_FAILURE, '用户名或密码错误！');
        }
        if ($password!==NULL && $userInfo->Password!==$userInfo->encodePassword($password)) {
            $this->returnJson(parent::STATUS_FAILURE, '用户名或密码错误！');
        }

        if ($userInfo->DeviceID != $deviceID && $userInfo->MobileVerifyStatus=='Verified' && $userInfo->IsProtect==1){
            $memberVerify = new DM_Model_Table_MemberVerifys();

            $count = $memberVerify->getSendCount($userInfo->MobileNumber);
            if($count >= 3 ){
                $this->returnJson(self::STATUS_FAILURE,'同一手机号一天只能发送3次！');
            }
            $code = $memberVerify->createVerify(6,$userInfo->MobileNumber, $userInfo->MemberID);

            if (!$code) {
                return false;
            }

            $message = "您好，您正在不常用设备登录，手机验证码为：".$code->VerifyCode;
            $result = DM_Module_EDM_Phone::send($userInfo->MobileNumber,$message);
            if(strpos($result, 'true') !== false){
               $this->returnJson(-101,'验证码发送成功！');
            }else{
                 $this->returnJson(parent::STATUS_FAILURE,$result);
            }
        }else{
            //$this->returnJson(parent::STATUS_OK, '验证成功，继续登陆！');
            return true;
        }
    }
    /**
     * 获取当前登录信息
     */
    public function getLoginInfoAction()
    {
    	$this->isLoginOutput();
    	$memberID = $this->memberInfo->MemberID;
    	//$memberModel = new DM_Model_Account_Members();
        //$memberFocusModel = new Model_MemberFocus();
    	//$memberInfo = $memberModel->getById($memberID);

    	//$result['UserName'] = $memberInfo['UserName'];
        $result['MemberID'] = $memberID;

        /*
        $result['Email'] = $memberInfo['Email'];
        $result['MobileNumber'] = $memberInfo['MobileVerifyStatus'] == 'Verified' ? $memberInfo['MobileNumber'] : '';

    	$result['NeedFillName'] = 0;
    	$result['IMUserName'] = $memberInfo['IMUserName'];
    	$result['IMPassword'] = $memberInfo['IMPassword'];
        $result['Province'] = $memberInfo['Province'];
        $result['City'] = $memberInfo['City'];
        $result['Signature'] = $memberInfo['Signature'];
        $result['Avatar'] = $memberModel->getMemberAvatar($memberID);
        $result['Focus'] = $memberFocusModel->getFocusInfo($memberID,null,'FocusID');
        $result['Gender'] = $memberInfo['Gender'];
        $result['Cover'] = $memberInfo['Cover'];

    	if(empty($memberInfo['UserName'])){
    		$result['NeedFillName'] = 1;
    	}else{
    		$focusModel = new Model_MemberFocus();
    		if(!$focusModel->hasFocusInfo($memberID)){
    			$result['NeedFillName'] = 1;
       		}
    	}
    	*/
        $this->addExtraInfo($result);
    	$this->returnJson(parent::STATUS_OK,'',$result);
    }

    /*
     *编辑个人资料
     */
    public function editLoginInfoAction()
    {
        try{
            $memberID = $this->memberInfo->MemberID;
            $this->isLoginOutput();
            $origin = intval($this->_request->getParam('origin',1));//调用接口来源，1APP，2PC
            
            if($origin==2){//编辑理财师的形象照、常驻地址等信息
                $photoImg = trim($this->_request->getParam('photoImg',''));
                $city = trim($this->_request->getParam('residentCity',''));
                $institution = trim($this->_request->getParam('institution',''));
                $job = $this->_request->getParam('job','');
                $workYear = intval($this->_request->getParam('workYear',0));
                $hobby = $this->_request->getParam('hobby','');
                $character = $this->_request->getParam('character','');
                $goodField = $this->_request->getParam('goodField','');
                $profiles = $this->_request->getParam('profiles','');
                $link = $this->_request->getParam('link','');
                if(empty($photoImg) || empty($city)){
                    throw new Exception('请将必填信息补充完整！');
                }
                $updateArr = array('PhotoImg'=>$photoImg,'City'=>$city);
                !empty($institution) && $updateArr['Institution'] = $institution;
                !empty($job) && $updateArr['Job'] = $job;
                !empty($workYear) && $updateArr['WorkYear'] = $workYear;
                !empty($hobby) && $updateArr['Hobby'] = $hobby;
                !empty($character) && $updateArr['Character'] = $character;
                !empty($goodField) && $updateArr['GoodField'] = $goodField;
                !empty($profiles) && $updateArr['Profiles'] = $profiles;
                !empty($link) && $updateArr['Link'] = $link;
                $columnModel =new Model_Column_Column();
                $columnInfo = $columnModel->getMyColumnInfo($memberID);
                $columnID = $columnInfo['ColumnID'];
                $columnModel->update($updateArr,array('ColumnID = ?'=>$columnID));
                $redisObj = DM_Module_Redis::getInstance();
                $key = 'columnInfo:columnID'.$columnID;
                $redisObj->DEl($key);
                $this->returnJson(parent::STATUS_OK,'编辑成功！');
            }
            $avatar = trim($this->_request->getParam('avatar',''));
            $province = trim($this->_request->getParam('province',null));
            $city = trim($this->_request->getParam('city',null));
            $signature = $this->_request->getParam('signature',null);
            $gender = intval($this->_request->getParam('gender',0));
            $cover = $this->_request->getParam('cover',null);
            $focusID = trim($this->_request->getParam('focusID',''));
            if( empty($avatar)  && empty($province) && empty($city) && is_null($signature) && $gender <= 0 && is_null($cover) && empty($focusID)){
                throw new Exception('编辑内容不能为空！');
            }

            $memberModel = new DM_Model_Account_Members();
            $params=array();
            if(!empty($avatar)){
                $params['Avatar'] = $avatar;
            }
            if(!is_null($province)){
                $params['Province'] = $province;
            }
            if(!is_null($city)){
                $params['City'] = $city;
                
                $params['CityCode'] = DM_Module_Region::getCityCode($city,!empty($province) ? $province : '');
            }
            if(!is_null($signature)){
                $params['Signature'] = trim($signature);
            }
            if($gender>0){
                $params['Gender'] = $gender;
            }
            if(!is_null($cover)){
                $params['Cover'] = trim($cover);
            }
            $focusIDArr = array_filter(explode(',',$focusID));
            if(!empty($focusIDArr) && count($focusIDArr) >= 1){
                $memberFocusModel = new Model_MemberFocus();
                $focusInfo = $memberFocusModel->getFocusInfo($memberID,null,'FocusID');
                foreach ($focusInfo as $item) {
                    if(!in_array($item['FocusID'], $focusIDArr)){
                        $memberFocusModel->removeFocus($memberID,$item['FocusID']);
                    }
                }
                foreach($focusIDArr as $fID){
                    $memberFocusModel->addFocus($memberID, $fID);
                }
            }

            if(!empty($params)){
                $memberModel->updateInfo($memberID,$params);
            }
            $memberModel->deleteCache($memberID);
            $this->returnJson(parent::STATUS_OK,'编辑成功！');
        }catch(Exception $e){
            $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
        }

    }

    /**
     * 通过手机或邮箱找回密码--验证码发送
     */
    public function sendFindPasswordCodeAction()
    {
        $account = $this->_request->getParam('account','');
        $isMobile = DM_Helper_Validator::checkmobile($account);
        $isEmail = DM_Helper_Validator::isEmail($account);
        if($isMobile || $isEmail){
            $memberModel = new DM_Model_Account_Members();
            if($isMobile){
                $memberInfo = $memberModel->getByMobile($account);
                if(empty($memberInfo)){
                    $this->returnJson(parent::STATUS_FAILURE,'该手机未绑定！');
                }

                $code = $memberInfo->createVerifyCode(3,$account);
                $message = '尊敬的会员您好，您正在通过手机号找回密码，验证码:'.$code->VerifyCode;
                $result = DM_Module_EDM_Phone::send($account,$message);
            }elseif($isEmail){
                $memberInfo = $memberModel->getByEmail($account);
                if(empty($memberInfo)){
                    $this->returnJson(parent::STATUS_FAILURE,'该会员不存在！');
                }
                $username = $memberInfo->UserName;
                $result = $memberInfo->sendResetPasswordMail($account,$username);

            }
            if($result){
                $this->returnJson(parent::STATUS_OK,'验证码发送成功！');
            }else{
                $this->returnJson(parent::STATUS_FAILURE,'验证码发送失败！');
            }
        }else{
//             $this->returnJson(parent::STATUS_FAILURE,'请输入邮箱或手机号！');
            $this->returnJson(parent::STATUS_FAILURE,'请输入手机号！');
        }
    }



    public function validateFindPasswordCodeAction()
    {
        $account = $this->_request->getParam('account','');
        $code = trim($this->_request->getParam('code',''));
        if(empty($account)){
            $this->returnJson(self::STATUS_FAILURE,'请输入邮箱或手机号！');
        }

        if(empty($code)){
            $this->returnJson(self::STATUS_FAILURE,'请输入验证码！');
        }

        $isMobile = DM_Helper_Validator::checkmobile($account);
        $isEmail = DM_Helper_Validator::isEmail($account);
        if($isMobile || $isEmail){
            try{
                $memberModel = new DM_Model_Account_Members();
                $verifyTable = new DM_Model_Table_MemberVerifys();

                if($isMobile){
                    $memberInfo = $memberModel->getByMobile($account);
                }elseif($isEmail){
                    $memberInfo = $memberModel->getByEmail($account);
                }

                $paramArray = array('VerifyCode'=>$code,'IdentifyID'=>$account,'VerifyType'=>3,'MemberID'=>$memberInfo['MemberID'],'Status'=>'Pending');
                $verifyinfo = $verifyTable->getVerify($paramArray);

                if(!$verifyinfo){
                    throw new Exception('验证码错误，请重新输入！');
                }

                if(time() > strtotime($verifyinfo['ExpiredTime'])){
                    throw new Exception('验证码已过期！');
                	}
            }catch(Exception $e){
                $this->returnJson(self::STATUS_FAILURE,$e->getMessage());
            }
            $this->returnJson(parent::STATUS_OK,'验证码正确！');
        }
    }

    /**
     * 通过手机或邮箱找回密码---重置密码
     */
    public function findPasswordAction()
    {
        $account = $this->_request->getParam('account','');
        $password = trim($this->_getParam('password', ''));
        $confirmPassword = trim($this->_getParam('confirmPassword', ''));

        $aesModel = new Model_CryptAES();
        $password = $aesModel->decrypt($password);
        $confirmPassword = $aesModel->decrypt($confirmPassword);

        if(!$password || !DM_Helper_Validator::checkPassword($password)){
            $this->returnJson(self::STATUS_FAILURE, '密码由6-20个英文字母和数字组成！');
        }
        if($confirmPassword != $password){
            $this->returnJson(self::STATUS_FAILURE, '两次输入的密码不相同，请重新输入！');
        }

        $code = trim($this->_request->getParam('code',''));
        if(empty($code)){
            $this->returnJson(self::STATUS_FAILURE,'请输入验证码！');
        }

        $isMobile = DM_Helper_Validator::checkmobile($account);
        $isEmail = DM_Helper_Validator::isEmail($account);

        if($isMobile || $isEmail){
            $memberModel = new DM_Model_Account_Members();
            $verifyTable = new DM_Model_Table_MemberVerifys();

            if($isMobile){
                $memberInfo = $memberModel->getByMobile($account);
            }elseif($isEmail){
                $memberInfo = $memberModel->getByEmail($account);
            }
    			if($memberInfo == null){
    				$this->returnJson(parent::STATUS_FAILURE,'账号有误');
    			}
            $paramArray = array('VerifyCode'=>$code,'IdentifyID'=>$account,'VerifyType'=>3,'MemberID'=>$memberInfo['MemberID'],'Status'=>'Pending');

            $db = $verifyTable->getAdapter();
            $db->beginTransaction();
            try{
                $verifyinfo = $verifyTable->getVerify($paramArray);

                if(!$verifyinfo){
                    throw new Exception('验证码错误，请重新输入！');
                }

                if(time() > strtotime($verifyinfo['ExpiredTime'])){
                    throw new Exception('验证码已过期！');
                }

                if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
                    throw new Exception('设置密码失败！');
                }
                $memberInfo->Password = $memberInfo->encodePassword($password);
                $memberInfo->save();
                $db->commit();
                $this->returnJson(parent::STATUS_OK,'设置新密码成功！');
            }catch(Exception $e){
                $db->rollBack();
                $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
            }
        }else{
            $this->returnJson(parent::STATUS_FAILURE,'请输入邮箱或手机号！');
        }
    }

    /**
     * 检测用户名格式，及建议
     */
    public function checkUsernameAction()
    {
    	//$this->isLoginOutput();

    	$memberID = 0;
    	if($this->isLogin()){
    		$memberID = $this->memberInfo->MemberID;
    	}
    	try{
    		$username = trim($this->_request->getParam('userName',''));
    		if(!DM_Helper_Validator::checkUsername($username)){
    			throw new Exception($this->getLang()->_("api.user.msg.username.format"));
    		}

            $sensitiveModel = new DM_Model_Account_Sensitive();
            $sensitive = $sensitiveModel->getInfo($username);

            $filter = $sensitiveModel->filter($username);
            if (!empty($sensitive) || $filter ==1) {
                $this->returnJson(parent::STATUS_FAILURE,'请不要使用敏感词汇注册！');
            }

    		$memberModel = new DM_Model_Account_Members();
    		$rowInfo = $memberModel->getByUsername($username,$memberID);
    		if(empty($rowInfo)){
    			//未被占用，可以注册
    			$this->returnJson(parent::STATUS_OK,'');
    		}else{
    			$suggestion = array();
    			$length = mb_strlen($username,'UTF-8');

    			if($length <= 9){
    				$username = mb_substr($username,0,8,'UTF-8');
    			}elseif($length == 10){
    				$username = mb_substr($username,0,8,'UTF-8');
    			}

    			for($i = 1;$i <= 3;$i++){
    				$originStr = '0123456789';
    				$shuffleStr = str_shuffle($originStr);
    				$tmpUsername= $username.substr($shuffleStr, 0,2);
    				$tmpInfo = $memberModel->getByUsername($tmpUsername);
    				if(empty($tmpInfo)){
    					$suggestion[] = $tmpUsername;
    				}
    			}
    			$this->returnJson(2,'帐号已被使用',array('Suggestion'=>$suggestion));
    		}

    	}catch(Exception $e){
    		$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
    	}
    }

	protected $basicProfileConf=array(
		array("memberID","/([\d]+[,]?)+/","会员ID不能为空",DM_Helper_Filter::MUST_VALIDATE),
	);
    /**
     * 获取基本资料  支持批量
     */
	public function basicProfileAction()
	{
		$this->isLoginOutput();
		try {
			$memberIDArr = explode(",", trim($this->_param['memberID'], ","));
			$memberModel = new DM_Model_Account_Members();

			$fields = array('MemberID', 'UserName', 'Province', 'City', 'Signature', 'Avatar', 'Gender', 'Cover', 'IsBest', 'BestType');
			$select = $memberModel->select()->from('members', $fields)->where('MemberID in (?)', $memberIDArr);
			$res = $select->query()->fetchAll();

			$helperModel = new Model_MessageHelper();
			$memberFollowModel = new Model_MemberFollow();
			$friendModel = new Model_IM_Friend();
			$memberNoteModel = new Model_MemberNotes();

			$is_best = array();
			foreach ($res as &$row) {
				$row['RelationCode'] = $memberFollowModel->getRelation($row['MemberID'], $this->memberInfo->MemberID);
				$row['NoteName'] = ($row['RelationCode'] == 3 || $row['RelationCode'] == 1) ? $memberNoteModel->getNoteName($this->memberInfo->MemberID, $row['MemberID']) : '';
				$row['Description'] = ($row['RelationCode'] == 3 || $row['RelationCode'] == 1) ? $memberNoteModel->getFriendDescription($this->memberInfo->MemberID, $row['MemberID']) : '';
                $helperInfo = $helperModel->getinfo($this->memberInfo->MemberID, $row['MemberID'], 1);
				$row['Messagehelper'] = empty($helperInfo) ? 0 : 1;
				$row['IsBest'] == $memberModel::BEST_STATUS_TRUE && $is_best[] = $row['MemberID'];
			}
			unset($row);

			//获取达人头衔信息 状态为有效获取待认证
			$bestModel = new Model_Best_Best();
			$best_info = $bestModel->getBestInfoByMemberID($is_best);

			foreach ($res as &$row)
				$row['BestInfo'] = isset($best_info[$row['MemberID']]) ? $best_info[$row['MemberID']] : array();

			$this->returnJson(parent::STATUS_OK, '', array('Rows' => $res));
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}


    /**
     * 获取扩展资料
     */
    public function extraProfileAction()
    {
    	$this->isLoginOutput();

    	try{
    		$memberID = trim($this->_request->getParam('memberID',''));
    		if(empty($memberID)){
    			throw new Exception('会员ID不能为空！');
    		}
    		//关注点
    		$memberFocusModel = new Model_MemberFocus();
    		$focusInfo = $memberFocusModel->getFocusInfo($memberID,null,'FocusID');

            //说说的数量
            $shuoshuoModel = new Model_Shuoshuo();
            $shuoshuoCount = $shuoshuoModel->getShuosCount($memberID);

            //相册
            $shuoImageModel = new Model_ShuoImage();
            $albumInfo = $shuoImageModel->getAlbum($memberID);

            //观点的数量
            $viewModel = new Model_Topic_View();
            $viewCount = $viewModel->getViewCount($memberID);

    		//关注话题数量
    		$topicModel = new Model_Topic_Topic();
    		$followCount = $topicModel->getFollowedTopicsCount($memberID);

    		$isSelf = $memberID == $this->memberInfo->MemberID ? 1 : 0;

    		//加入的群组
    		$groupMemberModel = new Model_IM_GroupMember();
    		$joinedGroups = $groupMemberModel->getJoinedGroups($memberID,$isSelf);
    		
    		if(!empty($joinedGroups)){
    			$groupFocusModel = new Model_IM_GroupFocus();
    			foreach($joinedGroups as &$group){
    				$group['Focus'] = $groupFocusModel->getFocusInfo($group['GroupID'],null,'FocusID');
    			}
    		}

    		//文章数量
    		$articleModel = new Model_Column_Article();
    		$articleCount = $articleModel->getArticleNum($memberID);

    		//订阅的专栏数
    		$key = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
    		$redisObj = DM_Module_Redis::getInstance();
    		$count = $redisObj->zcount($key,'-inf','+inf');
    		$columnCount = empty($count)?0:$count;
            
            //获取正在讨论的话题名称
            $discussingTopicName = $topicModel->getDiscussingTopicName($memberID);

            //帐号主题和资质
            $authenticateModel =new Model_Authenticate();
            $qualificationModel = new Model_Qualification();
            $subject='';
            $qualification='';
            $authenticateType = 1;
            $authenticateStatus=-1;
            $qualificationStatus =-1;
            $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID);
            if(!empty($authenticateInfo)){
                $authenticateType = $authenticateInfo['AuthenticateType'];
                $authenticateStatus = $authenticateInfo['Status'];
                if($authenticateType == 1){
                    $len = mb_strlen($authenticateInfo['OperatorName'],'utf-8');
                    $str = mb_substr($authenticateInfo['OperatorName'], 0,1,'utf-8');
                    $subject = $str.str_pad('*',$len-1,'*');
                }elseif($authenticateType == 2){
                    $subject = $authenticateInfo['OperatorName'];
                    $qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);
                    if(empty($qualificationInfo)){
                        $qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1);
                    }
                    if(!empty($qualificationInfo)){
                        $qualification=$qualificationInfo['FinancialQualificationType'];
                        $qualificationStatus= $qualificationInfo['CheckStatus'];
                    }
                }elseif($authenticateType==3){
                    $subject = $authenticateInfo['BusinessName'];
                }elseif($authenticateType==4){
                    $subject = $authenticateInfo['OrganizationName'];
                }
            }

			$this->returnJson(parent::STATUS_OK,'',array('Focus'=>$focusInfo,'ShuoshuoCount'=>$shuoshuoCount,'ViewCount'=>$viewCount,'JoinedGroups'=>$joinedGroups,'FollowTopicCount'=>$followCount,
			'ArticleCount'=>$articleCount,'ColumnCount'=>$columnCount,'AuthenticateType'=> $authenticateType,'Subject'=>$subject,'Qualification'=>$qualification,'AuthenticateStatus'=>$authenticateStatus,'QualificationStatus'=>$qualificationStatus,'AlbumInfo'=>$albumInfo,'DiscussingTopicName'=>$discussingTopicName));
    	}catch(Exception $e){
    		$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
    	}
    }

    /**
     * 隐私条款
     */
    public function privateAction()
    {
    	header('Content-type: text/html');
    	Zend_Layout::startMvc()->disableLayout();
    	echo $this->view->render('user/private.phtml');
    }

    /**
     * 话题公约
     */
    public function topicIssueAction()
    {
    	header('Content-type: text/html');
    	Zend_Layout::startMvc()->disableLayout();
    	echo $this->view->render('user/topic-issue.phtml');
    }

    /**
     * 隐私条款
     */
    public function serviceIssueAction()
    {
    	header('Content-type: text/html');
    	Zend_Layout::startMvc()->disableLayout();
    	echo $this->view->render('user/service-issue.phtml');
    }
    
     /**
     * 获取最热达人列表
     */
    public function getHotMasterListAction()
    {
        $this->isLoginOutput();
        $memberModel = new Model_Member();
        $memberList = $memberModel->getHotMasterList();
        if(count($memberList)>0){
            $viewModel = new Model_Topic_View();
            $shuoshuoModel = new Model_Shuoshuo();
            $memberFollowModel = new Model_MemberFollow();
            $memberNoteModel = new Model_MemberNotes();
            $bestModel = new Model_Best_Best();
            $focusModel = new Model_MemberFocus();
            foreach ($memberList as &$member) {
                $member['ViewCount'] = $viewModel->getViewCount($member['MemberID']);
                $member['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($member['MemberID']);
                $member['RelationCode'] = $memberFollowModel->getRelation($member['MemberID'], $this->memberInfo->MemberID);
                $member['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $member['MemberID']);
                $bestInfo = $bestModel->getBestInfoByMemberID(array($member['MemberID']), array(2,3));
                $bestTitleArr = array();
                if(!empty($bestInfo)){
                    $bestTitleArr = $bestInfo[$member['MemberID']];
                }  
                $member['BestTitle'] = !empty($bestTitleArr)?$bestTitleArr:array();
                $member['Focus'] = $focusModel->getFocusInfo($member['MemberID'],null,'FocusID'); //2.23版本之后删除           
            }
        }
        $this->returnJson(parent::STATUS_OK,'', array('Rows'=>$memberList));
    }

     /**
     * 获取最新达人列表
     */
    public function getRecentMasterListAction()
    {
        $this->isLoginOutput();
        $memberModel = new Model_Member();
        $memberList = $memberModel->getRecentMasterList();
        if(count($memberList)>0){
            $viewModel = new Model_Topic_View();
            $shuoshuoModel = new Model_Shuoshuo();
            $memberFollowModel = new Model_MemberFollow();
            $memberNoteModel = new Model_MemberNotes();
            foreach ($memberList as &$member) {
                $member['ViewCount'] = $viewModel->getViewCount($member['MemberID']);
                $member['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($member['MemberID']);
                $member['RelationCode'] = $memberFollowModel->getRelation($member['MemberID'], $this->memberInfo->MemberID);
                $member['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $member['MemberID']);
            }
        }
        $this->returnJson(parent::STATUS_OK,'', array('Rows'=>$memberList));
    }

     /**
     * 获取理财师列表
     */
    public function getFinancialPlannerListAction()
    {
        $this->isLoginOutput();
        $memberModel = new Model_Member();
        $memberList = $memberModel->getFinancialPlannerList();
        if(empty($memberList)){
            $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
            $select = $memberModel->select()->setIntegrityCheck(false);
            $select->from('members as m',array('MemberID','UserName','Avatar'))->where('m.MemberID != ?',$sysMemberID)->where('m.Status=?',1);
            $db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->db->dbname;
            $select->joinLeft($db.'.member_authenticate as ma','ma.MemberID = m.MemberID',array('AuthenticateID'))->where('ma.AuthenticateType = ?',2)->where('ma.Status= ?',1);

            $memberList = $select->order('m.MemberID asc')->query()->fetchAll();
        }
        if(!empty($memberList)){
            $memberFollowModel = new Model_MemberFollow();
            $focusModel = new Model_MemberFocus();
            $memberNoteModel = new Model_MemberNotes();
            $qualificationModel = new Model_Qualification();
            foreach ($memberList as &$member) {
                $member['RelationCode'] = $memberFollowModel->getRelation($member['MemberID'], $this->memberInfo->MemberID);
                $member['Focus'] = $focusModel->getFocusInfo($member['MemberID'],null,'FocusID');//2.23版本之后删除
                $member['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $member['MemberID']);

                $qualificationInfo = $qualificationModel->getDisplayQualification($member['AuthenticateID']);
                if(empty($qualificationInfo)){
                    $qualificationInfo = $qualificationModel->getInfoByqualificationID($member['AuthenticateID'],1,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
                }
                $member['Qualification'] = !empty($qualificationInfo)?array($qualificationInfo):array();
            }
        }
        $this->returnJson(parent::STATUS_OK,'', array('Rows'=>$memberList));
    }

    /*
    帐号主体
     */
    public function accountSubjectAction()
    {
        $memberID = intval($this->_request->getParam('memberID',''));
        if(empty($memberID)){
            throw new Exception('会员ID不能为空！');
        }
        //$memberID = $this->memberInfo->MemberID;
        $authenticateModel =new Model_Authenticate();
        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1);
        $subject = array();
        if(!empty($authenticateInfo)){
            $subject['Direction'] = $authenticateInfo['Direction'];
            $subject['DataTime'] = $authenticateInfo['DataTime'];

            if($authenticateInfo['AuthenticateType']== 1){
                $len = mb_strlen($authenticateInfo['OperatorName'],'utf-8');
                $str = mb_substr($authenticateInfo['OperatorName'], 0,1,'utf-8');
                $subject['Name'] = $str.str_pad('*',$len-1,'*');
                $subject['IDCard'] = substr_replace($authenticateInfo['IDCard'],'*************',3,13);
            }elseif($authenticateInfo['AuthenticateType']== 2){

                $subject['Name'] = $authenticateInfo['OperatorName'];
                $subject['IDCard'] = substr_replace($authenticateInfo['IDCard'],'*************',3,13);

            }elseif($authenticateInfo['AuthenticateType']== 3){
                $subject['BusinessName'] = $authenticateInfo['BusinessName'];
                $subject['BusinessLicenseNumber'] = $authenticateInfo['BusinessLicenseNumber'];
                $subject['FoundedTime'] = $authenticateInfo['FoundedTime'];
            }elseif($authenticateInfo['AuthenticateType']== 4){
                $subject['OrganizationName'] = $authenticateInfo['OrganizationName'];
                $subject['OrganizationCode'] = $authenticateInfo['OrganizationCode'];
                $subject['FoundedTime'] = $authenticateInfo['FoundedTime'];
            }
        }
       $this->returnJson(parent::STATUS_OK,'', $subject);
    }

    /*
    理财师资质
     */
    public function financialQualificationAction()
    {
        $memberID = intval($this->_request->getParam('memberID',''));
        if(empty($memberID)){
            throw new Exception('会员ID不能为空！');
        }
        //$memberID = $this->memberInfo->MemberID;
        $authenticateModel =new Model_Authenticate();
        $qualificationModel = new Model_Qualification();
        $authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1);
        $qualification= array('Direction'=>'','DataTime'=>'','Qualification'=>array());
        if(!empty($authenticateInfo) && $authenticateInfo['AuthenticateType'] ==2){
            $qualification['Direction'] = $authenticateInfo['Direction'];
            $qualification['DataTime'] = $authenticateInfo['DataTime'];
            $qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],null,1);
            //var_dump($qualificationInfo);exit;
            foreach ($qualificationInfo as &$item){
               unset($item['FinancialQualificationID']);
               unset($item['AuthenticateID']);
               unset($item['FinancialQualificationImage']);
               unset($item['DataTime']);
               unset($item['UpdateTime']);
               unset($item['Remark']);
               unset($item['CheckStatus']);
            }
            $qualification['Qualification'] = $qualificationInfo;

        }
        //var_dump($qualification);exit;
        $this->returnJson(parent::STATUS_OK,'', $qualification);
    }
    
    /**
     * 获取名人排行榜（理财师和达人混排）
     */
    public function getFamousPersonAction()
    {
    	$this->isLoginOutput();
    	$memberModel = new Model_Member();
    	$memberList = $memberModel->getFamousPerson($this->memberInfo->MemberID);
    	$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$memberList));
    }
    
    /**
     * 获取某个手机号是否已注册
     */
    public function isRegisterAction()
    {
    	$mobile = trim($this->_getParam('mobile', ''));
    	if (!$mobile || !DM_Helper_Validator::checkmobile($mobile) || !Model_Member::checkMobileFormat($mobile)){
    		$this->returnJson(parent::STATUS_FAILURE,'请输入正确的手机号！');
    	}
    	$memberModel = new DM_Model_Account_Members();
    	$memberInfo = $memberModel->getByMobile($mobile);
    	$isRegister = 0;
    	$memberID = 0;
    	if (!empty($memberInfo)) {
    		$isRegister = 1;
    		$memberID = $memberInfo['MemberID'];
    	}
    	$this->returnJson(parent::STATUS_OK,'',array('isRegister'=>$isRegister,'memberID'=>$memberID));
    }
    
    /**
     * 名人堂列表
     */
    public function getFamousListAction()
    {
    	$lastID = intval($this->_request->getParam('lastID',0));
    	$pageSize = intval($this->_request->getParam('pagesize',30));
    	$famousModel = new Model_Famous();
    	$select = $famousModel->select();
    	$select->from('famous',array('FID','ImgUrl'));
    	if($lastID > 0){
    		$select->where('FID < ?',$lastID);
    	}
    	$rows = $select->order('FID desc')->limit($pageSize)->query()->fetchAll();
    	$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$rows));
    }
    
    /**
     * 获取名人堂名人信息
     */
    public function getFamousDetailAction()
    {
    	$famousModel = new Model_Famous();
    	$memberModel = new DM_Model_Account_Members();
    	$fid = intval($this->_request->getParam('fid',0));
    	try{
    		$select = $famousModel->select();
    		$select->from('famous',array('MemberID','IsShowColumn','IsShowCounsel','Experience','DetailUrl','ImgUrl'));
    		$info = $select->where('FID = ?',$fid)->query()->fetch();
    		if(empty($info)){
    			//throw new Exception('未查询到信息');
                $this->returnJson(parent::STATUS_OK,'',array("Status"=>0));
    		}
    		
    		$info['ColumnID'] = 0;
    		$info['IsSubscribe'] = 0;
    		if($info['IsShowColumn']){
    			$columnModel = new Model_Column_Column();
    			$myColumnInfo = $columnModel->getMyColumnInfo($info['MemberID'],1);
    			if(!empty($myColumnInfo)){
    				$info['ColumnID'] = $myColumnInfo['ColumnID'];
    				if($this->isLogin()){
    					$info['IsSubscribe'] = $columnModel->isSubscribeColumn($this->memberInfo->MemberID, $myColumnInfo['ColumnID']);
    				}
    			}
    		}
    		$info['Status'] = 1;
    		$info['UserName'] = $memberModel->getUserName($info['MemberID']);
    		$this->returnJson(parent::STATUS_OK,'',$info);
    	}catch(Exception $e){
    		$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
    	}
    }
}
