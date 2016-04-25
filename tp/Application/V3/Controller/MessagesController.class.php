<?php
namespace V3\Controller;

use \V3\Controller\CommonController;

class MessagesController extends CommonController
{
	// 通知状态（已通知）
	const STATUS_NOTIFY_ALREADY = 1;
	// 通知类型（首次登录）
	const TYPE_FIRST_LOGIN = 1;
	// 用户类型（教师）
	const TYPE_TEACHER = '1';

	// 消息通知配置
	protected $read_get_html_conf = array(
		'check_user' => true
	);

	/**
	 * 消息通知
	 * @return json $result
	 */
	public function read_get_html()
	{
		// 初始化返回结果
		$result = null;
		// 获取用户ID
		$user_id = USER_ID;
		// 验证用户身份
		$type = M('Users')->getFieldById($user_id, 'type');
		// 首次登录不通知教师
		if($type == self::TYPE_TEACHER) $this->successReturn();

		// 查询是否已经通知
		$notify_cond = array(
			'user_id' => $user_id,
			'type' => self::TYPE_FIRST_LOGIN,
			'status' => self::STATUS_NOTIFY_ALREADY
		);
		$notify_result = M('NotifyRecord')->where($notify_cond)->getField('id');

		// 未通知
		if (empty($notify_result)) {
			$notify_cond['create_time'] = time();
			$insert_result = M('NotifyRecord')->add($notify_cond);
			// 新增失败
			if ($insert_result === false) $this->DResponse(500);
			$message = C('NOTIFY_MESSAGE.FIRST_LOGIN');
			$result['message'] = $message;
		}
		// 返回结果
		$this->successReturn($result);
	}
}