<?php

/**
 * 定义程序绝对路径
 */
define('SCRIPT_ROOT',  dirname(__FILE__).'/');
require_once SCRIPT_ROOT.'include/Client.php';

class YMSms {
	
	/**
	 * 网关地址
	 */
	private $gwUrl = 'http://sdkint.eucp.b2m.cn:8080/sdk/SDKService?wsdl';
	
	/**
	 * 序列号,请通过亿美销售人员获取
	 */
	private $serialNumber = '6INT-EMY-6600-JCZQQ';
	
	/**
	 * 密码,请通过亿美销售人员获取
	 */
	private $password = '135112';
	
	/**
	 * 登录后所持有的SESSION KEY，即可通过login方法时创建
	 */
	private $sessionKey = '203948';
	
	/**
	 * 连接超时时间，单位为秒
	 */
	private $connectTimeOut = 2;
	
	/**
	 * 远程信息读取超时时间，单位为秒
	 */
	private $readTimeOut = 10;
	
	/**
	 * $proxyhost		可选，代理服务器地址，默认为 false ,则不使用代理服务器
	 * $proxyport		可选，代理服务器端口，默认为 false
	 * $proxyusername	可选，代理服务器用户名，默认为 false
	 * $proxypassword	可选，代理服务器密码，默认为 false
	 */
	private $proxyhost = false;
	private $proxyport = false;
	private $proxyusername = false;
	private $proxypassword = false;
	
	private $client;
	
	public function __construct() {
		/**
		 * 实例化客户端
		 */
		$this->client = new Client($this->gwUrl,$this->serialNumber,$this->password,$this->sessionKey,$this->proxyhost,
			$this->proxyport,$this->proxyusername,$this->proxypassword,$this->connectTimeOut,$this->readTimeOut);
		/**
		 * 发送向服务端的编码，如果本页面的编码为GBK，请使用GBK
		 */
		$this->client->setOutgoingEncoding("GBK");
	}
	
	/**
	 * 登录
	 */
	public function login() {
		// 调用登录方法
		$statusCode = $this->client->login();
		// 调用成功
		if ($statusCode != null && $statusCode == '0') {
			$result = "session_key: {$this->client->getSessionKey()}";
		} else {
			$result = "error_code: {$statusCode}";
		}
		// 返回结果
		return $result;
	}
	
	/**
	 * 注册
	 */
	public function register() {
		$eName = "xx公司"; // 企业名称
		$linkMan = "陈xx"; // 联系人姓名
		$phoneNum = "010-1111111"; // 联系电话
		$mobile = "159xxxxxxxx"; // 联系手机号码
		$email = "xx@yy.com"; // 联系电子邮件
		$fax = "010-1111111"; // 传真号码
		$address = "xx路"; // 联系地址
		$postcode = "111111"; // 邮政编码
		// 调用注册方法
		$statusCode = $this->client->registDetailInfo($eName,$linkMan,$phoneNum,$mobile,$email,$fax,$address,$postcode);
		// 返回结果
		return $statusCode;
	}
	
	/**
	 * 发送短信
	 */
	public function sendSMS($mobile, $message) {
		// 调用发送短信方法
		$statusCode = $this->client->sendSMS(array($mobile), $message);
		// 返回结果
		return $statusCode;
	}
	
}


