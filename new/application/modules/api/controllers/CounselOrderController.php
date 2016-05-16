<?php

/**
 * 问财订单模块
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-8
 * Time: 下午2:21
 */
class Api_CounselOrderController extends Action_Api
{
	/**
	 * 预约咨询
	 */
	protected $orderConf = array(
		array("cid", "number", "咨询主题有误！", DM_Helper_Filter::MUST_VALIDATE),
		array("consult_desc", "10,300", "咨询问题描述长度为10-300！", DM_Helper_Filter::MUST_VALIDATE, "length"),
		array("meet_city", "/^[\d]{6}?$/", "咨询城市格式不正确！", DM_Helper_Filter::MUST_VALIDATE)
	);

	public function orderAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$orderModel = new Model_Counsel_CounselOrder();

			//验证主题的正确性
			$counselModel = new Model_Counsel_Counsel();
			$counsel_info = $counselModel->isValidCounsel($this->_param['cid'], array("MemberID", "CID", "Status"));

			//不能预约自己的主题
			if ($counsel_info['MemberID'] == $member_id)
				throw new Exception("不能约见自己！");

			//支持城市判断
			$regionModel = new Model_Counsel_CounselSupportRegion();
			$support_city = $regionModel->getInfoMix(array("CID =?" => $counsel_info['CID']), "Code", "All");
			!empty($support_city) && $support_city = array_map("current", $support_city);
			if (!in_array($this->_param['meet_city'], $support_city))
				throw new Exception("该询财主题不支持该城市！");

			//创建一个订单
			$data = array(
				"OrderNo" => DM_Model_Table_Finance_Order::getOrderSn(),
				"BuyerID" => $member_id,
				"SellerID" => $counsel_info['MemberID'],
				"CID" => $counsel_info['CID'],
				"ConsultDesc" => $this->_param['consult_desc'],
				"MeetCity" => $this->_param['meet_city'],
				"OrderStatus" => $orderModel::ORDER_STATUS_NEW,
				"ValidStatus" => $orderModel::ORDER_VALID_TRUE,
				"SellerStatus" => $orderModel::ORDER_SELLER_CONSULTING,
				"LastEvent" => $orderModel::ORDER_EVENT_NULL,
				"Ip" => $this->getRequest()->getClientIp(),
			);
			$result = $orderModel->insert($data);
			if ($result === false)
				throw new Exception("预约咨询失败！");

			//创建事件
			$orderModel->addEvent(time() + $orderModel::ORDER_PAY_NOTICE_TIME, $result, $orderModel::ORDER_TASK_NOTICE_PAY);
			$orderModel->addEvent(time() + $orderModel::ORDER_PAY_OVERDUE_TIME, $result, $orderModel::ORDER_TASK_OVERDUE_PAY);
			parent::succReturn(array("oid" => $result));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 预约付款
	 */
	protected $payConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("pay_password", "/^[\d]{6}$/", "支付密码不正确！", DM_Helper_Filter::EXISTS_VALIDATE)
	);

	public function payAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$counselModel = new Model_Counsel_CounselOrder();

			//密码校验
			$walletModel = new Model_Wallet_Wallet();
			$needCheckPwd = $walletModel->payValidation($member_id);//判断是否需要支付密码验证
			if ($needCheckPwd) {
				if (!isset($this->_param['pay_password']))
					throw new Exception("请输入支付密码！");
				$check = $walletModel->checkPayPasswordAction($member_id, $this->_param['pay_password']);
				if ($check['flag'] < 0)
					$this->returnJson($check['flag'], "支付密码错误！");
			}

			$counselModel->getAdapter()->beginTransaction();
			try {
				$counselModel->changeStatus($this->_param['oid'], $counselModel::ORDER_CHANGE_PAY, array(),
					$member_id, $counselModel::ORDER_FILTER_BUYER);

				$counselModel->getAdapter()->commit();
				parent::succReturn(array());
			} catch (Exception $e) {
				$e->getCode() != 1111 &&
				$counselModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 理财师接单
	 * 档期选择支持时分、跳跃
	 * 如果为跳跃，多个时间用英文逗号分割
	 * start_time,end_time&start_time,end_time
	 *
	 */
	protected $receiveConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("meet_site", "require", "咨询地点不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("meet_site_latitude", "/^[\d]+(\.[\d]+)?$/", "咨询地点纬度格式不正确！", DM_Helper_Filter::MUST_VALIDATE),
		array("meet_site_longitude", "/^[\d]+(\.[\d]+)?$/", "咨询地点经度格式不正确！", DM_Helper_Filter::MUST_VALIDATE),
		array("meet_date", "/^[\d]+,[\d]+$/", "咨询日期格式不正确！", DM_Helper_Filter::MUST_VALIDATE),
	);

	public function receiveAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$orderModel = new Model_Counsel_CounselOrder();
			$orderModel->getAdapter()->beginTransaction();
			try {
				$orderModel->changeStatus($this->_param['oid'], $orderModel::ORDER_CHANGE_RECEIVE, $this->_param, $member_id);

				$orderModel->getAdapter()->commit();
				parent::succReturn(array());
			} catch (Exception $e) {
				$e->getCode() != 1111 &&
				$orderModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 开始咨询
	 *
	 */
	protected $meetingConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("qrcode_hash", "/^[\w]{32}$/", "校验HASH格式不正确！", DM_Helper_Filter::MUST_VALIDATE)
	);

	public function meetingAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;

			$counselModel = new Model_Counsel_CounselOrder();
			$counselModel->getAdapter()->beginTransaction();
			try {
				$counselModel->changeStatus($this->_param['oid'], $counselModel::ORDER_CHANGE_MEET, $this->_param, $member_id);

				$counselModel->getAdapter()->commit();
				$order_info = $counselModel->getInfoMix(array("OID =?" => $this->_param['oid']),
					array("ValidStatus", "OrderStatus", "SellerStatus", "LastEvent")
				);
				$order_info['IsMeeting'] = 1;
				parent::succReturn($order_info);
			} catch (Exception $e) {
				$counselModel->getAdapter()->rollBack();

				//状态有误时返回订单详情
				if ($e->getCode() == 1111) {
					$order_info = $counselModel->getInfoMix(array("OID =?" => $this->_param['oid']),
						array("ValidStatus", "OrderStatus", "SellerStatus", "LastEvent")
					);
					$order_info['IsMeeting'] = 0;
					parent::succReturn($order_info);
				} else {
					parent::failReturn($e->getMessage());
				}
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 订单操作事件
	 * 卖家结束咨询|买家关闭订单
	 */
	protected $orderEventConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("event", "1,2", "请选择操作事件！", DM_Helper_Filter::MUST_VALIDATE, "in")
	);

	public function orderEventAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;

			$counselModel = new Model_Counsel_CounselOrder();
			$event = $role = "";
			switch ($this->_param['event']) {
				case "1":
					$event = $counselModel::ORDER_CHANGE_FINISH;
					$role = $counselModel::ORDER_FILTER_SELLER;
					break;
				case "2":
					$event = $counselModel::ORDER_CHANGE_BUYER_CLOSE;
					$role = $counselModel::ORDER_FILTER_BUYER;
					break;
			}

			$counselModel->getAdapter()->beginTransaction();
			try {
				$counselModel->changeStatus($this->_param['oid'], $event, array(), $member_id, $role);

				$counselModel->getAdapter()->commit();
				parent::succReturn(array());
			} catch (Exception $e) {
				$counselModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 评论
	 */
	protected $commentConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("comment", "2,1000", "评论长度为2-1000！", DM_Helper_Filter::MUST_VALIDATE, "length"),
		array("score", "/^[0|1]?[0-9](\.[0|5])?$/", "评分格式不对！", DM_Helper_Filter::MUST_VALIDATE)
	);

	public function commentAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;

			if ($this->_param['score'] > 10)
				throw new Exception("评分不能大于10分！");

			$counselModel = new Model_Counsel_CounselOrder();
			$counselModel->getAdapter()->beginTransaction();
			try {
				$counselModel->changeStatus($this->_param['oid'], $counselModel::ORDER_CHANGE_COMMENT,
					$this->_param, $member_id, $counselModel::ORDER_FILTER_BUYER);

				$counselModel->getAdapter()->commit();
				parent::succReturn(array());
			} catch (Exception $e) {
				$counselModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 买家取消订单
	 */
	protected $cancelConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("type", "number", "取消原因类型错误！", DM_Helper_Filter::EXISTS_VALIDATE, null, "1"),
		array("reason", "5,500", "取消预约原因字数为5-500！", DM_Helper_Filter::EXISTS_VALIDATE, "length"),
	);

	public function cancelAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;

			$cancelModel = new Model_Counsel_CounselCancelType();
			$is_type = $cancelModel->getInfoMix(array("Type =?" => $this->_param['type']), "Type");
			if (is_null($is_type))
				throw new Exception("取消原因类型错误！");

			$counselModel = new Model_Counsel_CounselOrder();
			$counselModel->getAdapter()->beginTransaction();
			try {
				$counselModel->changeStatus($this->_param['oid'], $counselModel::ORDER_CHANGE_CANCEL,
					$this->_param, $member_id, $counselModel::ORDER_FILTER_BUYER);

				$counselModel->getAdapter()->commit();
				parent::succReturn(array());
			} catch (Exception $e) {
				$counselModel->getAdapter()->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 取消预约原因列表
	 */
	public function cancelTypeAction()
	{
		$cancelModel = new Model_Counsel_CounselCancelType();
		parent::succReturn(array("Rows" => $cancelModel->getInfoMix(array(), array("Type", "Desc"), "All")));
	}

	/**
	 * 获取理财师的时间安排
	 */
	public function scheduleAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$scheduleModel = new Model_Counsel_CounselPlannerSchedule();

			$schedule_info = $scheduleModel->getInfoMix(array(
				"MemberID =?" => $member_id,
				"StartTime >=?" => time() - time() % 86400 - 28800,
			), array("OID", "StartTime", "EndTime"), "All");

			parent::succReturn(array("Rows" => $schedule_info));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}


	/**
	 * 卖家订单
	 *
	 * 状态显示顺序，根据当前角色显示不同文案：
	 *          LastEven
	 *          SellerStatus
	 *          OrderStatus
	 *
	 * 全部
	 * 待接单 1
	 * 待赴约 2
	 * 待结算 3
	 * 已结算 4
	 */
	protected $sellerOrdersConf = array(
		array("status", "1,2,3,4", "订单状态有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "0"),
		array("last_id", "number", "请选择最后查询ID！", DM_Helper_Filter::EXISTS_VALIDATE, null, "0"),
		array("page", "number", "请选择页数！", DM_Helper_Filter::EXISTS_VALIDATE),
		array("pagesize", "number", "请选择每页条数！", DM_Helper_Filter::EXISTS_VALIDATE, null, "10"),
	);

	public function sellerOrdersAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$counselModel = new Model_Counsel_CounselOrder();

			$user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
			$select = $counselModel->select()->setIntegrityCheck(false);
			$select->from("counsel_order_list as o", array("o.OID", "o.OrderNo", "o.ConsultDesc", "o.MeetingTime",
					"o.BuyerID", "o.SellerID", "o.CancelType", "o.CancelReason", "o.MeetSite", "o.OrderStatus",
					"o.ValidStatus", "o.SellerStatus", "o.LastEvent", "o.CreateTime", "o.SettlementAmount")
			);
			$select->joinLeft("{$user_db}.members as m", "o.BuyerID = m.MemberID", array("m.UserName", "m.Avatar", "m.MobileNumber"));
			$select->joinLeft("counsel as s", "o.CID=s.CID", array("s.Title", "s.Duration", "s.Price", "s.CID"));
			$select->joinLeft("region as r", "o.MeetCity=r.Code", array("r.Name as MeetCity", "r.RealCity"));

			//查询条件
			switch ($this->_param['status']) {
				case "1":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_PAYED);
					break;
				case "2":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_RECEIVED);
					break;
				case "3":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("SellerStatus =?", $counselModel::ORDER_SELLER_CLEARING);
					break;
				case "4":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("SellerStatus =?", $counselModel::ORDER_SELLER_DONG);
					break;
				default:
					$select->where("LastEvent !=?", $counselModel::ORDER_EVENT_CLOSE);
					$select->where("OrderStatus !=?", $counselModel::ORDER_STATUS_NEW);
					break;
			}
			$select->where("SellerID =?", $member_id);

			$result = $this->listResultsNew($counselModel, $select, "o.CreateTime", "o.OID", null, false);

			//过滤
			if (!empty($result['Rows'])) {
				$cancelModel = new Model_Counsel_CounselCancelType();
				$supportModel = new Model_Counsel_CounselSupportRegion();
				foreach ($result['Rows'] as &$val) {
					if ($val['CancelType'] > 0)
						$val['CancelType'] = $cancelModel->getInfoMix(array("Type =?" => $val['CancelType']), "Desc");
					else
						$val['CancelType'] = "";

					$val['SupportCity'] = str_replace(",", " ", $supportModel->getCityNameByCID($val['CID']));
					//$val['MobileNumber'] = substr($val['MobileNumber'], 0, 3) . "****" . substr($val['MobileNumber'], -4);
				}

				unset($val);
			}

			parent::succReturn($result);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}


	/**
	 * 买家订单
	 * 全部
	 * 待付款
	 * 待接单
	 * 待赴约
	 * 待评价
	 */
	protected $buyerOrdersConf = array(
		array("status", "1,2,3,4", "订单状态有误！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "0"),
		array("last_id", "number", "请选择最后查询ID！", DM_Helper_Filter::EXISTS_VALIDATE, null, "0"),
		array("pagesize", "number", "请选择每页条数！", DM_Helper_Filter::EXISTS_VALIDATE, null, "10"),
	);

	public function buyerOrdersAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$counselModel = new Model_Counsel_CounselOrder();

			$user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
			$select = $counselModel->select()->setIntegrityCheck(false);
			$select->from("counsel_order_list as o", array("o.OID", "o.OrderNo", "o.ConsultDesc", "o.MeetingTime",
					"o.CancelType", "o.CancelReason", "o.MeetSite", "o.OrderStatus", "o.ValidStatus", "o.SellerStatus",
					"o.LastEvent", "o.CreateTime", "o.BuyerID", "o.SellerID")
			);
			$select->joinLeft("{$user_db}.members as m", "o.SellerID = m.MemberID", array("m.RealName", "m.UserName", "m.MobileNumber"));
			$select->joinLeft("counsel as s", "o.CID=s.CID", array("s.Title", "s.Duration", "s.Price"));
			$select->joinLeft("region as r", "o.MeetCity=r.Code", array("r.Name as MeetCity", "r.RealCity"));
			$select->joinLeft("financial_planner_info as p", "o.SellerID=p.MemberID", array("p.Photo as Avatar"));

			//查询条件
			switch ($this->_param['status']) {
				case "1":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_NEW);
					break;
				case "2":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_PAYED);
					break;
				case "3":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_RECEIVED);
					break;
				case "4":
					$select->where("ValidStatus =?", $counselModel::ORDER_VALID_TRUE);
					$select->where("OrderStatus =?", $counselModel::ORDER_STATUS_COMMENT);
					break;
				default:
					$select->where("LastEvent !=?", $counselModel::ORDER_EVENT_CLOSE);
					break;
			}
			$select->where("BuyerID =?", $member_id);

			$result = $this->listResults($counselModel, $select, "o.CreateTime", "o.OID", null, false);

			//过滤
			if (!empty($result)) {
				$cancelModel = new Model_Counsel_CounselCancelType();
				foreach ($result as &$val) {
					if ($val['CancelType'] > 0)
						$val['CancelType'] = $cancelModel->getInfoMix(array("Type =?" => $val['CancelType']), "Desc");
					else
						$val['CancelType'] = "";
				}

				unset($val);
			}

			parent::succReturn(array("Rows" => $result));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 订单详情
	 * 当前角色根据会员ID来判断
	 *
	 */
	protected $orderInfoConf = array(
		array("oid", "number", "订单号不能为空！", DM_Helper_Filter::MUST_VALIDATE),
	);


	public function orderInfoAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$orderModel = new Model_Counsel_CounselOrder();

			//订单信息
			$order_fileds = array("OID", "CID", "OrderNo", "ConsultDesc", "MeetSite", "MeetTime", "MeetingTime",
				"OrderStatus", "BuyerID", "SellerID", "ValidStatus", "SellerStatus", "LastEvent", "CreateTime",
				"CancelType", "CancelReason", "QrCodeUrl", "MeetCity",
			);
			$result = $orderModel->getInfoMix(array("OID =?" => $this->_param['oid']), $order_fileds);
			if (!is_null($result)) {
				//当前角色判断
				$role = 0;
				if ($member_id == $result['BuyerID']) {
					$role = 2;
				} elseif ($member_id == $result['SellerID']) {
					$role = 1;
				} else {
					throw new Exception("不存在该订单！");
				}
				$result['Role'] = $role;

				//主题详情
				$counselModel = new Model_Counsel_Counsel();
				$counsel_info = $counselModel->getInfoMix(array("CID =?" => $result['CID']), array("Title", "Duration", "Price"));
				$result = array_merge($result, $counsel_info);

				//会员详情
				$memberModel = new DM_Model_Account_Members();
				$member_field = array("UserName", "RealName", "MobileNumber");
				if ($role == 1) {
					$member_field[] = "Avatar";
				} else {
					$financialModel = new Model_Financial_FinancialPlannerInfo();
					$result['Avatar'] = $financialModel->getInfoMix(array("MemberID =?" => $result['SellerID']), "Photo");
				}
				$member_info = $memberModel->getInfoMix(array("MemberID =?" => ($role == 1 ? $result['BuyerID'] : $result['SellerID'])), $member_field);
				$result = array_merge($result, $member_info);

				//角色特殊字段
				if ($role == 2) {//买家
					//$result['MobileNumber'] = substr($result['MobileNumber'], 0, 3) . "****" . substr($result['MobileNumber'], -4);
					!empty($result['QrCodeUrl']) &&
					$result['QrCodeUrl'] = "http://img.caizhu.com/" . $result['QrCodeUrl'];
				} else {
					$result['QrCodeUrl'] = "";
				}

				//预约城市显示中文
				$regionModel = new Model_Region();
				$code = $result['MeetCity'];
				$result['MeetCity'] = $regionModel->getInfoMix(array("Code =?" => $code), "Name");
				$result["RealCity"] = $regionModel->getInfoMix(array("Code =?" => $code), "RealCity");

				//取消原因显示中文
				$cancelModel = new Model_Counsel_CounselCancelType();
				if ($result['CancelType'] > 0)
					$result['CancelType'] = $cancelModel->getInfoMix(array("Type =?" => $result['CancelType']), "Desc");
				else
					$result['CancelType'] = "";
			} else {
				$result = array();
			}

			parent::succReturn($result);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $commentsConf = array(
		array("seller_id", "number", "理财师信息不存在！", DM_Helper_Filter::EXISTS_VALIDATE),
		array("cid", "number", "咨询主题信息不存在！", DM_Helper_Filter::EXISTS_VALIDATE),
		array("last_id", "number", "请选择最后查询ID！", DM_Helper_Filter::EXISTS_VALIDATE, null, "0"),
		array("page", "number", "请选择页数！", DM_Helper_Filter::EXISTS_VALIDATE),
		array("pagesize", "number", "请选择每页条数！", DM_Helper_Filter::EXISTS_VALIDATE, null, "30"),
	);

	/**
	 *理财师的评论列表 seller_id
	 *主题的评论列表 cid
	 *默认卖家理财师的评论列表
	 */
	public function commentsAction()
	{
		$this->isLoginOutput();
		try {
			mb_internal_encoding("UTF-8");

			$member_id = $this->memberInfo->MemberID;
			$user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;

			$commentModel = new Model_Counsel_CounselOrderComment();
			$select = $commentModel->select()->setIntegrityCheck(false);
			//查询备注信息
			$select->from("counsel_order_comment as c");

			$seller_realname = "";
			$seller_id = 0;
			$member_info = array("m.UserName", "m.Avatar");
			if (isset($this->_param['seller_id'])) {//理财师的评论列表
				$select->where("c.SellerID =?", $this->_param['seller_id']);
				$seller_id = $this->_param['seller_id'];
			} elseif (isset($this->_param['cid'])) {//主题的评论列表
				$select->where("c.CID =?", $this->_param['cid']);
			} else {//卖家理财师的评论列表
				//判断是否为理财师
				$select->where("c.SellerID =?", $member_id);
				$seller_id = $member_id;
				$member_info[] = "m.MobileNumber";
			}

			$memberModel = new DM_Model_Account_Members();
			if (!isset($this->_param['cid'])) {
				$seller_realname = $memberModel->getInfoMix(array("MemberID =?" => $seller_id), "RealName");
			}

			$select->joinLeft("counsel_order_list as l", "c.OID=l.OID", array("l.OrderNo"));
			$select->joinLeft("{$user_db}.members as m", "c.BuyerID = m.MemberID", $member_info);
			$select->joinLeft("counsel as s", "c.CID=s.CID", "s.Title");

			$result = $this->listResultsNew($commentModel, $select, "c.CreateTime", true, "c.OCID", false);

			//过滤
			if (!empty($result['Rows'])) {
				//username 隐藏中间字
				$is_hide = false;
				if ((isset($this->_param['seller_id']) && $this->_param['seller_id'] != $member_id)
					|| isset($this->_param['cid'])
				)
					$is_hide = true;

				foreach ($result['Rows'] as &$val) {
					if ($is_hide && $val['BuyerID'] != $member_id && $val['SellerID'] != $member_id) {
						$val['UserName'] = mb_substr($val['UserName'], 0, 1) . "***" .
							(mb_strlen($val['UserName']) <= 2 ? "" : mb_substr($val['UserName'], -1));
					}

					if (empty($seller_realname)) {
						$val['SellerRealName'] = $memberModel->getInfoMix(array("MemberID =?" => $val['SellerID']), "RealName");
					} else {
						$val['SellerRealName'] = $seller_realname;
					}
				}

				unset($val);

			}
			parent::succReturn($result);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $replyCommentConf = array(
		array("ocid", "number", "评论ID不能为空！", DM_Helper_Filter::MUST_VALIDATE),
		array("comment", "2,500", "回复评论长度为2-500！", DM_Helper_Filter::MUST_VALIDATE, "length")
	);

	/**
	 * 回复评论
	 */
	public function replyCommentAction()
	{
		$this->isLoginOutput();
		try {
			$member_id = $this->memberInfo->MemberID;
			$commentModel = new Model_Counsel_CounselOrderComment();

			$comment_info = $commentModel->getInfoMix(array("OCID =?" => $this->_param['ocid']), array("OCID", "SellerID", "ReplyComment"));
			if (is_null($comment_info) || $comment_info['SellerID'] != $member_id || !empty($comment_info['ReplyComment']))
				throw new Exception("无法回复评论！");

			$result = $commentModel->update(
				array(
					"ReplyComment" => $this->_param['comment'],
					"UpdateTime" => date("Y-m-d H:i:s", time())
				), array("OCID =?" => $comment_info['OCID']));
			if ($result === false)
				throw new Exception("回复失败！");

			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}
}