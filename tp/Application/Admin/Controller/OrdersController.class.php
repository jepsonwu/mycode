<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 *订单管理
 */
class OrdersController extends CommonController
{
	//订单状态
	const ORDERS_STATUS_CLOSE = 0;
	const ORDERS_STATUS_DONE = 4;

	protected $_where_fields = array(
		"eq" => array("status", "order_id"),
		"bet" => array("create_start_time",
			"create_end_time",
			"paid_start_time",
			"paid_end_time",
			"total_start_amount",
			"total_end_amount",
		),
		"like" => array(),
	);

	protected function _filter()
	{
		parent::_filter();

		//自定义
		if (isset($this->_request['mobile']) && $this->_request['mobile']) {
			$user_id = M("Users")->where("mobile='{$this->_request['mobile']}'")->getField("id");
			if ($user_id) {
				$this->_index_param['mobile'] = $this->_request['mobile'];
				$this->_index_where['_string'] = "sid={$user_id} OR tid={$user_id}";
				$this->assign('mobile', $this->_request['mobile']);
			}
		}

		foreach (array("called_start_time", "called_end_time") as $key) {
			$value = explode("_", $key);

			if (isset($this->_request[$key]) && $this->_request[$key] != "") {
				$this->_index_param[$key] = $this->_request[$key];

				$this->_index_where[$value[0] . "_" . $value[2]][] = array(
					$value[1] == "start" ? "egt" : "elt",
					$value[2] = $this->_request[$key]
				);
				$this->assign($key, $this->_request[$key]);
			}
		}

	}

	public function index()
	{
		$this->assign("order_status", C("ORDERS_STATUS"));

		$this->_list(M("Orders"), $this->_index_where, $this->_index_param, "id", "create_time");
		$this->display();
	}

	protected function _processer(&$volist)
	{
		$user_id = array();
		foreach ($volist as &$value) {
			//记录user_id
			$user_id[] = $value['sid'];
			$user_id[] = $value['tid'];

			$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['status_show'] = C("ORDERS_STATUS." . $value['status']);
			$value['total_amount'] = $value['total_amount'] / 100;
			$value['paid_amount'] = $value['paid_amount'] / 100;
			$value['coupon_amount'] = $value['coupon_amount'] / 100;
			$value['billing_time'] = floor($value['called_time'] / 60);
		}
		unset($value);

		//用户昵称
		$user_id = array_unique($user_id);

		$user_info = array();
		$result = M("Users")->where(array("id" => array("in", $user_id)))->field("id,mobile")->select();
		foreach ($result as $value) {
			$user_info[$value['id']] = $value['mobile'];
		}

		foreach ($volist as &$value) {
			$value['sname'] = isset($user_info[$value['sid']]) ? $user_info[$value['sid']] : "";
			$value['tname'] = isset($user_info[$value['tid']]) ? $user_info[$value['tid']] : "";
		}
	}

	/**
	 * 关闭订单
	 * @return [type] [description]
	 */
	public function close()
	{
		$order_id = I("get.order_id");

		$model = M("Orders");
		$status = $model->where("order_id='{$order_id}'")->getField("status");
		!$status && parent::fReturn('没有该订单!');
		!$this->is_close($status) && parent::fReturn('订单不允许关闭!');

		//todo 增加订单操作日志
		$result = M("Orders")->where("order_id='{$order_id}'")->save(array("status" => 0));
		if ($result === false)
			parent::fReturn('关闭失败!');
		else
			parent::sReturn('关闭成功!');
	}

	/**
	 * 判断订单是否允许关闭
	 * @param  [type]  $status [description]
	 * @return boolean         [description]
	 */
	protected function is_close($status)
	{
		return !in_array($status, array(
			self::ORDERS_STATUS_CLOSE,
			self::ORDERS_STATUS_DONE
		));
	}
}