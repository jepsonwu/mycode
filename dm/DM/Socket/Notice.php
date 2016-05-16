<?php

require_once "WebSocket/Client.php";

/**
 * socket 通知模块
 * User: jepson <jepson@duomai.com>
 * Date: 16-4-8
 * Time: 下午4:34
 */
class DM_Socket_Notice
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
	 * 发送通知消息 http方式
	 * @param array $member_ids
	 * @param array $data
	 * @return bool
	 * @throws Zend_Exception
	 * @throws Zend_Log_Exception
	 */
	public function push(array $member_ids, array $data)
	{
		$member_ids = array_map("intval", $member_ids);

		$send = array(
			"req" => json_encode(array(
				"method" => "notice.push",
				"params" => array(
					"receiveUids" => $member_ids,
					"content" => $data,
				),
			))
		);
		$result = DM_Controller_Front::curl($this->config->url, $send);
		$result = json_decode($result, true);
		if (is_null($result) || $result['code'] > 0) {
			$logger = $this->createLogger("socket", "notice");
			$logger->log("Send failed,data:" . json_encode($send), Zend_Log::INFO);
			return false;
		}

		return true;
	}

	/**
	 * 发送通知消息 socket方式 消息入队列
	 * @param $name
	 * @param $send_to
	 * @param $data
	 * @return bool
	 */
	public function put($name, $send_to, $data)
	{
		$data = json_encode(array("uid" => 9001, "method" => "notice.push", "receiveUids" => $send_to, "content" => $data));
		$logger = $this->createLogger("counsel", "notice");
		$logger->log($data, Zend_Log::INFO);
		return true;
		//return DM_HttpSQS_Client::getInstance()->put($name, $data);
	}

	/**
	 * 后台任务 发送通知
	 * @return bool
	 * @throws Zend_Log_Exception
	 */
	public function send()
	{
		$logger = $this->createLogger("socket", "notice");
		$sqs_key = "web_socket_notice";

//		$data = json_encode(array("uid" => 9001, "method" => "notice.push", "receiveUids" => "1", "content" => "test notice"));
//		DM_HttpSQS_Client::getInstance()->put($sqs_key, $data);

		try {
			//建立连接
			$client = new Client($this->config->url);

			//统计
			$failed = $total = 0;
			while (true) {
				//从队列取待发送信息
				$data = DM_HttpSQS_Client::getInstance()->get($sqs_key);
				if ($data) {
					try {
						$client->send($data);
						$result = $client->receive();

						//判断是否发送成功
						$response = json_decode($result, true);
						if (isset($response['code']) && $response['code'] >= 0) {
							echo "success";
						} else {
							throw new Exception(is_null($response) ? $result : $response);
						}

						//统计成功次数
						$total++;
						if ($total >= 1000) {
							$logger->log("Send success,total:{$total}", Zend_Log::INFO);
							$total = 0;
						}
						sleep(2);
					} catch (Exception $e) {
						$logger->log("Send data error:" . $e->getMessage() . ",data:{$data}", Zend_Log::INFO);
						//入队列
						DM_HttpSQS_Client::getInstance()->put($sqs_key, $data);

						//失败次数统计  等待发送
						$failed++;
						sleep(round($failed / 10) * 20);
					}
				} else {
					$logger->log("No data to send", Zend_Log::INFO);
					sleep(10);
				}
			}

		} catch (Exception $e) {
			$logger->log("Socket create error:" . $e->getMessage(), Zend_Log::ERR);
		}
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