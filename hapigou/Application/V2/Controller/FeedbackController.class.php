<?php
namespace V2\Controller;

use V2\Controller\CommonController;

class FeedbackController extends CommonController {
	
	// 反馈建议配置
	protected $feedback_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('content', 'is_string', 'CONTENT_IS_INVALID', 1, 'function')
		)
	);
	
	/**
	 * 反馈建议
	 */
	public function feedback_post_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 生成SQL条件
		$data['user_id'] = $user_id;
		$data['content'] = $this->content;
		$data['create_time'] = time();
		// 实例化反馈模型
		$model = M(CONTROLLER_NAME);
		$result = $model->add($data);
		// 插入失败
		if($result === false) $this->DResponse(500);
		// 反馈成功
		$feedback_result['id'] = $result;
		$this->successReturn($feedback_result);
	}
}