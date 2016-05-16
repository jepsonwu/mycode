<?php

/**
 * 钱包充值
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午2:04
 */
class Api_WalletRechargeController extends Action_Api
{
	/**
	 * 充值业务产品信息
	 * @var array
	 */
	protected $product_info = array(
		"no" => "53",
		"name" => "充值",
		"desc" => "用户充值"
	);

	protected $_yee_conf;

	//支付方式
	const PAY_DEBIT = 0;
	const PAY_BIND = 1;

	public function init()
	{
		parent::init();
		$this->_yee_conf = DM_Controller_Front::getInstance()->getConfig()->yee;
	}

	protected $debitCardRechargeConf = array(
		"encrypt" => "rsa",
		"method" => "post",
		array("cardno", "number", "订单号有误！", DM_Helper_Filter::MUST_VALIDATE),  //判断
		array("idcard", "is_string", "身份证信息有误！", DM_Helper_Filter::EXISTS_VALIDATE, 'function', "", true),
		array("owner", "is_string", "真实姓名有误！", DM_Helper_Filter::EXISTS_VALIDATE, 'function', "", true),
		array("phone", "/[\d]{11}/", "手机号有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("amount", "number", "金额有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("terminalid", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_channel", "1", "支付渠道有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
		array("set_passwd", "0,1", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "0"),
	);

	/**
	 *储蓄卡充值
	 *
	 */
	public function debitCardRechargeAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			//$member_id = 745;

			//用户一个渠道只能绑定一张卡
			$bindCardModel = new Model_Wallet_WalletBindCard();
			$bind_id = $bindCardModel->getBindInfoByMP($member_id, $bindCardModel::BIND_CHANNEL_YEE,
				array("ValidityPeriod", "Status"));
			if ($bind_id && $bind_id['Status'] == $bindCardModel::BIND_STATUS_TRUE && $bind_id['ValidityPeriod'] >= time())
				throw new Exception("已经绑定银行卡");

			//如果存在绑卡记录只能已第一次为准
			$bankCardModel = new Model_Wallet_WalletBankCard();
			$bank_info = $bankCardModel->getCardInfoByNo($this->_param['cardno'], array("owner", "BID", "MemberID"));

			//必须走正规流程，不允许未获取卡片信息直接请求该接口
			if (!$bank_info)
				throw new Exception("无效的储蓄卡");

			//不允许其他用户使用已绑定卡片支付
			if ($bank_info['MemberID'] > 0 && $bank_info['MemberID'] != $member_id)
				throw new Exception("该卡已被绑定");

			//以会员表RealName为准
			$memberModel = new DM_Model_Account_Members();
			$memberModel->deleteCache($member_id);
			$member_info = $memberModel->getMemberInfoCache($member_id, array("RealName", "IDCard"));

			if (!empty($member_info['RealName']))
				$this->_param['owner'] = $member_info['RealName'];
			else
				if (empty($this->_param['owner']))
					throw new Exception("真实姓名填写错误");

			if (!empty($member_info['IDCard']))
				$this->_param['idcard'] = $member_info['IDCard'];
			else
				if (empty($this->_param['idcard']))
					throw new Exception("身份证号不能为空");

			//金额大于0
			if ($this->_param['amount'] <= 0)
				throw new Exception("金额必须大于零");

			//先存入缓存异步回调存入数据库
//			if (empty($bank_info['owner'])) {
			$redis = DM_Module_Redis::getInstance();
			$res = $redis->setex("card_info:{$bank_info['BID']}", 3600 * 48, json_encode(array(
				"IdcardType" => 1,
				"Idcard" => $this->_param['idcard'],
				"Owner" => $this->_param['owner'],
				"Phone" => $this->_param['phone'],
			)));

			if ($res === false)
				throw new Exception("充值失败");
//			}

			$orderModel = new DM_Model_Table_Finance_Order();

			//生成充值订单交易记录
			$order_id = $orderModel->getOrderSn();
			//第三方订单号 订单号-卡号ID-是否设置密码
			$third_order_id = "{$order_id}-" . self::PAY_DEBIT . "{$bank_info['BID']}-{$this->_param['set_passwd']}";

			//第三方支付
			switch ($this->_param['pay_channel']) {
				case "1":
					//绑卡支付
					$post = array(
						"merchantaccount" => $this->_yee_conf->merchant_account,//商户账户编号
						"cardno" => $this->_param['cardno'],//卡号
						"idcardtype" => '01',//证件类型
						"idcard" => $this->_param['idcard'],//证件号
						"owner" => $this->_param['owner'],//持卡人姓名
						"phone" => $this->_param['phone'],//手机号
						"orderid" => $third_order_id,//订单号
						"transtime" => time(),//交易时间
						"currency" => 156,//币种
						"amount" => intval($this->_param['amount']),//金额分
						"productcatalog" => $this->_yee_conf->product_no,//商品类别码
						"productname" => $this->product_info['name'],//商品名称
						"productdesc" => $this->product_info['desc'],//描述
						"identityid" => (string)$member_id,//用户标识
						"identitytype" => 0,//用户标识类型
						"terminaltype" => 2,
						"terminalid" => $this->_param['terminalid'],//终端标识
						"userip" => $this->getRequest()->getClientIp(),//ip
						"callbackurl" => $this->getFullHost() . "/api/" . $this->_yee_conf->callback_url,//回调地址
					);

					$return = DM_Third_Yee_Yee::getInstance()->apiPost($this->_yee_conf->debit_pay_url, $post);

					//otherParamsNeed
					if (isset($return['otherParamsNeed']))
						throw new Exception($return['otherParamsNeed'] . "缺失");
			}

			$this->sendMsgAction($third_order_id);

			parent::succReturn(array("orderno" => $third_order_id));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $bindCardRechargeConf = array(
		"encrypt" => "rsa",
		"method" => "post",
		array("bcid", "number", "邦卡ID有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("amount", "number", "金额有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("terminalid", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_channel", "1", "支付渠道有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
	);

	/**
	 * 绑卡充值
	 */
	public function bindCardRechargeAction()
	{
		try {
			$this->isLoginOutput();
			$member_id = $this->memberInfo->MemberID;

			$bindCardModel = new Model_Wallet_WalletBindCard();
			$bind_card_info = $bindCardModel->getInfoByID($this->_param['bcid']);
			if (!$bind_card_info || $bind_card_info['BID'] < 1)
				throw new Exception("绑卡信息不存在");

			if ($bind_card_info['MemberID'] != $member_id)
				throw new Exception("绑卡信息不存在");

			if ($bind_card_info['ValidityPeriod'] < time())
				throw new Exception("绑卡已过期");

			//金额大于0
			if ($this->_param['amount'] <= 0)
				throw new Exception("金额必须大于零");

			$orderModel = new DM_Model_Table_Finance_Order();
			//生成充值订单交易记录  没有支付成功的订单可以继续支付 例如填错银行卡信息
			$order_id = $orderModel->getOrderSn();

			$third_order_id = "{$order_id}-" . self::PAY_BIND . "{$this->_param['bcid']}-0";
			switch ($this->_param['pay_channel']) {
				case "1":
					//绑卡支付
					$post = array(
						"merchantaccount" => $this->_yee_conf->merchant_account,//商户账户编号
						"bindid" => $bind_card_info['BindID'],
						"orderid" => $third_order_id,//订单号
						"transtime" => time(),//交易时间
						"currency" => 156,//币种
						"amount" => intval($this->_param['amount']),//金额 分
						"productcatalog" => $this->_yee_conf->product_no,//商品类别码
						"productname" => $this->product_info['name'],//商品名称
						"productdesc" => $this->product_info['desc'],//描述
						"identityid" => (string)$member_id,//用户标识
						"identitytype" => 0,//用户标识类型
						"terminaltype" => 2,
						"terminalid" => $this->_param['terminalid'],//终端标识
						"userip" => $this->getRequest()->getClientIp(),//ip
						"callbackurl" => $this->getFullHost() . "/api/" . $this->_yee_conf->callback_url,//回调地址
					);

					DM_Third_Yee_Yee::getInstance()->apiPost($this->_yee_conf->bind_pay_url, $post);
			}

			$this->sendMsgAction($third_order_id);

			parent::succReturn(array("orderno" => $third_order_id));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 确认支付
	 * @param $order_id
	 * @param string $validate_code
	 */
	private function confirmRechargeAction($order_id, $validate_code = "")
	{
		//确认支付
		$post = array(
			"merchantaccount" => $this->_yee_conf->merchant_account,//商户账户编号
			"orderid" => $order_id,
			"validatecode" => $validate_code
		);

		DM_Third_Yee_Yee::getInstance()->apiPost($this->_yee_conf->confirm_recharge, $post);
	}

	/**
	 * 发送验证码
	 * @param $order_id
	 */
	private function sendMsgAction($order_id)
	{
		$post = array(
			"merchantaccount" => $this->_yee_conf->merchant_account,//商户账户编号
			"orderid" => $order_id
		);

		DM_Third_Yee_Yee::getInstance()->apiPost($this->_yee_conf->send_msg, $post);
	}

	protected $sendMsgAgainConf = array(
		"method" => "post",
		array("orderno", "require", "订单号有误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 重复发送验证码
	 *
	 */
	public function sendMsgAgainAction()
	{
		$this->isLoginOutput();
		try {
			$this->sendMsgAction($this->_param['orderno']);
			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $msgConfirmRechargeConf = array(
		"method" => "post",
		array("orderno", "require", "订单号有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("validate_code", "/[\d]{6}/", "验证码格式有误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 需要验短信证码的确认支付
	 *
	 */
	public function msgConfirmRechargeAction()
	{
		$this->isLoginOutput();
		try {
			$this->confirmRechargeAction($this->_param['orderno'], $this->_param['validate_code']);
			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage(), "-" . $e->getCode());
		}
	}

	protected $payAsyncNotifyConf = array(
		"method" => "post",
		array("data", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("encryptkey", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 充值异步回调接口
	 */
	public function payAsyncNotifyAction()
	{
		//log
		$fp = fopen(APPLICATION_PATH . "/data/log/" . date("Y-m-d") . ".pay_async_notify.log", "a", false);
		$writer = new Zend_Log_Writer_Stream($fp);
		$logger = new Zend_Log($writer);

		$funds = new DM_Model_Table_Finance_Funds();
		$funds->tBegin();
		try {
			$return = DM_Third_Yee_Yee::getInstance()->callback($this->_param['data'], $this->_param['encryptkey']);

			$logger->log("\n" . json_encode($return), Zend_Log::INFO);
			//解析订单号
			$return['orderid'] = explode("-", $return['orderid']);

			//解析邦卡ID  0是储蓄卡支付  1是邦卡支付
			$pay_type = $return['orderid'][1]{0};
			$bind_id = 0;
			$return['orderid'][1] = substr($return['orderid'][1], 1);
			if ($pay_type == self::PAY_BIND)
				$bind_id = $return['orderid'][1];

			//是否发送消息，设置支付密码成功和充值失败发送
			$is_mess = false;

			//防止返回成功第三法未接收
			$orderRechargeModel = new Model_Wallet_WalletOrderRecharge();
			$order_info = $orderRechargeModel->getInfoByOrderNo($return['orderid'][0], "OrderNo");
			if ($order_info)
				exit("success");

			//创建订单
			$orderModel = new DM_Model_Table_Finance_Order();
			if ($return['status'] == 1) {
				$oid = $orderModel->createOrder(
					$return['identityid'], $orderModel::AMOUNT_TYPE_IN, $return['orderid'][0], $orderModel::ORDER_TYPE_RECHARGE,
					$orderModel::CURRENCY_CNY, $return['amount'] / 100, $orderModel::ORDER_STATUS_DONE,
					$orderModel::ORDER_ROLE_SENDER, 0, 0, $this->_yee_conf->product_no,
					"{$return['bank']}（{$return['lastno']}）快捷充值"
				);
				if ($oid === false)
					throw new Exception("创建订单失败");

				//修改零钱
				$res = $funds->modifyAmount($return['identityid'], $orderModel::CURRENCY_CNY, $return['amount'] / 100,
					$orderModel::AMOUNT_TYPE_IN, $orderModel::ORDER_TYPE_RECHARGE, 0);
				if ($res === false)
					throw new Exception("修改零钱失败");

				if ($pay_type == self::PAY_DEBIT) {
					//添加绑卡信息
					$bindCardModel = new Model_Wallet_WalletBindCard();
					$bind_info = $bindCardModel->getBindInfoByMP($return['identityid'], $bindCardModel::BIND_CHANNEL_YEE,
						array("BCID", "Status", "ValidityPeriod"), null, $return['orderid'][1]);

					if (!$bind_info) {
						$res = $bindCardModel->insert(
							array(
								"MemberID" => $return['identityid'],
								"BID" => $return['orderid'][1],
								"PayChannel" => $bindCardModel::BIND_CHANNEL_YEE,
								"CreateTime" => date("Y-m-d H:i:s", time()),
								"Status" => 1,
								"BindID" => $return['bindid'],
								"ValidityPeriod" => $return['bindvalidthru'],
							)
						);
						if ($res === false)
							throw new Exception("绑卡信息修改失败");
						else
							$bind_id = $res;
					} elseif ($bind_info['Status'] == 0 || $bind_info['ValidityPeriod'] < time()) {
						$bind_id = $bind_info['BCID'];
						$res = $bindCardModel->update(
							array(
								"BindID" => $return['bindid'],
								"ValidityPeriod" => $return['bindvalidthru'],
								"Status" => 1
							), array("BCID =?" => $bind_info['BCID']));
						if ($res === false)
							throw new Exception("绑卡信息修改失败");
					}

					//修改使用该卡的真实姓名
					$bankCardModel = new Model_Wallet_WalletBankCard();
					$redis = DM_Module_Redis::getInstance();
					$card_info = $redis->get("card_info:{$return['orderid'][1]}");

					if (!empty($card_info)) {
						$card_info = json_decode($card_info, true);

						$memberModel = new DM_Model_Account_Members();
						$res = $memberModel->update(
							array("RealName" => $card_info['Owner'],
								"IDCard" => $card_info['Idcard']),
							array("MemberID =?" => $return['identityid']));

						if ($res === false)
							throw new Exception("修改会员真实姓名失败");
						$memberModel->deleteCache($return['identityid']);

						//修改卡片信息
						$card_info['MemberID'] = $return['identityid'];
						$res = $bankCardModel->update(
							$card_info,
							array("BID =?" => $return['orderid'][1])
						);
						if ($res === false)
							throw new Exception("修改卡片信息失败");
						else
							$redis->del("card_info:{$return['orderid'][1]}");
					}

					//是否设置支付密码
					if ($return['orderid'][2] == 1) {
						$walletModel = new Model_Wallet_Wallet();
						if (!$walletModel->confirmPayPassword($return['identityid']))
							throw new Exception("设置密码失败");
						$is_mess = true;
					}

				}
			}

			//创建订单详情
			if ($return['status'] == 0) {
				$status = $orderModel::ORDER_STATUS_FAILED;
				$remark = $return['errormsg'];
			} elseif ($return['status'] == 1) {
				$status = $orderModel::ORDER_STATUS_DONE;
				$remark = "充值成功";
			} else {
				$status = $orderModel::ORDER_STATUS_CLOSE;
				$remark = "充值关闭";
			}

			$r_id = $orderRechargeModel->insert(array(
				"OrderNo" => $return['orderid'][0],
				"BCID" => $bind_id,
				"BID" => $return['orderid'][1],
				"TOrderNo" => $return['yborderid'],
				"MemberID" => $return['identityid'],
				"Amount" => $return['amount'] / 100,
				"Status" => $status,
				"Remark" => $remark
			));

			if ($r_id === false)
				throw new Exception("创建充值订单详情失败");

			//成功时生成消息
			if ($return['status'] == 1 || $is_mess) {
				$messageModel = new Model_Message();
				$messageModel->addMessage($return['identityid'], Model_Message::MESSAGE_TYPE_RECHARGE, $r_id, Model_Message::MESSAGE_SIGN_WALLET);
			}

			$funds->tCommit();
			echo "success";
		} catch (Exception $e) {
			$funds->tRollBack();
			$logger->log($e->getMessage(), Zend_Log::INFO);
			echo "failed";
		}
	}

	protected $getRechargeLimitConf = array(
		"method" => "post",
		array("bcid", "number", "邦卡ID有误", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_channel", "1", "支付渠道有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
	);

	/**
	 * 获取用户银行卡本次限额，根据每月和每日限额计算，如果有余则按单日限额
	 */
	public function getRechargeLimitAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;

		//根据bcid找到银行信息 bank_code
		$bindModel = new Model_Wallet_WalletBindCard();
		$bind_info = $bindModel->getInfoByID($this->_param['bcid'], array("BID", "MemberID", "PayChannel"), null);
		if (!$bind_info || $bind_info['MemberID'] != $member_id || $bind_info['PayChannel'] != $this->_param['pay_channel'])
			parent::failReturn("不存在邦卡记录");

		$cardModel = new Model_Wallet_WalletBankCard();
		$bank_code = $cardModel->getCardInfoById($bind_info['BID'], "BankCode");

		//获取支付渠道对应银行卡限额信息
		$limit_name = "_bank_limit_" . $this->_param['pay_channel'];
		$limit_info = Model_Wallet_WalletBankCard::$$limit_name;
		$limit_info = $limit_info[(string)$bank_code];

		//每日
		$money = $limit_info['v_single'];

		//用户每月充值总额
		$is_day = false;
		if ($limit_info['v_month'] != 0) {
			$rechargeModel = new Model_Wallet_WalletOrderRecharge();
			//这里实现成本太高 todo 优化
			$month_total = $rechargeModel->getMonthTotalByMID($member_id, $this->_param['bcid'], date("Y-m", time()));

			if ($month_total >= $limit_info['v_month']) {
				$money = 0;
			} elseif ($limit_info['v_month'] - $month_total < $money) {
				$money = $limit_info['v_month'] - $month_total;
			} else {
				$is_day = true;
			}
		} else {
			$is_day = true;
		}

		//查看当日限额
		if ($limit_info['v_day'] != 0 && $is_day) {
			$rechargeModel = new Model_Wallet_WalletOrderRecharge();
			$day_total = $rechargeModel->getMonthTotalByMID($member_id, $this->_param['bcid'], date("Y-m-d", time()));

			if ($day_total >= $limit_info['v_day']) {
				$money = 0;
			} else {
				$day_total = $limit_info['v_day'] - $day_total;
				$day_total < $money && $money = $day_total;
			}

		}

		parent::succReturn(array("limit" => $money));
	}

	protected $getPayResultConf = array(
		'method' => 'post',
		array("orderno", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE)
	);

	/**
	 * 查询是否支付成功
	 */
	public function getPayResultAction()
	{
		$orderno = $this->_param['orderno'];
		$ordernoArr = explode('-', $orderno);
		$orderID = $ordernoArr[0];
		$orderRechargeModel = new Model_Wallet_WalletOrderRecharge();
		$order_info = $orderRechargeModel->getInfoByOrderNo($orderID, array('Status', 'Remark'));
		if (empty($order_info)) {
			$paySuccess = 2;
		} elseif ($order_info['Status'] == 2) {
			$paySuccess = 1;
		} else {
			$paySuccess = 0;
		}

		parent::succReturn(array('PaySuccess' => $paySuccess,
			"Remark" => isset($order_info['Remark']) ? $order_info['Remark'] : ""));
	}
}
