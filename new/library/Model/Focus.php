<?php
/**
 *关注点
 * @author Kitty
 */
class Model_Focus extends Zend_Db_Table {
    protected $_name = 'focus';
    protected $_primary = 'FocusID';


    /**
     * 添加关注点
     * @param int $focusName
     */
    public function addFocus($focusParam)
    {
        $focusInfo = $this->getFocusByName($focusParam['FocusName']);
        if(empty($focusInfo)){
           return $this->insert($focusParam);
        }
        return 0;

    }


    public function deleteFocus($focusID = null) {
        if( !$focusID ) {
            throw new Exception('关注点ID不能为空');
        }
        $channelFocusModel = new Model_ChannelFocus();
        $channelFocusInfo = $channelFocusModel->getInfo($focusID,$channelID=null,'ChannelID');
        foreach ($channelFocusInfo as $item) {
            $channelFocusModel->removeChannelFocus($focusID,$item['ChannelID']);
        }        
        return $this->delete(array('FocusID = ?' => $focusID));
    } 

    public function getFocuss($where = null, $orderBy = null, $limit = null, $offset = null) {
        if (is_numeric($where)) {
            $where = $this->_primary . '=' . $where;
        } 
        $data = $this -> fetchAll($where, $orderBy, $limit, $offset) -> toArray();
        return empty($data) ? null : $data;
    }

    /**
     * 根据关注点名称获取关注点
     * @param int $focusName
     */
    public function getFocusByName($focusName)
    {
        $select = $this->select();
        $select->from($this->_name)->where('FocusName = ?',$focusName);
        return $select->query()->fetch();
    }

    /**
     * 根据标签类型获取标签
     * @param int $focusType
     */
    public function getFocusList($focusType='')
    {   
        $fieldsArr = array('FocusID','FocusName');
        $select = $this->select();
        $select->from($this->_name,$fieldsArr);
        if(!empty($focusType)){
            $select->where($focusType.' = 1');
        }
        return $select->query()->fetchAll();
    }
    
    /**
     * 获取关注点信息
     */
    public function getInfo($focusID) {
    	$data = $this -> select()->where("FocusID = ?" , $focusID)->query()->fetch();
    	return empty($data) ? null : $data;
    }

} 

