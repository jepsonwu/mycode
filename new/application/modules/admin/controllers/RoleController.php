<?php
class Admin_RoleController extends DM_Controller_Admin
{
	private $_platform = DM_Model_Table_User_Role::P_ADMIN;
	
	public function preDispatch()
	{
		if(strtolower($this->_getParam('platform',null)) == 'front'){
			$this->_platform = DM_Model_Table_User_Role::P_FRONT;
		}
	}
	
	
	private function isFront()
	{
		return $this->_platform == DM_Model_Table_User_Role::P_FRONT;
	}
	
	
	public function indexAction()
	{
		if($this->isFront()){
			$this->_helper->viewRenderer->setNoRender();
			echo $this->view->render('/role/frontindex.phtml');
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
		
		$roleModel = new DM_Model_Table_User_Role();
		$totalCount = $roleModel->getRoleTotal($this->_platform);
		$list = $roleModel->getRoleList($pageIndex, $pageSize,$this->_platform);
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
			$name = $this->_getParam('name','');
			if(empty($name)){
				$this->returnJson(0,'名称不能为空！');
			}
			
			$roleModel = new DM_Model_Table_User_Role();
			$role_id = $roleModel->addRole($name,$this->_platform);

			//分配权限
			$allowCheckArray = $this->_getParam('allowCheck','');
			if(!empty($allowCheckArray)){
				foreach($allowCheckArray as $key=>$val){
					$roleModel->grandRolePrivileges($role_id, $key, DM_Model_Table_User_Role::STATUS_ALLOW);
				}
			}
			
			$denyCheckArray = $this->_getParam('denyCheck','');
			if(!empty($denyCheckArray)){
				foreach($denyCheckArray as $k=>$v){
					$roleModel->grandRolePrivileges($role_id,$k,DM_Model_Table_User_Role::STATUS_DENY);
				}
			}
			
			$this->_helper->json(array('flag'=>1,'msg'=>''));
		}else{
			$privilegeModel = new DM_Model_Table_User_Privilege();
			$privilegeList = $privilegeModel->getPrivilegeList(NULL, NULL,$this->_platform);
			$this->escapeVar($privilegeList);
			$this->view->privilegeList = $privilegeList;
		}
	}
	
	/**
	 * 删除角色
	 */
	public function removeAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$role_id = $this->_getParam('id',0);
		$roleModel = new DM_Model_Table_User_Role();
		
		//判断角色是否已经被分配
		if($roleModel->isGrantToUser($role_id, DM_Model_Table_User_Role::P_ADMIN)){
			$this->returnJson(0,'改角色已经被分配了,暂时不能删除');
		}
		//删除角色 及 角色与权限的对应关系
		$roleModel->deleteRoleByID($role_id);
				
		$this->_helper->json(array('flag'=>1));
	}
	
	/**
	 * 编辑
	 */
	public function updateAction()
	{
		$roleModel = new DM_Model_Table_User_Role();
		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$role_id = $this->_getParam('role_id',0);
			$name = $this->_getParam('name','');
			if(empty($name)){
				$this->returnJson(0,'名称不能为空!');
			}
			
			$roleModel->updateRole($role_id, $name);
			
			//分配权限
			$allowCheckArray = $this->_getParam('allowCheck',array());
			$denyCheckArray = $this->_getParam('denyCheck',array());
			$willNotDeleteKeys = array_unique(array_merge(array_keys($allowCheckArray),array_keys($denyCheckArray)));
			
			//先移除多余权限
			$roleModel->stripRolePrivileges($role_id,$willNotDeleteKeys);
			
			
			if(!empty($allowCheckArray)){
				foreach($allowCheckArray as $key=>$val){
					$roleModel->grandRolePrivileges($role_id, $key, DM_Model_Table_User_Role::STATUS_ALLOW);
				}
			}
				
			if(!empty($denyCheckArray)){
				foreach($denyCheckArray as $k=>$v){
					$roleModel->grandRolePrivileges($role_id,$k,DM_Model_Table_User_Role::STATUS_DENY);
				}
			}
			$this->_helper->json(array('flag'=>1,'msg'=>''));
		}else{
			$role_id = $this->_getParam('id',0);
			$privilegesList = $roleModel->getRolePrivilegesList($role_id,$this->_platform);
			$this->escapeVar($privilegesList);
			$this->view->role = $roleModel->getRoleByID($role_id);
			$this->view->privilegeList = $privilegesList;
		}
	}
	
	/**
	 * 测试权限判断
	 */
	public function testAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		var_dump($this->checkPrivilege('33', '33'));		
	}
}