<?php
/**
 * Auth类
 * 
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Module_Auth extends DM_Module_Base
{
    const AUTH_COOKIE='DM_Auth';
    
    /**
     * 当前登录的用户
     * @var DM_Model_Row_Member
     */
    protected $loginUser=NULL;
    
    protected $isLogin=NULL;
    
    protected $session=NULL;
    
    private static $instance=NULL;
    
    protected function __construct()
    {
    }

    /**
     * 登录
     * 
     * @param string $user
     * @param string $password 密码为NULL表示开放登录
     */
    public function login($user, $password, $login_ip='', $platform='', $deviceID='', $pushID='',$isMemberID = false,$isOverrideLast = false)
    {
        $logModel=new DM_Model_Table_MemberLogs();
        $count=$logModel->getFailedLoginAccount();
        if ($count && $count->count>=10){
            //系统检测到您有异常登录情况，请稍后再试。
            return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.flood.detect"));
        }
        if(!$user){
            return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.user.null"));
        }
        if(!$password && $password !==NULL){
            return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.password.null"));
        }
        $userTable=new DM_Model_Table_Members();
        if (DM_Helper_Validator::isEmail($user)){
            $userInfo=$userTable->getByEmail($user);
        }elseif(DM_Helper_Validator::checkmobile($user) && $isMemberID == false){
           $userInfo= $userTable->getByMobile($user);
        }elseif(intval($user) > 0 && $isMemberID){
            $userInfo = $userTable->getById($user);
        }else{
            $userInfo = array();
        }
        
        if (!$userInfo){
            return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.userInfo.null"));
        }
        
        if ($this->isLogin() && !$isOverrideLast){//已登录
            if ($this->getLoginUser()->MemberID!=$userInfo->MemberID){//不同用户
                //翻译，其他用户已登录，请退出后再登录。
                return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.msg.login.other.logged"));
            }else{
                return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.login.ok"),array('MemberID'=>$userInfo->MemberID));
            }    
        }
        
        if ($password!==NULL && $userInfo->Password!==$userInfo->encodePassword($password)){
            //登录失败
            $userInfo->addLog(DM_Model_Table_MemberLogs::ACTION_LOGIN_FAIL, 'Use account: '.$user .' Failed.');
            return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.msg.login.failed"));
        }
        
        /////////登录成功/////////
        $userInfo->LastLoginIp=$login_ip;
        if(!empty($platform)){
            $userInfo->Platform=$platform;
        }
        if(!empty($deviceID)){
            $userInfo->DeviceID=$deviceID;
        }
        if(!empty($pushID)){
            $userInfo->PushID=$pushID;
        }
        $userInfo->LastLoginDate=DM_Helper_Utility::getDateTime();
        $userInfo->save();
        $userInfo->addLog(DM_Model_Table_MemberLogs::ACTION_LOGIN, 'Use account: '.$user .($password===NULL ? ' open' : ' password'));
        $this->getSession()->MemberID=$userInfo->MemberID;
        $this->getSession()->Email=$userInfo->Email;

        $this->isLogin=true;
        $this->loginUser=$userInfo;
        
        //临时密钥
        $this->getSession()->Password=DM_Helper_Utility::createHash(32);
        $auth=DM_Helper_Utility::authcode("{$this->session->MemberID}\t{$this->session->Email}\t{$this->session->Password}", 'ENCODE');
        setcookie(self::AUTH_COOKIE, $auth, time()+86400*365, '/', '', false, true);
        $_COOKIE[self::AUTH_COOKIE]=$auth;//注册后可马上获取登录信息
        
        return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.login.ok"),array('MemberID'=>$this->session->MemberID));
    }
    
    /**
     * 退出
     */
    public function logout()
    {
        $this->getSession()->MemberID=NULL;
        $this->getSession()->Email=NULL;
        $this->getSession()->Password=NULL;
        
        setcookie(self::AUTH_COOKIE, '', -time(), '/', '', false, true);
        
        $this->isLogin=false;
        $this->loginUser=NULL;
        
        return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.logout.ok"));
    }
    
    /**
     * 是否登录状态
     */
    public function isLogin()
    {
        if ($this->isLogin===NULL){
            $authCookie=DM_Controller_Front::getInstance()->getInstance()->getHttpRequest()->getCookie(self::AUTH_COOKIE, '');
            if (empty($authCookie)){
                $this->isLogin=false;
                if ($this->getSession()->MemberID){
                    $this->logout();
                }
            }else{
                $cookieUser=explode("\t", DM_Helper_Utility::authcode($authCookie));
                if (count($cookieUser)==3 && $cookieUser[0]==$this->getSession()->MemberID
                         && $cookieUser[1]==$this->getSession()->Email 
                         && $cookieUser[2]==$this->getSession()->Password  ){
                    $this->loginUser=DM_Model_Table_Members::create()->getByPrimaryId($this->getSession()->MemberID);
                    if (!$this->loginUser){
                        $this->logout();
                    }else{
                        $this->isLogin=true;
                    }
                }else{
                    $this->isLogin=false;
                }
            }
        }

        return $this->isLogin;
    }
    
    /**
     * 获取当前登录用户
     * 
     * @return DM_Model_Row_Member
     */
    public function getLoginUser()
    {
        if ($this->isLogin()){
            if (!$this->loginUser) throw new Exception('Login Auth module exception.');
            return $this->loginUser;
        }else{
            return NULL;
        }
    }
    
    /**
     * 获取session
     * 
     * @return Zend_Session_Namespace
     */
    protected function getSession()
    {
        if ($this->session===NULL){
            throw new Exception('Please set auth session instace firstly.');
        }
        
        return $this->session;
    }
    
    /**
     * 设置session
     */
    public function setSession(Zend_Session_Namespace $session)
    {
        $this->session=$session;
        return $this;
    }
    
    /**
     * 获取单实例
     *
     * @return DM_Module_Auth
     */
    public static function getInstance()
    {
        if (self::$instance===NULL){
            self::$instance=new self();
        }
    
        return self::$instance;
    }
}