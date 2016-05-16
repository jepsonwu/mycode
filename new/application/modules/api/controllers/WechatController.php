<?php

/**
 * 微信公众平台
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-18
 * Time: 上午11:00
 */
class Api_WechatController extends Action_Api
{
	public function init()
	{
		parent::init();
		//$this->isLoginOutput();
	}

	protected $getSignatureConf = array(
		array("url", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 获取微信签名 post
	 */
	public function getSignatureAction()
	{
		//if ($this->isPostOutput()) {
			try {
				//待签名参数
				$param = array(
					"noncestr" => DM_Helper_String::randString(18),
					"jsapi_ticket" => DM_Wechat_Wechat::getInstance()->getTicket(),
					"timestamp" => time(),
					"url" => $this->_param['url'] //不包含#及其后面部分
				);

				//sha1
				$signature = sha1($this->createParam($this->sortParam($param)));

				parent::succReturn(array(
					"appId" => DM_Wechat_Wechat::getInstance()->getConfig("AppID"),
					"timestamp" => $param['timestamp'],
					"nonceStr" => $param['noncestr'],
					"signature" => $signature
				));
			} catch (Exception $e) {
				parent::failReturn($e->getMessage());
			}

// 		}
	}

	/**
	 * 生成签名字符串 todo @jepson 封装DM 调方法
	 * @param $data
	 * @return string
	 */
	private function createParam($data)
	{
		$param = "";

		foreach ($data as $key => $value) {
			$param .= $key . "=" . $value . "&";
		}
		$param = trim($param, "&");

		//转义字符

		return $param;
	}

	/**
	 * [sort_param 按字典排序参数]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function sortParam($data)
	{
		ksort($data);

		return $data;
	}
}