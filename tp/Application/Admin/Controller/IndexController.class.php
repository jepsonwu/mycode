<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

class IndexController extends CommonController {
	/*
	 * 首页
	 */
	public function index() {
		//
		if (! isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
			redirect ( PHP_FILE . C ( 'USER_AUTH_GATEWAY' ) );
		}
		//
		$this->menu ();
		//
		if (! isset ( $_SESSION ['bkgd'] ['menuNow'] )) {
			if ( in_array(29, $_SESSION ['bkgd'] ['bg_listRows']) 
					|| in_array(55, $_SESSION ['bkgd'] ['bg_listRows']) ) {
				$_SESSION ['bkgd'] ['menuNow'] = 'AdviserHome';
			}
			else {
				$_SESSION ['bkgd'] ['menuNow'] = 'Orders';
			}
		}
		$this->assign ( 'menuNow', $_SESSION ['bkgd'] ['menuNow'] ); // 默认菜单
		//
		$this->display ();
	}
	
	/*
	 * 菜单
	 */
	protected function menu() {
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			//显示菜单项
			$menu = array();
			if ( isset($_SESSION['bkgd']['menu'.$_SESSION[C('USER_AUTH_KEY')]]) ) {
				//如果已经缓存，直接读取缓存
				$menu = $_SESSION['bkgd']['menu'.$_SESSION[C('USER_AUTH_KEY')]];
			}
			else {
				//读取数据库模块列表生成菜单项
				if ( isset($_SESSION['bkgd']['_ACCESS_LIST']) ) {
					$accessList = $_SESSION['bkgd']['_ACCESS_LIST'];
				}
				else {
					$accessList = \Org\Util\Rbac::getAccessList($_SESSION[C('USER_AUTH_KEY')]);
				}
				//
				$menuItem = array();
				$node = new \Admin\Model\RbacNodeModel();
				//
				$menu_types = C("MENU_TYPES");
				krsort($menu_types);
				foreach ($menu_types as $key => $value) {
					$list = $node->getMenuByType($key);
					if ( !empty($list) ) {
						// 模块访问权限
						foreach ( $list as $key_sub => $module) {
							if ( !isset($accessList[strtoupper(MODULE_NAME)][strtoupper($module['name'])]) && !isset($_SESSION[C('ADMIN_AUTH_KEY')]) ) {
								unset($list[$key_sub]);
							}
						}
						//
						if ( !empty($list) ) {
							$menuItem['title'] = $value;
							$menuItem['list'] = $list;
							array_push($menu, $menuItem);
						}
					}
				}
				// 缓存菜单
				$_SESSION['bkgd']['menu'.$_SESSION[C('USER_AUTH_KEY')]]	= $menu;
			}
			//
			$this->assign('menu', $menu);
		}
	}
	
}

?>