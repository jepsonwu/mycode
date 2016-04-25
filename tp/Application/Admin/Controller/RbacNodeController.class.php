<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

class RbacNodeController extends CommonController {
	
	// 查询前
	protected function _filter(&$map, &$param) {
		//
		$rqst = I('request.');
		//
		if (isset ( $rqst ['title'] )) {
			$title = $rqst ['title'];
		}
		if (! empty ( $title )) {
			$map ['title'] = array ( 'like', "%" . $title . "%" );
			$this->assign ( 'title', $title );
			$param ['title'] = $title;
		}
		//
		if (isset ( $rqst ['pid'] ) && $rqst ['pid'] != '') {
			$map ['pid'] = $rqst ['pid'];
		} else {
			$map ['pid'] = 1;
		}
		$this->assign ( 'pid', $map ['pid'] );
		$param ['pid'] = $map ['pid'];
		//
		if (isset ( $rqst ['type'] ) && $rqst ['type'] != '') {
			$map ['type'] = $rqst ['type'];
			$this->assign ( 'type', $map ['type'] );
			$param ['type'] = $map ['type'];
		}
		//
		$_SESSION ['bkgd'] ['currentNode'] = D ( CONTROLLER_NAME )->getById ( $map ['pid'] );
	}
	
	// 查询后
	protected function _processer(&$volist) {
		//
		$menu_types = C ( "MENU_TYPES" );
		$stt_jyqy = C ( "STT_JYQY" );
		//
		foreach ( $volist as &$value ) {
			$value ['status_show'] = get_status ( $value ['status'], '已' . $stt_jyqy [$value ['status']] );
			$value ['stt_val'] = - $value ['status'];
			$value ['stt_act'] = $stt_jyqy [$value ['stt_val']];
			//
			if ($volist [0] ['level'] == 2) {
				$value ['type_name'] = $menu_types [$value ['type']];
			}
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
		if ($param ['pid'] == 1) {
			$this->assign ( 'menu_types', C ( "MENU_TYPES" ) );
			unset($_SESSION[ C ( 'SEARCH_PARAMS_PREV_STR') ]);
		}
		else {
			$sch_params = json_decode($_SESSION [C ( 'SEARCH_PARAMS' )]);
			if ( $sch_params->pid == 1 ) {
				$_SESSION[ C ( 'SEARCH_PARAMS_PREV_STR') ] = $_SESSION[ C ( 'SEARCH_PARAMS_STR') ];
			}
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D ( CONTROLLER_NAME );
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'sort', true );
// 			echo $model->getLastSql();
		}
		//
		$this->display();
	}
	
	//
	public function _before_add() {
		if ($_SESSION ['bkgd'] ['currentNode'] ['level'] == 1) {
			$this->assign ( "menu_types", C ( "MENU_TYPES" ) );
		}
	}
	
	// 批新增
	public function grpAdd() {
		//
		$this->assign ( "com_nodes", C ( "COM_NODES" ) );
		//
		$this->display();
	}
	
	// 保存批新增
	public function grpInsert() {
		//
		$post = I('post.');
		unset( $post['names'] );
		//
		if (empty ( $_POST ['names'] )) {
			$this->ajaxReturn ( make_rtn("操作名丢失！") );
		}
		//
		$model = D ( CONTROLLER_NAME );
		$com_nodes = C ( "COM_NODES" );
		// 启动事务
		$model->startTrans ();
		$result = true;
		$reason = "未知";
		//
		foreach ( $_POST ['names'] as $value ) {
			$post ['name'] = $value;
			$post ['title'] = $com_nodes [$value];
			//
			$map ['name'] = $post ['name'];
			$map ['pid'] = $post ['pid'];
			$map ['status'] = 1;
			if ($model->where ( $map )->find ()) {
				$reason = "操作节点" . $post ['name'] . "已经存在！";
				continue;
			}
			//
			if (false === $model->add ( $post )) {
				$result = false;
				$reason = "新增操作节点" . $post ['name'] . "失败！";
// 				\Think\Log::write ( __SELF__ . $reason . $model->getLastSql (), \Think\Log::ERR );
			}
		}
		// 结束事务
		if ($result) {
			$model->commit ();
			$this->ajaxReturn ( make_url_rtn('批量新增操作节点成功!') );
		} else {
			$model->rollback ();
			$this->ajaxReturn ( make_rtn('批量新增操作节点失败!<br />' . $reason) );
		}
	}
	
	//
	public function _before_edit() {
		if ($_SESSION ['bkgd'] ['currentNode'] ['level'] == 1) {
			$this->assign ( "menu_types", C ( "MENU_TYPES" ) );
		}
	}
	
	// 永久删除
	public function foreverDelete() {
		$model = D ( CONTROLLER_NAME );
		if (! empty ( $model )) {
			$id = I('request.id');
			if (isset ( $id )) {
				$ary_ids = explode ( '|', $id );
				foreach ( $ary_ids as $value ) {
					if ($model->getByPid ( $value )) {
						$this->ajaxReturn ( make_rtn("存在下级节点，无法删除！") );
					}
				}
				//
				$access = D ( "RbacAccess" );
				$condition = array (
						'node_id' => array ( 'in', $ary_ids ) 
				);
				if (false === $access->where ( $condition )->delete ()) {
// 					\Think\Log::write ( __SELF__ . "永久删除节点访问权限失败！" . $access->getLastSql (), \Think\Log::ERR );
					$this->ajaxReturn ( make_rtn ( "永久删除节点访问权限失败！" ) );
				}
				//
				$condition = array (
						'id' => array ( 'in', $ary_ids ) 
				);
				if (false !== $model->where ( $condition )->delete ()) {
					$this->ajaxReturn ( make_url_rtn ( '永久删除节点成功!' ) );
				} else {
// 					\Think\Log::write ( __SELF__ . "永久删除节点失败！" . $model->getLastSql (), \Think\Log::ERR );
					$this->ajaxReturn ( make_rtn ( "永久删除节点失败！" ) );
				}
			} else {
				$this->ajaxReturn ( make_rtn ( "非法操作！未选中需永久删除的对象！" ) );
			}
		}
	}

}

?>