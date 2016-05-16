<?php
/**
 *  群组
 * @author Mark
 *
 */
class Model_IM_Group extends Zend_Db_Table
{
	protected $_name = 'group';
	protected $_primary = 'AID';
	
	/**
	 *  创建群
	 * @param string $groupName
	 * @param int $memberID
	 */
	public function createGroup($groupID,$groupName,$memberID,$description = '',$nowUserCount = 1)
	{
		$tmpInfo = $this->getInfo($groupID,false);
		if(empty($tmpInfo)){
			$gData['GroupID'] = $groupID;
			$gData['GroupName'] = $groupName;
			$gData['OwnerID'] = $memberID;
			$gData['Description'] = $description;
			$gData['NowUserCount'] = $nowUserCount;
			$this->insert($gData);
			$groupMemberModel = new Model_IM_GroupMember();
			$groupMemberModel->addMember($memberID, $groupID);
			return $groupID;
		}else{
			$gData['NowUserCount'] = $nowUserCount;
			$gData['SyncTime'] = date('Y-m-d H:i:s',time());
			$gData['Status'] = 1;
			$this->update($gData, array('GroupID = ?'=>$groupID));
		}
		return $tmpInfo['GroupID'];
	}
	
	/**
	 *  获取群信息
	 * @param int $gruopID
	 */
	public function getInfo($groupID,$memberID=0,$isNeedExtra = true)
	{
		$select = $this->select();
		$select = $select->from($this->_name,array('AID','GroupID','GroupName','OwnerID','Description','NowUserCount','Province','City','Status','GroupAvatar','GroupCover','IsPublic as OwnerIsPublic'));
		
		if(strlen($groupID) <=8){
			$groupID = $this->getGroupIDByAID($groupID);
		}

		$select->where('GroupID = ?',$groupID);
		
		$info = $select->query()->fetch();
		if(!empty($info) && $isNeedExtra){
			$groupTopicModel = new Model_IM_GroupTopic();
			if($memberID){
				$groupMemberModel = new Model_IM_GroupMember();
				$helperModel = new Model_MessageHelper();
				$memberInfo = $groupMemberModel->getInfo($memberID,$groupID);
				$info['NickName'] = empty($memberInfo)?'':$memberInfo['NickName'];
				$info['ShowNickName'] = empty($memberInfo)?1:$memberInfo['ShowNickName'];
				$info['IsPublic'] = empty($memberInfo)?1:$memberInfo['IsPublic'];
				$helperInfo = $helperModel->getinfo($memberID,$groupID,2);
				$info['Messagehelper'] = empty($helperInfo)?0:1;
			}
			$Amodel = new Model_IM_GroupAnnouncement();
			$info['AnnouncementNum'] = $Amodel->getcount($groupID);
			$groupFocusModel = new Model_IM_GroupFocus();
			//$info['GroupAvatar'] = empty($info['GroupAvatar']) ? 'http://img.caizhu.com/FlBzNE_KHmEVzZpdcuyjSi3OfsO-' : $info['GroupAvatar'];
			$info['Focus'] = $groupFocusModel->getFocusInfo($groupID,null,'FocusID');
			$info['TopicInfo'] = $groupTopicModel->getBindInfo($info['AID']);
		}
		return $info ? $info : array();
	}
	
	/**
	 * 根据AID获取群信息
	 * @param int $AID
	 */
	public function getGroupIDByAID($AID)
	{
		$select = $this->select();
		$groupID = $select->from($this->_name,array('GroupID'))->where('AID = ?',$AID)->query()->fetchColumn();
		return $groupID ? $groupID : 0;
	}
	
	/**
	 * 批量获取群组信息
	 * @param array $groupIDArr
	 */
	public function getBatchInfo($groupIDArr,$memberID=0)
	{
		$select = $this->select();
		$select = $select->from($this->_name,array('AID','GroupID','GroupName','OwnerID','Description','NowUserCount','Province','City','GroupAvatar','GroupCover','Status'));
		$result = $select->where('GroupID in (?)',$groupIDArr)->query()->fetchAll();
		if(!empty($result)){
			$groupFocusModel = new Model_IM_GroupFocus();
			$helperModel = new Model_MessageHelper();
			foreach($result as &$item){
				//$item['GroupAvatar'] = empty($item['GroupAvatar']) ? 'http://img.caizhu.com/FlBzNE_KHmEVzZpdcuyjSi3OfsO-' : $item['GroupAvatar'];
				$helperInfo = $helperModel->getinfo($memberID,$item['GroupID'],2);
				$item['Messagehelper'] = empty($helperInfo)?0:1;
				$item['Focus'] = $groupFocusModel->getFocusInfo($item['GroupID'],null,'FocusID');
			}
		}
		return $result ? $result : array();
	}
	
	
	public function syncGroup()
	{
		$easeModel = new Model_IM_Easemob();
		$res = $easeModel->chatGroups();
		$resArr = json_decode($res,true);
		$startTime = date('Y-m-d H:i:s',time());
		if(!empty($resArr['data'])){
			foreach($resArr['data'] as $tmpGroup){
				$groupID = $tmpGroup['groupid'];
				$groupName = $tmpGroup['groupname'];
				$ownerTmp = explode('#',$tmpGroup['owner']);
				$owner = $ownerTmp[1];
				$nowUserCount = $tmpGroup['affiliations'];
				$this->createGroup($groupID, $groupName, $owner, '',$nowUserCount);				
			}
			$this->update(array('Status'=>0),array('SyncTime < ?'=>$startTime));
		}
	}
	
	/**
	 * 同步群会员
	 */
	public function syncGroupMembers()
	{
		$groupIDs = $select = $this->select()->from($this->_name,'GroupID')->where('Status = 1')->query()->fetchAll();
		$easeModel = new Model_IM_Easemob();
		$groupMemberModel = new Model_IM_GroupMember();
		if(!empty($groupIDs)){
			foreach($groupIDs as $item){
				$res = $easeModel->groupsUser($item['GroupID']);
				$resArr = json_decode($res,true);
				if(!empty($resArr['data'])){
					foreach($resArr['data'] as $member){
						$itemMember = isset($member['member']) ? $member['member'] : (isset($member['owner']) ? $member['owner'] : '');
						if(empty($itemMember)){
							continue;
						}
						$groupMemberModel->addMember($itemMember, $item['GroupID'],date('Y-m-d H:i:s'));
					}
				}
				$groupMemberModel->delete(array('GroupID = ?'=>$item['GroupID'],'SyncTime < ?'=>date('Y-m-d H:i:s',time() - 3700)));
			}
		}
	}
	
	/**
	 *  群组增加成员
	 * @param int $memberID
	 * @param string $groupID
	 */
	public function addGroupMember($memberID,$groupID)
	{
		$groupMemberModel = new Model_IM_GroupMember();
		$ret = $groupMemberModel->addMember($memberID, $groupID);
		if($ret > 1){
			$this->update(array('NowUserCount'=>new Zend_Db_Expr("NowUserCount + 1")),array('GroupID = ?'=>$groupID));
		}
		return $ret;
	}
	
// 	/**
// 	 *  退群
// 	 * @param int    $memberID
// 	 * @param string $groupID
// 	 */
	public function quitGroup($memberID,$groupID)
	{
		$groupMemberModel = new Model_IM_GroupMember();
		$affectRows = $groupMemberModel->quit($memberID, $groupID);
		if($affectRows == 1){
			$retAffects = $this->update(array('NowUserCount'=>new Zend_Db_Expr("NowUserCount - 1")),array('GroupID = ?'=>$groupID,'NowUserCount > ? '=>0));
			$groupApplyModel = new Model_IM_GroupApply();
			$groupApplyModel->update(array('ApplyStatus'=>4,'ProcessTime'=>date('Y-m-d H:i:s')),array('GroupID = ?'=>$groupID,'ApplyMemberID = ?'=>$memberID));
		}
		return true;
	}

	public function getGroups($where = null, $orderBy = null, $limit = null, $offset = null, $onlyCount = false) {
		if(is_numeric($where)) {
			$where = $this->_primary . '=' . $where;
		}
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name);
		$this->_where($select, $where);
		$select->joinLeft('group_focus', 'group_focus.GroupID=group.GroupID', array());
        $select->joinLeft('focus', 'group_focus.FocusID=focus.FocusID', array('GroupTags'=>'group_concat(DISTINCT focus.FocusName order by focus.FocusID DESC)'));
        $select->joinLeft('group_announcement', 'group_announcement.GroupID=group.GroupID', array('AnnouncementNum'=>'group_concat(DISTINCT group_announcement.AnnouncementID)'));
        if( $onlyCount ) {
        	$select->group('group.AID');
        	$sql = $select->__toString();
        	// die($sql);
        	$countSql = preg_replace('/select(.*?)from/i', 'SELECT `group`.AID FROM', $sql);
        	$countSql = 'SELECT COUNT(*) AS TOTAL FROM ('.$countSql.') AS TEMP';
        	$total = $this->_db->query($countSql)->fetch();
        	return isset($total['TOTAL']) ? intval($total['TOTAL']) : 0;
        } else {
        	$select->limit($limit, $offset);
        	$select->group('group.AID');
        	$sql = $select->__toString();
        	// die($sql);
	        if( $data = $select->query()->fetchAll() ) {
	        	$modelAccount = new DM_Model_Account_Members();
	        	foreach ($data as $k => $v) {
	        		$data[$k]['OwnerName'] = $modelAccount->getUserName($v['OwnerID']);
	        	}
	        }
	        return $data ? $data : array();
        }
	}
	
	/**
	 * 获取我创建的群数量（群成员少于10的不算） 最热达人算法用到
	 */
	public function getCreateGroupNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(1) as num')->where('OwnerID = ?',$memberID)->where('NowUserCount > ?',10)->where('Status = ?',1)->query()->fetch();
		return $info['num'];
	}
}