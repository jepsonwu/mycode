<?php
/**
 * 违规记录
 * @author Kitty
 *
 */
class Model_Topic_Violations extends Zend_Db_Table
{
	protected $_name = 'topic_violations';
	protected $_primary = 'ViolationsID';


	/**
	 * 获取违规记录
	 * @param int $memberID
	 */
	public function getViolationList($memberID,$fields ='*')
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as tv',$fields)->where('tv.MemberID = ?',$memberID);
		$select->joinLeft('topics as t', 't.TopicID = tv.TopicID','TopicName');
		$res = $select->order('tv.CreateTime desc')->query()->fetchAll();
		return !empty($res) ? $res :array();
	}

}