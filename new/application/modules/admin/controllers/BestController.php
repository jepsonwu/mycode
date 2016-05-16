<?php

/**
 * 达人管理
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_BestController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("b#Status",),
		"bet" => array("b#Start_CreateTime", "b#End_CreateTime"),
		"like" => array("m#UserName")
	);

	public function listAction()
	{
		//初始化模型
		$bestModel = new Model_Best_Best();
		$select = $bestModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$select->from('best as b');
		$select->joinLeft($this->_user_db . '.members as m', 'b.MemberID = m.MemberID', array('m.UserName', "UNIX_TIMESTAMP(b.UpdateTime) as update_time"));
		$select->joinLeft('best_title as t', 't.TID = b.TID', 'Name as TitleName');

		$this->_helper->json($this->listResults($bestModel, $select, "BID"));
	}

	public function approveLinkAction()
	{
		$this->filterParam(array("b"));
		$this->view->h5_url = $this->inviteUrl();
	}

	/**
	 * 重复邀请
	 */
	public function repeatInviteAction()
	{
		$this->filterParam(array("b"));
		$this->inviteUrl(true);
		parent::succJson();
	}

	/**
	 * 取消头衔
	 * 支持多个
	 */
	public function cancelAction()
	{
		$this->filterParam(array("bs", "bt"));

		$type = $this->_param['type'];

		$bestModel = new Model_Best_Best();
		$bid = explode(",", trim($this->_param['BID'], ","));
		$best_info = $bestModel->getCancelInfo($bid, $type == 1 ? $bestModel::STATUS_TRUE : $bestModel::STATUS_CANCEL);

		if (!$best_info)
			parent::failJson("请选择头衔");

		if (count($best_info) != count($bid))
			parent::failJson("头衔不能撤销");

		$member_id = $title_id = array();
		foreach ($best_info as $val) {
			$member_id[] = $val['MemberID'];
			$title_id[] = $val['TID'];
		}
		//只能同时取消一个人的头衔
		if (count(array_unique($member_id)) > 1)
			parent::failJson("只能同时取消一个会员的头衔");


		//修改状态
		$res = $bestModel->update(array(
				"Status" => $bestModel::STATUS_CANCEL,
				"UpdateTime" => date("Y-m-d H:i:s"))
			, array("BID in(?)" => $bid));

		!$res && parent::failJson("取消失败");

		$memberModel = new DM_Model_Account_Members();
		$memberModel->deleteCache(current($member_id));

		//发送链接
		$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
		$bestTitleModel = new Model_Best_BestTitle();
		$title_name = $bestTitleModel->getInfoByID($title_id, "Name");
		count($title_id) > 1 && $title_name = array_map("current", $title_name);
		$title_name = implode(",", $title_name);

		$content = "财猪收到了您的撤销{$title_name}头衔申请，请确认此申请由您本人发出！";
		$url = $this->getFullHost() . "/api/public/cancel-topman";
		$url .= "?bID=" . implode(",", $bid) . "&info=" . $bestModel->getHash($best_info) .
			"&tID=" . implode(",", $title_id) . "&tName=" . urlencode($title_name) . "&caizhunotshowright=1&timestamp=" . time();

		$ext = array(
			"share_chat_msg_type" => 104,
			"share_chat_msg_type_desc" => $content,
			"share_chat_msg_type_id" => $url,
			"share_chat_msg_type_image" => "http://img.caizhu.com/caizhu-log-180_180.png",
			"share_chat_msg_type_name" => "达人头衔撤销",
			"share_chat_msg_type_title" => "达人头衔撤销",
			"share_chat_msg_type_url" => $url,
		);

		$easeModel = new Model_IM_Easemob();
		$easeModel->yy_hxSend(array($best_info[0]['MemberID']), '你的财猪达人头衔将被撤销', 'txt', 'users', $ext, $sysMemberID);

		parent::succJson();
	}

	private function inviteUrl($send = false)
	{
		$url = $this->getFullHost() . "/api/public/topman";

		$bestModel = new Model_Best_Best();
		$best_info = $bestModel->getByID($this->_param['BID']);

		$bestTitleModel = new Model_Best_BestTitle();
		$title_name = $bestTitleModel->getInfoByID($best_info['TID'], "Name");
		$url .= "?bID={$this->_param['BID']}&info=" . $bestModel->getHash($best_info) .
			"&tID={$best_info['TID']}&tName=" . urlencode($title_name['Name']) . "&caizhunotshowright=1";

		//发送链接
		if ($send) {
			$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
			$content = "点亮财猪达人标识即可获得更多达人权限！立刻进入！";
			$ext = array(
				"share_chat_msg_type" => 104,
				"share_chat_msg_type_desc" => $content,
				"share_chat_msg_type_id" => $url,
				"share_chat_msg_type_image" => "http://img.caizhu.com/caizhu-log-180_180.png",
				"share_chat_msg_type_name" => "达人头衔邀请",
				"share_chat_msg_type_title" => "达人头衔邀请",
				"share_chat_msg_type_url" => $url,
			);

			$easeModel = new Model_IM_Easemob();
			$easeModel->yy_hxSend(array($best_info['MemberID']), '邀请你成为财猪达人', 'txt', 'users', $ext, $sysMemberID);
		}

		return $url;
	}

	//验证参数
	protected $filter_fields = array(
		"b" => array("BID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"bs" => array("BID", "/([\d]+[,]?)+/", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"bt" => array("type", "1,2", "参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
		"m" => array("MemberID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"t" => array("Type", "1,2", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
		"ti" => array("TID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"s" => array("status", "0,1,2,3", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

}