<?php
/**
 * 频道管理
 * @author Kitty
 *
 */
class Api_ChannelController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 我的频道
	 */
	public function myChannelsAction()
	{	
		try{
			$memberID = $this->memberInfo->MemberID;
			$memberChannelModel  = new Model_MemberChannel();
			$results = $memberChannelModel->getChannelInfo($memberID, null, 'ChannelID');
			$mark = $memberChannelModel->getCleanMark($memberID);
			if(empty($results) && $mark == 0){
				$channelIDArr = array(1,2);
				foreach ($channelIDArr as $key => $channelID) {
					$memberChannelModel->addChannel($memberID,$channelID,$key);
				}				
				$results = $memberChannelModel->getChannelInfo($memberID, null, 'ChannelID');
			}

			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}	
	}

	/**
	 * 频道列表
	 */
	public function channelListAction()
	{

		$channelModel = new Model_Channel();
		$select = $channelModel->select();
		$fieldsArr = array('ChannelID','ChannelName');
		$result = $select->from('channel',$fieldsArr)->order('Sort desc')->query()->fetchAll();
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
	}
	
	/**
	 * 设置我的频道
	 */
	public function setChannelAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$channelID = $this->_request->getParam('channelID','');

			$memberChannelModel  = new Model_MemberChannel();
			if(empty($channelID)){
				//移除我的频道
		    	$memberChannelModel->removeChannel($memberID);
		    	//更改清空标识
		    	$memberChannelModel->setCleanMark($memberID,1);			
			}else{
				$channelIDArr = array_filter(explode(',',$channelID));
				$channelInfo = $memberChannelModel->getChannelInfo($memberID,null,'ChannelID');
		    	if(count($channelIDArr) >0){
		    		if(count($channelInfo)>0){
						foreach ($channelInfo as $item) {
				    		if(!in_array($item['ChannelID'], $channelIDArr)){
				    			$memberChannelModel->removeChannel($memberID,$item['ChannelID']);
				    		}
				    	}
				    }
			    	foreach ($channelIDArr as $key => $channelID) {
			    		$memberChannelModel->addChannel($memberID,$channelID,$key);
			    	}
		    	}
		    }
	    	
			$this->returnJson(parent::STATUS_OK,'频道更新成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 频道下的话题列表
	 */
	public function channelTopicListAction()
	{
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('pagesize', 20);
		$channelID = intval($this->_request->getParam('channelID',0));
		if($channelID <= 0){
			$this->returnJson(parent::STATUS_FAILURE,'频道ID不能为空！');
		}
        $channelFocusModel = new Model_ChannelFocus();
        $focusID = $channelFocusModel->getChannelFocusID($channelID);
        $result = array();
        $total = 0;
        if(count($focusID) >0 ){
        	$topicModel = new Model_Topic_Topic();
			$select = $topicModel->select()->setIntegrityCheck(false);
			$select->from('topics as t',array('TopicID'=>new Zend_Db_Expr("DISTINCT(t.TopicID)"),'TopicName','FollowNum','ViewNum','BackImage'))
						->where('t.CheckStatus = ?',1)->where('t.IsAnonymous = 0');
			$select->joinLeft('topic_focus as tf','t.TopicID = tf.TopicID','')->where('tf.FocusID in (?)',$focusID);

			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(DISTINCT t.TopicID) AS total FROM', $countSql);

			//总条数
			$total = $topicModel->getAdapter()->fetchOne($countSql);	
			
		    $select->order('t.Liveness desc')->limitPage($pageIndex, $pageSize);
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				foreach ($result as &$val){
					$val['IsFollowed'] = $topicModel->isFollowedTopic($this->memberInfo->MemberID, $val['TopicID']);
				}
			}

        }

        $this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$result));
	}
	
	/**
	 * 频道下的专栏列表
	 */
	public function channelColumnListAction()
	{
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('pagesize', 30);
		$channelID = intval($this->_request->getParam('channelID',0));
		if($channelID <= 0){
			$this->returnJson(parent::STATUS_FAILURE,'频道ID不能为空！');
		}
		$channelFocusModel = new Model_ChannelFocus();
		$focusID = $channelFocusModel->getChannelFocusID($channelID);
		$result = array();
		$total = 0;
		if(count($focusID) >0 ){
			$columnModel = new Model_Column_Column();
			$select = $columnModel->select()->setIntegrityCheck(false);
			$select->from('column as t',array('ColumnID'=>new Zend_Db_Expr("DISTINCT(t.ColumnID)"),'Title','Avatar','SubscribeNum','ArticleNum'))->where('t.CheckStatus = ?',1);
			$select->joinLeft('column_focus as tf','t.ColumnID = tf.ColumnID','')->where('tf.FocusID in (?)',$focusID);
	
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(DISTINCT t.ColumnID) AS total FROM', $countSql);
	
			//总条数
			$total = $columnModel->getAdapter()->fetchOne($countSql);
	
			$select->order('t.ColumnID desc')->limitPage($pageIndex, $pageSize);
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				foreach ($result as &$val){
					$val['IsSubscribe'] = $columnModel->isSubscribeColumn($this->memberInfo->MemberID, $val['ColumnID']);
				}
			}
		}
		$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$result));
	}

}