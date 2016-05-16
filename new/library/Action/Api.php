<?php

/**
 *  API
 * @author Mark
 */
class Action_Api extends DM_Api_Account
{
	//校验之后的请求数据
	protected $_param = array();

	//返回数据对称加密key
	protected $_return_encrypt_key;

	public function init()
	{
		parent::init();
		header('Content-type: application/json');
		$version = $this->_request->getParam('currentVersion', '1.0.0');
		$platform = intval($this->_request->getParam('platform', 0));
		$contorllerName = $this->_request->getControllerName();
		$actionName = $this->_request->getActionName();
		if ($platform > 0 && version_compare($version, '2.2.3') < 0 && $contorllerName != 'system' && $contorllerName != 'user') {
			//$this->logoutCall();
			$this->returnJson(parent::STATUS_FAILURE, '版本太低，请升级至2.2.3版本！');
		}

		//校验参数
		$this->checkParam();
	}

	/**
	 * 请求方式校验
	 * 校验参数
	 */
	protected function checkParam()
	{
		$action = explode("-", $this->_getParam('action'));
		$action_conf = array_shift($action);
		$action_conf = $action_conf . implode("", array_map("ucfirst", $action)) . 'Conf';
		$action_conf =& $this->$action_conf;

		//请求方式校验
		if (isset($action_conf['method'])) {
			$this->_request->getMethod() !== strtoupper($action_conf['method']) &&
			$this->failReturn("Not Allowed Method", -405);//应该是报http错误 anyways
			unset($action_conf['method']);
		}

		//根据请求类型获取参数  避免CSRF漏洞
		$all_param = array();
		switch ($this->_request->getMethod()) {
			case "PUT":
				break;
			case "DELETE":
				break;
			case "POST":
				$all_param = $this->_request->getPost();
				break;
			case "GET":
				$all_param = $this->_request->getQuery();
				break;
		}

		//是否需要解密 加密方案：encrypt：rsa|  data  PKCS1
		if (isset($action_conf['encrypt'])) {
			!isset($all_param['data']) && $this->failReturn("Illegal Request", -407);
			switch (strtoupper($action_conf['encrypt'])) {
				case "RSA":
					$rsa = DM_Authorize_Rsa::getInstance();
					$private_key = DM_Controller_Front::getInstance()->getConfig()->app->private_key;

					$all_param = $rsa->decrypt($all_param['data'], $private_key);
					if ($all_param === false)
						$this->failReturn("Illegal Request", -407);
					$all_param && $all_param = json_decode($all_param, true);

					//key
					if (isset($all_param['encrypt_key']) && $all_param['encrypt_key'])
						$this->_return_encrypt_key = $all_param['encrypt_key'];
					else
						$this->failReturn("Illegal Request", -407);
					break;
			}
		}
		//是否需要对解密的值进行签名校验 sign=true

		if (isset($action_conf)) {
			$param = DM_Helper_Filter::autoValidation($all_param, $action_conf);

			if (is_string($param))
				$this->failReturn(empty($param) ? "Bad Request" : $param, -400);
			else
				$this->_param = $param;
		}
	}

	/**
	 * API失败返回函数
	 * @param $msg
	 * @param null $code
	 */
	protected function failReturn($msg, $code = null)
	{
		$this->returnJson(is_null($code) ? parent::STATUS_FAILURE : $code, $msg);
	}

	/**
	 * API成功返回函数 方便以后修改数据返回格式
	 * @param $data
	 */
	protected function succReturn($data)
	{
		if ($this->_return_encrypt_key) {
			$mcrypt = new Model_CryptAES($this->_return_encrypt_key);
			$data = $mcrypt->encrypt(json_encode($data));

			$data = array(
				'data' => $data
			);
		}

		$this->returnJson(parent::STATUS_OK, '', $data);
	}

	public function checkDeny()
	{
		$memberID = $this->memberInfo->MemberID;
		$memberModel = new DM_Model_Account_Members();
		$mInfo = $memberModel->getOne($memberID);
		if (empty($mInfo) || $mInfo['Status'] == 0) {
			$this->returnJson(parent::STATUS_FAILURE, '');
		}
	}

	/**
	 * list获取结果集
	 * @param $model
	 * @param $select
	 * @param $sort
	 * @param bool|true $desc
	 * @param null $primary
	 * @return array
	 */
	protected function listResults($model, $select, $sort, $desc = true, $primary = null, $isEscape = true)
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

		return $results;
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
}