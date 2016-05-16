<?php
/**
 * 角色管理Model
 * @author Mark
 */
class DM_Model_Table_User_Role extends DM_Model_Table
{
	const P_ADMIN = 'ADMIN';
	const P_FRONT = 'FRONT';
	const STATUS_ALLOW = 'ALLOW';
	const STATUS_DENY = 'DENY';

	
	protected $_name = 'acl_roles';
	protected $_primary = 'RoleID';

	private $_rolePrivilegeName = 'acl_role_privileges';
	private $_privilegeName = 'acl_privileges';
	private $_adminRoleName = 'admin_roles';
	
	public function addRole($name,$platform = self::P_ADMIN)
	{
		$data = array(
				'Name'=>$name,
				'Platform'=>$platform
		);
		return $this->insert($data);
	}
	
	/**
	 * 角色列表
	 * @param int $pageIndex
	 * @param int $pageSize
	 * @param int $platform
	 * @return array
	 */
	public function getRoleList($pageIndex,$pageSize,$platform = self::P_FRONT)
	{
		return $this->select()->from($this->_name)->where('Platform = ? ',$platform)->order(' RoleID desc ')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
	}
	
	/**
	 * 获取记录总数
	 * @param string $platform
	 * @return int
	 */
	public function getRoleTotal($platform = self::P_FRONT)
	{
		$ret = $this->select()->from($this->_name,'count(1) as totalCount')->where('Platform=?',$platform)->query()->fetch();
		return !empty($ret) ? $ret['totalCount'] : 0;
	}
	
	/**
	 * 删除指定的角色
	 * @param int $role_id
	 */
	public function deleteRoleByID($role_id)
	{
		$this->delete(array('RoleID = ?'=>$role_id));
		$this->removeRolePrivilegesRelations($role_id);
	}
	
	/**
	 * 给角色分配权限
	 * @param int $role_id
	 * @param int $privilge_id
	 * @param string $flag
	 */
	public function grandRolePrivileges($role_id,$privilge_id,$allow)
	{
		$data = array(
				'RoleID'=>$role_id,
				'PrivilegeID'=>$privilge_id,
				'Status'=>$allow
		);
		
		$info = $this->_db->fetchRow('select * from '.$this->_rolePrivilegeName.' where RoleID = :RoleID and PrivilegeID = :PrivilegeID',array('RoleID'=>$role_id,'PrivilegeID'=>$privilge_id));
		if(!empty($info)){
			if($allow != $info['Status']){
				$this->_db->update($this->_rolePrivilegeName, array('Status'=>$allow),array('RoleID = ?'=>$role_id,'PrivilegeID = ?'=>$privilge_id));
			}
		}else{
			$this->_db->insert($this->_rolePrivilegeName,$data);
		}
	}
	
	/**
	 * 获取角色-分配权限列表
	 */
	public function getRolePrivilegesList($role_id,$platform)
	{
		$sql = 'select p.PrivilegeID,p.Describe,ifnull(rp.Status,"") as Status, p.MainSign  from '.$this->_privilegeName.' as p 
				left join '.$this->_rolePrivilegeName.' as rp on rp.PrivilegeID = p.PrivilegeID and rp.RoleID = :RoleID 
				where p.Platform = :Platform order by p.PrivilegeID desc ';
		return $this->_db->fetchAll($sql,array('RoleID'=>$role_id,'Platform'=>$platform));
	}
	
	/**
	 * 获取用户-分配角色列表
	 * @param int $role_id
	 * @param int $user_id
	 * @param string $platform
	 */
	public function getUserRolesList($user_id,$platform)
	{
		if(self::P_ADMIN == $platform){
			$sql = 'select r.RoleID,r.Name,ifnull(ur.AdminID,0) as urState from '.$this->_name.' as r 
					left join '.$this->_adminRoleName.' as ur on ur.RoleID = r.RoleID and ur.AdminID = :UserID 
					where r.Platform = :Platform order by r.RoleID desc ';
			return $this->_db->fetchAll($sql,array('UserID'=>$user_id,'Platform'=>$platform));
			
		}
		return array();
	}
	
	/**
	 * 给用户分配角色
	 * @param int $user_id
	 * @param int $role_id
	 * @param string $platform
	 */
	public function grantUserRole($user_id,$role_id,$platform)
	{
		if(self::P_ADMIN == $platform){
			$info = $this->_db->fetchRow('select 1 from '.$this->_adminRoleName.' where AdminID = :UserID and RoleID = :RoleID',array('UserID'=>$user_id,'RoleID'=>$role_id));
			if(empty($info)){
				$this->_db->insert($this->_adminRoleName, array('AdminID'=>$user_id,'RoleID'=>$role_id));
			}
		}
	}
	
	/**
	 * 移除用户角色
	 * @param int $user_id
	 * @param array $notDeleteIDs
	 * @param string $platform
	 */
	public function stripUserRoles($user_id,$notDeleteIds,$platform)
	{		
		if(self::P_ADMIN == $platform){
			if(empty($notDeleteIds)){
				$this->_db->delete($this->_adminRoleName,'AdminID = '.$this->_db->quote($user_id));	
			}else{
				$this->_db->delete($this->_adminRoleName,'AdminID = '.$this->_db->quote($user_id).' and RoleID not in ('.implode(',', $notDeleteIds).')');
			}
		}
	}
	
	/**
	 * 根据ID 查询role 信息
	 * @param int $role_id
	 */
	public function getRoleByID($role_id)
	{
		return $this->find($role_id)->current();
	}
	
	/**
	 * 
	 * @param int $role_id
	 * @param string $name
	 */
	public function updateRole($role_id,$name)
	{
		$data = array('Name'=>$name);
		return $this->update($data, array('RoleID = ?'=>$role_id));
	}
	
	/**
	 * 移除多余数据
	 * @param int $role_id
	 * @param array $notDeleteIds
	 */
	public function stripRolePrivileges($role_id,$notDeleteIds)
	{
		if(empty($notDeleteIds)){
			return $this->_db->delete($this->_rolePrivilegeName,' RoleID = '.$this->_db->quote($role_id));
		}else{
			return $this->_db->delete($this->_rolePrivilegeName,' RoleID = '.$this->_db->quote($role_id).' and PrivilegeID not in('.implode(',', $notDeleteIds).')');
		}
	}
	
	/**
	 * 判断指定的权限是否已分配了
	 * @param int $privilege_id
	 */
	public function isGrantToRole($privilege_id)
	{
		$ret = $this->_db->fetchRow('select 1 from '.$this->_rolePrivilegeName.' where PrivilegeID = :PrivilegeID',array('PrivilegeID'=>$privilege_id));
		return !empty($ret);
	}
	
	/**
	 * 判断指定的角色是否已经被分配
	 * @param int $role_id
	 */
	public function isGrantToUser($role_id,$platform)
	{	
		$tableName = self::P_ADMIN == $platform ? $this->_adminRoleName : $this->_userRoleName;	
		$ret = $this->_db->fetchRow('select 1 from '.$tableName.' where RoleID = :RoleID',array('RoleID'=>$role_id));
		return !empty($ret);
	}
	
	/**
	 * 删除角色与权限的对应关系
	 * @param int $role_id
	 */
	private function removeRolePrivilegesRelations($role_id)
	{
		$this->_db->delete($this->_rolePrivilegeName,array('RoleID = ? '=>$role_id));
	}
	
	/**
	 * 根据角色ID 获取
	 * @param array $role_id
	 */
	public function getRolePrivliegesByRoleIDs($role_id_array)
	{
		$sql = 'select p.MainSign,p.SubSign,rp.Status  from '.$this->_rolePrivilegeName.' as rp 
				inner join '.$this->_privilegeName.' as p on p.PrivilegeID = rp.PrivilegeID 
				where rp.RoleID IN('.implode(',', $role_id_array).')';
		$result = $this->_db->fetchAll($sql);
		
		$privileges = array('Allow'=>array(),'Deny'=>array());
		
		if(!empty($result)){
			foreach($result as $item){
				if(self::STATUS_ALLOW == $item['Status']){
					unset($item['Status']);
					$privileges['allow'][] = $item;
				}elseif(self::STATUS_DENY == $item['Status']){
					unset($item['Status']);
					$privileges['deny'][] = $item;
				}
			}
		}
		return $privileges;
	}

	/**
	 * 根据用户id 获取其分配的角色
	 * @param int $uid
	 * @param string $platform
	 */
	public function getUserRolesArraybyUid($uid,$platform)
	{
		$res = array();
		if(self::P_ADMIN == $platform){
			$res = $this->_db->fetchAll('select RoleID from '.$this->_adminRoleName.' where AdminID = :AdminID',array('AdminID'=>$uid));
		}
		
		$des = array();
		if(!empty($res)){
			foreach($res as $item){
				$des[] = $item['RoleID'];
			}
		}
		return $des;
	}
	
	/**
	 * 删除管理员 -- 角色  对应关系
	 * @param int $admin_id
	 */
	public function deleteAdminRoleRelations($admin_id)
	{
		$this->_db->delete($this->_adminRoleName,array('AdminID = ?'=>$admin_id));
	}
	
	/**
	 * 获取前端API角色-分配权限列表
	 */
	public function getApiRolePrivilegesList($role_id)
	{
	    $sql = 'select p.Describe,ifnull(rp.Status,"ALLOW") as Status, MainSign, SubSign  from '.$this->_privilegeName.' as p
				left join '.$this->_rolePrivilegeName.' as rp on rp.PrivilegeID = p.PrivilegeID and rp.RoleID = :RoleID
				where p.Platform = :Platform order by p.PrivilegeID desc ';
	    return $this->_db->fetchAll($sql,array('RoleID'=>$role_id,'Platform'=>self::P_FRONT));
	}
}