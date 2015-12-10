start.php
	php start.php start|restart|stop|reload|status|conf [-d] 开启服务
	php start.php -v|-V|-h
	php start.php pid [stop|restart|status] 子进程事件

CrontabServer.php
	crontab 服务
	1.事件监听，包括服务和进程事件监听
    2.任务多进程
    3.进程间通信(system v:shmop 共享内存段<大小和权限限制>,queue 队列,sem 信号量)
    4.日志记录
    5.用户权限

任务列表刷新机制
	1.服务开启或者重启
	2.服务运行中热刷新任务列表
	3.凌晨自动刷新
	4.事件出发

IPC进程通信
	1.shmop
	2.queue
	3.meg

Task任务模块
	开发者须知
	可以仿照core.php编码

Event事件
	1.包含服务事件和任务事件
	2.可以写文件，也可以终端命令行执行

TaskList任务列表配置
	1.遵循linux crontab 规则即可

LOG日志
	1.调式模式
	2.logfile模式
