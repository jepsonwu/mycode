<?php
namespace V3\Controller;

use V3\Controller\CommonController;
use Think\Log;
use Common\Logic\SystemRechargeLogic;

class RechargeController extends CommonController
{
	// 充值成功状态
	const STATUS_RECHARGE_SUCCESS = 2;
	// 充值待处理状态
	const STATUS_RECHARGE_PENDING = 1;
	// 资金流模式（加）
	const MODE_FUND_FLOW_PLUS = 1;
	// 资金流类型（充值）
	const TYPE_FUND_FLOW_RECHARGE = 1;
	// 资金流类型（奖励）
	const TYPE_FUND_FLOW_REWARD = 3;
	// 资金流类型（待处理）
	const TYPE_FUND_FLOW_PENDING = 4;
	// 系统充值类型
	const TYPE_SYSTEM_RECHARGE_REWARD = 1;

	/**
	 * 充值列表
	 * @return json $charge_list
	 */
	public function recharge_list_get_html()
	{
		// 实例化充值列表模型
		$model = M('RechargeList');
		// 查询结果
		$charge_list = $model->where('status=1')->field('amount,receive_amount,description')->order('amount')->select();
		// 查询失败
		if ($charge_list === false) $this->DResponse(500);
		// 返回结果
		$result['total'] = count($charge_list);
		$result['list'] = $charge_list;
		$this->successReturn($result);
	}

	// 获取充值对象配置
	protected $pay_get_html_conf = array(
		'check_user' => ture,
		'check_fields' => array(
			array('amount', 'number', 'AMOUNT_IS_INVALID', 1),
			array('receive_amount', 'number', 'RECEIVE_AMOUNT_IS_INVALID', 0),
			array('channel', 'alipay', 'CHANNEL_IS_INVALID', 1, 'in')
		)
	);

	/**
	 * 获取充值对象
	 * @return json $result
	 */
	public function pay_get_html()
	{
		// 获取用户ID,充值金额,赠送金额
		$user_id = USER_ID;
		$amount = $this->amount;
		$receive_amount = $this->receive_amount;
		// 验证金额有效性
		if ($amount <= 0) $this->failReturn(C('AMOUNT_IS_INVALID'));
		if (!empty($receive_amount)) {
			// 查询条件
			$amount_cond = array(
				'amount' => $amount,
				'receive_amount' => $receive_amount
			);
			$amount_valid = M('RechargeList')->where($amount_cond)->getField('id');
			if (empty($amount_valid)) $this->failReturn(C('RECEIVE_AMOUNT_IS_INVALID'));
		} else {
			$receive_amount = 0;
		}
		// 获取充值ID
		$recharge_id = create_recharge_id();

		// 创建充值记录
		$recharge_data = array(
			'user_id' => $user_id,
			'amount' => $amount,
			'receive_amount' => $receive_amount,
			'recharge_id' => $recharge_id,
			'create_time' => time()
		);
		$insert_result = M('Recharge')->add($recharge_data);
		// 插入失败
		if ($insert_result === false) $this->DResponse(500);

		// 获取支付对象
		$order_info = array(
			'order_no' => $recharge_id,
			'amount' => $amount
		);
		$charge_object = self::getChargeObject($this->channel, $order_info);
		// 请求失败
		if (!$charge_object) $this->DResponse(500);
		// 返回支付对象
		$this->successReturn($charge_object);
	}

	/**
	 * Ping++ Webhooks 通知
	 * @return http status code
	 */
	public function notify_post_html()
	{
		// 判断header是否包含签名
		if (!isset($_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE'])) $this->DResponse(500);

		// 获取请求数据
		$this->request = file_get_contents('php://input');
		$sign = $_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE'];
		// 验证签名
		$verify_result = \Org\Authorize\RsaAuthorize::rsaVerify($this->request,
			C("PINGPP.RSA_PUBLIC_KEY_PATH"), $sign, OPENSSL_ALGO_SHA256);

		// 验证成功
		if ($verify_result) {
			// 解析请求数据
			$this->request = json_decode($this->request, true);
			$type = $this->request['type'];
			$request = $this->request['data']['object'];
			// 记录日志
			Log::record("recharge callback.[recharge_id: {$request['order_no']}]");

			// 支付成功
			if ($type == 'charge.succeeded') {
				// 获取充值信息
				$recharge_info = M('Recharge')->where("recharge_id={$request['order_no']}")
					->field('id,user_id,receive_amount')->find();
				if (empty($recharge_info)) $this->DResponse(500);

				// 开启事物
				$model = new \Think\Model();
				$model->startTrans();
				// 更新预充值表
				$update_recharge = M('Recharge')->where("recharge_id={$request['order_no']}")
					->save(array('status' => self::STATUS_RECHARGE_SUCCESS));
				// 更新失败
				if ($update_recharge === false) {
					// 记录日志
					Log::record('Recharge model update failed.[' . M('Recharge')->getDbError() . ']');
					$this->DResponse(500);
				}

				// 更新用户表
				$balance = $request['amount'] + $recharge_info['receive_amount'];
				$update_users = M('Users')->where("id={$recharge_info['user_id']}")->setInc('balance', $balance);
				// 更新失败
				if ($update_users === false) {
					// 回滚
					$model->rollback();
					// 记录日志
					Log::record('Users model update failed.[' . M('Users')->getDbError() . ']');
					$this->DResponse(500);
				} else {
					// 查看是否为待处理记录
					$pending_cond = array(
						'user_id' => $recharge_info['user_id'],
						'relation_id' => $recharge_info['id'],
						'type' => self::TYPE_FUND_FLOW_PENDING
					);
					$pending_record = M('UserFundFlow')->where($pending_cond)->getField('id');
					
					// 如果不存在
					if (empty($pending_record)) {
						// 新增资金流变动
						$fund_add_data = array(
							'user_id' => $recharge_info['user_id'],
							'relation_id' => $recharge_info['id'],
							'mode' => self::MODE_FUND_FLOW_PLUS,
							'type' => self::TYPE_FUND_FLOW_RECHARGE,
							'amount' => $request['amount'],
							'create_time' => time()
						);
						$fund_flow_result = M('UserFundFlow')->add($fund_add_data);
					} else {
						// 更新资金流数据
						$fund_save_data = array(
							'type' => self::TYPE_FUND_FLOW_RECHARGE,
							'update_time' => time()
						);
						$fund_flow_result = M('UserFundFlow')->where($pending_cond)->save($fund_save_data);
					}
					// 新增失败
					if ($fund_flow_result === false) {
						// 回滚
						$model->rollback();
						// 记录日志
						Log::record('UserFundFlow model operate failed.[' . M('UserFundFlow')->getDbError() . ']');
						$this->DResponse(500);
					} else {
						// 有赠送金额时
						if (!empty($recharge_info['receive_amount'])) {
							// 添加系统充值记录
							$system_recharge_record = array(
								'user_id' => $recharge_info['user_id'],
								'amount' => $recharge_info['receive_amount'],
								'type' => self::TYPE_SYSTEM_RECHARGE_REWARD,
								'manager' => 'system',
								'create_time' => time()
							);
							$system_recharge_id = M('SystemRechargeHistory')->add($system_recharge_record);
							// 添加失败
							if ($system_recharge_id === false) {
								// 回滚
								$model->rollback();
								// 记录日志
								Log::record('SystemRechargeHistory model insert failed.[' . M('SystemRechargeHistory')->getDbError() . ']');
								$this->DResponse(500);
							} else {
								// 添加用户资金流
								$fund_receive_data = array(
									'user_id' => $recharge_info['user_id'],
									'relation_id' => $system_recharge_id,
									'mode' => self::MODE_FUND_FLOW_PLUS,
									'type' => self::TYPE_FUND_FLOW_REWARD,
									'amount' => $recharge_info['receive_amount'],
									'create_time' => time()
								);
								$fund_result = M('UserFundFlow')->add($fund_receive_data);
								if ($fund_result === false) {
									// 回滚
									$model->rollback();
									// 记录日志
									Log::record('UserFundFlow model insert failed.[' . M('UserFundFlow')->getDbError() . ']');
									$this->DResponse(500);
								}
							}
						}
						
						// 提交
						$model->commit();
						// 获取用户缓存
						$users = S('users');
						if(!empty($users) && isset($users[$recharge_info['user_id']])) {
							$users[$recharge_info['user_id']]['balance'] += $balance;
							// 更新缓存
							S('users', $users);
						}
						$this->DResponse(200);
					}
				}
			} else {
				// 记录日志
				Log::record("charge failed.[type: {$type}]");
				$this->DResponse(500);
			}
		} else {
			// 记录日志
			Log::record("verify failed.[request: {$this->request}|sign: {$sign}]");
			$this->DResponse(500);
		}
	}

	// 获取充值列表配置
	protected $list_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);

	/**
	 * 获取充值列表
	 * @return json $result
	 */
	public function list_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 查询条件
		$recharge_cond = array(
			'user_id' => $user_id,
			'status' => self::STATUS_RECHARGE_SUCCESS
		);
		// 查询结果
		$recharge_info = M('Recharge')->where($recharge_cond)->field('amount, create_time')
			->page($this->page, $this->listrows)->order('create_time desc')->select();
		// 查询失败
		if ($recharge_info === false) $this->DResponse(500);
		// 结果集为空
		if (empty($recharge_info)) $this->successReturn();
		// 返回结果
		$count = M('Recharge')->where($recharge_cond)->count();
		$result['total'] = (int)$count;
		$result['list'] = $recharge_info;
		$this->successReturn($result);
	}

	// 客户端回调(支付成功)配置
	protected $sync_notify_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('recharge_id', 'number', 'RECHARGE_ID_IS_INVALID', 1)
		)
	);

	/**
	 * 客户端回调(支付成功)
	 * @return json $result
	 */
	public function sync_notify_post_html()
	{
		// 获取充值ID与用户ID
		$recharge_id = $this->recharge_id;
		$user_id = USER_ID;
		// 获取充值信息
		$recharge_info = M('Recharge')->where("recharge_id={$recharge_id}")->field('id,amount,status')->find();
		// 获取失败
		if ($recharge_info === false) $this->DResponse(500);

		// 待处理状态
		if ($recharge_info['status'] == self::STATUS_RECHARGE_PENDING) {
			// 用户资金流插入待处理数据
			$fund_data = array(
				'user_id' => $user_id,
				'relation_id' => $recharge_info['id'],
				'mode' => self::MODE_FUND_FLOW_PLUS,
				'type' => self::TYPE_FUND_FLOW_PENDING,
				'amount' => $recharge_info['amount'],
				'create_time' => time()
			);
			$fund_result = M('UserFundFlow')->add($fund_data);
			// 插入失败
			if ($fund_result === false) $this->DResponse(500);
		}
		// 返回成功
		$this->successReturn();
	}

	// 系统充值配置
	protected $recharge_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('amount', 'number', 'AMOUNT_IS_INVALID', 1),
		)
	);

	/**
	 * 系统充值
	 * @return json $result
	 */
	public function recharge_post_html()
	{
		// 获取用户ID与充值金额
		$user_id = USER_ID;
		$amount = $this->amount;
		// 验证金额有效性
		if ($amount <= 0) $this->failReturn(C('AMOUNT_IS_INVALID'));

		// 充值
		$result = SystemRechargeLogic::systemRecharge($user_id, $amount);
		// 充值失败
		if (!$result) $this->failReturn(C('RECHARGE_FAILED'));

		// 更新缓存
		$users = S('users');
		if(isset($users[$user_id]['balance'])) {
			$users[$user_id]['balance'] += $amount;
			S('users', $users);
		}

		// 充值成功，返回结果
		$this->successReturn();
	}

	/**
	 * Ping++获取支付对象
	 * @param $channel
	 * @param $order_info
	 * @return \Pingpp\Charge|string
	 */
	private function getChargeObject($channel, $order_info)
	{
		// 设置API-Key
		\Pingpp\Pingpp::setApiKey(C("PINGPP.APP_KEY"));
		// 发送支付请求
		try {
			$return = \Pingpp\Charge::create(
				array(
					'subject' => C("PINGPP.SUBJECT"),
					'body' => C("PINGPP.BODY"),
					'amount' => $order_info['amount'],
					'order_no' => $order_info['order_no'],
					'currency' => 'cny',
					'channel' => $channel,
					'client_ip' => get_client_ip(),
					'app' => array('id' => C("RECHARGE.APP_ID"))
				)
			);
			// 解码返回值
			$return = json_decode($return, true);
		} catch (\Pingpp\Error\Base $e) {
			Log::record("get charge object failed.[order_id: {$order_info['order_id']}]" . PHP_EOL . $e->getHttpBody());
			$return = false;
		}

		return $return;
	}
}