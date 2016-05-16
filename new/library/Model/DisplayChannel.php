<?php

/**
 * 渠道 kitty
 */

class Model_DisplayChannel extends Zend_Db_Table {
    protected $_name = 'display_channel';
    protected $_primary = 'DisplayChannelID';

    /**
     * 获取显示渠道列表
     */
    public function getDisplayChannel()
    { 
        $info = $this->select()->from($this->_name,array('EnName','ZhName'))->where('Status = ?',1)->query()->fetchAll();
        return !empty($info) ? $info : array();
    } 


} 
