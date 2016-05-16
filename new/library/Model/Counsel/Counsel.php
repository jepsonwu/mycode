<?php

/**
 * 问财咨询
 * User: Hale
 * Date: 16-3-8
 * Time: 下午2:39
 */
class Model_Counsel_Counsel extends Model_Common_Common
{
	protected $_name = 'counsel';
	protected $_primary = 'CID';

	//状态
	const COUNSEL_STATUS_CLOSE = 0;
	const COUNSEL_STATUS_TRUE = 1;
	const COUNSEL_STATUS_HIDE = 2;
	
	const NEW_COUNSEL_KEY = 'NEW:COUNSEL:INFO';

	/**
	 * 判断咨询主题的有效性
	 * @param $cid
	 * @param null $fields
	 * @param bool $exclude_hide 默认排除隐藏
	 * @return bool
	 * @throws Exception
	 */
	public function isValidCounsel($cid, $fields = null, $exclude_hide = true)
	{
		$counsel_info = $this->getInfoMix(array("CID =?" => $cid), $fields);
		if (is_null($counsel_info)) {
			throw new Exception("咨询主题不存在！");
		}

		if ($exclude_hide) {
			if ($counsel_info['Status'] != self::COUNSEL_STATUS_TRUE)
				throw new Exception("咨询主题不存在！");
		} else {
			if ($counsel_info['Status'] == self::COUNSEL_STATUS_CLOSE)
				throw new Exception("咨询主题不存在！");
		}

		return $counsel_info;
	}


	/**
	 *  获取信息
	 * @param int $memberID
	 */
	public function getMyCounselInfo($memberID,$status=null,$field = null)
	{
		$select = $this->select();
		if(is_null($field)){
			$select->from($this->_name);
		}else{
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field);
		}
		$select->where('MemberID = ?',$memberID);
		if(!is_null($status)){
			$select->where('Status = ?',$status);
		}
		$info = $select->limit(1)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 保存最新的讯财信息
	 */
	public static function newCouselInfo($info = NULL)
	{
		if(!is_null($info)){
			DM_Module_Redis::getInstance()->hMSet(self::NEW_COUNSEL_KEY,$info);
		}
		return DM_Module_Redis::getInstance()->hGetAll(self::NEW_COUNSEL_KEY);
	}

	/**
	 *  是否有新讯财服务
	 * @param int $memberID
	 */
	public function hasNewInfo($memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$lastCounselKey = 'LAST_COUNSEL_INFO_ID';
		$lastCounselID = Model_Member::staticData($memberID,$lastCounselKey);
		
		$CID = $redisObj->hget(self::NEW_COUNSEL_KEY,'CID');
		
		if(empty($CID)){
			$info = $this->select()->from($this->_name,'*')->order('CID desc')->where('Status = 1')->where('DataType = 1')->limit(1)->query()->fetch();
			if(!empty($info)){
				$CID = $info['CID'];
				self::newCouselInfo($info);
			}
		}
		
		if(intval($CID) > intval($lastCounselID)){
			//Model_Member::staticData($memberID,$lastCounselKey,$CID);
			return true;
		}
		return false;
	}
	
	/**
	 *  更新上次请求ID
	 * @param int $memberID
	 * @param int $viewID
	 */
	public static function updateLastIDCache($memberID,$CID)
	{
		$lastCounselKey = 'LAST_COUNSEL_INFO_ID';
		$redisObj = DM_Module_Redis::getInstance();
		$lastCounselID = Model_Member::staticData($memberID,$lastCounselKey);
		if($CID > intval($lastCounselID)){
			Model_Member::staticData($memberID,$lastCounselKey,$CID);
		}
	}
}