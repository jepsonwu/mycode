<?php

/**
 * 问财订单模块
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-8
 * Time: 下午2:39
 */
class Model_Counsel_CounselOrder extends Model_Common_Common
{
	protected $_name = 'counsel_order_list';
	protected $_primary = 'OID';

	//结算佣金系数
	const ORDER_SETTLE_COMMISSION = 1;

	//结算比例，除非人为干预都为100
	const ORDER_SETTLE_PERCENT = 100;

	// 默认结算时间三天
	const ORDER_POSTPONE_TIME = 259200;

	//一个小时未支付则过期 过期前十五分钟提醒
	const ORDER_PAY_OVERDUE_TIME = 3600;
	const ORDER_PAY_NOTICE_TIME = 2700;

	//24小时未接单则过期
	const ORDER_RECEIVE_OVERDUE_TIME = 86400;

	//距离咨询前2小时通知
	const ORDER_MEET_NOTICE_TIME = 7200;
	//预计开始咨询30分钟后通知理财师
	const ORDER_MEET_OVERDUE_NOTICE_SELLER_TIME = 1800;

	//预计开始咨询45分钟后通知客服
	const ORDER_MEET_OVERDUE_NOTICE_SERVICE_TIME = 2700;

	//咨询结束后三小时通知
	const ORDER_FINISH_NOTICE_TIME = 10800;

	//订单状态：1-待付款，2-待接单，3-待赴约， 4-赴约中，5-待评价，6-完成
	const ORDER_STATUS_NEW = 1;
	const ORDER_STATUS_PAYED = 2;
	const ORDER_STATUS_RECEIVED = 3;
	const ORDER_STATUS_MEETING = 4;
	const ORDER_STATUS_COMMENT = 5;
	const ORDER_STATUS_DONE = 6;

	//订单有效性：0-关闭，1-有效
	const ORDER_VALID_CLOSE = 0;
	const ORDER_VALID_TRUE = 1;

	//订单卖家资金状态：1-咨询中，2-待结算，3-已结算
	const ORDER_SELLER_CONSULTING = 1;
	const ORDER_SELLER_CLEARING = 2;
	const ORDER_SELLER_DONG = 3;

	//订单最后操作事件类型：0-null,1-买家取消，2-买家关闭,3-支付过期,4-接单过期,（订单关闭事件）5-延期结算（特殊情况，订单未关闭）6-赴约过期 7-拒绝结算
	const ORDER_EVENT_NULL = 0;
	const ORDER_EVENT_CANCEL = 1;
	const ORDER_EVENT_CLOSE = 2;
	const ORDER_EVENT_PAY_OVERDUE = 3;
	const ORDER_EVENT_RECEIVE_OVERDUE = 4;
	const ORDER_EVENT_POSTPONE_CLEAR = 5;
	const ORDER_EVENT_MEET_OVERDUE = 6;//人工处理退款
	const ORDER_EVENT_REFUSE_CLEAR = 7;//拒绝结算

	//订单和会员有效性判断类型
	const ORDER_FILTER_SELLER = 1;
	const ORDER_FILTER_BUYER = 2;
	const ORDER_FILTER_SYSTEM = 3;

	//订单状态操作类型  日志延用此类型
	const ORDER_CHANGE_PAY = 1;//订单流程
	const ORDER_CHANGE_RECEIVE = 2;
	const ORDER_CHANGE_MEET = 3;
	const ORDER_CHANGE_FINISH = 4;//完成咨询，卖家开始结算
	const ORDER_CHANGE_COMMENT = 5;
	const ORDER_CHANGE_CANCEL = 6;//取消流程
	const ORDER_CHANGE_BUYER_CLOSE = 7;//买家关闭订单
	const ORDER_CHANGE_SELLER_DONE = 8;//完成结算
	const ORDER_CHANGE_SELLER_SERVICE_DONE = 9;//客服按比例结算
	const ORDER_CHANGE_SELLER_REFUSED = 10;//拒绝结算
	const ORDER_CHANGE_SELLER_POSTPONE = 11;//延期
	const ORDER_CHANGE_PAY_OVERDUE = 12;//未支付过期
	const ORDER_CHANGE_RECEIVE_OVERDUE = 13;//未接单过期
	const ORDER_CHANGE_MEET_OVERDUE = 14;//赴约过期

	//操作类型对应的备注
	protected $changeRemark = array(
		self::ORDER_CHANGE_PAY => "订单已支付",
		self::ORDER_CHANGE_RECEIVE => "订单已接单",
		self::ORDER_CHANGE_MEET => "订单开始咨询",
		self::ORDER_CHANGE_FINISH => "订单结束咨询",
		self::ORDER_CHANGE_COMMENT => "订单已评论",
		self::ORDER_CHANGE_CANCEL => "买家取消订单",
		self::ORDER_CHANGE_BUYER_CLOSE => "订单已关闭",
		self::ORDER_CHANGE_SELLER_DONE => "订单已结算，结算比例：@commission@",
		self::ORDER_CHANGE_SELLER_SERVICE_DONE => "客服结算，结算比例：@commission@",
		self::ORDER_CHANGE_SELLER_REFUSED => "订单拒绝结算，费用已退还咨询者，拒绝原因：@reason@",
		self::ORDER_CHANGE_SELLER_POSTPONE => "订单延期结算，延期时间：@postpone_time@天，延期原因：@reason@",
		self::ORDER_CHANGE_PAY_OVERDUE => "未在1个小时支付，已过期",
		self::ORDER_CHANGE_RECEIVE_OVERDUE => "未在24小时接单，已过期",
		self::ORDER_CHANGE_MEET_OVERDUE => "未在指定时间赴约，已过期",
	);

	//消息类型
	const ORDER_MESSAGE_WAITING_PAY = 1;
	const ORDER_MESSAGE_PAYED = 2;
	const ORDER_MESSAGE_NO_RECEIVE = 3;
	const ORDER_MESSAGE_RECEIVED = 4;
	const ORDER_MESSAGE_WAITING_MEET = 5;//2个小时通知  通知开始咨询
	const ORDER_MESSAGE_MEETING = 6;//达到时间通知
	const ORDER_MESSAGE_OVERDUE_MEET_SELLER = 7;//通知创建者
	const ORDER_MESSAGE_OVERDUE_MEET_SERVICE = 8;//通知客服
	const ORDER_MESSAGE_WAITING_FINISH = 9;
	const ORDER_MESSAGE_FINISH = 10;
	const ORDER_MESSAGE_CANCEL = 11;
	const ORDER_MESSAGE_COMMENT = 12;
	const ORDER_MESSAGE_DOING_MEET = 13;//开始咨询
	const ORDER_MESSAGE_REFUSED_SETTLEMENT = 14;//拒绝结算
	const ORDER_MESSAGE_POSTPONE_SETTLTMENT = 15;//延期结算
	const ORDER_MESSAGE_PERCENT_SETTLEMENT = 16;//客服比例结算
	const ORDER_MESSAGE_OVERDUE_MEET = 17;//赴约过期

	/**
	 *
	 * b：买家 s：卖家  @string@：需要解析部分 b@代表为短信发送
	 * @var array
	 */
	protected $messageRemark = array(
		self::ORDER_MESSAGE_WAITING_PAY =>
			array("b" => "您有待付款的订单哦，请及时付款，如未操作，订单将在15分钟后取消。"),
		self::ORDER_MESSAGE_PAYED =>
			array("s" => "@buyer_name@购买了你的询财，请及时接单。超过24小时未接单，订单将自动关闭。"),
		self::ORDER_MESSAGE_NO_RECEIVE =>
			array("b" => "@seller_name@24小时内未接单，订单已自动关闭，费用正在退还，请稍后在财猪钱包-资金记录中查看。",),
		self::ORDER_MESSAGE_RECEIVED =>
			array("b" => "@seller_name@已接受你的询财订单。咨询时间：@MeetTime@，咨询地点：@MeetSite@，请准时到达咨询地点。"),

		self::ORDER_MESSAGE_WAITING_MEET =>
			array("s" => "距离咨询时间还有2小时，请于@MeetTime@（时间）到达@MeetSite@（地点）哦。",
				"b" => "距离咨询时间还有2小时，请于@MeetTime@（时间）到达@MeetSite@（地点）哦。"),
		self::ORDER_MESSAGE_MEETING => array(
			"s" => "咨询时间已开始，请使用财猪扫描对方订单详情中的二维码，此为咨询开始的唯一凭证，如未扫描，将无法收取咨询费用。"),
		self::ORDER_MESSAGE_OVERDUE_MEET_SELLER => array(
			"@s" => "财猪未检测到咨询开始，咨询费用将于72小时后退还咨询者，如咨询已经开始请使用财猪扫描对方订单详情中的二维码。如有疑问，请与财猪客服联系400-681-2858"
		),
		self::ORDER_MESSAGE_OVERDUE_MEET_SERVICE =>//通知客服
			array("@s" => "有异常订单需要处理，订单号@OID@"),

		self::ORDER_MESSAGE_WAITING_FINISH =>
			array("s" => "您还有未结束的咨询服务订单，如服务已完成，请点击订单详情中的结束按钮。咨询费用将在点击结束按钮72小时后结算。"),
		self::ORDER_MESSAGE_FINISH =>
			array("b" =>
				array("lg" => "咨询服务已结束，此次咨询服务延时@lg_time@分钟，给@seller_name@个好评吧",
					"eq" => "咨询服务已结束，如果你满意此次服务，可以给@seller_name@个好评哦"),
				"s" => "咨询服务已结束，费用将于72小时内到账财猪钱包"),
		self::ORDER_MESSAGE_CANCEL =>
			array("s" =>
				array("no" => "@buyer_name@取消订单，点击查看具体原因",
					"yes" => "由于@buyer_name@取消订单，您将获得补偿金@DamagesAmount@元，请稍后在财猪钱包-资金记录中查看。")),
		self::ORDER_MESSAGE_COMMENT => array(
			"s" => "你的询财收到了新的评价，去看看吧"
		),
		self::ORDER_MESSAGE_DOING_MEET => array(
			"b" => ""
		),
		self::ORDER_MESSAGE_REFUSED_SETTLEMENT => array(
			"s" => "因为@reason@导致订单结算失败，订单费用将退还@buyer_name@",
		),
		self::ORDER_MESSAGE_POSTPONE_SETTLTMENT => array(
			"s" => "因为@reason@导致订单推迟（@postpone_time@天）结算",
		),
		self::ORDER_MESSAGE_PERCENT_SETTLEMENT => array(
			"s" => "因为@reason@导致订单将按照（@commission@%比例）结算，请稍后查看财猪钱包",
		),
		self::ORDER_MESSAGE_OVERDUE_MEET => array(
			"s" =>
				array("eq" => "因为@reason@导致订单结算失败，订单费用将退还@buyer_name@",
					"lg" => "因为@reason@导致订单将按照（@commission@%比例）结算，请稍后查看财猪钱包"),
		),
	);

	//web socket httpsqs key
	const ORDER_SOCKET_NOTICE_KEY = 'web_socket_notice';

	//系统任务事件
	const ORDER_TASK_NOTICE_PAY = 1;//提醒支付
	const ORDER_TASK_OVERDUE_PAY = 2;//支付过期
	const ORDER_TASK_OVERDUE_RECEIVE = 3;//接单过期
	const ORDER_TASK_NOTICE_MEET = 4;//提醒赴约
	const ORDER_TASK_NOTICE_MEETING = 5;//现在赴约
	const ORDER_TASK_NOTICE_SELLER_OVERDUE_MEET = 6;//提醒创建者
	const ORDER_TASK_NOTICE_SERVICE_OVERDUE_MEET = 7;//提醒客服
	const ORDER_TASK_NOTICE_FINISH = 8;//提醒结束
	const ORDER_TASK_DEAL_SETTLEMENT = 9;//结算

	//任务key
	const ORDER_TASK_KEY = "counsel_task_key";

	/**
	 * 卖家和买家操作过滤对应关系
	 * @var array
	 */
	protected $handleFilterMap = array(
		self::ORDER_FILTER_SELLER => array(
			self::ORDER_CHANGE_RECEIVE,
			self::ORDER_CHANGE_MEET,
			self::ORDER_CHANGE_FINISH,
		),
		self::ORDER_FILTER_BUYER => array(
			self::ORDER_CHANGE_PAY,
			self::ORDER_CHANGE_COMMENT,
			self::ORDER_CHANGE_CANCEL,
			self::ORDER_CHANGE_BUYER_CLOSE
		),
		self::ORDER_FILTER_SYSTEM => array(
			self::ORDER_CHANGE_SELLER_DONE,
			self::ORDER_CHANGE_SELLER_SERVICE_DONE,
			self::ORDER_CHANGE_SELLER_POSTPONE,
			self::ORDER_CHANGE_SELLER_REFUSED,
			self::ORDER_CHANGE_RECEIVE_OVERDUE,
			self::ORDER_CHANGE_PAY_OVERDUE,
			self::ORDER_CHANGE_MEET_OVERDUE,
		),
	);

	/**
	 * 获取有效的订单信息
	 * 只支持单条
	 * @param $oid |支持自定义条件
	 * @param $member_id
	 * @param int $type
	 * @param null $fields
	 * @param null $change
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public function getOrderInfo($oid, $member_id, $type = self::ORDER_FILTER_SELLER, $fields = null, $change = null)
	{
		if (is_string($oid))
			$oid = array("OID =?" => $oid);

		$order_info = $this->getInfoMix($oid, $fields);
		if (is_null($order_info))
			throw new Exception("不存在该订单！");

		$is_valid = true;
		switch ($type) {
			case self::ORDER_FILTER_SELLER:
				$order_info['SellerID'] != $member_id && $is_valid = false;
				break;
			case self::ORDER_FILTER_BUYER:
				$order_info['BuyerID'] != $member_id && $is_valid = false;
				break;
			case self::ORDER_FILTER_SYSTEM:
				break;
		}

		if (!$is_valid) {
			switch ($change) {
				case self::ORDER_CHANGE_MEET:
					$message = "该二维码不是来自你的询财订单";
					break;
				default:
					$message = "不能操作别人的订单！";
					break;
			}
			throw new Exception($message);
		}
		return $order_info;
	}

	/**
	 * 安全的操作订单
	 * 强烈建议使用此方法
	 * @param $oid
	 * @param $event |操作事件
	 * @param array $addinfo 追加的信息
	 * @param $member_id
	 * @param int $type |当前角色状态（卖家|买家|系统）
	 * @return bool
	 * @throws Exception
	 *
	 */
	public function changeStatus($oid, $event, $addinfo = array(), $member_id, $type = self::ORDER_FILTER_SELLER)
	{
		//支持自定义修改条件
		if (is_string($oid))
			$oid = array("OID =?" => $oid);

		//订单有效性过滤
		$order_info = $this->getOrderInfo($oid, $member_id, $type, null, $event);

		//操作过滤
		$this->handleFilter($event, $type);

		//订单表修改数据
		$order_data = array();
		$message_event = 10000;
		$message_ext = array();
		$log_info = null;
		switch ($event) {
			//订单流程
			case self::ORDER_CHANGE_PAY:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_NEW
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("订单不允许支付！");

				//是否过期
				if (strtotime($order_info['CreateTime']) + self::ORDER_PAY_OVERDUE_TIME < time()) {
					$this->getAdapter()->rollBack();
					//这里存在嵌套事物
					$this->getAdapter()->beginTransaction();
					try {
						$this->changeStatus($order_info['OID'], self::ORDER_CHANGE_PAY_OVERDUE, array(), 0, self::ORDER_FILTER_SYSTEM);
						$this->getAdapter()->commit();
					} catch (Exception $e) {
						$this->getAdapter()->rollBack();
					}
					throw new Exception("未在1小时内付款，订单已关闭！", 1111);
				}

				$order_data['OrderStatus'] = self::ORDER_STATUS_PAYED;

				//添加二维码
				$content = array(
					"oid" => $order_info['OID'],
					"qrcode_hash" => md5(DM_Helper_String::randString(10))//识别hash
				);

				$order_data['QrCodeUrl'] = $this->createQrCode(json_encode($content));
				$order_data['QrCodeHash'] = $content['qrcode_hash'];
				$order_data['PayTime'] = date("Y-m-d H:i:s", time());

				//付款
				$wallet_order_id = $this->settlement($order_info, "BuyerID", "PAY", "询财订单");

				//发钱包消息
				$messageModel = new Model_Message();
				$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_PAY, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

				//关注理财号
				$columnModel = new Model_Column_Column();
				$column_id = $columnModel->getMyColumnInfo($order_info['SellerID']);
				$subscribeModel = new Model_Column_MemberSubscribe();
				$result = $subscribeModel->addSubscribe($column_id['ColumnID'], $order_info['BuyerID']);
				if ($result === false)
					throw new Exception("付款失败！");

				//message
				$message_event = self::ORDER_MESSAGE_PAYED;

				//event
				$this->addEvent(time() + self::ORDER_RECEIVE_OVERDUE_TIME, $order_info['OID'], self::ORDER_TASK_OVERDUE_RECEIVE);
				break;
			case self::ORDER_CHANGE_RECEIVE:
				if ($order_info['ValidStatus'] == self::ORDER_VALID_CLOSE
					&& $order_info['LastEvent'] == self::ORDER_EVENT_CANCEL
				)
					throw new Exception("对方已取消订单！");//todo 通用提示

				if ($order_info['OrderStatus'] != self::ORDER_STATUS_PAYED
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("订单不允许接受！");

				//档期判断
				$scheduleModel = new Model_Counsel_CounselPlannerSchedule();

				//根据主题时长计算结束时间
				$counselModel = new Model_Counsel_Counsel();
				$duration = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Duration");
				$addinfo['meet_date'] = array(explode(",", $addinfo['meet_date']));
				$meet_time_date =& $addinfo['meet_date'][0];
				if (strlen(date("Y-m-d H:i:s", $meet_time_date[0])) > 19
					|| $meet_time_date[0] < time()
				)
					throw new Exception("无效的时间");
				$meet_time_date[1] = $meet_time_date[0] + $duration * 60 * 60 - 1;

				//校验时间是否可选  StartTime<=start<=EndTime  OR  StartTime<=end<=EndTime
				$result = $scheduleModel->getInfoMix(array(
					"MemberID =?" => $order_info['SellerID'],
					"(StartTime <= {$meet_time_date[0]} and EndTime >= {$meet_time_date[0]}) OR (StartTime <= {$meet_time_date[1]} and EndTime >= {$meet_time_date[1]})" => ""
				), "PSID");
				if (!is_null($result))
					throw new Exception("该时间段已经被预约！");

				//是否过期
				if (strtotime($order_info['PayTime']) + self::ORDER_RECEIVE_OVERDUE_TIME < time()) {
					$this->getAdapter()->rollBack();
					//这里存在嵌套事物
					$this->getAdapter()->beginTransaction();
					try {
						$this->changeStatus($order_info['OID'], self::ORDER_CHANGE_RECEIVE_OVERDUE, array(), 0, self::ORDER_FILTER_SYSTEM);
						$this->getAdapter()->commit();
					} catch (Exception $e) {
						$this->getAdapter()->rollBack();
					}
					throw new Exception("未在24小时接单，已过期！", 1111);
				}

				//咨询时间和地点
				$order_data['OrderStatus'] = self::ORDER_STATUS_RECEIVED;
				$order_data['MeetSite'] = $addinfo['meet_site'];
				$order_data['MeetSiteLatitude'] = $addinfo['meet_site_latitude'];
				$order_data['MeetSiteLongitude'] = $addinfo['meet_site_longitude'];
				$order_data['MeetTime'] = date("Y-m-d H:i:s", $meet_time_date[0]);
				$order_data['ReceiveTime'] = date("Y-m-d H:i:s", time());
				$order_data['FinishTime'] = date("Y-m-d H:i:s", $meet_time_date[0] + $duration * 60 * 60);

				//档期
				$this->insertSchedule($order_info['OID'], $order_info['SellerID'], $addinfo);

				//message
				$message_event = self::ORDER_MESSAGE_RECEIVED;

				//计算平均接单时间
				$stateModel = new Model_Counsel_CounselSellerState();
				$state_info = $stateModel->getInfoMix(array("MemberID =?" => $order_info['SellerID']), array("ReceiveTotalTime", "ReceiveNum"));
				$receive_total_time = $state_info['ReceiveTotalTime'] + strtotime($order_data['ReceiveTime']) - strtotime($order_info['PayTime']);
				$receive_num = $state_info['ReceiveNum'] + 1;
				$result = $stateModel->update(array(
					"ReceiveAverageTime" => DM_Helper_Utility::halfRound($receive_total_time / $receive_num),
					"ReceiveTotalTime" => $receive_total_time,
					"ReceiveNum" => $receive_num,
				), array("MemberID =?" => $order_info['SellerID']));
				if ($result === false)
					throw new Exception("接单失败！");

				//event
				$this->addEvent(strtotime($order_data['MeetTime']) - self::ORDER_MEET_NOTICE_TIME,
					$order_info['OID'], self::ORDER_TASK_NOTICE_MEET);
				$this->addEvent(strtotime($order_data['MeetTime']), $order_info['OID'], self::ORDER_TASK_NOTICE_MEETING);
				$this->addEvent(strtotime($order_data['MeetTime']) + self::ORDER_MEET_OVERDUE_NOTICE_SELLER_TIME,
					$order_info['OID'], self::ORDER_TASK_NOTICE_SELLER_OVERDUE_MEET);
				$this->addEvent(strtotime($order_data['MeetTime']) + self::ORDER_MEET_OVERDUE_NOTICE_SERVICE_TIME,
					$order_info['OID'], self::ORDER_TASK_NOTICE_SERVICE_OVERDUE_MEET);

				$order_info['MeetSite'] = $order_data['MeetSite'];
				$order_info['MeetTime'] = $order_data['MeetTime'];
				break;
			case self::ORDER_CHANGE_MEET:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_RECEIVED
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("订单不允许开始咨询！", 1111);
				$order_data['OrderStatus'] = self::ORDER_STATUS_MEETING;

				//校验二维码hash
				if ($order_info['QrCodeHash'] !== $addinfo['qrcode_hash'])
					throw new Exception("咨询失败！");

				$counselModel = new Model_Counsel_Counsel();
				$duration = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Duration");
				$order_data['MeetingTime'] = date("Y-m-d H:i:s", time());
				$order_data['FinishTime'] = date("Y-m-d H:i:s", time() + $duration * 60 * 60);

				//event
				$this->addEvent(strtotime($order_data['FinishTime']) + self::ORDER_FINISH_NOTICE_TIME,
					$order_info['OID'], self::ORDER_TASK_NOTICE_FINISH);

				$message_event = self::ORDER_MESSAGE_DOING_MEET;
				break;
			case self::ORDER_CHANGE_FINISH:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_MEETING)
					throw new Exception("订单不允许结束咨询！");
				$order_data['OrderStatus'] = self::ORDER_STATUS_COMMENT;

				//对于卖家该成待结算
				$order_data['SellerStatus'] = self::ORDER_SELLER_CLEARING;
				$order_data['SettlementTime'] = date("Y-m-d H:i:s", time() + self::ORDER_POSTPONE_TIME);

				//更新实际咨询时间
				$order_data['FinishingTime'] = date("Y-m-d H:i:s", time());

				//修改理财师完成交易总人数 待结算金额
				$counselModel = new Model_Counsel_Counsel();
				$price = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Price");
				$stateModel = new Model_Counsel_CounselSellerState();
				$result = $stateModel->update(array(
					"ConsultNum" => new Zend_Db_Expr('ConsultNum +1'),
					"WaitSettlement" => new Zend_Db_Expr("WaitSettlement +{$price}"),
				),
					array("MemberID =?" => $order_info['SellerID']));

				if ($result === false)
					throw new Exception("咨询失败！");

				//修改问财主题的咨询总人数
				$counselModel = new Model_Counsel_Counsel();
				$result = $counselModel->update(array("ConsultTotal" => new Zend_Db_Expr('ConsultTotal + 1'),
					"UpdateTime" => date("Y-m-d H:i:s", time())),
					array("CID =?" => $order_info['CID']));
				if ($result === false)
					throw new Exception("咨询失败！");

				//message
				$order_info['FinishingTime'] = $order_data['FinishingTime'];
				$message_event = self::ORDER_MESSAGE_FINISH;

				//event
				$this->addEvent(strtotime($order_data['SettlementTime']), $order_info['OID'], self::ORDER_TASK_DEAL_SETTLEMENT);

				//如果小于预计开始咨询时间 删除档期
				$this->deleteSchedule($order_info);
				break;
			case self::ORDER_CHANGE_COMMENT:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_COMMENT)
					throw new Exception("订单不允许评论！");
				$order_data['OrderStatus'] = self::ORDER_STATUS_DONE;

				//插入评价
				$comment_data = array(
					"OID" => $order_info['OID'],
					"SellerID" => $order_info['SellerID'],
					"BuyerID" => $order_info['BuyerID'],
					"CID" => $order_info['CID'],
					"Comment" => $addinfo['comment'],
					"Score" => $addinfo['score']
				);
				$commentModel = new Model_Counsel_CounselOrderComment();
				$result = $commentModel->insert($comment_data);
				if ($result === false)
					throw new Exception("评论失败！");

				//通知扩展信息
				$message_ext = array("OCID" => $result);

				//修改理财师评价总人数
				$stateModel = new Model_Counsel_CounselSellerState();
				$result = $stateModel->update(array("CommentNum" => new Zend_Db_Expr("CommentNum +1")),
					array("MemberID =?" => $order_info['SellerID']));

				if ($result === false)
					throw new Exception("评论失败！");

				//修改问财主题的评论总人数
				$counselModel = new Model_Counsel_Counsel();
				$counsel_info = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), array("CommentTotal", "ScoreTotal"));
				$comment_total = $counsel_info['CommentTotal'] + 1;
				$score_total = $counsel_info['ScoreTotal'] + $addinfo['score'];
				$result = $counselModel->update(array(
					"CommentTotal" => $comment_total,
					"UpdateTime" => date("Y-m-d H:i:s", time()),
					"Score" => DM_Helper_Utility::halfRound($score_total / $comment_total),
					"ScoreTotal" => $score_total,
				), array("CID =?" => $order_info['CID']));
				if ($result === false)
					throw new Exception("评论失败！");

				$message_event = self::ORDER_MESSAGE_COMMENT;
				break;
			//取消流程
			case self::ORDER_CHANGE_CANCEL:
				if (!in_array($order_info['OrderStatus'],
						array(self::ORDER_STATUS_NEW, self::ORDER_STATUS_PAYED, self::ORDER_STATUS_RECEIVED))
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("只能取消待赴约之前的订单！");

				$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
				$order_data['CancelType'] = $addinfo['type'];
				$order_data['CancelReason'] = isset($addinfo['reason']) ? $addinfo['reason'] : "";
				$order_data['LastEvent'] = self::ORDER_EVENT_CANCEL;
				$order_data['DamagesAmount'] = 0;

				if ($order_info['OrderStatus'] != self::ORDER_STATUS_NEW) {
					//已接单计算违约金
					$counselModel = new Model_Counsel_Counsel();
					$price = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Price");

					if ($order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED) {
						$meet_time = strtotime($order_info['MeetTime']) - time();
						if ($meet_time < 30 * 60) {
							$order_data['DamagesAmount'] = round($price * 50 / 100, 2);
						} elseif ($meet_time < 60 * 60) {
							$order_data['DamagesAmount'] = round($price * 20 / 100, 2);
						} elseif ($meet_time < 120 * 60) {
							$order_data['DamagesAmount'] = round($price * 10 / 100, 2);
						} else {
							$order_data['DamagesAmount'] = 0;
						}
					} else {
						$order_data['DamagesAmount'] = 0;
					}

					//退款
					$messageModel = new Model_Message();
					$wallet_order_id = $this->settlement($order_info, "BuyerID", "INCOME", "取消订单退款", $price - $order_data['DamagesAmount'], 20);
					$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_BACK, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

					//付违约金
					if ($order_data['DamagesAmount'] > 0) {
						$wallet_order_id = $this->settlement($order_info, "SellerID", "INCOME", "询财订单补偿费", $order_data['DamagesAmount']);
						$messageModel->addMessage($order_info['SellerID'], Model_Message::MESSAGE_TYPE_INCOME, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

						$stateModel = new Model_Counsel_CounselSellerState();
						$result = $stateModel->update(array(
							"Settlement" => new Zend_Db_Expr("Settlement +{$order_data['DamagesAmount']}"),
						),
							array("MemberID =?" => $order_info['SellerID']));

						if ($result === false)
							throw new Exception("取消失败！");
					}

					$message_event = self::ORDER_MESSAGE_CANCEL;
				}

				//message
				$order_info['DamagesAmount'] = $order_data['DamagesAmount'];

				//已经接单取消  删除档期
				if ($order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED)
					$this->deleteSchedule($order_info);

				break;
			//客服按比例结算
			case self::ORDER_CHANGE_SELLER_SERVICE_DONE:
			case self::ORDER_CHANGE_SELLER_DONE://卖家结算流程
				if ($order_info['SellerStatus'] != self::ORDER_SELLER_CLEARING)
					throw new Exception("订单不允许结算！");

				$order_data['SellerStatus'] = self::ORDER_SELLER_DONG;
				$order_data['ClearingTime'] = date("Y-m-d H:i:s", time());

				//平台扣除佣金
				$counselModel = new Model_Counsel_Counsel();
				$price = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Price");

				//平台扣除之后的钱
				$settle_price = round($price * 100 * self::ORDER_SETTLE_COMMISSION / 100, 2);
				$order_data['SettlementAmount'] = round($settle_price * 100 * $addinfo['commission'] / 10000, 2);
				$order_data['DeductedAmount'] = $price - $settle_price;

				//处理理财师已结算金额
				$stateModel = new Model_Counsel_CounselSellerState();
				$result = $stateModel->update(array(
					"Settlement" => new Zend_Db_Expr("Settlement +{$order_data['SettlementAmount']}"),
					"WaitSettlement" => new Zend_Db_Expr("WaitSettlement -{$price}"),
				),
					array("MemberID =?" => $order_info['SellerID']));

				if ($result === false)
					throw new Exception("结算失败！");

				//给卖家打钱
				$wallet_order_id = $this->settlement($order_info, "SellerID", "INCOME", "询财订单费用结算", $order_data['SettlementAmount']);

				//发钱包消息
				$messageModel = new Model_Message();
				$messageModel->addMessage($order_info['SellerID'], Model_Message::MESSAGE_TYPE_INCOME, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

				$log_info = $addinfo;

				if ($event == self::ORDER_CHANGE_SELLER_SERVICE_DONE) {
					//给买家退款
					if ($order_data['SettlementAmount'] < $settle_price) {
						//退款
						$wallet_order_id = $this->settlement($order_info, "BuyerID", "INCOME", "询财订单退款", $settle_price - $order_data['SettlementAmount'], 20);
						$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_BACK, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);
					}

					$message_event = self::ORDER_MESSAGE_PERCENT_SETTLEMENT;
					$order_info = array_merge($order_info, $addinfo);
				}
				break;
			//延长结算
			case self::ORDER_CHANGE_SELLER_POSTPONE:
				if ($order_info['SellerStatus'] != self::ORDER_SELLER_CLEARING)
					throw new Exception("订单不允许结算！");

				$order_data['SettlementTime'] = date("Y-m-d H:i:s", strtotime($order_info['SettlementTime']) + $addinfo['postpone_time']);
				$order_data['LastEvent'] = self::ORDER_EVENT_POSTPONE_CLEAR;

				//event
				$this->addEvent(strtotime($order_data['SettlementTime']), $order_info['OID'], self::ORDER_TASK_DEAL_SETTLEMENT);

				$addinfo['postpone_time'] = $addinfo['postpone_time'] / 86400;
				$log_info = $addinfo;

				$message_event = self::ORDER_MESSAGE_POSTPONE_SETTLTMENT;
				$order_info = array_merge($order_info, $addinfo);
				break;
			//拒绝结算
			case self::ORDER_CHANGE_SELLER_REFUSED:
				if ($order_info['SellerStatus'] != self::ORDER_SELLER_CLEARING)
					throw new Exception("订单不允许结算！");

				$order_data['SellerStatus'] = self::ORDER_SELLER_DONG;
				$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
				$order_data['LastEvent'] = self::ORDER_EVENT_REFUSE_CLEAR;
				$order_data['ClearingTime'] = date("Y-m-d H:i:s", time());

				//处理理财师已结算金额
				$counselModel = new Model_Counsel_Counsel();
				$price = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Price");
				$stateModel = new Model_Counsel_CounselSellerState();
				$result = $stateModel->update(array(
					"WaitSettlement" => new Zend_Db_Expr("WaitSettlement -{$price}"),
				),
					array("MemberID =?" => $order_info['SellerID']));

				if ($result === false)
					throw new Exception("结算失败！");

				//退款
				$wallet_order_id = $this->settlement($order_info, "BuyerID", "INCOME", "拒绝结算退款", null, 20);

				//发钱包消息
				$messageModel = new Model_Message();
				$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_BACK, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

				$log_info = $addinfo;

				$message_event = self::ORDER_MESSAGE_REFUSED_SETTLEMENT;
				$order_info = array_merge($order_info, $addinfo);
				break;
			/**
			 * 买家关闭订单，包含以下情况：
			 * 未支付过期订单、未接单过期订单
			 * 未支付取消、已支付取消、已接单取消
			 */
			case self::ORDER_CHANGE_BUYER_CLOSE:
				if (in_array($order_info['LastEvent'],
					array(self::ORDER_EVENT_CANCEL,
						self::ORDER_EVENT_PAY_OVERDUE,
						self::ORDER_EVENT_RECEIVE_OVERDUE
					)
				)) {
					$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
					$order_data['LastEvent'] = self::ORDER_EVENT_CLOSE;
				} else {
					throw new Exception("该订单无法关闭！");
				}
				break;
			//未支付订单过期
			case self::ORDER_CHANGE_PAY_OVERDUE:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_NEW
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("操作失败！");

				$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
				$order_data['LastEvent'] = self::ORDER_EVENT_PAY_OVERDUE;
				break;
			/**
			 * 未接单过期
			 * 通过支付时间来判断，修改时间会被申请取消时间改变
			 */
			case self::ORDER_CHANGE_RECEIVE_OVERDUE:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_PAYED
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
				)
					throw new Exception("操作失败！");

				$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
				$order_data['LastEvent'] = self::ORDER_EVENT_RECEIVE_OVERDUE;

				//退款
				$wallet_order_id = $this->settlement($order_info, "BuyerID", "INCOME", "对方未接单退款", null, 20);

				//发钱包消息
				$messageModel = new Model_Message();
				$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_BACK, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);

				//message
				$message_event = self::ORDER_MESSAGE_NO_RECEIVE;
				break;
			//赴约过期
			case self::ORDER_CHANGE_MEET_OVERDUE:
				if ($order_info['OrderStatus'] != self::ORDER_STATUS_RECEIVED
					|| $order_info['ValidStatus'] != self::ORDER_VALID_TRUE
					|| strtotime($order_info['MeetTime']) >= time()
				)
					throw new Exception("操作失败！");

				$order_data['ValidStatus'] = self::ORDER_VALID_CLOSE;
				$order_data['LastEvent'] = self::ORDER_EVENT_MEET_OVERDUE;

				//处理双方结算金额
				$counselModel = new Model_Counsel_Counsel();
				$price = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']), "Price");
				$settle_price = round($price * 100 * self::ORDER_SETTLE_COMMISSION / 100, 2);
				$order_data['DeductedAmount'] = $price - $settle_price;
				$order_data['SettlementAmount'] = 0;

				$messageModel = new Model_Message();

				if ($addinfo['commission'] > 0) {
					$order_data['SettlementAmount'] = round($settle_price * 100 * $addinfo['commission'] / 10000, 2);

					//处理理财师已结算金额
					$stateModel = new Model_Counsel_CounselSellerState();
					$result = $stateModel->update(array(
						"Settlement" => new Zend_Db_Expr("Settlement +{$order_data['SettlementAmount']}"),
					),
						array("MemberID =?" => $order_info['SellerID']));
					if ($result === false)
						throw new Exception("关闭失败！");

					//给买家打钱
					$wallet_order_id = $this->settlement($order_info, "SellerID", "INCOME", "询财订单费用结算", $order_data['SettlementAmount']);
					$messageModel->addMessage($order_info['SellerID'], Model_Message::MESSAGE_TYPE_INCOME, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);
				}

				//退款
				if ($settle_price - $order_data['SettlementAmount'] > 0) {
					$wallet_order_id = $this->settlement($order_info, "BuyerID", "INCOME", "未赴约订单退款", $settle_price - $order_data['SettlementAmount'], 20);
					$messageModel->addMessage($order_info['BuyerID'], Model_Message::MESSAGE_TYPE_BACK, $wallet_order_id, Model_Message::MESSAGE_SIGN_WALLET);
				}

				$log_info = $addinfo;

				//message
				$order_info['SettlementAmount'] = $order_data['SettlementAmount'];
				$order_info = array_merge($order_info, $addinfo);
				$message_event = self::ORDER_MESSAGE_OVERDUE_MEET;
				break;
			default:
				throw new Exception("非法操作！");
				break;
		}

		//修改订单表信息
		if (!empty($order_data)) {
			$order_data['UpdateTime'] = date("Y-m-d H:i:s", time());
			$result = $this->update($order_data, $oid);
			if ($result === false)
				throw new Exception("操作失败,CODE:1005！");
		}

		//插入日志
		$this->orderLog($order_info['OID'], $event, $member_id, $log_info);

		//发送提醒消息
		$this->sendMessage($message_event, $order_info, $message_ext);
		return true;
	}

	/**
	 * 写入订单操作日志
	 * @param $oid
	 * @param $event
	 * @param int $event_operator 操作人
	 * @param null $info
	 * @return bool
	 * @throws Exception
	 */
	protected function orderLog($oid, $event, $event_operator = 0, $info = null)
	{
		$orderLogModel = new Model_Counsel_CounselOrderLog();

		$remark = $this->changeRemark[$event];
		!is_null($info) && $remark = $this->parseRemark($remark, $info);

		$data = array(
			"OID" => $oid,
			"Event" => $event,
			"EventRemark" => $remark,
			"EventOperator" => $event_operator
		);

		$result = $orderLogModel->insert($data);
		if ($result === false)
			throw new Exception("操作失败，,CODE:1004！");

		return true;
	}

	/**
	 * 买家和卖家操作过滤
	 * @param $event
	 * @param int $type
	 * @throws Exception
	 *
	 */
	protected function handleFilter($event, $type = self::ORDER_FILTER_SELLER)
	{
		if (!in_array($event, $this->handleFilterMap[$type]))
			throw new Exception("当前用户不允许该操作！");
	}

	/**
	 * 发送提醒消息
	 * {"type":1,"oid":12333,"message":"test message","ext":[]}
	 * socket|短信发送
	 * @param $event
	 * @param $info
	 * @param array $ext 扩展信息
	 * @return bool
	 */
	protected function sendMessage($event, $info, $ext = array())
	{
		$message =& $this->messageRemark[$event];
		if (!isset($message))
			return false;

		//准备待解析数据
		$memberModel = new DM_Model_Account_Members();
		$info['buyer_name'] = $memberModel->getInfoMix(array("MemberID =?" => $info['BuyerID']), "UserName");
		$info['seller_name'] = $memberModel->getInfoMix(array("MemberID =?" => $info['SellerID']), "RealName");

		switch ($event) {
			case self::ORDER_MESSAGE_CANCEL:
				$message['s'] = $message['s'][$info['DamagesAmount'] > 0 ? 'yes' : 'no'];
				break;
			case self::ORDER_MESSAGE_FINISH:
				$lg_time = strtotime($info['FinishingTime']) - strtotime($info['FinishTime']);
				$message['b'] = $message['b'][$lg_time > 0 ? 'lg' : 'eq'];
				$info['lg_time'] = round($lg_time / 60);
				break;
			case self::ORDER_MESSAGE_OVERDUE_MEET:
				$message['s'] = $message['s'][$info['commission'] > 0 ? 'lg' : "eq"];
				break;
			case self::ORDER_MESSAGE_OVERDUE_MEET_SELLER:
				$info['Seller_phone'] = $memberModel->getInfoMix(array("MemberID =?" => $info['SellerID']), "MobileNumber");
				break;
			//插入提醒记录和短信通知客服
			case self::ORDER_MESSAGE_OVERDUE_MEET_SERVICE:
				$service_info = $this->getRandAdmin();

				//保存随机客服和订单的信息 注意可能会重复插入
				$exceptionalModel = new Model_Counsel_CounselOrderExceptional();
				$result = $exceptionalModel->insert(array(
					"OID" => $info['OID'],
					"Type" => $exceptionalModel::EXCEPTIONAL_NO_MEET,
					"AdminID" => $service_info['AdminID']
				));
				if (!$result)
					return false;

				//判断是否短信通知 随机通知 通过redis保存要发送短信的号码
				if (!empty($service_info['Telphone'])) {
					$info['Seller_phone'] = $service_info['Telphone'];
				} else {
					return true;
				}
				break;
		}

		//发送消息
		foreach ($message as $role => $mess) {
			$is_sms = $role{0} == '@' ? true : false;
			$is_sms && $role = $role{1};

			if ($is_sms) {
				$result = DM_Module_EDM_Phone::send($role == "b" ? $info['Buyer_phone'] : $info['Seller_phone'],
					$this->parseRemark($mess, $info));
			} else {
				//理财师用财猪团队通知  咨询者用socket通知 附带的信息：订单ID
				$data = array(
					"Type" => $event,
					"Time" => date("Y-m-d H:i:s", time()),
					"OID" => $info['OID'],
					"CID" => $info['CID'],
					"ColumnInfo" => array(),
					"Message" => $this->parseRemark($mess, $info),
					"Ext" => json_encode($ext)
				);
				if ($role == "b") {
					//理财号信息
					$columnModel = new Model_Column_Column();
					$data['ColumnInfo'] = $columnModel->getMyColumnInfo($info['SellerID'], 1, array("ColumnID", "Title", "Avatar"));
					//socket
					$result = DM_Socket_Notice::getInstance()->push(array(intval($info['BuyerID'])),
						array("CZSubAction" => "counsel", "data" => $data));
				} else {
					//发送链接
					$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
					$easeModel = new Model_IM_Easemob();
					$data["CZSubAction"] = "counsel";
					$result = $easeModel->yy_hxSend(array($info['SellerID']), $data['Message'], 'txt', 'users', $data, $sysMemberID);
				}
			}

			if (!$result)
				return false;
		}

		return true;
	}

	/**
	 * 随机获取客服ID
	 * @return int
	 */
	protected function getRandAdmin()
	{
		$admin_info = array("AdminID" => 1, "Telphone" => "");

		$admin_key = "admins_service_key";
		$redis = DM_Module_Redis::getInstance();
		$admin_ids = $redis->get($admin_key);

		if (empty($admin_ids)) {
			//客服角色ID
			$roleModel = new DM_Model_Table_User_Role();
			$select = $roleModel->select();
			$select->where("Platform =?", "ADMIN");
			$select->where("Name =?", "客服");
			$role_id = $roleModel->fetchRow($select);

			if (!empty($role_id)) {
				$role_id = $role_id['RoleID'];

				//查询admin_id
				$select = $roleModel->select()->setIntegrityCheck(false);
				$select->from("admin_roles", array("AdminID"));
				$select->where("RoleID =?", $role_id);
				$admin_ids = $roleModel->fetchAll($select);
				if (!empty($admin_ids)) {
					$admin_ids = $admin_ids->toArray();
					$admin_ids = array_map('current', $admin_ids);

					//缓存
					$redis->set($admin_key, json_encode($admin_ids));
				}
			}
		} else {
			$admin_ids = json_decode($admin_ids);
		}

		if (!empty($admin_ids)) {
			$admin = $admin_ids[rand(0, count($admin_ids) - 1)];
			$adminsModel = new DM_Model_Table_User_Admin();
			$select = $adminsModel->select();
			$select->where("AdminID =?", $admin);
			$select->from("admins", array("AdminID", "Telphone"));
			$admin_info = $adminsModel->fetchRow($select)->toArray();
		}

		return $admin_info;
	}

	/**
	 * 解析追加备注
	 * 这里可以做成动态解析信息的，例如：订单被@username@支付
	 * @param $remark
	 * @param $filter_data
	 * @return mixed
	 *
	 */
	protected function parseRemark($remark, $filter_data)
	{
		$result = preg_match_all("/@([\w]+)@/", $remark, $matches);
		if ($result && !empty($matches[1]))
			foreach ($matches[1] as $val)
				$remark = str_replace("@{$val}@", $filter_data[$val], $remark);

		return $remark;
	}


	/**
	 * 记录理财师档期
	 * @param $oid
	 * @param $seller_id
	 * @param $addinfo
	 * @throws Exception
	 */
	protected function insertSchedule($oid, $seller_id, $addinfo)
	{
		//记录理财师日程安排信息
		$scheduleModel = new Model_Counsel_CounselPlannerSchedule();
		$schedule_data = array(
			"OID" => $oid,
			"MemberID" => $seller_id
		);

		foreach ($addinfo['meet_date'] as $key => $val) {
			$schedule_data['StartTime'] = $val[0];
			$schedule_data['EndTime'] = $val[1];

			$result = $scheduleModel->insert($schedule_data);
			if ($result === false)
				throw new Exception("操作失败,CODE:1003！");
		}

		//删除过期的时间 可做可不做
	}

	/**
	 * 订单提前结束或者异常  删除未到的档期
	 * @param $order_info
	 * @throws Exception
	 */
	protected function deleteSchedule($order_info)
	{
		if (strtotime($order_info['MeetTime']) > time()) {
			$scheduleModel = new Model_Counsel_CounselPlannerSchedule();
			$result = $scheduleModel->delete(array("OID =?" => $order_info['OID']));
			if ($result === false)
				throw new Exception("操作失败,CODE:1006！");
		}
	}

	/**
	 * 操作金额
	 * @param $order_info
	 * @param string $role
	 * @param string $payment
	 * @param $order_remark
	 * @param null $price
	 * @param int $wallet_order_type 钱包订单类型
	 * @return int
	 * @throws Exception
	 */
	protected function settlement($order_info, $role = "BuyerID", $payment = "PAY", $order_remark, $price = null, $wallet_order_type = 9)
	{
		$counselModel = new Model_Counsel_Counsel();
		$counsel_info = $counselModel->getInfoMix(array("CID =?" => $order_info['CID']));

		$fundsModel = new DM_Model_Table_Finance_Funds();
		//$is_refund = ($payment == "INCOME" && $role == "BuyerID") ? true : false;//是否是退款
		$pay_type = ($payment == "PAY") ? $fundsModel::PAYMENT_TYPE_PAY : $fundsModel::PAYMENT_TYPE_INCOME;
		$price = is_null($price) ? $counsel_info['Price'] : $price;

		if ($pay_type == $fundsModel::PAYMENT_TYPE_PAY) {
			$balance = $fundsModel->getMemberBalance($order_info[$role]);
			if ($balance < $price)
				throw new Exception("余额不足！");
		}

		//创建资金记录订单
		$oid = $fundsModel->createOrder($order_info[$role], $pay_type, $wallet_order_type, $price,
			2, 0, $order_info['OID'], 0, "", $fundsModel::CURRENCY_CNY, 0, $order_remark,
			$wallet_order_type == 20 ? 9 : 0);

		if (!$oid)
			throw new Exception("操作失败,CODE:1002！");

		$result = $fundsModel->modifyAmount($order_info[$role], $fundsModel::CURRENCY_CNY, $price, $pay_type,
			$wallet_order_type, '', $order_remark, $order_info['OID']);

		if (!$result)
			throw new Exception("操作失败,CODE:1001！");

		return $oid;
	}

	/*-----------------------------------------以下为系统任务--------------------------------------*/
	/**
	 * 添加订单系统任务事件
	 *
	 * @param $time
	 * @param $oid
	 * @param $type
	 * @throws Exception
	 */
	public function addEvent($time, $oid, $type)
	{
		$redis = DM_Module_Redis::getInstance();
		$redis->zAdd(self::ORDER_TASK_KEY, $time, "{$oid}-{$type}");

		//这里除了添加都是返回0 没法判断
//		if (!$result)
//			throw new Exception("操作失败,CODE:1000！");
	}

	/**
	 * 处理订单事件
	 * key=>time oid-event_type
	 * 使用redis处理事件
	 * 极端情况下，订单量非常大时 例如事件未同步到文件时redis出现宕机 会出现丢失事件的情况 需要额外查表任务去保证完成性
	 */
	public function dealEvent()
	{
		$redis = DM_Module_Redis::getInstance();
		$logger = $this->createLogger("counsel", "deal_event");
		$now = time();

		//查出当前时间戳以前的待处理事件
		$event_list = $redis->zRangeByScore(self::ORDER_TASK_KEY, 0, $now);
		if (!empty($event_list)) {
			$logger->log("Order event total:" . count($event_list), Zend_Log::INFO);

			//成功、失败、无效的
			$success = $failed = $invalid = 0;
			foreach ($event_list as $event) {
				list($oid, $type) = explode("-", $event);

				try {
					$order_info = $this->getOrderInfo($oid, 0, self::ORDER_FILTER_SYSTEM);
				} catch (Exception $e) {
					$logger->log("No order about id:{$oid}", Zend_Log::INFO);
					$redis->zDelete(self::ORDER_TASK_KEY, $event);
					continue;
				}

				$is_delete = true;
				switch ($type) {
					case self::ORDER_TASK_NOTICE_PAY:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_NEW
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_WAITING_PAY, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice pay success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice pay failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_NOTICE_MEET:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_WAITING_MEET, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice meet success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice meet failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_NOTICE_MEETING:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_MEETING, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice meeting success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice meeting failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_NOTICE_SELLER_OVERDUE_MEET:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_OVERDUE_MEET_SELLER, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice seller missed success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice seller missed failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_NOTICE_SERVICE_OVERDUE_MEET:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_RECEIVED
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_OVERDUE_MEET_SERVICE, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice service missed success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice service missed failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_NOTICE_FINISH:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_MEETING
						) {
							$result = $this->sendMessage(self::ORDER_MESSAGE_WAITING_FINISH, $order_info);
							if ($result) {
								$success++;
								$logger->log("Notice finish success,oid:{$oid}", Zend_Log::INFO);
							} else {
								$failed++;
								$is_delete = false;
								$logger->log("Notice finish failed,oid:{$oid}", Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_OVERDUE_PAY:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_NEW
						) {
							try {
								$this->changeStatus($oid, self::ORDER_CHANGE_PAY_OVERDUE, array(), 0, self::ORDER_FILTER_SYSTEM);
								$success++;
								$logger->log("Overdue pay success,oid:{$oid}", Zend_Log::INFO);
							} catch (Exception $e) {
								$failed++;
								$is_delete = false;
								$logger->log("Overdue pay failed,oid:{$oid},message:" . $e->getMessage(), Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_OVERDUE_RECEIVE:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['OrderStatus'] == self::ORDER_STATUS_PAYED
						) {
							try {
								$this->changeStatus($oid, self::ORDER_CHANGE_RECEIVE_OVERDUE, array(), 0, self::ORDER_FILTER_SYSTEM);
								$logger->log("Overdue receive success,oid:{$oid}", Zend_Log::INFO);
								$success++;
							} catch (Exception $e) {
								$failed++;
								$is_delete = false;
								$logger->log("Overdue receive failed,oid:{$oid},message:" . $e->getMessage(), Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
					case self::ORDER_TASK_DEAL_SETTLEMENT:
						if ($order_info['ValidStatus'] == self::ORDER_VALID_TRUE
							&& $order_info['SellerStatus'] == self::ORDER_SELLER_CLEARING
						) {
							try {
								$this->changeStatus($oid, self::ORDER_CHANGE_SELLER_DONE, array("commission" => self::ORDER_SETTLE_PERCENT), 0, self::ORDER_FILTER_SYSTEM);
								$logger->log("Settle success,oid:{$oid}", Zend_Log::INFO);
								$success++;
							} catch (Exception $e) {
								$failed++;
								$is_delete = false;
								$logger->log("Settle failed,oid:{$oid},message:" . $e->getMessage(), Zend_Log::ERR);
							}
						} else {
							$invalid++;
						}
						break;
				}

				//删除事件
				$is_delete && $redis->zDelete(self::ORDER_TASK_KEY, $event);
			}

			//log 计数
			$logger->log("Deal order event,success:{$success},failed:{$failed},invalid:{$invalid},time:" . ($now - time()) . "sec", Zend_Log::INFO);
		} else {
			$logger->log("No order event", Zend_Log::INFO);
		}
	}
}
