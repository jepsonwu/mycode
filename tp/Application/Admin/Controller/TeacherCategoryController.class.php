<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class TeacherCategoryController extends CommonController
{
	// 教师审核状态（成功）
	const STATUS_AUDITING_SUCCESS = 2;

	/**
	 * 列表处理
	 */
	protected function _processer(&$volist)
	{
		// 增加外教总数
		foreach ($volist as $key => &$value) {
			$teacher_cond = array(
				'status' => self::STATUS_AUDITING_SUCCESS, 
				'type' => $value['type']
			);
			$value['customer_price'] /= 100;
			$value['teacher_price'] /= 100;
			$value['total'] = M('TeacherDetail')->where($teacher_cond)->count();
		}
	}

	/**
	 * 首页
	 */
	public function index()
	{
		// 列表过滤
		$this->_list(M('TeacherCategory'), $this->_index_where, $this->_index_param, 'type', 'create_time');
		// 模版显示
		$this->display();
	}

	/**
	 * 编辑
	 */
	public function edit()
	{
		$type = I('get.type');
		$info = M('TeacherCategory')->getByType($type);
		$info['customer_price'] /= 100;
		$info['teacher_price'] /= 100;
		$this->assign('info', $info);
		$this->display();
	}

	/**
	 * 添加
	 */
	public function insert()
	{
		$request = I('post.');
		$data = array(
			'category_name' => $request['category_name'],
			'customer_price' => $request['customer_price'] * 100,
			'teacher_price' => $request['teacher_price'] * 100
		);
		$result = M('TeacherCategory')->add($data);
		if ($result !== false) {
			$this->ajaxReturn(make_url_rtn('添加成功!'));
		} else {
			$this->ajaxReturn(make_rtn('添加失败!'));
		}
	}

	/**
	 * 编辑保存
	 */
	public function save()
	{
		$request = I('post.');
		$data = array(
			'category_name' => $request['category_name'],
			'customer_price' => $request['customer_price'] * 100,
			'teacher_price' => $request['teacher_price'] * 100
		);
		$result = M('TeacherCategory')->where("type={$request['type']}")->save($data);
		if ($result !== false) {
			$this->ajaxReturn(make_url_rtn('编辑成功!'));
		} else {
			$this->ajaxReturn(make_rtn('编辑失败!'));
		}
	}
}