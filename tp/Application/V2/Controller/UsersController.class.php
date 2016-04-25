<?php
namespace V2\Controller;

use V2\Controller\CommonController;
use Common\Logic\OrderCacheLogic;

class UsersController extends CommonController
{
	const TEACHER = 1;
	const STUDENT = 0;

	//老师在线状态
	private $teacher_online_status = array(
		"online" => 1,
		"busy" => 2,
		"offline" => 3
	);

	/**
	 *用户资料获取配置
	 * @var array
	 */
	protected $user_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			//字段、规则、错误提示(error_code 如果不存在则返回http 400)、验证条件、附加规则、默认值、
			//允许为空(默认是false)、自定函数参数
			array("fields",
				"name,gender,avatar,location,nationality,introduce,status,token,create_time,type",
				null, 0, "in",
				"name,gender,avatar,location,nationality,introduce,token,type"),
		)
	);

	/**
	 * [$user_get_html_conf description]
	 * @var array
	 */
	protected $user_others_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			//字段、规则、错误提示(error_code 如果不存在则返回http 400)、验证条件、附加规则、默认值、
			//允许为空(默认是false)、自定函数参数
			array("fields",
				"name,gender,avatar,location,nationality,introduce,type",
				null, 0, "in",
				"name,gender,avatar,location,nationality,introduce,type"),
			array("user_other_id", "require", null, 1)
		)
	);

	/**
	 *用户编辑配置
	 *修改：验证条件：0-存在就校验，1-必须校验
	 */
	protected $user_put_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("name", "require"),
			array("gender", "0,1", null, 0, "in"),
			array("avatar", "require"),
			array("nationality", "checkNationalityCode", "NATIONALITY_CODE_IS_INVALID", 0, "function"),
			//array("location","require"),
			array("introduce", "is_string", null, 0, "function", null, true),
		)
	);

	/**
	 *用户密码保存，注册阶段。配置
	 * @var array
	 */
	protected $user_passwd_put_html_conf = array(
		"check_fields" => array(
			array("passwd", "/^[^\s]{8,32}$/", 'PASSWORD_IS_INVALID', 1)
		)
	);

	/**
	 * 用户收款方式编辑配置
	 * @var array
	 */
	protected $user_payment_put_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			//array("binded_bank_card","number"),
			array("paypal_account", "require"),
			// array("bank_name","require"),
			array("real_name", "require"),
		),
	);

	/**
	 *用户收款方式获取配置
	 * @var array
	 */
	protected $user_payment_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("fields", "paypal_account,real_name",
				null, 0, "in", "paypal_account,real_name")
		),
	);

	// 用户注册配置
	protected $register_post_html_conf = array(
		'check_fields' => array(
			array('verify_code', '/^[0-9]{6}$/', 'VERIFY_CODE_IS_INVALID', 1)
		)
	);

	/**
	 * 用户注册
	 */
	public function register_post_html()
	{
		// 获取国际代码，手机号码，验证码
		$international_code = $this->request['international_code'];
		$mobile = $this->request['mobile'];
		$verify_code = $this->verify_code;
		// 验证国际代码
		$code_result = checkInternationalCode($international_code);
		if (!$code_result) $this->failReturn(C('INTERNATIONAL_CODE_IS_INVALID'));
		// 验证手机号码
		$mobile_result = checkMobile($international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));
		// SESSION中获取验证码与验证手机
		$session_verify_code = session('FT_VERIFY_CODE');
		$session_mobile = session('FT_MOBILE');
		// 验证是否为同一手机号码
		if ($international_code . $mobile != $session_mobile) $this->failReturn(C('MOBILE_IS_WRONG'));
		// 验证码确认
		if ($verify_code != $session_verify_code) $this->failReturn(C('VERIFY_CODE_IS_WRONG'));
		// 实例化用户表
		$users_model = M('Users');
		$map['international_code'] = $international_code;
		$map['mobile'] = $mobile;
		$map['status'] = 1;
		$user_id = $users_model->where($map)->field('id')->find();
		// 用户已存在
		if (!empty($user_id)) {
			$this->failReturn(C('USER_IS_EXIST'));
		} else {
			$user_info['international_code'] = $international_code;
			$user_info['mobile'] = $mobile;
			$user_info['name'] = 'user' . substr($mobile, -4);
			$user_info['create_time'] = time();
			$result = $users_model->add($user_info, array(), true);
			if ($result === false) $this->DResponse(500);
			// 注册成功
			$this->successReturn();
		}
	}

	// 忘记密码配置
	protected $forget_password_put_html_conf = array(
		'check_fields' => array(
			array('verify_code', '/^[0-9]{6}$/', 'VERIFY_CODE_IS_INVALID', 1)
		)
	);

	/**
	 * 忘记密码
	 */
	public function forget_password_put_html()
	{
		// 获取国际代码与手机号码
		$international_code = $this->request['international_code'];
		$mobile = $this->request['mobile'];
		// 验证国际代码
		$code_result = checkInternationalCode($international_code);
		if (!$code_result) $this->failReturn(C('INTERNATIONAL_CODE_IS_INVALID'));
		// 验证手机号码
		$mobile_result = checkMobile($international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));
		// SESSION中获取验证码与验证手机
		$session_verify_code = session('FT_VERIFY_CODE');
		$session_mobile = session('FT_MOBILE');
		// 验证是否为同一手机号码
		if ($international_code . $mobile != $session_mobile) $this->failReturn(C('MOBILE_IS_WRONG'));
		// 验证码确认
		if ($this->verify_code != $session_verify_code) $this->failReturn(C('VERIFY_CODE_IS_WRONG'));
		// 实例化用户表
		$users_model = M('Users');
		$map['international_code'] = $international_code;
		$map['mobile'] = $mobile;
		$user_id = $users_model->where($map)->field('id')->find();
		// 用户不存在
		if (empty($user_id)) $this->failReturn(C('USER_IS_NOT_EXIST'));
		// 验证成功
		$this->successReturn();
	}

	// 修改密码配置
	protected $change_password_put_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('old_password', '/^[^\s]{8,32}$/', 'OLD_PASSWORD_IS_INVALID', 1),
			array('new_password', '/^[^\s]{8,32}$/', 'NEW_PASSWORD_IS_INVALID', 1)
		)
	);

	/**
	 * 修改密码
	 */
	public function change_password_put_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化用户表
		$model = M('Users');
		$pass_info = $model->where("id={$user_id}")->field('password,salt')->find();
		// 用户不存在
		if (empty($pass_info)) $this->failReturn(C('USER_IS_NOT_EXIST'));
		// 验证密码
		if (md5($this->old_password . $pass_info['salt']) != $pass_info['password']) $this->failReturn(C('PASSWORD_IS_WRONG'));
		// 更新密码
		$conf['password'] = md5($this->new_password . $pass_info['salt']);
		$result = $model->where("id={$user_id}")->save($conf);
		// 更新失败
		if ($result === false) $this->DResponse(500);
		// 返回结果
		$this->successReturn();
	}

	// 用户登录配置
	protected $login_post_html_conf = array(
		'check_fields' => array(
			array('mobile', '/^1[34578]{1}\d{9}$/', 'MOBILE_IS_INVALID', 1),
			array('password', '/^[a-zA-Z0-9]{32}$/', 'PASSWORD_IS_INVALID', 1)
		)
	);

	/**
	 * 用户登录
	 */
	public function login_post_html()
	{
		// 实例化用户表
		$users_model = M('Users');
		$user_info = $users_model->where("mobile={$this->mobile}")->find();
		// 查询失败
		if ($user_info === false) $this->DResponse(500);
		// 用户不存在
		if (empty($user_info)) $this->failReturn(C('USER_IS_NOT_EXIST'));
		// 密码不正确
		if ($user_info['password'] != md5($this->password)) $this->failReturn(C('PASSWORD_IS_WRONG'));
		// 返回用户信息
		$this->successReturn($user_info);
	}


	/**
	 * [user_get_html 获取用户信息]
	 * @return [type] [description]
	 */
	public function user_get_html()
	{
		$is_add = false;
		if (strpos($this->fields, 'type') === false) {
			$is_add = true;
			$this->fields .= ",type";
		}

		$result = M("Users")->field($this->fields)->where("id='" . USER_ID . "'")->find();
		if ($result) {
			if (isset($result['avatar']))
				$result['avatar'] = create_pic_url() . $result['avatar'];

			if ($result['type'] == self::TEACHER) {
				//通话总时长
				$teacher_status = S('teacher_status')[USER_ID];
				if (!empty($teacher_status)) {
					$teacher_status = C("TEACHER_STATUS.{$teacher_status}");
					$called_time = S("teacher_{$teacher_status}_list")[USER_ID];
				}
				$result['called_time'] = $called_time ? (int)$called_time : 0;

				//在线状态
				foreach (array("online", "busy", "offline") as $status) {
					$offline = S("teacher_{$status}_list");

					if (in_array(USER_ID, array_keys($offline))) {
						$result['online_status'] = $this->teacher_online_status[$status];
					}
				}

				!isset($result['online_status']) && $result['online_status'] = $this->teacher_online_status['offline'];

				//计价
				$result['billing'] = OrderCacheLogic::billing_get(1)[1];
			}

			if ($is_add) {
				unset($result['type']);
			}
			parent::successReturn($result);
		} else
			parent::failReturn(C("USER_IS_NOT_EXIST"));
	}

	/**
	 * 获取其他人信息
	 * @return [type] [description]
	 */
	public function user_others_get_html()
	{
		$is_add = false;
		if (!in_array("type", $this->fields)) {
			$is_add = true;
			$this->fields .= ",type";
		}

		$result = M("Users")->field($this->fields)->where("id='" . $this->user_other_id . "'")->find();
		if ($result) {
			if (isset($result['avatar']))
				$result['avatar'] = create_pic_url("avatar", $this->user_other_id) . $result['avatar'];

			if ($result['type'] == self::TEACHER) {
				//通话总时长
				$called_time = M("TeacherCalledTime")->where("tid='" . $this->user_other_id . "'")->getField("called_time");
				$result['called_time'] = $called_time ? $called_time : 0;

				//是否在线
				foreach (array("online", "busy", "offline") as $status) {
					$offline = S("teacher_{$status}_list");

					if (in_array(USER_ID, array_keys($offline))) {
						$result['online_status'] = $this->teacher_online_status[$status];
					}
				}

				!isset($result['online_status']) && $result['online_status'] = $this->teacher_online_status['offline'];

				//计价
				$result['billing'] = OrderCacheLogic::billing_get(1)[1];
			}

			if ($is_add) {
				unset($result['type']);
			}

			parent::successReturn($result);
		} else
			parent::failReturn(C("USER_IS_NOT_EXIST"));
	}

	protected $auth_demo_get_html_conf = array(
		"check_user" => true,
		"authorize" => true,
	);

	public function auth_demo_get_html()
	{
		parent::successReturn($this->request);
	}

	/**
	 * 用户资料编辑
	 */
	public function user_put_html()
	{
		empty($this->_default) && $this->DResponse(400);

		$result = M("Users")->where("id='" . USER_ID . "'")->save($this->_default);
		if ($result === false)
			parent::failReturn(C("USER_EDIT_FAILD"));
		else
			parent::successReturn("");
	}

	/**
	 * [user_passwd_put_html 保存密码]
	 * @return [type] [description]
	 */
	public function user_passwd_put_html()
	{
		// 获取国际代码与手机号码
		$international_code = $this->request['international_code'];
		$mobile = $this->request['mobile'];
		// 验证国际代码
		$code_result = checkInternationalCode($international_code);
		if (!$code_result) $this->failReturn(C('INTERNATIONAL_CODE_IS_INVALID'));
		// 验证手机号码
		$mobile_result = checkMobile($international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));
		// SQL条件
		$map['mobile'] = $mobile;
		$map['international_code'] = $international_code;
		$query_result = M("Users")->where($map)->getField("id");
		// 手机号码不存在
		if (empty($query_result)) $this->failReturn(C("MOBILE_IS_WRONG"));
		// 生成盐
		$salt = \Org\Util\String::randString(32, 5, 'oOLl01');
		$data['salt'] = $salt;
		$data['password'] = md5($this->passwd . $salt);
		$data['status'] = 1;
		// 存入数据库
		$result = M("Users")->where($map)->save($data);
		// 更新失败
		if ($result === false) $this->failReturn(C("PASSWORD_EDIT_FAILD"));
		// 返回结果
		$this->successReturn();
	}

	/**
	 * [user payment get info]
	 * @return [type] [description]
	 */
	public function user_payment_get_html()
	{

		$result = M("UserDetail")->where("user_id='" . USER_ID . "'")->field($this->fields)->find();
		// var_dump($result);exit;
		parent::successReturn($result);
	}

	/**
	 * [收款方式编辑]
	 * @return [type] [description]
	 */
	public function user_payment_put_html()
	{
		empty($this->_default) && $this->DResponse(400);

		$result = M("UserDetail")->where("user_id='" . USER_ID . "'")->getField("user_id");

		if ($result) {
			$result = M("UserDetail")->where("user_id='" . USER_ID . "'")->save($this->_default);
		} else {
			$this->_default['user_id'] = USER_ID;
			$result = M("UserDetail")->add($this->_default);
		}

		if ($result === false)
			parent::failReturn(C("USER_PAYMENT_EDIT_FAILD"));
		else
			parent::successReturn();
	}
}
