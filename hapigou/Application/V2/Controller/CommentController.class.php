<?php
namespace V2\Controller;

use V2\Controller\CommonController;

class CommentController extends CommonController {
	
	// 获取课程评价接口配置
	protected $list_get_html_conf = array(
		'check_fields' => array(
			array('series_id', '/^\d+$/', 'SERIES_ID_IS_INVALID', 1),
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 获取课程评价接口
	 */
	public function list_get_html() {
		// 实例化课程评价模型
		$comment_model = M('SeriesComments s');
		$comment_info = $comment_model->join('left join ft_users u on s.sid=u.id')
			->field('s.id,u.avatar,u.name,u.mobile,u.id user_id,s.create_time,s.content')->page($this->page,$this->listrows)
			->where('series_id='.$this->series_id.' and u.id is not null')->order('create_time desc')->select();
		// 查询失败
		if ($comment_info === false) $this->DResponse(500);
		// 课程评价信息不存在
		if (empty($comment_info)) $this->successReturn($comment_info);
		// 敏感词过滤
		array_walk($comment_info, function(&$val){
			$val['content'] = filterSensitiveWords($val['content']);
		});
		// 返回结果
		$result['list'] = $comment_info;
		$count = $comment_model->join('left join ft_users u on s.sid=u.id')
			->where('series_id='.$this->series_id)->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	// 评价课程配置
	protected $series_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('series_id', '/^\d+$/', 'SERIES_ID_IS_INVALID', 1),
			array('content', 'is_string', 'CONTENT_IS_INVALID', 1, 'function')
		)
	);
	
	/**
	 * 评价课程
	 */
	public function series_post_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化课程模型
		$series_model = M('Series');
		$series_result = $series_model->getFieldById($this->series_id, 'id');
		// 课程不存在
		if (empty($series_result)) $this->failReturn(C('SERIES_IS_NOT_EXIST'));
		// 实例化用户表
		$users_model = M('Users');
		$users_result = $users_model->getFieldById($user_id, 'id');
		// 用户不存在
		if (empty($users_result)) $this->failReturn(C('USER_IS_NOT_EXIST'));
		// 生成SQL条件
		$map['series_id'] = $this->series_id;
		$map['sid'] = $user_id;
		$map['content'] = $this->content;
		$map['create_time'] = time();
		// 实例化课程评论模型
		$comment_model = M('SeriesComments');
		$insert_result = $comment_model->add($map);
		// 插入失败
		if ($insert_result === false) $this->DResponse(500);
		// 评价成功
		$this->successReturn();
	}
	
	// 获取教师评价配置
	protected $teacher_get_html_conf =array(
		'check_fields' => array(
			array('teacher_id', '/^\d+$/', 'TEACHER_ID_IS_INVALID', 1),
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 获取教师评价
	 */
	public function teacher_get_html() {
		// 实例化教师评价模型
		$comment_model = M('TeacherComments t');
		$comment_info = $comment_model->join('left join ft_users u on t.sid=u.id')
			->field('t.level,t.content,t.create_time,u.id user_id,u.avatar,u.name,u.type')
			->where('tid='.$this->teacher_id.' and u.id is not null')->page($this->page,$this->listrows)
			->order('create_time desc')->select();
		// 查询失败
		if ($comment_info === false) $this->DResponse(500);
		// 无评价信息
		if (empty($comment_info)) $this->successReturn($comment_info);
		// 遍历学生头像
		array_walk($comment_info, function(&$val){
			$val['avatar'] = create_pic_url('avatar', $val['user_id']) . $val['avatar'];
		});
		// 返回结果
		$result['list'] = $comment_info;
		// 记录总数
		$count = $comment_model->join('left join ft_users u on t.sid=u.id')
			->where('tid='.$this->teacher_id.' and u.id is not null')->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	// 评价教师配置
	protected $teacher_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('teacher_id', '/^\d+$/', 'TEACHER_ID_IS_INVALID', 1),
			array('level', '/^[12345]$/', 'LEVEL_IS_INVALID', 1),
			array('order_id', '/^\d+$/', 'ORDER_ID_IS_INVALID', 1),
			array('content', 'is_string', 'CONTENT_IS_INVALID', 0, 'function')
		)
	);
	
	/**
	 * 评价教师
	 */
	public function teacher_post_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化用户表
		$users_model = M('Users');
		$teacher_result = $users_model->getFieldById($this->teacher_id, 'id');
		// 检查是否存在该教师
		if (empty($teacher_result)) $this->failReturn(C('TEACHER_IS_NOT_EXIST'));
		$users_result = $users_model->getFieldById($user_id, 'id');
		// 检查是否存在该用户
		if (empty($users_result)) $this->failReturn(C('USER_IS_NOT_EXIST'));
		// 检查订单状态是否为待评价
		$order_model = M('Orders');
		$order_status = $order_model->getFieldByOrderId($this->order_id, 'status');
		if ($order_status != C('ORDERS_STATUS.COMMENT')) $this->failReturn(C('ORDER_IS_NOT_ALLOWED_TO_COMMENT'));
		// 生成SQL条件
		$map['tid'] = $this->teacher_id;
		$map['sid'] = $user_id;
		$map['order_id'] = $this->order_id;
		$map['level'] = $this->level;
		$map['content'] = $this->content;
		$map['create_time'] = time();
		// 实例化评价教师模型
		$comment_model = M('TeacherComments');
		// 开启事务
		$comment_model->startTrans();
		$insert_result = $comment_model->add($map);
		if ($insert_result === false) {
			// 插入失败
			$this->DResponse(500);
		} else {
			// 更新数据
			$order_data['status'] = C('ORDERS_STATUS.DONE');
			$update_result = $order_model->where("order_id={$this->order_id}")->save($order_data);
			if ($update_result === false) {
				// 回滚
				$comment_model->rollback();
				// 更新失败
				$this->DResponse(500);
			} else {
				// 提交
				$comment_model->commit();
				// 评论成功
				$this->successReturn();
			}
		}
	}
	
	// 申请修改评价配置
	protected $apply_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('order_id', '/^\d+$/', 'ORDER_ID_IS_INVALID', 1),
			array('content', 'is_string', 'CONTENT_IS_INVALID', 1, 'function')
		)
	);

	/**
	 * 申请修改评价
	 */
	public function apply_post_html() {
		// 获取请求参数
		$user_id = USER_ID;
		$order_id = $this->order_id;
		$content = htmlspecialchars($this->content, ENT_QUOTES);
		// 已经申请过修改评价
		$apply_model = M('ApplyForComments');
		$apply_id = $apply_model->getFieldByOrderId($order_id, 'id');
		if (!empty($apply_id)) $this->failReturn(C('HAVE_APPLIED_FOR_COMMENT'));
		// SQL条件
		$map['user_id'] = $user_id;
		$map['order_id'] = $order_id;
		$map['content'] = $content;
		$map['create_time'] = time();
		// 插入数据
		$result = $apply_model->add($map);
		// 插入失败
		if ($result === false) $this->DResponse(500);
		$this->successReturn();
	}

}