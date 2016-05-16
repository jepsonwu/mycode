<?php
/**
 * 会员反馈
 * 
 * @author Kitty
 * @since 2014/09/03
 */
class Model_Feedback extends Zend_Db_Table
{
	protected $_name = 'feedback';
	protected $_primary = 'FeedBackID';
    
    /**
     * 获取回复内容
     * @param int $FeedBackID
     */
    public function getInfo($FeedBackID)
    {
        $select = $this->select();
        $select->from($this->_name)->where('FeedBackID = ?',$FeedBackID);
        return $select->query()->fetch();
    }

    public function setFeedbackMember($memberID,$deviceNo)
    {
        $select = $this->select();
        return $this->update(array('MemberID'=>$memberID), array('DeviceNo = ?'=>$deviceNo,'MemberID = ?'=>0));

    }
	
}
