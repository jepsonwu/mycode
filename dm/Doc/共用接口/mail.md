# 邮件接口说明文档 #
- 文件名：mailController

## REDIS缓存说明
- 待发送队列，键名 smtp:queue:wait，hash类型，值为json格式数组字串。

- 已发送队列，键名 smtp:queue:ok，hash类型，值为json格式数组字串。【*已废弃*】

- 已发送邮件信息，键名 smtp:queue:sent:，string类型，后跟MessageId,值为json格式数组字串。有设置缓存时间。值为CACHE_TIME。

## 配置说明
以下都为protected属性类变量。

**\_key** : 访问密钥及其对应的配置数组，格式为

    protected $_key = array(
		'访问密钥'   =>  array(
			1(1为smtp类型)=>'smtp配置，数组通用格式',
    		2(2为服务商自建类型)=>'server配置，数组通用格式',
    		'key(固定名称)'   =>  '与访问密钥相同'，
			'name(别名)'	=>	'alias'
    	),
    );

**\_smtpConfig** : 默认的smtp配置，同英文意，格式为

	array(
	    'host'  =>  '',
	    'ssl'   =>  'ssl',
	    'port'  =>  25,
	    'auth'  =>  'login',
	    'username'  =>  'postmaster@cxstars.com',
	    'password'  =>  '9hpp2ijensv9',
	    'from'  =>  'postmaster@cxstars.com',
	    'name'  =>  'admin'
    );

**\_serverConfig** : 默认的服务商配置，同英文意，格式为

    array(
	    'key'   =>  'key-7n8p91mctdtgiermj6gr49-p87ll8e94',
	    'domain'=>  'cxstars.com',
		'server'=>	'servername'
    );

##接口API##

**send** : 对外的发送邮件接口，实为添加邮件队列。
参数：

- type 发送邮件类型，0为系统发送，即sendmail类型。1为smtp发送，可自定义配置。2为服务商发送，可自定义配置。

- key 访问密钥

- config 配置字符串，json格式。[可选]

- to 收件人邮箱

- mail_title 邮件标题

- mail_content 邮件内容

- server 邮件服务商名称，现有‘mailgun’，‘sendcloud’[可选]

当config为空时，则使用_key中对应密钥的配置，若没有对应配置，则使用默认配置。

*config配置详细说明*：

	SMTP配置同通用配置
	    'host'  	=>  '',
	    'ssl'   	=>  'ssl',
	    'port'  	=>  25,
	    'auth'  	=>  'login',
	    'username'  =>  'postmaster@cxstars.com',
	    'password'  =>  '9hpp2ijensv9',
	    'from'  	=>  'postmaster@cxstars.com',
	    'name'  	=>  'admin'

	服务商因具体服务商不同而有所不同
	sendcloud
        'username'  =>  'postmaster@duomai.sendcloud.org',
        'password'  =>  'yxcXYW6w',
        'from'      =>  'admin@star.com',
        'name'      =>  'star',
        'server'    =>  'sendcloud'
	有用户名和密码及通用的server命名。

	mailgun
		'key'	=>	'key-7n8p91mctdtgiermj6gr49-p87ll8e94',
		'domain'=>	'cxstars.com',
		'from'	=>	'admin@cxstars.com'
	使用密钥和域名。


**realSend** : 非对外接口，为实际发送邮件处理方法。

**failedHook** : 非对外接口，为失败挂钩接口。主要为服务商接口服务。

- failedHookMg  mailgun服务商的失败hook接口

- failedHookSc	sendcloud服务商的失败hook接口

##测试说明

*url:* 218.75.110.137

*mail:* 218.75.110.137/mail/send

- type 发送邮件类型，0为系统发送，即sendmail类型。1为smtp发送，可自定义配置。2为服务商发送，可自定义配置。

- key 访问密钥

- config 配置字符串，json格式。[可选]

- to 收件人邮箱

- mail_title 邮件标题

- mail_content 邮件内容

- server 邮件服务商名称，现有‘mailgun’，‘sendcloud’[可选]

当config为空时，则使用_key中对应密钥的配置，若没有对应配置，则使用默认配置。


*phone*: msg.duomai.cm/phone/send

- key 访问密钥

- phone 手机号码

- message 信息内容

- name 改名，会在信息尾部添加"【name】"[可选]

返回通用json格式字串，
'flag'	=>	true/false,
'msg'	=>	内容