<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_ColumnActivityController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("ca#Status"),
		"bet" => array("ca#Start_CreateTime", "ca#End_CreateTime"),
		"like" => array("ca#Title")
	);

	public function listAction()
	{
		//初始化模型
		$columnActivityModel = new Model_Column_Activity();
		$select = $columnActivityModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$select->from('column_activity as ca');
		$select->joinLeft($this->_user_db . '.members as m', 'ca.MemberID = m.MemberID', 'UserName');

		$this->_helper->json($this->listResults($columnActivityModel, $select, "AID"));
	}

	//验证参数
	protected $filter_fields = array(
		"A" => array("AID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"S" => array("Status", "0,1,2,3", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

	/**
	 * 修改状态
	 */
	public function editAction()
	{
		//判断是否为post请求
		if ($this->isPost()) {
			//获取参数
			$this->filterParam();

			$columnActivityModel = new Model_Column_Activity();
			$res = $columnActivityModel->update(array('Status' => $this->_param['Status']), array('AID = ?' => $this->_param['AID']));
			$res === false && $this->failJson("修改失败");

			$this->succJson();
		} else {
			//获取参数
			$this->filterParam();
			//
			$this->view->aid = $this->_param['AID'];
			$this->view->status = $this->_param['Status'];
		}
	}


	/**
	 * 广告链接
	 */
	public function adsLinkAction()
	{
		$a_id = intval($this->_getParam('AID',0));
		$h5Url = $this->_request->getScheme().'://'.$this->_request->getHttpHost()."/api/public/activity-detail?activityID=";
		$schemaUrl = "caizhu://caizhu/viewList?id=";
		$this->view->h5Url=$h5Url.$a_id;
		$this->view->schemaUrl = $schemaUrl.$a_id;
	}
}