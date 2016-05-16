<?php
/**
 *  话题关注
 *  
 * @author Kitty
 *
 */
class Model_Topic_Focus extends Zend_Db_Table
{
	protected $_name = 'topic_focus';
	protected $_primary = 'TopicFocusID';
	
	public function addFocus($topicID,$focusID)
	{
		$hasExists = $this->getInfo($topicID,$focusID);
		if(empty($hasExists)){
			$data = array('TopicID'=>$topicID,'FocusID'=>$focusID);
			$newFocusID = $this->insert($data);		
		}
		return true;
	}
	
	/**
	 *  移除话题标签
	 * @param int $channelID
	 */
	public function removeFocus($topicID,$focusID)
	{
        if( !$focusID ) {
            throw new Exception('标签ID不能为空');
        }
        $focusInfo = $this->getInfo($topicID,$focusID);
        if(!empty($focusInfo)){
			$this->delete(array('FocusID = ?' => $focusID,'TopicID = ?'=>$topicID));
        }
        return true; 
	}

	/**
	 *  获取信息
	 * @param int $topicID
	 * @param int $focusID
	 */
	public function getInfo($topicID,$focusID=null)
	{
		$select = $this->select();
		$select->from($this->_name)->where('TopicID = ?',$topicID);
		if(!is_null($focusID)){
			$select->where('FocusID = ?',$focusID);
			return $select->query()->fetch();
		}
		return $select->query()->fetchAll();
	}
}