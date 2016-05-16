<?php
include dirname(__FILE__).'/../Abstract.php';

class Member_Finance_IndexController extends Member_Abstract
{
    public function indexAction()
    {
        $this->view->headTitle($this->getLang()->translate('member.finance.index.amountview'));
    }

    public function setUsdAccountAction()
    {
        if($this->memberInfo["idcard_verify_status"] != 1||$this->memberInfo["refund_pwd_status"] != 1){
            $this->view->msg = $this->getLang()->_("member.user.verifyidentity.idcardorrefundpwdisnoset");//身份未认证或提现密码未设置
            $this->render('notice');
        }
        $this->view->headTitle($this->getLang()->translate('member.finance.index.usdaccount.setting'));
        $usdInfo = $this->api('finance/get-usd-account-info');
        if(isset($usdInfo['data']) &&$usdInfo['data'] ){
            $this->view->usdInfo=$usdInfo['data'];
        }
        //获取用户信息
        $user_info = $this->api('user/get-user-info');
        $name_en = '';
        if($user_info['flag']==1){
			$name_en = $user_info['data']['name_en'];
        }
        $this->view->name_en = $name_en;

        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            if (empty($ret['currency_type']))$ret['currency_type']='usd';
            
            if (!in_array($ret['currency_type'], array('usd', 'rmb'))){
                $this->returnJson(false,$this->getLang()->translate('member.finance.error.currency_type.illeagal'));
            }
            
            if ('usd' == $ret['currency_type']){
                if(!$ret['bank_name'] || !$ret['swift_code'] ||!$ret['account_num']){
                    $this->returnJson(false,$this->getLang()->translate('member.finance.index.fillbankinfo'));
                }
                if(!$ret['nation'] ||!$ret['state'] ||!$ret['city'] ||!$ret['address']){
                    $this->returnJson(false,$this->getLang()->translate('member.finance.index.fillarea'));
                }
                $ret['to_rmb']='N';
            }else{//人民币
                if(!$ret['bank_name'] ||!$ret['account_num']){
                    $this->returnJson(false,$this->getLang()->translate('member.finance.index.fillbankinfo'));
                }
                if(!$ret['state'] ||!$ret['city']){
                    $this->returnJson(false,$this->getLang()->translate('member.finance.index.fillarea'));
                }
                $ret['to_rmb']='Y';
            }

            if(!is_numeric($ret['account_num'])){
                $this->returnJson(false,$this->getLang()->translate('member.finance.index.cardnum'));
            }
            if(!preg_match('/^[0-9]{16,19}$/',$ret['account_num'])){
                $this->returnJson(false, $this->getLang()->translate('member.finance.index.cardnumformat'));
            }
            if(!$ret['password']){
                $this->returnJson(false,$this->getLang()->translate('member.finance.index.paypassword'));
            }

	        //验证码
	        $ret['code'] = $this->_request->getParam('code');
            $return = $this->api('finance/set-usd-account-info',$ret);
            if($return['flag']==1){
                $this->returnJson($return['flag'],$this->getLang()->translate('member.finance.index.setting.success'));
            }else{
                $this->returnJson($return['flag'], $return['msg']);
            }

        }

    }


    public function setCoinAccountAction()
    {
        if($this->memberInfo["idcard_verify_status"] != 1||$this->memberInfo["refund_pwd_status"] != 1){
            $this->view->msg = $this->getLang()->_("member.user.verifyidentity.idcardorrefundpwdisnoset");//身份未认证或提现密码未设置
            $this->render('notice');
        }
        $this->view->headTitle(/*'设置虚拟币账户'*/ $this->getLang()->translate('member.finance.index.coinaccount.setting'));
        $type_id = $this->_getParam('type_id');
        $this->view->type_id =$type_id;
        $coinInfo = $this->api('finance/get-amount-info',array('type_id'=>$type_id));
        $this->view->coinInfo=$coinInfo['data'][0];
        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            if(!$ret['type_id']){
                $this->returnJson(false, $this->getLang()->translate('member.finance.index.currency.type.error'));
            }
            if(!$ret['address']){
                $this->returnJson(false, /*"请输入提现账户！"*/ $this->getLang()->translate('member.finance.index.setcoin.fillaccount'));
            }
            if(!$ret['password']){
                $this->returnJson(false, /*"请输入支付密码！"*/ $this->getLang()->translate('member.finance.index.paypassword'));
            }

            //验证码
            $ret['code'] = $this->_request->getParam('code');
            $return = $this->api('finance/set-amount-coin-info',$ret);
            if($return['flag']==1){
                $this->returnJson($return['flag'], /*'设置成功'*/ $this->getLang()->translate('member.finance.index.setting.success'));
            }else{
                $this->returnJson($return['flag'], $return['msg']);
            }
            //$this->showMsg($return['flag'],$return['msg']);
        }
    }

    public function showMsg($status, $msg, $data = ''){
   		$script = '<script type="text/javascript">';
   		$script .= 'parent.showMsg("'.$status.'", "'.$msg.'")';
   		$script .= '</script>';
   		echo $script;exit;
   	}
   	
   	public function courseAction(){
   		//$this->_helper->viewRenderer->setNoRender();
   		//$this->view->setTerminal(true);
   		$this->_helper->layout->disableLayout();

   	}
}