<?php

/**
 * 会员对说说点赞
 * @author johnny 2015-07-07
 */

class Model_ShuoPraise extends Zend_Db_Table {
	protected $_name = 'shuo_praises';
	protected $_primary = array(1 => 'PraiseID');

	public function addPraise($shuoID,$memberID) {

		$modelShuoshuo = new Model_Shuoshuo();
		$info = $modelShuoshuo->getShuos($shuoID);
		if(!$this->isPraised($shuoID, $memberID)) {
			$insertID = $this->insert(array('ShuoID'=>$shuoID, 'PraiseBy'=>$memberID));
			if($insertID) {
				// 增加点赞的数量
				$modelShuoshuo->update(array('PraiseCount'=>new Zend_Db_Expr('PraiseCount+1')), 'ShuoID='.$shuoID);
				//保存会员点赞了哪些说说的ID至Redis
				$redisObj = DM_Module_Redis::getInstance();
				$redisObj->zadd('ShuoPraise:MemberID:'.$memberID, $insertID, $shuoID);
				// 保存说说被点赞的会员信息至redis
				$redisObj->zAdd('ShuoPraise:ShuoID:'.$shuoID, $insertID, $memberID);
				//增加对说说点赞的消息
				if($info[0]['MemberID'] != $memberID){
					$messageModel = new Model_Message();
					$messageModel->addMessage($info[0]['MemberID'], 3, $insertID, 2);
				}
			}else{
				return false;
			} 
		}
		return true;
	}
	
	/**
	 *  判断会员是否已赞了某个说说
	 * @param int $shuoID
	 * @param int $memberID
	 */
	public function isPraised($shuoID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$score = $redisObj->zscore('ShuoPraise:MemberID:'.$memberID,$shuoID);
		return $score ? $score : 0;
	}

	public function delPraises($shuoID,$memberID) {
		
		$messageModel = new Model_Message();
		$info = $this->select()->from($this->_name,array('PraiseID'))->where('ShuoID = ?',$shuoID)->where('PraiseBy = ?',$memberID)
		->query()->fetch();
		if(!empty($info)){
			$messageModel->delMessage($info['PraiseID']);
			$this->delete(array('PraiseID = ?'=>$info['PraiseID']));
			$modelShuoshuo = new Model_Shuoshuo();
			$modelShuoshuo->update(array('PraiseCount'=>new Zend_Db_Expr('PraiseCount-1')), array('ShuoID = ?'=>$shuoID,'PraiseCount > ?'=>0));
			$redisObj = DM_Module_Redis::getInstance();
			//从Redis移除会员点赞的说说
			$redisObj->zRem('ShuoPraise:MemberID:'.$memberID, $shuoID);
			// 从redis移除被点赞说说的会员
			$redisObj->zRem('ShuoPraise:ShuoID:'.$shuoID, $memberID);
		}
		return true;
	}

	public function getPraises($where = null) {
		if( !$where ) {
			return false;
		}
		if( is_numeric($where) ) {
			$where = $this->_primary[1] . '=' . $where;
		}
		$data = $this->fetchAll($where)->toArray();
		return empty($data) ? null : $data;
	}

	public function getPraiseByCached($shuoID = null, $getBy = null) {
		if( !$shuoID ) {
			throw new Exception('说说ID不能为空');
		}
		$redisObj = DM_Module_Redis::getInstance();
		if( $data = $redisObj->zRange('ShuoPraise:ShuoID:'.$shuoID, 0, -1, true) ) {
			$modelAccount = new DM_Model_Account_Members();
			foreach ($data as $key => $value) {
				$data[$key] = array(
					'time' => date('Y-m-d H:i:s', $value),
					'memberName' => $modelAccount->getUserName($key, $getBy)
				);
			}
			return $data;
		}
		return array();
	}

}