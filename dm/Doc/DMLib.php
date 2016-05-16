<?php
/** 
 * Controller控制器总常用操作
 * 
 * ControllerCall调用
 * 关于ControllerCall，可在之类中直接调用，如果失败直接返回用户错误，如果成功则返回给调用方法，便于应用方通过一个请求组装不同调用。
 * 不同应用方需求可能不同，哪怕注册，要求的字段可能都不一样。
 * 实例：DM_Api_Member提供了loginCall、registerCall等诸多调用方式，继承后可直接在项目底层控制器调用。
 */
//在子控制器中调用
$this->registerCall();
$this->loginCall();

// 获取当前登录的用户信息
$this->getLoginUser();

/**
 * DM_Controller_Action: session操作
 * 
 * 在控制器中获取Session，并可直接设置；Session会自动区分admin和web(包括api情况)命名空间。
 */
//将time设置为当前时间
$this->getSession()->time=time();
//获取key的session值
$this->getSession()->key;
//session也可以通过DM_Controller_Front获取
$session=DM_Controller_Front::getInstance()->getSession();


/**
 * DM_Controller_Action: csrf操作
 * 
 * csrf操作已经封装为相关的函数调用，在子类控制器中可以直接调用
 */
//生成CSRF验证码，返回生成的code
$this->createCsrfCode();
//验证CSRF验证码，code请通过CsrfCode参数get或post传递过来即可。
$this->verifyCsrfCode();


/**
 * DM_Controller_Action: 验证码操作
 * 
 * 生成图片验证码，直接在前端调用get-captcha方法，可获取return json:
 * {"flag":1,"msg":"ok","data":{"url":"\/captcha\/c6cbb91fd6d9aa6b688ca263bd5ef35a.png","width":"160","height":"50"}}
 *  
 * 前端可以通过解析json显示验证码图片
 */
//获取验证码方法，在前端通过http://duomai.dn/index/index/get-captcha获取
$this->getCaptchaAction();
//验证图片验证码，验证码请通过Captcha参数名称get或post传递过来即可。在控制器方法
$this->verifyCaptcha();


/**
 * 系统共用资源获取DM_Controller_Front
 */

//获取多语言翻译对象：
$lang=DM_Controller_Front::getInstance()->getLang($locale);
//已经自动做了翻译参数处理，可以在翻译文字中使用%s、%d等替换符号
$lang->_('lang.key', $args);
//清除多语言的缓存文件
DM_Controller_Front::getInstance()->cleanLangCache();
//获取浏览器的语言类型
DM_Controller_Front::getInstance()->getBrowserLang();

//获取Zend_Controller_Request_Http对象：
$request=DM_Controller_Front::getInstance()->getHttpRequest();
//获取ip等
$request->getClientIp();

//获取application.ini配置Zend_Config对象：
$config=DM_Controller_Front::getInstance()->getConfig();
//获取某个值
$config->setting->redis;

//获取application.ini配置Zend_Config对象：
$config=DM_Controller_Front::getInstance()->getConfig();
//获取某个值
$config->setting->redis;

//获取登录验证Auth对象
$auth=DM_Controller_Front::getInstance()->getAuth();
//获取当前登录用户信息
$auth->getLoginUser();
//执行登录操作
$auth->login($user, $password);
//判断是否登录
$auth->isLogin();

//获取缓存对象
$cache=DM_Controller_Front::getInstance()->getCache();
//设置一个值
$cache->save('test', 'hello');
//获取一个值
$cache->load('hello');

//数据库Zend_Db_Adapter_Abstract对象
// !!!!在控制器、Model、Module基类中全部已经添加快捷连接，以下快速获取
$this->getDb();
//获取默认适配器，默认获取multidb字段
$db=DM_Controller_Front::getInstance()->getDb();
//获取从数据库对象，配置在resources.slave.db.adapter
$db=DM_Controller_Front::getInstance()->getDb('slave');
//发起查询
$db->query($sql);
//开启事务
$db->beginTransaction();
//提交事务
$db->commit();







