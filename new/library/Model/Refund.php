<?php
/**
 *广告位相关
 * @author Jeff
 *
 */
class Model_Refund extends Zend_Db_Table
{
	protected $_name = 'wallet_refund_applications';
	protected $_primary = 'RefundApplicationID';
	
    /**
     * 处理提现申请
     */
    public function handleRefund(){
        header("Content-type: text/html; charset=utf-8");    
        $num = 100;
        $refundModel = new DM_Model_Table_Finance_Refund();
        $list = $refundModel->getRefundList($num,DM_Model_Table_Finance_Refund::REFUND_PROCESSED,0);
        if(empty($list)){
            echo "success1";die();
        }
        //生成提现处理列表
        $refundId = '';
        $refundList = array();
        $total_Num = 0;
        $cardModel = new Model_Wallet_WalletBankCard();
        foreach($list as $row){
            if(empty($row['order_Id'])){
                continue;
            }
            $bankInfo = $cardModel->getCardInfoById($row['bankId']);
            if(empty($bankInfo)){
                continue;
            }
            $row['bank_Name'] = $bankInfo['BankName'];
            $row['account_Number'] = $bankInfo['CardNo'];
            $row['account_Name'] = $bankInfo['Owner'];
            $refundList[] = $row;
            $total_Num++;
            $refundId .= $row['refundId'].',';
        }
        $refundId = trim($refundId,',');
        if(empty($refundId) || empty($refundList)){
            echo "success2";die();
        }
        try{
            $batch_No = substr(date('Y'),-2).date('mdHis').str_pad(rand(0,999),3,"0",STR_PAD_LEFT);
            //更新提现记录状态
            $ret = $this->update(array('BatchNo'=>$batch_No), array('RefundApplicationID IN(?)'=>$refundId));
            if($ret===false){
                throw new Exception('更新提现记录状态失败');
            }
            $content = "提现处理：batch_No:".$batch_No.';refundId:'.$refundId;
            //写入日志（文件）
            DM_Module_Log::addLogs($content,date("Y-m-d"),2,'log','refund');
            
            $refund_yeepay_model = new DM_Third_Yee_yeepayRefund();
            $refund_yeepay_model->setValue('batch_No',$batch_No);
            $refund_yeepay_model->setValue('refundList',$refundList);
            if($total_Num>1){
                $res = $refund_yeepay_model->batch();
            }else{
                $res = $refund_yeepay_model->one();
            }
            
            //将易宝返回结果写入日志（文件）
            DM_Module_Log::addLogs("提现处理,易宝返回结果：batch_No:".$batch_No.';res:'.json_encode($res),date("Y-m-d"),2,'log','refund');
            
            if($res['ret_Code']>1){
                throw new Exception('提交易宝提现失败');
            }
            
            //易宝接收失败的记录
            $failureList = array();
            if(isset($res['list']) && isset($res['list']['item']) && !empty($res['list']) && !empty($res['list']['item'])){
                foreach($res['list']['item'] as $item){
                    $failureList[] = $item['order_Id'];
                    $refundModel->_db->insert("wallet_refund_error_log",array('OrderNo'=>$item['order_Id'],'BatchNo'=>$batch_No,'ErrorCode'=>$item['error_Code'],'ErrorMsg'=>$item['error_Msg'],'Type'=>1));
                }
            }
            
            $fundsModel = new DM_Model_Table_Finance_Funds();
            $orderModel = new DM_Model_Table_Finance_Order();
            //将所有记录（除易宝返回的失败数据）都修改成已打款的状态
            $messageModel = new Model_Message();
            foreach($refundList as $rr){
                if(in_array($rr['order_Id'],$failureList)){
                    //处理方式待定
                }else{
                    $re = $fundsModel->finishRefund($rr['MemberID'],$rr['refundId']);
                    if($re['code']==0){//成功
                        //更新交易概要表的记录状态
                        $orderModel->disposeOrder($rr['oid'],DM_Model_Table_Finance_Order::ORDER_STATUS_DONE,'OID');
                        $messageModel->addMessage($rr['MemberID'], Model_Message::MESSAGE_TYPE_REFUND, $rr['RelationID'], Model_Message::MESSAGE_SIGN_WALLET);
                    }else{
                        $refundModel->_db->insert("wallet_refund_error_log",array('OrderNo'=>$rr['order_Id'],'BatchNo'=>$batch_No,'ErrorCode'=>$re['code'],'ErrorMsg'=>$re['msg'],'Type'=>2));
                    }
                    //将结果写入文件日志
                    DM_Module_Log::addLogs("提现处理,修改状态：batch_No:".$batch_No.';refundId:'.$rr['refundId'].';res:'.json_encode($re),date("Y-m-d"),2,'log','refund');
                }
            }
            echo "success3";die();
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
}