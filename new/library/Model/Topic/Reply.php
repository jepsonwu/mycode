<?php
/**
 *  观点回复
 *  
 * @author root
 *
 */
class Model_Topic_Reply extends Zend_Db_Table
{
	protected $_name = 'view_replies';
	protected $_primary = 'ReplyID';
	
	/**
	 *  获取回复列表
	 * @param int $topicID
	 */
	public function getList($viewID,$lastReplyID = 0,$limit = 5,$viewByMemberID = 0)
	{
		$viewModel = new Model_Topic_View();
		$viewInfo = $viewModel->getViewInfo($viewID);
		$isAnonymous = !empty($viewInfo)?$viewInfo['IsAnonymous']:0;

		$fields = array('ReplyID','MemberID','CreateTime','ReplyContent','PraiseNum','ReplyMemberID','RelationID','IsAnonymous','AnonymousUserName','AnonymousAvatar');
		$select = $this->select()->from($this->_name,$fields)->where('ViewID = ?',$viewID)->where('Status = ?',1);
		if($lastReplyID > 0){
			$select->where('ReplyID < ?',$lastReplyID);
		}
		$select->order('ReplyID desc')->limit($limit);
		$replies = $select->query()->fetchAll();
		if(!empty($replies)){
			$memberModel = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
			foreach($replies as &$r){
				if($isAnonymous ==1 ){
					$userName = $r['AnonymousUserName'];
					$avatar = $r['AnonymousAvatar'];
					$noteName= '';
					$replyUserName = '';
					if($r['RelationID'] > 0 && $r['ReplyMemberID'] > 0){
						$relationReplyInfo = $this->getReplyInfo($r['RelationID'],$r['ReplyMemberID']);
						$replyUserName = !empty($relationReplyInfo)?$relationReplyInfo['AnonymousUserName']:'';
					}
					$replyNoteName = '';
					//unset($r['MemberID']);
					unset($r['ReplyMemberID']);
				}else{
					$userName = $memberModel->getMemberInfoCache($r['MemberID'],'UserName');
					$avatar = $memberModel->getMemberAvatar($r['MemberID']);
					$noteName = $memberNoteModel->getNoteName($viewByMemberID, $r['MemberID']);
					$replyUserName = $r['ReplyMemberID'] > 0 ? $memberModel->getMemberInfoCache($r['ReplyMemberID'],'UserName') : '';
					$replyNoteName = $r['ReplyMemberID'] > 0 ? $memberNoteModel->getNoteName($viewByMemberID, $r['ReplyMemberID']) : '';
				}
				//unset($r['IsAnonymous']);
				unset($r['RelationID']);
				unset($r['AnonymousUserName']);
				unset($r['AnonymousAvatar']);
				$r['Avatar'] = $avatar;
				$r['UserName'] = $userName;
				$r['NoteName'] = $noteName; 
				$r['ReplyUserName'] = $replyUserName;
				$r['ReplyNoteName'] = $replyNoteName;
			}
		}
		return $replies ? $replies : array();
	}
	
	
	/**
	 * 获取回复详情
	 */
	public function getReplyDeatail($replyID,$viewByMemberID = 0)
	{
		$fields = array('ReplyID','MemberID','CreateTime','ReplyContent','PraiseNum','ReplyMemberID','RelationID','IsAnonymous','AnonymousUserName','AnonymousAvatar');
		$select = $this->select()->from($this->_name,$fields)->where('ReplyID = ?',$replyID);
		$replyInfo = $select->query()->fetch();
		$memberNoteModel = new Model_MemberNotes();
		if(!empty($replyInfo)){
			$memberModel = new DM_Model_Account_Members();
			if($replyInfo['IsAnonymous']==1){
				$userName = $replyInfo['AnonymousUserName'];
				$avatar = $replyInfo['AnonymousAvatar'];
				$replyUserName = '';
				if($replyInfo['RelationID'] > 0 && $replyInfo['ReplyMemberID'] > 0){
					$relationReplyInfo = $this->getReplyInfo($replyInfo['RelationID'],$replyInfo['ReplyMemberID']);
					$replyUserName = !empty($relationReplyInfo)?$relationReplyInfo['AnonymousUserName']:'';
				}
				
				$replyNoteName = '';
				unset($replyInfo['MemberID']);
				unset($replyInfo['ReplyMemberID']);
			}else{
				$userName = $memberModel->getMemberInfoCache($replyInfo['MemberID'],'UserName');
				$avatar = $memberModel->getMemberAvatar($replyInfo['MemberID']);
				$replyUserName = $replyInfo['ReplyMemberID']>0?$memberModel->getMemberInfoCache($replyInfo['ReplyMemberID'],'UserName'):'';
				$replyNoteName = $replyInfo['ReplyMemberID']>0?$memberNoteModel->getNoteName($viewByMemberID, $replyInfo['ReplyMemberID']) : '';
			}

			unset($replyInfo['RelationID']);
			//unset($replyInfo['IsAnonymous']);
			unset($replyInfo['AnonymousUserName']);
			unset($replyInfo['AnonymousAvatar']);
			$replyInfo['Avatar'] = $avatar;
			$replyInfo['UserName'] = $userName;
			$replyInfo['ReplyUserName'] = $replyUserName;
			$replyInfo['ReplyNoteName'] = $replyNoteName;
		}
		return $replyInfo;
	}
	
	/**
	 * 增加回复
	 * @param int $viewID
	 * @param int $memberID
	 * @param string $replyContent
	 */
	public function addRely($viewID,$memberID,$replyContent,$replyMemberID,$relationID,$isAnonymous=0,$anonymousUserName='',$anonymousAvatar='')
	{
		$data = array(
							'ViewID'=>$viewID,
							'MemberID'=>$memberID,
							'ReplyContent'=>$replyContent,
							'ReplyMemberID'=>$replyMemberID,
							'RelationID' =>$relationID,
							'IsAnonymous'=>$isAnonymous,											
							'AnonymousUserName'=>$anonymousUserName,
							'AnonymousAvatar'=>$anonymousAvatar
			);
		$newReplyID = $this->insert($data);
		if($newReplyID > 0){
			$viewModel = new Model_Topic_View();
			$viewInfo = $viewModel->getViewInfo($viewID);
			$viewModel->increaseReplyNum($viewID);
			$messageModel = new Model_Message();
			if($viewInfo['MemberID'] != $memberID){
				$messageModel->addMessage($viewInfo['MemberID'], 1, $newReplyID,1);
			}
			if($replyMemberID>0 && $replyMemberID != $memberID && $replyMemberID != $viewInfo['MemberID']){
				$messageModel->addMessage($replyMemberID, 1, $newReplyID,1);
			}
		}
		return $newReplyID;		
	}
	
	/**
	 *  获取回复信息
	 * @param int $replyID
	 */
	public function getReplyInfo($replyID,$memberID=null)
	{
		$select = $this->select()->where('ReplyID = ?',$replyID);
		if($memberID){
			$select = $select->where('MemberID = ?',$memberID);
		}
		return $select->query()->fetch();
	}

	/**
	 *  增加赞
	 * @param int $replyID
	 * @param int $increament
	 */
	public function increasePraiseNum($replyID,$increament = 1)
	{
		return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('ReplyID = ?'=>$replyID));
	}

	/**
	 *  获取赞的数量
	 * @param int $replyID
	 */
	public function getPraisedNum($replyID)
	{
		$select = $this->select();
		$praiseNum = $select->from($this->_name,'PraiseNum')->where('ReplyID = ?',$replyID)->query()->fetchColumn();
		return $praiseNum ? $praiseNum : 0;
	}

	/**
	 *  是否已赞过
	 * @param int $replyID
	 * @param int $memberID
	 */
	public function isPraised($replyID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'ReplyPraise:MemberID:'.$memberID;
		$score = $redisObj->zscore($cacheKey,$replyID);
		return $score ? 1 : 0;
	}
}