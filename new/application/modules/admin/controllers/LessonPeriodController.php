<?php
class Admin_LessonPeriodController extends DM_Controller_Admin
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
        $ClassTitle = trim($this->_getParam('ClassTitle',''));
        $LessonTitle = trim($this->_getParam('LessonTitle',''));
        $Status = trim($this->_getParam('Status',-1));
        $start_date = trim($this->_getParam('start_date',''));
        $end_date = trim($this->_getParam('end_date',''));

        $lessonClassModel = new Model_LessonClass();
        $select = $lessonClassModel->select()->setIntegrityCheck(false)->from('lesson_class as a');
        $select->joinLeft('lessons as b','a.LessonID = b.LessonID',array('b.LessonTitle','b.ModuleID','b.LessonType'));
        $ModuleID !=-1 && $select->where("b.ModuleID = ?", $ModuleID);
        $Status !=-1 && $select->where("a.Status = ?", $Status);
        !empty($LessonTitle) && $select->where("b.LessonTitle = ?", $LessonTitle);
        !empty($ClassTitle) && $select->where("a.ClassTitle = ?", $ClassTitle);
        !empty($start_date) && $select->where("a.AddTime >= ?", date('Y-m-d 00:00:00',strtotime($start_date)));
        !empty($end_date) && $select->where("a.AddTime <= ?", date('Y-m-d 23:59:59',strtotime($end_date)));

        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());
                
        //总条数
        $total = $lessonClassModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $select->order('a.Status desc')->order("a.ClassID desc");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表
        $lessonModuleModel = new Model_LessonModule();
        $results = $lessonClassModel->fetchAll($select)->toArray();
        foreach($results as &$row){
            $moduleInfo = $lessonModuleModel->fetchRow(array('ModuleID=?'=>$row['ModuleID']))->toArray();
            $row['ModuleName'] = empty($moduleInfo)?'':$moduleInfo['ModuleName'];
        }
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

	/**
	 * 添加
	 */
    public function addAction()
    { 
    	if($this->getRequest()->isPost()){
	        $this->_helper->viewRenderer->setNoRender();
            
            $LessonID = intval($this->_getParam('LessonID',0));
            if($LessonID <=0 ){
                $this->returnJson(0,'请选择课程!');
            }
	        $ClassTitle = trim($this->_getParam('ClassTitle',''));
	        if(empty($ClassTitle)){
	        	$this->returnJson(0,'课时标题不能为空!');
	        }
            $Status = trim($this->_getParam('Status',1));
            $ClassLink = "";
            $IsNative = 1;
            $lessonType = intval($this->_getParam('lessonType',0));
            if($lessonType==1){//图文
                $IsNative = $this->_getParam('IsNative',0);
                if(!$IsNative){
                    $ClassLink = trim($this->_getParam('ClassLink',''));
                    if(empty($ClassLink)){
                        $this->returnJson(0,'课时链接不能为空!');
                    }
                }
            }else{//视频
                $ClassLink = trim($this->_getParam('VideoLink',''));
                if(empty($ClassLink)){
                    $this->returnJson(0,'视频链接不能为空!');
                }
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
                $ClassPic = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
               
            $param = array(
                'LessonID'=>$LessonID,
                'ClassTitle'=>$ClassTitle,
                'ClassLink'=>$ClassLink,
                'ClassPic'=>$ClassPic,
                'Status'=>$Status,
                'IsNative'=>$IsNative
                );
            $lessonClassModel = new Model_LessonClass();
            $lessonClassID = $lessonClassModel->insert($param);         
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
    	$lesson_class_id = intval($this->_getParam('lesson_class_id',0));
    	$lessonClassModel = new Model_LessonClass();
        $lessonModel = new Model_Lesson();
        $lessonClassInfo = $lessonClassModel->find($lesson_class_id)->toArray();
        $lessonClassInfo = $lessonClassInfo[0];
        $lessonInfo = $lessonModel->find($lessonClassInfo['LessonID'])->toArray();
        $lessonClassInfo['LessonType'] = !empty($lessonInfo[0])?$lessonInfo[0]['LessonType']:0;
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            if($lesson_class_id <=0 ){
                $this->returnJson(0,'参数错误!');
            }
    		$ClassTitle = $this->_getParam('ClassTitle','');
            if(empty($ClassTitle)){
                $this->returnJson(0,'课时标题不能为空!');
            }
            $Status = trim($this->_getParam('Status',1));
            $param = array('ClassTitle'=>$ClassTitle,'Status'=>$Status);
            if($lessonClassInfo['LessonType']==2 || $lessonClassInfo['IsNative']==0){
                $ClassLink = $this->_getParam('ClassLink','');
                if(empty($ClassLink)){
                    $this->returnJson(0,'课时链接不能为空！'.$lessonClassInfo['LessonType'].'-'.$lessonClassInfo['IsNative']);
                }
                $param['ClassLink'] = $ClassLink;
            }
   		
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
                    $param['ClassPic'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
    		$lessonClassModel->update($param,array('ClassID = ?'=>$lesson_class_id));
    		$this->returnJson(1);
    	}
    	
    	//$this->escapeVar($lessonClassInfo);
    	$this->view->lessonClassInfo = $lessonClassInfo;
    }

    public function showAction(){
        $class_id = intval($this->_getParam('lesson_class_id',0));
        $classDetailModel = new Model_LessonClassDetail();
        $select = $classDetailModel->select()->from('lesson_class_details',array('DetailID','DetailType','Content','FontColor','IsBold','ImgWidth','ImgHeight'));
        $select->where("ClassID = ?", $class_id);
        
        //排序
        $select->order('DetailID asc');
        
        $results = $classDetailModel->fetchAll($select)->toArray();
        $this->view->list = $results;
    }
    
    public function addClassAction(){
        $class_id = $this->_getParam('Class_ID',0);
        if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            $classDetailModel = new Model_LessonClassDetail();
            $classDetailModel->getAdapter()->beginTransaction();
            try{
                $class_detail_order = trim($this->_getParam('class_detail_order',''),',');
                $class_detail_order_arr = explode(',',$class_detail_order);
                if(empty($class_id)){
                    throw new Exception('课时ID不能为空！');
                }
                foreach($class_detail_order_arr as $row){
                    if(empty($row)){
                        continue;
                    }
                    $arr = explode('-',$row);
                    $param = array();
                    $param['ClassID'] = $class_id;
                    if($arr[0]=='image'){
                        $param['DetailType'] = 2;
                        $upload_image = isset($_FILES['image'.$arr[1]])?$_FILES['image'.$arr[1]]:'';

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
                                $param['Content'] = "http://img.caizhu.com/".$ret['hash'];
                                $img_size = getimagesize($param['Content']);
                                $param['ImgWidth'] = $img_size[0];
                                $param['ImgHeight'] = $img_size[1];
                                unlink($file);           
                            }
                        }else{
                            continue;
                        }
                    }else{
                        $param['DetailType'] = 1;
                        $content = trim($this->_getParam('Detail_Content'.$arr[1],''));
                        $content = html_entity_decode($content);
                        if(empty($content)){
                            continue;
                        }
                        if(mb_strlen($content,'utf8')>2000){
                            $this->returnJson(0,'课时详情内容不能超过2000字！');
                        }
                        $param['Content'] = $content;
                        $param['FontColor'] = trim($this->_getParam('detail_color'.$arr[1],''));
                        $param['IsBold'] = $this->_getParam('WordIsBold'.$arr[1],0);
                    }
                    $classDetailModel->insert($param);  
                }
                $classDetailModel->getAdapter()->commit();
                $this->returnJson(1);
            }catch(Exception $e){
                $classDetailModel->getAdapter()->rollBack();
                $this->returnJson(0,'保存数据失败');
            }
    	}
        $this->view->class_id = $class_id;
    }

    public function delClassDetailAction(){
        $DetailID = intval($this->_getParam('DetailID',0));
        $classDetailModel = new Model_LessonClassDetail();
        $classDetailModel->delete(array('DetailID=?'=>$DetailID));
        $this->returnJson(1);
    }
    
    public function saveClassDetailAction(){
        $DetailID = intval($this->_getParam('DetailID',0));
        $DetailType = intval($this->_getParam('DetailType',0));
        if(empty($DetailID) || empty($DetailType)){
            $this->returnJson(0,'参数错误',array('code'=>0));
        }
        $param = array();
        if($DetailType==1){
            $content = trim($this->_getParam('detail_content',''));
            if(mb_strlen($content,'utf8')>2000){
                $this->returnJson(0,'课时详情内容不能超过2000字！',array('code'=>0));
            }
            $content = html_entity_decode($content);
            $param['Content'] = $content;
            $param['FontColor'] = trim($this->_getParam('detail_edit_color',''));
            $param['IsBold'] = $this->_getParam('detail_edit_bold',0);
        }else{
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
                    $param['Content'] = "http://img.caizhu.com/".$ret['hash'];
                    $img_size = getimagesize($param['Content']);
                    $param['ImgWidth'] = $img_size[0];
                    $param['ImgHeight'] = $img_size[1];
                    unlink($file);           
                }
            }
        }
        $classDetailModel = new Model_LessonClassDetail();
        $classDetailModel->update($param,array('DetailID=?'=>$DetailID));
        $param['DetailID'] = $DetailID;
        $param['DetailType'] = $DetailType;
        $param['code'] = 1;
        $this->returnJson(0,'',$param);
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