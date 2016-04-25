<?php
namespace V3\Controller;

use V3\Controller\CommonController;

class DemosController extends CommonController {

	Public function read_get_html(){
		$demo_id = $_GET['id'];

		$demo = M('Demos')->where('id = %d', $demo_id)->find();
		if ( empty($demo) ) {
			$this->successReturn();
		}
		$series_dir_name = M('Series')->where('id = %d', $demo['series_id'])->getField('dirname');
		$frame = M('FrameDetail')->join('LEFT join ft_exercise_detail ON ft_frame_detail.id=ft_exercise_detail.sort')->where('ft_frame_detail.type in (1,3) AND ft_frame_detail.demo_id = %d', $demo_id)->order('(ft_frame_detail.time+0)')->field('ft_frame_detail.time,ft_frame_detail.type,ft_frame_detail.picture,ft_frame_detail.title,ft_frame_detail.intro,ft_exercise_detail.type etype,ft_exercise_detail.label,ft_exercise_detail.guide,ft_exercise_detail.audio,ft_exercise_detail.audios,ft_exercise_detail.pic,ft_exercise_detail.classifications,ft_exercise_detail.contents,ft_exercise_detail.explanation,ft_exercise_detail.feedbacks,ft_exercise_detail.answer,ft_exercise_detail.enContent,ft_exercise_detail.chContent,ft_exercise_detail.content')->select();
		foreach ($frame as $key => $value) {
			$frame_tmp['time']=$value['time'];
			$frame_tmp['type']=$value['type'];
			$frame_tmp['picture']=__ROOT__.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'/'.$value['picture'];
			$frame_tmp['title']=htmlspecialchars_decode($value['title']);
			$frame_tmp['intro']=htmlspecialchars_decode($value['intro']);
			// type=1时，有道词典单词解释
			if ($value['type'] == 1) {
				$frame_tmp['meaning'] = getYoudaoMeaning($value['title']);
			}
			if (is_null($frame_tmp['meaning'])) {
				unset($frame_tmp['meaning']);
			}
			$frames[]=$frame_tmp;


		}
		$demo_json = array(
			"id" => $demo['id'],
			"audio" => __ROOT__.'/Uploads/'.$series_dir_name.'/'.$demo['dirname'].'/'.$demo['audio'],
			"frames" => $frames
		);

		$this->successReturn($demo_json);
	}

	// 获取指定课程下的小课配置
	protected $list_get_html_conf = array(
		'check_fields' => array(
			array('series_id', '/^\d+$/', 'SERIES_ID_IS_INVALID', 1),
			array('timestamp', '/^\d+$/', 'TIMESTAMP_IS_INVALID')
		)
	);
	
	/**
	 * 获取指定课程下的小课
	 */
	Public function list_get_html(){
		// 实例化课程模型
		$series_model = M('Series');
		$series_dirname = $series_model->getFieldById($this->series_id, 'dirname');
		// 课程不存在
		if (empty($series_dirname)) $this->failReturn(C('SERIES_IS_NOT_EXIST'));
		// timestamp参数存在
		if ($this->timestamp) {
			$map['update_time'] = array('gt', $this->timestamp);
			$result = M(CONTROLLER_NAME)->where($map)->getField('id');
			if (empty($result)) {
				$this->successReturn();
			} else {
				$cond['update_time'] = array('gt', $timestamp);
			}
		}
		// 实例化小课模型
		$demo_model = M(CONTROLLER_NAME);
		$cond['series_id'] = $this->series_id;
		$cond['zip_size'] = array('gt', 0);
		$demo_info = $demo_model->where($cond)->field('id,chName,enName,dirname,zip_size')->select();
		// 查询失败
		if ($demo_info === false) $this->DResponse(500);
		// 无查询结果
		if (empty($demo_info)) $this->successReturn();
		// 返回结果
		foreach ($demo_info as $key => $val) {
			$result['list'][$key]['demo_id'] = $val['id'];
			$result['list'][$key]['chinese_name'] = $val['chName'];
			$result['list'][$key]['english_name'] = htmlspecialchars_decode($val['enName']);
			$result['list'][$key]['resource_path'] = C('HTTP_DOMAIN').'/Uploads/'.$series_dirname.'/'.$val['dirname'].'.zip';
			$result['list'][$key]['zip_size'] = sprintf("%.2f", $val['zip_size']/1024) . 'M';
		}
		// 总记录数
		$count = $demo_info = $demo_model->where($cond)->count();
		$result['total'] = (int)$count;
		$this->successReturn($result);
	}

}
