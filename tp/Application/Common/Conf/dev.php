<?php
return array(

	'LOAD_EXT_CONFIG' => 'db_dev,user,popular_tag,error_code,alipay,order_price,international_code,nationality_code,redis_dev',

	//ping++
	"PINGPP" => array(
		"APP_ID" => "app_ffLS0Gmb9C8Ozb9W",
		// test key
		"APP_KEY" => "sk_test_iXfHeLjz1evPX14afTKej5iD",
		// "APP_KEY" => "sk_live_jb940KLajD8SGmLu1OXn50aP",
		"NOTIFY_URL" => "http://dev.hapigou.com/V2/mul_pay/order",
		"SUBJECT" => "口语聊",
		"BODY" => "口语聊支付",
		"RSA_PUBLIC_KEY_PATH" => BASE_PATH . '/pingpp_key/pingpp_public_key.pem',
	),

	// 教师列表显示限制
	'TEACHER_BLACKLIST' => array(
		223, 262
	),

	// 充值配置
	'RECHARGE' => array(
		'NOTIFY_URL' => 'http://dev.kouyuliao.com:3600/V3/recharge/notify',
		"APP_ID" => "app_0400u1PiTyzDeTCi",
	),
);

?>