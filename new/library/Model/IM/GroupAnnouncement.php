<?php
/**
 *  群组公告
 * @author Jeff
 *
 */
class Model_IM_GroupAnnouncement extends Zend_Db_Table
{
	protected $_name = 'group_announcement';
	protected $_primary = 'AnnouncementID';
	
	/**
	 * @param string $groupID
	 * 获取公告数量
	 */
	
	public function getAnnouncements($where, $orderBy = null, $limit = null, $offset = null) {
		if(is_numeric($where)) {
			$where = $this->_primary . '=' . $where;
		}
		$data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
		return $data ? $data : null;
	}

	public function getcount($groupID) {
		$select = $this->select()->from($this->_name,'count(1) as num');
		$select->where('GroupID = ?',$groupID)->where('Status = ?',1);
		$row = $select->query()->fetch();
		return isset($row['num']) ? intval($row['num']) : 0;
	}
	
	/**
	 * 获取上次请求的ID
	 */
	public function getLastID($groupID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Announcement:MemberID:'.$memberID;
		$field = "G".$groupID;
		$lastID = $redisObj->hget($cacheKey,$field);
		return $lastID?$lastID:0;
	}
	
	/**
	 * 更新redis里保存的LastID
	 */
	public function updateLastID($lastID,$groupID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Announcement:MemberID:'.$memberID;
		$field = "G".$groupID;
		$redisObj->hset($cacheKey,$field,$lastID);
		return true;
	}
}


