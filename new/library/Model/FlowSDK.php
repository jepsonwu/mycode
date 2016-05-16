<?php
use Qiniu\json_decode;
/**
 * 注册送流量活动
 * @author  Jeff
 * @date 	2015.9.16
 * ***/
class Model_FlowSDK{
	private $appkey = '1442306290477';
	private $appsecret ='2e9586ee79fd49bbcb8e8b1b513b58ad';
	private $partnerId = 117;
	private $url = 'http://partnerapi.zt.raiyi.com/';
	
	/**
	 * 获取公钥
	 */
	public function getPublicKey()
	{
		$url = $this->url."v1/public/$this->partnerId/common/getPublicKey";
		$result = file_get_contents($url);
		$result = json_decode($result,true);
		return $this->pubkeyFormat($result['data']['publicKey']);
	}
	
	/**
	 * 转化公钥
	 */
	public function pubkeyFormat($rawPubKey) {
		$pubKey = chunk_split($rawPubKey,64,"\n");
		return "-----BEGIN PUBLIC KEY-----\n$pubKey-----END PUBLIC KEY-----";
	}
	
	/**
	* Rsa加密
	 */
	 public function getRsa($pubKey,$data)
	 {
		 openssl_public_encrypt($data,$encrypted,$pubKey);//私钥加密
		 $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
		 return $encrypted;
	 }
	 
	 /**
	  * 充值流量
	  * @param unknown $memberID
	  * @param unknown $mobile
	  */
	 public function recharge($FID,$memberID,$mobile,$productID,$partnerOrderNo)
	 {
	 	$authTimespan = date("YmdHis",time());
	 	$redisObj = DM_Module_Redis::getInstance();
	 	$key = 'activity:pubKey';
	 	$pubKey = $redisObj->get($key);
	 	if(empty($pubKey)){
	 		$pubKey = $this->getPublicKey();
	 		$redisObj->set($key,$pubKey);
	 	}
	 	$tmpmobile = $this->getRsa($pubKey, $mobile);
	 	$str = 'mobile='.$tmpmobile.'partnerOrderNo='.$partnerOrderNo.'productId='.$productID;
	 	$authSign = md5($this->appkey.'authTimespan='.$authTimespan.$str.$this->appsecret);
	 	$url = $this->url.'v1/private/'.$this->partnerId.'/order/buyFlow?authAppkey='.$this->appkey.'&authSign='.
	 	       $authSign.'&mobile='.urlencode($tmpmobile).'&partnerOrderNo='.$partnerOrderNo.'&productId='.$productID.'&authTimespan='.$authTimespan;
	 	$reslut = file_get_contents($url);
	 	$reslut = json_decode($reslut,true);
	 	if($reslut['code'] == '0000'){
	 		$model = new Model_FlowOrder();
	 		$fmodel = new Model_FlowActivity();
		 	$model->addInfo($reslut['data']['orderNo'],$partnerOrderNo,$mobile,$productID,1,$memberID,2);
		 	$fmodel->update(array('IsRecharge'=>1),array('FID = ?'=>$FID));
		 	return 'OK';
	 	}else{
	 		return 'Failure';
	 	}
	 	
	 }
	 
	 /**
	  * 查询订单
	  * @param unknown $orderSn
	  */
	 public function queryOrder($orderSn)
	 {
	 	$url = $this->url.'v1/private/'.$this->partnerId.'/order/queryOrderByPartnerOrderNo?';
	 	$authTimespan = date("YmdHis",time());
	 	$authSign = md5($this->appkey.'authTimespan='.$authTimespan.'partnerOrderNo='.$orderSn.$this->appsecret);
	 	$queryUrl = $url.'partnerOrderNo='.$orderSn.'&authTimespan='.$authTimespan.'&authAppkey='.$this->appkey.'&authSign='.$authSign;
	 	$result = file_get_contents($queryUrl);
	 	$re = json_decode($result,true);
	 	
	 	if($re['code'] == '0000'){
		 	$model = new Model_FlowOrder();
		 	$model->update(array('Status'=>$re['data']['status']),array('OrderSn = ?'=>$orderSn));
	 	}else{
	 		return 0;
	 	}
	 }
	 
}