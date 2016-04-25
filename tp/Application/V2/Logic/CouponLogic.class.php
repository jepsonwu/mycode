<?php
namespace V2\Logic;

use V2\Logic\CommonLogic;
use V2\Controller\CouponRulesController;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-11
 * Time: 下午2:28
 */
class CouponLogic extends CommonLogic
{
	//优惠券状态
	const COUPON_NEW = 1;
	const COUPON_USED = 2;
	const COUPON_LOCK = 3;

	// 优惠券类型
	const RECEIVE_TYPE = '1'; // 用户自领
	const RECEIVE_ISSUING_TYPE = '3'; // 自领、发放均可
	// 优惠券状态
	const AVAILABLE_STATUS = '1'; // 有效
	const STOP_RECEIVE_STATUS = '2'; // 停止领取
	// 非固定期限的优惠券
	const NO_FIXED_PERIOD = '1';

	/**
	 * 判断用户优惠券是否可用
	 * @param $user_coupon_id
	 * @param string $where 默认ID order_id
	 * @return int
	 */
	static public function coupon_available($user_coupon_id, $where = "id")
	{
		$now = time();
		//判断优惠券有效性
		$coupon_info = M("UserCoupon")->where("{$where}='{$user_coupon_id}'")
			->field("coupon_id,user_id,status,start_time,end_time,amount")->find();

		//判断时间有效性 判断状态有效性
		if ($coupon_info && $coupon_info['user_id'] == USER_ID
			&& $now >= $coupon_info['start_time'] && $now <= $coupon_info['end_time']
			&& $coupon_info['status'] == self::COUPON_NEW
		) {
			//计算优惠金额
			//$coupon_amount = M("Coupons")->where("id='{$coupon_info['coupon_id']}'")->getField("amount");
			//!$coupon_amount && $coupon_amount = 0;

			return $coupon_info['amount'];
		}

		parent::define_code("COUPON_IS_NOT_AVAILABLE");
		return 0;
	}

	/**
	 * 锁定优惠券 插入order_id used_time 锁定状态 status
	 * @param $order_id
	 * @param $user_coupon_id
	 * @return bool
	 */
	static public function coupon_lock($order_id, $user_coupon_id)
	{
		$save = array(
			"order_id" => $order_id,
			"used_time" => time(),
			"status" => self::COUPON_LOCK
		);
		$result = M("UserCoupon")->where("id='{$user_coupon_id}'")->save($save);

		if ($result === false) {
			parent::define_code("COUPON_USED_FIELD");
			return false;
		}

		return true;
	}

	/**
	 * 优惠券使用
	 * 成功修改状态为已使用
	 * 不成功修改为未使用 置空使用时间、订单号
	 * @param $order_id
	 * @param int $is_true
	 * @return bool
	 */
	static public function coupon_used($order_id, $is_true = 1)
	{
		$save = array();
		if ($is_true == 1) {
			$save['status'] = self::COUPON_USED;
		} else {
			$save['status'] = self::COUPON_NEW;
			$save['used_time'] = "";
			$save['order_id'] = "";
		}

		$error_code = "";
		$model = M("UserCoupon");
		//判断订单优惠券信息是否存在  以及状态必须为锁定
		$result = $model->where("order_id='{$order_id}'")->field("status")->find();

		if ($result && $result['status'] == self::COUPON_LOCK) {
			$result = $model->where("order_id='{$order_id}'")->save($save);
			$result === false && $error_code = "COUPON_USED_FIELD";
		} else {
			$error_code = "COUPON_IS_NOT_AVAILABLE";
		}

		if ($error_code) {
			order_log($order_id, false, $error_code);
			parent::define_code($error_code);
			return false;
		}

		order_log($order_id,true);
		return true;
	}

	/**
	 * 快速使用 不经过锁定
	 * @param $user_coupon_id
	 * @param $order_id
	 * @return bool
	 */
	static public function coupon_quick($user_coupon_id, $order_id)
	{
		$save = array();
		$save['status'] = self::COUPON_USED;
		$save['used_time'] = time();
		$save['order_id'] = $order_id;

		$result = M("UserCoupon")->where("id='{$user_coupon_id}'")->save($save);
		if ($result === false) {
			parent::define_code("COUPON_USED_FIELD");
			return false;
		}

		return true;
	}

	/**
	 * 兑换优惠券
	 * @param $user_id 用户ID
	 * @param $discount_code 优惠码
	 * @return $user_coupon_info 用户优惠券信息
	 */
	public static function exchangeCoupon($user_id, $discount_code)
	{
		// 实例化优惠券模型
		$coupon_model = M('Coupons');
		// 查询条件
		$coupon_cond['type'] = array('in', self::RECEIVE_TYPE . ',' . self::RECEIVE_ISSUING_TYPE);
		$coupon_cond['status'] = self::AVAILABLE_STATUS;
		$coupon_cond['discount_code'] = $discount_code;
		// 查询结果
		$coupon_info = $coupon_model->where($coupon_cond)
			->field('id,start_time,validity,rule,everyone_limit,total,fixed_period,amount,priority,name,intro')->find();
		// 查询失败
		if ($coupon_info === false) {
			parent::define_code('GET_COUPON_INFO_FAILED');
			return false;
		}
		// 优惠券不存在
		if (empty($coupon_info)) {
			parent::define_code('DISCOUNT_CODE_IS_NOT_EXIST');
			return false;
		}

		// 判断是否存在领取规则
		if (!empty($coupon_info['rule'])) {
			// 获取领取规则集合
			$coupon_receive_rules = C('COUPON_RECEIVE_RULES');
			// 存在领取规则
			if (in_array($coupon_info['rule'], array_keys($coupon_receive_rules))) {
				// 调用领取规则
				$coupon_rule = new CouponRulesController();
				list($result, $message) = call_user_func(
					array($coupon_rule, $coupon_receive_rules[$coupon_info['rule']]), $user_id
				);
				// 不满足领取规则
				if (!$result) {
					parent::define_code($message);
					return false;
				}
			}
		}

		// 判断是否存在每人领取次数限制
		if (!empty($coupon_info['everyone_limit'])) {
			// 查询条件
			$user_coupon_cond['user_id'] = $user_id;
			$user_coupon_cond['coupon_id'] = $coupon_info['id'];
			// 获取用户领取次数
			$personal_count = M('UserCoupon')->where($user_coupon_cond)->count();
			// 已达到个人领取上限
			if ($personal_count == $coupon_info['everyone_limit']) {
				parent::define_code('ATTAIN_PERSONAL_RECEIVE_LIMIT');
				return false;
			}
		}

		// 获取领取次数
		$coupon_detail_model = M('CouponDetail');
		$receive_count = $coupon_detail_model->getFieldByCouponId($coupon_info['id'], 'receive_count');
		// 查询失败
		if ($receive_count === false) {
			parent::define_code('GET_COUPON_DETAIL_INFO_FAILED');
			return false;
		}
		// 开启事务
		$coupon_detail_model->startTrans();
		// 结果集为空
		if (is_null($receive_count)) {
			// 优惠券详情表插入数据
			$coupon_detail_data['coupon_id'] = $coupon_info['id'];
			$coupon_detail_data['receive_count'] = 1;
			$coupon_detail_insert = $coupon_detail_model->add($coupon_detail_data);
			// 插入失败
			if ($coupon_detail_insert === false) {
				parent::define_code('INSERT_COUPON_DETAIL_FAILED');
				return false;
			}
		} else {
			// 有数量限制的优惠券
			if (!empty($coupon_info['total'])) {
				// 更新优惠券领取数
				$update_cond['coupon_id'] = $coupon_info['id'];
				$update_cond['receive_count'] = array('lt', $coupon_info['total']);
				$coupon_detail_update = $coupon_detail_model->where($update_cond)->setInc('receive_count', 1);
				// 优惠券领取结束
				if ($coupon_detail_update === 0) {
					// 更新优惠券状态（禁止领取）
					$coupon_update = $coupon_model->where("id={$coupon_info['id']}")
						->save('status=' . self::STOP_RECEIVE_STATUS);
					// 返回信息
					parent::define_code('COUPON_RECEIVE_END');
					return false;
				}
			} // 无数量限制的优惠券
			else {
				// 更新优惠券领取数
				$coupon_detail_update = $coupon_detail_model->where("coupon_id={$coupon_info['id']}")
					->setInc('receive_count', 1);
			}
			// 更新失败
			if ($coupon_detail_update === false) {
				parent::define_code('UPDATE_COUPON_DETAIL_FAILED');
				return false;
			}
		}

		// 为用户添加优惠券
		$user_coupon_model = M('UserCoupon');
		$user_coupon_data['user_id'] = $user_id;
		$user_coupon_data['coupon_id'] = $coupon_info['id'];
		$user_coupon_data['receive_time'] = time();
		$user_coupon_data['amount'] = $coupon_info['amount'];
		$user_coupon_data['priority'] = $coupon_info['priority'];
		// 优惠券不是固定期限使用
		if ($coupon_info['fixed_period'] == self::NO_FIXED_PERIOD) {
			$user_coupon_data['start_time'] = $user_coupon_data['receive_time'];
		} else {
			$user_coupon_data['start_time'] = $coupon_info['start_time'];
		}
		$user_coupon_data['end_time'] = strtotime(date('Ymd', $user_coupon_data['start_time'])) +
			$coupon_info['validity'] * 24 * 3600;
		// 插入结果
		$user_coupon_insert = $user_coupon_model->add($user_coupon_data);
		// 插入失败
		if ($user_coupon_insert === false) {
			// 回滚
			$coupon_detail_model->rollback();
			parent::define_code('INSERT_USER_COUPON_FAILED');
			return false;
		} else {
			// 提交
			$coupon_detail_model->commit();
			// 返回信息
			$result = array(
				'id' => (string)$user_coupon_insert,
				'name' => $coupon_info['name'],
				'intro' => $coupon_info['intro'],
				'amount' => (string)($coupon_info['amount'] / 100),
				'start_time' => (string)$user_coupon_data['start_time'],
				'end_time' => (string)$user_coupon_data['end_time']
			);

			return $result;
		}
	}

}
