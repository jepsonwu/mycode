<?php
namespace V4\Controller;

use V4\Controller\CommonController;
use Common\Logic\OrderCacheLogic;

class UsersController extends CommonController
{
	const TEACHER = 1;
	const STUDENT = 0;
	// 国内国际代码
	const INLAND_INTERNATIONAL_CODE = 86;
	// 用户有效状态
	const STATUS_USER_VALID = 1;

	// 资金流有效状态
	const STATUS_FUND_FLOW_VALID = 1;
	// 资金流类型
	const TYPE_FUND_FLOW_RECHARGE = '1';
	const TYPE_FUND_FLOW_CALL = '2';
	const TYPE_FUND_FLOW_REWARD = '3';
	const TYPE_FUND_FLOW_PENDING = '4';
	// 资金流模式（加）
	const MODE_FUND_FLOW_PLUS = 1;

	// 新用户默认充值金额
	const NEW_USER_RECHARGE_AMOUNT = 1500;
	// 系统充值类型（注册赠送）
	const TYPE_SYSTEM_RECHARGE_REGISTER = 2;

	// 订单状态
	const ORDER_STATUS_NEW = 1;

	//老师在线状态
	private $teacher_online_status = array(
		"online" => 1,
		"busy" => 2,
		"offline" => 3
	);

	// 用户注册配置
	protected $register_post_html_conf = array(
		'check_fields' => array(
			array('international_code', 'checkInternationalCode', 'INTERNATIONAL_CODE_IS_INVALID', 1, 'function'),
			array('verify_code', '/^[0-9]{6}$/', 'VERIFY_CODE_IS_INVALID', 1),
			array('passwd', 'is_string', 'PASSWORD_IS_INVALID', 1, 'function')
		)
	);

	/**
	 * 用户注册
	 */
	public function register_post_html()
	{
		// 获取手机号码
		$mobile = $this->request['mobile'];
		// 验证手机号码
		$mobile_result = checkMobile($this->international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));

		// SESSION中获取验证码与验证手机
		$session_verify_code = session('FT_VERIFY_CODE');
		$session_mobile = session('FT_MOBILE');
		// 验证是否为同一手机号码
		if ($this->international_code . $mobile != $session_mobile) $this->failReturn(C('MOBILE_IS_WRONG'));
		// 验证码确认
		if ($this->verify_code != $session_verify_code) $this->failReturn(C('VERIFY_CODE_IS_WRONG'));

		// 实例化用户表
		$users_model = M('Users');
		$map['international_code'] = $this->international_code;
		$map['mobile'] = $mobile;
		$map['status'] = self::STATUS_USER_VALID;
		$user_id = $users_model->where($map)->field('id')->find();
		// 用户已存在
		if (!empty($user_id)) {
			$this->failReturn(C('USER_IS_EXIST'));
		} else {
			// 解密密码
			$crypt = new \Org\CoolChatCrypt();
			list($dec_res, $password) = $crypt->decrypt($this->passwd);
			// 解密成功
			if ($dec_res) {
				// 生成盐
				$salt = \Org\Util\String::randString(32, 5, 'oOLl01');
				// 用户信息
				$user_info['international_code'] = $this->international_code;
				$user_info['mobile'] = $mobile;
				$user_info['name'] = 'user' . substr($mobile, -4);
				$user_info['salt'] = $salt;
				$user_info['password'] = md5($password . $salt);
				$user_info['status'] = self::STATUS_USER_VALID;
				$user_info['create_time'] = time();
				$user_info['balance'] = self::NEW_USER_RECHARGE_AMOUNT; // 新用户充值

				// 开启事物
				$users_model->startTrans();
				// 存入数据库
				$user_id = $users_model->add($user_info, array(), true);
				if ($user_id) {
					// 如果是中国用户
					if ($this->international_code == self::INLAND_INTERNATIONAL_CODE) {
						// 验证用户是否通过分享链接注册
						$share_user_id = M('ShareRecord')->getFieldByMobile($mobile, 'user_id');
						if ($share_user_id === '0') {
							// 更新分享记录信息
							$share_data = array(
								'user_id' => $user_id,
								'update_time' => time()
							);
							$share_result = M('ShareRecord')->where("mobile={$mobile}")->save($share_data);
							// 更新失败
							if ($share_result === false) {
								// 回滚
								$users_model->rollback();
								$this->DResponse(500);
							}
						}
					}

					// 添加系统充值记录
					$system_recharge_record = array(
						'user_id' => $user_id,
						'amount' => self::NEW_USER_RECHARGE_AMOUNT,
						'type' => self::TYPE_SYSTEM_RECHARGE_REGISTER,
						'manager' => 'system',
						'create_time' => time()
					);
					$system_recharge_id = M('SystemRechargeHistory')->add($system_recharge_record);
					if ($system_recharge_id === false) {
						// 回滚
						$users_model->rollback();
						$this->DResponse(500);
					} else {
						// 添加用户资金流
						$fund_data = array(
							'user_id' => $user_id,
							'relation_id' => $system_recharge_id,
							'mode' => self::MODE_FUND_FLOW_PLUS,
							'type' => self::TYPE_FUND_FLOW_REWARD,
							'amount' => self::NEW_USER_RECHARGE_AMOUNT,
							'create_time' => time()
						);
						$fund_result = M('UserFundFlow')->add($fund_data);
						if ($fund_result === false) {
							// 回滚
							$users_model->rollback();
							$this->DResponse(500);
						} else {
							// 提交
							$users_model->commit();
							// 返回结果
							$this->successReturn();
						}
					}
				} else {
					$this->DResponse(500);
				}
			} else {
				$this->failReturn(C('PASSWORD_IS_INVALID'));
			}
		}
	}


	// 忘记密码配置
	protected $forget_password_put_html_conf = array(
		'check_fields' => array(
			array('international_code', 'checkInternationalCode', 'INTERNATIONAL_CODE_IS_INVALID', 1, 'function'),
			array('verify_code', '/^[0-9]{6}$/', 'VERIFY_CODE_IS_INVALID', 1),
			array('passwd', 'is_string', 'PASSWORD_IS_INVALID', 1, 'function')
		)
	);

	/**
	 * 忘记密码
	 */
	public function forget_password_put_html()
	{
		// 获取手机号码
		$mobile = $this->request['mobile'];
		// 验证手机号码
		$mobile_result = checkMobile($this->international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));

		// SESSION中获取验证码与验证手机
		$session_verify_code = session('FT_VERIFY_CODE');
		$session_mobile = session('FT_MOBILE');
		// 验证是否为同一手机号码
		if ($this->international_code . $mobile != $session_mobile) $this->failReturn(C('MOBILE_IS_WRONG'));
		// 验证码确认
		if ($this->verify_code != $session_verify_code) $this->failReturn(C('VERIFY_CODE_IS_WRONG'));

		// 实例化用户表
		$users_model = M('Users');
		$map['international_code'] = $this->international_code;
		$map['mobile'] = $mobile;
		$user_id = $users_model->where($map)->getField('id');
		// 用户不存在
		if (empty($user_id)) {
			$this->failReturn(C('USER_IS_NOT_EXIST'));
		} else {
			// 解密密码
			$crypt = new \Org\CoolChatCrypt();
			list($dec_res, $password) = $crypt->decrypt($this->passwd);
			// 解密成功
			if ($dec_res) {
				// 生成盐
				$salt = \Org\Util\String::randString(32, 5, 'oOLl01');
				$data['salt'] = $salt;
				$data['password'] = md5($password . $salt);
				// 更新密码
				$result = $users_model->where("id={$user_id}")->save($data);
				// 更新失败
				if ($result === false) $this->failReturn(C('PASSWORD_EDIT_FAILD'));
				// 返回结果
				$this->successReturn();
			} else {
				$this->failReturn(C('PASSWORD_IS_INVALID'));
			}
		}
	}


	// 修改密码配置
	protected $change_password_put_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('new_password', 'is_string', 'NEW_PASSWORD_IS_INVALID', 1, 'function')
		)
	);

	/**
	 * 修改密码
	 */
	public function change_password_put_html()
	{
		// 获取用户ID
		$user_id = USER_ID;

		// 解密新密码
		$crypt = new \Org\CoolChatCrypt();
		list($dec_new, $new_password) = $crypt->decrypt($this->new_password);
		if ($dec_new) {
			// 实例化用户表
			$model = M('Users');
			$pass_info = $model->where("id={$user_id}")->field('password,salt')->find();
			// 验证新密码与旧密码是否相同
			if ($pass_info['password'] == md5($new_password . $pass_info['salt']))
				$this->failReturn(C('SAME_AS_OLD_PASSWORD'));
			
			// 更新密码
			$conf['password'] = md5($new_password . $pass_info['salt']);
			$result = $model->where("id={$user_id}")->save($conf);
			// 更新失败
			if ($result === false) $this->DResponse(500);
			// 返回结果
			$this->successReturn();
		} else {
			$this->failReturn(C('NEW_PASSWORD_IS_INVALID'));
		}
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


	// 获取用户信息配置
	protected $user_get_html_conf = array(
		"check_fields" => array(
			//字段、规则、错误提示(error_code 如果不存在则返回http 400)、验证条件、附加规则、默认值、
			//允许为空(默认是false)、自定函数参数
			array("fields",
				"name,gender,avatar,location,nationality,introduce,status,token,create_time,type",
				null, 0, "in",
				"name,gender,avatar,location,nationality,introduce,type"),
			array('user_id', 'number', 'USER_INVALID', 1),
		)
	);

	/**
	 * 获取用户信息
	 * @return array
	 */
	public function user_get_html()
	{
		// 获取用户ID并初始化登录ID
		$user_id = $this->user_id;
		$login_id = '';
		// 如果用户上传登录状态，验证用户有效性
		if (isset($this->request['token']) && $this->request['token']) {
			$this->checkUser();
			// 获取登录ID
			$login_id = USER_ID;
		}

		// 验证用户有效性
		$user_cond = array(
			'id' => $user_id,
			'status' => self::STATUS_USER_VALID
		);
		$result = M("Users")->field($this->fields)->where($user_cond)->find();
		if ($result) {
			if (!empty($result['avatar']))
				$result['avatar'] = create_pic_url('avatar', $user_id) . $result['avatar'];
			// 用户为教师
			if ($result['type'] == self::TEACHER) {
				//通话总时长
				$online_status = S('teacher_status')[$user_id];
				if (!empty($online_status)) {
					$teacher_status = C("TEACHER_STATUS.{$online_status}");
					$called_time = S("teacher_{$teacher_status}_list")[$user_id];
				}
				$result['called_time'] = $called_time ? (int)$called_time : 0;

				//在线状态
				$result['online_status'] = $online_status ?: 3;

				//计价
				$teacher_detail = M('TeacherDetail')->where('user_id = '.$user_id)->field('type,introduce_audio,audio_time_length,skype')->find();
				$billing = M('TeacherCategory')->getFieldByType($teacher_detail['type'], 'customer_price');
				$result['billing'] = (int)$billing;
				$result['introduce_audio'] = $teacher_detail['introduce_audio'];
				$result['audio_time_length'] = $teacher_detail['audio_time_length'];
				$result['skype'] = $teacher_detail['skype'];

				// 是否被该用户收藏
				if (!empty($login_id)) {
					$collect_list = M('StudentTeacher')->where("sid={$login_id}")->getField('tid', true);
					if (!empty($collect_list) && in_array($user_id, $collect_list)) {
						$result['is_collected'] = true;
					} else {
						$result['is_collected'] = false;
					}
				}

				// 粉丝数量
				$fans_list = M('StudentTeacher')->where("tid={$user_id}")->getField('sid');
				$result['fans_count'] = count($fans_list);
			} else {
				// 收藏教师数量
				$teacher_list = M('StudentTeacher')->where("sid={$user_id}")->getField('tid');
				$result['collect_teacher_count'] = count($teacher_list);

				// 获取通话时长
				$calls_cond = array(
					'sid' => $user_id,
					'status' => array('gt', self::ORDER_STATUS_NEW)
				);
				$total_call_times = M('Orders')->where($calls_cond)->sum('called_time');
				$result['learning_time'] = (int)$total_call_times;
			}

			//返回云信ID
			switch(APP_STATUS) {
				//测试环境
				case 'dev':
					$result['nim_accid'] = 'dev_'.$user_id;
					break;
				//预发布环境
				case 'release':
					$result['nim_accid'] = 'release_'.$user_id;
					break;
				//正式环境
				case 'master':
					$result['nim_accid'] = 'master_'.$user_id;
					break;
			}
			parent::successReturn($result);
		} else
			parent::failReturn(C("USER_IS_NOT_EXIST"));
	}


	// 用户资料编辑配置
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
	 * 用户资料编辑
	 */
	public function user_put_html()
	{
		empty($this->_default) && $this->DResponse(400);
		
		// 查询用户是否为云信用户
		$nim_token = M('Users')->getFieldById(USER_ID, 'nim_token');
		if ($nim_token) {
			// 更新头像或昵称时，更新云信个人资料
			$nim_data = [];
			if (isset($this->_default['name'])) {
				$nim_data['name'] = $this->_default['name'];
			}
			if (isset($this->_default['avatar'])) {
				$nim_data['icon'] = create_pic_url('avatar', USER_ID) . $this->_default['avatar'];
			}
			if ($nim_data) {
				$nim_data['accid'] = APP_STATUS . '_' . USER_ID;
				$nim_result = update_nim_user_info($nim_data);
				if ($nim_result['code'] !== 200) $this->failReturn(C('NIM_USER_INFO_UPDATE_FAILED'));
			}
		}
		
		// 更新用户资料
		$result = M("Users")->where("id='" . USER_ID . "'")->save($this->_default);
		if ($result === false)
			parent::failReturn(C("USER_EDIT_FAILD"));
		else
			parent::successReturn("");
	}
	

	// 收款方式获取配置
	protected $user_payment_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("fields", "paypal_account,real_name",
				null, 0, "in", "paypal_account,real_name")
		),
	);

	/**
	 * 收款方式获取
	 * @return [type] [description]
	 */
	public function user_payment_get_html()
	{

		$result = M("UserDetail")->where("user_id='" . USER_ID . "'")->field($this->fields)->find();
		parent::successReturn($result);
	}


	// 收款方式编辑配置
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


	// 获取用户余额配置
	protected $balance_get_html_conf = array(
		'check_user' => true
	);

	/**
	 * 获取用户余额
	 * @return json $result
	 */
	public function balance_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 获取用户余额
		$result['balance'] = M('Users')->getFieldById($user_id, 'balance');
		// 返回结果
		$this->successReturn($result);
	}


	// 用户资金流配置
	protected $fund_flow_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);

	/**
	 * 用户资金流
	 * @return json $result
	 */
	public function fund_flow_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 查询条件
		$fund_cond = array(
			'user_id' => $user_id,
			'status' => self::STATUS_FUND_FLOW_VALID
		);
		// 查询结果
		$fund_flow = M('UserFundFlow')->where($fund_cond)->field('id,relation_id,mode,type,amount,create_time')
			->page($this->page, $this->listrows)->order('create_time desc')->select();
		// 查询失败
		if ($fund_flow === false) $this->DResponse(500);
		// 结果集为空
		if (empty($fund_flow)) $this->successReturn();
		// 处理结果集
		foreach ($fund_flow as &$fund) {
			switch ($fund['type']) {
				// 充值
				case self::TYPE_FUND_FLOW_RECHARGE:
				// 待处理
				case self::TYPE_FUND_FLOW_PENDING:
					// 增加用户昵称与头像
					$user_info = M('Users')->where("id={$user_id}")->field('name,avatar')->find();
					if (!empty($user_info)) {
						$fund['name'] = $user_info['name'];
						$fund['avatar'] = create_pic_url('avatar', $user_id) . $user_info['avatar'];
					}
					break;
				// 通话
				case self::TYPE_FUND_FLOW_CALL:
					// 增加教师昵称与头像
					$teacher_id = M('Calls')->getFieldById($fund['relation_id'], 'tid');
					if (!empty($teacher_id)) {
						$teacher_info = M('Users')->where("id={$teacher_id}")->field('name,avatar')->find();
						if (!empty($teacher_info)) {
							$fund['name'] = $teacher_info['name'];
							$fund['avatar'] = create_pic_url('avatar', $teacher_id) . $teacher_info['avatar'];
						}
					}
					break;
				// 系统奖励
				case self::TYPE_FUND_FLOW_REWARD:
					// 增加系统用户昵称与头像
					$system_user_info = C('SYSTEM_USER_INFO');
					$fund['name'] = $system_user_info['name'];
					$fund['avatar'] = create_pic_url('avatar', $system_user_info['id']) . $system_user_info['avatar'];
					break;
			}
			// 去除关联ID
			unset($fund['relation_id']);
		}		
		// 返回结果
		$count = M('UserFundFlow')->where($fund_cond)->count();
		$result['total'] = (int)$count;
		$result['list'] = $fund_flow;
		$this->successReturn($result);
	}
}
