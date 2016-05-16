<?php
/**
 * 关注点与频道关系表 kitty
 */
class Model_ChannelFocus extends Zend_Db_Table {
    protected $_name = 'channel_focus';
    protected $_primary = 'ChannnelFocusID';

    /**
     *  添加关注点与频道的关系
     * @param int $channelID
     * @param int $focusID
     */
    public function addChannelFocus($focusID,$channelID)
    {
        $info = $this->getInfo($focusID,$channelID);
        if(empty($info)){
            $this->insert(array('FocusID'=>$focusID,'ChannelID'=>$channelID));
        }
        return true;
    }

    /**
     *  移除关注点与频道的关系
     * @param int $channelID
     * @param int $focusID
     */
    public function removeChannelFocus($focusID,$channelID)
    {
        $info = $this->getInfo($focusID,$channelID);
        if(!empty($info)){
            $this->delete(array('FocusID = ?'=>$focusID,'ChannelID = ?' => $channelID,));
        }
        return true; 
    }
    
    /**
     *  获取关注信息
     * @param int $memberID
     * @param int $channelID
     */
    public function getInfo($focusID,$channelID=null,$fields ='*')
    {
        $select = $this->select()->setIntegrityCheck(false);
        $select->from($this->_name.' as cf',$fields)->where('cf.FocusID = ?',$focusID);
        $select->joinLeft('channel as c', 'cf.ChannelID = c.ChannelID ','ChannelName');
        if(!is_null($channelID)){
            return $select->where('cf.ChannelID = ?',$channelID)->query()->fetch();
        }
        $res = $select->query()->fetchAll();
        return $res ? $res : array();

    }

    /**
     * 获取频道下的标签ID
     */
    public function getChannelFocusID($channelID)
    {     
        $db = $this->getAdapter();
        $focusIDArr = $db->fetchCol("SELECT FocusID FROM channel_focus WHERE `ChannelID` = :ChannelID",array("ChannelID" => $channelID));
        return $focusIDArr ? $focusIDArr : array();
    }
} 
