<?php
/**
 * 用户表对象基类
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Model_Table_Members extends DM_Model_Table
{
    protected $_rowClass="DM_Model_Row_Member";
	protected $_name = 'members';
	protected $_primary = 'MemberID';

	/**
	 * 获取单条会员数据
	 * 
	 * @param int $id
	 * @return DM_Model_Row_Member
	 */
	public function getById($id)
	{
		return parent::getByPrimaryId($id);
	}

	/**
	 * 根据email获取用户信息
	 * 
	 * @return DM_Model_Row_Member
	 */
	public function getByEmail($email)
	{
	    if(!$email){
	        return NULL;
	    }
		$res = $this->fetchRow($this->select()->where('Email =?',$email));
		return $res;
	}
	
	/**
	 * 根据MOBILE获取用户信息
	 * 
	 * @return DM_Model_Row_Member
	 */
	public function getByMobile($mobile, $isVerify=DM_Model_Row_Member::STATUS_MOBILE_VERIFIED)
	{
		if(!$mobile){
			return NULL;
		}
		$res = $this->_db->fetchRow("select  * from ".$this->_name." where MobileNumber = :MobileNumber and MobileVerifyStatus=:MobileVerifyStatus", array('MobileNumber'=>$mobile, 'MobileVerifyStatus'=>$isVerify));
		return $this->createRowObject($res);
	}

	
	/**
	* 设置新密码
    * @param int $member_id
	*/
	public function updatePassword($member_id,$password)
	{
	    $password=$this->encodePassword($password);
	    //检查是否为原密码
	    $select = $this->select();
	    $select->from($this->_name, array('count(*) as num'))
	    ->where("MemberID = ?", $member_id)
	    ->where("Password = ?", $password);
	    $num =  $this->_db->fetchOne($select);
	    if($num){
	      	return $num;
    	}else{
	    	$res = $this->update(array('Password'=>$password),array('MemberID = ?'=>$member_id));
	      	return $res;
	    }
	}
	
	/**
	* 设置支付密码
	* @param int $member_id
	*/
	public function updateTradePassword($member_id,$password)
	{
		$password=$this->encodePassword($password);
		//检查密码是否正确
		$select = $this->select();
		$select->from($this->_name, array('count(*) as num'))
		->where("MemberID = ?", $member_id)
		->where("Password = ?", $password);
		$num =  $this->_db->fetchOne($select);
		if($num){
			return true;
        }
		$res = $this->update(array('RefundPassword'=>$password),array('MemberID = ?'=>$member_id));
			return $res;
	}
	
	/**
	 * 认证邮箱
	 */
	public function validatedEmail($id)
	{
		return $this->update(array('EmailVerifyStatus'=>'Verified'),array('MemberID = ?'=>$id));
	}
	
	/**
	*验证用户手机
	*/
	public function verifyMobile($member_id,$mobile = '')
	{
		$data = array(
		        'MobileVerifyStatus'=>'Verified',
		        'MobileNumber'=>$mobile
		);
	    return $this->update($data,array('MemberID = ?'=>$member_id));
	}

	
	/**
	* 检测邮箱格式
	*/
	private function checkmail($email)
	{
		if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$email)){
			list($username,$domain)=@split('@',$email);
			if(!checkdnsrr($domain,'MX')) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	* 检测密码格式
	*/
	public function verifyPassword($password){
		if(preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/', $password, $pass)){
			return true;
		}
		return false;
	}
	
	/**
	* 验证用户支付密码是否正确
	*/
	public function verifyRefundPassword($member_id = 0, $password = ''){
		if(!$member_id || !$password){
			return false;
		}
		$info = $this->fetchRow(array('MemberID=?'=>$member_id));
	
		if($info['refund_password'] == ''){
			return false;
		}
	
		if($info['refund_password'] != $this->encodePassword($password)){
            return false;
		}
		return true;
    }
    
	/**
	* 获取用户ip地区信息
	*/
	public function getAreaByIp($ip){
		if(!$ip){
			return false;
		}
		
		try{
			$result = @file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip);
		} catch (Exception $e){
			return false;
		}
	
		if($result){
			$data = json_decode($result, true);
		}
	
		return $data;
	}

    /**
     * 检查同一IP注册次数
     */
	public function getIpNum($ip)
	{
	    $ip = $this->_db->quote($ip);
		$sql = "select count(*) from members where LastLoginIp = ".$ip;
		return $this->_db->fetchOne($sql);
	}
	
}
