<?php

/**
 * 活动公共模块
 * User: jepson <jepson@duomai.com>
 * Date: 16-2-22
 * Time: 下午12:55
 */
class Action_Activity extends Action_Api
{
	//活动过期
	const ACTIVITY_INVALID_CODE = -1000;

	/*
	 * 活动配置信息
	 * 活动时间限制 微信用户信息获取模式 cookie 支持重写
	 */
	protected $_conf = array(
//		"start_time" => "2016-01-25", //活动时间
//		"end_time" => "2016-02-23",
		"activity_cookie" => "ACTIVITYCOOKIE",//普通登陆cookie
		"activity_cookie_time" => 2592000,
	);

	//微信开放平台model
	protected $_wechat_open_model = null;

	public function init()
	{
		parent::init();

		$this->isValid();
	}

	/**
	 * 判断时间有效性
	 */
	final protected function isValid()
	{
		$is_valid = true;
		$time = time();

		isset($this->_conf['start_time']) && $time < strtotime($this->_conf['start_time']) && $is_valid = false;
		isset($this->_conf['end_time']) && $time > strtotime($this->_conf['end_time']) && $is_valid = false;

		!$is_valid && parent::failReturn("活动已过期！", self::ACTIVITY_INVALID_CODE);
	}

	/**
	 * 判断是否在财猪APP
	 * @return bool
	 */
	final protected function isApp()
	{
		return strpos($this->_request->getServer("HTTP_USER_AGENT"), "caizhuapp") !== false ? true : false;
	}

	/**
	 * 提供一套最简单常用的COOKIE登陆模式，例如通过手机号确定用户
	 * @param $cookie
	 * @param null $name
	 * @param int $timeout
	 * @return bool
	 */
	final protected function setCookie($cookie, $name = null, $timeout = null)
	{
		$cookie = DM_Helper_Utility::authcode($cookie, 'ENCODE');
		$timeout = is_null($timeout) ? $this->_conf['activity_cookie_time'] : $timeout;
		return setcookie((is_null($name) ? $this->_conf['activity_cookie'] : $name),
			$cookie, $timeout == 0 ? 0 : time() + $timeout, '/', '', false, true);
	}

	/**
	 * 获取cookie
	 * @param null $name
	 * @return null|string
	 */
	final protected function getCookie($name = null)
	{
		$name = is_null($name) ? $this->_conf['activity_cookie'] : $name;
		$cookie = DM_Controller_Front::getInstance()->getHttpRequest()->getCookie($name, '');
		return DM_Helper_Utility::authcode($cookie);
	}

	/*****----------------------------------------微信网页授权（第三方开放平台模式）--------------------------------------***/
	/**
	 * 创建开放平台模型
	 */
	protected function createWechatOpenModel($app_id)
	{
		if (is_null($this->_wechat_open_model)) {
			$config = array(
				"oauth_userinfo_redirect" => $this->createWechatOpenRedirectUri("wechat-open-oauth-userinfo-redirect"),
				"oauth_base_redirect" => $this->createWechatOpenRedirectUri("wechat-open-oauth-base-redirect"),
			);
			$this->_wechat_open_model = Model_WechatOpen_Common::getInstance($config);
			$this->_wechat_open_model->wechatModel($app_id);
		}
	}

	/**
	 *
	 * 创建回调页
	 * @param $action
	 * @return string
	 */
	protected function createWechatOpenRedirectUri($action)
	{
		return $this->_request->getScheme() . "://" . $this->_request->getHttpHost() . "/" .
		$this->_request->getParam("module") . "/" . $this->_request->getParam("controller") .
		"/" . $action;
	}

	protected $wechatOpenOauthIndexConf = array(
		array("app_id", "require", "请填写APPID！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 需要微信网页认证的活动，首页打开该地址
	 */
	public function wechatOpenOauthIndexAction()
	{
		try {
			$this->createWechatOpenModel($this->_param['app_id']);

			$user_info = $this->_wechat_open_model->oauthIndex();
			if (is_string($user_info))
				$this->_redirect($user_info);
			else
				$this->wechatOpenIndexDo($user_info);
		} catch (Exception $e) {
			$this->failReturn($e->getMessage());
		}
	}

	protected $wechatOpenOauthBaseRedirectConf = array(
		array("code", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("state", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("appid", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * snsapi_base 回调接口
	 */
	public function wechatOpenOauthBaseRedirectAction()
	{
		try {
			$this->createWechatOpenModel($this->_param['appid']);

			$user_info = $this->_wechat_open_model->oauthBaseRedirect($this->_param['code']);
			if (is_string($user_info))
				$this->_redirect($user_info);
			else
				$this->wechatOpenIndexDo($user_info);
		} catch (Exception $e) {
			$this->failReturn($e->getMessage());
		}
	}

	protected $wechatOpenOauthUserinfoRedirectConf = array(
		array("code", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("state", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("appid", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * snsapi_userinfo 回调接口
	 */
	public function wechatOpenOauthUserinfoRedirectAction()
	{
		try {
			$this->createWechatOpenModel($this->_param['appid']);

			$this->wechatOpenIndexDo($this->_wechat_open_model->oauthUserinfoRedirect($this->_param['code']));
		} catch (Exception $e) {
			$this->failReturn($e->getMessage());
		}
	}

	/**
	 * 微信开放平台业务逻辑首页
	 * @param $user_info
	 */
	public function wechatOpenIndexDo($user_info)
	{

	}
}