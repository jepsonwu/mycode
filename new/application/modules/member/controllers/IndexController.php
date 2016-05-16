<?php
include_once dirname(__FILE__).'/Abstract.php';

class Member_IndexController extends Member_Abstract {
	
	/**
	 * 主页
	 */
	public function indexAction() {
	    $this->view->balanceInfo=$this->getLoginUser()->getAmountInfo();
	}
	
}