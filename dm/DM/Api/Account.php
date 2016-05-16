<?php
/**
 * API共用控制器
 * 账户系统
 * 
 * @author Kitty
 * @since 2015/01/15
 */
class DM_Api_Account extends DM_Controller_Account
{
    
    public function indexCall(){
        echo 'user api, nothing to do. ';
    }
    
    protected function sendRegCodeCall($mobile)
    {
        $memberVerify = new DM_Model_Table_MemberVerifys();

        $count = $memberVerify->getSendCount($mobile);
        if($count >= 3 ){
            $this->returnJson(self::STATUS_FAILURE,'同一手机号一天只能发送3次！');
        }
        $code = $memberVerify->createVerify(5,$mobile, 0);

        if (!$code) {
            return false;
        }
    
        // $lang=DM_Controller_Front::getInstance()->getLang();
        // $message = $lang->_('user.edm.phone.register.content',$code->VerifyCode);

        $message = "您好，您正在进行注册会员操作，手机验证码为：".$code->VerifyCode;
        $result = DM_Module_EDM_Phone::send($mobile,$message);
        
        return $result;
    }


    /**
     * 注册
     * 
     * 默认必填字段：email, password
     * 
     * @return DM_Model_Row_Member
     */
    protected function registerCall($isOverrideLast = false,$isNeedUsername = true)
    {
//         $this->isPostOutput();

        $mode = $this->_getParam('mode','');
        $username = trim($this->_getParam('username', ''));
        $password = trim($this->_getParam('password', ''));
        $deviceID = trim($this->_getParam('deviceNo', ''));
        if(empty($deviceID)){
        		$deviceID = trim($this->_getParam('deviceID',''));
           }
           
        $platform = intval($this->_getParam('platform', 0));
        $channel = trim($this->_getParam('channel', ''));
        $accountModel = new DM_Model_Account_Members();

        if($isNeedUsername){
	        if(!$username || !DM_Helper_Validator::checkUsername($username)){
	            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.username.format"));
	        }
	        if ($accountModel->getByUsername($username)){
	            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.username.existed"));
	        }
        }else{
        	$username = '';
        }
        
        if(!$password || !DM_Helper_Validator::checkPassword($password)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
        }
        if(!in_array($platform, array(1,2))){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));  
        }
        if ( $mode == 'email' ) {
            $account = trim($this->_getParam('email', ''));
            if (!$account || !DM_Helper_Validator::isEmail($account)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
            }
            if ($accountModel->getByEmail($account)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.existed"));
            }

//         		$this->returnJson(self::STATUS_FAILURE,'邮箱暂不能注册');

        }elseif ( $mode == 'mobile') {
            $account = trim($this->_getParam('mobile', ''));
            $code = trim($this->_getParam('code', ''));
            if (!$account || !DM_Helper_Validator::checkmobile($account)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.phone.format"));
            }
            if ($accountModel->getByMobile($account)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.phone.existed"));
            }
            
         	if(APPLICATION_ENV != 'development'){
		            $verifyModel = new DM_Model_Table_MemberVerifys();
		            if(!preg_match('/^[0-9]{6}$/', $code)){
		                $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
		            }
		            $paramArray = array('VerifyCode'=>$code, 'VerifyType'=>5,'MemberID'=>0,'Status'=>'Pending');
		            $verifyinfo = $verifyModel->getVerify($paramArray);
		    
		            if(!$verifyinfo){
		                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
		            }
		    
		            if(time() > strtotime($verifyinfo['ExpiredTime'])){
		                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
		            }
		    
		            try {
		                if(!$verifyModel->updateVerify($verifyinfo['VerifyID'])){
		                    throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
		                	}
		            } catch (Exception $e){
		                $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		            }
         		}
        }else{
             $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.choice.register.mode"));
        	}
        
        $user=$this->createUser($account, $mode, $username, $password,$deviceID,$channel);      
        //注册成功后登录
        DM_Module_Account::getInstance()->setSession($this->getSession())->login($user->MemberID, $password, $this->getRequest()->getClientIp(), $platform, $deviceID, '',true,$isOverrideLast);
        return $user;
    }
    

    /*
     * 创建用户 
     * 
     * 如果密码为空，则改用户不可密码登录
     */
    private function createUser($account, $mode = 'email', $username, $password='',$deviceID='',$channel='')
    {
        $memberModel=new DM_Model_Account_Members();
        $user=$memberModel->createRow();
        if( $mode == 'email'){
            $user->Email = $account;
            $user->EmailVerifyStatus = 'Verified';
        }elseif ( $mode == 'mobile') {
            $user->MobileNumber = $account;
            $user->MobileVerifyStatus = 'Verified';
            $user->IsProtect = 1;
        }
        $user->Mode = $mode;
        $user->UserName = $username;
        $system = DM_Controller_Front::getInstance()->getConfig()->project->attr_sign;
        if($system=='caizhu'){
            $user->GJDeviceID = $deviceID;
        }elseif ($system=='IM') {
            $user->DeviceID = $deviceID;
        }      
        $user->Channel = $channel;
        $user->RegisterTime = DM_Helper_Utility::getDateTime();
        $user->LastLoginIp=$this->getRequest()->getClientIp();
        $user->LastLoginDate=DM_Helper_Utility::getDateTime();
        //$user->Language=$this->getLocale();
        if ($password){
            $user->createPassword($password);
        }
        $user->save();
        return $user;
    }


    /**
     * 用户登录
     */
    protected function loginCall($isOverrideLast = false){
       // $this->isPostOutput();
        $user = trim($this->_getParam('user', ''));
        $password = trim($this->_getParam('password',''));
        $platform = trim($this->_getParam('platform', ''));
        $deviceID = trim($this->_getParam('deviceID',''));
        if(empty($deviceID)){
        	$deviceID = trim($this->_getParam('deviceNo',''));
        }
        
        $pushID = trim($this->_getParam('pushID', ''));
        $code = trim($this->_getParam('code', ''));
        $login_ip = $this->getRequest()->getClientIp();
        if(!$platform){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));  
        }
        return DM_Module_Account::getInstance()->setSession($this->getSession())->login($user, $password, $login_ip, $platform, $deviceID, $pushID,false,$isOverrideLast,$code);
    }

    

    /**
     * 退出系统
     */
    protected function logoutCall() 
    {
        //$this->isPostOutput();
	     if(!empty($this->memberInfo->MemberID)){  
		    	$member_id = $this->memberInfo->MemberID;
		    	$sessKey = DM_Model_Account_Members::staticData($member_id,'WebLoginSessKey');
		    	if(!empty($sessKey)){
		    		$sessionRedis = DM_Module_Redis::getInstance('session');
		    		$sessionRedis->delete('PHPREDIS_SESSION:'.$sessKey);
		    		DM_Model_Account_Members::staticData($member_id,'WebLoginSessKey','');
		    	}
	      }
        return DM_Module_Account::getInstance()->setSession($this->getSession())->logout();
    }

    /**
     * 修改密码
     */
    protected function resetPasswordCall($user){
        $password = trim($this->_getParam('password', ''));
        $rePassword = trim($this->_getParam('re_password', ''));
    
        if(!$password || !DM_Helper_Validator::checkPassword($password)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
        }
        if($password != $rePassword){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password"));
        }
        $this->isLoginOutput();
        //$user =DM_Controller_Front::getInstance()->getAuth()->getLoginUser();
    
        // if(!$user->verifyPassword($oldPassword)){
        //     $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.restpwd.oldpwd.unmatch"));
        // }
        $user->Password = $user->encodePassword($password);
        //和支付密码一致
        // if($user->RefundPassword == $user->Password){
        //     $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.inCommonWithRefundPassword"));
        // }
        //$user->MemberID = $user->MemberID;
    
        $user->save();
        
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.password.updated"));
    }

    /**
     * 发送手机验证码
     *
     * @return bool
     */
    protected function sendMobileCodeCall($user,$mobile='',$type)
    {

        $this->isLoginOutput();
        $user = DM_Module_Account::getInstance()->getLoginUser();
        //$mobile   = trim($this->_getParam('mobile', '')); 
        // if(!empty($user->MobileNumber) || $user->MobileNumber == $mobile){
        //     $sendMobile =  $mobile;
        // }else{
        //     $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.phone.notsame"));
        // }
        
        if($type==2){
            if (!$mobile || !DM_Helper_Validator::checkmobile($mobile)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.mobile.format"));
            }
            if($user->MobileVerifyStatus != 'Pending' && $user->IsUnderBind !=1){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
            }
            $memberModel =new DM_Model_Account_Members();
            $res = $memberModel->getAdapter()->fetchRow("select MemberID from members where MobileNumber = :MobileNumber and MemberID !=:MemberID", array('MobileNumber'=>$mobile,'MemberID'=>$user->MemberID));
          
            if($res){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.mobile.verified.byOther"));
            }
        }elseif($type==7){
            if($user->MobileVerifyStatus != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.mobile.noVerified")); 
            }
            $mobile = $user->MobileNumber;
        }elseif($type==10){
            if($user->MobileVerifyStatus != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.mobile.noVerified")); 
            }
            $mobile = $user->MobileNumber;
        }else{
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));  
        }

        $model=new DM_Model_Table_MemberVerifys;
        $count = $model->getSendCount($mobile);
        if($count >= 3 ){
            $this->returnJson(self::STATUS_FAILURE,'同一手机号一天只能发送3次！');
        }
        return $user->sendMobileCode($mobile,$type);
    }
    
    /**
     * 通过短信验证码绑定手机
     *
     * @Privilege user::bind-mobile
     */
    public function verifyMobileCall($user){
        $activeCode = trim($this->_getParam('code'));
        $mobile = trim($this->_getParam('mobile',''));
        $type = intval($this->_getParam('type'));
        $verifyModel = new DM_Model_Table_MemberVerifys();
        $memberModel =new DM_Model_Account_Members();
    
        if(!in_array($type, array(2,7,10))){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));
        }

        $userInfo=$user;
        if(!$userInfo){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.user.not.exist"));
        }

        if($type == 2){
            if (!$mobile || !DM_Helper_Validator::checkmobile($mobile)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.mobile.format"));
            }
            if(count($memberModel->getAdapter()->fetchAll("select MemberID from members where MobileNumber={$mobile} and MemberID !={$userInfo['MemberID']} and Status = 1"))>0){
                $this->renderFailure($this->getLang()->_("api.user.mobile.existed"));
            }

            if($userInfo['MobileVerifyStatus'] != 'Pending' && $userInfo['IsUnderBind']!=1){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
            }
        }elseif($type == 7){
            if($userInfo['MobileVerifyStatus'] != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.mobile.noVerified")); 
            }
        }elseif($type == 10){
			if($userInfo['MobileVerifyStatus'] != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.mobile.noVerified")); 
            }
        }else{
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));
        }
        if(!preg_match('/^[0-9]{6}$/', $activeCode)){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
        }
        $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>$type,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
        $verifyinfo = $verifyModel->getVerify($paramArray);
        if(!$verifyinfo){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
        }
    
        if(time() > strtotime($verifyinfo['ExpiredTime'])){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
        }
        $db = $verifyModel->getAdapter();
        $db->beginTransaction();
    
        try {
            if(!$verifyModel->updateVerify($verifyinfo['VerifyID'])){
                throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
            }
			if($type!=10){
				if(false === $memberModel->verifyMobile($verifyinfo['MemberID'],$verifyinfo['IdentifyID'],$type)){
					throw new Exception($this->getLang()->_("api.user.msg.verifystatus.failed"));
				}
			}
            $db->commit();
    
        } catch (Exception $e){
            $db->rollBack();
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    
        //$this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.verify.success"));
    }
    
   /**
     * 发送验证码相关邮件方法（通用版，需要传递验证码类型参数）
     * 
     * @param int $VerifyType
     * @return bool
     * @deprecated 建议使用独立的方法
     */
    protected function sendActivateAccountMailCall($VerifyType=1)
    {
        $this->isLoginOutput();
        $user = DM_Module_Account::getInstance()->getLoginUser();
        $email   = trim($this->_getParam('email', '')); 

        if($VerifyType ==8){
            if($user->EmailVerifyStatus != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.email.noVerified")); 
            }
        }else{
            //邮箱判断
            if (!$email || !DM_Helper_Validator::isEmail($email)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
            }
            if($user->EmailVerifyStatus != 'Pending' && $user->IsUnderBind != 2){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
            }

            $memberModel =new DM_Model_Account_Members();
            $res = $memberModel->getAdapter()->fetchRow("select MemberID from members where Email = :Email and MemberID !=:MemberID", array('Email'=>$email,'MemberID'=>$user->MemberID));
            if(!empty($res)){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.email.verified.byOther"));
            }
        }
        $username= $user->UserName;
        return $user->sendActivateAccountMail($VerifyType,$email,$username);
    }

    /**
     * 通过邮箱验证账号、验证邮箱
     *
     * @Privilege user::active-user 通过邮箱激活账号
     * @$operatel     不为空时，验证邮箱成功后 完善未注册时生成的中介交易[彩贝网]
     */
    public function activateAccountCall($VerifyType=1)
    {
        $this->isLoginOutput();
        $user = DM_Module_Account::getInstance()->getLoginUser();
        $activeCode = trim($this->_getParam('code'));
        $email =  trim($this->_getParam('email'));
    
        $verifyModel = new DM_Model_Table_MemberVerifys();
        $memberModel=new DM_Model_Account_Members;
        if($VerifyType==1){
            //邮箱判断
            if (!$email || !DM_Helper_Validator::isEmail($email)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
            }

            if(count($memberModel->getAdapter()->fetchAll("select MemberID from members where Email='{$email}' and MemberID !={$user->MemberID} and Status = 1"))>0){
                $this->renderFailure($this->getLang()->_("api.user.email.existed"));
            }
            if($user->EmailVerifyStatus != 'Pending' && $user->IsUnderBind != 2){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
            }
        }elseif($VerifyType==8){
            if($user->EmailVerifyStatus != 'Verified'){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.email.noVerified")); 
            }
        }else{
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.param.error"));
        }
 
        // $userInfo=$memberModel->getByEmail($email);
        // if(!$userInfo){
        //     $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.not.exist"));
        // }
        
        if(!preg_match('/^[0-9]{6}$/', $activeCode)){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
        }

        $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>$VerifyType,'MemberID'=>$user->MemberID,'Status'=>'Pending');
        $verifyinfo = $verifyModel->getVerify($paramArray);
    
        if(!$verifyinfo){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
        }
    
        if(time() > strtotime($verifyinfo['ExpiredTime'])){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
        }
    
        $db = $verifyModel->getAdapter();
        $db->beginTransaction();
    
        try {
            if(!$verifyModel->updateVerify($verifyinfo['VerifyID'])){
                throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
            }
            if(!$memberModel->validatedEmail($verifyinfo['MemberID'],$verifyinfo['IdentifyID'],$VerifyType)){
                throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
            }
            
            //账户激活成功后的事件调用 子类覆盖这个方法即可
            $this->onAfterActivated();
            
            $db->commit();
        } catch (Exception $e){
            $db->rollBack();
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    
        //$this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.verify.success"));
    }
    
    /**
     * 账户激活成功后的事件调用
     */
    protected function onAfterActivated()
    {

    }
        
    

   
    
    /**
     * 解除绑定
     */
   public function unboundCall()
   {
        $this->isLoginOutput();
        $member_id = $this->memberInfo->MemberID;
        $account = $this->_request->getParam('account','');
        if(!in_array($account, array('mobile','email'))){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.params.error"));
        }
        $memberModel = new DM_Model_Account_Members();
        return $memberModel->unbound($account,$member_id);
   }

    
    /**
     * 开启或关闭账号保护
     */
   public function updateProtectCall()
   {
        $this->isLoginOutput();
        $isprotect = $this->_request->getParam('isprotect',1);
        $member_id = $this->memberInfo->MemberID;
        $memberModel = new DM_Model_Account_Members();
        return $memberModel->updateProtect($member_id,$isprotect);
   }

    /**
     * 通过财猪号搜索到我
     */
   public function updateAccountableCall()
   {
        $this->isLoginOutput();
        $accountable = $this->_request->getParam('accountable',1);
        $member_id = $this->memberInfo->MemberID;
        $memberModel = new DM_Model_Account_Members();
        return $memberModel->updateInfo($member_id,array('IsAccountSearchable'=>$accountable));
   }

    /**
     * 通过手机号搜索到我
     */
   public function updateMobileableCall()
   {
        $this->isLoginOutput();
        $mobileable = $this->_request->getParam('mobileable',1);
        $member_id = $this->memberInfo->MemberID;
        $memberModel = new DM_Model_Account_Members();
        return $memberModel->updateInfo($member_id,array('IsMobileSearchable'=>$mobileable));
   }
   
   /**
    * 在用户通讯录里推荐我
    */
   public function updateContacteableCall()
   {
	   	$this->isLoginOutput();
	   	$IsShowContactList = $this->_request->getParam('IsShowContactList',1);
	   	$member_id = $this->memberInfo->MemberID;
	   	$memberModel = new DM_Model_Account_Members();
	   	return $memberModel->updateInfo($member_id,array('IsShowContactList'=>$IsShowContactList));
   }


    /**
     * 发送激活邮箱验证码邮件方法（单功能，只针对激活邮箱）
     *
     * @return bool
     */
    protected function sendVerifyEmailMailCall(){
        $this->isPostOutput();
        $this->isLoginOutput();
        
        $email = $this->getLoginUser()->Email;
    
        $memberModel = new DM_Model_Account_Members;
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
    
        $userInfo=$memberModel->getByEmail($email);
        if (!$userInfo){
            return $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.login.userInfo.null"));
        }
        if($userInfo->EmailVerifyStatus != 'Pending'){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
        }
    
        return $userInfo->sendVerifyEmailMail($email);
    }
    
    /**
     * 发送找回登录密码验证码邮件方法
     * 
     * 找回密码不在登录状态
     *
     * @return bool
     */
    protected function sendResetPasswordMailCall(){
        $this->isPostOutput();
        $email = $this->_getParam('email', '');

        $memberTable=new DM_Model_Table_Members;
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
    
        $userInfo=$memberTable->getByEmail($email);
        if (!$userInfo){
            return $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.login.userInfo.null"));
        }
    
        return $userInfo->sendResetPasswordMail($email);
    }
    
    /**
     * 发送找回支付密码验证码邮件方法
     *
     * @return bool
     */
    protected function sendResetRefundPasswordMailCall(){
        $this->isPostOutput();
        $this->isLoginOutput();
        $email = $this->getLoginUser()->Email;
    
        $memberTable=new DM_Model_Table_Members;
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
    
        $userInfo=$memberTable->getByEmail($email);
        if (!$userInfo){
            return $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.login.userInfo.null"));
        }
    
        return $userInfo->sendResetRefundPasswordMail($email);
    }
    

    

    
    /**
     * 身份认证
     *
     * @return bool
     */
    protected function verifyIdentityCall(){
        $this->isLoginOutput();
        $email = $this->getLoginUser()->Email;
        $memberTable=new DM_Model_Table_Members;
        $user = $memberTable->getByEmail($email);
    
        $name = trim($this->_getParam('name'));
        $code = trim($this->_getParam('code'));
        if ($name){
            if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\s]{4,40}$/s', $name)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.name.illegal"));
            }
            $user->Name=$name;
        }
    
        if (!$code){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.idcardcode.empty"));
        }
        $user->IdcardCode=$code;
    
        if(!empty($user->IdcardImgPath)){
            $path = explode('|', $user->IdcardImgPath);
            $frontImg = current($path);
            $backImg = end($path);
        }
        
        if(empty($_FILES["file"]["tmp_name"])){
            if(empty($frontImg)){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_('api.user.msg.idcardimg.empty'));
            }else{
                $user->IdcardImgPath =$frontImg;
            }
        }else{
            $res=Model_Common::getInstance()->upload($_FILES["file"]["tmp_name"]);
            if($res['flag']){
                $user->IdcardImgPath = $res['main_file'];
            }else{
                $this->returnJson(self::STATUS_FAILURE,$this->getLang()->_('api.user.msg.idcardimg.empty'));
            }
        }
        if(empty($_FILES["file1"]["tmp_name"])){
            if(empty($backImg)){
                $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_('api.user.msg.idcardimg.empty'));
            }else{
                $user->IdcardImgPath .= '|'.$backImg;
            }
        }else{
            $res=Model_Common::getInstance()->upload($_FILES["file1"]["tmp_name"]);
            if($res['flag']){
                $user->IdcardImgPath .= '|'.$res['main_file'];
            }else{
                $this->returnJson(self::STATUS_FAILURE,$this->getLang()->_('api.user.msg.idcardimg.empty'));
            }
        }
        $user->IdcardVerifyStatus= 'Processing';
        if($user->save()){
            $this->returnJson(self::STATUS_OK,$this->getLang()->_('api.user.msg.idcardverify.succeed'));
        }else{
            $this->returnJson(self::STATUS_FAILURE,$this->getLang()->_('api.user.msg.idcardverify.failed'));
        }
    
    }
    

    /**
     * 发送Google验证码
     *
     * @return bool
     */
    protected function sendResetGoogleKeyMailCall(){
        $this->isLoginOutput();
    
        $userInfo=DM_Module_Auth::getInstance()->getLoginUser();
        return $userInfo->sendResetGoogleKeyMail();
    }
    
    
    /**
     * 绑定google身份验证器
     */
    protected function bindAuthenticatorCall()
    {
        $code = $this->_getParam('code');
        $secret = $this->_getParam('secret');
        $gModel = new DM_Module_GoogleAuthenticator();
        $flag = $gModel->checkSecret($secret);
        if(!$flag){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.google.wrongkey"));//'密钥格式有误'
        }
    
        //验证
        $flag = $gModel->verifyCode($secret, $code, 2);
        if(!$flag){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.google.verifyfaild"));//'验证失败'
        }
    
        $userInfo=DM_Module_Auth::getInstance()->getLoginUser();
        $userInfo->GoogleSecret=$secret;
        $userInfo->save();
    
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.google.bindsuccess"));//'绑定成功'
    }


    
    /**
     * 设置一个密码
     * 
     * 针对开发登录过来的用户，密码字段为空的才可以设置
     */
    protected function setNewPasswordCall(){
        $newPassword = trim($this->_getParam('password', ''));
    
        if(!$newPassword || !DM_Helper_Validator::checkPassword($newPassword)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
        }
        $this->isLoginOutput();
        $user =DM_Controller_Front::getInstance()->getAuth()->getLoginUser();
        
        if($user->Password!==''){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.setnewpwd.oldpwd.exist"));
        }
        //需要createPassword encode不能产生hash
        $user->createPassword($newPassword);
        //和支付密码一致
        if($user->RefundPassword == $user->Password){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.inCommonWithRefundPassword"));
        }
    
        $user->save();
        
        return $this->returnJson(self::STATUS_OK, $this->getLang()->_("api.user.msg.password.updated"), array(), array('forward'=>'/member'));
    }
    
    /**
     * 修改支付密码
     */
    protected function resetRefundPasswordCall(DM_Model_Row_Member $user){
        $this->isLoginOutput();
        
        $newPassword = trim($this->_getParam('new_password', ''));
        $oldPassword = trim($this->_getParam('old_password', ''));

        if(!$newPassword){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.resetpassword.failed"));
        }
        if($newPassword == $oldPassword){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.newSameToOld"));
        }
        $this->isLoginOutput();
        
        $memberTable=new DM_Model_Table_Members;
        $user = $memberTable->getByEmail($user->Email);
    
        if($user->encodePassword($oldPassword) !== $user->RefundPassword){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.restpwd.oldpwd.unmatch"));
        }
        $user->createRefundPassword($newPassword);
        if($user->RefundPassword == $user->Password){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.refundpassword.inCommonWithPassword"));
        }
        //$user->MemberID = $user->MemberID;
    
        return $user->save();
    }
    
    /**
     * 找回登录密码 通过邮箱验证码 
     *
     * @Privilege user::findPassword
     */
    public function findPasswordCall(){
    	$activeCode = trim($this->_getParam('code'));
    	$email = trim($this->_getParam('email'));
    	$mobile = trim($this->_getParam('mobile'));
    	$password = trim($this->_getParam('password'));
    	$verifyTable = new DM_Model_Table_MemberVerifys();
    	$memberTable=new DM_Model_Table_Members;
    	
    	//邮箱判断
    	$userInfo=array();
    	if ($email){
    		if (!DM_Helper_Validator::isEmail($email)){
    			$this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
    		}
    	
    		$userInfo=$memberTable->getByEmail($email);
    	}elseif ($mobile){
    		$userInfo=$memberTable->getByMobile($mobile);
    	}
    	 
    	if(!$userInfo){
    		$this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.not.exist"));
    	}
    	
    	if(!preg_match('/^[0-9]{6}$/', $activeCode)){
    		$this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
    	}
    	
    	$paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>3,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
    	$verifyinfo = $verifyTable->getVerify($paramArray);
    	
    	if(!$verifyinfo){
    		$this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
    	}
    	if(time() > strtotime($verifyinfo['ExpiredTime'])){
    		$this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
    	}
    	
    	if(!$password || !DM_Helper_Validator::checkPassword($password)){
    		$this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
    	}
    	
    	$db = $verifyTable->getAdapter();
    	$db->beginTransaction();
    	try {
    		if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
    			throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
    		}
    		$userInfo->Password = $userInfo->encodePassword($password);
    		if(empty($userInfo->Password)){
    			throw new Exception($this->getLang()->_("api.user.msg.resetpassword.failed"));
    		}else{
    			if($userInfo->RefundPassword == $userInfo->Password){
    				throw new Exception($this->getLang()->_("api.user.msg.password.inCommonWithRefundPassword"));
    			}
    			$userInfo->save();
    		}
    		$db->commit();
    	
    	} catch (Exception $e){
    		$db->rollBack();
    		$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
    	}
    	
    	$this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.password.updated"));
	}

	/**
	 * 找回交易密码 通过短信验证码
	 *
	 * @Privilege user::findPassword
	 */
	public function findTradePasswordCall(){
	    $activeCode = trim($this->_getParam('code'));
	    $email = trim($this->_getParam('email'));
	    $mobile = trim($this->_getParam('mobile'));
	    $password = trim($this->_getParam('password'));
	    $verifyTable = new DM_Model_Table_MemberVerifys();
	    $memberTable=new DM_Model_Table_Members;
	     
	    //邮箱判断
	    $userInfo=array();
	    if ($email){
	        if (!DM_Helper_Validator::isEmail($email)){
	            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
	        }
	         
	        $userInfo=$memberTable->getByEmail($email);
	    }elseif ($mobile){
	        $userInfo=$memberTable->getByMobile($mobile);
	    }
	
	    if(!$userInfo){
	        $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.not.exist"));
	    }
	     
	    if(!preg_match('/^[0-9]{6}$/', $activeCode)){
	        $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
	    }
	     
	    $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>4,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
	    $verifyinfo = $verifyTable->getVerify($paramArray);
	     
	    if(!$verifyinfo){
	        $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
	    }
	    if(time() > strtotime($verifyinfo['ExpiredTime'])){
	        $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
	    }
	     
	    if(!$password){
	        $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.resetpassword.failed"));
	    }
	     
	    $db = $verifyTable->getAdapter();
	    $db->beginTransaction();
	    try {
	        if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
	            throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
	        }
	        $userInfo->RefundPassword = $userInfo->encodePassword($password);
	        if(empty($userInfo->RefundPassword)){
	            throw new Exception($this->getLang()->_("api.user.msg.resetpassword.failed"));
	        }else{
	            if($userInfo->RefundPassword == $userInfo->Password){
	                throw new Exception($this->getLang()->_("api.user.msg.password.inCommonWithRefundPassword"));
	            }
	            $userInfo->save();
	        }
	        $db->commit();
	         
	    } catch (Exception $e){
	        $db->rollBack();
	        $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
	    }
	     
	    $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.refundpassword.updated"));
	}
    /**
     * 通过邮箱验证码 重置支付密码
     *
     * @Privilege user::deleteRefundPassword 重置支付密码
     */
    public function deleteRefundPasswordCall(){
        $this->isLoginOutput();
        $activeCode = trim($this->_getParam('code'));
        $email = $this->getLoginUser()->Email;
        $verifyTable = new DM_Model_Table_MemberVerifys();
        $memberTable=new DM_Model_Table_Members;

    
        if(!preg_match('/^[0-9]{6}$/', $activeCode)){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
        }
    
        $userInfo=$memberTable->getByEmail($email);
        if(!$userInfo){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.not.exist"));
        }
    
        $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>4,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
        $verifyinfo = $verifyTable->getVerify($paramArray);
    
        if(!$verifyinfo){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.wrong"));
        }
    
        if(time() > strtotime($verifyinfo['ExpiredTime'])){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verifycode.expired"));
        }
    
        $db = $verifyTable->getAdapter();
        $db->beginTransaction();
        try {
            if(!$verifyTable->updateVerify($verifyinfo['VerifyID'])){
                throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
            }
            $userInfo->RefundPassword = '';
            $userInfo->save();

            $db->commit();
    
        } catch (Exception $e){
            $db->rollBack();
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.refundpassword.updated"));
    }



}
