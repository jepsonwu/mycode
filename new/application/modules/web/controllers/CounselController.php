<?php

class Web_CounselController extends Action_Web
{

	public function init()
	{
		parent::init();
		$this->hasColumn();
	}

	/**
	 * 问财咨询首页
	 */
	public function indexAction()
	{
		$this->view->headTitle("财猪 - 咨询服务首页");
		$memberID = $this->memberInfo->MemberID;

		//是否填写资料
		$financialModel = new Model_Financial_FinancialPlannerInfo();
		$result = $financialModel->getInfoMix(array("MemberID =?" => $memberID), "MemberID");
		$this->view->isComplete = is_null($result) ? 0 : 1;


	}

	/**
	 * 增加问财
	 */
	public function addAction()
	{
		$this->view->headTitle("财猪 - 添加问财");
		$memberID = $this->memberInfo->MemberID;
	}


	/**
	 * 我的订单
	 */
	public function myOrderAction()
	{
		$this->view->headTitle("问财 - 我的订单");
		$memberID = $this->memberInfo->MemberID;
        $financialModel = new Model_Financial_FinancialPlannerInfo();
		$result = $financialModel->getInfoMix(array("MemberID =?" => $memberID), "MemberID");
		$this->view->isComplete = is_null($result) ? 0 : 1;
	}

	/**
	 * 我的评论
	 */
	public function myCommentAction()
	{
		$this->view->headTitle("问财 - 我的评论");
		$memberID = $this->memberInfo->MemberID;
        $financialModel = new Model_Financial_FinancialPlannerInfo();
		$result = $financialModel->getInfoMix(array("MemberID =?" => $memberID), "MemberID");
		$this->view->isComplete = is_null($result) ? 0 : 1;
	}
}