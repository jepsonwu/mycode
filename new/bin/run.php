<?php
/**
 * Bin功能使用指南
 * 
 * 跟前台保持相同的路由路径即可路由到相应控制器的方法
 * 进入bin目录后操作，脚本采用cd /dir/bin/ && php run.php route
 * 
 * 传参数 name=hello age=15，会写入到$_GET中，通过zend获取参数方法可以获取
 * 
 * win下不用加前/
 * eg: php run.php api/test name=hello age=15
 */

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
if(APPLICATION_ENV == 'staging'){
    $pathItem = realpath(APPLICATION_PATH . '/../../caizhu.dm'); 
}elseif(APPLICATION_ENV == 'production'){
	 $pathItem = realpath(APPLICATION_PATH . '/../../../caizhu.production.dm');
}else{
    $pathItem = realpath(APPLICATION_PATH . '/../../caizhu.dm');
}

set_include_path(implode(PATH_SEPARATOR, array(
    $pathItem,
    realpath(APPLICATION_PATH .'/../library'),
    get_include_path(),
)));

//处理参数
if (!isset($_SERVER['argv'][1])) $_SERVER['argv'][1]='';
$requestUrl=trim($_SERVER['argv'][1]);
if (substr($requestUrl, 0, 1)!=='/') $requestUrl='/'.$requestUrl;
$_SERVER['REQUEST_URI']=$requestUrl;

//传参数
$varCount=count($_SERVER['argv']);
for ($iter=2; $iter<$varCount; $iter++){
    if (empty($_SERVER['argv'][$iter]) || strripos($_SERVER['argv'][$iter], '=')===false) continue;
    list($key, $value)=explode('=', $_SERVER['argv'][$iter]);
    $_GET[$key]=$value;
}

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

ini_set('display_errors', 1);
error_reporting(E_ALL);
$application->bootstrap()
            ->run();