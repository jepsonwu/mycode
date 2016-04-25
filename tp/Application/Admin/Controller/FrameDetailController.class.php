<?php

namespace Admin\Controller;

class FrameDetailController extends CommonController {

	public function _filter(&$map, &$param) {
		//
		$rqst = I ('request.');
		//
		$this->_com_filter('id', $rqst['id'], 2, $map, $param);
		$this->_com_filter('type', $rqst['type'], 2, $map, $param);
		$this->_com_filter('demo_id', $rqst['demo_id'], 2, $map, $param);
	}

	public function index() {
		//
		$map = array();
		$param = array();
		$param['series_id']=I('series_id');
		// 列表过滤器，生成查询Map对象
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ($map, $param);
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D(CONTROLLER_NAME);
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', "demo_id` asc,`time`+0 asc,`id", true );
		}
		//
		$exercises = M("exercise_detail")->order('id')->getField('sort,label');
		$this->assign('exercises', $exercises);
		$demos = M("Demos")->order('sort desc')->getField('id,chName');

		$this->assign('demos', $demos);
		//练习题分类
		$this->assign('exercise_type',C('EXERCISE_TYPES'));
		$this->display();
	}

	//
	public function add() {
		//
		$demos = M("Demos")->order('sort desc')->getField('id,chName');
		$this->assign('demos', $demos);
		//
		$this->assign('did',I('get.demo_id'));
		$this->display ();
	}

	// 保存新增
	public function insert() {
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		$demoInfo = M('Demos')->alias('a')->join('ft_series b ON b.id= a.series_id','left')->where('a.id = '.$post['demo_id'])->field('a.id,a.dirname as ddirname,b.dirname as sdirname')->find();

		$sort = $model->where('demo_id = %d', $post['demo_id'])->max('sort');
		$data['sort'] = is_null($sort) ? 1 : ($sort+1);
		$edata['create_id'] = $data['create_id'] = get_user_id();
		$edata['create_time'] = $data['create_time'] = time();
		$edata['demo_id'] = $data['demo_id'] = $post['demo_id'];
		$data['type'] = $post['type'];
		$data['time'] = !empty($post['time'])?$post['time']:0;
		$model->startTrans();
		$result = true;
		//根据type处理相关逻辑
		if($post['type']==1||$post['type']==3){
			$uploadInfo = $this->uploadFile("/".$demoInfo['sdirname']."/".$demoInfo['ddirname']."/tips/");
			$data['title'] = $post['title'];
			$data['intro'] = $post['intro'];
			$data['picture'] = 'tips/'.$uploadInfo['picture']['savename'];
			$fid = $model->add ($data);
			$result = ($fid === false)?$fid:$result;
		}elseif($post['type']==2){
			$fid = $model->add ($data);
			//处理上传文件
			$edata['sort'] = $fid;

			$edata['folder_name'] = "e".$fid;

			$uploadInfo = $this->uploadFile("/".$demoInfo['sdirname']."/".$demoInfo['ddirname']."/exercises/".$edata['folder_name']."/");
			$edata['type'] = $post['etype'];
			$edata['label'] = $post['label'];
			if($post['is_need_guide'] ==1) {
				$edata['guide'] = "exercises/" . $edata['folder_name'] . "/" . $uploadInfo['guide']['savename'];
			}else{
				$edata['guide'] = "";
			}
			//根据题型组合上传文件及特殊内容
			switch($post['etype']){
				//选图
				case 1:
					$edata['audio'] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio']['savename'];
					$contents[] = "exercises/".$edata['folder_name']."/".$uploadInfo['content_a']['savename'];
					$contents[] = "exercises/".$edata['folder_name']."/".$uploadInfo['content_b']['savename'];
					$contents[] = "exercises/".$edata['folder_name']."/".$uploadInfo['content_c']['savename'];
					$contents[] = "exercises/".$edata['folder_name']."/".$uploadInfo['content_d']['savename'];
					$edata['contents'] = $this->turnToStorage($contents);
					$feedbacks[] = "exercises/".$edata['folder_name']."/".$uploadInfo['feedback_a']['savename'];
					$feedbacks[] = "exercises/".$edata['folder_name']."/".$uploadInfo['feedback_b']['savename'];
					$feedbacks[] = "exercises/".$edata['folder_name']."/".$uploadInfo['feedback_c']['savename'];
					$feedbacks[] = "exercises/".$edata['folder_name']."/".$uploadInfo['feedback_d']['savename'];
					$edata['feedbacks'] = $this->turnToStorage($feedbacks);
					$edata['answer'] = $post['answer'];
					break;
				//连线
				case 2:
					$edata['classifications'] = $post['classifications'];
					$edata['contents'] = $post['contents'];
					$classifications= explode("\r\n", $edata['classifications']);
					if(count($classifications)!= count(array_unique($classifications))){
						$model->rollback();
						$this->ajaxReturn ( make_rtn ( '内容1有重复内容!' ) );
					}
					$contents = explode("\r\n", $edata['contents']);
					if(count($contents)!= count(array_unique($contents))){
						$model->rollback();
						$this->ajaxReturn ( make_rtn ( '内容2有重复内容!' ) );
					}
					break;
				//分类
				case 3:
					$contents = '';
					$part = 1;
					$contents_array = array();
					FOR ($i = 1; $i <= 16; $i++)
					{
						$contents_array[$part][] = $post['content_'.($i-1)];
						if($i%4 == 0){
							if(count($contents_array[$part])!= count(array_unique($contents_array[$part]))){
								$this->ajaxReturn ( make_rtn ( '组'.$part.'有重复内容!' ) );
							}
							if(!in_array($post['answer_'.($part-1)],$contents_array[$part])){
								$this->ajaxReturn ( make_rtn ( '组'.$part.'答案与内容不匹配，请确认是否多输了不必要的空格!' ) );
							}
							$part++;
							$contents .= $post['content_'.($i-1)].PHP_EOL;
						}else{
							$contents .= $post['content_'.($i-1)].'|';
						}

					}
					$edata['contents'] = rtrim($contents);
					FOR ($i = 0; $i <= 3; $i++)
					{
						$answer[] = $post['answer_'.$i];
					}
					$edata['answer'] = $this->turnToStorage($answer,PHP_EOL);
					$edata['pic'] = "exercises/".$edata['folder_name']."/".$uploadInfo['picture']['savename'];
					break;
				//短对话
				case 4:
					$audio[] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio1']['savename'];
					$audio[] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio2']['savename'];
					$edata['audios'] = $this->turnToStorage($audio);
					$edata['contents'] = $post['content_1'].PHP_EOL.$post['content_2'];
					$pic[] = "exercises/".$edata['folder_name']."/".$uploadInfo['pic1']['savename'];
					$pic[] = "exercises/".$edata['folder_name']."/".$uploadInfo['pic2']['savename'];
					$edata['pic'] = $this->turnToStorage($pic);
					break;
				//造句
				case 5:
					$edata['audio'] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio']['savename'];
					$edata['contents'] = $post['contents'];
					$edata['enContent'] = $post['enContent'];
					$edata['chContent'] = $post['chContent'];
					break;
				//填空
				case 6:
					$edata['audio'] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio']['savename'];
					$edata['explanation'] = $post['explanation'];
					FOR ($i = 0; $i <= 3; $i++)
					{
						if($post['explanation_'.$i]){
							$explanation[] = $post['explanation_'.$i];
							$answer[] = $post['answer_'.$i];
						}
					}
					$edata['explanation'] = $this->turnToStorage($explanation,PHP_EOL);
					$edata['answer'] = $this->turnToStorage($answer,PHP_EOL);
					$edata['enContent'] = $post['enContent'];
					$edata['chContent'] = $post['chContent'];
					break;
				//长对话
				case 7:
					$audios = array();
					$edata['contents']='';
					FOR ($i = 0; $i < 20; $i++)
					{
						if($uploadInfo['audios'.$i]['savename'])
							$audios[] = "exercises/".$edata['folder_name']."/".$uploadInfo['audios'.$i]['savename'];
						else
							break;
						if(empty($post['contents'.$i]))
							$this->ajaxReturn ( make_rtn ( '组'.($i+1).'内容不能为空!' ) );
						$edata['contents'] .= $post['contents'.$i].PHP_EOL;
					}
					$edata['contents'] = rtrim($edata['contents']);
					$edata['audios'] = $this->turnToStorage($audios);
					$pic[] = "exercises/".$edata['folder_name']."/".$uploadInfo['pic1']['savename'];
					$pic[] = "exercises/".$edata['folder_name']."/".$uploadInfo['pic2']['savename'];
					$edata['pic'] = $this->turnToStorage($pic);
					break;
				//跟读
				case 8:
					$edata['audio'] = "exercises/".$edata['folder_name']."/".$uploadInfo['audio']['savename'];
					$edata['content'] = $post['content'];
					$edata['explanation'] = $post['explanation'];
					break;
				default:
					break;
			}

			$eid = M('exercise_detail')->add($edata);
			$result = ($eid === false)?$eid:$result;
		}


		if (false !== $result) {
			$model->commit();
			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$model->rollback();
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}

	// 保存新增
	public function edit() {
		$model = D ( CONTROLLER_NAME );
		$id = I ( 'request.id' );

		$vo = $model->getById ( $id );
		// $data = $model->getLastSql();
		$demoInfo = M('Demos')->alias('a')->join('ft_series b ON b.id= a.series_id','left')->where('a.id = '.$vo['demo_id'])->field('a.id,a.dirname as ddirname,b.dirname as sdirname')->find();
		$exercise = M('exercise_detail')->where('sort = '.$id)->find();
		switch($exercise['type']) {
			//选图
			case 1:
				$exercise['contents'] = explode("<br />", $exercise['contents']);
				$exercise['feedbacks'] = explode("<br />", $exercise['feedbacks']);
				break;
			case 3:

				$exercise['contents'] = explode(PHP_EOL, $exercise['contents']);
				foreach($exercise['contents'] as $key=>$val){
					$exercise['contents'][$key] = explode("|", $val);
				}
				$exercise['answer'] = explode(PHP_EOL, $exercise['answer']);
				break;
			case 4:
				$exercise['audios'] = explode("<br />", $exercise['audios']);
				$exercise['contents'] = explode(PHP_EOL, $exercise['contents']);
				$exercise['pic'] = explode("<br />", $exercise['pic']);
				break;
			case 6:
				$exercise['explanation'] = explode(PHP_EOL, $exercise['explanation']);
				$exercise['answer'] = explode(PHP_EOL, $exercise['answer']);
				break;
			case 7:
				$exercise['audios'] = explode("<br />", $exercise['audios']);
				$exercise['pic'] = explode("<br />", $exercise['pic']);
				$exercise['contents'] = explode(PHP_EOL, $exercise['contents']);
				break;

		}
		//
		$demos = M("Demos")->order('sort desc')->getField('id,chName');
		$this->assign('demos', $demos);
		$this->assign('demoInfo', $demoInfo);

		$this->assign ( 'vo', $vo );
		$this->assign ( 'exercise', $exercise );
		//
		$this->display ();
	}

	// 保存编辑
	public function update() {

		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		$vo = $model->getById ( $post['id'] );
		if(empty($vo)){
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
			exit;
		}
		$demoInfo = M('Demos')->alias('a')->join('ft_series b ON b.id= a.series_id','left')->where('a.id = '.$vo['demo_id'])->field('a.id,a.dirname as ddirname,b.dirname as sdirname')->find();
		$edata['demo_id'] = $data['demo_id'] = $post['demo_id'];
		$data['time'] = !empty($post['time'])?$post['time']:0;
		$model->startTrans();
		$result = true;


		//根据type处理相关逻辑
		if($post['type']==1||$post['type']==3){
			$uploadInfo = $this->uploadFile("/".$demoInfo['sdirname']."/".$demoInfo['ddirname']."/tips/");
			$data['title'] = $post['title'];
			$data['intro'] = $post['intro'];
			if($uploadInfo['picture']['savename']){
				$data['picture'] = 'tips/'.$uploadInfo['picture']['savename'];
				$rcds[] = $demoInfo['sdirname']."/".$demoInfo['ddirname']."/".$post['picture_default'];
			}else{
				$data['picture'] = $post['picture_default'];
			}

			$fid = $model->where('id = '.$post['id'])->save ($data);
			$result = ($fid === false)?$fid:$result;
		}elseif($post['type']==2){
			$fid = $model->where('id = '.$post['id'])->save ($data);

			//处理上传文件
			$edata['folder_name'] = "e".$post['id'];
			$uploadInfo = $this->uploadFile("/".$demoInfo['sdirname']."/".$demoInfo['ddirname']."/exercises/".$edata['folder_name']."/");
			foreach($uploadInfo as $key=>$val){
				$rcds[] = $demoInfo['sdirname']."/".$demoInfo['ddirname']."/".$post[$key.'_default'];
			}

			if($post['is_need_guide'] ==1 && !empty($uploadInfo['guide']['savename'])) {
				$edata['guide'] ="exercises/".$edata['folder_name']."/".$uploadInfo['guide']['savename'];
			}elseif($post['is_need_guide'] !=1){
				$edata['guide'] = "";
				if(!empty($post['guide_default']))
					$rcds[] = $demoInfo['sdirname']."/".$demoInfo['ddirname']."/".$post['guide_default'];
			}
			$exvo = M('exercise_detail')->where('sort = '.$post['id'])->find();
			if(!empty($exvo)) {
				//根据题型组合上传文件及特殊内容
				switch ($exvo['type']) {
					//选图
					case 1:
						$edata['audio'] = $uploadInfo['audio']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio']['savename']:$post['audio_default'];
						$contents[] = $uploadInfo['content_a']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['content_a']['savename']:$post['content_a_default'];
						$contents[] = $uploadInfo['content_b']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['content_b']['savename']:$post['content_b_default'];
						$contents[] = $uploadInfo['content_c']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['content_c']['savename']:$post['content_c_default'];
						$contents[] = $uploadInfo['content_d']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['content_d']['savename']:$post['content_d_default'];
						$edata['contents'] = $this->turnToStorage($contents);
						$feedbacks[] = $uploadInfo['feedback_a']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['feedback_a']['savename']:$post['feedback_a_default'];
						$feedbacks[] = $uploadInfo['feedback_b']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['feedback_b']['savename']:$post['feedback_b_default'];
						$feedbacks[] = $uploadInfo['feedback_c']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['feedback_c']['savename']:$post['feedback_c_default'];
						$feedbacks[] = $uploadInfo['feedback_d']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['feedback_d']['savename']:$post['feedback_d_default'];
						$edata['feedbacks'] = $this->turnToStorage($feedbacks);
						$edata['answer'] = $post['answer'];
						break;
					//连线
					case 2:
						$edata['classifications'] = $post['classifications'];
						$edata['contents'] = $post['contents'];
						$classifications= explode("\r\n", $edata['classifications']);
						if(count($classifications)!= count(array_unique($classifications))){
							$model->rollback();
							$this->ajaxReturn ( make_rtn ( '内容1有重复内容!' ) );
						}
						$contents = explode("\r\n", $edata['contents']);
						if(count($contents)!= count(array_unique($contents))){
							$model->rollback();
							$this->ajaxReturn ( make_rtn ( '内容2有重复内容!' ) );
						}

						break;
					//分类
					case 3:
						$contents = '';
						$part = 1;
						$contents_array = array();
						FOR ($i = 1; $i <= 16; $i++)
						{
							$contents_array[$part][] = $post['content_'.($i-1)];
							if($i%4 == 0){
								if(count($contents_array[$part])!= count(array_unique($contents_array[$part]))){
									$this->ajaxReturn ( make_rtn ( '组'.$part.'有重复内容!' ) );
								}
								if(!in_array($post['answer_'.($part-1)],$contents_array[$part])){
									$this->ajaxReturn ( make_rtn ( '组'.$part.'答案与内容不匹配，请确认是否多输了不必要的空格!' ) );
								}
								$part++;
								$contents .= $post['content_'.($i-1)].PHP_EOL;
							}else{
								$contents .= $post['content_'.($i-1)].'|';
							}
						}
						$edata['contents'] = rtrim($contents);
						FOR ($i = 0; $i <= 3; $i++)
						{
							$answer[] = $post['answer_'.$i];
						}
						$edata['answer'] = $this->turnToStorage($answer,PHP_EOL);
						$edata['pic'] = $uploadInfo['picture']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['picture']['savename']:$post['picture_default'];
						break;
					//短对话
					case 4:
						$audio[] = $uploadInfo['audio1']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio1']['savename']:$post['audio1_default'];
						$audio[] = $uploadInfo['audio2']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio2']['savename']:$post['audio2_default'];
						$edata['audios'] = $this->turnToStorage($audio);
						$edata['contents'] = $post['content_1'].PHP_EOL.$post['content_2'];
						$pic[] = $uploadInfo['pic1']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['pic1']['savename']:$post['pic1_default'];
						$pic[] = $uploadInfo['pic2']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['pic2']['savename']:$post['pic2_default'];
						$edata['pic'] = $this->turnToStorage($pic);
						break;
					//造句
					case 5:
						$edata['audio'] = $uploadInfo['audio']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio']['savename']:$post['audio_default'];
						$edata['contents'] = $post['contents'];
						$edata['enContent'] = $post['enContent'];
						$edata['chContent'] = $post['chContent'];
						break;
					//填空
					case 6:
						$edata['audio'] = $uploadInfo['audio']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio']['savename']:$post['audio_default'];
						FOR ($i = 0; $i <= 3; $i++)
						{
							if($post['explanation_'.$i]){
								$explanation[] = $post['explanation_'.$i];
								$answer[] = $post['answer_'.$i];
							}
						}
						$edata['explanation'] = $this->turnToStorage($explanation,PHP_EOL);
						$edata['answer'] = $this->turnToStorage($answer,PHP_EOL);
						$edata['enContent'] = $post['enContent'];
						$edata['chContent'] = $post['chContent'];
						break;
					//长对话
					case 7:
						$audios = array();
						$edata['contents'] = '';
						FOR ($i = 0; $i < 20; $i++) {
							if ($uploadInfo['audios' . $i]['savename']||$post['audios' . $i .'_default'])
								$audios[] = $uploadInfo['audios' . $i]['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audios' . $i]['savename']:$post['audios' . $i .'_default'];
							else
								break;
							if(empty($post['contents'.$i]))
								$this->ajaxReturn ( make_rtn ( '组'.($i+1).'内容不能为空!' ) );
							$edata['contents'] .= $post['contents'.$i].PHP_EOL;

						}
						$edata['audios'] = $this->turnToStorage($audios);
						$edata['contents'] = rtrim($edata['contents']);
						$pic[] = $uploadInfo['pic1']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['pic1']['savename']:$post['pic1_default'];
						$pic[] = $uploadInfo['pic2']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['pic2']['savename']:$post['pic2_default'];
						$edata['pic'] = $this->turnToStorage($pic);
						break;
					//跟读
					case 8:
						$edata['audio'] = $uploadInfo['audio']['savename']?"exercises/" . $edata['folder_name'] . "/" . $uploadInfo['audio']['savename']:$post['audio_default'];
						$edata['content'] = $post['content'];
						$edata['explanation'] = $post['explanation'];
						break;
					default:
						break;
				}
				$eid = M('exercise_detail')->where('sort = ' . $post['id'])->save($edata);
				$result = ($eid === false) ? $eid : $result;
			}
		}


		if (false !== $result) {
			if (method_exists ( $this, 'my_after_update' )&&!empty($rcds)) {
				$this->my_after_update ( $rcds );
			}
			$model->commit();
			$this->ajaxReturn ( make_url_rtn ( '编辑成功!' ) );
		} else {
			$model->rollback();
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
		}

	}

	/**
	 * 永久删除
	 */
	public function foreverDelete($id = null){

		$model = D ( CONTROLLER_NAME );
		$model->startTrans();
		//
		if (! empty ( $model )) {
			$pk = $model->getPk ();
			if (empty ( $id )) {
				$id = $_REQUEST [$pk];
			}
			if (isset ( $id )) {
				$ids = explode ( '|', $id );
				$defult = true;
				$condition2 = $condition = array ();
				$condition2['sort'] = $condition [$pk] = array ( 'in', explode ( '|', $id ) );
				// 获取frames集合
				$rcds = $model->where ( $condition )->select ();

				$defult1 = $model->where ( $condition )->delete ();
				$defult = $defult1 !== false ? $defult : $defult1;
				$defult2 = M('exercise_detail')->where($condition2)->delete();
				$defult = $defult2 !== false ? $defult : $defult2;
				if ($defult !== false) {
					if (method_exists ( $this, 'my_after_delete' )) {
						$this->my_after_delete ( $rcds );
					}
					$model->commit();

					$this->ajaxReturn(make_url_rtn('永久删除成功!'));
				}else{
					// 失败返回
					$model->rollback();
					$this->ajaxReturn(make_rtn('永久删除失败！'));
				}
			} else {
				$this->ajaxReturn ( make_rtn ( '非法操作！未选中需永久删除的对象！' ) );
			}
		}
	}

	private function uploadFile($savePath){

		$upload = new \Think\Upload();// 实例化上传类
		$upload->autoSub = false;
		$upload->maxSize   =     3145728 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','mp3');// 设置附件上传类型
		$upload->savePath  =     $savePath; // 设置附件上传目录
		$info   =   $upload->upload();
//		if(!$info) {// 上传错误提示错误信息
//			$this->ajaxReturn ( make_rtn($upload->getError()) );
//		}
		return $info;
	}

	private function turnToStorage($arr,$break='<br />'){
		$val = implode($break,$arr);
		return $val;
	}

	// 删除后删除上传文件
	private function my_after_delete($rcds) {
		foreach($rcds as $val){
			$demo_ids[] = $val['demo_id'];
			$frames[$val['id']]['picture'] = $val['picture'];
			$frames[$val['id']]['type'] = $val['type'];
			$frames[$val['id']]['demo_id'] = $val['demo_id'];
			$frames[$val['id']]['id'] = $val['id'];
		}
		$demo_ids = array_unique($demo_ids);
		$condition['a.id'] = array('in',$demo_ids);
		$demoInfos = M('Demos')->alias('a')->join('ft_series b ON b.id= a.series_id','left')->where($condition)->field(array('a.id'=>'id','concat(b.dirname,"/",a.dirname)'=>'dirname'))->select();
		foreach($demoInfos as $val2){
			$demos[$val2['id']] = $val2['dirname'];
		}
		foreach($frames as $val3){
			if($val3['type'] == 2){
				deldir(BASE_PATH.'/Uploads/'.$demos[$val3['demo_id']].'/exercises/e'.$val3['id'] );
			}else{
				$path = BASE_PATH . '/Uploads/'.$demos[$val3['demo_id']].'/'.$val3['picture'];
				if ( !unlink($path) ) {
					err ( '图片删除失败：' . $path );
				}
			}
		}

	}

	// 保存后删除上传文件
	private function my_after_update($rcds) {
		foreach ($rcds as $val) {
			$path = BASE_PATH . '/Uploads/'.$val;
			if ( !unlink($path) ) {
				err ( '图片删除失败：' . $path );
			}
		}
	}



}