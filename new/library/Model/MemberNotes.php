<?php
/**
 *  会员备注
 * @author Mark
 *
 */
class Model_MemberNotes extends Zend_Db_Table
{
	protected $_name = 'member_notes';
	protected $_primary = 'MemberNoteID';
	
	/**
	 *  好友备注
	 * @param int $memberID
	 * @return string
	 */
	private function getFriendNoteNameKey($memberID)
	{
		return 'Friend:NoteName:'.$memberID;
	}
	
	/**
	 *  添加备注
	 * @param int $otherMemberID
	 * @param string $noteName
	 */
	public function addNoteName($memberID,$otherMemberID,$noteName,$description)
	{
		$dateTime = date('Y-m-d H:i:s',time());
		$db = $this->getAdapter();
		$sql = "insert into member_notes(MemberID,OtherMemberID,NoteName,Description,CreateTime,UpdateTime) values(:MemberID,:OtherMemberID,:NoteName,:Description,:CreateTime,:UpdateTime) 
				  on duplicate key update NoteName='".$noteName."',Description = '".$description."' , UpdateTime = '".$dateTime."'";
		$db->query($sql,array('MemberID'=>$memberID,'OtherMemberID'=>$otherMemberID,'NoteName'=>$noteName,'Description'=>$description,'CreateTime'=>$dateTime,'UpdateTime'=>$dateTime));
		
		$redisObj = DM_Module_Redis::getInstance();
		$redisObj->hset($this->getFriendNoteNameKey($memberID),$otherMemberID,$noteName);
		
		return true;
	}
	
	
	/**
	 * 获取好友备注
	 * @param int $memberID
	 * @param int $otherMemberID
	 */
	public function getNoteName($memberID,$otherMemberID)
	{
		if($memberID == $otherMemberID || empty($otherMemberID) || empty($memberID)){
			$noteName = '';
		}else{
			$redisObj = DM_Module_Redis::getInstance();
			$noteName = $redisObj->hget($this->getFriendNoteNameKey($memberID),$otherMemberID);
			$memberFollowModel = new Model_MemberFollow();
			$relationCode = $memberFollowModel->getRelation($otherMemberID, $memberID);
			if(false === $noteName){
				if($relationCode == 1 || $relationCode == 3){
					$noteName = $this->getNoteNameByOtherID($memberID, $otherMemberID);
					$redisObj->hset($this->getFriendNoteNameKey($memberID),$otherMemberID,$noteName);
				}else{
					$noteName = '';
				}
			}else{
				if(!($relationCode == 1 || $relationCode == 3)){
					$noteName = '';
				}
			}
		}
		return $noteName;
	}
	
	/**
	 *  获取指定好友的备注名
	 * @param int $memberID
	 * @param int $friendID
	 * @return Ambigous <string, mixed>
	 */
	private function getNoteNameByOtherID($memberID,$otherMemberID)
	{
		$info = $this->select()->from($this->_name,array('NoteName'))->where('MemberID = ?',$memberID)->where('OtherMemberID = ?',$otherMemberID)->query()->fetch();
		return !empty($info) ? $info['NoteName'] : '';
	}

	/**
	 *  获取指定好友的描述
	 * @param int $memberID
	 * @param int $friendID
	 * @return Ambigous <string, mixed>
	 */
// 	private function getDescriptionByOtherID($memberID,$otherMemberID)
// 	{
// 		$info = $this->select()->from($this->_name,array('Description'))->where('MemberID = ?',$memberID)->where('OtherMemberID = ?',$otherMemberID)->query()->fetch();
// 		return !empty($info) ? $info['Description'] : '';
// 	}
	
	/**
	 * 获取好友的描述
	 * @param unknown $memberID
	 * @param unknown $otherMemberID
	 * @return Ambigous <string, mixed>
	 */
	public function getFriendDescription($memberID,$otherMemberID)
	{
		$info = $this->select()->from($this->_name,array('Description'))->where('MemberID = ?',$memberID)->where('OtherMemberID = ?',$otherMemberID)->query()->fetch();
		return !empty($info) ? $info['Description'] : '';
	}
	
}