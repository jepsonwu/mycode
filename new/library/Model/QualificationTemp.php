<?php

/**
 * 资质临时表 kitty
 */
class Model_QualificationTemp extends Zend_Db_Table
{
	protected $_name = 'financial_qualification_temp';
	protected $_primary = 'FinancialQualificationID';


	/**
	 * 根据认证ID获取资质信息
	 * @param string $channelName
	 */

	public function getInfoByqualificationID($id, $limit = null, $status = null, $order = 'FinancialQualificationID desc', $field = null)
	{
		$select = $this->select();
		if(!is_null($field)){
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field)->where('AuthenticateID = ?', $id);
		}else{
			$select->from($this->_name)->where('AuthenticateID = ?', $id);
		}

		if (!is_null($status)) {
			$select->where('CheckStatus = ?', $status);
		}
		$select->order($order);
		if (is_null($limit)) {
			$result = $select->query()->fetchAll();
		} else {
			if($limit >1){
				$result = $select->limit($limit)->query()->fetchAll();
			}else{
				$result = $select->limit($limit)->query()->fetch();
			}
		}
		return $result;
	}


	/**
	 * 根据ID获取资质信息
	 * @param string $channelName
	 */
	public function getInfoByID($id)
	{
		$select = $this->select();
		$select->from($this->_name)->where('FinancialQualificationID = ?', $id);
		$result = $select->query()->fetch();
		return !empty($result) ? $result : array();
	}

	/*
	 *获取用户资料页显示的资质
	 */
	public function getDisplayQualification($authenticateID)
	{
		$select = $this->select()->from($this->_name,array('FinancialQualificationID','FinancialQualificationType'))
						->where('AuthenticateID = ?', $authenticateID)
						->where('CheckStatus = ?', 1)
						->where('IsDisplayInPersonalProfile = ?',1);
		$qualificationInfo = $select->query()->fetch();
		return !empty($qualificationInfo)?$qualificationInfo:array();
	}
} 