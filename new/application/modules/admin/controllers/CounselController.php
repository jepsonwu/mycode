<?php

/**
 * 咨询服务管理
 * User: kitty
 */
class Admin_CounselController extends DM_Controller_Admin
{

    public function indexAction()
	{
        
	}
 
    //查询条件
    protected $list_where = array(
        "eq" =>array("MemberID","Status"),
        "bet" => array("Start_Price", "End_Price"),
    );

    /**
     * 咨询服务列表
     */
	public function listAction()
	{

        $start_date = trim($this->_getParam('start_date',''));
        $end_date = trim($this->_getParam('end_date',''));

        $counselModel = new Model_Counsel_Counsel();
        $select = $counselModel->select();
        if(!empty($start_date)){
            $select->where("CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("CreateTime <= ?", date("Y-m-d H:i:s", strtotime($end_date)+86399));
        }
        $this->_helper->json($this->listResults($counselModel, $select, "CID"));
	}
    
    //询财统计页面
    public function statAction()
    {
        
    }
    
    /**
     * 统计数据
     */
    public function statDataAction(){
        $counselStateModel = new Model_Counsel_CounselSellerState();
        $select = $counselStateModel->select();
        $Start_ReceiveNum = $this->_getParam('Start_ReceiveNum',-1);
        $End_ReceiveNum = $this->_getParam('End_ReceiveNum',-1);
        $Start_Settlement = $this->_getParam('Start_Settlement',-1);
        $End_Settlement = $this->_getParam('End_Settlement',-1);
        if($Start_ReceiveNum>=0){
            $select->where("ReceiveNum >= ?", $Start_ReceiveNum);
        }
        if($End_ReceiveNum>=0){
            $select->where("ReceiveNum <= ?", $End_ReceiveNum);
        }
        if($Start_Settlement>=0){
            $select->where("Settlement >= ?", $Start_Settlement);
        }
        if($End_Settlement>=0){
            $select->where("Settlement <= ?", $End_Settlement);
        }
        $result = $this->listResults($counselStateModel, $select, "CSID");
        $this->_helper->json($result);
    }
}