<?php

namespace Admin\Controller;

use Think\Exception;

use Admin\Model\RbacRoleViewModel;

use Admin\Controller\CommonController;

class RbacRoleController extends CommonController {
	// 查询前
	protected function _filter(&$map, &$param) {
		//
		$rqst = I('request.');
		//
		$this->_com_filter('name', $rqst ['name'], 1, $map, $param);
		//
		$this->_com_filter('status', $rqst ['status'], 2, $map, $param);
		//
		$this->_com_filter('remark', $rqst ['remark'], 1, $map, $param);
		//
		if ( ! in_array(1, $_SESSION ['bkgd'] ['roles']) ) {
			$map ['id'] = array ( 'gt', 1 );
		}
	}
	
	// 查询后
	protected function _processer(&$volist) {
		//
		$stt_jyqy = C ( 'STT_JYQY' );
		//
		foreach ( $volist as &$value ) {
			$value ['status_show'] = get_status ( $value ['status'], '已' . $stt_jyqy [$value ['status']] );
			$value ['stt_val'] = - $value ['status'];
			$value ['stt_act'] = $stt_jyqy [$value ['stt_val']];
			//
			if (empty ( $value ['prt_name'] )) {
				$value ['prt_name'] = '无';
			}
		}
	}
	
	// 列表
	public function index() {
		//
		$this->assign ( 'stt_jyqy', C ( 'STT_JYQY' ) );
		// 列表过滤器，生成查询Map对象
		$map = array ();
		$param = array ();
		//
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map, $param );
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = new RbacRoleViewModel();
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'role1.id', 'id', true );
// 			echo $model->getLastSql();
		}
		//
		$this->display();
	}
	
	// 新增前
	public function _before_add() {
		//
		$this->assign ( 'stt_jyqy', C ( 'STT_JYQY' ) );
		//
		$model = D ( CONTROLLER_NAME );
		$list = $model->where ( 'pid=0' )->select ();
		$this->assign ( "root_roles", $list );
	}
	
	// 编辑前
	public function _before_edit() {
		//
		$this->assign ( 'stt_jyqy', C ( 'STT_JYQY' ) );
		//
		$model = D ( CONTROLLER_NAME );
		$list = $model->where ( 'pid=0' )->select ();
		$this->assign ( "root_roles", $list );
	}
	
	// 权限列表
	public function accredit() {
		//
		$id = I('request.id');
		// 读取系统组列表
		$group = D ( 'RbacRole' );
		$list = $group->field ( 'id,name' )->where ( 'id>1' )->select ();
		$this->assign ( "groupList", $list );
		//
		$this->assign ( 'role_id', $id );
		
		// 读取系统的项目列表
		$node = D ( "RbacNode" );
		$access = D ( "RbacAccess" );
		$list_node = $node->where ( 'level=1' )->field ( 'id,title' )->order ( 'type,sort' )->select ();
		$list_access = $access->field ( 'node_id' )->where ( 'level=1 and role_id=' . $id )->select ();
		$ary_access = array ();
		foreach ( $list_access as $value ) {
			array_push ( $ary_access, $value ['node_id'] );
		}
		foreach ( $list_node as $key => $value ) {
			if (in_array ( $value ['id'], $ary_access )) {
				$list_node [$key] ['access'] = 1;
			}
		}
		$this->assign ( 'appList', $list_node );
		//
		$this->display();
	}
	
	// 按上级节点取节点
	public function getNodesByPid() {
		//
		$rqst = I('request.');
		//
		$node = D ( "RbacNode" );
		$access = D ( "RbacAccess" );
		$list_node = $node->where ( 'id>3 and pid=' . $rqst ['pid'] )->field ( 'id,title' )->order ( 'type,sort' )->select ();
		$list_access = $access->field ( 'node_id' )->where ( 'pid=' . $rqst ['pid'] . ' and role_id=' . $rqst ['role_id'] )->select ();
		$ary_access = array ();
		foreach ( $list_access as $value ) {
			array_push ( $ary_access, $value ['node_id'] );
		}
		foreach ( $list_node as $key => $value ) {
			if (in_array ( $value ['id'], $ary_access )) {
				$list_node [$key] ['access'] = 1;
			}
		}
		$this->ajaxReturn ( $list_node, 'JSON' );
	}
	
	// 保存角色权限
	public function saveAccess() {
		//
		$post = I('post.');
		//
		$data = array ();
		$data ['role_id'] = $post ['role_id'];
		if (empty ( $data ['role_id'] )) {
			$this->ajaxReturn ( make_rtn('角色丢失！') );
		}
		$data ['level'] = $post ['level'];
		if (empty ( $data ['level'] )) {
			$this->ajaxReturn ( make_rtn('层级丢失！') );
		}
		$data ['pid'] = $post ['pid'];
		if ($data ['pid'] == '') {
			$this->ajaxReturn ( make_rtn('父节点丢失！') );
		}
		//
		$model = D ( "RbacAccess" );
		if (false === $model->where ( $data )->delete ()) {
			$this->ajaxReturn ( make_rtn('删除原角色权限失败！') );
		}
		//
		$ary_nodes = explode ( '|', $post ['node_ids'] );
		//
		try {
			foreach ( $ary_nodes as $value ) {
				$data ['node_id'] = $value;
				if (false === $model->add ( $data )) {
					E('保存角色权限失败!');
				}
			}
		}
		catch ( Exception $e ) {
			$this->ajaxReturn ( make_rtn ( $e->getMessage() ) );
		}
		//
		$data ['info'] = '保存角色权限成功!';
		$data ['status'] = true;
		if ($data ['level'] < 3) {
			$data ['url'] = __CONTROLLER__ . '/accredit/id/' .  $data ['role_id'];
		}
		$this->ajaxReturn ( $data );
	}
	
	// 权限等同于（权限拷贝）
	public function theSameAs() {
		//
		$post = I('post.');
		//
		$mdl_access = D("RbacAccess");
		//
		$mdl_access->startTrans();
		//
		try {
			if ( false === $mdl_access->where( 'role_id='.$post['to'] )->delete() ) {
				E ( '删除原权限失败！' );
			}
			//
			$list = $mdl_access->where( 'role_id='.$post['from'] )->select();
			foreach ($list as $key => $value) {
				$value['role_id'] = $post['to'];
				if ( false === $mdl_access->add( $value ) ) {
					E ( '添加新权限失败！' );
				}
			}
		} catch (Exception $e) {
			$mdl_access->rollback();
			$this->ajaxReturn(make_rtn($e->getMessage()));
		}
		//
		$mdl_access->commit();
		$this->ajaxReturn(make_rtn('权限拷贝成功！', true));
	}
	
	// 用户列表
	public function userList() {
		//
		$id = I('request.id');
		// 读取系统组列表
		$list_role = get_roles();
		if ( ! in_array( 1, $_SESSION ['bkgd'] ['roles'] ) ) {
			unset( $list_role[1] );
			//
			if ( ! in_array( 2, $_SESSION ['bkgd'] ['roles'] ) ) {
				unset( $list_role[2] );
			}
		}
		$this->assign ( "list_role", $list_role );
		//
		$this->assign ( 'role_id', $id );
		//
		if (count ( $list_role ) > 0) {
			$mdl_role_user = D ( "RbacRoleUser" );
			$users_now = $mdl_role_user->where( 'role_id=' . $id )->getField( 'user_id', true );
			//
			$list_user = get_users();
			if ( ! in_array(1, $_SESSION ['bkgd'] ['roles']) ) {
				if ( in_array(2, $_SESSION ['bkgd'] ['roles']) ) { // 管理员
					foreach ($list_user as $key => $value) {
						if ( $mdl_role_user->where( 'role_id=1 and user_id=' . $value )->find() ) {
							if ( $value != $_SESSION [C ( 'USER_AUTH_KEY' )] ) {
								unset( $list_user[$key] );
							}
						}
					}
				}
				else { // 仅显示本部门人员
					foreach ($list_user as $key => $value) {
						if ( $value['department'] != $_SESSION ['bkgd'] ['department'] ) {
							unset( $list_user[$key] );
						}
					}
				}
			}
			// 已选中用户
			foreach ( $list_user as $key => $value ) {
				if (in_array ( $value ['id'], $users_now )) {
					$list_user [$key] ['inlist'] = 1;
				}
			}
			$this->assign ( 'list_user', $list_user );
		}
		//
		$this->display();
	}
	
	// 保存用户列表
	public function saveUserList() {
		//
		$post = I('post.');
		//
		$data = array ();
		$data ['role_id'] = $post ['role_id'];
		if (empty ( $data ['role_id'] )) {
			$this->ajaxReturn ( make_rtn('角色丢失！') );
		}
		//
		$model = D ( "RbacRoleUser" );
		if (false === $model->where ( $data )->delete ()) {
			$this->ajaxReturn ( make_rtn('删除原角色用户列表失败！') );
		}
		//
		$ary_users = explode ( '|', $post ['user_ids'] );
		//
		try {
			foreach ( $ary_users as $value ) {
				$data ['user_id'] = $value;
				if (false === $model->add ( $data )) {
					E('保存角色权限用户列表失败！');
				}
			}
		}
		catch ( Exception $e ) {
			$this->ajaxReturn ( make_rtn ( $e->getMessage() ) );
		}
		//
		$this->ajaxReturn ( make_rtn('保存角色用户列表成功！', true) );
	}
	
}

?>