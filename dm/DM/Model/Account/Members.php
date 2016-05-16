<?php

/**
 * 用户表对象基类
 *
 * @author Kitty
 * @since 2015/01/05
 */
class DM_Model_Account_Members extends DM_Model_Table
{
	//用户达人状态值
	const BEST_STATUS_FAIL = 0;
	const BEST_STATUS_TRUE = 1;
	const BEST_STATUS_APPROVE = 2;

	//用户达人类型值
	const BEST_TYPE_NORMAL = 1;
	const BEST_TYPE_SIGNED = 2;

	protected $_rowClass = "DM_Model_Row_Member";
	protected $_name = 'members';
	protected $_primary = 'MemberID';


	/**
	 * 初始化数据库
	 */
	public function __construct()
	{
		$udb = DM_Controller_Front::getInstance()->getDb('udb');
		$this->_setAdapter($udb);
	}

	/**
	 * 获取匿名财猪号
	 */
	public function getAnonymousUserName($memberID, $changeAnother = false)
	{
		$field = 'AnonymouseUserName';
		$userName = self::staticData($memberID, $field);
		$userNameArr = array('无名神僧', '张三丰', '阿青', '萧峰', '郭靖', '杨过', '张无忌', '令狐冲', '段誉', '虚竹', '石破天', '无涯子',
			'天山童姥', '独孤求败', '东方不败', '李秋水', '萧远山', '慕容博', '王重阳', '周伯通', '欧阳锋', '洪七公', '黄药师', '一灯大师',
			'鸠摩智', '风清扬', '方正大师', '任我行', '岳不群', '左冷禅', '裘千仞', '黄蓉', '金轮法王', '慕容复', '小龙女', '向问天', '三渡', '林平之',
			'胡斐', '袁承志', '血刀老祖', '陈家洛', '夏雪宜', '狄云', '冲虚道长', '穆人清', '段正淳', '鹿杖客', '鹤笔翁', '梅超风', '成昆', '赵敏', '杨逍', '范遥',
			'程灵素', '谢逊', '莫大', '任盈盈', '灭绝师太', '周芷若', '丁春秋', '殷天正', '韦一笑', '田伯光', '李文秀', '康熙', '丘处机', '黛绮丝', '谢烟客', '胡一刀',
			'苗人凤', '袁士霄', '游坦之', '霍青桐', '香香公主', '萧中慧', '张召重', '无尘道长', '文泰来', '赵半山', '洪安通', '玉真子', '杨康', '九难', '阿朱', '阿紫',
			'龙木岛主', '李莫愁', '何铁手', '岳灵珊', '袁紫衣', '丁典', '陈近南', '殷离', '双儿', '王语嫣', '段延庆', '柯镇恶', '小昭', '木婉清', '叶二娘', '岳老三', '温青青',
			'鳌拜', '宋青书', '朱聪', '韦小宝'
		);
		if (empty($userName)) {
			$userName = $userNameArr[array_rand($userNameArr)];
			self::staticData($memberID, $field, $userName);
		} else {
			if ($changeAnother) {
				while (true) {
					$tmpUserName = $userNameArr[array_rand($userNameArr)];
					if ($tmpUserName != $userName) {
						$userName = $tmpUserName;
						self::staticData($memberID, $field, $userName);
						break;
					}
				}
			}
		}
		return $userName;
	}

	/**
	 * 获取匿名头像
	 */
	public function getAnonymousAvatar($memberID, $changeAnother = false)
	{
		$field = 'AnonymouseAvatar';
		$avatar = self::staticData($memberID, $field);
		$avatarArr = array('blue', 'bright-red', 'green', 'orange', 'orange-red', 'pink', 'violet', 'yellow');
		if (empty($avatar)) {
			$avatar = $avatarArr[array_rand($avatarArr)];
			self::staticData($memberID, $field, $avatar);
		} else {
			if ($changeAnother) {
				while (true) {
					$tmpAvatar = $avatarArr[array_rand($avatarArr)];
					if ($tmpAvatar != $avatar) {
						$avatar = $tmpAvatar;
						self::staticData($memberID, $field, $avatar);
						break;
					}
				}
			}
		}
		$avatar = 'http://img.caizhu.com/avatar_' . $avatar . '.png';
		return $avatar;
	}

	/**
	 * 获取单条会员数据
	 *
	 * @param int $id
	 */
	public function getById($id)
	{
		return parent::getByPrimaryId($id);
	}


	private function getCacheKey($memberID)
	{
		return 'MemberInfo:' . $memberID;
	}


	/**
	 *  注册IM 用户
	 * @param array $memberInfo
	 */
	public function registerIMUser(&$memberInfo)
	{
		if (empty($memberInfo['IMUserName']) || empty($memberInfo['IMPassword'])) {
			$config = DM_Controller_Front::getInstance()->getConfig();
			$strPrefix = $config->chat->settings->usernamePrefix;

			$IMUserName = $strPrefix . $memberInfo['MemberID'];
			$IMPassword = md5($memberInfo['Salt'] . $memberInfo['Password']);

			$chatModel = new Model_IM_Easemob();
			$res = $chatModel->accreditRegister(array('username' => $IMUserName, 'password' => $IMPassword));
			$resArr = json_decode($res, true);
			if (!empty($resArr['entities'][0])) {
				$this->update(array('IMUserName' => $IMUserName, 'IMPassword' => $IMPassword, 'IMRegisterTime' => date('Y-m-d H:i:s', time())), array('MemberID = ?' => $memberInfo['MemberID']));
				$memberInfo['IMUserName'] = $IMUserName;
				$memberInfo['IMPassword'] = $IMPassword;
			}
		}
		return true;
	}

	/**
	 * 获取会员信息
	 * @param $memberID
	 * @param null $field
	 * @return array|mixed
	 * @throws Exception
	 */
	public function getMemberInfoCache($memberID, $field = null)
	{
		$cacheKey = $this->getCacheKey($memberID);
		$redisObj = DM_Module_Redis::getInstance();

		if (is_null($field)) {
			$member_info = $redisObj->hGetAll($cacheKey);
		} else {
			$field = (array)$field;
			$member_info = $redisObj->hmGet($cacheKey, $field);
		}

		if (current($member_info) === false) {
			$member_info = $this->getById($memberID);//->toArray();
			$member_info = is_null($member_info) ? array() : $member_info->toArray();
			if (!empty($member_info)) {
				$redisObj->hMSet($cacheKey, $member_info);
				$redisObj->expire($cacheKey, 7 * 86400);
			}
			!is_null($field) && $member_info = array_intersect_key($member_info, array_flip($field));
		}

		return count($member_info) == 1 ? current($member_info) : (count($field) > 1 ? $member_info : '');
	}

	/**
	 * 删除会员缓存
	 * @param int $memberID
	 */
	public function deleteCache($memberID)
	{
		$cacheKey = $this->getCacheKey($memberID);
		$redisObj = DM_Module_Redis::getInstance();
		$redisObj->del($cacheKey);
	}

	/**
	 * 对update 进行封装，处理缓存
	 */
	public function update(array $data, $where)
	{
		$ret = parent::update($data, $where);
		if (is_array($where)) {
			foreach ($where as $key => $val) {
				if (substr($key, 0, 8) == 'MemberID') {
					$this->deleteCache($val);
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 *  更新username
	 * @param int $memberID
	 * @param int $userName
	 */
	public function updateUserName($memberID, $userName)
	{
		$this->update(array('UserName' => $userName), array('MemberID = ?' => $memberID));
// 		$cacheKey = $this->getCacheKey($memberID);
// 		$redisObj = DM_Module_Redis::getInstance();
// 		$redisObj->hset($cacheKey, 'UserName', $userName);
	}

	/**
	 * 获取头像
	 * @param int $memberID
	 */
	public function getMemberAvatar($memberID)
	{
		$avatar = $this->getMemberInfoCache($memberID, 'Avatar');
		if (empty($avatar)) {
			$avatar = 'http://img.caizhu.com/default_tx.png';
		}
		return $avatar;
// 		$request=DM_Controller_Front::getInstance()->getHttpRequest();
// 		return $request->getScheme().'://'.$request->getHttpHost().'/static/images/avatar.png';
	}

	/**
	 * 根据email获取用户信息
	 * @param string $email
	 *
	 */
	public function getByEmail($email)
	{
		if (!$email) {
			return NULL;
		}
		$res = $this->fetchRow($this->select()->where('Email =?', $email));
		return $res;
	}

	/**
	 * 根据MOBILE获取用户信息
	 * @param string $mobile
	 *
	 */
	public function getByMobile($mobile)
	{
		if (!$mobile) {
			return NULL;
		}
		$res = $this->fetchRow($this->select()->where('MobileNumber =?', $mobile));
		return $res;
	}


	/**
	 * 根据username获取用户信息
	 * @param string $username
	 *
	 */
	public function getByUsername($username, $memberID = 0)
	{
		if (!$username) {
			return NULL;
		}
		$select = $this->select()->where('UserName =?', $username);
		if ($memberID) {
			$select->where('MemberID != ?', $memberID);
		}
		$res = $this->fetchRow($select);
		return $res;
	}


	/**
	 * 设置新密码
	 * @param int $member_id
	 */
	public function updatePassword($member_id, $password)
	{
		$password = $this->encodePassword($password);
		//检查是否为原密码
		$select = $this->select();
		$select->from($this->_name, array('count(*) as num'))
			->where("MemberID = ?", $member_id)
			->where("Password = ?", $password);
		$num = $this->_db->fetchOne($select);
		if ($num) {
			return $num;
		} else {
			$res = $this->update(array('Password' => $password), array('MemberID = ?' => $member_id));
			return $res;
		}
	}

	/**
	 * 设置支付密码
	 * @param int $member_id
	 */
	public function updateTradePassword($member_id, $password)
	{
		$password = $this->encodePassword($password);
		//检查密码是否正确
		$select = $this->select();
		$select->from($this->_name, array('count(*) as num'))
			->where("MemberID = ?", $member_id)
			->where("Password = ?", $password);
		$num = $this->_db->fetchOne($select);
		if ($num) {
			return true;
		}
		$res = $this->update(array('RefundPassword' => $password), array('MemberID = ?' => $member_id));
		return $res;
	}

	/**
	 * 认证邮箱
	 */
	public function validatedEmail($id, $email, $type = 1)
	{
		$data = array();
		if ($type == 1) {
			$data['EmailVerifyStatus'] = 'Verified';
			$data['Email'] = $email;
			$data['IsUnderBind'] = 0;
		}
		if ($type == 8) {
			$data['IsUnderBind'] = 2;
		}
		return $this->update($data, array('MemberID = ?' => $id));
	}

	/**
	 *验证用户手机
	 */
	public function verifyMobile($member_id, $mobile = '', $type = 2)
	{
		$data = array();
		if ($type == 2) {
			$data['MobileVerifyStatus'] = 'Verified';
			$data['MobileNumber'] = $mobile;
			$data['IsUnderBind'] = 0;
		}
		if ($type == 7) {
			$data['IsUnderBind'] = 1;
		}
		return $this->update($data, array('MemberID = ?' => $member_id));
	}

	/**
	 *解除绑定
	 */
	public function  unbound($account, $member_id)
	{
		if ($account == 'email') {
			return $this->update(array('EmailVerifyStatus' => 'Pending'), array('MemberID = ?' => $member_id));
		} elseif ($account == 'mobile') {
			return $this->update(array('MobileVerifyStatus' => 'Pending', 'IsProtect' => 0), array('MemberID = ?' => $member_id));
		}
	}

	/**
	 * 检测邮箱格式
	 */
	private function checkmail($email)
	{
		if (preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $email)) {
			list($username, $domain) = @split('@', $email);
			if (!checkdnsrr($domain, 'MX')) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * 检测密码格式
	 */
	public function verifyPassword($password)
	{
		if (preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/', $password, $pass)) {
			return true;
		}
		return false;
	}

	/**
	 * 验证用户支付密码是否正确
	 */
	public function verifyRefundPassword($member_id = 0, $password = '')
	{
		if (!$member_id || !$password) {
			return false;
		}
		$info = $this->fetchRow(array('MemberID=?' => $member_id));

		if ($info['refund_password'] == '') {
			return false;
		}

		if ($info['refund_password'] != $this->encodePassword($password)) {
			return false;
		}
		return true;
	}

	/**
	 * 获取用户ip地区信息
	 */
	public function getAreaByIp($ip)
	{
		if (!$ip) {
			return false;
		}

		try {
			$result = @file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip);
		} catch (Exception $e) {
			return false;
		}

		if ($result) {
			$data = json_decode($result, true);
		}

		return $data;
	}

	/**
	 * 检查同一IP注册次数
	 */
	public function getIpNum($ip)
	{
		$ip = $this->_db->quote($ip);
		$sql = "select count(*) from members where LastLoginIp = " . $ip;
		return $this->_db->fetchOne($sql);
	}


	/**
	 *启用或禁用
	 */
	public function updateStatus($member_id, $status = 1)
	{
		return $this->update(array('Status' => $status), array('MemberID = ?' => $member_id));
	}

	/**
	 *开启或关闭账号保护
	 */
	public function updateProtect($member_id, $isprotect = 1)
	{
		//var_dump($member_id);var_dump($isprotect);exit;
		return $this->update(array('IsProtect' => $isprotect), array('MemberID = ?' => $member_id));
	}

	/*
	 *编辑用户资料
	 */
	public function updateInfo($member_id, $params)
	{
		return $this->update($params, array('MemberID = ?' => $member_id));
	}

	/**
	 * 获取用户名字
	 * @return string '' | UserName
	 */
	public function getUserName($memberID = null, $getBy = null)
	{
		if (!$memberID) {
			return '';
		}
		if ($getBy == $memberID) {
			// return '我';
		}
		$redis = DM_Module_Redis::getInstance();
		if (!$userName = $this->getMemberInfoCache($memberID, 'UserName')) {
			if ($memberInfo = $this->getById($memberID)) {
				$redis->hMset('MemberInfo:' . $memberID, $memberInfo);
				return $memberInfo['UserName'];
			}
			return '';
		}
		return $userName;
	}

	/**
	 * 会员统计数据  获取或设置
	 */
	public static function staticData($memberID, $field = null, $val = null)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$statisticKey = 'Statistic:Member' . $memberID;
		$result = null;
		if (!is_null($field)) {
			if (is_array($field)) {
				if ($val == -1) {
					$result = $redisObj->hmset($statisticKey, $field);
				} else {
					$result = $redisObj->hmget($statisticKey, $field);
				}
			} else {
				if (is_null($val)) {
					$result = $redisObj->hget($statisticKey, $field);
				} else {
					$result = $redisObj->hset($statisticKey, $field, $val);
				}
			}
		} else {
			$result = $redisObj->hgetall($statisticKey);
		}
		return $result;
	}

	/**
	 * 根据查询条件获取信息
	 * Row时特殊处理  只有一个子段时返回字符串
	 * @param array $where
	 * @param null $fields
	 * @param string $type Row|Assoc|Pairs($key=>$val)|All
	 * @param string|null $order 如：CID desc,MemberID asc
	 * @param null $limit
	 * @return array|mixed|null
	 */
	public function getInfoMix(array $where = array(), $fields = null, $type = "Row", $order = null, $limit = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		foreach ($where as $key => $val)
			$select->where($key, $val);

		if (!is_null($order)) {
			$orderByArr = explode(',', $order);
			foreach ($orderByArr as $r) {
				$select->order($r);
			}
		}

		if (!is_null($limit))
			$select->limit($limit);

		$func = "fetch{$type}";
		$info = $this->_db->$func($select);
		//这里row 返回false 其它返回array{} count(1) 不返回false 返回0
		return $info === false ? null :
			($type == 'Row' && count($info) == 1 ? current($info) : $info);
	}
}
