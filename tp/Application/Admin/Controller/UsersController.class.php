<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;
use Common\Logic\SystemRechargeLogic;

/**
 *用户管理
 */
class UsersController extends CommonController
{
	//用户状态
	const USER_STATUS_TRUE = 1;
	const USER_STATUS_FALSE = 0;

	protected $_where_fields = array(
		"eq" => array("mobile", "type", "status"),
		"like" => array("name"),
		"bet" => array("create_start_time", "create_end_time"),
	);
	//允许查找的字段，例如密码就不允许查找 todo
	protected $allow_fields = "";

	public function index()
	{
		$this->assign("user_status", C("USER_STATUS"));
		$this->assign("types", C("USER_TYPE"));
		$this->assign("user_gender", C("USER_GENDER"));
		$this->assign("user_white", C("USER_WHITE"));

		$this->_list(M("Users"), $this->_index_where, $this->_index_param, "id", "create_time");
		$this->display();
	}

	protected function _processer(&$volist)
	{
		foreach ($volist as &$value) {
			$value['type'] = C("USER_TYPE." . $value['type']);
			$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['status_show'] = C("USER_STATUS." . $value['status']);
			$value['gender'] = C("USER_GENDER." . $value['gender']);
			$value['avatar'] = create_pic_url("avatar", $value['id']) . $value['avatar'];
			$value['balance'] /= 100;
		}
	}

	/**
	 * 看他不爽 关闭用户
	 */
	public function close()
	{
		$user_id = I("get.id");
		!is_numeric($user_id) && parent::fReturn("非法ID！");

		$status = I("get.status");
		!in_array($status, array(self::USER_STATUS_FALSE, self::USER_STATUS_TRUE)) &&
		parent::fReturn("非法STATUS！");

		$model = M("Users");

		$result = $model->where("id='{$user_id}'")->save(array("status" => $status));
		if ($result === false)
			parent::fReturn("关闭失败！");
		else
			parent::sReturn("关闭成功！");
	}

	/**
	 * todo 更改状态通用方法
	 * 白名单操作
	 */
	public function close_white()
	{
		$user_id = I("get.id");
		!is_numeric($user_id) && parent::fReturn("非法ID！");

		$status = I("get.status");
		!in_array($status, array(self::USER_STATUS_FALSE, self::USER_STATUS_TRUE)) &&
		parent::fReturn("非法STATUS！");

		$model = M("Users");

		$result = $model->where("id='{$user_id}'")->save(array("white" => $status));
		if ($result === false)
			parent::fReturn("启用失败！");
		else
			parent::sReturn("启用成功！");
	}

	/**
	 * 系统充值
	 */
	public function system_recharge()
	{
		// 获取用户信息
		$user_info = I('post.');

		// 验证充值信息
		if (!empty($user_info['id']) && !empty($user_info['amount'])) {
			// 验证充值金额有效性
			if ($user_info['amount'] > 0) {
				// 充值
				$amount = $user_info['amount'] * 100;
				$result = SystemRechargeLogic::systemRecharge($user_info['id'], $amount);
				if ($result) {
					$this->ajaxReturn(make_url_rtn('充值成功!'));
				} else {
					$this->ajaxReturn(make_url_rtn('充值失败!'));
				}
			} else {
				$this->ajaxReturn(make_rtn('充值金额必须大于0!'));
			}
		} else {
			$this->ajaxReturn(make_rtn('获取充值信息失败!'));
		}
	}

	/**
	 * 充值页面
	 */
	public function recharge()
	{
		// 获取用户ID
		$id = I('get.id');
		// 获取用户信息
		if ($id) {
			$user_info = M('Users')->getById($id);
			$this->assign('vo', $user_info);
		} else {
			$this->ajaxReturn(make_rtn('获取用户信息失败!'));
		}

		$this->display();
	}
}