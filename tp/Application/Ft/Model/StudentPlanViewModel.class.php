<?php
namespace Students\Model;

use Think\Model\ViewModel;
//课程记录视图模型
class StudentPlanViewModel extends ViewModel {

	public $viewFields = array(
	
		'studentPlan' => array('id', 'sid', 'cid','mid', 'operator', 'type', 'jieduan', '_type'=>'LEFT'),
	
		'courses' => array('cname', '_on'=>'studentPlan.id=courses.id', '_type'=>'LEFT'),
	
		'materialsSmallType' => array('name'=>'mt_cname','ename','pic','parent','sort', '_on'=>'studentPlan.mid=materialsSmallType.id', '_type'=>'LEFT')
		
	);
}

?>
