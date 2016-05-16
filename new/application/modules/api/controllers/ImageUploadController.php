<?php
/**
 * 文件上传   已废弃
 * 
 * @author Mark
 *
 */
class Api_ImageUploadController extends Action_Api
{
	protected $uploadPath = "/public/upload";
	
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	public function uploadAction()
	{
		$this->isPostOutput();
		$memberID = $this->memberInfo->MemberID;
		$viewID = intval($this->_request->getParam('viewID',0));
		$sortIndex = intval($this->_request->getParam('sortIndex',0));
		try{
			$uploadTarget = $this->_request->getParam('uploadTarget','');
			if(empty($uploadTarget)){
				throw new Exception('上传类别参数错误');
			}
			
			$viewID = $this->_request->getParam('viewID',0);
			if($uploadTarget == 'view' && empty($viewID)){
				throw new Exception('观点ID参数不能为空');
			}
			
			if(!isset($_FILES['uploadfile']['name']) || empty($_FILES['uploadfile']['name'])){
				throw new Exception('请选择上传文件');
			}
			if($_FILES['uploadfile']['size'] > 10*1024*1024){
				throw new Exception("上传文件过大，不能超过10M");
			}
			$imgtype = getimagesize($_FILES['uploadfile']['tmp_name']);
			if(!in_array($imgtype[2], array(1, 2, 3, 6))){
				throw new Exception("文件类型不符合要求，请上传图片文件");
			}
		
			//目录
			$temp='/'.date('Y').'/'.date('m').'/'.date('d').'/';
			$upload_dir = str_replace('\\','/',APPLICATION_PATH.'/../'.$this->uploadPath.$temp);
			if(!file_exists($upload_dir)){
				mkdir("$upload_dir",0777,true);
			}
			
			//文件后缀
			$type = array(1 => 'gif', 2 => 'jpg', 3 => 'png', 6 => 'bmp');
			
			//文件名
			$mainName = md5($memberID.microtime(true).rand(100000,9999999));
			$filename =$mainName.".".$type[$imgtype[2]];
			
			$flag = move_uploaded_file($_FILES['uploadfile']['tmp_name'], $upload_dir . $filename);
			if(!$flag){
				throw new Exception("文件无法上传");
			}
			
			$thumbName = $mainName.'_200x200.'.$type[$imgtype[2]];
			
			$dstpath = $upload_dir.$thumbName;
			
			$imageModel = new Model_Image();
			$imageModel->resizeImage($upload_dir.$filename, 200, 200, 0, $dstpath);
			$fullHost = $this->_request->getScheme().'://'.$this->_request->getHttpHost().'/upload'.$temp;
			$viewImageModel = new Model_Topic_ViewImage();
			$viewImageModel->addImage($viewID, $temp.$filename, $temp.$thumbName, $sortIndex);
			$this->returnJson(parent::STATUS_OK,'',array('Uri'=>$fullHost.$filename,'ThumbUri'=>$fullHost.$thumbName));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	public function testAction()
	{
		header('Content-type: text/html');
		Zend_Layout::startMvc()->disableLayout();
		echo $this->view->render('image-upload/test.phtml');
	}
}