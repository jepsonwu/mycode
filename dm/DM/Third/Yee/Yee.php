<?php
if (!class_exists('yeepayMPay'))
	include 'yeepayMPay.php';

/**
 * yee api request
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-21
 * Time: 上午10:15
 */
class DM_Third_Yee_Yee
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private $yee_obj;

	const YEEPAY_PAY_API = 1;
	const YEEPAY_MERCHANT_API = 2;
	const YEEPAY_MOBILE_API = 3;

	private function __construct()
	{
		$config = DM_Controller_Front::getInstance()->getConfig()->yee;
		$this->yee_obj = new yeepayMPay($config->merchant_account, $config->merchant_public_key,
			$config->merchant_private_key, $config->yee_public_key);
	}

	/**
	 * 易宝POST方式API请求
	 * @param $url
	 * @param $post
	 * @param int $type
	 * @return mixed
	 * @throws Exception
	 */
	public function apiPost($url, $post, $type = self::YEEPAY_PAY_API)
	{
		try {
			return $this->yee_obj->post($type, $url, $post);
		} catch (Exception $e) {
			$code = $e->getCode();
			if ($code > 0 && isset(DM_Third_Yee_YeeCode::$_code[$code]))
				$message = DM_Third_Yee_YeeCode::$_code[$code];
			else
				$message = $e->getMessage();

			throw new Exception($message, $code);
		}
	}

	/**
	 * 回调
	 * @param $data
	 * @param $encryptkey
	 * @return array
	 */
	public function callback($data, $encryptkey)
	{
		return $this->yee_obj->callback($data, $encryptkey);
	}
}