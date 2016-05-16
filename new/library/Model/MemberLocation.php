<?php
/**
 *  会员位置定位
* @author jeff
*
*/
class Model_MemberLocation extends Zend_Db_Table
{
	protected $_name = 'member_location';
	protected $_primary = 'LID';
	
	/**
	 * 保存用户的位置坐标
	 * @param unknown $locationModel
	 * @param unknown $latitude
	 * @param unknown $memberID
	 */
	public function saveLocation($longitude,$latitude,$memberID,$isBest)
	{
// 		$columnModel = new Model_Column_Column();
// 		$info = $columnModel->getMyColumnInfo($memberID);
// 		if(!empty($info)){
// 			$type = 1; //理财号
// 		}else{
		$authenticateModel =new Model_Authenticate();
		$auInfo = $authenticateModel->getInfoByMemberID($memberID,1);
		if(!empty($auInfo) && $auInfo['AuthenticateType'] == 2){
			$type = 2;//理财师
		}else{
			$type = $isBest == 1 ? 3 : 4;//3达人，4其他
		}
		//}
		$hasExist = $this->getLocationInfo($memberID,$this->_primary);
		$db = $this->getAdapter();
		if(empty($hasExist)){
			$sql = "insert into member_location(`Longitude`,`Latitude`,`Location`,`MemberID`,`Type`)values($longitude,$latitude,GEOMFROMTEXT('POINT($latitude $longitude)'),$memberID,$type)";
			//echo $sql;exit;
			$db->query($sql);
		}else{
			$lID = $hasExist['LID'];
			$sql = "update member_location set `Longitude` = $longitude,`Latitude` = $latitude,`Type` = $type,`Location` = GEOMFROMTEXT('POINT($latitude $longitude)'),`UpdateTime` = Now(),`Status` = 1 where `LID` = $lID";
			//echo $sql;exit;
			$db->query($sql);
		}
		return true;
	}
	
	/**
	 * 获取定位信息
	 * @param unknown $memberID
	 * @param string $fileds
	 * @return mixed
	 */
	public function getLocationInfo($memberID,$fileds = '*',$status = 0){
		$select = $this->select()->from($this->_name,$fileds)->where('MemberID = ?',$memberID);
		if($status){
			$select->where('Status = ?',$status);
		}
		$info = $select->query()->fetch();
		return $info;
	}
	
	/**
	 * 获取符号条件的理财号
	 * @param unknown $columnRange
	 */
	public function getColumnList($y,$x,$columnRange,$radius,$memberID)
	{
		//x:纬度，y:经度
		$db = $this->getAdapter();
		$x0 = $columnRange['minLat'];
		$x1 = $columnRange['maxLat'];
		$y0 = $columnRange['minLng'];
		$y1 = $columnRange['maxLng'];
		$sql = "select MemberID,LID,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($x*PI()/180-Latitude*PI()/180)/2),2)+COS($x*PI()/180)*COS(Latitude*PI()/180)*POW(SIN(($y*PI()/180-Longitude*PI()/180)/2),2)))*1000)  AS distance from member_location
				where  Type = 1 and MemberID <>$memberID and Status=1 having distance< $radius*1000  
				ORDER BY distance asc";
				
		$list = $db->query($sql)->fetchAll();
		return $list;
		
	}
	
	public function getMemberList($y,$x,$memberRange,$radius,$memberID)
	{
		$db = $this->getAdapter();
		$x0 = $memberRange['minLat'];
		$x1 = $memberRange['maxLat'];
		$y0 = $memberRange['minLng'];
		$y1 = $memberRange['maxLng'];
		$sql = "select Type,MemberID,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($x*PI()/180-Latitude*PI()/180)/2),2)+COS($x*PI()/180)*COS(Latitude*PI()/180)*POW(SIN(($y*PI()/180-Longitude*PI()/180)/2),2)))*1000)  AS distance from member_location
				where MemberID <>$memberID and Status=1 having distance< $radius*1000 
				ORDER BY distance asc";
		$list = $db->query($sql)->fetchAll();
		return $list;
	}
	
	/**
	 * 10分钟后自动清除位置信息
	 */
	public function deleteLocation()
	{
		$time = time()-600;
		$info = $this->select()->where('Status = ?',1)->where('UNIX_TIMESTAMP(UpdateTime) < ?',$time)->query()->fetchAll();
		if(!empty($info)){
			foreach($info as $val){
				$this->update(array('Status'=>0),array('LID = ?'=>$val['LID']));
			}
		}
	}
}
