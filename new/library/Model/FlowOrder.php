<?php
/**
 *  猜股票涨跌活动
 * @author Jeff
 *
 */
class Model_FlowOrder extends Zend_Db_Table
{
	protected $_name = 'flow_order';
	protected $_primary = 'ID';

	/**
	 * 添加信息
	 * @param unknown $data
	 */
	public function addInfo($partnerOrde,$orderSn,$mobile,$productID,$isNew,$memberID,$activitType)
	{
		$data = array(
				'PartnerOrde'=>$partnerOrde,
				'OrderSn'=>$orderSn,
				'Mobile'=>$mobile,
				'ProductID'=>$productID,
				'IsNew'=>$isNew,
				'ActivityType'=>$activitType,
				'MemberID'=>$memberID
		);
		$newID = $this->insert($data);
		return $newID;
	}
	
	
	public function getinfo()
	{
		return $this->select()->from($this->_name,array('OrderSn'))->where('Status=0 or Status=4')->where('ActivityType = ?',2)->query()->fetchAll();
	}
	
	
	/**
	 * 查询充值是否成功
	 */
	public function queryOrders()
	{
		$info = $this->getinfo();
		$flowSDK = new Model_FlowSDK();
		if(!empty($info)){
			foreach($info as $val){
				$flowSDK->queryOrder($val['OrderSn']);
			}
		}
	}
	
	/**
	 * 通知送流量成功的财猪号
	 */
	public function noticeMemberAction()
	{
		$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
		$easeModel = new Model_IM_Easemob();
		$content = "欢迎加入财猪！恭喜获得了财猪官方赠送的新用户流量礼包。现在打开微信搜索“财猪”公众号并关注，就有机会获得小米手环、电影票、1G、100M流量等更多奖品。";
		$lastID = 0;
		while(true){
			$select = $this->select();
			$select->from($this->_name)->where('Status = 1')->where('IsNoticed = 0')->where('ActivityType = ? ',2)->where('ID  > ? ',$lastID)->order('ID asc')->limit(20);
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				$tmp = array();
				foreach($result as $item){
					$tmp[] = $item['MemberID'];
					$lastID = $item['ID'];
				}
				$ret = $easeModel->yy_hxSend($tmp, $content,'text','users',array('optionRand'=>1),$sysMemberID);
				$retArr = json_decode($ret,true);
				if(is_array($retArr) && !empty($retArr['data'])){
					foreach($retArr['data'] as $memberID=>$resSign){
						if($resSign == 'success'){
							$this->update(array('IsNoticed'=>1),array('MemberID = ?'=>$memberID));
						}
					}
				}
			}
			if(count($result < 20)){
				break;
			}
		}
	}
}