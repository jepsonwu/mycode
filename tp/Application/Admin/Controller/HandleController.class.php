<?php

namespace Admin\Controller;

class HandleController extends CommonController {
	
	public function index() {
		$this->display();
	}
	
	
	
	// 生成json文件
	public function generate() {
		$demo_id = 1;
		$demo = M('Demos')->where('id = %d', $demo_id)->find();

		$frames = M('FrameDetail')->field('id,demo_id,sort,create_time,create_id',true)->where('demo_id = %d', $demo_id)->order('sort')->select();
		$exercises = M('ExerciseDetail')->field('id,folder_name,demo_id,sort,create_time,create_id',true)->where('demo_id = %d', $demo_id)->order('sort')->select();
		//
		foreach ($frames as $key => $value) {
			foreach ($value as $k => $val) {
				if ( is_null($val) ) {
					unset( $frames[$key][$k] );
				}
			}
		}
		
		foreach ($exercises as $key => $value) {
			strToArray($exercises[$key]['audios']);
			strToArray($exercises[$key]['contents']);
			strToArray($exercises[$key]['feedbacks']);
			strToArray($exercises[$key]['classifications']);
			foreach ($value as $k => $val) {
				if ( empty($val) ) {
					unset( $exercises[$key][$k] );
				}
			}
		}
		
		$demo = array(
				"pic" => $demo['pic'],
				"topic" => $demo['topic'], //标题
			    "teacher" => $demo['teacher'], //老师
			    "audio" => $demo['audio'], //主音频
			    "intro" => $demo['intro'],
				"frames" => $frames,
				"exercises" => $exercises
		);
		
		$demo = json_encode($demo);
// dump($demo);
		// 中文转码
		$demo = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $demo);
		$demo = str_replace("\/", "/", $demo);
// 		$demo = str_replace("\\n", "\n", $demo);
		echo $demo;
		exit;
		// 写入文件
		$path = BASE_PATH.'\Application\Ft\Files\a.json';
		/* $file = fopen($path, 'w');
		fwrite($file, $demo);
		fclose($file); */
		file_put_contents($path, preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $demo));
	}
	
	
}