<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class DoubtController extends CommonController {
	
	// 有疑问模板配置
	protected $templete_get_html_conf = array(
		'check_fields' => array(
			array('role', '0,1', 'ROLE_IS_INVALID', 1, 'in'),
			array('status', '0,1,2,3,4', 'STATUS_IS_INVALID', 0, 'in')
		)
	);
	
	/**
	 * 获取有疑问模板
	 */
	public function templete_get_html() {
		// 获取用户类型
		$role = $this->role;
		// 用户为学生时，需要提供状态
		if ($role === '0' && !isset($_GET['status'])) $this->failReturn(C('PARAM_STATUS_NEED_TO_INPUT'));
		// 当用户为学生时
		if ($role === '0') {
			switch ($this->status) {
				case '0':
					$templete = C('STUDENT_DOUBT.0');
					break;
				case '1':
					$templete = C('STUDENT_DOUBT.1');
					break;
				case '2':
					$templete = C('STUDENT_DOUBT.2');
					break;
				case '3':
					$templete = C('STUDENT_DOUBT.3');
					break;
				case '4':
					$templete = C('STUDENT_DOUBT.4');
					break;
			}
			$this->successReturn($templete);
		}
		// 当用户为教师时
		if ($role === '1') $this->successReturn(C('TEACHER_DOUBT'));
	}
}