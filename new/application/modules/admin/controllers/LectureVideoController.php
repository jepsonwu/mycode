<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-13
 * Time: 下午4:42
 */
class Admin_LectureVideoController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("Status"),
		"bet" => array("Start_CreateTime", "End_CreateTime"),
		"like" => array("ImagTitle")
	);

	public function listAction()
	{
		//初始化模型
		$lectureVideoModel = new Model_Lecture_Video();
		$select = $lectureVideoModel->select()->setIntegrityCheck(false);

		//多表关联自定义
		$this->_helper->json($this->listResults($lectureVideoModel, $select, "VideoID"));
	}

	//验证参数
	protected $filter_fields = array(
		"V" => array("VideoID", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		//"U" => array("ImageUrl", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"T" => array("ImagTitle", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"VU" => array("VideoUrl", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"D" => array("Description", "require", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"S" => array("Status", "0,1", "状态参数错误！", DM_Helper_Filter::MUST_VALIDATE, "in"),
	);

	/**
	 * 编辑
	 */
	public function editAction()
	{
		$lectureVideo = new Model_Lecture_Video();
		//判断是否为post请求
		if ($this->isPost()) {
			//获取参数
			$this->filterParam();
            $upload_image = isset($_FILES['ImageUrl'])?$_FILES['ImageUrl']:'';

            if (!empty($upload_image)) {
                $image_src = $this->processUpload($upload_image);
            }

            if(isset($image_src) && $image_src){
                $qiniu = new Model_Qiniu();
                $token = $qiniu->getUploadToken();
                $uploadMgr = $qiniu->getUploadManager();
                
                $file = realpath(APPLICATION_PATH.'/../public/upload'.$image_src);
                list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
                if ($err !== null) {
                    var_dump($err);
                } else {
                    //var_dump($ret);
                    $ImageUrl = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
			$param = array(
				"ImageUrl" => $ImageUrl,
				"ImagTitle" => $this->_param['ImagTitle'],
				"VideoUrl" => $this->_param['VideoUrl'],
				"Description" => $this->_param['Description'],
				"Status" => $this->_param['Status']
			);

			$res = $lectureVideo->update($param, array('VideoID = ?' => $this->_param['VideoID']));
			$res === false && $this->failJson("修改失败");

			$this->succJson();
		} else {
			//获取参数
			$this->filterParam(array("V"));
			//
			$video_info = $lectureVideo->fetchRow(array('VideoID = ?' => $this->_param['VideoID']))->toArray();
			$this->view->video_info = $video_info;
		}
	}

	/**
	 * 新增
	 */
	public function addAction()
	{
		if ($this->isPost()) {
			$this->filterParam(array("T", "VU", "D", "S"));
			if (empty($_FILES['ImageUrl'])) {
                $this->returnJson(0,'请上传图片！');
            }
			$upload_image = $_FILES['ImageUrl'];
	        $image_src = $this->processUpload($upload_image);

            $qiniu = new Model_Qiniu();
            $token = $qiniu->getUploadToken();
            $uploadMgr = $qiniu->getUploadManager();
            
            $file = realpath(APPLICATION_PATH.'/../public/upload'.$image_src);
            list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
            if ($err !== null) {
                var_dump($err);
            } else {
                //var_dump($ret);
                $ImageUrl = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
			$param = array(
				"LectureID" => 0,
				"ImageUrl" => $ImageUrl,
				"ImagTitle" => $this->_param['ImagTitle'],
				"VideoUrl" => $this->_param['VideoUrl'],
				"Description" => $this->_param['Description'],
				"CreateTime" => date('Y-m-d H:i:s'),
				"Status" => $this->_param['Status']
			);

			$lectureVideo = new Model_Lecture_Video();
			$res = $lectureVideo->insert($param);
			$res === false && $this->failJson("新增失败");

			$this->succJson();
		}
	}

	 /**
     * 处理图片上传
     * @return string
     */
    protected function processUpload($upload_image)
    {
        $src = '';
        if(isset($upload_image)){
            $upFile = $upload_image;
            $allowTypes = array('image/jpeg'=>'jpeg','image/gif'=>'gif','image/png'=>'png');
            if(!array_key_exists($upFile['type'],$allowTypes)){
                $this->returnJson(0,'图片格式不正确');
            }
    
            //文件类型
            $fileType = $allowTypes[$upFile['type']];
            //构造文件名
            $curTimestamp = time();
            $dir = '/topic/';
            if(!file_exists(APPLICATION_PATH.'/../public/upload'.$dir)){
                mkdir(APPLICATION_PATH.'/../public/upload'.$dir,0775,true);
            }
            $fileName = $dir.$curTimestamp.'_'.rand(10000,99999).'.'.$fileType;
            $fullPath = APPLICATION_PATH.'/../public/upload'.$fileName;
    
            if(is_uploaded_file($upFile['tmp_name'])){
                if(!move_uploaded_file($upFile['tmp_name'], $fullPath)){
                    $this->returnJson(0,'移动文件出错啦');
                }
                //$src = 'http://'.$this->_request->getHttpHost().$fileName;
                $src = $fileName;
            }else{
                $this->returnJson(0,'出错啦');
            }
        }
    
        return $src;
    }
}