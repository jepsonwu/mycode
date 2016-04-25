<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class ConfigController extends CommonController {
	
	/**
	 * 获取教学相关信息
	 */
	public function teaching_get_html() {
		// 获取教学语言
		$teaching_language = C('TEACHING_LANGUAGE');
		// 获取教学分类
		$teaching_category = C('TEACHING_CATEGORY');
		// 返回结果
		$result['teaching_language'] = $teaching_language;
		$result['teaching_category'] = $teaching_category;
		$this->successReturn($result);
	}
}