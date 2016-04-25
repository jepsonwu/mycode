<?php
namespace V2\Controller;

use V2\Controller\CommonController;
use Common\Logic\OrderCacheLogic;

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
			array("real_name", "require", null, 1),
			array("live_photo", "require", null, 0),
			array("certificate_photo", "require", null, 1),
			array("language", "require", null, 0),
			array("category", "require", null, 0),
			array("job", "require", null, 1),
			array("skype", "require", null, 0),
			array("email", "email", null, 0),
			array("contact_info", "is_string", null, 0, "function", null, true),
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
		$live_photo = $this->live_photo;
		if (!empty($live_photo)) {
			$live_photo = upload_pic("teachers", $live_photo);
			if (C($live_photo)) parent::failReturn(C($this->live_photo));
		}
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
						$value['billing'] = OrderCacheLogic::billing_get(1)[1];
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