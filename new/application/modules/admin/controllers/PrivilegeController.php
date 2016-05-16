<?php
class Admin_PrivilegeController extends DM_Controller_Admin
{
    /** Bruce改为protected，因为前端API权限控制管理ApiAccessController是继承这个类的*/
	protected $_platform = 'ADMIN';
	
	public function preDispatch()
	{
		if($this->_getParam('platform',null) == 'front'){
			$this->_platform = 'FRONT';
		}	
	}

	
	private function isFront()
	{
		return $this->_platform == 'FRONT';	
	}
	
	public function indexAction()
	{
		if($this->isFront()){
			$this->_helper->viewRenderer->setNoRender();
			echo $this->view->render('/privilege/frontindex.phtml');
		}
	}
	
	/**
	 * 列表
	 */
	public function listAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',10);
		$privilegeModel = new DM_Model_Table_User_Privilege();
		
		$totalCount = $privilegeModel->getPrivilegeTotal($this->_platform);
		$list = $privilegeModel->getPrivilegeList($pageIndex, $pageSize,$this->_platform);
		$this->escapeVar($list);
		$this->_helper->json(array('total'=>$totalCount,'rows'=>$list));
	}
		
	/**
	 * 添加
	 */
	public function addAction()
	{
		if($this->getRequest()->isPost()){		
			$this->_helper->viewRenderer->setNoRender();
			$describe = $this->_getParam('describe','');
			if(empty($describe)){
				$this->returnJson(0,'描述不能为空');
			}
			$main_sign = $this->_getParam('main_sign','');
			$sub_sign = $this->_getParam('sub_sign','');
			if(empty($main_sign) || empty($sub_sign)){
				$this->returnJson(0,'主标识与副标识均不能为空');
			}
						
			$privilegeModel = new DM_Model_Table_User_Privilege();
			if($privilegeModel->checkHasExits($main_sign, $sub_sign, $this->_platform)){
				$this->returnJson(0,'已存在对应的标识,请使用其他标识!');
			}
			$privilege_id = $privilegeModel->addPrivilege($describe, $main_sign, $sub_sign,$this->_platform);
			$this->returnJson();
		}else{
			
		}		
	}
	
	
	/**
	 * 删除
	 */
	public function removeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$privilege_id = intval($this->_getParam('id',0));
		
		if($privilege_id < 0){
			$this->returnJson(0,'参数id错误');
		}
		
		//判断该权限是否已经分配给对应的角色
		$roleModel = new DM_Model_Table_User_Role();
		if($roleModel->isGrantToRole($privilege_id)){
			$this->returnJson(0,'该权限已经被分配了,暂时不能删除！');
		}
		
		$privilegeModel = new DM_Model_Table_User_Privilege();
		$privilegeModel->deletePrivilegeByID($privilege_id);
		
		$this->returnJson();
	}
	
	/**
	 * 修改
	 */
	public function updateAction()
	{
		$privilegeModel = new DM_Model_Table_User_Privilege();
		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$privilege_id = $this->_getParam('privilege_id',0);
			$describe = $this->_getParam('describe','');
			if(empty($describe)){
				$this->returnJson(0,'描述不能为空');
			}
			
			$main_sign = $this->_getParam('main_sign','');
			$sub_sign = $this->_getParam('sub_sign','');
			if(empty($main_sign) || empty($sub_sign)){
				$this->returnJson(0,'主标识与副标识均不能为空');
			}
			
			if($privilegeModel->checkHasExits($main_sign, $sub_sign, $this->_platform,$privilege_id)){
				$this->returnJson(0,'已存在对应的标识,请使用其他标识!');
			}
			
			$privilegeModel->updatePrivileges($privilege_id, $describe, $main_sign, $sub_sign);
						
			$this->returnJson();
		}else{
			$privilege_id = $this->_getParam('id',0);
			$privilege = $privilegeModel->getPrivilegeById($privilege_id);
			$this->escapeVar($privilege);
			$this->view->privilege = $privilege;
		}
	}
}