<?php
class Admin_LessonController extends DM_Controller_Admin
{
	public function indexAction()
	{
		$lessonModuleModel =new Model_LessonModule();
        $moduleList = $lessonModuleModel->getAllModule();
        $this->view->moduleList = $moduleList;
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        $ModuleID = intval($this->_getParam('ModuleID',-1));
        $LessonTitle = trim($this->_getParam('LessonTitle',''));
        $LessonType = trim($this->_getParam('LessonType',-1));
        $Status = trim($this->_getParam('Status',-1));
        $start_date = trim($this->_getParam('start_date',''));
        $end_date = trim($this->_getParam('end_date',''));

        $lessonModel = new Model_Lesson();
        $select = $lessonModel->select()->from('lessons',array('LessonID','LessonTitle','LessonType','Status','LessonPic','ModuleID','LessonDes','AddTime','ViewCount'));

        $ModuleID !=-1 && $select->where("ModuleID = ?", $ModuleID);
        $LessonType !=-1 && $select->where("LessonType = ?", $LessonType);
        $Status !=-1 && $select->where("Status = ?", $Status);
        !empty($LessonTitle) && $select->where("LessonTitle = ?", $LessonTitle);
        !empty($start_date) && $select->where("AddTime >= ?", date('Y-m-d 00:00:00',strtotime($start_date)));
        !empty($end_date) && $select->where("AddTime <= ?", date('Y-m-d 23:59:59',strtotime($end_date)));

        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM',$select->__toString());
                
        //总条数
        $total = $lessonModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $select->order('Status desc')->order("LessonID desc");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $lessonModel->fetchAll($select)->toArray();
        $lessonModuleModel = new Model_LessonModule();
        $lessonClassModel = new Model_LessonClass();
        $list = array();
        foreach($results as $row){
            $moduleInfo = $lessonModuleModel->fetchRow(array('ModuleID=?'=>$row['ModuleID']))->toArray();
            $row['ModuleName'] = empty($moduleInfo)?'':$moduleInfo['ModuleName'];
            $row['LessonClassCount'] = $lessonClassModel->getAdapter()->fetchOne("SELECT COUNT(*) AS total FROM lesson_class WHERE LessonID={$row['LessonID']} AND `Status`=1");
            $list[] = $row;
        }
        //$this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$list));
	}
    
    public function getLessonListByModuleAction(){
        $ModuleID = intval($this->_getParam('ModuleID',-1));
        $lessonModel = new Model_Lesson();
        $select = $lessonModel->select()->from('lessons',array('LessonID','LessonTitle','LessonType'));

        $ModuleID !=-1 && $select->where("ModuleID = ?", $ModuleID);
        $select->order("LessonID desc");
        $results = $lessonModel->fetchAll($select)->toArray();
        $this->_helper->json(array('list'=>$results));
    }

    /**
	 * 添加
	 */
    public function addAction()
    { 
    	if($this->getRequest()->isPost()){
	        $this->_helper->viewRenderer->setNoRender();
            
            $ModuleID = intval($this->_getParam('ModuleID',0));
            if($ModuleID <=0 ){
                $this->returnJson(0,'请选择课堂模块！');
            }
	        $LessonTitle = trim($this->_getParam('LessonTitle',''));
	        if(empty($LessonTitle)){
	        	$this->returnJson(0,'课程标题不能为空！');
	        }
            $LessonDes = trim($this->_getParam('LessonDes',''));
            if(empty($LessonDes)){
                $this->returnJson(0,'课程简介不能为空！');
            }
            if(mb_strlen($LessonDes,'utf8')>300){
                $this->returnJson(0,'课程简介不能超过300字！');
            }

            $upload_image = isset($_FILES['image'])?$_FILES['image']:'';
            if (empty($upload_image)) {
                $this->returnJson(0,'请上传课程图片！');
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
                $LessonPic = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
               
            $param = array(
                'ModuleID'=>$ModuleID,
                'LessonTitle'=>$LessonTitle,
                'LessonDes'=>$LessonDes,
                'LessonPic'=>$LessonPic,
                'LessonType'=>intval($this->_getParam('LessonType',0)),
                'Status'=>intval($this->_getParam('Status',0))
                );
            $lessonModel = new Model_Lesson();
            $lessonModel->insert($param);         
	        $this->returnJson(1);
    	}
        $lessonModuleModel =new Model_LessonModule();
        $moduleList = $lessonModuleModel->getAllModule();
        $this->view->moduleList = $moduleList;
    }
    
    

    /**
     * 编辑
     */
    public function editAction()
    {
    	$LessonID = intval($this->_getParam('LessonID',0));
    	$lessonModel = new Model_Lesson();
    
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();

            $LessonID = intval($this->_getParam('LessonID',0));
            if($LessonID <=0 ){
                $this->returnJson(0,'参数错误!');
            }
            $ModuleID = intval($this->_getParam('ModuleID',0));
            if($ModuleID <=0 ){
                $this->returnJson(0,'课堂模块不能为空!');
            }
            $LessonTitle = $this->_getParam('LessonTitle','');
            if(empty($LessonTitle)){
                $this->returnJson(0,'课程标题不能为空');
            }
    		$LessonDes = $this->_getParam('LessonDes','');
            if(empty($LessonDes)){
                $this->returnJson(0,'课程简介不能为空');
            }
            if(mb_strlen($LessonDes,'utf8')>300){
                $this->returnJson(0,'课程简介不能超过300字！');
            }
	    	$param = array(
                'ModuleID'=>$ModuleID,
                'LessonID' => $LessonID,
                'LessonTitle'=>$LessonTitle,
                'LessonDes'=>$LessonDes,
                'Status'=>intval($this->_getParam('CheckStatus',0))
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
                    $param['LessonPic'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
            
    		$lessonModel->update($param,array('LessonID = ?'=>$LessonID));
    		$this->returnJson(1);
    	}
    	$lessonInfo = $lessonModel->find($LessonID)->toArray();
    	$this->view->lessonInfo = $lessonInfo[0];
        $lessonModuleModel =new Model_LessonModule();
        $moduleList = $lessonModuleModel->getAllModule();
        $this->view->moduleList = $moduleList;
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