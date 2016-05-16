<?php 
/**
 * 明细管理
 * 
 * @author Kitty
 *
 */
class Admin_AccountbookController extends DM_Controller_Admin
{

     public function indexAction()
    {
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		$MemberID= intval($this->_getParam('MemberID',0));
		$bookname= trim($this->_getParam('bookname',''));
		$start_amount= $this->_getParam('start_amount');
		$end_amount= $this->_getParam('end_amount');
		$start_date= $this->_getParam('start_date');
		$end_date= $this->_getParam('end_date');

		$bookModel = new Model_Accountbook();
		$select = $bookModel->select()->setIntegrityCheck(false);
		$select->from('account_book as b');
		
		$select->joinLeft('account_system.members as m', 'm.MemberID = b.MemberID',array('Email'));
        if($MemberID>0){
            $select->where("b.MemberID = ?", $MemberID);
        }

        if(!empty($bookname)){
        	$select->where("b.BookName like ?","%{$bookname}%");
        }

        if(!empty($start_amount)){
			$select->where("b.Balance >= ?", $start_amount);
        }

        if(!empty($end_amount)){
			$select->where("b.Balance <= ?", $end_amount);
        }
        if(!empty($start_date)){
			$select->where("b.UpdateTime >= ?", strtotime($start_date));
        }

        if(!empty($end_date)){
			$select->where("b.UpdateTime <= ?", strtotime($end_date));
        }


		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
				
		//总条数
		$total = $bookModel->getAdapter()->fetchOne($countSql);
		
		//排序
		$sort = $this->_getParam('sort','ID');
		$order = $this->_getParam('order','desc');
		$select->order("$sort $order");
				
		$select->limitPage($pageIndex, $pageSize);
		
		//列表		
		$results = $bookModel->fetchAll($select)->toArray();
		$this->escapeVar($results);
		$this->_helper->json(array('total'=>$total,'rows'=>$results));
	}
}