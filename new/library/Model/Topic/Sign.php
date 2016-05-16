<?php
/**
 * 话题签到
 *
 * @author Mark
 *
 */
class Model_Topic_Sign extends Zend_Db_Table
{
	protected $_name = 'topic_sign';
	protected $_primary = 'SignID';
	
	/**
	 * 获取今天是否签到
	 * @param unknown $topicID
	 * @param unknown $memberID
	 */
	public function getTodaySign($topicID,$memberID) 
	{
		$info = $this->select()->where("DATE_FORMAT(CreateTime,'%Y-%m-%d')= ?",date('Y-m-d'))->where('MemberID = ?',$memberID)
		->where('TopicID = ?',$topicID)->query()->fetch();
		return $info;
	}
	
	public function getYesterdaySign($topicID,$memberID)
	{
		$yesterday =  date('Y-m-d' , strtotime('-1 day'));
		$info = $this->select()->where("DATE_FORMAT(CreateTime,'%Y-%m-%d')= ?",$yesterday)->where('MemberID = ?',$memberID)
		->where('TopicID = ?',$topicID)->query()->fetch();
		return $info;
	}
	
	/**获取某个话题下连续签到前20的会员
	 * @param unknown $topicID
	 */
	public function getFanList($topicID)
	{
		$db= $this->getAdapter();
		$fanArr = $db->fetchCol("SELECT DISTINCT MemberID FROM topic_sign WHERE `TopicID` = :TopicID  ORDER BY `SerialNum` desc LIMIT 20",array("TopicID" => $topicID));
		return empty($fanArr)? array() : $fanArr;
	}
}