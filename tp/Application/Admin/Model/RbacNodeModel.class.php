<?php

namespace Admin\Model;

use Think\Model;

class RbacNodeModel extends Model {
	//
	protected $tableName = 'rbac_node';
	//
	protected $_validate = array (
			array (
					'name',
					'checkNode',
					'节点已经存在',
					0,
					'callback' 
			) 
	);
	
	/**
	 * 节点验证
	 * @return boolean
	 */
	public function checkNode() {
		//
		$post = I ( 'post.' );
		//
		$map ['name'] = $post ['name'];
		$map ['pid'] = isset ( $post ['pid'] ) ? $post ['pid'] : 0;
		$map ['status'] = 1;
		if (! empty ( $post ['id'] )) {
			$map ['id'] = array (
					'neq',
					$post ['id'] 
			);
		}
		$result = $this->where ( $map )->field ( 'id' )->find ();
		if ($result) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 按菜单类别获取一级菜单列表
	 * 
	 * @author huawr
	 * @param int $type [菜单类别]
	 * @return array
	 */
	public function getMenuByType($type) {
		$condition = array (
				'status' => 1,
				'level' => 2,
				'pid' => 1, // 后台管理
				'type' => $type 
		);
		//
		$list = $this->where ( $condition )->order ( 'sort asc' )->select ();
		//
		return $list;
	}
}

?>