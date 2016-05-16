<?php

/**
 * 问财咨询主题支持城市
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-17
 * Time: 上午10:39
 */
class Model_Counsel_CounselSupportRegion extends Model_Common_Common
{
	protected $_name = 'counsel_support_region';
	protected $_primary = 'SRID';

	/**
	 * 根据主题ID获取支持的城市名称，用英文逗号分割，不存在返回空字符串
	 * @param $cid
	 * @return string
	 */
	public function getCityNameByCID($cid)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("{$this->_name} as s", null);
		$select->joinLeft("region as r", "s.Code=r.Code", "GROUP_CONCAT(r.Name) AS SupportCity");
		$select->where("s.CID =?", $cid);
		$select->group("s.CID");

		$result = $this->_db->fetchRow($select);
		return $result === false ? "" : $result['SupportCity'];
	}
    
    /**
	 * 根据主题ID获取支持的城市名称，返回数组
	 * @param $cid
	 * @return array
	 */
	public function getCityListByCID($cid)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from("{$this->_name} as s", null);
		$select->joinLeft("region as r", "s.Code=r.Code", array("r.Code",'r.Name',"r.RealCity",'r.FirstLetter'));
		$select->where("s.CID =?", $cid);

		return $this->_db->fetchAll($select);
	}
}
