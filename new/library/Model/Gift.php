<?php
/**
 * 礼物相关
 * @author Jeff
 *
 */
class Model_Gift extends Zend_Db_Table
{
	protected $_name = 'gifts';
	protected $_primary = 'GiftID';


	/**
	 *  获取观点礼物列表
	 * @param int $viewID
	 */
	public function getGiftList($lastID,$pagesize)
	{
		$select = $this->select()->from($this->_name,array('GiftID','GiftName','Price','Cover','Type'))->where('Status = ?',1);
		if($lastID){
			$select = $select->where('GiftID >?',$lastID);
		}
		$info = $select->order('GiftID asc')->limit($pagesize)->query()->fetchAll();
		return empty($info)?array():$info;
	}
	
	/**
	 * 打赏支付
	 * @param unknown $memberID
	 * @param unknown $giftID
	 * @param unknown $giftNum
	 * @param unknown $money
	 * @param unknown $ownerMemberID
	 */
	public function payGift($infoID,$memberID,$giftID,$giftNum,$money,$ownerMemberID,$type,$giftType=1,$realName=NULL,$mobile='')
	{
		$db = $this->getAdapter();
		$db->beginTransaction();
		try{
			$ip = DM_Controller_Front::getInstance()->getHttpRequest()->getClientIp();
			$system = DM_Controller_Front::getInstance()->getConfig()->system;
			$message = '';
			$topicID = 0;
			if($giftID){
				$giftModel = new Model_Gift();
				$giftInfo = $giftModel->getGiftInfo($giftID);
				if(empty($giftInfo)){
					throw new Exception('该礼物已不存在！');
				}
				if($giftType==1){
					$message = $money.'元零钱';
				}else{
					$message = $giftNum.$giftInfo['Unit'].$giftInfo['GiftName'];
				}
			}
			if($type == 1){//观点打赏
				$financeType = 4;
				$remark = '观点打赏';
				$rate = $system->activity_gift->rate;
				$viewModel = new Model_Topic_View();
				$viewInfo = $viewModel->getViewInfo($infoID);
				$topicID = $viewInfo['TopicID'];
			}elseif($type==2){//文章打赏
				$financeType = 6;
				$remark = '文章打赏';
				$rate = $system->article_gift->rate;
			}elseif($type==3){
				$financeType = 5; //文章付费
				$remark = '文章付费';
				$rate = $system->article_pay->rate;
			}elseif($type==4){//活动报名付费
				$financeType = 7;
				$remark = '活动报名';
				$rate = $system->activity_enroll->rate;
			}
			$remark = $remark.$message;
			$fee = $money*$rate;
			$realMoney = $money-$fee;
			
			$orderModel = new DM_Model_Table_Finance_Funds();
			$orderNo = $orderModel->getOrderSn();
			$messageModel = new Model_Message();
			//创建一条送礼人的支出记录
		
			$step1 = $orderModel->createOrder($memberID,2,$financeType,$money,3,1,0,$orderNo,$ip,'CNY',0,$remark);
			if(!$step1){
				throw new Exception('创建支出记录失败');
			}
			//添加支出消息
			$msg1 = $messageModel->addMessage($memberID,Model_Message::MESSAGE_TYPE_PAY,$step1,Model_Message::MESSAGE_SIGN_WALLET);
			if(!$msg1){
				throw new Exception('添加支出消息失败');
			}
			//创建一条收礼人的收入记录
			if($giftType==1){//现金打赏时才会给作者增加收入
				$step2 = $orderModel->createOrder($ownerMemberID,1,$financeType,$realMoney, 3,2,$memberID,$orderNo,$ip,'CNY',0,$remark);
				if(!$step2){
					throw new Exception('创建收入记录失败');
				}
				$msg2 = $messageModel->addMessage($ownerMemberID,  Model_Message::MESSAGE_TYPE_INCOME,$step2,Model_Message::MESSAGE_SIGN_WALLET);
				if(!$msg2){
					throw new Exception('添加收入消息失败');
				}
			}
			//扣除送礼人的余额
			$step3 = $orderModel->modifyAmount($memberID,'CNY',$money,2,$financeType,$ip,$remark,$step1);
			if(!$step3){
				throw new Exception('扣款失败');
			}
			//增加收礼人的余额
			if($giftType==1){
				$step4 = $orderModel->modifyAmount($ownerMemberID,'CNY',$realMoney,1,$financeType,$ip,$remark,$step2);
				if(!$step4){
					throw new Exception('收款失败');
				}
			}
			if($type==1){
				$viewGiftModel = new Model_Topic_ViewGift();
				//增加送礼记录
				$paramArr = array(
						'ViewID'=>$infoID,
						'MemberID'=>$ownerMemberID,
						'GiftID'=>$giftID,
						'GiftMemberID'=>$memberID,
						'Amount'=>$money,
						'GiftNum'=>$giftNum,
						'OrderNo'=>$orderNo,
						'RealityAmount'=>$realMoney,
						'FeeAmount'=>$fee
				);
				$step5 = $viewGiftModel->insert($paramArr);
			}elseif($type == 2 ||$type == 3){
				$articleGiftModel = new Model_Column_ArticleGift();
				//增加记录
				if($type==2){
					$paramArr = array(
							'ArticleID'=>$infoID,
							'MemberID'=>$ownerMemberID,
							'GiftID'=>$giftID,
							'GiftMemberID'=>$memberID,
							'Amount'=>$money,
							'GiftNum'=>$giftNum,
							'OrderNo'=>$orderNo,
							'RealityAmount'=>$realMoney,
							'FeeAmount'=>$fee,
					);
				}else{
					$paramArr = array(
							'ArticleID'=>$infoID,
							'MemberID'=>$ownerMemberID,
							'GiftMemberID'=>$memberID,
							'Amount'=>$money,
							'OrderNo'=>$orderNo,
							'Type'=>2,
							'RealityAmount'=>$realMoney,
							'FeeAmount'=>$fee,
					);
				}
				$step5 = $articleGiftModel->insert($paramArr);
			}elseif($type == 4){
				$enrollModel = new Model_Column_ActivityEnroll();
				$step5 = $enrollModel->enroll($infoID,$memberID,$realName,$mobile,$step1,$money,$realMoney,$fee);
			}
			if($step5 > 0){
				if($topicID>0){
					//统计某个话题下某个人被打赏的次数
					$redisObj = DM_Module_Redis::getInstance();
					$cacheKey = 'gitNum:TopicID:'.$topicID.':Date:'.date('Y-m');
					$redisObj->ZINCRBY($cacheKey,1,$ownerMemberID);
					$redisObj->EXPIRE($cacheKey,35*86400);
					//打赏支出金额
					$cacheKey2 = 'payAmount:TopicID:'.$topicID.':Date:'.date('Y-m');
					$redisObj->ZINCRBY($cacheKey2,$money,$ownerMemberID);
					$redisObj->EXPIRE($cacheKey2,35*86400);
				}
				$db->commit();
			}else{
				throw new Exception('失败');
			}
 			
		}catch(Exception $e){
			$db->rollBack();
			throw new Exception($e->getMessage(), -1);
		}
	}
	
	/**
	 * 根据订单号获取对应的观点或者文章ID
	 * @param unknown $type
	 * @param unknown $orderNo
	 */
	public function getIDByOrdersn($type,$orderNo)
	{
		if($type==1){
			$model = new Model_Column_ArticleGift();
			$info = $model->select()->from('column_article_gifts',array('ArticleID as ID'))->where('OrderNo = ?',$orderNo)->query()->fetch();
		}else{
			$model = new Model_Topic_ViewGift();
			$info = $model->select()->from('view_gifts',array('ViewID as ID'))->where('OrderNo = ?',$orderNo)->query()->fetch();
		}
		return empty($info)?0:$info['ID'];
	}
	
	/**
	 * 获取礼物的相关信息
	 */
	public function getGiftInfo($giftID)
	{
		$info = $this->select()->from($this->_name,array('GiftName','Unit'))->where('GiftID = ?',$giftID)->query()->fetch();
		return empty($info)?array():$info;
	}
	
}