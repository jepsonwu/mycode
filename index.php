<?php
function curl($url,$type="GET",$data=null,$header=null,$option=null){
	$ch=curl_init();

	$options=array(
		"CURLOPT_URL"=>$url,
		"CURLOPT_TIMEOUT"=>10,
		"CURLOPT_RETURNTRANSFER"=>true,
	);

	!is_null($option)&&is_array($option)&&$options=array_merge($options,$option);

	curl_setopt_array($ch,$options);

	switch (strtoupper($type)){
		case 'GET':
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			break;
		case 'POST':
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
		case 'HEAD':
			
			break;
		case 'PUT':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
		case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			break;
	}

	if(is_null($header))
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:text/json"));
	else
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$return=curl_exec($ch);
	if($return!==false){

	}else{
		$return="ERR:(".curl_errno($ch).")".curl_error($ch);
	}

	curl_close($ch);

	return $return;
}

function curl_multi(){

}

function curl_ftp(){

}

function curl_ssl(){

}

function curl_file(){

}

function curl_proxy(){

}



