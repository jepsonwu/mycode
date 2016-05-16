<?php
/**
 * 用户资金账户
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Amount extends DM_Model_Table
{
	protected $_name = 'wallet_amounts';
	protected $_primary = 'MemberAmountID';
	
	const CURRENCY_CNY='CNY';
	const CURRENCY_USD='USD';
	const CURRENCY_EURO='EURO';
	
	//账户类型
	private $allowAmountType = array('CNY');
	
	/**
	 * 判断账户类型
	 * @param unknown $currency
	 */
	private function checkMemberAmountType($currency)
	{
		return in_array($currency,$this->allowAmountType);
	}
	
	/**
	 * 初始化账户资金
	 * @param int $member_id
	 * @param string $currency
	 * @param int $is_default
	 */
	private function initMemberAmount($member_id,$currency)
	{
		if(!$this->checkMemberAmountType($currency)){
			return false;
		}
		$amountInfo = $this->getMemberAmountInfo($member_id, $currency);
		if(empty($amountInfo)){
			//是否为默认账户
			$is_default = 0;
			//查询是否已有默认账户
			$row = $this->fetchRow(array('MemberID = ?' =>$member_id,'IsDefault = ?'=>1));
			//设置为默认账户
			if(is_null($row)){
				$is_default = 1;
			}
			
			$data = array(
							'MemberID'		=>	$member_id,
							'Currency'		=>	$currency,
							'Balance'		=>	0,
							'FreezeAmount'	=>	0,
							'IsDefault'	=>	$is_default
			);
			$amountID = $this->insert($data);
			if(!$amountID){
				return false;
			}
		}
		
		//判断红包账号记录是否存在
		$sql = 'select WID from wallet where MemberID = :member_id';
		$walletInfo = $this->getAdapter()->fetchRow($sql,array('member_id'=>$member_id));
		if(empty($walletInfo)){//初始化红包记录
			$data = array(
							'MemberID'		=>	$member_id,
							'Status'		=>	1,
							'CreateTime'	=>	date('Y-m-d H:i:s')
			);
			$walletID = $this->getAdapter()->insert('wallet',$data);
			if(!$walletID){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 初始化会员账户(各币种账户)
	 * @param int $member_id
	 */
	public function memberAmountsInit($member_id)
	{
		$ret = true;
		foreach($this->allowAmountType as $currency){
			$ret && $this->initMemberAmount($member_id, $currency);
		}
		return $ret;
	}
	
	/**
	 * 获取用户账户信息
	 * @param int $member_id
	 * @param string $currency
	 * @param bool | int $isExLock
	 */
	public function getMemberAmountInfo($member_id,$currency,$isExLock = False)
	{
		$sql = 'select * from '.$this->_name.' where MemberID = :member_id and Currency = :currency';
		if(True === $isExLock){
			//写锁
			$sql .= ' for update';
		}elseif(False === $isExLock){
			//读锁
			$sql .= ' lock in share mode ';
		}
		$bind = array('member_id'=>$member_id,'currency'=>$currency);
		return $this->getAdapter()->fetchRow($sql,$bind);
	}
	
	/**
	 * 更改账户金额信息      不要直接调用该方法
	 * @param int $member_id
	 * @param string $currency
	 * @param string $type 支出/收入  类型，1收入，2支出
	 * @param float $amount
	 * @param int $freezes 冻结类型，0不需要冻结，1需要冻结，2解冻
	 */
	public function updateMemberAmount($member_id,$currency,$type,$amount,$freezes=0)
	{
		//查询账户信息
		$amountInfo = $this->getMemberAmountInfo($member_id, $currency,True);
		
		//不存在则初始化
		if(empty($amountInfo)){
			$insert_id = $this->initMemberAmount($member_id, $currency);
			if(!$insert_id){
				return false;
			}
			$amountInfo = $this->getMemberAmountInfo($member_id, $currency,True);
		}

		$amountData['Balance'] = $amountInfo['Balance'];
		$amountData['FreezeAmount'] = $amountInfo['FreezeAmount'];
		$amountData = self::processTypeAndAmount($amountData, $type, $amount,$freezes);
		if($amountData['Balance']<0 || $amountData['FreezeAmount']<0){
			return false;
		}
		
		//更新金额
		$this->update($amountData,array('MemberAmountID = ? '=>$amountInfo['MemberAmountID']));
		
		return $amountInfo['MemberAmountID'];		
	}

	
	/**
	 * 处理不同的类型
	 * @param string $type 支出/收入  类型，1收入，2支出
	 * @param float $amount
	 * @param int $freezes 冻结类型，0不需要冻结，1需要冻结，2解冻
	 */
	private static function processTypeAndAmount($amountData,$type,$amount,$freezes)
	{
	    if($type==1){
	        $amountData['Balance'] += $amount;
	    }elseif($type==2){
	        $amountData['Balance'] -= $amount;
	    }

	    if($freezes==1){
	        $amountData['FreezeAmount'] += $amount;
	    }elseif($freezes==2){
	        $amountData['FreezeAmount'] -= $amount;
	    }
		return $amountData;
	     
// 		switch ($type)
// 		{
// 			//收入
// 			case DM_Model_Table_Finance_Funds::FUND_FLOW_INCOME:
// 				$amountData['Balance'] += $amount;
// 				break;
// 			//提现
// 			case DM_Model_Table_Finance_Funds::FUND_FLOW_REFUND:
// 				$amountData['FreezeAmount'] -= $amount;
// 				break;
// 			//违约扣款
// 			case DM_Model_Table_Finance_Funds::FUND_FLOW_PUNISH:
// 				$amountData['Balance'] -= $amount;
// 				break;
// 			//申请提现
// 			case DM_Model_Table_Finance_Funds::FREEZE_REQUEST_REFUND:
// 				$amountData['Balance'] -= $amount;
// 				$amountData['FreezeAmount'] += $amount;
// 				break;
// 		}
	}
    /**
        * 得到会员的可用余额
        * 如果第二个参数为null，取默认货币
        *
        * @param $member_id
        * @param null $currency
        */
       public function getMemberBalance($member_id, $currency = "CNY")
       {
           $select = $this->_db->select();
           $select->from($this->_name,array('Balance'));
           $select->where("MemberID = ?", $member_id);
           if(null === $currency){
               $select->where("IsDefault = 1");
           }else{
               $select->where("Currency = ?", $currency);
           }
           $select->limit(1);
           $balance =  $this->_db->fetchOne($select);
           return !empty($balance) ? $balance : 0;
       }
       
       /**
        * 得到会员的所有账户可用余额信息
        * 如果第二个参数为null，取默认货币
        *
        * @param $member_id
        * @param null $currency
        */
       public function getAllBalance($member_id)
       {
           $select = $this->_db->select();
           $select->from($this->_name);
           $select->where("MemberID = ?", $member_id);

           $balance =  $this->_db->fetchAll($select);
           if (!$balance){
               $this->memberAmountsInit($member_id);
               $balance =  $this->_db->fetchAll($select);
           }
           
           if (!$balance){
               return array();
           }
           
           $info=array();
           foreach ($balance as $value){
               $info[$value['Currency']]=$value;
           }
           
           return $info;
       }
}