<?php

/**
 * 订单操作日志
 * Class Admin_CounselOrderLogController
 */
class Admin_CounselOrderLogController extends DM_Controller_Admin
{

	public function indexAction()
	{

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("LID", "OID"),
	);

	public function listAction()
	{
		$logModel = new Model_Counsel_CounselOrderLog();
		$select = $logModel->select();
		$this->_helper->json($this->listResults($logModel, $select, "LID"));
	}
}