<?php

/**
 * 达人管理
 * User: Hale <hale@duomai.com>
 * Date: 16-02-17
 * Time: 下午4:42
 */
class Admin_BonusController extends DM_Controller_Admin
{
    /**
     * 红包发送列表
     */
    public function indexAction()
	{
        
	}

    /**
     * 红包发送列表
     */
	public function listAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',10);

        $memberId = $this->_getParam('memberId',0);

        $status = $this->_getParam('status',0);
        $bonusType = $this->_getParam('bonusType',0);
        $groupType = $this->_getParam('groupType',0);

        $start_date = $this->_getParam('start_date','');
        $end_date = $this->_getParam('end_date','');

        $start_amount = $this->_getParam('start_amount',-1);
        $end_amount = $this->_getParam('end_amount',-1);
        
        $total = 0;
        
        $bonusModel = new Model_Bonus();
        $list = $bonusModel->sendList($memberId,$status,$bonusType,$groupType,$start_date,$end_date,$start_amount,$end_amount,$pageIndex,$pageSize,$total);
		
		$this->_helper->json(array('total'=>$total,'rows'=>$list));
	}
    
    /**
     * 某红包的领取记录
     */
    public function receiveAction(){
        $bounsId = $this->_getParam('bounsId',0);

        $bonusModel = new Model_Bonus();
        $list = $bonusModel->getReceiveList(0,$bounsId,false);
        $this->view->list = $list;
	}
    
    /**
     * 红包功能概况
     */
    public function surveyAction()
	{
        if($this->_request->isPost()){
            $pageIndex = $this->_getParam('page',1);
            $pageSize = $this->_getParam('rows',10);
            $memberId = $this->_getParam('memberId',0);
            $unit = $this->_getParam('unit',1);
            $start_date = $this->_getParam('start_date',date('Y-m-d',strtotime('- 30 days')));
            $end_date = $this->_getParam('end_date',date('Y-m-d'));
            $end_date>date('Y-m-d') && $end_date = date('Y-m-d');
            
            $total = 0;
            
            $bonusModel = new Model_Bonus();
            $list = $bonusModel->survey($memberId,$unit,date('Y-m-d',strtotime($start_date)),date('Y-m-d',strtotime($end_date)),$pageIndex,$pageSize,$total);
            $this->_helper->json(array('total'=>$total,'rows'=>$list));
        }
	}
    
    /**
     * 红包功能走势图
     */
    public function trendAction(){
        $memberId = $this->_getParam('memberId',0);
        $unit = $this->_getParam('unit',1);
        $start_date = $this->_getParam('start_date',date('Y-m-d',strtotime('- 30 days')));
        $end_date = $this->_getParam('end_date',date('Y-m-d'));
        $end_date>date('Y-m-d') && $end_date = date('Y-m-d');
        if($this->_request->isPost()){
            $total = 0;
            $bonusModel = new Model_Bonus();
            $list = $bonusModel->survey($memberId,$unit,date('Y-m-d',strtotime($start_date)),date('Y-m-d',strtotime($end_date)),0,0,$total);
            $this->_helper->json(array('total'=>$total,'rows'=>$list));
        }
        $this->view->memberId = $memberId;
        $this->view->unit = $unit;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
}