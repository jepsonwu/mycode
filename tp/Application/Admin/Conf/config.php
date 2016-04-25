<?php

return array(

	'SHOW_PAGE_TRACE' => false, // 显示页面记录

	'BACKGROUND_TITLE' => 'CoolChat',

	'USER_AUTH_GATEWAY' => '/Admin/Public/login', // 默认认证网关
	'RBAC_ROLE_TABLE' => 'ft_rbac_role',
	'RBAC_USER_TABLE' => 'ft_rbac_role_user',
	'RBAC_ACCESS_TABLE' => 'ft_rbac_access',
	'RBAC_NODE_TABLE' => 'ft_rbac_node',

	'SEARCH_PARAMS' => 'hpg_search_params',
	'SEARCH_PARAMS_STR' => 'hpg_search_params_str',
	'SEARCH_PARAMS_PREV_STR' => 'hpg_search_params_prev_str',

	'TMPL_PARSE_STRING' => array(
		'__PLUGIN__' => __ROOT__ . '/Plugin', // 定义第三方插件目录
		'__UPLOAD__' => __ROOT__ . '/Uploads', // 定义上传文件目录
	),

	'MENU_TYPES' => array( // 菜单类别
		'1' => '系统管理',
		'2' => '业务管理',
	),
	'TAG_TYPES' => array(//课程分类
		'1' => '影视音乐',
		'2' => '生活',
		'3' => '校园',
		'4' => '职场',
		'5' => '购物',
		'6' => '旅游'
	),
	'EXERCISE_TYPES' => array(//练习题分类
		'1' => '选图',
		'2' => '连线',
		'3' => '分类',
		'4' => '短对话',
		'5' => '造句',
		'6' => '填空',
		'7' => '长对话',
		'8' => '跟读'
	),
	'COM_NODES' => array( // 常用节点
		'index' => '列表',
		'add' => '新增',
		'insert' => '保存新增',
		'edit' => '编辑',
		'update' => '保存编辑',
		'foreverDelete' => '永久删除',
		'sort' => '排序',
		'saveSort' => '保存排序',
		'chgStt' => '更改状态',
		'uploadPic' => '上传图片',
		'uploadAtt' => '上传附件',
		'quickUpdateText' => '快速编辑文本',
		'quickUpdateDate' => '快速编辑日期',
	),

	'STT_JYQY' => array( // 状态
		'-1' => '禁用',
		'1' => '启用'
	),

	'BG_LIST_ROWS' => 30,  // 后台每页记录数

	'YORN' => array(
		'1' => '是',
		'-1' => '否'
	),

	'STT_NEWS' => array(
		'1' => '发布',
		'-1' => '撤销'
	),
	"MESSAGE_TYPE" => array(
		"1" => "学生",
		"2" => "老师",
		"3" => "活动",
		"4" => "系统"
	),
	//投诉类型和状态
	"APPROVE_STATUS" => array(
		"1" => "未处理",
		"2" => "已处理",
		"3" => "已忽略"
	),
	"COMPLAINT_TYPE" => array(
		"1" => "投诉外教",
		"2" => "支付问题",
		"3" => "投诉学生",
		"4" => "结算问题"
	),
	//反馈建议状态
	"FEEDBACK_STATUS" => array(
		"1" => "未处理",
		"2" => "已阅",
		"3" => "无效"
	),
	//申请外教状态
	"APPLY_TEACHER_STATUS" => array(
		"1" => "待审核",
		"2" => "审核成功",
		"3" => "审核失败"
	),
	//申请资料修改状态
	"EDIT_TEACHER_STATUS" => array(
		"1" => "待审核",
		"2" => "已审核",
		"3" => "未通过"
	),
	//订单状态
	"ORDERS_STATUS" => array(
		"0" => "已关闭",
		"1" => "未结算",
		"2" => "未支付",
		"3" => "未评论",
		"4" => "已完结"
	),
	//订单支付类型
	"ORDERS_PAY_TYPE" => array(
		"1" => "支付宝"
	),
	//用户性别
	"USER_GENDER" => array(
		"0" => "男",
		"1" => "女"
	),
	//用户状态
	"USER_STATUS" => array(
		"0" => "未激活",
		"1" => "已激活"
	),
	//用户类型
	"USER_TYPE" => array(
		"0" => "学生",
		"1" => "老师"
	),
	//白名单
	"USER_WHITE" => array(
		"0" => "失效",
		"1" => "有效"
	),
	//优惠券类型
	"COUPONS_TYPES" => array(
		"1" => "用户自领",
		"2" => "后台发放",
		"3" => "均可"
	),
	//优惠券状态
	"COUPONS_STATUS" => array(
		"1" => "有效",
		"2" => "禁止领取",
		"3" => "无效"
	),
	//优惠券额外规则
	"COUPONS_RULES" => array(
		"1" => "限新用户",
	),
	//是否优先使用
	"COUPONS_PRIORITY" => array(
		"1" => "否",
		"2" => "是"
	),
	// 不良记录状态与类型
	'ADVERSE_STATUS' => array(
		'1' => '有效',
		'2' => '无效'
	),
	'ADVERSE_TYPE' => array(
		'1' => '未接电话',
		'2' => '投诉(未支付)',
		'3' => '投诉(已支付)'
	),
	//是否固定期限
	"COUPONS_PERIOD" => array(
		"1" => "否",
		"2" => "是"
	),
);

?>