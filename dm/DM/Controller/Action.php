<?php

/**
 * DM\Controller\Action
 *
 * @since 2014/05/19
 */
abstract class DM_Controller_Action extends Zend_Controller_Action
{
	/**
	 * Zend_Auth
	 *
	 * @access protected
	 * @var Zend_Auth
	 */
	protected $auth = null;

	/**
	 * StaticUrl
	 *
	 * @access protected
	 * @var String
	 */
	protected $_staticUrl;

	/**
	 * session默认命名空间
	 * @var string
	 */
	const SESSION_NAMESPACE = 'default';

	/**
	 * 返回状态码总体说明：
	 * >=0 表示返回结果正常
	 * <0 表示错误方式
	 */
	// 操作成功
	const STATUS_OK = 1;
	// 操作失败
	const STATUS_FAILURE = -1;
	// 需登录(未登录或者session失效)
	const STATUS_NEED_LOGIN = -100;
	// HTTP/1.1 403 Forbidden
	const STATUS_FORBIDDEN = -403;

	//非好友关系
	const STATUS_NEED_FRIENDS = -108;

	
	//群组已解散
	const STATUS_GROUP_DISSOLVE = -300;
	
	//话题不存在
	const STATUS_TOPIC_NOTEXIST = -109;
	
	const CHECK_PAY_PWD_SUCCESS = 1;
	
	const CHECK_PAY_PWD_FAILURE = -1;
	
	const CHECK_PAY_PWD_FAILURE_ONE = -2;//错误一次
	
	const CHECK_PAY_PWD_FAILURE_TWO = -3;//错误两次
	
	const CHECK_PAY_PWD_FAILURE_FREEZE = -4;//冻结
	
	const STATUS_BALANCE_NOT_ENOUGH = -5;//余额不足
	
	//钱包状态
	const WALLET_STATUS_OK = 1;//正常
	
	const WALLET_STATUS_INVALID = 0;//无效
	
	const WALLET_STATUS_FREEZE = 2;//冻结

	//----------------------------------------
	//csrf验证码有效期 跟API状态返回值无关
	const CSRF_TIMEOUT = 1800;

	//异常报错后的日志保存
	const ERROR_LOG_SERVICE = "error";

	public function init()
	{
		parent::init();

		//记录错误
		register_shutdown_function(array($this, 'dmErrorHandler'));
	}

	/**
	 * 获取静态文件url地址
	 */
	protected function getStaticUrl()
	{
		if ($this->_staticUrl === null) {
			$this->_staticUrl = DM_Controller_Front::getInstance()->getStaticUrl();

			$this->view->staticUrl = $this->_staticUrl;
			$this->view->staticLocal = $this->getConfig()->static->local;
			$this->view->staticVersion = $this->getConfig()->static->version;
		}
		return $this->_staticUrl;
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
	 * 判断接口是否调用成功
	 *
	 * @param array $result json接口数据
	 * @return boolean
	 */
	protected function isSuccess($result)
	{
		return isset($result['flag']) && $result['flag'] >= 0;
	}

	/**
	 * 获取翻译对象
	 *
	 * @param string $locale
	 */
	protected function getLang($locale = NULL)
	{
		return DM_Controller_Front::getInstance()->getLang($locale);
	}

	/**
	 * 获取当前语言
	 */
	protected function getLocale()
	{
		return DM_Controller_Front::getInstance()->getLocale();
	}

	/**
	 * 获取Session对象
	 */
	protected function getSession()
	{
		return DM_Controller_Front::getInstance()->getSession(static::SESSION_NAMESPACE);
	}

	/**
	 * 获取Config对象
	 */
	protected function getConfig()
	{
		return DM_Controller_Front::getInstance()->getConfig();
	}

	/**
	 * 判断是否登录
	 */
	protected function isLogin()
	{
		return DM_Module_Auth::getInstance()->setSession($this->getSession())->isLogin();
	}

	/**
	 * 获取当前登录的用户信息
	 *
	 * @return DM_Model_Row_Member
	 */
	protected function getLoginUser()
	{
		return DM_Controller_Front::getInstance()->getAuth()->getLoginUser();
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
	 * 判断是否登录并进行跳转
	 *
	 * @param string $url
	 * @param string $from
	 */
	protected function checkAuth($url, $from = null)
	{
		if (!$this->isLogin()) {
			// store the current uri in session for redirecting on login
			if (is_null($from)) {
				$this->session->loginRedirectUri = $_SERVER['REQUEST_URI'];
			} else {
				$this->session->loginRedirectUri = $from;
			}

			// redirect to login page
			if ($this->_request->isXmlHttpRequest()) {
				$this->returnJson(-100, $this->getLang()->_("api.base.error.notLogin"));
			} else {
				$this->_redirect($url);
			}
		}
	}

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
		
		if(is_array($data) && empty($data)){
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

	/**
	 * 异常报错后的日志保存
	 */
	protected function logExceptionInfo(Exception $e)
	{
		$log = DM_Module_Log::create(self::ERROR_LOG_SERVICE);

		$log->add("[IP" . DM_Controller_Front::getInstance()->getClientIp() . "]发现异常：" . $e->getMessage() . PHP_EOL . "Params: " . json_encode($this->getRunParamsInfo()) . "\n" . 'INFO: ' . $e->getFile() . '(' . $e->getLine() . ')' . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
	}

	/**
	 * 错误日志一般是写在application/data/log文件夹中 请确保可写
	 *
	 * @return boolean
	 */
	public function dmErrorHandler()
	{
		//$errno, $errstr, $errfile, $errline
		// PHP错误处理
		$error = error_get_last();
		if (!empty ($error ['type'])) {
			//将常见错误类型转换为文字
			$error ['type'] = str_replace(array(4096, 2048, 1024, 512, 256, 8, 4, 2, 1), array('E_DEPRECATED', 'E_STRICT', 'E_USER_NOTICE', 'E_USER_WARNING', 'E_USER_ERROR', 'E_NOTICE', 'E_PARSE', 'E_WARNING', 'E_ERROR'), $error ['type']);

			$log = DM_Module_Log::create(self::ERROR_LOG_SERVICE);
			$message = "ERROR detect " . $error['type'] . ": " . $error['message'] . "\nIn file  " . $error['file'] . "(" . $error['line'] . ")\n";
			$message .= "Params: " . json_encode($this->getRunParamsInfo()) . "\n";
			$log->add($message);
		}

		return true;
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

}
