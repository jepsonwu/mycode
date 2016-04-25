<?php

namespace Admin\Controller;

class DemosController extends CommonController {
	
	public function _filter(&$map, &$param) {
		//
		$rqst = I ('request.');
		//
		$this->_com_filter('id', $rqst['id'], 2, $map, $param);
		$this->_com_filter('topic', $rqst['topic'], 1, $map, $param);
		$this->_com_filter('series_id', $rqst['series_id'], 1, $map, $param);
	}
	
	public function index() {
		//
		$map = array();
		$param = array();
		// 列表过滤器，生成查询Map对象
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ($map, $param);
		}
		//
		$_SESSION ['bkgd'] ['menuNow'] = CONTROLLER_NAME;
		$model = D(CONTROLLER_NAME);
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $param, 'id', 'id', true );
		}
		//
		$this->display();
	}
	// 查询后
	protected function _processer(&$volist) {
		$series=M('Series')->field('id,chName')->select();
		//
		foreach ( $volist as &$value ) {
			foreach($series as $key => $val){
				if($val['id']==$value['series_id']){
					$series_chName=$val['chName'];
				}
			}
			$value['series_name']=$series_chName;
		}
	}	
	
	// 保存新增
	public function insert() {
		$enName=I('enName');
		$series_id=I('series_id');
		$rs=M('Demos')->where('enName="'.$enName.'" AND series_id="'.$series_id.'"')->find();
		if($rs){
			$this->ajaxReturn ( make_rtn ( '小课名称已存在!' ) );
		}
		$sdirname=M('Series')->where('id=%d',array($series_id))->getField('dirname');
		$upload = new \Think\Upload();// 实例化上传类
		$upload->autoSub = false;
		$upload->maxSize   =     10485760;// 设置附件上传大小
		$upload->exts      =     array('mp3');// 设置附件上传类型
		if(file_exists(BASE_PATH . '/Uploads/'.$sdirname.'/'.strFilter($_POST['enName']))) $_POST['enName'].=time();
		$upload->savePath  =     '/'.$sdirname.'/'.strFilter($_POST['enName']).'/'; // 设置附件上传目录
		$info   =   $upload->upload();
		if(!$info) {// 上传错误提示错误信息 
			$this->ajaxReturn ( make_rtn($upload->getError()) );  
		}
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		
		$sort = $model->where('series_id = %d', $post['series_id'])->max('sort');
		$post['sort'] = is_null($sort) ? 1 : ($sort+1);
		$post['create_id'] = get_user_id();
		$post['create_time'] = time();
		$post['update_time'] = time();
		$post['audio']=$info['audio']['savename'];
		$post['dirname']=strFilter($_POST['enName']);

		if (false !== $model->add ($post)) {
			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}	
	public function edit(){
		$series = M("Series")->order('create_time desc')->getField('id,chName');
		$this->assign('series', $series);
		$model = D ( CONTROLLER_NAME );
		$id = I ( 'request.id' );
		$vo = $model->getById ( $id );
		// $data = $model->getLastSql();
		$this->assign ( 'vo', $vo );
		//
		$this->display ();
	}	
	public function update() {
		$enName=I('enName');	
		$rs=M('Demos')->where('enName="'.$enName.'" and id not in('.I('id').')')->find();
		if($rs){
			$this->ajaxReturn ( make_rtn ( '小课名称已存在!' ) );
		}

		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		$sdirname=M('Series')->where('id=%d',array($post['series_id']))->getField('dirname');
		$post['create_id'] = get_user_id();
		$post['update_time'] = time();

		// 重命名打包路径
		$enName = strFilter($enName);
		$dirname = I('dirname');
		if (!empty($enName) && $enName != $dirname) {
			$packagePath = BASE_PATH . '/Uploads/' . $sdirname . '/'. $dirname;
			$newPath = BASE_PATH . '/Uploads/' . $sdirname . '/'. $enName;
			$renameResult = rename($packagePath, $newPath);
			if (!$renameResult) {
				$this->ajaxReturn ( make_rtn ( '重命名失败!' ) );
			} else {
				$post['dirname'] = $enName;
			}
		}
		
		if(!empty($_FILES['audio']['tmp_name'])){
			$upload = new \Think\Upload();// 实例化上传类
			$upload->autoSub = false;
			$upload->maxSize   =     10485760;// 设置附件上传大小
			$upload->exts      =     array('mp3');// 设置附件上传类型
			$upload->savePath  =     '/'.$sdirname.'/'. $enName .'/'; // 设置附件上传目录
			$info   =   $upload->upload();
			if(!$info) {// 上传错误提示错误信息 
				$this->ajaxReturn ( make_rtn($upload->getError()) );  
			}
			if(!empty($post['audio']))
				$rcds = BASE_PATH . '/Uploads/' . $sdirname . '/' . $enName . '/' . $post['audio'];
			$post['audio']=$info['audio']['savename'];
		}	
		if (false !== $model->save ($post)) {
			if ($rcds && !unlink($rcds) ) {
				err ( '图片删除失败：' . $rcds );
			}
			$this->ajaxReturn ( make_url_rtn ( '编辑成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
		}
	}

	/**
	 * 永久删除
	 */
	public function foreverDelete($id = null){

		$model = D ( CONTROLLER_NAME );
		//
		if (! empty ( $model )) {
			$pk = $model->getPk ();
			if (empty ( $id )) {
				$id = $_REQUEST [$pk];
			}
			if (isset ( $id )) {
				$ids = explode ( '|', $id );
				$condition2 = $condition = array ();
				$condition2['demo_id'] = $condition [$pk] = array ( 'in', explode ( '|', $id ) );
				// 获取frames集合
				$rcds = $model->where ( $condition )->select ();
				$nums = M('frame_detail')->where ( $condition2 )->count();
				if($nums>0){
					// 失败返回
					$this->ajaxReturn(make_rtn('小课内还有未删除的内容，请清理完后重试！'));
					exit;
				}
				$defult = $model->where ( $condition )->delete ();
				if ($defult !== false) {
					if (method_exists ( $this, 'my_after_delete' )) {
						$this->my_after_delete ( $rcds );
					}

					$this->ajaxReturn(make_url_rtn('永久删除成功!'));
				}else{
					// 失败返回
					$this->ajaxReturn(make_rtn('永久删除失败！'));
				}
			} else {
				$this->ajaxReturn ( make_rtn ( '非法操作！未选中需永久删除的对象！' ) );
			}
		}
	}

	//
	public function add() {
		//
		$series = M("Series")->order('create_time desc')->getField('id,chName');
		$this->assign('series', $series);
		//
		$this->assign('serid',I('get.series_id'));
		$this->display ();
	}
	
	// 打包zip文件
	public function makeZip($demo_id) {
		// $demo_id = I('request.id');
		
		// if ( empty($demo_id) ) {
		// 	return false;
		// }
		
		$demo = M('Demos')->where('id = %d', $demo_id)->field('dirname,series_id')->find();
		$series_dir_name = M('Series')->where('id = %d', $demo['series_id'])->getField('dirname');

		\Org\Util\HZip::zipDir(BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'], BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'.zip');
		$this->zipSize=sprintf("%.2f",(filesize(BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'.zip')/1024/1024)).'M';

		$cond['zip_size'] = (int)filesize(BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'.zip')/1024;
		M('Demos')->where("id={$demo_id}")->save($cond);
		$this->tot_size_str.=$this->zipSize.' | ';
	}
	
	// 生成json文件
	public function makeJson() {
		$demo_id = I('request.id');
		$this->pub_makeJson($demo_id);
		$this->ajaxReturn(make_rtn('打包json成功! 压缩包: '.$this->zipSize));
	}

	/**
	 * [all_json_zip 多选操作生成json、打包zip]
	 * @return [type] [description]
	 */
	public function all_json_zip(){
		$data=I('get.id');
		$ids=explode('|',$data);
		for($i=0;$i<count($ids);$i++){
			$this->pub_makeJson($ids[$i]);
		}
		$this->ajaxReturn(make_rtn('批量打包json成功! 压缩包: '.$this->tot_size_str));
	}

	public function pub_makeJson($demo_id){
		if ( empty($demo_id) ) {
			return false;
		}
		
		$demo = M('Demos')->where('id = %d', $demo_id)->find();
		$series_dir_name = M('Series')->where('id = %d', $demo['series_id'])->getField('dirname');

		$frame = M('FrameDetail')->join('LEFT join ft_exercise_detail ON ft_frame_detail.id=ft_exercise_detail.sort')
			->where('ft_frame_detail.demo_id = %d', $demo_id)->order('(ft_frame_detail.time+0)')
			->field('ft_frame_detail.id,ft_frame_detail.time,ft_frame_detail.type,ft_frame_detail.picture,ft_frame_detail.title,
					ft_frame_detail.intro,ft_exercise_detail.type etype,ft_exercise_detail.label,ft_exercise_detail.guide,
					ft_exercise_detail.audio,ft_exercise_detail.audios,ft_exercise_detail.pic,ft_exercise_detail.classifications,
					ft_exercise_detail.contents,ft_exercise_detail.explanation,ft_exercise_detail.feedbacks,ft_exercise_detail.answer,
					ft_exercise_detail.enContent,ft_exercise_detail.chContent,ft_exercise_detail.content')->select();
		// $frames = M('FrameDetail')->field('id,demo_id,sort,create_time,create_id',true)->where('demo_id = %d', $demo_id)->order('(time+0)')->select();
		// $exercises = M('ExerciseDetail')->field('id,folder_name,demo_id,sort,create_time,create_id',true)->where('demo_id = %d', $demo_id)->order('sort')->select();
		foreach ($frame as $key => $value) {
			$frame_tmp['id']=$value['id'];
			$frame_tmp['time']=$value['time'];
			$frame_tmp['type']=$value['type'];
			$frame_tmp['picture']=$value['picture'];
			$frame_tmp['title']=htmlspecialchars_decode($value['title']);
			$frame_tmp['intro']=htmlspecialchars_decode($value['intro']);
			$frames[]=$frame_tmp;
			
			if($value['type']==2){
				$exercise_tmp['id']=$value['id'];
				$exercise_tmp['type']=$value['etype'];
				$exercise_tmp['label']=$value['label'];
				$exercise_tmp['guide']=$value['guide'];
				$exercise_tmp['audio']=$value['audio'];
				$exercise_tmp['audios']=$value['audios'];
				$exercise_tmp['pic']=$value['pic'];
				$exercise_tmp['content']=htmlspecialchars_decode($value['content']);
				$exercise_tmp['classifications']=htmlspecialchars_decode($value['classifications']);
				$exercise_tmp['contents']=htmlspecialchars_decode($value['contents']);
				$exercise_tmp['explanation']=htmlspecialchars_decode($value['explanation']);
				$exercise_tmp['feedbacks']=$value['feedbacks'];
				$exercise_tmp['answer']=htmlspecialchars_decode($value['answer']);
				$exercise_tmp['enContent']=$value['enContent'];
				$exercise_tmp['chContent']=$value['chContent'];
				$exercises[]=$exercise_tmp;
			}

		}
		foreach ($frames as $key => $value) {
			// type=1时，有道词典单词解释
			if ($value['type'] == 1) {
				$frames[$key]['meaning'] = getYoudaoMeaning($value['title']);

				// 知识点翻译存表
				$knowledge_data['knowledge_id'] = $value['id'];
				$knowledge_data['title'] = $value['title'];
				if(!empty($frames[$key]['meaning'])) {
					$knowledge_data['meaning'] = json_encode($frames[$key]['meaning']);
				}
				$knowledge_result = M('Knowledges')->add($knowledge_data, array(), true);
				if($knowledge_result === false) {
					$this->ajaxReturn(make_url_rtn('知识点翻译失败'));
				}
			}
			if (is_null($frames[$key]['meaning'])) {
				unset($frames[$key]['meaning']);
			}
			
			foreach ($value as $k => $val) {
				if ( empty($val) && $val !== 0 && $val !== '0') {
					// type=1,3 intro默认为''
					if (  ($value['type'] != 1 && $value['type'] != 3) || $k != 'intro'  ) {
						unset( $frames[$key][$k] );
					}
				}
			}
		}
		foreach ($exercises as $key => $value) {
			strToArray($exercises[$key]['audios']);
			strToArray($exercises[$key]['contents']);
			strToArray($exercises[$key]['feedbacks']);
			strToArray($exercises[$key]['classifications']);
			strToArray($exercises[$key]['answer']);
			if ($exercises[$key]['type'] != 3) {
				strToArray($exercises[$key]['pic']);
			}
			// 填空题
			if ($exercises[$key]['type'] == 6) {
				strToArray($exercises[$key]['explanation']);
				
			}
			foreach ($value as $k => $val) {
				if ( empty($val) ) {
					unset( $exercises[$key][$k] );
				}
			}
			if ($exercises[$key]['type'] == 6) {
				$exercises[$key]['feedbacks']=$exercises[$key]['answer'];
				unset($exercises[$key]['answer']);
			}
		}
		
		$demo_json = array(
				"pic" => $demo['pic'],
				"topic" => $demo['topic'], //标题
				"teacher" => $demo['teacher'], //老师
				"audio" => $demo['audio'], //主音频
				"intro" => $demo['intro'],
				"frames" => $frames,
				"exercises" => $exercises
		);
		
		$demo_json = json_encode($demo_json);
		// 中文转码
		//$demo_json = json_encode($demo_json, JSON_UNESCAPED_UNICODE); // php 5.4之后，而正式服务器是5.3.3版本
		$demo_json = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $demo_json);
		$demo_json = str_replace("\/", "/", $demo_json);
		// 写入文件
		if(file_exists(BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'])){	
			$source_path = BASE_PATH.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'/task.json';
			file_put_contents($source_path, $demo_json);
			$this->makeZip($demo_id);
		}else{
			$this->ajaxReturn(make_url_rtn('打包目录不存在!'));
		}
	}

	// 删除后删除上传文件
	private function my_after_delete($rcds) {
		foreach($rcds as $val){
			$series_ids[] = $val['series_id'];
			$demos[$val['id']]['dirname'] = $val['dirname'];
			$demos[$val['id']]['series_id'] = $val['series_id'];
			$demos[$val['id']]['id'] = $val['id'];
		}
		$series_ids = array_unique($series_ids);
		$condition['id'] = array('in',$series_ids);
		$seriesInfos = M('series')->where($condition)->field(array('id','dirname'))->select();
		foreach($seriesInfos as $val2){
			$series[$val2['id']] = $val2['dirname'];
		}
		foreach($demos as $val3){
			deldir(BASE_PATH.'/Uploads/'.$series[$val3['series_id']].'/'.$val3['dirname'] );
			unlink(BASE_PATH.'/Uploads/'.$series[$val3['series_id']].'/'.$val3['dirname'].'.zip' );
		}

	}
}