<?php
class Member_Abstract extends DM_Controller_Web{

    public function setActiveHeaderTab($tab){
        $this->view->activeTab=$tab;
    }
    
    public function setActiveMenuTab($tab){
        $this->view->activeMenuTab=$tab;
    }
    
    public function init(){
        parent::init();
     
        if(!$this->isLogin()){
            $this->_redirect("/account/login");
        }
            
        $this->setActiveHeaderTab('member');
    }
    
}