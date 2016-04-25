<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class ShareController extends CommonController
{
	// 国内国际代码
	const INLAND_INTERNATIONAL_CODE = 86;

	// 获取分享链接配置
	protected $link_get_html_conf = array(
		'check_user' =>true
	);

	/**
	 * 获取分享链接
	 * @return json $result
	 */
	public function link_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;

		// 查询唯一ID是否存在
		$uuid = M('UserUniqueCode')->getFieldByUserId($user_id, 'uuid');
		if ($uuid) {
			// 生成链接
			$result['link'] = APP_URL . "/V3/share?uuid=$uuid";
		} else {
			// 获取uuid
			$uuid = $this->createUniqueId();
			// 唯一ID入库
			$uuid_result = M('UserUniqueCode')->add(array('user_id' => $user_id, 'uuid' => $uuid));
			// 插入失败
			if ($uuid_result === false) $this->DResponse(500);
			// 生成链接
			$result['link'] = APP_URL . "/V3/share?uuid=$uuid";
		}
		
		// 返回结果
		$this->successReturn($result);

	}

	// 分享页面配置
	protected $page_get_html_conf = array(
		'check_fields' => array(
			array('uuid', 'check_uuid', 'SHARE_LINK_IS_INVALID', 1, 'function'),
			array('order_id', 'number', 'SHARE_LINK_IS_INVALID', 1)
		)
	);

	/**
	 * 分享页面
	 * @return  html
	 */
	public function page_get_html()
	{
		// 获取订单号
		$order_id = $this->order_id;
		// 获取订单详情
		$order_info = M('Orders')->where("order_id={$order_id}")->field('sid,tid,called_time')->find();
		// 订单不存在
		if (empty($order_info)) $this->failReturn(C('SHARE_LINK_IS_INVALID'));

		// 通话分钟数与秒数
		$call_minutes = floor($order_info['called_time'] / 60);
		$call_seconds = $order_info['called_time'] % 60;

		// 学生头像与昵称
		$student_info = M('Users')->where("id={$order_info['sid']}")->field('name,avatar')->find();
		// 学生不存在
		if (empty($student_info)) $this->failReturn(C('SHARE_LINK_IS_INVALID'));
		// 学生头像链接
		$student_avatar = get_avatar_url($order_info['sid'], $student_info['avatar']);

		// 教师头像与昵称
		$teacher_info = M('Users')->where("id={$order_info['tid']}")->field('name,avatar')->find();
		// 教师不存在
		if (empty($teacher_info)) $this->failReturn(C('SHARE_LINK_IS_INVALID'));
		// 教师头像链接
		$teacher_avatar = get_avatar_url($order_info['tid'], $teacher_info['avatar']);

		// 变量输出
		$this->assign('call_minutes', $call_minutes);
		$this->assign('call_seconds', $call_seconds);
		$this->assign('student_name', $student_info['name']);
		$this->assign('student_avatar', $student_avatar);
		$this->assign('teacher_name', $teacher_info['name']);
		$this->assign('teacher_avatar', $teacher_avatar);
		$this->assign('uuid', $this->uuid);
		
		// 显示页面
		$this->display('index');
	}

	// 分享记录配置
	protected $record_post_html_conf = array(
		'check_fields' => array(
			array('uuid', 'check_uuid', 'SHARE_LINK_IS_INVALID', 1, 'function'),
			array('mobile', '/^1[34578]{1}\d{9}$/', 'MOBILE_IS_INVALID', 1)
		)
	);

	/**
	 * 分享记录
	 * @return json $result
	 */
	public function record_post_html()
	{
		// 获取唯一ID与手机号码
		$uuid = $this->uuid;
		$mobile = $this->mobile;

		// 验证手机号码是否注册
		$user_cond = array(
			'international_code' => self::INLAND_INTERNATIONAL_CODE,
			'mobile' => $mobile
		);
		$user_id = M('Users')->where($user_cond)->getField('id');
		if ($user_id) $this->failReturn(C('MOBILE_IS_ALREADY_REGISTER'));

		// 验证手机号码是否申请过
		$apply_id = M('ShareRecord')->getFieldByMobile($mobile, 'id');
		if ($apply_id) $this->failReturn(C('ALREADY_APPLIED_FOR_A_USER'));

		// 获取分享人ID
		$share_id = M('UserUniqueCode')->getFieldByUuid($uuid, 'user_id');
		// 分享记录入库
		$share_data = array(
			'share_id' => $share_id,
			'mobile' => $mobile,
			'create_time' => time()
		);
		$share_result = M('ShareRecord')->add($share_data);
		// 插入失败
		if ($share_result === false) $this->DResponse(500);
		// 返回结果
		$this->successReturn();
	}

	/**
	 * 生成唯一ID
	 * @return string $uuid
	 */
	private function createUniqueId()
	{
		$uuid = uniqid();
		$id = M('UserUniqueCode')->getFieldByUuid($uuid, 'id');
		if (!empty($id)) {
			$this->createUniqueId();
		} else {
			return $uuid;
		}
	}
}