<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 *日志管理
 */
class WapLogsController extends CommonController
{
	protected $_where_fields = array(
		"eq" => array("user_id"),
		"bet" => array("create_start_time",
			"create_end_time",
		),
	);

	public function index()
	{
		$this->assign("apply_status", C("APPLY_TEACHER_STATUS"));

		$this->_list(M("WapLogs"), $this->_index_where, $this->_index_param, "user_id", "create_time");
		$this->display();
	}

	protected function _processer(&$volist)
	{
		foreach ($volist as &$value) {
			$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['user_name'] = M("Users")->where("id='{$value['user_id']}'")->getField("name");
			$value['down_url'] = create_pic_url("wap_logs", $value['user_id']) . $value['file_name'];
		}
	}

}