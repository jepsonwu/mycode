<?php
/**
 *  今日话题投票
 *  
 * @author Kitty
 *
 */
class Model_Topic_TopicVote extends Zend_Db_Table
{
	protected $_name = 'topic_vote';
	protected $_primary = 'TopicVoteID';
	
	/**
	 *  获取信息
	 * @param int $period 期数
	 */
	public function getTopicVote($period)
	{
		$db = $this->getAdapter();
		$viewIDArr = $db->fetchCol("SELECT ViewID FROM topic_vote WHERE `Period` = :Period",array("Period" => $period));
		return $viewIDArr ? $viewIDArr : array();
	}


	/*
	 *根据ID获取信息
	 *int $viewID 观点ID
	 *int $period 期数
	 */
	public function getInfoByID($viewID,$period)
	{
		$select = $this->select();
		$info = $select->from($this->_name)->where('ViewID = ?',$viewID)->where('Period = ?',$period)->query()->fetch();
		return !empty($info)?$info:array();
	}

	/**
	 *  增加投票数
	 * @param int $viewID 观点id
	 * @param int $period 期数
	 * @param int $增加数
	 */
	public function addVoteNum($viewID,$period,$memberID,$count = 1)
	{
		$info = $this->getInfoByID($viewID,$period);
		if(!empty($info)){
			$id = $info['TopicVoteID'];
		}
		$this->update(array('VoteCount'=>new Zend_Db_Expr("VoteCount + ".$count)),array('TopicVoteID = ?'=>$id));
		$voteListModel = new Model_Topic_VoteList();
		$voteListModel->add($memberID,$id);
		return true;
	}

	/**
	 * 通知活动参加者
	 */
	public function noticeSelected()
	{
		$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
		$easeModel = new Model_IM_Easemob();
		$content = '亲爱的财猪用户，您好，您的观点已被选中为本周热门观点，点击进入财猪首页或话题首页的活动，邀请好友为自己加油，赢取流量爱心礼包吧！';

		$select = $this->select();
		$select->from('topic_vote')->where('Status  = ? ',1);
		$result = $select->query()->fetchAll();
		if(!empty($result)){
			$viewModel = new Model_Topic_View();
			$tmpMemberID = array();
			foreach($result as $item){
				$viewInfo = $viewModel->getViewInfo($item['ViewID']);
				$tmpMemberID[] = $viewInfo['MemberID'];
			}
			$tmpMemberID[] = 6679;
			$ret = $easeModel->yy_hxSend($tmpMemberID, $content,'text','users',array('optionRand'=>1),$sysMemberID);
			//$retArr = json_decode($ret,true);
			// if(is_array($retArr) && !empty($retArr['data'])){
			// 	foreach($retArr['data'] as $memberID=>$resSign){
			// 		if($resSign == 'success'){
			// 			$this->update(array('IsNoticed'=>1),array('TopicVoteID in (?)'=>$tmpVoteID));
			// 		}
			// 	}
			// }
		}
		
	}

	/**
	 * 通知获奖者
	 */
	public function noticeWin()
	{
		$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
		$easeModel = new Model_IM_Easemob();
		$content = '亲爱的叮当猫，您好，恭喜您赢得本周热门观点流量大礼包，我们将尽快将流量充值到您财猪绑定的手机号中，请您关注手机流量变化哦~';

		$select = $this->select();
		$select->from('topic_vote')->where('IsWin = 1')->where('IsNoticed = 0')->where('Status  = ? ',1)->order('VoteCount desc');
		$result = $select->query()->fetchAll();
		if(!empty($result)){
			$viewModel = new Model_Topic_View();
			$tmpMemberID = array();
			$tmpVoteID = array();
			foreach($result as $item){
				$viewInfo = $viewModel->getViewInfo($item['ViewID']);
				$tmpMemberID[] = $viewInfo['MemberID'];
				$tmpVoteID[] =$item['TopicVoteID'];
			}
			$tmpMemberID[] = 6679;
			$ret = $easeModel->yy_hxSend($tmpMemberID, $content,'text','users',array('optionRand'=>1),$sysMemberID);
			$retArr = json_decode($ret,true);
			if(is_array($retArr) && !empty($retArr['data'])){
				foreach($retArr['data'] as $memberID=>$resSign){
					if($resSign == 'success'){
						$this->update(array('IsNoticed'=>1),array('TopicVoteID in (?)'=>$tmpVoteID));
					}
				}
			}
		}
		
	}
	
}