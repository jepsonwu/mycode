<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
	public function index()
	{

		$this->assign("title","welcome to the payMin's blog");

		$this->display();
	}

	/**
	 * 当访问不存在的方法时执行
	 */
	protected function _empty()
	{
//		header("http://1.1 404");
	}

	/**
	 * 控制器初始化类，由controller执行
	 */
	protected function _initialize()
	{
	}
}