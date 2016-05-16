<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-14
 * Time: 上午11:13
 */
class Admin_FeatureController extends DM_Controller_Admin
{
	public function indexAction()
	{

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("m#Status"),
		"bet" => array("m#Start_CreateTime", "m#End_CreateTime"),
		"like" => array("m#Name")
	);

	public function listAction()
	{
		//初始化模型
		$featureModel = new Model_Feature();
		$select = $featureModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$select->from('feature_members as m');
		$select->joinLeft('feature_group as g', 'm.GID = g.GID', 'g.Name as GroupName');
		$this->_helper->json($this->listResults($featureModel, $select, "FID"));
	}

	protected $filter_fields = array(
		"f" => array("FID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"g" => array("GID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"n" => array("Name", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"c" => array("Controller", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"a" => array("Action", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"s" => array("Status", "1,0", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

	public function addAction()
	{
		if ($this->isPost()) {
			$this->filterParam(array("n", "s", "a", "c", "g"));
			$featureModel = new Model_Feature();
			$param = array(
				'GID' => $this->_param['GID'],
				'Name' => $this->_param['Name'],
				'Controller' => $this->_param['Name'],
				'Action' => $this->_param['Name'],
				'Status' => $this->_param['Status'],
				'CreateTime' => date('Y-m-d H:i:s')
			);

			$featureModel->insert($param);
			$this->succJson();
		} else {
			$featureGroupModel = new Model_FeatureGroup();
			$this->view->groups = $featureGroupModel->getGroups();

		}
	}

	public function editAction()
	{
		$featureModel = new Model_Feature();
		if ($this->isPost()) {
			$this->filterParam();
			$param = array(
				'GID' => $this->_param['GID'],
				'Name' => $this->_param['Name'],
				'Controller' => $this->_param['Controller'],
				'Action' => $this->_param['Action'],
				'Status' => $this->_param['Status'],
			);

			$featureModel->update($param, array("FID =?" => $this->_param['FID']));
			$this->succJson();
		} else {
			$this->filterParam(array("f"));
			$this->view->feature_info = $featureModel->getInfoByID($this->_param['FID']);

			$featureGroupModel = new Model_FeatureGroup();
			$this->view->groups = $featureGroupModel->getGroups();
		}
	}
}