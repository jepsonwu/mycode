<?php

namespace Admin\Model;

use Think\Model\ViewModel;

class RbacRoleViewModel extends ViewModel {
	public $viewFields = array (
			'RbacRole' => array (
					'_as' => 'role1',
					'id',
					'name',
					'pid',
					'status',
					'remark',
					'ename',
					'create_time',
					'_type' => 'left' 
			),
			'rbacRole' => array (
					'_as' => 'role2',
					'name' => 'prt_name',
					'_on' => 'role1.pid=role2.id' 
			) 
	);
}

?>