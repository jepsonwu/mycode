<?php

/**
 * 微信用户表
 * User: jepson <jepson@duomai.com>
 * Date: 16-4-1
 * Time: 下午4:51
 */
class Model_Activity_WechatUser extends Model_Common_Common
{
	protected $_name = "activity_wechat_user";
	protected $_primary = "UID";


	/**
	 * 创建更新微信用户
	 * @param $user_info
	 * @param $app_id
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public function addUserInfo($user_info, $app_id)
	{
		$userOpenidModel = new Model_Activity_WechatUserOpenid();
		$uid = null;

		//Unionid 有没有openid都有两种情况
		//只有openid没有才需要更新openid表

		$data = array(
			"HeadImgUrl" => $user_info['headimgurl'],
			"Country" => $user_info['country'],
			"City" => $user_info['city'],
			"Province" => $user_info['province'],
			"Sex" => $user_info['sex'],
			"Nickname" => $user_info['nickname'],
			"Unionid" => $user_info['unionid'],
			"Language" => $user_info['language'],
			"Subscribe" => $user_info['subscribe'],
			"SubscribeTime" => date("Y-m-d H:i:s", $user_info['subscribe_time']),
			"Remark" => $user_info['remark'],
			"TagidList" => $user_info['tagid_list'],
		);

		//Unionid 是否存在
		!empty($user_info['unionid']) &&
		$uid = $this->getInfoMix(array("Unionid =?" => $user_info['unionid']), "UID");

		//app_id 是否存在
		$open_uid = $userOpenidModel->getInfoMix(array(
			"Openid =?" => $user_info['openid'],
			"AppID =?" => $app_id),
			"UID");
		is_null($uid) && !is_null($open_uid) && $uid = $open_uid;

		//插入还是更新
		if (is_null($uid)) {
			$uid = $this->insert($data);
			if ($uid === false)
				throw new Exception("insert user error");
		} else {
			$data['UpdateTime'] = date("Y-m-d H:i:s", time());
			$res = $this->update($data, array("UID =?" => $uid));
			if ($res === false)
				throw new Exception("update user error");
		}

		//插入open_id
		if (is_null($open_uid)) {
			$res = $userOpenidModel->insert(array(
				"UID" => $uid,
				"Openid" => $user_info['openid'],
				"AppID" => $app_id
			));
			if ($res === false)
				throw new Exception("insert openid error");
		}

		$data['UID'] = $uid;
		$data['Openid'] = $user_info['openid'];
		$data['AppID'] = $app_id;

		return $data;
	}
}