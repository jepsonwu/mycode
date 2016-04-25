<?php
namespace Tools\Controller;

use Tools\Controller\CommonController;

class IndexController extends CommonController
{
	public function index() {
		// 获取接口信息
		$interface_info = M('InterfaceInfo')->where('status=1')->order('category')->select();
		$this->assign('interface_info', $interface_info);
		$this->display();
	}
}