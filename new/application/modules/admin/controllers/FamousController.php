<?php
class Admin_FamousController extends DM_Controller_Admin
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
		
        $MemberID= intval($this->_getParam('MemberID',0));
        $IsShowColumn= intval($this->_getParam('IsShowColumn',-1));
        $IsShowCounsel= intval($this->_getParam('IsShowCounsel',-1));

        $start_date= trim($this->_getParam('start_date',''));
        $end_date= trim($this->_getParam('end_date',''));

        $famousModel = new Model_Famous();
        $select = $famousModel->select()->setIntegrityCheck(false);
        $select->from('famous as f');
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m','f.MemberID = m.MemberID','UserName');

        if($MemberID >0){
            $select->where("f.MemberID = ?", $MemberID);
        }

        if($IsShowColumn !=-1){
            $select->where("f.IsShowColumn = ?", $IsShowColumn);
        }
        if($IsShowCounsel !=-1){
            $select->where("f.IsShowCounsel = ?", $IsShowCounsel);
        }

        if(!empty($start_date)){
            $select->where("f.AddTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("f.AddTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $famousModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','FID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $famousModel->fetchAll($select)->toArray();
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}
    

    /**
     * 编辑
     */
    public function editAction()
    {
    	$famous_id = intval($this->_getParam('famous_id',0));
    	$famousModel = new Model_Famous();

    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            $FID = intval($this->_getParam('FID',0));
            if($FID <=0 ){
                $this->returnJson(0,'参数错误!');
            }
            $DetailUrl = trim($this->_getParam('DetailUrl',''));
            if(empty($DetailUrl)){
                $this->returnJson(0,'链接地址不能为空!');
            }
    		// $Experience = trim($this->_getParam('Experience',''));
      //       if(empty($Experience)){
      //           $this->returnJson(0,'从业经历不能为空!');
      //       }

            $IsShowColumn = intval($this->_getParam('IsShowColumn',0));
            $IsShowCounsel = intval($this->_getParam('IsShowCounsel',0));
	    	$param = array(
                'DetailUrl' => $DetailUrl,
                //'Experience'=>$Experience,
                'IsShowColumn'=>$IsShowColumn,
                'IsShowCounsel'=>$IsShowCounsel
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
                    $param['ImgUrl'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
            
    		$famousModel->update($param,array('FID = ?'=>$FID));
    		$this->returnJson(1);
    	}

        $famousInfo = $famousModel->find($famous_id)->toArray();
        $this->escapeVar($famousInfo);
        $this->view->famousInfo = $famousInfo[0];
        $columnModel = new Model_Column_Column();
        $counselModel = new Model_Counsel_Counsel();
        $columnInfo = $columnModel->getMyColumnInfo($famousInfo[0]['MemberID'],1);
        $counselInfo = $counselModel->getMyCounselInfo($famousInfo[0]['MemberID'],1);
        $this->view->hasColumn = !empty($columnInfo)?1:0;
        $this->view->hasCounsel = !empty($counselInfo)?1:0; 
    }

   /**
     * 删除
     */
    public function delAction() {
        if( !$id = (int) $this->_request->getParam('id') ) {
            $this->returnJson(parent::STATUS_FAILURE, '参数错误');
        }
        try {
            $famousModel = new Model_Famous();
            if($famousModel->delete(array('FID = ?'=>$id)) ) {
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