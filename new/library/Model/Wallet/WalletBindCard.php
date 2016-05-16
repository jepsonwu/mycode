<?php

/**
 * 用户绑卡信息
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午12:43
 */
class Model_Wallet_WalletBindCard extends Zend_Db_Table
{
	protected $_name = 'wallet_bind_card';

	protected $_primary = 'BCID';

	//支付渠道
	const BIND_CHANNEL_YEE = 1;
	const BIND_CHANNEL_LL = 2;
	const BIND_CHANNEL_ALI = 3;

	//邦卡状态
	const BIND_STATUS_TRUE = 1;
	const BIND_STATUS_FALSE = 0;

	/**
	 * 获取用户绑卡信息
	 * 包含绑卡过期的
	 * @param $member_id
	 * @param bool $is_valid 是否获取有效的  否则获取最近一条
	 * @return array
	 */
	public function getBindCardByMID($member_id, $is_valid = true)
	{
		$select = $this->select()->setIntegrityCheck(false);

		$select->from($this->_name . " as bc", array("bc.BCID", "bc.PayChannel", "bc.ValidityPeriod", "bc.BID"));
		$select->where("bc.MemberID =?", $member_id);

		if ($is_valid) {
			$select->where("bc.Status =?", 1);
			$select->where("bc.ValidityPeriod >=?", time());
		} else {
            $select->order("bc.Status desc");
			$select->order("BCID desc");
			$select->limit(1);
		}

		//先用Join吧 后期改为分开查
		$select->join("wallet_bank_card as c", "bc.BID = c.BID",
			array("c.CardNoLastFour", "c.CardType", "SUBSTR(c.Phone,-4) as PhoneLastFour",
				"SUBSTR(c.Owner,-1) as Owner", "c.BankName", "c.BankCode",'c.City'));

		$bind_info = $this->_db->fetchAll($select);
		return $bind_info;
	}

	/**
	 * @param $member_id
	 * @param int $pay_channel
	 * @param null $fields
	 * @param int $status
	 * @param null $bid
	 * @return array|mixed
	 */
	public function getBindInfoByMP($member_id, $pay_channel = self::BIND_CHANNEL_YEE, $fields = null, $status = 1, $bid = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		$select->where("MemberID =?", $member_id);
		$select->where("PayChannel =?", $pay_channel);
		!is_null($bid) && $select->where("BID =?", $bid);
		!is_null($status) && $select->where("Status =?", $status);

		$bind_info = $this->_db->fetchAll($select);
		return empty($bind_info) ? array() :
			(count($bind_info) == 1 ? current($bind_info) : $bind_info);
	}

	/**
	 * 根据ID获取信息
	 * @param $bcid
	 * @param null $fields string|array
	 * @return array|mixed
	 */
	public function getInfoByID($bcid, $fields = null, $status = 1)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		$select->where("BCID =?", $bcid);
		!is_null($status) && $select->where("Status =?", $status);

		$bind_card_info = $this->_db->fetchRow($select);
		return empty($bind_card_info) ? array() :
			(count($bind_card_info) == 1 ? current($bind_card_info) : $bind_card_info);
	}
}
