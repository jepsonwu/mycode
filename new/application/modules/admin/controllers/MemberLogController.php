<?php
class Admin_MemberLogController extends DM_Controller_Admin
{

	public function indexAction()
	{
		//会员
		//$this->view->memberOption = $this->getSelectOption('DM_Model_Table_Members', 'members', 'MemberID', 'Email');
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
        
        $fieldOneName = $this->_getParam('fieldOneName');
        $fieldOneValue = intval($this->_getParam('fieldOneValue'));
        $start_date = $this->_getParam('start_date', '');
        $end_date = $this->_getParam('end_date', '');

		
        $logModel = new DM_Model_Account_MemberLogs();
        $select = $logModel->select()->setIntegrityCheck(false);
        $select->from('account_system.member_logs')->where('SystemSign = ?','caizhu');

        if(!empty($fieldOneName) && !empty($fieldOneValue)){
        	$select->where($fieldOneName.' = ? ',$fieldOneValue);
        }

        if(!empty($start_date)) {
        	$select->where('CreateTime >= ? ',$start_date);
        }
        if(!empty($end_date)) {
        	$end_date = date('Y-m-d H:i:s',strtotime($end_date)+86399);
        	$select->where('CreateTime <= ? ',$end_date);
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
