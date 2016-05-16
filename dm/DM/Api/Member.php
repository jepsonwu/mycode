<?php
/**
 * API共用控制器
 * 
 * TODO: 邮件发送频道限制；验证码取回密码频度限制，防暴力破解。
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Api_Member extends DM_Controller_Api
{
    public function indexCall(){
        echo 'user api, nothing to do. ';
    }
    
    /**
     * 用户登录
     */
    protected function loginCall($isOverrideLast = false){
        $this->isPostOutput();
        
        $user = trim($this->_getParam('username', ''));
        $password = trim($this->_getParam('password',''));
        $platform = trim($this->_getParam('platform', ''));
        $deviceID = trim($this->_getParam('deviceID',''));
        $pushID = trim($this->_getParam('pushID', ''));
        $login_ip = $this->getRequest()->getClientIp();
        
        return DM_Module_Auth::getInstance()->setSession($this->getSession())->login($user, $password, $login_ip, $platform, $deviceID, $pushID,false,$isOverrideLast);
    }
    
    /**
     * 注册
     * 
     * 默认必填字段：email, password, agreement
     * 
     * @return DM_Model_Row_Member
     */
    protected function registerCall($isOverrideLast = false){
        $this->isPostOutput();

        $email   = trim($this->_getParam('email', ''));
        $password   = trim($this->_getParam('password',''));
        $inviteCode = trim($this->_getParam('invite_code'));
        
        $memberTable=new DM_Model_Table_Members;
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
        if ($memberTable->getByEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.existed"));
        }
        if(!$password || !DM_Helper_Validator::checkPassword($password)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
        }
        
        $inviteTable=new DM_Model_Table_InviteCodes();
        if ($inviteCode){
            $codeInfo=$inviteTable->isValidCode($inviteCode);
            if (!$codeInfo){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.referralcode.fail"));
            }
        }

        $user=$this->createUser($email, $password, $inviteCode);
        if ($inviteCode){
            $inviteTable->UpdateCode($codeInfo, $user->MemberID);
        }
        
        //注册成功后登录
        DM_Module_Auth::getInstance()->setSession($this->getSession())->login($email, $password, $this->getRequest()->getClientIp(), '', '', '',false,$isOverrideLast);
        
        return $user;
    }
    
    /**
     * 注册
     *
     * 默认必填字段：email
     *
     * @return DM_Model_Row_Member
     */
    protected function createOauthUserCall($email, $name='')
    {
        $memberTable=new DM_Model_Table_Members;
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            return $this->returnJsonArray(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
        if ($memberTable->getByEmail($email)){
            return $this->returnJsonArray(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.existed"));
        }

        $user=$this->createUser($email);
        if ($user) {
            $user->Name=$name;
            $user->save();
        }
        
        return $this->returnJsonArray(self::STATUS_OK, 'ok', $user);
    }
    
    /*
     * 创建用户 
     * 
     * 如果密码为空，则改用户不可密码登录
     */
    private function createUser($email, $password='', $inviteCode='')
    {
        $memberTable=new DM_Model_Table_Members;
        $user=$memberTable->createRow();
        $user->Email=$email;
        $user->RegisterTime=DM_Helper_Utility::getDateTime();
        $user->LastLoginIp=$this->getRequest()->getClientIp();
        $user->LastLoginDate=DM_Helper_Utility::getDateTime();
        $user->Language=$this->getLocale();
        if ($password){
            $user->createPassword($password);
        }
        if ($inviteCode){
            $user->InviteCode=$inviteCode;
        }
        $user->save();
        return $user;
    }
    
    /**
     * 更新用户资料
     */
    protected function updateCall(DM_Model_Row_Member $user) {
        $name   = trim($this->_getParam('name', ''));
        $nameEn = trim($this->_getParam('name_en'));
        $mobile = trim($this->_getParam('mobile'));
        $QQ = trim($this->_getParam('QQ'));
        $refundPassword = trim($this->_getParam('refund_password'));
        
        if ($name){
            if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z_\x7f-\xff\s]{1,40}$/s', $name)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.name.illegal"));
            }
            $user->Name=$name;
        }
        if ($nameEn){
            if(!preg_match('/^[a-zA-Z\-\_\s]{2,40}$/s', $nameEn)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.ename.illegal"));
            }
            $user->NameEn=$nameEn;
        }
        if ($mobile){
            if(!DM_Helper_Validator::checkmobile($mobile)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.mobile.illegal"));
            }
            $user->MobileNumber=$mobile;
        }
        if ($QQ){
            $user->QQ=(int)$QQ;
        }

        if ($refundPassword){
            if(empty($refundPassword)){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.resetpassword.failed"));
            }
            $user->createRefundPassword($refundPassword);
            if($user->RefundPassword == $user->Password){
                $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.refundpassword.inCommonWithPassword"));
            }
        }
        
        return $user->save();
    }
    
    /**
     * 退出系统
     */
    protected function logoutCall() {
        $this->isPostOutput();
        return DM_Module_Auth::getInstance()->setSession($this->getSession())->logout();
    }

    
    /**
     * 发送验证码相关邮件方法（通用版，需要传递验证码类型参数）
     * 
     * @param int $VerifyType
     * @param bool $emailActivateLinkShowParams (注册时发送的)激活邮件内容中的激活链接是否显示参数，默认否
     * @return bool
     * @deprecated 建议使用独立的方法
     */
    protected function sendActivateAccountMailCall($VerifyType=1, $emailActivateLinkShowParams = false){
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

        return $userInfo->sendActivateAccountMail($VerifyType,$email, '', 0, $emailActivateLinkShowParams);
    }
    
    /**
     * 发送激活邮箱验证码邮件方法（单功能，只针对激活邮箱）
     *
     * @return bool
     * @param int $SendType 1亚马逊 2mailgun
     * @param bool $emailActivateLinkShowParams (后台发送的卖家)激活邮件内容中的激活链接是否显示参数，默认否
     */
    protected function sendVerifyEmailMailCall($sendType = 1, $emailActivateLinkShowParams = false){
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
        if($userInfo->EmailVerifyStatus != 'Pending'){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
        }
    
        return $userInfo->sendVerifyEmailMail($email, $sendType, $emailActivateLinkShowParams);
    }
    
    /**
     * 发送找回登录密码验证码邮件方法
     * 
     * 找回密码不在登录状态
     *
     * @return bool
     */
    protected function sendResetPasswordMailCall($moreParams = false){
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
        if ($moreParams) {
            return $userInfo->sendResetPasswordMail($email, $email);
        } else {
            return $userInfo->sendResetPasswordMail($email);
        }
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
     * 通过邮箱验证账号、验证邮箱
     *
     * @Privilege user::active-user 通过邮箱激活账号
     * @$operatel     不为空时，验证邮箱成功后 完善未注册时生成的中介交易[彩贝网]
     */
    public function activateAccountCall(){
        $activeCode = trim($this->_getParam('code'));
        $email =  trim($this->_getParam('email'));
    
        $verifyTable = new DM_Model_Table_MemberVerifys();
        $memberTable=new DM_Model_Table_Members;
    
        //邮箱判断
        if (!$email || !DM_Helper_Validator::isEmail($email)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.format"));
        }
        
        if(!preg_match('/^[0-9]{6}$/', $activeCode)){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
        }
    
        $userInfo=$memberTable->getByEmail($email);
        if(!$userInfo){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.email.not.exist"));
        }
        if($userInfo['EmailVerifyStatus'] != 'Pending'){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
        }
        $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>1,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
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
    
            if(!$memberTable->validatedEmail($verifyinfo['MemberID'])){
                throw new Exception($this->getLang()->_("api.user.msg.verify.failed"));
            }
            
            //账户激活成功后的事件调用 子类覆盖这个方法即可
            $this->onAfterActivated();
            
            $db->commit();
        } catch (Exception $e){
            $db->rollBack();
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.verify.success"));
    }
    
    /**
     * 账户激活成功后的事件调用
     */
    protected function onAfterActivated()
    {
        
    }
    
    /**
     * 账户绑定手机后的事件调用
     */
    protected function onAfterBindMobile()
    {
    
    }
    
    /**
     * 发送手机验证码
     *
     * @return bool
     */
    protected function sendMobileCodeCall(){
        $this->isLoginOutput();
        $userInfo=DM_Module_Auth::getInstance()->getLoginUser();
        
        $mobile   = trim($this->_getParam('mobile', ''));
        
        
    
        $memberTable=new DM_Model_Table_Members;
        
        if (!$mobile || !DM_Helper_Validator::checkmobile($mobile)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.mobile.format"));
        }
    
        return $userInfo->sendMobileCode($mobile);
    }
    
    /**
     * 通过短信验证码绑定手机
     *
     * @Privilege user::bind-mobile
     */
    public function bindMobileCall($user){
        $activeCode = trim($this->_getParam('code'));
        $mobile = $this->_getParam('mobile');
    
        $verifyTable = new DM_Model_Table_MemberVerifys();
        $memberTable=new DM_Model_Table_Members;
    
        if (!$mobile || !DM_Helper_Validator::checkmobile($mobile)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.mobile.format"));
        }
        if($mobile){
            if(count(Zend_Db_Table::getDefaultAdapter()->fetchAll("select MemberID from members where MobileNumber={$mobile}"))>0){
                $this->renderFailure($this->getLang()->_("api.user.mobile.existed"));
            }
        }
    
        if(!preg_match('/^[0-9]{6}$/', $activeCode)){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.verifycode.format"));
        }
    
        $userInfo=$user;
        if(!$userInfo){
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("api.user.msg.user.not.exist"));
        }
        if($userInfo['MobileVerifyStatus'] != 'Pending'){
            $this->returnJson(parent::STATUS_FAILURE,$this->getLang()->_("api.user.msg.verified"));
        }
        $paramArray = array('VerifyCode'=>$activeCode, 'VerifyType'=>2,'MemberID'=>$userInfo['MemberID'],'Status'=>'Pending');
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
    
            if(!$memberTable->verifyMobile($verifyinfo['MemberID'],$verifyinfo['IdentifyID'])){
                throw new Exception($this->getLang()->_("api.user.msg.verifystatus.failed"));
            }
            $user->MobileNumber = $mobile;
            $user->MobileVerifyStatus = "Verified";
            $user->save();          
            
            //账户绑定手机成功后的事件调用 子类覆盖这个方法即可
            $this->onAfterBindMobile();
            
            $db->commit();
    
        } catch (Exception $e){
            $db->rollBack();
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.verify.success"));
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
     * 修改密码
     */
    protected function resetPasswordCall(){
        $newPassword = trim($this->_getParam('new_password', ''));
        $oldPassword = trim($this->_getParam('old_password', ''));
    
        if(!$newPassword || !DM_Helper_Validator::checkPassword($newPassword)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.format"));
        }
        if($newPassword == $oldPassword){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.newSameToOld"));
        }
        $this->isLoginOutput();
        $user =DM_Controller_Front::getInstance()->getAuth()->getLoginUser();
    
        if(!$user->verifyPassword($oldPassword)){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.restpwd.oldpwd.unmatch"));
        }
        $user->Password = $user->encodePassword($newPassword);
        //和支付密码一致
        if($user->RefundPassword == $user->Password){
            $this->returnJson(self::STATUS_FAILURE, $this->getLang()->_("api.user.msg.password.inCommonWithRefundPassword"));
        }
        //$user->MemberID = $user->MemberID;
    
        $user->save();
        
        $this->returnJson(parent::STATUS_OK, $this->getLang()->_("api.user.msg.password.updated"));
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
