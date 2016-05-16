<?php

/**
 * rsa
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午3:00
 */
class DM_Authorize_Rsa extends DM_Authorize_AuthorizeAbstract
{
	public static $instance = null;

	static public function getInstance()
	{
		is_null(self::$instance) &&
		self::$instance = new self();

		return self::$instance;
	}

	/**
	 * 验证签名
	 * @param $data
	 * @param $public_key
	 * @param $sign
	 * @param int $alg
	 * @return bool
	 */
	public function verify($data, $public_key, $sign, $alg = OPENSSL_ALGO_SHA1)
	{
		$res = openssl_pkey_get_public($public_key);

		$result = (bool)openssl_verify($this->createData($this->sortData($this->filterData($data))), base64_decode($sign), $res, $alg);
		openssl_free_key($res);

		return $result;
	}

	/**
	 * 签名
	 * @param $data
	 * @param $private_key
	 * @param int $alg
	 * @return mixed
	 */
	public function sign($data, $private_key, $alg = OPENSSL_ALGO_SHA1)
	{
		$res = openssl_pkey_get_private($private_key);
		openssl_sign($this->createData($this->sortData($this->filterData($data))), $sign, $res, $alg);
		openssl_free_key($res);

		return base64_encode($sign);
	}

	/**
	 * 解密数据
	 * @param $content
	 * @param $private_key
	 * @param int $padd
	 * @return string
	 *
	 */
	public function decrypt($content, $private_key, $padd = OPENSSL_PKCS1_PADDING)
	{
		$res = openssl_pkey_get_private($private_key);
		//用base64将内容还原成二进制
		$content = base64_decode($content);

		//把需要解密的内容，按128位拆开解密
		$result = '';
		for ($i = 0; $i < strlen($content) / 128; $i++) {
			$data = substr($content, $i * 128, 128);
			openssl_private_decrypt($data, $decrypt, $res, $padd);

			if (is_null($decrypt))
				return false;
			$result .= $decrypt;
		}

		openssl_free_key($res);
		return $result;
	}

	/**
	 * rsa公钥加密
	 * 分50位加密  操作117位会加密失败
	 * @param $content
	 * @param $public_key
	 * @param int $padd
	 * @return string
	 *
	 */
	public function encrypt($content, $public_key, $padd = OPENSSL_PKCS1_PADDING)
	{
		$inteval = 50;
		$public_key = openssl_pkey_get_public($public_key);
		$return = "";

		$end = ceil(strlen($content) / $inteval);

		for ($i = 0; $i < $end; $i++) {
			$result = '';
			openssl_public_encrypt(substr($content, $i * $inteval, $inteval), $result, $public_key, $padd);
			$return .= $result;
		}

		return base64_encode($return);
	}
}