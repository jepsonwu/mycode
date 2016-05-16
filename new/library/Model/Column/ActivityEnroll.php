<?php
/**
 * 专栏
 *
 * @author Jeff
 *
 */
class Model_Column_ActivityEnroll extends Zend_Db_Table
{
	protected $_name = 'column_activity_enroll';
	protected $_primary = 'EnrollID';
	
	public function enroll($activityID,$memberID,$realName,$mobile,$oid=0,$money=0,$realMoney=0,$fee=0)
	{
		if(!$this->getEnrollInfo($activityID, $memberID))
		{
			if(empty($oid) && empty($money)){
				$data = array('ActivityID'=>$activityID,'MemberID'=>$memberID,'RealName'=>$realName,'Mobile'=>$mobile);
			}else{
				$data = array('ActivityID'=>$activityID,'MemberID'=>$memberID,'RealName'=>$realName,'Mobile'=>$mobile,
						'OID'=>$oid,'Amount'=>$money,'RealityAmount'=>$realMoney,'FeeAmount'=>$fee);
			}
			$insertID = $this->insert($data);
			if($insertID > 0){
				$activityModel = new Model_Column_Activity();
				$activityModel->increaseEnrollNum($activityID);
			}
		}
		return $insertID;
	}
	
	/**
	 *  获取信息
	 * @param int $memberID
	 */
	public function getEnrollInfo($activityID,$memberID)
	{
		$select = $this->select()->where('ActivityID = ?',$activityID)->where('MemberID = ?',$memberID);
		$info = $select->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 获取某个活动的报名人数
	 * @param unknown $activityID
	 */
	public function getEnrollMembers($activityID)
	{
		$select = $this->select()->from($this->_name,array('MemberID'))->where('ActivityID = ?',$activityID);
		$info = $select->query()->fetchAll();
		return $info ? $info : array();
	}
}