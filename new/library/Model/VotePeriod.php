<?php
/**
 *  投票期数管理
 * @author Kitty
 *
 */
class Model_VotePeriod extends Zend_Db_Table
{
	protected $_name = 'vote_period';
	protected $_primary = 'PeriodID';


	/**
	 *  获取活动期数
	 */
	public function getPeriods()
	{
		$select = $this->select()->from($this->_name,array('PeriodID','PeriodName'));
		$periodInfo = $select->order('PeriodID desc')->query()->fetchAll();
		return !empty($periodInfo) ? $periodInfo : array();
	}

	/*
	 *获取当前期数
	 */
	public function getCurrentPeriod()
	{
		return $this->select()->from($this->_name)->where('Status = ?',1)->query()->fetch();
	}

	/*
	 *获取上期信息
	 */
	public function getLastPeriodInfo($periodID)
	{
		return $this->select()->from($this->_name)->where('PeriodID < ?',$periodID)->limit(1)->order('PeriodID desc')->query()->fetch();
	}

	/*
	 *获取当前期数详情
	 */
	public function getPeriodInfo($periodID)
	{
		return $this->select()->from($this->_name)->where('PeriodID = ?',$periodID)->query()->fetch();
	}
}