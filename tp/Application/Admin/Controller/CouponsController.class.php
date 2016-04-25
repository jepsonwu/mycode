<?php
namespace Admin\Controller;

use \Admin\Controller\CommonController;
use \Org\Util\String;
use \V2\Logic\CouponLogic;

/**
 * 优惠券管理
 */
class CouponsController extends CommonController
{
	//优惠券类型
	const COUPON_TYPE_USER = 1;
	const COUPON_TYPE_ADMIN = 2;
	const COUPON_TYPE_BOTH = 3;

	//优惠券状态
	const COUPON_STATUS_TRUE = 1;
	const COUPON_STATUS_CLOSE = 2;
	const COUPON_STATUS_FALSE = 3;

	protected $_where_fields = array(
		"eq" => array("discount_code", "status", "type"),
		"bet" => array("create_start_time",
			"create_end_time",
		),
		"like" => array("name"),
	);

	public function index()
	{
		$this->assign("types", C("COUPONS_TYPES"));
		$this->assign("coupons_status", C("COUPONS_STATUS"));

		$this->_list(M("Coupons"), $this->_index_where, $this->_index_param, "id", "create_time");

		$this->display();
	}

	public function _processer(&$volist)
	{
		foreach ($volist as &$value) {
			$value['type_show'] = C("COUPONS_TYPES." . $value['type']);
			$value['rule'] = C("COUPONS_RULES." . $value['rule']);
			$value['fixed_period'] = C("COUPONS_PERIOD." . $value['fixed_period']);
			$value['status_show'] = C("COUPONS_STATUS." . $value['status']);
			$value['priority'] = C("COUPONS_PRIORITY." . $value['priority']);
			$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['update_time'] = date("Y-m-d H:i:s", $value['update_time']);
			$value['start_time'] = date("Y-m-d H:i:s", $value['start_time']);
			$value['amount'] = $value['amount'] / 100;
		}
	}

	public function add()
	{
		//$this->assign("types", C("COUPONS_TYPES"));
		$this->assign("coupons_status", C("COUPONS_STATUS"));
		$this->assign("coupons_rules", C("COUPONS_RULES"));
		$this->assign("conpons_priority", C("COUPONS_PRIORITY"));
		$this->assign("coupons_period", C("COUPONS_PERIOD"));

		$this->display();
	}

	public function insert()
	{
		$info = I("post.");
		$model = M("Coupons");

		//判断优惠码长度（16）和唯一性
		mb_strlen($info['discount_code']) > 16 && parent::fReturn("优惠码长度超过16位");
		$res = $model->where("discount_code='{$info['discount_code']}'")->getField("id");
		$res && parent::fReturn("优惠码已经存在");

		//判断有效期是否大于零
		foreach (array("amount" => "优惠金额",
			         "validity" => "有效期",
			         "everyone_limit" => "每人限领次数",
			         "second_limit" => "时间限制") as $key => $value) {
			if (isset($info[$key])) {
				$info[$key] = intval($info[$key]);
				$info[$key] <= 0 && parent::fReturn("{$value}不能为零");
			}
		}

		$data = array(
			"name" => $info['name'],
			"amount" => $info['amount'] * 100,
			"type" => self::COUPON_TYPE_BOTH,
			"intro" => $info['intro'],
			"remark" => $info['remark'],
			"start_time" => strtotime($info['start_time']),
			"validity" => $info['validity'],
			"discount_code" => $info['discount_code'],
			"rule" => isset($info['rule']) ? intval($info['rule']) : "",
			"fixed_period" => intval($info['fixed_period']),
			"total" => isset($info['total']) && intval($info['total']) > 0 ? intval($info['total']) : 0,
			"everyone_limit" => $info['everyone_limit'],
			"second_limit" => $info['second_limit'],
			"priority" => intval($info['priority']),
			"status" => $info['status'],
			"create_time" => time(),
			"update_time" => time(),
		);

		$res = $model->add($data);
		if ($res === false)
			parent::fReturn("，请重新点击插入!");
		else
			parent::sReturn("新增成功!");
	}

	public function edit()
	{
		$this->assign("types", C("COUPONS_TYPES"));
		$this->assign("coupons_status", C("COUPONS_STATUS"));
		$this->assign("coupons_rules", C("COUPONS_RULES"));
		$this->assign("conpons_priority", C("COUPONS_PRIORITY"));

		$id = I("get.id");
		if ($id) {
			$info = M("Coupons")->where("id='{$id}'")->field("name,intro,remark,status,priority,id")->find();
			$this->assign("info", $info);
		}

		$this->display();
	}

	public function save()
	{
		$info = I("post.");

		$data = array(
			"name" => $info['name'],
			"intro" => $info['intro'],
			"remark" => $info['remark'],
			"priority" => isset($info['priority']) ? 2 : 1,
			"status" => $info['status'],
			"update_time" => time(),
		);

		$res = M("Coupons")->where("id='{$info['id']}'")->save($data);
		if ($res === false)
			parent::fReturn("编辑失败!");
		else
			parent::sReturn("编辑成功!");
	}

	/**
	 * 发送优惠券
	 */
	public function give()
	{
		$coupon_id = I("get.id");

		$coupon_info = M("Coupons")->where("id='{$coupon_id}'")->field("discount_code,name,intro")->find();
		$this->assign("coupon_info", $coupon_info);

		$this->display();
	}

	/**
	 * 发放优惠券
	 */
	public function give_out()
	{
		$give_info = I("post.");
		//获取手机号 用户ID
		$user_ids = $mobiles = array();
		if (isset($give_info['mobiles']))
			$mobiles = explode(",", trim(str_replace("，", ",", $give_info['mobiles']), ","));

		count($mobiles) > 1000 && parent::fReturn("每次限发1000");

		$user_ids = M("Users")->where(array("mobile" => array("in", $mobiles)))->field("id,mobile")->select();
		!$user_ids && parent::fReturn("请输入正确的手机号");
		$user_ids = array_column($user_ids, "mobile", "id");

		//todo 导入文件 切了佛的功能

		//发放优惠券
		$total = count($user_ids);
		$succ = $faild = 0;
		$faild_mobile = array();
		foreach ($user_ids as $user_id => $mobile) {
			$result = CouponLogic::exchangeCoupon($user_id, $give_info['discount_code']);
			if ($result) {
				$succ++;
			} else {
				$faild++;
				$faild_mobile[] = $mobile;
			}
		}

		parent::fReturn("操作完成。总数：{$total}，成功：{$succ}，失败：{$faild}，失败号码：" . implode("|", $faild_mobile));
	}
//	public function edit_status(){
//
//	}

	public function create_multi_code()
	{
		$coupon_id = I("get.coupon_id");
		!$coupon_id && parent::fReturn("优惠券ID不存在");

		$model = M("Coupons");
		$coupon_info = $model->where("id='{$coupon_id}'")->field("multi_code")->find();
		!$coupon_info && parent::fReturn("优惠券不存在");
		//$coupon_info['multi_code'] != 3 && parent::fReturn("优惠券码已经生成");

		//注意文件权限，不然不可写，这也是要做个crontab server的必要  权限控制是一大块
		system("nohup php index.php Crontab/Coupons/coupon_code/coupon_id/{$coupon_id} >/dev/null 2>&1 &", $return);
		if ($return === 0) {
			$model->where("id='{$coupon_id}'")->save(array("multi_code" => 2));

			parent::sReturn("生成中，请耐心等待");
		} else {
			parent::fReturn("生成失败");
		}
	}

	public function down_multi_code()
	{
		$coupon_id = I("get.coupon_id");
		!$coupon_id && parent::fReturn("优惠码ID不存在");

		$dir_name = BASE_PATH . "/Uploads/V2/coupon_code/{$coupon_id}/";
		!is_dir($dir_name) && parent::fReturn("没有优惠码");

		//抛出所有优惠码
		$file_names = glob($dir_name . "*.zip");
		!$file_names && parent::fReturn("没有优惠码");

		download_file($dir_name, substr($file_names[0], strrpos($file_names[0], "/") + 1));
	}
}