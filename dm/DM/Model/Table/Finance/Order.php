<?php

/**
 * 用户资金账户
 * @author Mark
 *
 */
class DM_Model_Table_Finance_Order extends DM_Model_Table
{
	protected $_name = 'wallet_order_list';
	protected $_primary = 'OID';

	const CURRENCY_CNY = 'CNY';
	const CURRENCY_USD = 'USD';
	const CURRENCY_EURO = 'EURO';

	//收支类型
	const AMOUNT_TYPE_IN = 1;
	const AMOUNT_TYPE_OUT = 2;

	//订单类型
	const ORDER_TYPE_RECHARGE = 1;//充值
	const ORDER_TYPE_WITHDRAW = 2;//提现
	const ORDER_TYPE_TRANSFER = 3;//转账
	const ORDER_TYPE_LUCKY_MONEY = 4;//红包
	const ORDER_TYPE_PRESENT = 5;//送礼

	//订单状态
	const ORDER_STATUS_FAILED = 0;
	const ORDER_STATUS_DOING = 1;
	const ORDER_STATUS_DONE = 2;
	const ORDER_STATUS_CLOSE = 3;

	//订单角色
	const ORDER_ROLE_SENDER = 1;
	const ORDER_ROLE_RECEIVER = 2;

	//账户类型
	private $allowAmountType = array('CNY', 'USD', 'EURO');

	/**
	 * 判断账户类型
	 * @param $currency
	 * @return bool
	 */
	private function checkMemberAmountType($currency)
	{
		return in_array($currency, $this->allowAmountType);
	}

	static public function getOrderSn()
	{
		//return "1234561138086901752" . rand(1000, 9000);
		//return date('YmdHis').'00'.$type.'00'.$orderType.$Amount;
		$mString = microtime(true);
		$msec = str_pad(substr($mString, strpos($mString, '.') + 1), 4, '0', STR_PAD_RIGHT);
		return substr(date('Y'), -2) . date('mdHis') . $msec . mt_rand(1000, 9999);
	}

	/**
	 * 创建订单
	 * $member_id 会员编号
	 * $type 类型，1收入，2支出
	 * $orderType 订单类型
	 * $Currency 货币类型
	 * $Amount 金额
	 * $Status 状态
	 * $role 角色类型
	 * $FromObj
	 * $RelationType 关联订单类型，退款的时候用到
	 */
	public function createOrder($member_id, $type, $orderSn, $orderType, $Currency, $Amount, $Status, $role, $FromObj, $ip = '', $ProductNo = '0', $Remark = '',$RelationType = 0)
	{
		$modelAmount = new DM_Model_Table_Finance_Amount();
		$modelAmount->memberAmountsInit($member_id);
		$amountInfo = $modelAmount->getMemberAmountInfo($member_id, $Currency);

		$balance = $type == 1 ? ($amountInfo['Balance'] + $Amount) : ($amountInfo['Balance'] - $Amount);
		$freezeAmount = $orderType == 2 ? ($amountInfo['FreezeAmount'] + $Amount) : $amountInfo['FreezeAmount'];

		$data = array(
			'MemberID' => $member_id,
			'OrderNo' => $orderSn,
			'Type' => $type,
			'OrderType' => $orderType,
			'Currency' => $Currency,
			'Amount' => $Amount,
			'Balance' => $balance,
			'FreezeAmount' => $freezeAmount,
			'ProductNo' => $ProductNo,
			'Status' => $Status,
			'IP' => $ip,
			'Remark' => $Remark,
			'Role' => $role,
			'FromObj' => $FromObj,
			'RelationType' => $RelationType

		);
		return $this->insert($data);
	}

	/**
	 * 处理订单状态
	 * 只适合订单号唯一的
	 * @param $value
	 * @param $status
	 * @param string $key
	 * @return bool
	 *
	 */
	public function disposeOrder($value, $status, $key = 'OrderNo')
	{
		if ($status == self::ORDER_STATUS_DOING)
			return false;

		$method = ($key == 'OrderNo') ? 'getInfoByOrderNo' : 'getInfoByOID';
		$order_info = $this->$method($value, array("Status", "OID", "IP"));

		if (!$order_info || $order_info['Status'] != self::ORDER_STATUS_DOING)
			return false;

		$res = $this->update(array("Status" => $status), array($key . " =?" => $value));
		if ($res === false)
			return false;

		return $order_info;
	}


	/**
	 * 根据订单号获取信息
	 * @param $order_id
	 * @param null $fields
	 * @return array|mixed
	 */
	public function getInfoByOrderNo($order_id, $fields = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		$select->where("OrderNo =?", $order_id);

		$order_info = $this->_db->fetchAll($select);
		return empty($order_info) ? array() :
			(count($order_info) == 1 ? current($order_info) : $order_info);
	}

	/**
	 * 根据订单号获取信息
	 * @param $oid
	 * @param null $fields
	 * @return array|mixed
	 *
	 */
	public function getInfoByOID($oid, $fields = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		$select->where("OID =?", $oid);

		$order_info = $this->_db->fetchRow($select);
		return empty($order_info) ? array() :
			(count($order_info) == 1 ? current($order_info) : $order_info);
	}
}