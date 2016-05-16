<?php

class Admin_MemberController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//加载枚举语言配置
		$config = DM_Controller_Front::getInstance()->getIniInfo('member');
		$this->view->memberEnum = $config;

		//会员等级
		//$this->view->levelOption = $this->getSelectOption('DM_Model_Table_Levels', 'levels', 'LevelID', 'LevelName',true);

	}

	public function listAction()
	{
		//关闭视图
		$this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('rows', 50);

		$fieldOneName = $this->_getParam('fieldOneName');
		$fieldOneValue = $this->_getParam('fieldOneValue');
		$emailStatus = $this->_request->getParam('emailStatus', '');
		$mobileStatus = $this->_request->getParam('mobileStatus', '');
		$status = $this->_request->getParam('status', -1);
		$start_date = $this->_getParam('start_date', '');
		$end_date = $this->_getParam('end_date', '');

		$memberModel = new DM_Model_Account_Members('udb');
		$select = $memberModel->select()->setIntegrityCheck(false);

		$select->from('members as m');
		$db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->db->dbname;
		$select->joinLeft("{$db}.member_authenticate as ma", "m.MemberID=ma.MemberID", "AuthenticateType");

		if (!empty($fieldOneName) && !empty($fieldOneValue)) {
			if (in_array($fieldOneName, array('MemberID', 'UserName', 'Email', 'MobileNumber'))) {
				$select->where("m." . $fieldOneName . ' = ?', $fieldOneValue);
			}
		}

		if (!empty($emailStatus)) {
			$select->where('m.EmailVerifyStatus = ?', $emailStatus);
		}

		if (!empty($mobileStatus)) {
			$select->where('m.MobileVerifyStatus = ?', $mobileStatus);
		}

		if ($status != -1) {
			$select->where('m.Status = ?', $status);
		}

		if (!empty($start_date)) {
			$select->where('m.RegisterTime >= ? ', $start_date);
		}
		if (!empty($end_date)) {
			$select->where('m.RegisterTime <= ? ', $end_date);
		}

		$total_sql = $select->__toString();
		$total_sql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $total_sql);

		//总记录数
		$totalCount = $memberModel->getAdapter()->fetchOne($total_sql);

		$select->order('m.MemberID desc');
		$select->limitPage($pageIndex, $pageSize);
		$list = $select->query()->fetchAll();

		//获取达人头衔信息
		$is_best = array();
		foreach ($list as $val)
			$val['IsBest'] == DM_Model_Account_Members::BEST_STATUS_TRUE && $is_best[] = $val['MemberID'];

		$bestModel = new Model_Best_Best();
		$best_info = $bestModel->getBestInfoByMemberID($is_best, $bestModel::STATUS_TRUE);
		$famousModel = new Model_Famous();
		foreach ($list as &$val) {
			$val['BestInfo'] = "";
			if (isset($best_info[$val['MemberID']]))
				foreach ($best_info[$val['MemberID']] as $info)
					$val['BestInfo'] .= "，{$info['Name']}";

			$val['BestInfo'] = trim($val['BestInfo'], ",");
			$val['IsJoinedFamous'] = $famousModel->hasJoined($val['MemberID']);

		}


		$this->_helper->json(array('total' => $totalCount, 'rows' => $list));
	}

	/**
	 * 查看会员信息
	 */
	public function viewAction()
	{
		//加载枚举语言配置
		$config = DM_Controller_Front::getInstance()->getIniInfo('member');
		$this->view->memberEnum = $config;

		$member_id = $this->_getParam('member_id');
		//会员信息
		$memberModel = new DM_Model_Account_Members('udb');
		$memberInfo = $memberModel->find($member_id)->current();
		if (empty($memberInfo)) {
			exit('会员信息不存在');
		}
		$this->view->memberInfo = $memberInfo;
	}

	/**
	 * 发送验证邮件
	 */
	public function sendValidateEmailAction()
	{
		$inviteCodeModel = new DM_Model_Table_Members();
		$email = $this->_getParam('email');
		$member_id = $this->_getParam('member_id');
		$flag = $inviteCodeModel->sendMailcode($email, $member_id);
		if ($flag) {
			$log = new DM_Model_Table_AdminLogs();
			$content = '发送激活邮件';
			$log->addLog($this->adminInfo->AdminID, $member_id, 'MemberVerifys', 'VerifyCode', $content);
		}
		$msg = $flag ? '激活邮件已发送' : '未发送成功';
		return $this->returnJson($flag, $msg);
	}

	/**
	 * 手机绑定状态变更
	 */
	public function sendValidateSmsAction()
	{
		$member_id = $this->_getParam('member_id');
		$status = $this->_getParam('status');
		$memberModel = new DM_Model_Table_Members();

		$flag = 0;
		$msg = '';
		try {
			$memberModel->update(array('MobileVerifyStatus' => $status), array('MemberID = ?' => $member_id));
			$flag = 1;
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}
		if ($flag) {
			$log = new DM_Model_Table_AdminLogs();
			$content = '更改手机绑定状态为' . $status;
			$log->addLog($this->adminInfo->AdminID, $member_id, 'members', 'MobileVerifyStatus', $content);
		}
		return $this->returnJson($flag, $msg);
	}

	/**
	 *邮箱验证状态变更
	 */
	public function setEmailValidateAction()
	{
		$member_id = $this->_getParam('member_id');
		$status = $this->_getParam('status');
		$memberModel = new DM_Model_Table_Members();

		$flag = 0;
		$msg = '';
		try {
			$memberModel->update(array('EmailVerifyStatus' => $status), array('MemberID = ?' => $member_id));
			$flag = 1;
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}
		if ($flag) {
			$log = new DM_Model_Table_AdminLogs();
			$content = '更改邮箱验证状态为' . $status;
			$log->addLog($this->adminInfo->AdminID, $member_id, 'members', 'EmailVerifyStatus', $content);
		}
		return $this->returnJson($flag, $msg);
	}

	/**
	 * 设置身份验证信息
	 */
	public function setIdVerifyStatusAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$member_id = $this->_getParam('member_id');
		$status = $this->_getParam('status');
		$memberModel = new DM_Model_Table_Members();

		$flag = 0;
		$msg = '';
		$adapter = $memberModel->getAdapter();
		$adapter->beginTransaction();
		try {
			$memberInfo = $memberModel->getById($member_id);
			if ($memberInfo['IdcardVerifyStatus'] == $status) {//原状态一致
				$this->returnJson(1, $msg);
				exit;
			}
			$ret = $memberModel->update(array('IdcardVerifyStatus' => $status), array('MemberID = ?' => $member_id));


			$log = new DM_Model_Table_AdminLogs();
			$content = '更改身份验证状态为' . $status;
			$log->addLog($this->adminInfo->AdminID, $member_id, 'members', 'IdcardVerifyStatus', $content);
			$adapter->commit();
			$flag = 1;
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$adapter->rollBack();
		}
		return $this->returnJson($flag, $msg);
	}

	/**
	 * 设置用户等级
	 */
	public function updateLevelAction()
	{
		$member_id = $this->_getParam('member_id');
		if (empty($member_id)) {
			$this->returnJson(false, '用户ID不存在');
		}
		$memberModel = new DM_Model_Table_Members();

		$level_id = $this->_getParam('level_id');

		$res = $memberModel->update(array('LevelID' => $level_id), array('MemberID =?' => $member_id));
		if ($res) {

			$log = new DM_Model_Table_AdminLogs();
			$content = '更改用户等级为' . $level_id;
			$log->addLog($this->adminInfo->AdminID, $member_id, 'members', 'LevelID', $content);

			$this->returnJson(true, '操作成功');
		} else {
			$this->returnJson(false, '修改失败');
		}


	}

	/**
	 * 处理身份证图片
	 */
	public function idcardImgAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$url = $this->_getParam('url');
		if (empty($url)) {
			return false;
		}
		$memberModel = new DM_Model_Table_Members();
		$memberModel->getIdcardImg(urldecode(base64_decode($url)));
	}

	/**
	 * 登录到前台
	 */
	public function loginfrontAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$member_id = $this->_getParam('member_id');

		//获取加密码
		$configObj = new Zend_Config_Ini(APPLICATION_PATH . '/configs/system.ini', 'encrypt');
		$config = $configObj->toArray();
		$prefixCode = $config['prefix_code'];

		$str1 = date('Ymd', time()) . $member_id;
		$str2 = md5($prefixCode . $str1);

		header('http://coinweb.cn/front/index/syslogin?str1=' . $str1 . '&str2=' . $str2);
	}

	/**
	 * 禁用或启用
	 */
	public function statusAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$member_id = intval($this->_getParam('member_id', 0));
		if ($member_id <= 0) {
			$this->returnJson(0, '参数错误');
		}

		$status = $this->_getParam('status', -1);
		if (!in_array($status, array(0, 1))) {
			$this->returnJson(0, '状态参数错误');
		}

		if ($status == 0) {
			$easeModel = new Model_IM_Easemob();
			$ret = $easeModel->disconnect($member_id);
		}

		$memberModel = new DM_Model_Account_Members('udb');
		$res = $memberModel->updateStatus($member_id, $status);
		if ($res) {
			$this->returnJson(1, '操作成功');
		} else {
			$this->returnJson(0, '操作失败');
		}

	}

	/*
	 *用户注册量统计
	 */
	public function registerStatAction()
	{
		if ($this->_request->isPost()) {
			$this->_helper->viewRenderer->setNoRender();
			$pageIndex = $this->_getParam('page', 1);
			$pageSize = $this->_getParam('rows', 50);

			$start_date = $this->_getParam('start_date', date('Y-m-d', strtotime('-1 week')));
			$end_date = $this->_getParam('end_date', date('Y-m-d', time()));

			$memberModel = new DM_Model_Account_Members();

			$select = $memberModel->select()->from('members', array("DATE_FORMAT(RegisterTime,'%Y-%m-%d') as RegisterTime", "COUNT(MemberID) as RegisterCount"));
			$select->where("FromSystem = 1 ")->group("DATE_FORMAT(RegisterTime,'%Y-%m-%d')");

			if (!empty($start_date)) {
				$select->where("RegisterTime >= ?", $start_date);
			}

			if (!empty($end_date)) {
				$select->where("RegisterTime <= ?", $end_date);
			}
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

			//总条数
			$total = $memberModel->getAdapter()->fetchOne($countSql);

			//排序
			$select->order("RegisterTime desc");

			$select->limitPage($pageIndex, $pageSize);

			//列表
			$results = $memberModel->fetchAll($select)->toArray();
			$this->escapeVar($results);
			$this->_helper->json(array('total' => $total, 'rows' => $results));
		}

	}

	/**
	 * 广告链接
	 */
	public function adsLinkAction()
	{
		$member_id = intval($this->_getParam('member_id', 0));
		$h5Url = $this->_request->getScheme() . '://' . $this->_request->getHttpHost() . "/api/public/member-page/mid/";
		$schemaUrl = "caizhu://caizhu/userInfo?id=";
		$this->view->h5Url = $h5Url . $member_id;
		$this->view->schemaUrl = $schemaUrl . $member_id;
	}

	//验证参数
	protected $filter_fields = array(
		"m" => array("MemberID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"t" => array("BestType", "1,2", "状态参数错误！", DM_Helper_Filter::EXISTS_VALIDATE, "in"),
		"ti" => array("TID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 新增达人
	 * 普通达人需要验证
	 * 签约达人不需要验证
	 */
	public function addBestAction()
	{
		if ($this->isPost()) {
			$this->filterParam(array('m', 't', 'ti'));
			$memberModel = new DM_Model_Account_Members();
			$bestModel = new Model_Best_Best();

			$is_best = $memberModel->getMemberInfoCache($this->_param['MemberID'], "IsBest");
			$db = $bestModel->getAdapter();
			$db->beginTransaction();

			try {
				//理财师不允许认证达人
				$authenticateModel = new Model_Authenticate();
				$res = $authenticateModel->getInfoByMemberID($this->_param['MemberID'], null, 'AuthenticateType');
				if ($res && $res['AuthenticateType'] != 1)
					throw new Exception("不允许添加达人！");

				//修改达人类型和状态
				if ($is_best == $memberModel::BEST_STATUS_FAIL) {
					if (!isset($this->_param['BestType']))
						throw new Exception("状态参数错误！");

					$res = $memberModel->updateInfo($this->_param['MemberID'],
						array(
							"IsBest" => ($this->_param['BestType'] == $memberModel::BEST_TYPE_NORMAL)
								? $memberModel::BEST_STATUS_FAIL
								: $memberModel::BEST_STATUS_TRUE,
							"BestType" => $this->_param['BestType']));

					if ($res===false)
						throw new Exception("新增失败");

					$memberModel->deleteCache($this->_param['MemberID']);
				}

				//不能重复添加
				$res = $bestModel->fetchRow(array(
					"MemberID=?" => $this->_param['MemberID'],
					"TID=?" => $this->_param['TID'],
					"Status !=?" => $bestModel::STATUS_FIAL
				));
				if ($res)
					throw new Exception("达人头衔已经添加");

				//新增
				$param = array(
					'MemberID' => $this->_param['MemberID'],
					'InviteCode' => DM_Helper_String::randString(),
					'TID' => $this->_param['TID'],
					'Status' => ($this->_param['BestType'] == $memberModel::BEST_TYPE_NORMAL)
						? Model_Best_Best::STATUS_APPROVE
						: Model_Best_Best::STATUS_TRUE,
					'CreateTime' => date('Y-m-d H:i:s')
				);

				//签约用户增加修改时间
				if ($this->_param['BestType'] == $memberModel::BEST_TYPE_SIGNED)
					$param['UpdateTime'] = date('Y-m-d H:i:s');

				$res = $bestModel->insert($param);
				$bid = $db->lastInsertId();
				if ($res===false)
					throw new Exception("新增失败");

				//给用户发认证连接
				if ($this->_param['BestType'] == $memberModel::BEST_TYPE_NORMAL) {
					$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
					$url = $this->getFullHost() . "/api/public/topman";

					$bestTitleModel = new Model_Best_BestTitle();
					$title_name = $bestTitleModel->getInfoByID($param['TID'], "Name");
					$url .= "?bID={$bid}&info=" . $bestModel->getHash($param) .
						"&tID={$param['TID']}&tName=" . urlencode($title_name['Name']) . "&caizhunotshowright=1";
					$content = "点亮财猪达人标识即可获得更多达人权限！立刻进入！";
					$ext = array(
						"share_chat_msg_type" => 104,
						"share_chat_msg_type_desc" => $content,
						"share_chat_msg_type_id" => $url,
						"share_chat_msg_type_image" => "http://img.caizhu.com/caizhu-log-180_180.png",
						"share_chat_msg_type_name" => "达人头衔邀请",
						"share_chat_msg_type_title" => "达人头衔邀请",
						"share_chat_msg_type_url" => $url,
					);
					$easeModel = new Model_IM_Easemob();
					$easeModel->yy_hxSend(array($this->_param['MemberID']), '邀请你成为财猪达人', 'txt', 'users', $ext, $sysMemberID);
				}
				$db->commit();
				$this->succJson();
			} catch (Exception $e) {
				$db->rollBack();
				$this->failJson($e->getMessage());
			}
		} else {
			$this->filterParam(array('m'));
			$memberModel = new DM_Model_Account_Members();
			$this->view->member_info = array(
				"MemberID" => $this->_param['MemberID'],
				"IsBest" => $memberModel->getMemberInfoCache($this->_param['MemberID'], "IsBest"),
				"BestType" => $memberModel->getMemberInfoCache($this->_param['MemberID'], "BestType"),
			);

			$bestTitleModel = new Model_Best_BestTitle();
			$this->view->best_titles = $bestTitleModel->getAllTitle();
		}
	}
	
	/*
     *添加用户
	 */
	public function addAction()
	{
		if($this->isPost()){
			$email = trim($this->_getParam('email', ''));
	        $password = trim($this->_getParam('password', ''));
	        $memberModel=new DM_Model_Account_Members();

	        if (!$email || !DM_Helper_Validator::isEmail($email)){
	            $this->returnJson(0,'请填写正确的邮箱格式！');
	        }

	        if($memberModel->getByEmail($email)){
	        	$this->returnJson(0,'该邮箱已存在！');
	        }

	        if(!$password || !DM_Helper_Validator::checkPassword($password)){
	            $this->returnJson(0, '请输入正确的密码！');
	        }

	        $user=$memberModel->createRow();
            $user->Email = $email;
            $user->EmailVerifyStatus = 'Verified';
            $user->createPassword($password);
            $user->FromSystem = 2;
	        $user->RegisterTime = DM_Helper_Utility::getDateTime();
	        $user->save();
	        
	        $this->returnJson(1, '添加成功！');
    	}
	}

	/*
     *添加到名人堂
	 */
	public function addFamousAction()
	{
		$memberID = intval($this->_getParam('MemberID', 0));
		if($this->isPost()){
			$famousModel = new Model_Famous();
			$memberID = intval($this->_getParam('MemberID', 0));
			if($memberID <= 0){
				$this->returnJson(0,'参数错误！');
			}

			$count = $famousModel->hasJoined($memberID);
			if($count > 0){
				$this->returnJson(0,'该用户已添加到名人堂！');
			}

            $DetailUrl = trim($this->_getParam('DetailUrl', ''));
	       // $Experience = trim($this->_getParam('Experience', ''));  	        
            $IsShowColumn = intval($this->_getParam('IsShowColumn',0));
            $IsShowCounsel = intval($this->_getParam('IsShowCounsel',0));

	        if (empty($DetailUrl)){
	            $this->returnJson(0,'链接地址不能为空！');
	        }
	        // if (empty($Experience)){
	        //     $this->returnJson(0,'从业经历不能为空！');
	        // }

	        $upload_image = isset($_FILES['image'])?$_FILES['image']:'';
            if (empty($upload_image)) {
                $this->returnJson(0,'请上传背景图片！');
            }

	        $image_src = $this->processUpload($upload_image);

            $qiniu = new Model_Qiniu();
            $token = $qiniu->getUploadToken();
            $uploadMgr = $qiniu->getUploadManager();
            
            $file = realpath(APPLICATION_PATH.'/../public/upload'.$image_src);
            list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
            if ($err !== null) {
                var_dump($err);
            } else {
                //var_dump($ret);
                $ImgUrl = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }


	        $famousParam = array(
	        		'MemberID'=>$memberID,
	        		//'Experience'=>$Experience,
	        		'DetailUrl'=>$DetailUrl,
	        		'ImgUrl'=>$ImgUrl,
	        		'IsShowColumn'=>$IsShowColumn,
	        		'IsShowCounsel'=>$IsShowCounsel,
	        		'AddTime'=>date('Y-m-d H:i:s',time())
	        	);

			$famousModel->insert($famousParam);
	        
	        $this->returnJson(1, '添加成功！');
    	}

    	$columnModel = new Model_Column_Column();
    	$counselModel = new Model_Counsel_Counsel();
    	$columnInfo = $columnModel->getMyColumnInfo($memberID,1);
    	$counselInfo = $counselModel->getMyCounselInfo($memberID,1);
    	$this->view->memberID = $memberID;
    	$this->view->hasColumn = !empty($columnInfo)?1:0;
    	$this->view->hasCounsel = !empty($counselInfo)?1:0;



	}


	/**
     * 处理图片上传
     * @return string
     */
    protected function processUpload($upload_image)
    {
        $src = '';
        if(isset($upload_image)){
            $upFile = $upload_image;
            $allowTypes = array('image/jpeg'=>'jpeg','image/gif'=>'gif','image/png'=>'png');
            if(!array_key_exists($upFile['type'],$allowTypes)){
                $this->returnJson(0,'图片格式不正确');
            }
    
            //文件类型
            $fileType = $allowTypes[$upFile['type']];
            //构造文件名
            $curTimestamp = time();
            $dir = '/topic/';
            if(!file_exists(APPLICATION_PATH.'/../public/upload'.$dir)){
                mkdir(APPLICATION_PATH.'/../public/upload'.$dir,0775,true);
            }
            $fileName = $dir.$curTimestamp.'_'.rand(10000,99999).'.'.$fileType;
            $fullPath = APPLICATION_PATH.'/../public/upload'.$fileName;
    
            if(is_uploaded_file($upFile['tmp_name'])){
                if(!move_uploaded_file($upFile['tmp_name'], $fullPath)){
                    $this->returnJson(0,'移动文件出错啦');
                }
                //$src = 'http://'.$this->_request->getHttpHost().$fileName;
                $src = $fileName;
            }else{
                $this->returnJson(0,'出错啦');
            }
        }
    
        return $src;
    }
}
