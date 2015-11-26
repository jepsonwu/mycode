<?php
/**
 * 公共配置文件
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:53
 */
return array(
	//需要内存G
	"MEMORY_AVALIABLE" => 1,
	//需要共享内存
	"SHMMAX" => 1,
	//多进程数量  如果为空默认为当前逻辑cpu数量 算超线程
	"MULTI_PROCESS" => 5,

	//sysvmsg 队列最大存储字节数
	"SYSVMSG_MAX_SIZE" => 104857600,
	//单条存储字节数
	"SYSVMSG_SINGLE_MAX_SIZE" => 10000,
	"SYSVMSG_MODE" => 0664,

	//syshm
	"SYSHM_MODE" => 0666,

	//php路径
	"PHP_EXEC" => "/usr/bin/php",

	//日志文件
	"LOG_FILE" => "log.txt",

	//默认获取多少时长的可执行任务列表 单位分 程序会优化
	"TASK_EXEC_TOTAL_TIME" => 60,
);