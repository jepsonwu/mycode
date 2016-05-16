<?php
class Action_Member extends DM_Controller_Web
{
    public function init()
    {
        parent::init();
       if(!$this->isLogin()){
        $url = DM_Controller_Front::getInstance()->getConfig()->system->login_url;
        $this->_redirect($url);
        }else{
            $this->memberInfo = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
            $this->view->memberInfo = $this->memberInfo;
            Zend_Layout::startMvc(array('layoutPath' => APPLICATION_PATH . "/modules/member/views/scripts/layout"));
        }
    }

    /**
     * 是否登录
     */
    public function isLogin()
    {
    	return DM_Module_Account::getInstance()->setSession($this->getSession())->isLogin();
    }
}
