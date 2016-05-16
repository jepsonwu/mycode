<?php
/**
 * 邀请码
 * 
 * @author Mark
 *
 */
class DM_Model_Table_InviteCodes extends DM_Model_Table
{
	protected $_name= 'invite_codes';
	protected $_primary = 'InviteCodeID';


    public function getPageList($search,$order)
   {
       $select = $this->_db->select();
       $select->from($this->_name)
              ->where("MemberID = ?", $search['MemberID']);
       if($order){
           $select->order($order);
       }else{
           $select->order('create_date desc');
       }

       return $select;
   }

	/**
	 * 生成随机码
	 * @param int $length
	 * @return string
	 */
	private function getCode($length)
	{
		return DM_Helper_Utility::createHash($length);
	}
	
	/**
	 * 生成可用验证码
	 * 
	 * @param int $length
	 */
	public function generateValideCode($length = 6)
	{
		$maxCheck = 6;
		$code = '';
		for($i = 1;$i<=$maxCheck;$i++){
			$code = $this->getCode($length);
			if(!$this->hasExistsCode($code)){
				break;
			}
		}
		return $code;
	}
	
	/**
	 * 判断指定验证码是否存在(True 存在；False 不存在)
	 * 
	 * @param string $code
	 */
	public function hasExistsCode($code,$returnData = false)
	{
		$ret = $this->select()->from($this->_name)->where('InviteCode = ?',$code)->query()->fetch();
		if(false == $returnData){
		  return !empty($ret);
		}
		return $ret;
	}
	
	/**
	 * 验证邀请码是否有效 返回行对象信息
	 *
	 * @param string $code
	 */
	public function isValidCode($code)
	{
        $select = $this->select();
		$select->where("InviteCode = ?",$code);
		$select->where("IsUsable = '1'");
        $select->where("ExpiryTime = '0000-00-00 00:00:00' || ExpiryTime > '".DM_Helper_Utility::getDateTime()."'");
		return $this->fetchRow($select);
	}
	/**
	 * 注册成功更新验证码状态
	 *
	 * @param object $code
	 * @param int $memberId 使用者
	 */
	public function UpdateCode($code, $memberId)
	{
		return  $this->update(array('IsUsable'=>0, 'UseMid'=>$memberId),array('InviteCode = ?'=>$code->InviteCode));
	}
	/**
	 * 插入验证码
	 * @param int $MemberID
	 * @param string $InviteCode
	 * @param date $expiry_date
	 */
	public function addInviteCode($MemberID,$InviteCode,$ExpiryDate)
	{
		$data = array(
						'MemberID'		=>	$MemberID,
						'InviteCode'	=>	$InviteCode,
						'IsUsable'		=>	1,
						'ExpiryTime'	=>	$ExpiryDate,
						'CreateTime'	=>	DM_Helper_Utility::getDateTime()	
			);
		return $this->insert($data);
	}
	
	/**
	 * 获取一个用户的邀请码数量
	 *
	 * @param string $member
	 */
	public function codeCount($member, $usable=NULL)
	{
	    $select = $this->select()->from($this->_name, 'count(*)')->where('MemberID = ?',$member);
	    if ($usable!==NULL){
	        $select->where('IsUsable = ?',(bool)$usable);
	    }
	    return $select->query()->fetchColumn();
	}
}