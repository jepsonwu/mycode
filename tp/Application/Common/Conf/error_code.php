<?php
return array(

	//common code
	'CONTENT_IS_INVALID' => array(1001, 'content is invalid'),
	'NOT_PAGE_LIST' => array(1002, 'page list is not found'),
	'PAGE_IS_INVALID' => array(1003, 'page is invalid'),
	'LISTROWS_IS_INVALID' => array(1004, 'listrows is invalid'),
	'DB_QUERY_FAILED' => array(1005, 'db query failed'),
	'SYSTEM_ERROR' => array(1006, 'system error'),

	//some code about user
	'MOBILE_IS_INVALID' => array(1101, 'mobile is invalid'),//参数格式不正确
	'INTERNATIONAL_CODE_IS_INVALID' => array(1102, 'international code is invalid'),
	'MOBILE_IS_WRONG' => array(1103, 'mobile is wrong'),//不存在
	'USER_INVALID' => array(1104, 'user invalid'),//user is not login
	'USER_IS_NOT_EXIST' => array(1105, 'user is not exist'),
	'USER_IS_EXIST' => array(1106, 'user is exist'),
	'PASSWORD_IS_INVALID' => array(1107, 'password is invalid'),
	'PASSWORD_IS_WRONG' => array(1108, 'password is wrong'),
	'PASSWORD_EDIT_FAILD' => array(1109, 'password edit faild'),
	'OLD_PASSWORD_IS_INVALID' => array(1110, 'old_password is invalid'),
	'NEW_PASSWORD_IS_INVALID' => array(1111, 'new_password is invalid'),
	'USER_EDIT_FAILD' => array(1112, 'user edit faild'),
	'USER_PAYMENT_EDIT_FAILD' => array(1113, 'user payment edit faild'),
	'NATIONALITY_CODE_IS_INVALID' => array(1114, 'nationality code is invalid'),
	'USER_STATUS_EXPIRED' => array(1115, 'user status expired'),
	'SAME_AS_OLD_PASSWORD' => array(1116, 'same as old password'),
	'NIM_USER_INFO_UPDATE_FAILED' => array(1117, 'nim user info update failed'),

	//some code about trade
	'ORDERS_IS_NULL' => array(1201, 'orders is not found'),
	'ORDER_ID_IS_INVALID' => array(1202, 'order_id is invalid'),
	'ORDER_NOT_ALLOW_PAY' => array(1203, 'orders are not allowed to pay'),
	'ORDER_IS_NULL' => array(1204, 'order is not found'),
	'ORDER_NOT_ALLOW_SETTLEMENT' => array(1205, 'orders are not allowed to settlement'),
	'ORDER_SETTLEMENT_FIELD' => array(1206, 'order settlement faild'),
	'ORDER_IS_NOT_ALLOWED_TO_COMMENT' => array(1207, 'order is not allowed to comment'),
	'ORDER_PAY_FIELD' => array(1208, 'order pay field'),

	//some code about apply for teacher
	'APPLY_TEACHER_REPETITIVE' => array(1301, 'you have applied'),
	'APPLY_TEACHER_FAILD' => array(1302, 'apply for teacher faild'),
	'EDIT_TEACHER_FAILD' => array(1303, 'edit teacher info faild'),

	//some code about upload
	'UPLOAD_TYPE_IS_INVALID' => array(1401, 'upload type is invalid'),
	'MKDIR_FAILD' => array(1402, 'mkdir is faild'),
	'FILESIZE_NOT_ALLOWED' => array(1403, 'filesize is not allowed'),
	'IMAGE_IS_CORRUPT' => array(1404, 'image is corrupt'),
	'UPLOAD_FAILD' => array(1405, 'upload faild'),
	'ROOM_ID_IS_INVALID' => array(1406, 'room_id is invalid'),
	'RECORDING_IS_INVALID' => array(1407, 'recording is invalid'),
	'UPLOAD_RECORDING_FAILED' => array(1408, 'upload recording failed'),
	'UPDATE_ORDER_INFO_FAILED' => array(1409, 'update order info failed'),

	//some code about collection
	'SERIES_IS_ALREADY_COLLECTED' => array(1501, 'series is already collected'),
	'SERIES_IS_NOT_COLLECTED' => array(1502, 'series is not collected'),
	'KNOWLEDGE_ID_IS_INVALID' => array(1503, 'knowledge_id is invalid'),
	'KNOWLEDGE_IS_ALREADY_COLLECTED' => array(1504, 'knowledge is already collected'),
	'KNOWLEDGE_IS_NOT_COLLECTED' => array(1505, 'knowledge is not collected'),
	'SERIES_ID_IS_INVALID' => array(1506, 'series_id is invalid'),
	'TEACHER_ID_IS_INVALID' => array(1507, 'teacher_id is invalid'),
	'TEACHER_IS_NOT_EXIST' => array(1508, 'teacher is not exist'),
	'TEACHER_IS_ALREADY_COLLECTED' => array(1509, 'teacher is already collected'),
	'TEACHER_COLLECTION_FAILED' => array(1510, 'teacher collection failed'),
	'TEACHER_IS_NOT_COLLECTED' => array(1511, 'teacher is not collected'),
	'CANCEL_TEACHER_COLLECTION_FAILED' => array(1512, 'cancel teacher collection failed'),
	'USER_IS_NOT_A_TEACHER' => array(1513, 'user is not a teacher'),

	//some code about comment
	'TEACHER_ID_IS_NULL' => array(1601, 'teacher_id is null'),
	'SERIES_IS_NOT_EXIST' => array(1602, 'series is not exist'),
	'TEACHER_IS_NOT_EXIST' => array(1603, 'teacher is not exist'),
	'TEACHER_ID_IS_INVALID' => array(1604, 'teacher_id is invalid'),
	'LEVEL_IS_INVALID' => array(1605, 'level is invalid'),
	'HAVE_APPLIED_FOR_COMMENT' => array(1606, 'have applied for comment'),
	'TEACHERS_IS_NULL' => array(1607, 'teachers is not found'),

	//some code about calls
	'USER_TYPE_IS_INVALID' => array(1701, 'user type is invalid'),

	//some code about doubt
	'ROLE_IS_INVALID' => array(1801, 'role is invalid'),
	'STATUS_IS_INVALID' => array(1802, 'status is invalid'),
	'PARAM_STATUS_NEED_TO_INPUT' => array(1803, 'param status need to input'),

	//some code about complain
	'TYPE_IS_INVALID' => array(1901, 'type is invalid'),

	//some code about series
	'CATEGORY_ID_IS_INVALID' => array(2001, 'category_id is invalid'),
	'RECOMMEND_IS_INVALID' => array(2002, 'recommend is invalid'),
	'TAG_IS_INVALID' => array(2003, 'tag is invalid'),
	'TIMESTAMP_IS_INVALID' => array(2004, 'timestamp is invalid'),

	//some code about verify code
	'VERIFY_CODE_IS_INVALID' => array(2101, 'verify code is invalid'),
	'VERIFY_CODE_IS_WRONG' => array(2102, 'verify code is wrong'),
	'VERIFY_CODE_ACHIEVE_FAILED' => array(2103, 'verify code achieve failed'),
	'PLEASE_WAIT_A_MOMENT' => array(2104, 'please wait a moment'),
	'ILLEGAL_IP_ADDRESS' => array(2105, 'illegal ip address'),
	'MODULE_IS_INVALID' => array(2106, 'module is invalid'),
	'ATTAIN_DAILY_REQUEST_LIMIT' => array(2107, 'attain daily request limit'),

	//some code about articles
	'ARTICLE_NAME_IS_INVALID' => array(2201, 'article name is invalid'),

	//some code about coupons
	'DISCOUNT_CODE_IS_INVALID' => array(2301, 'discount_code is invalid'),
	'DISCOUNT_CODE_IS_NOT_EXIST' => array(2302, 'discount_code is not exist'),
	'ATTAIN_PERSONAL_RECEIVE_LIMIT' => array(2303, 'attain personal receive limit'),
	'COUPON_RECEIVE_END' => array(2304, 'coupon receive end'),
	'NOT_A_NEW_USER' => array(2305, 'not a new user'),
	'COUPON_IS_NOT_AVAILABLE' => array(2306, 'coupon is not available'),
	'COUPON_USED_FIELD' => array(2307, 'coupon used field'),
	'COUPON_STATUS_IS_INVALID' => array(2308, 'coupon status is invalid'),
	'GET_COUPON_INFO_FAILED' => array(2309, 'get coupon info failed'),
	'GET_COUPON_DETAIL_INFO_FAILED' => array(2310, 'get coupon detail info failed'),
	'INSERT_COUPON_DETAIL_FAILED' => array(2311, 'insert coupon detail failed'),
	'UPDATE_COUPON_DETAIL_FAILED' => array(2312, 'update coupon detail failed'),
	'INSERT_USER_COUPON_FAILED' => array(2313, 'insert user coupon failed'),

	//some code about feedback
	'FEEDBACK_IS_NOT_EXIST' => array(2401, 'feedback is not exist'),

	// some code about recharge
	'AMOUNT_IS_INVALID' => array(2501, 'amount is invalid'),
	'CHANNEL_IS_INVALID' => array(2502, 'channel is invalid'),
	'RECHARGE_ID_IS_INVALID' => array(2503, 'recharge_id is invalid'),
	'RECEIVE_AMOUNT_IS_INVALID' => array(2504, 'receive amount is invalid'),
	'RECHARGE_FAILED' => array(2505, 'recharge failed'),

	// some code about share
	'SHARE_LINK_IS_INVALID' => array(2601, 'share link is invalid'),
	'MOBILE_IS_ALREADY_REGISTER' => array(2602, 'mobile is already register'),
	'ALREADY_APPLIED_FOR_A_USER' => array(2603, 'already applied for a user'),

);