<?php
/**
 *  猜股票涨跌活动
 * @author Jeff
 *
 */
class Model_FlowNew extends Zend_Db_Table
{
	protected $_name = 'flow_new';
	protected $_primary = 'FID';

	/**
	 * 获取5条送流量记录
	 */
	public function getRocords()
	{
		$result = array();
		$result = $this->select()->from($this->_name,array('Mobile','FlowSize'))
		->where('FlowSize > ?',0)->order('FID desc')->limit(6)->query()->fetchAll();
		if(!empty($result)){
			foreach($result as &$val){
				$val['Mobile'] = substr_replace($val['Mobile'], '****', 3, 4);
			}
		}
		return $result;
	}
	
	public function addInfo($memberID,$mobile,$flowSize)
	{
		$data = array('MemberID'=>$memberID,'Mobile'=>$mobile,'FlowSize'=>$flowSize);
		$insertID = $this->insert($data);
		return $insertID;
	}
	
	public function getInfo($memberID)
	{

		return $this->select()->from($this->_name)->where('MemberID = ?',$memberID)->order('FID desc')->query()->fetch();

	}
	
	/**
	 * 流量充值
	 */
	public function rechargeFlow()
	{
		$select = $this->select()->from($this->_name,array('FID','MemberID','Mobile','FlowSize'))->where('MemberID > ?',0)
		->where('FlowSize > ?',0)->where("IsRecharge = ? ",0);
		$info = $select->query()->fetchAll();
		$model = new Model_FlowSDK();
		$flowModel = new Model_FlowActivity();
		$productArr = array();
		if(!empty($info)){
			foreach($info as $val){
				$style = $flowModel->getMobileStyle($val['Mobile']);
				if($style==1){
					$productID = '2000981';
				}elseif($style==2){
					$productID = '2000976';
				}else{
					$productID = '2000718';
				}
				$partnerOrderNo = date("YmdHis",time()).$val['MemberID'].$val['FID'];
				$model->recharge($val['FID'],$val['MemberID'],$val['Mobile'],$productID,$partnerOrderNo);
			}
		}
	}
	
	
}