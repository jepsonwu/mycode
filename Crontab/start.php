<?php
/**
 * 开启服务
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:04
 */

//判断扩展
foreach (array("pcntl", "posix", "shmop") as $exten) {
	!extension_loaded($exten) && exit("{$exten} extension is not loaded");
}

//定义基本常量
!defined("BASE_PATH") && define("BASE_PATH", dirname(__FILE__) . "/");

//autoload
require_once "./Autoloader.php";

//crontaServer
require_once "./CrontabServer.php";
CrontabServer::Run();