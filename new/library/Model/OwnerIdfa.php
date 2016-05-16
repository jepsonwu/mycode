<?php
use Qiniu\json_decode;
/**
 *  IDFA
 * @author Jeff
 *
 */
class Model_OwnerIdfa extends Zend_Db_Table
{
	protected $_name = 'owner_idfa';
	protected $_primary = 'ID';

	/**
	 *  添加IDFA
	 * @param int $memberID
	 * @param string $idfa
	 */
	public function add($memberID,$idfa)
	{
		$db = $this->getAdapter();
		$sql = "insert into owner_idfa(MemberID,DeviceInfo,LastTime) values(:MemberID,:DeviceInfo,:LastTime) on duplicate key update LastTime = '".date('Y-m-d H:i:s')."',MemberID = $memberID";
		$db->query($sql,array('MemberID'=>$memberID,'DeviceInfo'=>$idfa,'LastTime'=>date('Y-m-d H:i:s')));
		return true;
	}
	
	/**
	 * 获取未回调的Idfa
	 */
	public function callbackYouqian()
	{
		$yqUrl = "http://www.iyouqian.com/external/callback/1000279/10411/?";//有钱回调地址
		$qmUrl = "http://www.qumi.com/api/vendor/ios/actived-15468?";
		$select = $this->select()->from($this->_name,array('ID','DeviceInfo'))->where('IsCallback = ?',0);
		$info = $select->query()->fetchAll();
		if(!empty($info)){
			foreach($info as $val){
				$idfa = $val['DeviceInfo'];
				$model = new Model_PartnerIdfa();
				$idfaArr = $model->getIdfaInfo($idfa);
				if(!empty($idfaArr)){
					$mac = $idfaArr['Mac'];
					if($idfaArr['PartnerName'] == 'youqian')
					{
						$callUrl = $yqUrl."did=$idfa&ret_type=0";
						$result = file_get_contents($callUrl);
					 	if($result == 'success'){
						 	$this->update(array('IsCallback'=>1),array('ID = ?'=>$val['ID']));
					 	}
					}elseif($idfaArr['PartnerName'] == 'qumi'){
						$callUrl = $qmUrl."mac=$mac&idfa=$idfa";
						$result = json_decode(file_get_contents($callUrl),true);
						if($result['message'] == 'success'){
							$this->update(array('IsCallback'=>3),array('ID = ?'=>$val['ID']));
						}
					}
				}else{
					$this->update(array('IsCallback'=>2),array('ID = ?'=>$val['ID']));
				}
			}
		}
	}

}