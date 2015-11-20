<?php
/**
* 
*/
class socket
{
	
	public function __construct()
	{

	}

	public function createClient($conn){
		$client=stream_socket_client($conn);
	}

	public function createServer(){
	}

	public function send(){
		$HEAD_LEN = 20;
		$client = stream_socket_client('tcp://119.254.108.209:9292');

		$data['serviceId'] = 40;
		$data['commandId'] = 1;
		$data['code'] = 20;
		$data['serialId'] = 1;
		$data['sender'] = 1;
		$data['version1'] = 1;
		$data['version2'] = 1;
		$data['flag'] = 1;
		$data['reserve'] = 1;
		$data['body'] = '{"mobile":"17098156705","password":"88888888","international_code":"86","uid":69,"role":"1","activated":"0","sid":"61","duration":"90","background":"1","order_id":"2015083157571001","device_id":"sasadsa","token":"f0tMZLKf5Dv6Q9zWe\/GO8b\/6PfobE3JyDaNAS8X+2g8*"}';
		$data['body'] = pack('a*',$data['body']);

		$package_len = $HEAD_LEN  + strlen($data['body']);
		$a = pack("NCCnNNCCCC",  $HEAD_LEN  + strlen($data['body']), $data['serviceId'], $data['commandId'], $data['code'], $data['serialId'], $data['sender'] ,$data['version1'],$data['version2'],$data['flag'],$data['reserve']).$data['body'];
		
		fwrite($client, $a);
		
		$buffer =  fread($client, 2000);
		$data = unpack("Nlength/CserviceId/CcommandId/ncode/NserialId/Nsender/Cversion1/Cversion2/Cflag/Creserve", $buffer);
		
		if($data['length'] > $HEAD_LEN)
		{
			$data['body'] = substr($buffer, $HEAD_LEN, $data['length']-$HEAD_LEN);
			$data['body'] = unpack("a*", $data['body']);
		}
		else
		{
			$data['body'] = '';

		}
		print_r($data);
	}
}
