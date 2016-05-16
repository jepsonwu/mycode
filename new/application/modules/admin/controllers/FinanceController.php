<?php
/**
 * 财务管理
 * 
 * @author Hale
 *
 */
class Admin_FinanceController extends DM_Controller_Admin
{	
	public function indexAction()
	{
        
	}
    
    /**
	 * 财务明细列表
	 */
    public function listAction()
    {
		$this->_helper->viewRenderer->setNoRender();
		$amountLogModel = new DM_Model_Table_Finance_AmountLog();
		
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',10);

        $numberType = $this->_getParam('numberType',1);
        $number = trim($this->_getParam('number',''));

        $Income_Payout = $this->_getParam('Income_Payout',0);
        $FinanceType = $this->_getParam('FinanceType',0);

        $start_date = $this->_getParam('start_date','');
        $end_date = $this->_getParam('end_date','');

        $start_amount = $this->_getParam('start_amount',-1);
        $end_amount = $this->_getParam('end_amount',-1);

		$select = $amountLogModel->select()->setIntegrityCheck(false);
		$select->from('wallet_amount_logs', array('AmountLogID','CreateDate','MemberID','orderType','Income_Payout'=>'Type','Balance','RelationID','Amount','Ip','Remark'));
        if(!empty($number)){
            $select->where($numberType.'=?',$number);
        }
        $Income_Payout && $select->where('`Type`=?',$Income_Payout);
        $FinanceType && $select->where('`orderType`=?',$FinanceType);

        if(!empty($start_date)){
            $select->where('CreateDate >= ?',date('Y-m-d 00:00:00',strtotime($start_date)));
        }

        if(!empty($end_date)){
            $select->where('CreateDate <= ?',date('Y-m-d 23:59:59',strtotime($end_date)));
        }

        if($start_amount>=0){
            $select->where('Amount >= ? ',$start_amount);
        }

        if($end_amount>=0){
            $select->where('Amount <= ?',$end_amount);
        }
		//获取sql
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
        //总条数
        $total = $select->getAdapter()->fetchOne($countSql);
        //排序
		$sort = $this->_getParam('sort','AmountLogID');
		$order = $this->_getParam('order','desc');
        $res = $select->order("$sort $order")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
        $list = array();
		foreach ($res as $row){
            $MemberInfo = $this->getMemberInfoById($row['MemberID']);
            $row['Email'] = isset($MemberInfo['Email'])?$MemberInfo['Email']:'';
            $FinanceType = $this->getFinanceTypeName($row['orderType']);
            $row['TypeName'] = isset($FinanceType['Tname'])?$FinanceType['Tname']:'';
            $list[] = $row;
        }
		
		$this->_helper->json(array('total'=>$total,'rows'=>$list));
    }

    /**
     * 添加财务明细记录
     */
    public function addDetailAction(){
        if($this->_request->isPost()){
			$this->_helper->viewRenderer->setNoRender();
            $MemberID = $this->_getParam('member_id',0);
            if(empty($MemberID)){
                $this->returnJson(0,'会员ID不能为空！');
            }
            if(!preg_match('/^\d+$/',$MemberID)){
				$this->returnJson(0,'会员编号必须为整数');
			}
            $currency = $this->_getParam('currency',"CNY");
            $amount = $this->_getParam('amount',0);
            if(empty($amount)){
                $this->returnJson(0,'金额不能为空！');
            }
            $orderType = $this->_getParam('financeType',1);
            $remark = $this->_getParam('remark','');
            if(empty($remark)){
				$this->returnJson(0,'备注不能为空');
			}
			$fundsModel = new DM_Model_Table_Finance_Funds();
			
			$fundsModel->tBegin();
			$flag = 0;
			$msg = '';
            
			$ip = $this->_request->getClientIp();
			try{
				if($orderType==1){
                    $orderID = $fundsModel->createOrder($MemberID,1,$orderType,$amount,2,2,0,0,$ip,$currency,0,$remark);
                    if(!$orderID){
                        $fundsModel->tRollBack();
                        $this->returnJson($flag,"创建订单失败");
                    }
                    $ret = $fundsModel->modifyAmount($MemberID,$currency,$amount,1,$orderType,$ip,$remark,$orderID);
                    $content = '会员充值';
                }elseif($orderType==2){
                    //$ret = $fundsModel->income($MemberID,$currency,$amount,2,$financeType,$ip,$remark);
                    $content = '会员提现';
				}
                $amount = number_format($amount,2,'.','');
                if($currency=="CNY"){
                    $content .= '[￥'.$amount.']';
                }elseif($currency=="USD"){
                    $content .= '[$'.$amount.']';
                }
                $lastInsertId = $fundsModel->getAdapter()->lastInsertId();
                if(!$this->addLogs($MemberID,'AMOUNT',$lastInsertId, $content)){
                    throw new Exception('添加操作日志失败');
                }
				if($ret == $fundsModel::OP_SUCCESS){
					$fundsModel->tCommit();
					$flag = 1;
				}else{
					throw new Exception('操作失败'.$ret);
				}
			}catch(Exception $e){
				$fundsModel->tRollBack();
				$msg = $e->getMessage();
			}
			$this->returnJson($flag,$msg);
		}
    }
    
    /**
     * 账户余额审计
     */
    public function auditingAction(){
        if($this->_request->isPost()){
            $this->_helper->viewRenderer->setNoRender();
			//分页参数
			$pageIndex = $this->_getParam('page',1);
			$pageSize = $this->_getParam('rows',10);

            $member_id = trim($this->_getParam('member_id',0));
            $currency = $this->_getParam('currency','CNY');
            $start_balance = trim($this->_getParam('start_balance',-1));
            $end_balance = trim($this->_getParam('end_balance',-1));
            $start_freeze = trim($this->_getParam('start_freeze',-1));
            $end_freeze = trim($this->_getParam('end_freeze',-1));

			$fundsModel = new DM_Model_Table_Finance_Funds();
            $amountModel = new DM_Model_Table_Finance_Amount();
			$select = $amountModel->select()->setIntegrityCheck(false);
			$select->from('wallet_amounts', array('MemberID','Balance','FreezeAmount'))->where('Currency=?',$currency);

            !empty($member_id) && $select->where('MemberID=?',$member_id);
            $start_balance>0 && $select->where('Balance>=?',$start_balance);
            $end_balance>=0 && $select->where('Balance<= ? ',$end_balance);
            $start_freeze>=0 && $select->where('FreezeAmount >= ?',$start_freeze);
            $end_freeze>=0 && $select->where('FreezeAmount <= ?',$end_freeze);
            $results = array();
            if(!empty($member_id)){
                $memberInfo = $this->getMemberInfoById($member_id);
                if(empty($memberInfo)){
                    $this->_helper->json(array('total'=>0,'rows'=>$results));
                }
            }
            //获取sql
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
            //总条数
            $total = $select->getAdapter()->fetchOne($countSql);
            //排序
            $sort = $this->_getParam('sort','Balance');
            $order = $this->_getParam('order','desc');
            $res = $select->order("$sort $order")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
            if(!empty($member_id) && empty($res)){
                $results[] = array('Email'=>$memberInfo['Email'],'MemberID'=>$member_id,'Balance'=>0,'FreezeAmount'=>0);
                $this->_helper->json(array('total'=>1,'rows'=>$results));
            }
            
			foreach ($res as $row){
                $MemberInfo = $this->getMemberInfoById($row['MemberID']);
                $row['Email'] = isset($MemberInfo['Email'])?$MemberInfo['Email']:'';
                $results[] = $row;
            }
			
			$this->_helper->json(array('total'=>$total,'rows'=>$results));
		}
    }
    
    /**
     * 财务操作日志
     */
    public function financeLogsAction(){
        if($this->_request->isPost())
        {
            $this->_helper->viewRenderer->setNoRender();
            $amountLogModel = new DM_Model_Table_Finance_AmountLog();

            $pageIndex = $this->_getParam('page',1);
            $pageSize = $this->_getParam('rows',10);

            $numberType = $this->_getParam('numberType','AdminID');
            $number = trim($this->_getParam('number',''));

            $select = $amountLogModel->select()->setIntegrityCheck(false);
            $select->from('admin_finance_logs', array('LogID','AdminID','MemberID','InfoSign','Content','AddTime','InfoID'));
            if(!empty($number)){
                $re = $amountLogModel->select()->setIntegrityCheck(false)->from('admins',array('AdminID'))->where('Username=?',$number)->query()->fetchAll();
                if(empty($re)){
                    $this->_helper->json(array('total'=>0,'rows'=>array()));
                }
                $select->where($numberType.'=?',$re[0]['AdminID']);
            }
            //获取sql
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
            //总条数
            $total = $select->getAdapter()->fetchOne($countSql);
            //排序
            $sort = $this->_getParam('sort','LogID');
            $order = $this->_getParam('order','desc');
            $res = $select->order("$sort $order")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
            $list = array();
            foreach ($res as $row){
                $MemberInfo = $this->getMemberInfoById($row['MemberID']);
                $row['Email'] = isset($MemberInfo['Email'])?$MemberInfo['Email']:'';
                $row['Username'] = $this->getAdminName($row['AdminID']);
                $list[] = $row;
            }

            $this->_helper->json(array('total'=>$total,'rows'=>$list));
        }
    }
    
    /**
     * 提现记录
     */
    public function refundListAction(){
        if($this->_request->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			//分页参数
			$pageIndex = $this->_getParam('page',1);
			$pageSize = $this->_getParam('rows',10);

            $status = $this->_getParam('status',0);
            $member_id = $this->_getParam('member_id',0);
            $start_amount = $this->_getParam('start_amount',-1);
            $end_amount = $this->_getParam('end_amount',-1);
            $start_date = $this->_getParam('start_date');
            $end_date = $this->_getParam('end_date');

			$refundModel = new DM_Model_Table_Finance_AmountLog();
			$select = $refundModel->select()->setIntegrityCheck(false);
			$select->from(array('t'=>'wallet_refund_applications'),array('RefundApplicationID','ApplicationDate','ConfirmDate','MemberID','BatchNo','BankInfoID','ApplicationAmount','FeeAmount','RealityAmount','Currency','Status'));
			$select->joinInner('wallet_order_list as m', 'm.OID = t.RelationID and m.MemberID=t.MemberID',array());

			$status>0 && $select->where('t.Status = ? ',$status);
            !empty($member_id) && $select->where('t.MemberID = ?',$member_id);
            $start_amount >= 0 && $select->where('t.ApplicationAmount >= ? ',$start_amount);
            $end_amount >= 0 && $select->where('t.ApplicationAmount <= ?',$end_amount);
            !empty($start_date) && $select->where('t.ApplicationDate >= ?',date('Y-m-d 00:00:00',strtotime($start_date)));
            !empty($end_date) && $select->where('t.ApplicationDate <= ?',date('Y-m-d 23:59:59',strtotime($end_date)));
            
            //获取sql
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
            //总条数
            $total = $select->getAdapter()->fetchOne($countSql);
            //排序
            $sort = $this->_getParam('sort','t.RefundApplicationID');
            $order = $this->_getParam('order','desc');
            $res = $select->order("$sort $order")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
            $list = array();
            $cardModel = new Model_Wallet_WalletBankCard();
            foreach ($res as $row){
                $row['ConfirmDate'] = $row['ConfirmDate']=='0000-00-00 00:00:00'?'':$row['ConfirmDate'];
                $MemberInfo = $this->getMemberInfoById($row['MemberID']);
                $row['Email'] = isset($MemberInfo['Email'])?$MemberInfo['Email']:'';
                
                $bankInfo = $cardModel->getCardInfoById($row['BankInfoID']);
                $row['BankName'] = isset($bankInfo['BankName'])?$bankInfo['BankName']:'';
                $row['Username'] = isset($bankInfo['Owner'])?$bankInfo['Owner']:'';
                $row['CardCode'] = isset($bankInfo['CardNo'])?$bankInfo['CardNo']:'';
                $row['City'] = str_replace(',','&nbsp;&nbsp;',$bankInfo['City']);
                if($row['Status']==3 && !empty($row['BatchNo'])){//打款中
                    $row['Error'] = 1;
                }else{
                    $row['Error'] = 0;
                }
                $list[] = $row;
            }
			$this->_helper->json(array('total'=>$total,'rows'=>$list));
		}
        $refundModel = new DM_Model_Table_Finance_AmountLog();
		$select = $refundModel->select()->setIntegrityCheck(false);
        $re = $select->from(array('wallet_refund_applications'),array('refundAmount'=>'sum(RealityAmount)'))->where('Status = 4')->query()->fetch();
        $this->view->refundAmount = empty($re)?0.00:$re['refundAmount'];
    }
    
    //设置卡的归属地
    public function setCityAction(){
        $bid = $this->_getParam('bid',0);
        if($this->_request->isPost()){
            $province = trim($this->_getParam('province',''));
            $city = trim($this->_getParam('city',''));
            if(empty($province) || empty($city) || empty($bid)){
                $this->returnJson(0,'参数错误');
            }
            $walletModel = new Model_Wallet_WalletBankCard();
            $walletModel->update(array('City'=>$province.','.$city), array('BID=?'=>$bid));
            $this->returnJson(1,'保存成功');
		}
        $city = array('province'=>'','city'=>'','bid'=>$bid);
        $walletModel = new Model_Wallet_WalletBankCard();
		$cardInfo = $walletModel->getCardInfoById($bid,array('City',"CardNo"));
        $city_arr = explode(',', $cardInfo['City']);
        $city['province'] = empty($city_arr[0])?'':$city_arr[0];
        $city['city'] = empty($city_arr[1])?'':$city_arr[1];
        $this->view->cardInfo = $city;
    }
    
    /**
     * 加载提现审核的视图
     */
    public function refundCheckAction(){
        $application_id = $this->_getParam('application_id');
        $member_id = $this->_getParam('member_id');
        $status = $this->_getParam('status');

        $refundModel = new DM_Model_Table_Finance_AmountLog();
        $select = $refundModel->select()->setIntegrityCheck(false);
        $select->from(array('wallet_refund_applications'),array('ApplicationAmount','FeeAmount','RealityAmount'))->where('RefundApplicationID = ? ',$application_id);
        $res = $select->query()->fetchAll();
        $info = !empty($res)?$res[0]:array();
        $this->view->application_id = $application_id;
        $this->view->member_id = $member_id;
        $this->view->status = $status;
        $this->view->refund = $info;
    }
    
    /**
     * 审核提现
     */
    public function refundStatusAction(){
        $this->_helper->viewRenderer->setNoRender();
        $application_id = $this->_getParam('application_id');
        $member_id = $this->_getParam('member_id');
        $status = $this->_getParam('status');
        if($this->_request->isPost()){
            if(empty($application_id) || empty($member_id) || empty($status)){
                $this->returnJson(0,'参数错误');
            }
            $flag = 0;
            $msg = '';
            $refundModel = new DM_Model_Table_Finance_Refund();
            $refundInfo = $refundModel->getApplicationInfo($application_id,$member_id,"ApplicationAmount,Currency,RelationID");
            $refundApplicationInfo = $refundModel->getApplicationInfo($application_id, $member_id,"ApplicationAmount,Currency,RelationID");
            if(empty($refundApplicationInfo)){
                $this->returnJson(0,'提现记录不存在');
            }
            $orderID = $refundInfo['RelationID'];
            $messageModel = new Model_Message();
            
            $ip = $this->_request->getClientIp();
            if($status == 4){//完成提现
                $fundsModel = new DM_Model_Table_Finance_Funds();
                $fundsModel->tBegin();
                try{
                    $res = $fundsModel->finishRefund($member_id,$application_id,$this->_request->getClientIp(),'提现成功');
                    if($res['code']==0){
                        if(!$this->addLogs($member_id, 'REFUND', $application_id, '完成提现')){
                            throw new Exception('加入操作日志失败');
                        }
                        $flag = 1;
                        $messageModel->addMessage($member_id, Model_Message::MESSAGE_TYPE_REFUND, $orderID, Model_Message::MESSAGE_SIGN_WALLET);
                        $fundsModel->tCommit();
                    }else{
                        throw new Exception($res['msg']);
                    }
                }catch(Exception $e){
                    $msg = $e->getMessage();
                    $fundsModel->tRollBack();
                }
            }elseif($status == 3){//审核通过
                $refundModel->getAdapter()->beginTransaction();
                try{
                    if($refundModel->setApplicationStatus($application_id, $member_id,3)){
                        $fee_amount = $this->_getParam('fee_amount',0.00);
                        if(!is_numeric($fee_amount) || $fee_amount < 0){
                            throw new Exception('手续费填写错误');
                        }
                        if($refundModel->updateRefundFee($application_id, $member_id, $fee_amount)){
                            if(!$this->addLogs($member_id, 'REFUND', $application_id, '审核提现手续费')){
                                throw new Exception('加入操作日志失败');
                            }
                            $flag = 1;
                            $refundModel->getAdapter()->commit();
                        }else{
                            throw new Exception('更新手续费出错');
                        }
                    }else{
                        throw new Exception('更新状态错误');
                    }
                }catch(Exception $e){
                    $msg = $e->getMessage();
                    $refundModel->getAdapter()->rollBack();
                }
            }elseif($status == 2){//拒绝提现
                $remark = trim($this->_getParam('remark'));//备注
                if(empty($remark)){
                    $this->returnJson(0,'备注内容不能为空');
                }
                $fundsModel = new DM_Model_Table_Finance_Funds();
                $fundsModel->tBegin();
                try{
                    $orderModel = new DM_Model_Table_Finance_Order();
                    $orderInfo = $orderModel->getInfoByOID($orderID,array("Remark","OrderNo"));
                    $amount = $refundInfo['ApplicationAmount'];
                    $backRemark = (isset($orderInfo['Remark'])?$orderInfo['Remark']:'')."退款";
                    $currency = $refundInfo['Currency'];
                    $orderType = 2;//提现
                    $backOrderID = $fundsModel->createOrder($member_id,1,20,$amount,2,2,$application_id,0,$ip,$currency,0,$backRemark,$orderType);
                    if(!$backOrderID){
                        throw new Exception('创建订单失败');
                    }
                    $res = $fundsModel->rejectRefund($member_id,$application_id,$remark,$this->_request->getClientIp());
                    if($res['code']==0){//成功
                        if(!$this->addLogs($member_id, 'REFUND', $application_id, '拒绝提现')){
                            throw new Exception('加入操作日志失败');
                        }
                        $fundsModel->tCommit();
                        $flag = 1;
                        $messageModel->addMessage($member_id, Model_Message::MESSAGE_TYPE_REFUND, $orderID, Model_Message::MESSAGE_SIGN_WALLET);
                    }else{
                        throw new Exception($res['msg']);
                    }
                }catch(Exception $e){
                    $msg = $e->getMessage();
                    $fundsModel->tRollBack();
                }
            }elseif($status==6){//退款
                $fundsModel = new DM_Model_Table_Finance_Funds();
                $fundsModel->tBegin();
                try{
                    $orderModel = new DM_Model_Table_Finance_Order();
                    $orderInfo = $orderModel->getInfoByOID($orderID, array("Remark","OrderNo"));
                    $amount = $refundInfo['ApplicationAmount'];
                    $backRemark = (isset($orderInfo['Remark'])?$orderInfo['Remark']:'')."退款";
                    $currency = $refundInfo['Currency'];
                    $orderType = 2;//提现
                    $backOrderID = $fundsModel->createOrder($member_id,1,20,$amount,2,2,$application_id,0,$ip,$currency,0,$backRemark,$orderType);
                    if(!$backOrderID){
                        throw new Exception('创建订单失败');
                    }
                    $updateStatus = $refundModel->setApplicationStatus($application_id,$member_id,DM_Model_Table_Finance_Refund::REFUND_BACK,'银行处理错误');
                    if($updateStatus === false){
                        throw new Exception('更新提现记录状态失败');
                    }
                    if($refundApplicationInfo['Currency']=="CNY"){
                        $content = '提现退款[￥'.$refundApplicationInfo['ApplicationAmount'].']';
                    }elseif($currency=="USD"){
                        $content = '提现退款[$'.$refundApplicationInfo['ApplicationAmount'].']';
                    }
                    $ret = $fundsModel->modifyAmount($member_id, $refundApplicationInfo['Currency'], $refundApplicationInfo['ApplicationAmount'],1,20, $ip, "提现退款", $refundApplicationInfo['RelationID']);
                    $lastInsertId = $fundsModel->getAdapter()->lastInsertId();
                    if(!$this->addLogs($member_id,'AMOUNT',$lastInsertId, $content)){
                        throw new Exception('添加操作日志失败');
                    }
                    if($ret == $fundsModel::OP_SUCCESS){
                        $fundsModel->tCommit();
                        $flag = 1;
                        $messageModel->addMessage($member_id, Model_Message::MESSAGE_TYPE_REFUND, $orderID, Model_Message::MESSAGE_SIGN_WALLET);
                    }else{
                        throw new Exception('调整退款后的金额失败'.$ret);
                    }
                }catch(Exception $e){
                    $msg = $e->getMessage();
                    $fundsModel->tRollBack();
                }
            }else{
                $msg = "状态参数错误";
            }
            $this->returnJson($flag,$msg);
        }
        $this->view->application_id = $application_id;
        $this->view->member_id = $member_id;
        $this->view->status = $status;
        $this->render("refund-reject");
    }
    
    /**
     * 冻结记录
     */
    public function freezeListAction(){
        if($this->_request->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$pageIndex = $this->_getParam('page',1);
			$pageSize = $this->_getParam('rows',10);

            $relationType = $this->_getParam('relation_type',0);
            $member_id = $this->_getParam('member_id',0);
            $start_amount = $this->_getParam('start_amount',-1);
            $end_amount = $this->_getParam('end_amount',-1);
            $start_date = $this->_getParam('start_date');
            $end_date = $this->_getParam('end_date');

			$freezeModel = new DM_Model_Table_Finance_AmountLog();
			$select = $freezeModel->select()->setIntegrityCheck(false);
			$select->from('wallet_freezes',array('FreezeID','FreezeTime','MemberID','RelationType','Amount','Status','Remark'=>'ifnull(Remark,"")','unFreezeTime','Currency'));

			$relationType>0 && $select->where('RelationType = ? ',$relationType);
            !empty($member_id) && $select->where('MemberID = ?',$member_id);
            $start_amount >= 0 && $select->where('Amount >= ? ',$start_amount);
            $end_amount >= 0 && $select->where('Amount <= ?',$end_amount);
            !empty($start_date) && $select->where('FreezeTime >= ?',date('Y-m-d 00:00:00',strtotime($start_date)));
            !empty($end_date) && $select->where('FreezeTime <= ?',date('Y-m-d 23:59:59',strtotime($end_date)));
            
            //获取sql
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());    
            //总条数
            $total = $select->getAdapter()->fetchOne($countSql);
            //排序
            $sort = $this->_getParam('sort','FreezeID');
            $order = $this->_getParam('order','desc');
            $res = $select->order("$sort $order")->limitPage($pageIndex,$pageSize)->query()->fetchAll();
            $list = array();
            foreach ($res as $row){
                $row['unFreezeTime'] = $row['unFreezeTime']=='0000-00-00 00:00:00'?'':$row['unFreezeTime'];
                $MemberInfo = $this->getMemberInfoById($row['MemberID']);
                $row['Email'] = isset($MemberInfo['Email'])?$MemberInfo['Email']:'';
                $list[] = $row;
            }
			$this->_helper->json(array('total'=>$total,'rows'=>$list));
		}
    }
    
    /**
     * 添加冻结资金记录
     */
    public function addFreezeAction(){
        if($this->_request->isPost()){
            $this->_helper->viewRenderer->setNoRender();
            $MemberID = $this->_getParam('member_id',0);
            if(empty($MemberID)){
                $this->returnJson(0,'会员ID不能为空！');
            }
            if(!preg_match('/^\d+$/',$MemberID)){
				$this->returnJson(0,'会员编号必须为整数');
			}
            $currency = $this->_getParam('currency',"CNY");
            $amount = $this->_getParam('amount',0);
            if(empty($amount)){
                $this->returnJson(0,'金额不能为空！');
            }
            $freezeType = $this->_getParam('freezeType',1);
            $remark = $this->_getParam('remark','');
            if(empty($remark)){
				$this->returnJson(0,'备注不能为空');
			}
			$fundsModel = new DM_Model_Table_Finance_Funds();
            $balance = $fundsModel->getMemberBalance($MemberID,$currency);
            if($balance<$amount){
                $this->returnJson(0,'冻结金额不能超过可用金额');
            }
			$fundsModel->tBegin();
			$flag = 0;
			$msg = '';
			try{
                $freezeID = $fundsModel->freeze($MemberID,$currency,$amount,$freezeType,0,$remark);
                if(!$freezeID){
                    $fundsModel->tRollBack();
                    $this->returnJson($flag,"冻结资金失败");
                }
                $modelAmount = new DM_Model_Table_Finance_Amount();
                $ret = $modelAmount->updateMemberAmount($MemberID,$currency,2,$amount,1);
                if(!$ret){
                    throw new Exception('修改可用金额失败');
                }
                $content = '冻结资金';
                $amount = number_format($amount,2,'.','');
                if($currency=="CNY"){
                    $content .= '[￥'.$amount.']';
                }elseif($currency=="USD"){
                    $content .= '[$'.$amount.']';
                }
                if(!$this->addLogs($MemberID,'FREEZE',$freezeID, $content)){
                    throw new Exception('添加操作日志失败');
                }
                $flag = 1;
				$fundsModel->tCommit();
			}catch(Exception $e){
				$fundsModel->tRollBack();
				$msg = $e->getMessage();
			}
			$this->returnJson($flag,$msg);
        }
    }
    
    /**
     * 获取提现的错误信息
     */
    public function refundErrorAction(){
        $this->_helper->viewRenderer->setNoRender();
        $application_id = $this->_getParam('application_id');
        $freezeModel = new DM_Model_Table_Finance_AmountLog();
        $select = $freezeModel->select()->setIntegrityCheck(false);
        $res = $select->from('wallet_refund_error_log',array('ErrorCode','ErrorMsg','ErrorType'=>'Type','AddTime'))->where('RefundID = ? ',$application_id)->order("LID desc")->limit(1)->query()->fetchAll();
        $error = array();
        if(!empty($res)){
            $error = $res[0];
            $error['ErrorType'] = $error['ErrorType']==1?"易宝返回":"解冻失败";
        }
        $this->view->Error = $error;
        $this->render("refund-error");
    }

    /**
     * 解冻资金
     */
    public function unfreezeAction(){
        $this->_helper->viewRenderer->setNoRender();
        $freeze_id = $this->_getParam('freeze_id',0);
        $relationType = $this->_getParam('relation_type',0);
        $member_id = $this->_getParam('member_id',0);
        if(empty($freeze_id) || empty($relationType) || empty($member_id)){
            $this->returnJson(0,'参数错误');
        }
        if($relationType==2){//提现
            $this->returnJson(0,'提现的冻结资金不能手动解冻');
        }
        $freezeModel = new DM_Model_Table_Finance_AmountLog();
        $select = $freezeModel->select()->setIntegrityCheck(false);
        $select->from('wallet_freezes',array('FreezeID','RelationType','Amount','Currency','Status'))->where('FreezeID=?',$freeze_id)->where('MemberID=?',$member_id)->where('Status=?',1)->where('RelationType',$relationType);
        $freezeInfo = $select->query()->fetchAll();
        if(empty($freezeInfo)){
            $this->returnJson(0,'冻结记录不存在');
        }
        $fundsModel = new DM_Model_Table_Finance_Funds();
        $fundsModel->tBegin();
        $flag = 0;
        $msg = '';
        try{
            $res = $fundsModel->unfreeze($member_id,$freeze_id,$relationType);
            if($res){//成功
                $content = '解冻资金';
                if($freezeInfo[0]['Currency']=="CNY"){
                    $content .= '[￥'.$freezeInfo[0]['Amount'].']';
                }elseif($currency=="USD"){
                    $content .= '[$'.$freezeInfo[0]['Amount'].']';
                }
                if(!$this->addLogs($member_id, 'UNFREEZE', $freeze_id, $content)){
                    throw new Exception('加入操作日志失败');
                }
                $fundsModel->tCommit();
                $flag = 1;
            }else{
                throw new Exception('解冻资金失败');
            }
        }catch(Exception $e){
            $msg = $e->getMessage();
            $fundsModel->tRollBack();
        }
        $this->returnJson($flag,$msg);
    }

    //获取用户信息
    public function getMemberInfoById($MemberID){
        /*$amountLogModel = new DM_Model_Table_Finance_AmountLog();
        $select = $amountLogModel->select()->setIntegrityCheck(false);
        $select->from("account_system.members",array('MemberID','UserName','Email'))->where("MemberID=?",$MemberID);
        $res = $select->query()->fetch();*/
        $memberModel = new DM_Model_Account_Members();
        $res = $memberModel->getMemberInfoCache($MemberID,array('MemberID','UserName','Email'));
        return $res;
    }
    
    //获取用户信息
    public function getFinanceTypeName($orderType){
        $amountLogModel = new DM_Model_Table_Finance_AmountLog();
        $select = $amountLogModel->select()->setIntegrityCheck(false);
        $select->from("wallet_order_type",array('Tname'))->where("Tid=?",$orderType);
        $res = $select->query()->fetch();
        return $res;
    }
    
    /**
     * 获取管理员名字
     */
    public function getAdminName($adminId){
        $amountLogModel = new DM_Model_Table_Finance_AmountLog();
        $select = $amountLogModel->select()->setIntegrityCheck(false);
        $select->from("admins",array('Username'))->where("AdminID=?",$adminId);
        $res = $select->query()->fetch();
        return empty($res)?'':$res['Username'];
    }

    /**
   	 * 添加日志
   	 * @param int $member_id
   	 * @param int $info_id
   	 * @param string $content
   	 */
   	private function addLogs($member_id,$info_sign,$info_id,$content)
   	{
   		$logsModel = new DM_Model_Table_Finance_Logs();
   		$admin_id = $this->auth->getIdentity()->AdminID;
   		return $logsModel->addLog($member_id, $admin_id, $info_sign,$info_id, $content);
   	}
}