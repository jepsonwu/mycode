<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 *审核外教申请
 */
class EditTeacherController extends CommonController
{
	protected $_where_fields = array(
		"eq" => array("status"),
		"bet" => array("create_start_time",
			"create_end_time",
		),
	);

	public function index()
	{
		$this->assign("status", C("EDIT_TEACHER_STATUS"));

		$this->_list(M("TeacherEdit"), $this->_index_where, $this->_index_param, "tid", "update_time");
		$this->display();
	}

	protected function _processer(&$volist)
	{
		foreach ($volist as &$value) {
			$value['update_time'] = date("Y-m-d H:i:s", $value['update_time']);
			$user_info = M('Users')->where("id={$value['tid']}")->field('international_code,mobile,name')->find();
			$value['international_code'] = $user_info['international_code'];
			$value['mobile'] = $user_info['mobile'];
			$value['name'] = $user_info['name'];
			$value['status_show'] = C("EDIT_TEACHER_STATUS." . $value['status']);
			$value['avatar'] = create_pic_url("avatar", $value['tid']) . $value['avatar'];
		}
		//print_r($value);exit;
	}

	/**
	 * 审核外教资料修改申请
	 * @return [type] [description]
	 */
	public function approve()
	{

		$info = I("get.");
		$model = M("TeacherEdit");

		$data['status'] = $info['status'];//修改数据
		$editInfo = $model->where("tid='{$info['tid']}'")->find();
		//审核不通过
		if ($info['status'] == "3") {
			!$info['reason'] && parent::fReturn('请填写原因!');
			$data['reason'] = $info['reason'];
			$result = $model->where("tid='{$info['tid']}'")->save($data);
			$result1 = M('TeacherDetail')->where("user_id={$info['tid']}")->save(array('edit_status'=>3));
		} else {
			$model->startTrans();

			$result = $model->where("tid='{$info['tid']}'")->delete();
			if ($result !== false) {
				if(!empty($editInfo['avatar']) || !empty($editInfo['introduce'])){
					if(!empty($editInfo['avatar'])){
						$userdata['avatar'] = $editInfo['avatar'];
						$nimdata['icon'] = $body['avatar'] = create_pic_url('avatar', $info['tid']) . $userdata['avatar'];
					}
					if(!empty($editInfo['introduce']))
						$body['introduce'] = $userdata['introduce'] = $editInfo['introduce'];
					$result1 = M("Users")->where("id='{$info['tid']}'")->save($userdata);
				}
				if(!empty($editInfo['skype']) || !empty($editInfo['introduce_audio'])){
					if(!empty($editInfo['skype']))
						$body['skype'] = $teacherdata['skype'] = $editInfo['skype'];
					if(!empty($editInfo['introduce_audio'])){
						$body['introduce_audio'] = $teacherdata['introduce_audio'] = $editInfo['introduce_audio'];
						$body['audio_time_length'] = $teacherdata['audio_time_length'] = $editInfo['audio_time_length'];
					}
					$teacherdata['edit_status'] = 2;
					$result2 = M("TeacherDetail")->where("user_id='{$info['tid']}'")->save($teacherdata);
				}
			}
		}
		//发送通知到socket端
		$body['uid'] = $info['tid'];
		$body['en_title'] = 'advice audit';
		if ($info['status'] == "3") {
			$body['result'] = 0;
			$body['en_content'] = 'Your application for updating profile has been rejected due to ' . "'{$info['reason']}'";
		}else{
			$body['result'] = 1;
			$body['en_content'] = 'Your application for updating profile has been approved!';
		}
		$body['type'] = 2;
		$result3 = audit_notice($body);
		if ($result !== false && $result1 !== false && $result2 !== false && $result3['code'] == 200) {
			$model->commit();
			if(!empty($nimdata)){
				//云信id
				$nimdata['accid'] =  APP_STATUS.'_'.$info['tid'];
				//更新云信用户信息
				update_nim_user_info($nimdata);
			}
			parent::sReturn('审核成功!');
		}else{
			$model->rollback();
			parent::fReturn('审核失败!');
		}

	}
}