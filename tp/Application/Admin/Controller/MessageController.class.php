<?php
namespace Admin\Controller;
use \Admin\Controller\CommonController;

/**
* 通知管理
*/
class MessageController extends CommonController
{
	public function _filter(&$map,&$param){
		$info=I("request.");
		foreach (array("type") as $key) {
			if(isset($info[$key])&&$info[$key]){
				$map[$key]=$param[$key]=$info[$key];
				$this->assign($key,$info[$key]);
			}
		}
	}

	public function index(){
		$this->assign("types",C("MESSAGE_TYPE"));

		$map=$param=array();
		$this->_filter($map,$param);

		$this->_list(M("Message"),$map,$param);

		$this->display();
	}

	public function _processer(&$volist){
		foreach ($volist as &$value) {
			$value['type']=C("MESSAGE_TYPE.".$value['type']);
			$value['create_time']=date("Y-m-d H:i:s",$value['create_time']);
			$value['user_name']=M("Users")->where("id='{$value['user_id']}'")->getField("name");
			$value['create_user_name']=M("RbacUser")->where("id='{$value['create_user']}'")->getField("nickname");
		}
	}

	public function add(){
		$this->assign("types",C("MESSAGE_TYPE"));

		$id=I("get.id");
		if($id){
			$info=M("Message")->where("id='{$id}'")->field("id,message,type,url")->find();
			$this->assign("info",$info);
		}

		$this->display();
	}

	public function insert(){
		$info=I("post.");

		$post=array(
			"message"=>$info['message'],
			"url"=>isset($info['url'])?$info['url']:"",
			"create_time"=>time(),
			"type"=>$info['type'],
			"user_id"=>"",
			"create_user"=>session("user_id")
		);

		if (false !== $model->add ($post)) {
			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}

	public function edit(){

	}

	public function save(){

	}
}