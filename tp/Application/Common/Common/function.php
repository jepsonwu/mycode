<?php

use Common\Model\CategoryModel;

use Common\Model\RbacRoleModel;

use Common\Model\RbacUserModel;

use Think\Log;

// 测试用文件log
function logtest($str, $file)
{
	if (!file_exists('../log') || !is_dir('../log')) {
		mkdir('../log', 0775);
	}
	if (empty($file)) {
		$file = date('Y-m-d') . '.log';
	}
	$file = '../log/' . $file;
	if (!file_exists($file)) {
		touch($file);
	}

	$str = __ACTION__ . ' ' . $str;

	$log = new Logger('abc360');
	$log->pushHandler(new StreamHandler($file, Logger::WARNING));
	$log->addWarning($str);
}

/**
 * debug log
 * @param string $msg 信息
 * @param string $dest 目标文件
 */
function debug($msg, $dest = '')
{
	\Think\Log::write(MODULE_NAME . ' -- ' . ACTION_NAME . ' -- ' . $msg, \Think\Log::DEBUG, '', $dest);
}

/**
 * err log
 * @param unknown_type $msg
 */
function err($msg)
{
	\Think\Log::write(MODULE_NAME . ' -- ' . ACTION_NAME . ' -- ' . $msg, \Think\Log::ERR);
}

/**
 * 取模
 * @param int $mod 模
 * @param int $val 数值
 * @return number
 */
function mod($mod, $val)
{
	return $val % $mod;
}

/**
 * 获取指定配置的值
 * @param unknown_type $name
 * @param unknown_type $val
 * @return Ambigous <>
 */
function config_val($name, $val)
{
	$ary = C($name);
	return $ary[$val];
}

/**
 * 生成ajax返回数组
 * @param string $info 弹出消息
 * @param boolean $status 返回结果
 * @param string $url 跳转地址
 * @param string $act 动作
 * @return array
 */
function make_rtn($info, $status = false, $url = '', $act = '')
{
	$rtn = array(
		'status' => $status,
		'info' => $info,
		'url' => $url,
		'act' => $act
	);
	return $rtn;
}

// 获取本月第一天
function get_fst_day_of_this_mon()
{
	return mktime(0, 0, 0, date('n'), 1, date('Y'));
}

// 获取下月第一天
function get_fst_day_of_next_mon()
{
	return mktime(0, 0, 0, date('n') + 1, 1, date('Y'));
}

/**
 * 按格式生成日期
 * @param int $time 时间戳
 * @param string $format 显示格式
 * @return string
 */
function to_date($time, $format = 'Y-m-d H:i')
{
	if (empty ($time)) {
		return '';
	}
	return date($format, $time);
}

//
function to_date_small($time, $format = 'Y-m-d')
{
	if (empty ($time)) {
		return '';
	}
	return date($format, $time);
}

/**
 * 字符串限长
 * @param string $str 输入字符串
 * @param int $lng 长度默认30
 * @return string
 */
function length_limit($str, $lng = 30, $suffix = true)
{
	return \Org\Util\String::msubstr($str, 0, $lng, 'utf-8', $suffix);
}

/**
 * 生成密码
 * @param string $password 密码原值
 * @param string $type 加密方式
 * @return string
 */
function pwd_hash($password, $type = 'md5')
{
	return hash($type, $password);
}

/**
 * 生成加盐密码
 * @param string $pwd 原密码
 * @param string $salt 密码参数
 * @return string
 */
function md5_pwd($pwd, $salt)
{
	return md5(md5($pwd) . $salt);
}

/**
 * 二维数组按指定的键值排序
 * @param array $arr 源数组
 * @param string $keys 排序键值
 * @param string $type 排序方式
 * @return array
 */
function array_sort($arr, $keys, $type = 'desc')
{
	$keysvalue = $new_array = array();
	foreach ($arr as $k => $v) {
		$keysvalue[$k] = $v[$keys];
	}
	if ($type == 'asc') {
		asort($keysvalue);
	} else {
		arsort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k => $v) {
		$new_array[$k] = $arr[$k];
	}
	return $new_array;
}

/**
 * 邮箱验证
 * @param string $email
 * @return boolean
 */
function check_email($email)
{
	$pattern_test = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
	return preg_match($pattern_test, $email);
}

/**
 * 发邮件
 * @param array $mail 邮件
 * @param string $cmp 公司名称（发件人）
 * @return Ambigous <string, boolean>
 */
function send_mail($mail, $cmp)
{
	//
	$variable = M("SysVariable");
	$send_from = $variable->getFieldByMykey('send_from', 'myvalue');
	$arrSendFrom = explode('|', $send_from);
	//
	$emailer = new \Org\Util\Email();
	$emailer->setConfig('smtp_host', $arrSendFrom[0]);
	$emailer->setConfig('smtp_user', $arrSendFrom[1]);
	$emailer->setConfig('smtp_pass', $arrSendFrom[2]);
	$emailer->setConfig('from', $arrSendFrom[1]);
	$emailer->setConfig('charset', 'UTF-8');
	//
	$fname = "=?UTF-8?B?" . base64_encode($cmp) . "?=";
	$emailer->setConfig('fromName', $fname);
	//
	$emailer->sendTo = $mail['mailto'];
	//
	$mail['title'] = "=?UTF-8?B?" . base64_encode($mail['title']) . "?=";
	$emailer->subject = $mail['title'];
	//
	$emailer->content = $mail['content'];

	return $emailer->send();
}

/**
 * 生成指定字段数组
 * @param array $list 原数据集
 * @param string $field 需生成数组的字段
 * @param int $uniq 唯一（默认）
 * @param int $notempty 非空（默认无需）
 */
function make_array($list, $field, $uniq = true, $notempty = false)
{
	//
	$aim_array = array();
	//
	if (!empty($list)) {
		//
		foreach ($list as $key => $value) {
			if ($notempty) {
				if (!empty($value[$field])) {
					$aim_array[] = $value[$field];
				}
			} else {
				$aim_array[] = $value[$field];
			}
		}
		//
		if ($uniq) {
			$aim_array = array_unique($aim_array);
		}
	}
	//
	return $aim_array;
}

/**
 * 获取星期几
 * @param int $w
 * @return Ambigous <string>
 */
function get_weekday($date)
{
	$weekdays = C('WEEKDAYS');
	return '星期' . $weekdays[date('N', $date)];
}

/**
 * 根据手机号获取归属地
 * @param str $mobile 手机号
 */
function get_city_by_mobile($mobile)
{
// 		$url = "http://www.youdao.com/smartresult-xml/search.s?type=mobile&q=";
	$url = "http://life.tenpay.com/cgi-bin/mobile/MobileQueryAttribution.cgi?chgmobile=";
	$url = $url . $mobile;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$mobile_result = curl_exec($ch);
	$mobile_result = substr($mobile_result, 0, strlen($mobile_result) - 2);
	curl_close($ch);
	//
	$xml = simplexml_load_string($mobile_result);
	$location = explode(" ", $xml->product [0]->location);
	$province = $location [0];
	$city = $location [1];
	//
	$data = array();
	if (!empty ($province)) {
		$data ['province'] = $province;
	}
	if (!empty ($city)) {
		$data ['city'] = $city;
	} else {
		$data ['city'] = $province;
	}
	//
	return $data;
}

/**
 * 根据ip获取归属地
 */
function get_city_by_ip()
{
	$IpLocation = new \Org\Net\IpLocation ();
	$ip = get_client_ip();
	$location = $IpLocation->getlocation($ip);
	$area = iconv("GB2312", "UTF-8", $location ['country']);
	$pos = strpos($area, '省');
	if ($pos !== false) {
		$province = substr($area, 0, strpos($area, '省') + 3);
		$city = substr($area, strpos($area, '省') + 3);
	} else {
		$province = $area;
		$city = $area;
	}
	//
	$data ['ip'] = $ip;
	$data ['ip_province'] = empty ($province) ? '' : $province;
	$data ['ip_city'] = empty ($city) ? '' : $city;
	return $data;
}

/**
 * 防跨站
 * @param unknown_type $val
 * @return mixed
 */
function remove_xss($val)
{
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=@avascript:alert('XSS')>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// @ @ search for the hex values
		$val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
		// @ @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	//$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}

/**
 * 唯一性验证
 * @param string $modle 表
 * @param string $field 字段
 * @param string $val 值
 * @param int $ignor 排除id
 * @return boolean
 */
function check_unique($modle, $field, $val, $ignor = null)
{
	//
	$condition = array(
		$field => $val
	);
	//
	if (!empty($ignor)) {
		$condition['id'] = array('neq', $ignor);
	}
	//
	if (M($modle)->where($condition)->find()) {
		return false;
	} else {
		return true;
	}
}

/**
 * 获取缓存中的角色信息
 * @param int $role_id 角色id
 * @param string $field 获取字段
 * @return Ambigous <unknown>|multitype:unknown
 */
function get_roles($role_id = null, $field = null)
{
	$roles = S('roles');
	if (empty($roles)) {
		$mdl_roles = new RbacRoleModel();
		$roles = $mdl_roles->setRoleCache();
	}
	//
	if (!empty($role_id)) {
		if (!empty($field)) {
			return $roles[$role_id][$field];
		} else {
			return $roles[$role_id];
		}
	} else {
		return $roles;
	}
}

/**
 * 获取缓存中的用户信息
 * @param int $user_id 用户id
 * @param string $field 获取字段
 * @return Ambigous <unknown>|multitype:unknown
 */
function get_users($user_id = null, $field = null)
{
	$users = S('backend_users');
	if (empty($users)) {
		$mdl_users = new RbacUserModel();
		$users = $mdl_users->setUserCache();
	}
	//
	if (!empty($user_id)) {
		if (!empty($field)) {
			return $users[$user_id][$field];
		} else {
			return $users[$user_id];
		}
	} else {
		return $users;
	}
}

/**
 * 获取缓存中的目录信息
 * @param int $cat_id 用户id
 * @param string $field 获取字段
 * @return Ambigous <unknown>|multitype:unknown
 */
function get_cats($cat_id = null, $field = null)
{
	$cats = S('cats');
	if (empty($cats)) {
		$mdl_cats = new CategoryModel();
		$cats = $mdl_cats->setCatCache();
	}
	//
	if (!empty($cat_id)) {
		if (!empty($field)) {
			return $cats[$cat_id][$field];
		} else {
			return $cats[$cat_id];
		}
	} else {
		return $cats;
	}
}

/**
 * 按父目录获取子目录
 * @param int $prt_id
 * @return array
 */
function get_cats_by_prtid($prt_id)
{
	$cats = get_cats();
	foreach ($cats as $key => $value) {
		if ($value['prt_id'] != $prt_id) {
			unset($cats[$key]);
		}
	}
	//
	return $cats;
}

/**
 * 按根目录获取目录树
 * @param int $root_id
 * @return array
 */
function get_cats_by_rootid($root_id)
{
	$cats = get_cats_by_prtid($root_id);
	if (!empty($cats)) {
		foreach ($cats as $key => $value) {
			$cats[$key]['sons'] = get_cats_by_rootid($value['id']);
		}
	}
	return $cats;
}

/**
 * 清缓存
 */
function clear_all_cache()
{
	S('roles', null);
	S('backend_users', null);
	S('cats', null);
	unset($_SESSION['pdtCats']);
}

/**
 * 获取有道词典的单词解释
 * @param string $str
 * @return null|array
 */
function getYoudaoMeaning($str)
{
	if (empty($str)) return null;
	// 有道词典api
	$url = "http://fanyi.youdao.com/openapi.do?keyfrom=HappyGo&key=1935294520&type=data&doctype=json&version=1.1&only=dict&q=";
	$arr = array();
	// 英语单词如 NO. carry-on
	preg_match_all("/[a-zA-Z.-]+/", $str, $arr);

	if (empty($arr[0])) return null;
	$meaning = array();

	foreach ($arr[0] as $u) {
		$content = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", file_get_contents($url . "" . $u));
		//$content = str_replace('\u2026','…',file_get_contents($url."".$u));
		$content = str_replace('\u201C', '', $content); // "单引号替换，防止json出错
		$meaning[$u] = $content;
	}

	return $meaning;
}

/**
 * [makeMessage 通知创建方法]
 * @param  string $user_id [description]
 * @param  integer $type [默认学生类型]
 * @param  string $message [description]
 * @param  string $url [详情url]
 * @return [type]           [description]
 */
function makeMessage($user_id = "", $type = 1, $message = "", $url = "")
{
	//学生、老师，通过模板创建通知 todo
	// if(in_array($type, array(1,2))){

	// }

	$data = array(
		"message" => $message,
		"url" => $url,
		"create_time" => time(),
		"type" => intval($type),
		"user_id" => intval($user_id),
		"create_user" => session("user_id"),
		"status" => 1
	);

	$res = M("Message")->add($data);
	return is_bool($res) ? false : true;
}

function is_date($date)
{
	return true;
}

function is_inte($inte)
{
	return true;
}

function is_timestamp($time)
{
	return true;
}

function is_orderno($orderno)
{
	return true;
}

/**
 * 优惠券码加密
 * @param $code 10亿到40亿十进制数字
 * @return string 十六进制8位
 */
function coupon_code_encrypt($code)
{
	//10亿到40亿十进制数字 依次递增 保证每个值唯一
	//为了加强安全性  可以在递增的过程中选择多位数间隔递增

	//转成二进制 移位运算 最后四位和第二位之后的四位对换移位
	//这样做的目的是为了保证十进制数在10和40亿之间 因为后四位有可能都为0
	$code = decbin($code);
	$code = $code{0} . substr($code, -4) . substr($code, 5, -4) . substr($code, 1, 4);

	//二进制转十六进制 位数为8位
	$code = dechex(bindec($code));

	return $code;
}

/**
 * 优惠券解密
 * @param $code
 * @return number|string
 */
function coupon_code_decrypt($code)
{
	$code = decbin(hexdec($code));

	$code = $code{0} . substr($code, -4) . substr($code, 5, -4) . substr($code, 1, 4);
	$code = bindec($code);

	return $code;
}

/**
 * 验证随机码的正确性
 * @param $code 随机数
 * @param $total 优惠券数量
 * @return bool
 */
function check_random_code($code, $total)
{
	$interval = C('COUPONS_INTERVAL');
	$min_val = 1000000000;
	$max_val = $total * $interval + $min_val;
	if ($code > $min_val && $code <= $max_val) {
		$check_val = ($code - $min_val) % $interval === 0 ? true : false;
		return $check_val;
	} else {
		return false;
	}
}

/**
 *创建优惠券码
 * @param int $len
 * @return string
 */
function create_coupon_code($len = 8)
{
	$chars = "abcdefghijkmnpqrstuvwxyz23456789";
	$str = "";

	for ($i = 1; $i <= $len; $i++)
		$str .= substr($chars, rand(0, 25), 1);

	return $str;
}

/**
 * 获取图像url路径
 * @param string $type
 * @param string $user_id
 * @return string
 */
function create_pic_url($type = "avatar", $user_id = "")
{
	empty($user_id) && defined("USER_ID") && $user_id = USER_ID;

	if (!in_array(strtoupper($type), array_keys(C("UPLOAD_PIC_TYPE"))))
		return "";

	$url = "http://{$_SERVER['HTTP_HOST']}/Uploads/V2/{$type}/{$user_id}/";
	return $url;
}

/**
 * 一个数组的值作为健，另一个字符串作为值
 * @param $array1
 * @param string $str
 * @return array
 */
function array_combine_str($array1, $str = "")
{
	$array2 = array();

	foreach ($array1 as $value) {
		$array2[$value] = $str;
	}

	return $array2;
}

/**
 * 订单日志记录方法  主要是为了统一格式
 * @param $order_id
 * @param bool|false $type 默认失败
 * @param string $info
 */
function order_log($order_id, $type = false, $info = "")
{
	$debug = debug_backtrace();
	$function = $debug[1]['function'];

	$type = $type ? "succeed" : "field";
	$log_info = "order {$function} {$type},order_id:{$order_id}";

	$info && $log_info = $log_info . "," . $info;
	Log::record($log_info, "INFO", true);
}


if (!function_exists('array_column')) {
	function array_column($input, $column_key, $index_key = null)
	{
		if ($index_key !== null) {
			// Collect the keys
			$keys = array();
			$i = 0; // Counter for numerical keys when key does not exist

			foreach ($input as $row) {
				if (array_key_exists($index_key, $row)) {
					// Update counter for numerical keys
					if (is_numeric($row[$index_key]) || is_bool($row[$index_key])) {
						$i = max($i, (int)$row[$index_key] + 1);
					}

					// Get the key from a single column of the array
					$keys[] = $row[$index_key];
				} else {
					// The key does not exist, use numerical indexing
					$keys[] = $i++;
				}
			}
		}

		if ($column_key !== null) {
			// Collect the values
			$values = array();
			$i = 0; // Counter for removing keys

			foreach ($input as $row) {
				if (array_key_exists($column_key, $row)) {
					// Get the values from a single column of the input array
					$values[] = $row[$column_key];
					$i++;
				} elseif (isset($keys)) {
					// Values does not exist, also drop the key for it
					array_splice($keys, $i, 1);
				}
			}
		} else {
			// Get the full arrays
			$values = array_values($input);
		}

		if ($index_key !== null) {
			return array_combine($keys, $values);
		}

		return $values;
	}
}

/**
 * 下载文件
 * @param $file_name
 */
function download_file($dir_name, $file_name)
{
	if (!file_exists($dir_name . $file_name))
		exit("file not exists");

	$file_extension = strtolower(substr(strrchr($file_name, "."), 1));
	$file_size = filesize($dir_name . $file_name);
	$md5_sum = md5_file($dir_name . $file_name);

	//This will set the Content-Type to the appropriate setting for the file
	switch ($file_extension) {
		case "exe":
			$ctype = "application/octet-stream";
			break;
		case "zip":
			$ctype = "application/zip";
			break;
		case "mp3":
			$ctype = "audio/mpeg";
			break;
		case "mpg":
			$ctype = "video/mpeg";
			break;
		case "avi":
			$ctype = "video/x-msvideo";
			break;

		//The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
		case "php":
		case "htm":
		case "html":
			exit("file not allow download");
			break;

		default:
			$ctype = "application/force-download";
	}

	if (isset($_SERVER['HTTP_RANGE'])) {
		$partial_content = true;
		$range = explode("-", $_SERVER['HTTP_RANGE']);
		$offset = intval($range[0]);
		$length = intval($range[1]) - $offset;
	} else {
		$partial_content = false;
		$offset = 0;
		$length = $file_size;
	}

	//read the data from the file
	$handle = fopen($dir_name . $file_name, 'r');
	//$data_size = $file_size;
	$buffer = '';

	fseek($handle, $offset);
	$buffer = fread($handle, $length);
	$md5_sum = md5($buffer);
	if ($partial_content)
		$data_size = intval($range[1]) - intval($range[0]);
	else
		$data_size = $file_size;
	fclose($handle);

	// send the headers and data
	header("Content-Length: " . $data_size);
	header("Content-md5: " . $md5_sum);
	header("Accept-Ranges: bytes");
	if ($partial_content)
		header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $file_size);

	//header("Connection: close");
	header("Content-type: " . $ctype);
	header('Content-Disposition: attachment; filename=' . $file_name);
	//fread($handle, $data_size);

	echo $buffer;
}

function ismobile() {
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
		return true;

	//此条摘自TPM智能切换模板引擎，适合TPM开发
	if(isset ($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT'])
		return true;
	//如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset ($_SERVER['HTTP_VIA']))
		//找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
	//判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array(
			'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
		);
		//从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	//协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
	return false;
}

/**
 * 获取用户头像链接
 * @param  string $user_id 用户ID
 * @param  string $avatar  用户头像
 * @return string $url     头像链接
 */
function get_avatar_url($user_id='default', $avatar='')
{
	
	// 用户头像路径
	$avatar_path = __ROOT__ . "Uploads/V2/avatar/{$user_id}/{$avatar}";
	// 系统默认头像
	$default_avatar = C('SYSTEM_DEFAULT_INFO.AVATAR');
	// 头像链接
	$url = "http://{$_SERVER['HTTP_HOST']}/Uploads/V2/avatar/{$user_id}/{$avatar}";
	if (!is_file($avatar_path)) {
		$url = "http://{$_SERVER['HTTP_HOST']}/Uploads/V2/avatar/default/{$default_avatar}";
	}

	return $url;
}

/**
 * socket端交互
 * @param $service_id 协议族ID
 * @param $command_id 具体协议ID
 * @param $body 包体内容
 * @return mixed 返回socket端响应包内容
 */
function socket_interact($service_id,$command_id,$body)
{
	$HEAD_LEN = 20;
	//返回云信ID
	switch(APP_STATUS) {
		//测试环境
		case 'dev':
			$tcp_uri = C('DEV_TCP');
			break;
		//预发布环境
		case 'release':
			$tcp_uri = C('RELEASE_TCP');
			break;
		//正式环境
		case 'master':
			$tcp_uri = C('MASTER_TCP');
			break;
	}
	$client = stream_socket_client($tcp_uri);
	if (!$client) exit("can not connect");
	$data['serviceId'] = $service_id;
	$data['commandId'] = $command_id;
	$data['code'] = 20;
	$data['serialId'] = 1;
	$data['sender'] = 1;
	$data['version1'] = 2;
	$data['version2'] = 2;
	$data['flag'] = 0;
	$data['reserve'] = 1;
	$data['body'] = json_encode($body);
	$data['body'] = pack('a*', $data['body']);
	$package_len = $HEAD_LEN + strlen($data['body']);
	$data_packet = pack("NCCnNNCCCC", $package_len, $data['serviceId'], $data['commandId'], $data['code'], $data['serialId'], $data['sender'], $data['version1'], $data['version2'], $data['flag'], $data['reserve']) . $data['body'];
	fwrite($client, $data_packet);
	$buffer = fread($client, 2000);
	$data = unpack("Nlength/CserviceId/CcommandId/ncode/NserialId/Nsender/Cversion1/Cversion2/Cflag/Creserve", $buffer);
	if ($data['length'] > $HEAD_LEN) {
		$data['body'] = substr($buffer, $HEAD_LEN, $data['length'] - $HEAD_LEN);
		$data['body'] = unpack("a*", $data['body']);
	} else {
		$data['body'] = '';
	}
	return $data;
}

/**
 * 审核结果通知
 * @param $body
 * @return mixed
 */
function audit_notice($body){
	return socket_interact(40,2,$body);
}

/**
 * @param int $length
 * @return string
 */
function make_char($length = 20)
{
	//字符集，可任意添加你需要的字符
	$chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
		'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's',
		't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D',
		'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O',
		'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z',
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
	// 在 $chars 中随机取 $length 个数组元素键名
	$char_txt = '';
	for($i = 0; $i < $length; $i++){
		// 将 $length 个数组元素连接成字符串
		$char_txt .= $chars[array_rand($chars)];
	}
	return $char_txt;
}

/**
 * 更新云信用户资料
 * @param $data
 */
function update_nim_user_info($data)
{
	$result = nim_curl($data,C('UPDATE_NIM_USER_INFO_URL'));
	return $result;
}

function nim_curl($data,$url){
	if(APP_STATUS == 'dev'){
		$AppKey = C('DEV_NIM_APPKEY');
		$AppSecret = C('DEV_NIM_APPSECRET');
	}else{
		$AppKey = C('MASTER_NIM_APPKEY');
		$AppSecret = C('MASTER_NIM_APPSECRET');
	}
	$Nonce = make_char();
	$CurTime = (string)time();
	$CheckSum = sha1($AppSecret.$Nonce.$CurTime);
	$header = array('AppKey:'.$AppKey,'Nonce:'.$Nonce,'CurTime:'.$CurTime,'CheckSum:'.$CheckSum,'Content-Type:application/x-www-form-urlencoded');
	$url = $url;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	$result = curl_exec($ch);
	curl_close($ch);
	return json_decode($result,true);
}

?>