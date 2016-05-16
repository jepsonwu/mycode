<?php
/**
 *  观点管理
 * @author Kitty
 *
 */
class Model_VoteViewList extends Zend_Db_Table
{
	protected $_name = 'vote_view_list';
	protected $_primary = 'VoteViewListID';

	/**
	 *  获取详情
	 */
	public function getVoteViewInfo($periodID,$type,$viewID=0)
	{
		$select = $this->select()->from($this->_name)
								 ->where('PeriodID = ?',$periodID)
								 ->where('ViewType =?',$type)
								 ->where('Status = ?',1);
		if($viewID >0){
			$info = $select->where('ViewID = ?',$viewID)->query()->fetch();
			return !empty($info) ? $info : array();
		}
							 
		$info = $select->query()->fetchAll();
		return !empty($info) ? $info : array();
	}


	/**
	 *  增加投票数
	 * @param int $viewID 观点id
	 * @param int $period 期数
	 * @param int $增加数
	 */
	public function addVoteNum($infoID,$memberID,$count = 1)
	{
		$this->update(array('VoteCount'=>new Zend_Db_Expr("VoteCount + ".$count)),array('VoteViewListID = ?'=>$infoID));
		$voteListModel = new Model_Topic_VoteList();
		$voteListModel->add($memberID,$infoID);
		return true;
	}
	
}