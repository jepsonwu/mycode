<?php
/**
 * 银行卡账户信息
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Banks extends DM_Model_Table
{
	protected $_name = 'wallet_bank_card';
	protected $_primary = 'BankInfoID';
	
	/**
	 * 添加银行账户信息
	 * @param unknown $member_id
	 * @param unknown $bank
	 * @param unknown $bank_name
	 * @param unknown $user_name
	 * @param unknown $area_id
	 * @param unknown $card_code
	 */
	public function addBankInfo($member_id,$bank,$bank_name,$user_name,$area_id,$card_code)
	{
		$data = array(
						'MemberID'		=>	$member_id,
						'Bank'			=>	$bank,
						'BankName'		=>	$bank_name,
						'Username'		=>	$user_name,
						'AreaID'		=>	$area_id,
						'CardCode'		=>	$card_code
		);
		return $this->insert($data);
	}
	
	/**
	 * 获取用户账户信息
	 * @param int $member_id
	 */
	public function getMemberBankInfo($member_id)
	{
		//return $this->fetchAll(array('member_id = ?'=>$member_id));
        $select = $this->select()
                   ->from( $this->_name)
                   ->where('MemberID = ?', $member_id)
                   ->query()
                   ->fetchAll();
        return (null == $select) ? null : $select;
	}
	
	/**
	 * 根据账户ID 获取信息
	 * @param int $bankinfo_id
	 */
	public function getBankInfoById($bankinfo_id,$member_id)
	{
		$sql = 'select * from '.$this->_name.' where '.$this->_primary.' = :BankInfoID and MemberID = :MemberID';
		$bind = array('BankInfoID'=>$bankinfo_id,'MemberID'=>$member_id);
		return $this->_db->fetchRow($sql,$bind);
	}
}
