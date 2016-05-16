<?php
/**
 *  会员频道管理
 * @author Kitty
 *
 */
class Model_MemberChannel extends Zend_Db_Table
{
	protected $_name = 'member_channel';
	protected $_primary = 'MemberChannelID';
	
	/**
	 *  增加频道
	 * @param int $memberID
	 * @param int $channelID
	 */
	public function addChannel($memberID,$channelID,$sort)
	{
		$db = $this->getAdapter();
		$sql = "insert into member_channel(MemberID,ChannelID,Sort) values(:MemberID,:ChannelID,:Sort) on duplicate key update Sort = '".$sort."'";
		$db->query($sql,array('MemberID'=>$memberID,'ChannelID'=>$channelID,'Sort'=>$sort));
		return true;
	}

	/**
	 *  移除频道
	 * @param int $channelID
	 */
	public function removeChannel($memberID,$channelID = null)
	{
        if( !is_null($channelID) && empty($channelID)) {
            throw new Exception('频道ID不能为空！');
        }
        //$channelInfo = $this->getChannelInfo($memberID,$channelID);
        $whereArr = array('MemberID = ?'=>$memberID);
        if(!is_null($channelID)){
        	$whereArr['ChannelID = ?'] = $channelID;
        }
		$this->delete($whereArr);   
        return true; 
	}
	
	/**
	 *  获取频道信息
	 * @param int $memberID
	 * @param int $channelID
	 */
	public function getChannelInfo($memberID,$channelID = null,$fields ='*')
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as mc',$fields)->where('mc.MemberID = ?',$memberID);
		$select->joinLeft('channel as c', 'c.ChannelID = mc.ChannelID ','ChannelName');
		if(!is_null($channelID)){
			return $select->where('mc.ChannelID = ?',$channelID)->query()->fetch();
		}
		$res = $select->order('mc.Sort asc')->query()->fetchAll();
		return $res ? $res : array();
	}

	/**
	 *  设置频道清空标识
	 * @param int $memberID
	 */
	public function setCleanMark($memberID,$mark = 1)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$channelKey = 'Statistic:Member:'.$memberID;
		$redisObj->hSet($channelKey,'isCleanChannel',$mark);
		return true;
	}

	/**
	 *  获取频道清空标识
	 * @param int $memberID
	 */
	public function getCleanMark($memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$channelKey = 'Statistic:Member:'.$memberID;
		$mark = $redisObj->hGet($channelKey,'isCleanChannel');
		return $mark ? 1 : 0;
	}

}