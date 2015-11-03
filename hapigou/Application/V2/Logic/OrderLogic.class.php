<?php
namespace V2\Logic;

use V2\Logic\CommonLogic;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-11
 * Time: 下午2:28
 */
class OrderLogic extends CommonLogic
{
	//支付方式
	const PAY_ALIPAY = 1;

	/**
	 * 判断订单是否允许的事件
	 * 结算，支付，评论  settlement pay comment
	 * @param $order_info当为字符串时判断为订单号
	 * @param string $event
	 * @return bool
	 */
	static public function order_allow_event($order_info, $event = "")
	{
		if (is_string($order_info))
			$order_info = M("Orders")->where("order_id='{$order_info}'")->field("status")->find();

		$error_code = "";

		if ($order_info) {
			switch (strtolower($event)) {
				case "new":
					$order_info['status'] != C("ORDERS_STATUS.NEW") && $error_code = "ORDER_NOT_ALLOW_SETTLEMENT";
					break;
				case "pay":
					$order_info['status'] != C("ORDERS_STATUS.PAY") && $error_code = "ORDER_NOT_ALLOW_PAY";
					break;
			}
		} else {
			$error_code = "ORDER_IS_NULL";
		}

		if ($error_code) {
			parent::define_code($error_code);
			return false;
		}

		return true;
	}

	/**
	 * 订单支付修改信息方法
	 * @param $order_id
	 * @param $info
	 * @param int $pay_type
	 * @return bool
	 */
	static public function order_pay($order_id, $info, $pay_type = self::PAY_ALIPAY)
	{
		$data['status'] = C("ORDERS_STATUS.COMMENT");
		$data['pay_type'] = $pay_type;
		$data['paid_time'] = time();
		$data = array_merge($data, $info);

		$result = M("Orders")->where("order_id='{$order_id}'")->save($data);

		if ($result === false) {
			order_log($order_id);

			parent::define_code("ORDER_PAY_FIELD");
			return false;
		}

		order_log($order_id, true);
		return true;
	}
}
