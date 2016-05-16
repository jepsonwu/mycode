<?php
/**
 *  好友
 * @author Mark
 *
 */
class Api_FriendController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 搜索--添加好友 
	 */
	public function searchMembersAction()
	{
		try{
			$keyWords = trim($this->_request->getParam('keyWords',''));
			$searchType = trim($this->_request->getParam('searchType','friend'));
			$pagesize = intval($this->_request->getParam('pagesize',20));
			
			if(!in_array($searchType,array('friend','group'))){
				throw new Exception('搜索类型参数错误');
			}
			
			if(empty($keyWords)){
				throw new Exception('关键字不能为空！');
			}
			
			$lastID = intval($this->_request->getParam('lastID','999999999'));
			
			if($searchType == 'friend'){
				$memberModel = new DM_Model_Account_Members();
				if(strlen($keyWords) == 11 && is_numeric($keyWords)){
					$select = $memberModel->select()->from('members',array('MemberID','UserName','Signature','Avatar'));
					$res = $select->where('MemberID < ? ',$lastID)->where('IMUserName != ""')->where("IsMobileSearchable = 1")->where("MobileNumber = ?",$keyWords)->where('MemberID != ?',$this->memberInfo->MemberID)->order('MemberID desc')->limit($pagesize)->query()->fetchAll();
				} else {
					$select = $memberModel->select()->from('members',array('MemberID','UserName','Signature','Avatar'));
					$res = $select->where('MemberID < ? ',$lastID)->where('IMUserName != ""')->where("IsAccountSearchable = 1")->where("UserName = ?",$keyWords)->where('MemberID != ?',$this->memberInfo->MemberID)->order('MemberID desc')->limit($pagesize)->query()->fetchAll();
				}
				
				$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$res));
			}elseif($searchType == 'group'){
				$groupMdoel = new Model_IM_Group();
				$select = $groupMdoel->select();
				$res = $select->from('group',array('AID','GroupID','GroupName','GroupAvatar'))->where('AID < ?',$lastID)
								  ->where('Status = 1')->where('IsPublic = 1')->where('GroupName like ?','%'.$keyWords.'%')->order('AID desc')->limit($pagesize)->query()->fetchAll();
				if(!empty($res)){
					$groupFocusModel = new Model_IM_GroupFocus();
					foreach($res as &$info){
						//$info['GroupAvatar'] = empty($info['GroupAvatar']) ? 'http://img.caizhu.com/FlBzNE_KHmEVzZpdcuyjSi3OfsO-' : $info['GroupAvatar'];
						$info['Focus'] = $groupFocusModel->getFocusInfo($info['GroupID'],null,'FocusID');
					}
				}
				$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$res));
			}
			
		} catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 同意好友
	 */
	public function agreeAddAction()
	{
		$friendModel = new Model_IM_Friend();
		$db = $friendModel->getAdapter();
		$db->beginTransaction();
		try{
			$friendID = intval($this->_request->getParam('friendID',''));
			$memberModel = new DM_Model_Account_Members();
			$mInfo = $memberModel->getById($friendID);
			if(empty($mInfo)){
				throw new Exception('不存在该会员！');
			}
			//添加好友关系
			$friendModel->addFriendsEachOther($friendID, $this->memberInfo->MemberID);
			//更新好友申请的状态
			$applyModel = new Model_FriendApply();
			$re = $applyModel->update(array('Status'=>2,'AgreeTime'=>date('Y-m-d H:i:s')),array('ApplyMemberID = ?'=>$friendID,'AcceptMemberID = ?'=>$this->memberInfo->MemberID));
			if($re === false){
				throw new Exception('失败！');
			}
			//透传消息给申请人
			$ext['CZSubAction'] = "agreeFriend";
			$ext['CZSendFrom'] = 'server';
			$ext['Message'] = $this->memberInfo->UserName.'已同意您的好友申请';
			$ext['MemberID'] = $this->memberInfo->MemberID;
			$easeModel = new Model_IM_Easemob();
			$easeModel->tc_hxSend(array($friendID), 'user','cmd','users',$ext);
			$db->commit();
			$this->returnJson(parent::STATUS_OK,'成功！');
			
		}catch(Exception $e){
			$db->rollBack();
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());	
		}
	}
	
	/**
	 * 删除好友
	 */
	public function deleteAction()
	{
		$friendModel = new Model_IM_Friend();
		$db = $friendModel->getAdapter();
		$db->beginTransaction();
		try{
			$friendID = intval($this->_request->getParam('friendID',''));
			$memberModel = new DM_Model_Account_Members();
			$mInfo = $memberModel->getById($friendID);
			if(empty($mInfo)){
				throw new Exception('不存在该会员！');
			}
			//删除好友关系	
			$friendModel = new Model_IM_Friend();
			$friendModel->deleteFriendsEachOther($friendID, $this->memberInfo->MemberID);
			//删除好友申请记录
			$applyModel = new Model_FriendApply();
			$relationModel = new Model_FriendApplyRelation();
			$applyID = $applyModel->getApplyID($this->memberInfo->MemberID, $friendID,0);
			if($applyID){
				$step2 = $applyModel->delete(array('ApplyID = ?'=>$applyID));
				if(!$step2){
					throw new Exception('删除失败！');
				}
				$step3 = $relationModel->delete(array('ApplyID = ?'=>$applyID));
				if(!$step3){
					throw new Exception('删除失败！');
				}
				//删掉对好友的描述
				$memberNoteModel = new Model_MemberNotes();
				$step4 = $memberNoteModel->update(array('Description'=>''),array('MemberID = ?'=>$this->memberInfo->MemberID,'OtherMemberID=?'=>$friendID));
				if($step4 === false){
					throw new Exception('删除失败！');
				}
				$memberNoteModel = new Model_MemberNotes();
				$step5 = $memberNoteModel->update(array('Description'=>''),array('MemberID = ?'=>$friendID,'OtherMemberID=?'=>$this->memberInfo->MemberID));
				if($step5 === false){
					throw new Exception('删除失败！');
				} 
			}
			$db->commit();
			//透传消息给被解除好友的人
			$ext['CZSubAction'] = "deleteFriend";
			$ext['CZSendFrom'] = 'server';
			$ext['Message'] = $this->memberInfo->UserName.'已把您从好友列表中移除';
			$ext['MemberID'] = $this->memberInfo->MemberID;
			$easeModel = new Model_IM_Easemob();
			$easeModel->tc_hxSend(array($friendID), 'user','cmd','users',$ext);
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$db->rollBack();
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}

	/**
	 * 设置备注
	 */
	public function setNoteAction()
	{
		try{
			$friendID = intval($this->_request->getParam('friendID',''));
			$memberModel = new DM_Model_Account_Members();
			$mInfo = $memberModel->getById($friendID);
			if(empty($mInfo)){
				throw new Exception('不存在该会员！');
			}
			
			$memberID = $this->memberInfo->MemberID;
			$note = trim($this->_request->getParam('note',''));

			if(mb_strlen($note,'utf-8')>10){
				throw new Exception('备注名不能大于10个字符！');
			}
			$description = trim($this->_request->getParam('description',''));
			if(mb_strlen($description,'utf-8')>100){
				throw new Exception('描述不能大于100个字符！');
			}
			$memberNoteModel = new Model_MemberNotes();
			$memberNoteModel->addNoteName($memberID,$friendID, $note,$description);
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 获取通讯录列表中的财猪用户
	 */
	public function getContactListAction()
	{
		try{
			$mobileNumber = trim($this->_request->getParam('mobileNumber',''));
			if(empty($mobileNumber)){
				throw new Exception('通讯录为空！');
			}
			
			$mobileNumberArr = explode(',', $mobileNumber);
			if(!is_array($mobileNumberArr) || count($mobileNumberArr) < 1){
				throw new Exception('通讯录为空！');
			}
			
			$memberModel = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
			$select = $memberModel->select()->from('members',array('MemberID','MobileNumber','UserName','Avatar','IsShowContactList'));
			$result = $select->where('MobileNumber in (?)',$mobileNumberArr)->where('MemberID != ?',$this->memberInfo->MemberID)->where('UserName != ?','')->query()->fetchAll();
			if(!empty($result)){
				$memberFollowModel = new Model_MemberFollow();
				foreach($result as &$val){
					$val['RelationCode'] = $memberFollowModel->getRelation($val['MemberID'], $this->memberInfo->MemberID);
					$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['MemberID']);
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 获取好友列表
	 */
	public function getFriendListAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$friendModel = new Model_IM_Friend();
			$friendArr = $friendModel->getFriendInfo($memberID);
			if(!empty($friendArr)){
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				$helperModel = new Model_MessageHelper();
				foreach($friendArr as &$val){
					$val['Avatar'] = $memberModel->getMemberInfoCache($val['FriendID'],'Avatar');
					$val['UserName'] = $memberModel->getMemberInfoCache($val['FriendID'],'UserName');
					$val['MemberID'] = $val['FriendID'];
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['Description'] = $memberNoteModel->getFriendDescription($memberID,$val['MemberID']);
					$helperInfo = $helperModel->getinfo($memberID, $val['MemberID'], 1);
					$val['Messagehelper'] = empty($helperInfo) ? 0 : 1;
					unset($val['FriendID']);
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$friendArr));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 申请加为好友
	 * @throws Exception
	 */
	public function applyAddFriendAction()
	{
		try{
			$friendID = $this->_getParam('friendID',0);
			$content = trim($this->_getParam('content',''));
			if($friendID<1){
				throw new Exception('参数错误！');
			}
			$memberFollowModel = new Model_MemberFollow();
			$relationCode = $memberFollowModel->getRelation($friendID, $this->memberInfo->MemberID);
			if($relationCode == 3){
				throw new Exception('您们已经是好友了，无需再申请！');
			}
			$applyModel = new Model_FriendApply();
			$applyModel->applyAddFriend($this->memberInfo->MemberID,$friendID,$content);
			$this->returnJson(parent::STATUS_OK,'申请成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取收到的好友申请列表
	 */
	public function applyFriendListAction()
	{
		try{
			$page = intval($this->_getParam('page',1));
			$pagesize = intval($this->_getParam('pagesize',30));
			$applyModel =new Model_FriendApply();
			$select = $applyModel->select();
			$select->from('friend_apply',array('ApplyID','ApplyMemberID','Status'))->where('AcceptMemberID = ?',$this->memberInfo->MemberID)
						->where('Status != ?',0);
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
			//总条数
			$total = $applyModel->getAdapter()->fetchOne($countSql);
			
			$result = $select->order('LastUpdateTime desc')->limitPage($page, $pagesize)->query()->fetchAll();
			if(!empty($result)){
				$relationModel = new Model_FriendApplyRelation();
				$memberModel = new DM_Model_Account_Members();
				foreach($result as &$val){
					$val['Records'] = $relationModel->getNewRecords($val['ApplyID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['ApplyMemberID'],'UserName');
					$val['MobileNumber'] = $memberModel->getMemberInfoCache($val['ApplyMemberID'],'MobileNumber');
					$val['Avatar'] = $memberModel->getMemberAvatar($val['ApplyMemberID']);
				}
			}
			Model_Member::staticData($this->memberInfo->MemberID,'lastApplyFriendTime',time());
			$this->returnJson(parent::STATUS_OK,'',array('TotalNum'=>$total,'Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除好友申请的记录
	 */
	public function deleteApplyRecordAction()
	{
		$applyModel = new Model_FriendApply();
		$db = $applyModel->getAdapter();
		$db->beginTransaction();
		try{
			$applyID = intval($this->_getParam('applyID',0));
			if($applyID<1){
				throw new Exception('参数错误！');
			}
			$relationModel = new Model_FriendApplyRelation();
			$step1 = $applyModel->delete(array('ApplyID = ?'=>$applyID));
			if($step1 === false){
				throw new Exception('删除失败！');
			}
			$step2 = $relationModel->delete(array('ApplyID = ?'=>$applyID));
			if($step2 === false){
				throw new Exception('删除失败！');
			}
			$db->commit();
			$this->returnJson(parent::STATUS_OK,'已删除！');
		}catch(Exception $e){
			$db->rollBack();
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除新朋友消息
	 */
	public function deleteApplyFriendNewsAction()
	{
		Model_Member::staticData($this->memberInfo->MemberID,'lastApplyFriendTime',time());
		$this->returnJson(parent::STATUS_OK,'');
	}
}
