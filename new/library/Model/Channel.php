<?php

/**
 * 频道 kitty
 */

class Model_Channel extends Zend_Db_Table {
    protected $_name = 'channel';
    protected $_primary = 'ChannelID';

    /**
     * 获取频道列表
     */
    public function getChannels()
    { 
        $info = $this->select()->from($this->_name,array('ChannelID','ChannelName'))->query()->fetchAll();
        return !empty($info) ? $info : array();
    } 


    /**
     * 添加频道
     * @param string $channelName
     */
    public function addChannel($channelName,$sort)
    {
        $channelInfo = $this->getChannelByName($channelName);
        if(empty($channelInfo)){
           return $this->insert(array('ChannelName'=>$channelName,'Sort'=>$sort));
        }
        return 0;
    } 

    /**
     * 根据频道名称获取信息
     * @param string $channelName
     */
    public function getChannelByName($channelName)
    {
        $select = $this->select();
        $select->from($this->_name)->where('ChannelName = ?',$channelName);
        return $select->query()->fetch();
    } 


    /**
     * 删除频道
     * @param int $channelID
     */
    public function removeChannel($channelID)
    {
        $channelFocusModel = new Model_ChannelFocus();
        $channelFocusModel->delete(array('ChannelID = ?'=>$channelID));
        return $this->delete(array('ChannelID = ?' => $channelID));
    } 

} 
