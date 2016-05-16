<?php
/**
 * 专栏
 *
 * @author Jeff
 *
 */
class Model_Column_MemberSubscribe extends Zend_Db_Table
{
	protected $_name = 'column_member_subscribe';
	protected $_primary = 'SID';
	
	public static function getUserSubscribeKey($memberID)
	{
		return 'User:Column:MemberID:'.$memberID;
	}
	
	public static function getSubscribeKey($columnID)
	{
		return 'User:Column:'.$columnID;
	}

	/**
	 * 添加订阅
	 * @param int $column 专栏ID
	 * @param int $memberID 用户ID
	 * @return boolean
	 */
	public function addSubscribe($columnID,$memberID)
	{
		$hasExists = $this->getInfo($columnID,$memberID);
		if(empty($hasExists)){
			$data = array('MemberID'=>$memberID,'columnID'=>$columnID);
			$insertID = $this->insert($data);
			if($insertID > 0){
				//增加订阅数
				$columnModel = new Model_Column_Column();
				$columnModel->increaseSubscribeNum($columnID);
				//保存信息至Redis
 				$redisObj = DM_Module_Redis::getInstance();
				//某个专栏的订阅用户
				$cacheKey = self::getSubscribeKey($columnID);
				$redisObj->zadd($cacheKey,time(),$memberID);
				//我某个用户订阅的专栏
				$userCacheKey = self::getUserSubscribeKey($memberID);
				$redisObj->zadd($userCacheKey,time(),$columnID);
			}
		}
		return true;
	}
	
	/**
	 *  获取信息
	 * @param int $ownerID
	 * @param int $memberID
	 */
	private function getInfo($column,$memberID)
	{
		$info = $this->select()->where('MemberID = ?',$memberID)->where('ColumnID = ?',$column)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 *  取消订阅
	 * @param int $columnID
	 * @param int $memberID
	 */
	public function unSubscribe($columnID,$memberID)
	{
		$ret = $this->delete(array('MemberID = ?'=>$memberID,'ColumnID = ?'=>$columnID));
		if($ret > 0){
			//减少订阅量
			$columnModel = new Model_Column_Column();
			$columnModel->increaseSubscribeNum($columnID,-1);
			
			$redisObj = DM_Module_Redis::getInstance();
			//取消用户订阅某个专栏
			$userCacheKey = self::getUserSubscribeKey($memberID);
			$redisObj->zrem($userCacheKey,$columnID);
			//取消某个专栏下的用户
			$cacheKey = self::getSubscribeKey($columnID);
			$redisObj->zrem($cacheKey,$memberID);
		}
		return true;
	}
	
	/**
	 *  获取用户已订阅专栏ID
	 * @param int $memberID
	 */
	public function getColumnArr($memberID)
	{
		$db = $this->getAdapter();
		$columnIDArr = $db->fetchCol("SELECT ColumnID FROM column_member_subscribe WHERE `MemberID` = :MemberID",array("MemberID" => $memberID));
		return $columnIDArr ? $columnIDArr : array();
	}
	
}