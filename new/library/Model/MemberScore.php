<?php
/**
 * 观点
 * @author Jeff
 *
 */
class Model_MemberScore extends Zend_Db_Table
{
	protected $_name = 'member_score';
	protected $_primary = 'SID';
	
	/**
	 * 添加用户评分
	 * @param unknown $memberID
	 * @param unknown $score
	 */
	public function add($memberID,$hotScore,$recentScore)
	{
		$info = $this->getMemberScore($memberID);
		$data = array('HotScore'=>$hotScore,'RecentScore'=>$recentScore);
		if(!empty($info)){
			$this->update($data, array('MemberID = ?'=>$memberID));
		}else{
			$data = array('HotScore'=>$hotScore,'RecentScore'=>$recentScore,'MemberID'=>$memberID);
			$this->insert($data);
		}
		return true;
	}
	
	/**
	 * 获取某个用户的达人评分
	 * @param unknown $memberID
	 */
	public function getMemberScore($memberID)
	{
		return $this->select()->from($this->_name)->where('MemberID = ?', $memberID)->query()->fetch();
	}
}