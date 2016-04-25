<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class RechargeController extends CommonController
{
	const STATUS_RECHARGE_SUCCESS = 2;

	/*
	 * 列表处理
	 */
	protected function _processer(&$volist)
	{
		foreach ($volist as $key => &$value) {
			$value['amount'] /= 100;
			$value['receive_amount'] /= 100;
			$value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
		}
	}

	// 查询条件
	protected $_where_fields = array(
		'eq' => array('recharge_id', 'mobile')
	);

	/*
	 * 反馈建议列表
	 */
	public function index()
	{
		// 列表过滤
		$this->_index_where['ft_recharge.status'] = self::STATUS_RECHARGE_SUCCESS;

		// 配置查询条件
		$model_conf = array(
			'table' => array('recharge', 'users'),
			'join' => 'left',
			'on' => array('user_id', 'id'),
			'field' => array(
				array('recharge_id', 'amount', 'receive_amount', 'create_time'),
				array('mobile')
			),
			'order' => 'create_time desc'
		);
		// 列表
		$this->_join_list($model_conf, $this->_index_where, $this->_index_param);

		// 模版显示
		$this->display();
	}
}