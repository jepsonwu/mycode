<?php
include dirname(__FILE__).'/../Abstract.php';

class Member_Finance_RefundController extends Member_Abstract
{
    public function indexAction()
    {
        $this->view->headTitle($this->getLang()->translate('member.finance.refund.pagetitle'));
        
        $this->initKvCurrency ();
    }
        
    private function initKvCurrency()
    {
        $currencies = $this->api ( 'finance/get-currencies' );
        if (! $currencies ['flag'] || empty ( $currencies ['data'] )) {
            throw new Zend_Exception ( $currencies ['msg'] );
        }
        $currencies = $currencies ['data'];
    
        $kvCurrency = array ();
        foreach ( $currencies as $key => $coin ) {
            $kvCurrency [$coin ['type_id']] = $coin ['en_name'];
        }
    
        $this->view->kvCurrency = $kvCurrency;
    }

    public function listAction()
    {
        $page = $this->_getParam('page',1);
        $pagesize = $this->_getParam('pagesize',10);
        $search= $this->_getParam('search');
        $search['application_status']=isset($search['application_status'])?implode(',',$search['application_status']):-1;
        $search['type_id']=isset($search['type_id'])?implode(',',$search['type_id']):'';
        $search['page']=$page;
        $search['pageSize']=$pagesize;
        $refund = $this->api('finance/get-refund-list',$search);

        $pageCount=isset($refund['data']['total'])?$refund['data']['total']:0;
        if(isset($refund['flag']) && $refund['flag']){
            if(count($refund['data']['list'])>0){
                $this->view->pageData =  $refund['data']['list'];
            }
        }

        $adapter = new Zend_Paginator_Adapter_Null((int)$pageCount);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setCurrentPageNumber((int)$page)->setItemCountPerPage((int)$pagesize);
        $this->view->pageList = $paginator;
    }
    
    /**
     * 提交提现申请
     */
    public function submitRefundAction()
    {
        if($this->memberInfo["idcard_verify_status"] != 1||$this->memberInfo["refund_pwd_status"] != 1){
            $this->view->msg = $this->getLang()->_("member.user.verifyidentity.idcardorrefundpwdisnoset");//身份未认证或提现密码未设置
            $this->render('notice');
        }
    	$this->view->headTitle($this->getLang()->_('common.finance.refund'));
    	//获取用户信息
    	$user_info = $this->memberInfo;
    	$member_id = $user_info['member_id'];
    	

    	if($this->_request->isPost()){
    		//账户ID
	        $amount_id = $this->_request->getParam('amount_id',0);
	        //申请额度
	        $application_amount = $this->_request->getParam('application_amount',0);
	        if(!is_numeric($application_amount) || $application_amount <= 0){
	        	$this->showMsg(0,/*"提现额度错误"*/ $this->getLang()->translate('member.finance.refund.amount.error'));;
	        }
	        	        
	        //提现密码
	        $password = $this->_request->getParam('password','');
	        //验证码
	        $code = $this->_request->getParam('code');

    		$result = $this->api('finance/submit-refund', array('amount_id' => $amount_id, 'application_amount' => $application_amount, 'password' => $password ,'code'=>$code));
            
    		$this->showMsg($result['flag'],$result['msg']);
    	}else{
    		$type_id = $this->_request->getParam('type_id',0);
    		$amount_id = $this->_request->getParam('amount_id',0);
    		$this->checkChargeOrRefund($type_id,2,1);
    		$amount_address = $this->_request->getParam('amount_address','');
    		if(empty($type_id)){
    			$this->showMsg(0,/*'请选择货币类型'*/ $this->getLang()->translate('member.finance.refund.submit.select.type'));
    		}
    		//获取用户信息
    		$user_info = $this->api('user/get-user-info');
    		if($user_info['flag']==1){
    			$this->view->idcard_verify_status = $user_info['data']['idcard_verify_status'];
    		}
    		//人民币汇率
    		$rate = $this->api('cache/get-rmb-rate');
    		$this->view->rmb_rate = $rate['msg'];
    		
    		$accountFlag = false;
    		$amount_info = $this->api('finance/get-amount-info',array('type_id'=>$type_id));
    		if($amount_info['flag']==1){
    			$this->view->info = $amount_info['data'][0];
    		}
    		
    		//美元
    		if ($type_id==1){
    		    $usdInfo = $this->api('finance/get-usd-account-info');
    		    
    		    if(isset($usdInfo['data']) &&$usdInfo['data'] ){
    		        $this->view->usdInfo=$usdInfo['data'];
    		        if(empty($usdInfo['data']['account_num'])){
    		        	$accountFlag = true;
    		        }
    		    }
    		}
    		$this->view->accountFlag = $accountFlag;
    	}
    }
    /**
     * 美元充值账户
     */
    public function creditUsdAction()
    {
    	$this->view->headTitle(/*'美元充值'*/$this->getLang()->translate('member.finance.USD.charge'));
    	$this->checkChargeOrRefund(1,1,1);
    	//获取用户信息
    	$user_info = $this->memberInfo;
    	$member_id = $user_info['member_id'];
    	$info = $this->api('finance/get-usd-account-info',array('member'=>0));
    	if($info['flag']==1){
    		$this->view->info = $info['data'];
    	}else{
    		$this->returnJson(0,$info['msg']);
    	}
    	
    }
    /**
     * 填写汇款通知单
     */
    public function remitAction()
    {
    	$this->view->headTitle(/*'汇款通知单'*/$this->getLang()->translate('member.finance.refund.remit.notice'));
    	if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
           
            
            if(isset($_FILES['file']['name']) && $_FILES['file']['name'] != ''){
            	$file = $this->upload();
            
	            $temp = array();
	            if(is_array($file)){
	            	foreach ($file as $key => $value) {
	            		$temp[$value['key']] = '@' . $value['savepath'] . $value['savename'];
	            	}
	            }
	            $ret['file'] = $temp['file'];
            }
            
            $return = $this->api('finance/set-usd-remittance-slip',$ret);
            if($return['flag']==1){
            	$this->returnJson($return['flag'], /*'提交成功'*/$this->getLang()->translate('member.finance.refund.remit.commit.success'));
            }else{
            	$this->returnJson(0, $return['msg']);
            }
    	}else{
    		$bank_id = $this->_getParam('bank_id',0);
    		$this->view->member_bank_id = $bank_id;
    	}

    }
    private function upload(){
    	$savePath = substr(dirname(__FILE__), 0, -47);
    	//网络地址
    	$sitePath = '/upload/remit_slip/';
    	$filepath = $savePath . $sitePath;
    
    	$upload = new Model_Upload();
    
    	$upload->maxSize  = 1024*1024 ;// 1M设置附件上传大小
    	$upload->allowExts  = array('jpg', 'png', 'jpeg');// 设置附件上传类型
    	$upload->savePath =  $filepath ;// 设置附件上传目录
    
    	if(!$rs = $upload->upload()) {// 上传错误提示错误信息
    		$msg = /*'上传操作失败:'*/$this->getLang()->translate('common.error.upload') . $upload->getErrorMsg();
    		$this->returnJson(0,$msg);
    	}
    	//文件上传后的信息
    	return $upload->getUploadFileInfo();
    }
    /**
     * 虚拟币充值地址
     */
    public function creditCoinAction()
    {
    	$this->view->headTitle(/*'虚拟币充值'*/$this->getLang()->translate('member.finance.coin.charge'));
    	//获取用户信息
    	$user_info = $this->memberInfo;
    	$member_id = $user_info['member_id'];
    	$type_id = $this->_request->getParam('type_id',0);
    	
    	if($this->_request->isPost()){
    	    if(!$this->checkChargeOrRefund($type_id,1,0)){
    	        $this->returnJson(0,/*'系统维护中,暂停充值'*/$this->getLang()->translate('member.finance.charge.stop'));
    	    }
    		$address = $this->_request->getParam('address','');
    		$address_info = $this->api('finance/get-credit-address',array('type_id'=>$type_id,'address'=>$address));
    		if($address_info['flag']==1){
    			$this->returnJson(1,$address_info['msg']);
    		}else{
    			$this->returnJson(0,$address_info['msg']);
    		}
    		
    	}else{
    	   $this->checkChargeOrRefund($type_id,1,1);
    		$info = $this->api('finance/get-amount-info',array('type_id'=>$type_id));
	    	if($info['flag']==1){
	    		$this->view->info = $info['data'][0];
	    	}else{
	    		$this->returnJson(0,$info['msg']);
	    	}
    		$un_info = $this->api('finance/get-un-account',array('type_id'=>$type_id));
	    	if($un_info['flag']==1){
	    		$this->view->un_info = isset($un_info['data'])?$un_info['data']:array();
	    	}else{
	    		$this->returnJson(0,$un_info['msg']);
	    	}
    		$address_info = $this->api('finance/get-credit-address',array('type_id'=>$type_id));
    		if($address_info['flag']==1){
    			$this->view->address = $address_info['msg'];
    		}else{
    			throw new Exception($address_info['msg']);
    		}
    	}
    }


    public function cancelAction()
    {
        $application_id = (int)$this->_request->getParam('application_id');
        if($application_id){
            $return = $this->api('finance/cancel-refund',array('application_id'=>$application_id));
            $this->returnJson($return);
        }
    }

    public function showMsg($status, $msg, $data = ''){
        header('Content-Type:text/html; charset=utf-8');
    	$script = '<script type="text/javascript">';
    	$script .= 'parent.showMsg("'.$status.'", "'.$msg.'")';
    	$script .= '</script>';
    	echo $script;exit;
    }
    
    /**
     * 转账
     */
    public function transferAction()
    {
        if($this->memberInfo["idcard_verify_status"] != 1||$this->memberInfo["refund_pwd_status"] != 1){
            $this->view->msg = $this->getLang()->_("member.user.verifyidentity.idcardorrefundpwdisnoset");//身份未认证或提现密码未设置
            $this->render('notice');
        }
        if($this->_request->isPost()){
           $to_id = $this->_request->getParam('to_id',0);
           if(empty($to_id)){
               $this->showMsg(0, /*'ID 不能为空'*/sprintf($this->getLang()->translate('common.error.not.empty'),'ID'));
              }
              
           $to_name = trim($this->_request->getParam('to_name',''));
           
           if(empty($to_name)){
               $this->showMsg(0,$this->getLang()->translate('member.finance.refund.error.name.empty'));
              }
              
           $amount = $this->_request->getParam('amount',0);
           
           if(!is_numeric($amount) || $amount <= 0){
              $this->showMsg(0, /*'转账额度有误,必须大于0'*/$this->getLang()->translate('member.finance.transfer.error.amount')); 
              }
              
              //提现密码
           $password = $this->_request->getParam('password','');
           if(empty($password)){
               $this->showMsg(0, /*'支付密码不能为空'*/$this->getLang()->translate('member.finance.index.paypassword'));
              }
	        //验证码
	        $code = $this->_request->getParam('code');
              
           $return = $this->api('finance/transfer',array('to_id'=>$to_id,'to_name'=>$to_name,'amount'=>$amount,'password'=>$password,'code'=>$code));
           $this->showMsg($return['flag'],$return['msg']);
        }else{
            $amount_info = $this->api('finance/get-amount-info',array('type_id'=>1));
            $this->view->info = $amount_info['data'][0];
        }
    }
    
    /**
     * 判断指定币种是否可充值或提现
     * @param int $type_id
     */
    private function checkChargeOrRefund($type_id,$type = 1,$showException = 0)
    {
        $status = false;
        $return = $this->api('finance/get-currencies',array('type_id'=>$type_id));
        if(!empty($return['data'])){
            $info = $return['data'][0];
            if($type == 1){
                if($info['allow_charge'] == 1){
                    $status = true;
                }
            }elseif($type == 2){
                if($info['allow_refund'] == 1){
                    $status = true;
                }
            }
        }
        if($showException){
            if(!$status){
                throw new Exception($type == 1 ? $this->getLang()->translate('member.finance.charge.stop') : $this->getLang()->translate('member.finance.refund.stop'));
            }
        }else{
            return $status;
        }
    }
}