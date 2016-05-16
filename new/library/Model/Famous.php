<?php
/**
 * 名人堂
 * @author kitty
 *
 */
class Model_Famous extends Zend_Db_Table
{
	protected $_name = 'famous';
	protected $_primary = 'FID';

	/**
	 *  判断是否已加入
	 * @param int $memberID
	 */
	public function hasJoined($memberID)
	{
		$select = $this->select()->from('famous','COUNT(1)')->where('MemberID = ?',$memberID);
		$count = $this->_db->fetchOne($select);
		return $count > 0 ? $count : 0;
	}

}