<?php

/**
 * 微信公众号第三方开反平台接口
 * User: jepson <jepson@duomai.com>
 * Date: 16-4-26
 * Time: 下午3:50
 */
class Api_WechatOpenController extends Action_Api
{
	public function init()
	{
		parent::init();
		$config = DM_Controller_Front::getInstance()->getConfig()->toArray();
		$this->_config = $config = $config['wechat_open']['settings'];
		$this->_wechat_open_model = DM_Wechat_WechatOpen::getInstance($config);
		//注册msghook函数
		$this->_wechat_open_model->registerMsgHookRouter("Model_WechatOpen_Common::msgHookRouter");

		$this->_logger = $this->createLogger("wechat", "wechat_open");
	}

	protected $_config = array();

	protected $_wechat_open_model = null;

	protected $_logger = null;

	/**
	 * 微信授权事件url
	 */
	public function openAuthEventAction()
	{
		try {
			//get 参数
			$get_data = array(
				"timestamp" => $this->_request->getParam("timestamp", ""),
				"nonce" => $this->_request->getParam("nonce", ""),
				"msg_signature" => $this->_request->getParam("msg_signature", ""),
				"encrypt_type" => $this->_request->getParam("encrypt_type", ""),
			);
			$post_data = $this->getRawData();

			$result = $this->_wechat_open_model->authEvent($post_data, $get_data);
			echo $result;
		} catch (Exception $e) {
			echo "failed";
			$this->_logger->log("Open auth error:" . $e->getMessage() . "\n\n", Zend_Log::ERR);
		}
	}

	/**
	 * 公众号消息与事件接受url 5秒处理
	 */
	public function openMemberEventAction()
	{
		try {
			//get 参数
			$get_data = array(
				"timestamp" => $this->_request->getParam("timestamp", ""),
				"nonce" => $this->_request->getParam("nonce", ""),
				"msg_signature" => $this->_request->getParam("msg_signature", ""),
				"encrypt_type" => $this->_request->getParam("encrypt_type", ""),
			);
			$post_data = $this->getRawData();

			$this->_wechat_open_model->memberEvent($post_data, $get_data);
		} catch (Exception $e) {
			echo "failed";
			$this->_logger->log("Open member event error:" . $e->getMessage() . "\n\n", Zend_Log::ERR);
		}
	}

	/**
	 * 公众号授权首页
	 */
	public function memberAuthAction()
	{
		try {
			header('Content-type: text/html');
			Zend_Layout::startMvc()->disableLayout();

			$this->view->url = $this->_wechat_open_model->getPreAuthCode("cz.caizhu.com/api/wechat-open/member-auth-redirect");
			echo $this->view->render('activity/wechat/auth-index.phtml');

		} catch (Exception $e) {
			$this->_logger->log("Member auth error:" . $e->getMessage() . "\n\n", Zend_log::ERR);
			echo "failed";
		}
	}

	/**
	 * 公众号授权登陆回调接口
	 */
	public function memberAuthRedirectAction()
	{
		try {
			//get 参数
			$get_data = array(
				"auth_code" => $this->_request->getParam("auth_code", ""),
				"expires_in" => $this->_request->getParam("expires_in", ""),
			);

			if (empty($get_data['auth_code']))
				throw new Exception("authorization_code is empty");

			$this->_wechat_open_model->queryAuth($get_data['auth_code']);
			echo "success";
		} catch (Exception $e) {
			echo "failed";
			$this->_logger->log("Member auth redirect error:" . $e->getMessage() . "\n\n", Zend_log::ERR);
		}
	}

	protected $jsSdkSignatureConf = array(
		array("url", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("app_id", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 获取js-sdk注册权限参数
	 */
	public function jsSdkSignatureAction()
	{
		try {
			$this->_wechat_open_model->setAuthorizerAppID($this->_param['app_id']);
			parent::succReturn($this->_wechat_open_model->jsSdkSignature($this->_param['url']));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $qrcodeConf = array(
		array("app_id", "require", "请填写APPID！", DM_Helper_Filter::MUST_VALIDATE),
		array("type", "1,2,3", "请选择二维码生成类型！", DM_Helper_Filter::MUST_VALIDATE, "in"),
		array("info", "require", "请填写二维码内容！", DM_Helper_Filter::MUST_VALIDATE),
		array("expire", "number", "过期时间格式有误！", DM_Helper_Filter::EXISTS_VALIDATE),
	);

	/**
	 * 生成待场景值二维码
	 */
	public function qrcodeAction()
	{
		try {
			$this->_wechat_open_model->setAuthorizerAppID($this->_param['app_id']);

			$expire = isset($this->_param['expire']) ? $this->_param['expire'] : null;
			$qrcode_ticket = $this->_wechat_open_model->qrcodeCreate($this->_param['type'], $this->_param['info'], $expire);
			//可根据该地址自行生成需要的二维码图片

			header("Content-Type:image/jpg");
			echo $this->_wechat_open_model->qrcodeShow($qrcode_ticket['ticket']);
		} catch (Exception $e) {
			$this->failReturn($e->getMessage());
		}
	}

	/**
	 * 获取post原始数据
	 * @return bool|string
	 */
	protected function getRawData()
	{
		return file_get_contents("php://input");
	}
}