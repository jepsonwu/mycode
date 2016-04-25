<?php
namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 *审核外教申请
 */
class ApplyTeacherController extends CommonController
{
	protected $_where_fields = array(
		"eq" => array("status", "mobile"),
		"bet" => array("create_start_time",
			"create_end_time",
		),
		"like" => array("name")
	);

	public function index()
	{
		$this->assign("apply_status", C("APPLY_TEACHER_STATUS"));
		$this->_list($this->_index_where, $this->_index_param);
		$this->display();
	}

	protected function _processer(&$volist)
	{
		foreach ($volist as &$value) {
			$value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['status_show'] = C("APPLY_TEACHER_STATUS." . $value['status']);
			$value['avatar'] = create_pic_url("avatar", $value['user_id']) . $user_info['avatar'];
		}
	}

	/**
	 * 列表
	 */
	protected function _list($map, $param)
	{
		// 格式化请求参数
		$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
		$_SESSION [C('SEARCH_PARAMS_STR')] = '';
		foreach ($param as $key => $value) {
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/' . $key . '/' . $value;
		}
		
		// 获取查询条件
		foreach ($map as $key => $value) {
			if ($key == 'name' || $key == 'mobile') {
				$map["u.{$key}"] = $value;
			} else {
				$map["t.{$key}"] = $value;
			}
			unset($map[$key]);
		}

		// 增加查询条件，防止数据不一致
		$map['u.id'] = array('exp', 'is not null');

		// 获取满足条件记录数
		$count = M('TeacherDetail')->alias('t')->join('left join ft_users u on t.user_id=u.id')->where($map)->count();

		// 记录数大于零
		if ($count > 0) {
			// 创建分页对象
			if (!empty ($_REQUEST ['listRows'])) {
				$listRows = $_REQUEST ['listRows'];
				$_SESSION ['bkgd'] ['bg_listRows'] = $listRows;
			} else {
				$listRows = $_SESSION ['bkgd'] ['bg_listRows'];
			}
			$pg = new \Org\Util\Page ($count, $listRows);

			// 分页查询
			$field = array(
				't.user_id' => 'user_id',
				'u.name' => 'name',
				'u.international_code' => 'international_code',
				'u.mobile' => 'mobile',
				'u.avatar' => 'avatar',
				'u.introduce' => 'introduce',
				't.skype' => 'skype',
				't.introduce_audio' => 'introduce_audio',
				't.reason' => 'reason',
				't.status' => 'status',
				't.create_time' => 'create_time'
			);
			$voList = M('TeacherDetail')->alias('t')->join('left join ft_users u on t.user_id=u.id')->where($map)->field($field)
				->limit($pg->firstRow . ',' . $pg->listRows)->order('status, create_time desc')->select();

			// 数据处理
			if (method_exists($this, '_processer')) {
				$this->_processer($voList);
			}

			// 分页跳转的时候保证查询条件
			$param ['p'] = $pg->nowPage;
			$_SESSION [C('SEARCH_PARAMS')] = json_encode($param);
			$_SESSION [C('SEARCH_PARAMS_STR')] .= '/p/' . $pg->nowPage;

			// 分页显示
			$page = $pg->show();
			// 模板赋值显示
			$this->assign('list', $voList);
			$this->assign("page", $page);
		}
	}

	/**
	 * 审核外教申请
	 * @return [type] [description]
	 */
	public function approve()
	{
		//todo 判断参数 这是一个让人有些纠结的问题

		$info = I("get.");
		$model = M("TeacherDetail");
		$teacher_info = $model->where("user_id='{$info['user_id']}'")->find();
		if(empty($teacher_info)){
			parent::fReturn('审核失败!');
		}
		$model->startTrans();
		$data['status'] = $info['status'];//修改数据

		//审核不通过
		if ($info['status'] == "3") {
			!$info['reason'] && parent::fReturn('请填写原因!');

			$data['reason'] = $info['reason'];
			$result = $model->where("user_id='{$info['user_id']}'")->save($data);
		} else {


			$result = $model->where("user_id='{$info['user_id']}'")->save($data);
			$result1 = M("Users")->where("id='{$info['user_id']}'")->save(array("type" => 1));

		}
		//发送通知到socket端
		$body['en_title'] = 'advice audit';
		if ($info['status'] == "3") {
			$body['result'] = 0;
			$body['en_content'] = 'Your application for Tollk Tutor has been rejected due to ' . "'{$info['reason']}'";
		}else{
			$body['result'] = 1;
			$body['en_content'] = "Congratulations~ Your application for Tollk Tutor has been approved, you can now click 'Online' button to receive calls";
		}
		$body['uid'] = $info['user_id'];
		$body['type'] = 1;
		$body['introduce_audio'] = $teacher_info['introduce_audio'];
		$body['audio_time_length'] = $teacher_info['audio_time_length'];
		$body['skype'] = $teacher_info['skype'];
		$result2 = audit_notice($body);
		if ($result !== false && $result1 !== false && $result2['code'] == 200) {
			$model->commit();
			parent::sReturn('审核成功!');
		}else{
			$model->rollback();
			parent::fReturn('审核失败!');
		}
	}
}