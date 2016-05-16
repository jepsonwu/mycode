<?php
/**
 *  好友申请相关
* @author Jeff 2016-01-25
*
*/
class Model_FriendApplyRelation extends Zend_Db_Table
{
	protected $_name = 'friend_apply_relation';
	protected $_primary = 'RelationID';

	/**
	 * 获取一分钟之内的申请次数
	 * @param unknown $memberID
	 */
	public function getApplyNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(1) as totalNum')->where('ApplyMemberID = ?',$memberID)
		->where('UNIX_TIMESTAMP(CreateTime) >= ?',(time()-60))->query()->fetch();
		return $info['totalNum'];
	}
	
	/**
	 * 获取最近的3条申请记录
	 * @param unknown $ApplyID
	 */
	public function getNewRecords($ApplyID)
	{
		$result = $this->select()->from($this->_name,array('Content','CreateTime'))->where('ApplyID = ?',$ApplyID)->order('RelationID desc')
					->limit(3)->query()->fetchAll();
		return empty($result)?array():$result;
	}
	
	/**
	 * 是否有新的好友申请
	 */
	public function hasNewPoints($memberID){
		$lastTime = Model_Member::staticData($memberID,'lastApplyFriendTime');
		$lastTime = empty($lastTime)?0:$lastTime;
		$result = array('isShowPoint'=>0,'userName'=>'','createTime'=>'');
		$select = $this->select()->setIntegrityCheck(false);
		$info = $select->from('friend_apply_relation as a' ,array('a.ApplyMemberID','a.CreateTime'))
		->joinInner('friend_apply as b', 'a.ApplyID = b.ApplyID')->where('b.AcceptMemberID = ?',$memberID)
		->where('UNIX_TIMESTAMP(a.CreateTime) >?',$lastTime)->where('b.Status = ?',1)
		->order('a.RelationID desc')->limit(1)->query()->fetch();
		if(empty($info)){
			$select = $this->select()->setIntegrityCheck(false);
			$info = $select->from('friend_apply_relation as r' ,array('r.ApplyMemberID','r.CreateTime'))
			->joinInner('friend_apply as c', 'r.ApplyID = c.ApplyID',null)->where('c.Status = ?',1)
			->where('c.AcceptMemberID = ?',$memberID)
			->order('r.RelationID desc')->limit(1)->query()->fetch();
		}else{
			$result['isShowPoint'] = 1;
		}
		if(!empty($info)){
			$memberModel = new DM_Model_Account_Members();
			$result['userName'] = $memberModel->getMemberInfoCache($info['ApplyMemberID'],'UserName');;
			$result['createTime'] = $info['CreateTime'];
		}
		return $result;
	}
	
}