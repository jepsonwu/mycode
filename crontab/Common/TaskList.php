<?php
/**
 * 任务列表
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-26
 * Time: 下午2:54
 */
return array(
	//任务格式
	//[进程数|线程数|*(不填)](进程p表示,线程t表示,如果大于配置最大值,均以最大值为准) [用户组] [用户]
	//[执行命令|sp(框架PHP)|ss(框架shell)] [时间格式] [执行程序] [参数](参数用/分割键值)

	//例如
	"p3 www www /usr/bin/php */1 * * * * /data/mycode/task1.php id/1",
	//t3 www www /usr/bin/php */1 * * * * /data/mycode/task1.php id/1
	"fsfsdf sdafsadf dsfsd",
	"1111 dsf p4",
	//第三方任务
//	"p3 www www /usr/bin/php */1 * * * * /data/mycode/task1.php id/1",

	//框架
	"* www www sp */1 * * * * Demo/Demo/demo name/test",
	"* www www ss */1 * * * * Shell/Demo",

	//Crontab 时间格式示例 遵循Linux Crontab规则
	//"*/1 * * * * Demo/Demo/demo id/1/name/test",
	//"1,5 * * * * /Demo/Demo/ id/1/name/test",
	//"1-5 * * * 1 /Demo/Demo/ id/1/name/test",
	//"*/5 * * May * /Demo/Demo/ id/1/name/test",
	//"*/2 1-5 1-10 * * /Demo/Demo/ id/1/name/test"
);
