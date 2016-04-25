<?php

namespace Admin\Controller;

class SeriesController extends CommonController {
	
	public function _filter(&$map, &$param) {
		//
		$rqst = I ('request.');
		//
		$this->_com_filter('id', $rqst['id'], 2, $map, $param);
		$this->_com_filter('name', $rqst['name'], 1, $map, $param);
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
	
	
	// 保存新增
	public function insert() {
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');
		
		$post['create_id'] = get_user_id();
		$post['create_time'] = time();
		$post['update_time'] = time();
		$post['categoryIds']=implode(',',$post['categoryIds']);
		unset($post['pic1']);
		unset($post['pic2']);
		if ($id=$model->add ($post)) {

			$npost['id']=$id;
			$npost['topicId']=date('Ymd').'000'+$id;
			$npost['dirname']=$npost['topicId'];
			$dir_name='/'.$npost['dirname'].'/';
			$uploadInfo = $this->uploadFile($dir_name);
			$npost['pic']=$uploadInfo['pic1']['savename'].','.$uploadInfo['pic2']['savename'];

			$model->save($npost);

			$this->ajaxReturn ( make_url_rtn ( '新增成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '新增失败!' ) );
		}
	}

	public function edit() {
		//
		$model = D ( CONTROLLER_NAME );
		$id = I ( 'request.id' );
		$vo = $model->getById ( $id );
		$categoryIds=explode(',',$vo['categoryIds'] );
		// $data = $model->getLastSql();
		$this->assign('imgs',explode(',',$vo['pic']));
		$this->assign('tag_types',C('TAG_TYPES'));
		$this->assign ( 'vo', $vo );
		$this->assign ( 'categoryIds', $categoryIds);
		//
		$this->display ();
	}	
	// 保存编辑
	public function update() {
		$model = D ( CONTROLLER_NAME );
		$post = I('post.');

		$post['create_id'] = get_user_id();
		$post['update_time'] = time();
		$uploadInfo = $this->uploadFile('/'.$post['dirname'].'/');

		$pic1 = $uploadInfo['pic1']['savename']?$uploadInfo['pic1']['savename']:$post['pic1_default'];
		$pic2 = $uploadInfo['pic2']['savename']?$uploadInfo['pic2']['savename']:$post['pic2_default'];
		$post['pic']=$pic1.','.$pic2;
		foreach($uploadInfo as $key=>$val){
			$rcds[] = $post['dirname'].'/'.$post[$key.'_default'];
		}
		$post['categoryIds']=implode(',',$post['categoryIds']);
		unset($post['pic1']);
		unset($post['pic2']);
		unset($post['pic1_default']);
		unset($post['pic2_default']);
		if (false !== $model->save ($post)) {
			if (method_exists ( $this, 'my_after_update' )&&!empty($rcds)) {
				$this->my_after_update ( $rcds );
			}
			$this->ajaxReturn ( make_url_rtn ( '编辑成功!' ) );
		} else {
			$this->ajaxReturn ( make_rtn ( '编辑失败!' ) );
		}
	}
	
	//
	public function add() {
		//
		$this->assign('tag_types',C('TAG_TYPES'));
		//
		$this->display ();
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
				$condition2['series_id'] = $condition [$pk] = array ( 'in', explode ( '|', $id ) );
				// 获取frames集合
				$rcds = $model->where ( $condition )->select ();
				$nums = M('demos')->where ( $condition2 )->count();
				if($nums>0){
					// 失败返回
					$this->ajaxReturn(make_rtn('大课内还有未删除的小课，请清理完后重试！'));
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
	
	// 打包zip文件
	public function makeZip() {
		$series_id = I('request.id');
		
		if ( empty($series_id) ) {
			return false;
		}
		
		$series_dir_name = M('Series')->where('id = %d', $series_id)->getField('dirname');
		
		\Org\Util\HZip::zipDir(BASE_PATH.'/Uploads/'.$series_dir_name, BASE_PATH.'/Uploads/'.$series_dir_name.'.zip');
		
	}
	
	// 生成json文件
	public function makeJson() {
		$series_id = I('request.id');
		
		if ( empty($series_id) ) {
			return false;
		}

		$series = M('Series')->where('id = %d', $series_id)->find();
		
		$demos = M('Demos')->where('series_id = %d', $series_id)->field('id,chName,enName,dirname')->select();
		
		foreach ( $demos as $key => $value ) {
			$demos[$key] = array(
					'courseId' => $value['id'],
					'chName' => $value['chName'],
					'enName' => $value['enName'],
					'json' => 'task.json',
					'resourcePath' => UPLOADS_URL.'/Uploads/'.$series['dirname'].'/'.$value['dirname'].'.zip',
			);
		}
		
		$series_json = array(
				"directory" => $series['enName'],
				"name" => $series['chName'],
				"topicId" => $series['topicId'],
				"tag" => $series['tag'],
				"intro" => $series['intro'],
				"tasks" => $demos
		);

		$series_json = json_encode($series_json);
		// 中文转码
		$series_json = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $series_json);
		$series_json = str_replace("\/", "/", $series_json);
		// 写入文件
		//$path = BASE_PATH.'\Uploads\flight_resource\topic.json';
		//$path = pathinfo(BASE_PATH.'/Uploads/flight_resource/topic.json');
		//$source_path = $path['dirname'].'/'.$path['basename'];
		$source_path = BASE_PATH.'/Uploads/'.$series['dirname'].'/topic.json';
		file_put_contents($source_path, $series_json);
		$this->ajaxReturn ( make_rtn ( '生成json文件成功！' ) );
	}
	
	//大课状态操作（上线or下线）
	public function statu(){
		$data['id']=I('request.id');
		$data['status']=I('request.status');
		$data['update_time'] = time();
		$series=M('Series');
		if($series->save($data)){
			$this->ajaxReturn(make_url_rtn('操作成功!'));
		}else{
			$this->ajaxReturn(make_url_rtn('操作失败!'));
		}
	}

	// 大课状态操作(是否推荐)
	public function recommend() {
		$data['id'] = I('request.id');
		$data['recommend'] = I('request.recommend');
		$series = M('Series');
		if($series->save($data)) {
			$this->ajaxReturn(make_url_rtn('操作成功!'));
		} else {
			$this->ajaxReturn(make_url_rtn('操作失败!'));
		}
	}
	
	private function uploadFile($savePath){

		$upload = new \Think\Upload();// 实例化上传类
		$upload->autoSub = false;
		$upload->maxSize   =     3145728 ;// 设置附件上传大小
		$upload->exts      =     array('jpg','png');// 设置附件上传类型
		$upload->savePath  =     $savePath; // 设置附件上传目录
		$info   =   $upload->upload();
//		if(!$info) {// 上传错误提示错误信息
//			$this->ajaxReturn ( make_rtn($upload->getError()) );
//		}
		return $info;
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

	// 删除后删除上传文件
	private function my_after_delete($rcds) {
		foreach($rcds as $val){
			deldir(BASE_PATH.'/Uploads/'.$val['dirname'] );
		}
	}
}