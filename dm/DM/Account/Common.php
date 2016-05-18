<?php

/**
 * 登陆认证公共抽象类，包含如下模块
 * 1.登陆操作
 * 2.session
 * 3.登陆用户信息
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午1:35
 */
abstract class DM_Account_Common
{
	/**
	 * session 命名空间
	 */
	const SESSION_NAMESPACE = 'DEFAULT';

	/**
	 * cookie名称
	 */
	const ACCOUNT_COOKIE = self::SESSION_NAMESPACE . "_ACCOUNT_COOKIE";

	/**
	 * session
	 * @var null
	 */
	protected $_session = null;

	/**
	 * 当前用户信息
	 * @var null
	 */
	protected $_member_info = null;

	/**
	 * 配置信息
	 * @var null
	 */
	protected $_config = null;

	protected $_is_login = null;

	/**
	 * 获取用户信息
	 * @throws Exception
	 */
	public function init()
	{
		//session
		$this->getSession();

		//member_info
		$this->getLoginUser();

		//config todo 这里统一取引用 减少变量的复制
		$this->_config = DM_Controller_Front::getInstance()->getConfig();
	}

	abstract public function register();

	abstract public function login();

	abstract public function logout();

	abstract public function resetPassword();

	abstract public function getLoginUser();

	abstract public function isLogin();

	/**
	 * 获取session
	 */
	protected function getSession()
	{
		is_null($this->_session) && $this->_session = new Zend_Session_Namespace(self::SESSION_NAMESPACE);
	}

	protected function setSession($session)
	{

	}
}