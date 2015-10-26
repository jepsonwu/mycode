<?php
/**
 * 开启服务
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:04
 */
require_once "./CrontabServer.php";
$server = new CrontabServer();
$server->Start();