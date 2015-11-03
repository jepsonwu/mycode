<?php
return array(
	"alipay_config"=>array(
	'seller_id'=>'englishbreak@163.com',
	'partner'=>'2088701819692065',//合作身份者id，以2088开头的16位纯数字
	'self_private_key_path'=>BASE_PATH.'/ali_key/self_private_key.pem',//商户的私钥（后缀是.pen）文件相对路径
	'ali_public_key_path'=>BASE_PATH.'/ali_key/ali_public_key.pem',//支付宝公钥（后缀是.pen）文件相对路径
	'sign_type'=>strtoupper('RSA'),
	'input_charset'=>strtolower('utf-8'),
	'cacert'=>getcwd().'\\cacert.pem',//ca证书路径地址，用于curl中ssl校验
	'transport'=>'http',
	"notify_url"=>"http://dev.hapigou.com/V2/alipay/order",
	),
);
?>