<?php
namespace Home\Controller;

use Home\Controller\CommonController;

class IndexController extends CommonController
{
	public function Index()
	{
		$this->assign("title", "welcome to the payMin's blog");
		$this->display();
	}

	public function delete(){
		parent::ajax_succ("删除失败");
	}
}