<?php
// 时区
date_default_timezone_set('Asia/Shanghai');

mb_internal_encoding("UTF-8");

//hapigou资源下载url
define('UPLOADS_URL', 'http://' . $_SERVER["HTTP_HOST"]);

return array(
	'DATA_CACHE_TYPE' => 'Redis',
	'ONEDAY' => 86400,
	'USER_AUTH_ON' => true,
	'USER_AUTH_TYPE' => 1, // 默认认证类型 1 登录认证 2 实时认证
	'USER_AUTH_KEY' => 'hpgAuthId', // 用户认证SESSION标记
	'ADMIN_AUTH_KEY' => 'hpgAdministrator',
	'USER_AUTH_MODEL' => 'RbacUser', // 默认验证数据表模型
	'AUTH_PWD_ENCODER' => 'md5', // 用户认证密码加密方式

	'NOT_AUTH_MODULE' => 'Index,Public', // 默认无需认证模块
	'GUEST_AUTH_ON' => false, // 是否开启游客授权访问
	'GUEST_AUTH_ID' => '', // 游客的用户ID
	'TMPL_STRIP_SPACE' => true, // 是否去除模板文件里面的html空格与换行

	'MODULE_ALLOW_LIST' => array( // 允许访问模块列表
		'Admin',
		'V2',
		'V3',
		'V4',
		'Index',
		'Teacher',
		'Crontab',
		'Error'
	),
	'DEFAULT_MODULE' => 'Index', // 默认模块

	'URL_MODEL' => '2', // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE 模式);
	// 3 (兼容模式)默认为PATHINFO 模式，提供最好的用户体验和SEO支持
	'URL_HTML_SUFFIX' => '', // URL伪静态后缀设置

	// 'DEFAULT_FILTER' => 'htmlspecialchars,trim,remove_xss', // I函数默认过滤
	'DEFAULT_FILTER' => 'htmlspecialchars,trim', // I函数默认过滤

	'LOG_RECORD' => true, // 开启日志记录

	'URL_CASE_INSENSITIVE' => false, // url不区分大小写

	'VAR_PAGE' => 'p', // 页码

	'COOKIE_PREFIX' => 'hpg_',  // cookie前缀

	'POPULAR_TAG_PATH' => CONF_PATH . 'popular_tag.php',
	// 错误页面
	'ERROR_PAGE' => __ROOT__.'/error',

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
	// 加密方式
	"COOLCHAT_CRYPT_TYPE" => "AES", // 目前支持AES，DES两种加密方式
	// 密钥
	"COOLCHAT_DES_KEY" => "YmEwYTZkZGQNCmQ1NTY2OTgyDQphMTgxYTYwMw0K",
	"COOLCHAT_AES_KEY" => "NRTz2wvKvlXJEuDXfz5ydYfLiK1snSGOV5hvZ0aprBE=",
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

	'UPLOAD_SITE_QINIU' => array (
		'maxSize' => 64 * 1024 * 1024,//文件大小
		'rootPath' => './',
		'saveName' => array ('uniqid', ''),
		'driver' => 'Qiniu',
		'driverConfig' => array (
			'secrectKey' => 'gGk7Df8L92mY_r4evyOXjo9jAEhMI7Igrn-AerSc',
			'accessKey' => 'Bl9oeQfJyIIivU7q24veVhydurPTib1lcHneu68K',
			'domain' => 'qn-kouyubang-admin.abc360.com',
			'bucket' => 'abc360-kouyubang-admin',
		)
	),

	"DEV_TCP" => 'tcp://119.254.108.209:9292',
	"RELEASE_TCP" => 'tcp://119.254.108.209:8282',
	"MASTER_TCP" => 'tcp://123.57.12.68:8282',

	/**云信配置信息开始**/
	//测试环境
	"DEV_NIM_APPKEY" => '48ab833596bdcee75251fbd0e6c10edc',
	"DEV_NIM_APPSECRET" => 'e1992d0ab3b3',
	//正式环境
	"MASTER_NIM_APPKEY" => 'e1a3207b9c79e31b7ed6c20e1e91d663',
	"MASTER_NIM_APPSECRET" => '2867e122f7e9',
	// 更新云信用户资料URL
	'UPDATE_NIM_USER_INFO_URL' => 'https://api.netease.im/nimserver/user/updateUinfo.action',
	/**云信配置信息结束**/
);

?>