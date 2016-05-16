<?php
/**
 *  好友申请相关
 * @author Jeff 2016-01-25
 *
 */
class Model_FriendApply extends Zend_Db_Table
{
	protected $_name = 'friend_apply';
	protected $_primary = 'ApplyID';
	
	/**
	 * 好友申请状态
	 * @param unknown $memberID
	 * @return string
	 */
	private function getFriendApplyKey($memberID)
	{
		return 'Friend:Apply:Status'.$memberID;
	}
	
	/**
	 * 申请加为好友
	 * @param int $memberID
	 * @param int $friendID
	 * @throws Exception
	 */
	public function applyAddFriend($memberID,$friendID,$content)
	{
		$db = $this->getAdapter();
		$db->beginTransaction();
		try{
			//先判断有没有因操作过于频繁冻结5分钟
			$redisObj = DM_Module_Redis::getInstance();
			$info = $redisObj->GET($this->getFriendApplyKey($memberID));
			if(!empty($info)){
				throw new Exception('操作过于频繁，请稍后再试!');
			}
			//获取一分钟只内申请的次数
			$relationModel = new Model_FriendApplyRelation();
			$applyNum = $relationModel->getApplyNum($memberID);
			if($applyNum>=3){
				//冻结5分钟
				$redisObj->SET($this->getFriendApplyKey($memberID),time());
				$redisObj->EXPIRE($this->getFriendApplyKey($memberID),300);
				throw new Exception('操作过于频繁，请稍后再试!');
			}
			//获取今天已经向多少人发送了申请
			$applyMemberNum = $this->getApplyMemberNum($memberID);
			if($applyMemberNum>=300){
				throw new Exception('每天最多可以向300人发送好友申请！');
			} 
			
			$this->addApply($memberID,$friendID);
			$applyID = $this->getApplyID($memberID, $friendID);
			if(!$applyID){
				throw new Exception('申请发送失败！');
			}
			$insertID = $relationModel->insert(array('ApplyID'=>$applyID,'ApplyMemberID'=>$memberID,'Content'=>$content));
			if($insertID>0){
				$db->commit();
				//透传消息给申请人
				$ext['CZSubAction'] = "addFriend";
				$ext['CZSendFrom'] = 'server';
				$ext['Message'] = '您有一个好友申请';
				$ext['ApplyMemberID'] = $memberID;
				$easeModel = new Model_IM_Easemob();
				$easeModel->tc_hxSend(array($friendID), 'user','cmd','users',$ext);
			}else{
				throw new Exception('申请发送失败！');
			}
		}catch(Exception $e){
			$db->rollBack();
			throw new Exception($e->getMessage(), -1);
		}
	}
	
	private function addApply($memberID,$friendID){
		$db = $this->getAdapter();
		$sql = "insert into friend_apply(ApplyMemberID,AcceptMemberID) values(:ApplyMemberID,:AcceptMemberID) on duplicate key update LastUpdateTime = '".date('Y-m-d H:i:s')."'";
		$db->query($sql,array('ApplyMemberID'=>$memberID,'AcceptMemberID'=>$friendID));
		return true;
	}
	
	/**
	 * 获取申请记录ID
	 * @param unknown $memberID
	 * @param unknown $friendID
	 */
	public function getApplyID($memberID,$friendID,$isKnowApplyMember=1){
		if($isKnowApplyMember){
			$info = $this->select()->from($this->_name,'ApplyID')->where('ApplyMemberID = ?',$memberID)->where('AcceptMemberID = ?',$friendID)->query()->fetch();
		}else{
			$db = $this->getAdapter();
			$sql = "SELECT ApplyID FROM `friend_apply` WHERE (ApplyMemberID=$friendID AND AcceptMemberID = $memberID) OR (ApplyMemberID=$memberID AND AcceptMemberID = $friendID)";
			$info = $db->query($sql)->fetch();
		}
		return empty($info)?0:$info['ApplyID'];
	}
	
	/**
	 * 查询今天申请的好友人数
	 * @param unknown $memberID
	 */
	private function getApplyMemberNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(1) as totalNum')->where('ApplyMemberID = ?',$memberID)->where("DATE_FORMAT(LastUpdateTime,'%Y-%m-%d') = ?",date('Y-m-d'))
		->query()->fetch();
		return $info['totalNum'];
	}
	
	/**
	 * 获取某人是否向我发出申请且我未同意
	 * @param unknown $memberID
	 * @param unknown $currentMemberID
	 */
	public function isSendApply($memberID, $currentMemberID)
	{
		$info = $this->select()->from($this->_name,array('ApplyID'))->where('ApplyMemberID = ?',$memberID)->where('AcceptMemberID = ?',$currentMemberID)
		->where('Status = ?',1)->query()->fetch();
		return empty($info)?0:1;
	}
	
}