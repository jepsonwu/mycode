<?php

/**
 * 达人头衔
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_BestTitleController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;

	}

	//查询条件
	protected $list_where = array(
		"bet" => array("Start_CreateTime", "End_CreateTime"),
		"like" => array("Name")
	);

	public function listAction()
	{
		//初始化模型
		$bestTitleModel = new Model_Best_BestTitle();
		$select = $bestTitleModel->select()->setIntegrityCheck(false);

		$this->_helper->json($this->listResults($bestTitleModel, $select, "TID"));
	}

	//验证参数
	protected $filter_fields = array(
		"i" => array("TID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"n" => array("Name", "require", '头衔名称不能为空!', DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 新增头衔
	 */
	public function addAction()
	{
		$bestTitleModel = new Model_Best_BestTitle();

		if ($this->isPost()) {
			$this->filterParam(array('n'));

			$res = $bestTitleModel->fetchRow(array("Name=?" => $this->_param['Name']));
			$res && $this->failJson("头衔已经添加");

			$param = array(
				'Name' => $this->_param['Name'],
				'CreateTime' => date('Y-m-d H:i:s')
			);

			$bestTitleModel->insert($param);
			$this->succJson();
		}
	}

	public function editAction()
	{
		$bestTitleModel = new Model_Best_BestTitle();

		if ($this->isPost()) {
			$this->filterParam();

			$bestTitleModel->update(array("Name" => $this->_param['Name']), array("TID=?" => $this->_param['TID']));
			$this->succJson();
		} else {
			$this->filterParam(array('i'));
			$title_info = $bestTitleModel->fetchRow(array("TID=?" => $this->_param['TID']));
			$this->view->title_info = $title_info;
		}
	}
}