<?php
namespace V2\Controller;

use V2\Controller\CommonController;
use V2\Logic\CouponLogic;

class CouponsController extends CommonController {

	// 优惠券状态
	const USEABLE_STATUS = '1'; // 可使用
	const USED_STATUS = '2'; // 已使用
	const EXPIRED_STATUS = '3'; // 已过期
	const MULTIPLE_CODE_STATUS = '1'; // 批量优惠码
	// 随机码长度
	const RANDOM_CODE_LENGTH = 8;

	// 优惠券兑换配置
	protected $exchange_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('discount_code', 'is_string', 'DISCOUNT_CODE_IS_INVALID', 1, 'function')
		)
	);

	/*
	 * 优惠劵兑换
	 */
	public function exchange_post_html() {
		// 获取用户ID与优惠码
		$user_id = USER_ID;
		$discount_code = $this->discount_code;

		// 确认优惠码有效性
		$multi_code = M('Coupons')->getFieldByDiscountCode($discount_code, 'multi_code');
		// 优惠码不存在
		if (empty($multi_code)) {
			// 验证随机码
			$sub_code = self::checkRandomCode($discount_code);
			if (!$sub_code) $this->failReturn(C('DISCOUNT_CODE_IS_NOT_EXIST'));
			// 判断是否领取过
			$discount_code_list = S('DISCOUNT_CODE_LIST');
			if (!empty($discount_code_list)) {
				$receive_result = in_array($discount_code, $discount_code_list);
				if ($receive_result) $this->failReturn(C('ATTAIN_PERSONAL_RECEIVE_LIMIT'));
			} else {
				$discount_code_list = array();
			}
			// 优惠码赋值
			$discount_code = $sub_code;
		} else {
			// 唯一优惠码状态为批量优惠码时，系统错误
			if ($multi_code == self::MULTIPLE_CODE_STATUS) $this->failReturn(C('SYSTEM_ERROR'));
		}
		
		// 兑换优惠券
		$result = CouponLogic::exchangeCoupon($user_id, $discount_code);
		// 兑换失败
		if (!$result) {
			$this->failReturn(C(ERROR_CODE));
		} else {
			// 兑换成功存入缓存
			$discount_code_list[] = $this->discount_code;
			S('DISCOUNT_CODE_LIST', $discount_code_list);
		}
		// 返回兑换信息
		$this->successReturn($result);
	}

	// 当前用户优惠券列表配置
	protected $list_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('status', '1,2,3', 'COUPON_STATUS_IS_INVALID', 1, 'in'),
			array('order_id', 'number', 'ORDER_ID_IS_INVALID'),
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);

	/*
	 * 当前用户优惠券列表
	 */
	public function list_get_html() {
		// 获取用户ID与优惠券状态
		$user_id = USER_ID;
		$status = $this->status;

		// 查询用户优惠券信息
		$user_coupon_model = M('UserCoupon uc');
		$user_coupon_cond['uc.user_id'] = $user_id;
		$user_coupon_cond['uc.status'] = $status;
		$user_coupon_cond['_string'] = 'c.name is not null';
		// 可使用优惠券
		if ($status == self::USEABLE_STATUS) {
			// 是否存在通话时长限制
			if (!empty($this->request['order_id'])) {
				$called_time = M('Orders')->getFieldByOrderId($this->order_id, 'called_time');
				// 查询失败
				if ($called_time === false) $this->DResponse(500);
				// 订单不存在
				if (is_null($called_time)) $this->failReturn(C('ORDER_IS_NULL'));
				// 增加通话时长限制条件
				$user_coupon_cond['c.second_limit'] = array('elt', $called_time);
				$user_coupon_cond['uc.start_time'] = array('elt', time());
			}

			// 查询优惠券信息
			$user_coupon_cond['uc.end_time'] = array('egt', time());
			$useable_coupons = $user_coupon_model->join('left join ft_coupons c on uc.coupon_id=c.id')
				->field('uc.id,c.name,c.intro,uc.amount,uc.start_time,uc.end_time')
				->where($user_coupon_cond)->page($this->page, $this->listrows)
				->order('uc.priority desc, uc.amount desc, uc.end_time asc, uc.receive_time asc')->select();
			// 查询失败
			if ($useable_coupons === false) $this->DResponse(500);
			// 查询结果
			$list = $useable_coupons;
		}
		// 已过期优惠券
		elseif ($status == self::EXPIRED_STATUS) {
			$user_coupon_cond['uc.status'] = self::USEABLE_STATUS;
			$user_coupon_cond['uc.end_time'] = array('lt', time());
			$expired_coupons = $user_coupon_model->join('left join ft_coupons c on uc.coupon_id=c.id')
				->field('uc.id,c.name,c.intro,uc.amount,uc.start_time,uc.end_time')
				->where($user_coupon_cond)->page($this->page, $this->listrows)->order('uc.end_time desc')->select();
			// 查询失败
			if ($expired_coupons === false) $this->DResponse(500);
			// 查询结果
			$list = $expired_coupons;
		}
		// 已使用优惠券
		elseif ($status == self::USED_STATUS) {
			$used_coupons = $user_coupon_model->join('left join ft_coupons c on uc.coupon_id=c.id')
				->field('uc.id,c.name,c.intro,uc.amount,uc.start_time,uc.end_time')
				->where($user_coupon_cond)->page($this->page, $this->listrows)->order('uc.used_time desc')->select();
			// 查询失败
			if ($used_coupons === false) $this->DResponse(500);
			// 查询结果
			$list = $used_coupons;
		}

		// 返回结果
		if (empty($list)) {
			$result = $list;
		} else {
			// 记录总数
			$result['total'] = $user_coupon_model->join('left join ft_coupons c on uc.coupon_id=c.id')
				->where($user_coupon_cond)->count();
			// 处理结果集
			array_walk($list, function(&$val) {
				// 金额
				$val['amount'] = (string)($val['amount'] / 100);
				// 还有多少天可用
				$days_to_available = (strtotime(date('Ymd', $val['start_time'])) - strtotime(date('Ymd'))) / (3600 * 24);
				$val['days_to_available'] = $days_to_available > 0 ? $days_to_available : 0;
				// 还有多少天到期
				$days_to_expired = (strtotime(date('Ymd', $val['end_time'])) - strtotime(date('Ymd'))) / (3600 * 24);
				$val['days_to_expired'] = $days_to_expired > 0 ? $days_to_expired : 0;
			});
			$result['list'] = $list;
		}
		$this->successReturn($result);
	}

	/**
	 * 验证优惠码中的随机码
	 * @param $discount_code
	 * @return bool
	 */
	private function checkRandomCode($discount_code) {
		// 长度不满足随机码长度
		if (strlen(trim($discount_code)) < self::RANDOM_CODE_LENGTH) return false;
		// 获取优惠码
		$random_code = substr($discount_code, -self::RANDOM_CODE_LENGTH);
		$int_code = coupon_code_decrypt($random_code);
		if (is_int($int_code)) {
			$sub_code = substr($discount_code, 0, strlen($discount_code) - self::RANDOM_CODE_LENGTH);
			$coupon_info = M('Coupons')->where("discount_code='{$sub_code}'")
				->field('multi_code, total')->find();
			// 优惠码信息不存在
			if (!empty($coupon_info) && $coupon_info['multi_code'] == self::MULTIPLE_CODE_STATUS) {
				// 验证随机码有效性
				$check_result = check_random_code($int_code, $coupon_info['total']);
				if (!$check_result) return false;
			} else {
				return false;
			}
		} else {
			return false;
		}
		// 返回优惠码
		return $sub_code;
	}

}