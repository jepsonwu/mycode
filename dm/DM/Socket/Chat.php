<?php

require_once "WebSocket/Client.php";

/**
 * socket 公众号
 * User: kitty 
 */
class DM_Socket_Chat
{
	protected static $instance = null;

	protected $config = array();

	protected function __construct()
	{
		$this->config = DM_Controller_Front::getInstance()->getConfig()->websocket;
	}

	public function getConfig($key = null)
	{
		return is_null($key) ? $this->config : $this->config->$key;
	}

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 发送或回复用户消息 http方式
	 * @param [int] [$columnID] [<公众号ID>]
	 * @param [array] [$member_ids] [<接受消息的用户ID列表>]
	 * @param [string] [$content] [<消息内容>]
	 * @return bool
	 * @throws Zend_Exception
	 * @throws Zend_Log_Exception
	 */
	public function push($memberID,$columnID, array $member_ids, $content,$mid=0)
	{
		$member_ids = array_map("intval", $member_ids);

		$send = array(
			"req" => json_encode(array(
				"uid"=>(int)$memberID,
				"method" => "chat.PubToPer",
				"params" => array(
					'publicId'=> (int)$columnID,
					"recvUidList" => $member_ids,
					"msg" => $content,
					"mid"=>$mid
				),
			))
		);
		$result = DM_Controller_Front::curl($this->config->url, $send);
		$result = json_decode($result, true);
		if (is_null($result) || $result['code'] > 0) {
			$logger = $this->createLogger("socket", "chat");
			$logger->log("Send failed,data:" . json_encode($send), Zend_Log::INFO);
			return false;
		}

		return true;
	}

	/**
	 * 群发消息
	 * @param [int] [$columnID] [<公众号ID>]
	 * @param [array] [$member_ids] [<接受消息的用户ID列表>]
	 * @param [string] [$content] [<消息内容>]
	 * @return bool
	 * @throws Zend_Exception
	 * @throws Zend_Log_Exception
	 */
	public function massPush($memberID,$columnID,$content)
	{
		$send = array(
			"req" => json_encode(array(
				"uid"=>(int)$memberID,
				"method" => "chat.PubToAll",
				"params" => array(
					'publicId'=> (int)$columnID,
					"msg" => $content
				),
			))
		);
		$result = DM_Controller_Front::curl($this->config->url, $send);
		$result = json_decode($result, true);
		if (is_null($result) || $result['code'] > 0) {
			$logger = $this->createLogger("socket", "chat");
			$logger->log("Send failed,data:" . json_encode($send), Zend_Log::INFO);
			return false;
		}
		return true;
	}


	/**
	 * 创建logger对象
	 * @param $dir
	 * @param $filename
	 * @return Zend_Log
	 */
	public function createLogger($dir, $filename)
	{
		$dir = APPLICATION_PATH . "/data/log/{$dir}/";
		!is_dir($dir) && mkdir($dir, 0777, true) && chown($dir, posix_getuid());

		$fp = fopen($dir . date("Y-m-d") . ".{$filename}.log", "a", false);
		$writer = new Zend_Log_Writer_Stream($fp);
		$logger = new Zend_Log($writer);
		return $logger;
	}
}