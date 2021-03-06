<?php
	return array (

		'GENDER' => array ( // 性别
				'1' => '女',
				'2' => '男' 
		),
		
		'GENDER_EN' => array ( // 性别
				'1' => 'Female',
				'2' => 'Male' 
		),
			
		'TAG_TYPES' => array( // 课程分类
				'1' => '影视音乐,Movies&Songs',
				'2' => '生活,Daily Life',
				'3' => '校园,Campus',
				'4' => '职场,Career',
				'5' => '购物,Shopping',
				'6' => '旅游,Travel'
		),

		'EXERCISE_TYPES' => array( // 习题分类
				'1' => '选图',
				'2' => '连线',
				'3' => '分类',
				'4' => '短对话',
				'5' => '造句',
				'6' => '填空',
				'7' => '长对话',
				'8' => '跟读',
		),
		
		// 不良记录类型
		'ADVERSE_RECORD_TYPE' => array(
			1 => array('未接电话', 3), // 不良通话记录及扣款
			2 => array('投诉(未支付)', 3), // 投诉(未支付)及扣款
			3 => array('投诉(已支付)', 3) // 投诉(已支付)及扣款
		),
		
		// 有疑问模板（教师）
		'TEACHER_DOUBT' => array(
			'3' => '投诉学生',
			'4' => '对结算有疑问',
			'5' => '反馈建议',
			'7' => '客服Email'
		),
		// 有疑问模板（学生）
		'STUDENT_DOUBT' => array(
			'0' => array( // 关闭
				'1' => '投诉外教',
				'2' => '对支付有疑问',
				'5' => '反馈建议'
			),
			'1' => array( // 未结算
				'1' => '投诉外教',
				'2' => '对支付有疑问',
				'5' => '反馈建议'
			),
			'2' => array( // 未支付
				'1' => '投诉外教',
				'2' => '对支付有疑问',
				'5' => '反馈建议'
			),
			'3' => array( // 未评价
				'1' => '投诉外教',
				'2' => '对支付有疑问',
				'5' => '反馈建议'
			),
			'4' => array( // 已完结
				'1' => '投诉外教',
				'2' => '对支付有疑问',
				'6' => '我想修改评价',
				'5' => '反馈建议'
			)
		),
		
		// 教学语言
		'TEACHING_LANGUAGE' => array(
			'1' => '英语,English'
		),
		// 教学分类
		'TEACHING_CATEGORY' => array(
			'1' => '日常口语,Daily Spoken English'
		),

		// 优惠劵领取规则
		'COUPON_RECEIVE_RULES' => array(
			'1' => 'newUser'
		),

		// 教师状态
		'TEACHER_STATUS' => array(
			1 => 'online',
			2 => 'busy',
			3 => 'offline'
		),

		// 系统用户信息
		'SYSTEM_USER_INFO' => array(
			'id' => 'system',
			'name' => 'Tollk',
			'avatar' => 'f674634ac4071cf0bee3bbadb6018ffd.png'
		),

		'SYSTEM_DEFAULT_INFO' => array(
			'AVATAR' => '971829797d371836e37008094af45d5f.png'
		),

		// 消息通知
		'NOTIFY_MESSAGE' => array(
			'FIRST_LOGIN' => '首次登录赠送15元'
		),
	);
	
?>
