<?php

/**
 * api登陆认证公共类
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午1:38
 */
class DM_Account_Api extends DM_Account_Common
{
	protected static $instance = null;

	protected function __construct()
	{
	}

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 发送手机验证码
	 * @param $mobile
	 * @return bool|json
	 * @throws Exception
	 */
	public function sendVerify($mobile)
	{
		//验证码模块
		$memberVerify = new DM_Model_Table_MemberVerifys();
		$count = $memberVerify->getSendCount($mobile);
		if ($count >= 3)
			throw new Exception("同一手机号一天只能发送3次！", "");//todo code message 集中管理在restfull模块

		//创建验证码
		$code = $memberVerify->createVerify(5, $mobile, 0);
		if (!$code)
			return false;

		// $lang=DM_Controller_Front::getInstance()->getLang();
		// $message = $lang->_('user.edm.phone.register.content',$code->VerifyCode);

		$message = "您好，您正在进行注册会员操作，手机验证码为：" . $code->VerifyCode;
		return DM_Module_EDM_Phone::send($mobile, $message);
	}

	public function register()
	{

	}

	public function login()
	{
	}

	public function logout()
	{

	}

	protected function resetPassword()
	{

	}
}