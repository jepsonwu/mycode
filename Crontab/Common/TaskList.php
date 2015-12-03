<?php
/**
 * 任务列表
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:54
 */
return array(
	//第三方任务
	//"/usr/bin/php */1 * * * * /data/mycode/task1.php",
	//"/usr/bash 5 * * * * Demo",
	//todo commond multi_process multi_threads user date argv
	//php任务  框架本身需要提供一些基础服务
	//s(框架本身的任务) 时间格式 commond argv
	"s */1 * * * * Demo/Demo/demo id/1/name/test",
	//"s 1,5 * * * * /Demo/Demo/ id/1/name/test",
	//"s 1-5 * * * 1 /Demo/Demo/ id/1/name/test",
//	"s */5 * * May * /Demo/Demo/ id/1/name/test",
//"s */2 1-5 1-10 * * /Demo/Demo/ id/1/name/test"
);
