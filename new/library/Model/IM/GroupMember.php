<?php
/**
 * 群成员
 * @author Mark
 *
 */
class Model_IM_GroupMember extends Zend_Db_Table
{
	protected $_name = 'group_member';
	protected $_primary = 'AID';
	
	/**
	 *  添加群成员
	 * @param int $memberID
	 * @param string $groupID
	 */
	public function addMember($memberID,$groupID,$syncTime = null)
	{
		$tmpInfo = $this->getInfo($memberID, $groupID);
		if(empty($tmpInfo)){
			$data['MemberID'] = $memberID;
			$data['GroupID'] = $groupID;
			
			if(!is_null($syncTime)){
				$data['SyncTime'] = $syncTime;
			}
			return $this->insert($data);
		}else{
			if(!is_null($syncTime)){
				$this->update(array('SyncTime'=>$syncTime),array('AID = ?'=>$tmpInfo['AID']));
			}
		}
		return true;
	}
	
	/**
	 *  获取信息
	 * @param int $memberID
	 * @param int $groupID
	 */
	public function getInfo($memberID, $groupID)
	{
		$select = $this->select();
		$select->where('MemberID = ?',$memberID)->where('GroupID = ?',$groupID);
		return $select->query()->fetch();
	}
	
	/**
	 *  退群
	 * @param int $memberID
	 * @param string $groupID
	 */
	public function quit($memberID,$groupID)
	{
		return $this->delete(array('GroupID = ?'=>$groupID,'MemberID = ?'=>$memberID));
	}
	
	/**
	 *  获取我所加的群组
	 * @param int $memberID
	 */
	public function getJoinedGroups($memberID,$isSelf = 0,$extraData1 = 0)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from(array('gm'=>$this->_name),null)->joinInner(array('g'=>'group'), 'gm.GroupID = g.GroupID',array('GroupID','GroupName','NowUserCount','OwnerID'));
		$select->where('gm.MemberID = ?',$memberID)->where('Status = 1');
		if(!$isSelf){
			$select->where('gm.IsPublic = 1')->where('g.IsPublic = 1');
		}
		$res = $select->query()->fetchAll();
		
		if(!empty($res) && $extraData1){
			$groupFocusModel = new Model_IM_GroupFocus();
			$helperModel = new Model_MessageHelper();
			foreach($res as &$item){
				$helperInfo = $helperModel->getinfo($memberID,$item['GroupID'],2);
				$item['Messagehelper'] = empty($helperInfo)?0:1;
				$item['Focus'] = $groupFocusModel->getFocusInfo($item['GroupID'],null,'FocusID');
			}
		}
		
		return $res ? $res : array();
	}
	
	/**
	 * @param string $groupID
	 * @param int $memberID
	 * @param int $isPublic
	 */
	public function updateInfo($groupID, $memberID,$isPublic)
	{
		$tmpInfo = $this->getInfo($memberID, $groupID);
		if(empty($tmpInfo)){
			$data['MemberID'] = $memberID;
			$data['GroupID'] = $groupID;
			$data['IsPublic'] = $isPublic;	
			return $this->insert($data);
		}else{
			$this->update(array('IsPublic'=>$isPublic),array('GroupID = ?'=>$groupID,'MemberID = ?'=>$memberID));
		}
		return true;
	}
	
	/**
	 * 获取群成员数量
	 * @param unknown $groupID
	 */
	public function getMemberCount($groupID)
	{
		$select = $this->select()->from($this->_name,'count(1) as num');
		$select->where('GroupID = ?',$groupID);
		$row = $select->query()->fetch();
		return $row['num'];
	}
	
	/**
	 * 我加入的群组数量
	 */
	public function myGroupCount($memberID){
		$select = $this->select()->from($this->_name,'count(1) as num');
		$select->where('MemberID = ?',$memberID);
		$row = $select->query()->fetch();
		return $row['num'];
	}
	
	/**
	 * 获取群成员
	 * @param string $groupID
	 * @param int $limits
	 */
	public function getGroupMembers($groupID,$limits = 6,$onlyMemberID = false)
	{
		$select = $this->select();
		
		if(strlen($groupID) <=8){
			$groupModel = new Model_IM_Group();
			$groupID = $groupModel->getGroupIDByAID($groupID);
		}
		
		$result = $select->from(array('gm'=>$this->_name),array('MemberID'))->where('gm.GroupID = ?',$groupID)->limit($limits)->query()->fetchAll();
		if(!empty($result) && !$onlyMemberID){
			$memberModel = new DM_Model_Account_Members();
			foreach($result as &$val){
				$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				$val['Avatar'] = $memberModel->getMemberInfoCache($val['MemberID'],'Avatar');
			}
		}
		return $result ? $result : array();
	}
}