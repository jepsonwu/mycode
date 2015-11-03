<?php
// 时区
date_default_timezone_set('Asia/Shanghai');

mb_internal_encoding("UTF-8");

//hapigou资源下载url
define('UPLOADS_URL', 'http://' . $_SERVER["HTTP_HOST"]);

return array(
	'USER_AUTH_ON' => true,
	'USER_AUTH_TYPE' => 1, // 默认认证类型 1 登录认证 2 实时认证
	'USER_AUTH_KEY' => 'hpgAuthId', // 用户认证SESSION标记
	'ADMIN_AUTH_KEY' => 'hpgAdministrator',
	'USER_AUTH_MODEL' => 'RbacUser', // 默认验证数据表模型
	'AUTH_PWD_ENCODER' => 'md5', // 用户认证密码加密方式

	'NOT_AUTH_MODULE' => 'Index,Public', // 默认无需认证模块
	'GUEST_AUTH_ON' => false, // 是否开启游客授权访问
	'GUEST_AUTH_ID' => '', // 游客的用户ID

	'LOAD_EXT_CONFIG' => 'db,user,popular_tag,error_code,alipay,order_price,international_code,nationality_code',

	'TMPL_STRIP_SPACE' => true, // 是否去除模板文件里面的html空格与换行

	'MODULE_ALLOW_LIST' => array( // 允许访问模块列表
		'Admin',
		'Api',
		'Ft',
		'V2',
		'Tools',
		'Index',
		'Teacher',
		'Crontab'
	),
	'DEFAULT_MODULE' => 'Index', // 默认模块

	'URL_MODEL' => '2', // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE 模式);
	// 3 (兼容模式)默认为PATHINFO 模式，提供最好的用户体验和SEO支持
	'URL_HTML_SUFFIX' => '', // URL伪静态后缀设置

// 			'DEFAULT_FILTER' => 'htmlspecialchars,trim,remove_xss', // I函数默认过滤
	'DEFAULT_FILTER' => 'htmlspecialchars,trim', // I函数默认过滤

	'SHOW_ERROR_MSG' => true, // 显示错误信息
	'LOG_RECORD' => true, // 开启日志记录
	'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误

	'URL_CASE_INSENSITIVE' => false, // url不区分大小写
// 			'URL_ROUTER_ON' => true, // 开启路由
// 			'URL_ROUTE_RULES' => array (), // 路由规则

	'VAR_PAGE' => 'p', // 页码

	'COOKIE_PREFIX' => 'hpg_',  // cookie前缀

	'POPULAR_TAG_PATH' => CONF_PATH . 'popular_tag.php',
	// 错误页面
// 			'ERROR_PAGE' => __ROOT__.'/Home/Index/error',

	'REGISTER_STATUS' => 1, // 1 (开启注册); 0 (关闭注册)

	'PAGE_LIST_NUM' => 10,//list of one page

	//学生、老师通知模板
	"MESSAGE_TEMPLATE" => array(),
	//APP_ID todo  应用方ID
	"APP_ID" => array(
		"20150001" => array(
			"MD5_SECRET_KEY" => "8a476f61b64f34ec0b4f7f4c5aef0d1b",
			"COOLCHAT_CRYPT_KEY" => "YmEwYTZkZGQNCmQ1NTY2OTgyDQphMTgxYTYwMw0K",
			"VENDOR_PUBLIC_KEY_PATH" => BASE_PATH . "/auth_key/20150001/vendor_public_key.pem",
		),
	),
	//口语聊公钥和私钥
	"SELF_PRIVATE_KEY_PATH" => BASE_PATH . "/auth_key/self_private_key.pem",
	"SELF_PUBLIC_KEY_PATH" => BASE_PATH . "/auth_key/self_public_key.pem",
	//API timestamp,单位妙
	"API_TIMESTAMP" => 900,
	//订单状态
	"ORDERS_STATUS" => array(
		"NEW" => 1,//待结算
		"PAY" => 2,//待付款
		"COMMENT" => 3,//待评论
		"DONE" => 4,//已完结
		"CLOSE" => 0,//已关闭
	),
	// 敏感词汇路径
	'SENSITIVE_WORDS_PATH' => BASE_PATH . '/Public/Common/txt/sensitive_words.txt',
	//通话状态
	"CALL_STATUS" => array(
		"CLOSE" => -1,
		"ST_HANG_UP" => 0,
		"TE_HANG_UP" => 1,
		"CALLING" => 2,
		"DONE" => 3
	),
	//ping++
	"PINGPP" => array(
		"APP_ID" => "app_ffLS0Gmb9C8Ozb9W",
		//sk_test_iXfHeLjz1evPX14afTKej5iD   test key
		"APP_KEY" => "sk_live_jb940KLajD8SGmLu1OXn50aP",
		"NOTIFY_URL" => "http://" . (APP_DEBUG ? "dev.hapigou.com" : "www.kouyuliao.com") . "/V2/mul_pay/order",
		"SUBJECT" => "口语聊",
		"BODY" => "口语聊支付",
		"RSA_PUBLIC_KEY_PATH" => BASE_PATH . '/pingpp_key/pingpp_public_key.pem',
	),
	// 密钥
	"COOLCHAT_CRYPT_KEY" => "YmEwYTZkZGQNCmQ1NTY2OTgyDQphMTgxYTYwMw0K",
	//上传文件大小限制
	"UPLOAD_FILE_MAX_SIZE" => 8 * 1024 * 1024,
	//上传图片类型配置
	"UPLOAD_PIC_TYPE" => array(
		//头像  max_len，prefix，写入规则
		"AVATAR" => array(2, "png", "md5"),
		//申请外交
		"TEACHERS" => array(2, "png", "md5"),
		//客户端上传日志
		"WAP_LOGS" => array(8, "zip", "md5"),
	),
	"HTTP_DOMAIN" => 'http://' . $_SERVER["HTTP_HOST"],
	//优惠券递增间隔
	"COUPONS_INTERVAL" => '3',

	'LOG_RECORD' => true, // 开启日志记录
	'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误
);

?>