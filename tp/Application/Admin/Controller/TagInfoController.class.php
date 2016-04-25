<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

class TagInfoController extends CommonController {
	
	/*
	 * 标签管理
	 */
	public function index() {
		// 获取热门标签
		$popular_tag = C('POPULAR_TAG');
		$this->assign('popular_tag', $popular_tag);
		$this->display();
	}
	
	public function edit() {
		// 获取热门标签
		$popular_tag = C('POPULAR_TAG');
		$popular_tag = implode(',', $popular_tag);
		$this->assign('popular_tag', $popular_tag);
		$this->display();
	}
	
	/*
	 * 追加热门标签
	 */
	public function insert() {
		// 获取热门标签
		$popular_tag = I('post.popular_tag');
		$popular_tag_path = C('POPULAR_TAG_PATH');
		
		// 清除热门标签
		if (empty($popular_tag)) {
			file_put_contents($popular_tag_path, '');
			$this->ajaxReturn(make_url_rtn('清除成功!'));
		}
		// 编辑热门标签
		else {
			$popular_tag = explode(',', trim($popular_tag));
			// 打开配置文件
			$handle = fopen($popular_tag_path, 'w');
			if (!$handle) {
				$this->ajaxReturn(make_url_rtn('编辑失败!'));
			}
			$tag_str = '<?php' . PHP_EOL . 'return array (' . PHP_EOL . "'POPULAR_TAG' => "
				. var_export($popular_tag, true) .');';
			fwrite($handle, $tag_str);
			fclose($handle);
			// 编辑成功
			$this->ajaxReturn(make_url_rtn('编辑成功!'));
		}
	}
}