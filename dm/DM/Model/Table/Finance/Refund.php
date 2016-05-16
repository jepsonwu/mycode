<?php
/**
 * 提现
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Refund extends DM_Model_Table
{
	protected $_name = 'wallet_refund_applications';
	protected $_primary = 'RefundApplicationID';
	
	//提现状态
	const REFUND_PENDING 		= '1';			//等待审核
	const REFUND_REJECTED 		= '2';			//已拒绝
	const REFUND_PROCESSED 		= '3';			//正在处理
	const REFUND_COMPLETED 		= '4';			//处理完成
	const REFUND_CANCELLED 		= '5';			//已取消
	const REFUND_BACK   		= '6';			//已退款

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
		$RealityAmount = $Amount-$FeeAmount;
		//$status = $Amount<1000?3:1;
		$status = 3;
		$data = array(
					'MemberID'			=>	$MemberID,
					'RelationID'		=>	$OrderID,
					'BankInfoID'  	 	=>	$BankID,
					'Currency'  	    =>	$Currency,
					'ApplicationAmount' =>  $Amount,
					'FeeAmount'       	=>  $FeeAmount,
					'RealityAmount'		=>	$RealityAmount,
					'Status'	        =>	$status
						
		);
		return $this->insert($data);
	}
	
	/**
	 * 修改申请的状态
	 * @param int $application_id
	 * @param int $member_id
	 * @param string $status
	 * @param strint $reject_reason
	 */
	public function setApplicationStatus($application_id,$member_id,$status,$reject_reason = '')
	{
		if(DM_Model_Table_Finance_Funds::REFUND_REJECTED != $status && DM_Model_Table_Finance_Funds::REFUND_BACK != $status){
			$reject_reason = '';
		}
		
		$data = array(
					'Status' => $status,
					'RejectReason' => $reject_reason,
		            'ConfirmDate' => date('Y-m-d H:i:s')
		);
		
		return $this->update($data, array($this->_primary.' = ?'=>$application_id,'MemberID = ? '=>$member_id));
	}
	
	/**
	 * 获取申请信息
	 * @param int $application_id
	 * @param int $member_id
	 * @param string|array $fields
	 */
	public function getApplicationInfo($application_id,$member_id,$fields = '*')
	{
		if(is_array($fields)){
			$fields = implode(',',$fields);
		}
		$sql = 'select '.$fields.' from '.$this->_name.' where '.$this->_primary.' = :application_id and MemberID = :member_id';
		return $this->_db->fetchRow($sql,array('application_id'=>$application_id,'member_id'=>$member_id));
	}
	
	/**
	 * 获取申请信息
	 * @param int $order_id
	 * @param int $member_id
	 * @param string|array $fields
	 */
	public function getRefundInfoByOid($member_id,$order_id,$fields = '*')
	{
		if(is_array($fields)){
			$fields = implode(',',$fields);
		}
		$sql = 'select '.$fields.' from '.$this->_name.' where RelationID = :order_id and MemberID = :member_id';
		return $this->_db->fetchRow($sql,array('order_id'=>$order_id,'member_id'=>$member_id));
	}

    /**
     * 修改提现手续费、实际支付费用
     * @param int $application_id
     * @param int $member_id
     * @param float $fee_amount
     */
    public function updateRefundFee($application_id,$member_id,$fee_amount)
    {
        //获取
        $applicationInfo = $this->getApplicationInfo($application_id, $member_id);

        if(empty($applicationInfo)){
            return false;
        }

        $application_amount = $applicationInfo['ApplicationAmount'];

        if($fee_amount > $application_amount){
            return false;
        }

        //计算实际支付
        $reality_amount = $application_amount - $fee_amount;

        $data = array(
                    'FeeAmount'		=>	$fee_amount,
                    'RealityAmount'	=>	$reality_amount
                );
        $ret = $this->update($data, array($this->_primary.' = ?'=>$application_id,'MemberID = ? '=>$member_id));
        return $ret === false ? false : true;
    }
    
    /**
     * 获取指定用户账户明细
     * @param int $member_id
     * @param string $currency
     */
    public function getRefundLog90d($member_id)
    {
        $select = $this->_db->select();
        $select->from($this->_name)
        ->where('MemberID =?',$member_id);
        $select->where('ApplicationDate >= ?', date('Y-m-d H:i:s', time()-86400*90))->order('RefundApplicationID desc');
        return $this->_db->fetchAll($select);
    }
	
	/**
	 * 获取提现次数
	 * $dateTime 当为空时表示当天
	 */
	public function getRefundNum($member_id,$dateTime = ''){
		$select = $this->_db->select();
        $select->from($this->_name,array('num'=>'count("*")'))->where('MemberID =?',$member_id)->where('Status in(1,3,4)');
		$dateTime = empty($dateTime)?date('Y-m-d 00:00:00'):date('Y-m-d 00:00:00',strtotime($dateTime));
        $select->where('ApplicationDate >= ?',$dateTime);
		$re = $this->_db->fetchRow($select);
		return empty($re)?0:$re['num'];
	}
	
	/**
	 * 获取待处理提现列表
	 */
	public function getRefundList($num,$status=self::REFUND_PROCESSED,$member_id=0){
		$select = $this->_db->select();
        $select->from(array('t'=>'wallet_refund_applications'),array('bankId'=>'BankInfoID','amount'=>'RealityAmount','refundId'=>'RefundApplicationID','MemberID','RelationID'));
		$select->joinLeft('wallet_order_list as m', 'm.OID = t.RelationID and m.MemberID=t.MemberID',array('order_Id'=>'m.OrderNo','oid'=>'m.OID'));
		$member_id>0 && $select->where('t.MemberID =?',$member_id);
		$select->where('t.Status=?',$status)->where("t.BatchNo=''")->order("t.RefundApplicationID desc")->limit($num);
		return $this->_db->fetchAll($select);
	}
}