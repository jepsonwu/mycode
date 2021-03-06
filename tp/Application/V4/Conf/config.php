<?php

return array(
	'verCode'=>'3.0',
	'verInfo'=>'Version 3.0',

	// 开启路由
    'URL_ROUTER_ON' => true,
	'URL_ROUTE_RULES'=>array(
		'exerciseDetail/:id\d$'=>array('exerciseDetail/read',array('ext'=>'html','method'=>'get')),
		'exerciseDetail/series_id/:series_id'=>array('exerciseDetail/list',array('ext'=>'html','method'=>'get')),
		'demos/:id\d$'=>array('demos/read',array('ext'=>'html','method'=>'get')),
		'demos/series_id/:series_id'=>array('demos/list',array('ext'=>'html','method'=>'get')),
		'series/category$' => array('series/category', array('ext' => 'html', 'method' => 'get')),
		'series/category/:category_id' => array('series/list', array('ext' => 'html', 'method' => 'get')),
		'series/:id\d$' => array('series/read', array('ext' => 'html', 'method' => 'get')),
		'comment/series/:series_id' => array('comment/list', array('ext' => 'html', 'method' => 'get')),
		'comment/series' => array('comment/series', array('ext' => 'html', 'method' => 'post')),
		'comment/teacher/:teacher_id' => array('comment/teacher', array('ext' => 'html', 'method' => 'get')),
		'comment/teacher' => array('comment/teacher', array('ext' => 'html', 'method' => 'post')),
		'comment/apply' => array('comment/apply', array('ext' => 'html', 'method' => 'post')),
		'register/status' => array('register/read', array('ext' => 'html', 'method' => 'get')),
		'collection/series' => array('collection/series', array('ext' => 'html', 'method' => 'post')),
		'collection/series' => array('collection/series', array('ext' => 'html', 'method' => 'delete')),
		'collection/series' => array('collection/series', array('ext' => 'html', 'method' => 'get')),
		'collection/knowledge' => array('collection/knowledge', array('ext' => 'html', 'method' => 'post')),
		'collection/knowledge' => array('collection/knowledge', array('ext' => 'html', 'method' => 'delete')),
		'collection/knowledge' => array('collection/knowledge', array('ext' => 'html', 'method' => 'get')),
		'collection/teacher' => array('collection/teacher', array('ext' => 'html', 'method' => 'post')),
		'collection/teacher' => array('collection/teacher', array('ext' => 'html', 'method' => 'delete')),
		'collection/teacher' => array('collection/teacher', array('ext' => 'html', 'method' => 'get')),
		'collection/fans' => array('collection/fans', array('ext' => 'html', 'method' => 'get')),
		'users/register' => array('users/register', array('ext' => 'html', 'method' => 'post')),
		'users/login' => array('users/login', array('ext' => 'html', 'method' => 'post')),
		'users/user'=>array('users/user',array('ext'=>'html','method'=>'get')),
		'users/user_other'=>array('users/user_others',array('ext'=>'html','method'=>'get')),
		'users/auth_demo'=>array('users/auth_demo',array('ext'=>'html','method'=>'get')),
		'users/user'=>array('users/user',array('ext'=>'html','method'=>'put')),
		'users/passwd'=>array('users/user_passwd',array('ext'=>'html','method'=>'put')),
		'users/forget_password'=>array('users/forget_password',array('ext'=>'html','method'=>'put')),
		'users/change_password'=>array('users/change_password',array('ext'=>'html','method'=>'put')),
		'users/user_payment'=>array('users/user_payment',array('ext'=>'html','method'=>'get')),
		'users/user_payment'=>array('users/user_payment',array('ext'=>'html','method'=>'put')),
		'users/balance'=>array('users/balance',array('ext'=>'html','method'=>'get')),
		'users/fund_flow'=>array('users/fund_flow',array('ext'=>'html','method'=>'get')),
		"teachers/apply"=>array("teachers/apply",array("ext"=>'html','method'=>'post')),
		"teachers/apply"=>array("teachers/apply",array("ext"=>'html','method'=>'get')),
		"teachers$"=>array("teachers/list",array("ext"=>'html','method'=>'get')),
		'messages$'=>array('messages/read',array('ext'=>'html','method'=>'get')),
		'orders$'=>array('orders/list',array('ext'=>'html','method'=>'get')),
		'orders/:order_id\d$'=>array('orders/read',array('ext'=>'html','method'=>'get')),
		'orders/settlement'=>array('orders/settlement',array('ext'=>'html','method'=>'post')),
		'alipay/:order_id\d$'=>array('alipay/order',array('ext'=>'html','method'=>'get')),
		'alipay/order'=>array('alipay/order',array('ext'=>'html','method'=>'post')),
		'mul_pay/:order_id\d$'=>array('mulPay/order',array('ext'=>'html','method'=>'get')),
		'mul_pay/notify'=>array('mulPay/notify',array('ext'=>'html','method'=>'post')),
		'mul_pay/sync_notify'=>array('mulPay/sync_notify',array('ext'=>'html','method'=>'put')),
		'upload/pic'=>array('upload/pic',array('ext'=>'html','method'=>'post')),
		'upload/wap_logs'=>array('upload/wap_logs',array('ext'=>'html','method'=>'post')),
		'upload/recording'=>array('upload/recording',array('ext'=>'html','method'=>'post')),
		'wages$'=>array('wages/list', array('ext'=>'html','method'=>'get')),
		'wages/:wages_id\d$'=>array('wages/read', array('ext'=>'html','method'=>'get')),
		'wages/current'=>array('wages/current', array('ext'=>'html','method'=>'get')),
		'calls$'=>array('calls/list', array('ext'=>'html','method'=>'get')),
		'calls/:call_id\d$'=>array('calls/read', array('ext'=>'html','method'=>'get')),
		'calls/teacher'=>array('calls/teacher', array('ext'=>'html','method'=>'get')),
		'calls/adverse'=>array('calls/adverse', array('ext'=>'html','method'=>'get')),
		'calls/missed'=>array('calls/missed', array('ext'=>'html','method'=>'get')),
		'doubt/templete'=>array('doubt/templete', array('ext'=>'html','method'=>'get')),
		'complain$'=>array('complain/complain', array('ext'=>'html','method'=>'post')),
		'feedback$'=>array('feedback/feedback', array('ext'=>'html','method'=>'post')),
		'verify_code/:mobile\d$'=>array('verifyCode/verify_code', array('ext'=>'html','method'=>'get')),
		'config/teaching'=>array('config/teaching', array('ext'=>'html','method'=>'get')),
		'articles/:name$'=>array('articles/article', array('ext'=>'html','method'=>'get')),
		'coupons/exchange'=>array('coupons/exchange', array('ext'=>'html','method'=>'post')),
		'coupons/:user_id\d$'=>array('coupons/list', array('ext'=>'html','method'=>'get')),
		'recharge/list'=>array('recharge/recharge_list', array('ext'=>'html','method'=>'get')),
		'recharge/pay'=>array('recharge/pay', array('ext'=>'html','method'=>'get')),
		'recharge/notify'=>array('recharge/notify', array('ext'=>'html','method'=>'post')),
		'recharge$'=>array('recharge/list', array('ext'=>'html','method'=>'get')),
		'recharge$'=>array('recharge/recharge', array('ext'=>'html','method'=>'post')),
		'recharge/sync_notify'=>array('recharge/sync_notify', array('ext'=>'html','method'=>'post')),
		'share/link'=>array('share/link', array('ext'=>'html','method'=>'get')),
		'share$'=>array('share/page', array('ext'=>'html','method'=>'get')),
		'share/record'=>array('share/record', array('ext'=>'html','method'=>'post')),
	)
);