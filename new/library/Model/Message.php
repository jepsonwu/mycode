<?php
/**
 *  消息
 *  
 * @author Mark
 *
 */
class Model_Message extends Zend_Db_Table
{
	protected $_name = 'member_messages';
	protected $_primary = 'MessageID';
    
    const MESSAGE_TYPE_VIEW = 1;
    const MESSAGE_TYPE_SHUO_REPLY = 2;
    const MESSAGE_TYPE_SHUO_PRAISES = 3;
    const MESSAGE_TYPE_RECHARGE = 4;
    const MESSAGE_TYPE_REFUND = 5;
    const MESSAGE_TYPE_PAY = 6;
    const MESSAGE_TYPE_INCOME = 7;
    const MESSAGE_TYPE_BACK = 8;

    const MESSAGE_SIGN_VIEW = 1;
    const MESSAGE_SIGN_SHUO = 2;
    const MESSAGE_SIGN_WALLET = 3;

    /**
	 *  添加消息
	 * @param int $memberID
	 * @param int $messageType
	 * @param int $relationID
	 */
	public function addMessage($memberID,$messageType,$relationID,$messageSigin)
	{
        /*if($messageType==self::MESSAGE_TYPE_RECHARGE){//判断是不是首次充值
            $select = $this->select()->from($this->_name,'MessageID');
            $info = $select->where('MemberID = ?',$memberID)->where('MessageType=?',$messageType)->query()->fetch();
            if(!empty($info)){
                return true;
            }
        }*/
        if($messageType==self::MESSAGE_TYPE_REFUND){
            $this->delete(array('MemberID = ?'=>$memberID,'MessageType=?'=>$messageType,'RelationID=?'=>$relationID));
        }
		$data = array('MemberID'=>$memberID,'MessageType'=>$messageType,'RelationID'=>$relationID,'MessageSign'=>$messageSigin);
		return $this->insert($data);
	}
	
	/**
	 *  是否有消息
	 * @param int $memberID
	 */
	public function hasMessage($memberID)
	{
		$select = $this->select()->from($this->_name,'count(1) as num');
		$info = $select->where('MemberID = ?',$memberID)->query()->fetch();
		return $info['num'];
	}
	
	/**
	 *  
	 * @param int $memberID
	 * @param int $lastMessageID
	 * @param int $pageSize
	 */
	public function getList($memberID,$lastMessageID,$MessageSign,$limit = 10)
	{
		$fields = array('MessageID','MemberID','MessageType','MessageSign','RelationID','CreateTime');
		$select = $this->select()->from($this->_name,$fields)->where('MemberID = ?',$memberID)->where('MessageSign!=3');
		if($lastMessageID > 0){
			$select->where('MessageID < ?',$lastMessageID);
		}
		if($MessageSign>0){
			$select->where('MessageSign = ?',$MessageSign);
		}
		$select->order('MessageID desc')->limit($limit);
		$lists = $select->query()->fetchAll();
		if(!empty($lists)){
			$replyModel = new Model_Topic_Reply();
			$memberModel = new DM_Model_Account_Members();
			$model = new Model_ShuoComment();
			$modelShuoshuo = new Model_ShuoPraise();
			$memberNoteModel = new Model_MemberNotes();
            $walletModel = new Model_Wallet_Wallet();
			foreach($lists as &$l){
				if($l['MessageType'] == 1){//观点评论
					$replyInfo = $replyModel->getReplyInfo($l['RelationID']);
					$l['Content'] = $replyInfo['ReplyContent'];
					$l['InfoID'] = $replyInfo['ViewID'];
					$l['ReplyUserName'] = '';
					$l['ReplyNoteName'] = '';
					$l['FromNoteName'] = '';
					$l['IsAnonymous'] = $replyInfo['IsAnonymous'];
					if($replyInfo['IsAnonymous'] == 1){
						if($replyInfo['ReplyMemberID'] >0 && $memberID != $replyInfo['ReplyMemberID']){
							$reInfo = $replyModel->getReplyInfo($replyInfo['RelationID']);
							$l['ReplyUserName'] = !empty($reInfo)?$reInfo['AnonymousUserName']:'';
						}
						$l['FromUserAvatar'] = $replyInfo['AnonymousAvatar'];
						$l['FromUserName'] = $replyInfo['AnonymousUserName'];
						unset($l['MemberID']);
					}else{
						if($replyInfo['ReplyMemberID']>0 && $replyInfo['ReplyMemberID']!=$memberID){
							$l['ReplyUserName'] = $memberModel->getMemberInfoCache($replyInfo['ReplyMemberID'],'UserName');
							$l['ReplyNoteName'] = $memberNoteModel->getNoteName($memberID, $replyInfo['ReplyMemberID']);
						}
						$l['FromMemberID'] = $replyInfo['MemberID'];
						$l['FromUserAvatar'] = $memberModel->getMemberAvatar($l['FromMemberID']);
		                $l['FromUserName'] = $memberModel->getMemberInfoCache($l['FromMemberID'],'UserName');
		                $l['FromNoteName'] = $memberNoteModel->getNoteName($memberID, $l['FromMemberID']);
					}
					
				}elseif($l['MessageType'] == 2){//说说评论
					$comentInfo = $model->getCommentInfo($l['RelationID']);
					$l['FromMemberID'] = $comentInfo['CommentBy'];
					$l['Content'] = $comentInfo['CommentTxt'];
					$l['InfoID'] = $comentInfo['ShuoID'];
					$l['ReplyUserName'] = '';
					$l['ReplyNoteName'] = '';
					if($comentInfo['At']>0 && $comentInfo['At']!=$memberID && $comentInfo['At']!=$comentInfo['CommentBy']){
						$l['ReplyUserName'] = $memberModel->getMemberInfoCache($comentInfo['At'],'UserName');
						$l['ReplyNoteName'] = $memberNoteModel->getNoteName($memberID, $comentInfo['At']);
					}
					$l['FromUserAvatar'] = $memberModel->getMemberAvatar($l['FromMemberID']);
	                $l['FromUserName'] = $memberModel->getMemberInfoCache($l['FromMemberID'],'UserName');
	                $l['FromNoteName'] = $memberNoteModel->getNoteName($memberID, $l['FromMemberID']);
				}elseif($l['MessageType'] == 3){//说说点赞
					$info = $modelShuoshuo->getPraises($l['RelationID']);
					$l['FromMemberID'] = $info[0]['PraiseBy'];
					$l['Content'] = '';
					$l['InfoID'] = $info[0]['ShuoID'];
					$l['ReplyUserName'] = '';
					$l['ReplyNoteName'] = '';
					$l['FromUserAvatar'] = $memberModel->getMemberAvatar($l['FromMemberID']);
	                $l['FromUserName'] = $memberModel->getMemberInfoCache($l['FromMemberID'],'UserName');
	                $l['FromNoteName'] = $memberNoteModel->getNoteName($memberID, $l['FromMemberID']);
				}
                //unset($l['RelationID']);			
			}
		}
		return $lists ? $lists : array();
	}
	
	/**
	 * 取消点赞说说删除点赞消息
	 */
	public function delMessage($praiseID){
		$where = array();
		$where['MessageType = ?'] = 3;
		$where['RelationID = ?'] = $praiseID;
		$this->delete($where);
	}
	
	/**
	 * 获取不同消息类型的数量
	 */
	public function getCount($memberID,$messageSign)
	{
		$select = $this->select()->from($this->_name,'count(1) as num')->where('MemberID = ?',$memberID);
		if($messageSign>0){
			$select = $select->where('MessageSign = ?',$messageSign);
		}
		$row = $select->query()->fetch();
		$this->setMaxMessageID($memberID,$messageSign);

		return $row['num'] ? $row['num'] : 0;
	}
	
	/**
	 *  观点相关的新消息数量
	 * @param int $memberID
	 */
	public function newViewMessageCount($memberID,$messageSign=0)
	{
		$maxMessageID = Model_Member::staticData($memberID,'maxReadMessageID');
		if(!$maxMessageID){
			$maxMessageID = 0;
		}
		$select = $this->select()->from($this->_name,array('RelationID','MessageType'))->where('MemberID = ?',$memberID);
		$select->where('MessageID > ?',$maxMessageID);
		if($messageSign>0){
			$select->where('MessageSign = ?',$messageSign);
		}
		$row = $select->order('MessageID desc')->limit(1)->query()->fetch();
		return empty($row) ? array() : $row;
	}
	
	/**
	 *  设置消息最大ID
	 * @param int $memberID
	 * @param int $messageSign
	 */
	private function setMaxMessageID($memberID,$messageSign=0)
	{
		$select = $this->select()->from($this->_name,array('MessageID'))->where('MemberID = ?',$memberID);
		$select->order('MessageID desc')->limit(1);
		if($messageSign>0){
			$select->where('MessageSign = ?',$messageSign);
		}
		$messageID = $select->query()->fetchColumn();
		$messageID = $messageID ? $messageID : 0;
		if($messageID > 0){
			Model_Member::staticData($memberID,'maxReadMessageID',$messageID);
		}
		return $messageID;		
	}
	
	/**
	 *  观点相关的新消息数量
	 * @param int $memberID
	 */
	public function getMessageCount($memberID,$messageSign)
	{
		$maxMessageID = Model_Member::staticData($memberID,'maxReadMessageID');
		if(!$maxMessageID){
			$maxMessageID = 0;
		}
		$select = $this->select()->from($this->_name,'count(1) as num')->where('MemberID = ?',$memberID);
		$select->where('MessageID > ?',$maxMessageID);
		if($messageSign>0){
			$select->where('MessageSign = ?',$messageSign);
		}
		$row = $select->query()->fetch();
		return $row['num'];
	}
}