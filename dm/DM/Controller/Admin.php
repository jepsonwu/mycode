<?php

/**
 * Admin通用控制�?
 *
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Controller_Admin extends DM_Controller_Action
{

	/**
	 * session命名空间
	 * @var string
	 */
	const SESSION_NAMESPACE = 'admin';

	/**
	 * 用户数据�?
	 * @var string
	 */
	protected $_user_db = '';

	/**新增编辑 验证之后的数�?
	 * @var array
	 */
	protected $_param = array();

	/**
	 * 当前管理用户登陆信息
	 * @var array
	 */
	protected $_auth_info = array();

	public function init()
	{
		parent::init();

		//兼容性代码，防止后台调用API model判断是否登录�?
		DM_Controller_Front::getInstance()->getAuth()->setSession($this->getSession());

		$this->getStaticUrl();
		// 配置信息
		$this->config = $this->getConfig();
		$front = Zend_Controller_Front::getInstance();
		$plugin = $front->getPlugin('Zend_Controller_Plugin_ErrorHandler');
		$plugin->setErrorHandlerModule($this->_request->getModuleName());
		$this->auth = Zend_Auth::getInstance();

		$this->auth->setStorage(new Zend_Auth_Storage_Session('Zend_Auth_Admin'));
		$this->session = $this->getSession();

		//不需要判断的url
		$notCheckArray = array('index_login', 'error_error', 'duomai_login', 'index_yzm', 'index_logout', 'duomai_logout');
		if (!in_array($this->getRequest()->getControllerName() . '_' . $this->getRequest()->getActionName(), $notCheckArray)) {
			$this->checkAuth('/admin/index/login');
//判断IP 2015.02暂时取消，很多人反馈不方便�?�已有七天登录限�?
//             $request_ip = $this->_request->getClientIp();
//             if($this->session->login_ip != $request_ip){
//                 $this->auth->clearIdentity();
//                 $this->checkAuth('/admin/index/login');
//             }
			$this->_auth_info = $authInfo = $this->auth->getIdentity();
			if ($authInfo) {
				//延长后台登录超时 默认�?周，现在�?出太频繁�?
				if (time() - $authInfo->Lasttime > 86400 * 7) {
					$this->auth->clearIdentity();
				} else {
					$authInfo->Lasttime = time();
					$this->adminInfo = $authInfo;
				}
			} else {
				$this->auth->clearIdentity();
				$this->checkAuth('/admin/index/login');
			}

			if (!$this->checkPrivilege($this->_request->getControllerName(), $this->_request->getActionName())) {
				if ($this->_request->isXmlHttpRequest()) {
					$this->returnJson(0, '您无权操�?');
				} else {
					header('Content-type:text/javascript;Charset=utf-8');
					exit('无权查看');
				}
			}
		}

		//获取用户数据库名�?
		$this->_user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
		//前台控制器名�?
		$this->view->CONTROLLER = str_replace("-", "_", $this->_request->getControllerName());
	}

	/**
	 * 权限判断
	 * @param 主标�? $main_sign
	 * @param 副标�? $sub_sign
	 * @return boolean
	 */
	protected function checkPrivilege($main_sign, $sub_sign)
	{
		$rolesArray = $this->session->selfRoles;
		if (empty($rolesArray) || empty($main_sign) || empty($sub_sign)) {
			return false;
		} else {
			//管理员默认拥有所有权�?
			if (in_array(1, $rolesArray)) {
				//return true;
			}
			//初始化角色对�?
			$roleModel = new DM_Model_Table_User_Role();

			//根据角色获取权限列表
			$privileges = $roleModel->getRolePrivliegesByRoleIDs($rolesArray);

			return $this->hasPermissionPrivileges($privileges, $main_sign, $sub_sign);
		}
	}

	/**
	 * 判断是否允许
	 * @param array $privileges
	 * @param string $main_sign
	 * @param string $sub_sign
	 */
	private function hasPermissionPrivileges($privileges, $main_sign, $sub_sign)
	{
		$isAllow = true;
		if (!empty($privileges)) {

			//判断deny列表
			if (!empty($privileges['deny'])) {
				foreach ($privileges['deny'] as $item) {
					if ($item['MainSign'] == $main_sign && ($item['SubSign'] == $sub_sign || trim($item['SubSign']) == '*')) {
						$isAllow = false;
						break;
					}
				}
			}

			//判断allow列表
			if (true == $isAllow && !empty($privileges['allow'])) {
				$isAllow = false;
				foreach ($privileges['allow'] as $item) {
					if ($item['MainSign'] == $main_sign && ($item['SubSign'] == $sub_sign || trim($item['SubSign']) == '*')) {
						$isAllow = true;
						break;
					}
				}
			}
		} else {
			$isAllow = false;
		}
		return $isAllow;
	}

	/**
	 * 获取ID和名称的键�?�对
	 * @param  $object_name 对象�?
	 * @param  string $table_name
	 * @param  string $key_field
	 * @param  string $value_field
	 * @param  boolean $nullable
	 * @return array ($key_field=>$value_field,...)
	 */
	public function getSelectOption($object_name, $table_name, $key_field, $value_field, $nullable = false, $where = '')
	{
		$model = new $object_name();

		$select = $model->select()->from($table_name, array($key_field, $value_field));
		if (!empty($where) && is_array($where)) {
			foreach ($where as $key => $value) {
				$select->where($key, $value);
			}
		}

		$adapter = $model->getAdapter();
		$result = $adapter->fetchPairs($select);

		$nullable ? $result[''] = '--�?�?--' : '';
		return $result;
	}


	/**
	 * 管理员后台重写是否登�?
	 */
	protected function isLogin()
	{
		return $this->auth->getIdentity();
	}


	/**
	 * 分页操作
	 * @param Zend_Db_Select $select
	 * @param unknown_type $page
	 * @param unknown_type $perpage
	 * @return multitype:multitype: number
	 */
	protected function getResultSet(Zend_Db_Select $select, $page = 1, $perpage = 10)
	{
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		$paginator = new Zend_Paginator($adapter);
		$paginator->setItemCountPerPage($perpage);
		$paginator->setCurrentPageNumber($page);
		$total = $paginator->getTotalItemCount();
		$items = $paginator->getCurrentItems();
		return array(
			'total' => $total,
			'rows' => iterator_to_array($items)
		);
	}

	/**
	 * 解析查询条件
	 * @param $select
	 * @param null $list_where
	 * @return mixed
	 * @internal param $model
	 */
	protected function parseListWhere($select, $list_where = null)
	{
		is_null($list_where) && isset($this->list_where) && $list_where = $this->list_where;

		if (!empty($list_where)) {
			foreach ($list_where as $type => $fields) {
				foreach ($fields as $field) {
					$pos = strpos($field, "#");
					$table = '';
					if ($pos !== false) {
						$table = substr($field, 0, $pos) . ".";
						$field = substr($field, $pos + 1);
					}

					//取�??
					$value = trim($this->_getParam($field));

					if ($value !== '') {
						switch (strtolower($type)) {
							case "eq":
								$select->where("{$table}{$field} = ?", $value);
								break;
							case "bet":
								$field = explode("_", $field);
								$select->where("{$table}{$field[1]} " . ($field[0] == "Start" ? ">" : "<") . "= ?", $value);
								break;
							case "like":
								$select->where("{$table}{$field} like ?", "%{$value}%");
								break;
						}
					}
				}
			}
		}

		return $select;
	}

	/**
	 * list获取结果�?
	 * @param $model
	 * @param $select
	 * @param $sort
	 * @param bool|true $desc
	 * @param null $list_where
	 * @return array
	 */
	protected function listResults($model, $select, $sort, $desc = true, $list_where = null)
	{
		$results = array();

		//解析查询条件
		$select = $this->parseListWhere($select, $list_where);

		//关闭视图
		$this->disableView();

		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

		//总条�?
		$total = $model->getAdapter()->fetchOne($countSql);
		if ($total) {
			//排序
			$sort = $this->_getParam('sort', $sort);
			$order = $this->_getParam('order', $desc ? 'desc' : 'asc');
			$select->order("$sort $order");

			//分页
			$pageIndex = $this->_getParam('page', 1);
			$pageSize = $this->_getParam('rows', 50);
			$select->limitPage($pageIndex, $pageSize);

			//列表
			$results = $model->fetchAll($select)->toArray();
			$this->escapeVar($results);
		}

		return array('total' => $total, 'rows' => $results);
	}

	/**
	 * 判断是否为post请求
	 * @return bool
	 */
	protected function isPost()
	{
		return DM_Controller_Front::getInstance()->getHttpRequest()->isPost();
	}

	/**
	 * 成功
	 * @param $msg
	 * @param int $code
	 */
	protected function succJson($msg = '', $code = 1)
	{
		$this->disableView();
		$this->returnJson($code, $msg);
	}

	/**
	 * 失败
	 * @param $msg
	 * @param int $code
	 */
	protected function failJson($msg, $code = 0)
	{
		$this->disableView();
		$this->returnJson($code, $msg);
	}

	/**
	 * 校验前台提交的数�?
	 * @param null $keys
	 */
	protected function filterParam($keys = null)
	{
		//获取待过滤参�?
		!isset($this->filter_fields) && $this->filter_fields = array();
		$filter_fields = array();
		if ($keys && is_array($keys)) {
			foreach ($keys as $key) {
				isset($this->filter_fields[$key]) && $filter_fields[] = $this->filter_fields[$key];
			}
		} else {
			$filter_fields = $this->filter_fields;
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
		$param = DM_Helper_Filter::autoValidation($all_param, $filter_fields);

		if (is_string($param)) {
			$this->failJson($param);
		} else {
			$this->_param = $param;
		}
	}
}
