<?php
/**
 * 理财号授权运营者
 * @author kitty
 * @since 2016-05-05
 */
class DM_Model_Account_MemberGranted extends DM_Model_Table
{
	protected $_name = 'member_granted';
	protected $_primary = 'GrantedID';

	/**
	 * 初始化数据库
	 */
	public function __construct()
	{
		$udb = DM_Controller_Front::getInstance()->getDb('udb');
		$this->_setAdapter($udb);
	}

	/**
	 * 获取授权给$id的用户列表
	 * @param int $id 被授权的用户id
	 */
	public function getGrantedList($id,$status=null)
	{
		$select=$this->select()->from($this->_name, 'MemberID')->where('GrantToMemberID =?', $id);
		if(!is_null($status)){
			$select->where('IsConfirm = ?',$status);
		}
		$list = $select->query()->fetchAll();
        return count($list)>0?$list:array();
	}

	/**
	 * 获取授权信息
	 * @param int $memberID 授权的用户id
	 * @param int $toMemberID 被授权的用户id
	 */
	public function getGrantedInfo($memberID,$toMemberID)
	{
		$info=$this->select()->where('MemberID = ?',$memberID)->where('GrantToMemberID = ?', $toMemberID)->query()->fetch();
        return !empty($info)?$info:array();
	}


	
	/**
	 *  根据codeStr 获取信息
	 * @param string $codeStr
	 */
	public function getInfoByCode($code,$where = array())
	{
		$select = $this->select();
		if(!empty($where)){
			if(isset($where['MemberID'])){
				$select->where('MemberID = ?',$where['MemberID']);
			}
		}
		$row = $select->where('GrantCode = ?',$code)->query()->fetch();
		return $row;
	}
	
	/**
	 * 生成code
	 */
	public function generateCode($memberID,$toMemberID)
	{
		$code = str_replace('.','',uniqid(null,true)).mt_rand(10000000,99999999);
		$expTime = time() + 2 * 60;
		$info = $this->getGrantedInfo($memberID,$toMemberID);
		if(!empty($info)){
			$this->update(array('GrantCode'=>$code,'ExpirationTime'=>$expTime),array('GrantedID = ?'=>$info['GrantedID']));
		}else{
			$this->insert(array('MemberID'=>$memberID,'GrantToMemberID'=>$toMemberID,'GrantCode'=>$code,'ExpirationTime'=>$expTime));
		}
		return $code;
	}
	
}
