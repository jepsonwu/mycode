<?php
	
	/**
	 * 生成ajax返回数组
	 * @param unknown_type $msg
	 * @return array
	 */
	function make_url_rtn( $info, $url=null ) {
		if ( empty($url) ) {
			$url = __CONTROLLER__ . '/index' . $_SESSION [C ( 'SEARCH_PARAMS_STR' )];
		}
		$rtn = array(
				'status' => true,
				'info' => $info,
				'url' => $url
		);
		return $rtn;
	}
	
	/**
	 * 获取状态显示
	 * @param int $status 状态值
	 * @param string $stt_title 状态文字
	 * @param boolean $imageShow 默认以图片方式显示
	 * @return Ambigous <string, unknown>
	 */
	function get_status($status, $stt_title, $imageShow = true) {
		switch ($status) {
			case -1 :
				$icon_class = "icon_del";
				break;
			case 1 :
			default :
				$icon_class = "icon_ok";
				break;
		}
		//
		$showText = $stt_title;
		$showImg = '<img src="'.__APP__.'/Public/Admin/images/blank.gif" width="16" height="16" class="icon '.$icon_class.'" title="'.$stt_title.'" />';
		//	
		return ($imageShow === true) ?  $showImg  : $showText;
	}
	
	/**
	 * 获取当前用户的ID
	 */
	function get_user_id() {
		return isset($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0;
	}

	/**
	 * 删除物理文件
	 * @param unknown_type $rcds 删除的记录
	 * @param unknown_type $fields 文件名字段
	 * @param unknown_type $fold 所在文件夹
	 * @param unknown_type $file 需删除文件
	 */
	function del_files( $rcds, $fields, $fold, $file=null ) {
		//
		$files = array ();
		//
		if ( empty($file) ) {
			foreach ( $rcds as $key => $value ) {
				foreach ($fields as $k => $val) {
					if (! empty ( $value [$val] )) {
						$files [] = $value [$val];
					}
				}
			}
		}
		else {
			$files[] = $file;
		}
		//
		if (! empty ( $files )) {
			//
			foreach ( $files as $key => $value ) {
				//
				$path = BASE_PATH . '/Uploads/' . $fold . '/' . $value;
				if (! unlink ( $path )) {
					\Think\Log::write ( '文件删除失败：' . $path, \Think\Log::ERR );
				}
				//
				$path = BASE_PATH . '/Uploads/' . $fold . '/s/' . $value;
				if (file_exists ( $path )) {
					if (! unlink ( $path )) {
						\Think\Log::write ( '文件删除失败s：' . $path, \Think\Log::ERR );
					}
				}
			}
		}
	}

	function deldir($directory){
		if(is_dir($directory)) {
			if($dir_handle=@opendir($directory)) {
				while(false!==($filename=readdir($dir_handle))) {
					$file=  $directory."/".$filename;
					if($filename!="." && $filename!="..") {
						if(is_dir($file)) {
							deldir($file);
						} else {
							if ( !unlink($file) ) {
								err ( '图片删除失败：' . $file );
							}
						}
					}
				}
				closedir($dir_handle);

			}
			rmdir($directory);
		}
	}
	
	/**
	 * 获取指定用户的所有角色
	 * @param int $user_id 用户id
	 * @param int $flag 1角色id，2角色名称
	 * @return string
	 */
	function getUserRoles( $user_id, $flag=1 ) {
		$roles = M("RbacRoleUser")->where('user_id='.$user_id)->getField('role_id', true);
		if ( $flag == 1 ) {
			return $roles;
		}
		//
		if ( !empty($roles) ) {
			foreach ($roles as $key => $value) {
				$roles[$key] = get_roles( $value, 'name' );
			}
			return join('，', $roles);
		}
		return '';
	}
	
	/**
	 * 数组转字符串
	 * @param array $arr
	 * @return boolean|string  
	 */
	function arrayHandler($arr) {
		if (empty($arr)) return true;
		
		$rs = "";
		foreach ( $arr as $u ) {
			$rs .= $u.",";
		}
		return substr($rs, 0 ,-1);
	}
	
	/**
	 * 字符串转数组
	 * @param string $str
	 * @return true|array  
	 */
	function strToArray(&$str) {
		if (is_null($str) || strlen($str) == 1) return true;
		
		$arr = explode('<br />', nl2br($str));
		foreach ($arr as $key => $value) {
			$arr[$key] = trim($value);
		}
	
		return $str = $arr;
	}


	/**
	 * [strFilter 过滤特殊字符]
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	function strFilter($str){
	    $str = str_replace(' ', '', $str);
	    $str = str_replace('`', '', $str);
	    $str = str_replace('・', '', $str);
	    $str = str_replace('~', '', $str);
	    $str = str_replace('!', '', $str);
	    $str = str_replace('！', '', $str);
	    $str = str_replace('@', '', $str);
	    $str = str_replace('#', '', $str);
	    $str = str_replace('$', '', $str);
	    $str = str_replace('￥', '', $str);
	    $str = str_replace('%', '', $str);
	    $str = str_replace('^', '', $str);
	    $str = str_replace('……', '', $str);
	    $str = str_replace('&', '', $str);
	    $str = str_replace('*', '', $str);
	    $str = str_replace('(', '', $str);
	    $str = str_replace(')', '', $str);
	    $str = str_replace('（', '', $str);
	    $str = str_replace('）', '', $str);
	    $str = str_replace('-', '', $str);
	    $str = str_replace('_', '', $str);
	    $str = str_replace('――', '', $str);
	    $str = str_replace('+', '', $str);
	    $str = str_replace('=', '', $str);
	    $str = str_replace('|', '', $str);
	    $str = str_replace('\\', '', $str);
	    $str = str_replace('[', '', $str);
	    $str = str_replace(']', '', $str);
	    $str = str_replace('【', '', $str);
	    $str = str_replace('】', '', $str);
	    $str = str_replace('{', '', $str);
	    $str = str_replace('}', '', $str);
	    $str = str_replace(';', '', $str);
	    $str = str_replace('；', '', $str);
	    $str = str_replace(':', '', $str);
	    $str = str_replace('：', '', $str);
	    $str = str_replace('\'', '', $str);
	    $str = str_replace('"', '', $str);
	    $str = str_replace('“', '', $str);
	    $str = str_replace('”', '', $str);
	    $str = str_replace(',', '', $str);
	    $str = str_replace('，', '', $str);
	    $str = str_replace('<', '', $str);
	    $str = str_replace('>', '', $str);
	    $str = str_replace('《', '', $str);
	    $str = str_replace('》', '', $str);
	    $str = str_replace('.', '', $str);
	    $str = str_replace('。', '', $str);
	    $str = str_replace('/', '', $str);
	    $str = str_replace('、', '', $str);
	    $str = str_replace('?', '', $str);
	    $str = str_replace('？', '', $str);
	    $str = str_replace('’', '', $str);
	    //去除中文
        $par = "/[\x80-\xff]/";
        $str=preg_replace($par,"",$str);
        //去除中文符号
        $str=urlencode($str);
        $str=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/",'',$str);
        $str=urldecode($str);
	    return trim($str);
	}
?>