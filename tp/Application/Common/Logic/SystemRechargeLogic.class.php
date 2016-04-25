<?php
namespace Common\Logic;

class SystemRechargeLogic
{
	// 系统充值类型（奖励）
	const TYPE_SYSTEM_RECHARGE_REWARD = 1;
	// 资金流模式（加）
	const MODE_FUND_FLOW_PLUS = 1;
	// 资金流类型（系统奖励）
	const TYPE_FUND_FLOW_REWARD = 3;

	/**
	 * 系统充值Logic
	 * @param  int $user_id 用户ID
	 * @param  int $amount  充值金额
	 * @return boolean      是否充值成功
	 */
	public static function systemRecharge($user_id, $amount)
	{
		// 更新用户余额
		$user_result = M('Users')->where("id={$user_id}")->setInc('balance', $amount);
		// 更新失败
		if ($user_result === false) return false;
		// 开启事务
		$model = new \Think\Model();
		$model->startTrans();
		// 添加系统充值记录
		$system_recharge_record = array(
			'user_id' => $user_id,
			'amount' => $amount,
			'type' => self::TYPE_SYSTEM_RECHARGE_REWARD,
			'manager' => 'system',
			'create_time' => time()
		);
		$system_recharge_id = M('SystemRechargeHistory')->add($system_recharge_record);
		// 插入失败
		if ($system_recharge_id === false) {
			// 回滚
			$model->rollback();
			return false;
		} else {
			// 添加用户资金流
			$fund_data = array(
				'user_id' => $user_id,
				'relation_id' => $system_recharge_id,
				'mode' => self::MODE_FUND_FLOW_PLUS,
				'type' => self::TYPE_FUND_FLOW_REWARD,
				'amount' => $amount,
				'create_time' => time()
			);
			$fund_result = M('UserFundFlow')->add($fund_data);
			if ($fund_result === false) {
				// 回滚
				$model->rollback();
				return false;
			} else {
				// 提交
				$model->commit();

				// 更新缓存
				$users = S('users');
				if (!empty($users) && isset($users[$user_id])) {
					$users[$user_id]['balance'] += $amount;
					S('users', $users);
				}
				return ture;
			}
		}
	}
}