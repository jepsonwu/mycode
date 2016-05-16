<?php
/**
 *  赞
 *  
 * @author Mark
 *
 */
class Model_Topic_Praise extends Zend_Db_Table
{
	protected $_name = 'view_praises';
	protected $_primary = 'PraiseID';
	
	/**
	 *  赞
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function addPraise($viewID,$memberID)
	{
		$hasExistsInfo = $this->getInfo($viewID, $memberID);
		if(empty($hasExistsInfo)){
			$data = array('ViewID'=>$viewID,'MemberID'=>$memberID);
			$newPraiseID = $this->insert($data);
			if($newPraiseID > 0) {
				//增加赞数
				$viewModel = new Model_Topic_View();
				$viewModel->increasePraiseNum($viewID);
				//保存会员赞信息至Redis
				$redisObj = DM_Module_Redis::getInstance();
				$cacheKey = 'Praise:MemberID:'.$memberID;
				$redisObj->zadd($cacheKey,time(),$viewID);
			}
		}
		return true;
	}
	
	
	/**
	 *  取消赞
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function unPraise($viewID,$memberID)
	{
		$ret = $this->delete(array('MemberID = ?'=>$memberID,'ViewID = ?'=>$viewID));
		if($ret > 0){
			$viewModel = new Model_Topic_View();
			$viewModel->increasePraiseNum($viewID,-1);
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'Praise:MemberID:'.$memberID;
			$redisObj->zrem($cacheKey,$viewID);
		}
	}
	
	/**
	 * 获取赞的记录
	 * @param int $viewID
	 * @param int $memberID
	 */
	private function getInfo($viewID,$memberID)
	{
		$info = $this->select()->where('MemberID = ?',$memberID)->where('ViewID = ?',$viewID)->query()->fetch();
		return $info;
	}
	
	/**
	 * 获取最新点赞的人
	 */
	public function getPraiseMember($viewID)
	{
		$info = $this->select($this->_name,array('MemberID'))->where('ViewID = ?',$viewID)->order('PraiseID desc')->limit(3)->query()->fetchALl();
		return $info;
	}
}