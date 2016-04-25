<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class CollectionController extends CommonController {

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
	
}