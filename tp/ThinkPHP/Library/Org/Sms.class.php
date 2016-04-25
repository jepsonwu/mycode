<?PHP
namespace Org;
include_once ("SMS_SDK/CCPRestSDK.php");
class Sms{
		/*
		 * 发送模板短信
		 * @param to 手机号码集合,用英文逗号分开
		 * @param  datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
		 * @param $tempId 模板Id
		 */
		function sendSMS($to, $datas, $tempId) {
			//todo:后期修改	
			//
			// if ( !in_array(gethostname(), array('www.abc360.com','i-y7qmuy0p','i-vvm9fzcz','i-dootn9bd')) ) {
			// 	logtest ('发送云通信短信失败 mobile => ' . $to . '; data => ' . join('|',$datas) . '; tempId => ' . $tempId, date('Y-m-d') .'-sms.log');
			// 	return true;
			// }
			//主帐号
			$accountSid = 'aaf98f89488d0aad0148a025abd006f8';
			// 主帐号Token
			$accountToken = 'eb34f70be10c493d8b9b072291819575';
			// 应用Id
			$appId = '8a48b5514c9d9c05014cb0aa731e0c0a';
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

			// 发送模板短信
			//echo "Sending TemplateSMS to $to <br/>";
			$result = $rest->sendTemplateSMS ( $to, $datas, $tempId );
			$result_arr = array();
			if ($result->statusCode != 0) {
				$result_arr['flag'] = 0;
				$result = (array)$result;
				$result_arr['error_code'] = $result['statusCode'];
				$result_arr['error_msg'] = $result['statusMsg'];
			} else {
				$result_arr['flag'] = 1;
				$smsmessage = $result->TemplateSMS;
				$smsmessage = (array)$smsmessage;
				$result_arr['send_time'] = $smsmessage['dateCreated'];
				$result_arr['message_id'] = $smsmessage['smsMessageSid'];
			}
			
			//todo:后期修改	
			// logtest( 'mobile => ' . $to . '; data => ' . join('|',$datas) . '; result => ' . join('|',$result_arr) , date('Y-m-d') .'-sms.log' );

			return $result_arr;
		}
		//Demo调用
	    //sendTemplateSMS("13575464961",array("2778832","2"),"1");
}
?>
