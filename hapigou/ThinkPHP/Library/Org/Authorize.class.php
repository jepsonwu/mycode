<?php
namespace Org;

/**
* API签名认证类，支持MD5，3DES，RSA
*/
class Authorize
{
	/**
	 * [$config 参数]
	 * @var array
	 */
	private $config=array(
		"md5_secret_key"=>"",//MD5方式密钥
		"mcrypt_secret_key"=>"",//mcrypt方式密钥,3DES
		"self_private_key_path"=>"",//口语聊私钥 生成签名,解密数据
		"vendor_public_key_path"=>"",//应用方公钥 验证签名
	);
	
	/**
	 * [__construct 构造函数]
	 * @param array $config [description]
	 */
	public function __construct($config=array())
	{
		$this->config=array_merge($this->config,$config);
	}

	/**
	 * [authVerify 认证签名,]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function authVerify($request){
		//签名方法
		$sign_type=strtolower($request['sign_type']);

		//密钥
		if(in_array($sign_type, array("md5","mcrypt"))){
			$secret_key=$this->config[$sign_type."_secret_key"];
		}else{
			$secret_key=$this->config["vendor_public_key_path"];
		}

		//待签名参数
		$param=self::createParam(self::sortParam(self::filterParam($request)));

		//class
		$class="\\Org\\Authorize\\".ucfirst($sign_type)."Authorize";
		
		//method
		$method=$sign_type."Verify";
		
		//执行驱动方法
		$result=$class::$method($param,$secret_key,$request['sign']);

		return $result;
	}

	/**
	 * [authSign 生成签名,返回包含request、sign的请求字符串]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function authSign($request){
		//签名方法
		$sign_type=strtolower($request['sign_type']);

		//密钥
		if(in_array($sign_type, array("md5","mcrypt"))){
			$secret_key=$this->config[$sign_type."_secret_key"];
		}else{
			$secret_key=$this->config["self_private_key_path"];
		}

		//待签名参数
		$param=self::createParam(self::sortParam(self::filterParam($request)));

		//class
		$class="\\Org\\Authorize\\".ucfirst($sign_type)."Authorize";
		
		//method
		$method=$sign_type."Sign";
		
		//执行驱动方法
		$result=$class::$method($param,$secret_key);
		return $param."&sign=".$result."&sign_type=".strtoupper($sign_type);
	}

	/**
	 * [authDecrypt 解密数据]
	 * @return [array] [解密之后的数据]
	 */
	public function authDecrypt($request){
		//加密方法
		$encrypt_type=strtolower($request['encrypt_type']);

		//密钥
		if(in_array($encrypt_type, array("mcrypt"))){
			$secret_key=$this->config[$encrypt_type."_secret_key"];
		}else{
			$secret_key=$this->config["self_private_key_path"];
		}

		//class
		$class="\\Org\\Authorize\\".ucfirst($encrypt_type)."Authorize";
		
		//method
		$method=$encrypt_type."Decrypt";
		
		//执行驱动方法
		$result=$class::$method($request['encrypt_data'],$secret_key);

		//字符串转数组
		parse_str($result,$return);
	
		return $return;
	}

	/**
	 * [authDecrypt 加密数据]
	 * @return [array] [加密之后的数据]
	 */
	public function authEncrypt($request){
		//加密方法
		$encrypt_type=strtolower($request['encrypt_type']);

		//密钥
		if(in_array($encrypt_type, array("mcrypt"))){
			$secret_key=$this->config[$encrypt_type."_secret_key"];
		}else{
			$secret_key=$this->config["vendor_public_key_path"];
		}

		//class
		$class="\\Org\\Authorize\\".ucfirst($encrypt_type)."Authorize";
		
		//method
		$method=$encrypt_type."Encrypt";
		
		$request['encrypt_data']=self::createParam(self::sortParam($request['encrypt_data']));

		//执行驱动方法
		$result=$class::$method($request['encrypt_data'],$secret_key);
	
		return $result;
	}

	/**
	 * todo url_encode
	 * [create_param 生成param]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function createParam($data){
		$param="";

		foreach ($data as $key => $value) {
			$param.=$key."=".$value."&";
		}
		$param=trim($param,"&");

		//转义字符

		return $param;
	}

	/**
	 * [createParamUrlencode urlencode生成参数]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function createParamUrlencode($data) {
		$param  = "";
		while (list ($key, $val) = each ($data)) {
			$param.=$key."=".urlencode($val)."&";
		}
		//去掉最后一个&字符
		$param = substr($param,0,count($param)-2);
		
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$param = stripslashes($param);}
		
		return $param;
	}

	/**
	 * [filter_param 过滤不需要签名的参数]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function filterParam($data){
		$return=array();

		foreach ($data as $key => $value) {
			if($key=="sign"||$key=="sign_type"||$key=="") 
				continue;
			else
				$return[$key]=trim($value);
		}

		return $return;
	}

	/**
	 * [sort_param 按字典排序参数]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function sortParam($data){
		ksort($data);

		return $data;
	}

	public function __get($name){
		return isset($this->config[$name])?$this->config[$name]:"";
	}

	public function __set($name,$value){
		return $this->config[$name]=$value;
	}

	public function __isset($name){
		return isset($this->config[$name]);
	}
}