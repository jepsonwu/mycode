<?php
if (!class_exists("JPush"))
	require_once 'JPush/JPush.php';

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-1-26
 * Time: 上午9:29
 */
class DM_Third_Jpush
{

	private $_app_key;

	private $_master_secret;

	//日志路径
	private $_log_path = '';

	//失败请求最大重试次数
	private $_retry_times = 3;

	//实例
	private $_client;

	protected static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$config = DM_Controller_Front::getInstance()->getConfig()->jpush;
		$this->_app_key = $config->app_key;
		$this->_master_secret = $config->master_secret;

		$this->_log_path = APPLICATION_PATH . "/data/log/" . date("Y-m-d") . ".jpush.log";

		try {
			$this->_client = new JPush($this->_app_key, $this->_master_secret, $this->_log_path, $this->_retry_times);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * 获取JPush client 对象自定义推送
	 * @return mixed
	 */
	public function client()
	{
		return $this->_client;
	}

	/**
	 * 所有平台推送消息
	 * @param $alert
	 * @param $content
	 * @param $title
	 * @param string $type
	 * @param array $extra
	 * @return array|object
	 * @throws Exception
	 */
	public function send($alert, $content, $title, $type = 'string', $extra = array())
	{
		try {
			$response = $this->_client
				->push()
				->setPlatform('all')
				->addAllAudience()
				->setNotificationAlert($alert)
				->setMessage($content, $title, $type, $extra)
				->send();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $response;
	}

	/**
	 * 短信推送
	 */
	public function smsSend()
	{

	}

	/**
	 * 定时发送
	 */
	public function timingSend($time, $alert)
	{
		try {
			$payload = $this->_client
				->push()
				->setPlatform('ios', 'android')
				->addAllAudience()
				->setNotificationAlert($alert)
				->setMessage(array())
				->build();

			$response = $this->_client->schedule()->createSingleSchedule("定时任务", $payload, array("time" => $time));
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return $response;
	}

}