<?php

namespace Admin\Model;
use Think\Model\ViewModel;

class UserRoleViewModel extends ViewModel {
	public $viewFields = array(
		'RbacUser' => array('id','account', 'nickname', '_type'=>'LEFT'),						
		'RbacRoleUser' => array('role_id', 'user_id','_on'=>'RbacUser.id=RbacRoleUser.user_id' )							
	);
}

?>
