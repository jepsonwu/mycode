<?php
/**
 *  合作商IDFA
 * @author Jeff
 *
 */
class Model_PartnerIdfa extends Zend_Db_Table
{
	protected $_name = 'partner_idfa';
	protected $_primary = 'ID';

	/**
	 *  添加IDFA
	 * @param string $deviceInfo
	 * @param string $partner_name
	 */
	public function add($partner_name,$deviceInfo,$mac)
	{
		$db = $this->getAdapter();
		if(empty($mac)){
			$sql = "insert into partner_idfa(DeviceInfo,PartnerName) values(:DeviceInfo,:PartnerName) on duplicate key update CallNum = CallNum+1";
			$re = $db->query($sql,array('DeviceInfo'=>$deviceInfo,'PartnerName'=>$partner_name));
		}else{
			$sql = "insert into partner_idfa(DeviceInfo,PartnerName,Mac) values(:DeviceInfo,:PartnerName,:Mac) on duplicate key update CallNum = CallNum+1";
			$re = $db->query($sql,array('DeviceInfo'=>$deviceInfo,'PartnerName'=>$partner_name,'Mac'=>$mac));
		}
		
		return true;
	}
	
	/**
	 * 查看idfa是否存在
	 * @param unknown $idfa
	 */
	public function getIdfaInfo($idfa)
	{
		$info = $this->select()->from($this->_name,array('ID','PartnerName','Mac'))->where('DeviceInfo = ?',$idfa)->query()->fetch();
		return $info ? $info : array();
	}
}