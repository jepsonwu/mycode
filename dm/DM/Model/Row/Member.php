<?php
/**
 * 用户行对象基类
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Model_Row_Member extends DM_Model_Row
{
    //手机验证
    const STATUS_MOBILE_PENDING='Pending';
    const STATUS_MOBILE_VERIFIED='Verified';
    const STATUS_MOBILE_PROCESSING='Processing';
    const STATUS_MOBILE_FAILED='Failed';
    
    /**
     * 创建一个登录密钥
     * 
     * @param string $password
     */
    public function createPassword($password)
    {
        //Salt一经初始化，不能再改，其他支付密码等关联Salt的业务密码会失效
        if (!$this->Salt){
            $this->Salt=DM_Helper_Utility::createHash(6);
        }
        $this->Password=$this->encodePassword($password);
        
        return true;
    }
    
    /**
     * 创建支付密钥
     *
     * @param string $password
     */
    public function createRefundPassword($password)
    {
        $this->RefundPassword=$this->encodePassword($password);
        
        return true;
    }
    
    /**
     * 密码加密算法
     * 
     * @param string $password
     * @return string
     */
    public function encodePassword($password)
    {
        return md5(md5($password.'_$$_DMLIB').$this->Salt);
    }
    
    /**
     * 验证密码是否正确
     *
     * @param string $password
     * @return string
     */
    public function verifyPassword($password)
    {
        return $this->encodePassword($password)==$this->Password;
    }
    
    /**
     * 验证支付密码
     *
     * @param string $password
     * @return string
     */
    public function verifyRefundPassword($refundPassword)
    {
        return $this->encodePassword($refundPassword)==$this->RefundPassword;
    }
    
    /**
     * 添加一条用户操作日志
     */
    public function addLog($action, $info='', $extra='',$platform=0,$deviceID='',$remark = false)
    {
        if($remark== true){
            $log = new DM_Model_Account_MemberLogs();
        }else{
            $log = new DM_Model_Table_MemberLogs();
        }
        return $log->add($this->MemberID, $action, $info, $extra, $platform, $deviceID);
    }
    
    /**
     * 创建验证码
     * 
     * @param string $IdentifyID 手机或邮箱
     */
    public function createVerifyCode($VerifyType = 1,$mobile='')
    {
        $model=new DM_Model_Table_MemberVerifys;
        $unit=$model->createVerify($VerifyType, $mobile, $this->MemberID);
        
        return $unit;
    }
    
    /**
     * 发送验证码相关邮件方法（通用版，需要传递验证码类型参数）
     * 
     * @param int $VerifyType
     * @param string $mail
     * @param int $SendType 1亚马逊 2mailgun
     * @param bool $emailActivateLinkShowParams (注册时发送的)激活邮件内容中的激活链接是否显示参数，默认否
     */
    public function sendActivateAccountMail($VerifyType=1,$mail='',$username='',$send_type = 0, $emailActivateLinkShowParams = false)
    {
        if (empty($mail)) $mail=$this->Email;
        //$name=$this->Name ? $this->Name : array_shift(explode('@', $mail));

        $code=$this->createVerifyCode($VerifyType,$mail);
        if (!$code) return false;
        
        $lang=DM_Controller_Front::getInstance()->getLang();
        //return DM_Module_EDM_Email::send($mail, $name, $lang->_('user.edm.account.activate.title'),  $lang->_('user.edm.account.activate.content', $mail, $code->VerifyCode));
        $content = $title = '';
        switch ($VerifyType){
            case 1:    //邮箱激活
                $title = $lang->_('user.edm.account.activate.title');
                if ($emailActivateLinkShowParams) {
                    $content = $lang->_('user.edm.account.activate.content', empty($username)?$mail:$username, $code->VerifyCode, empty($username)?$mail:$username, $code->VerifyCode);
                } else {
                    $content = $lang->_('user.edm.account.activate.content', empty($username)?$mail:$username, $code->VerifyCode);
                }
                break;
            case 3:    //找回登录密码
                $title = $lang->_('user.edm.password.reset.title');
                $content = $lang->_('user.edm.password.reset.content', $mail, $code->VerifyCode);
                break;
            case 4:    //找回支付密码
                $title = $lang->_('user.edm.refundpassword.reset.title');
                $content = $lang->_('user.edm.refundpassword.reset.content', $mail, $code->VerifyCode);
                break;
            case 8:
                $title = $lang->_('user.edm.account.unbind.title');
                $content = $lang->_('user.edm.account.unbind.content', $mail, $code->VerifyCode);
                break;
        }
        //获取各项目的具体mail配置
        $mailConfig = DM_Controller_Front::getInstance()->getConfig()->mail;
        $mailArray = array(
                'type'=>$send_type ? $send_type : $mailConfig->type,
                'key'=>$mailConfig->key,
                'to'=>$mail,
                'mail_title'=>$title,
                'mail_content'=>$content,
        );

        return DM_Controller_Front::getInstance()->curl($mailConfig->url,$mailArray);
    }
    
    /**
     * 发送邮箱激活验证码邮件
     *
     * @param int $VerifyType
     * @param string $mail
     * @param int $SendType 1亚马逊 2mailgun
     * @param bool $emailActivateLinkShowParams (后台发送的卖家)激活邮件内容中的激活链接是否显示参数，默认否
     */
    public function sendVerifyEmailMail($mail='', $SendType=0, $emailActivateLinkShowParams = false)
    {
        if (empty($mail)) $mail=$this->Email;
    
        $code=$this->createVerifyCode(1);
        if (!$code) return false;
    
        $lang=DM_Controller_Front::getInstance()->getLang();
        $content = $title = '';

        $title = $lang->_('user.edm.account.activate.title');
        if ($emailActivateLinkShowParams) {
            $content=$lang->_('user.edm.account.activate.content', $mail, $code->VerifyCode, $mail, $code->VerifyCode);
        } else {
            $content=$lang->_('user.edm.account.activate.content', $mail, $code->VerifyCode);
        }

        //获取各项目的具体mail配置
        $mailConfig = DM_Controller_Front::getInstance()->getConfig()->mail;
        $mailArray = array(
                'type'=>$SendType?$SendType:$mailConfig->type,
                'key'=>$mailConfig->key,
                'to'=>$mail,
                'mail_title'=>$title,
                'mail_content'=>$content,
        );
        return DM_Controller_Front::getInstance()->curl($mailConfig->url,$mailArray);
    }
    
    /**
     * 发送找回密码邮件
     *
     * @param string $mail
     */
    public function sendResetPasswordMail($mail=NULL,$username='')
    {
        if (empty($mail)) $mail=$this->Email;
    
        $code=$this->createVerifyCode(3,$mail);
        if (!$code) return false;
    
        $lang=DM_Controller_Front::getInstance()->getLang();
        $content = $title = '';

        $title = $lang->_('user.edm.password.reset.title');
        $content=$lang->_('user.edm.password.reset.content', $username, $code->VerifyCode);

        //获取各项目的具体mail配置
        $mailConfig = DM_Controller_Front::getInstance()->getConfig()->mail;
        $mailArray = array(
                'type'=>$mailConfig->type,
                'key'=>$mailConfig->key,
                'to'=>$mail,
                'mail_title'=>$title,
                'mail_content'=>$content
        );
        return DM_Controller_Front::getInstance()->curl($mailConfig->url,$mailArray);
    }
    
    /**
     * 发送找回支付密码邮件
     *
     * @param string $mail
     */
    public function sendResetRefundPasswordMail($mail=NULL)
    {
        if (empty($mail)) $mail=$this->Email;
    
        $code=$this->createVerifyCode(4);
        if (!$code) return false;
    
        $lang=DM_Controller_Front::getInstance()->getLang();
        $content = $title = '';

        $title = $lang->_('user.edm.refundpassword.reset.title');
        $content=$lang->_('user.edm.refundpassword.reset.content', $mail, $code->VerifyCode);

        //获取各项目的具体mail配置
        $mailConfig = DM_Controller_Front::getInstance()->getConfig()->mail;
        $mailArray = array(
                'type'=>$mailConfig->type,
                'key'=>$mailConfig->key,
                'to'=>$mail,
                'mail_title'=>$title,
                'mail_content'=>$content
        );
        return DM_Controller_Front::getInstance()->curl($mailConfig->url,$mailArray);
    }
    
    /**
     * 发送绑定谷歌验证器key邮件
     *
     * @param string $mail
     */
    public function sendResetGoogleKeyMail($mail=NULL)
    {
        if (!$mail) $mail=$this->Email;
        $name=$this->Name ? $this->Name : array_shift(explode('@', $mail));
        
        $code=$this->createVerifyCode($mail);
        if (!$code) return false;
        
        $lang=DM_Controller_Front::getInstance()->getLang();
        return DM_Module_EDM_Email::send($mail, $name, $lang->_('user.edm.googlekey.reset.title'),  $lang->_('user.edm.googlekey.reset.content', $mail, $code->VerifyCode));
    }
    
    /**
     * 发送手机绑定验证码短信
     *
     * @param string $mail
     */
    public function sendMobileCode($mobile,$type=2)
    {
        if (!$mobile) return false;
        $code=$this->createVerifyCode($type,$mobile);
        if (!$code) return false;
    
        $lang=DM_Controller_Front::getInstance()->getLang();
        $message = '';
        if($type == 2){
            $message = $lang->_('user.edm.bind.phone.content', $code->VerifyCode);
        }
        if($type == 7){
            $message = $lang->_('user.edm.unbind.phone.content', $code->VerifyCode);
        }
		if($type == 10){
            $message = $lang->_('user.edm.payPassword.phone.content', $code->VerifyCode);
        }
   
        $result = DM_Module_EDM_Phone::send($mobile,$message);
        
        return $result;
    }
    
    public function isMailVerified()
    {
        return $this->EmailVerifyStatus=='Verified';
    }
    
    public function isMobileVerified()
    {
        return $this->MobileVerifyStatus=='Verified';
    }
    
    /**
     * 获取账户余额信息
     */
    public function getAmountInfo()
    {
        $table=new DM_Model_Table_Finance_Amount();

        return $table->getAllBalance($this->MemberID);
    }
    
    /**
     * 获取账户余额信息
     * 
     * @param string $currency 货币类型
     */
    public function getBalance($currency)
    {
        $table=new DM_Model_Table_Finance_Amount();
    
        return $table->getMemberBalance($this->MemberID, $currency);
    }
    
    /**
     * 账户状态操作
     *
     * @param string $Status 
     * @param timestamp $unBandedTime 解禁时间，仅管理员暂停时有效 
     * @return bool
     */
    public function opStatus($Status,$unBandedTime='')
    {
        switch ($Status){
            case 'NORMAL'://解禁
                $this->Status = 'NORMAL';
                break;
            case 'BANNED'://禁止
                $this->Status = 'BANNED';
                break;
            case 'CANCELED'://用户申请撤销
                $this->Status = 'CANCELED';
                break;
            case 'SUSPEND'://暂停
                $this->Status = 'SUSPEND';
                $this->BanEnd = !empty($unBandedTime)?$unBandedTime:date('Y-m-d H:i:s',strtotime('+1 day'));
                break;
            default:
                return false;
                break;
        }
        $this->save();
        return true;
    }

}
