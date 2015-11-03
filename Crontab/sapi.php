<?php
/**
 * 单任务执行模式
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-28
 * Time: 上午10:10
 */
require_once "./CrontabServer.php";
$server = new CrontabServer();
$server->Sapi();
