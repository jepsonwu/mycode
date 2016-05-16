<?php
/**
 *  消息助手
 *2015-07-01
 * @author Jeff
 *
 */
class Model_MessageHelper extends Zend_Db_Table
{
	protected $_name = 'message_helper';
	protected $_primary = 'MID';

	/**
	 *  添加消息
	 * @param int $memberID
	 * @param int $messageType
	 * @param int $relationID
	 */
	public function setHelper($objectType,$objectID,$memberID,$status)
	{
		if($status == 1){//开启助手
			$info = $this->getInfo($memberID, $objectID, $objectType);
			if(empty($info)){
				$data = array('ObjectType'=>$objectType,'ObjectID'=>$objectID,'MemberID'=>$memberID,'CreateTime'=>date('Y-m-d H:i:s'));
				return $this->insert($data);
			}
		}else{//关闭助手
			$this->delete(array('ObjectID = ?'=>$objectID,'MemberID = ?'=>$memberID));
		}
		return true;
	}
	
	/**
	 *  获取信息
	 * @param int $memberID
	 * @param int $groupID
	 */
	public function getInfo($memberID,$groupID,$objectType)
	{
		$select = $this->select();
		$select->where('MemberID = ?',$memberID)->where('ObjectID = ?',$groupID)->where('ObjectType = ?',$objectType);
		return $select->query()->fetch();
	}

}