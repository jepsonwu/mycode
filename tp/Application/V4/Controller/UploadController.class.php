<?php
namespace V4\Controller;

use \V4\Controller\CommonController;
use Think\Log;

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
			$file_name = upload_pic("wap_logs", $this->data, $user_id);
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

			parent::successReturn(array("file_name" => $file_name, "dir" => create_pic_url("wap_logs", $user_id)));
		} else {
			$this->failReturn(C("USER_IS_NOT_EXIST"));
		}
	}

	// 上传录音配置
	protected $recording_post_html_conf = array(
		'check_fields' =>array(
			array('room_id', 'number', 'ROOM_ID_IS_INVALID', 1)
		)
	);

	/**
	 * 上传录音
	 */
	public function recording_post_html()
	{
		// 获取room_id
		$room_id = $this->room_id;
		// 录音不为空
		if (!empty($_FILES['recording'])) {
			// 获取七牛配置信息
			$qiniu_config = C('UPLOAD_SITE_QINIU');
			// 上传录音
			$uploader = new \Think\Upload($qiniu_config);
			$info = $uploader->upload($_FILES);
			// 上传成功
			if ($info) {
				// 更新订单表
				$order_result = M('Orders')->where("room_id={$room_id}")
					->save(array('recording_url' => $info['recording']['url']));
				// 更新失败
				if ($order_result === false) $this->failReturn(C('UPDATE_ORDER_INFO_FAILED'));
				// 返回结果
				$this->successReturn();
			} else {
				// 获取错误信息
				$upload_error = $uploader->getError();
				// 记录日志
				Log::record("upload error: {$upload_error}");
				// 返回错误信息
				$this->failReturn(C('UPLOAD_RECORDING_FAILED'));
			}
		} else {
			$this->failReturn(C('RECORDING_IS_INVALID'));
		}
	}
}