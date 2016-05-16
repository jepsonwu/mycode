<?php
include_once dirname(__FILE__).'/Abstract.php';

class Web_AccountController extends Web_Abstract
{
    /**
     * 登录
     */
    public function loginAction()
    {
        //die($this->getLang()->_("api.user.settradepwd.error.setted"));
        //Where are you from
        $backurl = '';
        if(isset($_SERVER['HTTP_REFERER'])){
            if(strpos($_SERVER['HTTP_REFERER'],'/account')===FALSE){
                $backurl = $_SERVER['HTTP_REFERER'];
            }else{
                $backurl = '/member';
            }
        }
        if($this->isLogin()){
            $this->_redirect($backurl);
        }
        $this->view->backurl = $backurl;
        $this->view->csrfCode = $this->createCsrfCode();
    }
    
    /**
     * 用户注册
     */
    public function registerAction(){
        //检查是否登录
        if($this->isLogin()){
            $this->_redirect("/member");
        }
        $this->view->csrfCode = $this->createCsrfCode();
    }
    /**
     * 注册成功后处理页面
     */
    public function registerSuccessAction()
    {
        $email = $this->_getParam('email');
        $this->view->email = $email;
    }

    /**
     * 获取注册验证码
     */
    public function captchaAction()
    {
        Zend_Captcha_Word::$V  = array("a", "e", "u", "y");
        Zend_Captcha_Word::$VN = array("a", "e", "u", "y","2","3","4","5","6","7","8","9");
        Zend_Captcha_Word::$C  = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z");
        Zend_Captcha_Word::$CN = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z","2","3","4","5","6","7","8","9");
        $captcha = new Zend_Captcha_Image(array(
                'width'=>120,
                'height'=>50,
        ));
        $captcha->setFont(APPLICATION_PATH.'/data/fonts/verdana.ttf');
        $captcha->setImgDir(APPLICATION_PATH.'/../public/captcha/');
        $captcha->setImgUrl('/captcha/');
        $captcha->setFontSize(28);
        $captcha->setWordlen(4);
        $captcha->setExpiration(60);  //每5秒
        $captcha->setGcFreq(20);  //删除旧文件
        $id=$captcha->generate();
        $_SESSION['captcha']=$captcha->getWord();
        echo $captcha->getImgUrl().$id.$captcha->getSuffix();
        
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /**
     * 用户协议
     */
    public function userAgreementAction()
    {
    }
    
    /**
     * 找回密码
     */
    public function forgetPasswordAction()
    {
    }



}
