<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class WagesController extends CommonController {
	
	// 工资记录列表配置
	protected $list_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 工资记录列表
	 */
	public function list_get_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化工资模型
		$wages_model = M(CONTROLLER_NAME);
		$wages_info = $wages_model->where('uid='.$user_id)->field('id,checkout_date,status,real_amount')
			->page($this->page,$this->listrows)->order('checkout_date desc')->select();
		// 查询失败
		if ($wages_info === false) $this->DResponse(500);
		// 无工资记录
		if (empty($wages_info)) $this->successReturn();
		// 返回结果
		array_walk($wages_info, function(&$val){
			$val['real_amount'] = $val['real_amount']/100;
		});
		$result['list'] = $wages_info;
		// 记录总数
		$count = $wages_model->where('uid='.$user_id)->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	/**
	 * 工资详情
	 */
	public function read_get_html() {
		// 获取工资ID
		$wages_id = I('get.wages_id');
		// 实例化工资模型
		$wages_model = M(CONTROLLER_NAME);
		$wages_info = $wages_model->getById($wages_id);
		// 查询失败
		if ($wages_info === false) $this->DResponse(500);
		// 返回结果
		$this->successReturn($wages_info);
	}
	
	// 当前工资状况配置
	protected $current_get_html_conf = array(
			'check_user' => true
	);
	
	// 获取当前工资状况
	public function current_get_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 本周一
		$current_mon = strtotime('last sunday +1 day');
		$map['paid_time'] = array('between', $current_mon.','.time());
		$map['tid'] = $user_id;
		$map['status'] = array('in', C('ORDERS_STATUS.COMMENT').','.C('ORDERS_STATUS.DONE'));
		// 实例化订单模型
		$order_model = M('Orders');
		$week_wages = $order_model->where($map)->sum('total_amount');
		// 查询失败
		if ($week_wages === false) $this->DResponse(500);
		// 获取工资
		$result['week_wages'] = $week_wages/100;
		// 实例化工资模型
		$wages_model = M(CONTROLLER_NAME);
		$accumulate_amount = $wages_model->where('uid='.$user_id)->order('checkout_date desc')->getField('accumulate_amount');
		// 查询失败
		if ($accumulate_amount === false) $this->DResponse(500);
		// 获取累积金额
		$result['accumulate_amount'] = $accumulate_amount/100;
		// 结账日
		$result['checkout_date'] = strtotime('next sunday');
		// 打款日
		$result['balance_time'] = date('w') == '0'?strtotime('next monday +1 week'):strtotime('next monday');
		$this->successReturn($result);
	}
	
}