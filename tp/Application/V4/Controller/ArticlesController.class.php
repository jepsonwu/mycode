<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class ArticlesController extends CommonController {

	// 静态页面接口
	protected $article_get_html_conf = array(
		'check_fields' => array(
			array('name', 'foreign,gauge,money,pay,privacy,product,service,teacher,wage,index,protocol,recharge,guide',
				'ARTICLE_NAME_IS_INVALID', 1, 'in'
			)
		)
	);

	/*
	 * 静态页面接口
	 */
	public function article_get_html() {
		$this->display($this->name);
	}
}