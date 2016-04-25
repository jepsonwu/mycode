<?php
namespace V4\Controller;

use V4\Controller\CommonController;

class ComplainController extends CommonController {
	
	// 投诉配置
	protected $complain_post_html_conf = array(
		'check_user' => true,
		'check_fields' => array(
			array('order_id', '/^\d+$/', 'ORDER_ID_IS_INVALID', 1),
			array('content', 'is_string', 'CONTENT_IS_INVALID', 1, 'function'),
			array('type', '1,2,3,4,5', 'TYPE_IS_INVALID', 1, 'in')
		)
	);
	
	/**
	 * 投诉
	 */
	public function complain_post_html() {
		// 获取请求参数
		$user_id = USER_ID;
		$order_id = $this->order_id;
		$content = htmlspecialchars($this->content, ENT_QUOTES);
		$type = $this->type;
		// 将数据插入数据库
		$map['user_id'] = $user_id;
		$map['order_id'] = $order_id;
		$map['content'] = $content;
		$map['create_time'] = time();
		$map['type'] = $type;
		// 实例化投诉模型
		$complain_model = M(CONTROLLER_NAME);
		$result = $complain_model->add($map);
		if ($result === false) $this->DResponse(500);
		$this->successReturn();
	}
}