<?php

/**
 * 公众号消息处理接口
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-9
 * Time: 下午1:48
 */
interface DM_Wechat_MsgHookInterface
{
	/**
	 * 事件消息
	 * @param $event
	 * @param $xml_info
	 * @return mixed
	 */
	public function eventMsg($event, $xml_info);

	/**
	 * 文本消息
	 * @param $xml_info
	 * @return mixed
	 */
	public function textMsg($xml_info);

	/**
	 * 设置插件对象
	 * todo 用抽象类继承来写
	 * @param $wechat_obj
	 * @return mixed
	 */
	public function setWechatObj($wechat_obj);
}