<?php

/**
 * 控制器公共抽象类，包含如下功能：
 * 1.请求方法
 * 2.错误日志
 * 3.返回数据
 * 4.多语言处理
 * 5.获取配置文件
 * 6.获取数据适配器
 * 7.常用功能模块
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午2:57
 */
abstract class DM_Controller_Common extends Zend_Controller_Action
{
	/**
	 * 配置参数
	 * @var null
	 */
	protected $_config = null;

	/**
	 * 静态地址信息
	 * @var null
	 */
	protected $_static_url = '';

	/**
	 * 请求参数
	 * @var null
	 */
	protected $_param = null;

	/**
	 * response类型
	 * @var array
	 */
	protected $_header_type = array(
		'xml' => 'application/xml',
		'json' => 'application/json',
		'html' => 'text/html',
	);

	public function init()
	{
		parent::init();
		//错误机制
		register_shutdown_function(array($this, 'errorHandler'));
		//配置文件
		$this->getConfig();
		//静态地址
		$this->getStaticUrl();
	}

	/**
	 * 获取静态文件url地址
	 * @return null
	 * @throws Exception
	 */
	protected function getStaticUrl()
	{
		isset($this->_config['static']['url']) && $this->_static_url = $this->_config['static']['url'];
	}

	/**
	 * 获取Config
	 */
	protected function getConfig()
	{
		$this->_config = DM_Controller_Front::getInstance()->getConfig()->toArray();
	}

	/**
	 * 获取数据库适配器
	 * @param null $db
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDb($db = null)
	{
		return DM_Controller_Front::getInstance()->getDb($db);
	}

	/**
	 * 获取从库数据库适配器
	 * @return Zend_Db_Adapter_Abstract
	 * @throws Exception
	 */
	public function getHashSlaveDB()
	{
		return DM_Controller_Front::getInstance()->getHashSlaveDB();
	}

	/**
	 * 关闭视图
	 */
	protected function disableView()
	{
		$this->_helper->viewRenderer->setNoRender();
	}

	/**--------------------------------------------response----------------------------------------------**/
	/**
	 * 输出返回数据
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type 返回类型 JSON XML
	 * @param integer $code HTTP状态
	 * @return void
	 */
	protected function response($data, $type = '', $code = 200)
	{
		$this->sendHttpStatus($code);
		exit($this->encodeData($data, strtolower($type)));
	}

	/**
	 * 输出错误数据
	 * 支持重写
	 * @param $code
	 */
	protected function responseError($code)
	{
		$this->sendHttpStatus($code);
		exit();
	}

	/**
	 * 编码数据
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type 返回类型 JSON XML
	 * @return string
	 */
	protected function encodeData($data, $type = '')
	{
		$type = strtolower($type);
		switch ($type) {
			case "json":
				$data = json_encode($data);
				break;
			case "xml":
				$data = $this->xmlEncode($data);
				break;
			case "php":
				$data = serialize($data);
				break;
			case "html":
				break;
			default:
				return $data;
				break;
		}

		$this->setContentType($type);
		return $data;
	}

	/**
	 * XML编码
	 * @param mixed $data 数据
	 * @param string $root 根节点名
	 * @param string $item 数字索引的子节点名
	 * @param string $attr 根节点属性
	 * @param string $id 数字索引子节点key转换的属性名
	 * @param string $encoding 数据编码
	 * @return string
	 */
	protected function xmlEncode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
	{
		if (is_array($attr)) {
			$_attr = array();
			foreach ($attr as $key => $value) {
				$_attr[] = "{$key}=\"{$value}\"";
			}
			$attr = implode(' ', $_attr);
		}
		$attr = trim($attr);
		$attr = empty($attr) ? '' : " {$attr}";
		$xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
		$xml .= "<{$root}{$attr}>";
		$xml .= data_to_xml($data, $item, $id);
		$xml .= "</{$root}>";
		return $xml;
	}

	/**
	 * 设置页面输出的CONTENT_TYPE和编码
	 * @access public
	 * @param string $type content_type 类型对应的扩展名
	 * @param string $charset 页面输出编码
	 * @return void
	 */
	public function setContentType($type, $charset = 'UTF-8')
	{
		if (headers_sent()) return;
		header('Content-Type: ' . $this->_header_type[$type] . '; charset=' . $charset);
	}

	/**
	 * 发送Http状态信息
	 * @param $code
	 */
	protected function sendHttpStatus($code)
	{
		static $_status = array(
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',
			// Success 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily ',  // 1.1
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			// 306 is deprecated but reserved
			307 => 'Temporary Redirect',
			// Client Error 4xx
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'Timestamp Exceeded',//timestamp 超过15分钟
			419 => 'Request Exceeded',//请求次数受限
			420 => 'Decrypt Failed',//解密错误
			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			509 => 'Bandwidth Limit Exceeded'
		);
		if (isset($_status[$code])) {
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
			// 确保FastCGI模式下正常
			header('Status:' . $code . ' ' . $_status[$code]);
		}
	}

	/**-------------------------------------------------基础功能------------------------------------------------------**/
	/**
	 * 创建日志对象
	 * @param $path
	 * @param $filename
	 * @return Zend_Log
	 */
	protected function createLogger($path, $filename)
	{
		$dir = APPLICATION_PATH . "/data/log/{$path}/";
		!is_dir($dir) && mkdir($dir, 0777, true) && chown($dir, posix_getuid());

		$fp = fopen($dir . date("Y-m-d") . ".{$filename}.log", "a", false);
		$writer = new Zend_Log_Writer_Stream($fp);
		$logger = new Zend_Log($writer);
		return $logger;
	}

	/**
	 * 常用列表分页 支持page 和last_id两种模式
	 * @param $model
	 * @param $select
	 * @param $sort
	 * @param bool|true $desc
	 * @param null $primary
	 * @return array
	 */
	protected function listResult($model, $select, $sort, $desc = true, $primary = null)
	{
		$results = array();

		//解析查询条件
		$select = $this->listParseWhere($select);

		//关闭视图
		$this->disableView();

		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

		//总条数
		$total = $model->getAdapter()->fetchOne($countSql);
		if ($total) {
			//排序
			$select->order("{$sort} " . ($desc ? "desc" : "asc"));

			//分页 支持last_id参数
			if (isset($this->_param['page'])) {
				$select->limitPage($this->_param['page'], $this->_param['pagesize']);
			} else {
				is_null($primary) && $primary = $model->getPrimary();
				if ($this->_param['last_id'] > 0) {
					$select->where("{$primary} " . ($desc ? "<" : ">") . "?", $this->_param['last_id']);
				}
				$select->limit($this->_param['pagesize']);
			}

			//列表
			$results = $model->fetchAll($select)->toArray();
		}

		return array("Rows" => $results, "Total" => $total);
	}

	/**
	 * 解析查询条件 管理后台模块可以重写
	 * @param $select
	 * @return mixed
	 */
	protected function listParseWhere($select)
	{
		return $select;
	}

	/**
	 * 转义变量
	 * @param $var
	 */
	protected function escapeVar(&$var)
	{
		if (!empty($var)) {
			if (is_array($var)) {
				array_walk_recursive($var, array($this, 'escapeVar'));
			} elseif (is_string($var)) {
				$var = htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
			} elseif (is_object($var)) {
				if (method_exists($var, 'toArray')) {
					$arr = $var->toArray();
					$this->escapeVar($arr);
					foreach ($arr as $k => $v) {
						$var->$k = $v;
					}
				}
			}
		}
	}

	/**----------------------------------------------------------错误处理机制--------------------------------------------**/
	/**
	 * 错误日志机制
	 * @return bool
	 * @throws Zend_Log_Exception
	 */
	public function errorHandler()
	{
		$error = error_get_last();
		if (!empty ($error ['type'])) {
			$log = $this->createLogger("process", "error");
			$message = "ERROR detect " . $this->errorType($error['type']) . ": " . $error['message'] . "\nIn file  " . $error['file'] . "(" . $error['line'] . ")\n";
			$message .= "Params: " . json_encode($this->getParamsInfo()) . "\n";
			$log->log($message, Zend_Log::ERR);
		}

		return true;
	}

	/**
	 * 获取请求参数
	 * @return array
	 */
	protected function getParamsInfo()
	{
		$info = $this->_getAllParams();
		if (isset($info['error_handler'])) {
			unset($info['error_handler']);
		}
		if (isset($_SERVER['REQUEST_URI'])) {
			$info['uri'] = $_SERVER['REQUEST_URI'];
		}
		if (isset($_SERVER['HTTP_REFERER'])) {
			$info['referer'] = $_SERVER['HTTP_REFERER'];
		}

		return $info;
	}

	/**
	 * 获取错误类型字符描述
	 * @param $type
	 * @return string
	 */
	protected function errorType($type)
	{
		switch ($type) {
			case E_ERROR: // 1 //
				return 'E_ERROR';
			case E_WARNING: // 2 //
				return 'E_WARNING';
			case E_PARSE: // 4 //
				return 'E_PARSE';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING : // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return "";
	}

	/**-------------------------------------------------------多语言处理-----------------------------------------**/
	/**
	 * 获取翻译对象
	 * @param null $locale
	 * @return Zend_Translate
	 * @throws DM_Exception_Lang
	 * @throws Exception
	 */
	protected function getLang($locale = NULL)
	{
		return DM_Controller_Front::getInstance()->getLang($locale);
	}

	/**
	 * 获取当前语言
	 * @return mixed
	 */
	protected function getLocale()
	{
		return DM_Controller_Front::getInstance()->getLocale();
	}
}
