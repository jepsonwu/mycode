<?php
class DM_Model_Table_Message_Type extends  DM_Model_Table
{
	protected $_name = 'message_type';
	
	protected $_primary = 'TypeId';


	public function veryfyExist($typename)
	{
        $select = $this->_db->select();
        $select->from($this->_name)
               ->where("TypeName = ?", $typename)    
               ->limit(1);
        $item =  $this->_db->fetchRow($select);
        return !empty($item) ? TRUE : FALSE;
	}

	/**
	 * 根据type_id 获取信息
	 * @param int $type_id
	 */
	public function getTypeInfoByID($type_id)
	{

		$select = $this->_db->select();
        $select->from($this->_name)
               ->where("TypeId = ?", $type_id);
  
        return $this->_db->fetchRow($select);
	}

	/**
	 * 获取分类信息
	 */
	public function getPairsInfo()
    {
    	$select = $this->_db->select();
    	$select->from($this->_name,array('TypeId','TypeName'))->order('TypeId desc');
    	return $this->_db->fetchPairs($select);
    }
}