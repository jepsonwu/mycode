<?php

/**
 * 微信开放平台公共模块  包括：网页授权 用户信息管理 消息发送机制处理（路由，这一点很重要）
 *
 * user sql:
 * CREATE TABLE `activity_wechat_user` (
 * `UID`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
 * `Unionid`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `HeadImgUrl`  varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `Country`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `City`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `Province`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `Sex`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '0-未知，1-男性，2-女性' ,
 * `Nickname`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `CreateTime`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
 * `UpdateTime`  timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ,
 * `Language`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `Subscribe`  tinyint(1) NOT NULL DEFAULT 0 ,
 * `SubscribeTime`  timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ,
 * `Remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * `TagidList`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ,
 * PRIMARY KEY (`UID`),
 * UNIQUE INDEX `unionid` (`Unionid`) USING BTREE
 * )
 * ENGINE=InnoDB
 * DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
 * COMMENT='微信用户表'
 * AUTO_INCREMENT=1
 * ROW_FORMAT=COMPACT
 * ;
 *
 * CREATE TABLE `activity_wechat_user_openid` (
 * `UID`  int(11) UNSIGNED NOT NULL DEFAULT 0 ,
 * `Openid`  varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' ,
 * `AppID`  varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' ,
 * PRIMARY KEY (`UID`)
 * )
 * ENGINE=InnoDB
 * DEFAULT CHARACTER SET=utf8 COLLATE=utf8_unicode_ci
 * COMMENT='微信用户openid机制'
 * ROW_FORMAT=COMPACT
 * ;
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-9
 * Time: 下午5:08
 */
class Model_WechatOpen_Common
{
	protected $config = array(
		"oauth_cookie" => "WECHATOPENOAUTHCOOKIE",//微信开放平台网页授权cookie
		"oauth_cookie_time" => 0,
		"oauth_model" => 2,//网页授权模式 1-实时更新 cookie模式 2-存储数据库 静态模式

		"oauth_userinfo_redirect" => "",
		"oauth_base_redirect" => "",
	);

	//微信开放平台model
	protected $wechat_model = null;

	//当前app_id
	protected $app_id = null;

	//app_id msg hook map
	static protected $msg_hook_map = array(
		"gh_9725d6091422" => "Model_WechatOpen_ActivityDisneyMsgHook",//服务号
	);

	/**
	 * 消息机制路由 根据app_id、open_id选择发送模式
	 * 目前只支持一个公众号对应一个活动
	 * @param $app_id
	 * @return string
	 */
	static public function msgHookRouter($app_id)
	{
		return isset(self::$msg_hook_map[$app_id]) ? self::$msg_hook_map[$app_id] : "";
	}

	/*****----------------------------------------微信网页授权start--------------------------------------***/
	/**
	 * 创建开放平台模型
	 * @param null $app_id
	 */
	public function wechatModel($app_id = null)
	{
		$config = DM_Controller_Front::getInstance()->getConfig()->toArray();
		$this->wechat_model = DM_Wechat_WechatOpen::getInstance($config['wechat_open']['settings']);

		if (!is_null($app_id)) {
			$this->wechat_model->setAuthorizerAppID($app_id);
			$this->app_id = $app_id;
		}
	}

	/**
	 * 网页授权首页
	 * @return array
	 */
	public function oauthIndex()
	{
		//根据不同模式处理
		$user_info = array();
		switch ($this->config['oauth_model']) {
			case 1://实时更新
				$open_id = $this->getCookie();
				if (!empty($open_id))
					$user_info = $this->wechat_model->oauthUserInfo($open_id);

				if (empty($user_info))
					return $this->wechat_model->oauthAuthorizeUri($this->config['oauth_userinfo_redirect'], 2);
				break;
			case 2://数据库存储
				return $this->wechat_model->oauthAuthorizeUri($this->config['oauth_base_redirect']);
				break;
		}

		return $user_info;
	}

	/**
	 * snsapi_base 回调接口
	 * @param $code
	 * @return array|mixed|null
	 */
	public function oauthBaseRedirect($code)
	{
		//获取open_id
		$base_info = $this->wechat_model->oauthAccessToken($code);

		//数据库查询 todo redis 缓存 刷新用户数据机制
		$user_info = array();
		$userOpenidModel = new Model_Activity_WechatUserOpenid();
		$uid = $userOpenidModel->getInfoMix(array(
			"Openid =?" => $base_info['openid'],
			"AppID =?" => $this->app_id,
		), "UID");
		if (!is_null($uid)) {
			$userModel = new Model_Activity_WechatUser();
			$user_info = $userModel->getInfoMix(array("UID =?" => $uid));
		}

		if (empty($user_info))
			return $this->wechat_model->oauthAuthorizeUri($this->config['oauth_userinfo_redirect'], 2);

		return $user_info;
	}

	/**
	 * snsapi_userinfo 回调接口
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	public function oauthUserinfoRedirect($code)
	{
		$base_info = $this->wechat_model->oauthAccessToken($code);
		$user_info = $this->wechat_model->oauthUserInfo($base_info['openid'], $base_info);

		//根据不同模式处理
		switch ($this->config['oauth_model']) {
			case 1:
				$this->setCookie($user_info['openid']);
				break;
			case 2:
				$userModel = new Model_Activity_WechatUser();
				$userModel->addUserInfo($user_info, $this->app_id);
				break;
		}

		return $user_info;
	}

	/*****----------------------------------------微信网页授权end--------------------------------------***/

	/**
	 * 提供一套最简单常用的COOKIE登陆模式，例如通过手机号确定用户
	 * @param $cookie
	 * @param null $name
	 * @param int $timeout
	 * @return bool
	 */
	protected function setCookie($cookie, $name = null, $timeout = null)
	{
		$cookie = DM_Helper_Utility::authcode($cookie, 'ENCODE');
		$timeout = is_null($timeout) ? $this->config['oauth_cookie'] : $timeout;
		return setcookie((is_null($name) ? $this->config['oauth_cookie_time'] : $name),
			$cookie, $timeout == 0 ? 0 : time() + $timeout, '/', '', false, true);
	}

	/**
	 * 获取cookie
	 * @param null $name
	 * @return null|string
	 */
	protected function getCookie($name = null)
	{
		$name = is_null($name) ? $this->config['oauth_cookie'] : $name;
		$cookie = DM_Controller_Front::getInstance()->getHttpRequest()->getCookie($name, '');
		return DM_Helper_Utility::authcode($cookie);
	}

	protected static $instance = null;

	protected function __construct($config)
	{
		foreach ($config as $key => $val) {
			if (isset($this->config[$key]))
				$this->config[$key] = $val;
		}
	}

	public function getConfig($key = null)
	{
		return is_null($key) ? $this->config : (isset($this->config[$key]) ? $this->config[$key] : "");
	}

	public static function getInstance(array $config)
	{
		if (is_null(self::$instance)) {
			self::$instance = new self($config);
		}

		return self::$instance;
	}
}