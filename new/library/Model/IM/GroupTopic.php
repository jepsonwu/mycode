<?php
/**
 *  群组相关话题
 * @author Mark
 *
 */
class Model_IM_GroupTopic extends Zend_Db_Table
{
	protected $_name = 'group_topics';
	protected $_primary = 'ID';
	
	/**
	 * 获取指定AID、topicID 的信息
	 * @param int $AID
	 * @param int $topicID
	 */
	public function getInfo($AID,$topicID = null)
	{
		$select = $this->select();
		$select->where('AID = ?',$AID);
		if(!is_null($topicID)){
			$select->where('TopicID = ?',$topicID);
		}
		$info = $select->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 *  获取话题绑定信息 
	 * @param int $AID
	 */
	public function getBindInfo($AID)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from(array('gt'=>$this->_name),null)->joinInner(array('t'=>"topics"), "t.TopicID = gt.TopicID",array('TopicID','TopicName'));
		$info = $select->where('gt.AID = ?',$AID)->query()->fetchAll();
		return $info ? $info : array();
	}
	
	/**
	 * 添加群组关联话题
	 * @param int $AID
	 * @param int $topicID
	 */
	public function addInfo($AID,$topicID)
	{
		$info = $this->getInfo($AID, $topicID);
		if(empty($info)){
			$data = array('AID'=>$AID,'TopicID'=>$topicID);
			$newID = $this->insert($data);
		}else{
			$newID = $info['ID'];
		}
		return $newID;
	}
	
	/**
	 * 解绑
	 * @param int $AID
	 * @param int $topicID
	 */
	public function unbind($AID,$topicID)
	{
		return $this->delete(array('AID = ?'=>$AID,'TopicID = ?'=>$topicID));
	}
}