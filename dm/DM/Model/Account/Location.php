<?php
/**
 * 定位相关
 * @author Jeff
 *
 */
class DM_Model_Account_Location extends Zend_Db_Table
{
	protected $_name = 'member_location';
	protected $_primary = 'LID';

	/**
	 * 初始化数据库
	 */
	public function __construct()
	{
		$udb = DM_Controller_Front::getInstance()->getDb('udb');
		$this->_setAdapter($udb);
	}
	
	/**
	 * 根据当前经纬度以及范围获取4个临界的经纬度
	 * @param unknown $myLng
	 * @param unknown $myLat
	 * @param unknown $distance
	 * @return multitype:number 
	 */
	public function getRange($myLng,$myLat,$distance)
	{
		$range = 180 / pi() * $distance / 6372.797;      //里面的 $distance 就代表搜索 $distancekm 之内，单位km
		$lngR = $range / cos($myLat * pi() / 180.0);
		$maxLat = $myLat + $range;
		$minLat = $myLat - $range;
		$maxLng = $myLng + $lngR;
		$minLng = $myLng - $lngR;
		return array('maxLat'=>$maxLat,'minLat'=>$minLat,'maxLng'=>$maxLng,'minLng'=>$minLng);
	}
}