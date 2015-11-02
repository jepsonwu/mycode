<?php
$url = "http://hpg.com/v2/teachers/apply";
$file_name = "aa.png";
$data = array(
	"user_id"=>133,
	"real_name"=>"wu",
	"email"=>"wjp@163.com",
	"certificate_photo"=>base64_encode(file_get_contents($file_name)),
	"skype"=>"sdffas",
	"job"=>"老师"
);

$result = curl($url, "POST", $data,array("version:2"));
var_dump($result);

//$fp=fopen('data://text/plain;base64,','r');
//$meta=stream_get_meta_data($fp);
//print_r($meta);
//echo  file_get_contents ( 'data://text/plain;base64,SSBsb3ZlIFBIUAo=' );
//php 重定向标准输入输出
//while (true) {
//	$line = fgets(STDIN);//	if ($line == "exit".PHP_EOL)
//		break;
//	echo $line;
//}

//fclose(STDOUT);
//$STDOUT=fopen("text.log","a");
//echo "a";


// $key="8a476f61b64f34ec0b4f7f4c5aef0d1b";
// $private_key_path="self_private_key.pem";
// $service_public_key_path="service_public_key.pem";

// $url="http://hpg.com/V2/users/auth_demo";
// //todo url_encode
// //测试demo
// $param=array(
// 	"timestamp"=>time(),
// 	"app_id"=>"20150001",
// 	"encrypt_type"=>"RSA",
// );

// $data=array(
// 	"user_id"=>1,
// 	"name"=>"wujp"
// );
// $param['encrypt_data']=rsaEncrypt(createParam(sortParam($data)),$service_public_key_path);

// //$sign=rsaSign(createParam(sortParam(filterParam($param))),$private_key_path);
// $sign=md5(createParam(sortParam(filterParam($param))).$key);
// // echo $sign;exit;
// $param['sign']=$sign;
// $param['sign_type']="MD5";

// //echo $url."?".createParamUrlencode($param)."\n";

// $result=curl($url."?".createParamUrlencode($param));
// var_dump($result);exit;
//$filename="abc360_access.log";
//$file=file_get_contents($filename);
//
//$data=array(
//	"user_id"=>59,
//	"feedback_id"=>1,
//	"start_time"=>"1442115258",
//	"end_time"=>"1442201613",
//	"file"=>urlencode($file),
//);
//$result=curl("http://dev.hapigou.com/V2/upload/wap_logs","POST",$data);
//var_dump($result);


function curl($url, $type = "GET", $data = null, $header = null, $option = null)
{
	$ch = curl_init();

	$options = array(
		CURLOPT_URL => $url,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_RETURNTRANSFER => true,
	);

	!is_null($option) && is_array($option) && $options = array_merge($options, $option);

	curl_setopt_array($ch, $options);

	switch (strtoupper($type)) {
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

	if(!is_null($header)&&is_array($header)){
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
	}

	// if(is_null($header))
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:text/json"));
	// else
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$return = curl_exec($ch);
	if ($return !== false) {
		echo curl_getinfo($ch)['http_code'];
	} else {
		$return = "ERR:(" . curl_errno($ch) . ")" . curl_error($ch);
	}

	curl_close($ch);

	return $return;
}

function curl_multi()
{

}

function curl_ftp()
{

}

function curl_ssl()
{

}

function curl_file()
{

}

function curl_proxy()
{

}

//pkcs1
function rsaEncrypt($content, $public_key_path)
{
	$public_key = file_get_contents($public_key_path);
	$public_key = openssl_pkey_get_public($public_key);

	$result = "";
	openssl_public_encrypt($content, $result, $public_key);

	return base64_encode($result);
}

function createParamUrlencode($data)
{
	$param = "";
	while (list ($key, $val) = each($data)) {
		$param .= $key . "=" . urlencode($val) . "&";
	}
	//去掉最后一个&字符
	$param = substr($param, 0, count($param) - 2);

	//如果存在转义字符，那么去掉转义
	if (get_magic_quotes_gpc()) {
		$param = stripslashes($param);
	}

	return $param;
}

function rsaSign($data, $private_key_path)
{
	$private_key = file_get_contents($private_key_path);

	$res = openssl_pkey_get_private($private_key);
	openssl_sign($data, $sign, $res);
	openssl_free_key($res);

	//base64编码
	$sign = base64_encode($sign);
	return $sign;
}

function createParam($data)
{
	$param = "";

	foreach ($data as $key => $value) {
		$param .= $key . "=" . $value . "&";
	}
	$param = trim($param, "&");

	//转义字符

	return $param;
}

function filterParam($data)
{
	$return = array();

	foreach ($data as $key => $value) {
		if ($key == "sign" || $key == "sign_type" || $key == "")
			continue;
		else
			$return[$key] = trim($value);
	}

	return $return;
}

function sortParam($data)
{
	ksort($data);
	reset($data);

	return $data;
}


