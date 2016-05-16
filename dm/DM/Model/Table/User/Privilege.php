<?php
/**
 * 权限管理
 * 
 * @author Mark
 *
 */
class DM_Model_Table_User_Privilege extends DM_Model_Table
{
	protected $_name = 'acl_privileges';
	protected $_primary = 'PrivilegeID';
	
	/**
	 * 权限列表
	 * @param int $pageIndex
	 * @param int $pageSize
	 * @param string $plateform
	 * @return array
	 */
	public function getPrivilegeList($pageIndex,$pageSize,$platform='ADMIN')
	{
		$select = $this->select()->from($this->_name)->where('Platform = ? ',$platform)->order(' PrivilegeID desc ');
		if($pageIndex > 0 && $pageSize >0){
			$select->limitPage($pageIndex, $pageSize);
		}
		return $select->query()->fetchAll();
	}
	
	
	/**
	 * 查询记录总条数
	 * @return int
	 */
	public function getPrivilegeTotal($platform='ADMIN')
	{
		$ret = $this->select()->from($this->_name,'count(1) as totalCount')->where('Platform=?',$platform)->query()->fetch();
		return !empty($ret) ? $ret['totalCount'] : 0;
	}
	
	/**
	 * 添加
	 * @param string $describe
	 * @param string $main_sign
	 * @param string $sub_sign
	 * @param string $platform
	 */
	public function addPrivilege($describe,$main_sign,$sub_sign,$platform='ADMIN')
	{
		$data = array(
				'Describe'=>$describe,
				'Platform'=>$platform,
				'MainSign'=>$main_sign,
				'SubSign'=>$sub_sign
		);
		return $this->insert($data);
	}
	
	/**
	 * 删除
	 * @param int $privilege_id
	 */
	public function deletePrivilegeByID($privilege_id)
	{
		return $this->delete(array('PrivilegeID = ? '=>$privilege_id));
	}
	
	/**
	 * 修改
	 * @param int $privilege_id
	 * @param string $describe
	 * @param string $main_sign
	 * @param string $sub_sign
	 */
	public function updatePrivileges($privilege_id,$describe,$main_sign,$sub_sign)
	{
		$data = array(
				
				'Describe'=>$describe,
				'MainSign'=>$main_sign,
				'SubSign'=>$sub_sign
		);
		
		return $this->update($data, array('PrivilegeID = ? '=>$privilege_id));
	}
	
	/**
	 * 根据ID查询指定一条记录
	 * @param int $privilege_id
	 * @return obj
	 */
	public function getPrivilegeById($privilege_id)
	{
		return $this->find($privilege_id)->current();
	}
	
	/**
	 * 判断标识是否已经存在
	 * @param string $main_sign
	 * @param string $sub_sign
	 * @param string $platform
	 */
	public function checkHasExits($main_sign,$sub_sign,$platform,$explict_id = 0)
	{
		$bind = array(
				'Platform'=>$platform,
				'MainSign'=>$main_sign,
				'SubSign'=>$sub_sign,
				'PrivilegeID'=>$explict_id
		);
		$ret = $this->_db->fetchRow('select 1 from '.$this->_name.' where Platform = :Platform and MainSign = :MainSign and SubSign = :SubSign and PrivilegeID != :PrivilegeID',$bind);
		return !empty($ret);
	}
}