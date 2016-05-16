<?php
include_once dirname(__FILE__).'/Abstract.php';

class Web_ErrorController extends Web_Abstract
{
    public function errorAction()
	{
		$errors = $this->_getParam('error_handler');
		
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->getResponse()->setHttpResponseCode(400);
				$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');

				//$this->view->title = $this->_('common.404.title');
				//$this->view->content = $this->_('common.404.message', $_SERVER['REQUEST_URI']);
				break;
			default:
				$this->getResponse()->setHttpResponseCode(500);
				$this->getResponse()->setRawHeader('HTTP/1.1 500 Internal Server Error');
				// Application error
				//$this->view->title = $this->_('common.500.title');
				//$this->view->content = $this->_('common.500.message');
				break;
		}
		
		$this->view->exception = $errors->exception;
		$this->view->request   = $errors->request;
	}
}