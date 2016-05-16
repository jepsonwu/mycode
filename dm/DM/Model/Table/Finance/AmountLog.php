<?php
/**
 * 用户资金账户明细
 * @author Mark
 *
 */
class DM_Model_Table_Finance_AmountLog extends DM_Model_Table
{
	protected $_name = 'wallet_amount_logs';
	protected $_primary = 'AmountLogID';

	
	/**
	 * 添加资金明细记录
	 * @param int $member_id
	 * @param string $type
	 * @param string $orderType
	 * @param string $currency
	 * @param float $amount
	 * @param float $balance
	 * @param string $ip
	 * @param int $relation_id
	 */
	public function addAmountLog($member_id,$type,$orderType,$currency,$amount,$balance,$freezeAmount,$ip,$relation_id,$remark='')
	{
		$data = array(
						'MemberID'=>$member_id,
						'Type'=>$type,
						'orderType'=>$orderType,
						'RelationID'=>$relation_id,
						'Currency'=>$currency,
						'Amount'=>$amount,
						'Balance'=>$balance,
						'FreezeAmount'=>$freezeAmount,
						'Ip'=>$ip,
						'Remark'=>$remark
			);
		return $this->insert($data);
	}
	
	/**
	 * 获取指定用户账户明细
	 * @param int $member_id
	 * @param string $currency
	 */
	public function getAmountLog($member_id,$currency=null)
	{
        $select = $this->_db->select();
                $select->from($this->_name)
                       ->where('MemberID =?',$member_id);
        if($currency){
                $select->where('Currency =?',$currency);
        }
		return $this->_db->fetchAll($select);
	}
}