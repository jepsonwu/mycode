Crontab service overview
	Crontab服务采用多进程模式，支持主进程和任务事件
	任务支持多进程和多线程，类型包含框架和第三方

start.php
	php start.php start|restart|stop|reload|status|conf [-d] 开启服务
	php start.php -v|-V|-h
	php start.php pid [stop|restart|status] 子进程事件

例：
/usr/bin/php start.php start [-d]

任务列表刷新机制
	1.服务开启或者重启
	2.服务运行中定时刷新任务列表
	3.reload

Task任务模块
	可以仿照Application/Demo/Demo.php编码

Event事件
	1.包含服务事件和任务事件
	2.可以写文件，也可以终端命令行执行

TaskList任务列表配置
	1.遵循linux crontab 规则即可
	2.附加规则见详情
