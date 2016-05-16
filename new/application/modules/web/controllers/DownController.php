<?php
/**
 * 应用下载
 */
include_once dirname(__FILE__).'/Abstract.php';

class Web_DownController extends Web_Abstract
{
    public function indexAction(){
        Zend_Layout::getMvcInstance()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $user = $this->_getParam('user');
        $model = new DM_Module_GetUserAgent();
        $res = $model->getBrowser();
        $this->view->downloadurl =  $this->getConfig()->app->toArray();
        
        if($res=="iphone" || $res=="ipad"){
            DM_Controller_Front::getInstance()->getLayout()->disableLayout();
            $this->render('to');
        }elseif($res=="android"){
            DM_Controller_Front::getInstance()->getLayout()->disableLayout();
            $this->render('to');
        }else{
            $this->render('down');
        }
    
    }
    
    public function toAction(){
        Zend_Layout::getMvcInstance()->disableLayout();
        $this->view->downloadurl =  $this->getConfig()->app->toArray();
    }
}