<?php
namespace Teacher\Controller;


class IndexController extends CommonController
{
	private $domain = '';

	public function __construct()
	{	
		parent::__construct();
		$this->domain = C('APP_SUB_DOMAIN_RULES') ? 'http://' . key(C('APP_SUB_DOMAIN_RULES')) : APP_URL.'/Teacher';
	}

	public function index() {
		// 设置域名
		$this->assign('domain', $this->domain);
		if (ismobile())
			// 显示移动端页面
			$this->display('mTea');
		else
			// 显示PC端页面
			$this->display();
	}

	public function about() {
		// 设置域名
		$this->assign('domain', $this->domain);
		if (ismobile())
			// 显示移动端页面
			$this->display('mabout');
		else
			// 显示PC端页面
			$this->display();
	}
}