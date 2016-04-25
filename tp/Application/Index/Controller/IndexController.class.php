<?php
namespace Index\Controller;

use Index\Controller\CommonController;

class IndexController extends CommonController
{
	public function index() {
		// 获取域名
		$domain = C('APP_SUB_DOMAIN_RULES') ? 'http://' . key(C('APP_SUB_DOMAIN_RULES')) : APP_URL.'/Teacher';
		$this->assign('domain', $domain);
		if (ismobile())
			// 显示移动端页面
			$this->display('mStu');
		else
			// 显示PC端页面
			$this->display();
	}

}