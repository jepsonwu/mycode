<?php
/**
 * 说说相关模块 @author johnny 2015-07-07
 * 修改了点赞action的名称为praise，完善点赞功能
 */

class Api_ShuoshuoController extends Action_Api {

	protected $_CaiYouQuanLimit = 10;

	public function init() {
		parent::init();
		$this->isLoginOutput();
		$this->checkDeny();
	}
	
	/**
	 * 说说列表
	 */
	public function listAction()
	{
		try{
			$memberID = intval($this->_getParam('memberID'));
			if(!$memberID) {// 自己查看自己的说说
				$memberID = $this->memberInfo->MemberID;
			}
			$currentMemberID = $this->memberInfo->MemberID;
			if($currentMemberID != $memberID) {
				$modelMemberFollow = new Model_MemberFollow();
				$relationCode = $modelMemberFollow->getRelation($memberID,$currentMemberID);
				if($relationCode != 3) {
					throw new Exception('非好友不能查看他人说说');
				}
			}
			$pagesize = intval($this->_request->getParam('pagesize', 10));
			$lastID = intval($this->_request->getParam('lastID',0));
			$model = new Model_Shuoshuo();
			$shuos = $model->getShuoshuoList($memberID, $lastID, $pagesize, $currentMemberID);
			$this->returnJson(parent::STATUS_OK,'', array('Rows'=>$shuos));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
	
	/**
	 * 发布说说
	 */
	public function publishAction(){
		try {
			$memberID = $this->memberInfo->MemberID;
			$shuoText = trim($this->_request->getParam('content'));
			$imagesURL = trim($this->_request->getParam('images'));
			$contentType = intval($this->_request->getParam('contentType',1));
			$relationID = intval($this->_request->getParam('relationID',0));
			$title = trim($this->_request->getParam('title',''));
			$link = trim($this->_request->getParam('link',''));
			$linkImage = trim($this->_request->getParam('linkImage',''));
			
			if($contentType==1 && !$shuoText) {
				throw new Exception('说说内容不能为空');
			}
			if($contentType==2 && $relationID<=0){
				throw new Exception('参数错误');
			}
			$model = new Model_Shuoshuo();
			$model->addShuo($memberID, $shuoText, $imagesURL,$contentType,$relationID,$title,$link,$linkImage);
			$this->returnJson(parent::STATUS_OK, '发布成功！');
		} catch (Exception $e) {
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
	
	/**
	 * 删除说说
	 */
	public function unPublishAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$shuoID = (int)$this->_request->getParam('shuoID');
			if(!$shuoID){
				throw new Exception('说说ID不能为空');
			}
			$model = new Model_Shuoshuo();
			$shuoData = $model->getShuos($shuoID);
			if(empty($shuoData)) {
				throw new Exception('该说说不存在');
			}
			if( $shuoData[0]['MemberID'] != $memberID ) {
				throw new Exception('该说说不属于该用户');
			}
			$re = $model->update(array('Status' => 0),array('ShuoID = ?'=>$shuoID));
			if($re){
				$redisObj = DM_Module_Redis::getInstance();
				$value = 'delete-'.$shuoID.'-'.$memberID;
				$redisObj->rpush(Model_Shuoshuo::getRedisListKey(),$value);
				//删除缓存的说说及详细信息
				$redisObj->zrem('ShuoShuo:MemberID:'.$memberID, $shuoID);
				$redisObj->del(Model_Shuoshuo::getShuoDetailKey($shuoID));
			}
			$this->returnJson(parent::STATUS_OK, '删除成功');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
	
	/**
	 * 说说点赞
	 */
	public function praiseAction()
	{
		try{
			$shuoID = (int) $this->_request->getParam('shuoID');
			$memberID = $this->memberInfo->MemberID;
			if( !$shuoID ) {
				throw new Exception('必须指定点赞的说说');
			}
			$modelShuoshuo = new Model_Shuoshuo();
			$shuoData = $modelShuoshuo->getShuos($shuoID);
			if(empty($shuoData)){
				throw new Exception('说说不存在');
			}
			$modelMemberFollow = new Model_MemberFollow();
			$relation = $modelMemberFollow->getRelation($shuoData[0]['MemberID'], $memberID);
			if( $relation != 3 && $relation != -1 ) {
				throw new Exception("不是好友关系不能点赞");
			}
			$modelShuoPraise = new Model_ShuoPraise();
			$modelShuoPraise->addPraise($shuoID, $memberID);
			$this->returnJson(parent::STATUS_OK, '成功点赞');
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
	
	/**
	 * 取消点赞
	 */
	public function unPraiseAction()
	{
		try{
			$shuoID = (int) $this->_request->getParam('shuoID');
			$memberID = $this->memberInfo->MemberID;
			if( !$shuoID ) {
				$this->returnJson(parent::STATUS_FAILURE, '必须指定点赞的说说');
			}
			$modelShuoshuo = new Model_Shuoshuo();
			if($modelShuoshuo->getShuos($shuoID) == null) {
				throw new Exception('该说说不存在');
			}
			$modelShuoPraise = new Model_ShuoPraise();
			$modelShuoPraise->delPraises($shuoID,$memberID);
			$this->returnJson(parent::STATUS_OK, '成功取消点赞');	
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	
	/**
	 * 评论说说
	 */
	public function addCommentAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$shuoID =intval($this->_request->getParam('shuoID'));
			$commentTxt = trim($this->_request->getParam('content'));
			$At = intval($this->_request->getParam('At',0));
			if(!$At) {
				$At = $memberID;
				$msg = '评论';
			} else {
				$msg = '回复';
			}
			if(empty($commentTxt)){
				throw new Exception($msg.'内容不能为空');
			}
			$modelShuoshuo = new Model_Shuoshuo();
			$shuoData = $modelShuoshuo->getShuos($shuoID);
			if($shuoData == null){
				throw new Exception('该说说不存在');
			}
			$modelMemberFollow = new Model_MemberFollow();
			$relation = $modelMemberFollow->getRelation($shuoData[0]['MemberID'], $memberID);
			if ($relation != 3 && $relation != -1) {
				throw new Exception('不是好友关系不能评论',-108);
			}
			if($At != $memberID){
				$relationAt = $modelMemberFollow->getRelation($memberID,$At);
				if ($relationAt != 3 && $relationAt != -1) {
					throw new EXception('不是好友关系不能回复',-108);
				}
			}
			$model = new Model_ShuoComment();
			$modelAccount = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
			$insertID = $model->newAddComment($shuoID, $commentTxt, $memberID, $At,$shuoData[0]['MemberID']);
			if($insertID){
				$data = array(
					'ID'=>$insertID,
					'Txt'=>stripcslashes($commentTxt),
					'Time'=>date('Y-m-d H:i:s'),
					'By' =>$memberID,
					'ByUserName'=>$modelAccount->getMemberInfoCache($memberID, 'UserName'),
					'ByNoteName'=>'',
					'ByAvatar' =>$modelAccount->getMemberAvatar($memberID),
					'At' =>$At,
					'AtAvatar'=>$modelAccount->getMemberAvatar($At),
					'AtUserName'=>$modelAccount->getMemberInfoCache($At,'UserName'),
					'AtNoteName' => $memberNoteModel->getNoteName($memberID, $At),
					'Status'=>1
				);
				
				$this->returnJson(parent::STATUS_OK, $msg.'成功',$data);
			}
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	
	/**
	 * 说说详情，包含评论
	 */
	public function detailV2Action() 
	{
		if( !$shuoID = (int) $this->_request->getParam('shuoID') ) {
			$this->returnJson(parent::STATUS_FAILURE, '说说ID不能为空');
		}
		
		$memberID = $this->memberInfo->MemberID;
		$modelShuoshuo = new Model_Shuoshuo();
		if( !$shuoData = $modelShuoshuo->getShuos($shuoID) ) {
			$this->returnJson(parent::STATUS_FAILURE, '说说不存在');
		}
		$shuoData = current($shuoData);
		$shuoMemberID = $shuoData['MemberID'];
		$modelMemberFollow = new Model_MemberFollow();
		$relation = $modelMemberFollow->getRelation($shuoData['MemberID'], $memberID);
		if( $relation != 3 && $relation != -1 ) {
			$this->returnJson(parent::STATUS_NEED_FRIENDS, '非好友不能查看他人说说');
		}
		$modelShuoPraise = new Model_ShuoPraise();
		$modelAccount = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
	
		$pariseArr = $modelShuoshuo->getPraiseList($shuoID,$shuoMemberID,$memberID);
		
		$result = array(
				'ID' => (int) $shuoData['ShuoID'],
				'Txt' => stripcslashes($shuoData['ShuoTxt']),
				'Img' => $shuoData['imagesURL'] ? explode(',', $shuoData['imagesURL']) : array(),
				'Time' => $shuoData['CreateTime'],
				'ContentType' => (int) $shuoData['ContentType'],
				'RelationID' => (int) $shuoData['RelationID'],
				'Title' =>$shuoData['Title'],
				'Link' =>$shuoData['Link'],
				'LinkImage'=>$shuoData['LinkImage'],
				'By' => (int) $shuoData['MemberID'],
				'ByUserName' => $modelAccount->getUserName($shuoData['MemberID'], $memberID),
				'ByNoteName' => $memberNoteModel->getNoteName($memberID, $shuoData['MemberID']),
				'Avatar' => $modelAccount->getMemberAvatar($shuoData['MemberID']),
				'CommentCount'=>$modelShuoshuo->getCommentCount($memberID,$shuoID),
				'PraiseCount' =>  $pariseArr['PraiseCount'],
				'PraiseByMyself' => $pariseArr['PraiseByMyself'],
				'PraiseBy' => $pariseArr['PraiseBy']
		);
		
		$modelShuoshuo->addExtraInfo($result);
		//获取评论
		$comment = $modelShuoshuo->getCommentList($shuoID,$shuoMemberID,$memberID);
		$result['CommentCount'] = count($comment['Rows']);
		$result['Comments'] = $comment;
		$this->returnJson(parent::STATUS_OK, '', $result);
	}
	
	
	/**
	 * 获取评论列表
	 */
	public function commentListAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$shuoID = intval($this->_getParam('shuoID',0));
			$pagesize = intval($this->_getParam('pagesize',10));
			$lastID = intval($this->_getParam('lastID',0));
			if($shuoID<1){
				throw new Exception('说说ID错误');
			}
			$modelShuoshuo = new Model_Shuoshuo();
			if( ($shuoData = $modelShuoshuo->getShuos($shuoID)) == null ) {
				throw new Exception('该说说不存在');
			}
			$realPagesize = $pagesize;
			if($shuoData[0]['MemberID'] != $memberID){
				$pagesize = null;
			}
			$modelShuoComment = new Model_ShuoComment();
			$data = $modelShuoComment->getCommentList($shuoID,$lastID,$pagesize);
			
			$modelAccount = new DM_Model_Account_Members();
			$modelMemberFollow = new Model_MemberFollow();
			$memberNoteModel = new Model_MemberNotes();
				
			$realCounter = 0;
			$result = array();
			if(!empty($data)){
				foreach ($data as $val) {
					// 自己的说说，去除已经解除好友关系的评论
					$relationSBy = $modelMemberFollow->getRelation($shuoData[0]['MemberID'], $val['CommentBy']);
					$relationSAt = $modelMemberFollow->getRelation($shuoData[0]['MemberID'], $val['At']);
					if(($relationSBy != -1 && $relationSBy != 3) || ($relationSAt != -1 && $relationSAt != 3)) {
						continue;
					}
					// 当说说不是自己发表的时候，只能看到自己好友的说说
					if( $shuoData[0]['MemberID'] != $memberID ) {
						$relationBy = $modelMemberFollow->getRelation($memberID, $val['CommentBy']);
						$relationAt = $modelMemberFollow->getRelation($memberID, $val['At']);
						if(($relationBy != -1 && $relationBy != 3) || ($relationAt != -1 && $relationAt != 3)) {
							continue;
						}
					}
					$result[] = array(
							'ID' => (int) $val['CommentID'],
							'Txt' => stripcslashes($val['CommentTxt']),
							'Time' => $val['CreateTime'],
							'By' => (int) $val['CommentBy'],
							'ByAvatar' => $modelAccount->getMemberAvatar($val['CommentBy']),
							'ByUserName' => $modelAccount->getUserName($val['CommentBy']),
							'ByNoteName' => $memberNoteModel->getNoteName($memberID, $val['CommentBy']),
							'At' => (int) $val['At'],
							'AtAvatar' => $modelAccount->getMemberAvatar($val['At']),
							'AtUserName' => $modelAccount->getUserName($val['At']),
							'AtNoteName' => $memberNoteModel->getNoteName($memberID, $val['At'])
					);
					$realCounter ++;
					if($realCounter >= $realPagesize){
						break;
					}
				}
			}
			$this->returnJson(parent::STATUS_OK, null, array('Rows'=>$result, 'Current'=>count($result), 'Total'=>$shuoData[0]['CommentCount']));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	
	/**
	 * 删除评论
	 */
	public function delCommentAction()
	{
		try{
			$commentID = intval($this->_request->getParam('commentID'));
			if($commentID<1){
				throw new Exception('评论ID不能为空');
			}
			$modelShuoComment = new Model_ShuoComment();
			$result = $modelShuoComment->getCommentInfo($commentID);
			if(empty($result)) {
				throw new Exception('评论已不存在');
			} elseif( $result['CommentBy'] != $this->memberInfo->MemberID ) {
				throw new Exception('该评论不属于该用户');
			}
			$shuoID = $result['ShuoID'];
			if($modelShuoComment->newDelComment($commentID,$shuoID) ) {
				$this->returnJson(parent::STATUS_OK, '删除成功');
			} else {
				$this->returnJson(parent::STATUS_FAILURE, '删除失败');
			}
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}

	/**
	 * 回复说说的评论
	 */
	public function addCommentReplyAction() {
		return $this->addCommentAction();
	}

	
	/**
	 * 财友圈
	 */
	public function caiYouQuanAction()
	{
		try{
			$pagesize = intval($this->_request->getParam('pagesize', 10));
			$lastID = intval($this->_request->getParam('lastID',0));
			$model = new Model_Shuoshuo();
			$shuos = $model->newGetCaiYouQuan($this->memberInfo->MemberID, $lastID, $pagesize);
			$this->returnJson(parent::STATUS_OK, '', array('Rows'=>$shuos));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
		}
	}
}