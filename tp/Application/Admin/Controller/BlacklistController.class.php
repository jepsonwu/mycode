<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class BlacklistController extends CommonController
{
	// 显示黑名单教师列表开关
	const DISPLAY_BLACKLIST_ON = '1';
	const DISPLAY_BLACKLIST_OFF = '0';

	/**
	 * 首页
	 */
	public function index()
	{
		// 获取显示黑名单开关
		$display_flg = S('display_blacklist_flg');
		if ($display_flg === false) {
			$display_flg = self::DISPLAY_BLACKLIST_OFF;
			S('display_blacklist_flg', $display_flg);
		}
		$this->assign('display_flg', $display_flg);
		// 获取黑名单
		$teacher_blacklist = S('teacher_blacklist');
		if ($teacher_blacklist === false) {
			$teacher_blacklist = C('TEACHER_BLACKLIST');
			S('teacher_blacklist', $teacher_blacklist);
		}
		$this->assign('teacher_blacklist', $teacher_blacklist);
		// 显示页面
		$this->display();
	}

	/**
	 * 删除
	 */
	public function foreverdelete()
	{
		$teacher_id = I('get.id');
		if (!empty($teacher_id)) {
			$teacher_blacklist = S('teacher_blacklist');
			$key = array_search($teacher_id, $teacher_blacklist);
			unset($teacher_blacklist[$key]);
			S('teacher_blacklist', $teacher_blacklist);
			$this->ajaxReturn(make_url_rtn('永久删除成功!'));
		} else {
			$this->ajaxReturn(make_rtn('永久删除失败！'));
		}
	}

	/**
	 * 更换显示状态
	 */
	public function change()
	{
		$status = I('get.status');
		if ($status === self::DISPLAY_BLACKLIST_ON) {
			S('display_blacklist_flg', self::DISPLAY_BLACKLIST_ON);
			$this->ajaxReturn(make_url_rtn('已开启!'));
		} elseif ($status === self::DISPLAY_BLACKLIST_OFF) {
			S('display_blacklist_flg', self::DISPLAY_BLACKLIST_OFF);
			$this->ajaxReturn(make_url_rtn('已关闭!'));
		}

		$this->ajaxReturn(make_url('系统异常!'));
	}

	/**
	 * 保存
	 */
	public function insert()
	{
		$teacher_id = I('post.teacher_id');
		// 获取黑名单教师列表
		$teacher_blacklist = S('teacher_blacklist');
		// 单个教师
		if (strpos($teacher_id, ',') === false) {
			$teacher_blacklist[] = $teacher_id;
		}
		// 多个教师
		else {
			$teachers = explode(',', $teacher_id);
			$teacher_blacklist = array_merge($teacher_blacklist, $teachers);
		}
		S('teacher_blacklist', $teacher_blacklist);

		$this->ajaxReturn(make_url_rtn('新增成功!'));
	}
}