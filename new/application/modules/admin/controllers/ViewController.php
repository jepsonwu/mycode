<?php
class Admin_ViewController extends DM_Controller_Admin
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
        $searchTypeValue= $this->_getParam('searchTypeValue');

        $CheckStatus= $this->_getParam('CheckStatus',-1);
        $ReportNum= $this->_getParam('ReportNum',-1);
        $start_date= $this->_getParam('start_date');
        $end_date= $this->_getParam('end_date');

        $viewModel = new Model_Topic_View();
        $select = $viewModel->select()->setIntegrityCheck(false);
        $select->from('topic_views as tv');
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m','tv.MemberID = m.MemberID','UserName');
        //$select->joinLeft('message_report as mr','mr.InfoID = tv.ViewID AND InfoType = 2',"IFNULL(COUNT(*),0) AS ReportNum");
         
        if(!empty($searchType) && !empty($searchTypeValue)){
            if($searchType == 'TopicID'){
                $select->where("tv.".$searchType."=?", $searchTypeValue);
            }elseif ($searchType == 'ViewContent') {
                $select->where("tv.".$searchType." like ?", "%{$searchTypeValue}%");
            }elseif ($searchType == 'UserName') {
                $select->where("m.".$searchType." = ?", $searchTypeValue);
            }  
        }

        if($CheckStatus!=-1){
            $select->where("tv.CheckStatus = ?", $CheckStatus);
        }
        if($ReportNum!=-1){
            if($ReportNum == 0){
                $select->where("tv.ReportNum = 0");
            }
            if($ReportNum == 1){
                $select->where("tv.ReportNum > 0");
            }         
        }
        
        if(!empty($start_date)){
            $select->where("tv.CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("tv.CreateTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $viewModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','ViewID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $viewModel->fetchAll($select)->toArray();

        $specialCotentModel = new Model_SpecialContent();

        foreach ($results as  &$item) {
            $hasContent = $specialCotentModel->getByTypeID(1,$item['ViewID']);
            $item['HasJoin'] = $hasContent;
        }
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

    /**
     * 审核
     */
    public function checkAction()
    {
        $view_id = intval($this->_getParam('view_id',0));
        $status = intval($this->_getParam('status',0));
        $this->view->view_id = $view_id;
        $this->view->status = $status;

        if($this->getRequest()->isPost()){
            $this->_helper->viewRenderer->setNoRender();
            $remind = intval($this->_getParam('remind',0));
            $forbid = intval($this->_getParam('forbid',0));

            $viewID = intval($this->_getParam('viewID',0));
            $checkStatus = intval($this->_getParam('checkStatus',0));
            $reason = trim($this->_getParam('Reason',''));
   
            if($viewID <= 0){
                $this->returnJson(0,'参数错误！');
            }
            
            if(!in_array($checkStatus,array(1,2,3))){
                $this->returnJson(0,'状态参数错误！');
            }
            
            if(empty($reason) && $checkStatus != 1){
                $this->returnJson(0,'请输入理由！');
            }
            $viewModel = new Model_Topic_View();
            $viewInfo = $viewModel->getViewInfo($viewID);
            if($checkStatus ==2 || $checkStatus == 1){
               $retCount = $viewModel->update(array('CheckStatus'=>$checkStatus,'Remark'=>$reason), array('ViewID = ?'=>$viewID)); 
            }else{
               $retCount = $viewModel->update(array('CheckStatus'=>$checkStatus), array('ViewID = ?'=>$viewID));
            }
                      
            if($retCount > 0 && !empty($viewInfo)){
            	$topicModel = new Model_Topic_Topic();
            	if($checkStatus == 1 && $viewInfo['CheckStatus'] == 2){
            		$topicModel->increaseViewNum($viewInfo['TopicID']);
            	}elseif($checkStatus == 2 && $viewInfo['CheckStatus'] == 1){
            		$topicModel->increaseViewNum($viewInfo['TopicID'],-1);
            		$redisObj = DM_Module_Redis::getInstance();
            		$value = 'delete-'.$viewID.'-'.$this->memberInfo->MemberID;
            		$redisObj->rpush(Model_Topic_View::getFollowedViewKey(),$value);
            	}

                $violationModel = new Model_Topic_Violations();
                $violationParam = array(
                        'MemberID'=>$viewInfo['MemberID'],
                        'TopicID'=>$viewInfo['TopicID'],
                        'InfoType'=>1,
                        'InfoID'=>$viewInfo['ViewID'],
                        'Reason'=>$reason
                    );
                if($checkStatus == 2){
                    $violationParam['ProcessMethod']=3;
                }elseif ($checkStatus == 3) {
                    $violationParam['ProcessMethod']=1;
                }
                $violationModel->insert($violationParam);
            }
        
            $this->returnJson();
        }
    } 

    /**
     * 广告链接
     */
    public function adsLinkAction()
    {
        $view_id = intval($this->_getParam('view_id',0));
        $h5Url = $this->_request->getScheme().'://'.$this->_request->getHttpHost()."/api/public/view-detail/viewID/";
        $schemaUrl = "caizhu://caizhu/standpoint?id=";
        $this->view->h5Url=$h5Url.$view_id;
        $this->view->schemaUrl = $schemaUrl.$view_id;

       
    }

    /**
     * 加入财猪日报
     */
    public function joinSpecialAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $view_id = intval($this->_getParam('view_id',0));
        $specialCotentModel = new Model_SpecialContent();
        $hasContent = $specialCotentModel->getByTypeID(1,$view_id);
        if($hasContent >0){
            $this->returnJson(0,'该观点已经加入财猪日报！');
        }else{
            $param= array(
                    'ContentType'=>1,
                    'ContentTypeID' =>$view_id,
                    'SpecialType'=>2
                );
            $specialCotentModel->insert($param);
            $this->returnJson(1,'添加成功！');
        }

       
    }



}