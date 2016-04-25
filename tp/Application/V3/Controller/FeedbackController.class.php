<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class FeedbackController extends CommonController
{
	// 用户有效状态
	const STATUS_USER_VALID = 1;

	// 反馈建议配置
	protected $feedback_post_html_conf = array(
		'check_fields' => array(
			array('content', 'is_string', 'CONTENT_IS_INVALID', 1, 'function'),
			array('user_id', 'number', 'USER_INVALID', 1),
		)
	);
	
	/**
	 * 反馈建议
	 */
	public function feedback_post_html()
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
		} else {
			$this->failReturn(C("USER_IS_NOT_EXIST"));
		}
	}
}