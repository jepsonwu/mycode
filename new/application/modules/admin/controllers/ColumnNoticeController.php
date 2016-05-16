<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_ColumnNoticeController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
	}

	/**
	 * 查询条件
	 * 等于|区间|模糊匹配
	 * #用来区分表别名和字段
	 * @var array
	 */
	protected $list_where = array(
		"eq" => array("cn#Status"),
		"bet" => array("cn#Start_CreateTime", "cn#End_CreateTime"),
		"like" => array("cn#Title")
	);

	public function listAction()
	{
		//初始化模型
		$columnNoticeModel = new Model_Column_Notice();
		$select = $columnNoticeModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$select->from('column_notice as cn');
		$select->joinLeft($this->_user_db . '.members as m', 'cn.MemberID = m.MemberID', 'UserName');

		$this->_helper->json($this->listResults($columnNoticeModel, $select, "NoticeID"));
	}


	/**
	 * 通用字段过滤参数
	 * 字段|过滤规则|错误提示|验证条件|附加规则|默认值|允许为空(默认是false)|自定函数参数
	 * @var array
	 */
	protected $filter_fields = array(
		"N" => array("NoticeID", "number", "参数错误!", DM_Helper_Filter::MUST_VALIDATE),
		"C" => array("Content", "require", '公告内容不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"T" => array("Title", "require", '公告标题不能为空!', DM_Helper_Filter::MUST_VALIDATE),
		"S" => array("Status", "0,1", '状态参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
		"TY" => array("Type", "1,2", '类型参数错误！', DM_Helper_Filter::MUST_VALIDATE, 'in'),
	);

	/**
	 * 编辑
	 */
	public function editAction()
	{
		//获取参数
		$columnNoticeModel = new Model_Column_Notice();

		if ($this->isPost()) {
			//校验参数
			$this->filterParam();

			$param = array(
				'Title' => $this->_param['Title'],
				'Content' => $this->_param['Content'],
				'Status' => $this->_param['Status'],
				'Type' => $this->_param['Type']
			);

			$columnNoticeModel->update($param, array('NoticeID = ?' => $this->_param['NoticeID']));
			$this->succJson(1);
		} else {
			$this->filterParam(array("N"));
		}

		$column_notice = $columnNoticeModel->find($this->_param['NoticeID'])->toArray();
		$this->escapeVar($column_notice);
		$this->view->column_notice = $column_notice[0];
	}

	/**
	 * 编辑
	 */
	public function addAction()
	{
		$columnNoticeModel = new Model_Column_Notice();
		if ($this->isPost()) {
			$this->filterParam(array('C','T','S','TY'));
			$param = array(
				'MemberID' => 0,
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
		}
	}
}