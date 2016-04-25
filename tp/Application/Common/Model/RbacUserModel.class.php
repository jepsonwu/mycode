<?php

namespace Common\Model;

use Think\Model;

class RbacUserModel extends Model {
	
	/**
	 * 设置用户缓存
	 */
	public function setUserCache() {
		//
		$users = $this->order('id')->getField('id, nickname, face, status');
		S( 'backend_users', $users, C('ONEDAY') );
    	//
    	return $users;
	}
	
}

?>