<?php
/**
 * 说说的评论
 * 
 * @author johnny 2015-07-08 
 */

class Model_ShuoComment extends Zend_Db_Table {
    protected $_name = 'shuo_comments';
    protected $_primary = array(1 => 'CommentID');

    /**
     * 获取说说的评论列表
     * 
     * @param int $ | string | array | select obj $where
     * @param string $orderBy 
     * @param int $limit 
     * @param int $offset 
     * @return array | null
     */

    public function getComments($where = null, $orderBy = null, $limit = null, $offset = null) {
        if (!$where) {
            return null;
        } 
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where . ' AND Status = 1';
        } 
        if (!$orderBy) {
            $orderBy = $this->_primary[1] . ' DESC';
        } 
        $data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
        return empty($data) ? null : $data;
    } 
    /**
     * 获取评论总数
     * 
     * @param string $ | array | select_object $where [description]
     * @return int [description]
     */
    public function getCommentsCount($where = true) {
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where . ' AND Status = 1';
        } 
        $select = $this->select();

        $select->from($this->_name, array('Total' => 'COUNT(*)'));
        $this->_where($select, $where);
        $result = $select->query()->fetch();
        return isset($result['Total']) ? intval($result['Total']) : 0;
    } 

    /**
     * 添加说说评论
     * 自己可以对自己的说说进行评论
     * 
     * @param int $shuoID 　@param string $commentTxt
     * @param int $commentBy 
     * @return the comment ID if success or false if failed
     */

    /*public function addComment($shuoID = null, $commentTxt = '', $commentBy = null, $At = null) {
        if (! $shuoID = (int) $shuoID) {
            throw new Exception('说说ID不能为空');
        } 
        $modelShuoshuo = new Model_Shuoshuo();
        if (($shuoData = $modelShuoshuo->getShuos($shuoID)) == null) {
            throw new Exception('该说说不存在');
        } 
        if (trim($commentTxt) == '') {
            throw new Exception('评论内容不能为空');
        } 
        if (!$commentBy = (int) $commentBy) {
            throw new Exception('评论者ID不能为空');
        } 
        $modelAccount = new DM_Model_Account_Members();
        if ($modelAccount->getById($commentBy) == null) {
            throw new Exception('不存在该评论会员');
        } 
        $modelMemberFollow = new Model_MemberFollow();
        $relation = $modelMemberFollow->getRelation($shuoData[0]['MemberID'], $commentBy);
        if ($relation != 3 && $relation != -1) {
            throw new Exception('不是好友关系不能评论',-108);
        } 
        if (!$At = (int) $At) {
            $At = $commentBy;
        } 
        $relationAt = $modelMemberFollow->getRelation($At, $commentBy);
        if ($relationAt != 3 && $relationAt != -1) {
            throw new EXception('不是好友关系不能回复',-108);
        } 
        if ($commentID = $this->insert(array('ShuoID' => $shuoID, 'CommentTxt' => $commentTxt, 'CommentBy' => $commentBy, 'At' => $At, 'Status' => 1))) {
            $redis = DM_Module_Redis :: getInstance();
            $redis->ZADD('ShuoComment:ShuoID:' . $shuoID, $commentBy, $commentID);
            $redis->SET('ShuoCommentDetail:' . $commentID, json_encode(array('Txt' => $commentTxt, 'Time' => time(), 'By' => $commentBy, 'At' => $At)));
            $redis->EXPIRE('ShuoCommentDetail:' . $commentID, 33 * 24 * 60 * 60); 
            // 还要将说说的评论数++
            $modelShuoshuo->update(array('CommentCount' => ++$shuoData[0]['CommentCount']), 'ShuoID=' . $shuoID); 
            // 加入对说说评论的消息
            $messageModel = new Model_Message();
            if ($shuoData[0]['MemberID'] != $commentBy) {
                $messageModel->addMessage($shuoData[0]['MemberID'], 2, $commentID, 2);
            } 
            if ($At > 0 && $At != $commentBy && $At != $shuoData[0]['MemberID']) {
                $messageModel->addMessage($At, 2, $commentID, 2);
            } 
            return $commentID;
        } else {
            return false;
        } 
    } */
    
    public function newAddComment($shuoID, $commentTxt, $memberID, $At,$ownerID)
    {
    	$data = array('ShuoID' => $shuoID, 'CommentTxt' => $commentTxt, 'CommentBy' => $memberID, 'At' => $At, 'Status' => 1);
    	$commentID = $this->insert($data);
    	if ($commentID){
    		$redis = DM_Module_Redis :: getInstance();
    		$redis->ZADD('ShuoComment:ShuoID:' . $shuoID, $memberID, $commentID);
    		$redis->hmset('NewShuoCommentDetail:' . $commentID,array('Txt' => $commentTxt, 'Time' => time(), 'By' => $memberID, 'At' => $At));
    		$redis->EXPIRE('NewShuoCommentDetail:' . $commentID, 30 * 86400);
    		// 还要将说说的评论数++
    		$modelShuoshuo = new Model_Shuoshuo();
    		$modelShuoshuo->update(array('CommentCount'=>new Zend_Db_Expr("CommentCount + 1")),array('ShuoID = ?'=>$shuoID));
    		// 加入对说说评论的消息
    		$messageModel = new Model_Message();
    		if ($ownerID != $memberID) {
    			$messageModel->addMessage($ownerID, 2, $commentID, 2);
    		}
    		if ($At > 0 && $At != $memberID && $At != $ownerID) {
    			$messageModel->addMessage($At, 2, $commentID, 2);
    		}
    		return $commentID;
    	} else {
    		return false;
    	}
    }

    /**
     * 标记说说评论为删除状态
     * 
     * @param int $ | string | array | select obj $where
     * @return boolean 
     */

    public function unComment($where) {
        if (!$where) {
            return false;
        } 
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where;
        } 
        if ($this->update(array('Status' => 0), $where)) {
            return true;
        } else {
            return false;
        } 
    } 

    /**
     * 删除说说评论
     * 
     * @param int $ | string | array | select obj $where
     * @return boolean 
     */
   /* public function delComment($where = null) {
        if (!$where) {
            return false;
        } 
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where;
        } 
        // 还要将说说的评论数--
        $modelShuoshuo = new Model_Shuoshuo();
        $comments = $this->getComments($where);
        if ($comments) {
            $tmp = array();
            foreach ($comments as $comment) {
                $tmp[$comment['CommentID']] = $comment['ShuoID'];
            } 
            $comments = $tmp;
            unset($tmp); 
            // 说说评论数--
            if ($modelShuoshuo->update(array('CommentCount' => new Zend_Db_Expr('CommentCount - 1')), 'ShuoID IN (' . implode(',', array_unique(array_values($comments))) . ') AND CommentCount > 0')) {
                if ($this->unComment($this->_primary[1] . ' IN (' . implode(',', array_keys($comments)) . ')')) {
                    $redis = DM_Module_Redis :: getInstance();
                    $pipe = $redis->pipeline();
                    foreach ($comments as $commentID => $shuoID) {
                        $pipe->ZREM('ShuoComment:ShuoID:' . $shuoID, $commentID);
                        $pipe->DEL('ShuoCommentDetail:' . $commentID);
                    } 
                    $pipe->exec();
                    return true;
                } else {
                    return false;
                } 
            } else {
                return false;
            } 
        } else {
            throw new Exception('评论不存在');
        } 
    } */
    
    /**
     * 删除评论
     * @param unknown $commentID
     * @param unknown $shuoID
     * @return boolean
     */
    public function newDelComment($commentID,$shuoID){
    	$re = $this->update(array('Status'=>0), array('CommentID = ?'=>$commentID));
    	if($re){
    		$modelShuoshuo = new Model_Shuoshuo();
    		$modelShuoshuo->update(array('CommentCount' => new Zend_Db_Expr('CommentCount - 1')),array('ShuoID = ?'=>$shuoID));
    		$redis = DM_Module_Redis :: getInstance();
    		//删除缓存信息
    		$redis->ZREM('ShuoComment:ShuoID:' . $shuoID, $commentID);
    		$redis->DEL('NewShuoCommentDetail:' . $commentID);
    	}
    	return true;
    }

    public function getCommentInfo($CommentID) {
        if (!$CommentID) {
            throw new Exception('评论ID不能为空');
        } 
        return $this->select()->from($this->_name)->where('CommentID = ?', $CommentID)->query()->fetch();
    }
    
    /**
     * 获取某个说说的评论
     * @param unknown $shuoID
     * @param unknown $lastID
     * @param unknown $pagesize
     */
    public function getCommentList($shuoID,$lastID,$pagesize = null)
    {
    	$select = $this->select()->from($this->_name,'*')->where('ShuoID = ?',$shuoID)->where('Status = ?',1);
    	if($lastID>0){
    		$select->where('CommentID > ?',$lastID);
    	}
    	if($pagesize>0){
    		$info = $select->order('CommentID ASC')->limit($pagesize)->query()->fetchAll();
    	}else{
    		$info = $select->order('CommentID ASC')->query()->fetchAll();
    	}
    	return empty($info)?array():$info;
    }
} 
