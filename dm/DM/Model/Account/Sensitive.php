<?php
/**
 * 账本
 * 
 * @author Kitty
 * @since 2015/03/05
 */
class DM_Model_Account_Sensitive extends Zend_Db_Table
{
	protected $_name = 'sensitive';
	protected $_primary = 'SensitiveID';



    /**
     * 初始化数据库
     */
	public function __construct()
	{
		$udb = DM_Controller_Front::getInstance()->getDb('udb');
		$this->_setAdapter($udb);
	}
    /**
     * [add description]
     * @param [type] $ret [description]
     */
    public function add($name)
    {
    	return $this->_db->insert($this->_name,array('SensitiveName'=>$name));
    } 	

	/**
	 * 获取类型信息
	 * @param int $memberID
	 * @param int $microTime
	 */
	public function getInfo($name)
	{
	    $select = $this->select();
	    $select->from($this->_name)->where('SensitiveName = ?',$name);
	    return $select->query()->fetch();
	}

	/**
	 * 过滤敏感词汇
	 * @param int $memberID
	 * @param int $microTime
	 */
	public function filter($username)
	{
		$filter = 0;
	    if (strrpos($username, '小麦金融')!==false || strrpos($username, '匿名') !==false || strrpos($username, '财猪') !==false || strrpos($username, '财主')!==false || strrpos($username, '米贷')!==false || strrpos($username, '多麦')!==false || mb_substr($username,0,2,'UTF-8')=='我是' || mb_substr($username, 0,2,'UTF-8')=='今日') {
            $filter = 1;
        }
	    return $filter;
	}


}
