<?php
namespace V2\Controller;

use \V2\Controller\CommonController;

/**
 * 上传API
 */
class UploadController extends CommonController
{
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
		"check_user" => true,
		"check_fields" => array(
			array("feedback_id", "number", null, 1),
			array("data", "require", null, 1),
		),
	);

	/**
	 * wap端日志上传
	 * @return [type] [description]
	 */
	public function wap_logs_post_html()
	{
		//feedback_id 判断
		$res = M("Feedback")->where("id='{$this->feedback_id}'")->field("id")->find();
		!$res && parent::failReturn(C("FEEDBACK_IS_NOT_EXIST"));

		//上传文件
		$file_name = upload_pic("wap_logs", $this->data);
		C($file_name) && parent::failReturn(C($file_name));

		//保存记录
		$data = array(
			"user_id" => USER_ID,
			"feedback_id" => $this->feedback_id,
			"file_name" => $file_name,
			"create_time" => time()
		);
		$res = M("WapLogs")->add($data);
		$res === false && parent::failReturn(C("UPLOAD_FAILD"));

		parent::successReturn(array("file_name" => $file_name, "dir" => create_pic_url("wap_logs")));
	}
}