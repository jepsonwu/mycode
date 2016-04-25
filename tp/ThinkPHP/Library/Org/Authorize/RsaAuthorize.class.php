<?php
namespace Org\Authorize;

/**
* rsa认证驱动
*/
class RsaAuthorize
{
	/**
	 * [rsaVerify rsa验证签名]
	 * @param  [type] $data       [待签名数据]
	 * @param  [type] $secret_key [公钥]
	 * @param  [type] $sign       [签名]
	 * @return [type]             [bool]
	 */
	static public function rsaVerify($data,$secret_key_path,$sign,$alg=OPENSSL_ALGO_SHA1){
		$secret_key=file_get_contents($secret_key_path);
		$res = openssl_pkey_get_public($secret_key);

		$result = (bool)openssl_verify($data, base64_decode($sign), $res,$alg);
		openssl_free_key($res);

		return $result;
	}

	/**
	 * [rsaSign 生成签名]
	 * @param  [type] $data        [description]
	 * @param  [type] $private_key [description]
	 * @return [type]              [description]
	 */
	static public function rsaSign($data, $private_key_path,$alg=OPENSSL_ALGO_SHA1) {
		$private_key=file_get_contents($private_key_path);

		$res = openssl_pkey_get_private($private_key);
		openssl_sign($data, $sign, $res,$alg);
		openssl_free_key($res);
			    
		//base64编码
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * [rsaDecrypt 解密数据]
	 * @param  [type] $content          [description]
	 * @param  [type] $private_key_path [description]
	 * @return [type]                   [description]
	 */
	static public function rsaDecrypt($content, $private_key_path,$padd=OPENSSL_PKCS1_PADDING) {
		$priKey = file_get_contents($private_key_path);
		$res = openssl_pkey_get_private($priKey);

		//用base64将内容还原成二进制
		$content = base64_decode($content);

		//把需要解密的内容，按128位拆开解密
		$result  = '';
		for($i = 0; $i < strlen($content)/128; $i++  ) {
		    $data = substr($content, $i * 128, 128);
		    openssl_private_decrypt($data, $decrypt, $res,$padd);
		    $result .= $decrypt;
		}

		openssl_free_key($res);
		return $result;
	}

	/**
	 * rsa公钥加密
	 * @param  [type] $content         [description]
	 * @param  [type] $public_key_path [description]
	 * @return [type]                  [description]
	 */
	static public function rsaEncrypt($content,$public_key_path,$padd=OPENSSL_PKCS1_PADDING){
		$public_key=file_get_contents($public_key_path);
		$public_key=openssl_pkey_get_public($public_key);

		$result="";
		openssl_public_encrypt($content, $result, $public_key,$padd);

		return base64_encode($result);
	}
}