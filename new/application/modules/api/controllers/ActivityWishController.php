<?php

/**
 * 心愿购物车活动
 * User: jepson <jepson@duomai.com>
 * Date: 16-1-20
 * Time: 上午9:22
 */
class Api_ActivityWishController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->is_valid();
	}

	//每人限领金额
	const WISH_TOTAL_AMOUNT = 2000;

	//cookie
	const WISH_COOKIE = "WISHCOOKIE";

	//share cookie
	const WISH_SHARE_COOKIE = "WISHSHARECOOKIE";

	//phone
	protected $_phone = '';
	protected $_wish_count = 0;
	protected $_is_wish = false;
	protected $_is_privilege = false;
	protected $_wish_pig = 0;//财猪个数

	//礼券领取状态
	const GIFT_PRIVILEGE_FAILED = 0;//未抽奖
	const GIFT_PRIVILEGE_DOING = 1;//已抽奖
	const GIFT_PRIVILEGE_SUCCESS = 2;//已经领取
	const GIFT_FREE_FAILED = 0;//不能免费
	const GIFT_FREE_SUCCESS = 1;//免费

	//礼券优惠券状态
	const GIFT_COUPON_URL = 1;//url
	const GIFT_COUPON_CODE = 0;
	const GIFT_COUPON_ONE = 2;//一个优惠码

	//活动时间
	protected function is_valid()
	{
		if (time() < strtotime("2016-01-25") || time() > strtotime("2016-02-23")) {
			$url = "http://fe.caizhu.com/public/html/over.html?status=%E6%B4%BB%E5%8A%A8%E5%B7%B2%E8%BF%87%E6%9C%9F";
			header('Location: ' . $url);
//			Zend_Layout::startMvc()->disableLayout();
//
//			echo $this->view->render('');
//			echo $this->view->render('public/empty.phtml');
		}
	}

	/**
	 * 判断是否在财猪APP
	 * @return bool
	 */
	protected function isApp()
	{
		return strpos($this->_request->getServer("HTTP_USER_AGENT"), "caizhuapp") !== false ? true : false;
	}

	/**
	 * 是否记录手机号
	 * 1-正常
	 * 2-没有登陆
	 * 3-没有手机号  绑定手机号
	 * @return bool
	 */
	protected function isWishLogin()
	{
		$wishModel = new Model_Activity_Wish();
		if ($this->isApp()) {
			if (!empty($this->memberInfo)) {
				if (!empty($this->memberInfo->MobileNumber)) {
					$this->_phone = $this->memberInfo->MobileNumber;

					$wishModel = new Model_Activity_Wish();
					$phone = $wishModel->getInfoMix(array("Phone =?" => $this->_phone), array("Phone", "MemberID"));
					if (is_null($phone)) {
						$wishModel->insert(array("Phone" => $this->_phone));
					} elseif ($phone['MemberID'] == 0) {
						$wishModel->update(array("MemberID" => $this->memberInfo->MemberID), array("Phone =?" => $phone['Phone']));
					}
				} else {
					return 3;
				}
			}
		} else {
			$cookie = DM_Controller_Front::getInstance()->getHttpRequest()->getCookie(self::WISH_COOKIE, '');
			$cookie = DM_Helper_Utility::authcode($cookie);
			if (preg_match("/^[\d]{11}$/", $cookie) === 1)
				$this->_phone = $cookie;
		}

		if ($this->_phone) {
			//判断是否有许愿
			$this->isWish();
			return 1;
		}

		return 2;
	}

	/**
	 * 是否许愿
	 * 判断礼券数量
	 * @return bool
	 */
	protected function isWish()
	{
		if ($this->_phone) {
			$wishModel = new Model_Activity_Wish();
			$is_wish = $wishModel->getInfoMix(array("Phone =?" => $this->_phone), array("GID", "Pig", "PrivilegeStatus"));

			if (!empty($is_wish) && !empty($is_wish['GID'])) {
				$this->_is_wish = true;
				if ($is_wish['PrivilegeStatus'] == self::GIFT_PRIVILEGE_SUCCESS)
					$this->_wish_count = count(array_unique(explode(",", trim($is_wish['GID'], ","))));
				$this->_wish_pig = $is_wish['Pig'];
				$is_wish['PrivilegeStatus'] == self::GIFT_PRIVILEGE_SUCCESS && $this->_is_privilege = true;
			}
		}
	}

	protected function indexDoAction($is_login)
	{
		//获取实现的愿望列表
		$wishModel = new Model_Activity_Wish();
		$select = $wishModel->select()->setIntegrityCheck(false);
		$select->from("activity_wish", array("WID", "Phone", "GID", "UserName", "Avatar", "CreateTime", "MemberID"));
		$select->where("PrivilegeStatus =?", self::GIFT_PRIVILEGE_SUCCESS);
		$select->order("CreateTime desc");
		$select->limitPage(1, 30);
		$wish_list = $wishModel->fetchAll($select)->toArray();

		$wish_list_count = 0;
		if (!empty($wish_list)) {
			$gids = array();
			for ($i = 0; $i < count($wish_list); $i++) {
				$info =& $wish_list[$i]['GID'];
				$info = explode(",", $info);
				count($info) > $wish_list_count && $wish_list_count = count($info);
				$gids = array_unique(array_merge($gids, $info));
			}

			$giftModel = new Model_Activity_WishGift();
			$gift_list = $giftModel->getInfoMix(array("GID IN(?)" => $gids), array("GID", "Name"), "Pairs");
		} else {
			$gift_list = array();
		}

		$this->view->phone = $this->_phone;
		$this->view->is_privilege = $this->_is_privilege;
		$this->view->is_app = $this->isApp();
		$this->view->is_login = $is_login;
		$this->view->is_wish = $this->_is_wish;
		$this->view->wish_count = $this->_wish_count;
		$this->view->wish_list_count = $wish_list_count;
		$this->view->gift_list = $gift_list;
		$this->view->wish_list = $wish_list;

		echo $this->view->render('activity/wish160126/wish-index.phtml');
		exit;
	}

	//首页
	public function wishIndexAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();

		//是否登陆
		$is_login = $this->isWishLogin();
		$this->indexDoAction($is_login);
	}

	//礼券清单
	public function giftListAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();

		$is_login = $this->isWishLogin();
		$is_login !== 1 && $this->indexDoAction($is_login);

		$giftModel = new Model_Activity_WishGift();
		$gift_list = $giftModel->getInfoMix(array(), array('GID', 'Name', 'Desc', 'Logo', 'Amount', 'Count'), "All", "CreateTime desc");

		$this->view->phone = $this->_phone;
		$this->view->is_privilege = $this->_is_privilege;
		$this->view->is_app = $this->isApp();
		$this->view->is_login = $is_login;
		$this->view->is_wish = $this->_is_wish;
		$this->view->wish_total_amount = self::WISH_TOTAL_AMOUNT;
		$this->view->gift_list = json_encode(array_values($this->filterTag($gift_list)));

		echo $this->view->render('activity/wish160126/wish-gift-list.phtml');
	}

	protected function filterTag(array &$result)
	{
		for ($i = 0; $i < count($result); $i++) {
			$val =& $result[$i]['Desc'];
			if (isset($val)) {
				$val = preg_replace(array(
					"/\s/",
					"/\"/"
				), array(
					" ",
					"\\\""
				), $val);
			}

			$val =& $result[$i]['Logo'];
			if (isset($val)) {
				$val = 'http://img.caizhu.com/' . $val;
			}
		}

		return $result;
	}

	//我的礼券
	public function myGiftAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();

		$is_login = $this->isWishLogin();
		$is_login !== 1 && $this->indexDoAction($is_login);

		if ($is_login === 1) {
			$wishModel = new Model_Activity_Wish();
			$wish_info = $wishModel->getInfoMix(array("Phone =?" => $this->_phone,
				"PrivilegeStatus =?" => self::GIFT_PRIVILEGE_SUCCESS), array("GID", "WID"));
			$my_wish_list = empty($wish_info) ? array() : explode(",", $wish_info['GID']);
		} else {
			$my_wish_list = array();
		}

		if (!empty($my_wish_list)) {
			$giftModel = new Model_Activity_WishGift();
			$gift_list = $giftModel->getInfoMix(array("GID IN(?)" => $my_wish_list),
				array("GID", "Name", "Logo", 'CouponName', 'IsCouponUrl', 'CouponUrl', 'UsedRule'), "Assoc");

			//查找优惠码
			$couponModel = new Model_Activity_WishInfo();

			$coupon_info = $couponModel->getInfoMix(
				array("WID =?" => $wish_info['WID'],
					"GID IN(?)" => $my_wish_list), array("GID", "CouponCode", "CouponPass"), "Assoc"
			);
		} else {
			$coupon_info = array();
			$gift_list = array();
		}

		$this->view->phone = $this->_phone;
		$this->view->is_privilege = $this->_is_privilege;
		$this->view->is_app = $this->isApp();
		$this->view->is_login = $is_login;
		$this->view->is_wish = $this->_is_wish;
		$this->view->wish_count = $this->_wish_count;
		$this->view->coupon_info = $coupon_info;
		$this->view->gift_list = $gift_list;
		$this->view->my_wish_list = $my_wish_list;
		echo $this->view->render('activity/wish160126/wish-my-gift.phtml');
	}

	//我的愿望
	public function myWishAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();

		$is_login = $this->isWishLogin();

		$phone = $this->_request->getParam("phone", "");

		if (empty($phone) || (!empty($this->_phone) && $this->_phone == $phone)) {
			$is_login !== 1 && $this->indexDoAction($is_login);

			$wish_info = $my_wish_gift = array();
			if ($is_login === 1 && $this->_is_wish) {
				$wishModel = new Model_Activity_Wish();
				$result = $wishModel->getInfoMix(array("Phone =?" => $this->_phone));
				if (!is_null($result)) {
					$wish_info = array(
						"pig" => $result['Pig'],
						"privilege_status" => $result['PrivilegeStatus'],
						"support_count" => $result['SupportCount'],
						"is_sign" => $result['IsSign'],
						"is_free" => $result['FreeStatus'],
						"phone" => $this->_phone
					);

					//gift logo
					$wishGiftModel = new Model_Activity_WishGift();
					$my_wish_gift = $wishGiftModel->getInfoMix(array("GID in(?)" => explode(",", $result['GID'])), array("GID", "Logo"), "Pairs");
				}
			} else {
				$wish_info = array(
					"pig" => 0,
					"privilege_status" => 0,
					"support_count" => 0,
					"is_sign" => 0,
					"is_free" => 0,
					"phone" => 0
				);
			}

			$this->view->phone = $this->_phone;
			$this->view->is_privilege = $this->_is_privilege;
			$this->view->my_wish_gift = $my_wish_gift;
			$this->view->is_app = $this->isApp();
			$this->view->wish_total_amount = self::WISH_TOTAL_AMOUNT;
			$this->view->is_login = $is_login;
			$this->view->is_wish = $this->_is_wish;
			$this->view->wish_info = json_encode($wish_info);
			echo $this->view->render('activity/wish160126/wish-my-info.phtml');
		} else {
			$this->shareDoAction();
		}
	}

	protected function shareDoAction()
	{
		$phone = $this->_request->getParam("phone", "");
		if (empty($phone))
			parent::failReturn("手机号格式错误");

		$is_support = false;
		$is_wish = false;

		$wishModel = new Model_Activity_Wish();
		$wish_info = $wishModel->getInfoMix(array("Phone =?" => $phone), array("GID", "", "SupportCount"));
		if (!is_null($wish_info) && !empty($wish_info['GID'])) {
			//种一个COOKIE
			$wish_cookie = DM_Controller_Front::getInstance()->getHttpRequest()->getCookie(self::WISH_SHARE_COOKIE, '');
			$have_cookie = false;
			if (!empty($wish_cookie)) {
				$wish_cookie = DM_Helper_Utility::authcode($wish_cookie);
				if (!empty($wish_cookie)) {
					//修复bug  同一个浏览器只中了一个cookie
					parse_str($wish_cookie, $wish_cookie);

					//判断是否已经支持  判断是否中cookie
					if (isset($wish_cookie[$phone])) {
						$have_cookie = true;
						$wish_cookie[$phone] == 1 && $is_support = true;
					} else {
						$wish_cookie[$phone] = 0;
					}
				}
			} else {
				$wish_cookie = array(
					$phone => 0
				);
			}

			//种cookie
			if (!$have_cookie) {
				$wish_cookie = DM_Helper_Utility::authcode(http_build_query($wish_cookie), 'ENCODE');
				setcookie(self::WISH_SHARE_COOKIE, $wish_cookie, time() + 86400 * 60, '/', '', false, true);
			}

			$is_wish = true;
		} else {
			$wish_info = array(
				"Pig" => 0,
				"SupportCount" => 0
			);
		}

		$this->view->phone = $phone;
		$this->view->is_app = $this->isApp();
		$this->view->is_wish = $is_wish;
		$this->view->wish_info = $wish_info;
		$this->view->is_support = $is_support;
		echo $this->view->render('activity/wish160126/wish-sharing.phtml');
		exit;
	}

	//分享
	public function wishShareAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();

		$this->shareDoAction();
	}

	protected $wishConf = array(
		array("gids", "/^([\d]+,?)+$/", "请选择礼券", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 许愿
	 * 这个功能存在太多业务逻辑  太需要优化了
	 */
	public function wishAction()
	{
		try {
			$is_login = $this->isWishLogin();
			if ($is_login !== 1)
				throw new Exception("请填写手机号");

			$wishModel = new Model_Activity_Wish();
			$wish_info = $wishModel->getInfoMix(array("Phone =?" => $this->_phone), array("GID", "WID"));

			if (!empty($wish_info) && !empty($wish_info['GID']))
				throw new Exception("已经许过愿望啦");

			//对比礼券
			$gids = array_unique(explode(",", trim($this->_param['gids'], ",")));

			$giftModel = new Model_Activity_WishGift();
			$gift_list = $giftModel->getInfoMix(array("GID IN(?)" => $gids),
				array("GID", "Amount", 'Count', 'IsCouponUrl', 'CouponType'), "Assoc");

			if (empty($gids) || empty($gift_list))
				throw new Exception("请选择礼券");

			$gift_list_count = array();
			foreach ($gift_list as $info)
				$gift_list_count[$info['CouponType']] = $info['Count'];

			//金额不能大于指定值  库存判断
			//需要记录优惠券的愿望  type=>gid
			$wish_coupon_type = $wish_coupon_count = array();
			$amount = 0;
			foreach ($gids as $gid) {
				if (!isset($gift_list[$gid]))
					throw new Exception("不存在该礼券");

				if ($gift_list[$gid]['Count'] <= 0)
					throw new Exception("该礼券已经被抢光啦");

				if ($gift_list[$gid]['IsCouponUrl'] == self::GIFT_COUPON_CODE) {
					$wish_coupon_type[$gift_list[$gid]['GID']] = $gift_list[$gid]['CouponType'];
					$temp =& $wish_coupon_count[$gift_list[$gid]['CouponType']];
					$temp = isset($temp) ? $temp + 1 : 1;
				}

				$amount = intval($amount + $gift_list[$gid]['Amount']);
			}
			if ($amount / 100 > self::WISH_TOTAL_AMOUNT)
				throw new Exception("礼券总金额不能大于" . self::WISH_TOTAL_AMOUNT);

			//获取优惠券吗  todo jepson 用system queue重写
			$wish_coupon_info = $coupon_ids = array();
			$wishCouponModel = new Model_Activity_WishCoupons();
			if ($wish_coupon_type) {
				$i = 0;
				foreach ($wish_coupon_type as $gid => $type) {
					$i++;//随机一个没有被使用的优惠吗
					$coupon_info = $wishCouponModel->getInfoMix(array("Type =?" => $type), null, "Row", null, "{$i},1");
					$wish_coupon_info[$gid . '-' . $type] = $coupon_info;
					$coupon_ids[] = $coupon_info['CID'];
				}
			}

			$wishModel->getAdapter()->beginTransaction();
			try {
				$data = array(
					"GID" => implode(",", $gids),
					"Ip" => $this->_request->getClientIp()
				);
				//如果是财猪，添加会员信息
				if (!is_null($this->memberInfo)) {
					$data['MemberID'] = $this->memberInfo->MemberID;
					$data['Avatar'] = $this->memberInfo->Avatar;
					$data['UserName'] = $this->memberInfo->UserName;
				} else {
					$data['Avatar'] = "";
					$data['UserName'] = "第三方用户";
				}

				$result = $wishModel->update($data, array("Phone =?" => $this->_phone));
				if ($result === false)
					throw new Exception("修改心愿信息失败");

				//修改礼券库存
				if ($wish_coupon_count) {
					foreach ($wish_coupon_count as $type => $count) {
						$result = $giftModel->update(array("Count" => $gift_list_count[$type] - $count), array("CouponType =?" => $type));
						if ($result === false)
							throw new Exception("修改库存失败");
					}
				}

				//插入愿望详情
				if ($wish_coupon_info) {
					$wishInfoModel = new Model_Activity_WishInfo();
					foreach ($wish_coupon_type as $gid => $type) {
						$result = $wishInfoModel->insert(array(
							"WID" => $wish_info['WID'],
							"GID" => $gid,
							"CouponCode" => $wish_coupon_info[$gid . '-' . $type]['CouponCode'],
							"CouponPass" => $wish_coupon_info[$gid . '-' . $type]['CouponPass']
						));

						if ($result === false)
							throw new Exception("插入详情失败");
					}

					//删除优惠码
					$result = $wishCouponModel->delete(array("CID in(?)" => $coupon_ids));
					if ($result === false)
						throw new Exception("删除优惠码失败");
				}

				$wishModel->getAdapter()->commit();
			} catch (Exception $e) {
				$wishModel->getAdapter()->rollBack();
				throw new Exception("许愿失败");
			}

			parent::succReturn("");
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $profileConf = array(
		array("name", "require", "请填写姓名", DM_Helper_Filter::MUST_VALIDATE),
		array("address", "require", "请填写收件地址", DM_Helper_Filter::MUST_VALIDATE),
		array("add_phone", "/^[\d]{11}$/", "请填写收件人手机号", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 修改资料
	 */
	public function profileAction()
	{
		try {
			$is_login = $this->isWishLogin();
			if ($is_login !== 1)
				throw new Exception("请填写手机号");

			//许愿
			if (!$this->_is_wish)
				throw new Exception("还不快去许愿");

			//许愿
			$wishModel = new Model_Activity_Wish();

			$result = $wishModel->update(array(
				"Name" => $this->_param['name'],
				"Address" => $this->_param['address'],
				"AddPhone" => $this->_param['add_phone'],
			), array("Phone =?" => $this->_phone));

			if ($result === false)
				throw new Exception("保存失败");

			parent::succReturn("");
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $augmentPigConf = array(
		array("type", "1,2,3", "参数错误", DM_Helper_Filter::MUST_VALIDATE, "in"),
		array("phone", "/^[\d]{11}$/", "手机号格式错误", DM_Helper_Filter::EXISTS_VALIDATE),
	);

	/**
	 * 增加财猪
	 * 1-抽奖
	 * 2-支持
	 * 3-签到
	 */
	public function augmentPigAction()
	{
		try {
			if ($this->_param['type'] == 2) {
				if (!isset($this->_param['phone']))
					throw new Exception("请填写手机号");

				//支持
				$wish_cookie = DM_Controller_Front::getInstance()->getHttpRequest()->getCookie(self::WISH_SHARE_COOKIE, '');
				if (!empty($wish_cookie)) {
					$wish_cookie = DM_Helper_Utility::authcode($wish_cookie);
					if (!empty($wish_cookie)) {
						parse_str($wish_cookie, $wish_cookie);

						if (!isset($wish_cookie[$this->_param['phone']]))
							throw new Exception("非法操作");

						//不能支持自己
						$this->isWishLogin();
						if ($this->_phone == $this->_param['phone'])
							throw new Exception("不能支持自己");

						//判断是否已经支持
						if ($wish_cookie[$this->_param['phone']] == 1)
							throw new Exception("已经支持过了");

						$this->_phone = $this->_param['phone'];
					} else {
						throw new Exception("非法操作");
					}
				} else {
					throw new Exception("非法操作");
				}
			} else {
				$is_login = $this->isWishLogin();
				if ($is_login !== 1)
					throw new Exception("请填写手机号");

				//许愿
				if (!$this->_is_wish)
					throw new Exception("还不快去许愿");
			}

			//许愿
			$wishModel = new Model_Activity_Wish();

			$wish_info = $wishModel->getInfoMix(array("Phone =?" => $this->_phone), array("PrivilegeStatus", "IsSign", "SupportCount", "Pig"));
			if (is_null($wish_info))
				throw new Exception("请填写手机号");

			$data = array();
			$pig = 0;
			switch ($this->_param['type']) {
				case "1":
					$pig = rand(500, 600);
					if ($wish_info['PrivilegeStatus'] != self::GIFT_PRIVILEGE_FAILED)
						throw new Exception("已经抽过奖了");
					$data = array(
						"Pig" => $wish_info['Pig'] + $pig,
						"PrivilegeStatus" => self::GIFT_PRIVILEGE_DOING
					);
					break;
				case "2":
					$pig = rand(15, 20);
					$data = array(
						"Pig" => $wish_info['Pig'] + $pig,
						"SupportCount" => $wish_info['SupportCount'] + 1
					);
					break;
				case "3":
					if ($wish_info['IsSign'] != 0)
						throw new Exception("已经签过到了");

					//判断是app
					if (!$this->isApp())
						throw new Exception("只能财猪APP签到");
					$pig = rand(200, 250);
					$data = array(
						"Pig" => $wish_info['Pig'] + $pig,
						"IsSign" => 1
					);
					break;
			}

			$result = $wishModel->update($data, array("Phone =?" => $this->_phone));
			if ($result === false)
				throw new Exception("操作失败");

			if ($this->_param['type'] == 2) {
				$wish_cookie[$this->_param['phone']] = 1;
				$wish_cookie = DM_Helper_Utility::authcode(http_build_query($wish_cookie), 'ENCODE');
				setcookie(self::WISH_SHARE_COOKIE, $wish_cookie, time() + 86400 * 60, '/', '', false, true);
			}

			parent::succReturn(array("pig" => $pig));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $realizeWishConf = array(
		array("type", "1,2", "参数错误", DM_Helper_Filter::EXISTS_VALIDATE, "in", "1")
	);

	/**
	 * 兑换奖品
	 * 1-privilege
	 * 2-free
	 */
	public function realizeWishAction()
	{
		try {
			$is_login = $this->isWishLogin();
			if ($is_login !== 1)
				throw new Exception("请填写手机号");

			//许愿
			if (!$this->_is_wish)
				throw new Exception("还不快去许愿");

			$wishModel = new Model_Activity_Wish();

			$wish_info = $wishModel->getInfoMix(array("Phone =?" => $this->_phone), array("PrivilegeStatus", "FreeStatus"));
			$data = array();
			switch ($this->_param['type']) {
				case "1":
					if ($wish_info['PrivilegeStatus'] != self::GIFT_PRIVILEGE_DOING)
						throw new Exception("已经领取过啦");

					if ($this->_wish_pig < 500)
						throw new Exception("必须要集齐500只财猪才能领取豪礼哦");

					$data['PrivilegeStatus'] = self::GIFT_PRIVILEGE_SUCCESS;
					break;
				case "2":
					if ($wish_info['FreeStatus'] != self::GIFT_FREE_FAILED)
						throw new Exception("已经兑换过啦");

					if ($this->_wish_pig < 1000)
						throw new Exception("必须要集齐1000只财猪才能兑换哦");
					$data['FreeStatus'] = self::GIFT_FREE_SUCCESS;
					break;
			}
			//许愿
			$wishModel = new Model_Activity_Wish();

			$result = $wishModel->update($data, array("Phone =?" => $this->_phone));

			if ($result === false)
				throw new Exception("兑换失败");

			parent::succReturn("兑换成功");
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $wishLoginConf = array(
		array("phone", "/^[\d]{11}$/", "手机号格式不正确", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 通过手机号确定身份
	 */
	public function wishLoginAction()
	{
		try {
			if ($this->isApp())
				throw new Exception("你已经登陆啦");

			$is_login = $this->isWishLogin();
			if ($is_login === 1)
				throw new Exception("已经提交了");

			$wishModel = new Model_Activity_Wish();
			$phone = $wishModel->getInfoMix(array("Phone =?" => $this->_param['phone']), "Phone");
			if (is_null($phone)) {
				$result = $wishModel->insert(array("Phone" => $this->_param['phone']));
				if ($result === false)
					throw new Exception("提交失败");
			}

			$cookie = DM_Helper_Utility::authcode("{$this->_param['phone']}", 'ENCODE');
			setcookie(self::WISH_COOKIE, $cookie, time() + 86400 * 60, '/', '', false, true);

			parent::succReturn("");
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}
}