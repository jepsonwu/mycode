<?php
namespace V3\Controller;

use Think\Controller;

class ImportController extends Controller
{
	// 资金流模式（加）
	const MODE_FUND_FLOW_PLUS = 1;
	// 资金流类型
	const TYPE_FUND_FLOW_REWARD = 3;
	// 系统奖励类型
	const TYPE_SYSTEM_REWARD = 1;

	// 每次执行条数
	private $each_execute_count = 1000;
	// 充值金额
	private $recharge_amount = 1500;
	// 白名单充值金额
	private $white_recharge_amount = 20000;
	// 处理失败用户列表
	private $failed_user_list = array();
	// 处理成功用户总数
	private $success_user_count = 0;

	/**
	 * 老用户充值
	 */
	public function index()
	{
		// 用户条件
		$user_cond = array(
			'status' => 1,
			'type' => 0
		);
		// 用户总数
		$count = M('Users')->where($user_cond)->count();
		
		// 循环次数
		$loop_count = ceil($count / $this->each_execute_count);
		if ($loop_count > 1) {
			for ($i = 0; $i < $loop_count; $i++) {
				// 起始位置
				$start_position = $i * $this->each_execute_count;
				// 充值
				self::recharge($start_position, $this->each_execute_count);
			}
		} else {
			// 充值
			self::recharge(0, $count);
		}

		// 输出处理结果
		$execute_result = array(
			'user_count' => $count,
			'failed_user_list' => json_encode($this->failed_user_list),
			'success_user_count' => $this->success_user_count
		);
		echo json_encode($execute_result);
	}

	/**
	 * 充值
	 * @param  int $start 起始位置
	 * @param  int $rows 查询条数
	 */
	private function recharge($start, $rows)
	{
		// 用户条件
		$user_cond = array(
			'status' => 1,
			'type' => 0
		);
		$user_info = M('Users')->where($user_cond)->field('id,white')->limit($start, $rows)->select();
		foreach ($user_info as $user) {
			// 开启事务
			$model = new \Think\Model();
			$model->startTrans();

			// 获取充值金额
			$balance = $user['white'] == '1' ? $this->white_recharge_amount : $this->recharge_amount;
			// 更新余额
			$user_result = M('Users')->where("id={$user['id']}")->setInc('balance', $balance);

			// 更新成功
			if ($user_result) {
				// 添加系统充值记录
				$system_recharge_record = array(
					'user_id' => $user['id'],
					'amount' => $balance,
					'type' => self::TYPE_SYSTEM_REWARD,
					'manager' => 'system',
					'create_time' => time()
				);
				$system_recharge_id = M('SystemRechargeHistory')->add($system_recharge_record);

				// 添加成功
				if ($system_recharge_id) {
					// 添加用户资金流
					$fund_data = array(
						'user_id' => $user['id'],
						'relation_id' => $system_recharge_id,
						'mode' => self::MODE_FUND_FLOW_PLUS,
						'type' => self::TYPE_FUND_FLOW_REWARD,
						'amount' => $balance,
						'create_time' => time()
					);
					$fund_result = M('UserFundFlow')->add($fund_data);
					// 添加成功
					if ($fund_result) {
						$this->success_user_count++;
						$model->commit();
					} else {
						$this->failed_user_list[] = $user['id'];
						$model->rollback();
						continue;
					}
				} else {
					$this->failed_user_list[] = $user['id'];
					$model->rollback();
					continue;
				}
			} else {
				$this->failed_user_list[] = $user['id'];
				continue;
			}
		}
	}

}