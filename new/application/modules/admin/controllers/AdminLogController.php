<?php
class Admin_AdminLogController extends DM_Controller_Admin
{

	public function indexAction()
	{
		//会员
		//$this->view->memberOption = $this->getSelectOption('DM_Model_Table_Members', 'members', 'MemberID', 'Email');
		//管理员
		$this->view->adminOption = $this->getSelectOption('DM_Model_Table_User_Admin', 'admins', 'AdminID', 'Username',true);

    }
    /*
     * 列表
    */
    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
        $pageSize = $this->_getParam('rows',50);
        
        $admin_id = $this->_getParam('admin_id', '');
        $start_date = $this->_getParam('start_date', '');
        $end_date = $this->_getParam('end_date', '');
        $fieldOneName = $this->_getParam('fieldOneName');
        $fieldOneValue = trim($this->_getParam('fieldOneValue'));
		
        $logModel = new DM_Model_Table_AdminLogs();
        $select = $logModel->select()->setIntegrityCheck(false);

        if(!empty($fieldOneName) && !empty($fieldOneValue)){
        	$select->where($fieldOneName.' = ? ',$fieldOneValue);
        }
        if(!empty($admin_id)) {
        	$select->where('AdminID = ? ',$admin_id);
        }
        if(!empty($start_date)) {
        	$select->where('Addtime > ? ',$start_date);
        }
        if(!empty($end_date)) {
        	$end_date = date('Y-m-d H:i:s',strtotime($end_date)+86399);
        	$select->where('Addtime < ? ',$end_date);
        }

        $total_sql = $select->__toString();
        $total_sql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $total_sql); 

         //总记录数
        $totalCount = $logModel->getAdapter()->fetchOne($total_sql);

        $select->order('LogID desc ');
        $select->limitPage($pageIndex, $pageSize);
        $list = $select->query()->fetchAll();


		$this->_helper->json(array('total'=>$totalCount,'rows'=>$list));
    }

}
