<?php
class DM_Model_Table_Message_Broadcast extends  DM_Model_Table
{
	protected $_name = 'message_broadcast';
	
	protected $_primary = 'BroadcastId';

	/**
	 * 根据BroadcastId 获取信息
	 * @param int $BroadcastId
	 */
	public function getBroadcastInfoByID($BroadcastId)
	{

		$select = $this->_db->select();
        $select->from($this->_name)
               ->where("BroadcastId = ?", $BroadcastId);  
        return $this->_db->fetchRow($select);
	}
	
	/**
	 * 广播
	 */
	public function broadCastMessage($broadCast_id,$data,$room_id = 0)
	{
	    $redis = DM_Module_Redis::getInstance();
	    if($room_id == 0){
	        //全局广播
	        $redis->rpush('message_list:broadcast',$broadCast_id);
	        $redis->hmset('message_detail:broadcast:'.$broadCast_id,$data);
	    }else{
	        //指定房间广播
	        $redis->rpush('message_list:room:'.$room_id,$broadCast_id);
	        $redis->hmset('message_detail:room:'.$room_id.':'.$broadCast_id,$data);
	    }
	    return true;
	}
}