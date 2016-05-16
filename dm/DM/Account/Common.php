<?php

/**
 * 登陆认证公共抽象类
 * 判断是否登陆
 * 获取登陆信息
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午1:35
 */
abstract class DM_Account_Common
{
	/**
	 * session 命名空间
	 */
	const SESSION_NAMESPACE = 'default';

	/**
	 * 当前用户信息
	 * @var null
	 */
	protected $_member_info = null;

	/**
	 * 获取用户信息
	 * @throws Exception
	 */
	public function init()
	{
		$this->_member_info = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
	}

	/**
	 * 判断是否登陆
	 * @return bool|null
	 */
	public function isLogin()
	{
		return DM_Module_Account::getInstance()->setSession($this->getSession())->isLogin();
	}

	/**
	 * 获取session
	 * @return Zend_Session_Namespace
	 */
	protected function getSession()
	{
		return DM_Controller_Front::getInstance()->getSession(self::SESSION_NAMESPACE);
	}

	/**
	 * 设置session
	 * @param $session
	 * @return $this
	 */
	protected function setSession($session)
	{
		return DM_Module_Account::getInstance()->setSession($session);
	}
}