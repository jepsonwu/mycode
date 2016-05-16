<?php

/**
 * 对说说评论的回复
 * 已经取消使用
 */

 class Model_ShuoCommentReply extends Zend_Db_Table {
 	protected $_name = 'shuo_comment_replies';
 	protected $_primary = array( 1 => 'ReplyID' );

	/**
	 * @return array() | null
	 */
 	public function getReplies($where = null, $orderBy = null, $limit = null, $offset = null) {
 		if( is_numeric($where) ) {
 			$where = $this->_primary[1] . '=' . $where;
 		}
 		if( !$orderBy ) {
 			$orderBy = $this->_primary[1] . ' DESC';
 		}
 		$data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
 		return empty($data) ? null : $data;
 	}

 	/**
 	 * 获取财友圈中说说评论的回复
 	 * @return array('CommentID'=>array('ReplyID'=>array('Txt', 'By', 'At', 'Time'), 'ReplyID'=>array('Txt', 'By', 'At', 'Time')))
 	 */
 	public function getCaiYouQuanReplies($commentIDArray = array(), $limit = 5) {
 		if( empty($commentIDArray) ) {
 			return array();
 		}
 		$commentIDArray = array_unique($commentIDArray);
 		$select = $this->select();
 		$i = 0;
 		foreach ($commentIDArray as $commentID) {
 			$selectAnother[$i] = $this->select()->setIntegrityCheck(false);
 			if( $limit ) {
 				$selectAnother[$i]->from($this->_name, array('*', 'Total'=>'count(*)'))->where('CommentID='.$commentID)->having('Total > ?', 0)->limit($limit);
 			} else {
 				$selectAnother[$i]->from($this->_name, array('*', 'Total'=>'count(*)'))->where('CommentID='.$commentID)->having('Total > ?', 0);
 			}
 			$selectAnother[$i] = '('.$selectAnother[$i].')';
 			$i++;
 		}
 		$select->union($selectAnother)->order($this->_primary[1].' DESC');
 		// echo $select->__toString();
 		unset($i, $selectAnother);
 		$tmp = array();
 		if( $result = $select->query()->fetchAll() ) {
	 		foreach ($result as $key => $value) {
	 			$tmp[$value['CommentID']][$value['ReplyID']] = array('Txt'=>$value['ReplyTxt'], 'By'=>$value['ReplyBy'], 'At'=>$value['At'], 'Time'=>$value['CreateTime'], 'Total'=> (int) $value['Total']);
	 		}
	 		unset($result);
 		}
 		return $tmp;
 	}

 	public function addReply($commentID = null, $memberID = null, $replyTxt = '') {
 		if( !$commentID = (int) $commentID ) {
 			throw new Exception('说说评论ID不能为空');
 		}
 		$model = new Model_ShuoComment();
 		if( ($comment = $model->getComments($commentID)) == null ) {
 			throw new Exception('该条说说评论不存在');
 		} else {
 			$At = $comment[0]['CommentBy'];
 		}
 		if( !$memberID = (int) $memberID ) {
 			throw new Exception('会员ID不能为空');
 		}
 		$modelAccount = new DM_Model_Account_Members();
		if( $modelAccount->getById($memberID) == null ) {
			throw new Exception('不存在该会员');
		}
 		if( !$replyTxt = trim($replyTxt) ) {
 			throw new Exception('回复内容不能为空');
 		}
 		$modelMemberFollow = new Model_MemberFollow();
 		$relation = $modelMemberFollow->getRelation($comment[0]['CommentBy'], $memberID);
		if( $relation != 3 && $relation != -1 ) {
			throw new Exception("不是好友关系不能回复评论");
		}
 		if( $this->insert(array('CommentID'=>$commentID, 'ReplyTxt'=>$replyTxt, 'At'=>$At, 'ReplyBy'=>$memberID)) ) {
 			return true;
 		} else {
 			return false;
 		}
 	}

 	public function delReply($where = null) {
 		if( !$where ) {
 			return false;
 		}
 		if( is_numeric($where) ) {
 			$where = $this->_primary[1] . '=' . $where;
 		}
 		if( $this->getReplies($where) == null ) {
 			throw new Exception('回复不存在');
 		}
 		if( $this->delete($where) ) {
 			return true;
 		} else {
 			return false;
 		}
 	}
 }