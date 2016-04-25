<?php
namespace Common\Logic;

use Think\Model;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-29
 * Time: 下午4:40
 */
class OrderCacheLogic extends Model
{
	/**
	 * 获取计价
	 * @param null $name 可以获取指定键值
	 *
	 * @return mixed|string
	 */
	static public function billing_get($name = null)
	{
		$order_price = S("billing");
		if (!$order_price) {
			$order_price = M("Billing")->field("free,define,last")->find();
			S("billing", $order_price);
		}

		$order_price['define'] = explode("&", $order_price['define']);
		foreach ($order_price['define'] as $key => $value) {
			$order_price["phase_" . ($key + 1)] = explode("-", $value);
		}

		unset($order_price['define']);

		if (is_null($name)) {
			return $order_price;
		} elseif (is_numeric($name) && isset($order_price["phase_" . $name])) {
			return $order_price["phase_" . $name];
		} elseif (isset($order_price[$name])) {
			return $order_price[$name];
		} else {
			return "";
		}
	}
}