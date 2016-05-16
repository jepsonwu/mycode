<?php
/**
 *  获取城市编码
 * @author Mark
 *
 */
class DM_Module_Region extends DM_Module_Base
{
	/**
	 * 获取城市编码
	 */
	public static function getCityCode($city,$provice = '')
	{
		$specialArray = array('北京','天津','上海','重庆');
		foreach($specialArray as $sa){
			if(strpos($provice,$sa) !== false){
				$city = $sa;
			}
		}
		
		
		$redisObj = DM_Module_Redis::getInstance();
		$originCityDataKey = 'Origin_City_Data';
		$cityDataLen = $redisObj->zCount($originCityDataKey,1,100000000);
		if(empty($cityDataLen)){
			$adapter = self::getDb();
			$sql = 'select Code,Name from region where Level = 1';
			$res = $adapter->query($sql)->fetchAll();
			if(!empty($res)){
				foreach($res as $item){
					$cityCode = $item['Code'];
					$cityName = str_replace('市','',$item['Name']);
					$redisObj->zAdd($originCityDataKey,$cityCode,$cityName);
				}
			}
		}
		
		$city = str_replace('市','',$city);
		$code = $redisObj->zScore($originCityDataKey,$city);
		return $code ? $code : 0;
	}
}