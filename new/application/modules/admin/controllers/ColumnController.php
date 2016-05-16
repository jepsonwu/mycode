<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_ColumnController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("c#CheckStatus"),
		"bet" => array("c#Start_CreateTime", "c#End_CreateTime"),
		"like" => array("c#Title")
	);

	public function listAction()
	{
		//初始化模型
		$columnModel = new Model_Column_Column();
		$select = $columnModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$select->from('column as c');
		$select->joinLeft($this->_user_db . '.members as m', 'c.MemberID = m.MemberID', 'UserName');

		$this->_helper->json($this->listResults($columnModel, $select, "ColumnID"));
	}

	//验证参数
	protected $filter_fields = array(
		"C" => array("ColumnID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"S" => array("CheckStatus", "1,2", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
		"s" => array("status", "0", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
		'M' => array("member_id", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"CN" => array("Content", "require", '公告内容不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"T" => array("Title", "require", '公告标题不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"ST" => array("Status", "0,1", '状态参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
		"TY" => array("Type", "1,2", '类型参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
		"CR" => array("CheckRemark", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE)
	);

	/**
	 * 审核
	 */
	public function checkAction()
	{
		//判断是否为post请求
		if ($this->isPost()) {
			//获取参数
			$this->filterParam(array('C', 'S', 'M', "CR"));

			$columnModel = new Model_Column_Column();
			$retUpdate = $columnModel->update(
				array(
					'CheckStatus' => $this->_param['CheckStatus'],
					'CheckTime' => date('Y-m-d H:i:s'),
					'CheckRemark' => $this->_param['CheckRemark']
				),
				array('ColumnID = ?' => $this->_param['ColumnID']));

			$columnInfo = $columnModel->getColumnInfo($this->_param['ColumnID']);

			$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
			if ($this->_param['CheckStatus'] == 1 && $retUpdate) {
				$subscribeModel = new Model_Column_MemberSubscribe();
				$subscribeModel->addSubscribe($this->_param['ColumnID'], $this->_param['member_id']);

				/*审核通过*/
				$content = '您创建的理财号已通过审核，请登录财猪网（电脑端）进行后续操作。';
				$easeModel = new Model_IM_Easemob();
				$ext['ColumnID'] = $this->_param['ColumnID'];
				$easeModel->yy_hxSend(array($columnInfo['MemberID']), $content, 'txt', 'users', $ext, $sysMemberID);
			} elseif ($this->_param['CheckStatus'] == 2 && $retUpdate) {
				/*审核未通过*/
				$content = '您创建的理财号未通过审核，请登录财猪网（电脑端）进行修改。';
				$easeModel = new Model_IM_Easemob();
				$ext['ColumnID'] = $this->_param['ColumnID'];
				$easeModel->yy_hxSend(array($columnInfo['MemberID']), $content, 'txt', 'users', $ext, $sysMemberID);
			}

			$this->succJson(1);
		} else {
			//获取参数
			$this->filterParam(array('s', 'C', 'M'));
			//
			$this->view->column_id = $this->_param['ColumnID'];
			$this->view->member_id = $this->_param['member_id'];
			$this->view->status = $this->_param['status'];
		}
	}

	/**
	 * 新增公告
	 */
	public function addAction()
	{
		$columnNoticeModel = new Model_Column_Notice();

		if ($this->isPost()) {
			$this->filterParam(array('M', 'CN', 'T', 'TY', 'ST'));
			$param = array(
				'MemberID' => $this->_param['member_id'],
				'Title' => $this->_param['Title'],
				'Content' => $this->_param['Content'],
				'Status' => $this->_param['Status'],
				'Type' => $this->_param['Type'],
				'CreateTime' => date('Y-m-d H:i:s')
			);

			$columnNoticeModel->insert($param);
			$this->succJson(1);
		} else {
			//获取参数
			$this->filterParam(array('M'));
			$this->view->member_id = $this->_param['member_id'];
		}
	}
}