<?php
namespace V4\Controller;

use V4\Controller\CommonController;
use Common\Logic\OrderCacheLogic;
use Think\Log;

/**
 * 外教申请
 */
class TeachersController extends CommonController
{
	// 显示黑名单教师列表开关
	const DISPLAY_BLACKLIST_ON = '1';
	const DISPLAY_BLACKLIST_OFF = '0';

	protected $apply_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("name", "require", null, 1),
			array("avatar", "require", null, 0),
			array("gender", "require", null, 1),
			array("nationality", "require", null, 1),
			array("introduce", "require", null, 1),
			//array("introduce_audio", "require", null, 0),
			array("audio_time_length", "require", null, 1),
			array("skype", "require", null, 1),
		)
	);

	/**
	 * 申请外教
	 */
	public function apply_post_html()
	{
		$return = array();
		//看是否已经申请
		$model = M("TeacherDetail");
		$res = $model->where("user_id='" . USER_ID . "'")->getField("status");

		$res && in_array($res, array(2)) && parent::failReturn(C("APPLY_TEACHER_REPETITIVE"));

		$avatar = $this->avatar;
		if(!empty($avatar)){
			//图片处理
			$this->avatar = upload_pic("avatar", $this->avatar);
			$return['avatar'] = create_pic_url('avatar', USER_ID) . $this->avatar;
			if (C($this->avatar))
				parent::failReturn(C($this->avatar));
			$userData['avatar'] = $this->avatar;
		}
		//音频处理
		$setting = C('UPLOAD_SITE_QINIU');
		$Upload = new \Think\Upload($setting);
		//print_r($_FILES);exit;
		if(empty($_FILES['introduce_audio']))parent::failReturn(C("APPLY_TEACHER_FAILD"));
		$info = $Upload->upload($_FILES);
		$return['introduce_audio'] = $this->introduce_audio = $info['introduce_audio']['url'];

		$userData['name'] = $this->name;
		$userData['gender'] = $this->gender;
		$userData['nationality'] = $this->nationality;
		$userData['introduce'] = $this->introduce;
		$userData['status'] =  $teacherData['status'] =  1;
		$model->startTrans();
		$result1 = M('Users')->where("id='" . USER_ID . "'")->save($userData);
		$teacherData['introduce_audio'] =  $this->introduce_audio;
		$teacherData['skype'] =  $this->skype;
		$teacherData['audio_time_length'] =  $this->audio_time_length;
		//审核失败  修改内容
		if ($res) {
			$result2 = $model->where("user_id='" . USER_ID . "'")->save($teacherData);
		} else {
			$teacherData['user_id'] =  USER_ID;
			$teacherData['create_time'] =  time();
			$result2 = $model->add($teacherData);
		}

		if(false !== $result1 && false !== $result2){
			$model->commit();
			//更新云信id
			$data =  array(
				"accid" => APP_STATUS.'_'.USER_ID,
				"name" => $this->name
			);
			if(!empty($this->avatar))
				$data['icon'] = $return['avatar'];
			//更新云信ID
			update_accid($data);
			parent::successReturn($return);

		}else{
			$model->rollback();
			parent::failReturn(C("APPLY_TEACHER_FAILD"));
		}

	}


	protected $teacher_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("avatar", "require", null, 0),
			array("introduce", "require", null, 0),
			//array("introduce_audio", "require", null, 0),
			array("skype", "require", null, 0),
			array("audio_time_length", "require", null, 0),
		)
	);

	/**
	 * 更新老师信息
	 */
	public function teacher_post_html()
	{
		$return = array();
		//看是否已经申请
		$model = M("TeacherEdit");
		$res = $model->where("tid='" . USER_ID . "'")->getField("status");
		$avatar = $this->avatar;
		if(!empty($avatar)){
			//图片处理
			$this->avatar = upload_pic("avatar", $this->avatar);
			$return['avatar'] = create_pic_url('avatar', USER_ID) . $this->avatar;
			if (C($this->avatar))
				parent::failReturn(C($this->avatar));
		}

		//音频处理
		//print_r($_FILES);
		if(!empty($_FILES['introduce_audio'])){
			$setting = C('UPLOAD_SITE_QINIU');
			$Upload = new \Think\Upload($setting);
			$info = $Upload->upload($_FILES);
			$return['introduce_audio'] = $this->introduce_audio = $info['introduce_audio']['url'];
		}
		$this->tid = USER_ID;
		$this->status =  1;
		//修改内容
		if ($res) {
			$this->update_time = time();
			$res = $model->where("tid='" . USER_ID . "'")->save($this->_default);
		} else {
			$teacherData['tid'] =  USER_ID;
			$this->update_time =  time();
			// 开启事物
			$model->startTrans();
			$res = $model->add($this->_default);
			if ($res) {
				// 更新申请状态
				$update_result = M('TeacherDetail')->where('user_id=' . USER_ID)->save(array('edit_status'=>1));
				if ($update_result !== false) {
					$model->commit();
				} else {
					$model->rollback();
					$res = false;
				}
			}
		}
		$res === false && parent::failReturn(C("EDIT_TEACHER_FAILD"));
		parent::successReturn($return);
	}

	protected $apply_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("fields", "real_name,live_photo,certificate_photo,language,category,job,contact_info,status",
				null, 0, "in", "real_name,status"),
		)
	);

	/**
	 * 获取外教申请信息
	 * @return [type] [description]
	 */
	public function apply_get_html()
	{
		$result = M("TeacherDetail")->where("user_id='" . USER_ID . "'")->field($this->fields)->find();
		if (isset($result['live_photo']))
			$result['live_photo'] = create_pic_url("teachers") . $result['live_photo'];

		if (isset($result['certificate_photo']))
			$result['certificate_photo'] = create_pic_url("teachers") . $result['certificate_photo'];

		parent::successReturn($result);
	}

	protected $list_get_html_conf = array(
		"check_fields" => array(
			array("fields", "id,name,gender,avatar,nationality"),
			array("page", "number", null, 0, null, 1),
			array("listrows", "number", null, 0, null, 6),
		),
	);

	/**
	 * 老师列表获取
	 */
	public function list_get_html()
	{
		//获取老师状态缓存
		$teachers_count = S("teacher_count");
		//判断分页总额
		$start = intval($this->page - 1) * $this->listrows;
		$end = $this->page * $this->listrows;
		if ($teachers_count && array_sum($teachers_count) > $start) {
			//map
			$map = array_flip(C('TEACHER_STATUS'));
			$teachers = $teachers_tmp = array();

			//计算取第几段
			foreach (array("online", "busy", "offline") as $status) {
				$teachers_tmp = S("teacher_{$status}_list");

				foreach ($teachers_tmp as $tid => $called_time) {
					$teachers[$tid] = array(
						"called_time" => (int)$called_time,
						"status" => $map[$status]
					);
				}

			}
			unset($teachers_tmp);

			// 是否显示黑名单教师
			$display_flg = S('display_blacklist_flg');
			if ($display_flg === false) S('display_blacklist_flg', self::DISPLAY_BLACKLIST_OFF);
			// 隐藏黑名单教师
			if ($display_flg === self::DISPLAY_BLACKLIST_OFF) {
				// 获取黑名单列表
				$blacklist = S('teacher_blacklist');
				if ($blacklist === false) {
					$blacklist = C('TEACHER_BLACKLIST');
					S('teacher_blacklist', $blacklist);
				}
				// 教师列表剔除黑名单教师
				foreach ($blacklist as $teacher_id) {
					if (in_array($teacher_id, array_keys($teachers))) {
						unset($teachers[$teacher_id]);
					}
				}
			}

			if ($teachers) {
				// 总数
				$total = count($teachers);
				//分页
				$i = 1;
				foreach ($teachers as $key => $value) {
					if ($i <= $start || $i > $end)
						unset($teachers[$key]);

					$i++;
				}

				//查询信息
				$info = M("Users")->where(array("id" => array("in", array_keys($teachers))))->
				field($this->fields)->select();

				if ($info) {
					foreach ($info as $value) {
						$value['avatar'] && $value['avatar'] = create_pic_url("avatar", $value['id']) . $value['avatar'];
						// 收费标准
						$type = M('TeacherDetail')->getFieldByUserId($value['id'], 'type');
						$billing = M('TeacherCategory')->getFieldByType($type, 'customer_price');
						$value['billing'] = $billing;
						//合并数据
						$teachers[$value['id']] = array_merge($teachers[$value['id']], $value);
					}

					$this->successReturn(
						array(
							"total" => $total,
							"list" => array_values($teachers)
						)
					);
				}
			}
		}

		$this->successReturn();
	}
}