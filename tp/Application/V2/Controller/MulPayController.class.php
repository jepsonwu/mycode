<?php
namespace V2\Controller;

use \V2\Controller\CommonController;
use \V2\Logic\OrderLogic;
use \V2\Logic\CouponLogic;

/**
 * 订单支付详情
 * 处理优惠券
 * 多种方式支付接口 ping++实现
 */
class MulPayController extends CommonController
{
	protected $order_get_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("order_id", "number", null, 1),
			array("user_coupon_id", "number", null, 0,),
			array("channel", "alipay_wap,alipay", null, 1, "in")
		)
	);

	/**
	 * 获取订单支付详情
	 */
	public function order_get_html()
	{
		$return = "";
		$model = M("Orders");
		$result = $model->field("order_id,total_amount,status")->where("order_id='" . $this->order_id . "'")->find();

		//判断订单是否可以支付
		OrderLogic::order_allow_event($result, "pay");
		defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

		//如果有使用优惠券
		$is_coupon = false;
		$is_pay = true;

		if ($this->user_coupon_id) {
			//获取金额
			$coupon_amount = CouponLogic::coupon_available($this->user_coupon_id);
			defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

			//优惠券金额大于等于支付金额，则直接支付成功
			if ($coupon_amount >= $result['total_amount']) {
				$is_pay = false;
				$model->startTrans();

				//这里填的优惠券金额是订单总金额，不是优惠券的总金额
				$or_res = OrderLogic::order_pay($this->order_id, array("paid_amount" => 0, "coupon_amount" => $result['total_amount']));
				defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));
				if ($or_res) {
					//修改优惠券使用状态
					$cou_res = CouponLogic::coupon_quick($this->user_coupon_id, $this->order_id);
					defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

					if ($cou_res)
						$model->commit();
					else
						$model->rollback();
				}
			} else {
				$is_coupon = true;
				$result['total_amount'] -= $coupon_amount;
			}
		}

		//需要支付金额
		if ($is_pay)
			$return = $this->ping_getinfo($this->channel, $result);

		//修改用户优惠券相关信息
		if ($is_coupon) {
			CouponLogic::coupon_lock($this->order_id, $this->user_coupon_id);
			defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));
		}

		parent::successReturn($return);
	}

	protected $sync_notify_put_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("order_id", "number", null, 1),
			array("pay_result", "1,0", null, 0, "in", "1"),
		)
	);

	/**
	 * todo 签名 保证安全性 签名如果已经使用  利马请求接口改为未使用
	 * 订单支付同步通知接口
	 * 现阶段主要用于优惠券功能使用
	 */
	public function sync_notify_put_html()
	{
		//校验订单为待支付 不能判断 因为异步接口可能会先请求改掉状太
		//这个接口必须要做rsa签名认证
//		OrderLogic::order_allow_event($this->order_id, "pay");
//		defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

		//修改订单状态  暂时由异步处理

		//修改优惠券使用状态
		CouponLogic::coupon_used($this->order_id, $this->pay_result);
		defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

		parent::successReturn();
	}

	protected $notify_post_html_conf = array();

	/**
	 * 回调接口
	 */
	public function notify_post_html()
	{
		if (!isset($_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE']))
			http_response_code(500);

		//计算得出通知验证结果
		$this->request = file_get_contents('php://input');
		$sign = $_SERVER['HTTP_X_PINGPLUSPLUS_SIGNATURE'];

		$verify_result = \Org\Authorize\RsaAuthorize::rsaVerify($this->request,
			C("PINGPP.RSA_PUBLIC_KEY_PATH"), $sign, OPENSSL_ALGO_SHA256);

		if ($verify_result) {
			$this->request = json_decode($this->request, true);
			$type = $this->request['type'];
			$request = $this->request['data']['object'];

			$result = false;

			switch ($type) {
				case 'charge.succeeded':
					//更该优惠券使用状态,使用时间，订单号  防止客户端意外终端没有同步更新
					$info['paid_amount'] = $request['amount'];
					$coupon_amount = M("UserCoupon")->where("order_id='{$request['order_no']}'")->getField("amount");
					if ($coupon_amount && $coupon_amount > 0)
						$info['coupon_amount'] = $coupon_amount;

					$result = OrderLogic::order_pay($request['order_no'], $info);
					//不做这件事
//					CouponLogic::coupon_used($this->order_id);
//					defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));
					break;
			}

			http_response_code($result ? 200 : 500);
		} else {
			order_log("", false, "verify faild," . $this->request . "\n" . $sign);
			http_response_code(500);
		}
	}

	/**
	 * Ping++获取支付详情
	 * @param $channel
	 * @param $order_info
	 * @return \Pingpp\Charge|string
	 */
	protected function ping_getinfo($channel, $order_info)
	{
		$return = "";

		//ping++附加参数
		$extra = array();
		switch ($channel) {
			case 'alipay_wap':
				$extra = array(
					'success_url' => C("PINGPP.NOTIFY_URL"),
				);
				break;
		}

		\Pingpp\Pingpp::setApiKey(C("PINGPP.APP_KEY"));
		try {
			$return = \Pingpp\Charge::create(
				array(
					'subject' => C("PINGPP.SUBJECT"),
					'body' => C("PINGPP.BODY"),
					'amount' => $order_info['total_amount'],
					'order_no' => $order_info['order_id'],
					'currency' => 'cny',
					'extra' => $extra,
					'channel' => $channel,
					'client_ip' => $_SERVER['REMOTE_ADDR'],
					'app' => array('id' => C("PINGPP.APP_ID"))
				)
			);

			$return = json_decode($return, true);
		} catch (\Pingpp\Error\Base $e) {
			order_log($order_info['order_id'], false, $e->getHttpBody());
			$this->DResponse(500);
		}

		return $return;
	}
}