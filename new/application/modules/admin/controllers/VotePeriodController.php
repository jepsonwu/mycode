<?php
/**
 * 投票期数管理
 * @author kitty
 */

class Admin_VotePeriodController extends DM_Controller_Admin {
	
	public function indexAction()
	{
		
	}

	public function listAction()
	{
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        // $searchType= trim($this->_getParam('searchType',''));
        // $searchTypeValue= $this->_getParam('searchTypeValue');

        $votePeriodModel = new Model_VotePeriod();
        $select = $votePeriodModel->select()->from('vote_period');

        
        // if(!empty($start_date)){
        //     $select->where("tv.CreateTime >= ?", $start_date);
        // }

        // if(!empty($end_date)){
        //     $select->where("tv.CreateTime <= ?", $end_date);
        // }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $votePeriodModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','PeriodID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $votePeriodModel->fetchAll($select)->toArray();
        $topicModel = new Model_Topic_Topic();
        $viewModel = new Model_Topic_View();
            
        foreach ($results as  &$item) {
            $topicInfo = $topicModel->getTopicInfo($item['TopicID']);
            $viewInfo = $viewModel->getViewInfo($item['WinViewID']);
            $item['TopicName'] = $topicInfo['TopicName'];
            $item['ViewContent'] = $viewInfo['ViewContent'];
        }
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

	/**
	 * 添加期数
	 */
    public function addAction()
    { 
    	if($this->getRequest()->isPost()){
	        $this->_helper->viewRenderer->setNoRender();
            $periodName = trim($this->_getParam('PeriodName',''));
	        $introduction = trim($this->_getParam('Introduction',''));
            $topicID = intval($this->_getParam('TopicID',0));
            $reward = trim($this->_getParam('Reward',''));
	        $startTime = $this->_getParam('StartTime');
	        $endTime = $this->_getParam('EendTime');

            if(empty($periodName)){
                $this->returnJson(0,'期数名称不能为空！');
            }
	        if(empty($introduction)){
	        	$this->returnJson(0,'引言不能为空！');
	        }
	           
            if (empty($_FILES['Image'])) {
                $this->returnJson(0,'请上传背景图片！');
            }

	        $image_src = $this->processUpload($_FILES['Image']);

            $qiniu = new Model_Qiniu();
            $token = $qiniu->getUploadToken();
            $uploadMgr = $qiniu->getUploadManager();
            
            $file = realpath(APPLICATION_PATH.'/../public/upload/'.$image_src);
            list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
            if ($err !== null) {
                var_dump($err);
            } else {
                //var_dump($ret);
                $backImge = "http://img.caizhu.com/".$ret['hash'];
                unlink($file);           
            }
            if($topicID<1){
                $this->returnJson(0,'请输入本期话题ID！');
            }
            $topicModel = new Model_Topic_Topic();
            $topicInfo = $topicModel->getTopicInfo($topicID);
            if(empty($topicInfo)){
                $this->returnJson(0,'您输入的话题ID不存在，或暂时未通过审核！');
            }

            if(empty($reward)){
                $this->returnJson(0,'请输入本期奖励！');
            }

            if(empty($startTime)){
				$this->returnJson(0,'请选择活动开始时间！');
            }

            if(empty($endTime)){
				$this->returnJson(0,'请选择活动结束时间！');
            }

            $param = array(
                'PeriodName'=>$periodName,
                'Introduction'=>$introduction,
                'Image'=>$backImge,
                'TopicID'=>$topicID,
                'Reward'=>$reward,
                'StartTime'=>$startTime,
                'EendTime'=>$endTime,
                );
            $votePeriodModel = new Model_VotePeriod();
            $periodID = $votePeriodModel->insert($param);
	        $this->returnJson(1);
    	}
    }


    /**
     * 编辑
     */
    public function editAction()
    {
    	$period_id = intval($this->_getParam('period_id',0));
    	$votePeriodModel = new Model_VotePeriod();
        
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$period_id = intval($this->_getParam('PeriodID',0));
            $periodName = trim($this->_getParam('PeriodName',''));
    		$introduction = trim($this->_getParam('Introduction',''));
            $topicID = intval($this->_getParam('TopicID',0));
            $reward = trim($this->_getParam('Reward',''));
    		$startTime = $this->_getParam('StartTime');
	        $endTime = $this->_getParam('EendTime');
            $winViewID = intval($this->_getParam('WinViewID',0));
            
            if($period_id <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
    		if(empty($periodName)){
                $this->returnJson(0,'期数名称不能为空！');
            }
	        if(empty($introduction)){
	        	$this->returnJson(0,'引言不能为空！');
	        }
            if($topicID<1){
                $this->returnJson(0,'请输入本期话题ID！');
            }
            $topicModel = new Model_Topic_Topic();
            $topicInfo = $topicModel->getTopicInfo($topicID);
            if(empty($topicInfo)){
                $this->returnJson(0,'您输入的话题ID不存在，或暂时未通过审核！');
            }
            if(empty($reward)){
                $this->returnJson(0,'请输入本期奖励！');
            }

            if(empty($startTime)){
				$this->returnJson(0,'请选择活动开始时间！');
            }

            if(empty($endTime)){
				$this->returnJson(0,'请选择活动结束时间！');
            }
            $curDate = date('Y-m-d H:i:s',time());
            if($endTime > $curDate){
                $winViewID =0;
            }

            $param = array(
                    'PeriodName'=>$periodName,
	                'Introduction'=>$introduction,
                    'Reward'=>$reward,
	                'StartTime'=>$startTime,
	                'EendTime'=>$endTime,
                    'WinViewID'=>$winViewID
                );

            $upload_image = isset($_FILES['Image'])?$_FILES['Image']:'';

            if (!empty($upload_image)) {
                $image_src = $this->processUpload($upload_image);
            }

            if(isset($image_src) && $image_src){
                $qiniu = new Model_Qiniu();
                $token = $qiniu->getUploadToken();
                $uploadMgr = $qiniu->getUploadManager();
                
                $file = realpath(APPLICATION_PATH.'/../public/upload/'.$image_src);
                list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
                if ($err !== null) {
                    var_dump($err);
                } else {
                    //var_dump($ret);
                    $param['Image'] = "http://img.caizhu.com/".$ret['hash'];
                    unlink($file);           
                }
            }

    		$votePeriodModel->update($param,array('PeriodID = ?'=>$period_id));           
    		$this->returnJson(1);
    	}
    	$periodInfo = $votePeriodModel->find($period_id)->toArray();
    	$this->escapeVar($periodInfo);
    	$this->view->periodInfo = $periodInfo[0];
        $voteViewModel =  new Model_VoteViewList();
        $voteViewList = $voteViewModel->getVoteViewInfo($period_id,1);
        $viewModel = new Model_Topic_View();
        $voteViewArr = array();   
        foreach ($voteViewList as $key=>$item) {
            $voteViewArr[$key]['ViewID']=$item['ViewID'];
            $viewInfo = $viewModel->getViewInfo($item['ViewID']);
            $voteViewArr[$key]['ViewContent']=mb_substr($viewInfo['ViewContent'],0,20,'utf-8').'...';
        }
        $this->view->voteViewArr = $voteViewArr;
    }


    /**
     * 激活期数
     */
    public function statusAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $period_id = intval($this->_getParam('id',0));
        if($this->_request->isPost()){
            if($period_id < 1){
                $this->returnJson(0,'参数错误');
            }

            $votePeriodModel = new Model_VotePeriod();
            $votePeriodModel->update(array('Status'=>0), array('PeriodID != ?'=>$period_id));
            $votePeriodModel->update(array('Status'=>1), array('PeriodID = ?'=>$period_id));
            $this->returnJson(1,'激活成功！');
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
            if(!file_exists(APPLICATION_PATH.'/../public/upload')){
                mkdir(APPLICATION_PATH.'/../public/upload',0775,true);
            }
            $fileName = $curTimestamp.'_'.rand(10000,99999).'.'.$fileType;
            $fullPath = APPLICATION_PATH.'/../public/upload/'.$fileName;
    
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