<?php

/**
 * 充值订单表
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-22
 * Time: 下午1:20
 */
class Model_Wallet_WalletOrderRecharge extends Zend_Db_Table
{
	protected $_name = 'wallet_order_recharge';

	protected $_primary = 'ORID';

	/**
	 * 根据ID获取信息
	 * @param $order_id
	 * @param null $fields string|array
	 * @return array|mixed
	 *
	 */
	public function getInfoByOrderNo($order_id, $fields = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		$select->where("OrderNo =?", $order_id);

		$bind_card_info = $this->_db->fetchRow($select);
		return empty($bind_card_info) ? array() :
			(count($bind_card_info) == 1 ? current($bind_card_info) : $bind_card_info);
	}

	/**
	 * 获取用户当月|当日充值总额
	 * @param $member_id
	 * @param $bcid
	 * @param $time
	 * @return int|string
	 */
	public function getMonthTotalByMID($member_id, $bcid, $time)
	{
		$select = $this->select();
		$select->from($this->_name, "SUM(Amount) AS amount");

		$select->where("MemberID =?", $member_id);
		$select->where("BCID =?", $bcid);
		$select->where("Status =?", DM_Model_Table_Finance_Order::ORDER_STATUS_DONE);
		$select->where("CreateTime LIKE ?", $time . "%");

		$total = $this->_db->fetchOne($select);
		return empty($total) ? 0 : $total;
	}
}