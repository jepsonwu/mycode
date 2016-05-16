<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午3:08
 */
class DM_Controller_Api extends DM_Controller_Rest
{
	//返回数据对称加密key
	protected $_return_encrypt_key;

	//是否需要API授权
	protected $_authorize = false;

	//是否请求限制
	protected $_limit_request = true;

	//是否需要用户授权,默认授权
	protected $_check_user = false;

	//请求参数
	protected $_request = null;

	//系统级参数验证
	//todo app_key
	protected $_api_fields = array(
		array("sign", "require", null, 1),
		array("sign_type", "MD5,RSA", null, 0, "in", "MD5"),
		array("timestamp", "number", null, 1, null),
		array("app_id", "number", null, 1),
		array("encrypt_type", "RSA,MCRYPT", null, 0, "in", "RSA"),//是否存在加密
		array("encrypt_data", "require", null, 0),//加密之后的数据体
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
			$this->_method !== strtoupper($action_conf['method']) && $this->sendHttpError(405);
			unset($action_conf['method']);
		}

		//根据请求类型获取参数  避免CSRF漏洞
		switch ($this->_method) {
			case "PUT":
			case "DELETE":
				$this->_request = file_get_contents("php://input");
				break;
			case "POST":
				$this->_request = $this->_request->getPost();
				break;
			case "GET":
				$this->_request = $this->_request->getQuery();
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
		if (isset($action_conf['authorize'])) {
			$action_conf['authorize'] && $this->authorize();
		} else {
			$this->_authorize && $this->authorize();
		}

		//请求次数限制
		if (isset($action_conf['limit_request'])) {
			$action_conf['limit_request'] && $this->limitRequest();
		} else {
			$this->_limit_request && $this->limitRequest();
		}

		//用户授权
		if (isset($action_conf['check_user']) && $action_conf['check_user']) {
			$this->checkUser();
		} elseif ($this->_check_user) {
			$this->checkUser();
		}

		//请求参数过滤
		if (isset($action_conf['check_fields'])) {
			$this->checkFields($action_conf);
		}

		unset($action_conf);
	}

	/**
	 * 检查用户登陆状态
	 */
	protected function checkUser()
	{
		$apiAccount = DM_Account_Api::getInstance();
		$apiAccount->isLogin() && $this->failReturn("", -100);
	}

	/**
	 * API签名认证
	 * API数据解密
	 * @return bool
	 */
	protected function authorize()
	{
		//系统级参数判断
		$res = DM_Helper_Filter::autoValidation($this->_request, $this->_api_fields);
		if (is_array($res)) {
			$this->_request['sign_type'] = $res['sign_type'];
		} elseif (empty($res)) {
			parent::sendHttpError(400);
		} else {
			$this->failReturn($this->getCodeMsg($res), $res);
		}

		//403访问受限
		$this->appIdRequest();

		//timestamp判断 15分钟
		if ((time() - $this->_request['timestamp']) > 15 * 60)
			parent::sendHttpError(418);

		//401未授权 签名

		//如果有加密
		//$this->decryptRequest();

		return true;
	}

	/**
	 * 解密数据到$this->request
	 * @return bool
	 */
	protected function decryptRequest()
	{
		if (isset($this->request['encrypt_data']) && $this->request['encrypt_data']) {
			!isset($this->request['encrypt_type']) && ($this->request['encrypt_type'] = "RSA");

			switch (strtoupper($this->request['encrypt_type'])) {
				case 'MCRYPT':
					$conf = array("mcrypt_secret_key" => $this->_app_conf['COOLCHAT_CRYPT_KEY']);
					break;

				default://rsa
					$conf = array("self_private_key_path" => C("SELF_PRIVATE_KEY_PATH"));
					break;
			}

			$authorize = new \Org\Authorize($conf);
			$this->request = $authorize->authDecrypt($this->request);

			//解密错误
			!$this->request && parent::DResponse(420);
		}

		return true;
	}

	abstract protected function getCodeMsg($code);

	/**
	 * 403访问受限，授权过期,通过app_key对session_key的判断，todo
	 */
	protected function appIdRequest()
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
	 * 请求方式校验
	 * 校验参数
	 */
	protected function checkParam()
	{


		//是否需要解密 加密方案：encrypt：rsa|  data  PKCS1
		if (isset($action_conf['encrypt'])) {
			!isset($all_param['data']) && $this->failReturn("Illegal Request", -407);
			switch (strtoupper($action_conf['encrypt'])) {
				case "RSA":
					$rsa = DM_Authorize_Rsa::getInstance();
					$private_key = DM_Controller_Front::getInstance()->getConfig()->app->private_key;

					$all_param = $rsa->decrypt($all_param['data'], $private_key);
					if ($all_param === false)
						$this->failReturn("Illegal Request", -407);
					$all_param && $all_param = json_decode($all_param, true);

					//key
					if (isset($all_param['encrypt_key']) && $all_param['encrypt_key'])
						$this->_return_encrypt_key = $all_param['encrypt_key'];
					else
						$this->failReturn("Illegal Request", -407);
					break;
			}
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
		$this->response($result, $this->_request_type);
	}

	/**
	 * API正确返回函数
	 * @param array $data
	 */
	protected function succReturn($data = array())
	{
		if ($this->_return_encrypt_key) {
			$mcrypt = new Model_CryptAES($this->_return_encrypt_key);
			$data = $mcrypt->encrypt(json_encode($data));
		}

		$result['code'] = 0;
		$result['message'] = 'success';
		$result['data'] = $data;
		$this->response($result, $this->_request_type);
	}
}