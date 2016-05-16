<?php
/**
 * 文章评论相关
 * @author Jeff
 *
 */
class Api_ArticleCommentController extends Action_Api
{
	public function init()
	{
		parent::init();
		$actionArr = array('get-article-comments');
		if(!in_array($this->_getParam('action'),$actionArr)){
			$this->isLoginOutput();
		}
	}
	
	/**
	 * 添加评论
	 */
	public function addCommentAction()
	{
		try{
			$articleID = intval($this->_getParam('articleID',0));
			$CommentContent = DM_Module_XssFilter::filter(trim($this->_getParam('commentContent','')));
			if($articleID<1){
				throw new Exception('文章ID错误!');
			}
			if(empty($CommentContent)){
				throw new Exception('评论内容不能为空！');
			}
			if(mb_strlen($CommentContent,'UTF-8') > 200){
				throw new Exception('评论内容不能超过200个字符！');
			}
			$CommentModel = new Model_Column_ArticleComment();
			$data = array(
				'CommentContent'=>$CommentContent,
				'ArticleID'=>$articleID,
				'MemberID'=>$this->memberInfo->MemberID,
			);
			$insertID = $CommentModel->insert($data);
			if(!$insertID){
				throw new Exception('失败！');
			}
			$articleModel = new Model_Column_Article();
			$articleModel->increaseCommentNum($articleID);
			$this->returnJson(parent::STATUS_OK,'评论成功！');
		}catch (Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取当前用户在某一文章下的评论
	 */
	public function getMyCommentsAction()
	{
		try{
			$articleID = intval($this->_getParam('articleID',0));
			$lastID = intval($this->_getParam('lastID',0));
			$pagesize = intval($this->_getParam('pagesize',5));
			if($articleID<1){
				throw new Exception('文章ID错误!');
			}
			$CommentModel = new Model_Column_ArticleComment();
			$result = $CommentModel->getCommentList($this->memberInfo->MemberID,$articleID,$lastID,$pagesize);
			$this->returnJson(parent::STATUS_OK,'',array('Row'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 删除评论
	 */
	public function updateCommentStatusAction()
	{
		try{
			$CommentID = intval($this->_getParam('commentID',0));
			$type = intval($this->_getParam('type',0));// 1 通过，2拒绝，3删除
			if($CommentID<1){
				throw new Exception('参数错误！');
			}
			if(!in_array($type, array(1,2,3))){
				throw new Exception('类型参数错误！');
			}
			$CommentModel = new Model_Column_ArticleComment();
			$info = $CommentModel->getCommentInfo($CommentID);
			if(empty($info)){
				throw new Exception('该评论已删除！');
			}
			if($type == 3 && $info['MemberID'] == $this->memberInfo->MemberID){
				$CommentModel->delete(array('CommentID = ?'=>$CommentID));
			}else{
				$type = $type == 3 ? 0 : $type;
				$CommentModel->update(array('Status'=>$type), array('CommentID = ?'=>$CommentID));
			}
			if($type == 3){
				$articleModel = new Model_Column_Article();
			$articleModel->increaseCommentNum($info['ArticleID'],-1);
			}
			$this->returnJson(parent::STATUS_OK,'删除成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取某一文章下的精选评论（用于文章详情页面的展示）
	 */
	public function getArticleCommentsAction()
	{
		try{
			$articleID = intval($this->_getParam('articleID',0));
			$page = intval($this->_getParam('page',1));
			$pagesize = intval($this->_getParam('pagesize',10));
			if($articleID<1){
				throw new Exception('文章ID错误!');
			}
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			$CommentModel = new Model_Column_ArticleComment();
			$fileds = array('CommentID','CommentContent','MemberID','PraiseNum','CreateTime');
			$select = $CommentModel->select()->from('column_article_comment',$fileds)->where('ArticleID = ?',$articleID)
			->where('Status = ?',1)->where('RelationCommentID = ?',0);
			
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
			//总条数
			$total = $CommentModel->getAdapter()->fetchOne($countSql);
			
			$select->order('PraiseNum desc')->order('CommentID desc')->limitPage($page, $pagesize);
			$results = $select->query()->fetchAll();
			if(!empty($results)){
				$memberModel = new DM_Model_Account_Members();
				$memberNoteModel = new Model_MemberNotes();
				foreach ($results as &$val){
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$val['NoteName'] = $memberID?$memberNoteModel->getNoteName($memberID, $val['MemberID']):'';
					$val['Avatar'] = $memberModel->getMemberAvatar($val['MemberID']);
					$val['CreateTime'] = Model_Topic_View::changeDateStyle($val['CreateTime']);
					$val['ReplyList'] = $CommentModel->getAuthorReply($val['CommentID']);
					$val['IsPraise'] = $memberID?$CommentModel->isPraised($val['CommentID'], $memberID):0;
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 回复某条评论
	 */
	public function replyCommentAction()
	{
		try{
			$commentID = intval($this->_getParam('commentID',0));
			$articleID = intval($this->_getParam('articleID',0));
			if($commentID<1 || $articleID<1){
				throw new Exception('参数错误！');
			}
			$replyContent = DM_Module_XssFilter::filter(trim($this->_getParam('replyContent','')));
			if(empty($replyContent) || mb_strlen($replyContent,'UTF-8') > 500){
				throw new Exception('回复内容只能是1-500个字符！');
			}
			
			$CommentModel = new Model_Column_ArticleComment();
			$info = $CommentModel->getCommentInfo($commentID);
			if(empty($info)){
				throw new Exception('该评论已删除！');
			}
			$data = array(
					'CommentContent'=>$replyContent,
					'ArticleID'=>$articleID,
					'MemberID'=>$this->memberInfo->MemberID,
					'RelationCommentID'=>$commentID,
					'Status' => 1
			);
			$insertID = $CommentModel->insert($data);
			if(!$insertID){
				throw new Exception('失败！');
			}
			$this->returnJson(parent::STATUS_OK,'成功！');
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 对文章评论点赞
	 */
	public function praiseAction()
	{
		try{
			$commentID = intval($this->_getParam('commentID',0));
			if($commentID<1){
				throw new Exception('参数错误!');
			}
			$CommentModel = new Model_Column_ArticleComment();
			$info = $CommentModel->getCommentInfo($commentID);
			if(empty($info)){
				throw new Exception('该评论已删除！');
			}
			$articleModel = new Model_Column_ArticleComment();
			$articleModel->addPraise($commentID, $this->memberInfo->MemberID);
			$praiseNum = $articleModel->getPraisedNum($commentID);
			$this->returnJson(parent::STATUS_OK,'已赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消对文章评论点赞
	 */
	public function unPraiseAction()
	{
		try{
			$commentID = intval($this->_getParam('commentID',0));
			if($commentID<1){
				throw new Exception('参数错误!');
			}
			$CommentModel = new Model_Column_ArticleComment();
			$info = $CommentModel->getCommentInfo($commentID);
			if(empty($info)){
				throw new Exception('该评论已删除！');
			}
			$articleModel = new Model_Column_ArticleComment();
			$articleModel->unPraise($commentID, $this->memberInfo->MemberID);
			$praiseNum = $articleModel->getPraisedNum($commentID);
			$this->returnJson(parent::STATUS_OK,'已取消赞！',array('PraiseNum'=>$praiseNum));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
}