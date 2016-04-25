<?php
namespace V2\Controller;

use V2\Controller\CommonController;

class ExerciseDetailController extends CommonController {

    //获取单个练习题
	Public function read_get_html(){

	}

	//获取练习题列表
	Public function list_get_html(){
		$cond['series_id'] = $_GET['series_id'];
		$page = $_GET['page']?$_GET['page']:1;
		$listRows = $_GET['listrows']?$_GET['listrows']:6;
		$list = M('ExerciseDetail')->where($cond)->page($page, $listRows)->field('id,demo_id,type')->select();
		if (empty($list)) {
			$this->successReturn();
		}
		foreach($list as $key => $vo){
			$result['list'][$key]['exercise_id'] = $vo['id'];
			$result['list'][$key]['demo_id'] = $vo['demo_id'];
			$result['list'][$key]['type'] = C('EXERCISE_TYPES')[$vo['type']];
		}
		$result['total'] = count($list);
		$this->successReturn($result);
	}



	public function dobind(){
		$series = M('Series')->select();
		foreach($series as $serie){
			$data['series_id'] = $serie['id'];
			$Demos = M('Demos')->where('series_id = '.$serie['id'])->getField('id',true);
			$cond['demo_id'] = array('in',$Demos);
			M('ExerciseDetail')->where($cond)->save($data);
		}
	}
}
