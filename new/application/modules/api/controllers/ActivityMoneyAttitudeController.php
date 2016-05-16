<?php

/**
 * 金钱观
 * User: jepson <jepson@duomai.com>
 * Date: 16-4-1
 * Time: 上午11:19
 */
class Api_ActivityMoneyAttitudeController extends Action_Activity
{
//	protected $_conf = array(
//		"start_time" => "2016-01-25",
//		"end_time" => "2016-02-23",
//	);

	protected $user_info = null;

	public function init()
	{
		parent::init();
	}

	/**
	 * 获取code接口由客户端调用 客户端做个循环 直到监听到cookie 这里如果设置错误 会一直跳
	 * 微信网页授权回调接口
	 *
	 */
	public function wechatWebAuthAction()
	{
		$logger = $this->createLogger("activity_money_attitude", "wechat_web_auth");
		$auth_code = $this->_request->getParam("code");
		if (!is_null($auth_code)) {
			try {
				$user_info = DM_Wechat_Wechat::getInstance()->webAuth($auth_code);
				$logger->log("UserInfo:" . json_encode($user_info), Zend_Log::INFO);

				$userModel = new Model_Activity_MoneyAttitudeUser();

				//插入用户表
				$uid = $userModel->getInfoMix(array("Unionid =?" => $user_info['unionid']), "UID");
				$data = array(
					"HeadImgUrl" => $user_info['headimgurl'],
					"Country" => $user_info['country'],
					"City" => $user_info['city'],
					"Province" => $user_info['province'],
					"Sex" => $user_info['sex'],
					"Nickname" => $user_info['nickname'],
					"Openid" => $user_info['openid']
				);
				if (is_null($uid)) {
					$data['Unionid'] = $user_info['unionid'];
					$uid = $userModel->insert($data);
				} else {
					$data['UpdateTime'] = date("Y-m-d H:i:s", time());
					$userModel->update($data, array("Unionid =?" => $user_info['unionid']));
				}

				//设置cookie 不过期
				$this->setCookie($uid, 0);
				echo "success";
			} catch (Exception $e) {
				$logger->log("WebAuth failed,code:" . $e->getCode() . ",message:" . $e->getMessage(), Zend_Log::INFO);
			}
		} else {
			echo "code is null";
			$logger->log("Code is null", Zend_Log::INFO);
		}
	}

	protected $attitudeConf = array(
		array("aid", "number", "参数错误！", DM_Helper_Filter::EXISTS_VALIDATE),
	);

	/**
	 * 用户的问题详情页，两种情况
	 * 1.传ID
	 * 2.第一次默认给当前用户创建问题详情页或者复制问题页
	 *
	 */
	public function attitudeAction()
	{
		$this->isAuth();

		$attitude_info = array();
		$attitudeModel = new Model_Activity_MoneyAttitude();
		$questionModel = new Model_Activity_MoneyAttitudeQuestion();
		$answerModel = new Model_Activity_MoneyAttitudeAnswer();

		$is_create = true;
		if (!isset($this->_param['aid'])) {
			$result = $attitudeModel->getInfoMix(array("UID =?" => $this->user_info['UID']), "AID");
			if (!is_null($result))
				$this->_param['aid'] = $result;
			else
				$is_create = false;
		}

		//创建
		if (!$is_create) {
			$result = $attitudeModel->insert(array(
				"QID" => "1,2,3,4,5,6,7,8,9,10,11,12,13",
				"UID" => $this->user_info['UID']
			));
			if ($result !== false) {
				$attitude_info = array(
					"AID" => $result,
					"QID" => "1,2,3,4,5,6,7,8,9,10,11,12,13",
					"UID" => $this->user_info['UID'],
					"HeadImgUrl" => $this->user_info['HeadImgUrl'],
					"Nickname" => $this->user_info['Nickname']
				);
			}
		} else {
			$select = $attitudeModel->select()->setIntegrityCheck(false);
			$select->from("activity_money_attitude as a", array("a.AID", "a.QID"));
			$select->where("a.AID =?", $this->_param['aid']);
			$select->joinLeft("activity_money_attitude_user as u", "a.UID=u.UID", array("u.UID", "u.HeadImgUrl", "u.Nickname"));
			$attitude_info = $attitudeModel->fetchRow($select);
			$attitude_info = !empty($attitude_info) ? $attitude_info->toArray() : array();
		}

		if (!empty($attitude_info)) {
			//此人观点详情
			$attitude_info['QuestionInfo'] = $questionModel->getInfoMix(array(), array("QID", "Title", "AnswerA", "AnswerB"), "Assoc");

			//观点答案
			if ($is_create) {
				$select = $answerModel->select()->setIntegrityCheck(false);
				$select->from("activity_money_attitude_answer as a", array("a.QID", "a.UID", "a.Answer"));
				$select->where("a.AID =?", $attitude_info['AID']);
				$select->joinLeft("activity_money_attitude_user as u", "a.UID=u.UID", array("u.HeadImgUrl", "u.Nickname"));
				$answer_info = $answerModel->fetchAll($select);
				if (!empty($answer_info)) {
					$answer_info = $answer_info->toArray();
					foreach ($answer_info as $answer)
						$attitude_info['QuestionInfo'][$answer['QID']]['AnswerInfo'][$answer['UID']] = $answer;
				}
			}
		}

		parent::succReturn($attitude_info);
	}


	protected $choiceConf = array(
		array("aid", "number", "请选择问题页！", DM_Helper_Filter::MUST_VALIDATE),
		array("qid", "number", "请选择问题！", DM_Helper_Filter::MUST_VALIDATE),
		array("answer", "1,2", "请选择答案！", DM_Helper_Filter::MUST_VALIDATE, "in", "1"),
	);

	/**
	 * 选择答案
	 */
	public function choiceAction()
	{
		$this->isAuth();

		try {
			$answerModel = new Model_Activity_MoneyAttitudeAnswer();
			$result = $answerModel->getInfoMix(array(
				"AID =?" => $this->_param['aid'],
				"UID =?" => $this->user_info['UID'],
				"QID =?" => $this->_param['qid']
			), "Answer");
			if (is_null($result)) {
				$result = $answerModel->insert(array(
					"AID" => $this->_param['aid'],
					"UID" => $this->user_info['UID'],
					"QID" => $this->_param['qid'],
					"Answer" => $this->_param['answer']
				));

				if ($result === false)
					throw new Exception("选择失败！");
			} else {
				throw new Exception("你已经选择过答案了！");
			}

			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $rankingConf = array(
		array("aid", "number", "请选择问题页！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 *
	 * 匹配度排行
	 * 没有回答记录 1
	 * 只有别人 列出回答人列表 2
	 * 只有自己 分享 3
	 * 两者都有 计算排行榜 4
	 */
	public function rankingAction()
	{
		$this->isAuth();

		try {
			$answerModel = new Model_Activity_MoneyAttitudeAnswer();
			$answer_total = $answerModel->getInfoMix(array("AID =?" => $this->_param['aid']), "count(DISTINCT UID)");

			$ranking_info = array(
				"AnswerTotal" => $answer_total,
				"AnswerInfo" => array(),
				"Type" => 1
			);

			if ($answer_total > 0) {
				$user_total = $answerModel->getInfoMix(array(
					"AID =?" => $this->_param['aid'],
					"UID =?" => $this->user_info['UID']
				), "count(1)");
				if ($user_total > 0) {
					if ($answer_total > 1) {//计算排行榜
						$select = $answerModel->select()->setIntegrityCheck(false);
						$select->from("activity_money_attitude_answer", array("UID", "GROUP_CONCAT(CONCAT(QID,Answer)) AS Answer"));
						$select->where("AID =?", $this->_param['aid']);
						$select->group("UID");

						$ranking_list = $answerModel->getAdapter()->fetchPairs($select);
						$current_ranking = explode(",", $ranking_list[$this->user_info['UID']]);
						unset($ranking_list[$this->user_info['UID']]);

						//计算排行榜
						$answer_info = array();
						foreach ($ranking_list as $uid => $answer) {
							$match = count(array_intersect(explode(",", $answer), $current_ranking));
							$answer_info[$uid] = round($match / 13, 2) * 100;
						}

						arsort($answer_info);
						//加上头像
						$userModel = new Model_Activity_MoneyAttitudeUser();
						$head_img = $userModel->getInfoMix(array("UID IN(?)" => array_keys($answer_info)),
							array("UID", "HeadImgUrl"), "Pairs");

						foreach ($answer_info as $uid => &$info) {
							$info = array(
								"match" => $info,
								"HeadImgUrl" => $head_img[$uid]
							);
						}

						$ranking_info['AnswerInfo'] = $answer_info;
						$ranking_info['Type'] = 4;
					} else {//只有自己
						$ranking_info['Type'] = 3;
					}
				} else {//只有别人
					//回答列表
					$select = $answerModel->select()->setIntegrityCheck(false);
					$select->from("activity_money_attitude_answer as a", array("DISTINCT(a.UID)"));
					$select->where("a.AID =?", $this->_param['aid']);
					$select->order("a.CreateTime DESC");
					$select->joinLeft("activity_money_attitude_user as u", "a.UID=u.UID", array("u.HeadImgUrl"));
					$answer_info = $answerModel->getAdapter()->fetchAll($select);

					$ranking_info['AnswerInfo'] = $answer_info;
					$ranking_info['Type'] = 2;
				}
			}//没有人回答

			parent::succReturn($ranking_info);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $stateConf = array(
		array("type", "1", "参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

	/**
	 * 统计数据
	 */
	public function stateAction()
	{
		$this->isAuth();

		switch ($this->_param['type']) {
			case "1":
				if ($this->user_info['IsDown'] != 1) {
					$userModel = new Model_Activity_MoneyAttitudeUser();
					$userModel->update(array("IsDown" => 1), array("UID =?" => $this->user_info['UID']));
				}
				break;
		}

		parent::succReturn(array());
	}

	/**
	 * 获取当前用户信息
	 */
	public function userInfoAction()
	{
		parent::succReturn($this->isAuth());
	}

	/**
	 * 判断是否授权 没有授权 返回-100 客户端调用接口获取code 回调 wechatWebAuth
	 */
	protected function isAuth()
	{
		$userModel = new Model_Activity_MoneyAttitudeUser();
		//$this->getCookie()
		$this->user_info = $userModel->getInfoMix(array("UID =?" => 1));

		if (is_null($this->user_info))
			parent::failReturn("请授权", self::STATUS_NEED_LOGIN);

		return $this->user_info;
	}
}