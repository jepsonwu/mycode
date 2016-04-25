<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

class AdverseRecordController extends CommonController {

	/*
	 * 列表处理
	 */
	protected function _processer(&$volist){
		foreach ($volist as &$value) {
			$value['create_time']=date("Y-m-d H:i:s",$value['create_time']);
			$value['name']=M("Users")->where("id='{$value['tid']}'")->getField("name");
			$value['mobile']=M('Users')->where("id={$value['tid']}")->getField('mobile');
			$value['status_show']=C("ADVERSE_STATUS.".$value['status']);
			$value['type']=C("ADVERSE_TYPE.".$value['type']);
			$value['checkout_date']=date("Y-m-d",$value['checkout_date']);
		}
	}

	protected $_where_fields = array(
		'eq' => array('type'),
		'bet' => array('create_start_time', 'create_end_time')
	);

	/*
	 * 查询过滤
	 */
	protected function _filter() {
		// 执行父类方法
		parent::_filter();
		// 手机号为查询条件
		if (isset($this->_request['mobile']) && $this->_request['mobile']) {
			$tid = M('Users')->getFieldByMobile($this->_request['mobile'], 'id');
			$this->_index_param['mobile'] = $this->_request['mobile'];
			$this->_index_where['tid'] = $tid;
			$this->assign('mobile', $this->_request['mobile']);
		}
	}

	/*
	 * 不良记录列表
	 */
	public function index() {
		// 处理状态
		$this->assign("adverse_type",C("ADVERSE_TYPE"));
		// 列表过滤
		$this->_list(M('AdverseRecord'), $this->_index_where, $this->_index_param, 'id', 'create_time');
		// 模版显示
		$this->display();
	}

	/*
	 * 审批处理
	 */
	public function approve() {
		// 更新数据
		$data['id'] = I('request.id');
		$data['status'] = I('request.status');
		$data['update_time'] = time();
		$feedback_model = M('AdverseRecord');
		if($feedback_model->save($data)) {
			$this->ajaxReturn(make_url_rtn('操作成功!'));
		} else {
			$this->ajaxReturn(make_url_rtn('操作失败!'));
		}
	}

}