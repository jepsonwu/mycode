<?php
class Admin_LessonModuleController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        $ModuleName= trim($this->_getParam('ModuleName',''));
        $Status= intval($this->_getParam('Status',-1));
        $start_date= trim($this->_getParam('start_date',''));
        $end_date= trim($this->_getParam('end_date',''));

        $lessonModelModel = new Model_LessonModule();
        $select = $lessonModelModel->select()->from('lesson_modules');

        if(!empty($ModuleName)){
            $select->where("ModuleName = ?", $ModuleName);
        }

        if($Status!=-1){
            $select->where("Status = ?", $Status);
        }
        if(!empty($start_date)){
            $select->where("AddTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("AddTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $lessonModelModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $select->order('DisOrder desc')->order('ModuleID desc');         
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $lessonModelModel->fetchAll($select)->toArray();
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

	/**
	 * 添加
	 */
    public function addAction()
    { 
    	if($this->getRequest()->isPost()){
	        $this->_helper->viewRenderer->setNoRender();
	        $ModuleName = $this->_getParam('ModuleName','');
	        if(empty($ModuleName)){
	        	$this->returnJson(0,'模块名称不能为空!');
	        }

            if(mb_strlen($ModuleName, 'utf8')>6){
                $this->returnJson(0,'模块名称不能超过6个字!');
            }
            $upload_image = isset($_FILES['image'])?$_FILES['image']:'';
            if (empty($upload_image)) {
                $this->returnJson(0,'请上传背景图片！');
            }

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
                $ModulePic = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
               
            $Status = $this->_getParam('Status',1);
            if(!in_array($Status,array(0,1))){
                $this->returnJson(0,'状态参数错误！');
            }

            $param = array(
                'ModuleName'=>$ModuleName,
                'ModulePic'=>$ModulePic,
                'Status'=>$Status
                );
            $lessonModelModel = new Model_LessonModule();
            $lessonModelID = $lessonModelModel->insert($param);         
	        $this->returnJson(1);
    	}

    }
    
    

    /**
     * 编辑
     */
    public function editAction()
    {
    	$lesson_module_id = intval($this->_getParam('lesson_module_id',0));
    	$lessonModuleModel = new Model_LessonModule();
    
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$ModuleID = intval($this->_getParam('ModuleID',0));
            if($ModuleID <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
    		$ModuleName = $this->_getParam('ModuleName','');
            if(empty($ModuleName)){
                $this->returnJson(0,'模块名称不能为空!');
            }
            if(mb_strlen($ModuleName, 'utf8')>6){
                $this->returnJson(0,'模块名称不能超过6个字!');
            }
            $Status = $this->_getParam('CheckStatus',1);
            if(!in_array($Status,array(0,1))){
                $this->returnJson(0,'状态参数错误！');
            }
            $DisOrder = intval($this->_getParam('DisOrder',0));

	    	$param = array(
                'ModuleName'=>$ModuleName,
                'Status'=>$Status,
                'DisOrder'=>$DisOrder
                );
   		
            $upload_image = isset($_FILES['image'])?$_FILES['image']:'';

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
                    $param['ModulePic'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
            
    		$lessonModuleModel->update($param,array('ModuleID = ?'=>$ModuleID));
    		$this->returnJson(1);
    	}
    	 $lessonModuleInfo = $lessonModuleModel->find($lesson_module_id)->toArray();
    	 $this->escapeVar($lessonModuleInfo);
    	 $this->view->lessonModuleInfo = $lessonModuleInfo[0];
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