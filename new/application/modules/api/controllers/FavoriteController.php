<?php
/**
 * 收藏相关
 * @author Jeff
 *
 */
class Api_FavoriteController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 添加收藏
	 */
	public function addFavoriteAction()
	{
		try{
			$type = intval($this->_getParam('type',0));
			$relationID = intval($this->_getParam('relationID',0));
			if(!in_array($type, array(1,2,3))){
				throw new Exception('类型参数错误！');
			}
			if($relationID<1){
				throw new Exception('参数错误！');
			}
			if($type == 1){
				$articleModel = new Model_Column_Article();
				$articleInfo = $articleModel->getArticleInfo($relationID);
				if(empty($articleInfo) || $articleInfo['Status'] != 1){
					throw new Exception('抱歉，文章已被删除！');
				}
			}elseif($type == 2){
				$viewModel = new Model_Topic_View();
				$viewInfo = $viewModel->getViewInfo($relationID);
				if(empty($viewInfo) || $viewInfo['CheckStatus'] != 1){
					throw new Exception('抱歉，观点已被删除！');
				}
			}elseif($type == 3){
				$lessonModel = new Model_Lesson();
				$lessonInfo = $lessonModel->getInfo($relationID);
				if(empty($lessonInfo)){
					throw new Exception('抱歉，课程不存在！');
				}
			}else{
				throw new Exception('参数错误');
			}
			$model = new Model_Favorite();
			$model->addFavorite($type,$relationID,$this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'收藏成功');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除收藏
	 */
	public function deleteFavoriteAction()
	{
		try{
			$favoriteID = intval($this->_getParam('favoriteID',0));
			if($favoriteID < 1){
				throw new Exception('参数错误！');
			}
			$model = new Model_Favorite();
			$model->delete(array('FavoriteID = ?'=>$favoriteID));
			$this->returnJson(parent::STATUS_OK,'收藏已取消');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 我的收藏列表
	 */
	public function myFavoriteListAction()
	{
		try{
			$type = intval($this->_getParam('type',0));
			$page = intval($this->_getParam('page',1));
			$pagesize = intval($this->_getParam('pagesize',10));
			$memberID = $this->memberInfo->MemberID;
			$model = new Model_Favorite();
			
			//按收藏时间倒叙（多次收藏收藏时间会更新）
			$select = $model->select()->from('favorite',array('FavoriteID','Type','RelationID'))->where('MemberID = ?',$memberID);
			if($type > 0){
				$select->where('Type = ?',$type);
			}
			
			$countSql = $select->__toString();
			//echo $countSql;exit;
			
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			//总条数
			$total = $model->getAdapter()->fetchOne($countSql);
			$result = $select->order('CreateTime desc')->limitPage($page,$pagesize)->query()->fetchAll();
			if(!empty($result)){
				$articleModel = new Model_Column_Article();
				$columnModel = new Model_Column_Column();
				$viewModel = new Model_Topic_View();
				$topicModel = new Model_Topic_Topic();
				$memberModel = new DM_Model_Account_Members();
				$viewImageModel = new Model_Topic_ViewImage();
				$memberNoteModel = new Model_MemberNotes();
				$lessonModel = new Model_Lesson();
				foreach($result as &$val)
				{
					if($val['Type'] == 1){//文章
						$info = $articleModel->getArticleInfo($val['RelationID']);
						if(!empty($info)){
							$val['Title'] = $info['Title'];
							$val['MemberID'] = $info['MemberID'];
							$val['Cover'] = $info['Cover'];
							$val['PublishTime'] = $info['PublishTime'];
							$val['ColumnTitle'] = $columnModel->getColumnInfoCache($info['ColumnID'],'Title');
						}
					}elseif($val['Type'] == 2){//观点
						$info = $viewModel->getViewInfo($val['RelationID']);
						if(!empty($info)){
							$val['TopicID'] = $info['TopicID'];
							$val['MemberID'] = $info['IsAnonymous']? 0 :$info['MemberID'];
							$val['ViewContent'] = $info['ViewContent'];
							$val['PraiseNum'] = $info['PraiseNum'];
							$val['ReplyNum'] = $info['ReplyNum'];
							$val['CreateTime'] = $info['CreateTime'];
							$topicInfo = $topicModel->getTopicInfo($info['TopicID'],null);
							$val['TopicName'] = $topicInfo['TopicName'];
							$val['Avatar'] = $info['IsAnonymous'] ? $info['AnonymousAvatar'] : $memberModel->getMemberAvatar($info['MemberID']);
							$val['NoteName'] = $info['IsAnonymous'] ? '' : $memberNoteModel->getNoteName($memberID, $val['MemberID']);
							$val['UserName'] = $info['IsAnonymous']? $info['AnonymousUserName'] : $memberModel->getMemberInfoCache($info['MemberID'],'UserName');
							$val['Images'] = $viewImageModel->getImages($info['ViewID']);
						}
					}elseif($val['Type'] == 3){
						$info = $lessonModel->getInfo($val['RelationID']);
						if(!empty($info)){
							$val['LessonID'] = $info['LessonID'];
							$val['ModuleID'] = $info['ModuleID'];
							$val['LessonTitle'] = $info['LessonTitle'];
							$val['ModuleName'] = $info['ModuleName'];
							$val['ViewCount'] = $info['ViewCount']*2;
							$val['LessonPic'] = $info['LessonPic'];
						}
					}
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Totla'=>$total,'Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
}