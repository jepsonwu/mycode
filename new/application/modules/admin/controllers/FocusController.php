<?php
class Admin_FocusController extends DM_Controller_Admin
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
		
        $FocusName = trim($this->_getParam('FocusName',''));
        $start_date = $this->_getParam('start_date');
        $end_date = $this->_getParam('end_date');

        $focusModel = new Model_Focus();
        $select = $focusModel->select()->setIntegrityCheck(false);
        $select->from('focus as f');
        //$select->joinLeft('channel_focus as cf', 'cf.FocusID = f.FocusID','');
        //$select->joinLeft('channel as c', 'c.ChannelID = cf.ChannelID','ChannelName');
 
        if(!empty($FocusName)){
            $select->where("f.FocusName like ?", "%{$FocusName}%");
        }

        if(!empty($start_date)){
            $select->where("f.CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("f.CreateTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $focusModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','FocusID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $focusModel->fetchAll($select)->toArray();
        $channelFocusModel = new Model_ChannelFocus();
        foreach ($results as &$item) {
            $channelFocus = $channelFocusModel->getInfo($item['FocusID'],$channelID=null,'');
            $item['ChannelName'] = implode(',', array_column($channelFocus,'ChannelName'));
        }
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
	        $FocusName = $this->_getParam('FocusName','');
            $focusType = $this->_getParam('focusType',array());

	        if(empty($FocusName)){
	        	$this->returnJson(0,'关注点不能为空!');
	        }
	           
            $upload_image = $_FILES['image'];
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
                $focusImg = "http://img.caizhu.com/".$ret['hash'];
                 unlink($file);           
            }


            try {
                $focusModel = new Model_Focus();
                $focusParam = array();
                $focusParam['FocusName'] = $FocusName;
                $focusParam['FocusImg'] = $focusImg;
                $focusTypeArr = array('IsBeforeRegisterFocus','IsRegistedFocus','IsGroupFocus','IsTopicFocus');
                foreach ($focusTypeArr as $item) {
                    if(in_array($item,$focusType)){
                        $focusParam[$item] = 1;
                    }else{
                        $focusParam[$item] = 0;
                    }
                }

                $focusID = $focusModel->addFocus($focusParam);
                if($focusID >0 ){
                    $channelIDArr = $this->_request->getParam('ChannelID',array());
                    if( count($channelIDArr) >0 ) {
                        $channelFocusModel = new Model_ChannelFocus();
                        $info = $channelFocusModel->getInfo($focusID, null,'ChannelID');
                        foreach ($info as $item) {
                            if(!in_array($item['ChannelID'], $channelIDArr)){
                                $channelFocusModel->removeChannelFocus($focusID,$item['ChannelID']);
                            }
                        }
                        foreach ($channelIDArr as $channelID) {
                            $channelFocusModel->addChannelFocus($focusID,$channelID);
                        }
                    }
                }else{
                    $this->returnJson(0, '添加失败，请修改后再试！');
                }

            } catch (Exception $e) {
                $this->returnJson(0, $e->getMessage()); 
            }

	        $this->returnJson();
    	} 

        $channelModel = new Model_Channel();
        $channels = $channelModel->getChannels();
        $this->view->channels = $channels;
        
    }
     

    /**
     * 编辑
     */
    public function editAction()
    {
    	$focus_id = intval($this->_getParam('focus_id',0));
    	$focusModel = new Model_Focus();
        $channelFocusModel = new Model_ChannelFocus();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$focus_id = intval($this->_getParam('FocusID',0));
            if($focus_id <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
    		$FocusName = $this->_getParam('FocusName','');
            if(empty($FocusName)){
                $this->returnJson(0,'关注点不能为空!');
            }

            
	    	$param = array(
                'FocusName'=>$FocusName,
                );
   		    $focusType = $this->_getParam('focusType',array());
            $focusTypeArr = array('IsBeforeRegisterFocus','IsRegistedFocus','IsGroupFocus','IsTopicFocus');
            foreach ($focusTypeArr as $item) {
                if(in_array($item,$focusType)){
                    $param[$item] = 1;
                }else{
                    $param[$item] = 0;
                }
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
                    $param['FocusImg'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);
                }              
            }
            
            try {
                $focusModel->update($param,array('FocusID = ?'=>$focus_id));

                $channelIDArr = $this->_request->getParam('ChannelID',array());
                if( count($channelIDArr) >0 ) {

                    $info = $channelFocusModel->getInfo($focus_id, null,'ChannelID');
                    foreach ($info as $item) {
                        if(!in_array($item['ChannelID'], $channelIDArr)){
                            $channelFocusModel->removeChannelFocus($focus_id,$item['ChannelID']);
                        }
                    }
                    foreach ($channelIDArr as $channelID) {
                        $channelFocusModel->addChannelFocus($focus_id,$channelID);
                    }
                }
        		
            } catch (Exception $e) {
                $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
            }
    		$this->returnJson(1);
    	}
    	 $focusInfo = $focusModel->find($focus_id)->toArray();
    	 $this->escapeVar($focusInfo);
    	 $this->view->focus = $focusInfo[0];
         $channelModel = new Model_Channel();
         $channels = $channelModel->getChannels();
         $this->view->channels = $channels;

         $channelFocus = $channelFocusModel->getInfo($focus_id,null,'ChannelID');
         $channelIDArr = array_column($channelFocus,'ChannelID');

         $this->view->channelIDArr = $channelIDArr;
    }

    public function removeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $focus_id = intval($this->_getParam('focus_id',0));
        $focusModel = new Model_Focus();     
        $focusModel->deleteFocus($focus_id);
        $this->returnJson();    
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
            $dir = '/focus/';
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