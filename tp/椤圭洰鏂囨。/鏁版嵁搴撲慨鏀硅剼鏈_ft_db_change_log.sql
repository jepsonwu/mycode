/* 
* @Author: Darkerlu
* @Date:   2015-04-29 15:53:24
* @Last Modified by:   Darkerlu
* @Last Modified time: 2015-05-12 14:32:12
*/

-- 2015/04/29 by Darker
alter table `ft_series`
add `categoryId` int(11) NULL COMMENT '分类ID';

alter table `ft_teachers`
add  `voipAccount` varchar(20) NULL COMMENT 'voip子账号',
add  `voipPassword` varchar(10) NULL COMMENT 'voip子密码';

alter table `ft_students`
add  `voipAccount` varchar(20) NULL COMMENT 'voip子账号',
add  `voipPassword` varchar(10) NULL COMMENT 'voip子密码';

-- 2015/05/4 by Darker

alter table `ft_teachers`
add  `subAccountSid` varchar(32) NULL COMMENT '子账户Id',
add  `subToken` varchar(32) NULL COMMENT '子账户的授权令牌';

alter table `ft_students`
add  `subAccountSid` varchar(32) NULL COMMENT '子账户Id',
add  `subToken` varchar(32) NULL COMMENT '子账户的授权令牌';

-- 2015/05/5 by Darker
alter table `ft_series`
change `directory` `enName` varchar(255) NULL COMMENT '英文名',
change `name` `chName` varchar(255) NULL COMMENT '中文名';

-- 2015/05/6 by Darker
alter table `ft_teachers`
change `status`  tinyint(4) NULL DEFAULT '0' COMMENT '状态 0:未上线 1:上线未上课 2:上课中';


-- 2015/05/7 by Darker
alter table `ft_series`
change `categoryId` `categoryIds` varchar(20) DEFAULT NULL COMMENT '分类ID',

-- 2015/05/8 by Luiz
alter table `ft_exercise_detail`
MODIFY COLUMN sort int(11);

-- 2015/05/12 by Darker
alter table `ft_series`
add  `status` int(11) NOT NULL DEFAULT '0' COMMENT '课程状态:0.未上线1.上线';

-- 2015/06/23 by zach
alter table `ft_series`
ADD COLUMN `recommend`  tinyint(1) NULL COMMENT '推荐(1:是 0:否)' AFTER `status`;


-- 2015/07/01 by luiz
ALTER TABLE `ft`.`ft_exercise_detail`
ADD COLUMN `series_id` int(11) NOT NULL COMMENT '大课ID' AFTER `create_time`;

ALTER TABLE `ft`.`ft_demos`
ADD COLUMN `audio_size` int(11) NOT NULL DEFAULT 0 COMMENT '音频大小' AFTER `create_time`;

--2015/07/1 by zach
alter table `ft_series`
ADD COLUMN `level`  tinyint(1) NULL COMMENT '课程难易等级' AFTER `recommend`;

--2015/07/2 by zach
CREATE TABLE `ft_series_comments` (
`id`  int(11) NOT NULL AUTO_INCREMENT COMMENT '评论ID' ,
`topic_id`  int(11) NOT NULL COMMENT '课程ID' ,
`sid`  int(11) NOT NULL COMMENT '学生ID' ,
`content`  varchar(512) NULL COMMENT '评论内容' ,
`create_time`  datetime NULL COMMENT '评论时间' ,
`status`  tinyint(1) NULL COMMENT '评论状态' ,
PRIMARY KEY (`id`)
);

--2015/07/3 by zach
alter table `ft_series_comments`
CHANGE COLUMN `topic_id` `series_id`  int(11) NOT NULL COMMENT '课程ID' AFTER `id`;

alter table `ft_student_course`
CHANGE COLUMN `topicid` `series_id`  int(11) NOT NULL COMMENT '大课程id' AFTER `sid`;

ALTER TABLE `ft_series_comments`
MODIFY COLUMN `create_time` int(11) NULL DEFAULT NULL COMMENT '评论时间' AFTER `content`;

--2015/07/21 by zach
CREATE TABLE `ft_users` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '用户ID' ,
`mobile`  varchar(20) NOT NULL COMMENT '手机号码' ,
`password`  varchar(64) NOT NULL COMMENT '密码' ,
`type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户类型 0:学生 1:老师' ,
`name`  varchar(20) NULL COMMENT '昵称' ,
`gender`  tinyint(1) NULL COMMENT '性别  0:男 1:女' ,
`avatar`  varchar(255) NULL COMMENT '头像' ,
`status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0:未激活 1:已激活' ,
`token`  varchar(255) NULL COMMENT '微信token' ,
`create_time`  int NULL COMMENT '注册时间' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE `ft_user_detail` (
`user_id`  int NOT NULL COMMENT '用户ID' ,
`birth`  date NULL COMMENT '生日' ,
`job`  varchar(255) NULL COMMENT '工作' ,
`mail`  varchar(64) NULL COMMENT '邮箱' ,
`nationality`  varchar(64) NULL COMMENT '国籍' ,
`location`  varchar(255) NULL COMMENT '地区' ,
`introduce`  varchar(255) NULL COMMENT '介绍自己' ,
`is_push`  tinyint(1) NULL DEFAULT 0 COMMENT '是否推送 1:推送 0:不推送' ,
`is_sync`  tinyint(1) NULL COMMENT '是否同步 1:同步  0:不同步' ,
`voip_account`  varchar(20) NULL COMMENT 'voip子账号' ,
`voip_password`  varchar(10) NULL COMMENT 'voip子密码' ,
`sub_account_sid`  varchar(32) NULL COMMENT '子账户Id' ,
`sub_token`  varchar(32) NULL COMMENT '子账户的授权令牌' ,
PRIMARY KEY (`user_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户详情表';

CREATE TABLE `ft_teacher_detail` (
`teacher_id`  int NOT NULL COMMENT '老师ID' ,
`course_count`  int NULL COMMENT '上课次数' ,
PRIMARY KEY (`teacher_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='老师详情表';

CREATE TABLE `ft_comments` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '评论ID' ,
`teacher_id`  int NOT NULL COMMENT '老师ID' ,
`student_id`  int NOT NULL COMMENT '学生ID' ,
`content`  varchar(255) NULL COMMENT '评论内容' ,
`grade`  tinyint(1) NULL COMMENT '评价等级 (0-5)' ,
`create_time`  int NULL COMMENT '评论时间' ,
`status`  tinyint(1) NULL COMMENT '评论状态' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='评论表';

CREATE TABLE `ft_articles` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '文章ID' ,
`title`  varchar(64) NULL COMMENT '文章标题'
`content`  text NULL COMMENT '文章内容' ,
`manager_id`  int NULL COMMENT '管理者ID' ,
`create_time`  int NULL COMMENT '创建时间' ,
`type`  varchar(32) NULL COMMENT '文章类型' ,
PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='文章表';

--2015/07/24 by zach
CREATE TABLE `ft_student_knowledge` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '自增ID' ,
`sid`  int NOT NULL COMMENT '学生ID' ,
`kid`  int NOT NULL COMMENT '知识点ID' ,
PRIMARY KEY (`id`)
);

--2015/07/27 by zach
ALTER TABLE `ft_users`
ADD UNIQUE INDEX `index_mobile` (`mobile`) USING BTREE ;

--2015/07/28 by zach
ALTER TABLE `ft_users`
ADD COLUMN `location`  varchar(255) NULL COMMENT '地区' AFTER `avatar`,
ADD COLUMN `nationality`  varchar(64) NULL COMMENT '国籍' AFTER `location`,
ADD COLUMN `introduce`  varchar(255) NULL COMMENT '介绍自己' AFTER `nationality`;

ALTER TABLE `ft_user_detail`
DROP COLUMN `location`,
DROP COLUMN `nationality`,
DROP COLUMN `introduce`;

--2015/07/29 by zach
ALTER TABLE `ft_users`
MODIFY COLUMN `status`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0:未激活 1:已激活' AFTER `introduce`;

--2015/08/03 by zach
ALTER TABLE `ft_series`
ADD COLUMN `update_time`  int NULL COMMENT '更新时间' AFTER `level`;

ALTER TABLE `ft_demos`
ADD COLUMN `update_time`  int NULL COMMENT '更新时间' AFTER `audio_size`;

--2015/08/04 by zach
CREATE TABLE `ft_complain` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '投诉ID' ,
`user_id`  int NOT NULL COMMENT '用户ID' ,
`orderno`  int NOT NULL COMMENT '订单号' ,
`content`  varchar(255) NULL COMMENT '投诉内容' ,
`create_time`  int NULL COMMENT '创建时间' ,
`type`  tinyint NULL COMMENT '投诉类型 1：投诉外教 2：支付问题 3：投诉学生 4：结算问题' ,
PRIMARY KEY (`id`)
)
COMMENT='投诉表';

--2015/08/04 by jepson 
ALTER TABLE `ft_calls`
ADD COLUMN `orderno`  int(11) NULL COMMENT '订单ID' AFTER `talk_time`;

DROP TABLE IF EXISTS `ft_teacher_detail`;
CREATE TABLE `ft_teacher_detail` (
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `real_name` varchar(100) DEFAULT NULL COMMENT '真实姓名',
  `true_pic` varchar(100) DEFAULT NULL COMMENT '真人照片',
  `cert_pic` varchar(100) DEFAULT NULL COMMENT '证件照',
  `tea_langue` varchar(100) DEFAULT NULL COMMENT '教学语言',
  `tea_category` varchar(100) DEFAULT NULL COMMENT '教学分类',
  `job` varchar(100) DEFAULT NULL COMMENT '职业',
  `reason` varchar(255) DEFAULT NULL COMMENT '审核失败原因',
  `status` tinyint(2) DEFAULT NULL COMMENT '1-待审核，2-审核成功，3-审核失败，',
  `create_time` int(11) DEFAULT NULL COMMENT '申请时间',
  `contact` varchar(30) DEFAULT NULL COMMENT '其它联系方式',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='老师详情表';

--2015/08/05 by jepson
ALTER TABLE `ft_user_detail`
ADD COLUMN `binded_bank_card`  varchar(30) NULL COMMENT '绑定银行卡' AFTER `sub_token`,
ADD COLUMN `bank_name`  varchar(50) NULL COMMENT '开户行' AFTER `binded_bank_card`,
ADD COLUMN `real_name`  varchar(20) NULL COMMENT '真实姓名' AFTER `bank_name`;

ALTER TABLE `ft_orders`
CHANGE COLUMN `pay_amount` `paid_amount`  int(11) NOT NULL DEFAULT 0 COMMENT '实际支付金额' AFTER `total_amount`,
CHANGE COLUMN `pay_time` `paid_time`  int(11) NULL DEFAULT NULL COMMENT '支付时间' AFTER `create_time`;
ALTER TABLE `ft_orders`
CHANGE COLUMN `orderno` `order_id`  int(11) NULL DEFAULT NULL COMMENT '订单流水号' AFTER `id`;


ALTER TABLE `ft_calls`
CHANGE COLUMN `talk_time` `called_time`  int(11) NOT NULL DEFAULT 0 COMMENT '通话时长，单位秒' AFTER `status`,
CHANGE COLUMN `orderno` `order_id`  int(11) NULL DEFAULT NULL COMMENT '订单ID' AFTER `called_time`;

ALTER TABLE `ft_teacher_comments`
CHANGE COLUMN `level` `feedback_level`  int(11) NULL DEFAULT NULL COMMENT '教师水平（1-5分）' AFTER `manner`;

--2015/08/06 by luiz
CREATE TABLE `ft`.`ft_offline_msgs` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键',
	`user_id` int(11) NOT NULL COMMENT '用户ID',
	`type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '类型：1踢人',
	`content` text COMMENT '消息内容',
	`create_time` int(11) COMMENT '创建时间',
	`status` tinyint(2) DEFAULT 1 COMMENT '状态1有效，-1无效',
	`device_id` varchar(32) COMMENT '设备号',
	PRIMARY KEY (`id`)
) COMMENT='';

ALTER TABLE `ft`.`ft_users` ADD COLUMN `device_id` varchar(35) COMMENT '设备号' AFTER `type`;

--2015/08/06 by jepson
ALTER TABLE `ft_teacher_detail`
CHANGE COLUMN `true_pic` `live_photo`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '真人照片' AFTER `real_name`,
CHANGE COLUMN `cert_pic` `certificate_photo`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '证件照' AFTER `live_photo`,
CHANGE COLUMN `tea_langue` `language`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '教学语言' AFTER `certificate_photo`,
CHANGE COLUMN `tea_category` `category`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '教学分类' AFTER `language`,
CHANGE COLUMN `contact` `contact_info`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '其它联系方式' AFTER `create_time`;

--2015/08/07 by zach
ALTER TABLE `ft_teacher_comments`
ADD COLUMN `content`  varchar(512) NULL COMMENT '评论内容' AFTER `feedback_level`,
ADD COLUMN `create_time`  int NULL COMMENT '创建时间' AFTER `content`,
ADD COLUMN `status`  tinyint(1) NULL COMMENT '评论状态' AFTER `create_time`;

--2015/08/07 by luiz
ALTER TABLE `ft`.`ft_calls`
ADD COLUMN `create_time` int(11) COMMENT '创建时间' AFTER `order_id`,
ADD COLUMN `update_time` int(11) COMMENT '更新时间' AFTER `create_time`;

--2015/08/08 by zach
ALTER TABLE `ft_wages`
ADD COLUMN `accumulate_amount`  int NULL COMMENT '累积金额' AFTER `balance_time`;

--2015/08/10 by luiz
ALTER TABLE `ft`.`ft_users` CHANGE COLUMN `token` `wx_token` varchar(255) DEFAULT NULL COMMENT '微信token';
ALTER TABLE `ft`.`ft_users` ADD COLUMN `token` varchar(255) COMMENT '用户token' AFTER `device_id`;

--2015/08/10 by zach
CREATE TABLE `ft_adverse_record` (
`id`  int NOT NULL AUTO_INCREMENT COMMENT '不良记录ID' ,
`tid`  int NOT NULL COMMENT '教师ID' ,
`create_time`  int NULL COMMENT '产生时间' ,
`type`  tinyint NULL COMMENT '不良记录类型 1:教师未接听 2:投诉' ,
`status`  tinyint NULL DEFAULT 1 COMMENT '状态 1:有效 2:无效' ,
`update_time`  timestamp NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更改时间' ,
`checkout_date`  int NULL COMMENT '结账日' ,
PRIMARY KEY (`id`)
)
COMMENT='不良记录表';

--2015/08/12 by zach
ALTER TABLE `ft_wages`
MODIFY COLUMN `status`  tinyint(4) NULL DEFAULT NULL COMMENT '工资状态 1:累积 2:已结算 3:已打款' AFTER `uid`;

ALTER TABLE `ft_series`
ADD COLUMN `collect_count`  int NULL DEFAULT 0 COMMENT '收藏数量' AFTER `update_time`;

--2015/08/13 by zach
ALTER TABLE `ft_teacher_comments`
CHANGE COLUMN `manner` `order_id`  int(11) NOT NULL COMMENT '订单号' AFTER `sid`,
CHANGE COLUMN `feedback_level` `level`  tinyint(1) NULL DEFAULT NULL COMMENT '教师水平（1-5分）' AFTER `order_id`,
ADD COLUMN `update_time`  int NULL COMMENT '更新时间' AFTER `status`;
ADD UNIQUE INDEX `index_order_id` (`order_id`) USING BTREE ;

ALTER TABLE `ft_feedback`
CHANGE COLUMN `sid` `user_id`  int(11) NULL DEFAULT NULL COMMENT '用户ID' AFTER `id`;

--2015/08/24 by zach
ALTER TABLE `ft`.`ft_users` ADD COLUMN `international_code` tinyint COMMENT '国际代码' AFTER `id`;

--2015/08/25 by zach
CREATE TABLE `ft`.`ft_verify_code_history` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `international_code` tinyint COMMENT '国际代码',
  `ip_address` int UNSIGNED COMMENT 'IP地址',
  `mobile` varchar(11) COMMENT '手机号码',
  `achieve_time` int COMMENT '获取时间',
  PRIMARY KEY (`id`),
  INDEX `index_code_mobile` USING HASH (`international_code`, `mobile`) comment '国际代码与手机号码索引'
) COMMENT='获取验证码记录表';

--2015/08/27 by zach
ALTER TABLE `ft`.`ft_demos` CHANGE COLUMN `audio_size` `zip_size` int(11) NOT NULL DEFAULT 0 COMMENT 'zip包大小';

--2015/09/02 by jepson
ALTER TABLE `ft_user_detail`
ADD COLUMN `paypal_account`  varchar(50) NULL DEFAULT '' COMMENT '国外支付账号' AFTER `real_name`;

--2015/09/07 by zach
ALTER TABLE `ft`.`ft_users` DROP INDEX `index_mobile`, ADD INDEX `index_mobile_code` USING HASH (`mobile`, `international_code`) comment '';

--2015/09/08 by zach
CREATE TABLE `ft`.`ft_apply_for_comments` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int NOT NULL COMMENT '用户ID'
  `order_id` int NOT NULL COMMENT '订单号',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '修改评价内容',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1:审批中 2:通过 3:未通过',
  `create_time` int NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE `index_order_id` USING HASH (`order_id`) comment ''
) COMMENT='评价申请表';

ALTER TABLE `ft`.`ft_users` CHANGE COLUMN `status` `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0:未激活 1:已激活';
DROP INDEX `index_mobile_code`, ADD UNIQUE `index_mobile_code` USING BTREE (`mobile`, `international_code`) comment '';

--2015/09/10 by zach
ALTER TABLE `ft`.`ft_users` ADD COLUMN `salt` varchar(64) NOT NULL DEFAULT '' COMMENT '盐' AFTER `international_code`;
ALTER TABLE `ft`.`ft_teacher_comments` CHANGE COLUMN `order_id` `order_id` varchar(20) NOT NULL DEFAULT 0 COMMENT '订单号';
ALTER TABLE `ft`.`ft_complain` CHANGE COLUMN `orderno` `order_id` varchar(20) NOT NULL DEFAULT 0 COMMENT '订单号';
ALTER TABLE `ft`.`ft_apply_for_comments` CHANGE COLUMN `order_id` `order_id` varchar(20) NOT NULL COMMENT '订单号';

--2015/09/14 by jepson
CREATE TABLE `ft_wap_logs` (
`id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
`feedback_id`  int(11) UNSIGNED NOT NULL COMMENT '反馈ID' ,
`file_path`  varchar(100) NULL DEFAULT '' COMMENT '文件绝对路径' ,
`start_time`  int(11) NULL DEFAULT 0 COMMENT '开始时间' ,
`end_time`  int(11) NULL DEFAULT 0 COMMENT '结束时间' ,
`create_time`  int(11) NULL DEFAULT 0 COMMENT '创建时间' ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
COMMENT='日志记录表';

--2015/09/15 by zach
CREATE TABLE `ft`.`ft_interface_info` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `url` varchar(64) NOT NULL COMMENT 'URL',
  `method` varchar(6) NOT NULL COMMENT '请求方式',
  `param` varchar(64) NOT NULL COMMENT '参数列表'，
  `comment` varchar(128) NOT NULL COMMENT '注释',
  `category` varchar(20) NOT NULL COMMENT '资源类别',
  `version` varchar(10) NOT NULL COMMENT '接口版本',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '接口状态 1:有效 2:无效',
  PRIMARY KEY (`id`)
) COMMENT='接口信息表';

--2015/09/16 by zach
ALTER TABLE `ft`.`ft_complain` ADD COLUMN `status` tinyint NOT NULL DEFAULT 1 COMMENT '投诉状态 1:审批中 2:审批成功 3:审批失败' AFTER `type`;

--2015/09/17 by zach
ALTER TABLE `ft`.`ft_interface_info` ADD INDEX `index_category` USING BTREE (`category`) comment '';

--2015/09/18 by jepson
ALTER TABLE `ft_users`
MODIFY COLUMN `introduce`  varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '介绍自己' AFTER `nationality`;

--2015/09/18 by zach
CREATE TABLE `ft`.`ft_knowledges` (
  `knowledge_id` int NOT NULL COMMENT '知识点ID',
  `title` varchar(64) NOT NULL COMMENT '标题',
  `meaning` text NOT NULL COMMENT '翻译',
  PRIMARY KEY (`knowledge_id`)
) COMMENT='知识点表';

--2015/09/22 by zach
ALTER TABLE `ft`.`ft_adverse_record` CHANGE COLUMN `type` `type` tinyint(4) DEFAULT 0 COMMENT '不良记录类型 1:教师未接听 2:投诉(未支付) 3:投诉(已支付)';

--2015/09/22 by jepson
ALTER TABLE `ft_orders`
ADD INDEX `sid_create_time` (`sid`, `create_time`) USING BTREE ;

ALTER TABLE `ft_orders`
ADD INDEX `tid_create_time` (`tid`, `create_time`) USING BTREE ;)

ALTER TABLE `ft_users`
ADD COLUMN `white`  tinyint NULL DEFAULT 0 COMMENT '白名单，0-失效；1-有效' AFTER `salt`;

--2015/09/23 by luiz
ALTER TABLE `ft`.`ft_calls`
ADD COLUMN `s_called_time` int(11) NOT NULL DEFAULT 0 COMMENT '学生通话时长' AFTER `update_time`,
ADD COLUMN `t_called_time` int(11) NOT NULL DEFAULT 0 COMMENT '老师通话时长' AFTER `s_called_time`;

--2015/09/24 by zach
ALTER TABLE `ft`.`ft_wages` CHANGE COLUMN `money` `amount` int(11) DEFAULT 0 COMMENT '金额', CHANGE COLUMN `create_time` `checkout_date` int(11) DEFAULT 0 COMMENT '结帐日';
ALTER TABLE `ft`.`ft_wages` ADD INDEX `index_uid_checkout_date` USING BTREE (`uid`, `checkout_date`) comment '用户ID与结账日索引';

--2015/09/24 by jepson
ALTER TABLE `ft_users`
MODIFY COLUMN `name`  varchar(35) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '昵称' AFTER `password`;

--2015/09/29 by zach
ALTER TABLE `ft`.`ft_complain` CHANGE COLUMN `status` `status` tinyint NOT NULL DEFAULT 1 COMMENT '投诉状态 1:未处理 2:已处理 3:已忽略',
ADD COLUMN `note` varchar(255) NOT NULL COMMENT '处理结果描述' AFTER `status`;

--2015/10/08 by zach
CREATE TABLE `ft`.`ft_coupons` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(20) NOT NULL COMMENT '优惠劵名称',
  `amount` int UNSIGNED NOT NULL COMMENT '优惠金额',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '优惠劵类型 1:用户自领 2:后台发放 3:自领、发放均可',
  `intro` varchar(255) NOT NULL COMMENT '优惠劵简介',
  `start_time` int UNSIGNED NOT NULL COMMENT '开始时间',
  `validity` smallint UNSIGNED NOT NULL COMMENT '有效期',
  `discount_code` varchar(10) NOT NULL COMMENT '优惠码',
  `rule` tinyint UNSIGNED NOT NULL COMMENT '候补规则 1:新人',
  `total` int UNSIGNED NOT NULL COMMENT '优惠劵总数',
  `everyone_limit` tinyint UNSIGNED NOT NULL COMMENT '每人限领次数',
  `second_limit` smallint UNSIGNED NOT NULL COMMENT '时间限制可用',
  `priority` tinyint NOT NULL DEFAULT 1 COMMENT '是否优先使用 1:否 2:是',
  `fixed_period` tinyint NOT NULL DEFAULT 1 COMMENT '是否固定期限 1:否 2:是',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1:有效 2:禁止领取 3:无效',
  `create_time` int UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) COMMENT='优惠券表';

CREATE TABLE `ft`.`ft_user_coupon` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `coupon_id` int UNSIGNED NOT NULL COMMENT '优惠劵ID',
  `order_id` varchar(20) NOT NULL COMMENT '订单号',
  `amount` int UNSIGNED NOT NULL COMMENT '优惠金额',
  `receive_time` int UNSIGNED NOT NULL COMMENT '领取时间',
  `used_time` int UNSIGNED NOT NULL COMMENT '使用时间',
  `start_time` int UNSIGNED NOT NULL COMMENT '开始时间',
  `end_time` int UNSIGNED NOT NULL COMMENT '截止时间',
  `priority` tinyint NOT NULL DEFAULT 1 COMMENT '是否优先使用 1:否 2:是',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '使用状态：1:未使用 2:已使用 3:已锁定',
  PRIMARY KEY (`id`)
) COMMENT='优惠劵使用记录表';

CREATE TABLE `ft`.`ft_coupon_detail` (
  `coupon_id` int UNSIGNED NOT NULL COMMENT '优惠劵ID',
  `receive_count` int UNSIGNED NOT NULL COMMENT '已领取数量',
  CONSTRAINT `coupons_id_coupon_detail_coupon_id` FOREIGN KEY (`coupon_id`) REFERENCES `ft`.`ft_coupons` (`id`)   ON UPDATE NO ACTION ON DELETE NO ACTION
) COMMENT='优惠券详情表';

--2015/10/09 by zach
ALTER TABLE `ft`.`ft_coupons` ADD UNIQUE `index_discount_code` USING BTREE (`discount_code`) comment '优惠码唯一索引';

--2015/10/12 by jepson
ALTER TABLE `ft_orders`
ADD COLUMN `coupon_amount`  int(11) NULL DEFAULT 0 COMMENT '优惠券金额' AFTER `paid_amount`;

--2015/10/14 by jepson
ALTER TABLE `ft_wap_logs`
DROP COLUMN `start_time`,
DROP COLUMN `end_time`,
CHANGE COLUMN `file_path` `file_name`  char(36) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '文件名' AFTER `feedback_id`,
ADD COLUMN `user_id`  int(11) NULL DEFAULT 0 COMMENT '用户ID' AFTER `create_time`;

--2015/10/14 by zach
ALTER TABLE `ft`.`ft_complain` ADD COLUMN `update_time` int UNSIGNED NOT NULL COMMENT '更新时间';
ALTER TABLE `ft`.`ft_apply_for_comments` ADD COLUMN `note` varchar(255) NOT NULL COMMENT '处理结果描述',
ADD COLUMN `update_time` int NOT NULL COMMENT '更新时间';
ALTER TABLE `ft`.`ft_apply_for_comments` CHANGE COLUMN `status` `status` tinyint NOT NULL DEFAULT 1 COMMENT '申请状态 1:未处理 2:已处理 3:已忽略';

--2015/10/16 by jepson
ALTER TABLE `ft_orders`
ADD COLUMN `called_time`  int(11) NULL DEFAULT 0 COMMENT '通话时长' AFTER `pay_type`;

--2015/10/19 by jepson
ALTER TABLE `ft_coupons`
ADD COLUMN `remark`  varchar(100) NOT NULL DEFAULT '' COMMENT '备注' AFTER `create_time`;

--2015/10/20
ALTER TABLE `ft_coupons`
MODIFY COLUMN `discount_code`  varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '优惠码' AFTER `validity`;

--2015/10/19 by zach
ALTER TABLE `ft`.`ft_feedback` ADD COLUMN `update_time` int UNSIGNED NOT NULL COMMENT '更新时间',
ADD COLUMN `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1:未处理 2:已阅 3:无效';

--2015/10/21 by zach
ALTER TABLE `ft`.`ft_series` CHANGE COLUMN `update_time` `update_time` int UNSIGNED NOT NULL COMMENT '更新时间';

--2015/10/22 by jepson
ALTER TABLE `ft_coupons`
ADD COLUMN `multi_code`  tinyint(4) NULL DEFAULT 0 COMMENT '是否多优惠券码。0-否，1-是' AFTER `update_time`;

ALTER TABLE `ft_coupons`
MODIFY COLUMN `multi_code`  tinyint(4) NULL DEFAULT 0 COMMENT '是否多优惠券码。0-否，1-是，2-生成中' AFTER `update_time`;

ALTER TABLE `ft_coupons`
MODIFY COLUMN `multi_code`  tinyint(4) NULL DEFAULT 0 COMMENT '是否多优惠券码。3-否，1-是，2-生成中' AFTER `update_time`;

ALTER TABLE `ft_coupons`
MODIFY COLUMN `multi_code`  tinyint(4) NULL DEFAULT 3 COMMENT '是否多优惠券码。3-否，1-是，2-生成中' AFTER `update_time`;

--2015/10/21 by zach
ALTER TABLE `ft`.`ft_coupons` CHANGE COLUMN `discount_code` `discount_code` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '优惠码';

--2015/10/23 by zach
ALTER TABLE `ft`.`ft_feedback` CHANGE COLUMN `user_id` `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
CHANGE COLUMN `content` `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '反馈内容',
CHANGE COLUMN `create_time` `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '反馈时间';

--2015/10/27 bu jepson
CREATE TABLE `ft_billing` (
`free`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '免费阶段秒' ,
`define`  varchar(255) NOT NULL DEFAULT '' COMMENT '自定义阶段' ,
`last`  int(11) NOT NULL DEFAULT 0 COMMENT '最后阶段金额'
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
COMMENT='计费表';

ALTER TABLE `ft_billing`
ADD COLUMN `id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT AFTER `last`,
ADD PRIMARY KEY (`id`);

--2015/10/29 by jepson
CREATE TABLE `ft_teacher_called_time` (
`tid`  int(11) NOT NULL DEFAULT 0 COMMENT '老师ID' ,
`called_time`  int(11) NULL DEFAULT 0 COMMENT '通话总时长'
);

--2015/11/02 by jepson
CREATE TABLE `ft_teacher_detail_v2` (
`user_id`  int(11) NOT NULL DEFAULT 0 COMMENT '用户ID' ,
`real_name`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '真实姓名' ,
`certificate_photo`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '证件照' ,
`job`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '职业' ,
`reason`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '审核失败原因' ,
`status`  tinyint(2) NULL DEFAULT 0 COMMENT '1-待审核，2-审核成功，3-审核失败，' ,
`create_time`  int(11) NULL DEFAULT 0 COMMENT '申请时间' ,
`email`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '邮箱' ,
`skype`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT 'skype账号' ,
PRIMARY KEY (`user_id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
COMMENT='老师详情表v2'
ROW_FORMAT=COMPACT;

-- 2015/11/04 by zach
ALTER TABLE `ft`.`ft_complain` CHANGE COLUMN `type` `type` tinyint(4) DEFAULT 1 COMMENT '投诉类型 1：投诉外教 2：支付问题 3：投诉学生 4：结算问题 5:其他';
ALTER TABLE `ft`.`ft_coupons` CHANGE COLUMN `discount_code` `discount_code` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '优惠码';

-- 2015/11/05 by zach
ALTER TABLE `ft`.`ft_teacher_detail` ADD COLUMN `email` varchar(30) NOT NULL COMMENT '邮箱' AFTER `contact_info`,
ADD COLUMN `skype` varchar(30) NOT NULL COMMENT 'skype账号' AFTER `email`;

-- 2015/11/12 by zach
ALTER TABLE `ft`.`ft_users` CHANGE COLUMN `international_code` `international_code` smallint DEFAULT 0 COMMENT '国际代码';

-- 2015/11/22 by zach
ALTER TABLE `ft`.`ft_users` ADD COLUMN `balance` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '余额';

CREATE TABLE `ft`.`ft_recharge` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `recharge_id` varchar(20) NOT NULL COMMENT '预充值ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `amount` int UNSIGNED NOT NULL COMMENT '金额',
  `create_time` int UNSIGNED NOT NULL COMMENT '充值时间',
  `update_time` int UNSIGNED NOT NULL COMMENT '充值完成时间',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '充值状态：1:待处理 2:成功',
  PRIMARY KEY (`id`)
) COMMENT='预充值表';

CREATE TABLE `ft`.`ft_user_fund_flow` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `relation_id` int UNSIGNED NOT NULL COMMENT '关联ID 根据type值区分(1:充值自增id 2:通话id 3:系统充值id)',
  `mode` tinyint NOT NULL DEFAULT 1 COMMENT '方式：1:加 2:减',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '类型：1:充值 2:通话 3:系统奖励 4:待处理',
  `amount` int UNSIGNED NOT NULL COMMENT '金额',
  `create_time` int UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL COMMENT '更新时间',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1:有效 2:无效',
  PRIMARY KEY (`id`)
) COMMENT='用户资金流表';

CREATE TABLE `ft`.`ft_recharge_list` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` int UNSIGNED NOT NULL COMMENT '充值金额',
  `receive_amount` int UNSIGNED NOT NULL COMMENT '赠送金额',
  `description` varchar(20) NOT NULL COMMENT '描述',
  `create_time` int UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL COMMENT '更新时间',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1:有效 2:无效',
  PRIMARY KEY (`id`)
) COMMENT='预充值列表';

-- 2015/11/30 by zach
ALTER TABLE `ft`.`ft_recharge` ADD UNIQUE `index_recharge_id` (`recharge_id`) comment '充值ID唯一索引';

-- 2015/12/04 by zach
CREATE TABLE `ft`.`ft_system_recharge_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `amount` int UNSIGNED NOT NULL COMMENT '充值金额',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '充值类型：1:系统奖励 2:注册赠送',
  `manager` varchar(20) NOT NULL COMMENT '管理员',
  `create_time` int UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) COMMENT='系统充值历史';

-- 2015/12/10 by zach
CREATE TABLE `ft_notify_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型：1:首次登录',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态： 1:已通知 2:未通知',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) COMMENT='消息通知表';

-- 2015/12/11 by zach
ALTER TABLE `ft`.`ft_recharge` ADD COLUMN `receive_amount` int UNSIGNED NOT NULL COMMENT '赠送金额' AFTER `amount`;

-- 2015/12/14 by zach
CREATE TABLE `ft`.`ft_user_unique_code` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `uuid` varchar(15) NOT NULL COMMENT '唯一ID',
  PRIMARY KEY (`id`),
  UNIQUE `index_uuid` (`uuid`) comment '唯一ID唯一索引'
) COMMENT='分享唯一码表';

CREATE TABLE `ft`.`ft_share_record` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `mobile` varchar(11) NOT NULL COMMENT '手机号码',
  `share_id` int UNSIGNED NOT NULL COMMENT '分享用户ID',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：通话时长是否达到一分钟 1:否 2:是',
  `create_time` int UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE `index_mobile` (`mobile`) comment '手机号码唯一索引'
) COMMENT='分享记录表';

-- 2015/12/16 by luiz
ALTER TABLE `ft`.`ft_users` ADD COLUMN `nim_token` varchar(255) NOT NULL COMMENT '云信token' AFTER `balance`;

-- 2015/12/22 by zach
CREATE TABLE `ft`.`ft_teacher_category` (
  `type` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '外教类型',
  `customer_price` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '客户价格',
  `teacher_price` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '外教价格',
  `category_name` varchar(10) NOT NULL DEFAULT '' COMMENT '类型名称',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`type`)
) COMMENT='教师类型表';

ALTER TABLE `ft`.`ft_teacher_detail` ADD COLUMN `type` tinyint UNSIGNED NOT NULL DEFAULT 1 COMMENT '外教类型： 1:菲律宾 2:欧美';

-- 2015/12/23 by luiz
ALTER TABLE `ft`.`ft_teacher_detail` ADD COLUMN `introduce_audio` varchar(255) NOT NULL COMMENT '自我介绍音频' AFTER `skype`;

-- 2015/12/24 by luiz
ALTER TABLE `ft`.`ft_teacher_detail` ADD COLUMN `edit_status` tinyint(2) DEFAULT 1 COMMENT '1-已审核，2-审核中，3-审核失败，' AFTER `type`;

CREATE TABLE `ft_teacher_edit` (
  `tid` int(10) unsigned NOT NULL COMMENT '老师ID',
  `avatar` varchar(255) NOT NULL COMMENT '头像',
  `introduce` varchar(255) NOT NULL COMMENT '自我介绍',
  `introduce_audio` varchar(255) NOT NULL COMMENT '自我介绍音频',
  `skype` varchar(30) NOT NULL COMMENT 'skype账号',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态 1:待审核2:已审核3:未通过',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='教师资料修改审核表';

ALTER TABLE `ft`.`ft_teacher_edit` ADD COLUMN `reason` varchar(255) NOT NULL COMMENT '理由' AFTER `update_time`;

-- 2016/01/05 by luiz
ALTER TABLE `ft`.`ft_teacher_detail` ADD COLUMN `audio_time_length` int(11) NOT NULL DEFAULT 0 COMMENT '音频时长（毫秒）' AFTER `edit_status`;
ALTER TABLE `ft`.`ft_teacher_edit` ADD COLUMN `audio_time_length` int(11) NOT NULL DEFAULT 0 COMMENT '音频时长（毫秒）' AFTER `reason`;

-- 2016/1/6 by zach
CREATE TABLE `ft`.`ft_auditing_notify` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `apply_info` varchar(612) NOT NULL DEFAULT '' COMMENT '申请信息',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '读取状态：1:未读 2:已读',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`)
) COMMENT='审核通知表';

-- 2016/1/18 by zach
CREATE TABLE `ft`.`ft_teacher_online_times` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `teacher_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '教师ID',
  `total_times` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '总时长',
  `date` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '日期',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`)
) COMMENT='教师在线时长表';

-- 2016/2/17 by zach
ALTER TABLE `ft`.`ft_teacher_edit` CHANGE COLUMN `introduce` `introduce` varchar(1000) NOT NULL DEFAULT '' COMMENT '自我介绍';

-- 2016/2/23 by zach
ALTER TABLE `ft`.`ft_auditing_notify` CHANGE COLUMN `apply_info` `apply_info` text NOT NULL COMMENT '申请信息';

-- 2016/2/24 by zach
CREATE TABLE `ft`.`ft_student_teacher` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `sid` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '学生ID',
  `tid` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '教师ID',
  PRIMARY KEY (`id`)
) COMMENT='教师收藏表';

-- 2016/2/26 by zach
ALTER TABLE `ft`.`ft_student_teacher` ADD INDEX `index_sid` USING BTREE (`sid`) comment '学生ID索引',
ADD INDEX `index_tid` USING BTREE (`tid`) comment '教师ID索引';

-- 2016/3/03 by zach
ALTER TABLE `ft`.`ft_orders` ADD COLUMN `room_id` varchar(20) NOT NULL DEFAULT '' COMMENT '通话房间号',
ADD COLUMN `recording_url` varchar(255) NOT NULL DEFAULT '' COMMENT '录音链接';

-- 2016/3/09 by zach
ALTER TABLE `ft`.`ft_users` CHANGE COLUMN `device_id` `device_id` varchar(64) DEFAULT '' COMMENT '设备号';
