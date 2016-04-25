<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
*审批投诉
*/
class ComplainController extends CommonController
{
	//投诉类型
	const COMPLAINT_TYPE_TEACHERS=1;
	const COMPLAINT_TYPE_PAY=2;
	const COMPLAINT_TYPE_STUDENTS=3;
	const COMPLAINT_TYPE_SETTLEMENT=4;

	//不良记录类型
	const ADVERSE_RECORD_TYPE_TEACHER=1;
	const ADVERSE_RECORD_TYPE_COMPLAIN=2;

	protected $_where_fields = array(
		'eq' => array('type', 'status', 'order_id'),
		'bet' => array('create_start_time', 'create_end_time')
	);

	public function index(){
		$this->assign("com_status", C("APPROVE_STATUS"));
		$this->assign("types", C("COMPLAINT_TYPE"));
		$this->_list(M("Complain"), $this->_index_where, $this->_index_param, "id", "create_time");
		$this->display();
	}

	protected function _processer(&$volist){
		foreach ($volist as &$value) {
			$value['type']=C("COMPLAINT_TYPE.".$value['type']);
			$value['create_time']=date("Y-m-d H:i:s",$value['create_time']);
			$value['name']=M("Users")->where("id='{$value['user_id']}'")->getField("name");
			$value['mobile']=M('Users')->where("id={$value['user_id']}")->getField('mobile');
			$value['status_show']=C("APPROVE_STATUS.".$value['status']);
		}
	}

	/**
	 * 审批。学生投诉老师，审批成功则添加不良记录
	 * @return [type] [description]
	 */
	public function approve(){
		$info=I("get.");
		//查找不良记录信息
		$model=M("Complain");
		$com_info=$model->where("id='{$info['id']}'")->field("order_id,type")->find();
		if(!$com_info)
			$this->ajaxReturn ( make_rtn ( '没有找到记录!' ) );
		
		$model->startTrans();

		//修改状态
		$complain_date = array(
			'status' => $info['status'],
			'note' => $info['note'],
			'update_time' => time()
		);
		$result=$model->where("id='{$info['id']}'")->save($complain_date);
		if($result!==false){
			//插入不良记录
			if($com_info['type']==self::COMPLAINT_TYPE_TEACHERS&&$info['status']==2){
				$tid=M("Orders")->where("order_id='{$com_info['order_id']}'")->getField("tid");

				$data=array(
					"create_time"=>time(),
					"type"=>self::ADVERSE_RECORD_TYPE_COMPLAIN,
					"update_time"=>time(),
					"checkout_date"=>strtotime("next sunday"),
					"tid"=>$tid,
				);
				$result=M("AdverseRecord")->add($data);
			}else{
				$result=true;
			}

			if($result!==false){
				$model->commit();
				$this->ajaxReturn ( make_url_rtn ( '审核成功!' ) );
			}
		}

		$model->rollback();
		$this->ajaxReturn ( make_rtn ( '审核失败!' ) );
	}
}