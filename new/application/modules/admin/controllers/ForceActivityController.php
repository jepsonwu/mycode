<?php
class Admin_ForceActivityController extends DM_Controller_Admin
{
	public function indexAction()
	{

    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        $ActivityName= trim($this->_getParam('ActivityName',''));

        $start_date= trim($this->_getParam('start_date',''));
        $end_date= trim($this->_getParam('end_date',''));

        $forceModel = new Model_ForceActivity();
        $select = $forceModel->select()->from('force_activity');
 

        if(!empty($ActivityName )){
            $select->where("ActivityName = ?", $ActivityName);
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
        $total = $forceModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','ActivityID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $forceModel->fetchAll($select)->toArray();
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

            $upload_image = isset($_FILES['image'])?$_FILES['image']:'';
            if (empty($upload_image)) {
                $this->returnJson(0,'请上传活动图片！');
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
                $ActivityPic = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }

            $ActivityName = trim($this->_getParam('ActivityName',''));
            $ActivityLink = trim($this->_getParam('ActivityLink',''));
            $CountPerDay = intval($this->_getParam('CountPerDay',0));
            $StartTime = trim($this->_getParam('StartTime',''));
            $EndTime = trim($this->_getParam('EndTime',''));

            if(empty($ActivityName)){
                $this->returnJson(0,'活动名称不能为空!');
            }
            if(empty($ActivityLink)){
                $this->returnJson(0,'活动链接不能为空!');
            }

            if($CountPerDay <0){
                $this->returnJson(0,'出现次数不能为负数!');
            }
            if(empty($StartTime)){
                $this->returnJson(0,'开始时间不能为空!');
            }
            if(empty($EndTime)){
                $this->returnJson(0,'结束时间不能为空!');
            }


            $param = array(
                'ActivityName'=>$ActivityName,
                'ActivityPic'=>$ActivityPic,
                'ActivityLink'=>$ActivityLink,
                'CountPerDay'=>$CountPerDay,
                'StartTime'=>$StartTime,
                'EndTime'=>$EndTime,
                'AddTime'=>date('Y-m-d H:i:s',time())
                );
            $forceModel = new Model_ForceActivity();
            $forceID = $forceModel->insert($param);         
            $this->returnJson(1);
        }

    } 



    /**
     * 编辑
     */
    public function editAction()
    {
    	$force_activity_id = intval($this->_getParam('force_activity_id',0));
    	$forceModel = new Model_ForceActivity();

    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            $ActivityID = intval($this->_getParam('ActivityID',0));
            if($ActivityID <=0 ){
                $this->returnJson(0,'参数错误!');
            }

            $ActivityName = trim($this->_getParam('ActivityName',''));
            $ActivityLink = trim($this->_getParam('ActivityLink',''));
            $CountPerDay = intval($this->_getParam('CountPerDay',0));
            $StartTime = trim($this->_getParam('StartTime',''));
            $EndTime = trim($this->_getParam('EndTime',''));
            if(empty($ActivityName)){
                $this->returnJson(0,'活动名称不能为空!');
            }
            if(empty($ActivityLink)){
                $this->returnJson(0,'链接地址不能为空!');
            }
            if($CountPerDay <0){
                $this->returnJson(0,'出现次数不能为负数!');
            }
            if(empty($StartTime)){
                $this->returnJson(0,'开始时间不能为空!');
            }
            if(empty($EndTime)){
                $this->returnJson(0,'结束时间不能为空!');
            }

	    	$param = array(
                'ActivityName' => $ActivityName,
                'ActivityLink'=>$ActivityLink,
                'CountPerDay'=>$CountPerDay,
                'StartTime'=>$StartTime,
                'EndTime'=>$EndTime
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
                    $param['ActivityPic'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
            
    		$forceModel->update($param,array('ActivityID = ?'=>$ActivityID));
    		$this->returnJson(1);
    	}
    	$forceInfo = $forceModel->find($force_activity_id)->toArray();
    	$this->escapeVar($forceInfo);
    	$this->view->forceInfo = $forceInfo[0];
    }

   /**
     * 删除
     */
    public function delAction() {
        if( !$id = (int) $this->_request->getParam('id') ) {
            $this->returnJson(parent::STATUS_FAILURE, '参数错误');
        }
        try {
            $forceModel = new Model_ForceActivity();
            if($forceModel->delete(array('ActivityID = ?'=>$id)) ) {
                $this->returnJson(parent::STATUS_OK, '删除成功');
            } else {
                $this->returnJson(parent::STATUS_FAILURE, '删除失败');
            }
        } catch (Exception $e) {
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
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