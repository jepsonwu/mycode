<?php
namespace V2\Controller;

use \V2\Controller\CommonController;

/**
 * 外教申请
 */
class TeachersController extends CommonController
{
	protected $apply_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("real_name", "require", null, 1),
			array("live_photo", "require", null, 1),
			array("certificate_photo", "require", null, 1),
			array("language", "require", null, 1),
			array("category", "require", null, 1),
			array("job", "require", null, 1),
			array("contact_info", "is_string", null, 1, "function", null, true),
		)
	);

	/**
	 * 申请外教
	 */
	public function apply_post_html()
	{
		//看是否已经申请
		$model = M("TeacherDetail");
		$res = $model->where("user_id='" . USER_ID . "'")->getField("status");

		$res && in_array($res, array(1, 2)) && parent::failReturn(C("APPLY_TEACHER_REPETITIVE"));

		//图片处理
		$this->live_photo = upload_pic("teachers", $this->live_photo);
		if (C($this->live_photo))
			parent::failReturn(C($this->live_photo));
		$this->certificate_photo = upload_pic("teachers", $this->certificate_photo);
		if (C($this->certificate_photo))
			parent::failReturn(C($this->certificate_photo));

		$this->create_time = time();
		$this->status = 1;

		//审核失败  修改内容
		if ($res) {
			$res = $model->where("user_id='" . USER_ID . "'")->save($this->_default);
		} else {
			$this->_default['user_id'] = USER_ID;
			$res = $model->add($this->_default);
		}

		$res === false && parent::failReturn(C("APPLY_TEACHER_FAILD"));
		parent::successReturn("");

	}

	protected $apply_v2_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("real_name", "require", null, 1),
			array("certificate_photo", "require", null, 1),
			array("email", "email", null, 1),
			array("skype", "require", null, 1),
			array("job", "require", null, 1),
		)
	);

	/**
	 * 申请外教
	 */
	public function apply_v2_post_html()
	{
		//看是否已经申请
		$model = M("TeacherDetail_{$this->_api_version}");
		$res = $model->where("user_id='" . USER_ID . "'")->getField("status");

		$res && in_array($res, array(1, 2)) && parent::failReturn(C("APPLY_TEACHER_REPETITIVE"));

		//图片处理
		$this->certificate_photo = upload_pic("teachers", $this->certificate_photo);
		if (C($this->certificate_photo))
			parent::failReturn(C($this->certificate_photo));

		$this->create_time = time();
		$this->status = 1;

		//审核失败  修改内容
		if ($res) {
			$res = $model->where("user_id='" . USER_ID . "'")->save($this->_default);
		} else {
			$this->_default['user_id'] = USER_ID;
			$res = $model->add($this->_default);
		}

		$res === false && parent::failReturn(C("APPLY_TEACHER_FAILD"));
		parent::successReturn("");

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

	protected $apply_v2_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("fields", "real_name,certificate_photo,job,email,status,skype",
				null, 0, "in", "real_name,status"),
		)
	);

	/**
	 * 获取外教申请信息
	 * @return [type] [description]
	 */
	public function apply_v2_get_html()
	{
		$result = M("TeacherDetail_{$this->_api_version}")->
		where("user_id='" . USER_ID . "'")->field($this->fields)->find();

		if (isset($result['certificate_photo']))
			$result['certificate_photo'] = create_pic_url("teachers") . $result['certificate_photo'];

		parent::successReturn($result);
	}

	protected $list_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("fields", "id,name,avatar,nationality"),
			array("page", "number", null, 0, null, 1),
			array("listrows", "number", null, 0, null, 6),
		),
	);

	/**
	 * 老师列表获取
	 */
	public function list_get_html()
	{
		S("teacher_count", array("offline" => 1, "online" => 1, "busy" => 2));
		S("teacher_online_list", array("129" => 65));
		S("teacher_busy_list", array("132" => 5656, "130" => 78));
		S("teacher_offline_list", array("127" => 456));

		//获取老师状态缓存
		$teachers_count = S("teacher_count");

		//判断分页总额
		$start = intval($this->page - 1) * $this->listrows;
		$end = $this->page * $this->listrows;
		if ($teachers_count && array_sum($teachers_count) > $start) {
			//map
			$map = array(
				"online" => 1,
				"busy" => 2,
				"offline" => 3
			);
			$teachers = $teachers_tmp = array();

			//计算取第几段
			foreach (array("online", "busy", "offline") as $status) {
				$teachers_tmp = S("teacher_{$status}_list");

				foreach ($teachers_tmp as $tid => $called_time) {
					$teachers[$tid] = array(
						"called_time" => $called_time,
						"status" => $map[$status]
					);
				}

			}
			unset($teachers_tmp);

			if ($teachers) {
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

						//合并数据
						$teachers[$value['id']] = array_merge($teachers[$value['id']], $value);
					}

					parent::successReturn(array_values($teachers));
				}
			}
		}

		parent::failReturn(C("TEACHERS_IS_NULL"));
	}
}