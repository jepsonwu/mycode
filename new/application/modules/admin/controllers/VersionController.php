<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_VersionController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
	}

	public function listAction()
	{
		//初始化模型
		$versionModel = new Model_System_Version();
		$select = $versionModel->select()->setIntegrityCheck(false);

		$this->_helper->json($this->listResults($versionModel, $select, "Platform"));
	}

	//验证参数
	protected $filter_fields = array(
		"B" => array("Button", "require", 'button内容不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"I" => array("Info", "require", '更新详情不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"M" => array("MinVersion", "require", '最小版本不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"U" => array("Url", "require", 'url不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"C" => array("CurrentVersion", "require", '当前版本不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"P" => array("Platform", "2,1", '平台类型参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
		"T" => array("UpdateType", "0,1,2", '更新类型参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
	);

	/**
	 * 编辑
	 */
	public function editAction()
	{
		$versionModel = new Model_System_Version();

		if ($this->isPost()) {
			$this->filterParam();

			$this->_param['Info'] = implode("\n", array_map("trim", explode(PHP_EOL, $this->_param['Info'])));

			$param = array(
				'Button' => $this->_param['Button'],
				'Info' => $this->_param['Info'],
				'MinVersion' => $this->_param['MinVersion'],
				'Url' => $this->_param['Url'],
				'CurrentVersion' => $this->_param['CurrentVersion'],
				'UpdateType' => $this->_param['UpdateType']
			);

			$versionModel->update($param, array("Platform = ?" => $this->_param['Platform']));
			$this->succJson();
		} else {
			//获取参数
			$this->filterParam(array('P'));
			$version_info = $versionModel->find($this->_param['Platform'])->toArray();
			$this->view->version_info = $version_info ? $version_info[0] : array();
		}
	}
}