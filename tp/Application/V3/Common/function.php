<?php
// +----------------------------------------------------------------------
// | Peshine
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://peshine.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: huawr <service@peshine.com>
// +----------------------------------------------------------------------
// $Id$

//公共函数
// ----------------------------------------------------------------------
// 截取字utf符串
function utf_substr($str, $len)
{
	for ($i = 0; $i < $len; $i++) {
		$temp_str = substr($str, 0, 1);
		if (ord($temp_str) > 127) {
			$i++;
			if ($i < $len) {
				$new_str[] = substr($str, 0, 3);
				$str = substr($str, 3);
			}
		} else {
			$new_str[] = substr($str, 0, 1);
			$str = substr($str, 1);
		}
	}
	return join($new_str);
}

// 写日志
// eq: lTrace('Log/lastSql', $this->getActionName(), $poll->getLastSql());
function lTrace($fname, $from, $str)
{
	$fname .= '.txt';
	$record = sprintf("%s%s-%s------ [%s] %s%c%c", file_get_contents($fname), date('Y-m-d H:i:s'), floor(microtime() * 1000), $from, $str, 10, 13);
	file_put_contents($fname, $record);
}


/**
 * Json返回
 * @param int $code 返回码 ：0    Success    成功；1    Fail    失败；2    Unknown error    未知错误；3    Login fail    密码账号不匹配；4    None user    无此用户
 * @param string $msg 返回内容
 * @param arr $array 输出内容
 * @return json
 */
function json_echo($code, $msg, $array)
{
	$array["code"] = $code;
	$array["message"] = $msg;
	$str = json_encode($array);
	//中文转换
	echo preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $str);
}

/**
 * err log
 * @param unknown_type $msg
 */
function ft_err($msg)
{
	\Think\Log::write(MODULE_NAME . ' -- ' . ACTION_NAME . ' -- ' . $msg, \Think\Log::ERR);
}

//数据处理, string字符串 转  array
function strToArray($str)
{
	if (is_null($str)) return true;

	$arr = explode(',', $str);
	$new_str = "";

	foreach ($arr as $u) {
		$new_str .= trim($u) . "##";
	}

	return explode('##', substr($new_str, 0, -2));
}

function rtn_str_tag($ids)
{
	$tag_str = '';
	$tag_types = C('TAG_TYPES');
	$id_arr = explode(',', $ids);
	for ($i = 0; $i < count($id_arr); $i++) {
		$tag_str .= $tag_types[$id_arr[$i]] . ' ';
	}
	return $tag_str;
}

function arrayToStr($pic, $link_str, $dirname)
{
	$arr = explode(',', $pic);
	for ($i = 0; $i < count($arr); $i++) {
		$arr[$i] = $link_str . $dirname . '/' . $arr[$i];
	}
	return implode(',', $arr);
}

/**
 * 过滤敏感词汇
 * @param $str 被过滤的字符串
 * @param $char 替换字符
 */
function filterSensitiveWords($str, $char = '*')
{
	// 获取敏感词汇
	$sensitive_words = S('SENSITIVE_WORDS');
	if (empty($sensitive_words)) {
		$sensitive_path = C('SENSITIVE_WORDS_PATH');
		$handle = fopen($sensitive_path, 'r');
		if (!$handle) {
			json_echo(0, '文件打开失败');
		}
		while (!feof($handle)) {
			$word = trim(fgets($handle));
			if (!empty($word)) {
				$sensitive_words[] = $word;
			}
		}
		fclose($handle);
		S('SENSITIVE_WORDS', $sensitive_words);
	}

	// 敏感词替换
	$sensitive_replace = array_combine($sensitive_words, array_fill(0, count($sensitive_words), $char));
	$replaced_str = strtr($str, $sensitive_replace);

	return $replaced_str;
}

/**
 * 验证国际代码
 */
function checkInternationalCode($code)
{
	// 获取国际代码数组
	$code_arr = S('INTERNATIONAL_CODE');
	// 存入缓存
	if (empty($code_arr)) {
		$code_arr = C('NATIONALITY_CODE');
		S('NATIONALITY_CODE', $code_arr);
	}
	// 返回结果
	return in_array($code, $code_arr);
}

/**
 * 验证国籍代码
 * @param $code
 * @return bool
 */
function checkNationalityCode($code)
{
	// 获取国际代码数组
	$code_arr = S('NATIONALITY_CODE');
	// 存入缓存
	if (empty($code_arr)) {
		$code_arr = C('NATIONALITY_CODE');
		S('NATIONALITY_CODE', $code_arr);
	}

	// 返回结果
	return in_array($code, $code_arr);
}

/**
 * 验证手机号码
 */
function checkMobile($code, $mobile)
{
	// 国际代码或手机号码为空
	if (empty($code) || empty($mobile)) return false;
	// 中国用户
	if ($code == '86') {
		$result = preg_match('/^1[34578]{1}\d{9}$/', $mobile) === 1 ? true : false;
	} // 国外用户
	else {
		$result = preg_match('/^\d{8,11}$/', $mobile) === 1 ? true : false;
	}
	// 返回结果
	return $result;
}

/**
 * 上传图片、文件方法
 * @param $type (类型
 * @param $data (urlencode数据)
 * @return string  返回error_code filename
 */
function upload_pic($type, $data, $user_id='')
{
	// 获取用户ID
	if ($user_id == '' && !defined('USER_ID')) return 'USER_INVALID';
	if ($user_id == '' && defined('USER_ID')) $user_id = USER_ID;
	//传入存储目录，二进制图片流
	$upload_dir = '/Uploads/V2/' . $type . '/' . $user_id . '/';
	$save_dir = BASE_PATH . $upload_dir;

	//配置信息
	$con_info = C("UPLOAD_PIC_TYPE." . strtoupper($type));

	if (!is_dir($save_dir) && !mkdir($save_dir, 0777, true))
		return "MKDIR_FAILD";

	//处理图片流 转成二进制
	$len = strlen($data) % 4;
	$len > 0 && $data .= str_repeat("=", 4 - $len);//base64 规则

	$pic_byte = base64_decode($data);

	//判断是否为正确的图片
	if (in_array($con_info[1], array("png", "jpg", "jpeg")) && !imagecreatefromstring($pic_byte))
		return "IMAGE_IS_CORRUPT";

	//上传文件大小限制后期可以通过参数指定传输类型，判断大小
	$max_len = 1024 * 1024 * $con_info[0];
	if (strlen($pic_byte) > $max_len)
		return "FILESIZE_NOT_ALLOWED";

	//采用md5作为文件名  可以防止一直写入
	if (function_exists($con_info[2]))
		$file_name = call_user_func($con_info[2], $pic_byte);
	else
		$file_name = md5($pic_byte);
	$file_name = $file_name . "." . $con_info[1];

	$res = false;
	if (file_exists($save_dir . $file_name)) {
		$res = true;
	} else {
		$fp = fopen($save_dir . $file_name, "w");
		if ($fp) {
			$res = fwrite($fp, $pic_byte);
			fclose($fp);
		}
	}

	if ($res)
		return $file_name;

	return "UPLOAD_FAILD";
}

/**
 * 创建预充值ID
 * @return string $recharge_id
 */
function create_recharge_id()
{
	// 唯一ID
	$uniqid = implode('',array_map('ord', str_split(uniqid())));
	$recharge_id = rand(10000000, 99999999) . substr($uniqid, -8);
	$result = M('Recharge')->getFieldByRechargeId($recharge_id, 'id');
	if (empty($result)) {
		return $recharge_id;
	} else {
		create_recharge_id();
	}
}

/**
 * 验证唯一ID
 * @param  string $uuid 唯一ID
 * @return boolean      验证结果
 */
function check_uuid($uuid)
{
	// 验证uuid是否存在
	$id = M('UserUniqueCode')->getFieldByUuid($uuid, 'id');
	$result = empty($id) ? false : true;
	return $result;
}

?>