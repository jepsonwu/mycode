<?php
namespace Org\Authorize;

/**
* md5 sign
*/
class Md5Authorize
{
	/**
	 * [md5Verify md5验证签名]
	 * @param  [type] $data       [description]
	 * @param  [type] $secret_key [description]
	 * @param  [type] $sign       [description]
	 * @return [type]             [description]
	 */
	static public function md5Verify($data,$secret_key,$sign){
		$self_sign=md5($data.$secret_key);
		return $sign===$self_sign;
	}

	/**
	 * [md5Sign 生成签名]
	 * @param  [type] $data       [description]
	 * @param  [type] $secret_key [description]
	 * @return [type]             [description]
	 */
	static public function md5Sign($data,$secret_key){
		return md5($data.$secret_key);
	}
}