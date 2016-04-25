<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class CallsController extends CommonController
{
	
	// 不良记录状态
	const ADVERSE_VALID_STATUS = '1';
	// 教师类型
	const TEACHER_TYPE = '1';
	// 不良记录类型
	const MISSED_CALLS_TYPE = '1';
	// 订单状态
	const ORDER_STATUS_NEW = '1';

	// 通话记录列表配置
	protected $list_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('type', '0,1', 'USER_TYPE_IS_INVALID', 1, 'in'),
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 通话记录列表
	 */
	public function list_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 查询条件
		$map_str = $this->type==='0'?'sid':'tid';
		$map[$map_str] = $user_id;
		$map['status'] = 3;
		// 实例化通话记录模型
		$calls_model = M(CONTROLLER_NAME);
		$calls_info = $calls_model->where($map)->field('sid,tid,begin_time,end_time,called_time,order_id')
			->page($this->page,$this->listrows)->order('begin_time desc')->select();
		// 查询失败
		if ($calls_info === false) $this->DResponse(500);
		// 无通话记录
		if (empty($calls_info)) $this->successReturn();
		// 返回结果
		$result['list'] = $calls_info;
		// 记录总数
		$count = $calls_model->where($map)->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	/**
	 * 通话记录详情
	 */
	public function read_get_html()
	{
		// 获取通话记录ID
		$call_id = I('get.call_id');
		// 实例化通话记录模型
		$calls_model = M(CONTROLLER_NAME);
		$calls_info = $calls_model->getById($call_id);
		// 查询失败
		if ($calls_info === false) {
			$this->DResponse(500);
		}
		// 返回结果
		$this->successReturn($calls_info);
	}
	
	// 不良记录接口配置
	protected $adverse_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 不良记录接口
	 */
	public function adverse_get_html()
	{
		// 获取教师ID
		$teacher_id = USER_ID;
		// 获取不良记录类型
		$adverse_type = C('ADVERSE_RECORD_TYPE');
		// 查询语句
		$query = 'select ';
		for ($i = 1; $i <= count($adverse_type); $i++)
			$query .= 'sum((case type when ' . $i . ' then count else 0 end)) type' . $i . ', ';
		$query .= 'checkout_date ';
		$query .= 'from ';
		$query .= '(select checkout_date,type,count(1) as count ';
		$query .= 'from ft_adverse_record ';
		$query .= 'where tid=' . $teacher_id . ' ';
		$query .= 'and status=' . self::ADVERSE_VALID_STATUS . ' ';
		$query .= 'group by checkout_date,type) adverse ';
		$query .= 'group by checkout_date ';
		$query .= 'limit ' . ($this->page-1)*$this->listrows . ',' . $this->listrows;
		// 获取查询结果
		$model = new \Think\Model();
		$adverse_query = $model->query($query);
		// 查询失败
		if ($adverse_query === false) $this->DResponse(500);
		// 无结果集
		if (empty($adverse_query)) $this->successReturn();
		// 处理结果集
		$adverse_result = array_map(function($val) use ($adverse_type){
			$result['checkout_date'] = $val['checkout_date'];
			for ($i = 1; $i <= count($adverse_type); $i++) {
				$result['cut_payment'] += $val['type' . $i] * $adverse_type[$i][1];
				$detail .= $val['type' . $i] . ',';
			}
			$result['detail'] = rtrim($detail, ',');
			return $result;
		}, $adverse_query);

		// 返回结果
		$result['list'] = $adverse_result;
		// 总记录数
		$count = M('AdverseRecord')->where('tid='.$teacher_id)->count('distinct checkout_date');
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}

	// 获取老师通话记录详情配置
	protected $teacher_get_html_conf = array(
		'check_user' => true
	);

	/**
	 * 获取老师通话记录详情接口
	 */
	public function teacher_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 判断是否为老师
		$type = M('Users')->getFieldById($user_id, 'type');
		if ($type != self::TEACHER_TYPE) $this->failReturn(C('TEACHER_IS_NOT_EXIST'));

		// 获取总通话时长
		$calls_cond['tid'] = $user_id;
		$calls_cond['status'] = array('gt', self::ORDER_STATUS_NEW);
		$total_call_times = M('Orders')->where($calls_cond)->sum('called_time');
		// 查询失败
		if ($total_call_times === false) $this->DResponse(500);
		// 查询结果为空
		if (empty($total_call_times)) $total_call_times = '0';
		// 获取教师状态列表
		$teacher_status_list = S('teacher_status');
		if (isset($teacher_status_list[$user_id])) {
			$status_num = $teacher_status_list[$user_id];
			$teacher_status = C("TEACHER_STATUS.{$status_num}");
			// 获取通话时长列表
			$called_time_list = S("teacher_{$teacher_status}_list");
			$called_time = &$called_time_list[$user_id];
			// 更新缓存
			if ($called_time != $total_call_times) {
				$called_time_list[$user_id] = $total_call_times;
				S("teacher_{$teacher_status}_list", $called_time_list);
			}
		}

		// 获取未接电话次数
		$adverse_cond['tid'] = $user_id;
		$adverse_cond['type'] = self::MISSED_CALLS_TYPE;
		$adverse_cond['status'] = self::ADVERSE_VALID_STATUS;
		$missed_calls = M('AdverseRecord')->where($adverse_cond)->count();
		// 查询失败
		if ($missed_calls === false) $this->DResponse(500);

		// 返回结果
		$result = array(
			'total_call_times' => $total_call_times,
			'missed_calls' => $missed_calls
		);
		$this->successReturn($result);
	}

	// 获取教师未接电话列表接口配置
	protected $missed_get_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);

	/**
	 * 获取教师未接电话列表接口
	 */
	public function missed_get_html()
	{
		// 获取教师ID
		$user_id = USER_ID;
		// 判断是否为老师
		$type = M('Users')->getFieldById($user_id, 'type');
		if ($type != self::TEACHER_TYPE) $this->failReturn(C('TEACHER_IS_NOT_EXIST'));

		// 查询条件
		$cond['tid'] = $user_id;
		$cond['type'] = self::MISSED_CALLS_TYPE;
		$cond['status'] = self::ADVERSE_VALID_STATUS;
		// 获取查询结果
		$missed_calls = M('AdverseRecord')->where($cond)->order('create_time desc')
			->page($this->page, $this->listrows)->getField('create_time', true);
		// 查询失败
		if ($missed_calls === false) $this->DResponse(500);

		// 返回结果
		$count = M('AdverseRecord')->where($cond)->count();
		$result['total'] = (int)$count;
		$result['list'] = $missed_calls;
		$this->successReturn($result);
	}

}