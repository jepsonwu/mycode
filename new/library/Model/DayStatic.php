<?php
/**
 *日常数据统计相关
 * @author Jeff
 *
 */
class Model_DayStatic extends Zend_Db_Table
{
	protected $_name = 'day_static';
	protected $_primary = 'SID';
	
	/**
	 * 日常统计
	 */
	public function dayStatic()
	{
		$redisObj = DM_Module_Redis::getInstance();
		//激活量
		$actNUmKey = 'ActiveNum:date:'.date('Y-m-d');
		$activeNum = $redisObj->get($actNUmKey);
		$activeNum = $activeNum ? $activeNum : 0;
		//启动人数
		$uKey = 'OpenUserNum:date:'.date('Y-m-d');
		$userNum = $redisObj->get($uKey);
		$userNum = $userNum ? $userNum : 0;
		//启动次数
		$tKey = 'OpenTotalNum:date:'.date('Y-m-d');
		$appNum = $redisObj->get($tKey);
		$appNum = $appNum ? $appNum : 0;
		//注册量
		$registerKey = 'RegisterNum:date:'.date('Y-m-d');
		$registerNum = $redisObj->get($registerKey);
		$registerNum = $registerNum ? $registerNum : 0;
		$data = array('ActivatNum'=>$activeNum,'RegisterNum'=>$registerNum,'StartMemberNum'=>$userNum,
						'StartNum'=>$appNum,'CreateDate'=>date('Y-m-d'));
		$re = $this->select()->from($this->_name,$this->_primary)->where("CreateDate = ? ",date("Y-m-d"))->query()->fetch();
		if(empty($re)){
			$this->insert($data);
		}else{
			$this->update($data, array('SID = ?'=>$re['SID']));
		}
		return true;
	}
}