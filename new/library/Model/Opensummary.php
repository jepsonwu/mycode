<?php
class Model_Opensummary extends Zend_Db_Table
{
    protected $_name = 'open_summary';
    protected $_primary = 'OpenSummaryID';


    public function getInfo($deviceNo)
    {
    	$select = $this->select();
        $select->from($this->_name)->where('DeviceNo = ?',$deviceNo);
        return $select->query()->fetch();
    }

}