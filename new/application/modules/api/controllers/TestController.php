<?php

/**
 * todo 接口文档自动解析
 * Class Api_TestController
 */
class Api_TestController extends DM_Controller_Api
{
	/**
	 * 错误码图
	 * @var array
	 */
	protected $_code_map = array(
		100001 => "地址不能为空！"
	);

	protected function getCodeMsg($code)
	{
		return isset($this->_code_map[$code]) ? $this->_code_map[$code] : "参数错误！";
	}


	protected $indexConf = array(
		"method" => "get",
		"authorize" => true,
		"check_param" => array(
			array("name", "require", 100001, DM_Helper_Filter::MUST_VALIDATE),
		),
	);

	public function indexAction()
	{
		parent::succReturn(json_encode($this->_param));
	}
}
