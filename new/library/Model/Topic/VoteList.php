<?php
/**
 *  今日话题投票列表
 *  
 * @author Kitty
 *
 */
class Model_Topic_VoteList extends Zend_Db_Table
{
	protected $_name = 'vote_list';
	protected $_primary = 'VoteListID';
	
	/**
	 *  获取信息
	 * @param int $memberID 用户id
	 * @param int $memberID 用户id
	 */
	public function add($memberID,$infoID)
	{
		return $this->insert(array('MemberID'=>$memberID,'InfoID'=>$infoID));
	}

	/*
	 *是否投票过
	 */
	public function isVoted($infoID,$memberID)
	{
		$select = $this->select()->from($this->_name,"COUNT(1)")->where('MemberID =?',$memberID);
		if(is_array($infoID)){
			$isVoted = $select->where('InfoID in (?)',$infoID)->query()->fetchColumn();
		}else{
			$isVoted = $select->where('InfoID = ? ',$infoID)->query()->fetchColumn();
		}
		return $isVoted ? $isVoted : 0;
	}

	/*
	用户一天投票次数
	 */
	public function votedOneday($memberID)
	{
		$select = $this->select();
		$startTime = date('Y-m-d H:i:s',strtotime(date('Y-m-d',time())));
		$endTime =date('Y-m-d H:i:s',time());
		$count = $select->from($this->_name,"COUNT(1)")->where('MemberID =?',$memberID)
		                  ->where('CreateTime >= ?',$startTime)
		                  ->where('CreateTime <= ?',$endTime)->query()->fetchColumn();

		return $count ? $count : 0;
	}
}