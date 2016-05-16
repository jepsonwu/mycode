<?php
/**
 * 红包相关的操作
 * User: hale <hale@duomai.com>
 * Date: 16-01-26
 * Time: 13:39
 */
class Model_Bonus extends Zend_Db_Table
{
    protected $_name = 'bonus';
	protected $_primary = 'BID';
    
    const GROUP_TYPE_ONE = 1;//红包群体属性，个人
    const GROUP_TYPE_MANY = 2;//红包群体属性，群组
    
    const BONUS_TYPE_ORDINARY = 1;//红包类型，普通红包
    const BONUS_TYPE_LUCK = 2;//红包类型，拼手气红包
    
    const GRAB_STATUS_OK = 1;//抢到红包
    const GRAB_STATUS_OVER = 2;//红包已抢完
    const GRAB_STATUS_EXPIRE = 3;//红包已过期

        /**
     * 获取红包的信息
     */
    public function getBonusInfo($id,$fields = null){
        $select = $this->select()->setIntegrityCheck(false);
		if (is_null($fields)){
			$select->from("bonus", array("MemberID", "BonusType", "SendTime", "BonusAmount", "BonusNum", "ReceiveNum",'ReceiveAmount','GroupType','GroupID','Wishes','Status','RelationID','ORID','PayType'));
        }else{
			$select->from("bonus", (array)$fields);
        }
		return $select->where("BID =?", $id)->query()->fetch();
    }
    
    /**
     * 发送红包（发新红包）
     * $groupType 红包群体属性（首次发送时的选择），1个人，2群
     * $groupID 接收方ID，群ID或会员ID
     * $num 红包个数
     * $amount 红包金额,普通红包表示单个红包的金额
     * $wishes 祝福语
     * $bonusType 红包类型，1普通，2拼手气
     */
    public function sendNew($memberId,$groupType,$groupID,$bonusType,$num,$amount,$wishes,$ip = ''){
        //判断groupID的有效性
        if($groupType==self::GROUP_TYPE_ONE){
            if($memberId==$groupID){
                return array('code'=>1,'msg'=>'不能给自己发红包');
            }
            $memberModel = new DM_Model_Account_Members();
            $re = $memberModel->getMemberInfoCache($groupID,array('UserName','Avatar'));
            $msg = '财猪账号不存在';
        }else{
            $groupModel = new Model_IM_GroupMember();
            $re = $groupModel->getInfo($memberId, $groupID);
            $msg = '群组不存在';
        }
        if(empty($re)){
            return array('code'=>1,'msg'=>$msg);
        }
        
        $presetAmountList = array();
        if($groupType==self::GROUP_TYPE_MANY){//拼手气红包才需要预生成列表
            if($bonusType==self::BONUS_TYPE_ORDINARY){
                $presetAmountList = array_fill(0,$num,$amount);
                $amount = $num*$amount;
            }else{
                $presetAmountList = $this->divideBonus($amount,$num);
            }
            if(empty($presetAmountList)){
                return array('code'=>1,'msg'=>'发送红包失败!');
            }
        }
        //生成订单记录
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $balance = $fundsModel->getMemberBalance($memberId,"CNY");
        if($balance<$amount){
            return array('code'=>1,'msg'=>'可用金额不足');
        }
        try{
            //开启事务
            $fundsModel->tBegin();
            $orderId = $fundsModel->createOrder($memberId,2,3,$amount,2,1,0,0,$ip,'CNY',0,"红包");
            if(!$orderId){
                throw new Exception('发送红包失败');
            }
            
            //修改金额
            $ret = $fundsModel->modifyAmount($memberId,'CNY',$amount,2,3,$ip,"红包",$orderId);
            if($ret == $fundsModel::OP_FAILURE){
                throw new Exception('发送红包失败');
            }
            
            $data = array(
                'MemberID'=>$memberId,
                'BonusType'=>$bonusType,
                'BonusAmount'=>$amount,
                'BonusNum'=>$num,
                'GroupType'=>$groupType,
                'GroupID'=>$groupID,
                'Wishes'=>$wishes,
                'Status'=>1,
                'RelationID'=>$orderId,
                'PayType'=>1
            );
            $bonusID = $this->insert($data);
            if(!$bonusID){
                throw new Exception('创建红包记录失败');
            }
            $bsid = $this->_db->insert('bonus_subject',array('BID'=>$bonusID,'GroupType'=>$groupType,'GroupID'=>$groupID));
            if(!$bsid){
                throw new Exception('创建红包记录失败!');
            }
            if(!empty($presetAmountList)){
                /*foreach($presetAmountList as $r){
                    $presetId = $this->_db->insert('bonus_detail_preset',array('BID'=>$bonusID,'Amount'=>$r));
                    if(!$presetId){
                        throw new Exception('创建红包记录失败!');
                    }
                    $redisVal[] = $presetId;
                }*/
                $insertSql = "insert into bonus_detail_preset (BID,Amount) values ";
                foreach($presetAmountList as $r){
                    $insertSql .= "({$bonusID},{$r}),";
                }
                $insertSql = trim($insertSql,',');
                $insertRes = $this->_db->query($insertSql);
                if(!$insertRes){
                    throw new Exception('创建红包记录失败!');
                }
                
                //写入redis
                $redis = DM_Module_Redis::getInstance();
                $key = "Bonus:Member".$memberId.'-'.$bonusID;
                $redis->hset($key, 'bonusNum', $num);//用于存放未领取的红包总数
                $redis->expire($key,90000);
            }
            
            //修改统计信息
            $this->bonusStat($memberId, $amount,2);
            
            //发送一条消息
            
            //钱包消息
            $messageModel = new Model_Message();
            $messageModel->addMessage($memberId, Model_Message::MESSAGE_TYPE_PAY,$orderId,Model_Message::MESSAGE_SIGN_WALLET);
            
            $fundsModel->tCommit();
            return array('code'=>0,'bonusID'=>$bonusID);
        }catch(Exception $e){
            $fundsModel->tRollBack();
            return array('code'=>1,'msg'=>$e->getMessage());
        }  
    }
    
    /**
     * 发送红包（继续发送）
     * $groupType 红包群体属性（首次发送时的选择），1个人，2群
     * $groupID 接收方ID，群ID或会员ID
     * $bonusID 红包ID
     */
    public function sendAgain($memberId,$bonusID,$groupType,$groupID){
        //判断groupID的有效性
        if($groupType==self::GROUP_TYPE_ONE){
            $memberModel = new DM_Model_Account_Members();
            $re = $memberModel->getMemberInfoCache($groupID,array('UserName','Avatar'));
            $msg = '财猪账号不存在';
        }else{
            $groupModel = new Model_IM_GroupMember();
            $re = $groupModel->getInfo($memberId, $groupID);
            $msg = '群组不存在';
        }
        if(empty($re)){
            return array('code'=>1,'msg'=>$msg);
        }
        $select = $this->select()->setIntegrityCheck(false);
		$res = $select->from("bonus_subject", array("BSID"))->where("BID =?", $bonusID)->where("GroupType=?",$groupType)->where("GroupID=?",$groupID)->query()->fetch();
        if(empty($res)){//不存在对应关系
            $bsid = $this->_db->insert('bonus_subject',array('BID'=>$bonusID,'GroupType'=>$groupType,'GroupID'=>$groupID));
            if(!$bsid){
                return array('code'=>1,'msg'=>"继续发送红包失败");
            }
        }
        return array('code'=>0,'bonusID'=>$bonusID);
    }
    
    /**
     * 发出去的红包列表
     */
    public function sendBonusList($memberID,$year,$lastLogID,$pagesize){
        $data = array();
		$statInfo = $this->select()->setIntegrityCheck(false)->from("bonus_member_stat", array('Amount' => 'sum(SendAmount)', 'BonusNum' => 'sum(SendNum)'))->where("MemberID=?", $memberID)->where('Year=?',$year)->query()->fetch();
		$data['Amount'] = isset($statInfo['Amount'])?$statInfo['Amount']:0.00;
        $data['BonusNum'] = isset($statInfo['BonusNum'])?$statInfo['BonusNum']:0;
        
        $select = $this->select()->setIntegrityCheck(false);
        $sdate = $year.'-01-01 00:00:00';
        $edate = ($year+1).'-01-01 00:00:00';
        $select->from("bonus", array('Amount'=>'BonusAmount', 'BonusID'=>'BID','BonusType','Time'=>'SendTime','GroupType','BonusNum','ReceiveNum','Status'))->where("MemberID=?", $memberID)->where('SendTime>=?',$sdate)->where('SendTime<?',$edate);
        if ($lastLogID > 0) {
			$select->where('BID < ? ', $lastLogID);
		}
        $list = array();
        $res = $select->order('BID desc')->limit($pagesize)->query()->fetchAll();
        
        foreach ($res as $r){
            $r['LogID'] = $r['BonusID'];
            if(strtotime($r['Time'])<(time()-86400)){
                $r['Status'] = 2;
            }
            $list[] = $r;
        }
        $data['Logs'] = $list;
		return $data;
    }
    
    /**
     * 收到的红包列表
     */
    public function receiveBonusList($memberID,$year,$lastLogID,$pagesize){
        $data = array();
		$statInfo = $this->select()->setIntegrityCheck(false)->from("bonus_member_stat", array('Amount' => 'sum(BonusAmount)', 'BonusNum' => 'sum(BonusNum)', 'BestNum'=>'sum(BestNum)'))->where("MemberID=?", $memberID)->where('Year=?',$year)->query()->fetch();
		$data['Amount'] = isset($statInfo['Amount'])?$statInfo['Amount']:0.00;
        $data['BonusNum'] = isset($statInfo['BonusNum'])?$statInfo['BonusNum']:0;
        $data['BestNum'] = isset($statInfo['BestNum'])?$statInfo['BestNum']:0;
        
        $select = $this->select()->setIntegrityCheck(false);
        $sdate = $year.'-01-01 00:00:00';
        $edate = ($year+1).'-01-01 00:00:00';
        $select->from("bonus_detail_receive", array('Amount', 'Time' => 'ReceiveTime','IsBest','LogID'=>'DRID','BonusID'=>'BID'))->where("MemberID=?", $memberID)->where('ReceiveTime>=?',$sdate)->where('ReceiveTime<?',$edate);
        if ($lastLogID > 0) {
			$select->where('DRID < ? ', $lastLogID);
		}
        $list = array();
        $res = $select->order('DRID desc')->limit($pagesize)->query()->fetchAll();
        
        $notesModel = new Model_MemberNotes();
        $memberModel = new DM_Model_Account_Members();
        foreach ($res as $r){
            $bonusInfo = $this->getBonusInfo($r['BonusID'],array('BonusType','MemberID'));
            if(empty($bonusInfo)){
                continue;
            }
            $r['MemberID'] = $bonusInfo['MemberID'];
            $r['BonusType'] = $bonusInfo['BonusType'];
            $r['Remark'] = $notesModel->getNoteName($memberID,$r['MemberID']);
            $memberInfo = $memberModel->getMemberInfoCache($r['MemberID'],array('UserName','Avatar'));
            $r['UserName'] = empty($memberInfo)?"":$memberInfo['UserName'];
            $r['Avatar'] = empty($memberInfo)?"":$memberInfo['Avatar'];
            $list[] = $r;
        }
        $data['Logs'] = $list;
		return $data;
    }
    
    /**
     * 获取红包的兑换列表
     * $bonusID 红包ID
     * $isMy 是否是查询自己的记录
     */
    public function getReceiveList($memberID,$bonusID,$isMy = false){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from("bonus_detail_receive", array('Amount', 'Time' => 'ReceiveTime','IsBest','GroupType','GroupID','MemberID'))->where("BID=?", $bonusID);
        if($isMy){
            return $select->where("MemberID=?", $memberID)->query()->fetch();
        }
        $res = $select->order("DRID desc")->query()->fetchAll();
        $list = array();
        $notesModel = new Model_MemberNotes();
        $memberModel = new DM_Model_Account_Members();
        foreach ($res as $r){
            if($memberID!=0){
                $r['Remark'] = $notesModel->getNoteName($memberID,$r['MemberID']);
                $memberInfo = $memberModel->getMemberInfoCache($r['MemberID'],array('UserName','Avatar'));
                $r['UserName'] = empty($memberInfo)?"":$memberInfo['UserName'];
                $r['Avatar'] = empty($memberInfo)?"":$memberInfo['Avatar'];
            }
            $list[] = $r;
        }
		return $list;
    }
    
    /**
     * 验证会员是否在红包的发送范围内
     */
    public function checkPermission($memberID,$bonusID,$groupID=0){
        if($groupID==0){
            $re = $this->select()->setIntegrityCheck(false)->from("bonus_subject", array('GroupType','GroupID'))->where("BID=?", $bonusID)->where('Status=1')->where('GroupType='.self::GROUP_TYPE_ONE)->where('GroupID=?',$memberID)->query()->fetch();
        }else{
            $groupModel = new Model_IM_GroupMember();
            $res = $groupModel->getInfo($memberID, $groupID);
            if(empty($res)){
                return array('code'=>1);
            }
            $re = $this->select()->setIntegrityCheck(false)->from("bonus_subject", array('GroupType','GroupID'))->where("BID=?", $bonusID)->where('Status=1')->where('GroupType='.self::GROUP_TYPE_MANY)->where('GroupID=?',$groupID)->query()->fetch();
        }
        if(empty($re)){
        		$bonusInfo = $this->getBonusInfo($bonusID,array('GroupType','MemberID'));
            if(!($bonusInfo['GroupType'] == 2 && $bonusInfo['MemberID'] == $memberID)){//群红包转发后，发送者自己也可以抢
        			return array('code'=>1);
            	}
        }
        return array('code'=>0,'GroupID'=>$re['GroupID'],'GroupType'=>$re['GroupType']);
    }
    
    /**
     * 执行抢红包操作
     * $memberID 抢红包的会员编号
     * $bonusID 红包编号
     * $bonusNum 红包属性-红包的总个数
     * $groupType 红包属性-群组类型
     * $fromGroupType 抢红包的会员来源类型，1个人，2群组
     * $fromGroupID 抢红包的会员来源，根据$fromGroupType表示不同含义，会员编号或者群组ID
     * 
     * return code:0数据处理异常，其他对应定义的常量
     */
    public function grab($memberID,$bonusID,$bonusMemberID,$bonusNum,$groupType,$fromGroupType,$fromGroupID,$chatMemberID,$ip = ''){
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $fundsModel->tBegin();
        try{
            if($groupType==self::GROUP_TYPE_MANY){//群红包
                $sql = "select Amount,DPID from bonus_detail_preset where BID={$bonusID} and Status=0 for update";
                $res = $this->_db->fetchRow($sql);
                if(empty($res)){
                    return array('code'=>  self::GRAB_STATUS_OVER,'msg'=>'手慢了，没抢到');
                }
                $dpid = $res['DPID'];
                //将预分配的红包修改未领取状态
                $updateRes = $this->_db->update('bonus_detail_preset',array('Status'=>1),array('DPID=?'=>$dpid));
                if(!$updateRes){
                    throw new Exception('处理失败，请稍后再试');
                }
            }else{//个人红包
                $res = $this->getBonusInfo($bonusID,array('Amount'=>'BonusAmount'));
            }
            $amount = $res['Amount'];
            
            //查询是否已领取
            $historyTmp = $this->_db->select()->from('bonus_detail_receive','BID')->where('BID = ?',$bonusID)->where('MemberID = ?',$memberID)->forUpdate()->query()->fetch();
            if(!empty($historyTmp)){
            	throw new Exception('已领取过了，不能重复领取');
            }
            
            //创建订单
            $orderType = 3;//财猪红包
            $remark = '红包';
            $currency = 'CNY';
            $orderID = $fundsModel->createOrder($memberID,1,$orderType,$amount,2,2,$bonusID,0,$ip,$currency,0,$remark);
            if(!$orderID){
                throw new Exception('处理失败，请稍后再试');
            }
            //修改金额
            $ret = $fundsModel->modifyAmount($memberID,$currency,$amount,1,$orderType,$ip,$remark,$orderID);
            if($ret == $fundsModel::OP_FAILURE){
                throw new Exception('处理失败，请稍后再试');
            }
            
//             //判断是否是最佳
//             $bestMemberID = 0;//手气最佳会员ID
//             $sql = "SELECT COUNT(*) AS total,IFNULL(MAX(Amount),0) as maxAmount FROM bonus_detail_receive WHERE BID={$bonusID}";
//             $res_1 = $this->_db->fetchRow($sql);
//             if(empty($res_1) || ($res_1['total']+1)<$bonusNum){//未抽完
//                 $isBest = 0;
//             }else{//抽完
//                 $isOver = 1;
//                 if($amount<=$res_1['maxAmount']){//小于最大金额，次数需要查出手气最佳的会员
//                     $isBest = 0;
//                     $bestMember = $this->select()->setIntegrityCheck(false)->from("bonus_detail_receive", array('MemberID'))->where('BID=?',$bonusID)->order('Amount desc')->order('ReceiveTime desc')->limit(1)->query()->fetch();
//                     $bestMemberID = empty($bestMember)?0:$bestMember['MemberID'];
//                 }else{
//                     $isBest = $groupType==self::GROUP_TYPE_MANY?1:0;
//                 }
//             }
            
            
            //插入数据
            $receiveTime = date('Y-m-d H:i:s');
            $result = $this->_db->insert('bonus_detail_receive',array('BID'=>$bonusID,'MemberID'=>$memberID,'ReceiveTime'=>$receiveTime,'Amount'=>$amount,'GroupType'=>$fromGroupType,'GroupID'=>$fromGroupID,'RelationID'=>$orderID));
            if(!$result){
                throw new Exception('处理失败，请稍后再试');
            }
            
            //手气最佳
            $isOver = 0;
            $bestMemberID = 0;//手气最佳会员ID
            $isBest = 0;
            if($groupType == self::GROUP_TYPE_MANY){
            	$sql = "SELECT COUNT(*) AS total FROM bonus_detail_receive WHERE BID={$bonusID}";
            	$totalNow = $this->_db->fetchOne($sql);
            	if($totalNow >= $bonusNum){
            		$isOver = 1;
            		$bestMember = $this->select()->setIntegrityCheck(false)->from("bonus_detail_receive", array('MemberID','DRID'))->where('BID=?',$bonusID)->order('Amount desc')->order('DRID asc')->limit(1)->query()->fetch();
            		$isBest = $bestMember['MemberID'] == $memberID ? 1 : 0;
            		$this->_db->update('bonus_detail_receive', array('IsBest'=>1),array('DRID = ?'=>$bestMember['DRID']));
            		$bestMemberID = $bestMember['MemberID'];
            		}
            }else{
            	$isOver = 1;
            	}
            	
				//插入消息
            $messageModel = new Model_Message();
            $messageModel->addMessage($memberID, Model_Message::MESSAGE_TYPE_INCOME,$orderID,Model_Message::MESSAGE_SIGN_WALLET);
            
            //修改统计数据
            if($isOver){
                $res_2 = $this->update(array('ReceiveNum'=>new Zend_Db_Expr('ReceiveNum + 1'),'ReceiveAmount'=>new Zend_Db_Expr('ReceiveAmount + '.$amount),'ReceiveTime'=>new Zend_Db_Expr(strtotime($receiveTime).'-UNIX_TIMESTAMP(SendTime)')), array('BID=?'=>$bonusID));
            }else{
                $res_2 = $this->update(array('ReceiveNum'=>new Zend_Db_Expr('ReceiveNum + 1'),'ReceiveAmount'=>new Zend_Db_Expr('ReceiveAmount + '.$amount)), array('BID=?'=>$bonusID));
            }
            if(!$res_2){
                throw new Exception('处理失败，请稍后再试');
            }
            
            $this->bonusStat($memberID,$amount,1,$isBest,$bestMemberID);
            
            $bonusInfoTmp = $this->getBonusInfo($bonusID,array('BonusType'));
            //发送环信透传消息
            $easeModel = new Model_IM_Easemob();
            $hxExt = array(//'Action'=>'bonus',
                           'CZSubAction'=>'grabBonus',
                           'BonusID'=>(string)$bonusID,
                           'Sender'=>(string)$bonusMemberID,
                           'BonusAmount'=>$amount,
                           'MemberID'=>(string)$memberID,
            		         'BonusType'=>(int)$bonusInfoTmp['BonusType'],
            					'ChatMemberID'=>$chatMemberID,
                           'IsOver'=>$isOver);//自定义参数
            
            $isNeedNotice = 1;
            if($fromGroupType==self::GROUP_TYPE_ONE){
                //$easeModel->yy_hxSend(array($bonusMemberID),"红包消息",'txt','users',$hxExt);
                //$easeModel->tc_hxSend(array($bonusMemberID), 'user','cmd','users',$hxExt);
                $fromGroupID = 0;
            }else{
                //$easeModel->yy_hxSend(array($fromGroupID),"",'txt','chatgroups',$hxExt);
                //$easeModel->tc_hxSend(array($fromGroupID), 'group','cmd','chatgroups',$hxExt);
            	$groupModel = new Model_IM_GroupMember();
            	$groupMemberInfo = $groupModel->getInfo($bonusMemberID, $fromGroupID);
            	if(empty($groupMemberInfo)){
            		$isNeedNotice = 0;
            	}
            }
         $hxExt['GroupID'] = $fromGroupID;
         
         if($isNeedNotice){
         	$easeModel->yy_hxSend(array($bonusMemberID),"红包消息",'txt','users',$hxExt);
         	}
         	
            //提交
            $fundsModel->tCommit();
            return array('code'=>  self::GRAB_STATUS_OK,'Amount'=>$amount,'IsOver'=>$isOver);
        }catch(Exception $e){
            $fundsModel->tRollBack();
            return array('code'=>0,'msg'=>$e->getMessage());
        }
    }
    
    /**
     * 修改会员抢红包的统计信息
     * $type 类型，1抢红包，2发红包
     * $amount 金额，发出去的金额或者抢到的金额
     * $isBest 是否是手气最佳，1最佳，0不是
     * $bestMemberID 手气最佳的会员ID
     */
    public function bonusStat($memberID,$amount,$type = 1,$isBest = 0,$bestMemberID = 0){
        $year = date('Y');
        $month = date('m');
        $re = $this->select()->setIntegrityCheck(false)->from("bonus_member_stat", array('SID'))->where('MemberID=?',$memberID)->where("Year=?", $year)->where('Month=?',$month)->query()->fetch();
        if(empty($re)){
            if($type==1){//抢红包
                $result = $this->_db->insert('bonus_member_stat',array('Year'=>$year,'Month'=>$month,'MemberID'=>$memberID,'BonusNum'=>1,'BestNum'=>$isBest,'BonusAmount'=>$amount));
            }else{//发红包
                $result = $this->_db->insert('bonus_member_stat',array('Year'=>$year,'Month'=>$month,'MemberID'=>$memberID,'SendNum'=>1,'SendAmount'=>$amount));
            }
            if(!$result){
                return false;
            }
        }else{
            if($type==1){//抢红包
                if($isBest){
                    $updateRet = $this->_db->update('bonus_member_stat',array('BonusNum'=>new Zend_Db_Expr('BonusNum + 1'),'BestNum'=>new Zend_Db_Expr('BestNum + 1'),'BonusAmount'=>new Zend_Db_Expr('BonusAmount + '.$amount)),array('SID=?'=>$re['SID']));
                }else{
                    $updateRet = $this->_db->update('bonus_member_stat',array('BonusNum'=>new Zend_Db_Expr('BonusNum + 1'),'BonusAmount'=>new Zend_Db_Expr('BonusAmount + '.$amount)),array('SID=?'=>$re['SID']));
                }
            }else{//发红包
                $updateRet = $this->_db->update('bonus_member_stat',array('SendNum'=>new Zend_Db_Expr('SendNum + 1'),'SendAmount'=>new Zend_Db_Expr('SendAmount + '.$amount)),array('SID=?'=>$re['SID']));
            }
            if(!$updateRet){
                return false;
            }
        }
        if($bestMemberID>0 && $bestMemberID != $memberID){
            $re = $this->select()->setIntegrityCheck(false)->from("bonus_member_stat", array('SID'))->where('MemberID=?',$bestMemberID)->order('SID desc')->limit(1)->query()->fetch();
            $SID = empty($re)?0:$re['SID'];
            //$updateRet = $this->_db->update('bonus_member_stat',array('BonusNum'=>new Zend_Db_Expr('BonusNum + 1'),'BestNum'=>new Zend_Db_Expr('BestNum + 1')),array('SID=?'=>$SID));
            $updateRet = $this->_db->update('bonus_member_stat',array('BestNum'=>new Zend_Db_Expr('BestNum + 1')),array('SID=?'=>$SID));
            if(!$updateRet){
                return false;
            }
        }
        return true;
    }

    /**
     * 生成红包的随机金额
     * 随机的金额在 minAmount-剩余平均数*2  之间
     */
    public function divideBonus($amount,$num){
        $presetAmountList = array();
        $unit = 0.01;//每份的最小单位
        //最小单位的5000倍为50元，而最高是200元，所以这个最小份数就根据总金额对50的倍数来确定，保证最大最小不会相差5000倍
        $min = ceil($amount/50);
        $minAmount = $min*$unit;//最小金额
        $total = $amount/$unit;//总份数
        for($i=$num;$i>0;$i--){
            if($i==1){
                $randAmount = $total*$unit;//最后一份把剩余的金额都分配出去
            }else{
                $average = number_format($amount/$i*2,2,'.','');
                $maxAmount = $average>$minAmount?($average-$minAmount):$minAmount;//确定随机数的最大金额
                $max = $maxAmount/$unit;//确定随机数的最大份数
                $rand = rand($min,$max);
                $randAmount = $rand*$unit;
                $amount -= $randAmount;
                $total -= $rand;
            }
            $presetAmountList[] = $randAmount;
        }
        return $presetAmountList;
    }
    
    /**
     * 过期红包退款
     * 每五分钟执行一次
     */
    public function bonusBack(){
    	//$expireTimeFrom = date('Y-m-d H:i:s',strtotime('-24 hours'));
    	$expireTimeFrom = date('Y-m-d H:i:s',time() - 86400);
        $expiredBonus = $this->select()->from("bonus", array('BID','MemberID','BackAmount','BonusAmount','ReceiveAmount','GroupType'))->where('Status=1')->where('SendTime<?',$expireTimeFrom)->query()->fetchAll();
        if(empty($expiredBonus)){
            return true;
        }
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $messageModel = new Model_Message();
        foreach($expiredBonus as $row){
            if($row['BackAmount']>0){
                continue;
            }
            $bonusAmount = $row['BonusAmount'];
            $receiveAmount = $row['ReceiveAmount'];
            $receiveRet = $this->select()->setIntegrityCheck(false)->from("bonus_detail_receive", array('amount'=>'sum(Amount)'))->where('BID=?',$row['BID'])->query()->fetch();
            $receiveMoney = empty($receiveRet)?0:$receiveRet['amount'];
            if(intval($receiveAmount * 100) != intval($receiveMoney * 100)){
                continue;
            }
            if($receiveAmount>=$bonusAmount){
                continue;
            }
            $backAmount = $bonusAmount-$receiveAmount;
            $fundsModel->tBegin();
            //创建订单
            $orderType = 3;//财猪红包
            $currency = 'CNY';
            $backRemark = $backAmount < $bonusAmount ? "红包部分退款":'红包全部退款';
            $row['GroupType'] == 1 && $backRemark = '退款';
            $backOrderID = $fundsModel->createOrder($row['MemberID'],1,20,$backAmount,2,2,$row['BID'],0,'',$currency,0,$backRemark,$orderType);
            if(!$backOrderID){
                $fundsModel->tRollBack();
                continue;
            }
            $ret = $fundsModel->modifyAmount($row['MemberID'],$currency, $backAmount,1,20, '', "财猪红包退款", $row['BID']);
            if($ret==$fundsModel::OP_FAILURE){
                $fundsModel->tRollBack();
                continue;
            }
            $this->update(array('Status'=>2,'BackAmount'=>$backAmount),array('BID = ?'=>$row['BID']));
            $messageModel->addMessage($row['MemberID'],Model_Message::MESSAGE_TYPE_BACK,$backOrderID,Model_Message::MESSAGE_SIGN_WALLET);
            $fundsModel->tCommit();
        }
        return true;
    }
    
    /**
     * 清理红包预分配表中的无效数据
     * 每天执行一次
     */
    public function clearPreset(){
        $expiredBonus = $this->select()->from("bonus", array('BID'))->where('SendTime<?',date('Y-m-d H:i:s',strtotime('-36 hours')))->query()->fetchAll();
        if(empty($expiredBonus)){
            return true;
        }
        foreach($expiredBonus as $row){
            $this->_db->delete("bonus_detail_preset","BID=".$row['BID']);
        }
        return true;
    }
    
    /**********************红包后台管理相关方法*********************/
    
    /**
     * 获取红包发送列表
     */
    public function sendList($memberId,$status = 1,$bonusType = 0,$groupType = 0,$start_date = '',$end_date = '',$start_amount = 0,$end_amount = 0,$pageIndex = 1,$pageSize = 20,&$total = 0){
        $select = $this->select()->setIntegrityCheck(false);
		$select->from('bonus', array('BID','MemberID','SendTime','BonusAmount','BonusNum','ReceiveAmount','ReceiveNum','BackAmount','BonusType','GroupType'));
        if(!empty($memberId)){
            $select->where('MemberID=?',$memberId);
        }
        $bonusType && $select->where('bonusType=?',$bonusType);
        $groupType && $select->where('groupType=?',$groupType);

        $sendTime = date('Y-m-d H:i:s',strtotime('- 1 day'));
        
        if($status == 1){
            if(!empty($start_date)){
                $start_date = date('Y-m-d 00:00:00',strtotime($start_date));
                $start_date>$sendTime && $sendTime = $start_date;
            }
            $select->where('SendTime >= ?',$sendTime);
            if(!empty($end_date)){
                $select->where('SendTime <= ?',date('Y-m-d 23:59:59',strtotime($end_date)));
            }
        }elseif($status == 2){
            if(!empty($start_date)){
                $select->where('SendTime >= ?',date('Y-m-d 00:00:00',strtotime($start_date)));
            }
            if(!empty($end_date)){
                $end_date = date('Y-m-d 23:59:59',strtotime($end_date));
                $end_date<$sendTime && $sendTime = $end_date;
            }
            $select->where('SendTime <= ?',$sendTime);
        }else{
            if(!empty($start_date)){
                $select->where('SendTime >= ?',date('Y-m-d 00:00:00',strtotime($start_date)));
            }
            if(!empty($end_date)){
                $select->where('SendTime <= ?',date('Y-m-d 23:59:59',strtotime($end_date)));
            }
        }

        if($start_amount>=0){
            $select->where('BonusAmount >= ? ',$start_amount);
        }

        if($end_amount>=0){
            $select->where('BonusAmount <= ?',$end_amount);
        }
        //die($select->__toString());
		//获取sql
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
        //总条数
        $total = $select->getAdapter()->fetchOne($countSql);
        $list = array();
        $res = $select->order("BID desc")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
        foreach($res as $row){
            $re = $this->select()->setIntegrityCheck(false)->from("bonus_subject", array('GroupType', 'GroupID'))->where("BID=?", $row['BID'])->where('Status=1')->query()->fetchAll();
            $row['groupList'] = $re;
            $row['Status'] = $row['SendTime']<$sendTime? 2:1;
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * 红包功能的概况信息
     */
    public function survey($memberId,$unit = 1,$start_date = '',$end_date = '',$pageIndex = 1,$pageSize = 20,&$total = 0){
        if($unit==1){//按天
            $total = (strtotime($end_date)-strtotime($start_date))/86400;
            $start = $pageIndex==0?$start_date:date('Y-m-d',strtotime($start_date ." + ".($pageIndex-1)*$pageSize.' days'));
            $end = $pageIndex==0?$end_date:date('Y-m-d',strtotime($start ." + ".($pageSize-1).' days'));
        }else{//按月
            $end_date_arr = explode('-',$end_date);
            $start_date_arr = explode('-',$start_date);
            $total = abs($end_date_arr[0] - $start_date_arr[0]) * 12 + abs($end_date_arr[1] - $start_date_arr[1]);
            $start_date = date('Y-m-01',strtotime($start_date));
            $end_date = date('Y-m-01',strtotime($end_date));
            $start = $pageIndex==0?$start_date:date('Y-m-d',strtotime( $start_date." + ".($pageIndex-1)*$pageSize.' months'));
            $end = $pageIndex==0?$end_date:date('Y-m-d',strtotime($start ." + ".($pageSize-1).' months'));
        }
        $end>$end_date && $end = $end_date;
        $list = array();
        $memberStr = $memberId>0?"MemberID={$memberId} and ":'';
        while ($start<=$end){
            $row = array('ReceiveAmount'=>0.00,'ReceiveNum'=>0,'SendBonusNum'=>0,'SendBonusAmount'=>0.00,'DateTime'=>'');
            
            if($unit==1){
                $row['DateTime'] = $start;
                $receiveSql = "SELECT SUM(Amount) as ReceiveAmount,COUNT(DRID) as ReceiveNum FROM bonus_detail_receive WHERE ".$memberStr."TO_DAYS(ReceiveTime)=TO_DAYS('{$start}') GROUP BY to_days(ReceiveTime)";
                $sendSql = "SELECT SUM(BonusAmount) as SendBonusAmount,COUNT(BID) as SendBonusNum FROM bonus WHERE ".$memberStr."TO_DAYS(SendTime)=TO_DAYS('{$start}') GROUP BY DATE_FORMAT(SendTime,'%Y-%m-%d') ";
                $start = date('Y-m-d',strtotime($start.' + 1 day'));
            }else{
                $row['DateTime'] = date('Y-m',strtotime($start));
                $receiveSql = "SELECT SUM(Amount) as ReceiveAmount,COUNT(DRID) as ReceiveNum FROM bonus_detail_receive WHERE ".$memberStr."DATE_FORMAT(ReceiveTime,'%Y-%m')='{$row['DateTime']}' GROUP BY DATE_FORMAT(ReceiveTime,'%Y-%m')";
                $sendSql = "SELECT SUM(BonusAmount) as SendBonusAmount,COUNT(BID) as SendBonusNum FROM bonus WHERE ".$memberStr."DATE_FORMAT(SendTime,'%Y-%m')='{$row['DateTime']}' GROUP BY DATE_FORMAT(SendTime,'%Y-%m')";
                $start = date('Y-m-d',strtotime($start.' + 1 month'));
            }
            $receiveRes = $this->_db->query($receiveSql)->fetch();
            if(!empty($receiveRes)){
                $row['ReceiveAmount'] = empty($receiveRes['ReceiveAmount'])?0.00:$receiveRes['ReceiveAmount'];
                $row['ReceiveNum'] = empty($receiveRes['ReceiveNum'])?0:$receiveRes['ReceiveNum'];
            }
            $sendRes = $this->_db->query($sendSql)->fetch();
            if(!empty($sendRes)){
                $row['SendBonusAmount'] = empty($sendRes['SendBonusAmount'])?0.00:$sendRes['SendBonusAmount'];
                $row['SendBonusNum'] = empty($sendRes['SendBonusNum'])?0:$sendRes['SendBonusNum'];
            }
            $list[] = $row;
        }
        return $list;
    }
}