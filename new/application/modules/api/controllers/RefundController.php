<?php
/**
 * 钱包-提现
 * User: Hale
 * Date: 15-12-22
 * Time: 上午10:00
 */
class Api_RefundController extends Action_Api
{
	public function init()
	{
		parent::init();
        $actionArr = array('handle-refund');
		if(!in_array($this->_getParam('action'),$actionArr)){
			$this->isLoginOutput();
		}
	}
    
    /**
     * 获取当天的提现次数
     */
    public function refundNumAction(){
		$memberID = $this->memberInfo->MemberID;
		$refundModel = new DM_Model_Table_Finance_Refund();
        $refundNum = $refundModel->getRefundNum($memberID);
        $limitNum = DM_Controller_Front::getInstance()->getConfig()->yee->LimitNum;
        $limitAmount = DM_Controller_Front::getInstance()->getConfig()->yee->LimitAmount;
        $this->returnJson(parent::STATUS_OK, null, array('Num'=>$refundNum,'LimitAmount'=>$limitAmount,'Surplus'=>$refundNum>$limitNum?0:($limitNum-$refundNum),'MiniAmount'=>0.5,'FeeRatio'=>0.5));
    }
    
    /**
     * 计算手续费
     */
   public function refundFeeAction()
   {
        $Money = $this->_request->getParam('Money', 0);

        if(!preg_match("/^([1-9]{1}\d*(.(\d){1,2})?)|(0.(\d){1,2})$/",$Money)){
            $this->returnJson(parent::STATUS_FAILURE,"请输入有效的金额");
        }

        if($Money<= 0.5){
            $this->returnJson(parent::STATUS_FAILURE,"提现金额不能低于0.5元");
        }
        $feeRatio = DM_Controller_Front::getInstance()->getConfig()->system->refund_feeRatio;	
        $fee = number_format($Money * $feeRatio,2,'.','');
        $fee = $fee < 0.5 ? 0.5 : $fee;
        $this->returnJson(parent::STATUS_OK,'',array('Fee'=>$fee));
   }
    
    /**
     * 申请提现
     */
    public function applyRefundAction(){
        $memberID = $this->memberInfo->MemberID;
		$BankID = (int) $this->_request->getParam('BankID', 0);
        if(empty($BankID)){
            $this->returnJson(parent::STATUS_FAILURE,"请选择银行卡");
        }
        $Money = $this->_request->getParam('Money', 0);
        if(!preg_match("/^([1-9]{1}\d*(.(\d){1,2})?)|(0.(\d){1,2})$/",$Money)){
           $this->returnJson(parent::STATUS_FAILURE,"请输入有效的金额");
        }
        if($Money<= 0.5){
            $this->returnJson(parent::STATUS_FAILURE,"提现金额不能低于0.5元");
        }
        $city = $this->_request->getParam('City','');
        $limitAmount = DM_Controller_Front::getInstance()->getConfig()->yee->LimitAmount;
        if($Money>$limitAmount){
            $this->returnJson(parent::STATUS_FAILURE,"每笔提现金额不能大于".number_format($limitAmount,0,'.',',')."元");
        }
        
        //判断这个银行卡编号是否有效
        $bindCard = new Model_Wallet_WalletBindCard();
        $BindCardInfo = $bindCard->getBindCardByMID($memberID);
        if(empty($BindCardInfo)){
            $this->returnJson(parent::STATUS_FAILURE,"未绑定银行卡");
        }
        $bindBankID = isset($BindCardInfo[0]['BID'])?$BindCardInfo[0]['BID']:0;
        if($bindBankID!=$BankID){
            $this->returnJson(parent::STATUS_FAILURE,"该银行卡无效");
        }
        
        $needSaveCity = false;
        if(empty($BindCardInfo[0]['City'])){
            if(empty($city)){
                $version = $this->_request->getParam('currentVersion', '1.0.0');
                if(version_compare($version, '2.4.2')){
                    $this->returnJson(parent::STATUS_FAILURE,"请选择银行卡的归属地,请升级到最新版本");
                }else{
                    $this->returnJson(parent::STATUS_FAILURE,"请选择银行卡的归属地");
                }
            }
            $needSaveCity = true;
        }
        
        $payPassword = $this->_request->getParam('payPassword','');
		$walletModel = new Model_Wallet_Wallet();
        $check = $walletModel->checkPayPasswordAction($memberID, $payPassword);
        if($check['flag']<0){
            $this->returnJson($check['flag'], null,new stdClass());
        }
        
        //判断余额是否够用
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $balance = $fundsModel->getMemberBalance($memberID,"CNY");
        if($balance<$Money){
            $this->returnJson(0,'可用金额不足');
        }
        //获取银行的相关信息
        $cardModel = new Model_Wallet_WalletBankCard();
        $bankInfo = $cardModel->getCardInfoById($bindBankID);
        if(empty($bankInfo)){
            $this->returnJson(parent::STATUS_FAILURE,"该银行卡无效");
        }
        $bankRemark = $bankInfo['BankName'].'('.substr($bankInfo['CardNo'], -4).')'."提现";
        
        $limitNum = DM_Controller_Front::getInstance()->getConfig()->yee->LimitNum;
        $refundModel = new DM_Model_Table_Finance_Refund();
        $refundNum = $refundModel->getRefundNum($memberID);
        if($refundNum>=$limitNum){
            $this->returnJson(parent::STATUS_FAILURE,"每日只可提现".$limitNum."次");
        }
        
        //开启事务
        $fundsModel->tBegin();
        if($needSaveCity){
            $bankModel = new Model_Wallet_WalletBankCard();
            $bankModel->update(array('City'=>$city), array('BID=?'=>$BindCardInfo[0]['BID']));
        }
        //创建提现订单
        $type = 2;//支出
        $orderType = 2;//提现
        $Status = 1;//处理中
        $role = 1;//发起方
        $ip = $this->_request->getClientIp();
        try{
            $orderId = $fundsModel->createOrder($memberID,$type,$orderType,$Money,$Status,$role,0,0,$ip,'CNY',0,$bankRemark);
            if(!$orderId){
                throw new Exception('提现申请失败');
            }
            $feeRatio = DM_Controller_Front::getInstance()->getConfig()->system->refund_feeRatio;	
            $FeeAmount = number_format($Money * $feeRatio,2,'.','');//手续费
            $FeeAmount = $FeeAmount < 0.5 ? 0.5 : $FeeAmount;
            $ret = $fundsModel->applyRefund($memberID,$orderId,$BankID,'CNY',$Money,$FeeAmount);
            if(!$ret){
                throw new Exception('提现申请失败');
            }
            //$messageModel = new Model_Message();
            //$messageModel->addMessage($memberID, Model_Message::MESSAGE_TYPE_REFUND,$orderId, Model_Message::MESSAGE_SIGN_WALLET);
            $fundsModel->tCommit();
            $this->returnJson(parent::STATUS_OK);
        }catch(Exception $e){
            $fundsModel->tRollBack();
            $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
        }
    }
}