<?php

/**
 * 理财师 扩展信息表
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-18
 * Time: 上午11:07
 */
class Model_Financial_FinancialPlannerInfo extends Model_Common_Common
{
	protected $_name = 'financial_planner_info';
	protected $_primary = 'FPID';

	/**
	 *  根据ID获取理财师扩展信息
	 * @param int $FPID
	 */
	public function getFinancialInfoByID($FPID)
	{
		return $this->select()->from($this->_name)->where('FPID = ?',$FPID)->query()->fetch();
	}

	/**
	 *  根据用户ID获取理财师扩展信息
	 * @param int $memberID
	 */
	public function getFinancialInfoByMemberID($memberID)
	{
		return $this->select()->from($this->_name)->where('MemberID = ?',$memberID)->query()->fetch();
	}

}