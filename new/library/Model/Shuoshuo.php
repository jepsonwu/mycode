<?php
use Qiniu\json_decode;
/**
 * 会员发布说说
 * @author johnny 2015-07-07
 */

class Model_Shuoshuo extends Zend_Db_Table {
	protected $_name = 'shuoshuo';
	protected $_primary = array(1 => 'ShuoID');
	protected $_commentLimit = 0;
	protected $_commentReplyLimit = 0;
	
	/**
	 * 消息队列key
	 */
	public static function getRedisListKey()
	{
		return 'NewQueuePublishShuo';
	}
	
	/**
	 * 说说详情key
	 * @return string
	 */
	public static  function getShuoDetailKey($shuoID)
	{
		return 'NewShuoDetail:'.$shuoID;
	}
	
	/**
	 * 获取某人的财友圈key
	 * @param unknown $memberID
	 * @return string
	 */
	public static function  getCaiYQKey($memberID)
	{
		return 'Friends:ShuoShuo:MemberID'.$memberID;
	}
	
	

	/**
	 * @param Int | string | array | select obj $where
	 * @param string | array $orderBy
	 * @param int $limit
	 * @return array | null
	 */
	public function getShuos($where = null, $orderBy = null, $limit = null, $offset = null) {
		if( !$where ) {
			return null;
		}
		if( is_numeric($where) ) {
			$where = $this->_name . '.' . $this->_primary[1] . '=' . $where . ' AND Status = 1';
		}
		if( !$orderBy ) {
			$orderBy = $this->_primary[1] . ' DESC';
		}
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name);
		$select->joinLeft('shuo_images', 'shuo_images.'.$this->_primary[1].'='.$this->_name.'.'.$this->_primary[1], 'group_concat(shuo_images.Url order by SortIndex ASC, ImageID ASC) as imagesURL');
		$select->group($this->_name.'.'.$this->_primary[1]);
		$this->_where($select, $where);
		// echo $select->__toString();
		$data = $this->fetchAll($select, $orderBy, $limit, $offset)->toArray();
		return empty($data) ? null : $data;
	}

	
	/**
	 * 获取说说列表
	 * @param unknown $memberID
	 * @param unknown $lastID
	 * @param unknown $pagesize
	 * @param unknown $currentMemberID
	 */
	public function getShuoshuoList($memberID, $lastID, $pagesize, $currentMemberID)
	{
		$redis = DM_Module_Redis::getInstance();
		// 从redis获取说说
		if($lastID) {
			$result = $redis->zRevRangeByScore('ShuoShuo:MemberID:'.$memberID, '('.$lastID, '-inf', array('limit'=>array(0, $pagesize)));
		} else {
			$result = $redis->zRevRangeByScore('ShuoShuo:MemberID:'.$memberID, '+inf', '-inf', array('limit'=>array(0, $pagesize)));
		}
		$memberNoteModel = new Model_MemberNotes();
		
		$modelShuoImage = new Model_ShuoImage();
		$data = array();
		if(!empty($result)){
			foreach($result as $k=>$shuoID){
				$shuoDetail = $redis->HGETALL(self::getShuoDetailKey($shuoID));
				
				if(empty($shuoDetail)){
					$shuoDetail = $this->getShuos($shuoID);
					if(empty($shuoDetail)){//表里的数据被清楚
						unset($result[$k]);
						continue;
					}
					$shuoDetail = current($shuoDetail);
					$shuoDetailTmp = array('Txt'=>$shuoDetail['ShuoTxt'], 'Img'=>'', 
											'Time'=>strtotime($shuoDetail['CreateTime']), 'By'=>$shuoDetail['MemberID'],
											'ContentType'=>$shuoDetail['ContentType'],'RelationID'=>$shuoDetail['RelationID'],
											'Title'=>$shuoDetail['Title'],'Link'=>$shuoDetail['Link'],'LinkImage'=>$shuoDetail['LinkImage']);
					$redis->hmset(self::getShuoDetailKey($shuoID),$shuoDetailTmp);
					$redis->EXPIRE(self::getShuoDetailKey($shuoID),30*86400);
					$shuoDetail = $shuoDetailTmp;

				}
				if(!empty($shuoDetail)){
					$this->addExtraInfo($shuoDetail,$shuoID);
					$data[$k]=$shuoDetail;
					$data[$k]['ID'] = $shuoID;
					$data[$k]['Time'] = date('Y-m-d H:i:s',$data[$k]['Time']);
					
					// 图片为空时，返回空数组
					$img = $modelShuoImage->getImageURLs($shuoID,1);
					$data[$k]['Img'] = empty($img)?array():$img;
					$modelAccount = new DM_Model_Account_Members();
					$data[$k]['Avatar'] = $modelAccount->getMemberAvatar($data[$k]['By']);
					$data[$k]['ByUserName'] = $modelAccount->getMemberInfoCache($data[$k]['By'], 'UserName');
					$data[$k]['ByNoteName'] = $memberNoteModel->getNoteName($currentMemberID, $data[$k]['By']);
					//说说的评论
					$comment = $this->getCommentList($shuoID,$memberID,$currentMemberID);
					$data[$k]['CommentCount'] = count($comment['Rows']);
					$data[$k]['Comments'] = $comment;
					
					//说说的点赞
					$praise = $this->getPraiseList($shuoID,$memberID,$currentMemberID);
					$data[$k]['PraiseBy'] = $praise['PraiseBy'];
					$data[$k]['PraiseByMyself'] = $praise['PraiseByMyself'];
					$data[$k]['PraiseCount'] = $praise['PraiseCount'];
				}
			}
		}
		return array_values($data);
	}
	
	/**
	 * 获取赞的数据
	 * @param unknown $shuoID
	 * @param unknown $memberID
	 * @param unknown $currentMemberID
	 * @return multitype:boolean number multitype: 
	 */
	public function getPraiseList($shuoID,$memberID,$currentMemberID)
	{
		$redis = DM_Module_Redis::getInstance();
		$PraiseBy = array();
		$modelAccount = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
		$pipe = $redis->pipeline();
		$shuoPraiseModel = new Model_ShuoPraise();
		////说说发布者好友的点赞（好友关注可能解除了，所以需要一次交集）
		$pipe->zInter('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID, array('ShuoPraise:ShuoID:'.$shuoID, Model_IM_Friend::getFriendCacheKey($memberID)), array(1, 0), 'MAX');
		if( $currentMemberID != $memberID ) {//查看别人的说说
			//查看说说的人看到的赞
			$pipe->zInter('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID.':ViewMember:'.$currentMemberID, array('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID, Model_IM_Friend::getFriendCacheKey($currentMemberID)), array(1, 0), 'MAX');
			$pipe->zRange('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID.':ViewMember:'.$currentMemberID, 0, -1, true);
			$tmp = $pipe->exec();
			//管道是两个结果集的集合
			$tmp = isset($tmp[2]) ? $tmp[2] : false;
			$redis->delete('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID.':ViewMember:'.$currentMemberID);
			//说说发布者自己是否点赞
			$tmpScore = $shuoPraiseModel->isPraised($shuoID, $memberID);
			if($tmpScore){
				$PraiseBy[$tmpScore] = array(
						'ID'	=> $memberID,
						'UserName' => $modelAccount->getUserName($memberID,$currentMemberID),
						'NoteName'=>$memberNoteModel->getNoteName($currentMemberID, $memberID),
						'Avatar'=>$modelAccount->getMemberAvatar($memberID)
				);
			}
		}else{//擦看自己的说说
			$pipe->zRange('ShuoPraise:ShuoID:'.$shuoID,0,-1,true);
			$tmp = $pipe->exec();
			$tmp = isset($tmp[1]) ? $tmp[1] : false;
		}
		
		$redis->delete('ShuoPraise:ShuoID:'.$shuoID.':MemberID:'.$memberID);
		
		if($tmp) {
			foreach ($tmp as $PraiseByMemberID => $PraiseID) {
				$PraiseBy[$PraiseID] = array(
						'ID' => $PraiseByMemberID,
						'UserName' => $modelAccount->getUserName($PraiseByMemberID, $memberID),
						'NoteName'=>$memberNoteModel->getNoteName($currentMemberID, $PraiseByMemberID),
						'Avatar'=>$modelAccount->getMemberAvatar($PraiseByMemberID)
				);
			}
			unset($tmp, $PraiseByMemberID, $PraiseID);
		}
		
		$PraiseByMyself = false;
		//当前登录用户是否点赞
		$tmpScore = $shuoPraiseModel->isPraised($shuoID,$currentMemberID);
		if($tmpScore){
			$PraiseBy[$tmpScore] = array(
					'ID'	=> $currentMemberID,
					'UserName' => $modelAccount->getUserName($currentMemberID,$currentMemberID),
					'NoteName' => '',
					'Avatar'=>$modelAccount->getMemberAvatar($currentMemberID)
			);
			$PraiseByMyself = true;
		}
		
		@ksort($PraiseBy);
		$PraiseList = $PraiseBy ? array_values($PraiseBy) : array();
		$PraiseCount = count($PraiseList);
		return array('PraiseBy'=>$PraiseList,'PraiseByMyself'=>$PraiseByMyself,'PraiseCount'=>$PraiseCount);
	}
	
	/**
	 * 获取评论数据
	 */
	public function getCommentList($shuoID,$memberID,$currentMemberID)
	{
		$redis = DM_Module_Redis::getInstance();
		//说说作者的好友
		$cachekey = Model_IM_Friend::getFriendCacheKey($memberID);
		$friends = $redis->zRangeByScore($cachekey, '-inf', '+inf');
 		if($currentMemberID != $memberID ) {
 			//当前查看人的好友
			$cachekey = Model_IM_Friend::getFriendCacheKey($currentMemberID);
			$friendsOfView = $redis->zRangeByScore($cachekey, '-inf', '+inf');
		}
		$modelAccount = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
		//获取某个说说的所有评论
		$comments = $redis->zRange('ShuoComment:ShuoID:'.$shuoID, 0, $this->_commentLimit - 1);
		@sort($comments);
		$commentList = array();
		if($comments) {
			foreach ($comments as $key => $commentID) {
				$shuoCommentDetail = $redis->hGETAll('NewShuoCommentDetail:'.$commentID);
				if(empty($shuoCommentDetail)){
					$modelShuoComment = new Model_ShuoComment();
					$shuoCommentDetail = current($modelShuoComment->getComments($commentID));
					if(!empty($shuoCommentDetail)){
						if((isset($shuoCommentDetail['At']) && ($shuoCommentDetail['At'] != 0) && ($shuoCommentDetail['At'] != $memberID) && !in_array($shuoCommentDetail['At'], $friends)) || ( isset($shuoCommentDetail['CommentBy']) && ($shuoCommentDetail['CommentBy'] != $memberID) && !in_array($shuoCommentDetail['CommentBy'], $friends)) ) {
							// 只能看到好友和自己的评论
							continue;
						}
						
						$redisData = array('Txt'=>$shuoCommentDetail['CommentTxt'], 'Time'=>strtotime($shuoCommentDetail['CreateTime']), 'By'=>$shuoCommentDetail['CommentBy'],'At'=>$shuoCommentDetail['At']);
						$commentList[$key] = $redisData;
						$commentList[$key]['ID'] = (int) $commentID;
						$redis->hmset('NewShuoCommentDetail:'.$commentID,$redisData);
						$redis->EXPIRE('NewShuoCommentDetail:'.$commentID,30*86400);
						$shuoCommentDetail = $redisData;
					} else {
						// 数据库中也没有记录，那就将redis下的评论记录删除
						$redis->ZREM('ShuoComment:ShuoID:'.$shuoID, $commentID);
						continue;
					}
				}
				if(!empty($shuoCommentDetail)) {
					if( (isset($shuoCommentDetail['At']) && ($shuoCommentDetail['At'] != 0) && ($shuoCommentDetail['At'] != $memberID) && !in_array($shuoCommentDetail['At'], $friends)) || ( isset($shuoCommentDetail['By']) && ($shuoCommentDetail['By'] != $memberID) && !in_array($shuoCommentDetail['By'], $friends)) ) {
						// 只能看到好友和自己的评论(@了作者的非好友，作者也看不到)
						continue;
							
					}
					if($memberID != $currentMemberID){
						
						if((isset($shuoCommentDetail['At']) && ($shuoCommentDetail['At'] != 0) && ($shuoCommentDetail['At'] != $currentMemberID) && !in_array($shuoCommentDetail['At'], $friendsOfView)) || ( isset($shuoCommentDetail['By']) && ($shuoCommentDetail['By'] != $currentMemberID) && !in_array($shuoCommentDetail['By'], $friendsOfView)) ) {
							// 只能看到好友和自己的评论（@了不是好友的人，是看不到的）
							continue;
						}
					}
						
					$shuoCommentDetail['Time'] = date('Y-m-d H:i:s', $shuoCommentDetail['Time']);
					$commentList[$key] = $shuoCommentDetail;
					$commentList[$key]['ID'] = (int) $commentID;
					if( isset($commentList[$key]['At']) ) {
						$commentList[$key]['AtUserName'] = $modelAccount->getUserName($commentList[$key]['At'], $memberID);
						$commentList[$key]['AtNoteName'] = $memberNoteModel->getNoteName($currentMemberID, $commentList[$key]['At']);
						$commentList[$key]['AtAvatar'] = $modelAccount->getMemberAvatar($commentList[$key]['At']);
					} else {
						$commentList[$key]['At'] = 0;
						$commentList[$key]['AtUserName'] = '';
						$commentList[$key]['AtNoteName'] = '';
						$commentList[$key]['AtAvatar'] = '';
					}
				}
				$commentList[$key]['ByUserName'] = $modelAccount->getUserName($commentList[$key]['By'], $memberID);
				$commentList[$key]['ByNoteName'] = $memberNoteModel->getNoteName($currentMemberID, $commentList[$key]['By']);
				$commentList[$key]['ByAvatar'] = $modelAccount->getMemberAvatar($commentList[$key]['By']);
			}
		} 
		// 将总数变为只有好友评论的总数，而不是所有人评论的总数
		return array('Rows'=>array_values($commentList));
	}

	/**
	 * @param int $memberID 会员ID
	 * @param string $shuoText 说说内容
	 * @return boolean
	 */
	public function addShuo($memberID = null, $shuoText = '', $imagesURL = '', $contentType = 1,$relationID=0,$title = '',$link = '',$linkImage = '') {
		
		$data = array(
				'MemberID' => $memberID,
				'ShuoTxt' => $shuoText,
				'ContentType' => $contentType,
				'Status' => 1,
				'RelationID'=>$relationID,
				'Title'=>$title,
				'Link'=>$link,
				'LinkImage'=>$linkImage
		);
		$shuoID = $this->insert($data);
		if($shuoID){
			$images = array();
			if(!empty($imagesURL)){
				$modelShuoImage = new Model_ShuoImage();
				$images = $modelShuoImage->addImageURL($shuoID, $imagesURL);	
			}
			// 说说push到好友财友圈的推送队列
			$redis = DM_Module_Redis::getInstance();

			$redis->hmset(self::getShuoDetailKey($shuoID), array('Txt'=>$shuoText, 'Img'=>'', 'Time'=>time(), 'By'=>$memberID, 'ContentType'=>(int) $contentType ,
					'RelationID'=>(int) $relationID,'Title'=>$title,'Link'=>$link,'LinkImage'=>$linkImage));
			// 说说详情在30天后过期
			$redis->EXPIRE(self::getShuoDetailKey($shuoID), 30 * 86400);
			$value = 'publish-'.$shuoID.'-'.$memberID;
			$redis->RPUSH(self::getRedisListKey(),$value);
			//先把说说分发给自己
			$redis->zAdd(self::getCaiYQKey($memberID), $shuoID, $shuoID);
			
			$redis->zAdd('ShuoShuo:MemberID:'.$memberID, $shuoID, $shuoID);
			$redis->hSet('ShuoLasttime', $memberID, time());
			return true;
		} else {
			return false;
		}
	}


	
	public function delShuo2($shuoID)
	{
		$this->update(array('Status' => 0, array('ShuoID = ?'=>$shuoID)));
	}


	
	/**
	 * 获取用户的说说数量
	 * @param int $memberID 会员ID
	 */
	public function getShuosCount($memberID) {
		if( !$memberID = (int) $memberID ) {
			throw new Exception('会员ID不能为空');
		}
		$select = $this->select()->from($this->_name,'count(1) as num');
		$select->where('MemberID = ?', $memberID);
		$select->where('Status = ?', 1);
		$row = $select->query()->fetch();
		return isset($row['num']) ? intval($row['num']) : 0;
	}

	/**
	 *  是否有财友圈新说说
	 * @param int $memberID
	 * @param int $lastID
	 */
	public function hasNewCaiYouQuan($memberID)
	{
		$lastID = Model_Member::staticData($memberID,'maxCYQID');
		$counts = 0;
		if($lastID !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$key = self::getCaiYQKey($memberID);
			$counts = $redisObj->zcount($key,'('.$lastID,'+inf');
		}
		return $counts ? $counts : 0;
	}
	
	public function newGetCaiYouQuan($memberID, $lastID, $pagesize)
	{
		$redis = DM_Module_Redis::getInstance();
		if($lastID >0) {
			$result = $redis->zRevRangeByScore(self::getCaiYQKey($memberID), '('.$lastID, '-inf', array('limit'=>array(0, $pagesize)));
		} else {
			$result = $redis->zRevRangeByScore(self::getCaiYQKey($memberID), '+inf', '-inf', array('limit'=>array(0, $pagesize)));
			//标识最大ID
			$maxPosition = reset($result);
			if($maxPosition){
				Model_Member::staticData($memberID,'maxCYQID',$maxPosition);
			}
		}
		$memberNoteModel = new Model_MemberNotes();
		
		$modelShuoImage = new Model_ShuoImage();
		$data = array();
		if(!empty($result)){
			foreach($result as $k=>$shuoID){
				$shuoDetail = $redis->HGETALL(self::getShuoDetailKey($shuoID));
				if(empty($shuoDetail)){
					$shuoDetail = $this->getShuos($shuoID);
					$shuoDetail = current($shuoDetail);
					$shuoDetailTmp = array('Txt'=>$shuoDetail['ShuoTxt'], 'Img'=>'',
							'Time'=>strtotime($shuoDetail['CreateTime']), 'By'=>$shuoDetail['MemberID'],
							'ContentType'=>$shuoDetail['ContentType'],'RelationID'=>$shuoDetail['RelationID'],
							'Title'=>$shuoDetail['Title'],'Link'=>$shuoDetail['Link'],'LinkImage'=>$shuoDetail['LinkImage']);
					$redis->hmset(self::getShuoDetailKey($shuoID),$shuoDetailTmp);
					$redis->EXPIRE(self::getShuoDetailKey($shuoID),30*86400);
					$shuoDetail = $shuoDetailTmp;
				}
				$this->addExtraInfo($shuoDetail,$shuoID);
				$data[$k]=$shuoDetail;
				$data[$k]['ID'] = $shuoID;
				$data[$k]['Time'] = date('Y-m-d H:i:s', $data[$k]['Time']);
		
				// 图片为空时，返回空数组
				$img = $modelShuoImage->getImageURLs($shuoID,1);
				$data[$k]['Img'] = empty($img)?array():$img;
				$modelAccount = new DM_Model_Account_Members();
				$data[$k]['Avatar'] = $modelAccount->getMemberAvatar($data[$k]['By']);
				$data[$k]['ByUserName'] = $modelAccount->getUserName($data[$k]['By'], $memberID);
				$data[$k]['ByNoteName'] = $memberNoteModel->getNoteName($memberID, $data[$k]['By']);
				//说说的评论
				$comment = $this->getCommentList($shuoID,$data[$k]['By'],$memberID);
				$data[$k]['CommentCount'] = count($comment['Rows']);
				$data[$k]['Comments'] = $comment;
		
				//说说的点赞
				$praise = $this->getPraiseList($shuoID,$data[$k]['By'],$memberID);
				$data[$k]['PraiseBy'] = $praise['PraiseBy'];
				$data[$k]['PraiseByMyself'] = $praise['PraiseByMyself'];
				$data[$k]['PraiseCount'] = $praise['PraiseCount'];
			}
		}
		return $data;
	}

	/**
	 * 将财友圈分发到每个好友
	 */
	/*public function pushCaiYouQuan() {
		header('Content-Type: text/plain; charset=utf-8');
		$redis = DM_Module_Redis::getInstance();
		while(true) {
			if( date('H-i-s') == '23-59-59' ) {
				// 每日23点59分59秒钟退出脚本
				exit;
			}
			if( $redis->LLEN('QueuePublishShuo') <= 0 ) {
				// no task then sleep one second
				sleep(1);
				continue;
			}
			$task = array();
			$tmp = json_decode($redis->LPOP('QueuePublishShuo'), true);
			if( isset($tmp['ID'], $tmp['From']) && $tmp['ID'] && $tmp['From'] ) {
				$task = $tmp;
			}
			unset($tmp);
			//$len--;

			if( $task ) {
				$shuoID = $task['ID']; //说说ID
				$memberID = $task['From']; //说说发布者
				$friends = array();
				// 获取发布说说者的所有好友，其中包括自己
				$cachekey = Model_IM_Friend::getFriendCacheKey($memberID);
				
				if( $friends = $redis->zRangeByScore($cachekey, '-inf', '+inf') ) {
					//$friends = $redis->zRange('User:Friends:MemberID:'.$memberID, 0, -1);
				} else {
					if( !isset($model) ) {
						$model = new Model_IM_Friend();
					}
					if( $tmp = $model->getFriendInfo($memberID) ) {
						foreach ($tmp as $val) {
							$friends[] = $val['FriendID'];
						}
					}
					unset($tmp);
				}
				// 添加自己到队列
				$friends[] = $memberID;

				if( $friends ) {
					$pipe = $redis->pipeline();
					foreach ($friends as $friendID) {
						echo '说说'.$shuoID.'=>push到会员'.$memberID.'的好友'.$friendID.PHP_EOL;
						$pipe->zADD('CaiYouQuan:MemberID:'.$friendID, $shuoID, $shuoID);
					}
					$pipe->exec();
				} else {
					echo '获取会员'.$memberID.'的好友失败'.PHP_EOL;
				}
				unset($shuoID, $memberID, $friends, $task);
				echo 'sleep for 0.05 second every job'.PHP_EOL;
				usleep(50000);
			}
		}
	}*/
	
	/**
	 * 添加额外信息
	 * @param unknown $info
	 */
	public function addExtraInfo(&$info)
	{
		$relationModel = new Model_shuoRelation();
		$relationInfo = $relationModel->getRelationInfo($info['ContentType'], $info['RelationID']);
// 		$requestObj = DM_Controller_Front::getInstance()->getHttpRequest();
// 		$version = $requestObj->getParam('currentVersion','1.0.0');
// 		if(isset($info['Txt']) && empty($info['Txt']) && $info['ContentType'] != 1){
// 			if(version_compare($version, '2.0.1') <= 0){
// 				$info['Txt'] = "[该版本不支持该功能，请下载新版本]";
// 			}
// 		}
// 		if(isset($info['Txt']) && empty($info['Txt']) && $info['ContentType'] == 3){
// 			if(version_compare($version, '2.1.0') <= 0){
// 				$info['Txt'] = "[该版本不支持该功能，请下载新版本]";
// 			}
// 		}
		$info['relationContent'] = $relationInfo['relationContent'];
		$info['relationImage'] = $relationInfo['relationImage'];
	
	}
	
	/**
	 * 获取某个人能看到的说说评论数
	 * @param unknown $member
	 * @param unknown $shuoID
	 */
	public function getCommentCount($memberID,$shuoID)
	{
		$num = 0;
		$redis = DM_Module_Redis::getInstance();
// 		$redis->zInter('User:Friends:MemberID:'.$memberID, array('User:Fans:MemberID:'.$memberID, 'User:Follow:MemberID:'.$memberID), array(1, 1), 'MAX');
// 		$friends = $redis->zRange('User:Friends:MemberID:'.$memberID, 0, -1);
		$cachekey = Model_IM_Friend::getFriendCacheKey($memberID);
		$friends = $redis->zRangeByScore($cachekey, '-inf', '+inf');
		$shuoDetail = $redis->hGETAll(self::getShuoDetailKey($shuoID));
		if(!empty($shuoDetail))
		{
			$comments = $redis->zRevRange('ShuoComment:ShuoID:'.$shuoID, 0,- 1);
			if(!empty($comments)) {
				if($shuoDetail['By'] == $memberID){//自己的说说
					$num = count($comments);
				}else{
					foreach ($comments as $k => $commentID) {
						$shuoCommentDetail = $redis->hGETALL('NewShuoCommentDetail:'.$commentID);
						if(!empty($shuoCommentDetail)) {
							if( (isset($shuoCommentDetail['At']) && ($shuoCommentDetail['At'] != 0) && ($shuoCommentDetail['At'] != $memberID) && !in_array($shuoCommentDetail['At'], $friends)) || ( isset($shuoCommentDetail['By']) && ($shuoCommentDetail['By'] != $memberID) && !in_array($shuoCommentDetail['By'], $friends)) ) {
								// 只能看到好友和自己的评论
								continue;
							}
							$num++;
						}
					}
				}
			}
		}
		return $num;
	}
	
	/**
	 * 财友圈新的分发机制
	 */
	public function newPushCaiYouquan()
	{
		$redisObj = DM_Module_Redis::getInstance();
		$caiYQKey = self::getRedisListKey();
		while(true){
			$value = $redisObj->lpop($caiYQKey);
			if(empty($value)){
				usleep(500000);//0.5秒
				$s = date('s');
				if( $s >= 58){
					break;
				}
				continue;
			}
			$arr = explode('-',$value);
			if($arr[0] == 'publish'){//发布说说事件
				$shuoID = $arr[1];
				$ownerID = $arr[2];
					
				//查询当前说说发布者的好友，把说说分发给自己和好友
				$friendsKey = Model_IM_Friend::getFriendCacheKey($ownerID);
				$friendsArr = $redisObj->zRevRangeByScore($friendsKey,'+inf','-inf');
				if(empty($friendsArr)){
					$friendsArr = array($ownerID);
				}else{
					array_unshift($friendsArr,$ownerID);
				}
				foreach($friendsArr as $memberID){
					$redisObj->zadd(self::getCaiYQKey($memberID),$shuoID,$shuoID);
				}
		
			}elseif($arr[0] == 'delete'){//删除说说事件
				$shuoID = $arr[1];
				$ownerID = $arr[2];
				//查询当前说说发布者的好友
				$friendsKey = Model_IM_Friend::getFriendCacheKey($ownerID);
				$friendsArr = $redisObj->zRevRangeByScore($friendsKey,'+inf','-inf');
				if(empty($friendsArr)){
					$friendsArr = array($ownerID);
				}else{
					array_unshift($friendsArr,$ownerID);
				}
				foreach($friendsArr as $memberID){
					$redisObj->zrem(self::getCaiYQKey($memberID),$shuoID);
				}
		
			}elseif ($arr[0]== 'unFriend') {//取消好友事件
				$memberID = $arr[1];
				$ownerID = $arr[2];
				$shuoArr = $this->select()->from($this->_name,array('ShuoID'))->where('MemberID = ?',$memberID)->where('Status = ?',1)->query()->fetchAll();
				if(!empty($shuoArr)){
					foreach($shuoArr as $val){
						$redisObj->zrem(self::getCaiYQKey($ownerID),$val['ShuoID']);
					}
				}
			}
		}
	}
	
	/**
	 * 老的说说数据重新分发一次
	 */
	public function syncShuoshuoData()
	{
		$lastID = 0;
		$limit = 1000;
		$redisObj = DM_Module_Redis::getInstance();
		while(true){
			$shuoArr = $this->select()->from($this->_name,array('ShuoID','MemberID','Status'))
			->where('ShuoID > ?',$lastID)->order('ShuoID ASC')->limit($limit)->query()->fetchAll();
			if(!empty($shuoArr)){//把每一条说说分发给自己和好友
				foreach ($shuoArr as $val){
					$friendsKey = Model_IM_Friend::getFriendCacheKey($val['MemberID']);
					$friendsArr = $redisObj->zRevRangeByScore($friendsKey,'+inf','-inf');
					if(empty($friendsArr)){
						$friendsArr = array($val['MemberID']);
					}else{
						array_unshift($friendsArr,$val['MemberID']);
					}
					if($val['Status'] == 1){
						foreach($friendsArr as $memberID){
							$redisObj->zadd(self::getCaiYQKey($memberID),$val['ShuoID'],$val['ShuoID']);
						}
					}else{
						
						//把已删除的说说从缓存中删除
						$redisObj->zrem('ShuoShuo:MemberID:'.$val['MemberID'],$val['ShuoID']);
					}
				}
			}
			$counts = count($shuoArr);
			if($counts < $limit){
				break;
			}
			$lastID = $shuoArr[$counts - 1]['ShuoID'];
		}
	}

}