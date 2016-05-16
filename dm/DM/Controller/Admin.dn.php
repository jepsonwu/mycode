<?php
/**
 * Admin通用控制器 
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Controller_Admin extends DM_Controller_Action
{
	
    /**
     * session命名空间
     * @var string
     */
    const SESSION_NAMESPACE='admin';

    protected function extraCheck($authInfo){
        $adminModel = new Application_Model_DbTable_Admins();
        $gcode = $adminModel->getCode($authInfo->AdminID);
        if(empty($gcode)){
            $this->_redirect("/admin/duomai/bind/");
        }
        if(!$this->session->isVerified){
            $this->_redirect("/admin/duomai/gcode/");
        }
    }
    
    public function init()
    {
        parent::init();
        
        $this->getStaticUrl();
        // 配置信息
        $this->config = $this->getConfig();
        $front = Zend_Controller_Front::getInstance();
        $plugin = $front->getPlugin('Zend_Controller_Plugin_ErrorHandler');
        $plugin->setErrorHandlerModule($this->_request->getModuleName());
        $this->auth = Zend_Auth::getInstance();
       
        $this->auth->setStorage(new Zend_Auth_Storage_Session('Zend_Auth_Admin'));
        $this->session = $this->getSession();
         
        //不需要判断的url
        $notCheckArray = array('index_login','error_error','duomai_login','index_yzm','index_logout','duomai_logout','duomai_bind','duomai_gcode');
        if(!in_array($this->getRequest()->getControllerName().'_'.$this->getRequest()->getActionName(),$notCheckArray)){
            $this->checkAuth('/admin/index/login');
            //判断IP
            $request_ip = $this->_request->getClientIp();
            if($this->session->login_ip != $request_ip){
                $this->auth->clearIdentity();
                $this->checkAuth('/admin/index/login');
            }
            $authInfo = $this->auth->getIdentity();
            $this->extraCheck($authInfo);
            if($authInfo){
                //延长后台登录超时 默认一周，现在退出太频繁了
                if(time()-$authInfo->Lasttime >86400*7){
                    $this->auth->clearIdentity();
                } else {
                    $authInfo->Lasttime = time();
                    $this->adminInfo = $authInfo;
                }
            }else{
                $this->auth->clearIdentity();
                $this->checkAuth('/admin/index/login');
            }
        
            if(!$this->checkPrivilege($this->_request->getControllerName(),$this->_request->getActionName())){
                if($this->_request->isXmlHttpRequest()){
                   $this->returnJson(0,'您无权操作');
                }else{
                	header('Content-type:text/javascript;Charset=utf-8');
                   exit('无权查看');
                }
            }
        }
    }
    
    /**
     * 权限判断
     * @param 主标识 $main_sign
     * @param 副标识 $sub_sign
     * @return boolean
     */
    protected function checkPrivilege($main_sign,$sub_sign)
    {
    	$rolesArray = $this->session->selfRoles;
    	if(empty($rolesArray) || empty($main_sign) || empty($sub_sign)){
    		return false;
    	}else{
    	    //管理员默认拥有所有权限
    	    if(in_array(1, $rolesArray)){
    	        return true;
    	    }
    		//初始化角色对象
    		$roleModel = new DM_Model_Table_User_Role();
    
    		//根据角色获取权限列表
    		$privileges = $roleModel->getRolePrivliegesByRoleIDs($rolesArray);
    
    		return $this->hasPermissionPrivileges($privileges,$main_sign, $sub_sign);
    	}
    }
    
    /**
     * 判断是否允许
     * @param array $privileges
     * @param string $main_sign
     * @param string $sub_sign
     */
    private function hasPermissionPrivileges($privileges,$main_sign,$sub_sign)
    {
    	$isAllow = true;
    	if(!empty($privileges)){
    
    		//判断deny列表
    		if(!empty($privileges['deny'])){
    			foreach($privileges['deny'] as $item){
    				if($item['MainSign'] == $main_sign && ($item['SubSign'] == $sub_sign || trim($item['SubSign']) == '*')){
    					$isAllow = false;
    					break;
    				}
    			}
    		}
    
    		//判断allow列表
    		if(true == $isAllow && !empty($privileges['allow'])){
    			$isAllow = false;
    			foreach($privileges['allow'] as $item){
    				if($item['MainSign'] == $main_sign && ($item['SubSign'] == $sub_sign || trim($item['SubSign']) == '*')){
    					$isAllow = true;
    					break;
    				}
    			}
    		}
    	}else{
    		$isAllow = false;
    	}
    	return $isAllow;
    }
    
    /**
     * 获取ID和名称的键值对
     * @param  $object_name 对象名
     * @param  string $table_name
     * @param  string $key_field
     * @param  string $value_field
     * @param  boolean $nullable
     * @return array ($key_field=>$value_field,...)
     */
    public function getSelectOption($object_name, $table_name, $key_field, $value_field, $nullable=false, $where='')
    {
    	$model = new $object_name();
    
    	$select = $model->select()->from($table_name,array($key_field, $value_field));
    	if(!empty($where) && is_array($where)){
    		foreach($where as $key=>$value){
    			$select->where($key,$value);
    		}
    	}
    
    	$adapter = $model->getAdapter();
    	$result = $adapter->fetchPairs($select);
    
    	$nullable ? $result[''] = '--所有--' : '';
    	return $result;
    }
    
    
    /**
     * 管理员后台重写是否登录
     */
    protected function isLogin()
    {
        return $this->auth->getIdentity();
    }
    

    /**
     * 分页操作
     * @param Zend_Db_Select $select
     * @param unknown_type $page
     * @param unknown_type $perpage
     * @return multitype:multitype: number
     */
    protected function getResultSet(Zend_Db_Select $select, $page = 1, $perpage = 10)
    {
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($perpage);
        $paginator->setCurrentPageNumber($page);
        $total = $paginator->getTotalItemCount();
        $items = $paginator->getCurrentItems();
        return array(
                'total'		=> $total,
                'rows'	=> iterator_to_array($items)
        );
    }
}
