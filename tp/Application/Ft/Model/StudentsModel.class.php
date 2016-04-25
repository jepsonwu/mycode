<?php
namespace Ft\Model;
use Think\Model;
// 学生表
class StudentsModel extends Model {
	//注册
	public function reg($mobile){
		if($this->where("mobile=$mobile")->find()){
			json_echo(1,'该手机号码已经注册！');
			exit;
		}
		json_echo(0,'success');
	}
	

}
?>
