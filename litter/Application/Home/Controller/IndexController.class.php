<?php
namespace Home\Controller;

use Home\Controller\CommonController;

class IndexController extends CommonController
{
	public function index()
	{
		$this->assign("title", "welcome to the payMin's blog");

		$this->display();
	}
}