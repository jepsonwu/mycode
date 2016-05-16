<?php
/**
 * 红包相关
 * User: hale <hale@duomai.com>
 * Date: 16-01-25
 * Time: 16:45
 */
class Api_BonusController extends Action_Api
{
	public function init()
	{
		parent::init();
        $this->isLoginOutput();
	}
    
    /**
     * 该方法上线的时候要删掉
     */
    public function testAction(){
        //发送消息
        /*$easeModel = new Model_IM_Easemob();
        $hxExt = array('Action'=>'bonus',
                           'CZSubAction'=>'grabBonus',
                           'BonusID'=>63,
                           'Sender'=>269,
                           'BonusAmount'=>100,
                           'MemberID'=>969);
        //$easeModel->yy_hxSend(array(269),"",'txt','users',$hxExt,969);
        $easeModel->tc_hxSend(array(269), 'user','cmd','users',$hxExt,969);
        echo 'ok';  */   
        
        $easeModel = new Model_IM_Easemob();
        $content = $this->_request->getParam('content','你收到一个红包');
        $username = $this->_request->getParam('username', '171963873551712732');
        $from_user = $this->_request->getParam('from_user', 'admin');
        $ext = array('CZUnsupportDesc'=>'你收到一个红包，请下载最新版本');
        $easeModel->yy_hxSend(array($username),$content,"txt","chatgroups",$ext,$from_user) ;
        echo 'ok';
    }
    
    /**
     * 发送红包
     */
    public function sendBonusAction(){
        try{
            $memberID = $this->memberInfo->MemberID;
            $sendType = (int)$this->_request->getParam('sendType', 1);
            $groupType = (int)$this->_request->getParam('groupType', 0);
            $groupID = $this->_request->getParam('groupID', 0);
            if($sendType!=1 && $sendType!=2){
                throw new Exception('红包发送方式参数错误');
            }
            if(empty($groupType) || empty($groupID)){
                throw new Exception('红包发送对象参数错误');
            }
            $bonusModel = new Model_Bonus();
            $ip = $this->_request->getClientIp();
            if($sendType==1){//发新红包
                $num = (int)$this->_request->getParam('num', 0);
                if(empty($num) || $num == 0){
                    throw new Exception('红包数量不能为0');
                }
                if($num > 100){
                    throw new Exception('红包数量不能超过100');
                }
                $amount = $this->_request->getParam('amount', 0);
                if(!preg_match("/^([1-9]{1}\d*(.(\d){1,2})?)|(0.(\d){1,2})$/",$amount)){
                   throw new Exception('请输入有效的金额');
                }
                if($amount<0.01){
                    throw new Exception('红包总额不能低于0.01元');
                }
                if($amount>200){
                    throw new Exception('红包总额不能超过200元');
                }
                if(($amount/$num)<0.01){
                    throw new Exception('单个红包金额不能低于0.01元');
                }
                $wishes = $this->_request->getParam('wishes', '');
                empty($wishes) && $wishes = "恭喜发财，大吉大利！";
                $bonusType = (int)$this->_request->getParam('bonusType', 0);
                if($bonusType!=Model_Bonus::BONUS_TYPE_ORDINARY && $bonusType!=Model_Bonus::BONUS_TYPE_LUCK){
                    throw new Exception('红包类型参数错误');
                }
                $payPassword = $this->_request->getParam('payPassword', '');
                $walletModel = new Model_Wallet_Wallet();
                $needCheckPwd = $walletModel->payValidation($memberID);//判断是否需要支付密码验证
                if($needCheckPwd){
                    $check = $walletModel->checkPayPasswordAction($memberID, $payPassword);
                    if($check['flag']<0){
                        $this->returnJson($check['flag'], null,new stdClass());
                    }
                }
                $sendResult = $bonusModel->sendNew($memberID, $groupType, $groupID, $bonusType, $num, $amount, $wishes, $ip);
            }elseif($sendType==2){//继续发送
                $bonusID = (int)$this->_request->getParam('bonusID', 0);
                $bonusInfo = $bonusModel->getBonusInfo($bonusID,array('BID','MemberID','SendTime','Status','BonusType','Wishes','BonusNum','ReceiveNum'));
                $expireTimeFrom = time()-86400;
                if(strtotime($bonusInfo['SendTime']) < $expireTimeFrom){
                	throw new Exception('该红包已过期');
                	}
                
                if(empty($bonusInfo) || $bonusInfo['MemberID']!=$memberID || $bonusInfo['Status']==0){
                    throw new Exception('该红包不存在');
                	}
                
                if($bonusInfo['ReceiveNum'] >= $bonusInfo['BonusNum']){
                	throw new Exception('红包已领完，不可被分享');
                	}
                
                $bonusType = $bonusInfo['BonusType'];
                $wishes = $bonusInfo['Wishes'];
                
                $sendResult = $bonusModel->sendAgain($memberID,$bonusID,$groupType,$groupID);
            }
            if($sendResult['code']>0){
                throw new Exception($sendResult['msg']);
            }
            //发送消息
            $easeModel = new Model_IM_Easemob();
            $hxExt = array(//'Action'=>'bonus',
                           'CZSubAction'=>'sendBonus',
                           'GroupType'=>$groupType,
                           'BonusType'=>(int)$bonusType,
                           'Sender'=>(string)$memberID,
                           'BonusID'=>(string)$sendResult['bonusID'],
                           'Wishes'=>$wishes,
                           'Subject'=>(string)$groupID,
                           'CZUnsupportDesc'=>'你收到一个红包，请下载最新版本');//自定义参数
            if($groupType==Model_Bonus::GROUP_TYPE_ONE){
                $easeModel->yy_hxSend(array($groupID),"你收到一个红包",'txt','users',$hxExt,$memberID);
            }else{
                $easeModel->yy_hxSend(array($groupID),"你收到一个红包",'txt','chatgroups',$hxExt,$memberID);
            }
            $this->returnJson(parent::STATUS_OK,null,array('ID' => $sendResult['bonusID']));
        }catch(Exception $e){
            $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
        }
    }
    
    /**
     * 红包记录列表
     */
    public function listAction(){
        $memberID = $this->memberInfo->MemberID;
		$year = (int)$this->_request->getParam('year', date('Y'));
        $type = (int)$this->_request->getParam('type', 1);
        $lastLogID = (int)$this->_request->getParam('lastLogID', 0);
        $pagesize = (int)$this->_request->getParam('pagesize', 10);
        $pagesize = min(100, max($pagesize, 1));
        
        $bonusModel = new Model_Bonus();
        if($type==1){//收到红包记录
            $result = $bonusModel->receiveBonusList($memberID,$year,$lastLogID,$pagesize);
        }elseif($type==2){//发出去的红包记录
            $result = $bonusModel->sendBonusList($memberID,$year,$lastLogID,$pagesize);
        }else{
            $this->returnJson(parent::STATUS_FAILURE, "类型参数错误");
        }
        $this->returnJson(parent::STATUS_OK, null, $result);
    }
    
    /**
     * 红包详情
     */
    public function detailAction(){
        $memberID = $this->memberInfo->MemberID;
		$bonusID = (int)$this->_request->getParam('bonusID', 0);
        if(empty($bonusID)){
            $this->returnJson(parent::STATUS_FAILURE, "参数错误");
        }
        
        $bonusModel = new Model_Bonus();
        $bonusInfo = $bonusModel->getBonusInfo($bonusID,array('MemberID', 'ID'=>'BID','Wishes','BonusNum','BonusAmount','ReceiveNum','ReceiveAmount','BonusType','GroupType','SendTime','Status'));
        if(empty($bonusInfo)){
            $this->returnJson(parent::STATUS_FAILURE, "红包不存在");
        }
        
        $expireTimeFrom = time() - 86400;
        
        if(strtotime($bonusInfo['SendTime']) < $expireTimeFrom){//过期
            $bonusInfo['Status'] = 2;
        }
        $notesModel = new Model_MemberNotes();
        $memberModel = new DM_Model_Account_Members();
        $memberInfo = $memberModel->getMemberInfoCache($bonusInfo['MemberID'],array('UserName','Avatar'));
        $bonusInfo['UserName'] = empty($memberInfo)?"":$memberInfo['UserName'];
        $bonusInfo['Avatar'] = empty($memberInfo)?"":$memberInfo['Avatar'];
        if($bonusInfo['MemberID']!=$memberID){
            $bonusInfo['Remark'] = $notesModel->getNoteName($memberID,$bonusInfo['MemberID']);
        }else{
            $bonusInfo['Remark'] = "";
        }
        $receiveList = $bonusModel->getReceiveList($memberID,$bonusID);
        $bonusInfo['ReceiveList'] = $receiveList;
        $this->returnJson(parent::STATUS_OK, null, $bonusInfo);
    }
    
    /**
     * 抢红包（停用，使用redis缓存的方法）
     */
    public function grabBackAction(){
        try{
            $memberID = $this->memberInfo->MemberID;
            $bonusID = (int)$this->_request->getParam('bonusID', 0);
            $bonusMemberID = (int)$this->_request->getParam('MemberID', 0);
            $groupID = $this->_request->getParam('GroupID', 0);
            if(empty($bonusID) || empty($bonusMemberID)){
                throw new Exception('参数错误');
            }
            $bonusModel = new Model_Bonus();
            $bonusInfo = $bonusModel->getBonusInfo($bonusID,array('MemberID', 'ID'=>'BID','Wishes','BonusNum','BonusAmount','ReceiveNum','ReceiveAmount','BonusType','GroupType','SendTime','Status'));
            if(empty($bonusInfo) || $bonusInfo['MemberID'] != $bonusMemberID){
                throw new Exception('红包不存在');
            }
            $groupType = $bonusInfo['GroupType'];//红包的群组属性
            $bonusNum = $bonusInfo['BonusNum'];
            
            $re = $bonusModel->getReceiveList($memberID, $bonusID, true);
            if(!empty($re)){
                $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OK,'Msg'=>'','IsFirst'=>0,'Amount'=>$re['Amount']));
            }
            if(strtotime($bonusInfo['SendTime'])<(time()-86400)){//过期
                $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_EXPIRE,'Msg'=>'未及时领取，红包已过期','IsFirst'=>0,'Amount'=>0));
            }
            $ip = $this->_request->getClientIp();
            //验证会员是否在红包的发送范围内
            $check = $bonusModel->checkPermission($memberID,$bonusID,$groupID);
            if(empty($check) || $check['code']>0){
                throw new Exception('无领取该红包的权限');
            }
            
            $fromGroupID = $groupID==0?$memberID:$groupID;
            $fromGroupType = $groupID==0?Model_Bonus::GROUP_TYPE_ONE:Model_Bonus::GROUP_TYPE_MANY;
            
            $grabResult = $bonusModel->grab($memberID,$bonusID,$bonusMemberID,$bonusNum,$groupType,$fromGroupType,$fromGroupID,$ip);
            if($grabResult['code']!=Model_Bonus::GRAB_STATUS_OK){
                if($grabResult['code']==0){
                    throw new Exception($grabResult['msg']);
                }else{
                    $this->returnJson(parent::STATUS_OK, null, array('Status'=>$grabResult['code'],'Msg'=>$grabResult['msg'],'IsFirst'=>0,'Amount'=>0));
                }
            }
            $this->returnJson(parent::STATUS_OK, null, $grabResult['Amount']);
        }catch(Exception $e){
            $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
        }
    }
    
    /**
     *   详情
     * @param int $memberID
     * @param int $bonusID
     * @return mixed
     */
    private function getBonusDetail($memberID,$bonusID)
    {
    	$bonusModel = new Model_Bonus();
    	$bonusInfo = $bonusModel->getBonusInfo($bonusID,array('MemberID', 'ID'=>'BID','Wishes','BonusNum','BonusAmount','ReceiveNum','ReceiveAmount','BonusType','GroupType','SendTime','Status'));
    	$expireTimeFrom = time() - 86400;
    	
    	if(strtotime($bonusInfo['SendTime']) < $expireTimeFrom){//过期
    		$bonusInfo['Status'] = 2;
    	}
    	
    	$notesModel = new Model_MemberNotes();
    	$memberModel = new DM_Model_Account_Members();
    	$memberInfo = $memberModel->getMemberInfoCache($bonusInfo['MemberID'],array('UserName','Avatar'));
    	$bonusInfo['UserName'] = empty($memberInfo)?"":$memberInfo['UserName'];
    	$bonusInfo['Avatar'] = empty($memberInfo)?"":$memberInfo['Avatar'];
    	if($bonusInfo['MemberID']!=$memberID){
    		$bonusInfo['Remark'] = $notesModel->getNoteName($memberID,$bonusInfo['MemberID']);
    	}else{
    		$bonusInfo['Remark'] = "";
    	}
    	$receiveListTmp = $bonusModel->getReceiveList($memberID,$bonusID);
    	$bonusInfo['ReceiveList'] = $receiveListTmp;
    	return $bonusInfo;
    } 
    
    /**
     * 增加了redis缓存过程抢红包
     */
    public function grabAction(){
        try{
            $memberID = $this->memberInfo->MemberID;
            $bonusID = (int)$this->_request->getParam('bonusID', 0);
            $bonusMemberID = (int)$this->_request->getParam('MemberID', 0);
            $groupID = $this->_request->getParam('GroupID', 0);
            
            $chatMemberID = $this->_request->getParam('ChatMemberID',0);
            
            if(empty($bonusID) || empty($bonusMemberID)){
                throw new Exception('参数错误');
            }
            $bonusModel = new Model_Bonus();
            $bonusInfo = $bonusModel->getBonusInfo($bonusID,array('MemberID', 'ID'=>'BID','Wishes','BonusNum','BonusAmount','ReceiveNum','ReceiveAmount','BonusType','GroupType','SendTime','Status'));
            if(empty($bonusInfo) || $bonusInfo['MemberID'] != $bonusMemberID){
                throw new Exception('红包不存在');
            }
            $bonusType = $bonusInfo['BonusType'];
            $groupType = $bonusInfo['GroupType'];
            $bonusNum = $bonusInfo['BonusNum'];
            
            $expireTimeFrom = time() - 86400;
            if($groupType==Model_Bonus::GROUP_TYPE_MANY){//只有群红包才用到缓存
                $redis = DM_Module_Redis::getInstance();
                $key = "Bonus:Member".$bonusMemberID.'-'.$bonusID;
                $bonusNumCache = $redis->hget($key, 'bonusNum');
                //查询已领取会员列表

                $receiveListCache = $redis->hget($key, 'receive');
                if($receiveListCache===false){
                    $receiveList = array();
                }else{
                    $receiveList = array_values((array)json_decode($receiveListCache));
                }

                if(in_array($memberID,$receiveList)){//在已领取列表里面
                    $re = $bonusModel->getReceiveList($memberID, $bonusID, true);
                    if(!empty($re)){
                        $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OK,'Msg'=>'','IsFirst'=>0,'Amount'=>$re['Amount'],'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                    }
                }
                if($bonusNumCache===false){//缓存中不存在，可能的原因：红包已过期
                    $re = $bonusModel->getReceiveList($memberID, $bonusID, true);
                    if(!empty($re)){
                        $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OK,'Msg'=>'','IsFirst'=>0,'Amount'=>$re['Amount'],'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                    }
                }elseif($bonusNumCache==0){
                    $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OVER,'Msg'=>'手慢了，没抢到','IsFirst'=>0,'Amount'=>0,'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                }
            }else{//个人红包直接领取
                $re = $bonusModel->getReceiveList($memberID, $bonusID, true);
                if(!empty($re)){
                    $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OK,'Msg'=>'','IsFirst'=>0,'Amount'=>$re['Amount'],'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                }
            }
            
            if(strtotime($bonusInfo['SendTime'])< $expireTimeFrom){//过期
                $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_EXPIRE,'Msg'=>'未及时领取，红包已过期','IsFirst'=>0,'Amount'=>0,'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
            }
            $ip = $this->_request->getClientIp();
            //验证会员是否在红包的发送范围内
            $check = $bonusModel->checkPermission($memberID,$bonusID,$groupID);
            if(empty($check) || $check['code']>0){
                throw new Exception('无领取该红包的权限');
            }
            $fromGroupID = $groupID==0?$memberID:$groupID;
            $fromGroupType = $groupID==0?Model_Bonus::GROUP_TYPE_ONE:Model_Bonus::GROUP_TYPE_MANY;
            
            if($groupType==Model_Bonus::GROUP_TYPE_MANY){//群红包
                //在执行领取前的最后一次验证，因为之前的验证和执行领取中间的间隔太长
                $bonusNumCache = $redis->hget($key, 'bonusNum');
                if($bonusNumCache==0){
                    //$this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OVER,'Msg'=>'手慢了，没抢到','IsFirst'=>0,'Amount'=>0,'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                }
                $receiveListCache = $redis->hget($key, 'receive');
                if($receiveListCache===false){
                    $receiveList = array();
                }else{
                    $receiveList = array_values((array)json_decode($receiveListCache));
                }

                //$redis->hIncrBy($key, 'bonusNum', -1);//更新红包库存缓存
            }
            
            //执行抢红包的动作
            $grabResult = $bonusModel->grab($memberID,$bonusID,$bonusMemberID,$bonusNum,$groupType,$fromGroupType,$fromGroupID,$chatMemberID,$ip);
            
            if($grabResult['code']!=Model_Bonus::GRAB_STATUS_OK){//领取失败
                if($groupType==Model_Bonus::GROUP_TYPE_MANY){//群红包
                    //$redis->hIncrBy($key, 'bonusNum', 1);//更新红包库存缓存
                }
                if($grabResult['code']==0){//数据操作异常
                    throw new Exception($grabResult['msg']);
                }else{
                    $this->returnJson(parent::STATUS_OK, null, array('Status'=>$grabResult['code'],'Msg'=>$grabResult['msg'],'IsFirst'=>0,'Amount'=>0,'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
                }
         }else {
         		if($groupType==Model_Bonus::GROUP_TYPE_MANY){
            		$redis->hIncrBy($key, 'bonusNum', -1);//更新红包库存缓存
         			}
            }
            
            if($groupType==Model_Bonus::GROUP_TYPE_MANY){//群红包
                $receiveListCache = $redis->hget($key, 'receive');
                if($receiveListCache===false){
                    $receiveList = array();
                }else{
                    $receiveList = array_values((array)json_decode($receiveListCache));
                }
                $receiveListNew = $receiveList;
                $receiveListNew[] = $memberID;
                $redis->hset($key, 'receive', json_encode((object)$receiveListNew));//更新领取列表的缓存
            }
            $IsOver = !empty($grabResult['IsOver']) ? $grabResult['IsOver'] : 0; 
            $this->returnJson(parent::STATUS_OK, null, array('Status'=>Model_Bonus::GRAB_STATUS_OK,'Msg'=>'','IsFirst'=>1,'Amount'=>$grabResult['Amount'],'IsOver'=>$IsOver,'Detail'=>$this->getBonusDetail($memberID, $bonusID)));
        }catch(Exception $e){
            $this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
        }
    }
}