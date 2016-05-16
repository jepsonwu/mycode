<?php
/**
 * 资金管理
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Funds extends DM_Model_Table
{	
	//操作状态
	const OP_SUCCESS = 1;								//成功
	const OP_FAILURE = 0;								//失败
	const OP_BALANCE_NOT_ENOUGH = -1;					//余额不足
	
	//提现状态
	const REFUND_PENDING 		= '1';			//等待审核
	const REFUND_REJECTED 		= '2';			//已拒绝
	const REFUND_PROCESSED 		= '3';			//正在处理
	const REFUND_COMPLETED 		= '4';			//处理完成
	const REFUND_CANCELLED 		= '5';			//已取消
	const REFUND_BACK   		= '6';			//已退款
	
	//资金往来类型
	const FUND_FLOW_CREDIT       = 'CREDIT';                       //充值
	const FUND_FLOW_INCOME       = 'INCOME';                       //收入
	const FUND_FLOW_PAYOUT       = 'PAYOUT';                      
	const FUND_FLOW_DEPOSIT       = 'DEPOSIT';                     
	const FUND_FLOW_REFUND 		 = 'REFUND';				      
	const FUND_FLOW_AGENCY       = 'AGENCY';                    
	const FUND_FLOW_PUNISH     = 'PUNISH';                       
	const FUND_FLOW_DAMAGE     = 'DAMAGE';                    
	 
	const FUND_FLOW_CREDIT_FEE   = 'CREDIT_FEE';                       //充值手续费
	const FUND_FLOW_REFUND_FEE   = 'REFUND_FEE';                       //提现手续费
	const FUND_FLOW_BUY_FEE      = 'BUY_FEE';                       //买入手续费
	const FUND_FLOW_SELL_FEE     = 'SELL_FEE';                       //卖出手续费

	//冻结类型
	const FREEZE_REQUEST_REFUND  = '2';		           //申请提现
	const FREEZE_BUY_IN          = '2_FREEZE';                //限价买入冻结
	const FREEZE_SELL_OUT        = '3_FREEZE';                //限价卖出冻结
	const FREEZE_IME_SELL_OUT    = '4_FREEZE';                //市价卖出冻结
	
	//收费费类型
	const FEE_SETTING_CREDIT = 1;                          //充值手续费
	const FEE_SETTING_REFUND = 2;                          //提现手续费
	const FEE_SETTING_BUY    = 3;                          //买入手续费
	const FEE_SETTING_SELL   = 4;                          //卖出手续费
	
	const CURRENCY_CNY = 'CNY';//人民币
	const CURRENCY_USD = 'USD';//美元
	
	const PAYMENT_TYPE_INCOME = 1;//支付类型，收入
	const PAYMENT_TYPE_PAY = 2;//支付类型，支出
	
	

	/**
	 * 获取Finance下面 的 model 对象
	 * @param string $name
	 * @return object
	 */
	private static function getInstance($name)
	{
		static $modelArr = array();
		
		$key = 'DM_Model_Table_Finance_'.ucfirst($name);
		if(!isset($modelArr[$key])){
			$modelArr[$key] = new $key();
		}
		return $modelArr[$key];
	}
	
	/**
	 * 执行事务操作
	 * @param int $member_id
	 * @param string $currency
	 * @param float $amount
	 * @param string $ip
	 * @param int $type 支出/收入  类型，1收入，2支出
	 * @param int $orderType 明细类型，finance_type表外键
	 * @param int $freezes 冻结类型，0不需要冻结，1需要冻结，2解冻
	 */
	private function doAmountTransaction($member_id,$currency,$amount,$type,$orderType,$freezes,$ip,$relation_id = 0,$remark = '')
	{
		//更新额度
		$ret = self::getInstance('Amount')->updateMemberAmount($member_id, $currency, $type, $amount,$freezes);
		$flag = self::OP_FAILURE;
		if($ret > 0){
			$flag = self::OP_SUCCESS;
			//记录资金来往明细
			if(self::TransOpt('isAmountLog')){
				//查询账户信息
				$amountInfo = self::getInstance('Amount')->getMemberAmountInfo($member_id, $currency);
				//账户总额
				//$totalAmount = $amountInfo['Balance'] + $amountInfo['FreezeAmount'];
				if(self::getInstance('AmountLog')->addAmountLog($member_id, $type,$orderType, $currency, $amount,$amountInfo['Balance'],$amountInfo['FreezeAmount'], $ip, $relation_id,$remark)){
				
				}else{
					$flag = self::OP_FAILURE;
				}
			}
		}else{
			$flag = self::OP_FAILURE;
		}
		return $flag;
	}
	
	/**
	 * 获取或设置参数选项
	 * @param string|array $key
	 * @param boolean $isInit
	 * @return boolean|multitype:number
	 */
	private static function TransOpt($key = NULL,$val = NULL,$isInit = False)
	{
		static $tansOpt = array();
				
		if(empty($tansOpt) || $isInit){
			$tansOpt = array(
					'isAmountLog'=>1	//是否记录到member_amount_logs
			);
		}
		
		if(!is_null($key)){
			if(is_null($val) && !is_array($key)){
				if(isset($tansOpt[$key]) && $tansOpt[$key] == 1){
					return True;
				}else{
					return False;
				}
			}else{//赋值
				if(is_array($key)){
					foreach($key as $k=>$v){
						$tansOpt[$k] = $v;
					}	
				}else{
					$tansOpt[$key] = $val;
				}
			}
		}
		return $tansOpt;
	}
	
	/**
	 * 开始事务
	 */
	public  function tBegin()
	{
		$this->getAdapter()->beginTransaction();
	}
	
	/**
	 * 提交事务
	 */
	public  function tCommit()
	{
		$this->getAdapter()->commit();
	}
	
	/**
	 * 回滚事务
	 */
	public  function tRollBack()
	{
		$this->getAdapter()->rollBack();
	}


    /**
     * 初始化会员账户
     * @param int $member_id
     * @param int $currency
     */
    public function initMemberAmount($member_id)
    {
        $amountModel = self::getInstance('Amount');
        $amountInfo = $amountModel->memberAmountsInit($member_id);
        return $amountInfo;
    }
	
	/**
	 * 创建钱包订单
	 */
	public function getOrderSn(){
// 		return date('YmdHis').'00'.$type.'00'.$orderType;
		return DM_Model_Table_Finance_Order::getOrderSn();
	}
	
	/**
	 * 创建钱包订单
	 */
	public function createOrder($member_id,$type,$orderType,$Amount,$Status,$role,$FromObj,$orderSn = 0,$ip = '',$Currency = 'CNY',$ProductNo = 0,$Remark ='',$RelationType = 0){
		$orderModel = self::getInstance('Order');
		if(empty($orderSn)){
			$orderSn = $this->getOrderSn($member_id,$type,$orderType,$Amount);
		}
		$orderId = $orderModel->createOrder($member_id,$type,$orderSn,$orderType,$Currency,$Amount,$Status,$role,$FromObj,$ip,$ProductNo,$Remark,$RelationType);
		return $orderId;
	}
	
	/**
	 * 获取账户余额
	 * $member_id 会员编号
	 * $currency 货币类型
	 */
	public function getMemberBalance($member_id,$currency = 'CNY'){
		$amountModel = self::getInstance('Amount');
		return $amountModel->getMemberBalance($member_id,$currency);
	}

	/**
	 * 调整金额
	 * $MemberID 会员编号
	 * $Currency 货币类型
	 * $Amount  金额
	 * $type 收支类型，1收入，2支出
	 * $orderType 订单类型,对应值从wallet_order_type表中获取
	 * $relation_id 关联编号，对应wallet_order_list中的ID
	 */
	public function modifyAmount($MemberID,$Currency,$Amount,$type,$orderType,$ip,$remark = '',$relation_id = 0,$transOptions = NULL)
	{
		self::TransOpt($transOptions,NULL,True);
		return $this->doAmountTransaction($MemberID,$Currency,$Amount,$type,$orderType,0,$ip,$relation_id,$remark);
	}
	
	/**
	 * 添加提现记录
	 * $MemberID 会员编号
	 * $OrderID 订单编号
	 * $BankID 银行卡编号
	 * $Currency 货币类型
	 * $Amount 提现金额
	 * $FeeAmount 手续费
	 */
	public function applyRefund($MemberID,$OrderID,$BankID,$Currency,$Amount,$FeeAmount=0){
		$amountInfo = self::getInstance('Amount')->getMemberAmountInfo($MemberID, $Currency);
		if($amountInfo['Balance']<$Amount){
			return self::OP_FAILURE;
		}
		//添加提现记录
		$refundModel = self::getInstance('Refund');
		$refundId = $refundModel->applyRefund($MemberID,$OrderID,$BankID,$Currency,$Amount,$FeeAmount);
		if(!$refundId){//添加提现记录失败
			return self::OP_FAILURE;
		}
		//添加冻结记录
		$res = $this->freeze($MemberID,$Currency,$Amount,2,$refundId,'提现冻结');
		if(!$res){
			return self::OP_FAILURE;
		}
		
		$freezes = 1;//需要冻结资金
		$ret = self::getInstance('Amount')->updateMemberAmount($MemberID,$Currency,2,$Amount,$freezes);
		if(!$ret){
			return self::OP_FAILURE;
		}
		return self::OP_SUCCESS;
	}
	
	/**
	 * 完成提现
	 */
	public function finishRefund($member_id,$refund_application_id,$ip = '',$remark = ''){
		//查询申请提现信息
		$refundApplicationInfo = self::getInstance('Refund')->getApplicationInfo($refund_application_id, $member_id);
		if(empty($refundApplicationInfo)){
			//处理错误
			return array('code'=>1,'msg'=>'提现记录不存在');
		}
		//判断冻结记录
		$freezeModel = self::getInstance('Freezes');
		$freezeInfo = $freezeModel->getFreezeInfoByRelate($member_id,$refund_application_id,self::FREEZE_REQUEST_REFUND);//获取相应的冻结记录
		if(empty($freezeInfo)){
			return array('code'=>1,'msg'=>'未查询到冻结款项记录');
		}
		
		//资金解冻
		$ret = $this->unfreeze($member_id,$freezeInfo['FreezeID'],self::FREEZE_REQUEST_REFUND);//解冻
		if(!$ret){
			return array('code'=>1,'msg'=>'解冻冻结款项失败');
		}
		$ret = self::getInstance('Refund')->setApplicationStatus($refund_application_id, $member_id,4,'');//更新提现申请记录状态
		if(!$ret){
			return array('code'=>1,'msg'=>'更新提现申请记录状态失败');
		}
		
		//扣掉提现的金额
		empty($remark) && $remark = "提现完成";
		$ret = $this->modifyAmount($member_id,$refundApplicationInfo['Currency'],$refundApplicationInfo['ApplicationAmount'],2,2,$ip,$remark,$refundApplicationInfo['RelationID']);
		if(!$ret){
			return array('code'=>1,'msg'=>'扣除提现金额失败');
		}
		
		//更新订单状态
		
		return array('code'=>0);
	}
	
	/**
	 * 拒绝提现
	 */
	public function rejectRefund($member_id,$application_id,$rejectReason='',$ip = ''){
		$freezeModel = self::getInstance('Freezes');
		$freezeInfo = $freezeModel->getFreezeInfoByRelate($member_id,$application_id,self::FREEZE_REQUEST_REFUND);//获取相应的冻结记录
		if(empty($freezeInfo)){
			return array('code'=>1,'msg'=>'未查询到冻结款项记录');
		}
		$ret = $this->unfreeze($member_id,$freezeInfo['FreezeID'],self::FREEZE_REQUEST_REFUND);//解冻
		if(!$ret){
			return array('code'=>1,'msg'=>'解冻冻结款项失败');
		}
		$ret = self::getInstance('Refund')->setApplicationStatus($application_id, $member_id,2,$rejectReason);//更新提现申请记录状态
		if(!$ret){
			return array('code'=>1,'msg'=>'更新提现申请记录状态失败');
		}
		//更新订单状态
		
		return array('code'=>0);
	}
	
	/**
	 * 提现退款（不常用）
	 */
	public function backRefund($member_id,$refund_application_id,$rejectReason='',$ip = ''){
		//查询申请提现信息
		$refundApplicationInfo = self::getInstance('Refund')->getApplicationInfo($refund_application_id, $member_id);
		if(empty($refundApplicationInfo)){
			//处理错误
			return array('code'=>1,'msg'=>'提现记录不存在');
		}
		//判断冻结记录
		$freezeModel = self::getInstance('Freezes');
		$freezeInfo = $freezeModel->getFreezeInfoByRelate($member_id,$refund_application_id,self::FREEZE_REQUEST_REFUND);//获取相应的冻结记录
		if(empty($freezeInfo)){
			return array('code'=>1,'msg'=>'未查询到冻结款项记录');
		}
		
		//资金解冻
		$ret = $this->unfreeze($member_id,$freezeInfo['FreezeID'],self::FREEZE_REQUEST_REFUND);//解冻
		if(!$ret){
			return array('code'=>1,'msg'=>'解冻冻结款项失败');
		}
		$ret = self::getInstance('Refund')->setApplicationStatus($refund_application_id, $member_id,4,'');//更新提现申请记录状态
		if(!$ret){
			return array('code'=>1,'msg'=>'更新提现申请记录状态失败');
		}
		
		//扣掉提现的金额
		empty($remark) && $remark = "提现完成";
		$ret = $this->modifyAmount($member_id,$refundApplicationInfo['Currency'],$refundApplicationInfo['ApplicationAmount'],2,2,$ip,$remark,$refundApplicationInfo['RelationID']);
		if(!$ret){
			return array('code'=>1,'msg'=>'扣除提现金额失败');
		}
		
		//更新订单状态
		
		return array('code'=>0);
	}
		
	/**
	 * 资金冻结
	 * @param int $MemberID
	 * @param string $Currency
	 * @param float $Amount
	 * @param string $ip
	 */
	public function freeze($MemberID,$Currency,$Amount,$relationType,$relation_id,$remark='')
	{
		//检查可用额度
		$amountInfo = self::getInstance('Amount')->getMemberAmountInfo($MemberID, $Currency);
		if(empty($amountInfo) || $amountInfo['Balance'] < $Amount){
			//可用余额不足
			return self::OP_BALANCE_NOT_ENOUGH;
		}else{
			//插入冻结记录表
			$freezeID = self::getInstance('Freezes')->addFreeze($MemberID,$relationType,$relation_id,$Currency,$Amount,$remark);
			if($freezeID){
				return $freezeID;
			}else{
				return self::OP_FAILURE;
			}
		}
	}
	
	/**
	 * 解冻资金
	 * @param int $member_id
	 * @param int $freezeId
	 * @param int $freezeType
	 * @param string $ip
	 */
	public function unfreeze($member_id,$freezeId,$relationType){
		$freezeInfo = self::getInstance('Freezes')->getFreezeInfo($member_id,$freezeId,$relationType);
		if(empty($freezeInfo)){
			return self::OP_FAILURE;
		}
		
		//检查冻结状态
		if(DM_Model_Table_Finance_Freezes::UNFREEZE_STATUS == $freezeInfo['Status']){
			return self::OP_FAILURE;
		}
		
		//额度
		$amount = $freezeInfo['Amount'];
		$currency = $freezeInfo['Currency'];
		$ret = self::getInstance('Amount')->updateMemberAmount($member_id,$currency,1,$amount,2);
		if($ret){
			//更改状态
			self::getInstance('Freezes')->setUnFreezeStatus($freezeId,$member_id);
		   
			return self::OP_SUCCESS;	
		}else{
			return self::OP_FAILURE;
		}
	}
}