<?php

/**
 * 达人模块
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-27
 * Time: 上午11:21
 */
class Api_BestController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}

	protected $applyTitleConf = array(
		array("bID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("info", "/[\w]{32}/", "认证详情错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("status", "0,1", "认证状态错误!", DM_Helper_Filter::MUST_VALIDATE, "in")
	);

	/**
	 * 认证头衔
	 */
	public function applyTitleAction()
	{
		try {
			$this->isPostOutput();

			$bestModel = new Model_Best_Best();
			$best_info = $bestModel->getByID($this->_param['bID'], "MemberID,InviteCode,TID,Status");

			if (!$best_info)
				throw new Exception("认证头衔信息不存在");

			if ($best_info['Status'] != $bestModel::STATUS_APPROVE)
				throw new Exception("头衔已经认证");

			//校验安全性
			if ($this->_param['info'] != $bestModel->getHash($best_info))
				throw new Exception("非法操作");

			$memberModel = new DM_Model_Account_Members();
			$member_id = $this->memberInfo->MemberID;
			$is_best = $memberModel->getMemberInfoCache($member_id, "IsBest");
			//如果状态为0 不允许认证
			if ($member_id != $best_info['MemberID'])
				throw new Exception("非法操作");

			//认证
			$db = $bestModel->getAdapter();
			$db->beginTransaction();
			try {
				//修改会员达人状态
				if ($is_best == $memberModel::BEST_STATUS_FAIL) {
					$res = $memberModel->updateInfo($member_id,
						array("IsBest" => ($this->_param['status'] == 1) ?
							$memberModel::BEST_STATUS_TRUE : $memberModel::BEST_STATUS_FAIL));
					if ($res===false)
						throw new Exception("认证失败");

					$memberModel->deleteCache($member_id);
				}

				//修改该达人头衔状态
				$res = $bestModel->update(array(
						"Status" => ($this->_param['status'] == 1) ? $bestModel::STATUS_TRUE : $bestModel::STATUS_FIAL,
						"UpdateTime" => date("Y-m-d H:i:s"))
					, array("BID = ?" => $this->_param['bID']));
				if ($res===false)
					throw new Exception("认证失败");

				$db->commit();
				parent::succReturn("认证成功");
			} catch (Exception $e) {
				$db->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $cancelTitleConf = array(
		array("bID", "/([\d]+[,]?)+/", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("info", "/[\w]{32}/", "撤销详情错误！", DM_Helper_Filter::MUST_VALIDATE),
		array("status", "0,1", "状态错误!", DM_Helper_Filter::MUST_VALIDATE, "in")
	);

	/**
	 * 可以撤销一个或多个
	 * 注销头衔
	 */
	public function cancelTitleAction()
	{
		try {
			$this->isPostOutput();

			$bestModel = new Model_Best_Best();
			$bid = explode(",", trim($this->_param['bID'], ","));
			$best_info = $bestModel->getCancelInfo($bid);

			if (count($best_info) != count($bid))
				throw new Exception("头衔不能撤销");

			//校验安全性
			if ($this->_param['info'] != $bestModel->getHash($best_info))
				throw new Exception("非法操作");

			$memberModel = new DM_Model_Account_Members();
			$member_id = $this->memberInfo->MemberID;

			$is_best = $memberModel->getMemberInfoCache($member_id, "IsBest");
			//如果状态为0 不允许认证
			if ($is_best != $memberModel::BEST_STATUS_TRUE)
				throw new Exception("非法操作");

			//认证
			$db = $bestModel->getAdapter();
			$db->beginTransaction();
			try {
				//修改会员达人状态
				$true_count = $bestModel->countBestByMemberID($member_id, $bestModel::STATUS_TRUE) +
					$bestModel->countBestByMemberID($member_id, $bestModel::STATUS_CANCEL);

				if ($this->_param['status'] == 1 && $true_count == 1) {
					$res = $memberModel->updateInfo($member_id, array("IsBest" => $memberModel::BEST_STATUS_FAIL));
					if (!$res)
						throw new Exception("取消失败");

					$memberModel->deleteCache($member_id);
				}

				//修改该达人头衔状态
				$res = $bestModel->update(array(
						"Status" => ($this->_param['status'] == 1) ? $bestModel::STATUS_FIAL : $bestModel::STATUS_TRUE,
						"UpdateTime" => date("Y-m-d H:i:s"))
					, array("BID in(?)" => $bid));
				if (!$res)
					throw new Exception("取消失败");

				$db->commit();
				parent::succReturn("取消成功");
			} catch (Exception $e) {
				$db->rollBack();
				parent::failReturn($e->getMessage());
			}
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 获取头衔
	 */
	public function titlesAction()
	{
		try {
			parent::isGet();

			$titleModel = new Model_Best_BestTitle();
			parent::succReturn(array("Rows" => $titleModel->getAllTitle("fetchAll")));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}

	}

	protected $getMemberTitlesConf = array(
		array("memberID", "number", "参数错误!", DM_Helper_Filter::MUST_VALIDATE),
		array("status", "0,1,2,3", "状态错误!", DM_Helper_Filter::EXISTS_VALIDATE, "in")
	);

	/**
	 * 返回当前登陆用户的达人头衔
	 * 默认返回除状态失效的头衔 可以指定状态
	 */
	public function getMemberTitlesAction()
	{
		try {
			$bestModel = new Model_Best_Best();
			$best_info = $bestModel->getBestInfoByMemberID(array($this->_param['memberID']), isset($this->_param['status']) ? $this->_param['status'] : null);

			parent::succReturn(array("Rows" => current($best_info)));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}
}