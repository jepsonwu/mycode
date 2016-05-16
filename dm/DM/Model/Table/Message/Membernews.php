<?php
class DM_Model_Table_Message_Membernews extends  DM_Model_Table
{
	protected $_name = 'message_member_news';
	
	protected $_primary = 'MemberNewsId';

	/**
	 * 根据type_id 获取信息
	 * @param int $type_id
	 */
	public function getBroadcastInfoByID($broadcast_id)
	{

		$select = $this->_db->select();
        $select->from($this->_name)
               ->where("BroadcastId = ?", $broadcast_id);
  
        return $this->_db->fetchRow($select);
	}
}