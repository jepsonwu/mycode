<?php
/**
 * API共用控制器
 * 
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Api_Oauth extends DM_Controller_Api
{
    /**
     * 通过开放平台登录
     * 
     * TODO
     *
     * @Privilege user::open-login 通过开放平台登录
     */
    public function openLoginAction() {
        
    }
    
    /**
     * 跳转过来的，非API接口
     * 
     * 原始登录地址类似：
     * https://www.4.cn/oauth/signin?client_id=b527ce1045&state=fa667ea24b450c11a68badedefdfb46d&redirect_uri=http://www.dn.com/api/member/oauth-login4cn&lang=zh
     */
    public function oauthLogin4cnAction()
    {
        header('content-type:text/html; charset=utf-8');
        if (trim($this->_getParam('state'))!=$this->getSession()->state){
            echo $this->getLang()->_("front.account.oauth.state.illegal");die();
        }
    
        $code = trim($this->_getParam('code'));
        $oauth=$this->getConfig()->oauth->cn4;
    
        $result=DM_Controller_Front::curl($oauth->access_token, array('code'=>$code, 'client_id'=>$oauth->appid, 'client_secret'=>$oauth->appkey, 'redirect_uri'=>$oauth->callback_url));
        $result=json_decode($result, true);
        if (empty($result['access_token'])){
            echo $this->getLang()->_("front.account.oauth.access.fail");die();
        }
    
        $access=$result['access_token'];
        $userinfo=DM_Controller_Front::curl($oauth->userinfo_url, array('access_token'=>$access, 'client_id'=>$oauth->appid, 'client_secret'=>$oauth->appkey, 'redirect_uri'=>$oauth->callback_url));
        $userinfo=json_decode($userinfo, true);
        if (empty($userinfo['mid']) || empty($userinfo['mid'])){
            echo $this->getLang()->_("front.account.oauth.profile.fail");die();
        }
    
        $newUser=false;
        $mode=new DM_Model_Table_MemberConnect();
        $connect=$mode->getByPlatformID($mode::PLATFORM_4CN, $userinfo['mid']);
        if (!$connect){
            $result=$this->createOauthUserCall($userinfo['email'], $userinfo['name']);
            if ($result['flag']<0) {
                echo $result['msg'];
                die();
            }
    
            $newUser=true;
            $user=$result['data'];
            //4.cn同步登陆后用户身份验证自动为已认证，账号已激活。
            $user->EmailVerifyStatus='Verified';
            $user->IdcardVerifyStatus='Verified';
            $user->save();
    
            $connect=$mode->add($user->MemberID, $mode::PLATFORM_4CN, $userinfo['mid'], $access, $userinfo);
        }
    
        if (!$connect){
            echo $this->getLang()->_("front.account.oauth.profile.fail");die();
        }
    
        $userTable=new DM_Model_Table_Members();
        $user=$userTable->getByPrimaryId($connect->MemberID);
        if (!$user){
            echo $this->getLang()->_("api.user.login.userInfo.null");die();
        }
    
        $result=DM_Module_Auth::getInstance()->setSession($this->getSession())->login($user->Email, NULL, $this->getRequest()->getClientIp());
        if ($result['flag']<0) {
            echo $result['msg'];
            die();
        }
    
        if ($newUser){
            header('location:/member/account/set-password');
        }else{
            header('location:/member');
        }
    }
       
}