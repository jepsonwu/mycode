<?php
namespace Home\Controller;

use Home\Controller\CommonController;

class LoginController extends CommonController
{
	public function Index()
	{
		$this->display("Public/Login");
	}
}
