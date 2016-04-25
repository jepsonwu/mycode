<?php

namespace Admin\Controller;

class StudentsController extends CommonController {
	
	public function _filter(&$map, &$param) {
		//
		$rqst = I ('request.');
		//
		$this->_com_filter('id', $rqst['id'], 2, $map, $param);
		$this->_com_filter('nickname', $rqst['nickname'], 1, $map, $param);
	}
	
	public function index() {
		//
		$map = array();
		$param = array();
		// 列表过滤器，生成查询Map对象
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ($map, $param);
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D(CONTROLLER_NAME);
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'id', true );
		}
		//
		$this->display();
	}
	
	
	// 保存新增
	public function insert() {
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		
		if (false !== $model->add ($post)) {
			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}
	
	// 保存编辑
	public function update() {
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
	
		if (false !== $model->save ($post)) {
			$this->ajaxReturn ( make_url_rtn ( '编辑成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
		}
	}
	
	
}