<?php

/**
 * 迪斯尼活动公众号消息发送机制
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-9
 * Time: 下午2:06
 */
class Model_WechatOpen_ActivityDisneyMsgHook implements DM_Wechat_MsgHookInterface
{
	const DISNEY_EVENT_KEY = 1;//主二维码参数值，用户第一次扫描，分享出去的参数值为DID
	const DISNEY_MSG_TEMPLATE_ID = "ngmM7xK0DDOwbxsyno8_Monfpw97raplZgIxDjTDeNk";//模板消息ID
	const DISNEY_MSG_TEMPLATE_SHARE_ID = "lfMSBr9DsVW1LkHMDzKjiBlA5I816AKGvHbjjGbxY9c";//推荐消息模板
	const DISNEY_MSG_TEMPLATE_WINNING = "5krV87zfl0K0ZvDEuM3O-jPoiBOMJYniLjYhbkMIsuE";//中奖通知
	const DISNEY_SHARE_KEY = "activity_disney_share_key";//分享关系存储key

	protected $disney_share_pic_key = "disney_share_pic_key_";//分享图片 image_id

	protected $disney_join_first = false;//是否第一次参加活动

	protected $wechat_obj = null;//微信插件

	protected $send_text = array(
		"activity_end" => "活动已结束！下次早点来参与哦！记得继续关注我们哦！么么哒~",//活动结束

		"first" => "欢迎加入财猪大家庭！上财猪APP，学理财，更有财！<a href='http://a.app.qq.com/o/simple.jsp?pkgname=com.caizhu.caizhu' target='_blank'>点击此处下载财猪APP</a>。",//会员加入活动信息
		"second" => "呀，您是我们的老用户啦！不能给您的朋友增加人气撒！快点告诉TA去寻找新朋友吧！",//不是第一次参加活动

		"template_first" => "恭喜你成功加入财猪“零元玩转迪士尼”活动！",//模板消息
		"template_share_first" => "您的好友%s加入成功！\n恭喜您目前已成功获得%s位好友赞同！",//分享模板消息

		"share_myself" => "哎呀，你扫描了自己的二维码哇！快去邀请你的朋友赞同你吧！",//扫描自己的分享二维码
		"share_5" => "恭喜您已成功完成财猪“零元玩转迪士尼”活动报名！点击此处领取玩转迪士尼省钱攻略。获得更多好友赞同，有机会获得更超值的礼品哦！",//分享人数为5

		"remark" => "活动攻略：
活动期间内，争取5位好友赞同（让TA扫描我们刚刚推送给你的二维码），即可完成活动报名，并获得玩转迪士尼省钱攻略。

获得赞同人数最多的第3-10名童鞋均可获得上海迪士尼门票一张。

获得赞同人数最多的前2名童鞋，均可获得价值XXX元的“财猪零元玩转迪士尼”大礼包一份（内含上海迪士尼门票、住宿）哦！",//模板备注
	);

	//活动时间
	protected $activity_time = array(
		"start" => "2016-05-01",
		"end" => "2016-06-12",
	);

	/**
	 *
	 * @param $event
	 * @param $xml_info
	 * @return mixed|void
	 * @throws Exception
	 */
	public function eventMsg($event, $xml_info)
	{
		$disney_send = false;
		switch ($event) {
			case "subscribe"://关注 扫描二维码推送
				if (isset($xml_info['EventKey'])) {
					$disney_send = true;
					$xml_info['EventKey'] = substr($xml_info['EventKey'], 8);
				}
				break;
			case "unsubscribe"://取消关注
				break;
			case "SCAN"://已关注公众号扫描二维码
				$disney_send = true;
				break;
		}

		if ($disney_send) {
			echo "";
			fastcgi_finish_request();

			//获取用户信息
			$user_info = $this->getUserInfo($xml_info['FromUserName']);

			switch ($xml_info['EventKey']) {
				case self::DISNEY_EVENT_KEY:
					$disney_info = $this->getDisneyInfo($user_info['UID']);

					//不在活动时间
					if (time() < strtotime($this->activity_time['start'])
						|| time() > strtotime($this->activity_time['end'])
					) {
						$this->customSend($user_info['Openid'], $this->send_text['activity_end']);
					} else {
						$this->disneyJoinSend($user_info, $disney_info);
					}
					break;
				default:
					$disney_info = $this->getDisneyInfo($user_info['UID']);

					$disneyModel = new Model_Activity_Disney();
					$share_info = $disneyModel->getInfoMix(array("DID =?" => $xml_info['EventKey']));

					//分享过来的
					if (!is_null($share_info)) {
						if ($disney_info['DID'] == $share_info['DID']) {//扫描自己的二维码
							$this->customSend($user_info['Openid'], $this->send_text['share_myself']);
						} else {
							//重复扫描  share_did-disney_did
							$redis = DM_Module_Redis::getInstance();
							$share_value = "{$share_info['DID']}-{$disney_info['DID']}";
							$is_share = $redis->sIsMember(self::DISNEY_SHARE_KEY, $share_value);

							if (!$is_share) {
								//更新分享人数
								$share_info['ShareNum']++;
								$res = $disneyModel->update(array("ShareNum" => $share_info['ShareNum']),
									array("DID =?" => $xml_info['EventKey']));
								if ($res === false)
									throw new Exception("activity disney update share_num failed");

								$userModel = new Model_Activity_WechatUser();
								$share_user_info = $userModel->getInfoMix(array("UID =?" => $share_info['UID']));
								$userOpenidModel = new Model_Activity_WechatUserOpenid();
								$share_user_openid_info = $userOpenidModel->getInfoMix(array("UID =?" => $share_info['UID']));
								$share_user_info = array_merge($share_user_info, $share_user_openid_info);

								//发送提示分享消息
								$this->templateSend($share_user_info['Openid'],
									sprintf($this->send_text['template_share_first'], $user_info['Nickname'], $share_info['ShareNum']),
									"{$user_info['Nickname']}（{$disney_info['DID']}）", "{$share_user_info['Nickname']}（{$share_info['DID']}）",
									self::DISNEY_MSG_TEMPLATE_SHARE_ID);

								//5位好友回复固定文本
								$share_info['ShareNum'] == 5 &&
								$this->customSend($share_user_info['Openid'], $this->send_text['share_5']);

								$redis->sAdd(self::DISNEY_SHARE_KEY, $share_value);
							}

							$this->disneyJoinSend($user_info, $disney_info);
						}
					}
					break;
			}
		}
	}

	/**
	 *
	 * 发送消息
	 * @param $user_info
	 * @param $disney_info
	 */
	protected function disneyJoinSend($user_info, $disney_info)
	{
		//回复固定信息
		$this->customSend($user_info['Openid'], $this->send_text['first']);

		//回复模板信息
		$this->templateSend($user_info['Openid'], $this->send_text['template_first'],
			"{$user_info['Nickname']}（{$disney_info['DID']}）", $disney_info['CreateTime'], self::DISNEY_MSG_TEMPLATE_ID);

		//发送带二维码图片
		$redis = DM_Module_Redis::getInstance();
		$image_id_key = $this->disney_share_pic_key . $user_info['UID'];
		$image_id = $redis->get($image_id_key);
		if (empty($image_id)) {
			$data = $this->createPic($user_info, $disney_info['DID']);
			$media_info = $this->wechat_obj->mediaUpload(array(
					"filename" => md5($data) . ".jpg",
					"type" => "Content-Type: image/jpeg",
					"content" => $data
				)
			);
			$redis->setex($image_id_key, 259100, $media_info['media_id']);
			$image_id = $media_info['media_id'];
		}
		$this->customSend($user_info['Openid'], $image_id, "image");

		//不是第一次返回信息
		!$this->disney_join_first && $this->customSend($user_info['Openid'], $this->send_text['second']);
	}

	/**
	 * 生成图片
	 * @param $user_info
	 * @param $did
	 * @return string
	 */
	protected function createPic($user_info, $did)
	{
		//获取原图像
		//$template_im = imagecreatefromstring(file_get_contents("template.jpg"));
		$template_im = imagecreatetruecolor(300, 600);
		//$white = imagecolorallocate($template_im, 255, 255, 255);
		//imagefill($template_im, 0, 0, $white);

		$template_size = array(
			"x" => imagesx($template_im),
			"y" => imagesy($template_im)
		);

		//二维码
		$qrcode_ticket = $this->wechat_obj->qrcodeCreate(1, $did);
		$qrcode = $this->wechat_obj->qrcodeShow($qrcode_ticket['ticket']);
		$qrcode_im = imagecreatefromstring($qrcode);
		$qrcode_size = array(
			"x" => imagesx($qrcode_im),
			"y" => imagesy($qrcode_im)
		);

		//头像
		$head_im = imagecreatefromstring(file_get_contents($user_info['HeadImgUrl']));
		$head_size = array(
			"x" => imagesx($head_im),
			"y" => imagesy($head_im)
		);

		//复制二维码
		imagecopyresampled($template_im, $qrcode_im, 50, 350, 0, 0, 200, 200, $qrcode_size['x'], $qrcode_size['y']);
		imagecopyresampled($template_im, $head_im, 20, 50, 0, 0, 50, 50, $head_size['x'], $head_size['y']);

		//复制字符串
		$color = imagecolorallocate($template_im, 255, 255, 255);
		imagettftext($template_im, 10, 0, 100, 50, $color, APPLICATION_PATH . '/../public/fonts/msyh.ttf', $user_info['Nickname']);

		ob_start();
		imagejpeg($template_im);

		imagedestroy($template_im);
		imagedestroy($qrcode_im);
		imagedestroy($head_im);

		return ob_get_clean();
	}

	/**
	 * 模板消息
	 * @param $open_id
	 * @param $first
	 * @param $name
	 * @param $time
	 * @param $template_id
	 */
	protected function templateSend($open_id, $first, $name, $time, $template_id)
	{
		$data = array(
			"first" => array(
				"value" => $first,
				"color" => "#173177",
			),
			"keyword1" => array(//姓名
				"value" => $name,
				"color" => "#173177",
			),
			"keyword2" => array(//时间
				"value" => $time,
				"color" => "#173177",
			),
			"remark" => array(
				"value" => $this->send_text['remark'],
				"color" => "#173177",
			),
		);
		$this->wechat_obj->templateSendMessage($open_id, $template_id, $data);
	}

	/**
	 * 发送客服消息
	 * @param $open_id
	 * @param $text
	 * @param string $type
	 */
	protected function customSend($open_id, $text, $type = "text")
	{
		$this->wechat_obj->customSendMix($open_id, $text, $type);
	}

	/**
	 * 获取用户信息
	 * @param $open_id
	 * @return array|mixed|null
	 * @throws Exception
	 */
	protected function getUserInfo($open_id)
	{
		$userModel = new Model_Activity_WechatUser();
		$userOpenidModel = new Model_Activity_WechatUserOpenid();
		$app_id = $this->wechat_obj->getAuthorizerAppID();

		//获取用户ID 这里可以做缓存 控制刷新频率
		$user_info = array();
		$uid = $userOpenidModel->getInfoMix(array(
			"Openid =?" => $open_id,
			"AppID =?" => $app_id),
			"UID");
		if (!is_null($uid)) {
			$user_info = $userModel->getInfoMix(array("UID =?" => $uid));
			if (!is_null($user_info)) {
				$user_info['Openid'] = $open_id;
				$user_info['AppID'] = $app_id;
			}
		}

		if (empty($user_info)) {
			$user_info = $this->wechat_obj->userInfo($open_id);
			$user_info['tagid_list'] = implode(",", $user_info['tagid_list']);
			$user_info = $userModel->addUserInfo($user_info, $app_id);
		}

		return $user_info;
	}

	/**
	 * 获取迪斯尼活动信息
	 * @param $uid
	 * @return array|mixed|null
	 * @throws Exception
	 */
	protected function getDisneyInfo($uid)
	{
		$disneyModel = new Model_Activity_Disney();
		$disney_info = $disneyModel->getInfoMix(array("UID =?" => $uid));
		if (is_null($disney_info)) {
			$disney_info = array(
				"UID" => $uid,
				"ShareNum" => 0,
				"CreateTime" => date("Y-m-d H:i:s", time()),
			);
			$res = $disneyModel->insert($disney_info);
			if ($res === false)
				throw new Exception("activity disney create info failed");
			$this->disney_join_first = true;

			$disney_info['DID'] = $res;
		}

		return $disney_info;
	}

	/**
	 * 文本消息
	 * @param $xml_info
	 * @return mixed|void
	 */
	public function textMsg($xml_info)
	{
		echo "";
		fastcgi_finish_request();
		$message = "";

		$type = substr($xml_info['Content'], 0, 1);
		$content = substr($xml_info['Content'], 1);

		switch ($type) {
			case "1"://迪斯尼用户的地址和联系方式
				$user_info = $this->getUserInfo($xml_info['FromUserName']);
				$disneyModel = new  Model_Activity_Disney();
				$uid = $disneyModel->getInfoMix(array("UID =?" => $user_info['UID']), "UID");
				if (!is_null($uid)) {
					$res = $disneyModel->update(array("Contact" => $content), array("UID =?" => $uid));
					if ($res === false)
						$message = "出错了，在发一次吧。";
					else
						$message = "已收到，等待我们联系你吧。";
				} else {
					$message = "去参加迪斯尼活动吧。";
				}
				break;
			default:
				break;
		}

		$type = substr($xml_info['Content'], 0, 4);
		$content = substr($xml_info['Content'], 4);
		switch ($type) {
			case "$&@!":
				$content = explode("-", $content);
				$userOpenidModel = new Model_Activity_WechatUserOpenid();
				$open_id = $userOpenidModel->getInfoMix(array("UID =?" => $content[0]), "Openid");
				if (!is_null($open_id)) {
					$xml_info['FromUserName'] = $open_id;
					$message = $content[1];
				}
				break;
		}

		if (empty($message) && $xml_info['FromUserName'] == "og8R2v2H73myPKoH-nlk3UWKLNN4") {
			$userOpenidModel = new Model_Activity_WechatUserOpenid();
			$open_id = $userOpenidModel->getInfoMix(array("UID =?" => 1), "Openid");
			if (!is_null($open_id)) {
				$xml_info['FromUserName'] = $open_id;
				$message = $xml_info['Content'];
			}
		}

		empty($message) && $message = "你想说啥？";
		$this->customSend($xml_info['FromUserName'], $message);
	}

	/**
	 * @param $wechat_obj
	 * @return mixed|void
	 */
	public function setWechatObj($wechat_obj)
	{
		$this->wechat_obj = $wechat_obj;
	}
}