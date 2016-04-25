<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;
use Think\Cache\Driver\Redis;

/**
 *订单计费管理
 */
class BillingController extends CommonController
{
	public function index()
	{
		$billing_info = M("Billing")->find();
		if ($billing_info) {
			$billing_info['last'] = $billing_info['last'] / 100;
			$billing_info['define'] = explode("&", $billing_info['define']);
			foreach ($billing_info['define'] as $key => $val) {
				$val = explode("-", $val);
				$billing_info['phase_' . ($key + 1)] = $val[0] . '-' . $val[1] / 100;
			}
		}

		$this->assign("billing_info", $billing_info);
		$this->display();
	}

	public function save()
	{
		$info = I("post.");

		$data = array(
			"free" => intval($info['free']),
			"last" => intval($info['last'] * 100),
			"define" => ""
		);

		//处理数据
		foreach ($info as $key => $value) {
			if (strpos($key, "_") !== false) {
				$value = explode("-", $value);
				$value[0] = intval($value[0]);
				$value[1] = intval($value[1] * 100);

				$data['define'][substr($key, strpos($key, "_") + 1)] = $value[0] . "-" . $value[1];
			}
		}

		ksort($data['define']);
		$data['define'] = implode("&", $data['define']);

		$result = M("Billing")->where("id=1")->save($data);
		if ($result > 0) S("billing_rule", $data);

		if ($result === false)
			parent::fReturn("编辑失败");
		else
			parent::sReturn("编辑成功");
	}
}