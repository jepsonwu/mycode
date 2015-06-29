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
		defined("__PUBLIC__") || define("__PUBLIC__", __ROOT__ . "/Public");
		defined("__URL__") || define("__URL__", $_SERVER['HTTP_HOST'] . "/" . MODULE_NAME);
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

