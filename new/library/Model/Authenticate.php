<?php

/**
 * 认证 kitty
 */
class Model_Authenticate extends Zend_Db_Table
{
	protected $_name = 'member_authenticate';
	protected $_primary = 'AuthenticateID';

	/**
	 * 根据ID获取认证信息
	 * @param string $channelName
	 */
	public function getInfoByMemberID($memberID, $status = null, $field = null)
	{
		$select = $this->select();
		if (is_null($field))
			$select->from($this->_name);
		else
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field);

		$select->where('MemberID = ?', $memberID);
		if (!is_null($status)) {
			$select->where('Status = ?', $status);
		}

		$result = $select->query()->fetch();

		if (!empty($result) && isset($result['AuthenticateType'])) {
			if (in_array($result['AuthenticateType'], array(1, 2))) {
				$result['Direction'] = "帐号主体依法享有本帐号产生的权利和收益，同时也对帐号的所有行为承担全部责任。认证信息来源于帐号主体向小麦金融提交的主体信息、资质文件等，并由帐号主体确保其真实合法，小麦金融已进行合理必要的甄别和核实。
    ";
			} elseif (in_array($result['AuthenticateType'], array(3, 4))) {
				$result['Direction'] = "帐号主体依法享有本帐号产生的权利和收益，同时也对帐号的所有行为承担全部责任。认证信息来源于帐号主体向小麦金融提交的主体信息、资质文件等，并由帐号主体确保其真实合法，小麦金融已进行合理必要的甄别和核实。
    ";
			}
		}

		return !empty($result) ? $result : array();
	}

	/**
	 * 根据ID获取认证信息
	 * @param string $channelName
	 */
	public function getInfoByID($id)
	{
		$select = $this->select();
		$select->from($this->_name)->where('AuthenticateID = ?', $id);
		$result = $select->query()->fetch();
		return !empty($result) ? $result : array();
	}

	/*
	 *根据身份证号码获取认证数量
	 */
	public function getCountByIDCard($v)
	{
		$select = $this->select();
		$select->from($this->_name)->where('IDCard = ?', $v)->where('Status = ?', 1);
		$result = $select->query()->fetchAll();
		return !empty($result) ? count($result) : 0;
	}

	/*
	 *根据营业执照注册号获取认证数量
	 */
	public function getCountByBusinessLicenseNumber($v)
	{
		$select = $this->select();
		$select->from($this->_name)->where('BusinessLicenseNumber = ?', $v)->where('Status = ?', 1);
		$result = $select->query()->fetchAll();
		return !empty($result) ? count($result) : 0;
	}

	/*
	 *根据组织机构代码获取认证数量
	 */
	public function getCountByOrganizationCode($v)
	{
		$select = $this->select();
		$select->from($this->_name)->where('OrganizationCode = ?', $v)->where('Status = ?', 1);
		$result = $select->query()->fetchAll();
		return !empty($result) ? count($result) : 0;
	}


	/**
	 * 添加
	 */
	public function add($ret)
	{
		return $this->insert($ret);
	}

	/**
	 * 编辑
	 */
	public function edit($ret, $authenticateID)
	{
		return $this->update($ret, array('AuthenticateID = ? ' => $authenticateID));
	}

} 
