<?php

namespace Admin\Controller;

use Admin\Model\RbacUserViewModel;

use Admin\Controller\CommonController;

class RbacUserController extends CommonController {

	// 查询前
	protected function _filter(&$map, &$param) {
		//
		$rqst = I('request.');
		//
		$this->_com_filter('nickname', $rqst ['nickname'], 1, $map, $param);
		//
		$this->_com_filter('id', $rqst ['id'], 2, $map, $param);
		//
		if ( in_array(1, $_SESSION ['bkgd'] ['roles']) ) {
			return;
		}
		//
		if ( in_array(2, $_SESSION ['bkgd'] ['roles']) ) {
			$ids = M('RbacRoleUser')->where('role_id=1')->getField('user_id', true);
			$map ['id'] = array( 'not in', $ids );
			return;
		}
		// 按角色显示用户列表
		$pids = M('RbacRole')->distinct(true)->where('pid>0')->getField( 'pid', true );
		$jiaoji = array_intersect($pids, $_SESSION ['bkgd'] ['roles']);
		if ( ! empty( $jiaoji ) ) {
			$role_ids = M('RbacRole')->where( 'pid in (' . implode(',', $jiaoji) . ')' )->getField( 'id', true );
			$role_ids = array_merge( $jiaoji, $role_ids );
			$ids = M('RbacRoleUser')->distinct(true)->where('role_id in (' . implode(',', $role_ids) . ')')->getField('user_id', true);
			$map ['id'] = array( 'in', $ids );
			return;
		}
		//
		$map ['id'] = $_SESSION [C ( 'USER_AUTH_KEY' )];
	}
	
	// 查询后
	protected function _processer(&$volist) {
		//
		$stt_jyqy = C ( "STT_JYQY" );
		//
		foreach ($volist as $key => $value) {
			$volist [$key] ['roles'] = getUserRoles( $value['id'], 2 );
			$volist [$key] ['status_show'] = get_status ( $value ['status'], '已' . $stt_jyqy [$value ['status']] );
			$volist [$key] ['stt_val'] = - $value ['status'];
			$volist [$key] ['stt_act'] = $stt_jyqy [$volist [$key] ['stt_val']];
		}
	}
	
	// 列表
	public function index() {
		//
		$map = array ();
		$param = array ();
		// 列表过滤器，生成查询Map对象
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map, $param );
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D( CONTROLLER_NAME );
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'id', true );
		}
		//
		$this->display();
	}

	// 检查帐号
	public function checkAccount() {
		if (! preg_match ( '/^[a-z]\w{4,}$/i', $_POST ['account'] )) {
			$this->ajaxReturn ( make_rtn ( "用户名必须是字母，且5位以上！" ) );
		}
		$User = D ( CONTROLLER_NAME );
		// 检测用户名是否冲突
		$name = I('request.account');
		if ($User->getByAccount ( $name )) {
			$this->ajaxReturn ( make_rtn("该用户名已经存在！") );
		} else {
			$this->ajaxReturn ( make_rtn("该用户名可以使用！", true) );
		}
	}
	
	// 重置密码
	public function resetPwd() {
		$password = I('post.password');
		if ('' == trim ( $password )) {
			$this->ajaxReturn ( make_rtn("密码不能为空！") );
		}
		//
		$user = D ( CONTROLLER_NAME );
		$user->password = md5 ( $password );
		$user->id = I('post.id');
		if (false !== $user->save ()) {
			$this->ajaxReturn ( make_rtn("密码已修改为".$password, true) );
		} else {
			$this->ajaxReturn ( make_rtn("重置密码失败！") );
		}
	}
	
	// 编辑前
	public function _before_edit() {
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
	}
	
	// 编辑后
	public function my_after_update($post, $rcdNow) {
		//
		S ('backend_users', null);
		// 删旧头像
		if ($rcdNow ['face'] != 'photo.png' && $rcdNow ['face'] != $post ['face']) {
			del_files( null, null, CONTROLLER_NAME, $rcdNow ['face'] );
		}
	}
	
	// 删除后
	public function my_after_delete( $rcds ) {
		//
		S ('backend_users', null);
		//
		$fields = array('face');
		del_files( $rcds, $fields, CONTROLLER_NAME );
	}
	
	// 安全验证
	protected function checkPost() {
		$_POST = I('post.');
	}
	
}

?>