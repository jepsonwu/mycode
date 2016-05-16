<?php
class Admin_AdminsController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
		$adminModel = new DM_Model_Table_User_Admin();
		$totalCount = $adminModel->getAdminTotal();
		$list = $adminModel->getAdminList($pageIndex, $pageSize);
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
	        $username = $this->_getParam('name','');
	        if(empty($username)){
	        	$this->returnJson(0,'用户名不能为空!');
	        }
	        
	        $adminModel = new DM_Model_Table_User_Admin();
	        if($adminModel->hasExistsUsername($username)){
	        	$this->returnJson(0,'该用户名已存在,请使用其他用户名!');
	        }
	        
	        $password = $this->_getParam('password','');
            if(empty($password)){
                $this->returnJson(0,'密码不能为空');
            }

	        $repassword = $this->_getParam('repassword','');
	        if($password != $repassword){
	        	$this->returnJson(0,'两次输入的密码不一致!');
	        }
	        	        
	        $empno = $this->_getParam('empno','');
	        $telphone = $this->_getParam('telphone','');
	        
	        $adminModel->addAdmin($username, $password,$empno,$telphone);
	        $this->returnJson();
    	}
    }
    
    /**
     * 删除管理员
     */
    public function removeAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$admin_id = intval($this->_getParam('admin_id',0));
    	$adminModel = new DM_Model_Table_User_Admin();
    	$adminModel->deleteAdmin($admin_id);
    	$this->returnJson();	
    }
    
    /**
     * 禁用或启用
     */
    public function statusAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$admin_id = intval($this->_getParam('admin_id',0));
    	if($admin_id <= 0){
    		$this->returnJson(0,'参数错误');
    	}
    	
    	$status = $this->_getParam('status',-1);
    	if(!in_array($status,array(0,1))){
    		$this->returnJson(0,'状态参数错误');
    	}
    	
    	$statusName = $status == 1 ? DM_Model_Table_User_Admin::ENABLE_STATUS : DM_Model_Table_User_Admin::DISABLE_STATUS;
    	$adminModel = new DM_Model_Table_User_Admin();
    	$adminModel->setAdminStatus($admin_id, $statusName);
    	$this->returnJson();
    }
    
    /**
     * 编辑
     */
    public function updateAction()
    {
    	$admin_id = intval($this->_getParam('admin_id',0));
    	$adminModel = new DM_Model_Table_User_Admin();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		
    		if($admin_id <= 0){
    			$this->returnJson(0,'参数错误!');
    		}
    		
    		$username = $this->_getParam('name','');
    		if(empty($username)){
    			$this->returnJson(0,'用户名不能为空!');
    		}
    		
    		$adminModel = new DM_Model_Table_User_Admin();
    		if($adminModel->hasExistsUsername($username,$admin_id)){
    			$this->returnJson(0,'该用户名已存在,请使用其他用户名!');
    		}
    		
    		$data['Username'] = $username;
    		
    		$password = $this->_getParam('password','');
            if(empty($password)){
                $this->returnJson(0,'密码不能为空');
            }

    		$repassword = $this->_getParam('repassword','');    
    		if($password != $repassword){
    			$this->returnJson(0,'两次输入的密码不一致!');
    		}
	    		   		
	    	$data['Passwd'] = $password;		
    		$data['Empno'] = $this->_getParam('empno');
    		$data['Telphone'] = $this->_getParam('telphone');
    		
    		$adminModel->editAdmin($admin_id,$data);
    		$this->returnJson(1);
    	}
    	$adminInfo = $adminModel->getAdminInfoByID($admin_id);
    	$this->escapeVar($adminInfo);
    	$this->view->admin = $adminInfo;
    }

    /**
     * 分配角色
     */
    public function grantAction()
    {
    	$admin_id = $this->_getParam('admin_id',0);
    	$roleModel = new DM_Model_Table_User_Role();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$roleCheck = $this->_getParam('roleCheck',array());

    		//移除角色
    		$notDeleteRoles = array_unique(array_keys($roleCheck));
    		$roleModel->stripUserRoles($admin_id, $notDeleteRoles, DM_Model_Table_User_Role::P_ADMIN);

    		//分配角色
    		if(!empty($notDeleteRoles)){
    			foreach($notDeleteRoles as $v){
    				$roleModel->grantUserRole($admin_id, $v,DM_Model_Table_User_Role::P_ADMIN);
    			}
    		}
    		$this->returnJson();
    	}else{
    		$roleList = $roleModel->getUserRolesList($admin_id, DM_Model_Table_User_Role::P_ADMIN);
    		$this->escapeVar($roleList);
    		$this->view->admin_id = $admin_id;
    		$this->view->roleList = $roleList;
    	}
    }

}