<?php
/**
 *广告位相关
 * @author Jeff
 *
 */
class Model_Ads extends Zend_Db_Table
{
	protected $_name = 'ads';
	protected $_primary = 'AdsID';
	
	/**
	 * 获取广告位
	 */
	public function getAdsList($lastAdsBarID, $count, $adsType) {
		$adArr = array();
		$maxNum = $lastAdsBarID;
		if($count>0){
			$model = new Model_AdsBar();
			$result = $model->getAdsbar($lastAdsBarID, $count, $adsType);
			$requestObj = DM_Controller_Front::getInstance()->getHttpRequest();
			$platform = $requestObj->getParam('platform','0');
			$channel = $requestObj->getParam('channel','caizhu');
			$memberModel = new DM_Model_Account_Members();
			if(!empty($result)){
				foreach ($result as $k => $val){
					$fileds = array('ContentType'=>new Zend_Db_Expr(1),'logo as AdsAvatar','Name as AdsShowName','AdsID','AdsImg','AdsTitle','AdsLink','ImgWidth','ImgHeight','ValidFrom as CreateTime','MemberID');
					$select = $this->select()->from($this->_name, $fileds)->where('AdsBarID = ?', $val['AdsBarID'])->where('Status = ?', 1)->where('UNIX_TIMESTAMP(ValidFrom) < ?',time())->where('UNIX_TIMESTAMP(ValidEnd) > ?',time());
					if($platform){
						$select = $select->where('Platform = 0 or Platform = ?',$platform);
					}
					if(!empty($channel)){
						$select = $select->where('DisplayChannel = " " or DisplayChannel like ?','%'.$channel.'%');
					}
					$adInfo = $select->order('AdsID desc')->limit(1)->query()->fetch();
					if(!empty($adInfo)){					
						$adInfo['BarNum'] = $val['BarNum'];
						$adInfo['AdsType'] = $val['AdsType'];
						if($adInfo['MemberID']>0){
							$adInfo['AdsAvatar'] = $memberModel->getMemberAvatar($adInfo['MemberID']);
							$adInfo['AdsShowName'] = $memberModel->getMemberInfoCache($adInfo['MemberID'],'UserName');
						}
						$adArr[] = $adInfo;
					}else{
						$adArr[] = array();
					}
				}
				$maxNum = $result[count($result) - 1]['BarNum'];
			}
		}
		
		return array('ads'=>$adArr,'maxNum'=>$maxNum);
		
	}

	/**
	 * @param int $barID 广告位ID
	 */
	public function addAds($data = array()) {
		if( empty($data) ) {
			return false;
		}
		$row = $this->createRow($data);
		if( $row->save() ) {
			return true;
		} else {
			return false;
		}
	}

	public function delAds($where = null) {
		if( !$where ) {
			return false;
		}
		if( is_numeric($where) ) {
			$where = $this->_primary . '=' . $where;
		}
		if( $this->update(array('Status'=>2), $where)) {
			return true;
		} else {
			return false;
		}
	}

	public function getAdss($where = null, $orderBy = null, $limit = null, $offset = null) {
		if( is_numeric($where) ) {
			$where = $this->_name . '.' . $this->_primary . '=' . $where;
		}
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name)->joinLeft('ads_bar', 'ads_bar.AdsBarID=ads.AdsBarID', array('BarNum', 'ShowType'))->where($where)->limit($limit, $offset)->order($orderBy);
		// die($select->__toString());
		$data = $select->query()->fetchAll();
		return empty($data) ? null : $data;
	}

	public function updateAds($ID = null, $data = array()) {
		if( !$ID ) {
			return false;
		}
		if( empty($data) ) {
			return false;
		}
		$row = $this->fetchRow('AdsID = '.$ID);
		if( $row ) {
			foreach ($data as $k => $v) {
				isset($row->$k) && ($row->$k = $v);
			}
			if( $row->save() ) {
				return true;
			} else {
				return false;
			}
		} else {
			throw new Exception('广告不存在');
		}
	}

	public function getAdssTotal($where = null) {
		if( is_numeric($where) ) {
			$where = $this->_name . '.' . $this->_primary . '=' . $where;
		}
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name, array('total'=>'count(*)'))->joinLeft('ads_bar', 'ads_bar.AdsBarID=ads.AdsBarID', array())->where($where);
		// die($select->__toString());
		$data = $select->query()->fetch();
		return isset($data['total']) ? intval($data['total']) : 0;
	}

	public function getAdsStatus() {
		return array(0=>'待审核', 1=>'有效', 2=>'已删除');
	}

	public function getAdsShowType() {
		return array(3=>'专栏首页',4=>'财猪首页',5=>'话题首页',6=>'话题订阅页面',7=>'头条首页',8=>'财猪课堂',9=>'启动页');
	}
	
	public function getAdsPlatform() {
		return array(0=>'所有', 1=>'安卓', 2=>'IOS');
	}
	
	public function bannerAds($showType,$platform){
		$result = array();
		$barModel = new Model_AdsBar();
		$re = $barModel->select()->from('ads_bar',array('AdsBarID'))->where('BarNum = ?',1)->where('Showtype = ?',$showType)->query()->fetch();
		if(!empty($re)){
			$select = $this->select()->from($this->_name,array('AdsID','AdsImg','AdsLink','ValidEnd'))->where('AdsBarID = ?',$re['AdsBarID'])
			->where('Status = ?', 1)->where('UNIX_TIMESTAMP(ValidFrom) < ?',time())->where('UNIX_TIMESTAMP(ValidEnd) > ?',time());
			if($platform){
				$select = $select->where('Platform = 0 or Platform = ?',$platform);
			}
			$select = $select->order('ValidFrom desc');
			$result = $select->query()->fetchAll();
		}
		return $result;
	}
}