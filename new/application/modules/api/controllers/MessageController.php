<?php
/**
 *  消息
 * @author Mark
 *
 */

class Api_MessageController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	
	/**
	 * 是否显示小红点
	 */
	public function isShowPointAction()
	{
		$isShow = $counts = 0;
		$pointType = intval($this->_getParam('pointType',''));
		if(!empty($pointType)){
			switch($pointType){
				case 1:
					//财猪bar是否显示红点  当有新内容或新消息
					$messageModel = new Model_Message();
					$counts = $messageModel->newViewMessageCount($this->memberInfo->MemberID);
					if($counts <= 0){
						$viewModel = new Model_Topic_View();
						$counts = $viewModel->hasNewFollowedMemberViews($this->memberInfo->MemberID);
					}
					break;
				case 2:
					//消息是否显示红点      当有新粉丝时
					$model = new Model_MemberFollow();
					$counts = $model->hasNewFans($this->memberInfo->MemberID);
					break;
				case 3:
					//发现-财友圈红点       当有新说说时
					$shuoshuoModel = new Model_Shuoshuo();
					$counts = $shuoshuoModel->hasNewCaiYouQuan($this->memberInfo->MemberID);
					break;
			}
		}
		$isShow = $counts > 0 ? 1 : 0;
		$this->returnJson(parent::STATUS_OK,'',array('isShow'=>$isShow));
	}
	
	/**
	 * 新的是否显示红点
	 */
	public function newShowPointAction()
	{
		$pointType = $this->_getParam('pointType','');
		$platform = intval($this->_getParam('platform',1));
		$version = $this->_getParam('currentVersion','1.0.0');
		if(is_numeric($pointType)){
			switch($pointType){
				case 1:
					//财猪bar是否显示红点  当有新内容
					$type = 0;
					$articleModel = new Model_Column_Article();
					$counts = $articleModel->hasNewArticles($this->memberInfo->MemberID);
					break;
				case 2:
					//消息是否显示红点      当有新粉丝时
					$type = 0;
					$counts = 0;
					if(version_compare($version,'2.2.3',"<")){
						$model = new Model_MemberFollow();
						$counts = $model->hasNewFans($this->memberInfo->MemberID);
					}
					break;
				case 3:
					//财友圈红点       当有新说说时
					$type = 0;
					$avatar = '';
					$shuoshuoModel = new Model_Shuoshuo();
					$modelAccount = new DM_Model_Account_Members();
					$counts = $shuoshuoModel->hasNewCaiYouQuan($this->memberInfo->MemberID);
					if($counts<=0){
						$messageModel = new Model_Message();
						$sInfo = $messageModel->newViewMessageCount($this->memberInfo->MemberID,2);
						if(!empty($sInfo)){
							$counts = 1;
							if($sInfo['MessageType'] ==2){//评论说说
								$commentModel = new Model_ShuoComment();
								$commentInfo = $commentModel->getComments($sInfo['RelationID']);
								$avatar = !empty($commentInfo)?$modelAccount->getMemberAvatar($commentInfo[0]['CommentBy']):'';
							}else{//点赞说说
								$praiseModel = new Model_ShuoPraise();
								$praiseInfo = $praiseModel->getPraises($sInfo['RelationID']);
								$avatar = !empty($praiseInfo)? $modelAccount->getMemberAvatar($praiseInfo[0]['PraiseBy']):'';
							}
						}
						
					}else{
						//获取最新的说说ID
						$redisObj = DM_Module_Redis::getInstance();
						$key = Model_Shuoshuo::getCaiYQKey($this->memberInfo->MemberID);
						$re = $redisObj->ZREVRANGE($key,0,0);
						$shuoInfo = $shuoshuoModel->getShuos($re[0]);
						$memberID = empty($shuoInfo)? 0 : $shuoInfo[0]['MemberID'];
						$avatar = $memberID>0 ? $modelAccount->getMemberAvatar($memberID) : '';
						
					}
					if($counts>0){
						$type = 1;
					}
					$info['pointType'] = 3;
					$info['isShowPoint'] = $counts;
					$info['internalPoint'] = $type;
					$info['avatar'] = $avatar;
					break;
				case 4:
					// 话题，我关注的人产生新观点,新的观点消息
					$viewModel = new Model_Topic_View();
					$info =array();
					$type = 0;
					$avatar = '';
					$messageCount = 0;
					
					$counts = $viewModel->hasNewFriendViews($this->memberInfo->MemberID);
					
					//有没有新消息已经新消息头像
					$messageModel = new Model_Message();
					$messageinfo = $messageModel->newViewMessageCount($this->memberInfo->MemberID,1);
					if(!empty($messageinfo)){
						$counts = 1;
						$type=1;
						$messageCount = $messageModel->getMessageCount($this->memberInfo->MemberID,1);
						$replyModel = new Model_Topic_Reply();
						$replyInfo = $replyModel->getReplyInfo($messageinfo['RelationID']);
						$memberID = empty($replyInfo)? 0 : $replyInfo['MemberID'];
						$avatar = $memberID>0 ? $modelAccount->getMemberAvatar($memberID) : '';
					}
					$info['pointType'] = 4;
					$info['isShowPoint'] = $counts;
					$info['internalPoint'] = $type;
					$info['messageCount'] = $messageCount;
					$info['avatar'] = $avatar;
					break;
				case 5:
					//专栏的红点逻辑  我关注的专栏产生新内容  我关注的人产生新内容
					$columnModel = new Model_Column_Article();
					$counts = $columnModel->hasNewFollowedColumnArticles($this->memberInfo->MemberID);
					$type = 1;
					if($counts<=0){
						$type = 0;
						//$counts = $columnModel->hasNewFollowedMemberArticles($this->memberInfo->MemberID);
					}
					break;
				case 6:
					//讲堂  当有新的视频时
					$type = 0;
					$lectureModel = new Model_Lecture_Video();
					$counts = $lectureModel->hasNewVideos($this->memberInfo->MemberID);
					break;
				case 7://理财号
					$columnModel = new Model_Column_Column();
					$info = $columnModel->getColumnNews($this->memberInfo->MemberID);
					$info['pointType'] = 7;
                    break;
                case 8://钱包
                    $walletModel = new Model_Wallet_Wallet();
					$info = $walletModel->getWalletNews($this->memberInfo->MemberID);
					$info['pointType'] = 8;
                    break;
                case 9://新朋友消息
                	//新朋友是否有红点
                	$applyRelationModel = new Model_FriendApplyRelation();
                	$info = $applyRelationModel->hasNewPoints($this->memberInfo->MemberID);
                	$info['pointType'] = 9;
                    break;
                case 10://当订阅的话题产生新观点
                	$viewModel = new Model_Topic_View();
                	$type=0;
					// if(($platform==1 && version_compare($version,'2.1.4')>=0)||($platform==2 && version_compare($version,'2.1.3')>=0)){
					// 	$counts = 0;
					// 	$type = 0;
					// }else{
						$counts = $viewModel->hasNewFollowedTopicViews($this->memberInfo->MemberID);
						if($counts>0){
							$type = 1;
						}	
					//}
					break;
					
                case 11://询财
                		$counselModel = new Model_Counsel_Counsel();
                		$hasNew = $counselModel->hasNewInfo($this->memberInfo->MemberID);
                		$counts = $hasNew ? 1 : 0;
                		break;
                case 12://匿名爆料
                		$viewModel = new Model_Topic_View();
                		$hasNew = $viewModel->hasNewAnonymous($this->memberInfo->MemberID);
                		$counts = $hasNew ? 1 : 0;
                		break;
			}
			if($pointType == 3 || $pointType == 4 || $pointType ==7 || $pointType==8 || $pointType==9){
				$result = array($info);
			}else{
                $result = array(array('pointType'=>$pointType,'isShowPoint'=>$counts ,'internalPoint'=>$type));
			}
		}elseif($pointType == 'all'){
			$type = 0;
			$articleModel = new Model_Column_Article();
			$counts = $articleModel->hasNewArticles($this->memberInfo->MemberID);
			$arr1 = array('pointType'=>1,'isShowPoint'=>$counts ,'internalPoint'=>$type);
			//消息是否显示红点      当有新粉丝时
			$type = 0;
			$counts = 0;
			if(version_compare($version,'2.2.3',"<")){
				$model = new Model_MemberFollow();
				$counts = $model->hasNewFans($this->memberInfo->MemberID);
			}
			$arr2 = array('pointType'=>2,'isShowPoint'=>$counts ,'internalPoint'=>$type);
			//发现-财友圈红点       当有新说说时
// 			$shuoshuoModel = new Model_Shuoshuo();
// 			$counts = $shuoshuoModel->hasNewCaiYouQuan($this->memberInfo->MemberID);
// 			//echo $this->memberInfo->MemberID;exit;
// 			if($counts<=0){
// 				$messageModel = new Model_Message();
// 				$info = $messageModel->newViewMessageCount($this->memberInfo->MemberID,2);
// 				$counts = empty($info)? 0 : 1;
// 			}
			$avatar = '';
			$shuoshuoModel = new Model_Shuoshuo();
			$modelAccount = new DM_Model_Account_Members();
			$counts = $shuoshuoModel->hasNewCaiYouQuan($this->memberInfo->MemberID);
			if($counts<=0){
				$messageModel = new Model_Message();
				$sInfo = $messageModel->newViewMessageCount($this->memberInfo->MemberID,2);
				if(!empty($sInfo)){
					$counts = 1;
					if($sInfo['MessageType'] ==2){//评论说说
						$commentModel = new Model_ShuoComment();
						$commentInfo = $commentModel->getComments($sInfo['RelationID']);
						$avatar = !empty($commentInfo)?$modelAccount->getMemberAvatar($commentInfo[0]['CommentBy']):'';
					}else{//点赞说说
						$praiseModel = new Model_ShuoPraise();
						$praiseInfo = $praiseModel->getPraises($sInfo['RelationID']);
						$avatar = !empty($praiseInfo)? $modelAccount->getMemberAvatar($praiseInfo[0]['PraiseBy']):'';
					}
				}
			
			}else{
				//获取最新的说说ID
				$redisObj = DM_Module_Redis::getInstance();
				$key = Model_Shuoshuo::getCaiYQKey($this->memberInfo->MemberID);
				$re = $redisObj->ZREVRANGE($key,0,0);
				$shuoInfo = $shuoshuoModel->getShuos($re[0]);
				$memberID = empty($shuoInfo)? 0 : $shuoInfo[0]['MemberID'];
				$avatar = $memberID>0 ? $modelAccount->getMemberAvatar($memberID) : '';
			
			}
			
			$arr3 = array('pointType'=>3,'isShowPoint'=>$counts ,'internalPoint'=>$counts,'avatar'=>$avatar);

			$viewModel = new Model_Topic_View();
			$info =array();
			$type = 0;
			$avatar = '';
			$messageCount = 0;
			if(version_compare($version,'2.2.3',">=")){
				$counts = $viewModel->hasNewFriendViews($this->memberInfo->MemberID);
			}else{
				$counts = $viewModel->hasNewFollowedMemberViews($this->memberInfo->MemberID);
			}
			//有没有新消息已经新消息头像
			$messageModel = new Model_Message();
			$messageinfo = $messageModel->newViewMessageCount($this->memberInfo->MemberID,1);
			if(!empty($messageinfo)){
				$counts = 1;
				$type=1;
				$messageCount = $messageModel->getMessageCount($this->memberInfo->MemberID,1);
				$replyModel = new Model_Topic_Reply();
				$replyInfo = $replyModel->getReplyInfo($messageinfo['RelationID']);
				$memberID = empty($replyInfo)? 0 : $replyInfo['MemberID'];
				$avatar = $memberID>0 ? $modelAccount->getMemberAvatar($memberID) : '';
			}
			$arr4 = array('pointType'=>4,'isShowPoint'=>$counts ,'internalPoint'=>$type,'messageCount'=>$messageCount,'avatar'=>$avatar);
			
			//专栏的红点逻辑  我关注的专栏产生新内容  我关注的人产生新内容
			$columnModel = new Model_Column_Article();
			$counts = $columnModel->hasNewFollowedColumnArticles($this->memberInfo->MemberID);
			$type = 1;
			if($counts<=0){
				$type = 0;
				//$counts = $columnModel->hasNewFollowedMemberArticles($this->memberInfo->MemberID);
			}
			$arr5 = array('pointType'=>5,'isShowPoint'=>$counts ,'internalPoint'=>$type);
			//讲堂  当有新的视频时
			$type = 0;
			$lectureModel = new Model_Lecture_Video();
			$counts = $lectureModel->hasNewVideos($this->memberInfo->MemberID);
			$arr6 = array('pointType'=>6,'isShowPoint'=>$counts ,'internalPoint'=>$type);
			
			//理财号是否有红点
			$columnModel = new Model_Column_Column();
			$info = $columnModel->getColumnNews($this->memberInfo->MemberID);
			$info['pointType'] = 7;
			$arr7 = $info;
            
            //钱包是否有红点
			$walletModel = new Model_Wallet_Wallet();
			$info = $walletModel->getWalletNews($this->memberInfo->MemberID);
			$info['pointType'] = 8;
			$arr8 = $info;
			
			//新朋友是否有红点
			$applyRelationModel = new Model_FriendApplyRelation();
			$arr9 = $applyRelationModel->hasNewPoints($this->memberInfo->MemberID);
			$arr9['pointType'] = 9;

			//话题-我关注的话题下面有新观点时
         	$type=0;
			// if(($platform==1 && version_compare($version,'2.1.4')>=0)||($platform==2 && version_compare($version,'2.1.3')>=0)){
			// 	$counts = 0;
			// 	$type = 0;
			// }else{
				$counts = $viewModel->hasNewFollowedTopicViews($this->memberInfo->MemberID);
				if($counts>0){
					$type = 1;
				}	
			//}
			$arr10 = array('pointType'=>10,'isShowPoint'=>$counts ,'internalPoint'=>$type);
			
			//询财
			$counselModel = new Model_Counsel_Counsel();
			$hasNew = $counselModel->hasNewInfo($this->memberInfo->MemberID);
			$counts = $hasNew ? 1 : 0;
			$arr11 = array('pointType'=>11,'isShowPoint'=>$counts ,'internalPoint'=>0);
			
			//匿名爆料
			$viewModel = new Model_Topic_View();
			$hasNew = $viewModel->hasNewAnonymous($this->memberInfo->MemberID);
			$counts = $hasNew ? 1 : 0;
			$arr12 = array('pointType'=>12,'isShowPoint'=>$counts ,'internalPoint'=>0);
			
			$result = array($arr1,$arr2,$arr3,$arr4,$arr5,$arr6,$arr7,$arr8,$arr9,$arr10,$arr11,$arr12);
		}
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
	}
	
	/**
	 * 检查是否有新消息
	 */
	public function hasNewMessageAction()
	{
		try{
			$messageModel = new Model_Message();
			$hasNew = $messageModel->hasMessage($this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'',array('HasNewMessage'=>$hasNew));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 消息列表
	 */
	public function messageListAction()
	{
		try{
			
			$memberID = $this->memberInfo->MemberID;
			$pagesize = intval($this->_request->getParam('pagesize',10));
			$lastMessageID = intval($this->_request->getParam('lastMessageID',0));
			$MessageSign = intval($this->_request->getParam('messageSign',0));
			$messageModel = new Model_Message();
			$messageList = $messageModel->getList($memberID,$lastMessageID,$MessageSign,$pagesize);
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$messageList,'TimeStamp'=>time()));
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 清除消息
	 */
	public function clearAction()
	{
		try{
			$messageID = trim($this->_request->getParam('messageID',''));
			if(empty($messageID)){
				throw new Exception('请选择要删除的消息！');
			}
			$messageModel = new Model_Message();
			$where = array();
			
			$where['MemberID = ?'] = $this->memberInfo->MemberID;
			
			if($messageID != -1){
				$messageID = explode(',', $messageID);
				$where['MessageID in (?)'] = $messageID;
			}else{
				$messageSign = intval($this->_getParam('messageSign',0));
				if($messageSign>0){
					$where['MessageSign = ?'] = $messageSign;
				}
			}
			$messageModel->delete($where);
			$this->returnJson(parent::STATUS_OK,'');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 设置消息助手
	 */
	public function setHelperAction()
	{
		try{
			//类型 1好友，2群
			$objectType = intval($this->_getParam('objectType',1));
			if(!in_array($objectType, array(1,2))){
				throw  new Exception('类型参数值错误！');
			}
			$objectID = $this->_getParam('objectID','');
			if(empty($objectID)){
				throw new Exception('群组ID或好友ID不能为空！');
			}
			$memberID = $this->memberInfo->MemberID;
			//1开启 0关闭
			$status = intval($this->_getParam('status',1));
			if(!in_array($status, array(0,1))){
				throw  new Exception('状态参数值错误！');
			}
			$model = new Model_MessageHelper();
			$model->setHelper($objectType,$objectID,$memberID,$status);
			$this->returnJson(parent::STATUS_OK,'设置成功');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
	
	/**
	 * 获取消息数量
	 */
	public function getMessageCountAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$messageSign = intval($this->_request->getParam('messageSign',0));
			$messageModel = new Model_Message();
			$count = $messageModel->getCount($memberID,$messageSign);
			$this->returnJson(parent::STATUS_OK,'',array('MessageCount'=>$count));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
}