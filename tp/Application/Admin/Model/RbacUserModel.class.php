<?php

namespace Admin\Model;

use Think\Model;

class RbacUserModel extends Model {
	//
	protected $tableName = 'rbac_user';
	//
	protected $_validate = array (
			array (
					'password',
					'require',
					'密码必须' 
			),
			array (
					'nickname',
					'require',
					'昵称必须' 
			),
			array (
					'repassword',
					'require',
					'确认密码必须' 
			),
			array (
					'account',
					'',
					'帐号已经存在',
					self::EXISTS_VALIDATE,
					'unique',
					self::MODEL_INSERT 
			) 
	);
	//
	protected $_auto = array (
			array (
					'password',
					'pwd_hash',
					self::MODEL_INSERT,
					'function' 
			),
			array (
					'create_time',
					'time',
					self::MODEL_INSERT,
					'function' 
			),
			array (
					'update_time',
					'time',
					self::MODEL_BOTH,
					'function' 
			) 
	);

}

?>