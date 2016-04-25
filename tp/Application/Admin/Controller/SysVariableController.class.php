<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

class SysVariableController extends CommonController {
	
	// 查询前
	protected function _filter(&$map, &$param) {
		//
		$rqst = I('request.');
		//
		if (isset ( $rqst ['mykey'] )) {
			$mykey = $rqst ['mykey'];
		}
		if (! empty ( $mykey )) {
			$map ['mykey'] = array ( 'like', "%" . $mykey . "%" );
			$this->assign ( "mykey", $mykey );
			$param ['mykey'] = $mykey;
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
		$model = D ( CONTROLLER_NAME );
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'id', true );
			// echo $model->getLastSql();
		}
		//
		$this->display();
	}
}

?>