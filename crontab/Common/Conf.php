<?php
/**
 * 公共配置文件
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:53
 */
return array(
	//任务列表配置文件
	"TASK_LIST" => "TaskList",

	//需要内存 单位G 默认2
	"MEMORY_AVAILABLE" => 2,

	//多进程数量  如果为空默认为当前逻辑cpu数量 算超线程
	"MULTI_PROCESS" => 5,
	"MULTI_THREAD" => 4,

	//sysvmsg 队列最大存储字节数
	"SYSVMSG_MAX_SIZE" => 104857600,

	//单条存储字节数
	"SYSVMSG_SINGLE_MAX_SIZE" => 10000,
	"SYSVMSG_MODE" => 0664,

	//syshm
	"SYSHM_MODE" => 0666,

	"APPLICATION" => 'Application',
);