<?php
/**
 * 二维码
 * @author Mark
 *
 */
class DM_Model_Account_QrCodes extends Zend_Db_Table
{
	protected $_name = 'qrcodes';
	protected $_primary = 'CodeID';
	
    /**
     * 初始化数据库
     */
	public function __construct()
	{
		$udb = DM_Controller_Front::getInstance()->getDb('udb');
		$this->_setAdapter($udb);
	}
	
	/**
	 *  根据codeStr 获取信息
	 * @param string $codeStr
	 */
	public function getInfoByCode($codeStr,$where = array())
	{
		$select = $this->select();
		if(!empty($where)){
			if(isset($where['MemberID'])){
				$select->where('MemberID = ?',$where['MemberID']);
			}
		}
		$row = $select->where('CodeStr = ?',$codeStr)->query()->fetch();
		return $row;
	}
	
	/**
	 * 生成code
	 */
	public function generateCode()
	{
		$code = str_replace('.','',uniqid(null,true)).mt_rand(10000000,99999999);
		$ValidEndSconds = time() + 2 * 60;
		$this->insert(array('CodeStr'=>$code,'ValidEndSconds'=>$ValidEndSconds));
		return $code;
	}
}