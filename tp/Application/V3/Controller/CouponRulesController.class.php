<?php
namespace V3\Controller;

class CouponRulesController {

	/*
	 * 新人规则
	 * 
	 * @param $user_id 用户ID
	 * @return array(bool,$message)
	 */
	public function newUser($user_id) {
		// 用户ID为空
		if (empty($user_id)) return array(false, 'USER_INVALID');
		// 查询用户订单记录
		$order_model = M('Orders');
		$cond['sid'] = $user_id;
		$cond['status'] = array('in', C('ORDERS_STATUS.COMMENT') . ',' . C('ORDERS_STATUS.DONE'));
		$id = $order_model->where($cond)->getField('id');
		// 查询失败
		if ($id === false) return array(false, 'DB_QUERY_FAILED');
		// 不是新用户
		if (!empty($id)) {
			return array(false, 'NOT_A_NEW_USER');
		} else {
			return array(true, '');
		}
	}
}