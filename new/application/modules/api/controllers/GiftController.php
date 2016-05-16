<?php
/**
 * 打赏送礼相关
 * @author Jeff
 *
 */
class Api_GiftController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
		$this->checkDeny();
	}

	/**
	 * 获取系统提供的礼物列表
	 */
	public function getSystemGiftsAction()
	{
		try{
			$lastID = intval($this->_getParam('lastID',0));
			$pagesize = intval($this->_getParam('pagesize',30));
			$giftModel = new Model_Gift();
			$result = $giftModel->getGiftList($lastID,$pagesize);
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取某个观点或文章打赏列表
	 */
	public function getGiftListAction(){
		try{
			$type = intval($this->_getParam('type',0));//1观点 2文章
			if(!in_array($type, array(1,2))){
				Throw new Exception('参数错误!');
			}
			$infoID = intval($this->_getParam('infoID',0));
			if($infoID<1){
				Throw new Exception('参数错误!');
			}
			$lastID = intval($this->_getParam('lastID',0));
			$pagesize = intval($this->_getParam('pagesize',30));
			$memberModel = new DM_Model_Account_Members();
			if($type==1){//观点打赏
				$viewGiftModel = new Model_Topic_ViewGift();
				//送礼物的用户列表
				$result = $viewGiftModel->getViewGifts($infoID,$lastID,$pagesize);
			}else{//文章打赏
				$articleModel = new Model_Column_ArticleGift();
				$result = $articleModel->getArticleGifts($infoID,$lastID,$pagesize);
			}
			if(!empty($result)){
				$memberNoteModel = new Model_MemberNotes();
				foreach($result as &$val){
					$val['Avatar'] = $memberModel->getMemberAvatar($val['GiftMemberID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['GiftMemberID'],'UserName');
					$val['NoteName'] = $memberNoteModel->getNoteName($this->memberInfo->MemberID, $val['GiftMemberID']);
				}
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取某个观点，文章礼物统计
	 */
	public function getGiftStaticAction()
	{
		try{
			$type = intval($this->_getParam('type',0));//1观点 2文章
			if(!in_array($type, array(1,2))){
				Throw new Exception('参数错误!');
			}
			$infoID = intval($this->_getParam('infoID',0));
			if($infoID<1){
				Throw new Exception('参数错误!');
			}
			$result = array();
			if($type == 1){//观点
				$viewGiftModel = new Model_Topic_ViewGift();
				$result = $viewGiftModel->getStaticGifts($infoID);
			}else{//文章
				$articleModel = new Model_Column_ArticleGift();
				$result = $articleModel->getStaticGifts($infoID);
			}
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 给观点或文章打赏
	 */
	public function sendGiftAction(){
		try{
			$type = intval($this->_getParam('type',0));//1观点打赏  2文章打赏  3文章付费 
			if(!in_array($type, array(1,2,3))){
				throw new Exception("非法类型！", parent::STATUS_FAILURE);
			}
			$infoID = intval($this->_getParam('infoID',0));
			if($infoID<1){
				throw new Exception("参数错误!", parent::STATUS_FAILURE);
			}
 			$memberID = $this->memberInfo->MemberID;
			$ownerMemberID = intval($this->_getParam('ownerMemberID',0));
			if($ownerMemberID<1){
				if($type == 1){
					$viewModel = new Model_Topic_View();
					$viewInfo = $viewModel->getViewInfo($infoID);
					$ownerMemberID = $viewInfo['MemberID'];
				}else{
					$articleModel = new Model_Column_Article();
					$articleInfo = $articleModel->getArticleInfo($infoID);
					$ownerMemberID = $articleInfo['MemberID'];
				}
				//throw new Exception("参数错误!", parent::STATUS_FAILURE);
			}
			$giftID = intval($this->_getParam('giftID',0));
			$giftNum = intval($this->_getParam('giftNum',1));
			$money = $this->_getParam('money',0);
			if(intval($money*100)<1){
				throw new Exception("最少支付0.01元", parent::STATUS_FAILURE);
			}
			
			$payPwd = trim($this->_getParam('payPassword',''));
			$giftType = intval($this->_getParam('giftType',0));
			//观点文章打赏判断礼物类型
			if($type!=3 && !in_array($giftType, array(1,2))){
				throw new Exception("参数错误!", parent::STATUS_FAILURE);
			}
			if(in_array($type, array(1,2)) && $giftType<1){
				throw new Exception("参数错误!", parent::STATUS_FAILURE);
			}
			$walletModel = new Model_Wallet_Wallet();
			$re = $walletModel->payValidation($memberID);
			if($re && empty($payPwd)){
				throw new Exception('请输入支付密码!',parent::STATUS_FAILURE);
			}
			if(!empty($payPwd)){
				$check = $walletModel->checkPayPasswordAction($memberID,$payPwd);
				if($check['flag']<0){
					throw new Exception("支付密码验证失败", $check['flag']);
				}
			}
			$fundsModel = new DM_Model_Table_Finance_Funds();
			$blance = $fundsModel->getMemberBalance($memberID);
			if($blance < $money){
				throw new Exception("零用钱余额不足，请充值！", parent::STATUS_BALANCE_NOT_ENOUGH);
			}
			if($type==3){
				$giftType = 1;
			}
			$giftModel = new Model_Gift();
			$giftModel->payGift($infoID,$memberID,$giftID,$giftNum,$money,$ownerMemberID,$type,$giftType);
			$msg = $type == 3?'支付成功':'打赏成功';
			$this->returnJson(parent::STATUS_OK,$msg);
		}catch(Exception $e){
			$this->returnJson($e->getCode(),$e->getMessage());
		}
	}
	
}