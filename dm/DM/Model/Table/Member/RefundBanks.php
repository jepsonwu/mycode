<?php
class DM_Model_Table_Member_RefundBanks extends DM_Model_Table {
	protected $_name = 'member_refund_banks';
	protected $_primary = 'BankId';
	
	public function addaccount($data){
		$res = self::valicatebank($data);
		if($res==true){
		    return $this->insert($data);
		}else{
			return -1;
		}
	
	}

    public function deleteBanck($id,$memberId){
        $where = $this->_db->quoteInto("BankId = ?",$id);
        $where .= " and ".$this->_db->quoteInto("MemberId = ?",$memberId);
        return $this->delete($where);
    }
	
	public function getAccountInfo($MemberId){
		$res =  $this->_db->fetchAll("select BankId,Username,MemberId,BankName,City,AccountNum,Date from ". $this->_name." where MemberId = :mid",array('mid'=>$MemberId));
		return $res;
	}
	
	public function deleteinfo($id){
		return $this->_db->delete($this->_name,array('BankId = ? '=>$id));
	}
	
	public function getBankInfo($bankId){
		$select = $this->select()
		          ->from( $this->_name)
		          ->where('BankId = ?', $bankId)
		          ->query()
		          ->fetchAll();
		return $select;
		
	}

	public function updateinfo($arr){
		$res = $this->update($arr, array("BankId = ?"=>$arr["BankId"]));
		if($res > 0){
			return $res;			
		}else{
			return -1;
		}		
	}
    
    public function valicatebank($data){
       if(preg_match("/^[0-9]*$/", $data["AccountNum"])){
			return true;
		}else{
			return false;
		}
    } 
}