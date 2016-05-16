<?php

/**
 * web通用控制器
 *
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Controller_Web extends DM_Controller_Common
{
	/**
	 * session命名空间
	 * @var string
	 */
	const SESSION_NAMESPACE = 'web';

	public function init()
	{
		parent::init();
		Zend_Layout::startMvc();

		$this->initView();

		//初始化各种静态链接地址
		$this->getStaticUrl();

		$this->view->locale = $this->getLocale();
		//csrf
		$this->view->CsrfCode = $this->createCsrfCode();

		//登录用户
//         if ($this->isLogin()){

//             $this->view->self=$this->getLoginUser();
//         }

		$this->controller_name = $this->view->controller_name = $this->_request->getControllerName();
		$this->action_name = $this->view->action_name = $this->_request->getActionName();
	}


}
