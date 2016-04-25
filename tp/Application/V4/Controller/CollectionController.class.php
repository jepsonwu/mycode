<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class CollectionController extends CommonController {

	// 用户类型（教师）
	const USER_TYPE_TEACHER = 1;
	// 用户状态（有效）
	const USER_STATUS_VALID = 1;
	// 教师在线状态（在线）
	const TEACHER_STATUS_ONLINE = 1;
	// 教师在线状态（忙碌）
	const TEACHER_STATUS_BUSY = 2;
	// 教师在线状态（离线）
	const TEACHER_STATUS_OFFLINE = 3;
	// 订单状态
	const ORDER_STATUS_NEW = 1;

	// 获取收藏课程列表配置
	protected $series_get_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 获取收藏课程列表
	 */
	public function series_get_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化课程模型
		$series_model = M('Series s');
		// 查询课程详情
		$series_info = $series_model->join('left join ft_student_course on s.id=ft_student_course.series_id')
			->where("ft_student_course.sid={$user_id} and s.status=1")
			->field('s.id,s.chName,s.enName,s.dirname,s.categoryIds,s.pic,s.level,s.intro,s.collect_count')
			->page($this->page,$this->listrows)->select();
		// 查询失败
		if ($series_info === false) $this->DResponse(500);
		// 查询结果为空
		if (empty($series_info)) $this->successReturn();
		// 返回结果
		foreach ($series_info as $key => $val) {
			$result['list'][$key]['series_id'] = $val['id'];
			$result['list'][$key]['chinese_name'] = $val['chName'];
			$result['list'][$key]['english_name'] = $val['enName'];
			$result['list'][$key]['category'] = $val['categoryIds'];
			$result['list'][$key]['picture'] = arrayToStr($val['pic'],C('HTTP_DOMAIN').'/Uploads/',$val['dirname']);
			$result['list'][$key]['level'] = $val['level'];
			$result['list'][$key]['intro'] = $val['intro'];
			$result['list'][$key]['collect_count'] = $val['collect_count'];
		}
		// 记录总数
		$count = $series_model->join('left join ft_student_course on s.id=ft_student_course.series_id')
			->where("ft_student_course.sid={$user_id}")->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	// 收藏课程配置
	protected $series_post_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('series_id', '/^\d+$/', 'SERIES_ID_IS_INVALID', 1)
		)
	);
	
	/**
	 * 收藏课程
	 */
	public function series_post_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 获取series_id
		$series_id = $this->series_id;
		// SQL条件
		$map['sid'] = $user_id;
		$map['series_id'] = $series_id;
		
		// 实例化收藏模型
		$collect_model = M('StudentCourse');
		$result = $collect_model->where($map)->find();
		// 查询失败
		if ($result === false) $this->DResponse(500);
		// 已收藏
		if (!empty($result)) {
			$this->failReturn(C('SERIES_IS_ALREADY_COLLECTED'));
		} else {
			$insert_result = $collect_model->add($map);
			// 插入失败
			if ($insert_result === false) $this->DResponse(500);
			// 实例化课程模型
			$series_model = M('Series');
			$series_result = $series_model->where('id='.$series_id)->setInc('collect_count', 1);
			$this->successReturn();
		}
	}
	
	// 取消收藏课程配置
	protected $series_delete_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('series_id', '/^\d+$/', 'SERIES_ID_IS_INVALID', 1)
		)
	);
	
	/**
	 * 取消收藏课程
	 */
	public function series_delete_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 获取series_id
		$series_id = $this->series_id;
		$map['sid'] = $user_id;
		$map['series_id'] = $series_id;
		
		// 实例化收藏模型
		$collect_model = M('StudentCourse');
		$id = $collect_model->where($map)->getField('id');
		// 未收藏
		if (empty($id)) {
			$this->failReturn(C('SERIES_IS_NOT_COLLECTED'));
		} else {
			$result = $collect_model->where('id=' . $id)->delete();
			// 删除失败
			if ($result === false) $this->DResponse(500);
			// 实例化课程模型
			$series_model = M('Series');
			$series_result = $series_model->where('id='.$series_id)->setDec('collect_count', 1);
			$this->successReturn();
		}
	}
	
	// 获取收藏知识点列表配置
	protected $knowledge_get_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6)
		)
	);
	
	/**
	 * 获取收藏知识点列表
	 */
	public function knowledge_get_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 实例化知识点模型
		$knowledge_model = M('FrameDetail f');
		$knowledge_info = $knowledge_model->join('left join ft_student_knowledge sk on f.id=sk.kid')
			->join('left join ft_knowledges k on f.id=k.knowledge_id')
			->where("sk.sid={$user_id}")->field('f.id,f.time,f.picture,f.title,f.intro,k.meaning')
			->page($this->page,$this->listrows)->select();
		// 查询失败
		if ($knowledge_info === false) $this->DResponse(500);
		// 查询结果为空
		if (empty($knowledge_info)) $this->successReturn();
		// 遍历结果集，去掉meaning为null的数据
		foreach ($knowledge_info as $key => $val) {
			if (empty($val['meaning'])) {
				unset($knowledge_info[$key]['meaning']);
			}
		}
		// 返回结果
		$result['list'] = $knowledge_info;
		// 记录总数
		$count = $knowledge_model->join('left join ft_student_knowledge on f.id=ft_student_knowledge.kid')
			->where('ft_student_knowledge.sid='.$user_id)->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}
	
	// 收藏知识点配置
	protected $knowledge_post_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('knowledge_id', '/^\d+$/', 'KNOWLEDGE_ID_IS_INVALID', 1)
		)
	);
	
	/**
	 * 收藏知识点
	 */
	public function knowledge_post_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 获取knowledge_id
		$knowledge_id = $this->knowledge_id;
		$map['sid'] = $user_id;
		$map['kid'] = $knowledge_id;
		
		// 实例化收藏知识点模型
		$collect_model = M('StudentKnowledge');
		$result = $collect_model->where($map)->find();
		// 查询失败
		if ($result === false) $this->DResponse(500);
		// 已收藏
		if (!empty($result)) {
			$this->failReturn(C('KNOWLEDGE_IS_ALREADY_COLLECTED'));
		} else {
			$insert_result = $collect_model->add($map);
			// 插入失败
			if ($insert_result === false) $this->DResponse(500);
			// 收藏成功
			$this->successReturn();
		}
	}
	
	// 取消收藏知识点配置
	protected $knowledge_delete_html_conf=array(
		'check_user' => true,
		'check_fields' => array(
			array('knowledge_id', '/^\d+$/', 'KNOWLEDGE_ID_IS_INVALID', 1)
		)
	);
	
	/**
	 * 取消收藏知识点
	 */
	public function knowledge_delete_html() {
		// 获取用户ID
		$user_id = USER_ID;
		// 获取knowledge_id
		$knowledge_id = $this->knowledge_id;
		$map['sid'] = $user_id;
		$map['kid'] = $knowledge_id;
		
		// 实例化收藏知识点模型
		$collect_model = M('StudentKnowledge');
		$id = $collect_model->where($map)->getField('id');
		// 查询失败
		if ($id === false) $this->DResponse(500);
		// 未收藏
		if (empty($id)) {
			$this->failReturn(C('KNOWLEDGE_IS_NOT_COLLECTED'));
		} else {
			$result = $collect_model->where('id=' . $id)->delete();
			// 删除失败
			if ($result === false) $this->DResponse(500);
			// 取消成功
			$this->successReturn();
		}
	}

	// 收藏教师配置
	protected $teacher_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('tid', '/^\d+$/', 'TEACHER_ID_IS_INVALID', 1)
		)
	);

	/**
	 * 收藏教师
	 */
	public function teacher_post_html()
	{
		// 获取用户ID与教师ID
		$user_id = USER_ID;
		$tid = $this->tid;

		// 验证教师有效性
		$check_result = self::checkTeacherValidity($tid);
		// 教师不存在
		if (!$check_result) $this->failReturn(C('TEACHER_IS_NOT_EXIST'));

		// 获取教师收藏ID
		$collect_id = self::getTeacherCollectId($user_id, $tid);
		// 教师已收藏
		if ($collect_id) $this->failReturn(C('TEACHER_IS_ALREADY_COLLECTED'));

		// 收藏教师
		$collect_map = array(
			'sid' => $user_id,
			'tid' => $tid
		);
		$collect_result = M('StudentTeacher')->add($collect_map);
		// 收藏失败
		if ($collect_result === false) $this->failReturn(C('TEACHER_COLLECTION_FAILED'));
		// 收藏成功
		$this->successReturn();
	}

	// 取消收藏教师配置
	protected $teacher_delete_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('tid', '/^\d+$/', 'TEACHER_ID_IS_INVALID', 1)
		)
	);

	/**
	 * 取消收藏教师
	 */
	public function teacher_delete_html()
	{
		// 获取用户ID与教师ID
		$user_id = USER_ID;
		$tid = $this->tid;

		// 验证教师有效性
		$check_result = self::checkTeacherValidity($tid);
		// 教师不存在
		if (!$check_result) $this->failReturn(C('TEACHER_IS_NOT_EXIST'));

		// 获取教师收藏ID
		$collect_id = self::getTeacherCollectId($user_id, $tid);
		// 教师已收藏
		if (!$collect_id) $this->failReturn(C('TEACHER_IS_NOT_COLLECTED'));

		// 取消收藏
		$collect_result = M('StudentTeacher')->where("id={$collect_id}")->delete();
		// 删除失败
		if ($collect_result === false) $this->failReturn(C('CANCEL_TEACHER_COLLECTION_FAILED'));
		// 取消收藏成功
		$this->successReturn();
	}

	// 收藏教师列表配置
	protected $teacher_get_html_conf = array(
		'check_user' => true
	);

	/**
	 * 收藏教师列表
	 */
	public function teacher_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 初始化收藏教师列表
		$collect_teacher_list = [];
		// 获取用户收藏的教师列表
		$teacher_list = M('StudentTeacher')->where("sid={$user_id}")->getField('tid', true);

		// 教师列表不为空
		if (!empty($teacher_list)) {
			// 获取教师状态列表
			$teacher_status_list = S('teacher_status');

			if ($teacher_status_list) {
				// 初始化状态列表
				$online_list = $busy_list = $offline_list = [];
				// 获取教师各状态的列表
				$teacher_online_list = S('teacher_online_list');
				$teacher_busy_list = S('teacher_busy_list');
				$teacher_offline_list = S('teacher_offline_list');

				// 遍历教师ID列表
				foreach ($teacher_list as $teacher_id) {
					// 获取教师相关信息
					$teacher_info = M('Users')->where("id={$teacher_id}")->field('id,name,gender,avatar,nationality')->find();
					// 教师信息不为空
					if (!empty($teacher_info)) {
						// 获取头像路径
						$teacher_info['avatar'] && $teacher_info['avatar'] = create_pic_url("avatar", $teacher_info['id']) .
							$teacher_info['avatar'];
					} else {
						continue;
					}

					// 获取教师在线状态
					$teacher_info['status'] = $teacher_status_list[$teacher_info['id']];
					// 获取教师通话总时长
					switch ($teacher_info['status']) {
						case self::TEACHER_STATUS_ONLINE:
							$teacher_info['called_time'] = (int)$teacher_online_list[$teacher_info['id']];
							$online_list[] = $teacher_info;
							break;
						case self::TEACHER_STATUS_BUSY:
							$teacher_info['called_time'] = (int)$teacher_busy_list[$teacher_info['id']];
							$busy_list[] = $teacher_info;
							break;
						case self::TEACHER_STATUS_OFFLINE:
							$teacher_info['called_time'] = (int)$teacher_offline_list[$teacher_info['id']];
							$offline_list[] = $teacher_info;
							break;
					}
				}
				// 收藏教师列表
				$teacher_list = array_merge($online_list, $busy_list, $offline_list);
				$collect_teacher_list = array(
					'total' => count($teacher_list),
					'list' => $teacher_list
				);
			}
		}
		// 返回结果
		$this->successReturn($collect_teacher_list);
	}

	// 粉丝列表配置
	protected $fans_get_html_conf = array(
		'check_user' => true,
	);

	/**
	 * 粉丝列表
	 */
	public function fans_get_html()
	{
		// 获取用户ID
		$user_id = USER_ID;
		// 初始化返回结果
		$result = [];

		// 验证教师身份
		$check_result = self::checkTeacherValidity($user_id);
		// 用户不是教师身份
		if (!$check_result) $this->failReturn(C('USER_IS_NOT_A_TEACHER'));

		// 获取粉丝信息
		$fans_list = M('StudentTeacher')->where("tid={$user_id}")->getField('sid', true);
		// 查询结果不为空
		if ($fans_list) {
			// 查询粉丝基本信息
			$fans_map['id'] = array('in', $fans_list);
			$fans_info = M('Users')->where($fans_map)->field('id,name,avatar')->select();

			// 获取头像路径及通话时长
			if ($fans_info) array_walk($fans_info, function(&$val) {
				// 用户头像
				$val['avatar'] && $val['avatar'] = create_pic_url('avatar', $val['id']) . $val['avatar'];
				// 获取通话时长
				$calls_cond = array(
					'sid' => $val['id'],
					'status' => array('gt', self::ORDER_STATUS_NEW)
				);
				$val['learning_time'] = M('Orders')->where($calls_cond)->sum('called_time');
			});
			// 格式化返回结果
			$result = array(
				'total' => count($fans_info),
				'list' => $fans_info
			);
		}

		// 返回结果
		$this->successReturn($result);
	}

	/**
	 * 验证教师有效性
	 * @param  int $tid 教师ID
	 * @return bool     是否有效
	 */
	private function checkTeacherValidity($tid)
	{
		// 验证条件
		$teacher_map = array(
			'id' => $tid,
			'type' => self::USER_TYPE_TEACHER,
			'status' => self::USER_STATUS_VALID
		);
		// 查询结果
		$teacher_result = M('Users')->where($teacher_map)->getField('id');
		$result = !empty($teacher_result) ?: false;
		// 返回结果
		return $result;
	}

	/**
	 * 查看学生是否收藏过指定教师
	 * @param  int $sid 学生ID
	 * @param  int $tid 教师ID
	 * @return int      收藏ID
	 */
	private function getTeacherCollectId($sid, $tid)
	{
		// 查询条件
		$collect_map = array(
			'sid' => $sid,
			'tid' => $tid
		);
		// 查询结果
		$collect_id = M('StudentTeacher')->where($collect_map)->getField('id');
		$result = $collect_id ?: 0;
		// 返回结果
		return $result;
	}
	
}