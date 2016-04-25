<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class FeedbackController extends CommonController
{
	
	/*
	 * 列表处理
	 */
	protected function _processer(&$volist)
	{
		foreach ($volist as $key => &$value) {
			$value['create_time'] = date("Y-m-d H:i:s",$value['create_time']);
			$user_info = M("Users")->where("id='{$value['user_id']}'")->field('name, mobile')->find();
			if (empty($user_info)) {
				$value['name'] = '游客';
				$value['mobile'] = '';
			} else {
				$value['name'] = $user_info['name'];
				$value['mobile'] = $user_info['mobile'];
			}
			$value['file_name'] = M('WapLogs')->getFieldByFeedbackId($value['id'], 'file_name');
			$value['status_show'] = C("FEEDBACK_STATUS.".$value['status']);
		}
	}

	protected $_where_fields = array(
		'eq' => array('status'),
		'bet' => array('create_start_time', 'create_end_time')
	);

	/*
	 * 反馈建议列表
	 */
	public function index()
	{
		// 处理状态
		$this->assign("feedback_status",C("FEEDBACK_STATUS"));
		// 列表过滤
		$this->_list(M('Feedback'), $this->_index_where, $this->_index_param, 'id', 'create_time');
		// 模版显示
		$this->display();
	}

	/*
	 * 审批处理
	 */
	public function approve()
	{
		// 更新数据
		$data['id'] = I('request.id');
		$data['status'] = I('request.status');
		$data['update_time'] = time();
		$feedback_model = M('Feedback');
		if($feedback_model->save($data)) {
			$this->ajaxReturn(make_url_rtn('操作成功!'));
		} else {
			$this->ajaxReturn(make_url_rtn('操作失败!'));
		}
	}

	/*
	 * 日志下载
	 */
	public function download()
	{
		// 获取反馈ID
		$feedback_id = I('get.id');
		// 生成下载路径
		$log_info = M('WapLogs')->where("feedback_id={$feedback_id}")->field('user_id, file_name')->find();
		$log_file = BASE_PATH . "/Uploads/V2/wap_logs/{$log_info['user_id']}/{$log_info['file_name']}";
		if (file_exists($log_file)) {
		    header('Content-Type: application/zip');
		    header('Accept-Ranges: bytes');
		    header('Content-Disposition: attachment; filename="'.basename($log_file).'"');
		    header('Content-Length: ' . filesize($log_file));
		    readfile($log_file);
		}
	}

}