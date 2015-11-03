<?php

namespace Common\Model;

use Think\Model;

class RbacRoleModel extends Model {
	
	/**
	 * 设置角色缓存
	 */
	public function setRoleCache() {
		//
		$roles = $this->order('id')->getField('id, name, pid');
		S( 'roles', $roles, C('ONEDAY') );
    	//
    	return $roles;
	}
	
}

?>