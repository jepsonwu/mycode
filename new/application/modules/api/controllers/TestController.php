<?php

/**
 *
 * Class Api_TestController
 */
class Api_TestController extends DM_Controller_Api
{
	/**
	 * 错误码图
	 * @var array
	 */
	protected $_code_map = array(
		10001 => "地址不能为空！"
	);

	protected function getCodeMsg($code)
	{
		return isset($this->_code_map[$code]) ? $this->_code_map[$code] : "参数错误！";
	}

	public function indexAction()
	{
		parent::succReturn(array("test" => "ffsafasasdf"));
	}
}
