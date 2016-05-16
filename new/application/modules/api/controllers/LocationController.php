<?php
/**
 * 附近定位相关
 * @author Jeff
 *
 */
class Api_LocationController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 保存用户的定位坐标
	 */
	public function saveLocationAction()
	{
		try{
			$longitude = trim($this->_getParam('longitude'));
			$latitude = trim($this->_getParam('latitude'));
			if(empty($longitude)||empty($latitude)){
				throw new Exception('参数错误！');
			}
			$locationModel = new Model_MemberLocation();
			$locationModel->saveLocation($longitude,$latitude,$this->memberInfo->MemberID,$this->memberInfo->IsBest);
			$this->returnJson(parent::STATUS_OK,'保存成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
			
	}
	
	/**
	 * 清空位置信息
	 */
	public function deleteLocationAction()
	{
		try{
			$locationModel = new Model_MemberLocation();
			$locationModel->update(array('Status'=>0),(array('MemberID = ?'=>$this->memberInfo->MemberID)));
			$this->returnJson(parent::STATUS_OK,'成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取附近的人（包括理财号）
	 */
	public function getNearMembersAction()
	{
		try{
			//根据城市查出认证类型为机构或企业的理财号
			$city = $this->memberInfo->CityCode;
			$columnList = array();
			$authenticateModel =new Model_Authenticate();
			$columnModel = new Model_Column_Column();
			if(!empty($city)){
				$select = $authenticateModel->select()->setIntegrityCheck(false);
				$select->from('member_authenticate as a',null)->where('a.CityCode = ?',$city)->where('a.AuthenticateType > ?',2)->where('a.Status = ?',1);
				$select->joinInner('column as b', 'a.MemberID = b.MemberID',array('b.ColumnID','b.MemberID','b.Title','b.Avatar','b.Description'))->where('b.CheckStatus = ?',1);
				$columnList = $select->query()->fetchAll();
				if(!empty($columnList)){
					foreach ($columnList as &$val){
						$val['IsSubscribe'] = $columnModel->isSubscribeColumn($this->memberInfo->MemberID, $val['ColumnID']);
					}
				}
				
			}
			//获取20公里内的财猪号
			$model = new DM_Model_Account_Location;
			$locationModel = new Model_MemberLocation();
			$info = $locationModel->getLocationInfo($this->memberInfo->MemberID,array('Longitude','Latitude'),1);
			$memberList = array();
			if(!empty($info)){	
				$memberRange = $model->getRange($info['Longitude'],$info['Latitude'],20);
				$memberList = $locationModel->getMemberList($info['Longitude'],$info['Latitude'],$memberRange,20,$this->memberInfo->MemberID);
				$memberArr = array();
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				if(!empty($memberList)){
					$qualificationModel = new Model_Qualification();
					$bestModel = new Model_Best_Best();
					$focusModel = new Model_MemberFocus();
					$memberFollowModel = new Model_MemberFollow();
					foreach($memberList as &$row){
						$row['Avatar'] = $memberModel->getMemberAvatar($row['MemberID']);
						$row['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $row['MemberID']);
						$row['UserName'] = $memberModel->getMemberInfoCache($row['MemberID'],'UserName');
						$row['RelationCode'] = $memberFollowModel->getRelation($row['MemberID'], $this->memberInfo->MemberID);
						$row['BestTitle'] = array();
						$row['Qualification'] = array();
						$row['Focus'] = array();
						if($row['Type']==2){//理财师
							$authenticateInfo = $authenticateModel->getInfoByMemberID($row['MemberID'],1,'AuthenticateID');
							$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],3,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
	        				$row['Qualification'] = !empty($qualificationInfo)?$qualificationInfo:array();
						}elseif($row['Type']==3){
							$bestInfo = $bestModel->getBestInfoByMemberID(array($row['MemberID']), array(2,3));
							$bestTitleArr = array();
							if(!empty($bestInfo)){
								$bestTitleArr = $bestInfo[$row['MemberID']];
							}
							$row['BestTitle'] = !empty($bestTitleArr)?$bestTitleArr:array();
						}else{
							$row['Focus'] = $focusModel->getFocusInfo($row['MemberID'],null,'FocusID');
						}
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('ColumnList'=>$columnList,'MemberList'=>$memberList));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	function getDistanceAction()
	{
		$lat1 = '29.490295';
		$lng1 = '106.486654';
		$lat2 = '29.615467';
		$lng2 = '106.581515';
		$earthRadius = 6367000; //approximate radius of earth in meters
		 
		$lat1 = ($lat1 * pi() ) / 180;
		$lng1 = ($lng1 * pi() ) / 180;
		 
		$lat2 = ($lat2 * pi() ) / 180;
		$lng2 = ($lng2 * pi() ) / 180;
		 
		 
		$calcLongitude = $lng2 - $lng1;
		$calcLatitude = $lat2 - $lat1;
		$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
		$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
		$calculatedDistance = $earthRadius * $stepTwo;
		 
		echo round($calculatedDistance);
	}
}