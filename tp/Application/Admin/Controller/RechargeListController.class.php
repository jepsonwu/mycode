<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class RechargeListController extends CommonController
{
	// 金额列表元素个数
	const RECHARGE_LIST_COUNT = 5;

	/**
	 * 首页
	 */
	public function index()
	{
		// 获取充值金额列表
		$recharge_list = M('RechargeList')->field('amount,receive_amount,description')
			->order('amount')->limit(5)->select();
		if (!empty($recharge_list)) {
			array_walk($recharge_list, function(&$val){
				$val['amount'] /= 100;
				$val['receive_amount'] /= 100;
			});
			$this->assign('recharge_list', $recharge_list);
		}
		$this->assign('recharge_list_count', self::RECHARGE_LIST_COUNT);
		// 显示页面
		$this->display();
	}

	/**
	 * 保存
	 */
	public function save()
	{
		// 获取请求信息
		$data = I('post.');
		for($i = 0; $i < self::RECHARGE_LIST_COUNT; $i++) {
			$recharge_list[$i] = array(
				'amount' => $data["amount{$i}"] * 100,
				'receive_amount' => $data["receive_amount{$i}"] * 100,
				'description' => $data["description{$i}"]
			);
		}
		// 获取充值列表
		$recharge_list_db = M('RechargeList')->find();
		if (empty($recharge_list_db)) {
			// 批量新增
			$result = M('RechargeList')->addAll($recharge_list);
			if ($result) {
				$this->ajaxReturn(make_url_rtn('保存成功!'));
			} else {
				$this->ajaxReturn(make_url('保存失败!'));
			}
		} else {
			// 清空充值金额列表数据
			$del_result = M('RechargeList')->where('1')->delete();
			if ($del_result === false) {
				$this->ajaxReturn(make_url('保存失败!'));
			} else {
				// 批量新增
				$add_result = M('RechargeList')->addAll($recharge_list);
				if ($add_result) {
					$this->ajaxReturn(make_url_rtn('保存成功!'));
				} else {
					$this->ajaxReturn(make_url('保存失败!'));
				}
			}
		}
	}
}