<?php
/**
 * 群标签
* @author Mark
*
*/
class Model_IM_GroupFocus extends Zend_Db_Table
{
	protected $_name = 'group_focus';
	protected $_primary = 'GroupFocusID';

	/**
	 *  增加关注点
	 * @param string $groupID
	 * @param int $focusID
	 */
	public function addFocus($groupID,$focusID)
	{
		$focusInfo = $this->getFocusInfo($groupID,$focusID);
		if(empty($focusInfo)){
			$this->insert(array('GroupID'=>$groupID,'FocusID'=>$focusID));
		}
		return true;
	}

	/**
	 *  获取关注信息
	 * @param string $groupID
	 * @param int $focusID
	 */
	public function getFocusInfo($groupID,$focusID = null,$fields ='*')
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as gf',$fields)->where('gf.GroupID = ?',$groupID);
		$select->joinInner('focus as f', 'f.FocusID = gf.FocusID ','FocusName');
		if(!is_null($focusID)){
			$res = $select->where('gf.FocusID = ?',$focusID)->query()->fetch();
		}else{
			$res = $select->query()->fetchAll();
		}
		return $res ? $res : array();
	}

	/**
	 *  是否有关注点
	 * @param string $groupID
	 */
	public function hasFocusInfo($groupID)
	{
		$res = $this->getFocusInfo($groupID);
		return !empty($res) ? true : false;
	}
}