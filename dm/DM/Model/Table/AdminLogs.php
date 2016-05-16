<?php
/**
 * 管理员操作日志
 *
 */
class DM_Model_Table_AdminLogs extends Zend_Db_Table {

    protected $_name = 'admin_logs';
    protected $_primary = 'LogID';
    
    /*
     * 记录日志
     */
    public function addLog($admin_id,$member_id,$table,$field,$content,$ip=''){
    	if(!$admin_id || !$member_id || !$table || !$field || !$content){
    		return false;
    	}

    	$data = array(
    			'AdminID'=>$admin_id,
    			'MemberID'=>$member_id,
    			'ActionTable'=>$table,
    			'ActionField'=>$field,
    			'Content'=>$content,
    			'Addtime'=>date('Y-m-d H:i:s'),
    	);
    	if ($ip){
    	    $data['UpdateIp']=$ip;
    	}
    	$return = $this->insert($data);
    	return $return?true:false;
    }


}
