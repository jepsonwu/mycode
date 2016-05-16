<?php

/**
 * 微信公众号功能类
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-18
 * Time: 下午1:04
 */
class DM_Wechat_Wechat
{
	protected static $instance = null;

	//wechat config
	protected $config = array();

	//redis key
	protected $ticket_redis_key = "wechat_ticket";

	protected $access_token_redis_key = "wechat_access_token";

	/**
	 * 获取access_token
	 * @return mixed|string
	 * @throws Exception
	 * @throws Zend_Exception
	 */
	public function getAccessToken()
	{
		$redis = DM_Module_Redis::getInstance();
		$access_token = $redis->get($this->access_token_redis_key);

		if (!$access_token) {
			//获取access_token
			$token_url = "{$this->config->TokenUrl}?grant_type=client_credential&appid={$this->config->AppID}&secret={$this->config->AppSecret}";
			$access_token = DM_Controller_Front::curl($token_url, array(), false);
			$access_token = json_decode($access_token, true);

			if (isset($access_token['errcode'])) {
				throw new Exception($access_token['errmsg']);
			} else {
				//保存access_token redis
				$redis->set($this->access_token_redis_key, $access_token['access_token']);
				$redis->expire($this->access_token_redis_key, $access_token['expires_in']);

				return $access_token['access_token'];
			}
		}

		return $access_token;
	}

	/**
	 * 获取ticket
	 * @return mixed|string
	 * @throws Exception
	 * @throws Zend_Exception
	 */
	public function getTicket()
	{
		$redis = DM_Module_Redis::getInstance();
		$ticket = $redis->get($this->ticket_redis_key);

		if (!$ticket) {
			$access_token = $this->getAccessToken();

			//获取ticket
			$ticket_url = "{$this->config->TicketUrl}?access_token={$access_token}&type=jsapi";
			$ticket = DM_Controller_Front::curl($ticket_url, array(), false);
			$ticket = json_decode($ticket, true);

			if ($ticket['errcode'] === 0) {
				//保存ticket redis
				$redis->set($this->ticket_redis_key, $ticket['ticket']);
				$redis->expire($this->ticket_redis_key, $ticket['expires_in']);

				return $ticket['ticket'];
			} else {
				throw new Exception($ticket['errmsg']);
			}
		}

		return $ticket;
	}

	protected function __construct()
	{
		$this->config = DM_Controller_Front::getInstance()->getConfig()->wechat->settings;
	}

	public function getConfig($key = null)
	{
		return is_null($key) ? $this->config : $this->config->$key;
	}

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 微信网页认证 返回用户详情信息 todo 已经认证过了会是什么情况
	 * @param $code
	 * @return mixed|string
	 * @throws Exception
	 * @throws Zend_Exception
	 */
	public function webAuth($code)
	{
		$access_token_url = "{$this->config->Host}{$this->config->service->auth_access_token}?" .
			"appid={$this->config->service->AppID}&secret={$this->config->service->AppSecret}&code={$code}&grant_type=authorization_code";
		$access_token = DM_Controller_Front::curl($access_token_url, array(), false);
		$access_token = json_decode($access_token, true);

		if (isset($access_token['errcode'])) {
			throw new Exception($access_token['errmsg'], $access_token['errcode']);
		} else {
			//拉取用户信息
			$info_url = "{$this->config->Host}{$this->config->service->auth_get_userinfo}?" .
				"access_token={$access_token['access_token']}&openid={$access_token['openid']}&lang=zh_CN";
			$user_info = DM_Controller_Front::curl($info_url, array(), false);
			$user_info = json_decode($user_info, true);
			if (isset($user_info['errcode'])) {
				throw new Exception($user_info['errmsg'], $user_info['errcode']);
			}

			return $user_info;
		}
	}
}