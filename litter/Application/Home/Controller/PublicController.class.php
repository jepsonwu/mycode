<?php
namespace Home\Controller;

use Think\Controller;

class PublicController extends Controller
{

	/**
	 * 登录
	 */
	public function Login()
	{
		$this->display();
	}

	/**
	 * 生成验证码
	 */
	public function Verify()
	{
		ob_end_clean();
		$verify = new \Think\Verify(C("VERIFY_CONFIG"));
		$verify->entry();
	}

	/**
	 * 登录
	 */
	public function User_login()
	{
		pre("a");
	}
}
