<?php
$key="8a476f61b64f34ec0b4f7f4c5aef0d1b";
$private_key_path="self_private_key.pem";

$url="http://dev.hapigou.com/V2/users/auth_demo";
//todo url_encode
//测试demo
$param=array(
	"timestamp"=>time(),
	"user_id"=>59
);

//$sign=rsaSign(createParam(sortParam(filterParam($param))),$private_key_path);
$sign=md5(createParam(sortParam(filterParam($param))).$key);
$param['sign']=$sign;
$param['sign_type']="MD5";

echo $url."?".createParamUrlencode($param);
$result=curl($url."?".createParamUrlencode($param));
//$result=curl("
http://dev.hapigou.com/v2/users/auth_demo?timestamp=1441003656&user_id=59&sign_type=MD5&sign=3023fbb9f212cbe0071db3216e55aa60");

var_dump($result);exit;

function createParamUrlencode($data) {
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

function rsaSign($data, $private_key_path) {
		$private_key=file_get_contents($private_key_path);

	    $res = openssl_pkey_get_private($private_key);
	    openssl_sign($data, $sign, $res);
	    openssl_free_key($res);
	    
		//base64编码
	    $sign = base64_encode($sign);
	    return $sign;
	}

function createParam($data){
	$param="";

	foreach ($data as $key => $value) {
		$param.=$key."=".$value."&";
	}
	$param=trim($param,"&");

	//转义字符

	return $param;
}

function filterParam($data){
	$return=array();

	foreach ($data as $key => $value) {
		if($key=="sign"||$key=="sign_type"||$key=="") 
			continue;
		else
			$return[$key]=trim($value);
	}

	return $return;
}

function sortParam($data){
	ksort($data);
	reset($data);

	return $data;
}


function curl($url,$type="GET",$data=null,$header=null,$option=null){
	$ch=curl_init();

	$options=array(
		CURLOPT_URL=>$url,
		CURLOPT_TIMEOUT=>10,
		CURLOPT_RETURNTRANSFER=>true,
	);

	!is_null($option)&&is_array($option)&&$options=array_merge($options,$option);

	curl_setopt_array($ch,$options);

	switch (strtoupper($type)){
		case 'GET':
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			break;
		case 'POST':
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
		case 'HEAD':
			
			break;
		case 'PUT':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
		case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
	}

	// if(is_null($header))
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:text/json"));
	// else
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$return=curl_exec($ch);
	if($return!==false){
		echo curl_getinfo($ch)['http_code'];
	}else{
		$return="ERR:(".curl_errno($ch).")".curl_error($ch);
	}

	curl_close($ch);

	return $return;
}

function curl_multi(){

}

function curl_ftp(){

}

function curl_ssl(){

}

function curl_file(){

}

function curl_proxy(){

}



