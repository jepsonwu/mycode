<?php
class DM_Model_Table_Message_Room extends  DM_Model_Table
{
	protected $_name = 'message_room';
	
	protected $_primary = 'RoomId';


	public function veryfyExist($roomname)
	{
        $select = $this->_db->select();
        $select->from($this->_name)
               ->where("RoomName = ?", $roomname)    
               ->limit(1);
        $item =  $this->_db->fetchRow($select);
        return !empty($item) ? TRUE : FALSE;
	}

	/**
	 * 根据type_id 获取信息
	 * @param int $type_id
	 */
	public function getRoomInfoByID($room_id)
	{

		$select = $this->_db->select();
        $select->from($this->_name)
               ->where("RoomId = ?", $room_id);
  
        return $this->_db->fetchRow($select);
	}

	/**
	 * 获取房间信息
	 */
	public function getPairsInfo()
    {
    	$select = $this->_db->select();
    	$select->from($this->_name,array('RoomId','RoomName'))->order('RoomId desc');
    	return $this->_db->fetchPairs($select);
    }
}