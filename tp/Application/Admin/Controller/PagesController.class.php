<?php

namespace Admin\Controller;

use Admin\Model\CategoryModel;

use Admin\Controller\CommonController;

class PagesController extends CommonController {
	
	// 查询前
	protected function _filter(&$map, &$param) {
		//
		$rqst = I ( 'request.' );
		//
		$this->_com_filter ( 'title', $rqst ['title'], 1, $map, $param );
		$this->_com_filter ( 'status', $rqst ['status'], 2, $map, $param );
	}
	
	// 查询后
	protected function _processer(&$volist) {
		//
		$stt_news = C('STT_NEWS');
		//
		foreach ( $volist as $key => $value ) {
			$volist[$key] ['status_show'] = get_status ( $value ['status'], '已' . $stt_news [$value ['status']] );
			$volist[$key]['stt_val'] = -$value['status'];
			$volist[$key]['stt_act'] = $stt_news[$volist[$key]['stt_val']];
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
		$model = M ( CONTROLLER_NAME );
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'id', true );
		}
		//
		$this->display ();
	}
	
	// 安全验证
	protected function checkPost() {
		$post = I("post.");
		$post['content'] = stripslashes($_POST['content']);
		//
		return $post;
	}
	
	// 新增前
	public function _before_add() {
		//
		$model = M ( CONTROLLER_NAME );
		$vo = $model->getById ( $model->max ( 'id' ) );
		$this->assign ( 'vo', $vo );
	}
	
	// 保存新增
	public function insert() {
		//
		$model = D ( CONTROLLER_NAME );
		//
		$post = $this->checkPost();
		$post['create_time'] = time();
		// 新增记录
		if (false !== $model->add ($post)) {
			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}
	
	// 保存编辑
	public function update() {
		// 生成模型
		$model = D ( CONTROLLER_NAME );
		//
		$post = $this->checkPost();
		// 更新记录
		if ( false !== $model->save ($post) ) {
			// 成功返回
			$this->ajaxReturn ( make_url_rtn ( '编辑成功!' ) );
		} else {
			// 失败返回
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
		}
	}
	
	// 更新后
	public function my_after_update( $post, $rcdNow ) {
		//
		if ( !empty($rcdNow['pic']) && $post['pic'] != $rcdNow['pic'] ) {
			//
			$path = BASE_PATH . '/Uploads/Pages/'.$rcdNow['pic'];
			if ( !unlink($path) ) {
				err ( '图片删除失败：' . $path );
			}
		}
	}
	
	// 删除后
	public function my_after_delete( $rcds ) {
		//
		if ( !empty($rcds['pic']) ) {
			$path = BASE_PATH . '/Uploads/Pages/'.$rcds['pic'];
			if ( !unlink($path) ) {
				err ( '图片删除失败：' . $path );
			}
		}
	}
	
}

?>