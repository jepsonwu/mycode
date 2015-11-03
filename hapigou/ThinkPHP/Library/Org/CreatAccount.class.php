<?php
/**
 * @Author: Darkerlu
 * @Date:   2015-04-28 13:23:28
 * @Last Modified by:   Darkerlu
 * @Last Modified time: 2015-04-28 16:19:49
 */
namespace Org;
include_once ("SMS_SDK/CCPRestSDK.php");
class CreatAccount{
		/*
		 * 创建云通讯子账号
		 * @param friendlyName 子账户名称
		 */
		function creat_account($friendlyName) {
			//主帐号
			$accountSid = 'aaf98f89488d0aad0148a025abd006f8';
			// 主帐号Token
			$accountToken = 'eb34f70be10c493d8b9b072291819575';
			// 应用Id
			$appId = 'aaf98f89488d0aad0148a0555e290716';
			// 请求地址，格式如下，不需要写https://
			$serverIP = 'app.cloopen.com';
			// 请求端口
			$serverPort = '8883';
			// REST版本号
			$softVersion = '2013-12-26';
			
			// 初始化REST SDK
			//global $accountSid, $accountToken, $appId, $serverIP, $serverPort, $softVersion;
			$rest = new REST ( $serverIP, $serverPort, $softVersion );
			$rest->setAccount ( $accountSid, $accountToken );
			$rest->setAppId ( $appId );
			//创建子账号
			return $rest->createSubAccount($friendlyName);
		}
}
?>
