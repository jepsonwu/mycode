<?php
class DM_Model_Table_User_Admin extends DM_Model_Table_User_Abstract
{
	//启用
	const ENABLE_STATUS = 'ENABLE';
	//停用
	const DISABLE_STATUS = 'DISABLE';
	
	protected $_name = 'admins';
	
	protected $_primary = 'AdminID';
		
	/**
	 * 角色列表
	 * @param int $pageIndex
	 * @param int $pageSize
	 * @param int $platform
	 * @return array
	 */
	public function getAdminList($pageIndex,$pageSize)
	{
		return $this->select()->from($this->_name)->order(' AdminID asc ')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
	}
	
	/**
	 * 获取记录总数
	 * @param string $platform
	 * @return int
	 */
	public function getAdminTotal()
	{
		$ret = $this->select()->from($this->_name,'count(1) as totalCount')->query()->fetch();
		return !empty($ret) ? $ret['totalCount'] : 0;
	}
	
	
	/**
	 * 加密密码
	 * @param string $password
	 * @return string
	 */
	public static function encodePassword($password)
	{
		return md5('Duomai'. $password .'2013');
	}
	
	/**
	 * 登录验证adapter
	 * @access public
	 * @param mixed $username
	 * @param string $password
	 * @return Zend_Auth_Adapter_DbTable
	 */
	public function getAuthAdapter($username, $password)
	{
		$authAdapter = new Zend_Auth_Adapter_DbTable($this->getAdapter());
		$authAdapter->setTableName($this->_name);
	
		$authAdapter->setIdentityColumn('Username');
		$authAdapter->setCredentialColumn('Passwd');
		
		$authAdapter->setIdentity($username);
		$authAdapter->setCredential(self::encodePassword($password));
		return $authAdapter;
	}
	
	/**
	 * 更新登录时间
	 * @param int $admin_id
	 */
	public function updateLastLoginTime($admin_id)
	{
		$this->update(array('LastLoginTime'=>date('Y-m-d H:i:s',time())),array('AdminID = ?'=>$admin_id));
	}
	
	/**
	 * 添加管理员
	 * @param string $username
	 * @param string $password
	 */
	public function addAdmin($username,$password,$empno,$telphone)
	{
		$data = array(
				'Username'=>$username,
				'Passwd'=>self::encodePassword($password),
				'Empno'=>$empno,
				'Telphone'=>$telphone
		);
		return $this->insert($data);
	}
	
	/**
	 * 编辑管理员
	 * @param int $admin_id
	 * @param string $username
	 * @param string $password
	 */
	public function editAdmin($admin_id,$data)
	{
		if(isset($data['Passwd']) && !empty($data['Passwd'])){
			$data['Passwd'] = $this->encodePassword($data['Passwd']);
		}
		return $this->update($data,array('AdminID = ?'=>$admin_id));
	}
	
	/**
	 * 删除管理员
	 * @param int $admin_id
	 */
	public function deleteAdmin($admin_id)
	{
		$this->delete(array('AdminID = ? '=>$admin_id));
	}
	
	/**
	 * 停用或启用管理员
	 * @param int $admin_id
	 * @param string $status
	 */
	public function setAdminStatus($admin_id,$status)
	{
		return $this->update(array('Status'=>$status), array('AdminID = ?'=>$admin_id));
	}
	
	/**
	 * 判断用户名是否已经存在
	 * @param string $username
	 */
	public function hasExistsUsername($username,$explict_id = 0)
	{
		$bind = array(
				'Username'=>$username,
				'AdminID'=>$explict_id
		);
		$ret = $this->_db->fetchRow('select 1 from '.$this->_name.' where Username = :Username and AdminID != :AdminID ',$bind);
		return !empty($ret);
	}
	
	/**
	 * 根据admin_id 获取信息
	 * @param int $admin_id
	 */
	public function getAdminInfoByID($admin_id)
	{
		return $this->find($admin_id)->current();
	}
}