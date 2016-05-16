<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-14
 * Time: 上午11:13
 */
class Admin_FeatureGroupController extends DM_Controller_Admin
{
	public function indexAction()
	{

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("Status"),
		"bet" => array("Start_CreateTime", "End_CreateTime"),
		"like" => array("Name")
	);

	public function listAction()
	{
		//初始化模型
		$featureGroupModel = new Model_FeatureGroup();
		$select = $featureGroupModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$this->_helper->json($this->listResults($featureGroupModel, $select, "GID"));
	}

	protected $filter_fields = array(
		"g" => array("GID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"n" => array("Name", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"s" => array("Status", "1,0", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

	public function addAction()
	{
		$featureGroupModel = new Model_FeatureGroup();

		if ($this->isPost()) {
			$this->filterParam(array("n", "s"));
			$param = array(
				'Name' => $this->_param['Name'],
				'Status' => $this->_param['Status'],
				'CreateTime' => date('Y-m-d H:i:s')
			);

			$featureGroupModel->insert($param);
			$this->succJson();
		}
	}

	public function editAction()
	{
		$featureGroupModel = new Model_FeatureGroup();

		if ($this->isPost()) {
			$this->filterParam();
			$param = array(
				'Name' => $this->_param['Name'],
				'Status' => $this->_param['Status'],
			);

			$featureGroupModel->update($param, array("GID =?" => $this->_param['GID']));
			$this->succJson();
		} else {
			$this->filterParam(array("g"));
			$this->view->group_info = $featureGroupModel->getInfoByID($this->_param['GID'], array("Name", "Status", "GID"));
		}
	}
}