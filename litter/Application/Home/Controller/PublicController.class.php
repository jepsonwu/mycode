<?php
namespace Home\Controller;

use Think\Controller;

class PublicController extends Controller
{

	public function Login()
	{
		$this->display();
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
