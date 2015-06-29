<?php
namespace Home\Controller;

use Home\Controller\CommonController;

class PublicController extends CommonController
{

	public function Login()
	{
		$this->display();
	}

	public function Check_login()
	{
		
	}


	/**
	 * 生成验证码
	 */
	public function Verify()
	{
		$verify = new \Think\Verify();
		$verify->entry();
	}
}
