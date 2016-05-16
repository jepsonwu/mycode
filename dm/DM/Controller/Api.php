<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午3:08
 */
class DM_Controller_Api extends DM_Controller_Rest
{
	//校验之后的请求数据
	protected $_param = array();

	//返回数据对称加密key
	protected $_return_encrypt_key;

	public function init()
	{
		parent::init();

		//校验参数
		$this->checkParam();
	}

	/**
	 * 判断是否登录，直接返回
	 */
	protected final function isLoginOutput()
	{
		$isLogin = $this->isLogin();
		if (!$isLogin) {
			return $this->returnJson(parent::STATUS_NEED_LOGIN, $this->getLang()->_("api.base.error.notLogin"));
		} else {
			return true;
		}
	}

	/**
	 * 请求方式校验
	 * 校验参数
	 */
	protected function checkParam()
	{
		$action = explode("-", $this->_getParam('action'));
		$action_conf = array_shift($action);
		$action_conf = $action_conf . implode("", array_map("ucfirst", $action)) . 'Conf';
		$action_conf =& $this->$action_conf;

		//请求方式校验
		if (isset($action_conf['method'])) {
			$this->_request->getMethod() !== strtoupper($action_conf['method']) &&
			$this->failReturn("Not Allowed Method", -405);//应该是报http错误 anyways
			unset($action_conf['method']);
		}

		//根据请求类型获取参数  避免CSRF漏洞
		$all_param = array();
		switch ($this->_request->getMethod()) {
			case "PUT":
				break;
			case "DELETE":
				break;
			case "POST":
				$all_param = $this->_request->getPost();
				break;
			case "GET":
				$all_param = $this->_request->getQuery();
				break;
		}

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
		//是否需要对解密的值进行签名校验 sign=true

		if (isset($action_conf)) {
			$param = DM_Helper_Filter::autoValidation($all_param, $action_conf);

			if (is_string($param))
				$this->failReturn(empty($param) ? "Bad Request" : $param, -400);
			else
				$this->_param = $param;
		}
	}

	/**
	 * API失败返回函数
	 * @param $msg
	 * @param null $code
	 */
	protected function failReturn($msg, $code = null)
	{
		$this->returnJson(is_null($code) ? parent::STATUS_FAILURE : $code, $msg);
	}

	/**
	 * API成功返回函数 方便以后修改数据返回格式
	 * @param $data
	 */
	protected function succReturn($data)
	{
		if ($this->_return_encrypt_key) {
			$mcrypt = new Model_CryptAES($this->_return_encrypt_key);
			$data = $mcrypt->encrypt(json_encode($data));

			$data = array(
				'data' => $data
			);
		}

		$this->returnJson(parent::STATUS_OK, '', $data);
	}

	public function checkDeny()
	{
		$memberID = $this->memberInfo->MemberID;
		$memberModel = new DM_Model_Account_Members();
		$mInfo = $memberModel->getOne($memberID);
		if (empty($mInfo) || $mInfo['Status'] == 0) {
			$this->returnJson(parent::STATUS_FAILURE, '');
		}
	}
}