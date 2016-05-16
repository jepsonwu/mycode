<?php
/**
 *  统计
 * @author Mark
 *
 */
class Model_Static
{
	/**
	 * 统计入口
	 */
	public function doStatic()
	{
		$this->viewLiveness();
		$this->memberScore();
		$this->hotGroup();
		$this->hotTopic();
		$this->articleLiveness();
		$this->colunmnLiveness();
		$this->FinancialMember();
	}
	
	
	/**
	 * 观点活跃度
	 */
	private function viewLiveness()
	{
		$viewModel = new Model_Topic_View();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $viewModel->select();
			$select->from('topic_views',array('ViewID','PraiseNum','ReplyNum','ShareNum','CreateTime'))->where('ViewID > ?',$lastID);
			$rows = $select->where('CheckStatus=1')->where('IsAnonymous=0')->order('ViewID asc')->limit($limit)->query()->fetchAll();
			
			if(!empty($rows)){
				foreach ($rows as $val){
					$liveness = number_format(100*($val['PraiseNum']+$val['ReplyNum']) / ((time() - strtotime($val['CreateTime'])) / 60 + 2880),2,'.','');
					$viewModel->update(array('Liveness'=>$liveness),array('ViewID = ?'=>$val['ViewID']));
				}
			}
			
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['ViewID'];
		}
		$redisObj = DM_Module_Redis::getInstance();
		$viewKey = 'HotViews';
		$viewKeyTmp = 'HotViewsTmp';
// 		$select = $viewModel->select();
// 		$select->from('topic_views',array('ViewID','Liveness'));
// 		$result = $select->where('CheckStatus=1')->order('Liveness desc')->limit(50)->query()->fetchAll();
		$select = $viewModel->select();
		$select->from('topic_views',array('MemberID','max(Liveness) as Liveness'));
		$result = $select->where('CheckStatus=1')->where('IsAnonymous=0')->where('PraiseNum <> 0 or ReplyNum <> 0')->group('MemberID')->order('Liveness desc')->order('ViewID desc')->limit(50)->query()->fetchAll();
		if(!empty($result)){
			foreach ($result as $row){
				$re = $viewModel->select()->from('topic_views',array('ViewID'))->where('MemberID = ?',$row['MemberID'])
				->where('Liveness = ?',$row['Liveness'])->where('CheckStatus=1')->where('IsAnonymous=0')->where('PraiseNum <> 0 or ReplyNum <> 0')->limit(1)->query()->fetch();
				$redisObj->zadd($viewKeyTmp,$row['Liveness'],$re['ViewID']);
			}
			$redisObj->rename($viewKeyTmp,$viewKey);
		}
	}
	
	
	/**
	 * 某个用户观点评分总和
	 */
	private function memberViewScore($memberID)
	{
		$viewModel = new Model_Topic_View();
		$totalScore = $viewModel->getViewScore($memberID,'member');
		return $totalScore;
	}
	
	/**
	 * 某个话题观点评分总和
	 */
	private function topicViewScore($topicID)
	{
		$viewModel = new Model_Topic_View();
		$totalScore = $viewModel->getViewScore($topicID,'topic');
		return $totalScore;
	}
	
	/**
	 * 过去24小时新增观点评分总和
	 */
	private function todayViewScore($topicID)
	{
		$viewModel = new Model_Topic_View();
		$totalScore = $viewModel->getViewScore($topicID,'topic',1);
		return $totalScore;
	}
	/**
	 * 达人评分
	 */
	private function memberScore()
	{
		$memberModel = new DM_Model_Account_Members();
		$redisObj = DM_Module_Redis::getInstance();
		$viewModel = new Model_Topic_View();
		$groupMemberModel = new Model_IM_GroupMember();
		$friendModel = new Model_IM_Friend();
		$memberScoreModel = new Model_MemberScore();
		$memberFollowModel = new Model_MemberFollow();
		$groupModel = new Model_IM_Group();
		$lastID = 0;
		$limit = 1000;

		$sql = "TRUNCATE member_score";
		$memberScoreModel->getAdapter()->query($sql);
		while(true){
			$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
			$select = $memberModel->select();
			$select->from('members',array('MemberID'))->where('MemberID > ?',$lastID)->where('MemberID != ?',$sysMemberID)
			->where('IsBest = ?',1);
			$rows = $select->order('MemberID asc')->limit($limit)->query()->fetchAll();
			if(!empty($rows)){
				foreach($rows as $member){
					//打开app次数
// 					$key = 'openAppNum:MemberID:'.$member['MemberID'].':date:'.date('Y-m-d');
					
// 					$appNum = $redisObj->get($key);
// 					$appNum = $appNum ? $appNum : 0;
// 					if($appNum>5){
// 						$appNum = 5;
// 					}
					
					//参与话题数量
					$topicNum = $viewModel->getTopicNum($member['MemberID']);
					//该用户观点评分总和
					$viewScore = $this->memberViewScore($member['MemberID']);
					//该用户加入的群组数量
					//$joinGroupNum = $groupMemberModel->myGroupCount($member['MemberID']);
					//好友数量
					$firendNum = $friendModel->getFriendCount($member['MemberID']);
					//获取我的粉丝数和关注数
					//$result = $memberFollowModel->getStatistic($member['MemberID']);
					//粉丝数
					//$fansCount = empty($result['FansCount'])?0:$result['FansCount'];
					//关注数
					//$followCount = empty($result['FollowedCount'])?0:$result['FollowedCount'];
// 					$totalNum = $joinGroupNum+$firendNum+$fansCount+$followCount;
// 					if($totalNum>300){
// 						$totalNum = 300;
// 					}
					//创建的群组数
					$createGroupNum = $groupModel->getCreateGroupNum($member['MemberID']);
// 					if($createGroupNum>100){
// 						$createGroupNum = 100;
// 					}
					
					// 获取某人观点被屏蔽的数量
					//$hideViewNum = $viewModel->getHideViewNum($member['MemberID']);
					
					// 获取某人观点被举报次数
					//$reportNum = $viewModel->getReportNum($member['MemberID']);
					//文章评分总和
					$articleScore = $this->articleScore($member['MemberID'],'member');
					
					$hotScore = number_format((0.5 * $articleScore)/100 + (0.2 * $firendNum)/100 + (0.3 * $viewScore)/100 + (0.1 * $createGroupNum)/100 ,2,'.','');
					
					//$hotScore = 0.1*$appNum+0.2*$topicNum/100+0.4*$viewScore+0.2*$totalNum/100+0.1*$createGroupNum/100-$hideViewNum/10-$reportNum/20;
					
					//$topScore = number_format($hotScore+$articleScore,2,'.','');
					$recentScore = number_format(0.5 * $viewScore/100 + 0.5 * $topicNum/100,2,'.','');
					$memberScoreModel->add($member['MemberID'],$hotScore,$recentScore);
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['MemberID'];
		}
		
		$hotKey = 'HotMember';
		$hotKeyTmp = 'HotMemberTmp';
		$select = $memberScoreModel->select();
		$select->from('member_score',array('MemberID','HotScore'));
		$result = $select->order('HotScore desc')->limit(50)->query()->fetchAll();
		if(!empty($result)){
			foreach ($result as $val){
				$redisObj->zadd($hotKeyTmp,$val['HotScore'],$val['MemberID']);
			}
			$redisObj->rename($hotKeyTmp,$hotKey);
		}
		
		$hotMemberArr = $redisObj->zRevRangeByScore($hotKey,'+inf','-inf');
		$recentKey = 'RecentMember';
		$recentKeyTmp = 'RecentMemberTmp';
		$select = $memberScoreModel->select();
		$select->from('member_score',array('MemberID','RecentScore'))->where('MemberID not in (?)',$hotMemberArr);
		$info = $select->order('RecentScore desc')->limit(50)->query()->fetchAll();
		if(!empty($info)){
			foreach ($info as $row){
				$redisObj->zadd($recentKeyTmp,$row['RecentScore'],$row['MemberID']);
			}
			$redisObj->rename($recentKeyTmp,$recentKey);
		}
	}
	
	/**
	 * 热门群组
	 */
	private function hotGroup()
	{
		$groupModel = new Model_IM_Group();
		$select = $groupModel->select();
		$select->from('group',array('AID','OwnerID','NowUserCount'))->where('Status = ?',1)->where('IsPublic = ?',1);
		$rows = $select->order('AID asc')->query()->fetchAll();
		if(!empty($rows)){
			$memberScoreModel = new Model_MemberScore();
			foreach($rows as $val){
				$memberinfo = $memberScoreModel->getMemberScore($val['OwnerID']);
				$liveness = number_format(0.6 * $val['NowUserCount'] + 0.4 * $memberinfo['HotScore'],2,'.','');
				$groupModel->update(array('Liveness'=>$liveness),array('AID = ?'=>$val['AID']));
			}
			$groupKey = 'HotGroup';
			$groupKeyTmp = 'HotGroupTem';
			$select = $groupModel->select();
			$select->from('group',array('AID','Liveness'))->where('Status = ?',1)->where('IsPublic = ?',1);
			$info = $select->order('Liveness desc')->limit(50)->query()->fetchAll();
			$redisObj = DM_Module_Redis::getInstance();
			//保存到redis里
			foreach ($info as $row){
				$redisObj->zadd($groupKeyTmp,$row['Liveness'],$row['AID']);
			}
			$redisObj->rename($groupKeyTmp,$groupKey);
		}
	}

	/**
	 * 最热话题
	 */
	private function hotTopic()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$topicModel = new Model_Topic_Topic();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $topicModel->select();
			$select->from('topics',array('TopicID','FollowNum'))->where('CheckStatus = ?',1)->where('IsAnonymous=0');
			$rows = $select->order('TopicID asc')->limit($limit)->query()->fetchAll();
			if(!empty($rows)){
				foreach($rows as $val){
					$viewScore = $this->topicViewScore($val['TopicID']);
					$viewNum = $this->getViewNum($val['TopicID']);
					//话题活跃度
					$Liveness = number_format((0.4 * $viewScore)/100 + 0.4 * $viewNum + (0.2 * $val['FollowNum'])/1000 ,2,'.','');
					//今日热议话题活跃度
					$TodayLiveness = $this->todayViewScore($val['TopicID']);
					$topicModel->update(array('Liveness'=>$Liveness,'TodayLiveness'=>$TodayLiveness),array('TopicID = ?'=>$val['TopicID']));
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['TopicID'];
		}
		$select = $topicModel->select();
		$select->from('topics',array('TopicID','Liveness'))->where('CheckStatus = ?',1)->where('IsAnonymous=0');
		$result = $select->order('Liveness desc')->limit(50)->query()->fetchAll();
		$redisObj = DM_Module_Redis::getInstance();
		if(!empty($result)){
			$topicKey = 'HotTopic';
			$topicKeyTmp = 'HotTopicTmp';
			foreach($result as $row){
				$redisObj->zadd($topicKeyTmp,$row['Liveness'],$row['TopicID']);
			}
			$redisObj->rename($topicKeyTmp,$topicKey);
		}
		//今日热议话题
		$select = $topicModel->select();
		$select->from('topics',array('TopicID','TodayLiveness'))->where('CheckStatus = ?',1)->where('IsAnonymous=0');
		$result2 = $select->order(array('TodayLiveness desc','Liveness desc'))->limit(10)->query()->fetchAll();
		if(!empty($result2)){
			$todayTopicKey = 'TodayHotTopic';
			$todayTopicKeyTmp = 'TodayHotTopicTmp';
			foreach($result2 as $key=>$row2){
				$redisObj->zadd($todayTopicKeyTmp,$key,$row2['TopicID']);
			}
			$redisObj->rename($todayTopicKeyTmp,$todayTopicKey);
		}
	}
	
	/**
	 * 获取某个话题下每天发布的观点数
	 * @param unknown $topicID
	 */
	private function getViewNum($topicID)
	{
		$viewModel = new Model_Topic_View();
		$select = $viewModel->select()->from('topic_views','count(1) as num')->where('TopicID = ?',$topicID)->where('CheckStatus = ?',1)
					->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$info = $select->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * 文章活跃度
	 */
	private function articleLiveness()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$articleModel = new Model_Column_Article();
		$giftModel = new Model_Column_ArticleGift();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $articleModel->select();
			$select->from('column_article',array('AID','ReadNum','PraiseNum','ShareNum','PublishTime'))->where('Status = ?',1);
			$rows = $select->order('AID asc')->limit($limit)->query()->fetchAll();
			if(!empty($rows)){
				foreach($rows as $val){
					$giftNum = $giftModel->getGiftNum($val['AID']);
					$Liveness = number_format(($val['ReadNum'] + 5 * $val['PraiseNum'] + 3 * $val['ShareNum'] + 2 * $giftNum +5) / ((time() - strtotime($val['PublishTime'])) / 60 + 30) ,2,'.','');
					$articleModel->update(array('Liveness'=>$Liveness),array('AID = ?'=>$val['AID']));
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['AID'];
		}
		$select = $articleModel->select();
		$select->from('column_article',array('AID','Liveness'))->where('Status = ?',1);
		$result = $select->order('Liveness desc')->limit(50)->query()->fetchAll();
		if(!empty($result)){
			$redisObj = DM_Module_Redis::getInstance();
			$articleKey = 'HotColumnArticle';
			$articleKeyTmp = 'HotColumnArticleTmp';
			foreach($result as $row){
				$redisObj->zadd($articleKeyTmp,$row['Liveness'],$row['AID']);
			}
			$redisObj->rename($articleKeyTmp,$articleKey);
		}
	}
	
	/**
	 * 专栏活跃度
	 */
	private function colunmnLiveness()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$columnModel = new Model_Column_Column();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $columnModel->select();
			$select->from('column',array('ColumnID','SubscribeNum'))->where('CheckStatus = ?',1);
			$rows = $select->order('ColumnID asc')->limit($limit)->query()->fetchAll();
			if(!empty($rows)){
				foreach($rows as $val){
					//专栏文章评分总和
					$articleScore = $this->articleScore($val['ColumnID']);
					//每日发布的文章数量
					$articleNum = $this->getArticleNum($val['ColumnID']);
					$Liveness = number_format((0.3 * $articleScore)/100 + (0.5 * $articleNum)/10 + (0.2 * $val['SubscribeNum'])/1000 ,2,'.','');
					$columnModel->update(array('Liveness'=>$Liveness),array('ColumnID = ?'=>$val['ColumnID']));
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['ColumnID'];
		}
		$select = $columnModel->select();
		$select->from('column',array('ColumnID','Liveness'))->where('CheckStatus = ?',1);
		$result = $select->order('Liveness desc')->limit(50)->query()->fetchAll();
		if(!empty($result)){
			$redisObj = DM_Module_Redis::getInstance();
			$columnKey = 'HotColumn';
			$columnKeyTmp = 'HotColumnTmp';
			foreach($result as $row){
				$redisObj->zadd($columnKeyTmp,$row['Liveness'],$row['ColumnID']);
			}
			$redisObj->rename($columnKeyTmp,$columnKey);
		}
	}
	
	/**
	 * 理财师排行
	 */
	private function FinancialMember()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$authenticateModel =new Model_Authenticate();
		$lastID = 0;
		$limit = 1000;
		while(true){
			$select = $authenticateModel->select()->setIntegrityCheck(false);
			$select->from('member_authenticate as ma',array('AuthenticateID','MemberID'));
			$select->joinLeft('column as c','ma.MemberID = c.MemberID',array('ColumnID'))->where('ma.AuthenticateType = ?',2);	
			$rows = $select->order('AuthenticateID asc')->limit($limit)->query()->fetchAll();
			$memberFollowModel = new Model_MemberFollow();
			$groupModel = new Model_IM_Group();
			$friendModel = new Model_IM_Friend();
			if(!empty($rows)){
				foreach($rows as $val){
					//文章评分总和					
					$articleScore = $this->articleScore($val['MemberID'],'member');
					//粉丝数量
					$firendNum = $friendModel->getFriendCount($val['MemberID']);
					//某人观点评分总和
					$viewScore = $this->memberViewScore($val['MemberID']);
					//用户创建的群组数
					$createGroupNum = $groupModel->getCreateGroupNum($val['MemberID']);
					$Liveness = number_format((0.5 * $articleScore)/100 + (0.2 * $firendNum)/100 + (0.3 * $viewScore)/100 + (0.1 * $createGroupNum)/100 ,2,'.','');
					$authenticateModel->update(array('Liveness'=>$Liveness),array('AuthenticateID = ?'=>$val['AuthenticateID']));
				}
			}
			$counts = count($rows);
			if($counts < $limit){
				break;
			}
			$lastID = $rows[$counts - 1]['AuthenticateID'];
		}
		$select = $authenticateModel->select();
		$select->from('member_authenticate',array('MemberID','Liveness'))->where('AuthenticateType = ?',2)->where('Status = ?',1);
		$result = $select->order('Liveness desc')->limit(50)->query()->fetchAll();
		if(!empty($result)){
			$redisObj = DM_Module_Redis::getInstance();
			$columnKey = 'FinancialPlanner';
			$columnKeyTmp = 'FinancialPlannerTmp';
			foreach($result as $row){
				$redisObj->zadd($columnKeyTmp,$row['Liveness'],$row['MemberID']);
			}
			$redisObj->rename($columnKeyTmp,$columnKey);
		}
	}


	
	/**
	 * 获取某个专栏下每天发布的文章数
	 * @param unknown $topicID
	 */
	private function getArticleNum($columnID)
	{
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article','count(1) as num')->where('ColumnID = ?',$columnID)->where('Status = ?',1)
		->where("date_format(PublishTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		$info = $select->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * 某个专栏下文章评分总和
	 */
	private function articleScore($infoID,$type = 'column')
	{
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article','SUM(Liveness) as score')->where('Status = ?',1);
		if($type == 'column'){
			$select = $select->where('ColumnID = ?',$infoID);
		}elseif($type == 'member'){
			$select = $select->where('MemberID = ?',$infoID);
		}
		$result = $select->query()->fetch();
		return $result['score'];
	}
}