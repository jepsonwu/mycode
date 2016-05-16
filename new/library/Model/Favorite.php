<?php
/**
 *广告位相关
 * @author Jeff
 *
 */
class Model_Favorite extends Zend_Db_Table
{
	protected $_name = 'favorite';
	protected $_primary = 'FavoriteID';
	
	/**
	 * 添加收藏
	 * @param unknown $type
	 * @param unknown $relationID
	 * @param unknown $memberID
	 */
	public function addFavorite($type,$relationID,$memberID)
	{
		$db = $this->getAdapter();
		$sql = "insert into favorite(MemberID,RelationID,Type) values(:MemberID,:RelationID,:Type) on duplicate key update CreateTime = '".date('Y-m-d H:i:s')."'";
		$db->query($sql,array('MemberID'=>$memberID,'RelationID'=>$relationID,'Type'=>$type));
		return true;
	}
	
	
	/**
	 *  获取收藏信息
	 * @param int $type
	 * @param int $relationID
	 * @param int $memberID
	 */
	public function getInfo($type,$relationID,$memberID)
	{
		$select = $this->select();
		return $select->where('MemberID = ?',$memberID)->where('Type = ?',$type)->where('RelationID = ?',$relationID)->limit(1)->query()->fetch();
	}
}