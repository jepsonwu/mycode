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

	const SESSION_NAMESPACE = 'API';

	//用户身份认证错误code码
	protected $_account_code_map = array();

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

	/**
	 * user[email|mobile|username|] password
	 * 原本的session机制之外 附加cookie信息 比对cookie和sessin信息是否一致 增加安全性
	 * @return mixed
	 * @throws Exception
	 */
	public function login()
	{
		$userModel = new DM_Model_Account_Members();
		$user_info = array();

		//状态判断

		//是否登陆 不同帐号在登陆

		//密码判断 加密处理 盐

		//验证码判断 错误 过期

		//账号保护 不同设备登陆

		//记录日志和修改数据库

		//cookie session
	}

	/**
	 * 退出登陆
	 * @return bool
	 */
	public function logout()
	{
		$this->_session->MemberID = null;
		$this->_session->Password = null;//临时密码

		setcookie(self::ACCOUNT_COOKIE, '', -time(), '/', $this->_config['session']['cookie_domain'], false, true);
		$this->_is_login = false;
		$this->_session->unsetAll();
		empty($_SESSION) && Zend_Session::destroy(true, false);

		return true;
	}

	protected function resetPassword()
	{

	}

	protected function getLoginUser()
	{

	}

	public function isLogin()
	{
		if ($this->isLogin === NULL) {
			$authCookie = DM_Controller_Front::getInstance()->getInstance()->getHttpRequest()->getCookie(self::AUTH_COOKIE, '');
			if (empty($authCookie)) {
				$this->isLogin = false;
				if ($this->getSession()->MemberID) {
					$this->logout();
				}
			} else {
				$cookieUser = explode("\t", DM_Helper_Utility::authcode($authCookie));
				if (count($cookieUser) == 3 && $cookieUser[0] == $this->getSession()->MemberID
					&& $cookieUser[1] == $this->getSession()->Email
					&& $cookieUser[2] == $this->getSession()->Password
				) {
					$this->loginUser = DM_Model_Account_Members::create()->getByPrimaryId($this->getSession()->MemberID);
					if (!$this->loginUser) {
						$this->logout();
					} else {
						$this->isLogin = true;
					}
				} else {
					$this->isLogin = false;
				}
			}
		}

		return $this->isLogin;
	}
}