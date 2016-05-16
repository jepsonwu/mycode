<?php
class DM_Model_Table_Tickets_Tickets extends DM_Model_Table {

    protected $_name = 'tickets';
    protected $_primary = 'TicketID';
    private   $_ticketCate = 'tickets_category';

    /*
     *获取一条工单信息
     */
    public function getTicketInfoById($TicketID){
        $TicketID = (int)$TicketID;
    	$sql = "select * from tickets where TicketID = $TicketID and ParentID = 0";
    	$rows = $this->_db->fetchAll($sql);
    	return $rows;

    }



    /* 添加问题
     *
     */
    public function addtTicket1($arr){
    	$arr['TicketTitle'] = str_ireplace('script','',$arr['TicketTitle']);
    	$arr['TicketContent'] = str_ireplace('script','',$arr['TicketContent']);
    	$arr['Status'] = 0;
    	$arr['Type'] = 1;
    	$arr['Addtime'] = date("Y-m-d H:m:s");
    	$res=$this->insert($arr);
    	return $res;
    }

    /* 回答问题
     *
     */
    public function addTicket2($arr){
    	$arr['TicketContent'] = str_ireplace('script','',$arr['TicketContent']);
    	$arr['Status'] = 0;
    	$arr['Type'] = 2;
    	$arr['Addtime'] = date("Y-m-d H:m:s");
    	$num=$this->insert($arr);
    	return $num;     
    }
    
    /*
     * 回答问题后更新状态
     */
    public function updateReply($TikcetID){
        $data=array(
        		"Status" => 1,
                "Updatetime"=>date("Y-m-d H:i:s")
        	);       
        $this->update($data, array('TicketID = ? '=>$TikcetID));
    }
    
    /*
     * 获取答案
     */
    public function getTicketReplies($ticket_id,$pageIndex,$pageSize){
       $select=$this->select();
       $select->from('tickets', '*');
       $select->where('ParentID = ?', $ticket_id);
       $select->order('TicketID desc');
       $select->limitPage($pageIndex,$pageSize);
       $list=$select->query()->fetchAll();
       return $list;
    }

    /*
     * 获取对应工单答案总数
     */
    public function getTicketRepliesTotal($ticket_id){
    	$select=$this->select()->setIntegrityCheck(false);
        $select=$this->select()->from(array('t'=>'tickets'))->where("ParentID = ?",$ticket_id);
        $total_sql = $select->__toString();  
        $total_sql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $total_sql);
        $totalCount = $this->_db->fetchOne($total_sql);
        return $totalCount;   
    }
    
    /*
     * 关闭工单
     */
    public function closeTicket($TicketID){
    	 $data=array(
        		"Status" => 2,
        	);       
        $this->update($data, array('TicketID = ? '=>$TicketID));

    }

    /*
     *  获取全部工单分类
     */
    public function getTicketCate(){
    	$sql="select * from $this->_ticketCate";
    	$arr= $this->_db->fetchAll($sql);
    	return $arr;

    }

    /*
     * 增加分类
     */
    public function addTicketCate($ret){
    	return $this->_db->insert($this->_ticketCate,$ret);
    }

    /*
     * 获取一条分类信息
     */
    public function getOneTicketCate($edit_id){
        $edit_id = (int)$edit_id;
    	$sql = "select * from $this->_ticketCate where CategoryID = $edit_id";
    	$rows = $this->_db->fetchRow($sql);
    	return  $rows;
    }

    /*
     * 更新分类信息
     */
    public function editTicketCate($ret){
    	return $this->_db->update($this->_ticketCate,$ret, array('CategoryID = ? '=>$ret['CategoryID']));
    }

    /*
     * 删除一条分类
     */
    public function deleteTicketCate($id){
        return $this->_db->delete($this->_ticketCate,array('CategoryID = ? '=>$id));
    }
}
