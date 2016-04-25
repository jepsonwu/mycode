<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class WagesController extends CommonController
{
	// 菲律宾教师通话单价
	const PH_CALL_PRICE = 0.35;
	// 菲律宾教师类型
	const PH_TEACHER_TYPE = 1;
	// 欧美教师通话单价
	const EA_CALL_PRICE = 1;
	// 菲律宾教师类型
	const EA_TEACHER_TYPE = 2;

	// 查询条件
	protected $_where_fields = array(
		'eq' => array('mobile'),
		'like' => array('name'),
		'between' => array(
			array('start_date', 'end_date', 'create_time')
		)
	);

	protected function _processer(&$volist)
	{
		foreach ($volist as $key => &$value) {
			// 获取教师类型
			$type = M('TeacherDetail')->getFieldByUserId($value['tid'], 'type');
			if ($type == self::PH_TEACHER_TYPE) {
				$value['wages'] = $value['total_times'] * self::PH_CALL_PRICE;
			} elseif ($type == self::EA_TEACHER_TYPE) {
				$value['wages'] = $value['total_times'] * self::EA_CALL_PRICE;
			}
		}
	}

	/*
	 * 教师在线时长列表
	 */
	public function index()
	{
		// 列表信息
		$this->_list($this->_index_where, $this->_index_param);
		// 显示
		$this->display();
	}

	/**
	 * 列表
	 */
	protected function _list($map, $param)
	{
		// 格式化请求参数
		$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
		$_SESSION [C('SEARCH_PARAMS_STR')] = '';
		foreach ($param as $key => $value) {
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/' . $key . '/' . $value;
		}

		// 获取查询条件
		if (empty($map)) {
			$map['create_time'] = array(
				array('egt', strtotime(date('Y-m-01'))),
				array('lt', strtotime('+1 month', strtotime(date('Y-m-01'))))
			);
		}
		foreach ($map as $key => $value) {
			if ($key == 'create_time') {
				$map["o.{$key}"] = $value;
			} else {
				$map["u.{$key}"] = $value;
			}
			unset($map[$key]);
		}
		
		// 获取满足条件记录数
		$sub_query = M('Orders')->alias('o')->join('left join ft_users u on o.tid=u.id')
			->where($map)->field('tid')->group('tid')->buildSql();
		$count = M('Orders')->table($sub_query . ' wages')->count();

		// 记录数大于零
		if ($count > 0) {
			// 创建分页对象
			if (!empty ($_REQUEST ['listRows'])) {
				$listRows = $_REQUEST ['listRows'];
				$_SESSION ['bkgd'] ['bg_listRows'] = $listRows;
			} else {
				$listRows = $_SESSION ['bkgd'] ['bg_listRows'];
			}
			$pg = new \Org\Util\Page ($count, $listRows);

			// 分页查询
			$field = array(
				'o.tid' => 'tid',
				'u.name' => 'name',
				'u.international_code' => 'international_code',
				'u.mobile' => 'mobile',
				'SUM(FLOOR(o.called_time/60))' => 'total_times'
			);
			$voList = M('Orders')->alias('o')->join('left join ft_users u on o.tid=u.id')->where($map)->field($field)
				->group('tid')->limit($pg->firstRow . ',' . $pg->listRows)->order('total_times desc')->select();

			// 数据处理
			if (method_exists($this, '_processer')) {
				$this->_processer($voList);
			}

			// 分页跳转的时候保证查询条件
			$param ['p'] = $pg->nowPage;
			$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/p/' . $pg->nowPage;

			// 分页显示
			$page = $pg->show();
			// 模板赋值显示
			$this->assign('list', $voList);
			$this->assign("page", $page);
		}
	}
}