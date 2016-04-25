<?php
namespace Error\Controller;


class IndexController extends CommonController
{
	public function index() {
		$this->display('404');
	}
}