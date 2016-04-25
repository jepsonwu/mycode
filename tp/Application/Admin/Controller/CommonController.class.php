<?php

namespace Admin\Controller;

use Think\Exception;
use Think\Controller;

class CommonController extends Controller
{
	protected $_index_param = array();
	protected $_index_where = array();
	protected $_request = array();

	/**
	 * +----------------------------------------------------------
	 * 空操作
	 * +----------------------------------------------------------
	 */
	public function _empty($name)
	{
		$this->error('无此操作：' . $name, PHP_FILE . C('USER_AUTH_GATEWAY'));
	}

	/**
	 * +----------------------------------------------------------
	 * 初始化
	 * +----------------------------------------------------------
	 */
	public function _initialize()
	{
		// 检查认证识别号
		if (!isset ($_SESSION [C('USER_AUTH_KEY')])) {
			redirect(U(MODULE_NAME . '/Public/relogin'));
		}
		// 用户权限检查
		if (C('USER_AUTH_ON') && !in_array(CONTROLLER_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
			if (!\Org\Util\Rbac::AccessDecision()) {
				// 没有权限 抛出错误
				// $this->error ( L ( '_VALID_ACCESS_' ), $_SERVER
				// ['HTTP_REFERER'] );
				$this->error(L('_VALID_ACCESS_'));
			}
		}


		//处理查询条件
		ACTION_NAME == 'index' && $this->_filter();
	}

	/**
	 * +----------------------------------------------------------
	 * 默认列表操作
	 * +----------------------------------------------------------
	 */
	public function index()
	{
		//
		$map = array();
		$param = array();
		// 列表过滤器，生成查询Map对象
		if (method_exists($this, '_filter')) {
			$this->_filter($map, $param);
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D(CONTROLLER_NAME);
		if (!empty ($model)) {
			$this->_list($model, $map, $param);
			// $data = $model->getLastSql();
		}
		//
		$this->display();
	}

	/**
	 * 公用查询参数处理
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $type
	 *            1：string、2：int
	 * @param quote $map
	 * @param quote $param
	 */
	protected function _com_filter($key, $value, $type, &$map, &$param)
	{
		//
		if (isset ($value) && (!empty ($value) || $value === 0 || $value === '0')) {
			switch ($type) {
				case 1 : // 字符串
					$map [$key] = array('like', "%" . $value . "%");
					break;
				case 2 : // 数字
					$map [$key] = $value;
					break;
				case 3 : // 日期
					$date_val = strtotime($value);
					$map [$key] = array(
						array('gt', $date_val),
						array('lt', $date_val + C('ONEDAY'))
					);
					break;
			}
			$this->assign($key, $value);
			$param [$key] = $value;
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 * +----------------------------------------------------------
	 *
	 * @param Model $model
	 *            数据对象
	 * @param HashMap $map
	 *            过滤条件
	 * @param HashMap $param
	 *            参数
	 * @param string $pk
	 *            主键
	 * @param string $sortBy
	 *            排序
	 * @param boolean $asc
	 *            是否正序
	 * @return void
	 */
	protected function _list($model, $map, $param, $pk = 'id', $sortBy = '', $asc = false)
	{
		// 排序字段 默认为主键名
		if (isset ($_REQUEST ['order']) && !empty ($_REQUEST ['order'])) {
			$order = I('request.order');
		} else {
			$order = !empty ($sortBy) ? $sortBy : $pk;
		}
		$param ['order'] = $order;

		// 排序方式默认按照倒序排列
		if (isset ($_REQUEST ['sort'])) {
			$sort = I('request.sort');
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}
		$param ['sort'] = $sort;
		//
		if (!empty ($param ["create_time"])) {
			$between = $param ["create_time"] [0];
			$time = $param ["create_time"] [1];
			$param ["create_time"] = $between . ',' . $time [0] . ',' . $time [1];
		}
		$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
		$_SESSION [C('SEARCH_PARAMS_STR')] = '';
		foreach ($param as $key => $value) {
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/' . $key . '/' . $value;
		}
		// 取得满足条件的记录数
		$count = $model->where($map)->count($pk);
		if ($count > 0) {
			// 创建分页对象
			if (!empty ($_REQUEST ['listRows'])) {
				$listRows = $_REQUEST ['listRows'];
				$_SESSION ['bkgd'] ['bg_listRows'] = $listRows;
			} else {
				$listRows = $_SESSION ['bkgd'] ['bg_listRows'];
			}
			$pg = new \Org\Util\Page ($count, $listRows);
			// 分页查询数据
			if (false != strpos($order, '.')) {
				$voList = $model->where($map)->order($order . ' ' . $sort)->limit($pg->firstRow . ',' . $pg->listRows)->select();
			} else {
				$voList = $model->where($map)->order('`' . $order . '` ' . $sort)->limit($pg->firstRow . ',' . $pg->listRows)->select();
			}

			// 数据处理
			if (method_exists($this, '_processer')) {
				$this->_processer($voList);
			}
			// 分页跳转的时候保证查询条件
			// $pg->parameter = $param_str;
			$param ['p'] = $pg->nowPage;
			$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/p/' . $pg->nowPage;

			// 分页显示
			$page = $pg->show();
			// 模板赋值显示
			$this->assign('list', $voList);
			$this->assign("page", $page);
		}
		return;
	}

	/**
	 * +----------------------------------------------------------
	 * 默认新增操作
	 * +----------------------------------------------------------
	 */
	public function add()
	{
		//
		$this->display();
	}

	/**
	 * +----------------------------------------------------------
	 * 默认保存新增操作
	 * +----------------------------------------------------------
	 */
	public function insert()
	{
		//
		if (method_exists($this, 'checkPost')) {
			$this->checkPost();
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D(CONTROLLER_NAME);
		if (false === $model->create()) {
			$this->error($model->getError());
		}
		// 新增记录
		$new_id = $model->add();
		if (false !== $new_id) {
			//
			if (method_exists($this, 'my_after_insert')) {
				$this->my_after_insert($new_id);
			}
			//
			$this->ajaxReturn(make_url_rtn('新增成功!'));
		} else {
			$this->ajaxReturn(make_rtn('新增失败!'));
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认编辑操作
	 * +----------------------------------------------------------
	 */
	public function edit()
	{
		//
		$model = D(CONTROLLER_NAME);
		$id = I('request.id');
		$vo = $model->getById($id);
		// $data = $model->getLastSql();
		$this->assign('vo', $vo);
		//
		$this->display();
	}

	/**
	 * +----------------------------------------------------------
	 * 默认保存编辑操作
	 * +----------------------------------------------------------
	 */
	public function update()
	{
		// 字段验证
		if (method_exists($this, 'checkPost')) {
			$this->checkPost();
		}
		// 生成模型
		$model = D(CONTROLLER_NAME);
		$rcdNow = $model->getById(I('post.id'));
		if (false === $model->create()) {
			$this->error($model->getError());
		}
		// 更新记录
		if (false !== $model->save()) {
			//
			if (method_exists($this, 'my_after_update')) {
				$post = I('post.');
				$this->my_after_update($post, $rcdNow);
			}
			// 成功返回
			$this->ajaxReturn(make_url_rtn('编辑成功!'));
		} else {
			// 失败返回
			$this->ajaxReturn(make_rtn('编辑失败!'));
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认保存快速编辑文本操作
	 * +----------------------------------------------------------
	 */
	public function quickUpdateText()
	{
		//
		$model = D(CONTROLLER_NAME);
		if (false !== $model->save($_POST)) {
			//
			if (method_exists($this, 'my_after_update')) {
				$this->my_after_update();
			}
			//
			$this->ajaxReturn(make_url_rtn('快速编辑文本成功!'));
		} else {
			$this->ajaxReturn(make_rtn('快速编辑文本失败!'));
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认保存快速编辑日期操作
	 * +----------------------------------------------------------
	 */
	public function quickUpdateDate()
	{
		//
		$model = D(CONTROLLER_NAME);
		foreach ($_POST as $key => $value) {
			if ($key != 'id') {
				$_POST [$key] = strtotime($value);
			}
			if ($key == 'publish_time') {
				$_POST ['status'] = 1;
			}
		}
		if (false !== $model->save($_POST)) {
			$this->ajaxReturn(make_url_rtn('快速编辑日期成功!'));
		} else {
			$this->ajaxReturn(make_rtn('快速编辑日期失败!'));
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认永久删除操作
	 * +----------------------------------------------------------
	 */
	public function foreverDelete($id = null, $noAjaxReturn = false)
	{
		//
		$model = D(CONTROLLER_NAME);
		//
		if (!empty ($model)) {
			$pk = $model->getPk();
			if (empty ($id)) {
				$id = $_REQUEST [$pk];
			}
			if (isset ($id)) {
				// 生成图片/附件、目录集合
				$condition = array();
				$condition [$pk] = array('in', explode('|', $id));
				$rcds = $model->where($condition)->select();

				// 删除记录
				if (false !== $model->where($condition)->delete()) {
					//
					if (method_exists($this, 'my_after_delete')) {
						$this->my_after_delete($rcds);
					}
					// 成功返回
					if ($noAjaxReturn) {
						return;
					}
					//
					$this->ajaxReturn(make_url_rtn('永久删除成功!'));
				} else {
					// 失败返回
					$this->ajaxReturn(make_rtn('永久删除失败！'));
				}
			} else {
				$this->ajaxReturn(make_rtn('非法操作！未选中需永久删除的对象！'));
			}
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认排序操作
	 * +----------------------------------------------------------
	 */
	public function sort()
	{
		//
		$model = D(CONTROLLER_NAME);
		$map = array();
		if (!empty ($_REQUEST ['id'])) {
			$map ['id'] = array(
				'in',
				$_REQUEST ['id']
			);
		} else {
			$params = json_decode($_SESSION [C('SEARCH_PARAMS')]);
			foreach ($params as $key => $value) {
				if (!empty ($value) && $key != 'sort' && $key != 'order' && $key != 'p') {
					$map [$key] = $value;
				}
			}
		}
		$field = I('request.field');
		if (empty ($field)) {
			$field = 'title';
		}

		$sortList = $model->where($map)->order('sort asc, id asc')->select();

		foreach ($sortList as &$value) {
			$value ['txt_show'] = $value [$field];
		}
		$this->assign("sortList", $sortList);
		//
		$this->display("Public:sort");
	}

	/**
	 * +----------------------------------------------------------
	 * 默认保存排序操作
	 * +----------------------------------------------------------
	 */
	function saveSort()
	{
		$seqNoList = I('post.seqNoList');
		if (!empty ($seqNoList)) {
			// 更新数据对象
			$model = D(CONTROLLER_NAME);
			$col = explode('|', $seqNoList);
			//
			$topSort = I('post.topSort'); // 置顶排序
			$cnt = count($col);
			// 启动事务
			$model->startTrans();
			//
			try {
				foreach ($col as $val) {
					$val = explode(':', $val);
					if ($topSort == 1) { // 置顶排序
						$model->id = $val [0];
						$model->top = $cnt - $val [1] + 1;
					} else { // 普通排序
						$sort = $model->getFieldById($val [0], 'sort');
						if ($sort == $val [1])
							continue;
						$model->id = $val [0];
						$model->sort = $val [1];
					}
					if (false === $model->save()) {
						E('保存排序失败!');
					}
				}
			} catch (Exception $e) {
				// 回滚事务
				$model->rollback();
				$this->ajaxReturn(make_rtn($e->getMessage()));
			}
			//
			$model->commit();
			$this->ajaxReturn(make_url_rtn('排序成功!'));
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 默认更改状态
	 * +----------------------------------------------------------
	 */
	public function chgStt()
	{
		//
		$rqst = I('request.');
		//
		$model = D(CONTROLLER_NAME);
		//
		if (!empty ($rqst ['id'])) {
			$ids = explode('|', $rqst ['id']);
			$condition ['id'] = array('in', $ids);
			//
			$field = $rqst ['field'];
			if (empty($field)) {
				$field = 'status';
			}
			$data [$field] = $rqst ['stt'];
			//
			if (isset ($rqst ['time'])) {
				$data [$rqst ['time']] = time();
			}
			//
			if (false === $model->where($condition)->save($data)) {
				$this->ajaxReturn(make_rtn($rqst ['act'] . ' 失败！'));
			} else {
				$this->ajaxReturn(make_url_rtn($rqst ['act'] . ' 成功！'));
			}
		}
	}

	/**
	 * +----------------------------------------------------------
	 * 自动生成别名
	 * +----------------------------------------------------------
	 */
	public function setSlug($title)
	{
		//
		$pattern = '/[^\x00-\x80]/';
		//
		$pinyin = new \Org\Util\Pinyin();
		if (preg_match($pattern, $title)) { // 中文
			return $pinyin->toPinyin($title);
		} else { // 英文
			$ary = explode(' ', strtolower($title));
			return implode('-', $ary);
		}
	}

	public function sReturn($info, $url = null)
	{
		!$url && $url = __CONTROLLER__ . '/index' . $_SESSION [C('SEARCH_PARAMS_STR')];

		parent::ajaxReturn(
			array(
				'status' => true,
				'info' => $info,
				'url' => $url
			)
		);
	}

	public function fReturn($info, $status = false, $url = '', $act = '')
	{
		parent::ajaxReturn(
			array(
				'status' => $status,
				'info' => $info,
				'url' => $url,
				'act' => $act
			)
		);
	}

	/**
	 * 查询条件处理
	 * 处理一些基本的查询条件，如果有复杂的需要自定义
	 * 通常情况下查询条件包含等于、区间、模糊匹配(只允许右模糊匹配)
	 * 控制文件定义待处理查询属性
	 * @return bool
	 */
	protected function _filter()
	{
		$info = $this->_request = I("request.");
		if (empty($info))
			return false;

		//等于
		if (isset($this->_where_fields['eq'])) {
			foreach ($this->_where_fields['eq'] as $key) {
				if (isset($info[$key]) && $info[$key] != "") {
					$this->_index_where[$key] = $this->_index_param[$key] = $info[$key];
					$this->assign($key, $info[$key]);
				}
			}
		}

		//区间
		if (isset($this->_where_fields['bet'])) {
			foreach ($this->_where_fields['bet'] as $key) {
				$value = explode("_", $key);

				if (isset($info[$key]) && $info[$key] != "") {
					$this->_index_param[$key] = $info[$key];

					$w_value = "";
					switch ($value[2]) {
						case "time":
							$w_value = strtotime($info[$key]);
							break;
						case "amount":
							$w_value = $info[$key] * 100;
							break;
						default:
							$w_value = $info[$key];
					}

					$this->_index_where[$value[0] . "_" . $value[2]][] = array(
						$value[1] == "start" ? "egt" : "elt",
						$value[2] = $w_value
					);
					$this->assign($key, $info[$key]);
				}
			}
		}

		// 新增区间
		if (isset($this->_where_fields['between'])) {
			foreach ($this->_where_fields['between'] as $key) {
				if (isset($info[$key[0]]) && $info[$key[0]]) {
					$this->_index_where[$key[2]][] = array('egt', strtotime($info[$key[0]]));
					$this->_index_param[$key[0]] = $info[$key[0]];
					$this->assign($key[0], $info[$key[0]]);
				}
				if (isset($info[$key[1]]) && $info[$key[1]]) {
					$this->_index_where[$key[2]][] = array('lt', strtotime($info[$key[1]]));
					$this->_index_param[$key[1]] = $info[$key[1]];
					$this->assign($key[1], $info[$key[1]]);
				}
			}
		}

		//like
		if (isset($this->_where_fields['like'])) {
			foreach ($this->_where_fields['like'] as $key) {
				if (isset($info[$key]) && $info[$key] != "") {
					$this->_index_param[$key] = $info[$key];
					$this->_index_where[$key] = array("like", $info[$key] . "%");
					$this->assign($key, $info[$key]);
				}
			}
		}

		return true;
	}

	/**
	 * 多表列表处理
	 */
	protected function _join_list($model_conf, $map, $param)
	{
		/* 例:
		$model_conf = array(
			'table' => array('table1', 'table2'),
			'join' => 'left',
			'on' => array('column1', 'column2'),
			'field' => array(array('column1'), array('column2' => 'column')),
			'order' => 'column1,column2 desc' // 别名排序
		)
		*/


		// 连接条件(注：暂时只做两表关联)
		$table1 = C('DB_PREFIX') . strtolower($model_conf['table'][0]);
		$table2 = C('DB_PREFIX') . strtolower($model_conf['table'][1]);
		$join = "{$table2} on {$table1}.{$model_conf['on'][0]} = {$table2}.{$model_conf['on'][1]}";

		// 初始化查询字段数组
		$field = [];
		// 重命名查询字段
		foreach ($model_conf['field'] as $offset => $columns) {
			foreach ($columns as $key => $val) {
				// 字段为索引数组
				if (is_numeric($key)) {
					$field[C('DB_PREFIX') . $model_conf['table'][$offset] . '.' . $val] = $val;
				}
				// 字段为关联数组
				else {
					$field[C('DB_PREFIX') . $model_conf['table'][$offset] . '.' . $key] = $val;
				}
			}
		}

		// 获取满足条件的记录总数
		$count = M(ucfirst($model_conf['table'][0]))->join($join, $model_conf['join'])->field($field)->where($map)->count();

		// 查询结果大于0
		if ($count) {
			// 创建分页对象
			if (!empty($_REQUEST['listRows'])) {
				$listRows = $_REQUEST['listRows'];
				$_SESSION['bkgd']['bg_listRows'] = $listRows;
			} else {
				$listRows = $_SESSION['bkgd']['bg_listRows'];
			}
			$pg = new \Org\Util\Page($count, $listRows);

			// 分页查询数据
			$voList = M(ucfirst($model_conf['table'][0]))->join($join, $model_conf['join'])->field($field)
				->where($map)->order($model_conf['order'])->limit($pg->firstRow . ',' . $pg->listRows)->select();

			// 数据处理
			if (method_exists($this, '_processer')) {
				$this->_processer($voList);
			}

			// 查询数据传入session
			$param['p'] = $pg->nowPage;
			$_SESSION[C('SEARCH_PARAMS')] = json_encode($param);
			$_SESSION[C('SEARCH_PARAMS_STR')] = '';
			foreach ($param as $key => $val) {
				$_SESSION[C('SEARCH_PARAMS_STR')] .= '/' . $key . '/' . $val;
			}

			// 分页显示
			$page = $pg->show();
			// 模板赋值显示
			$this->assign('list', $voList);
			$this->assign("page", $page);
		}
	}

}

?>