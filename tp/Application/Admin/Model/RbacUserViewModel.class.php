<?php

namespace Admin\Model;

use Think\Model\ViewModel;

class RbacUserViewModel extends ViewModel {
	//
	public $viewFields = array (
			'rbacUser' => array (
					'id',
					'account',
					'nickname',
					'last_login_time',
					'login_count',
					'face',
					'mobile',
					'qq',
					'status',
					'_type' => 'LEFT' 
			),
			'rbacRoleUser' => array (
					'role_id',
					'_on' => 'rbacUser.id=rbacRoleUser.user_id',
					'_type' => 'LEFT' 
			),
			'rbacRole' => array (
					'name' => 'role_name',
					'_on' => 'rbacRoleUser.role_id=rbacRole.id' 
			) 
	);
}

?>