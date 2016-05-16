<?php
/**
 *  活动页面
 * @author Jeff
 *
 */
class Api_ActivityController extends Action_Api
{
	public function init()
	{
		parent::init();
		header('Content-type: text/html');
	}
	
	/**
	 * 猜股票涨跌
	 */
	public function guessStockAction()
	{
		exit();
		Zend_Layout::startMvc()->disableLayout();
		$memberID = 0;
// 		if($this->isLogin()){
// 			$memberID = $this->memberInfo->MemberID;
// 		}
		$this->view->memberID = $memberID;
		echo $this->view->render('activity/index.phtml');
	}
	
	/**
	 * 注册页面
	 */
	public function registerAction()
	{
		exit();
		Zend_Layout::startMvc()->disableLayout();
		try{
			$guess = $this->_request->getParam('guess','');
			if(empty($guess)){
				throw new Exception('参数错误！');
			}
		}catch(Exception $e){
			exit($e->getMessage());
		}
		$this->view->guess = $guess;
		echo $this->view->render('activity/register.phtml');
	}
	
	/**
	 * 保存用户投票结果
	 */
	public function saveAction()
	{
		try{
			$guess = $this->_request->getParam('guess','');
			if(empty($guess)||!in_array($guess, array('up','down'))){
				throw new Exception('参数错误！');
			}
			$memberID = intval($this->_request->getParam('memberID',0));
			if($memberID){
				$memberID = $this->memberInfo->MemberID;
				$stockModel = new Model_StockGuess();
				$re = $stockModel->addInfo($memberID, $guess);
				if($re){
					 $this->returnJson(parent::STATUS_OK,'参与成功！');     
				}else{
					$this->returnJson(parent::STATUS_FAILURE,'您今天已参与过了！');
				}
			}
// 			}else{
// 				$this->_redirect('/api/activity/register?guess='.$guess,array('exit'=>true));
// 			}
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取中奖名单
	 */
	public function resultAction()
	{
		exit();
		Zend_Layout::startMvc()->disableLayout();
		try{
			$stockModel = new Model_StockGuess();
			$result = $stockModel->getWinList();
		}catch(Exception $e){
			exit($e->getMessage());
		}
		$this->view->result = $result;
		echo $this->view->render('activity/result.phtml');
	}
	
	
	/**
	 * 注册送流量首页
	 */
	public function flowAction()
	{
		exit();
		Zend_Layout::startMvc()->disableLayout();

		try{
			$flowModel = new Model_FlowActivity();
			$record = $flowModel->getRecord();
		}catch(Exception $e){
			exit($e->getMessage());
		}		
		$isStart = date('G',time())>=8?1:0;
		$time = strtotime('2015-09-23 08:00');
		if($time>time()){
			$isStart = 0;
		}
		$this->view->isStart = $isStart;
		$this->view->record = $record;
		echo $this->view->render('activity/flow0916/index.phtml');
	}
	
	/**
	 * 领取流量结果页
	 */
	public function flowRecordAction()
	{
		exit();
		Zend_Layout::startMvc()->disableLayout();
		try{
			$success = intval($this->_getParam('success',0));
			$mobile = trim($this->_getParam('mobile'));
			$memberID = 0;
			if($success && $this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
			}
			$flowModel = new Model_FlowActivity();
			$record = $flowModel->getRecord($memberID);
			$mobileStyle = $flowModel->getMobileStyle($mobile);
		}catch(Exception $e){
			exit($e->getMessage());
		}
		$this->view->record = $record;
		$this->view->success = $success;
		$this->view->mobileStyle = $mobileStyle;
		echo $this->view->render('activity/flow0916/get.phtml');
	}
	
	/**
	 * app内 送流量活动广告链接页面
	 */
	public function promotionAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		$Model = new Model_FlowNew();
		$records = $Model->getRocords();
		$this->view->record = $records;
		echo $this->view->render('activity/flow0916/cz-flow.phtml');
	}
	
	/**
	 * app内部点击领取
	 */
	public function receiveAction()
	{
		//Zend_Layout::startMvc()->disableLayout();
		try{
			$memberID = 0;
			if($this->isLogin()){
				$memberID = $this->memberInfo->MemberID;
				$mobile = $this->memberInfo->MobileNumber;
				$registerTime = $this->memberInfo->RegisterTime;
				$time = strtotime('2015-10-27');
				if(strtotime($registerTime)<$time){
					$this->returnJson(parent::STATUS_FAILURE,'该活动只针对新注册用户');
				}
			}else{
				$this->returnJson(parent::STATUS_FAILURE,'请先登录APP');
			}
			//判断该用户是否领取过流量
			$flowModel = new Model_FlowNew();
			$info = $flowModel->getInfo($memberID);
			if(!empty($info)){
				$this->returnJson(parent::STATUS_FAILURE,'您已领取过该流量');
			}
			$activityModel = new Model_FlowActivity();
			$mobileStyle = $activityModel->getMobileStyle($mobile);
			if($mobileStyle==2){
				$flowSize=20;
			}else{
				$flowSize=10;
			}
			$re = $flowModel->addInfo($memberID,$mobile,$flowSize);
			if(!$re){
				$this->returnJson(parent::STATUS_FAILURE,'领取流量失败');
			}
			$this->returnJson(parent::STATUS_OK,'领取流量成功',array('mobileStyle'=>$mobileStyle));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
// 		if($isValid ==1){
// 			echo $this->view->render('activity/flow0916/cz-eighty.phtml');
// 		}else{
// 			echo $this->view->render('activity/flow0916/cz-nomore.phtml');
// 		}
	}
	
	public function flowResultAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		$mobileStyle = intval($this->_getParam('mobileStyle',1));
		$Model = new Model_FlowNew();
		$records = $Model->getRocords();
		$this->view->record = $records;
		$this->view->mobileStyle = $mobileStyle;
		echo $this->view->render('activity/flow0916/get.phtml');
	}
	
	/**
	 * 获取是否有效用户
	 */
	public function getValidAction()
	{
		$mobile = $this->_getParam('mobile','');
		if(empty($mobile)){
			return $this->returnJson(parent::STATUS_FAILURE,'手机号不能为空');
		}
		$flowModel = new Model_FlowActivity();
		$info = $flowModel->getMobile($mobile);
		$isValid =0;
		if(!empty($info)){
			$isValid = 1;
		}
		$data = array('isValid'=>$isValid);
		return $this->returnJson(parent::STATUS_OK,'请求成功',$data);
	}
	
// 	public function testAction()
// 	{
// 		$model = new Model_FlowSDK();
// 		$a = $model->recharge(1,'11196','18268079326',2000981,'201509211732301119613');
// 		echo $a;exit;
	
// 	}
	public function testAction()
	{
		$memberID = 0;
		if($this->isLogin()){
			$memberID = $this->memberInfo->MemberID;
		}
		if($memberID>0){
			echo $memberID;
			echo $this->view->render('activity/index.phtml');
		}else{
			echo "获取不到Cookie";
		}
	
	}
	
	/**
	 * 供友钱调用上报idfa
	 */
	public function reportIdfaAction(){
		try{
			$partner_name = trim($this->_getParam('partner_name',''));
			$deviceInfo = trim($this->_getParam('idfa',''));
			if(empty($partner_name)||empty($deviceInfo)){
				echo json_encode(array('code'=>201,'msg'=>'partner_name或者idfa 参数错误！'));exit;
			}
			$mac = trim($this->_getParam('mac',''));
			if($partner_name == 'qumi'&& empty($mac))
			{
				echo json_encode(array('code'=>201,'msg'=>'mac参数不能为空！'));exit;
			}
// 			if($partner_name !="youqian"){
// 				echo json_encode(array('code'=>201,'msg'=>'参数错误！'));exit;
// 			}
			$model = new Model_PartnerIdfa();
			$re = $model->add($partner_name,$deviceInfo,$mac);
			echo json_encode(array('code'=>200,'msg'=>'上报成功！'));exit;
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}

	/*
	 *今日话题讨论页面
	 */
	public function topicVoteAction()
	{
		Zend_Layout::startMvc()->disableLayout();

		if($this->isLogin()){
			$memberID = $this->memberInfo->MemberID;
		}else{
			exit('登录后才能参加活动！');
		}

		$voteModel = new Model_Topic_TopicVote();
		$period = $this->_getParam('period',1);;
		$viewIDArr = $voteModel->getTopicVote($period);


		$viewModel = new Model_Topic_View();
		$viewInfo = $viewModel->getTopicVoteInfo($viewIDArr,$period,$memberID);
		$this->view->viewInfo = $viewInfo;
		$this->view->period = $period;
		echo $this->view->render('activity/topic-vote.phtml');
	}
	

	/*
	 *投票
	 */
	public function voteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		try{
			$viewID = intval($this->_request->getParam('id',0));
			$period = intval($this->_request->getParam('period',1));	
			if($viewID <=0 || $period <=0){
				throw new Exception('参数错误！');
			}
			$voteModel =new Model_Topic_TopicVote();
			$voteListModel = new Model_Topic_VoteList();
			$memberID = $this->memberInfo->MemberID;
			$count = $voteListModel->votedOneday($memberID);
			if($count < 10 ){
				switch (date("w")) {
					case '1':
						$startTime =strtotime("last Friday")+57600;
						$endTime = strtotime("Monday")+57600;
						break;
					case '2':
					case '3':
					case '4':
						$startTime =strtotime("last Friday")+57600;
						$endTime = strtotime("last Monday")+57600;
						break;
					case '5':
						$startTime =strtotime("Friday")+57600;
						$endTime = strtotime("next Monday")+57600;
						break;
					case '6':
					case '0':
						$startTime =strtotime("last Friday")+57600;
						$endTime = strtotime("next Monday")+57600;
						break;
				}
				if(time() >= $startTime && time() <=$endTime){
					$return = $voteModel->addVoteNum($viewID,$period,$memberID,1);
					if($return){
						$this->returnJson(parent::STATUS_OK,'参与成功！');
					}else{
						$this->returnJson(parent::STATUS_FAILURE,'投票失败！');
					}
				 }else{
				 	$this->returnJson(parent::STATUS_FAILURE,'不在活动时间内！');
				 }				
			}else{
				$this->returnJson(parent::STATUS_FAILURE,'一天只能投票10次！');

			}
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}


	/**
	 * 今日话题中奖通知
	 */
	public function voteNoticeWinAction()
	{
		$ip = "115.236.163.195";
		if($this->_request->getClientIp() == $ip){
			$model = new Model_Topic_TopicVote();
			$model->noticeWin();
		}

	}

	/**
	 * 今日话题中奖通知
	 */
	public function voteNoticeSelectedAction()
	{
		$ip = "115.236.163.195";
		if($this->_request->getClientIp() == $ip){
			$model = new Model_Topic_TopicVote();
			$model->noticeSelected();
		}
	}
	
	/**
     * 活动页面的视图加载
     */
    public function hdAction(){
        $this->_helper->viewRenderer->setNoRender();
        $ScriptPath = APPLICATION_PATH.'/../public/hd/';
        $this->view->setScriptPath($ScriptPath);
        $activityId = intval($this->_getParam('id',0));
        $templateId = intval($this->_getParam('templateId',0));
        $model_obj = new Model_ActivityTemplate();
        if($templateId>0){
            $TemplateInfo = $model_obj->getTemplateListById(0,$templateId);
            $activityConf['Path'] = $TemplateInfo[0]['Path'];
            $this->view->activityConf = $activityConf;
            echo $this->view->render($TemplateInfo[0]['Path'].'/index.html');die();
        }
        if($activityId<=0){
            echo "activity page not found!";die();
        }
        
        //验证活动ID是否有效
        $activityInfo = $model_obj->getActivityInfo($activityId);
        if(empty($activityInfo)){
            echo "activity non-existent!";die();
        }
        $activityConf = $model_obj->getActivityConf($activityId);
        if(!empty($activityConf)){
            $activityConf = (array)json_decode($activityConf);
            if(isset($activityConf['account'])){
                $memberId = isset($activityConf['memberId'])?$activityConf['memberId']:0;
                //获取账号信息
                $member_model = new DM_Model_Account_Members();
                $activityConf['Avatar'] = $member_model->getMemberAvatar($memberId);
            }
        }
        if($activityInfo['TemplateType']==3){//打卡系列
            //14号为第一堂课，以后每天增加一堂课，周末除外
            $curDate = date('Y-m-d 00:00:00');
            $w = date("w");
            if($w==0){
                $curDate = date("Y-m-d",  strtotime("+1 days"));
            }elseif($w==6){
                $curDate = date("Y-m-d",  strtotime("+2 days"));
            }
            $activityConf['month'] = (int)date("m",strtotime($curDate));
            $activityConf['day'] = date("d",strtotime($curDate));
            $activityConf['count'] = $this->getNum(date('Y-m-d 00:00:00'));
        }
        $templatePath = $activityInfo['Path'];
        $activityConf['Path'] = $templatePath;
        $activityConf['activityId'] = $activityId;
        $this->view->activityConf = $activityConf;
        echo $this->view->render($templatePath.'/index.html');
    }
    
    //获取课程数
    public function getNum($datetime){
        $num = 0;
        $startdate = strtotime("2015-12-14");
        $days = round((strtotime($datetime)-$startdate)/3600/24);
        $w = date("w",strtotime($datetime));
        if($w==0){
            $num = $this->getNum(date("Y-m-d",strtotime($datetime.' +1 days')));
        }elseif($w==6){
            $num = $this->getNum(date("Y-m-d",strtotime($datetime.' +2 days')));
        }else{
            $n = floor(($days+1)/7);
            $num = $days+1-$n*2;
        }
        if($datetime>='2016-01-01 00:00:00'){
            $num = $num-1;
        }
        return $num;
    }
    
    /**
     * 保存活动中用户提交的数据
     */
    public function submitDataAction(){
        $this->_helper->viewRenderer->setNoRender();
        $activityId = intval($this->_getParam('id',0));
        if($activityId<=0){
            $this->returnJson(parent::STATUS_FAILURE,'活动ID无效！');
        }
        $model_obj = new Model_ActivityTemplate();
        //验证活动ID是否有效
        $activityInfo = $model_obj->getActivityInfo($activityId);
        if(empty($activityInfo)){
            $this->returnJson(parent::STATUS_FAILURE,'活动已失效或不存在！');
        }
        $params = $_REQUEST;
        if(isset($params['_callback'])){
            unset($params['_callback']);
        }
        if($this->isLogin()){
            $memberID = $this->memberInfo->MemberID;//当前用户编号
        }else{
            $memberID = 0;//当前用户编号
        }
        
        if($activityInfo['TemplateType']==3){//课程打卡活动
            if($memberID == 0){
                //$this->returnJson(parent::STATUS_FAILURE,'先登录系统再来吧！');
                $this->isLoginOutput();die();
            }
            if(date('Y-m-d H:i:s')<'2015-12-14 00:00:00'){
                $this->returnJson(parent::STATUS_FAILURE,'课程开始后才能打卡，听完课再来吧！');
            }
            $w = date("w");
            if($w==0 || $w==6){
                $this->returnJson(parent::STATUS_FAILURE,'周末时间，您也休息下吧！');
            }
            $cur_hour = (int)date('H');
            if($cur_hour>=8 && $cur_hour<20){
                $this->returnJson(parent::STATUS_FAILURE,'课程开始后才能打卡，听完课再来吧！');
            }
            $checkSign = $model_obj->checkSignStatus($activityId,$activityInfo['TemplateType'],$memberID);
            if($checkSign['code']>0){//验证未通过
                $this->returnJson(parent::STATUS_FAILURE,$checkSign['msg']);
            }
            $params['sign'] = 1;
            $activityConf = $model_obj->getActivityConf($activityId);
            if(!empty($activityConf)){
                $activityConf = (array)json_decode($activityConf);
                $params['topicID'] = $activityConf['topicID'];
            }
        }
        $params['TemplateType'] = $activityInfo['TemplateType'];
        $params['add_time'] = date("Y-m-d H:i:s");
        $submit = $model_obj->submitActivityData($activityId,$memberID,$params);
        if($submit['code']>0){
            $this->returnJson(parent::STATUS_FAILURE,"提交数据失败，请稍后重试！");
        }
        $this->returnJson(parent::STATUS_OK,$submit['msg']);
    }


    /*
	 *	观点投票活动页面
	 */
	public function viewVoteAction()
	{
		Zend_Layout::startMvc()->disableLayout();
		$memberID=0;
		if($this->isLogin()){
			$memberID = $this->memberInfo->MemberID;
		}

		$votePeriodModel = new Model_VotePeriod();
		$voteViewModel = new Model_VoteViewList();
	    $voteListModel = new Model_Topic_VoteList();

		$periodInfo = $votePeriodModel->getCurrentPeriod();
		if(empty($periodInfo)){
			$this->returnJson(0,'活动暂未上线，敬请期待！');
		}
		$voteViewInfo = $voteViewModel->getVoteViewInfo($periodInfo['PeriodID'],1);
		$hotViewInfo = $voteViewModel->getVoteViewInfo($periodInfo['PeriodID'],2);
		
		$voteViewListIDArr = array_column($voteViewInfo,'VoteViewListID');		
		$isVoted = $voteListModel->isVoted($voteViewListIDArr,$memberID);

		$voteViewList = array();
		$hotViewList = array();
		$lastWinInfo = array();

		$viewModel = new Model_Topic_View();
		$topicModel = new Model_Topic_Topic();
		foreach ($voteViewInfo as $key=>$item) {
			$voteViewList[$key]['id'] = $item['VoteViewListID'];
			$viewInfo = $viewModel->getViewInfo($item['ViewID']);
			$voteViewList[$key]['name'] = !empty($viewInfo)?$viewInfo['ViewContent']:'';
			$voteViewList[$key]['voteCount'] = $item['VoteCount'];
		}

		foreach ($hotViewInfo as $key=>$item) {
			$viewInfo = $viewModel->getViewInfo($item['ViewID']);
			$hotViewList[$key]['name'] = !empty($viewInfo)?$viewInfo['ViewContent']:'';
			$hotViewList[$key]['id'] = $item['ViewID'];
		}

		$lastPeriodInfo = $votePeriodModel->getLastPeriodInfo($periodInfo['PeriodID']);
		if(!empty($lastPeriodInfo)){
			$lastWinInfo['Reward'] = $lastPeriodInfo['Reward'];
			$lastWinInfo['ViewID'] = $lastPeriodInfo['WinViewID'];
			if($lastPeriodInfo['WinViewID'] >0){
				$lastViewInfo = $viewModel->getViewInfo($lastPeriodInfo['WinViewID']);
				$topicInfo = $topicModel->getTopicInfo($lastViewInfo['TopicID'],null);
			}
			
			$lastWinInfo['ViewContent'] = !empty($lastViewInfo['ViewContent'])?$lastViewInfo['ViewContent']:'';
			$lastWinInfo['TopicName']=!empty($topicInfo['TopicName'])?$topicInfo['TopicName']:'';
			$memberModel = new DM_Model_Account_Members();
			if(isset($lastViewInfo) && $lastViewInfo['MemberID']>0){
				$userName =$memberModel->getMemberInfoCache($lastViewInfo['MemberID'],'UserName');
			}
			$lastWinInfo['UserName'] = !empty($userName)?$userName:'';
			 
		}
		
		$this->view->periodInfo = $periodInfo;
		$this->view->voteViewList = json_encode($voteViewList);
		$this->view->hotViewList = json_encode($hotViewList);
		$this->view->lastWinInfo = $lastWinInfo;
		$this->view->isVoted = $isVoted;
		$this->view->memberID = $memberID;
		echo $this->view->render('activity/view-vote.phtml');
	}


	public function saveVoteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$votePeriodModel = new Model_VotePeriod();
		$voteViewModel = new Model_VoteViewList();
		$voteListModel = new Model_Topic_VoteList();

		try{
			$periodInfo = $votePeriodModel->getCurrentPeriod();
			if(empty($periodInfo)){
				$this->returnJson(0,'活动暂未上线，敬请期待！');
			}

			$curDate = date('Y-m-d H:i:s',time());
			if($periodInfo['StartTime'] > $curDate ){
				$this->returnJson(0,'投票暂未开始，过会再来吧！');
			}

			if($periodInfo['EendTime']<$curDate){
				$this->returnJson(0,'投票已经结束，请下期再来！');
			}

			$infoID = intval($this->_request->getParam('id',0));
			if($infoID <1){
				$this->returnJson(0,'请选择要投票的观点！');
			}

			$voteViewInfo = $voteViewModel->getVoteViewInfo($periodInfo['PeriodID'],1);

			if(!empty($voteViewInfo)){
				$voteViewListIDArr = array_column($voteViewInfo,'VoteViewListID');
				if(!in_array($infoID, $voteViewListIDArr)){
					$this->returnJson(0,'您选择的观点不在投票列表中，请重新选择！');
				}
				$memberID = $this->memberInfo->MemberID;
				$isVoted = $voteListModel->isVoted($voteViewListIDArr,$memberID);
				if($isVoted >0){
					$this->returnJson(0,'每期活动一个用户只能投票一次，请下期再来！');
				}
				
				$return = $voteViewModel->addVoteNum($infoID,$memberID,1);
				if($return){
					$this->returnJson(1,'投票成功！');
				}else{
					$this->returnJson(0,'投票失败！');
				}
			}else{
				$this->returnJson(0,'暂时没有需要投票的观点！');
			}
			
		}catch(Exception $e){
			$this->returnJson(0,$e->getMessage());
		}
	}
}
