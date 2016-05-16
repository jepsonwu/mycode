<?php
/**
 * 冻结资金
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Freezes extends DM_Model_Table
{
	protected $_name = 'wallet_freezes';
	protected $_primary = 'FreezeID';
	
	const FREEZE_STATUS = '1';
	const UNFREEZE_STATUS = '2';

	/**
	 * 添加冻结记录
	 * @param int $member_id
	 * @param string $relation_type
	 * @param string $currency
	 * @param float $amount
	 */
	public function addFreeze($MemberID,$orderType,$relation_id,$Currency,$Amount,$remark='')
	{
		$data = array(
						'MemberID'		=>	$MemberID,				
						'RelationType'	=>	$orderType,
						'RelationID'	=>	$relation_id,
						'Currency'		=>	$Currency,
						'Amount'		=>	$Amount,
						'Status'        =>  1,
						'Remark'		=>	$remark
					);
		return $this->insert($data);		
	}
	
	/**
	 * 根据member_id、relation_id、relation_type 获取对应的冻结记录
	 * @param int $member_id
	 */
	public function getFreezeInfo($member_id,$freeze_id,$relation_type)
	{
		$bind = array(	
						'member_id'=>$member_id,
						'relation_type'=>$relation_type,
						'freeze_id'=>$freeze_id);
		return $this->_db->fetchRow('select * from '.$this->_name.' where MemberID =:member_id and RelationType =:relation_type and FreezeID =:freeze_id',$bind);
	}

    /**
   	 * 根据member_id,relation_id,relation_type 获取对应的冻结记录
   	 *
   	 * @param int $member_id
   	 * @param int $relation_id
   	 * @param string $relation_type
   	 */
   	public function getFreezeInfoByRelate($member_id,$relation_id,$relation_type)
   	{
   		$bind = array(
   				'member_id'=>$member_id,
   				'relation_type'=>$relation_type,
   				'relation_id'=>$relation_id);
   		return $this->_db->fetchRow('select * from '.$this->_name.' where MemberID =:member_id and RelationType =:relation_type and RelationID =:relation_id for update',$bind);
   	}
	
	/**
	 * 更改状态为 未冻结
	 * @param int $freeze_id
	 * @param int $member_id
	 */
	public function setUnFreezeStatus($freeze_id,$member_id)
	{
		$data = array('status'=>self::UNFREEZE_STATUS,'unFreezeTime'=>date('Y-m-d H:i:s'));
		return $this->update($data, array('FreezeID = ? '=>$freeze_id,'MemberID = ? '=>$member_id));
	}
}