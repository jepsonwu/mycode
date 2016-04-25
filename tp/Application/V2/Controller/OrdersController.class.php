<?php
namespace V2\Controller;

use \V2\Controller\CommonController;
use \V2\Logic\OrderLogic;
use \Common\Logic\OrderCacheLogic;

/**
 * trade class
 */
class OrdersController extends CommonController
{
	//订单状态
	const ORDER_STATUS_NEW = 1;
	const ORDER_STATUS_PAY = 2;
	const ORDER_STATUS_COMMENT = 3;
	const ORDER_STATUS_DONE = 4;
	const ORDER_STATUS_CLOSE = 0;

	//每个action都有自定义控制器
	protected $list_get_html_conf = array(
		//是否需要用户授权
		"check_user" => true,
		//是否需要API授权
		// "authorize"=>true,
		//是否请求次数限制
		// "limit_request"=>true,
		//开启多表关联查询 请求接口时，主表的字段必须填
		"join_table" => true,
		//应用级输入参数  包含名称、类型、是否必须、默认值、是否允许为空
		"check_fields" => array(
			//允许查找字段,支持!取反，不填默认全部
			//array("fields","",null,0,""),

			//多表关联，目前只支持LEFT JOIN
			//多表子段分开定义，格式(表定义，支持，默认)：array("[table_name]:fields","field_name:alas_name,[],[]","field_name")
			//表名用_分割，全为小写
			//如果第三个不写会认为是所有，如果第一个字符是!会认为是取反，注意：有别名的情况是写别名
			//参数认证通过之后会已下划线分割表名的方式存储字段数组到$this->_default,例如：$this->_default['orders_fields']
			array("orders:fields", "order_id,total_amount,paid_amount,create_time,paid_time,status,pay_type,sid,tid",
				"order_id,total_amount,status,sid,tid"),
			array("calls:fields", "called_time,begin_time"),
			array("users:fields", "name,gender,avatar,nationality,type"),
			array("status", "0,1,2,3,4", null, 0, "in"),
			array("role", "0,1", null, 0, "in", 0),
			array("page", "number", null, 0, null, 1),
			array("listrows", "number", null, 0, null, 6),
			array("start_time", "number", null, 0),
			array("end_time", "number", null, 0),
		),
	);

	/**
	 * 订单列表
	 */
	public function list_get_html()
	{
		//查询条件
		$where = array();

		//订单类型
		$where[(($this->role == 1) ? 'tid' : 'sid')] = USER_ID;

		//create_time
		$start_time = $this->start_time;
		$end_time = $this->end_time;
		if (!empty($start_time) && empty($end_time)) {
			$where['create_time'] = array('egt', $start_time);
		} elseif (empty($start_time) && !empty($end_time)) {
			$where['create_time'] = array('elt', $end_time);
		} elseif (!empty($start_time) && !empty($end_time)) {
			$where['create_time'] = array('between', $start_time . ',' . $end_time);
		}

		//状态 这里要注意  status为0时候
		$this->status !== "" && $where['status'] = array('in', $this->status);

		//分页查询
		$model = M("Orders");
		$count = $model->where($where)->count(1);

		if ($count) {
			//大于分页
			($this->page - 1) * $this->listrows >= $count && $this->failReturn(C("NOT_PAGE_LIST"));

			//当状态不为空或者为教师时，按时间排序
			//当为学生，排序规则：1.未付款 未评价 其他按时间
			if ($this->status !== "" || $this->role === '1') {
				$result = $model->where($where)->page($this->page, $this->listrows)
					->field($this->_default['orders_fields'])->order("create_time desc")->select();
			}
			// 学生
			else {
				$result = $model->where($where)->page($this->page, $this->listrows)->field($this->_default['orders_fields'])
					->order('field(status,'. self::ORDER_STATUS_COMMENT . ',' . self::ORDER_STATUS_PAY .
					') desc, create_time desc')->select();
			}

			$result = array(
				'total' => $count,
				'list' => $result
			);

			//关联条件
			$order_id = $user_id = array();

			//所有多表关联都分开查找
			if ($result) {
				foreach ($result['list'] as $key => &$value) {
					isset($value['total_amount']) && $value['total_amount'] = $value['total_amount'] / 100;//money divide 100
					isset($value['paid_amount']) && $value['paid_amount'] = $value['paid_amount'] / 100;

					//关联查询
					//如果order_id不唯一，则会覆盖
					isset($value['order_id']) && $order_id[$value['order_id']][] = $key;

					//关联对方的信息
					$t_id = ($this->role == 1) ? "sid" : "tid";
					isset($value[$t_id]) && $user_id[$value[$t_id]][] = $key;

					//记录图片目录ID
					$value['dirname_id'] = $value[$t_id];

					//处理orders表fields为空的情况
//					if ($value_diff)
//						$value = array_diff($this->_default['orders_fields'], $value);
				}
				unset($value);

				//查找子表
				//关联查询格式：array(table_name=>array(join_field,where))
				//todo 支持子表查询条件
				$join_info = array('calls' => array("order_id", $order_id), 'users' => array("id", $user_id));
				parent::_left_join($join_info, $result['list']);

				//todo 子表字段支持callback函数操作
				foreach ($result['list'] as &$value) {
					if (isset($value['avatar']))
						$value['avatar'] = create_pic_url("avatar", $value['dirname_id']) . $value['avatar'];

					unset($value['dirname_id']);
				}
			}
		}

		parent::successReturn($result);
	}

	protected $read_get_html_conf = array(
		"check_user" => true,
		"join_table" => true,
		"check_fields" => array(
			array("orders:fields",
				"order_id,total_amount,paid_amount,create_time,paid_time,status,sid,tid,pay_type",
				"!paid_amount,paid_time"),
			array("calls:fields", "called_time,begin_time"),
			array("users:fields", "name,gender,avatar,nationality,type"),
			array("teacher_comments:fields", "level"),
			array("order_id", "number", null, 1),
			array("role", "0,1", null, 1, "in"),
		)
	);

	/**
	 * 订单详情
	 */
	public function read_get_html()
	{
		$where = "order_id='" . $this->order_id . "'";
		$model = M("Orders");

		$return = $model->field($this->_default['orders_fields'])->where($where)->find();
		if ($return) {
			isset($return['total_amount']) && $return['total_amount'] = $return['total_amount'] / 100;//money divide 100
			isset($return['paid_amount']) && $return['paid_amount'] = $return['paid_amount'] / 100;

			//关联字段
			$order_id[$this->order_id][] = 0;
			$join_id = ($this->role == 1) ? $return['sid'] : $return['tid'];
			$user_id[$join_id][] = 0;

			$join_info = array(
				"calls" => array("order_id", $order_id),
				"users" => array("id", $user_id),
				"teacher_comments" => array("order_id", $order_id),
			);

			$return = array($return);
			parent::_left_join($join_info, $return);
			$return = $return[0];

			if (isset($return['avatar']))
				$return['avatar'] = create_pic_url("avatar", $join_id) . $return['avatar'];
		} else {
			parent::failReturn(C("ORDER_IS_NULL"));
		}

		parent::successReturn($return);
	}

	protected $settlement_post_html_conf = array(
		"check_user" => true,
		"check_fields" => array(
			array("called_time", "number", null, 1),
			array("order_id", "number", null, 1)
		)
	);

	/**
	 * /免费时段/价格、时间自定义时段,随意设定/
	 * 订单结算
	 */
	public function settlement_post_html()
	{
		//订单状态
		OrderLogic::order_allow_event($this->order_id, "new");
		defined("ERROR_CODE") && parent::failReturn(C(ERROR_CODE));

		//获取计价
		$order_price = OrderCacheLogic::billing_get();

		//通话时长
		$called_time = $this->called_time;

		$total_amount = 0;

		//免费阶段
		$called_time -= $order_price['free'];

		//自定义阶段
		if ($called_time > 0) {
			for ($i = 1; $i < count($order_price); $i++) {
				$de_pri =& $order_price["phase_" . $i];

				if (isset($de_pri) && $called_time > 0) {
					if ($called_time > $de_pri[0]) {//大于第一阶段时间
						$total_amount += $de_pri[1] * $de_pri[0] / 60;
					} else {//小于第一阶段时间
						$total_amount += $de_pri[1] * $called_time / 60;
					}

					//减去时间
					$called_time -= $de_pri[0];
				} else {
					break;
				}
			}
		}
		//最后阶段
		if ($called_time > 0) {
			$total_amount += $called_time * $order_price["last"] / 60;
		}

		$model = M("Orders");
		$model->startTrans();

		//结算订单
		$status = ($total_amount > 0) ? C("ORDERS_STATUS.PAY") : C("ORDERS_STATUS.COMMENT");
		$result = $model->where("order_id='" . $this->order_id . "'")->
		save(array(
			"total_amount" => $total_amount,
			"status" => $status,
			"called_time" => $this->called_time
		));

		if ($result !== false) {
			$call_res = M("Calls")->where("order_id='" . $this->order_id . "'")->save(
				array(
					"status" => C("CALL_STATUS.DONE"),
					"called_time" => $this->called_time,
					"update_time" => time(),
				));

			if ($call_res !== false) {
				$model->commit();

				$result = M("Orders")->where("order_id='" . $this->order_id . "'")->
				field("order_id,total_amount,sid,tid")->find();
				isset($result['total_amount']) && $result['total_amount'] = $result['total_amount'] / 100;

				parent::successReturn($result);
			}
		}

		$model->rollback();
		parent::failReturn(C("ORDER_SETTLEMENT_FIELD"));
	}
}