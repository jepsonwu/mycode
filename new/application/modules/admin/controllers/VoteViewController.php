<?php
/**
 * 投票观点管理
 * @author kitty
 */

class Admin_VoteViewController extends DM_Controller_Admin {
	
	public function indexAction()
	{
        $votePeriodModel = new Model_VotePeriod();
        $periodInfo = $votePeriodModel->getPeriods();
        $this->view->periodInfo = $periodInfo;
		
	}

	public function listAction()
	{
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        $periodID= intval($this->_getParam('PeriodID',-1));
        $viewType= intval($this->_getParam('ViewType',-1));
        $status= intval($this->_getParam('Status',-1));  
        
        $voteViewModel = new Model_VoteViewList();
        $select = $voteViewModel->select()->setIntegrityCheck(false);
        $select->from('vote_view_list as vl');
        $select->joinLeft('vote_period as vp','vl.PeriodID = vp.PeriodID','PeriodName');
        
        if($periodID >-1){
            $select->where("vl.PeriodID = ?", $periodID);
        }

        if($viewType >-1){
            $select->where("vl.ViewType = ?", $viewType);
        }
        if($status >-1){
            $select->where("vl.Status = ?", $status);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $voteViewModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','VoteViewListID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $voteViewModel->fetchAll($select)->toArray();      
        $viewModel = new Model_Topic_View();
        foreach ($results as &$item) {
            $viewInfo = $viewModel->getViewInfo($item['ViewID']);
            $item['ViewContent'] = !empty($viewInfo)?$viewInfo['ViewContent']:'';
        }
                           
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

	/**
	 * 添加观点
	 */
    public function addAction()
    { 
        $voteViewModel = new Model_VoteViewList();
        $votePeriodModel = new Model_VotePeriod();
        $periodInfo = $votePeriodModel->getPeriods();
        $this->view->periodInfo = $periodInfo;

    	if($this->getRequest()->isPost()){
	        $this->_helper->viewRenderer->setNoRender();
            $periodID = intval($this->_getParam('PeriodID',0));
            $viewID = intval($this->_getParam('ViewID',0));
            $viewType = intval($this->_getParam('ViewType',1));
	        $status = intval($this->_getParam('Status',1));
            //$url = '';

	        if($periodID <1){
	        	$this->returnJson(0,'请选择活动期数！');
	        }

            $add_period =$votePeriodModel->getPeriodInfo($periodID);
            if(empty($add_period)){
                $this->returnJson(0,'您选择的期数不存在！');
            }

            if($viewID <1){
                $this->returnJson(0,'请输入本期活动的观点ID！');
            }

            $viewModel = new Model_Topic_View();
            $viewInfo = $viewModel->getViewInfo($viewID);

	        if(empty($viewInfo) || $viewInfo['CheckStatus']!=1){
                $this->returnJson(0,'您输入的观点不存在，或未审核通过！');
            }

            if($viewInfo['TopicID'] != $add_period['TopicID']){
                $this->returnJson(0,'您输入的观点不在本期投票的话题下！');
            }

            if(!in_array($viewType, array(1,2))){
                $this->returnJson(0,'参数错误！');
            }  

            $voteViewInfo = $voteViewModel->getVoteViewInfo($periodID,$viewType,$viewID);
            if(!empty($voteViewInfo)){
                $this->returnJson(0,'您输入的观点已存在！');
            }

            // if($viewType == 2){
            //     $url = trim($this->_getParam('Url',''));
            //     if(empty($url)){
            //         $this->returnJson(0,'请输入热门观点的url地址！');
            //     }
            // }

            if(!in_array($status, array(0,1))){
                $this->returnJson(0,'参数错误！');
            }  

            $param = array(
                'PeriodID'=>$periodID,
                'ViewID'=>$viewID,
                'ViewType'=>$viewType,
                //'Url'=>$url,
                'Status'=>$status,
                );
            $voteViewID = $voteViewModel->insert($param);
	        $this->returnJson(1);
    	}
    }


    /**
     * 编辑观点
     */
    public function editAction()
    {
    	$voteview_id = intval($this->_getParam('voteview_id',0));
        $voteViewModel = new Model_VoteViewList();
        $votePeriodModel = new Model_VotePeriod();
        $periodInfo = $votePeriodModel->getPeriods();
        $this->view->periodInfo = $periodInfo;

    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$voteview_id = intval($this->_getParam('VoteViewListID',0));
    		$periodID = intval($this->_getParam('PeriodID',0));
            $viewID = intval($this->_getParam('ViewID',''));
    		$viewType = intval($this->_getParam('ViewType'));
	        $status = intval($this->_getParam('Status'));
            //$url = '';

            if($voteview_id < 1 ){
                $this->returnJson(0,'参数错误!');
            }
    		
	        if($periodID < 1){
	        	$this->returnJson(0,'请选择活动期数！');
	        }

            $edit_period =$votePeriodModel->getPeriodInfo($periodID);
            if(empty($edit_period)){
                $this->returnJson(0,'您选择的期数不存在！');
            }
            if($viewID <1){
                $this->returnJson(0,'请输入本期活动的观点ID！');
            }

            $viewModel = new Model_Topic_View();
            $viewInfo = $viewModel->getViewInfo($viewID);

            if(empty($viewInfo) || $viewInfo['CheckStatus']!=1){
                $this->returnJson(0,'您输入的观点不存在，或未审核通过！');
            }

            if($viewInfo['TopicID'] != $edit_period['TopicID']){
                $this->returnJson(0,'您输入的观点不在本期投票的话题下！');
            }

            if(!in_array($viewType, array(1,2))){
                $this->returnJson(0,'参数错误！');
            } 

            $voteViewInfo = $voteViewModel->getVoteViewInfo($periodID,$viewType,$viewID);
            if(!empty($voteViewInfo) && $voteview_id != $voteViewInfo['VoteViewListID']){
                $this->returnJson(0,'您输入的观点已存在！');
            }
            // if($viewType == 2){
            //     $url = trim($this->_getParam('Url',''));
            //     if(empty($url)){
            //         $this->returnJson(0,'请输入热门观点的url地址！');
            //     }
            // }

            if(!in_array($status, array(0,1))){
                $this->returnJson(0,'参数错误！');
            }  

            $param = array(
                'PeriodID'=>$periodID,
                'ViewID'=>$viewID,
                'ViewType'=>$viewType,
                //'Url'=>$url,
                'Status'=>$status,
                );

    		$voteViewModel->update($param,array('VoteViewListID = ?'=>$voteview_id));           
    		$this->returnJson(1);
    	}

    	$voteViewInfo = $voteViewModel->find($voteview_id)->toArray();
    	$this->escapeVar($voteViewInfo);
    	$this->view->voteViewInfo = $voteViewInfo[0];
    }

}