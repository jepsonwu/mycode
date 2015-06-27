<?php
namespace Home\Controller;

use Think\Controller;

class CommonController extends Controller
{
	/**
	 * 控制器初始化类
	 */
	protected function _initialize()
	{
		define("__PUBLIC__", __ROOT__ . "/Public");
	}

	/**
	 * 当访问不存在的方法时执行
	 */
	protected function _empty()
	{
		//https();
		//layout(false);
		$this->display("Public/404");
	}

}
