<?php

/**
 * API控制器抽象类，包含如下功能模块：
 * 1.签名
 * 2.参数过滤
 * 3.数据加密
 * 4.数据返回实例
 *
 * HTTP 错误码：
 * 420  数据解密错误
 * 418  签名时间戳过期
 * 405  请求方式错误
 * 401  签名错误
 * 400  参数参错 强校验
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午3:08
 */
abstract class DM_Controller_Api extends DM_Controller_Rest
{
	//加密响应数据模式
	protected $_encrypt_return = array(
		"type" => null,
	);

	//是否需要API授权
	protected $_authorize = false;

	//签名过期时间 15分钟
	protected $_valid_timestamp = 900;

	//是否需要用户授权,默认授权
	protected $_check_user = false;

	//请求参数
	protected $_request_param = null;

	//response
	protected $_response_type = 'json';

	//系统级参数验证
	protected $_api_fields = array(
		array("sign", "require", 10001, DM_Helper_Filter::MUST_VALIDATE),
		array("sign_type", "MD5,RSA", 10002, DM_Helper_Filter::MUST_VALIDATE, "in"),
		array("timestamp", "number", 10003, DM_Helper_Filter::MUST_VALIDATE),
		array("encrypt_type", "HTTPS,RSA,MCRYPT", 10004, DM_Helper_Filter::EXISTS_VALIDATE, "in"),//是否存在加密
		array("encrypt_data", "require", 10005, DM_Helper_Filter::EXISTS_VALIDATE),//加密之后的数据体
	);
	//系统参数错误
	protected $_api_code_map = array(
		10001 => "签名有误！",
		10002 => "签名方式有误，允许MD5,RSA！",
		10003 => "时间戳为空或格式不正确！",
		10004 => "加密方式有误！",
		10005 => "加密数据体格式有误！",
	);

	public function init()
	{
		parent::init();

		//记录请求次数 todo

		//请求参数
		$action = explode("-", $this->_getParam('action'));
		$action_conf = array_shift($action);
		$action_conf = $action_conf . implode("", array_map("ucfirst", $action)) . 'Conf';
		$action_conf =& $this->$action_conf;

		//请求方式校验
		if (isset($action_conf['method'])) {
			$this->_method !== strtoupper($action_conf['method']) && $this->responseError(405);
			unset($action_conf['method']);
		}

		//根据请求类型获取参数  避免CSRF漏洞 todo  form-data|x-www-form-urlencode|raw|binary 完善其它请求方式
		switch ($this->_method) {
			case "PUT":
			case "DELETE":
				$this->_request_param = file_get_contents("php://input");
				break;
			case "POST":
				$this->_request_param = $this->_request->getPost();
				break;
			case "GET":
				$path_info = $this->_request->getPathInfo();
				$this->_request_param = $this->_request->getQuery();
				unset($this->_request_param[substr($path_info, 1)]);
				break;
		}

		//系统级参数,包含API签名
		//app_key(后期不同前端有不同应用ID，用于查找secret_key，前期不做)
		//在没有做app_key之前  只能客户端通过公钥加密  服务端私钥解密
		//secret_key(密钥，32位)
		//sign_type(签名类型：md5，hash，mcrypt，rsa  默认md5)
		//sign(签名，除sign,sign_type和图片之外的所有参数拼接成字符串并且末尾加上密钥用于签名,参数必须排序，去除末尾空格)
		//timestamp 时间戳，服务端允许和前端的时间误差为15分钟
		//V4  接口版本号，不做
		isset($action_conf['authorize']) && $this->_authorize = $action_conf['authorize'];
		$this->_authorize && $this->authorize();

		//请求次数限制 todo 一套完整的规则
		isset($action_conf['limit_request']) &&
		$action_conf['limit_request'] && $this->limitRequest();

		//用户授权
		isset($action_conf['check_user']) && $this->_check_user = $action_conf['check_user'];
		$this->_check_user && $this->checkUser();

		//请求参数过滤
		isset($action_conf['check_param']) && $this->checkParam($action_conf['check_param']);

		unset($action_conf);
	}

	/**
	 * 检查用户登陆状态
	 */
	protected function checkUser()
	{
		$apiAccount = DM_Account_Api::getInstance();
		try {
			$apiAccount->isLogin();
		} catch (Exception $e) {
			$this->failReturn($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * API签名认证 无法做到参数强校验 正则顺序强匹配
	 * API数据解密
	 * @return bool
	 */
	protected function authorize()
	{
		//系统级参数判断
		$res = DM_Helper_Filter::autoValidation($this->_request_param, $this->_api_fields);
		if (is_array($res)) {
			$this->_request_param['sign_type'] = $res['sign_type'];
		} elseif (empty($res)) {
			parent::responseError(400);
		} else {
			$this->failReturn($this->_api_code_map[$res], $res);
		}

		//timestamp判断 15分钟
		if ((time() - $this->_request_param['timestamp']) > $this->_valid_timestamp)
			parent::responseError(418);

		//401未授权
		switch ($this->_request_param['sign_type']) {
			case "MD5":
				$md5Model = DM_Authorize_Md5::getInstance();
				!$md5Model->verify($this->_request_param, $this->_config['api']['sign_md5_key'], $this->_request_param['sign']) &&
				$this->responseError(401);
				break;
			case "RSA":
				$rsaModel = DM_Authorize_Rsa::getInstance();
				!$rsaModel->verify($this->_request_param, $this->_config['api']['sign_public_key'], $this->_request_param['sign']) &&
				$this->responseError(401);
				break;
		}

		unset($this->_request_param['sign_type'], $this->_request_param['timestamp'], $this->_request_param['sign']);

		//如果有加密 json格式
		$this->decryptRequest();

		return true;
	}

	/**
	 * 解密数据到$this->_request
	 * @return bool
	 */
	protected function decryptRequest()
	{
		if (isset($this->_request_param['encrypt_type'])) {
			!isset($this->_request_param['encrypt_data']) && $this->failReturn($this->_api_code_map[10005], 10005);

			$this->_encrypt_return = array("type" => $this->_request_param['encrypt_type']);
			switch ($this->_request_param['encrypt_type']) {
				case "MCRYPT"://todo 研究一下
					break;
				case "RSA":
					$rsaModel = DM_Authorize_Rsa::getInstance();
					$this->_request_param = $rsaModel->decrypt($this->_request_param['encrypt_data'], $this->_config['api']['crypt_private_key']);
					break;
				case "HTTPS":
					$rsaModel = DM_Authorize_Rsa::getInstance();
					$this->_request_param = $rsaModel->decrypt($this->_request_param['encrypt_data'], $this->_config['api']['crypt_private_key']);
					$this->_request_param === false && $this->responseError(420);
					$this->_request_param && $this->_request_param = json_decode($this->_request_param, true);

					//key
					if (isset($this->_request_param['encrypt_key']) && $this->_request_param['encrypt_key']) {
						$this->_encrypt_return['encrypt_key'] = $this->_request_param['encrypt_key'];
						unset($this->_request_param['encrypt_key']);
					} else {
						$this->responseError(420);
					}
					break;
			}
		}

		return true;
	}

	abstract protected function getCodeMsg($code);

	/**
	 * 403访问受限，授权过期,通过app_key对session_key的判断，todo
	 */
	protected function appidRequest()
	{
		return true;
	}

	/**
	 * 419请求过多被限制
	 * @return bool
	 */
	protected function limitRequest()
	{
		return true;
	}

	/**
	 * 校验参数 严格参数校验
	 * @param $filter
	 */
	protected function checkParam($filter)
	{
		$this->_param = DM_Helper_Filter::autoValidation($this->_request_param, $filter, true);
		if (!is_array($this->_param)) {
			if (empty($this->_param))
				$this->responseError(400);
			else
				$this->failReturn($this->getCodeMsg($this->_param), $this->_param);
		}
	}

	/**
	 * API失败返回函数
	 * @param $msg
	 * @param null $code
	 */
	protected function failReturn($msg, $code)
	{
		$result['code'] = intval($code);
		$result['message'] = $msg;
		$result['data'] = array();
		$this->response($result, $this->_response_type);
	}

	/**
	 * API正确返回函数
	 * @param array $data
	 */
	protected function succReturn($data = array())
	{
		$result['code'] = 0;
		$result['message'] = 'success';
		$result['data'] = $this->encryptReturn($data);
		$this->response($result, $this->_response_type);
	}

	/**
	 * 加密响应模式
	 * @param $data
	 * @return string
	 */
	protected function encryptReturn($data)
	{
		switch ($this->_encrypt_return['type']) {
			case "HTTPS":
				$mcrypt = new DM_Authorize_Aes($this->_encrypt_return['encrypt_key']);
				$data = $mcrypt->encrypt(json_encode($data));
				break;
			case "RSA":
				$rsaModel = DM_Authorize_Rsa::getInstance();
				$data = $rsaModel->encrypt(json_encode($data), $this->_config['sign_public_key']);
				break;
		}

		return $data;
	}
}