<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class RegisterController extends CommonController {
	
	/**
	 * 注册状态接口
	 */
	public function read_get_html() {
		// 获取注册状态
		$register_status['status'] = C('REGISTER_STATUS');
		$this->successReturn($register_status);
	}
}