<?php
namespace V2\Controller;

use \V2\Controller\CommonController;

/**
 * 上传API
 */
class UploadController extends CommonController
{
	// 用户有效状态
	const STATUS_USER_VALID = 1;

	protected $pic_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("type", "AVATAR,TEACHERS", 'UPLOAD_TYPE_IS_INVALID', 1, "in"),
			// array("data","/^[a-zA-Z0-9\/+]*={0,3}$/",null,1),  ios 过滤失败gui
			array("data", "require", null, 1)
		),
	);

	public function pic_post_html()
	{
		$type = strtolower($this->type);

		$file_name = upload_pic($type, $this->data);
		C($file_name) && parent::failReturn(C($file_name));

		parent::successReturn(array("file_name" => $file_name, "dir" => create_pic_url($type)));
	}

	protected $wap_logs_post_html_conf = array(
		"check_fields" => array(
			array("feedback_id", "number", null, 1),
			array("data", "require", null, 1),
			array('user_id', 'number', 'USER_INVALID', 1),
		),
	);

	/**
	 * wap端日志上传
	 * @return [type] [description]
	 */
	public function wap_logs_post_html()
	{
		// 获取用户ID
		$user_id = $this->user_id;
		// 验证用户有效性
		$user_cond = array(
			'id' => $user_id,
			'status' => self::STATUS_USER_VALID
		);
		$user_result = M('Users')->where($user_cond)->getField('id');

		// 用户存在
		if ($user_result || $user_id == '0') {
			//feedback_id 判断
			$res = M("Feedback")->where("id='{$this->feedback_id}'")->field("id")->find();
			!$res && parent::failReturn(C("FEEDBACK_IS_NOT_EXIST"));

			//上传文件
			$file_name = upload_pic("wap_logs", $this->data);
			C($file_name) && parent::failReturn(C($file_name));

			//保存记录
			$data = array(
				"user_id" => $user_id,
				"feedback_id" => $this->feedback_id,
				"file_name" => $file_name,
				"create_time" => time()
			);
			$res = M("WapLogs")->add($data);
			$res === false && parent::failReturn(C("UPLOAD_FAILD"));

			parent::successReturn(array("file_name" => $file_name, "dir" => create_pic_url("wap_logs")));
		} else {
			$this->failReturn(C("USER_IS_NOT_EXIST"));
		}
	}
}