<?php
/**
 *  猜股票涨跌活动
 * @author Jeff
 *
 */
class Model_FlowActivity extends Zend_Db_Table
{
	protected $_name = 'flow_activity';
	protected $_primary = 'FID';

	/**
	 * 添加信息
	 * @param unknown $data
	 */
	public function addInfo($memberID,$mobile,$flowSize,$activityType=1)
	{
		$data = array(
				'MemberID'=>$memberID,
				'Mobile'=>$mobile,
				'FlowSize'=>$flowSize,
				'ActivityType'=>$activityType
		);
		$newID = 0;
		$info = $this->getInfo($memberID,$flowSize);
		if(empty($info)){
			$newID = $this->insert($data);
		}
		return $newID;
	}
	
	/**
	 * 获取今天已注册的人数
	 */
	public function getcount($flag)
	{
		$select = $this->select()->from($this->_name,'count(1) as num')->where('MemberID > ?',0)->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"));
		if($flag==1){
			$select->where('FlowSize <?',50);
		}else{
			$select->where('FlowSize >=?',50);
		}
		
		$info = $select->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * 获取信息
	 */
	public function getInfo($memberID,$flowSize=0)
	{
		if($flowSize){
			return $this->select()->from($this->_name)->where('MemberID = ?',$memberID)->where('FlowSize = ?',$flowSize)->query()->fetch();
		}else{
			return $this->select()->from($this->_name)->where('MemberID = ?',$memberID)->order('FID desc')->query()->fetch();
		}
	}
	
	/**
	 * 获取5条送流量记录
	 */
	public function getRecord($memberID=0)
	{
		$result = array();
		$select = $this->select()->from($this->_name,array('Mobile','SUM(FlowSize) as FlowSize'));//->order('FlowSize desc')
		if($memberID>0){
			$select->where('MemberID = ?',$memberID)->where('FlowSize > ?',0)->group('MemberID');
			$myinfo = $select->query()->fetchAll();

			$otherInfo = $this->select()->from($this->_name,array('Mobile','SUM(FlowSize) as FlowSize'))
				->where('MemberID != ?',$memberID)->where('FlowSize > ?',0)->group('MemberID')->order('MemberID desc')->limit(5)->query()->fetchAll();
			$result = array_merge($myinfo,$otherInfo);
		}
		else{
			$result = $this->select()->from($this->_name,array('Mobile','SUM(FlowSize) as FlowSize'))
			->where('FlowSize > ?',0)->group('MemberID')->order('MemberID desc')->limit(6)->query()->fetchAll();
		}
		if(!empty($result)){
			foreach($result as &$val){
				$val['Mobile'] = substr_replace($val['Mobile'], '****', 3, 4);
			}
		}
		return $result;
	}
	
	/**
	 * 流量充值
	 */
	public function rechargeFlow()
	{
		$select = $this->select()->from($this->_name,array('FID','MemberID','Mobile','FlowSize'))->where('MemberID > ?',0)
					->where('FlowSize > ?',0)->where("IsRecharge = ? ",0)->where("ActivityType = ? ",2);
		$info = $select->query()->fetchAll();
		$model = new Model_FlowSDK();
		if(!empty($info)){
			foreach($info as $val){
				$style = $this->getMobileStyle($val['Mobile']);
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
	
	/**
	 * 获取手机号码所属的运营商
	 */
	public function getMobileStyle($mobile){
		$mobilecom = array(134,135,136,137,138,139,150,151,152,157,158,159,182,183,187,188,147,178,184);
		$unicom  = array(130,131,132,155,156,185,186,145,176);
		$telecom = array(133,153,180,181,189,177);
		$mobile = substr($mobile,0,3);
		if(in_array($mobile, $mobilecom)){
			$style = 1;//移动
		}elseif(in_array($mobile, $unicom)){
			$style = 2;//联通
		}else{
			$style = 3;//电信
		}
		return $style;
	}
	
	/**
	 * 获取信息
	 */
	public function getMobile($mobile)
	{
		return $this->select()->from($this->_name)->where('Mobile = ?',$mobile)->query()->fetch();
		
	}
}