<?php
/**
 *  群组
 * @author Mark
 *
 */
class Api_GroupController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();	
	}
		
	/**
	 * 获取群信息
	 */
	public function getGroupDetailAction()
	{
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			$groupInfo = $groupModel->getInfo($groupID,$this->memberInfo->MemberID);
			
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['Status'] == 0){
				$this->returnJson(parent::STATUS_GROUP_DISSOLVE,'群组已解散');
			}
			
			$this->returnJson(parent::STATUS_OK,'',$groupInfo);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 批量获取群信息
	 */
	public function getBatchGroupInfoAction()
	{
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');	
			}
			$groupIDArr = explode(',',$groupID);
			if(empty($groupIDArr)){
				throw new Exception('群组ID不能为空！');
			}
			
			$groupInfo = $groupModel->getBatchInfo($groupIDArr,$this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$groupInfo));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/**
	 * 编辑群信息
	 */
	public function editGroupInfoAction()
	{
		$groupModel = new Model_IM_Group();
		try{
			
				$groupID = trim($this->_request->getParam('groupID',''));
				$groupInfoTmp = $groupModel->getInfo($groupID);
				if(empty($groupInfoTmp)){
					//throw new Exception('不存在该群组！');
				}
				
				if(!empty($groupInfoTmp) && $groupInfoTmp['OwnerID'] != $this->memberInfo->MemberID){
					throw new Exception('非群主不能编辑群资料');
				}
				
				$groupName = trim($this->_request->getParam('groupName',''));
				$description = trim($this->_request->getParam('description',null));
				$province = trim($this->_request->getParam('province',''));
				$city = trim($this->_request->getParam('city',''));
				$groupAvatar = trim($this->_request->getParam('groupAvatar',''));
				$groupCover = trim($this->_request->getParam('groupCover',''));
				$focusID = trim($this->_request->getParam('focusID',''));
	
				if(empty($groupName) && is_null($description) && empty($province) && empty($city) && empty($groupAvatar) && empty($groupCover) && empty($focusID)){
					throw new Exception('参数不能为空！');
				}
				$data = array();
				if(!empty($groupName)){
					$data['GroupName'] = $groupName;
				}
				if(!is_null($description)){
					$data['Description'] = $description;
				}
				if(!empty($province)){
					$data['Province'] = $province;
				}
				
				if(!empty($city)){
					$data['City'] = $city;
				}

				if(!empty($groupAvatar)){
					$data['GroupAvatar'] = $groupAvatar;
				}
				
				if(!empty($groupCover)){
					$data['GroupCover'] = $groupCover;
				}

				$focusIDArr = array_filter(explode(',',$focusID));			
	            if(!empty($focusIDArr) && count($focusIDArr) >= 1){
	                $groupFocusModel = new Model_IM_GroupFocus();
	                $focusInfo = $groupFocusModel->getFocusInfo($groupID,null,'FocusID');
	                foreach ($focusInfo as $item) {
	                    if(!in_array($item['FocusID'], $focusIDArr)){
	                        $groupFocusModel->delete(array('FocusID = ?' => $item['FocusID'],'GroupID = ? '=>$groupID));
	                    }
	                }
	                foreach($focusIDArr as $fID){
	                    $groupFocusModel->addFocus($groupID,$fID);
	                }
	            }
				
				if(!empty($groupInfoTmp)){
					!empty($data) && $groupModel->update($data,array('GroupID = ?'=>$groupID));
				}else{
					$data['GroupID'] = $groupID;
					$groupModel->insert($data);
				}
				$groupInfo = $groupModel->getInfo($groupID);
				
				//透传消息给群组
				if($groupInfo['GroupName'] != $groupInfoTmp['GroupName']){
					$ext['CZSubAction'] = "updateGroupName";
					$ext['CZSendFrom'] = 'server';
					$ext['GroupID'] = $groupID;
					$ext['GroupName'] = $groupInfo['GroupName'];
					$easeModel = new Model_IM_Easemob();
					$easeModel->tc_hxSend(array($groupInfo['GroupID']), 'group','cmd','chatgroups',$ext);
				}
				
				$this->returnJson(parent::STATUS_OK,'编辑成功！',$groupInfo);		
			}catch(Exception $e){
				$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 解散群回调 2.2版将废弃
	 */
	public function deleteCallAction()
	{
		$groupModel = new Model_IM_Group();
		try{
				
			$groupID = trim($this->_request->getParam('groupID',''));
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
		
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能解散群');
			}
			
			$groupModel->update(array('Status'=>0),array('GroupID = ?'=>$groupID));
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 解散群
	 */
	public function dissolveGroupAction()
	{
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能解散群');
			}

			//环信端删除群
			$easeModel = new Model_IM_Easemob();
			$res = $easeModel->deleteGroups($groupID);
			$resArr = json_decode($res,true);
			if(!isset($resArr['data']['success']) || $resArr['data']['success'] != true){
				throw new Exception('解散群失败');
			}
			$groupModel->update(array('Status'=>0),array('GroupID = ?'=>$groupID));
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 创建群
	 */
	public function createGroupAction()
	{
		try{
			$groupName = trim($this->_request->getParam('groupName',''));
			if(empty($groupName)){
				throw new Exception('群名称不能为空!');
			}
			
			$focusID = trim($this->_request->getParam('focusID',''));
			$focusIDArr = explode(',', $focusID);
			if(empty($focusIDArr) || count($focusIDArr) < 1){
				throw new Exception('群标签不能为空!');
			}
			
			$description = trim($this->_request->getParam('description',''));
			$memberID = $this->memberInfo->MemberID;
			
			$groupModel = new Model_IM_Group();
			
			//在环信创建群组
			$easeModel = new Model_IM_Easemob();
			$option = array('groupname'=>$groupName,
								 'desc'=>$description ? $description : 'empty',
					          'public'=>true,
								 'maxusers'=>2000,
								 'approval'=>true,
					          'owner'=>$memberID
			);
			$resRet = $easeModel->createGroups($option);
			$resArr = json_decode($resRet,true);
			if(is_array($resArr) && !empty($resArr['data']['groupid'])){
				$groupID = $resArr['data']['groupid'];
			}else{
				throw new Exception('创建群组失败！');
			}
			
			$groupID = $groupModel->createGroup($groupID,$groupName, $memberID,$description);
			if($groupID){
				$groupFocusModel = new Model_IM_GroupFocus();
				foreach($focusIDArr as $focusID){
					$groupFocusModel->addFocus($groupID, $focusID);
				}
			}else{
				throw new Exception('创建群失败！');
			}
			$groupInfo = $groupModel->getInfo($groupID);
			$this->returnJson(parent::STATUS_OK,'',$groupInfo);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());			
		}
	}
		
	/**
	 * 申请加群
	 */
	public function applyJoinAction()
	{
		$easeModel = new Model_IM_Easemob();
		$groupModel = new Model_IM_Group();
		$memberModel = new DM_Model_Account_Members();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$applyContent = trim($this->_request->getParam('applyContent','申请加群'));
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			//保存加群申请
			$groupApplyModel = new Model_IM_GroupApply();
			$applyID = $groupApplyModel->addApply($this->memberInfo->MemberID, $groupID,$applyContent);
			$applyInfo = $groupApplyModel->getApplyInfo($applyID);
						
			if(!empty($applyInfo) && $applyInfo['ApplyStatus'] != 1){
					if($applyInfo['ApplyStatus'] != 0){
						$groupApplyModel->update(array('ApplyStatus'=>0),array('ApplyID = ?'=>$applyID));	
					}
					
					//透传自定义消息给群组
					$ext['GroupID'] = $groupID;
					$ext['GroupName'] = $groupInfo['GroupName'];
					$ext['CZSubAction'] = "applyJoinGroup";
					$ext['ApplyID'] = $applyID;
					$ext['ApplyMemberID'] = $this->memberInfo->MemberID;
					$ext['ApplyUserName'] = $this->memberInfo->UserName;
					$ext['ApplyAvatar'] = $memberModel->getMemberAvatar($this->memberInfo->MemberID);
					$ext['OwnerID'] = $groupInfo['OwnerID'];
					$ext['ApplyContent'] = $applyContent;
					//$easeModel->tc_hxSend(array($groupID), 'group','cmd','chatgroups',$ext);
					$easeModel->tc_hxSend(array($groupInfo['OwnerID']), 'group','cmd','users',$ext);
			}
			
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());			
		}
	}
	
	/**
	 * 拒绝加群
	 */
	public function refuseJoinAction()
	{
		$easeModel = new Model_IM_Easemob();
		$groupModel = new Model_IM_Group();
		try{
			$applyID = trim($this->_request->getParam('applyID',0));
			if(empty($applyID)){
				throw new Exception('参数错误！');
			}
			
			$groupApplyModel = new Model_IM_GroupApply();
			$applyInfo = $groupApplyModel->getApplyInfo($applyID);
			if(empty($applyInfo) || $applyInfo['ApplyStatus'] != 0){
				throw new Exception('该申请已过期');
			}
			
			$groupID = $applyInfo['GroupID'];
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
						
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能进行该操作！');
			}
			
			//更新申请列表
			$groupApplyModel->update(array('ApplyStatus'=>2,'ProcessTime'=>date('Y-m-d H:i:s')),array('ApplyID = ?'=>$applyID));
			
			$this->returnJson(parent::STATUS_OK,'');			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 同意加群
	 */
	public function agreeJoinAction()
	{
		$easeModel = new Model_IM_Easemob();
		$groupModel = new Model_IM_Group();
		try{
			
			$applyID = trim($this->_request->getParam('applyID',0));
			if(empty($applyID)){
				throw new Exception('参数错误！');
			}
				
			$groupApplyModel = new Model_IM_GroupApply();
			$applyInfo = $groupApplyModel->getApplyInfo($applyID);
			if(empty($applyInfo) || $applyInfo['ApplyStatus'] != 0){
				throw new Exception('该申请已过期');
			}
			
			$groupID = $applyInfo['GroupID'];
			$applyMemberID = $applyInfo['ApplyMemberID'];
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
								
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能进行该操作！');
			}
			//环信加群
			$res = $easeModel->addGroupsUser($groupID, $applyMemberID);
			$resArr = json_decode($res,true);
			if(!(isset($resArr['data']['result']) && $resArr['data']['result'] == true)){
				throw new Exception('加群操作失败！');
			}
			//应用服务加群
			$groupModel->addGroupMember($applyMemberID, $groupID);
			
			//TODO 发送消息给群内成员
			$memberModel = new DM_Model_Account_Members();
			$ext['Action'] = 'group';
			$ext['CZSubAction'] = 'agreeJoinApply';
			$ext['ApplyMemberID'] = $applyMemberID;
			$ext['GroupID'] = $groupID;
			
			$easeModel->yy_hxSend(array($groupInfo['GroupID']),'有新成员加入群组','txt','chatgroups',$ext);
			
			//更新申请列表
			$groupApplyModel->update(array('ApplyStatus'=>1,'ProcessTime'=>date('Y-m-d H:i:s')),array('ApplyID = ?'=>$applyID));
			
			$this->returnJson(parent::STATUS_OK,'');			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 获取加群申请
	 */
	public function getGroupAppliesAction()
	{
		$groupApplyModel = new Model_IM_GroupApply();
		$groupModel = new Model_IM_Group();
		try{
			$groupID = $this->_request->getParam('groupID','');
			if(empty($groupID)){
				throw new Exception('参数错误');
			}
			
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				//throw new Exception('非群主不能进行该操作！');
			}
			
			$select = $groupApplyModel->select()->from('group_applies',array('ApplyID','ApplyMemberID','ApplyTime','ApplyContent'))->where('GroupID = ?',$groupInfo['GroupID'])->where('ApplyStatus = ?',0);
			$applies = $select->order("ApplyTime desc")->query()->fetchAll();
			if(!empty($applies)){
				$memberModel = new DM_Model_Account_Members();
				foreach($applies as &$item){
					$item['ApplyMemberAvatar'] = $memberModel->getMemberAvatar($item['ApplyMemberID']);
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$applies));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 退出群  2.2版本以后将废弃
	 */
	public function quitCallAction()
	{
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['OwnerID'] == $this->memberInfo->MemberID){
				throw new Exception('群主不能退出！');
			}
			
			//应用服务退群
			$groupModel->quitGroup($this->memberInfo->MemberID, $groupID);
			$this->returnJson(parent::STATUS_OK,'');			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	
	/**
	 * 退群
	 */
	public function quitGroupAction()
	{
		$groupModel = new Model_IM_Group();
		$easeModel = new Model_IM_Easemob();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
				
			if($groupInfo['OwnerID'] == $this->memberInfo->MemberID){
				throw new Exception('群主不能退出！');
			}
			
			//环信退群
			$res = $easeModel->delGroupsUser($groupID,$this->memberInfo->MemberID);
			
			$resArr = json_decode($res,true);
			if(!(isset($resArr['data']['result']) && $resArr['data']['result'] == true)){
				throw new Exception('退群操作失败！');
			}	
			//应用服务退群
			$groupModel->quitGroup($this->memberInfo->MemberID, $groupID);
			
			//更新申请列表
			$groupApplyModel = new Model_IM_GroupApply();
			$groupApplyModel->update(array('ApplyStatus'=>4,'ProcessTime'=>date('Y-m-d H:i:s')),array('ApplyMemberID = ?'=>$this->memberInfo->MemberID,'GroupID = ?'=>$groupID));
			

			//透传给群主
			$ext['CZSubAction'] = "quitGroup";
			$ext['CZSendFrom'] = 'server';
			$ext['GroupName'] = $groupInfo['GroupName'];
			$ext['GroupID'] = $groupID;
			$ext['QuitMemberID'] = $this->memberInfo->MemberID;
			$ext['QuitMemberUserName'] = $this->memberInfo->UserName;
			$ext['Message'] = $this->memberInfo->UserName.' 已退出群'.$groupInfo['GroupName'];
			$easeModel->tc_hxSend(array($groupInfo['OwnerID']), 'group','cmd','users',$ext);
						
			
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取群成员列表
	 */
	public function getGroupMembersAction()
	{
		$groupModel = new Model_IM_Group();
		$groupMemberModel = new Model_IM_GroupMember();
		
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$limit = intval($this->_request->getParam('limit',20));
			$groupInfo = $groupModel->getInfo($groupID,0,false);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			$members = $groupMemberModel->getGroupMembers($groupID,$limit,true);
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$members));			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 获取群公告
	 */
	public function announcementListAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$groupID = trim($this->_getParam('groupID',''));
			$lastID = intval($this->_getParam('lastID',0));
			$pageIndex= $this->_getParam('page', 1);
			$pageSize = $this->_getParam('pagesize', 10);
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			$groupMemberModel = new Model_IM_GroupMember();
			//查询当前用户是否是群成员
			$groupMemberInfo = $groupMemberModel->getInfo($memberID,$groupID);
// 			if(empty($groupMemberInfo)){
// 				throw new Exception('您还不是该群组成员，无权查看群公告！');
// 			}
			$fields = array('AnnouncementID','GroupID','Content','CreateTime','IsTop');
			$AModel = new Model_IM_GroupAnnouncement();
			$select = $AModel->select()->from('group_announcement',$fields)->where('GroupID = ?',$groupID)->where('Status = ?',1);
			if(!$lastID){
				$lastID = $AModel->getLastID($groupID,$memberID);
			}
			if($lastID>0){
				$select = $select->where('AnnouncementID > ?',$lastID);
			}
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
			//总条数
			$total = $AModel->getAdapter()->fetchOne($countSql);
			$version = $this->_request->getParam('currentVersion','1.0.0');
			if(version_compare($version, '2.3.2') >= 0){
				$results = $select->order('IsTop desc')->order('AnnouncementID desc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
			}else{
				$results = $select->order('AnnouncementID desc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
			}
			if(!empty($results)){
				foreach ($results as &$val){
					if(strtotime($val['CreateTime'])>strtotime(date('Y-m-d'))){
						$val['CreateTime'] = date('H:i',strtotime($val['CreateTime']));
					}else{
						$val['CreateTime'] = date('Y-m-d',strtotime($val['CreateTime']));
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'LastID'=>$lastID,'Rows'=>$results));
		}catch (Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 发布群公告
	 */
	public function publishAnnouncementAction()
	{
		$this->checkPostMethod();
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$content = DM_Module_XssFilter::filter(trim($this->_getParam('content','')));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			if(empty($content)){
				throw new Exception('群组公告不能为空！');
			}
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能发布公告');
			}
			$param = array(
					'GroupID'=>$groupID,
					'Content'=>$content,
					'CreateTime'=>date('Y-m-d H:i:s')
			);
			$AModel = new Model_IM_GroupAnnouncement();
			$insertID = $AModel->insert($param);
			if($insertID>0){
				$param['AnnouncementID'] = $insertID;
				$param['CreateTime'] = date('H:i');
				$this->returnJson(parent::STATUS_OK,'发布成功！',$param);
			}else{
				$this->returnJson(parent::STATUS_FAILURE,'发布失败！');
			}
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除群公告
	 */
	public function deleteAnnouncementAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$announcementID = intval($this->_getParam('announcementID',0));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			if(empty($announcementID)){
				throw new Exception('公告ID不能为空！');
			}
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能删除群公告');
			}
			$AModel = new Model_IM_GroupAnnouncement();
			$AModel->update(array('Status'=>0),array('AnnouncementID = ?'=>$announcementID));
			$this->returnJson(parent::STATUS_OK,'删除成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 修改我在某群的昵称
	 */
	public function editNicknameAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$memberID = $this->memberInfo->MemberID;
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			$nickName = trim($this->_getParam('nickName',''));
// 			if(empty($nickName)){
// 				throw new Exception('昵称不能为空！');
// 			}

			if($nickName !== null){
				$groupMemberModel = new Model_IM_GroupMember();
				//查询当前用户是否是群成员
				$groupMemberInfo = $groupMemberModel->getInfo($memberID,$groupID);
				if(empty($groupMemberInfo)){
					$data = array();
					$data['GroupID'] = $groupID;
					$data['MemberID'] = $memberID;
					$data['NickName'] = $nickName;
					$data['AddTime'] = date('Y-m-d H:i:s');
					$groupMemberModel->insert($data);
				}
				$groupMemberModel->update(array('NickName'=>$nickName),array('GroupID = ?'=>$groupID,'MemberID = ?'=>$memberID));
			}
			$this->returnJson(parent::STATUS_OK,'修改成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取所有设置过群昵称的群成员及对应昵称
	 */
	public function getGroupNicknameAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$isShow = 1;
			
			$groupID = trim($this->_getParam('groupID',''));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			
			$groupMemberModel = new Model_IM_GroupMember();
			$isShowInfo = $groupMemberModel->getInfo($memberID, $groupID);

			if(!empty($isShowInfo)){
				$isShow = $isShowInfo['ShowNickName'];
			}
			
			$results = array();
			if($isShow){
				$select = $groupMemberModel->select()->from('group_member',array('MemberID','NickName'))->where('GroupID = ?',$groupID)->where('NickName !=""');	
				$results = $select->query()->fetchAll();
			}
			$this->returnJson(parent::STATUS_OK,'',array('Row'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 设置群是否为公开群
	 */
	public function setGroupPublicAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$isPublic = intval($this->_getParam('isPublic',1));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			if(!in_array($isPublic, array(0,1))){
				throw  new Exception('isPublic参数值错误！');
			}
			$memberID = $this->memberInfo->MemberID;
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			$groupMemberModel = new Model_IM_GroupMember();
			$groupMemberInfo = $groupMemberModel->getInfo($memberID,$groupID);
			if(empty($groupMemberInfo)){
				$data = array();
				$data['GroupID'] = $groupID;
				$data['MemberID'] = $memberID;
				$data['NickName'] = '';
				$data['AddTime'] = date('Y-m-d H:i:s');
				$data['IsPublic'] = $isPublic;
				$groupMemberModel->insert($data);
			}else{
				$groupMemberModel->updateInfo($groupID, $memberID,$isPublic);
			}
			if($groupInfo['OwnerID'] == $memberID){
				$groupModel->update(array('IsPublic'=>$isPublic),array('GroupID = ?'=>$groupID));	
			}
			$this->returnJson(parent::STATUS_OK,'设置成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 更新最后请求的群公告ID
	 */
	public function updateLastidAction()
	{
		try{
			$lastID = intval($this->_getParam('lastID',0));
			$groupID = trim($this->_getParam('groupID',''));
			if(empty($lastID)||empty($groupID)){
				throw new Exception('参数值不能为空！');
			}
			$AModel = new Model_IM_GroupAnnouncement();
			$AModel->updateLastID($lastID,$groupID,$this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 设置群成员昵称是否显示
	 */
	public function showNicknameAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$isShow = intval($this->_getParam('isShow',1));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			if(!in_array($isShow, array(0,1))){
				throw  new Exception('isShow参数值错误！');
			}
			$memberID = $this->memberInfo->MemberID;
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			$groupMemberModel = new Model_IM_GroupMember();
			$groupMemberModel->update(array('ShowNickName'=>$isShow),array('GroupID = ?'=>$groupID,'MemberID = ?' =>$memberID));
			$this->returnJson(parent::STATUS_OK,'设置成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 是否有新的群公告
	 */
	public function hasNewAnnouncementAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			$memberID = $this->memberInfo->MemberID;
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			//获取是否第一次进群
			$cacheKey = 'isFirstJoinGroup:Group'.$groupID.'MemberID'.$memberID;
			$redisObj = DM_Module_Redis::getInstance();
			$num = $redisObj->get($cacheKey);
			$num = $num ? $num : 0;
			if(!$num){
				$redisObj->INCR($cacheKey);
			}
			$hasNewAnnouncement = 0;
			$content = '';
			$newestAnnouncementID = 0;
			$info = array();
			$AModel = new Model_IM_GroupAnnouncement();
			$lastID = $AModel->getLastID($groupID,$memberID);
			$select = $AModel->select()->from('group_announcement',array('AnnouncementID','Content'))->where('GroupID = ?',$groupID)->where('Status = ?',1);
			if($num){
				$results = $select->order('AnnouncementID desc')->limit(1)->query()->fetch();
			}else{
				$info = $select->order('AnnouncementID desc')->limit(1)->query()->fetch();
				$results = $select->where('IsTop = ?',1)->limit(1)->query()->fetch();
			}
			if(!empty($results) && $results['AnnouncementID']>$lastID){
				$hasNewAnnouncement = 1;
				$content = $results['Content'];
				$newestAnnouncementID = $results['AnnouncementID'];
			}
			if(!empty($info)){
				$newestAnnouncementID = $info['AnnouncementID'];
			}
			$this->returnJson(parent::STATUS_OK,'',array('HasNewAnnouncement'=>$hasNewAnnouncement,'Content'=>$content,'NewestAnnouncementID'=>$newestAnnouncementID));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取最新群组
	 */
	public function recentGroupsAction()
	{
		try{
			$groupMdoel = new Model_IM_Group();
			$select = $groupMdoel->select();
			$groupList = $select->from('group',array('AID','GroupID','GroupName','GroupAvatar','OwnerID'))
			->where('Status = 1')->where('IsPublic = 1')->order('AID desc')->limit(50)->query()->fetchAll();
			if(!empty($groupList)){
				$groupFocusModel = new Model_IM_GroupFocus();
				foreach($groupList as &$info){
					$info['Focus'] = $groupFocusModel->getFocusInfo($info['GroupID'],null,'FocusID');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$groupList));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取热门群组
	 */
	public function hotGroupsAction()
	{
		try{
			$redisObj = DM_Module_Redis::getInstance();
			$key = 'HotGroup';
			$GArr = $redisObj->zRevRangeByScore($key,'+inf','-inf');
			$groupList = array();
			if(!empty($GArr)){
				$groupMdoel = new Model_IM_Group();
				$select = $groupMdoel->select();
				$groupList = $select->from('group',array('AID','GroupID','GroupName','GroupAvatar','OwnerID'))->where('AID in (?)',$GArr)
				->where('Status = 1')->where('IsPublic = 1')->order(new Zend_Db_Expr("field(AID,".implode(',',$GArr).")"))->query()->fetchAll();
				$groupFocusModel = new Model_IM_GroupFocus();
				foreach($groupList as &$info){
					$info['Focus'] = $groupFocusModel->getFocusInfo($info['GroupID'],null,'FocusID');
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$groupList));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 解散群登录密码验证
	 */
	public function checkPasswordAction()
	{
		try{
			$passWord = $this->_getParam('passWord','');
			if(empty($passWord)){
				throw new Exception('参数错误！');
			}
			
			$aesModel = new Model_CryptAES();
			$passWord = $aesModel->decrypt($passWord);
			$passWord = md5(md5($passWord.'_$$_DMLIB').$this->memberInfo->Salt);
			if($passWord !== $this->memberInfo->Password){
				throw new Exception('密码错误！');
			}
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 邀请会员加入群组
	 */
	public function inviteJoinAction()
	{
		$easeModel = new Model_IM_Easemob();
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			
			$inviteMembers = trim($this->_request->getParam('inviteMembers',''));
			$inviteMembersArr = explode(',', $inviteMembers);
			if(!is_array($inviteMembersArr) || count($inviteMembersArr) < 1){
				throw new Exception('被邀请的者不能为空');	
			}
			
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能进行该操作！');
			}
			
			//环信加群
			$res = $easeModel->addGroupsUserBatch($groupID, array('usernames'=>$inviteMembersArr));
			$resArr = json_decode($res,true);
			if(!(isset($resArr['data']['newmembers']) && count($resArr['data']['newmembers']) >= 1)){
				throw new Exception('加群操作失败！');
			}
			
			$realAdd = array();
			$groupApplyModel = new Model_IM_GroupApply();
			foreach($resArr['data']['newmembers'] as $item){
				//应用服务加群
				$groupModel->addGroupMember($item, $groupID);
				$realAdd[] = $item;
				$groupApplyModel->update(array('ApplyStatus'=>1,'ProcessTime'=>date('Y-m-d H:i:s')),array('GroupID = ?'=>$groupID,'ApplyMemberID = ?'=>$item));
			}
			
			//发送自定义消息到群组
			$ext['Action'] = 'group';
			$ext['CZSubAction'] = 'inviteJoin';
			$ext['JoinedMembers'] = implode(',', $realAdd);
			$ext['GroupID'] = $groupID;
			
			$easeModel->yy_hxSend(array($groupInfo['GroupID']),'有新成员加入群组','txt','chatgroups',$ext);
			
			$this->returnJson(parent::STATUS_OK,'');
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 群主移除指定成员
	 */
	public function removeMemberAction()
	{
		$easeModel = new Model_IM_Easemob();
		$groupModel = new Model_IM_Group();
		try{
			$groupID = trim($this->_request->getParam('groupID',''));
			$groupInfo = $groupModel->getInfo($groupID);
			$removeMemberID = trim($this->_request->getParam('removeMemberID',''));
			if(empty($removeMemberID)){
				throw new Exception('请指定将被移除群的成员');
			}
			
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
				
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能进行该操作！');
			}
			
			//环信服务器移除成员
			$res = $easeModel->delGroupsUser($groupID, $removeMemberID);
			$resArr = json_decode($res,true);
			if(!(isset($resArr['data']['result']) && $resArr['data']['result'] == true)){
				throw new Exception('移除群成员操作失败');
			}
			
			//应用服务器移除群成员
			$groupModel->quitGroup($removeMemberID, $groupID);
			
			//透传消息给被移除的成员
			$ext['CZSubAction'] = "removedFromGroup";
			$ext['CZSendFrom'] = 'server';
			$ext['GroupName'] = $groupInfo['GroupName'];
			$ext['GroupID'] = $groupID;
			$ext['Message'] = '您已被群主移出群'.$groupInfo['GroupName'];
			$easeModel->tc_hxSend(array($removeMemberID), 'group','cmd','users',$ext);
			
			//发送自定义消息给群组
			$ext2['Action'] = 'group';
			$ext2['CZSubAction'] = "beRemoved";
			$ext2['CZSendFrom'] = 'server';
			$ext2['RemovedMemberID'] = $removeMemberID;
			$ext2['GroupID'] = $groupID;
			
			//$memberModel = new DM_Model_Account_Members();
			//$removeMemberUserName = $memberModel->getMemberInfoCache($removeMemberID,'UserName');
			
			$easeModel->yy_hxSend(array($groupInfo['GroupID']),'有成员退出群','txt','chatgroups',$ext2);
			
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取我加入的群组
	 */
	public function getMyJoinedGroupsAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$groupMemberModel = new Model_IM_GroupMember();
		try{
			$groups = $groupMemberModel->getJoinedGroups($memberID,1,1);
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$groups));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 置顶/取消置顶群公告
	 */
	public function topGroupAnnocementAction()
	{
		try{
			$groupID = trim($this->_getParam('groupID',''));
			$announcementID = intval($this->_getParam('announcementID',0));
			$type = intval($this->_getParam('type',0));//1置顶 2取消置顶
			if(empty($groupID)){
				throw new Exception('群组ID不能为空！');
			}
			if(empty($announcementID)){
				throw new Exception('公告ID不能为空！');
			}
			if(!in_array($type, array(1,2))){
				throw new Exception('类型参数错误！');
			}
			$isTop = $type == 1 ? 1:0;
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($groupID);
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能操作');
			}
			$AModel = new Model_IM_GroupAnnouncement();
			if($isTop){//置顶，先把其他置顶取消
				$AModel->update(array('IsTop'=>0),array('GroupID = ?'=>$groupID ,'Istop = ?'=>1));
			}
			$AModel->update(array('IsTop'=>$isTop),array('AnnouncementID = ?'=>$announcementID));
			$this->returnJson(parent::STATUS_OK,'成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 绑定话题
	 */
	public function bindTopicAction()
	{
		try{
			$AID = intval($this->_getParam('AID',0));
			$topicID = intval($this->_getParam('topicID',0));
			
			$groupTopicModel = new Model_IM_GroupTopic();
			
			if(empty($topicID)){
				throw new Exception("请选择话题");
			}
			
			$topicModel = new Model_Topic_Topic();
			$topicInfo = $topicModel->getTopicInfo($topicID,1);
			if(empty($topicInfo)){
				throw new Exception('请选择有效的话题');
			}
			
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($AID);
			
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
			
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能操作');
			}
			
			$bindTopicInfo = $groupTopicModel->getInfo($groupInfo['AID']);
			if(!empty($bindTopicInfo)){
				throw new Exception('已绑定话题，不能重复绑定');
			}
			
			$groupTopicModel->addInfo($groupInfo['AID'], $topicID);
			
			$this->returnJson(parent::STATUS_OK,'绑定成功');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 解绑话题
	 */
	public function unBindTopicAction()
	{
		try{
			$AID = intval($this->_getParam('AID',0));
			$topicID = intval($this->_getParam('topicID',0));
				
			$groupTopicModel = new Model_IM_GroupTopic();
				
			if(empty($topicID)){
				throw new Exception("请选择话题");
			}
			
			$groupModel = new Model_IM_Group();
			$groupInfo = $groupModel->getInfo($AID);
				
			if(empty($groupInfo)){
				throw new Exception('不存在该群组！');
			}
				
			if($groupInfo['OwnerID'] != $this->memberInfo->MemberID){
				throw new Exception('非群主不能操作');
			}
				
			$bindTopicInfo = $groupTopicModel->getInfo($groupInfo['AID'],$topicID);
			if(empty($bindTopicInfo)){
				throw new Exception('未绑定该话题，不能解绑');
			}
			
			$groupTopicModel->unbind($AID, $topicID);
			$this->returnJson(parent::STATUS_OK,'解绑成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
}