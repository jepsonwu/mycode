<?php
namespace Home\Controller;
use Think\Controller;
class DemoController extends Controller{
	public function Index(){
		$url="http://joytalk.com/Admin/Public/verify/".time();
        $fp=curl_init();

		curl_setopt($fp,CURLOPT_URL,$url);
		curl_setopt($fp,CURLOPT_RETURNTRANSFER,true);
		$result=curl_exec($fp);

		pre($result);
		if($result){

		}


		curl_close($fp);
	}
}
