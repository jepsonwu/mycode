<?php

/**
 * Auth类
 *
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Module_Account extends DM_Module_Base
{
	const AUTH_COOKIE = 'DM_AccountCZ';

	/**
	 * 当前登录的用户
	 * @var DM_Model_Row_Member
	 */
	protected $loginUser = NULL;

	protected $isLogin = NULL;

	protected $session = NULL;

	private static $instance = NULL;

	protected function __construct()
	{
	}

	/**
	 * 登录
	 *
	 * @param string $user
	 * @param string $password 密码为NULL表示开放登录
	 */
	public function login($user, $password, $login_ip = '', $platform = '', $deviceID = '', $pushID = '', $isMemberID = false, $isOverrideLast = false, $code = '')
	{
		if (!$user) {
			return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.user.null"));
		}
		if (!$password && $password !== NULL) {
			return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.password.null"));
		}
		$userModel = new DM_Model_Account_Members();
		if (DM_Helper_Validator::isEmail($user)) {
//         	return $this->resultArray(DM_Controller_Action::STATUS_FAILURE,'邮箱暂不能登录');
			$userTem = $userModel->getByEmail($user);
			if (!empty($userTem) && $userTem->EmailVerifyStatus == 'Verified') {
				$userInfo = $userTem;
			} else {
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.email.noVerified"));
			}
			//return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.email.noAllowed"));
		} elseif (DM_Helper_Validator::checkmobile($user) && $isMemberID == false) {
			$userTem = $userModel->getByMobile($user);
			if (!empty($userTem) && $userTem->MobileVerifyStatus == 'Verified') {
				$userInfo = $userTem;
			} else {
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.mobile.noVerified"));
			}
			//return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.mobile.noAllowed"));
		} elseif (DM_Helper_Validator::checkUsername($user)) {
			$userInfo = $userModel->getByUsername($user);
		} elseif (intval($user) > 0 && $isMemberID) {
			$userInfo = $userModel->getById($user);
		} else {
			$userInfo = $userModel->getByUsername($user);
		}

		if (empty($userInfo) || $userInfo->Status != 1) {
			return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, '您已触犯了《财猪使用条款及隐私协议》，暂时无法登录');
		}

		if (!$userInfo) {
			return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.login.userInfo.null"));
		}
		if ($this->isLogin() && !$isOverrideLast) {//已登录

			if ($this->getLoginUser()->MemberID != $userInfo->MemberID) {//不同用户
				//翻译，其他用户已登录，请退出后再登录。
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.msg.login.other.logged"));
			} else {
				return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.login.ok"), array('MemberID' => $userInfo->MemberID));
			}
		}
		//var_dump($userInfo->Password);echo '<br/>';var_dump($userInfo->encodePassword($password));exit;

		if ($password !== NULL && $userInfo->Password !== $userInfo->encodePassword($password)) {
			return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, $this->getLang()->_("api.user.msg.login.failed"));
		}

		//账号保护
		if (!empty($code)) {
			$verifyTable = new DM_Model_Table_MemberVerifys();
			$paramArray = array('VerifyCode' => $code, 'IdentifyID' => $userInfo->MobileNumber, 'VerifyType' => 6, 'MemberID' => $userInfo->MemberID, 'Status' => 'Pending');
			$verifyinfo = $verifyTable->getVerify($paramArray);

			if (!$verifyinfo) {
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, '验证码错误，请重新输入！');
			}

			if (time() > strtotime($verifyinfo['ExpiredTime'])) {
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, '验证码已过期！');
			}
			if (!$verifyTable->updateVerify($verifyinfo['VerifyID'])) {
				return $this->resultArray(DM_Controller_Action::STATUS_FAILURE, '验证码验证失败！');
			}
			if (!empty($deviceID)) {
				$system = DM_Controller_Front::getInstance()->getConfig()->project->attr_sign;
				if ($system == 'caizhu') {
					$userInfo->GJDeviceID = $deviceID;
				} elseif ($system == 'IM') {
					$userInfo->DeviceID = $deviceID;
				}
			}
		}

		/////////登录成功/////////
		$userInfo->LastLoginIp = $login_ip;
		// if(!empty($platform)){
		//     $userInfo->Platform=$platform;
		// }

		if (!empty($pushID)) {
			$userInfo->PushID = $pushID;
		}
		$userInfo->LastLoginDate = DM_Helper_Utility::getDateTime();
		$userInfo->save();

		$userInfo->addLog(DM_Model_Account_MemberLogs::ACTION_LOGIN, 'Use account: ' . $user . ($password === NULL ? ' open' : ' password'), '', $platform, $deviceID, true);
		$this->getSession()->MemberID = $userInfo->MemberID;
		$this->getSession()->Email = $userInfo->Email;

		//临时密钥
		$this->getSession()->Password = DM_Helper_Utility::createHash(32);
		$auth = DM_Helper_Utility::authcode("{$this->session->MemberID}\t{$this->session->Email}\t{$this->session->Password}", 'ENCODE');


		$configObj = DM_Controller_Front::getInstance()->getConfig();

		setcookie(self::AUTH_COOKIE, $auth, time() + 86400 * 365 * 2, '/', $configObj->phpSettings->session->cookie_domain, false, true);
		$_COOKIE[self::AUTH_COOKIE] = $auth;//注册后可马上获取登录信息

		$this->isLogin = true;
		$this->loginUser = $userInfo;

		if (in_array($platform, array(1, 2, 3))) {/*Android,ios,WEB端登录*/
			$member_id = $userInfo->MemberID;
			$sessKey = DM_Model_Account_Members::staticData($member_id, 'WebLoginSessKey');
			if (!empty($sessKey)) {
				$sessionRedis = DM_Module_Redis::getInstance('session');
				$sessionRedis->delete('PHPREDIS_SESSION:' . $sessKey);
				DM_Model_Account_Members::staticData($member_id, 'WebLoginSessKey', '');
			}
		}

		return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.login.ok"), array('MemberID' => $this->session->MemberID));
	}

	/**
	 * 退出
	 */
	public function logout()
	{
		$this->getSession()->MemberID = NULL;
		$this->getSession()->Email = NULL;
		$this->getSession()->Password = NULL;

		$configObj = DM_Controller_Front::getInstance()->getConfig();
		setcookie(self::AUTH_COOKIE, '', -time(), '/', $configObj->phpSettings->session->cookie_domain, false, true);

		$this->isLogin = false;
		$this->loginUser = NULL;
		$this->getSession()->unsetAll();
		if (empty($_SESSION)) {
			Zend_Session::destroy(true, false);
		}
		return $this->resultArray(DM_Controller_Action::STATUS_OK, $this->getLang()->_("api.user.msg.logout.ok"));
	}

	/**
	 * 是否登录状态
	 */
	public function isLogin()
	{
		if ($this->isLogin === NULL) {
			$authCookie = DM_Controller_Front::getInstance()->getInstance()->getHttpRequest()->getCookie(self::AUTH_COOKIE, '');
			if (empty($authCookie)) {
				$this->isLogin = false;
				if ($this->getSession()->MemberID) {
					$this->logout();
				}
			} else {
				$cookieUser = explode("\t", DM_Helper_Utility::authcode($authCookie));
				if (count($cookieUser) == 3 && $cookieUser[0] == $this->getSession()->MemberID
					&& $cookieUser[1] == $this->getSession()->Email
					&& $cookieUser[2] == $this->getSession()->Password
				) {
					$this->loginUser = DM_Model_Account_Members::create()->getByPrimaryId($this->getSession()->MemberID);
					if (!$this->loginUser) {
						$this->logout();
					} else {
						$this->isLogin = true;
					}
				} else {
					$this->isLogin = false;
				}
			}
		}

		return $this->isLogin;
	}

	/**
	 * 获取当前登录用户
	 *
	 * @return DM_Model_Row_Member
	 */
	public function getLoginUser()
	{
		if ($this->isLogin()) {
			if (!$this->loginUser) throw new Exception('Login Auth module exception.');
			return $this->loginUser;
		} else {
			return NULL;
		}
	}

	/**
	 * 获取session
	 *
	 * @return Zend_Session_Namespace
	 */
	protected function getSession()
	{
		if ($this->session === NULL) {
			throw new Exception('Please set auth session instace firstly.');
		}

		return $this->session;
	}

	/**
	 * 设置session
	 */
	public function setSession(Zend_Session_Namespace $session)
	{
		$this->session = $session;
		return $this;
	}

	/**`
	 * 获取单实例
	 *
	 * @return DM_Module_Auth
	 */
	public static function getInstance()
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}