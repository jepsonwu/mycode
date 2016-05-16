<?php
/**
 *  加群申请
 * @author Mark
 *
 */
class Model_IM_GroupApply extends Zend_Db_Table
{
	protected $_name = 'group_applies';
	protected $_primary = 'ApplyID';
	
	/**
	 *  插入加群申请
	 * @param int $applyMemberID
	 * @param string $groupID
	 * @return mixed
	 */
	public function addApply($applyMemberID,$groupID,$applyContent)
	{
		$sql = "insert into group_applies(GroupID,ApplyMemberID,ApplyContent) values(:GroupID,:ApplyMemberID,:ApplyContent) on duplicate key update ApplyTime = '".date('Y-m-d H:i:s')."',ApplyContent = '".$applyContent."'";
		$this->getAdapter()->query($sql,array('GroupID'=>$groupID,'ApplyMemberID'=>$applyMemberID,'ApplyContent'=>$applyContent));
		return $this->getAdapter()->lastInsertId();
	}
	
	/**
	 *  获取加群申请信息
	 * @param int $applyID
	 */
	public function getApplyInfo($applyID)
	{
		$info = $this->select()->where('ApplyID = ?',$applyID)->query()->fetch();
		return $info ? $info : array();
	}
}