<?php

/**
 * 钱包
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 上午9:45
 */
class Api_WalletController extends Action_Api
{
	public function init()
	{
		parent::init();
	}

	protected $rsaDemoConf = array(
		"encrypt" => "rsa",
		"method" => "post",
	);

	/**
	 * rsa 测试demo
	 * 客户端到服务端走RSA加密
	 * 服务端到客户端走对层加密3DES key由客户端随即生成附加到data值里传过来 data['encrypt_key']
	 * 服务端将会用KEY加密数据返回
	 */
	public function rsaDemoAction()
	{
		parent::succReturn(array("rsa_demo" => 1));
	}

	/**
	 * 判断钱包的有效性
	 * 是否设置支付密码
	 * 是否被冻结
	 */
	public function isValidAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;
		$walletModel = new Model_Wallet_Wallet();

		try {
			$walletModel->isValidByMID($member_id, false);

			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage(), $e->getCode());
		}
	}

	protected $getBindCardConf = array(
		array("is_valid", "0,1", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
	);

	/**
	 * 获取用户绑卡信息
	 * 后期会出现多种支付渠道
	 */
	public function getBindCardAction()
	{
		$is_valid = (bool)$this->_param['is_valid'];
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;

		$walletBindCardModel = new Model_Wallet_WalletBindCard();
		$bind_info = $walletBindCardModel->getBindCardByMID($member_id, $is_valid);

		if ($bind_info) {
			foreach ($bind_info as &$val) {
				$val['BankLogo'] = str_replace('bank_', 'abank_', Model_Wallet_WalletBankCard::$_bank_list[$val['BankCode']]['BankLogo']);/*替换为有白底logo*/
                if(!empty($val['City'])){
                    $city_arr = explode(',',$val['City']);
                    $val['City'] =  isset($city_arr[1])?$city_arr[1]:'';
                }
			}
		}
		parent::succReturn(array("Rows" => $bind_info));
	}

	protected $getCardInfoConf = array(
		array("cardno", "number", "订单号有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_channel", "1", "支付渠道有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
	);

	/**
	 * 获取借记卡信息
	 * 只返回特定信息
	 * 支持多种渠道
	 */
	public function getCardInfoAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;

			//服务器查询
			$bankCardModel = new Model_Wallet_WalletBankCard();
			$card_info = $bankCardModel->getCardInfoByNo($this->_param['cardno']);

			//第三方查询
			if (!$card_info) {
				$is_card = false;
				switch ($this->_param['pay_channel']) {
					//易宝
					case "1":
						$map = array(
							"CardNo" => "cardno",
							"CardType" => "cardtype",
							"BankName" => "bankname",
							"BankCode" => "bankcode",
							"IsValid" => "isvalid"
						);

						$config = DM_Controller_Front::getInstance()->getConfig()->yee;
						$post = array(
							"merchantaccount" => $config->merchant_account,//商户账户编号
							"cardno" => $this->_param['cardno'],
						);

						$return = DM_Third_Yee_Yee::getInstance()->apiPost($config->card_info_url, $post);
						if ($return['cardtype'] == -1) {
							throw new Exception("无效的银行卡");
						} else {
							$is_card = true;
							foreach ($map as $key => $val)
								$card_info[$key] = $return[$val];
						}
						break;
				}

				if ($is_card) {
					//同步服务器 不保存会员ID 只有当绑定成功时才保存会员ID
					$param = array(
						"CardNoLastFour" => substr($this->_param['cardno'], -4),
						"CreateTime" => date("Y-m-d H:i:s", time()),
					);

					$res = $bankCardModel->getCardInfoByNo($this->_param['cardno'], "CardNo");
					if (empty($res))
						$bankCardModel->insert(array_merge($card_info, $param));
				}
			} else {
				//判断一下会员ID和已存在记录会员ID是否一致
				if ($card_info['MemberID'] > 0 && $card_info['MemberID'] != $member_id)
					throw new Exception("该卡已经被绑定");

				unset($card_info['MemberID']);
			}

			if ($card_info['CardType'] != 1)
				throw new Exception("不支持信用卡");

			$bank_list = Model_Wallet_WalletBankCard::$_bank_list;

			if ($card_info) {
				if (!array_key_exists($card_info['BankCode'], $bank_list))
					throw new Exception("不支持该银行卡");

				$card_info['BankLogo'] = isset($bank_list[$card_info['BankCode']]) ? $bank_list[$card_info['BankCode']]['BankLogo'] : "";
			}

			parent::succReturn($card_info);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}

	}

	protected $removeBindCardConf = array(
		"method" => "post",
		array("bcid", "number", "邦卡ID有误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 解除绑卡
	 * 多种支付渠道
	 */
	public function removeBindCardAction()
	{
		$this->isLoginOutput();
		try {
			$bindCardModel = new Model_Wallet_WalletBindCard();
			$bind_card_info = $bindCardModel->getInfoByID($this->_param['bcid']);
			if (!$bind_card_info)
				throw new Exception("绑卡记录不存在");

			$member_id = $this->memberInfo->MemberID;

			if ($bind_card_info['MemberID'] != $member_id)
				throw new Exception("非法操作");

			if ($bind_card_info['ValidityPeriod'] < time())
				throw new Exception("卡片已过期");

			$bindCardModel->getAdapter()->beginTransaction();
			try {
				//解除绑卡
				$res = $bindCardModel->update(array("Status" => $bindCardModel::BIND_STATUS_FALSE),
					array("BCID =?" => $this->_param['bcid']));
				if ($res === false)
					throw new Exception("解除绑卡失败");

				switch ($bind_card_info['PayChannel']) {
					//易宝
					case "1":
						$config = DM_Controller_Front::getInstance()->getConfig()->yee;
						$post = array(
							"merchantaccount" => $config->merchant_account,//商户账户编号
							"bindid" => $bind_card_info['BindID'],
							"identityid" => $member_id,//用户标识
							"identitytype" => 0,//用户标识类型
						);

						DM_Third_Yee_Yee::getInstance()->apiPost($config->remove_bind_card, $post);
						break;
				}

				$bindCardModel->getAdapter()->commit();
			} catch (Exception $e) {
				$bindCardModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}

			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**************************以下为支付密码及交易记录相关方法(Hale)***************************/

	/**
	 * 获取钱包的相关信息
	 */
	public function walletInfoAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$walletModel = new Model_Wallet_Wallet();
		$walletInfo = $walletModel->getWalletInfo($memberID);
		$this->returnJson(parent::STATUS_OK, null, $walletInfo);
	}

	/**
	 * 获取钱包余额(已下线)
	 */
	public function getBalanceAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$fundsModel = new DM_Model_Table_Finance_Funds();
		$balance = $fundsModel->getMemberBalance($memberID, "CNY");
		$this->returnJson(parent::STATUS_OK, null, array('Balance' => $balance));
	}

	/**
	 * 获取交易记录列表
	 */
	public function orderListAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$pagesize = (int)$this->_request->getParam('pagesize', 10);
		$lastID = (int)$this->_request->getParam('lastID', 0);
		$pagesize = min(100, max($pagesize, 1));
		$walletModel = new Model_Wallet_Wallet();
		$orderList = $walletModel->getOrderListByMemberID($memberID, $lastID, $pagesize);
		$list = array();
		$refundModel = new DM_Model_Table_Finance_Refund();
        $counselOrderModel = new Model_Counsel_CounselOrder();
		foreach ($orderList as $row) {
            $Status = $row['Status'];
			if ($row['OrderType'] == 2) {//提现
				$refundInfo = $refundModel->getRefundInfoByOid($memberID, $row['ID'], 'FeeAmount,Status');
				$row['Fee'] = (empty($refundInfo) || $refundInfo['FeeAmount'] == 0) ? 0 : $refundInfo['FeeAmount'];
				$Status = $refundInfo['Status'];
            } elseif ($row['OrderType'] == 9) {//咨询服务
                if($row['Type']==1){//收入才会去获取手续费
                    if(strstr($row['Remark'], '补偿费') !== false){
                        $row['Remark'] = "补偿费入账";
                        $row['Fee'] = 0;
                    }else{
                        $counselOrderInfo = $counselOrderModel->getInfoMix(array('OID=?'=>$row['FromObj']), array('FeeAmount'=>'DeductedAmount','OID'));
                        $row['Fee'] = (empty($counselOrderInfo) || $counselOrderInfo['FeeAmount'] == 0) ? 0 : $counselOrderInfo['FeeAmount'];
                    }
                    $row['Remark'] = strstr($row['Remark'], '补偿费') !== false ? "补偿费入账" : "订单结算";
                }
            }
            $remark = $row['Remark'];
			$re = $walletModel->getStatusType($row['ID'], $row['OrderType'], $Status, $row['Type'], $row['RelationType']);
			$row['Title'] = isset($re['TypeName']) ? $re['TypeName'] : '';
            if($row['OrderType'] != 9){
                $row['Remark'] = isset($re['Status']) ? $re['Status'] : '';
            }

            if($row['OrderType']==20){//退款
                if($row['RelationType']==3){
                    if(strstr($remark, '红包') !== false){
                        $row['Remark'] = strstr($remark, '全部退款') !== false ? "全部退款" : "已部分退款";
            		}
                }
            }

			$row['Money'] = $row['Type'] == 2 ? ('-' . $row['Money']) : $row['Money'];
			unset($row['Type']);
			unset($row['Status']);
			unset($row['OrderType']);
			unset($row['RelationType']);
			$list[] = $row;
		}
		$this->returnJson(parent::STATUS_OK, null, array('Rows' => $list));
	}

	/**
	 * 获取某交易的详细信息
	 */
	public function orderDetailAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$orderId = (int)$this->_request->getParam('ID', 0);
		if (empty($orderId)) {
			$this->returnJson(parent::STATUS_FAILURE, "交易记录ID不能为空");
		}
		$walletModel = new Model_Wallet_Wallet();
		$orderInfo = $walletModel->getOrderInfoByID($memberID, $orderId);
		if (empty($orderInfo)) {
			$this->returnJson(parent::STATUS_FAILURE, "交易记录不存在");
		}
		$giftModel = new Model_Gift();
        $counselOrderModel = new Model_Counsel_CounselOrder();
		$orderInfo['Type'] = $orderInfo['Type'] == 1 ? "收入" : "支出";
		if ($orderInfo['OrderType'] == 1) {//充值
			$orderInfo['Type'] = "充值";
		} elseif ($orderInfo['OrderType'] == 2) {//提现
			$orderInfo['Type'] = "提现";
			$refundModel = new DM_Model_Table_Finance_Refund();
			$refundInfo = $refundModel->getRefundInfoByOid($memberID, $orderInfo['ID'], 'FeeAmount,ConfirmDate,RealityAmount,Status,ifnull(RejectReason,"") as RejectReason');
			$orderInfo['Fee'] = (empty($refundInfo) || $refundInfo['FeeAmount'] == 0) ? 0 : $refundInfo['FeeAmount'];
			$orderInfo['HandleTime'] = (empty($refundInfo) || $refundInfo['ConfirmDate'] == '0000-00-00 00:00:00') ? '' : $refundInfo['ConfirmDate'];
			$orderInfo['RealAmount'] = (empty($refundInfo) || $refundInfo['RealityAmount'] == 0) ? 0 : $refundInfo['RealityAmount'];
			$orderInfo['Reason'] = $refundInfo['Status'] == 6 ? "银行处理错误" : $refundInfo['RejectReason'];
			if ($refundInfo['Status'] == 2 || $refundInfo['Status'] == 5 || $refundInfo['Status'] == 6) {
				$orderInfo['Remark'] = $orderInfo['Remark'] . '失败';
			} elseif ($refundInfo['Status'] == 4) {
                $orderInfo['Remark'] = $orderInfo['Remark'] . '处理成功，请注意查收';
            } else {
                $orderInfo['Remark'] = $orderInfo['Remark'] . '申请已提交';
                $orderInfo['HandleTime'] = "";
            }
        } elseif ($orderInfo['OrderType'] == 3){//红包
            if($orderInfo['Type']=='收入'){//接红包，查询发红包的人
                $bounsModel = new Model_Bonus();
                $bounsInfo = $bounsModel->getBonusInfo($orderInfo['FromObj'],array('MemberID'));
                $notesModel = new Model_MemberNotes();
                $orderInfo['From'] = $notesModel->getNoteName($memberID,$bounsInfo['MemberID']);
                if(empty($orderInfo['From'])){
                    $memberModel = new DM_Model_Account_Members();
                    $memberInfo = $memberModel->getMemberInfoCache($bounsInfo['MemberID'],array('UserName','Avatar'));
                    $orderInfo['From'] = empty($memberInfo)?"":$memberInfo['UserName'];
                }
            }
		} elseif ($orderInfo['OrderType'] == 4) {//观点打赏
			$orderInfo['Describe'] = '观点打赏';
			$orderInfo['TargetID'] = $giftModel->getIDByOrdersn(2, $orderInfo['OrderID']);
			if ($orderInfo['Type'] == "收入") {
				$amountInfo = $walletModel->getMoneyByOrdersn($memberID, $orderInfo['OrderID'], 2);
				$orderInfo['RealAmount'] = empty($amountInfo) ? '0.00' : $amountInfo['RealityAmount'];
				$orderInfo['Fee'] = empty($amountInfo) ? '0.00' : $amountInfo['FeeAmount'];
			}
		} elseif ($orderInfo['OrderType'] == 5) {//文章付费
			$orderInfo['Describe'] = '文章付费';
			$orderInfo['TargetID'] = $giftModel->getIDByOrdersn(1, $orderInfo['OrderID']);
			if ($orderInfo['Type'] == "收入") {
				$amountInfo = $walletModel->getMoneyByOrdersn($memberID, $orderInfo['OrderID'], 1);
				$orderInfo['RealAmount'] = empty($amountInfo) ? '0.00' : $amountInfo['RealityAmount'];
				$orderInfo['Fee'] = empty($amountInfo) ? '0.00' : $amountInfo['FeeAmount'];
			}
		} elseif ($orderInfo['OrderType'] == 6) {//文章打赏
			$orderInfo['Describe'] = '文章打赏';
			$orderInfo['TargetID'] = $giftModel->getIDByOrdersn(1, $orderInfo['OrderID']);
            if($orderInfo['Type'] == "收入"){
                $amountInfo = $walletModel->getMoneyByOrdersn($memberID,$orderInfo['OrderID'],1);
                $orderInfo['RealAmount'] = empty($amountInfo)?'0.00':$amountInfo['RealityAmount'];
                $orderInfo['Fee'] = empty($amountInfo)?'0.00':$amountInfo['FeeAmount'];
            }
        } elseif ($orderInfo['OrderType'] == 9) {//咨询服务
            $counselOrderInfo = $counselOrderModel->getInfoMix(array('OID=?'=>$orderInfo['FromObj']), array('FeeAmount'=>'DeductedAmount','SettlementAmount','CID'));
            $orderInfo['TargetID'] = $orderInfo['FromObj'];//empty($counselOrderInfo) ? 0 : $counselOrderInfo['CID'];
            if($orderInfo['Type'] == "收入"){
                $orderInfo['Fee'] = empty($counselOrderInfo) ? 0 : $counselOrderInfo['FeeAmount'];
                $counselModel = new Model_Counsel_Counsel();
                $counselInfo = $counselModel->getInfoMix(array('CID=?'=>$counselOrderInfo['CID']), array('Price','CID'));
                $orderInfo['RealAmount'] = $counselInfo['Price'];//empty($counselOrderInfo) ? 0 : number_format($counselOrderInfo['FeeAmount']+$counselOrderInfo['SettlementAmount'],2,'.','');
            }
        } elseif ($orderInfo['OrderType'] == 20){//退款
            $orderInfo['Type'] = "退款";
            if($orderInfo['RelationType'] == 2){
                $refundModel = new DM_Model_Table_Finance_Refund();
                $goodsInfo = $refundModel->getApplicationInfo($orderInfo['FromObj'], $memberID, 'FeeAmount,ConfirmDate,ApplicationAmount as Amount,Status,ApplicationDate as Addtime,ConfirmDate,ifnull(RejectReason,"") as RejectReason');
                $orderInfo['HandleTime'] = (!empty($goodsInfo) && $goodsInfo['ConfirmDate']!='0000-00-00 00:00:00')?$goodsInfo['ConfirmDate']:$orderInfo['Time'];
                $orderInfo['Time'] = !empty($goodsInfo)?$goodsInfo['Addtime']:"";
                $orderInfo['Reason'] = !empty($goodsInfo)?$goodsInfo['RejectReason']:"银行处理错误";
                empty($orderInfo['Reason']) && $orderInfo['Reason'] = "银行处理错误";
            } elseif ($orderInfo['RelationType'] == 3){
            	$bonusModel = new Model_Bonus();
                $goodsInfo = $bonusModel->getBonusInfo($orderInfo['FromObj'],array('Amount'=>'BonusAmount','GroupType'));
                //$goodsInfo['GroupType'] == 1 && $orderInfo['Remark'] = '退款';
            } elseif($orderInfo['RelationType'] == 9){
                $counselOrderInfo = $counselOrderModel->getInfoMix(array('OID=?'=>$orderInfo['FromObj']), array('FeeAmount'=>'DeductedAmount','DamagesAmount','CID'));
                $counselModel = new Model_Counsel_Counsel();
                $orderInfo['TargetID'] = $orderInfo['FromObj'];//empty($counselOrderInfo) ? 0 : $counselOrderInfo['CID'];
                $counselInfo = $counselModel->getInfoMix(array('CID=?'=>$counselOrderInfo['CID']), array('Price','CID'));
                $orderInfo['RealAmount'] = $counselInfo['Price'];
                $orderInfo['DamagesAmount'] = empty($counselOrderInfo) ? 0 : $counselOrderInfo['DamagesAmount'];
                $goodsInfo['Amount'] = $orderInfo['Money'];
            }
			$orderInfo['BackAmount'] = $orderInfo['Money'];
            $orderInfo['Money'] = !empty($goodsInfo)?$goodsInfo['Amount']:'0.00';
		} else {
			$orderInfo['Describe'] = '活动付费';
			$orderInfo['TargetID'] = '';
		}
		$this->returnJson(parent::STATUS_OK, null, $orderInfo);
	}

	/**
	 * 验证支付密码是否正确
	 */
	public function checkPayPasswordAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$password = trim($this->_request->getParam('password', ''));
		$walletModel = new Model_Wallet_Wallet();
		$check = $walletModel->checkPayPasswordAction($memberID, $password);
		if ($check['flag'] < 0) {
			$this->returnJson($check['flag'], null, new stdClass());
		}
		$this->returnJson(parent::STATUS_OK, null, new stdClass());
	}

	/**
	 * 修改(设置)支付密码
	 */
	public function modifyPayPasswordAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$oldPassword = trim($this->_request->getParam('oldPassword', ''));
		$newPassword = trim($this->_request->getParam('newPassword', ''));
		$type = (int)($this->_request->getParam('type', 0));
		if ($type < 1 || $type > 4) {//1修改密码，2忘记支付密码，3设置支付密码
			$this->returnJson(parent::STATUS_FAILURE, "参数错误");
		}
		if (empty($newPassword)) {
			$this->returnJson(parent::STATUS_FAILURE, "支付密码不能为空");
		}
		if (!is_numeric($newPassword)) {
			$this->returnJson(parent::STATUS_FAILURE, "支付密码必须为数字");
		}
		if (strlen($newPassword) != 6) {
			$this->returnJson(parent::STATUS_FAILURE, "支付密码长度必须为6位");
		}
		$user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
		$newPassword = $user->encodePassword($newPassword);
		$walletModel = new Model_Wallet_Wallet();
		if ($type == 1) {//修改支付密码
			$check = $walletModel->checkPayPasswordAction($memberID, $oldPassword);
			if ($check['flag'] < 0) {
				$this->returnJson(parent::STATUS_FAILURE, "原支付密码错误");
			}
		} elseif ($type == 2) {//忘记密码在验证银行信息之后设置密码
			$check = $walletModel->checkBankIsPass($memberID);
			if (!$check) {
				$this->returnJson(parent::STATUS_FAILURE, "请先进行身份认证");
			}
		}
		if ($type == 4) {//预设置支付密码
			$modify = $walletModel->beforePayPassword($memberID, $newPassword);
		} elseif ($type == 3) {//设置密码
			//先验证是否绑定过银行卡
			$bindCardModel = new Model_Wallet_WalletBindCard();
			$cardInfo = $bindCardModel->getBindCardByMID($memberID, false);
			if (empty($cardInfo)) {
				$this->returnJson(parent::STATUS_FAILURE, "请先绑定银行卡");
			}
			$modify = $walletModel->modifyPayPassword($memberID, $newPassword);
		} else {
			$modify = $walletModel->modifyPayPassword($memberID, $newPassword);
		}
		if (!$modify) {
			$this->returnJson(parent::STATUS_FAILURE, "修改支付密码失败");
		}
		$this->returnJson(parent::STATUS_OK);
	}

	protected $checkBankInfoConf = array(
		"encrypt" => "rsa",
		"method" => "post",
		array("CardNo", "number", "卡号错误！", DM_Helper_Filter::MUST_VALIDATE),  //判断
		array("Idcard", "require", "身份证错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("Owner", "require", "持卡人错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("Code", "number", "验证码错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 修改支付密码前的银行卡信息验证
	 */
	public function checkBankInfoAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$CardNo = trim($this->_param['CardNo']);
		$Owner = trim($this->_param['Owner']);
		$Idcard = trim($this->_param['Idcard']);
		$Code = trim($this->_param['Code']);

		$this->_request->setParam('type', 10);
		$this->_request->setParam('code', $Code);
		$walletModel = new Model_Wallet_Wallet();
		$check = $walletModel->checkBankInfo($memberID, $CardNo, $Owner, $Idcard);
		if (!$check) {
			$this->returnJson(parent::STATUS_FAILURE, "银行卡信息验证失败", new stdClass());
		}
		$user = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
		$this->verifyMobileCall($user);
		$this->returnJson(parent::STATUS_OK, null, new stdClass());
	}

	/**
	 * 是否需要支付密码验证（已下线）
	 */
	public function payValidationAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;
		$walletModel = new Model_Wallet_Wallet();
		$validation = $walletModel->payValidation($member_id);
		$this->returnJson(parent::STATUS_OK, null, array('Validation' => $validation));
	}

	/**
	 * 消息列表
	 */
	public function messageListAction()
	{
		$this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
		$pagesize = intval($this->_request->getParam('pagesize', 10));
		$lastMessageID = intval($this->_request->getParam('lastMessageID', 0));
		$walletModel = new Model_Wallet_Wallet();
		$messageList = $walletModel->getList($memberID, $lastMessageID, $pagesize);
		if (!empty($messageList)) {
			$lastID = Model_Member::staticData($memberID, 'maxWalletListID');
			$newLastID = isset($messageList[0]['MessageID']) ? $messageList[0]['MessageID'] : 0;
			if ($newLastID > $lastID) {
				Model_Member::staticData($memberID, 'maxWalletListID', $newLastID);
			}
			$messageList = array_reverse($messageList);
		}

		$this->returnJson(parent::STATUS_OK, '', array('Rows' => $messageList));
	}
    
    /**
     * 删除钱包的消息（红点）
     */
    public function delMessageNewsAction(){
        $this->isLoginOutput();
		$memberID = $this->memberInfo->MemberID;
        $walletModel = new Model_Wallet_Wallet();
        $messageList = $walletModel->getList($memberID,0,1);//获取最新的一条数据，将消息ID存入redis
        $newLastID = empty($messageList) ? 0: $messageList[0]['MessageID'];
        Model_Member::staticData($memberID, 'maxWalletListID', $newLastID);
		$this->returnJson(parent::STATUS_OK,'');
    }

	/**
	 * 获取支持的银行
	 */
	public function getSupportBankAction()
	{
		$this->returnJson(parent::STATUS_OK, '', array('Rows' => array_values(Model_Wallet_WalletBankCard::$_bank_list)));
	}

	protected $getBankLimitConf = array(
		"method" => "post",
		array("bank_code", "require", "参数错误", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_channel", "1", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1"),
	);

	/**
	 * 获取用户银行卡限额
	 */
	public function getBankLimitAction()
	{
		$bank_code = strtoupper($this->_param['bank_code']);
		$limit_name = "_bank_limit_" . $this->_param['pay_channel'];
		$limit_info = Model_Wallet_WalletBankCard::$$limit_name;

		if (!array_key_exists($bank_code, $limit_info))
			parent::failReturn("银行卡信息不存在");

		$limit_info = $limit_info[$bank_code];

		parent::succReturn($limit_info);
	}
}