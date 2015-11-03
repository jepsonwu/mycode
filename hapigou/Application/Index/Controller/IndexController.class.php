<?php
namespace Index\Controller;

use Index\Controller\CommonController;

class IndexController extends CommonController
{
	public function index() {
		if (ismobile())
			// 显示移动端页面
			$this->display('mStu');
		else
			// 显示PC端页面
			$this->display();
	}

}