<?php
/**
 *  讲堂相关接口
 * @author Jeff
 *
 */
class Api_LectureController extends Action_Api{
	
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 评论讲堂视频
	 */
	public function lectureCommentAction()
	{
		try{
			$videoID = intval($this->_getParam('videoID',0));
			$memberID = $this->memberInfo->MemberID;
			$content = trim($this->_getParam('content',''));
			if($videoID<1 || empty($content)){
				$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
			}
			$commentModel = new Model_Lecture_Comment();
			$commentModel->addComment($memberID,$videoID,$content);
			$memberModel = new DM_Model_Account_Members();
			$avater = $memberModel->getMemberAvatar($memberID);
			$data['Avater'] = empty($avater)?'http://img.caizhu.com/default.png':$avater;
			$data['UserName'] = $memberModel->getMemberInfoCache($memberID,'UserName');
			$data['CreateTime'] = '刚刚';
			$this->returnJson(parent::STATUS_OK,'评论成功',$data);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	
	/**
	 * 对视频点赞
	 */
	public function praiseAction()
	{
		try{
			$videoID = intval($this->_getParam('videoID',0));
			if($videoID<1){
				throw new Exception('参数错误!');
			}
			$videoModel = new Model_Lecture_Video();
			$videoModel->addPraise($videoID, $this->memberInfo->MemberID);
			$praiseNum = $videoModel->getPraisedNum($videoID);
			$this->returnJson(parent::STATUS_OK,'已赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
				
		}
	}
	
	/**
	 * 取消视频章点赞
	 */
	public function unPraiseAction()
	{
		try{
			$videoID = intval($this->_getParam('videoID',0));
			if($videoID<1){
				throw new Exception('参数错误!');
			}
			$videoIDModel = new Model_Lecture_Video();
			$videoIDModel->unPraise($videoID, $this->memberInfo->MemberID);
			$praiseNum = $videoIDModel->getPraisedNum($videoID);
			$this->returnJson(parent::STATUS_OK,'已取消赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
	
		}
	}
	
}
