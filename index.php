<?php
echo '<pre>';
$uids = array();
$info = array(
	1 => array("uid" => 1,),
	8 => array("uid" => 8,),
	2 => array("uid" => 2,),
	10 => array("uid" => 10,),
);
foreach ($info as $key => $val)
	$uids[] = $key;

$info_temp = array(
	3 => array("uid" => 3),
);

$info[key($info_temp)] = current($info_temp);

print_r($uids);
print_r($info);
exit;


//include_once 'Authorize/Rsa.php';
//include_once 'Authorize/Mcrypt.php';
//include_once 'Authorize/CryptAES.php';
//include_once 'Yee/yeepayMPay.php';

$private_key = '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAM85HJf8a/XqFfPl
R4LjJPeNGT4V2wc47Bee0LYlrAfUu0FLIz7sbdzf+3WCrtC9hZ7SDzSM3GgtG7bv
i+SuZxQPh/XbrJ5fOfPjdV+ZAfSNviHecGs2Oe/e2WZ18xEAJhPYDtSfajt4gZV+
w+76J7QOFCaRySDmM4rZsEjgN8r3AgMBAAECgYAaKJp8cSfrviYRSMMzOZtECLLE
DJw+mCftf2XXaIAD6Q3OWz7SxaPEux2SIvIQdaw1dUzoFFQKbo9OE4U0U/O88WHl
qS15nnIauzZh6opiM4cEdEL620LYViHCURUPVaDZNzwOMNfL9fSRPbZ5Y0hG4QC7
b5t8k6wb8eeLzDiRcQJBAOwcxFKXYWx7XPVodiRSTuGKWkKQOnH256WFkOOLgizz
Nvo6wpL2MoWoAQNi4R5L0NC32q7j+7YrOu3NOweh+L8CQQDgrWmSo/VQsvrmdzNi
LByvqUZY1miVDOxn7R38YE3aJ8okjt3ReWzf5VUpasxjhyhsAVadBp76emI9aMXA
7sPJAkBflnXUifyjEn5by+KoaboNjRllgUZoBPFbDWvO8xfMYtqLC2biYFGr0ow2
dr10qnTrSsN5skqhQXcl9sRDHsu5AkEAzpjHFk9r2Vvq+JctiZ10d1aZWEE4A67R
h7LzOsm3bN3ftAQnFmKoaa0wxRfuf6qd0crdQSEAeOSmhz9bcFBdeQJBALP1LIgg
WOoid6rtRHsJLqchqXc90C3+t8p+BSyJ6yFEVeFBmS49+2sK1FjU8YtWGhBSRQyg
5/JeVRallyPhwM8=
-----END PRIVATE KEY-----';
$public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDPORyX/Gv16hXz5UeC4yT3jRk+
FdsHOOwXntC2JawH1LtBSyM+7G3c3/t1gq7QvYWe0g80jNxoLRu274vkrmcUD4f1
26yeXznz43VfmQH0jb4h3nBrNjnv3tlmdfMRACYT2A7Un2o7eIGVfsPu+ie0DhQm
kckg5jOK2bBI4DfK9wIDAQAB
-----END PUBLIC KEY-----';


include_once 'dm/DM/Authorize/AuthorizeAbstract.php';
include_once 'dm/DM/Authorize/Md5.php';
include_once 'dm/DM/Authorize/Rsa.php';
include_once 'dm/DM/Authorize/Mcrypt.php';
$url = "http://myzend.com/api/demo/demo-test";

$crypt_data = array(
	"name" => "jepson",
	"password" => "12312",
	"encrypt_key" => "fsdf"
);

$md5SignModel = DM_Authorize_Md5::getInstance();
$rsaModel = DM_Authorize_Rsa::getInstance();

$get_param = array(
	"sign_type" => "MD5",
	"timestamp" => time(),
	"name" => "jepson",
	"password" => "sadfas",
	//"encrypt_type" => "HTTPS",
	//"encrypt_data" => $rsaModel->encrypt(json_encode($crypt_data), $public_key),
);

$sign_md5_key = "ee7daa0c94574bee62b5f79d2b447de6";
$sign = $md5SignModel->sign($get_param, $sign_md5_key);
$get_param['sign'] = $sign;

$get_param = "?" . http_build_query($get_param);

try {
	$result = curlFunc($url . $get_param, array(), false);
	echo $result;
} catch (Exception $e) {
	echo $e->getMessage();
}

exit;
//$pwd="caizhuwjp13671142513";
//$aes=new Model_CryptAES();
//var_dump($aes->encrypt($pwd));exit;


//$string="YWtkdY8riDosyo701ExD4N2cFK/8DzhEw7qpbC/8g0BlPBjilc77JZPMPikP6Bk7wFLx3cDatwt8YWf0pSr0B3BRBQEnBQXOM4Y0ZQXpz79/ypjsQs0wT6pxK+GAFtk/SCgIC/4o0m20jF6Rs8llzX/khoBTQelYe7evllmLPOM=";
//$string="UnvC4sRbNJ8Hryg9h+4fMHSCPmvqAPXEF3767JAc470SNgSYVkr0iIg0mtGeBudogo4GmZe0BrHrBOteQU2aRxN5q+qT87IHix3twMUQnjGArZCrwiY6HwinbzVSn3vxVITP16JfyjJpeOA+pd/DYxdG1/w4FecBdoJfwvbcsG8=";
//$yee=new yeepayMPay($private_key);
//$result=$yee->encrypt_data($string);

//$data=DM_Authorize_Rsa::getInstance()->encrypt("hank",$public_key);
//$url = "http://test.caizhu.com/api/wallet/rsa-demo";
//$result = curl($url, "POST", array("data"=>$string));
//var_dump($result);
//exit;

//$result=DM_Authorize_Rsa::getInstance()->decrypt($string,$private_key);
//var_dump($result);exit;
/*------------rsa-demo-------------------------*/
//$url = "http://test.caizhu.com/api/wallet/rsa-demo";
//$param['encrypt_key'] = 'abcs';
//$data = array(
//	"data" => DM_Authorize_Rsa::getInstance()->encrypt(json_encode($param), $public_key)
//);

//$result = curl($url, "POST", $data);
//var_dump($result);
//exit;
//$result = json_decode($result, true);
//
//if ($result['flag'] > 0) {
//	$result = DM_Authorize_Mcrypt::getInstance($param['encrypt_key'])->decrypt($result['data']['data']);
//	$result = json_decode($result, true);
//}
//var_dump($result);
//exit;
/*------------rsa-demo-------------------------*/


//储蓄卡充值
$recharage_url = "http://test.caizhu.com/api/wallet-recharge/debit-card-recharge";
$param = array(
	"cardno" => '6222081202004444038',
	"idcard" => "41130219861118341X",
	"owner" => "蒋纪托",
	"phone" => '18806711513',
	"amount" => "1",
	"terminalid" => "00-EO-4C-6C-08-75"
);

//绑卡充值
//$recharage_url = "http://test.caizhu.com/api/wallet-recharge/bind-card-recharge";
//$param = array(
//	"bcid" => "6",
//	"amount" => "1",
//	"terminalid" => "00-EO-4C-6C-08-75"
//);

//回调
//$recharage_url="http://test.caizhu.com/api/wallet-recharge/pay-async-notify";
//$return = array(
//	"bindid" => 2,
//	"orderid" => "16010713410387342659-0157-0",
//	"status" => 1,
//	"yborderid" => "411305315766812955",
//	"bindvalidthru" => 1452169621,
//	"identityid" => '745',
//	"amount" => 1,
//"bank" => "ICBC",
//"lastno" => "6548"
//);

//$param['encrypt_key'] = 'abcs';
//
//$param = array(
//	'amount' => 1,
//	'cardno' => '6227002746050809184',
//	'owner' => '成鹏飞',
//	'idcard' => '420222199310035996',
//	'phone' => '17702726720',
//	'terminalid' => '6001a4193ace4c3e238dfb03f55f25336b888f26',
//	'set_passwd' => 0,
//	'encrypt_key' => 'MQANJZZSWKNZPHDFXSRWXOKRMNPLVLHP',
//);
//$data = array(
//	"data" => DM_Authorize_Rsa::getInstance()->encrypt(json_encode($param), $public_key)
//);
//
//
//$result = curl($recharage_url, "POST", $data);
//var_dump($result);
//exit;
$result = json_decode($result, true);
//var_dump($result);exit;
if ($result['flag'] > 0) {
	$aes = new Model_CryptAES($param['encrypt_key']);
	$result = $aes->decrypt($result['data']['data']);
	$result = json_decode($result, true);
}
var_dump($result);
exit;
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

function curlFunc($url, $fields, $ispost = true)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	if ($ispost) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36');

	//禁止ssl验证
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		throw new Exception(curl_error($ch), 0);
	} else {
		$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (200 !== $httpStatusCode) {
			throw new Exception("http status code exception :{$httpStatusCode}", 0);
		}
	}
	curl_close($ch);
	return $response;
}

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

	if (!is_null($header) && is_array($header)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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


