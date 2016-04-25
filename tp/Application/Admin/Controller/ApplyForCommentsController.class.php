<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class ApplyForCommentsController extends CommonController {
	
	/*
	 * 列表处理
	 */
	protected function _processer(&$volist){
		foreach ($volist as &$value) {
			$value['create_time']=date("Y-m-d H:i:s",$value['create_time']);
			$value['name']=M("Users")->where("id='{$value['user_id']}'")->getField("name");
			$value['mobile']=M('Users')->where("id={$value['user_id']}")->getField('mobile');
			$value['status_show']=C("APPROVE_STATUS.".$value['status']);
		}
	}

	protected $_where_fields = array(
		'eq' => array('status', 'order_id'),
		'bet' => array('create_start_time', 'create_end_time')
	);

	/*
	 * 申请修改评价列表
	 */
	public function index() {
		// 处理状态
		$this->assign("approve_status",C("APPROVE_STATUS"));
		// 列表过滤
		$this->_list(M('ApplyForComments'), $this->_index_where, $this->_index_param, 'id', 'create_time');
		// 模版显示
		$this->display();
	}

	/*
	 * 处理申请
	 */
	public function approve() {
		// 请求信息
		$request = I('get.');
		// 查询申请信息是否存在
		$model = M('ApplyForComments');
		$apply_info = $model->where("id={$request['id']}")->field('order_id,content')->find();
		if (empty($apply_info)) $this->ajaxReturn(make_rtn('该申请不存在!'));
		// 更新申请信息
		$apply_data = array(
			'status' => $request['status'],
			'note' => $request['note'],
			'update_time' => time()
		);
		// 开启事务
		$model->startTrans();
		$apply_result = $model->where("id={$request['id']}")->save($apply_data);
		// 更新失败
		if ($apply_result === false) $this->ajaxReturn(make_rtn('处理失败!'));
		
		// 处理申请
		if ($request['status'] == 2) {
			// 查询该评价是否存在
			$comment_id = M('TeacherComments')->getFieldByOrderId($apply_info['order_id'], 'id');
			if (empty($comment_id)) $this->ajaxReturn(make_rtn('该评论不存在!'));
			// 修改评价
			$comment_data = array(
				'content' => $apply_info['content'],
				'update_time' => time()
			);
			$comment_result = M('TeacherComments')->where("order_id={$apply_info['order_id']}")->save($comment_data);
			// 返回结果
			if ($comment_result === false) {
				// 回滚
				$model->rollback();
				$this->ajaxReturn(make_rtn('评论修改失败!'));
			} else {
				// 提交
				$model->commit();
			}
		}
		
		// 处理成功
		$this->ajaxReturn(make_url_rtn('处理成功!'));
	}

}