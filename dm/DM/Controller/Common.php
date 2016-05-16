<?php

/**
 * 控制器公共抽象类
 * 请求方法
 * 错误日志
 * 返回数据
 * 多语言处理
 * 获取配置文件
 * 获取数据适配器
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午2:57
 */
abstract class DM_Controller_Common extends Zend_Controller_Action
{
	/**
	 * Zend_Auth
	 *
	 * @access protected
	 * @var Zend_Auth
	 */
	protected $auth = null;

	/**
	 * 静态地址信息
	 * @var null
	 */
	protected $_static_url = null;

	public function init()
	{
		parent::init();
		$this->getStaticUrl();
		register_shutdown_function(array($this, 'errorHandler'));
	}

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
			$message .= "Params: " . json_encode($this->getRunParamsInfo()) . "\n";
			$log->log($message, Zend_Log::ERR);
		}

		return true;
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


	/**
	 * 获取静态文件url地址
	 * @return null
	 * @throws Exception
	 */
	protected function getStaticUrl()
	{
		if ($this->_static_url === null) {
			$this->_staticUrl = DM_Controller_Front::getInstance()->getStaticUrl();

			$this->view->staticUrl = $this->_static_url;
			$this->view->staticLocal = $this->getConfig()->static->local;
			$this->view->staticVersion = $this->getConfig()->static->version;
		}
		return $this->_static_url;
	}

	/**
	 * 获取HOST
	 */
	protected function getFullHost()
	{
		$schema = $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
		return $schema . '://' . $_SERVER['HTTP_HOST'];
	}


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

	/**
	 * 获取Config对象
	 */
	protected function getConfig()
	{
		return DM_Controller_Front::getInstance()->getConfig();
	}


	/**
	 * 获取数据库适配器
	 *
	 * @param string $db The adapter to retrieve. Null to retrieve the default connection
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDb($db = NULL)
	{
		return DM_Controller_Front::getInstance()->getDb($db);
	}

	/**
	 * 获取从库数据库适配器
	 *
	 * @return Zend_Db_Adapter_Abstract
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

	/**
	 * 获取图片验证码信息
	 *
	 * /get-captcha
	 *
	 * return: {"flag":1,"msg":"ok","data":{"url":"\/captcha\/c6cbb91fd6d9aa6b688ca263bd5ef35a.png","width":"160","height":"50"}}
	 */
	public function getCaptchaAction()
	{
		Zend_Captcha_Word::$V = array("a", "e", "u", "y");
		Zend_Captcha_Word::$VN = array("a", "e", "u", "y", "2", "3", "4", "5", "6", "7", "8", "9");
		Zend_Captcha_Word::$C = array("b", "c", "d", "f", "g", "h", "j", "k", "m", "n", "p", "q", "r", "s", "t", "u", "v", "w", "x", "z");
		Zend_Captcha_Word::$CN = array("b", "c", "d", "f", "g", "h", "j", "k", "m", "n", "p", "q", "r", "s", "t", "u", "v", "w", "x", "z", "2", "3", "4", "5", "6", "7", "8", "9");

		if (empty($this->getConfig()->settings->captcha)) {
			$this->renderFailure($this->getLang()->_('secure.captcha.config.null'));
		}

		$captcha = new Zend_Captcha_Image($this->getConfig()->settings->captcha);
		if (!$captcha->getFont()) {
			$captcha->setFont(APPLICATION_PATH . '/data/fonts/verdana.ttf');
		}
		if (!$captcha->getImgAlt()) {
			$captcha->setImgDir(APPLICATION_PATH . '/../public/captcha/');
		}
		if (!$captcha->getImgUrl()) {
			$captcha->setImgUrl('/captcha/');
		}
		if (!$captcha->getFontSize()) {
			$captcha->setFontSize(28);
		}
		if (!$captcha->getWordlen()) {
			$captcha->setWordlen(4);
		}
		if (!$captcha->getExpiration()) {
			$captcha->setExpiration(60);
		}
		if (!$captcha->getGcFreq()) {
			$captcha->setGcFreq(20);  //删除旧文件
		}

		$this->disableView();

		$id = $captcha->generate();
		$session = $this->getSession();
		$session->Captcha = $captcha->getWord();
		if ($this->getConfig()->settings->captcha->timeout) {
			$session->CaptchaTimeout = time() + $this->getConfig()->settings->captcha->timeout;
		}

		$data = array('url' => $captcha->getImgUrl() . $id . $captcha->getSuffix(), 'width' => $captcha->getWidth(), 'height' => $captcha->getHeight());

		$this->returnJson(self::STATUS_OK, 'ok', $data);
	}

	/**
	 * 验证图片验证码
	 *
	 * 以Captcha字段传回
	 */
	protected function verifyCaptcha()
	{
		$session = $this->getSession();
		$captcha = strtolower(trim($this->_getParam('Captcha', '')));

		//默认半小时有效期
		if (empty($captcha) || empty($session->Captcha) || $session->Captcha !== $captcha || $session->CaptchaTimeout && time() - $session->CaptchaTimeout > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 验证图片验证码，远程调用校验api
	 */
	public function verifyCaptchaAction()
	{
		if (!$this->verifyCaptcha()) {
			$this->returnJson(self::STATUS_FAILURE, $this->getLang()->_('secure.captcha.error'));
		} else {
			$this->returnJson(self::STATUS_OK, 'ok');
		}
	}

	/**
	 * 获取Csrf验证码
	 */
	public function createCsrfCode()
	{
		$session = $this->getSession();
		if (!isset($session->LastCsrfCodeUpdate)) {
			$session->LastCsrfCodeUpdate = 0;
		}
		if (!isset($session->CsrfCode) || time() - $session->LastCsrfCodeUpdate > self::CSRF_TIMEOUT) {
			//默认16位，太长没用
			$length = 16;
			$session->CsrfCode = DM_Helper_Utility::createHash($length);
			$session->LastCsrfCodeUpdate = time();
		}
		return $session->CsrfCode;
	}

	/**
	 * 验证Csrf验证码
	 *
	 * 以CsrfCode字段传回
	 */
	protected function verifyCsrfCode()
	{
		$session = $this->getSession();
		$submitHash = trim($this->_getParam('CsrfCode', ''));
		//默认半小时有效期
		if (empty($submitHash) || empty($session->CsrfCode) || $session->CsrfCode !== $submitHash || time() - $session->LastCsrfCodeUpdate > self::CSRF_TIMEOUT) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 是否ajax请求
	 */
	protected function isAjax()
	{
		if (!$this->getRequest()->isXmlHttpRequest()) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 构建返回数组
	 */
	protected function returnJsonArray($flag = true, $msg = '', $data = NULL, $extra = NULL)
	{
		$arr = array('flag' => $flag, 'msg' => $msg);
		if ($flag < 0 && APPLICATION_ENV != 'production') {
			$params = $this->_request->getParams();
			$arr['param'] = $params;
		}
		if ($data !== NULL) {
			$arr['data'] = $data;
		}
		if ($extra !== NULL) {
			$arr['extra'] = $extra;
		}

		return $arr;
	}

	/**
	 * Render Ajax response with JSON encode
	 */
	protected function returnJson($flag = true, $msg = '', $data = array(), $extra = NULL)
	{
		if ($extra === NULL) {
			//extra默认对象
			$extra = new stdClass();
		}

		if (is_array($data) && empty($data)) {
			$data = new stdClass();
		}

		$arr = $this->returnJsonArray($flag, $msg, $data, $extra);
		return $this->returnResult($arr);
	}

	/**
	 * Render JSON，数组模式
	 *
	 * $result['extra']['forward']='/account/login'; //该定义可以使前台跳转
	 * 通过_callback兼容jsonp请求
	 */
	protected function returnResult($result)
	{
		if (!isset($result['flag'])) $result['flag'] = self::STATUS_FAILURE;
		if (!isset($result['msg'])) $result['msg'] = '';
		if (!isset($result['data'])) $result['data'] = array();
		if (!isset($result['extra'])) $result['extra'] = new stdClass();

		if (isset($result['param'])) {
			$this->escapeVar($result['param']);
		}

		//由于api没有返回值，将输出放到session中以便检验数据
		if (defined('UNIT_TEST_API') && UNIT_TEST_API === true) {
			//防止一个接口有多次输出
			if (empty($_SESSION['UNIT_TEST_API'])) {
				$_SESSION['UNIT_TEST_API'] = json_encode($result);
				throw new Exception('API已返回结果，后面代码无需执行。', 8888);
			}
		} else {
			if (DM_Controller_Front::getInstance()->getHttpRequest()->isGet() && $this->_getParam('_callback')) {
				$callback = $this->_getParam('_callback');
				echo $callback . '(' . json_encode($result) . ');';
			} else {
				echo json_encode($result);
			}

			exit;
		}
	}

	/**
	 * 操作成功
	 */
	protected function renderSuccess($msg = '')
	{
		$this->returnJson(true, $msg);
	}

	/**
	 * 操作失败
	 */
	protected function renderFailure($msg = '')
	{
		$this->returnJson(false, $msg);
	}


	/**
	 * 转义变量
	 *
	 * added by Mark
	 * @param string | array | object $var
	 */
	public function escapeVar(&$var)
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

	/*远程读取页面
	*/
	public function getstringurl($url, $fields = Array())
	{
		return DM_Controller_Front::getInstance()->curl($url, $fields);
	}

	/**
	 * 判断是否是post请求
	 */
	protected final function isPostOutput()
	{
		if (!DM_Controller_Front::getInstance()->getHttpRequest()->isPost()) {
			$this->returnJson(false, $this->getLang()->_("api.base.error.notPostRequest"));
		} else {
			return true;
		}
	}

	protected function isGet()
	{
		if (!DM_Controller_Front::getInstance()->getHttpRequest()->isGet())
			$this->returnJson(false, "请使用GET提交！");
	}


	private function getRunParamsInfo()
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
	 * 当初没想好 傻逼了
	 * @param $model
	 * @param $select
	 * @param $sort
	 * @param bool|true $desc
	 * @param null $primary
	 * @param bool|true $isEscape
	 * @return array
	 */
	protected function listResultsNew($model, $select, $sort, $desc = true, $primary = null, $isEscape = true)
	{
		$results = array();

		//解析查询条件
//		$select = $this->parseListWhere($select);

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
			$isEscape && $this->escapeVar($results);
		}

		return array("Rows" => $results, "Total" => $total);
	}
}
