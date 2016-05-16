<?php
/**
 * 专栏活动
 *
 * @author Jeff
 *
 */
class Model_Column_Activity extends Zend_Db_Table
{
	protected $_name = 'column_activity';
	protected $_primary = 'AID';
	
	/**
	 *  获取信息
	 * @param int $memberID
	 */
	public function getActvityInfo($activityID,$fileds="*")
	{
		$select = $this->select()->from($this->_name,$fileds)->where('AID = ?',$activityID);
		$info = $select->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 增加报名数
	 * @param unknown $activityID
	 * @param number $increment
	 * @return number
	 */
	public function increaseEnrollNum($activityID,$increment=1)
	{
		return $this->update(array('EnrollNum'=>new Zend_Db_Expr("EnrollNum + ".$increment)),array('AID = ?'=>$activityID));
	}
	
	/**
	 * 增加阅读数
	 * @param unknown $activityID
	 * @param number $increment
	 * @return number
	 */
	public function increaseReadNum($activityID,$increment=1)
	{
		return $this->update(array('ReadNum'=>new Zend_Db_Expr("ReadNum + ".$increment)),array('AID = ?'=>$activityID));
	}
	
	/**
	 * 获取活动数量
	 * @param unknown $type
	 */
	public function getCount($columnID,$type)
	{
		$select= $this->select()->from($this->_name,'count(1) as num')->where('Status = ?',1)->where('ColumnID = ?',$columnID);
		if($type==1){
			$select->where('EndTime > ?',date('Y-m-d H:i:s'));
		}else{
			$select->where('EndTime < ?',date('Y-m-d H:i:s'));
		}
		$re = $select->query()->fetch();
		return $re['num'];
	}
	
	/**
	 * 获取草稿箱活动数量
	 * @param unknown $type
	 */
	public function getDraftCount($memberID,$column)
	{
		$select= $this->select()->from($this->_name,'count(1) as num')->where('Status = ?',2)->where('MemberID = ?',$memberID);
		$select->where('ColumnID = ?',$column);
		$re = $select->query()->fetch();
		return $re['num'];
	}
	
	/**
	 * 获取理财号最新的活动
	 * @param unknown $columnID
	 */
	public function getNewActivity($columnID,$limit)
	{
		$info = $this->select()->from($this->_name,array('ContentType'=>new Zend_Db_Expr(2),'AID','MemberID','Title','Cover','StartTime','EndTime','Province','City','EnrollNum','CreateTime as PublishTime'))->where('ColumnID = ?',$columnID)
		->where('Status = ?',1)->order('AID desc')->limit($limit)->query()->fetchAll();
		return $info ? $info : array();
	}
	
	/**
	 * 获取我订阅的理财号有没有产生新的活动
	 */
	public function getMessageActivity($columnArr,$lastTime)
	{
		$info = $this->select()->from($this->_name,array('AID'))->where("ColumnID in (?)",$columnArr)
		->where('Status = ?',1)->where('UNIX_TIMESTAMP(CreateTime) > ?',$lastTime)->limit(1)->query()->fetch();
		return $info ? $info : array();
	}
}