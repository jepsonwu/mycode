<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-27
 * Time: 下午12:57
 */
class Model_Best_Best extends Zend_Db_Table
{
	//达人头衔申请记录状态
	const STATUS_FIAL = 0;
	const STATUS_APPROVE = 1;
	const STATUS_TRUE = 2;
	const STATUS_CANCEL = 3;

	protected $_name = 'best';
	protected $_primary = 'BID';

	/**
	 * 通过id获取达人信息
	 * @param $bid
	 * @param null $field
	 * @return array
	 */
	public function getByID($bid, $field = null)
	{
		$select = $this->select();
		if (is_null($field))
			$select->from($this->_name);
		else
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field);
		$select->where("BID = ?", $bid);

		$result = $this->_db->fetchRow($select);
		return $result;
	}

	/**
	 * 返回可以取消的头衔
	 * @param $bid
	 * @return array|Zend_Db_Table_Rowset_Abstract
	 */
	public function getCancelInfo($bid, $status = self::STATUS_CANCEL)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("best", array('MemberID', 'InviteCode', 'TID'));
		$select->where("BID in(?)", $bid);
		$select->where("Status = ?", $status);
		$best_info = $this->_db->fetchAll($select);

		return empty($best_info) ? array() : $best_info;
	}

	/**
	 * 获取达人头衔校验hash
	 * @param $best_info
	 * @return string
	 */
	public function getHash($best_info)
	{
		if (is_array(current($best_info)))
			$hash = md5(serialize($best_info));
		else
			$hash = md5($best_info['MemberID'] . $best_info['InviteCode'] . $best_info['TID']);

		return $hash;
	}

	/**
	 * 根据会员ID返回达人信息
	 * @param array $member_id
	 * @return array
	 */
	public function getBestInfoByMemberID(array $member_id, $status = null, $eq = true)
	{
		$best_info = array();
		if (!empty($member_id)) {
			$select = $this->select()->setIntegrityCheck(false);
			$select->from("best as b", array("b.MemberID", "b.TID", "b.Status"));
			$select->joinLeft("best_title as t", "b.TID=t.TID", "t.Name");

			if (is_null($status)){
				$select->where("b.Status != ?", self::STATUS_FIAL);

			}elseif(is_array($status)){
				$select->where("b.Status in (?)", $status);
			}else{
				$select->where("b.Status " . ($eq ? "" : "!") . "= ?", $status);
			}
			$select->where("b.MemberID in (?)", $member_id);

			$select->order("b.UpdateTime desc");

			$best_res = $this->_db->fetchAll($select);
			if (!empty($best_res)) {
				foreach ($best_res as $val)
					$best_info[array_shift($val)][] = $val;
			}
		}

		return $best_info;
	}

	/**
	 * 根据member_id返回各状态头衔
	 * @param int $member_id
	 * @param null $status
	 * @param bool|true $eq
	 * @return int|string
	 */
	public function countBestByMemberID($member_id, $status = null, $eq = true)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("best", "count(1)");

		if (is_null($status))
			$select->where("Status != ?", self::STATUS_FIAL);
		elseif(is_array($status)){
			$select->where("Status in (?)", $status);
		}else{
			$select->where("Status " . ($eq ? "" : "!") . "= ?", $status);
		}
		
		$select->where("MemberID =?", $member_id);

		$best_res = $this->_db->fetchOne($select);
		return empty($best_res) ? 0 : $best_res;
	}

	/**
	 * 获取某个人最新认证的达人
	 */
	public function getNewBestInfo($memberID)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("best as b", array("b.MemberID", "b.TID", "b.Status"));
		$select->joinLeft("best_title as t", "b.TID=t.TID", "t.Name");
		$select->where("b.Status >= ?", self::STATUS_TRUE);
		$select->where("b.MemberID = ?", $memberID);
		$select->order('b.UpdateTime desc');
		$info = $select->limit(1)->query()->fetch();
		return empty($info) ? array() : $info;
	}
}