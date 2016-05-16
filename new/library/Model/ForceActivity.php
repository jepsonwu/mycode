<?php
/**
 * 强推活动
 * @author kitty
 *
 */
class Model_ForceActivity extends Zend_Db_Table
{
	protected $_name = 'force_activity';
	protected $_primary = 'ActivityID';

	
	/**
	 * 获取可用强推活动
	 */
	private function getActivity()
	{
		$dateTime = date('Y-m-d H:i:s');
		$select = $this->select();
		$select->where('StartTime <= ?',$dateTime)->where('EndTime >= ?',$dateTime)->where('CountPerDay > 0');
		$info = $select->order('StartTime desc')->limit(1)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 获取推送广告
	 */
	public function getAvaliable($memberID)
	{
		$field = 'ForceActivityData';
		$dataTmp = Model_Member::staticData($memberID,$field);
		$isPush = 0;
		$curDate = date('Ymd');	
		$new = $this->getActivity();
		if(!empty($dataTmp) && !empty($new)){
			$data = json_decode($dataTmp,true);
			!isset($data['ShowDate']) && $data['ShowDate'] = '';
// 			if($new['ActivityID'] != $data['ActivityID']){
// 				$isPush = 1;	
// 			}elseif($new['ActivityID'] == $data['ActivityID']){
// 				if($data['ShowDate'] == $curDate){
// 					if($new['CountPerDay'] > $data['CountPerDay']){
// 						$isPush = 1;
// 					}
// 				}else{
// 					$isPush = 1;
// 				}
// 			}

			if(!($new['ActivityID'] == $data['ActivityID'] && $data['ShowDate'] == $curDate && $data['CountPerDay'] >= $new['CountPerDay'])){
				$isPush = 1;
			}			
		}else{
			$isPush = 1;
		}
		
		if($isPush == 1 && !empty($new)){
// 			if(!empty($data)){
// 				if($new['ActivityID'] != $data['ActivityID']){
// 					$countPerDay = 1;
// 				}else{
// 					if($data['ShowDate'] == $curDate){
// 						$countPerDay = $data['CountPerDay'] + 1;
// 					}else{
// 						$countPerDay = 1;
// 					}
// 				}
// 			}else{
// 				$countPerDay = 1;
// 			}
			
			if(!empty($data) && $data['ActivityID'] == $new['ActivityID'] && $data['ShowDate'] == $curDate){
				$countPerDay = $data['CountPerDay'] + 1;
			}else{
				$countPerDay = 1;
			}
			
			$strJson = json_encode(array('ActivityID'=>$new['ActivityID'],'CountPerDay'=>$countPerDay,'ShowDate'=>$curDate));
			Model_Member::staticData($memberID,$field,$strJson);
			return array('ActivityID'=>$new['ActivityID'],'ActivityName'=>$new['ActivityName'],'ActivityPic'=>$new['ActivityPic'],'ActivityLink'=>$new['ActivityLink'],'CurrentCount'=>$countPerDay);
		}
		return array();
	}
}