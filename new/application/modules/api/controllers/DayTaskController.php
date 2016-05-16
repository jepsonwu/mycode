<?php

class Api_DayTaskController extends DM_Controller_Api
{
	public function init()
	{
		//parent::init();
		$sapiName = strtoupper(php_sapi_name());

		//禁止以其他方式运行，必须在CLI 下运行
		if ('CLI' !== $sapiName && APPLICATION_ENV == 'production') {
			exit('DENIED');
		}
		set_time_limit(0);
	}

	/**
	 * 群组同步
	 */
	public function syncGroupAction()
	{
		$groupModel = new Model_IM_Group();
		$groupModel->syncGroup();
		$groupModel->syncGroupMembers();
	}

	/**
	 * 强制用户下线   已废弃
	 */
	public function disconnectAction()
	{
		$username = '1268';
		$easeModel = new Model_IM_Easemob();
		$ret = $easeModel->disconnect($username);
		echo $ret;
		exit;
	}

	/**
	 * 通知获得流量的会员
	 */
	public function noticeMemberAction()
	{
		$flowModel = new Model_FlowOrder();
		$flowModel->noticeMemberAction();
	}

	/**
	 * 分发用户观点
	 */
	public function handViewsAction()
	{
		$viewModel = new Model_Topic_View();
		$viewModel->handViews();
	}

	/**
	 * 将说说分发到财友圈的好友
	 */

	public function caiYouQuanAction()
	{
		$model = new Model_Shuoshuo();
		$model->newPushCaiYouquan();
	}

	/**
	 * 关注关系同步成申请好友关系
	 */
	public function syncFriendAction()
	{

		$friendModel = new Model_IM_Friend();
		$friendModel->newSyncFriendRelation();
	}

	/**
	 * 数据统计
	 */
	public function staticAction()
	{
		$staticModel = new Model_Static();
		$staticModel->doStatic();
	}

	/**
	 * 设置话题首字母    已废弃
	 */
	public function setCapitalCharAction()
	{
		$topicModel = new Model_Topic_Topic();
		$topicModel->setCapitalChar();
	}

	/**
	 * 话题统计相关
	 */
	public function topicStaticAction()
	{
		$topicModel = new Model_Topic_Topic();
		$topicModel->topicStatic();
	}

	/**
	 * 日常统计
	 */
	public function dayStaticAction()
	{
		$dayStaticModel = new Model_DayStatic();
		$dayStaticModel->dayStatic();
	}

	/**
	 * 查询充值流量是否成功
	 */
	public function queryOrderAction()
	{
		$model = new Model_FlowOrder();
		$model->queryOrders();
	}

	/**
	 * 流量充值接口
	 */
	public function rechargeAction()
	{

		$model = new Model_FlowActivity();
		$model->rechargeFlow();
	}

	/**
	 * 回调友钱接口
	 */
	public function callbackYouqianAction()
	{
		$model = new Model_OwnerIdfa();
		$model->callbackYouqian();
	}

	/**
	 * 定时发布文章
	 */
	public function publishArticleAction()
	{
		$model = new Model_Column_Article();
		$model->publishArticle();
	}

	/**
	 * 处理提现申请
	 */
	public function handleRefundAction()
	{
		$model = new Model_Refund();
		$model->handleRefund();
	}

	/**
	 * 每天凌晨执行将冻结的账号恢复
	 * 执行时间：每天零点执行一次
	 */
	public function unFreezeAction()
	{
		$model = new Model_Wallet_Wallet();
		$model->unFreeze();
	}

	/**
	 * 每天凌晨执行清理红包的预分配表中的无效数据
	 * 执行时间：每天零点执行一次
	 */
	public function clearBonusPresetAction()
	{
		$model = new Model_Bonus();
		$model->clearPreset();
	}

	/**
	 * 过期红包退款处理
	 * 执行时间：每10分钟执行一次
	 */
	public function bonusBackAction()
	{
		$model = new Model_Bonus();
		$model->bonusBack();
	}

	/**
	 * 把之前的好友关系同步到缓存中
	 */
	public function syncFriendCacheAction()
	{
		$model = new Model_IM_Friend();
		$model->syncFriendCache();
	}

	/**
	 * 问财系统任务
	 * 执行时间：每10分钟执行一次 可以缩短间隔时间
	 */
	public function counselDealEventAction()
	{
		$model = new Model_Counsel_CounselOrder();
		$model->dealEvent();
	}

	public function historyFriendViewListAction()
	{
		$model = new Model_Topic_View();
		$model->historyFriendViewList();
	}

	public function syncShuoshuoDataAction()
	{
		$model = new Model_Shuoshuo();
		$model->syncShuoshuoData();

	}

	/**
	 * 10分钟后自动清楚位置信息
	 */
	public function deleteLocationAction()
	{
		$model = new Model_MemberLocation();
		$model->deleteLocation();
	}

	/**
	 * websocket notice 开启进程即可 可以开启多个
	 */
	public function webSocketNoticeAction()
	{
		DM_Socket_Notice::getInstance()->send();
	}

	/**
	 * 同步CityCode
	 */
	public function syncCityCodeAction()
	{
		$memberModel = new DM_Model_Account_Members();
		$select = $memberModel->select();
		$res = $select->from('members', array('MemberID', 'Province', 'City'))->where('CityCode <=0')->query()->fetchAll();
		if (!empty($res)) {
			foreach ($res as $item) {
				if (empty($item['City']) && empty($item['Province'])) {
					continue;
				}
				$memberModel->update(array('CityCode' => DM_Module_Region::getCityCode($item['City'], $item['Province'])), array('MemberID = ?' => $item['MemberID']));
			}
		}

		$memberAu = new Model_Authenticate();
		$select = $memberAu->select();
		$res = $select->from('member_authenticate', array('AuthenticateID', 'Province', 'City'))->query()->fetchAll();
		if (!empty($res)) {
			foreach ($res as $item) {
				if (empty($item['City']) && empty($item['Province'])) {
					continue;
				}
				$memberAu->update(array('CityCode' => DM_Module_Region::getCityCode($item['City'], $item['Province'])), array('AuthenticateID =? ' => $item['AuthenticateID']));
			}
		}
		exit;
	}

	/**
	 * 威尼斯微信活动发奖品通知
	 */
	public function activityDisneyAction()
	{
		$first_tem = "恭喜您在财猪“零元玩转迪士尼”活动中获得%s位好友赞同，排名第%s位，您将收到财猪为您送出的奖品xxxxx。";
		$award = $first = "";
		$template_id = "5krV87zfl0K0ZvDEuM3O-jPoiBOMJYniLjYhbkMIsuE";
		$app_id = "wxd652282e3ac5ffa3";

		//给1-10名发通知 注意相同分享人数算一个人
		$disneyModel = new Model_Activity_Disney();
		$disney_info = $disneyModel->getInfoMix(array(), array("UID", "ShareNum"), "Assoc", "ShareNum DESC,CreateTime ASC", 10);
		if (!empty($disney_info)) {
			$uids = array_keys($disney_info);

			$userModel = new Model_Activity_WechatUserOpenid();
			$userInfo = $userModel->getInfoMix(array("UID IN(?)" => $uids), array("UID", "Openid"), "Assoc");

			//发模板消息
			$data = array(
				"first" => array(
					"value" => &$first,
					"color" => "#173177",
				),
				"keyword1" => array(//活动
					"value" => "零元玩转迪士尼",
					"color" => "#173177",
				),
				"keyword2" => array(//奖品
					"value" => &$award,
					"color" => "#173177",
				),
				"remark" => array(
					"value" => "请回复您的姓名、联系方式，以便我们为您发放奖品。",
					"color" => "#173177",
				),
			);

			$config = DM_Controller_Front::getInstance()->getConfig()->toArray();
			$wechat_obj = DM_Wechat_WechatOpen::getInstance($config['wechat_open']['settings']);
			$wechat_obj->setAuthorizerAppID($app_id);
			foreach ($uids as $key => $uid) {
				try {
					$first = sprintf($first_tem, $disney_info[$uid]['ShareNum'], $key);
					$award = $key < 2 ? "“财猪零元玩转迪士尼”大礼包一份" : "上海迪士尼门票一张";

					$wechat_obj->templateSendMessage($userInfo[$uid]['Openid'], $template_id, $data);
				} catch (Exception $e) {
					echo "send template message failed,uid:{$uid}" . $e->getMessage();
				}
			}

			echo "success";
		} else {
			echo "disney info is empty";
		}
	}
}