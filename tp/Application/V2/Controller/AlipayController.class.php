<?php
namespace V2\Controller;
use \V2\Controller\CommonController;

/**
* alipay
*/
class AlipayController extends CommonController
{

	protected  $order_get_html_conf=array(
		"check_user"=>true,
		"check_fields"=>array(
			array("order_id","number",null,1),
		)
	);

	/**
	 * trade info
	 * @return [type]
	 */
	public function order_get_html(){
		$result=M("Orders")->field("order_id,total_amount,status")->where("order_id='".$this->order_id."'")->find();
		if(empty($result))
			parent::failReturn(C("ORDERS_IS_NULL"));
		else{
			$result['status']!=C("ORDERS_STATUS.PAY")&&parent::failReturn(C("ORDER_NOT_ALLOW_PAY"));

			isset($result['total_amount'])&&$result['total_amount']=$result['total_amount']/100;//money divide 100

			//生成签名,以及加上参数
			$data=array(
				"partner"=>C("alipay_config.partner"),
				"seller_id"=>C("alipay_config.seller_id"),
				"out_trade_no"=>$result['order_id'],
				"total_fee"=>$result['total_amount'],
				"notify_url"=>C("alipay_config.notify_url"),
				"service"=>"mobile.securitypay.pay",
				"payment_type"=>"1",
				"_input_charset"=>"utf-8",
				"it_b_pay"=>"30m",
				"show_url"=>"m.alipay.com",
				"subject"=>"口语聊",
				"body"=>"口语聊支付",
				"sign_type"=>"RSA",
			);

			$authorize=new \Org\Authorize(array("self_private_key_path"=>C("alipay_config.self_private_key_path")));
			$result=$authorize->authSign($data);
			
			parent::successReturn(array("param"=>$result));
		}
		
	}

	protected $order_post_html_conf=array();

	public function order_post_html(){
		if(!is_dir(C("LOG_PATH")))
			mkdir(C("LOG_PATH"),0777,true);
		
		//计算得出通知验证结果
		$alipayNotify = new \Org\Alipay\AlipayNotify(C('alipay_config'));
		$verify_result = $alipayNotify->verifyNotify();

		if($verify_result) {
			$data=array();
			switch ($this->request['trade_status']) {
				case 'TRADE_CLOSED':
					$status=C("ORDERS_STATUS.CLOSE");
				break;
				case 'TRADE_SUCCESS':
					$status=C("ORDERS_STATUS.COMMENT");
					$data['paid_time']=time();
				break;//后期改正，涉及到退款问题
				case 'TRADE_FINISHED':
					$data['paid_time']=time();
					$status=C("ORDERS_STATUS.COMMENT");
				break;
				default:$status=false;break;
			}

			if($status){
				$data['status']=$status;
				$data['pay_type']=1;
				$data['paid_amount'] = $this->request['total_fee'];

				$where=array(
					"order_id"=>$this->request['out_trade_no'],
				);
				$result=M("Orders")->where($where)->save($data);

				if($result===false){
					file_put_contents(C("LOG_PATH")."pay.log", time().":update faild\n",FILE_APPEND);
					echo "fail";
					exit();
				}else{
					file_put_contents(C("LOG_PATH")."pay.log", time().":update success\n",FILE_APPEND);
					echo "success";
					exit();
				}
			}
		}else{
			file_put_contents(C("LOG_PATH")."pay.log", time().":verify faild\n",FILE_APPEND);
			echo "fail";
			exit;
		}
	}
}