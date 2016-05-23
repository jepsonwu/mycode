<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-23
 * Time: 下午1:21
 */
class DM_Authorize_Aes
{
	private $hex_iv = '00000000000000000000000000000000'; # converted JAVA byte code in to HEX and placed it here
	private $key = '!@#^&*-_+18//.6~'; #Same as in JAVA

	function __construct($key = null)
	{
		$this->key = hash('sha256', is_null($key) ? $this->key : $key, true);
	}

	/**
	 * 加密
	 * @param $str
	 * @return string
	 */
	function encrypt($str)
	{
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		mcrypt_generic_init($td, $this->key, $this->hexToStr($this->hex_iv));
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$pad = $block - (strlen($str) % $block);
		$str .= str_repeat(chr($pad), $pad);
		$encrypted = mcrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return base64_encode($encrypted);
	}

	/**
	 * 解密
	 * @param $code
	 * @return bool|string
	 */
	function decrypt($code)
	{
		if (!empty($code)) {
			$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
			mcrypt_generic_init($td, $this->key, $this->hexToStr($this->hex_iv));
			$str = mdecrypt_generic($td, base64_decode($code));
			$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			return $this->strippadding($str);
		}
		return $code;
	}

	/*
	 For PKCS7 padding
	 */
	private function addpadding($string, $blocksize = 16)
	{
		$len = strlen($string);
		$pad = $blocksize - ($len % $blocksize);
		$string .= str_repeat(chr($pad), $pad);
		return $string;
	}

	private function strippadding($string)
	{
		$slast = ord(substr($string, -1));
		$slastc = chr($slast);
		$pcheck = substr($string, -$slast);
		if (preg_match("/$slastc{" . $slast . "}/", $string)) {
			$string = substr($string, 0, strlen($string) - $slast);
			return $string;
		} else {
			return false;
		}
	}

	function hexToStr($hex)
	{
		$string = '';
		for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
			$string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
		}
		return $string;
	}

}