<?php

/**
 * 单例，共用资源获取器
 *
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Controller_Front
{
	protected static $instance = NULL;

	/**
	 * Zend_Auth
	 *
	 * @var Zend_Auth
	 */
	private $auth = null;

	/**
	 * Application session namespace
	 *
	 * @var Zend_Session_Namespace
	 */
	private $session = null;

	/**
	 * @var Zend_Translate
	 */
	private $translate = null;

	/**
	 * @var Zend_Config
	 */
	private $config = null;

	/**
	 * Associative array containing all configured salve db's
	 *
	 * @var array
	 */
	protected $_slaves = array();

	/**
	 * slave键值匹配
	 */
	protected $_slaveKVs = array();

	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_slaveHashed = NULL;

	/**
	 * 是否含有从库
	 * @var bool
	 */
	protected $_hasSlaveDB = NULL;


	/**
	 * @return Zend_Application_Bootstrap_BootstrapAbstract
	 */
	public function getBootstrap()
	{
		return Zend_Controller_Front::getInstance()->getParam('bootstrap');
	}

	/**
	 * @return Zend_Application
	 */
	public function getApplication()
	{
		return $this->getBootstrap()->getApplication();
	}

	/**
	 * @return Zend_Controller_Request_Http
	 */
	public function getHttpRequest()
	{
		return Zend_Controller_Front::getInstance()->getRequest();
	}

	/**
	 * 获取IP
	 *
	 * 有时cli调用，不存在情况下返回空字符串
	 *
	 * @return string
	 */
	public function getClientIp($checkProxy = true)
	{
		$ip = $this->getHttpRequest()->getClientIp($checkProxy = true);
		if ($ip === NULL) {
			return '';
		}

		return $ip;
	}

	/**
	 * @return Zend_Config
	 */
	public function getConfig()
	{
		if ($this->config === NULL) {
			$this->config = new Zend_Config($this->getBootstrap()->getOptions());
		}
		return $this->config;
	}

	/**
	 * 获取翻译对象
	 *
	 * Zend_Translate通过__call转发的，自动支持参数
	 *
	 * zend translate有坑，如果是不存在的语言这里直接返回设置的默认语言
	 * 配置总这个选项resources.translate.locale
	 *
	 * @param string $locale
	 * @return Zend_Translate
	 */
	public function getLang($locale = NULL)
	{
		if ($this->translate !== NULL) {
			if ($locale) $this->translate->setLocale($locale);
			return $this->translate;
		}

		$lang = NULL;
		if ($locale === NULL) {
			if (!empty($_COOKIE['language'])) {
				$lang = $_COOKIE['language'];
			} else {
				$blang = $this->getBrowserLang();
				if ($blang) $lang = $blang;
			}

			//通过url参数强制修改
			$qlang = isset($_REQUEST['language']) ? trim($_REQUEST['language']) : NULL;
			if ($qlang === NULL) $qlang = $this->getHttpRequest()->getParam("language", NULL);
			if ($qlang && $lang != $qlang) {
				$lang = $qlang;
				setcookie('language', $lang, time() + 86400 * 1000, '/');
			}

		} else {
			$lang = $locale;
		}
		$config = $this->getConfig()->resources->translate;
		if (!$config) {
			throw new DM_Exception_Lang('applicationi.ini translate section is needed.');
		}
		//默认语言
		if ($lang === NULL) {
			$lang = $config->locale;
		}

		$this->translate = new DM_Helper_Translate($this->getBootstrap()->getResource('translate'));

		//其他没有设置的语言改为系统默认语言
		$langAll = $this->translate->getAdapter()->getMessages('all');
		if (isset($langAll[$lang])) {
			$this->translate->setLocale($lang);
		} else {
			if (!$this->getConfig()->resources->translate->locale) {
				throw new Exception("application.ini 语言配置 resources.translate.locale 未设置。");
			}
			//设置为默认语言
			$this->translate->setLocale($this->getConfig()->resources->translate->locale);
		}

		return $this->translate;
	}

	/**
	 * 清除缓存文件
	 *
	 * 缓存目录在同个文件的情况下是全部清除的
	 *
	 * @return boolean
	 */
	public function cleanCache()
	{
		return $this->getCache()->clean();
	}

	/**
	 * 清除多语言的缓存文件
	 * @return boolean
	 */
	public function cleanLangCache()
	{
		return Zend_Translate_Adapter::getCache()->clean();
	}

	/**
	 * 获取系统使用的当前语言
	 */
	public function getLocale()
	{
		return $this->getLang()->getLocale();
	}

	/**
	 * 获取登录验证Auth对象
	 *
	 * @return DM_Module_Auth
	 */
	public function getAuth()
	{
		return DM_Module_Auth::getInstance();
	}

	/**
	 * 是否登录状态
	 */
	public function isLogin()
	{
		return self::getAuth()->isLogin();
	}

	/**
	 * 获取当前登录用户
	 *
	 * @return DM_Model_Row_Member
	 */
	public function getLoginUser()
	{
		return self::getAuth()->getLoginUser();
	}

	/**
	 * 获取系统cache配置
	 *
	 * @param string $cacheName 配置文件中的名称
	 * @return Zend_Cache_Core
	 */
	public function getCache($cacheName = 'default')
	{
		//获取缓存
		//print_r(DM_Controller_Front::getInstance()->getCache()->save('test', 'hello'));
		//print_r(DM_Controller_Front::getInstance()->getCache()->load('hello'));

		return $this->getBootstrap()->getResource('cachemanager')->getCache($cacheName);
	}

	/**
	 * 获取默认语言
	 */
	protected function getBrowserLang()
	{
		$langHead = '';
		//zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$langStr = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langStr = str_ireplace(array(';'), array(','), $langStr);
			$langInfo = explode(',', $langStr);
			//print_r($langInfo);die();

			foreach ($langInfo as $lang) {
				$lang = trim($lang);
				$langInfo = explode('-', $lang);
				if (!empty($langInfo[0])) {
					return trim($langInfo[0]);
				} else {
					return '';
				}
			}
		}
		return NULL;
	}


	/**
	 * 获取枚举字段配置文件的信息
	 * @param  $name
	 * @return mixed
	 */
	public function getIniInfo($name)
	{
		$reg_name = 'enum_' . $name;
		if (!Zend_Registry::isRegistered($reg_name)) {
			$info = new Zend_Config_Ini(APPLICATION_PATH . '/configs/enum_option.ini', $name);
			Zend_Registry::set($reg_name, $info->toArray());
		}
		return Zend_Registry::get($reg_name);
	}

	/**
	 * 获取数据库适配器
	 *
	 * @param string $db The adapter to retrieve. Null to retrieve the default connection
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getDb($db = NULL)
	{
		return $this->getBootstrap()->getResource('multidb')->getDb($db);
	}

	/**
	 * 获取数据库适配器
	 *
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getHashSlaveDB()
	{
		if ($this->_slaveHashed !== NULL) {
			return $this->_slaveHashed;
		}

		if (empty($this->_slaves)) {
			$this->_slaves = $this->getBootstrap()->getResource('multidb')->getSlaveDbs();
			$this->_slaveKVs = array_keys($this->_slaves);
		}

		//不存在从数据库 指向master
		if (empty($this->_slaves)) {
			$this->_slaveHashed = $this->getDb();
			DM_Module_Log::create('error')->add('Slave: No slave, use master. Param: ' . json_encode($this->getHttpRequest()->getParams()));
			return $this->_slaveHashed;
		}

		$hashValue = 0;
		$clientip = $this->getClientIp();
		if ($clientip) {
			$iplong = abs(ip2long($clientip));
			$hashValue = $iplong;
		} else {
			$pid = getmypid();
			if (!$pid) {
				throw new Exception('Slave: Failed to fetch Server pid.');
			}
			$hashValue = $pid;
		}
		$this->_slaveHashed = $this->_slaves[$this->_slaveKVs[$hashValue % count($this->_slaveKVs)]];
		//print_r($this->_slaves); print_r($this->_slaveKVs);die();
		//手工触发连接，用于判断是否可连
		$this->_slaveHashed->getConnection();
		if (!$this->_slaveHashed->isConnected()) {
			$config = $this->_slaveHashed->getConfig();
			if (isset($config['password'])) $config['password'] = '******';
			DM_Module_Log::create('error')->add('Slave: fetch Slave failed，use master。Slave param: ' . json_encode($config));
			$this->_slaveHashed = $this->getDb();
		}

		return $this->_slaveHashed;
	}

	/**
	 * 数据库是否有配置从库
	 *
	 * @return boolean
	 */
	public function hasSlaveDB()
	{
		if ($this->_hasSlaveDB === NULL) {
			$slaves = $this->getBootstrap()->getResource('multidb')->getSlaveDbs();
			if (!empty($slaves)) {
				$this->_hasSlaveDB = true;
			} else {
				$this->_hasSlaveDB = false;
			}
		}

		return $this->_hasSlaveDB;
	}

	/**
	 * curl
	 */
	public static function curl($url, $fields, $ispost = true)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		if ($ispost) curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36');

		//禁止ssl验证
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new Zend_Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new Zend_Exception("http status code exception :{$httpStatusCode}", 0);
			}
		}
		curl_close($ch);
		return $response;
	}

	/**
	 * 获取layout对象
	 * @return Zend_Layout
	 */
	public function getLayout()
	{
		return Zend_Layout::getMvcInstance();
	}

	/**
	 * 获取静态文件url地址
	 */
	public function getStaticUrl()
	{
		$url = $this->getConfig()->static->url;
		if (!$url) {
			throw new Exception('配置文件static.url部分并未设置。');
		}
		return $this->absoluteUrl($url);
	}

	/**
	 * 获取静态文件url地址
	 */
	public function getStaticLocal()
	{
		$url = $this->getConfig()->static->local;
		if (!$url) {
			throw new Exception('配置文件static.local部分并未设置。');
		}
		return $this->absoluteUrl($url);
	}

	protected function absoluteUrl($url)
	{
		$request = DM_Controller_Front::getHttpRequest();
		return (stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0) ? $url : $request->getScheme() . '://' . $request->getHttpHost() . $url;
	}

	/**
	 * 获取单实例
	 *
	 * @return DM_Controller_Front
	 */
	public static function getInstance()
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
