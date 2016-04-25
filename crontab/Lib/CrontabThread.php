<?php

/**
 * 多线程实现类
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-9
 * Time: 下午5:05
 */
class CrontabThread extends Thread
{
	//当前线程ID
	private $thread_id = 0;
	//处理完的数据
	public $data = "";

	public function __construct($thread_id)
	{
		$this->thread_id = $thread_id;
	}

	public function run()
	{

	}
}