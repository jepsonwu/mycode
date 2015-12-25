<?php
include_once 'AuthorizeAbstract.php';
/**
 * mcrypt
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午3:00
 */
class DM_Authorize_Mcrypt
{
	// 初始化密钥
	private $_common_key = '';
	// 向量
	const KEY_IV = "\x00\x00\x00\x00\x00\x00\x00\x00";

	public static $instance = null;

	static public function getInstance($encode_key)
	{
		is_null(self::$instance) &&
		self::$instance = new self($encode_key);

		return self::$instance;
	}

	/**
	 * @param $encode_key
	 */
	private function __construct($encode_key)
	{
		self::setCommonKey($encode_key);
	}

	/**
	 * @param $encode_key
	 */
	public function setCommonKey($encode_key)
	{
		$this->_common_key = $encode_key;
	}

	/**
	 * @return string
	 *
	 */
	public function getCommonKey()
	{
		return $this->_common_key;
	}

	/**
	 * 加密
	 *
	 * @param $data
	 * @return mixed $enc_data 密文
	 * @throws Exception
	 */
	public function encrypt($data)
	{
		// 打开加密算法与模式
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		// 获取初始化向量长度
		$ks = mcrypt_enc_get_iv_size($td);
		// 验证向量长度
		if (strlen(self::KEY_IV) !== $ks)
			return "";

		// 初始化加密
		mcrypt_generic_init($td, self::getCommonKey(), self::KEY_IV);

		// 加密
		$crypt_text = mcrypt_generic($td, $data);

		// 清理缓冲区并且关闭加密模块
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		// 处理加密数据
		$enc_data = base64_encode($crypt_text);
		return $enc_data;
	}

	/**
	 * 解密
	 *
	 * @param $enc_data
	 * @return string $dec_data 明文
	 * @throws Exception
	 */
	public function decrypt($enc_data)
	{
		// 密文为空
		if (empty($enc_data)) return '';
		// 加密数据格式化
		$data = base64_decode($enc_data);
		// 打开加密算法与模式
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');

		// 初始化加密
		mcrypt_generic_init($td, self::getCommonKey(), self::KEY_IV);

		// 解密
		$dec_data = mdecrypt_generic($td, $data);

		// 清理缓冲区并且关闭加密模块
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		// 返回解密数据
		return rtrim($dec_data, "\0");
	}
}