<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class SeriesController extends CommonController {
	
	// 获取课程接口配置
	protected $list_get_html_conf = array(
		'check_fields' => array(
			array('category_id', '/^(\d,)*\d+$/', 'CATEGORY_ID_IS_INVALID', 1),
			array('recommend', '1', 'RECOMMEND_IS_INVALID', 0, 'equal'),
			array('tag', 'is_string', 'TAG_IS_INVALID', 0, 'function'),
			array('timestamp', '/^\d+$/', 'TIMESTAMP_IS_INVALID'),
			array('page', '/^[1-9][0-9]*$/', 'PAGE_IS_INVALID', 0, 'regex', 1),
			array('listrows', '/^[1-9][0-9]*$/', 'LISTROWS_IS_INVALID', 0, 'regex', 6),
			array('user_id', '/^\d+$/', 'USER_INVALID')
		)
	);
	
	/**
	 * 获取课程接口
	 */
	public function list_get_html() {
		// 检索条件
		if ($this->category_id === '0') {
			$map = array();
			$map['status'] = 1;
		} elseif ($this->category_id === '99') {
			$map = array();
		} else {
			$map['_string'] = "INSTR(CONCAT(',',categoryIds,','),'," . $this->category_id .",')";
			$map['status'] = 1;
		}
		// 是否为推荐课程
		if ($this->recommend) $map['recommend'] = $this->recommend;
		// 标签检索
		if ($this->tag) {
			$tag = explode(',', $this->tag);
			for($i = 0; $i < count($tag); $i++) {
				$query_tag[] = '%' . $tag[$i] . '%';
			}
			$map['tag'] = array('like', $query_tag, 'or');
		}
		// timestamp不为空
		if ($this->timestamp) {
			$timestamp['update_time'] = array('gt', $this->timestamp);
			$timestamp_result = M(CONTROLLER_NAME)->where($timestamp)->find();
			if (empty($timestamp_result)) {
				$this->successReturn();
			} else {
				$map['update_time'] = array('gt', $this->timestamp);
			}
		}
		// 查询的字段
		$field = 'id, chName, enName, pic, tag, intro, level, dirname, categoryIds, update_time, collect_count';
		// 默认按照时间降序排序
		$order = "update_time asc";
		// 实例化
		$model = M(CONTROLLER_NAME);
		$rs = $model->where($map)->field($field)->page($this->page, $this->listrows)->order($order)->select();
		// 查询出错
		if ($rs === false) $this->DResponse(500);
		// 结果集为空
		if ($rs === null) $this->successReturn();
		
		// 判断用户是否收藏该课程
		if ($this->user_id) {
			// 实例化收藏模型
			$collect_model = M('StudentCourse');
			foreach($rs as $key => $val){
				$result['list'][$key]['series_id'] = $val['id'];
				$result['list'][$key]['chinese_name'] = $val['chName'];
				$result['list'][$key]['english_name'] = $val['enName'];
				$result['list'][$key]['category'] = $val['categoryIds'];
				$result['list'][$key]['tag'] = $val['tag'];
				$result['list'][$key]['picture'] = arrayToStr($val['pic'],C('HTTP_DOMAIN').'/Uploads/',$val['dirname']);
				$result['list'][$key]['intro'] = $val['intro'];
				$result['list'][$key]['level'] = $val['level'];
				$result['list'][$key]['collect_count'] = $val['collect_count'];
				$result['list'][$key]['update_time'] = $val['update_time'];
				$is_collect = $collect_model->where(array('sid'=>$this->user_id,'series_id'=>$val['id']))->find();
				$is_collect = empty($is_collect)?'0':'1';
				$result['list'][$key]['is_collect'] = $is_collect;
			}
		} else {
			foreach($rs as $key => $val){
				$result['list'][$key]['series_id'] = $val['id'];
				$result['list'][$key]['chinese_name'] = $val['chName'];
				$result['list'][$key]['english_name'] = $val['enName'];
				$result['list'][$key]['category'] = $val['categoryIds'];
				$result['list'][$key]['tag'] = $val['tag'];
				$result['list'][$key]['picture'] = arrayToStr($val['pic'],C('HTTP_DOMAIN').'/Uploads/',$val['dirname']);
				$result['list'][$key]['intro'] = $val['intro'];
				$result['list'][$key]['level'] = $val['level'];
				$result['list'][$key]['collect_count'] = $val['collect_count'];
				$result['list'][$key]['update_time'] = $val['update_time'];
				$result['list'][$key]['is_collect'] = '0';
			}
		}
		// 记录总数
		$count = $model->where($map)->count();
		$result['total'] = (int)$count;
		// 返回数据
		$this->successReturn($result);
	}
	
	// 课程详情接口配置
	protected $read_get_html_conf = array(
		'check_fields' => array(
			array('user_id', '/^\d+$/', 'USER_INVALID')
		)
	);
	
	/**
	 * 课程详情接口
	 */
	public function read_get_html() {
		// 获取课程ID
		$series_id = $this->request['id'];
		// 获取用户ID
		$user_id = $this->user_id;
		
		// 是否收藏该课程
		if (!empty($user_id)) {
			$map['sid'] = $user_id;
			$map['series_id'] = $series_id;
			// 实例化收藏模型
			$collect_model = M('StudentCourse');
			$is_collect = $collect_model->where($map)->find();
		}
		$is_collect = empty($is_collect)?0:1;
		
		// 实例化课程模型
		$series_model = M(CONTROLLER_NAME);
		$series_info = $series_model->getById($series_id);
		
		// 课程信息不存在
		if (empty($series_info)) {
			$this->successReturn();
		} else {
			$result['series_id'] = $series_info['id'];
			$result['chinese_name'] = $series_info['chName'];
			$result['english_name'] = $series_info['enName'];
			$result['tag'] = $series_info['tag'];
			$result['pic'] = arrayToStr($series_info['pic'],C('HTTP_DOMAIN').'/Uploads/',$series_info['dirname']);
			$result['level'] = $series_info['level'];
			$result['is_collect'] = $is_collect;
			$this->successReturn($result);
		}
	}

	/**
	 * 获取课程分类接口
	 */
	public function category_get_html() {
		// 获取课程分类
		$categories = C('TAG_TYPES');
		$this->successReturn($categories);
	}
}
