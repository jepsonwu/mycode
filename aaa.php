<?php

$HEAD_LEN = 20;

/*$buffer = '000000c7010100c80000000200000000016400007b0a2020226170705f6261636b67726f756e6422203a2066616c73652c0a202022726f6c6522203a2066616c73652c0a2020226465766963655f696422203a20224232314245364634343630353445323638393544464337303034313535434233222c0a2020226d6f62696c6522203a20223133363636363636363636222c0a20202270617373776f726422203a20226531306164633339343962613539616262653536653035376632306638383365220a7d';
$buffer = hex2bin($buffer);
$data = unpack("Nlength/CserviceId/CcommandId/ncode/NserialId/Nsender/Cversion1/Cversion2/Cflag/Creserve", $buffer);
$data['body'] = substr($buffer, $HEAD_LEN, $data['length']-$HEAD_LEN);
//echo bin2hex($data['body']);exit;
$data['body'] = unpack("a*", $data['body']);
//var_dump($data['body'][1]);exit;
$data['body'] = trim($data['body'][1]);
$data['body'] = json_decode($data['body'],true);
print_r($data);exit;*/
//$a = 191.5;
//echo (int)$a;exit;
//$message['message'] = 'login failed';
//echo json_encode($message);exit;

//$data['body']['mobile'] = '18646343052';
//echo json_encode($data['body']);exit;
/*$buffer = '000000240a0a00c80000000300000000016400577b0a2020227479706522203a20310a7d';
$buffer = hex2bin($buffer);
$data = unpack("Nlength/CserviceId/CcommandId/ncode/NserialId/Nsender/Cversion1/Cversion2/Cflag/Creserve", $buffer);
$data['body'] = substr($buffer, $HEAD_LEN, $data['length']-$HEAD_LEN);
$data['body'] = unpack("a*", $data['body']);
$data['body'] = trim($data['body'][1]);
$data['body'] = json_decode($data['body'],true);
print_r($data);exit;*/
//echo phpinfo();exit;

//echo json_encode(array('login failed'));exit;
//echo md5(111111);exit;
//badmin.hapigou.com
/*
$uri = 'http://badmin.hapigou.com/ft/index/login';
$data = array (
	'mobile' => 18268830314,
	'password' => md5(111111)
);
$ch = curl_init ();
curl_setopt ( $ch, CURLOPT_URL, $uri );
curl_setopt ( $ch, CURLOPT_POST, 1 );
curl_setopt ( $ch, CURLOPT_HEADER, 0 );
curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
$file_contents = curl_exec ( $ch );
curl_close ( $ch );
print_r($file_contents);exit;*/



    // 建立连接，@see http://php.net/manual/zh/function.stream-socket-client.php
    $client = stream_socket_client('tcp://119.254.108.209:9292');
    if(!$client)exit("can not connect");


//echo strlen( pack("C",$data['length'] ));exit;
	$data['serviceId'] = 40;
	$data['commandId'] = 1;
	$data['code'] = 20;
	$data['serialId'] = 1;
	$data['sender'] = 1;
	$data['version1'] = 1;
	$data['version2'] = 1;
	$data['flag'] = 1;
	$data['reserve'] = 1;
	//$data['body']['mobile'] = '13754273830';
	//$data['body']['password'] = '96e79218965eb72c92a549dd5a330112';
$data['body'] = '{"mobile":"17098156705","password":"88888888","international_code":"86","uid":69,"role":"1","activated":"0","sid":"61","duration":"90","background":"1","order_id":"2015083157571001","device_id":"sasadsa","token":"f0tMZLKf5Dv6Q9zWe\/GO8b\/6PfobE3JyDaNAS8X+2g8*"}';
    //$data['body'] = '{"user_id":61,"device_id":"131B0C022E954E6A93445AE18985D4A5","role":"1","activated":"0","app_background":"0"}';
//$data['body'] = json_encode($data['body']);
//echo $data['body'];exit;
    $data['body'] = pack('a*',$data['body']);
//echo strlen($data['body']);
    $package_len = $HEAD_LEN  + strlen($data['body']);
	$a = pack("NCCnNNCCCC",  $HEAD_LEN  + strlen($data['body']), $data['serviceId'], $data['commandId'], $data['code'], $data['serialId'], $data['sender'] ,$data['version1'],$data['version2'],$data['flag'],$data['reserve']).$data['body'];
    //$a = pack("NCCnnJCC", $package_len, $data['serviceId'], $data['commandId'], $data['serialId'], $data['code'], $data['sender'] ,$data['version1'],$data['version2']).$data['body'];
//echo var_export($a);exit;
//echo strlen($a);EXIT;
//echo bin2hex($a);exit;*/
    // 模拟超级用户，以文本协议发送数据，注意Text文本协议末尾有换行符（发送的数据中最好有能识别超级用户的字段），这样在Event.php中的onMessage方法中便能收到这个数据，然后做相应的处理即可
    fwrite($client, $a);

	$buffer =  fread($client, 2000);
//echo bin2hex($buffer);
//echo strlen($buffer);exit;
    //var_dump($response);
//$data = unpack("Nlength/CserviceId/CcommandId/nserialId/ncode/Jsender/Cversion1/Cversion2", $buffer);
$data = unpack("Nlength/CserviceId/CcommandId/ncode/NserialId/Nsender/Cversion1/Cversion2/Cflag/Creserve", $buffer);
if($data['length'] > $HEAD_LEN)
{
	$data['body'] = substr($buffer, $HEAD_LEN, $data['length']-$HEAD_LEN);
	//echo bin2hex($data['body']);exit;
	$data['body'] = unpack("a*", $data['body']);
	//var_dump($data['body'][1]);exit;
	//$data['body'] = trim($data['body'][1]);
	//$data['body'] = json_decode($data['body'],true);
}
else
{
	$data['body'] = '';

}
print_r($data);
//echo strlen($response);exit;
    

