<?php

class Admin_IndexController extends DM_Controller_Admin
{
	public function indexAction()
	{
		$this->view->userInfo = $this->auth->getIdentity();
		$rolesArray = $this->session->selfRoles;
		$this->view->isAdmin = in_array(1, $rolesArray) ? 1 : 0;

		//功能
		$featureModel = new Model_FeatureGroup();
		$features = $this->getPrivilegeModule($featureModel->getFeatures());
		$this->view->features = $features;
	}

	/**
	 * 模块权限处理
	 */
	private function getPrivilegeModule($modules)
	{
		$features = array();
		foreach ($modules as &$item) {
			if ($this->checkPrivilege($item['Controller'], $item['Action'])) {
				$features[$item['Name']][] = array(
					"Feature" => $item['Feature'],
					"Url" => $this->getHelper("Url")->url(array('controller' => $item['Controller'], 'action' => $item['Action']))
				);
			}
		}
		return $features;
	}

	/**
	 * 验证码
	 */
	public function yzmAction()
	{
		$this->getCaptchaAction();
	}

	/**
	 * 登录
	 */
	public function loginAction()
	{
		if (!$this->_request->isPost()) {
			$this->view->notice('请输入用户名和密码进行登录！');
		} else {
			$username = $this->_getParam('Username', '');
			$password = $this->_getParam('Password', '');
			$authCode = $this->_getParam('authcode', '');
			if (APPLICATION_ENV == 'production') {
				if (empty($username)) {
					$this->view->error('用户名不能为空');
				} elseif (empty($password)) {
					$this->view->error('密码不能为空');
				} elseif (empty($authCode)) {
					$this->view->error('验证码不能为空');
				} else {
					$this->view->error('用户名或密码错误');
				}
			} else {
				$request = $this->getRequest();
				if ($request->isPost()) {
					if ((APPLICATION_ENV != 'production') || $authCode == $_SESSION['imgcode']['word']) {
						if (!empty($username) && !empty($password)) {
							$adminModel = new DM_Model_Table_User_Admin();

							$authAdapter = $adminModel->getAuthAdapter($username, $password);
							// 进行验证
							$result = $this->auth->authenticate($authAdapter);
							if ($result->isValid()) {
								$data = $authAdapter->getResultRowObject(null, 'passwd');
								if ($data->Status == DM_Model_Table_User_Admin :: ENABLE_STATUS) {
									$data->Lasttime = time();
									$this->auth->getStorage()->write($data);
									// 更新登录时间
									$adminModel->updateLastLoginTime($data->AdminID);
									// 获取角色
									$roleModel = new DM_Model_Table_User_Role();
									$roles = $roleModel->getUserRolesArraybyUid($data->AdminID, DM_Model_Table_User_Role :: P_ADMIN);

									$this->session->selfRoles = $roles;

									$this->session->login_ip = $this->_request->getClientIp();
									$this->_redirect('/admin', array('exit' => true));
								} else {
									$this->auth->clearIdentity();
									$this->view->error('该用户已被禁用');
								}
							} else {
								switch ($result->getCode()) {
									case Zend_Auth_Result :: FAILURE_IDENTITY_NOT_FOUND:
										$msg = '用户名或密码错误，请重试！';
										break;
									case Zend_Auth_Result :: FAILURE_CREDENTIAL_INVALID:
										$msg = '用户名或密码错误，请重试！';
										break;
									case Zend_Auth_Result :: FAILURE_IDENTITY_AMBIGUOUS:
										$msg = '登录失败，已有多个用户使用相同的用户名登录！';
										break;
									default:
										$msg = '登录失败，错误原因未知，请联系技术！';
										break;
								}
								$this->view->error($msg);
							}
						} else {
							$this->view->error('用户名和密码不能为空');
						}
					} else {
						if (empty($authCode)) {
							$this->view->error('验证码不能为空');
						} else {
							$this->view->error('验证码输入错误');
						}
					}
				}
			}
		}
	}

	/**
	 * 退出系统
	 */
	public function logoutAction()
	{
		if ($this->auth->hasIdentity()) {
			$this->auth->clearIdentity();
		}
		$this->_redirect('/admin/index/login');
	}
} 
