<?php

/**
 * 银行卡
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-18
 * Time: 下午1:44
 */
class Model_Wallet_WalletBankCard extends Zend_Db_Table
{
	protected $_name = 'wallet_bank_card';

	protected $_primary = 'BID';

	//银行对应logo
	static $_bank_list = array(
		"ICBC" => array('BankCode' => 'ICBC', 'BankName' => '工商银行', 'BankLogo' => 'http://img.caizhu.com/bank_ICBC.png'),
		"ABC" => array('BankCode' => 'ABC', 'BankName' => '农业银行', 'BankLogo' => 'http://img.caizhu.com/bank_ABC.png'),
		"BOC" => array('BankCode' => 'BOC', 'BankName' => '中国银行', 'BankLogo' => 'http://img.caizhu.com/bank_BOC.png'),
		"CCB" => array('BankCode' => 'CCB', 'BankName' => '建设银行', 'BankLogo' => 'http://img.caizhu.com/bank_CCB.png'),
		"PINGAN" => array('BankCode' => 'PINGAN', 'BankName' => '平安银行', 'BankLogo' => 'http://img.caizhu.com/bank_PINGAN.png?a=3'),
		"CIB" => array('BankCode' => 'CIB', 'BankName' => '兴业银行', 'BankLogo' => 'http://img.caizhu.com/bank_CIB.png'),
		"SPDB" => array('BankCode' => 'SPDB', 'BankName' => '浦发银行', 'BankLogo' => 'http://img.caizhu.com/bank_SPDB.png'),
		"HXB" => array('BankCode' => 'HXB', 'BankName' => '华夏银行', 'BankLogo' => 'http://img.caizhu.com/bank_HXB.png'),
		"CEB" => array('BankCode' => 'CEB', 'BankName' => '光大银行', 'BankLogo' => 'http://img.caizhu.com/bank_CEB.png'),
		"BCCB" => array('BankCode' => 'BCCB', 'BankName' => '北京银行', 'BankLogo' => 'http://img.caizhu.com/bank_BCCB.png'),
		"SHB" => array('BankCode' => 'SHB', 'BankName' => '上海银行', 'BankLogo' => 'http://img.caizhu.com/bank_SHB.png'),
		"ECITIC" => array('BankCode' => 'ECITIC', 'BankName' => '中信银行', 'BankLogo' => 'http://img.caizhu.com/bank_ECITIC.png'),
		"CMBC" => array('BankCode' => 'CMBC', 'BankName' => '民生银行', 'BankLogo' => 'http://img.caizhu.com/bank_CMBC.png'),
		//"CMBCHINA" => array('BankCode' => 'CMBCHINA', 'BankName' => '招商银行', 'BankLogo' => 'http://img.caizhu.com/bank_CMBCHINA.png'),
		"GZCB" => array('BankCode' => 'GZCB', 'BankName' => '广州银行', 'BankLogo' => 'http://img.caizhu.com/bank_GZCB.png'),
		"GDB" => array('BankCode' => 'GDB', 'BankName' => '广发银行', 'BankLogo' => 'http://img.caizhu.com/bank_GDB.png'),
		"POST" => array('BankCode' => 'POST', 'BankName' => '邮储银行', 'BankLogo' => 'http://img.caizhu.com/bank_POST.png'),
	);

	//易宝银行对应限额
	static $_bank_limit_1 = array(
		"ICBC" => array('v_month' => '20000', 'v_day' => '20000', 'v_single' => '10000',
			'm_month' => '20000', 'm_day' => '20000', 'm_single' => '10000'),
		"ABC" => array('v_month' => '500', 'v_day' => '500', 'v_single' => '500',
			'm_month' => '1000', 'm_day' => '1000', 'm_single' => '1000'),
		"BOC" => array('v_month' => '0', 'v_day' => '10000', 'v_single' => '10000',
			'm_month' => '0', 'm_day' => '10000', 'm_single' => '10000'),
		"CCB" => array('v_month' => '50000', 'v_day' => '10000', 'v_single' => '10000',
			'm_month' => '50000', 'm_day' => '10000', 'm_single' => '10000'),
		"PINGAN" => array('v_month' => '0', 'v_day' => '50000', 'v_single' => '50000',
			'm_month' => '0', 'm_day' => '50000', 'm_single' => '50000'),
		"CIB" => array('v_month' => '500', 'v_day' => '500', 'v_single' => '500',
			'm_month' => '1000', 'm_day' => '1000', 'm_single' => '1000'),
		"SPDB" => array('v_month' => '0', 'v_day' => '20000', 'v_single' => '20000',
			'm_month' => '0', 'm_day' => '20000', 'm_single' => '20000'),
		"HXB" => array('v_month' => '10000', 'v_day' => '10000', 'v_single' => '5000',
			'm_month' => '10000', 'm_day' => '10000', 'm_single' => '5000'),
		"CEB" => array('v_month' => '0', 'v_day' => '50000', 'v_single' => '50000',
			'm_month' => '0', 'm_day' => '30000', 'm_single' => '30000'),
		"BCCB" => array('v_month' => '10000', 'v_day' => '10000', 'v_single' => '5000',
			'm_month' => '10000', 'm_day' => '10000', 'm_single' => '5000'),
		"SHB" => array('v_month' => '20000', 'v_day' => '10000', 'v_single' => '5000',
			'm_month' => '20000', 'm_day' => '0000', 'm_single' => '5000'),
		"ECITIC" => array('v_month' => '150000', 'v_day' => '50000', 'v_single' => '20000',
			'm_month' => '150000', 'm_day' => '50000', 'm_single' => '20000'),
		"CMBC" => array('v_month' => '0', 'v_day' => '5000', 'v_single' => '1000',
			'm_month' => '0', 'm_day' => '5000', 'm_single' => '1000'),
		"CMBCHINA" => array('v_month' => '1000', 'v_day' => '1000', 'v_single' => '1000',
			'm_month' => '1000', 'm_day' => '1000', 'm_single' => '1000'),
		"GZCB" => array('v_month' => '20000', 'v_day' => '20000', 'v_single' => '20000',
			'm_month' => '20000', 'm_day' => '20000', 'm_single' => '20000'),
		"GDB" => array('v_month' => '20000', 'v_day' => '20000', 'v_single' => '20000',
			'm_month' => '20000', 'm_day' => '20000', 'm_single' => '20000'),
		"POST" => array('v_month' => '20000', 'v_day' => '20000', 'v_single' => '20000',
			'm_month' => '20000', 'm_day' => '20000', 'm_single' => '20000'),//不确定
	);

	/**
	 * 通过卡号获取银行卡信息
	 * 只返回特定信息 在绑卡时使用
	 * 返回重要信息  需要走签名
	 * @param $card_no
	 * @return array
	 */
	public function getCardInfoByNo($card_no, $fields = null)
	{
		$select = $this->select();
		if (is_null($fields))
			$select->from($this->_name, array("CardNo", "CardType", "BankName", "BankCode", "IsValid", "MemberID"));
		else
			$select->from($this->_name, (array)$fields);
		$select->where("CardNo =?", $card_no);

		$card_info = $this->_db->fetchRow($select);
		return empty($card_info) ? array() : count($card_info) == 1 ? current($card_info) : $card_info;
	}

	/**
	 * @param $member_id
	 * @param null $fields
	 * @return array|mixed
	 */
	public function getCardInfoByMID($member_id, $fields = null)
	{
		$select = $this->select();
		if (is_null($fields))
			$select->from($this->_name, array("CardNo", "CardType", "BankName", "BankCode", "IsValid"));
		else
			$select->from($this->_name, (array)$fields);
		$select->where("MemberID =?", $member_id);

		$card_info = $this->_db->fetchAll($select);
		return empty($card_info) ? array() : count($card_info) == 1 ? current($card_info) : $card_info;
	}

	/**
	 * 通过卡号主键ID获取卡的信息
	 * @param $bankId
	 * @return array
	 */
	public function getCardInfoById($bankId, $fields = null)
	{
		$select = $this->select();
		if (is_null($fields))
			$select->from($this->_name, array("CardNo", "CardType", "BankName", "BankCode", "Idcard", "Owner", "IsValid",'City'));
		else
			$select->from($this->_name, (array)$fields);
		$select->where("BID =?", $bankId);

		$card_info = $this->_db->fetchRow($select);
		return empty($card_info) ? array() : count($card_info) == 1 ? current($card_info) : $card_info;
	}
}