<?php
class Admin_ViolationController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
    }

    public function viewListAction()
    {

        $member_id = intval($this->_getParam('member_id'));
        $violationModel = new Model_Topic_Violations();
        $violationList = $violationModel->getViolationList($member_id);
        $this->view->violationList = $violationList;
        $this->view->countViolation = count($violationList);
	}

    public function reportListAction()
    {
        $reportModel = new Model_IM_MessageReport();
        $infoID = intval($this->_getParam('infoID'));
        $infoType = intval($this->_getParam('infoType'));
        $reportList = $reportModel->getReportListByType($infoID,$infoType);
        $this->view->reportList = $reportList;
        $this->view->reportNum = count($reportList);
    }

}