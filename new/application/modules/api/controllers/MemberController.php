<?php
/**
 * 会员接口
 *  
 * @author Kitty
 * 
 * @since 2015/03/10
 */
class Api_MemberController extends Action_Api
{
	
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}

    /**
     * 退出系统
     *
     * @Privilege user::logout 退出系统
     */
    public function logoutAction() {
        $this->returnResult($this->logoutCall());
    }


    /**
     * 修改密码
     *
     * @Privilege user::reset-password 修改密码
     */
    public function updatePasswordAction()
    {
        $user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
        $password = trim($this->_getParam('password', ''));
        $rePassword = trim($this->_getParam('re_password', ''));

        $aesModel = new Model_CryptAES();       
        $password = $aesModel->decrypt($password);
        $rePassword = $aesModel->decrypt($rePassword);  
        $this->_request->setParam('password',$password);   
        $this->_request->setParam('re_password',$rePassword);

        try{
            $this->resetPasswordCall($user);
        }catch (Exception $e){
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
        $this->returnJson(parent::STATUS_OK, '密码修改成功！');
    }

    /**
     * 发送手机验证码
     *
     * /api/member/send-mobile-code
     */
    protected function sendMobileCodeAction()
    {
        $mobile = trim($this->_getParam('mobile', ''));
        $type = intval($this->_getParam('type'));
        if(!in_array($type, array(2,7,10))){
            $this->returnJson(parent::STATUS_FAILURE, '参数错误！');
        }
        $user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
        $this->sendMobileCodeCall($user,$mobile,$type);
    
        $this->returnJson(parent::STATUS_OK, '验证码发送成功，请检查您的手机！');
    }
    
    /**
     * 手机绑定或解绑
     *
     * /api/member/verify-mobile-code
     */
    protected function verifyMobileCodeAction()
    {
        $user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
        $this->verifyMobileCall($user);
    
        $this->returnJson(parent::STATUS_OK, '操作成功!');
    }

    /**
     * 发送账户激活邮件
     *
     * /api/member/send-email-code
     */
    protected function sendEmailCodeAction()
    {
        $type = intval($this->_getParam('type'));
        if(!in_array($type, array(1,8))){
            $this->returnJson(parent::STATUS_FAILURE, '参数错误！');
        }
        $this->sendActivateAccountMailCall($type);
        
        $this->returnJson(parent::STATUS_OK, '邮件发送成功！');
    }
    
    /**
     * 账户激活
     *
     * /api/member/send-activate-account-mail
     */
    protected function verifyEmailCodeAction()
    {
        $type = intval($this->_getParam('type'));
        if(!in_array($type, array(1,8))){
            $this->returnJson(parent::STATUS_FAILURE, '参数错误！');
        }
        $this->activateAccountCall($type);
    
        $this->returnJson(parent::STATUS_OK, "操作成功！");
    }


    /**
     * 解除绑定
     *
     * /api/member/send-activate-account-mail
     */
    public function unboundAction()
    {
        $this->isLoginOutput();
        $this->unboundCall(); 
        $this->returnJson(parent::STATUS_OK, '解除绑定成功!');
    }



    public function updateProtectAction()
    {
        $this->isLoginOutput();
        $user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
        if($user->MobileVerifyStatus !='Verified'){
            $this->returnJson(parent::STATUS_FAILURE, "请先绑定手机！");  
        }
        $isProtect = $this->_request->getParam('isProtect');
        $this->_request->setParam('isprotect',$isProtect); 
        if(false !== $this->updateProtectCall()){
            $this->returnJson(parent::STATUS_OK, "更新成功！",array('IsProtect' => $isProtect));   
        }else{
            $this->returnJson(parent::STATUS_FAILURE, "更新失败！");   
        }
        
    }


    /**
     * 用户提交反馈
     */
    public function feedbackAction()
    {
        $content = trim($this->_request->getParam('content',''));
        $deviceNo = $this->_request->getParam('deviceID','');
        $platform = $this->_request->getParam('platform',0);
        if(empty($content)){
            $this->returnJson(self::STATUS_FAILURE,'反馈内容不能为空'); 
        }
        if(empty($deviceNo)){
            $this->returnJson(parent::STATUS_FAILURE,'设备号不能为空');
        }
        if(empty($platform)){
            $this->returnJson(parent::STATUS_FAILURE,'平台标示不能为空');
        }

        $data = array(
                'MemberID'=> $this->memberInfo->MemberID,
                'Content' => $content,
                'DeviceNo'=> $deviceNo,
                'Platform'=> $platform,
                'AddTime' => date('Y-m-d H:i:s',time())
            );
        $feedbackModel = new Model_Feedback();
        $return = $feedbackModel->insert($data);
        if($return){
            $this->returnJson(self::STATUS_OK,'提交成功！');
        }else{
            $this->returnJson(self::STATUS_FAILURE,'提交失败！');
        }        
    }

    public function accountSearchableAction()
    {
        if(false !== $this->updateAccountableCall()){
            $this->returnJson(parent::STATUS_OK, "更新成功！"); 
        }else{
            $this->returnJson(parent::STATUS_FAILURE, "更新失败！");  
        }
          
        
    }

    public function mobileSearchableAction()
    {
        if(false !== $this->updateMobileableCall()){
            $this->returnJson(parent::STATUS_OK, "更新成功！");    
        }else{
            $this->returnJson(parent::STATUS_FAILURE, "更新失败！");  
        } 
    }
    
    /**
     * 是否在用户通讯录里推荐我
     */
    public function showContactAction()
    {
    	if(false !== $this->updateContacteableCall()){
    		$this->returnJson(parent::STATUS_OK, "更新成功！");
    	}else{
    		$this->returnJson(parent::STATUS_FAILURE, "更新失败！");
    	}
    }

}
