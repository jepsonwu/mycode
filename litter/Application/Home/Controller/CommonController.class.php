<?php
namespace Home\Controller;

use Think\Controller;

class CommonController extends Controller
{
	/**
	 * 控制器初始化类
	 */
	protected function _initialize()
	{
		//$this->Check_login();

		//权限检查  节点|操作
	}

	/**
	 * 当访问不存在的方法时执行
	 */
	protected function _empty()
	{
		send_http_status(404);
		//layout(false);
		parent::display("Public/404");
	}

	protected function Check_login()
	{
		!isset($_SESSION['AUTH_USER_KEY']) && redirect("/Home/Public/Login");
	}


	/**
	 * 普通ajax返回
	 * @param $data
	 */
	protected function ajax_return($data)
	{
		parent::ajaxReturn(array(
			"data" => $data,
			"status" => true
		));
	}

	/**
	 * succ 返回
	 * @param $info
	 */
	protected function ajax_succ($info)
	{
		parent::ajaxReturn(array(
			"status" => false,
			"info" => $info,
			"url" => "",
			"param" => ""
		));
	}

	/**
	 * error 返回
	 * @param $info
	 */
	protected function ajax_error($info)
	{
		parent::ajaxReturn(array(
			"status" => false,
			"info" => $info
		));
	}
}

