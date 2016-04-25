<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class TeacherOnlineTimesController extends CommonController
{
	/*
	 * 列表处理
	 */
	protected function _processer(&$volist)
	{
		foreach ($volist as $key => &$value) {
			$value['name'] = M('Users')->getFieldById($value['teacher_id'], 'name');
			$value['mobile'] = M('Users')->getFieldById($value['teacher_id'], 'mobile');
			$value['total_times'] = floor($value['total_times'] / 60);
			$value['date'] = date('Y-m-d', $value['date']);
		}
	}

	/**
	 * 查询条件
	 */
	protected function _filter()
	{
		$request = I('post.');
		if (isset($request['start_date']) && $request['start_date']) {
			$this->_index_where['date'][] = array('egt', strtotime($request['start_date']));
			$this->_index_param['start_date'] = $request['start_date'];
			$this->assign('start_date', $request['start_date']);
		}
		if (isset($request['end_date']) && $request['end_date']) {
			$this->_index_where['date'][] = array('elt', strtotime($request['end_date']));
			$this->_index_param['end_date'] = $request['end_date'];
			$this->assign('end_date', $request['end_date']);
		}
		if (isset($request['mobile']) && $request['mobile']) {
			$teacher_id = M('Users')->getFieldByMobile($request['mobile'], 'id');
			$this->_index_where['teacher_id'] = $teacher_id;
			$this->_index_param['mobile'] = $request['mobile'];
			$this->assign('mobile', $request['mobile']);
		}

	}

	/*
	 * 教师在线时长列表
	 */
	public function index()
	{
		// 列表过滤
		$this->_list(M('TeacherOnlineTimes'), $this->_index_where, $this->_index_param, 'id', 'create_time');
		// 模版显示
		$this->display();
	}
}