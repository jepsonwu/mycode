<?php

include_once "Encoding/wxBizMsgCrypt.php";

/**
 * 微信公众号第三方开放平台功能类
 * 当前只能操作一个公众号
 *
 * 使用方法：
 * $wechat=DM_Wechat_WechatOpen::getInstance(config); 配置开放平台
 * $wechat->setAuthorizerAppID($app_id); 设置当前操作公众号APPID
 * try{$wechat->method}cache($e){echo $e->getMessage();}
 *
 * 注册msghook路由函数 微信异步消息 根据open_id选择消息发送机制
 * $wechat->registerMsgHookRouter("Model_WechatOpen_Common::msgHookRouter");
 *
 * 需要配置storage、log，storage默认为redis，或者修改为自定义存储
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-4-26
 * Time: 下午3:46
 */
class DM_Wechat_WechatOpen
{
	/**----------------------redis存储key-------------------------------------**/

	//开放平台verify_ticket
	protected $component_verify_ticket = "component_verify_ticket";

	//开放平台access_token
	protected $component_access_token = "component_access_token";

	//公众号授权信息key 和app_id拼接
	protected $authorizer_info = "authorizer_info_";

	//公众号信息 原始ID对应的信息
	protected $authorizer_openid_info = "authorizer_openid_info_";

	//是否授权
	protected $authorizer_isvalid = "authorizer_isvalid_";

	//公众号调用token
	protected $authorizer_access_token = "authorizer_access_token_";

	//js-sdk ticket
	protected $js_ticket = "";

	//网页授权access token
	protected $oauth_access_token = "oauth_access_token_";

	//网页授权refresh token
	protected $oauth_refresh_token = "oauth_refresh_token_";

	/**----------------------redis存储key-------------------------------------**/
	//msg hook router
	protected $msg_hook_router = null;

	//msg hook
	protected $msg_hook = "";

	//存储
	protected $storage = null;

	//log
	public $log = null;

	//加密模块
	protected $crypt = null;

	//当前操作的公众号
	protected $authorizer_app_id = null;

	//公众号授予权限map  这个权限范围较大 公众号本身的权限更细
	protected $authorizer_func_map = array(
		1 => "消息管理权限",
		2 => "用户管理权限",
		3 => "帐号服务权限",
		4 => "网页服务权限",
		5 => "微信小店权限",
		6 => "微信多客服权限",
		7 => "群发与通知权限",
		8 => "微信卡券权限",
		9 => "微信扫一扫权限",
		10 => "微信连WIFI权限",
		11 => "素材管理权限",
		12 => "微信摇周边权限",
		13 => "微信门店权限",
		14 => "微信支付权限",
		15 => "自定义菜单权限",
	);

	//全网发布测试公众号
	protected $authorizer_test = array(
		"app_id" => "wx570bc396a51b8ff8",
		"username" => "gh_3c884a361561",
	);

	//配置文件
	protected $config = array(
		//crypt
		"encoding_aes_key_prev" => "",//保存上一次的key，因为key可以修改，存在时间差
		"encoding_aes_key_curr" => "",//公众号消息加解密key

		//verify
		"token" => "",
		"app_id" => "",
		"app_secret" => "",

		//url 授权 公众号信息
		"component_token" => "https://api.weixin.qq.com/cgi-bin/component/api_component_token",//获取第三方平台component_access_token
		"create_preauthcode" => "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=%s",//获取预授权码pre_auth_code
		"component_login_page" => "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s",//公众号登陆授权页
		"query_auth" => "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=%s",//使用授权码换取公众号的接口调用凭据和授权信息
		"authorizer_token" => "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=%s",//刷新授权公众号的接口调用凭据
		"authorizer_info" => "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=%s",//获取授权方的公众号帐号基本信息
		"get_authorizer_option" => "https://api.weixin.qq.com/cgi-bin/component/ api_get_authorizer_option?component_access_token=%s",//获取授权方的选项设置信息
		"set_authorizer_option" => "https://api.weixin.qq.com/cgi-bin/component/ api_set_authorizer_option?component_access_token=%s",//设置授权方的选项信息

		//url 客服
		"custom_add" => "https://api.weixin.qq.com/customservice/kfaccount/add?access_token=%s",//添加客服帐号
		"custom_update" => "https://api.weixin.qq.com/customservice/kfaccount/update?access_token=%s",//修改客服帐号
		"custom_del" => "https://api.weixin.qq.com/customservice/kfaccount/del?access_token=%s",//删除客服帐号
		"custom_headimg" => "http://api.weixin.qq.com/customservice/kfaccount/uploadheadimg?access_token=%s&kf_account=KFACCOUNT",//设置客服帐号的头像
		"custom_list" => "https://api.weixin.qq.com/cgi-bin/customservice/getkflist?access_token=%s",//获取所有客服账号
		"custom_send_message" => "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s",//客服接口-发消息

		//网页授权
		"oauth_authorize" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s&component_appid=%s#wechat_redirect",//请求CODE
		"oauth_access_token" => "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=%s&code=%s&grant_type=authorization_code&component_appid=%s&component_access_token=%s",//通过code换取access_token
		"oauth_userinfo" => "https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN",//获取用户信息
		"oauth_refresh_token" => "https://api.weixin.qq.com/sns/oauth2/component/refresh_token?appid=%s&grant_type=refresh_token&component_appid=%s&component_access_token=%s&refresh_token=%s",//刷新access_token

		//js-sdk
		"js_ticket" => "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",//获取ticket

		//素材管理
		"media_upload" => "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s",//新增临时素材
		"media_get" => "https://api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s",//获取临时素材

		//模板消息
		"template_send_message" => "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s",//发送模板消息
		"template_list" => "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=%s",//获取模板列表

		//user
		"user_info" => "https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN",//获取用户基本信息

		//生成带场景值二维码
		"qrcode_create" => "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=%s",//生成二维码
		"qrcode_show" => "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=%s",//显示二维码
	);

	/**-----------------------------------------------------生成二维码start-----------------------------------------**/

	/**
	 * 生成带场景值二维码
	 * @param $type 1-临时 2-永久数字 3-永久字符串
	 * @param $info
	 * @param null $expire
	 * @return mixed
	 * @throws Exception
	 */
	public function qrcodeCreate($type, $info, $expire = null)
	{
		$data = array();
		switch ($type) {
			case '1':
				if (is_null($expire)) {
					$expire = 604800;
				} else {
					$expire = intval($expire);
					($expire > 604800 || $expire <= 0) && $expire = 604800;
				}
				$data['expire_seconds'] = $expire;
				$data['action_name'] = 'QR_SCENE';

				$info = intval($info);
				if (strlen($info) > 32 || $info <= 0)
					throw new Exception("QR_SCENE scene_id is failed");
				$data['action_info'] = array("scene" => array("scene_id" => $info));
				break;
			case '2':
				$data['action_name'] = 'QR_LIMIT_SCENE';

				$info = intval($info);
				if ($info <= 0 || $info > 100000)
					throw new Exception("QR_LIMIT_SCENE scene_id is failed");
				$data['action_info'] = array("scene" => array("scene_id" => $info));
				break;
			case '3':
				$data['action_name'] = 'QR_LIMIT_STR_SCENE';

				if (strlen($info) > 64 || strlen($info) <= 0)
					throw new Exception("QR_LIMIT_STR_SCENE scene_id is failed");
				$data['action_info'] = array("scene" => array("scene_str" => $info));
				break;
			default:
				throw new Exception("Not support type");
				break;
		}

		return $this->curl(sprintf($this->config['qrcode_create'], $this->authorizerAccessToken()),
			json_encode($data));
	}

	/**
	 * 显示二维码
	 * @param $ticket
	 * @return mixed
	 * @throws Exception
	 */
	public function qrcodeShow($ticket)
	{
		return $this->curl(sprintf($this->config['qrcode_show'], urlencode($ticket)), array(), false);
	}

	/**-----------------------------------------------------生成二维码end-----------------------------------------**/

	/**-----------------------------------------------------设置当前操作公众号start-------------------------------**/
	/**
	 * 设置当前操作公众号ID
	 * @param $app_id
	 * @throws Exception
	 */
	public function setAuthorizerAppID($app_id)
	{
		if (!$this->isvalidAuthorizer($app_id))
			throw new Exception("Authorizer is not valid");

		$this->authorizer_app_id = $app_id;
	}

	/**
	 * 获取当前操作公众号ID
	 * @return null
	 * @throws Exception
	 */
	public function getAuthorizerAppID()
	{
		if (is_null($this->authorizer_app_id))
			throw new Exception("Authorizer app_id is null");
		return $this->authorizer_app_id;
	}

	/**
	 * 判断公众号是否授权
	 * @param $app_id
	 * @return bool
	 */
	protected function isvalidAuthorizer($app_id)
	{
		$isvalid = $this->storage->get($this->authorizer_isvalid . $app_id);
		return empty($isvalid) ? false : true;
	}

	/**
	 * 判断接口是否授权给开放平台
	 *
	 * @param $api_id
	 * @return bool
	 * @throws Exception
	 */
	public function isvalidAuthorizerFunc($api_id)
	{
		$authorizer_info = $this->storage->get($this->authorizer_info . $this->getAuthorizerAppID());
		$func_allow = $authorizer_info['func_allow'];
		if (!in_array($api_id, $func_allow))
			throw new Exception("authorizer func not allow,id:{$api_id}");

		return true;
	}

	/**
	 * 判断公众号是否有权限
	 * http://mp.weixin.qq.com/wiki/7/2d301d4b757dedc333b9a9854b457b47.html
	 * @param $api_id
	 * @return bool
	 * @throws Exception
	 */
	public function isvalidAuthorizerApi($api_id)
	{
		$authorizer_info = $this->storage->get($this->authorizer_info . $this->getAuthorizerAppID());
		$func_allow = $authorizer_info['func_allow'];
		if (!in_array($api_id, $func_allow))
			throw new Exception("authorizer api not allow,id:{$api_id}");

		return true;
	}

	/**-----------------------------------------------------设置当前操作公众号end------------------------------------**/

	/**-----------------------------------------------------模板消息接口start------------------------------------**/
	/**
	 * todo 接口管理模板
	 */

	/**
	 * 获取模板列表
	 * @return mixed
	 * @throws Exception
	 */
	public function templateList()
	{
		return $this->curl(sprintf($this->config['template_list'], $this->authorizerAccessToken()), array());
	}

	/**
	 * todo 发送状态异步回调 memberEvent
	 */

	/**
	 * 发送模板消息
	 * data :"first": {"value":"恭喜你购买成功！","color":"#173177"},
	 * @param $open_id
	 * @param $template_id
	 * @param $url
	 * @param $data
	 * @return bool
	 * @throws Exception
	 */
	public function templateSendMessage($open_id, $template_id, $data, $url = "")
	{
		$post = array(
			"touser" => $open_id,
			"template_id" => $template_id,
			"url" => $url,
			"data" => $data
		);
		$this->curl(sprintf($this->config['template_send_message'], $this->authorizerAccessToken()), json_encode($post));
		return true;
	}

	/**-----------------------------------------------------模板消息接口end------------------------------------------**/

	/**-----------------------------------------------------客服消息接口start-------------------------------------**/
	/**
	 * todo 客服管理
	 */
	/**
	 * 客服发送文本、图片、语音、视频、音乐、图文、卡券消息
	 * 视频：{"media_id":"MEDIA_ID","thumb_media_id":"MEDIA_ID","title":"TITLE","description":"DESCRIPTION"}
	 * 音乐：{"title":"MUSIC_TITLE","description":"MUSIC_DESCRIPTION","musicurl":"MUSIC_URL",
	 * "hqmusicurl":"HQ_MUSIC_URL","thumb_media_id":"THUMB_MEDIA_ID"}
	 * 图文： todo
	 * 卡券： todo
	 * @param $open_id
	 * @param $content
	 * @param string $type
	 * @param null $custom
	 * @return mixed
	 * @throws Exception
	 */
	public function customSendMix($open_id, $content, $type = "text", $custom = null)
	{
		$data = array(
			"touser" => $open_id,
			"msgtype" => $type,
		);

		switch ($type) {
			case "text":
				$data['text'] = array("content" => urlencode($content));
				break;
			case "image":
				$data['image'] = array("media_id" => $content);
				break;
			case "voice":
				$data['voice'] = array("media_id" => $content);
				break;
			case "video":
				$data['video'] = $content;
				break;
			case "music":
				$data['music'] = $content;
				break;
			case "wxcard":
				$data['wxcard'] = $content;
				break;
			default:
				return false;
				break;
		}

		//指定客服发送
		if (!is_null($custom)) {
			$data['customservice'] = array(
				"kf_account" => $custom
			);
		}

		$data = json_encode($data);
		$type == 'text' && $data = urldecode($data);
		$this->curl(sprintf($this->config['custom_send_message'], $this->authorizerAccessToken()), $data);

		return true;
	}
	/**-----------------------------------------------------客服消息接口end-------------------------------------**/

	/**---------------------------------------------------------素材管理start-------------------------------------**/
	/**
	 * todo 永久素材管理
	 */

	/**
	 * 获取临时素材
	 * @param $media_id
	 * @return mixed
	 * @throws Exception
	 */
	public function mediaGet($media_id)
	{
		return $this->curl(sprintf($this->config['media_get'], $this->authorizerAccessToken(), $media_id), array());
	}

	/**
	 * 新增临时素材 3天失效
	 * {"type":"TYPE","media_id":"MEDIA_ID","created_at":123456789}
	 * @param $data
	 * @param string $type
	 * @return mixed
	 * @throws Exception
	 */
	public function mediaUpload($data, $type = 'image')
	{
		$strlen = true;
		switch ($type) {
			case 'image':
				strlen($data['content']) > 1 << 20 && $strlen = false;
				break;
			case 'voice':
				strlen($data) > 1 << 21 && $strlen = false;
				break;
			case 'video':
				strlen($data) > (1 << 20) * 10 && $strlen = false;
				break;
			case 'thumb':
				strlen($data) > 1 << 6 && $strlen = false;
				break;
			default:
				throw new Exception("Not support type");
				break;
		}

		//todo 二进制数据格式判断 unpack

		if (!$strlen)
			throw new Exception("media is to large");

		$data = array(
			"media\"; filename=\"{$data['filename']}\r\n{$data['type']}\r\n" => $data['content']
		);
		$media_info = $this->curl(sprintf($this->config['media_upload'], $this->authorizerAccessToken(), $type), $data);

		return $media_info;
	}

	/**---------------------------------------------------------素材管理end-------------------------------------**/

	/**--------------------------------------------------------网页授权start------------------------------------------**/
	/**
	 * 使用方法：
	 * oauthAuthorizeUri 返回静默授权连接 获取用户open_id
	 * redirect_uri 回调open_id 根据open_id判断是否要处理微信用户信息
	 * 没有授权 获取手动授权连接 用户授权
	 * 回调 更新用户信息
	 *
	 * 返回获取code连接
	 * @param $redirect_uri
	 * @param int $type 1-base 2-user_info
	 * @return string
	 */
	public function oauthAuthorizeUri($redirect_uri, $type = 1)
	{
		return sprintf($this->config['oauth_authorize'], $this->getAuthorizerAppID(), $redirect_uri,
			$type == 1 ? "snsapi_base" : "snsapi_userinfo", "1", $this->config['app_id']);
	}

	/**
	 * 获取access_token 和open_id base模式下 access_token没有用处
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	public function oauthAccessToken($code)
	{
		return $this->curl(sprintf($this->config['oauth_access_token'], $this->getAuthorizerAppID(), $code,
			$this->config['app_id'], $this->componentAccessToken()), array());
	}

	/**
	 * 获取用户信息
	 * @param $open_id
	 * @param null $base_info 提供缓存处理
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function oauthUserInfo($open_id, $base_info = null)
	{
		$app_id = $this->getAuthorizerAppID();
		$token_key = $this->oauth_access_token . $app_id;
		$refresh_key = $this->oauth_refresh_token . $app_id;

		if (is_null($base_info)) {
			$access_token = $this->storage->get($token_key);
			if (empty($access_token)) {
				$refresh_token = $this->storage->get($refresh_key);
				if (empty($refresh_token)) {
					return false;
				} else {
					//刷新token
					$base_info = $this->curl(sprintf($this->config['oauth_refresh_token'], $app_id, $this->config['app_id'],
						$this->componentAccessToken(), $refresh_token), array());
					$access_token = $base_info['access_token'];
				}
			}
		} else {
			$access_token = $base_info['access_token'];
		}

		$user_info = $this->curl(sprintf($this->config['oauth_userinfo'], $access_token, $open_id), array());
		!isset($user_info['unionid']) && $user_info['unionid'] = "";

		//获取用户基本信息 公众号相关信息
		$user_ext_info = $this->userInfo($open_id);

		$user_info['subscribe'] = $user_ext_info['subscribe'];
		if ($user_info['subscribe'] != 0) {
			$user_info['subscribe_time'] = date("Y-m-d H:i:s", $user_ext_info['subscribe_time']);
			$user_info['remark'] = $user_ext_info['remark'];
			$user_info['tagid_list'] = implode(",", $user_ext_info['tagid_list']);
			$user_info['groupid'] = $user_ext_info['groupid'];
		} else {
			$user_info['subscribe_time'] = '0000-00-00 00:00:00';
			$user_info['remark'] = "";
			$user_info['tagid_list'] = "";
			$user_info['groupid'] = "";
		}

		if (!is_null($base_info)) {
			$this->storage->setex($token_key, $base_info['expires_in'] - 60, $base_info['access_token']);
			$this->storage->setex($refresh_key, 2592000, $base_info['refresh_token']);
		}

		return $user_info;
	}

	/**--------------------------------------------------------网页授权end------------------------------------------**/

	/**--------------------------------------------------------用户信息start------------------------------------------**/

	/**
	 *
	 * 获取用户基础信息
	 * @param $open_id
	 * @return mixed
	 * @throws Exception
	 */
	public function userInfo($open_id)
	{
		return $this->curl(sprintf($this->config['user_info'], $this->authorizerAccessToken(), $open_id), array(), false);
	}

	/**--------------------------------------------------------用户信息end------------------------------------------**/

	/**---------------------------------------------------------js-sdk权限认证start------------------------------------**/
	/**
	 * 获取注册权限
	 * @param $url
	 * @return array
	 */
	public function jsSdkSignature($url)
	{
		//待签名参数
		$param = array(
			"noncestr" => DM_Helper_String::randString(18),
			"jsapi_ticket" => $this->jsSdkTicket(),
			"timestamp" => time(),
			"url" => $url //不包含#及其后面部分
		);

		//sha1
		$signature = sha1($this->createParam($this->sortParam($param)));

		return array(
			"appId" => $this->getAuthorizerAppID(),
			"timestamp" => $param['timestamp'],
			"nonceStr" => $param['noncestr'],
			"signature" => $signature
		);
	}

	/**
	 * 获取ticket
	 * @return mixed|string
	 * @throws Exception
	 */
	protected function jsSdkTicket()
	{
		$redis = DM_Module_Redis::getInstance();
		$ticket = $redis->get($this->js_ticket);

		if (!$ticket) {
			$access_token = $this->authorizerAccessToken();

			//获取ticket
			$ticket = $this->curl(sprintf($this->config['js_ticket'], $access_token), array());
			//保存ticket redis
			$this->storage->setex($this->js_ticket, $ticket['expires_in'] - 30, $ticket['ticket']);
		}

		return $ticket;
	}

	/**
	 * 生成签名字符串
	 * @param $data
	 * @return string
	 */
	protected function createParam($data)
	{
		$param = "";

		foreach ($data as $key => $value) {
			$param .= $key . "=" . $value . "&";
		}
		$param = trim($param, "&");

		//转义字符

		return $param;
	}

	/**
	 * 按字典排序参数
	 * @param $data
	 * @return mixed
	 */
	protected function sortParam($data)
	{
		ksort($data);

		return $data;
	}
	/**---------------------------------------------------------js-sdk权限认证end------------------------------------**/

	/**---------------------------------------------------------授权公众号信息处理start-------------------------------------**/
	/**
	 * 设置授权方的选项信息
	 *
	 * @param $app_id
	 * @param $name
	 * @param $value
	 * @return bool
	 * @throws Exception
	 */
	public function setAuthorizerOption($app_id, $name, $value)
	{
		$request = array(
			"component_appid" => $this->config['app_id'],
			"authorizer_appid" => is_null($app_id) ? $this->getAuthorizerAppID() : $app_id,
			"option_name" => $name,
			"option_value" => $value
		);
		$this->curl(sprintf($this->config['set_authorizer_option'],
			$this->componentAccessToken()), json_encode($request));

		return true;
	}

	/**
	 * 获取授权方的选项设置信息 location_report(地理位置上报选项)
	 * voice_recognize（语音识别开关选项） customer_service（多客服开关选项）
	 * @param $app_id
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function getAuthorizerOption($app_id, $name)
	{
		$request = array(
			"component_appid" => $this->config['app_id'],
			"authorizer_appid" => is_null($app_id) ? $this->getAuthorizerAppID() : $app_id,
			"option_name" => $name
		);
		$result = $this->curl(sprintf($this->config['get_authorizer_option'],
			$this->componentAccessToken()), json_encode($request));

		//func_info
		foreach ($result['func_info'] as $func)
			$result['func_allow'][] = $func['funcscope_category']['id'];

		return $result;
	}

	/**
	 * 获取授权方的公众号帐号基本信息
	 *
	 * @param null $app_id 在授权回调和公众号回调时是不会设置app_id的
	 * @return mixed
	 * @throws Exception
	 */
	public function getAuthorizerInfo($app_id = null)
	{
		$request = array(
			"component_appid" => $this->config['app_id'],
			"authorizer_appid" => is_null($app_id) ? $this->getAuthorizerAppID() : $app_id,
		);

		$result = $this->curl(sprintf($this->config['authorizer_info'],
			$this->componentAccessToken()), json_encode($request));

		return $result;
	}

	/**
	 * 使用授权码换取公众号的接口调用凭据和授权信息
	 *
	 * @param $auth_code
	 * @return mixed
	 * @throws Exception
	 */
	public function queryAuth($auth_code)
	{
		$request = array(
			"component_appid" => $this->config['app_id'],
			"authorization_code" => $auth_code
		);
		$result = $this->curl(sprintf($this->config['query_auth'], $this->componentAccessToken()), json_encode($request));

		//保存公众号接口调用凭据
		$result = $result['authorization_info'];
		$app_id = $result['authorizer_appid'];
		$res = $this->storage->setex($this->authorizer_access_token . $app_id,
			intval($result['expires_in'] - 600), $result['authorizer_access_token']);
		if (!$res)
			throw new Exception("authorizer access token save failed");

		$res = $this->storage->set($this->authorizer_isvalid . $app_id, "1");
		if (!$res)
			throw new Exception("authorizer is valid save failed");

		//公众号基本信息 授权信息
		$authorizer_info = $this->getAuthorizerInfo($app_id);
		$authorizer_info['authorizer_refresh_token'] = $result['authorizer_refresh_token'];
		$res = $this->storage->set($this->authorizer_info . $app_id, json_encode($authorizer_info));
		if (!$res)
			throw new Exception("authorizer info save failed");

		$res = $this->storage->set($this->authorizer_openid_info . $authorizer_info['authorizer_info']['user_name'], $app_id);
		if (!$res)
			throw new Exception("authorizer openid info save failed");

		return $result;
	}

	/**---------------------------------------------------------授权公众号信息处理end-------------------------------------**/

	/**---------------------------------------------------------开放平台授权start----------------------------------------**/
	/**
	 * 第三方平台方获取预授权码
	 *
	 * @param $redirect_uri
	 * @return string 跳转url 公众号登陆授权页
	 * @throws Exception
	 */
	public function getPreAuthCode($redirect_uri)
	{
		//获取预授权码pre_auth_code
		$request = array(
			"component_appid" => $this->config['app_id']
		);

		$result = $this->curl(sprintf($this->config['create_preauthcode'],
			$this->componentAccessToken()), json_encode($request));

		//返回跳转url，授权登陆页
		$redirect_url = sprintf($this->config['component_login_page'], $this->config['app_id'],
			$result['pre_auth_code'], $redirect_uri);
		return $redirect_url;
	}

	/**
	 * 开发平台授权事件回调
	 * @param $post_data 加密消息体
	 * @param $get_data get 参数用于校验
	 * @return string
	 * @throws Exception
	 */
	public function authEvent($post_data, $get_data)
	{
		$return = "success";

		if (empty($post_data) || empty($get_data))
			throw new Exception("post data or get data is mepty");

		//解析xml数据体
		$xml_tree = $this->msgDecrypt($post_data, $get_data);
		$xml_info = $this->getXmlNode($xml_tree);

		//不同类型数据
		switch ($xml_info['InfoType']) {
			//微信每10分钟推送
			case "component_verify_ticket":
				$this->storage->setex($this->component_verify_ticket, 1000, $xml_info['ComponentVerifyTicket']);
				$this->log->log("component_verify_ticket succ \n\n", Zend_Log::INFO);
				break;
			//取消授权通知
			case "unauthorized":
				$res = $this->storage->set($this->authorizer_isvalid . $xml_info['AuthorizerAppid'], "0");
				$this->log->log("unauthorized result:{$res},app_id:" . $xml_info['AuthorizerAppid'] . "\n\n", Zend_Log::INFO);
				break;
			//授权成功通知
			case "authorized":
				if (!$this->isvalidAuthorizer($xml_info['AuthorizerAppid']))
					$this->queryAuth($xml_info['AuthorizationCode']);
				$this->log->log("authorized succ,app_id:" . $xml_info['AuthorizerAppid'] . "\n\n", Zend_Log::INFO);
				break;
			//授权更新通知
			case "updateauthorized":
				$this->queryAuth($xml_info['AuthorizationCode']);
				$this->log->log("updateauthorized succ,app_id:" . $xml_info['AuthorizerAppid'] . "\n\n", Zend_Log::INFO);
				break;
		}

		return $return;
	}

	/**---------------------------------------------------------开放平台授权end----------------------------------------**/

	/**----------------------------------------------------------公众号事件推送start-----------------------------------**/
	/**
	 * 公众号消息事件推送
	 *
	 * @param $post_data
	 * @param $get_data
	 * @return string
	 * @throws Exception
	 */
	public function memberEvent($post_data, $get_data)
	{
		//解析xml数据体
		$xml_tree = $this->msgDecrypt($post_data, $get_data);
		//array("ToUserName", "FromUserName", "CreateTime", "MsgType")
		//公众号原始ID 发送方帐号（一个OpenID） 消息创建时间 （整型） 	消息类型   事件类型，subscribe(订阅)、unsubscribe(取消订阅)
		$xml_info = $this->getXmlNode($xml_tree);

		$this->getMsgHook($xml_info['ToUserName']);

		$this->log->log("member event,xml_info:" . json_encode($xml_info) . "\n\n", Zend_Log::INFO);

		//普通消息和事件推送消息
		switch ($xml_info['MsgType']) {
			//事件推送消息
			case "event":
				if ($xml_info['ToUserName'] == $this->authorizer_test['username']) {//全网发布测试
					echo $this->backTxtMsg($xml_info['FromUserName'], $xml_info['ToUserName'], "{$xml_info['Event']}from_callback");
				} else {
					$this->eventMsgHook($xml_info['Event'], $xml_info);
				}
				break;
			//文本消息 普通消息
			case "text":
				if ($xml_info['ToUserName'] == $this->authorizer_test['username']) {//全网发布测试
					if ($xml_info['Content'] == "TESTCOMPONENT_MSG_TYPE_TEXT") {
						echo $this->backTxtMsg($xml_info['FromUserName'], $xml_info['ToUserName'], "TESTCOMPONENT_MSG_TYPE_TEXT_callback");
					} else {
						echo "";//立即返回
						fastcgi_finish_request();

						//客服消息接口发送消息
						$auth_code = str_replace("QUERY_AUTH_CODE:", "", $xml_info['Content']);
						$auth_info = $this->queryAuth($auth_code);

						//发消息
						$this->curl(sprintf($this->config['custom_send_message'],
							$auth_info['authorizer_access_token']), json_encode(array(
							"touser" => $xml_info['FromUserName'],
							"msgtype" => "text",
							"text" => array(
								"content" => "{$auth_code}_from_api"
							)
						)));
					}
				} else {
					$this->textMsgHook($xml_info);
				}
				break;
		}
	}


	/**
	 * 事件推送消息钩子
	 * @param $event
	 * @param $xml_info
	 */
	protected function eventMsgHook($event, $xml_info)
	{
		if (empty($this->msg_hook)) {
			echo "";
		} else {
			$this->msg_hook->eventMsg($event, $xml_info);
		}
	}

	/**
	 * 文本消息钩子
	 * @param $xml_info
	 */
	protected function textMsgHook($xml_info)
	{
		if (empty($this->msg_hook)) {
			echo "";
		} else {
			$this->msg_hook->textMsg($xml_info);
		}
	}

	/**
	 * 解析消息hook路由
	 * @param string $open_id 公众号原始ID
	 * @throws Zend_Log_Exception
	 */
	protected function getMsgHook($open_id)
	{
		$msg_hook = call_user_func_array($this->msg_hook_router, array($open_id));
		if (!empty($msg_hook)) {
			$obj = new $msg_hook();
			if ($obj instanceof DM_Wechat_MsgHookInterface) {
				$this->msg_hook = $obj;
				$this->setAuthorizerAppID($this->storage->get($this->authorizer_openid_info . $open_id));
				$this->msg_hook->setWechatObj($this);
			} else {
				$this->log->log("Msg hook is not instanceof DM_Wechat_MsgHookInterface,msg hook:{$msg_hook}\n\n", Zend_Log::ERR);
			}
		} else {
			$this->log->log("Msg hook is empty,open_id:{$open_id}\n\n", Zend_Log::ERR);
		}
	}

	/**
	 * 注册消息hook函数
	 * 目前只支持静态函数 call_user_func_array
	 * @param $router
	 */
	public function registerMsgHookRouter($router)
	{
		$this->msg_hook_router = $router;
	}

	/**
	 * 返回当前消息hook函数
	 * @return null
	 */
	public function getMsgHookRouter()
	{
		return $this->msg_hook_router;
	}

	/**----------------------------------------------------------公众号事件推送end-----------------------------------**/
	/**
	 * 获取、刷新授权公众号的接口调用凭据
	 * @return mixed
	 * @throws Exception
	 */
	protected function authorizerAccessToken()
	{
		$app_id = $this->getAuthorizerAppID();
		$token_key = $this->authorizer_access_token . $app_id;
		$info_key = $this->authorizer_info . $app_id;
		$access_token = $this->storage->get($token_key);

		//刷新token
		if (empty($access_token)) {
			//获取公众号信息
			$authorizer_info = $this->storage->get($info_key);
			$authorizer_info = json_decode($authorizer_info, true);

			$request = array(
				"component_appid" => $this->config['app_id'],
				"authorizer_appid" => $app_id,
				"authorizer_refresh_token" => $authorizer_info['authorizer_refresh_token']
			);
			$result = $this->curl(sprintf($this->config['authorizer_token'], $this->componentAccessToken()),
				json_encode($request));

			//刷新token
			$this->storage->setex($token_key, intval($result['expires_in'] - 600), $result['authorizer_access_token']);
			$access_token = $result['authorizer_access_token'];

			//处理一下信息
			$authorizer_info = $this->getAuthorizerInfo();
			$authorizer_info['authorizer_refresh_token'] = $result['authorizer_refresh_token'];
			$this->storage->set($info_key, json_encode($authorizer_info));
		}

		return $access_token;
	}

	/**
	 *
	 * 获取第三方平台component_access_token
	 *
	 * @return mixed
	 * @throws Exception
	 * @throws Zend_Exception
	 */
	protected function componentAccessToken()
	{
		$access_token = $this->storage->get($this->component_access_token);
		if (empty($access_token)) {
			$request = array(
				"component_appid" => $this->config['app_id'],
				"component_appsecret" => $this->config['app_secret'],
				"component_verify_ticket" => $this->storage->get($this->component_verify_ticket),
			);
			$result = $this->curl($this->config['component_token'], json_encode($request));

			//缓存component_access_token
			$this->storage->setex($this->component_access_token, intval($result['expires_in'] - 600), $result['component_access_token']);

			$access_token = $result['component_access_token'];
		}

		return $access_token;
	}

	/**
	 * 回复文本消息
	 * @param $to_user
	 * @param $from_user
	 * @param $content
	 * @return string
	 * @throws Exception
	 */
	public function backTxtMsg($to_user, $from_user, $content)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[text]]></MsgType>
				<Content><![CDATA[%s]]></Content>
				</xml>";

		$text = sprintf($text, $to_user, $from_user, time(), $content);
		return $this->msgEncrypt($text);
	}

	/**
	 * 回复图片消息
	 * @param $to_user
	 * @param $from_user
	 * @param $image_id
	 * @return string
	 * @throws Exception
	 */
	public function backImageMsg($to_user, $from_user, $image_id)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[image]]></MsgType>
				<Image>
				<MediaId><![CDATA[%s]]></MediaId>
				</Image>
				</xml>";
		$text = sprintf($text, $to_user, $from_user, time(), $image_id);
		return $this->msgEncrypt($text);
	}

	/**
	 *
	 * 回复语音消息
	 * @param $to_user
	 * @param $from_user
	 * @param $media_id
	 * @return string
	 * @throws Exception
	 */
	public function backVoiceMsg($to_user, $from_user, $media_id)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[voice]]></MsgType>
				<Voice>
				<MediaId><![CDATA[%s]]></MediaId>
				</Voice>
				</xml>";
		$text = sprintf($text, $to_user, $from_user, time(), $media_id);
		return $this->msgEncrypt($text);
	}

	/**
	 * 回复视频消息
	 *
	 * @param $to_user
	 * @param $from_user
	 * @param $media_id
	 * @param $title
	 * @param $desc
	 * @return string
	 * @throws Exception
	 */
	public function backVideoMsg($to_user, $from_user, $media_id, $title, $desc)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[video]]></MsgType>
				<Video>
				<MediaId><![CDATA[%s]]></MediaId>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				</Video>
				</xml>";
		$text = sprintf($text, $to_user, $from_user, time(), $media_id, $title, $desc);
		return $this->msgEncrypt($text);
	}

	/**
	 *
	 * 回复音乐消息
	 * @param $to_user
	 * @param $from_user
	 * @param $media_id
	 * @param $title
	 * @param $desc
	 * @param $url
	 * @param $hq_url
	 * @return string
	 * @throws Exception
	 */
	public function backMusicMsg($to_user, $from_user, $media_id, $title, $desc, $url, $hq_url)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[music]]></MsgType>
				<Music>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				<MusicUrl><![CDATA[%s]]></MusicUrl>
				<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
				<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
				</Music>
				</xml>";
		$text = sprintf($text, $to_user, $from_user, time(), $title, $desc, $url, $hq_url, $media_id);
		return $this->msgEncrypt($text);
	}

	/**
	 * 发送图文消息
	 *
	 * @param $to_user
	 * @param $from_user
	 * @param array $news
	 * @return string
	 * @throws Exception
	 */
	public function backNewsMsg($to_user, $from_user, array $news)
	{
		$text = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[news]]></MsgType>";
		$text = sprintf($text, $to_user, $from_user, time());

		$text .= "<ArticleCount>" . count($news) . "</ArticleCount>
				<Articles>";

		//消息内容 最多十条
		$item = "<item>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				<PicUrl><![CDATA[%s]]></PicUrl>
				<Url><![CDATA[%s]]></Url>
				</item>";
		foreach ($news as $new)
			$text .= sprintf($item, $new['title'], $new['desc'], $new['pic_url'], $new['url']);

		$text .= "</Articles>
				</xml>";
		return $this->msgEncrypt($text);
	}

	/**
	 * 创建加密模块
	 */
	protected function createCrypt()
	{
		is_null($this->crypt) &&
		$this->crypt = new WXBizMsgCrypt($this->config['token'], $this->config['encoding_aes_key_curr'], $this->config['app_id']);
	}

	/**
	 * 加密数据
	 * @param $text
	 * @return string
	 * @throws Exception
	 */
	protected function msgEncrypt($text)
	{
		$result = "";

		$errcode = $this->crypt->encryptMsg($text, time(), 45646, $result);
		if ($errcode != 0)
			throw new Exception($errcode);

		return $result;
	}

	/**
	 * 解密数据
	 *
	 * @param $post_data
	 * @param $get_data
	 * @return string
	 * @throws Exception
	 */
	protected function msgDecrypt($post_data, $get_data)
	{
		$result = '';

		$errcode = $this->crypt->decryptMsg($get_data['msg_signature'], $get_data['timestamp'], $get_data['nonce'], $post_data, $result);
		if ($errcode != 0)
			throw new Exception($errcode);

		return $result;
	}

	/**
	 * 获取xml节点信息
	 * @param $xml_tree
	 * @return array
	 */
	protected function getXmlNode($xml_tree)
	{
		$xml_info = array();
		$parse = xml_parser_create();
		xml_parser_set_option($parse, XML_OPTION_CASE_FOLDING, 0);

		$res = xml_parse_into_struct($parse, $xml_tree, $value, $index);
		xml_parser_free($parse);

		if ($res === 1) {
			foreach ($index as $key => $val) {
				if ($key == 'xml')
					continue;

				$xml_info[$key] = $value[$val[0]]['value'];
			}
		}

		return $xml_info;
	}

	/**
	 *
	 * 这样封装好之后  整个模块就可以通用 必要的时候修改基础代码即可 例如curl函数
	 * @param $url
	 * @param $data
	 * @param bool $is_post
	 * @return mixed
	 * @throws Exception
	 */
	protected function curl($url, $data, $is_post = true)
	{
		$result = $this->curlFunc($url, $data, $is_post);
		$return = json_decode($result, true);
		if (is_null($return))
			return $result;

		if (!isset($return['errcode']) || $return['errcode'] == 0) {
			return $return;
		} else {
			throw new Exception("curl error,url:{$url},data:" . json_encode($data) . ",result:{$result}");
		}
	}

	/**
	 * curl 函数
	 * @param $url
	 * @param $fields
	 * @param bool|true $ispost
	 * @return mixed
	 * @throws Exception
	 */
	protected function curlFunc($url, $fields, $ispost = true)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		if ($ispost) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36');

		//禁止ssl验证
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new Exception("http status code exception :{$httpStatusCode}", 0);
			}
		}
		curl_close($ch);
		return $response;
	}

	/**
	 * 创建日志对象
	 * @param $path
	 * @param $filename
	 * @return Zend_Log
	 */
	protected function createLogger($path, $filename)
	{
		$dir = APPLICATION_PATH . "/data/log/{$path}/";
		!is_dir($dir) && mkdir($dir, 0777, true) && chown($dir, posix_getuid());

		$fp = fopen($dir . date("Y-m-d") . ".{$filename}.log", "a", false);
		$writer = new Zend_Log_Writer_Stream($fp);
		$logger = new Zend_Log($writer);
		return $logger;
	}

	protected static $instance = null;

	protected function __construct($config)
	{
		foreach ($config as $key => $val) {
			if (isset($this->config[$key]))
				$this->config[$key] = $val;
		}

		//方便修改
		$this->storage = DM_Module_Redis::getInstance();

		//log
		$this->log = $this->createLogger("wechat", "wechat_open");

		//crypt
		$this->createCrypt();
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