<?php
namespace V2\Controller;

use Think\Controller;

class ImportController extends Controller {
	
	/**
	 * 将students表中数据导入users表与user_detail表
	 */
	public function index() {
		$map = array();
		// 实例化students模型
		$students_model = M('Students');
		$map['mobile'] = array('neq', 'null');
		$map['password'] = array('neq', 'null');
		$students_info = $students_model->where($map)->select();
		echo "共有" . count($students_info) . "条数据" . "<br>";
		// 查询结果集不为空
		if (!empty($students_info)) {
			// 实例化users与user_detail模型
			$users_model = M('Users');
			$user_detail_model = M('UserDetail');
			$fail_arr = array();
			$count = 0;
			foreach ($students_info as $student) {
				$users_info['mobile'] = $student['mobile'];
				$users_info['password'] = $student['password'];
				$users_info['name'] = $student['nickname'];
				$users_info['gender'] = $student['gender'];
				$users_info['avatar'] = $student['avatar'];
				$users_info['location'] = $student['location'];
				$users_info['status'] = $student['status'];
				$users_info['token'] = $student['token'];
				$users_info['create_time'] = $student['create_time'];
				// 开启users表事务
				$users_model->startTrans();
				$users_result = $users_model->add($users_info);
				// users表插入成功
				if ($users_result) {
					$user_detail_info['user_id'] = $users_result;
					$user_detail_info['birth'] = $student['birth'];
					$user_detail_info['job'] = $student['job'];
					$user_detail_info['mail'] = $student['mail'];
					$user_detail_info['is_push'] = $student['isPush'];
					$user_detail_info['is_sync'] = $student['isSync'];
					$user_detail_info['voip_account'] = $student['voipAccount'];
					$user_detail_info['voip_password'] = $student['voipPassword'];
					$user_detail_info['sub_account_sid'] = $student['subAccountSid'];
					$user_detail_info['sub_token'] = $student['subToken'];
					$user_detail_result = $user_detail_model->add($user_detail_info);
					if ($user_detail_result) {
						$users_model->commit();
						$count ++;
					} else {
						$users_model->rollback();
						$fail_arr[] = $users_result;
					}
				} else {
					$fail_arr[] = $student['id'];
				}
			}
			echo "插入成功" . $count ."条数据";
			if ($count != count($students_info)) {
				echo "插入失败的学生ID为" . json_encode($fail_arr);
			}
		}
	}
}