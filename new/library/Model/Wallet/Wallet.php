<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 上午10:39
 */
class Model_Wallet_Wallet extends Zend_Db_Table
{

	protected $_name = 'wallet';
	protected $_primary = 'WID';

	//钱包状态
	const STATUS_INVALID = 0;
	const STATUS_VALID = 1;
	const STATUS_FREEZE = 2;

	//冻结时间
	const FREEZE_TIME = 86400;

	/**
	 * 判断钱包有效性
	 * @param $member_id
	 * @param bool $is_return
	 * @return int
	 * @throws Exception
	 */
	public function isValidByMID($member_id, $is_return = true)
	{
		$select = $this->select();
		$select->from($this->_name, array("PayPassword", "Status", "FreezeTime", "WID"));
		$select->where("MemberID =?", $member_id);

		//是否设置密码
		$wallet_info = $this->_db->fetchRow($select);
		if (empty($wallet_info)){
            $walletId = $this->insert(array('MemberID'=>$member_id));
            if(!$walletId){
                if ($is_return)
                    return -1;
                else
                    throw new Exception("请设置支付密码", -1);
            }
            $PayPassword = '';
        }else{
            $PayPassword = $wallet_info['PayPassword'];
        }
        if(empty($PayPassword))
			if ($is_return)
				return -2;
			else
				throw new Exception("请设置支付密码", -2);

		//无效钱包
		if ($wallet_info['Status'] == self::STATUS_INVALID)
			if ($is_return)
				return -3;
			else
				throw new Exception("无效的钱包", -3);

		//是否被冻结
		if ($wallet_info['Status'] == self::STATUS_FREEZE) {
			if ((strtotime($wallet_info['FreezeTime']) + self::FREEZE_TIME) >= time()) {
				if ($is_return)
					return -4;
				else
					throw new Exception("钱包已被冻结", -4);
			} else {
				//解除冻结
				$this->update(array("Status" => self::STATUS_VALID), array("WID =?" => $wallet_info['WID']));
			}
		}

		return 1;
	}

	/**************************以下为支付密码及交易记录相关方法(Hale)***************************/
    
    /**
     * 获取钱包的相关信息
     */
    public function getWalletInfo($memberID){
        $info = array();
		$wallet_info = $this->select()->from($this->_name, array('PayPassword','Status','FreezeTime','WID','FailureNum'))->where('MemberID=?', $memberID)->query()->fetch();
        if(empty($wallet_info)){
            $this->insert(array('MemberID'=>$memberID));
            $wallet_info = array('Status'=>self::STATUS_VALID);
        }
        if($wallet_info['Status'] == self::STATUS_VALID){
            $info['Status'] = 1;
        }elseif ($wallet_info['Status'] == self::STATUS_FREEZE || $wallet_info['FailureNum'] >=3) {//是否被冻结
            $info['Status'] = 3;
			if (date('Y-m-d',strtotime($wallet_info['FreezeTime'])) < date('Y-m-d')) {
				//解除冻结
				$this->unFreeze($memberID);
                $info['Status'] = 1;
			}
		}else{
            $info['Status'] = 2;
        }
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $info['Balance'] = $fundsModel->getMemberBalance($memberID,"CNY");
        $info['PayPwd'] = empty($wallet_info['PayPassword'])?0:1;
        return $info;
    }

    /**
	 * 获取交易记录列表
	 * $memberID 会员编号
	 * $lastID 最后一条记录ID
	 * $pagesize 每次请求返回的记录数
	 */
	public function getOrderListByMemberID($memberID, $lastID, $pagesize)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("wallet_order_list", array('ID' => 'OID', 'Money' => 'Amount', 'Status', 'OrderType', 'Type', 'Time' => "CreateTime",'RelationType','Remark','FromObj'))->where("MemberID=?", $memberID)->where('IsShow=1');
		if ($lastID > 0) {
			$select->where('OID < ? ', $lastID);
		}
		return $select->order('OID desc')->limit($pagesize)->query()->fetchAll();
	}

	/**
	 * 获取交易的类型及状态信息
	 * $orderType 订单类型
	 * $status 订单状态
	 * $oid 订单编号
     * $orderType 订单类型
     * $status 订单状态
     * $type 收支类型，1收入2支出
     * $relationType 关联订单类型，当$orderType=20时用到
	 */
	public function getStatusType($oid, $orderType, $status,$type,$relationType = 0)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("wallet_order_type", array('Tname'))->where("Tid=?", $orderType);
		$res = $select->query()->fetch();
		$orderTypeName = isset($res['Tname']) ? $res['Tname'] : "";
        $statusTxt = "";
		//订单状态，0交易失败，1处理中，2交易完成，4关闭
		if ($status == 4  && $orderType!=2) {
			$statusTxt = "交易关闭";
		} else {
			if ($orderType == 1) {//充值
				if ($status == 0) {
					$statusTxt = "充值失败";
				} elseif ($status == 2) {
					$statusTxt = "充值完成";
				} else {
					$statusTxt = "充值处理中";
				}
			} elseif ($orderType == 2) {//提现，状态,1待审核,2拒绝,3正在付款,4已付款,5已取消,6已退款
				if ($status == 1 || $status == 3) {
					$statusTxt = "提现申请已提交";
				} elseif ($status == 4) {
					$statusTxt = "提现处理成功";
                //} elseif($status == 6) {
                //    $statusTxt = "提现已退款";
                //} elseif($status == 5) {
				//	$statusTxt = "提现已取消";
				} else {
                    $statusTxt = "提现失败";
                }
				//状态,1待审核,2拒绝,3正在付款,4已付款,5已取消
			} elseif ($orderType == 3) {//红包
                if($type==1){
                    $statusTxt = $status == 0 ? '支付失败' : "红包收入";
                }else{
                    $statusTxt = $status == 0 ? '支付失败' : "支付成功";
                }
			} elseif ($orderType == 8) {//转账

			} elseif($orderType == 4) {//观点打赏
                if($type==1){
                    $statusTxt = $status == 0 ? '支付失败' : "观点收入";
                }else{
                    $statusTxt = $status == 0 ? '支付失败' : "支付成功";
                }
			} elseif($orderType == 7) {//活动付费
                $statusTxt = "支付成功";
            } elseif($orderType == 9){//询财服务
                if($type==1){
                    $statusTxt = $status == 0 ? '支付失败' : "订单结算";
                }else{
                    $statusTxt = $status == 0 ? '支付失败' : "支付成功";
                }
            }elseif($orderType == 20) {//退款
                if($relationType == 2){
                    $statusTxt = "提现退款";
                }elseif($relationType == 3){
                    $statusTxt = "红包退款";
                    $orderTypeName = "财猪红包";
                }elseif($relationType == 9){
                    $statusTxt = "订单退款";
                    $orderTypeName = "询财服务";
                }
            } else {//文章付费、文章送礼
                if($type==1){
                    $statusTxt = $status == 0 ? '支付失败' : "文章收入";
                }else{
                    $statusTxt = $status == 0 ? '支付失败' : "支付成功";
                }
			}
		}
		return array('TypeName' => $orderTypeName, 'Status' => $statusTxt);
	}

	/**
	 * 获取订单的详细信息
	 * $orderId 订单编号
	 */
	public function getOrderInfoByID($memberID, $orderId)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("wallet_order_list", array('ID' => 'OID', 'OrderID' => 'OrderNo', 'Money' => 'Amount', 'Type', 'OrderType', 'Balance', 'Time' => "CreateTime", 'Remark','RelationType','FromObj'))->where('OID=?', $orderId)->where("MemberID=?", $memberID);
		return $select->query()->fetch();
	}

	/**
	 * 验证支付密码
	 * @param int $memberID
	 * @param string $password 明文密码
	 */
	public function checkPayPasswordAction($memberID, $password)
	{
		/*if (empty($password)) {
			//$this->returnJson(parent::STATUS_FAILURE,"支付密码不能为空");
			return false;
		}
		if (!is_numeric($password)) {
			//$this->returnJson(parent::STATUS_FAILURE,"支付密码必须为数字");
			return false;
		}
		if (strlen($password) != 6) {
			//$this->returnJson(parent::STATUS_FAILURE,"支付密码长度必须为6位");
			return false;
		}*/
        if(APPLICATION_ENV == 'development'){
            return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_SUCCESS,'Num'=>0);
        }
		$memberModel = new DM_Model_Account_Members();
		$user = $memberModel->getById($memberID);
		$password = $user->encodePassword($password);
		$select = $this->select()->setIntegrityCheck(false);
		$res = $select->from("wallet", array('WID','PayPassword','FailureNum','Status','FreezeTime'))->where('MemberID=?', $memberID)->query()->fetch();
		
		if($res['FailureNum']>3 || $res['Status'] == self::STATUS_FREEZE){
			//added by Mark 次日之后调用，将自动解除冻结
			$freezeTime = $res['FreezeTime'];
			if(date('Y-m-d',strtotime($freezeTime)) < date('Y-m-d')){
                $this->unFreeze($memberID);
				$res['FailureNum'] = 0;
				$res['Status'] = self::STATUS_VALID;
			}				
		}
		
        $FailureNum = isset($res['FailureNum'])?$res['FailureNum']:0;
        $Status = isset($res['Status'])?$res['Status']:1;
        if($FailureNum>=3 || $Status==self::STATUS_FREEZE){
            return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_FAILURE_FREEZE,'Num'=>$FailureNum);
        }
        $PayPassword = isset($res['PayPassword'])?$res['PayPassword']:'';
        $bool = $password==$PayPassword ? true : false;
        if($bool){
            $res = $this->_db->update("wallet", array('FailureNum' =>0), array('MemberID=?' => $memberID));
            return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_SUCCESS,'Num'=>0);
        }else{
            $this->_db->update("wallet", array('FailureNum' =>new Zend_Db_Expr('FailureNum+1')), array('MemberID=?' => $memberID));
            if($FailureNum==2){//冻结账户
                $this->update(array("Status" => self::STATUS_FREEZE,'FreezeTime'=>new Zend_Db_Expr('NOW()')), array("MemberID =?" => $memberID));
                return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_FAILURE_FREEZE,'Num'=>$FailureNum+1);
            }
            if($FailureNum==0){
                return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_FAILURE_ONE);
            }else{
                return array('flag'=>DM_Controller_Action::CHECK_PAY_PWD_FAILURE_TWO);
            }
        }
	}

	/**
	 * 修改(设置)支付密码
	 */
	public function modifyPayPassword($memberID, $newPassword)
	{
        $this->unFreeze($memberID);
        $res = $this->_db->update("wallet", array('PayPassword' => $newPassword), array('MemberID=?' => $memberID));
		return $res===false?false:true;
	}
    
    /**
	 * 预设支付密码
	 */
	public function beforePayPassword($memberID, $newPassword)
	{
        $res = $this->_db->update("wallet", array('tmpPassword' => $newPassword), array('MemberID=?' => $memberID));
		return $res===false?false:true;
	}
    
    /**
	 * 将预设支付密码保存为正式密码
	 */
	public function confirmPayPassword($memberID)
	{
        $res = $this->_db->update("wallet", array('PayPassword' =>new Zend_Db_Expr('TmpPassword'),'TmpPassword' =>''), array('MemberID=?' => $memberID,'TmpPassword!=?'=>''));
		return $res===false?false:true;
	}
    
    /**
	 * 忘记密码在修改密码之前要判断是否通过了银行卡的验证
	 */
	public function checkBankIsPass($memberID)
	{
        $res = $this->select()->setIntegrityCheck(false)->from('member_verifys', array('VerifyID'))->where('MemberID=?', $memberID)->where("VerifyType=10")->where("Status='Pass'")->where('ExpiredTime>?',date('Y-m-d H:i:s',strtotime('- 30 minutes')))->query()->fetch();
        return empty($res)?false:true;
	}
    
    /**
     * 判断是否进行支付密码验证
     */
    public function payValidation($member_id){
        $bindCardModel = new Model_Wallet_WalletBindCard();
        $cardInfo = $bindCardModel->getBindCardByMID($member_id,false);
		$res = $this->select()->from($this->_name, array('PayPassword'))->where('MemberID=?', $member_id)->query()->fetch();
        if(empty($res) || (empty($res['PayPassword']) && empty($cardInfo))){
            return 0;
        }
        return 1;
    }

    /**
	 * 修改支付密码前的银行卡信息验证
	 * $CardNo 卡号
	 * $Owner 卡所属人
	 * $Idcard 身份证
	 */
	public function checkBankInfo($memberID, $CardNo, $Owner, $Idcard)
	{
		$bindCard = new Model_Wallet_WalletBindCard();
		$BindCardInfo = $bindCard->getBindCardByMID($memberID,false);
		if (empty($BindCardInfo)) {
			return false;
		}
		$bankID = isset($BindCardInfo[0]['BID']) ? $BindCardInfo[0]['BID'] : 0;
		$cardModel = new Model_Wallet_WalletBankCard();
		$bankInfo = $cardModel->getCardInfoById($bankID);
		if (empty($bankInfo)) {
			return false;
		}

		if ($CardNo != $bankInfo['CardNo'] || $Owner != $bankInfo['Owner'] || $Idcard != $bankInfo['Idcard']) {
			return false;
		}
		return true;
	}
    
    /**
     * 判断是否有最新的钱包相关的消息
     */
    public function getWalletNews($memberID){
		$lastID = Model_Member::staticData($memberID,'maxWalletListID');
        $isShowPoint = 0;
        $contentTitle = '';
        $publishTime = '';
        $select = $this->select()->setIntegrityCheck(false);
		if($lastID !== false){
            $select->from("member_messages", array('MessageType','RelationID','CreateTime'))->where('MemberID=?', $memberID)->where('MessageSign=3')->where("MessageID>?",$lastID);
            $res = $select->order('MessageID desc')->query()->fetch();
            if(empty($res)){//如果没有最新的获取最后一条数据
                $res = $this->select()->setIntegrityCheck(false)->from("member_messages", array('MessageType','RelationID','CreateTime'))->where("MessageID=?",$lastID)->where('MemberID=?', $memberID)->where('MessageSign=3')->query()->fetch();
            }else{
                $isShowPoint = 1;
            }
		}else{
            $res = $select->from("member_messages", array('MessageType','RelationID','CreateTime'))->where('MemberID=?', $memberID)->where('MessageSign=3')->order('MessageID desc')->query()->fetch();
            if(!empty($res)){
                $isShowPoint = 1;
            }
        }
		if(!empty($res)){
            $publishTime = $res['CreateTime'];
            switch ($res['MessageType']){//4:绑卡充值,5:提现,6零钱支付,7零钱收入,8退款
                case 4:
                    $contentTitle = "充值失败";
                    $re = $this->select()->setIntegrityCheck(false)->from("wallet_order_recharge", array('Status'))->where('ORID=?',$res['RelationID'])->query()->fetch();
                    if(!empty($re) && $re['Status']==2){
                        $contentTitle = "绑卡充值成功";
                    }
                    break;
                case 5:
                    $contentTitle = "零钱提现通知";
                    break;
                case 6:
                    $contentTitle = "零钱支付通知";
                    break;
                case 7:
                    $contentTitle = "零钱收入通知";
                    $re = $this->select()->setIntegrityCheck(false)->from("wallet_order_list", array('OrderType','Remark'))->where('OID=?',$res['RelationID'])->query()->fetch();
                    if(!empty($re)){
                        if($re['OrderType']==9){
                            $contentTitle = strstr($re['Remark'], '补偿费') !== false ? "询财订单补偿费入账通知" : "询财订单费用结算通知";
                        }
                    }
                    break;
                case 8:
                    $contentTitle = "退款通知";
                    $re = $this->select()->setIntegrityCheck(false)->from("wallet_order_list", array('RelationType'))->where('OID=?',$res['RelationID'])->query()->fetch();
                    if(!empty($re)){
                        if($re['RelationType']==3){
                            $contentTitle = "红包退款通知";
                        }elseif($re['RelationType']==9){
                            $contentTitle = "询财订单退款通知";
                        }
                    }
                    break;
            }
        }
        return array('isShowPoint'=>$isShowPoint,'contentTitle'=>$contentTitle,'publishTime'=>$publishTime);
    }
    
    /**
	 *  
	 * @param int $memberID
	 * @param int $lastMessageID
	 * @param int $pageSize
	 */
	public function getList($memberID,$lastMessageID,$limit = 10)
	{
		$fields = array('MessageID','MemberID','MessageType','MessageSign','RelationID','CreateTime');
		$select = $this->select()->setIntegrityCheck(false)->from('member_messages',$fields)->where('MemberID = ?',$memberID)->where('MessageSign=3');
		if($lastMessageID > 0){
			$select->where('MessageID < ?',$lastMessageID);
		}
		$select->order('MessageID desc')->limit($limit);
		$lists = $select->query()->fetchAll();
		if(!empty($lists)){
			foreach($lists as &$l){
                $info = $this->getMessageInfo($l['MessageID'],$l['MessageType'],$l['RelationID']);
                if(!empty($info)){
                    $l = array_merge($l,$info);
                }
				unset($l['RelationID']);
			}
        }
		return $lists ? $lists : array();
	}
    
    /**
     * 获取某条消息的详细内容
     * $MessageType 4:绑卡充值,5:提现,6零钱支付,7零钱收入,8退款
     */
    public function getMessageInfo($MessageId,$MessageType,$RelationID){
        $info = array();
        $info['ID'] = $RelationID;
        $select = $this->select()->setIntegrityCheck(false);
        if($MessageType==4){
            $res = $select->from("wallet_order_recharge", array('Money'=>'Amount','BCID','Remark'=>'ifnull(Remark,"")','BID','Status'))->where('ORID=?',$RelationID)->query()->fetch();
        }else{
            $res = $select->from("wallet_order_list", array('Money'=>'Amount','OrderNo','MemberID','Type','Balance','Remark','OrderType','RelationType','FromObj','CreateTime'))->where('OID=?',$RelationID)->query()->fetch();
        }
        if($MessageType==4){
            $info['Title'] = $res['Status']==2?"绑卡充值成功":"充值失败";
            $info['Remark'] = $res['Status']==2?"设置支付密码成功":"";
            $info['Reason'] = $res['Status']==2?"":(empty($res['Remark'])?"充值失败":$res['Remark']);
            $info['Money'] = $res['Money'];
            $info['BankInfo'] = "";
            if($res['BID']>0){
                $cardModel = new Model_Wallet_WalletBankCard();
                $bankInfo = $cardModel->getCardInfoById($res['BID'],array("CardNoLastFour","BankName"));
                if (!empty($bankInfo)) {
                    $info['BankInfo'] = $bankInfo['BankName']."（".$bankInfo['CardNoLastFour']."）";
                }
            }
        }elseif($MessageType==5){
            $info['Title'] = "零钱提现";
            $refundModel = new DM_Model_Table_Finance_Refund();
			$refundInfo = $refundModel->getRefundInfoByOid($res['MemberID'],$RelationID, 'RefundApplicationID,FeeAmount,ConfirmDate,RealityAmount,Status,ApplicationDate');
            $info['Money'] = $res['Money'];
			$info['Fee'] = (empty($refundInfo) || $refundInfo['FeeAmount'] == 0) ? 0 : $refundInfo['FeeAmount'];
            $info['ApplyTime'] = (empty($refundInfo) || $refundInfo['ConfirmDate'] == '0000-00-00 00:00:00') ? '' : $refundInfo['ApplicationDate'];
			$info['HandleTime'] = (empty($refundInfo) || $refundInfo['ConfirmDate'] == '0000-00-00 00:00:00') ? '' : $refundInfo['ConfirmDate'];
			$info['RealAmount'] = (empty($refundInfo) || $refundInfo['RealityAmount'] == 0) ? 0 : $refundInfo['RealityAmount'];
            $info['Remark'] = $res['Remark'];
            $RefundApplicationID = empty($refundInfo) ? 0 : $refundInfo['RefundApplicationID'];
            //状态,1待审核,2拒绝,3正在付款,4已付款,5已取消,6已退款
            if($refundInfo['Status']==2 || $refundInfo['Status']==5 || $refundInfo['Status']==6){
                $info['Abstract'] = '您的零钱提现失败通知';
                $info['Remark'] = "提现失败，退回零钱";
                $info['RefundStatus'] = 2;
                $res_1 = $this->select()->setIntegrityCheck(false)->from("wallet_order_list", array('ID'=>'OID'))->where('FromObj=?',$RefundApplicationID)->where('RelationType=2')->query()->fetch();
                !empty($res_1) && $info['ID'] = $res_1['ID'];
            }elseif($refundInfo['Status']==4){
                $info['RefundStatus'] = 4;
                $info['Abstract'] = '您的零钱提现处理成功，请注意查收';
            }else{
                $info['RefundStatus'] = 3;
                $info['HandleTime'] = "";
                $info['Abstract'] = '您的零钱提现提交通知';
            }
        }elseif($MessageType==6){
            $info['Title'] = "零钱支付";
            $info['Money'] = $res['Money'];
            $info['PayType'] = 1;
            $info['Remark'] = $res['Remark'];
            $info['OrderSn'] = $res['OrderNo'];
            $info['Abstract'] = '您的零钱支付通知';
        }elseif($MessageType==7){
            $info['Title'] = "零钱收入";
            $info['Money'] = $res['Money'];
            $info['Balance'] = $res['Balance'];
            $info['Remark'] = $res['Remark'];
            if($res['OrderType']==4){//观点
                $re = $this->getMoneyByOrdersn($res['MemberID'],$res['OrderNo'],2);
            }elseif($res['OrderType']==5 || $res['OrderType']==6){//文章
                $re = $this->getMoneyByOrdersn($res['MemberID'],$res['OrderNo'],1);
            }elseif($res['OrderType']==9){
                $counselOrderModel = new Model_Counsel_CounselOrder();
                $re = $counselOrderModel->getInfoMix(array('OID=?'=>$res['FromObj']), array('FeeAmount'=>'DeductedAmount','RealityAmount'=>'SettlementAmount','DamagesAmount','CID'));
                if(strstr($res['Remark'], '补偿费') !== false){
                    $info['Money'] = empty($re) ? 0 : $re['DamagesAmount'];
                }else{
                    $counselModel = new Model_Counsel_Counsel();
                    $counselInfo = $counselModel->getInfoMix(array('CID=?'=>$re['CID']), array('Price','CID'));
                    $info['Money'] = $counselInfo['Price'];
                }
            }else{
                $re = array();
            }
            $info['Fee'] = empty($re)?'0.00':$re['FeeAmount'];
            $info['RealAmount'] = empty($re)?'0.00':$re['RealityAmount'];
            
            if($res['OrderType'] == 3 || $res['OrderType']==9){
                $info['RealAmount'] = $res['Money'];
            }
        }elseif($MessageType==8){//退款
            $info['PayType'] = 1;
            if($res['RelationType']==3){//红包退款
                $info['Title'] = "红包退款通知";
                $bounsModel = new Model_Bonus();
                $bonusInfo = $bounsModel->getBonusInfo($res['FromObj'],array('ReceiveAmount','BonusAmount'));
                $info['ReceiveAmount'] = empty($bonusInfo)?0.00:$bonusInfo['ReceiveAmount'];
                $info['Money'] = empty($bonusInfo)?0.00:$bonusInfo['BonusAmount'];
                $info['Reason'] = "未在24小时内领取";
            }elseif($res['RelationType']==9){
                $info['Title'] = "订单退款通知";
                $counselOrderModel = new Model_Counsel_CounselOrder();
                $counselOrderInfo = $counselOrderModel->getInfoMix(array('OID=?'=>$res['FromObj']), array('DamagesAmount','CID'));
                $info['DamagesAmount'] = empty($counselOrderInfo) ? 0 : $counselOrderInfo['DamagesAmount'];
                $counselModel = new Model_Counsel_Counsel();
                $counselInfo = $counselModel->getInfoMix(array('CID=?'=>$counselOrderInfo['CID']), array('Price','CID'));
                $info['Money'] = $counselInfo['Price'];
                $info['Reason'] = $res['Remark'];
            }
            $info['BackAmount'] = $res['Money'];
            $info['HandleTime'] = $res['CreateTime'];
            $info['RelationType'] = $res['RelationType'];
        }
        return $info;
    }
    
    /**
     * 获取打赏收入的实际金额
     * @param type $type 1文章，2观点
     */
    public function getMoneyByOrdersn($MemberID,$OrderNo,$type){
        if($type==1){
            $re = $this->select()->setIntegrityCheck(false)->from('column_article_gifts',array('RealityAmount','FeeAmount'))->where('MemberID=?',$MemberID)->where('OrderNo = ?',$OrderNo)->query()->fetch();
        }else{
            $re = $this->select()->setIntegrityCheck(false)->from('view_gifts',array('RealityAmount','FeeAmount'))->where('MemberID=?',$MemberID)->where('OrderNo = ?',$OrderNo)->query()->fetch();
        }
        return $re;
    }


    /**
     * 钱包账号解冻操作
     */
    public function unFreeze($memberID=0){
        if($memberID == 0){//处理所有
            $this->update(array("Status" => self::STATUS_VALID,'FreezeTime'=>'0000-00-00 00:00:00','FailureNum'=>0), array("Status =?" => self::STATUS_FREEZE,'FreezeTime<?'=>date('Y-m-d 00:00:00')));
        }else{
            $this->update(array("Status" => self::STATUS_VALID,'FreezeTime'=>'0000-00-00 00:00:00','FailureNum'=>0), array("MemberID =?" => $memberID));
        }
    }
}