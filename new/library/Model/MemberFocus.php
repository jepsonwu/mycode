<?php
/**
 *  会员关注点
 * @author Mark
 *
 */
class Model_MemberFocus extends Zend_Db_Table
{
	protected $_name = 'member_focus';
	protected $_primary = 'MemberFocusID';
	
	/**
	 *  增加关注点
	 * @param int $memberID
	 * @param int $focusID
	 */
	public function addFocus($memberID,$focusID)
	{
		$focusInfo = $this->getFocusInfo($memberID,$focusID);
		if(empty($focusInfo) && $focusID > 0){
			$this->insert(array('MemberID'=>$memberID,'FocusID'=>$focusID));
		}
		return true;
	}
	
	/**
	 *  移除关注点
	 * @param int $channelID
	 */
	public function removeFocus($memberID,$focusID)
	{
        if( !$focusID ) {
            throw new Exception('关注点ID不能为空');
        }
        $focusInfo = $this->getFocusInfo($memberID,$focusID);
        if(!empty($focusInfo)){
			$this->delete(array('FocusID = ?' => $focusID,'MemberID = ?'=>$memberID));
        }
        return true; 
	}
	/**
	 *  获取关注信息
	 * @param int $memberID
	 * @param int $focusID
	 */
	public function getFocusInfo($memberID,$focusID = null,$fields ='*')
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as mf',$fields)->where('mf.MemberID = ?',$memberID);
		$select->joinInner('focus as f', 'f.FocusID = mf.FocusID ','FocusName');
		if(!is_null($focusID)){
			return $select->where('mf.FocusID = ?',$focusID)->query()->fetch();
		}
		$res = $select->query()->fetchAll();
		return $res ? $res : array();
	}
	
	/**
	 *  是否有关注点
	 * @param int $memberID
	 */
	public function hasFocusInfo($memberID)
	{
		$res = $this->getFocusInfo($memberID);
		return !empty($res) ? true : false;
	}
	
	public function getFocusID($memberID)
	{
		$db = $this->getAdapter();
		$focusIDArr = $db->fetchCol("SELECT FocusID FROM member_focus WHERE `MemberID` = :MemberID",array("MemberID" => $memberID));
		return $focusIDArr ? $focusIDArr : array();
	}
}