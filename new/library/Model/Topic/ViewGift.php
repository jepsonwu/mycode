<?php
/**
 * 观点打赏相关
 * @author Jeff
 *
 */
class Model_Topic_ViewGift extends Zend_Db_Table
{
	protected $_name = 'view_gifts';
	protected $_primary = 'RecodeID';


	/**
	 *  获取观点礼物统计列表
	 * @param int $viewID
	 */
	public function getStaticGifts($viewID,$limit = NULL)
	{
		$select = $this->select()->setIntegrityCheck(false)->from('view_gifts as a',array('SUM(Amount) as TotalAmount','SUM(GiftNum) as TotalNum'))
		->joinLeft('gifts as b', 'a.GiftID = b.GiftID',array('GiftID','GiftName','Type','Cover','Unit'))->where('a.ViewID = ?',$viewID)
		->group('a.GiftID')->order(array('b.Type asc','TotalNum desc'));
		if($limit){
			$select = $select->limit($limit);
		}
		$info = $select->query()->fetchAll();
		return empty($info)?array():$info;
	}
	
	/**
	 * 获取某个观点收到的礼物
	 * @param unknown $lastID
	 * @param unknown $pagesize
	 */
	public function getViewGifts($viewID,$lastID,$pagesize){
		$select = $this->select()->setIntegrityCheck(false)->from('view_gifts as a',array('RecodeID','Amount','GiftNum','GiftMemberID','CreateTime'))
		->joinLeft('gifts as b', 'a.GiftID = b.GiftID',array('GiftID','GiftName','Type','Cover','Unit'))->where('a.ViewID = ?',$viewID);
		if($lastID){
			$select = $select->where('RecodeID < ?',$lastID);
		}
		$info = $select->order('RecodeID desc')->limit($pagesize)->query()->fetchAll();
		return empty($info)?array():$info;
	}
	
	/**
	 * 获取送取金额最多的前5个人
	 * @param unknown $viewID
	 * @param unknown $limit
	 */
	public function getExpensiveGift($viewID,$limit){
		$select = $this->select()->from($this->_name,array('SUM(Amount) as TotalAmount','GiftMemberID'))
		->where('ViewID = ?',$viewID)->group('GiftMemberID')->order('TotalAmount desc')->order('RecodeID desc')->limit($limit);
		$info = $select->query()->fetchAll();
		return empty($info)?array():$info;
	}
}