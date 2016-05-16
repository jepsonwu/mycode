<?php
/**
 * 执行提现的操作，调用易宝的接口
 * User: hale <jepson@duomai.com>
 * Date: 15-12-21
 * Time: 上午10:15
 */
class DM_Third_Yee_yeepayRefund{

	private $hmacKey;
	
	private $mer_Id;
	
	private $cmd;
	
	private $version = '1.0';
	
	private $product = '';
	
	private $batch_No = '';
	
	private $hmac;
	
	private $refundList = null;
	
	private $balanceURL = '';
	
	private $otherURL = '';
	
	private $getHmacURL = '';
	
	//手续费收取方式
	const FEE_TYPE_SOURCE = 'SOURCE';//商户承担
	const FEE_TYPE_TARGET = 'TARGET';//用户承担
	
	//是否需要加急处理
	const URGENCY = '1';//加急
	const UN_URGENCY = '0';//不加急

	public function __construct()
	{
		$config = DM_Controller_Front::getInstance()->getConfig()->yee;
		$this->hmacKey = $config->hmacKey;
		$this->mer_Id = $config->merchant_account;
		$this->balanceURL = $config->BalanceURL;
		$this->otherURL = $config->OtherURL;
		$this->getHmacURL = $config->getHmacURL;
	}
	
	public function setValue($name,$value){
		$this->$name = $value;
	}
	
	/**
	 * 批量处理提现
	 */
	public function batch(){
		return array('ret_Code'=>1);
		$this->cmd = "TransferBatch";
		$xml = $this->getXml($this->refundList);
		$res = $this->http($this->otherURL,'POST',$xml);
		$xml = new SimpleXMLElement($res);
		$xmlArr = Model_Tools::object_to_array($xml);
		return $xmlArr;
	}
	
	/**
	 * 单笔提现
	 */
	public function one(){
		return array('ret_Code'=>1);
		$this->cmd = "TransferSingle";
		$xml = $this->getXml($this->refundList);
		$res = $this->http($this->otherURL,'POST',$xml);
		$xml = new SimpleXMLElement($res);
		$xmlArr = Model_Tools::object_to_array($xml);
		return $xmlArr;
	}
	
	public function getHmac($str){
		return Model_Tools::curl($this->getHmacURL,array('hmacStr'=>$str));
	}

	/**
	 * 生成xml数据
	 */
	public function getXml($refundList=null){
		$xmlDoc = new DOMDocument('1.0', 'GBK');
		$publicData = $xmlDoc->createElement("data");//请求的公共参数部分
		$xmlDoc->appendChild($publicData);
		$num = count($refundList);
		$total_Num = $num;//总条数
		$total_Amt = 0;//总金额
		$is_Repay = 1;
		if($num>1){//批量
			$listData = $xmlDoc->createElement("list");
			$itemData = $xmlDoc->createElement("item");
			
			//xml节点
			$publicElementArr = array("cmd"=>$this->cmd,"Version"=>$this->version,"group_Id"=>$this->mer_Id,"mer_Id"=>$this->mer_Id,'product'=>$this->product,'batch_No'=>$this->batch_No,'is_Repay'=>$is_Repay);
		}else{//单笔提现
			$itemData = $publicData;
			
			//xml节点
			$publicElementArr = array("cmd"=>$this->cmd,"version"=>$this->version,"group_Id"=>$this->mer_Id,"mer_Id"=>$this->mer_Id,'product'=>$this->product,'batch_No'=>$this->batch_No);
		}
		
		foreach($publicElementArr as $k=>$v){
			if(!empty($v) && $v!=0){
				$element = $k."Element";
				$element = $xmlDoc->createElement($k);
				$element->appendChild($xmlDoc->createTextNode($v));
				$publicData->appendChild($element);	
			}
		}
		
		$itemElementArr = array("order_Id","bank_Code","cnaps","bank_Name",'branch_Bank_Name','amount','account_Name','account_Number','account_Type','province','city','payee_Email','payee_Mobile','leave_Word','abstractInfo','remarksInfo');
		foreach($refundList as $row){
			foreach($itemElementArr as $r){
				if(isset($row[$r])){
					$element = $r."Element";
					$element = $xmlDoc->createElement($r);
					$element->appendChild($xmlDoc->createTextNode($row[$r]));
					$itemData->appendChild($element);
				}
			}
			//手续费收取方式
			$element = $xmlDoc->createElement('fee_Type');
			$element->appendChild($xmlDoc->createTextNode(self::FEE_TYPE_SOURCE));
			$itemData->appendChild($element);
			//是否需要加急处理
			$element = $xmlDoc->createElement('urgency');
			$element->appendChild($xmlDoc->createTextNode(self::UN_URGENCY));
			$itemData->appendChild($element);
			
			if($num>1){
				$listData->appendChild($itemData);
			}else{
				$order_Id = $row['order_Id'];
				$amount = $row['amount'];
				$account_Number = $row['account_Number'];
			}
			$total_Amt += $row['amount'];
		}
		if($num>1){//批量
			$total_AmtElement = $xmlDoc->createElement('total_Amt');
			$total_AmtElement->appendChild($xmlDoc->createTextNode($total_Amt));
			$publicData->appendChild($total_AmtElement);
			$total_NumElement = $xmlDoc->createElement('total_Num');
			$total_NumElement->appendChild($xmlDoc->createTextNode($total_Num));
			$publicData->appendChild($total_NumElement);
			$publicData->appendChild($listData);
			$hmacStr = $this->cmd.$this->mer_Id.$this->batch_No.$total_Num.$total_Amt.$is_Repay.$this->hmacKey;
		}else{
			$hmacStr = $this->cmd.$this->mer_Id.$this->batch_No.$order_Id.$amount.$account_Number.$this->hmacKey;
		}
		
		$hmac = $this->getHmac($hmacStr);
		$hmacElement = $xmlDoc->createElement('hmac');
		$hmacElement->appendChild($xmlDoc->createTextNode($hmac));
		$publicData->appendChild($hmacElement);
		return $xmlDoc->saveXML();
	}
	
	public function http($url, $method, $postfields = NULL){
		$header[] = "Content-type: text/xml";
		$ci = curl_init();
		curl_setopt($ci, CURLOPT_USERAGENT,'Yeepay MobilePay PHPSDK v1.1x');
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT,30);
		curl_setopt($ci, CURLOPT_TIMEOUT,30);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION,1);
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
		$method = strtoupper($method);
		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields))
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields))
					$url = "{$url}?{$postfields}";
		}
		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$httpCode = curl_getinfo($ci,CURLINFO_HTTP_CODE); 
		curl_close ($ci);
		return $response;
	}
}