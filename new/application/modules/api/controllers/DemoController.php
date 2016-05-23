<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-23
 * Time: 上午10:02
 */
class Api_DemoController extends DM_Controller_Api
{
	protected $code_map = array(
		100001 => "名称错误！",
		100002 => "密码错误！",
	);

	public function getCodeMsg($code)
	{
		return isset($this->code_map[$code]) ? $this->code_map[$code] : "";
	}

	protected $demoConf = array();

	public function demoAction()
	{
		parent::succReturn(array("demo" => "fdasf"));
	}

	protected $demoTestConf = array(
		"method" => "get",
		"authorize" => true,
		"limit_request" => "",
		"check_user" => false,//todo
		"check_param" => array(
			array("name", "require", 100001, DM_Helper_Filter::MUST_VALIDATE),
			array("password", "require", 100002, DM_Helper_Filter::EXISTS_VALIDATE, null, "fdafas"),
		),
	);

	public function demoTestAction()
	{
		parent::succReturn($this->_param);
	}
}