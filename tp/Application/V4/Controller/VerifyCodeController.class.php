<?php
namespace V4\Controller;

use V4\Controller\CommonController;
require_once(LIB_PATH.'Org/YM_SMS/YMSms.php');

class VerifyCodeController extends CommonController {
	
	// 有效用户状态
	const USER_VALID_STATUS = 1;
	// 相同IP限制每天请求次数
	const IP_LIMIT_COUNT = 20;
	// 相同号码限制每天请求次数
	const MOBILE_LIMIT_COUNT = 5;

	// 获取验证码配置
	protected $verify_code_get_html_conf = array(
		'check_fields' => array(
			array('module', 'register,forget_password', 'MODULE_IS_INVALID', 1, 'in')
		)
	);

	/**
	 * 获取验证码
	 */
	public function verify_code_get_html() {
		// 获取国际代码与手机号码
		$international_code = $this->request['international_code'];
		$mobile = $this->request['mobile'];
		// 验证国际代码
		$code_result = checkInternationalCode($international_code);
		if(!$code_result) $this->failReturn(C('INTERNATIONAL_CODE_IS_INVALID'));
		// 验证手机号码
		$mobile_result = checkMobile($international_code, $mobile);
		if (!$mobile_result) $this->failReturn(C('MOBILE_IS_INVALID'));

		// 实例化用户模型
		$user_model = M('Users');
		$user_map['international_code'] = $international_code;
		$user_map['mobile'] = $mobile;
		$user_map['status'] = self::USER_VALID_STATUS;
		$user_id = $user_model->where($user_map)->field('id')->find();
		// 注册时，用户已存在
		if (!empty($user_id) && $this->module == 'register') $this->failReturn(C('USER_IS_EXIST'));
		// 忘记密码时，用户不存在
		if (empty($user_id) && $this->module == 'forget_password') $this->failReturn(C('USER_IS_NOT_EXIST'));

		// 限制同一用户获取短信时间
		$limit_model =M('VerifyCodeHistory');
		// SQL条件
		$time_map['international_code'] = $international_code;
		$time_map['mobile'] = $mobile;
		$time_map['achieve_time'] = array('gt', time()-60);
		$time_limit_result = $limit_model->where($time_map)->find();
		if (!empty($time_limit_result)) $this->failReturn(C('PLEASE_WAIT_A_MOMENT'));
		// 限制同一IP请求次数
		$ip_map['ip_address'] = get_client_ip(1);
		$ip_map['achieve_time'] = array('gt', strtotime('today'));
		$ip_count_limit = $limit_model->where($ip_map)->count();
		if ($ip_count_limit > self::IP_LIMIT_COUNT) $this->failReturn(C('ILLEGAL_IP_ADDRESS'));
		// 限制同一手机号码请求次数
		$mobile_map['international_code'] = $international_code;
		$mobile_map['mobile'] = $mobile;
		$mobile_map['achieve_time'] = array('gt', strtotime('today'));
		$mobile_count_limit = $limit_model->where($mobile_map)->count();
		if ($mobile_count_limit > self::MOBILE_LIMIT_COUNT) $this->failReturn(C('ATTAIN_DAILY_REQUEST_LIMIT'));

		// 生成验证码
		$verify_code = \Org\Util\String::randString ( 6, 1 );
		// 中国用户
		if ($international_code == '86') {
			// 发送验证码
			$sms=new \Org\Sms();
			$result = $sms->sendSMS($mobile, array($verify_code),40442);
			// 获取验证码失败
			if ($result['flag'] === 0) $this->failReturn(array($result['error_code'], $result['error_msg']));
		}
		// 国外用户
		else {
			// 发送验证码
			$sms = new \YMSms();
			$status_code = $sms->sendSMS('00' . $international_code . $mobile,
				"(Danyue Technology) Your verification code is: {$verify_code}. - CoolChat");
			// 获取验证码失败
			if ($status_code !== '0') $this->failReturn(C('VERIFY_CODE_ACHIEVE_FAILED'));
		}

		// 记录用户获取验证码信息
		$limit_data['international_code'] = $international_code;
		$limit_data['mobile'] = $mobile;
		$limit_data['ip_address'] = get_client_ip(1);
		$limit_data['achieve_time'] = time();
		$insert_result = $limit_model->add($limit_data);
		// 插入失败
		if ($insert_result === false) $this->DResponse(500);
		// 验证码与手机号存入session
		session('FT_VERIFY_CODE', $verify_code);
		session('FT_MOBILE', $international_code . $mobile);
		// 返回结果
		$this->successReturn();
	}
	
}