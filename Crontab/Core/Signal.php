<?php
namespace Core;

/**
 * 信号处理
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-3
 * Time: 上午10:46
 */
class Signal
{
	public static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 注册信号处理函数
	 */
	public function registerServerSignal()
	{
		//必须使用ticks进程信号回调机制 听说这种机制很复杂
		declare(ticks = 1);

		//终止程序信号
		pcntl_signal(SIGHUP, array($this, "serverSignalHandler"));
		pcntl_signal(SIGINT, array($this, "serverSignalHandler"));
		pcntl_signal(SIGQUIT, array($this, "serverSignalHandler"));
		pcntl_signal(SIGTSTP, array($this, "serverSignalHandler"));

		//重新执行信号
		pcntl_signal(SIGCONT, array($this, "serverSignalHandler"));

		//子进程信号事件
		pcntl_signal(SIGCHLD, array($this, "serverSignalHandler"));

		//用户自定义信号

		//终端读取输入信号
		pcntl_signal(SIGTTIN, array($this, "serverSignalHandler"));
	}

	/**
	 * 信号处理函数
	 * @param $signal
	 * //所有注册的回调类函数必须声明为公共的
	 */
	public function serverSignalHandler($signal)
	{
		switch ($signal) {
			//用户终端正常或非正常结束时发出 退出登陆
			case SIGHUP:
				//程序终止信号 Ctrl-C INTR字符
			case SIGINT:
				//程序终止信号 Ctrl-\ QUIT字符
			case SIGQUIT:
				//程序终止信号  Ctrl-Z SUSP字符
			case SIGTSTP:
				//释放内存
				Core::getInstance()->freeMemory();
				//todo 关闭闹钟
				posix_kill(posix_getpid(), SIGTERM);
				break;
			//程序终止信号 可以处理和阻塞  SIGKILL 不可以被处理和阻塞
//			case SIGTERM:
//				break;
			//留给用户使用的信号
			case SIGUSR1:
				break;
			case SIGUSR2:
				break;
			//时钟定时信号
			case SIGALRM:
				break;
			//子进程结束信号  避免僵尸进程
			case SIGCHLD:
				Core::getInstance()->childFinished();
				break;
			//当重终端读取数据时 todo
			case SIGTTIN:
				break;

			//子进程信号
			//停止进程 不能处理
//			case SIGSTOP:
//				break;
			//开始一个停止的进程
			case SIGCONT:
				break;
		}
	}
}