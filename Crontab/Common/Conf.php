<?php
/**
 * 公共配置文件
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:53
 */
return array(
	//默认IPC通信机制
	"DEFAULT_IPC_TYPE" => "shmop",
	//需要内存G
	"MEMORY_AVALIABLE" => 1,
	//需要共享内存
	"SHMMAX" => 1,
	//多进程数量  如果为空默认为当前逻辑cpu数量 算超线程
	"MULTI_PROCESS" => 5,

	//php路径
	"PHP_EXEC" => "/usr/bin/php",

	//日志文件
	"LOG_FILE" => "log.txt",
);