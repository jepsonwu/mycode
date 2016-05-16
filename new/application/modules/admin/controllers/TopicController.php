<?php
class Admin_TopicController extends DM_Controller_Admin
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
		
        $searchType= trim($this->_getParam('searchType',''));
        $searchTypeValue= trim($this->_getParam('searchTypeValue',''));

        $CheckStatus= $this->_getParam('CheckStatus',-1);
        $IsAnonymous= $this->_getParam('IsAnonymous',-1);
        $start_date= $this->_getParam('start_date');
        $end_date= $this->_getParam('end_date');

        $topicModel = new Model_Topic_Topic();
        $select = $topicModel->select()->setIntegrityCheck(false);
        $select->from('topics as t');
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m','t.MemberID = m.MemberID','UserName');
        
 
        if(!empty($searchType) && !empty($searchTypeValue)){
            if($searchType == 'TopicName'){
                $select->where("t.".$searchType." like ?", '%'.$searchTypeValue.'%');
            }elseif($searchType == 'UserName'){
                $select->where("m.".$searchType."=?", $searchTypeValue);
            }
        }

        if($IsAnonymous!=-1){
            $select->where("t.IsAnonymous = ?", $IsAnonymous);
        }

        if($CheckStatus!=-1){
            $select->where("t.CheckStatus = ?", $CheckStatus);
        }
        if(!empty($start_date)){
            $select->where("t.CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("t.CreateTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $topicModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','TopicID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $topicModel->fetchAll($select)->toArray();
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
	        $topicName = $this->_getParam('TopicName','');
	        if(empty($topicName)){
	        	$this->returnJson(0,'话题名称不能为空!');
	        }
	           
	        $status = $this->_getParam('CheckStatus',-1);
            if(!in_array($status,array(0,1,2))){
                $this->returnJson(0,'状态参数错误！');
            }

            $IsAnonymous = $this->_getParam('IsAnonymous',-1);
            if(!in_array($IsAnonymous,array(0,1))){
                $this->returnJson(0,'状态参数错误！');
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
                $backImge = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
            $pinyinModel = new Model_Pinyin();
            $capitalChar = $pinyinModel->initial($topicName);
            $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->topicCreated->member_id;
            $param = array(
                'TopicName'=>$topicName,
                'CapitalChar'=>strtoupper($capitalChar),
                'CheckStatus'=>$status,
                'IsAnonymous'=>$IsAnonymous,
                'CreateTime'=>date('Y-m-d H:i:s'),
                'CheckTime' => $status>0?date('Y-m-d H:i:s'):'',
                'BackImage'=>$backImge,
                'MemberID'=>$sysMemberID
                );
            $topicModel = new Model_Topic_Topic();
            $topicID = $topicModel->insert($param);
            $focusIDArr = $this->_getParam('focusIDArr',array());
            if($topicID > 0 && !empty($focusIDArr)){             
                $topicFocusModel = new Model_Topic_Focus();
                foreach ($focusIDArr as $focusID) {
                   $topicFocusModel->addFocus($topicID,$focusID);
                }                
            }
	        $this->returnJson();
    	}
        $focusModel = new Model_Focus();
        $focusList = $focusModel->getFocusList('IsTopicFocus');
        $this->view->focusList = $focusList;

    }
    
    

    /**
     * 编辑
     */
    public function editAction()
    {
    	$topic_id = intval($this->_getParam('topic_id',0));
    	$topicModel = new Model_Topic_Topic();
        $focusModel = new Model_Focus();
        $focusList = $focusModel->getFocusList('IsTopicFocus');
        $this->view->focusList = $focusList;
        $topicFocusModel = new Model_Topic_Focus();
        $selectFocus = $topicFocusModel->getInfo($topic_id);
        $this->view->selectFocus = array_column($selectFocus,'FocusID');
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$topic_id = intval($this->_getParam('TopicID',0));
            if($topic_id <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
    		$topicName = $this->_getParam('TopicName','');
            if(empty($topicName)){
                $this->returnJson(0,'话题名称不能为空!');
            }
             
            $IsAnonymous = $this->_getParam('IsAnonymous',-1);
            if(!in_array($IsAnonymous,array(0,1))){
                $this->returnJson(0,'状态参数错误！');
            }

            $status = $this->_getParam('CheckStatus',-1);
            if(!in_array($status,array(0,1,2))){
                $this->returnJson(0,'状态参数错误！');
            }
            $pinyinModel = new Model_Pinyin();
            $capitalChar = $pinyinModel->initial($topicName);
	    	$param = array(
                'TopicName'=>$topicName,
                'CapitalChar'=>strtoupper($capitalChar),
                'IsAnonymous'=>$IsAnonymous,
                'CheckStatus'=>$status
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
                    $param['BackImage'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }
            
    		$topicModel->update($param,array('TopicID = ?'=>$topic_id));
            $focusIDArr = $this->_getParam('focusIDArr',array());
            if(!empty($focusIDArr)){             
                $topicFocusModel = new Model_Topic_Focus();
                $focus = array_column($topicFocusModel->getInfo($topic_id),'FocusID');
                foreach ($focus as $fid) {
                    if(!in_array($fid, $focusIDArr)){
                        $topicFocusModel->removeFocus($topic_id,$fid);
                    }
                }
                foreach ($focusIDArr as $focusID) {
                   $topicFocusModel->addFocus($topic_id,$focusID);
                }                
            }

    		$this->returnJson(1);
    	}
    	 $topicInfo = $topicModel->find($topic_id)->toArray();
    	 $this->escapeVar($topicInfo);
    	 $this->view->topic = $topicInfo[0];
    }

    /**
     * 审核
     */
    public function checkAction()
    {
        $topic_id = intval($this->_getParam('topic_id',0));
        $status = intval($this->_getParam('status',0));
        $this->view->topicID = $topic_id;
        $this->view->status = $status;
        if($this->getRequest()->isPost()){
            $this->_helper->viewRenderer->setNoRender();
            $remind = $this->_getParam('remind',0);

            $topicID = $this->_getParam('topicID',0);
            $status = $this->_getParam('checkStatus',0);
            $remark = $this->_getParam('remark','');
            if($topicID <= 0){
                $this->returnJson(0,'参数错误');
            }

            $topicModel = new Model_Topic_Topic();
            $topicInfoOld = $topicModel->getTopicInfo($topicID,null);
            
            if(!in_array($status,array(1,2))){
                $this->returnJson(0,'状态参数错误');
            }

            if($status == 2 && empty($remark)){
                 $this->returnJson(0,'请输入隐藏理由！');
            }
            $topicModel = new Model_Topic_Topic();
            $topicModel->update(array('CheckStatus'=>$status,'Remark'=>$remark,'CheckTime'=>date('Y-m-d H:i:s')), array('TopicID = ?'=>$topicID));

            
            $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
            $content='你创建的名为 "'.$topicInfoOld['TopicName'].'"的话题';
            $content.= $status==1?'已通过审核':'未通过审核，创建话题请遵守《财猪话题公约》';
            
            if($remind == 1 && $topicInfoOld['CheckStatus'] != $status && $topicInfoOld['MemberID'] > 0){
            	//审核通过
            	$followModel = new Model_Topic_Follow();
            	$followModel->addFollow($topicID,$topicInfoOld['MemberID']);
            	$easeModel = new Model_IM_Easemob();
            	$ext['TopicID'] = $topicID;
            	$ext['TopicName'] = $topicInfoOld['TopicName'];
            	$easeModel->yy_hxSend(array($topicInfoOld['MemberID']), $content,'txt','users',$ext,$sysMemberID);
            }
            $this->returnJson(); 
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


    /**
     * 广告链接
     */
    public function adsLinkAction()
    {
        $topic_id = intval($this->_getParam('topic_id',0));
        $h5Url = $this->_request->getScheme().'://'.$this->_request->getHttpHost()."/api/public/view-list/topicID/";
        $schemaUrl = "caizhu://caizhu/viewList?id=";
        $this->view->h5Url=$h5Url.$topic_id;
        $this->view->schemaUrl = $schemaUrl.$topic_id;

       
    }

}