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
    function utf_substr($str,$len){
        for ($i=0; $i<$len; $i++) {
            $temp_str=substr($str,0,1);
            if(ord($temp_str) > 127) {
                $i++;
                if($i<$len) {
                    $new_str[]=substr($str,0,3);
                    $str=substr($str,3);
                }
            }
            else {
                $new_str[]=substr($str,0,1);
                $str=substr($str,1);
            }
        }
        return join($new_str);
    }
    // 写日志
    // eq: lTrace('Log/lastSql', $this->getActionName(), $poll->getLastSql());
    function lTrace($fname, $from, $str){
    	$fname .= '.txt';
    	$record = sprintf("%s%s-%s------ [%s] %s%c%c", file_get_contents($fname), date('Y-m-d H:i:s'), floor(microtime()*1000),$from, $str, 10, 13);
    	file_put_contents($fname, $record);
    }
   
    
    /**
     * Json返回
     * @param int $code 返回码 ：0	Success	成功；1	Fail	失败；2	Unknown error	未知错误；3	Login fail	密码账号不匹配；4	None user	无此用户
     * @param string $msg 返回内容
     * @param arr $array 输出内容
     * @return json
     */
    function json_echo($code,$msg,$array){
    	$array["code"]=$code;
    	$array["message"]=$msg;
    	$str=json_encode($array);
    	//中文转换
    	echo preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $str);
    }
	/**
     * err log
     * @param unknown_type $msg
     */
    function ft_err( $msg ) {
    	\Think\Log::write( MODULE_NAME . ' -- ' . ACTION_NAME . ' -- ' . $msg, \Think\Log::ERR );
    }
    
	//数据处理, string字符串 转  array
	function strToArray($str) {
		if (is_null($str)) return true;

		$arr = explode(',' , $str);
		$new_str = "";
		
		foreach ($arr as $u) {
			$new_str .= trim($u)."##";
		}
		
		return explode('##', substr($new_str, 0, -2));
	}
    
  function rtn_str_tag( $ids ){
        $tag_str='';
        $tag_types=C('TAG_TYPES');
        $id_arr=explode(',',$ids);
        for($i=0;$i<count($id_arr);$i++){
            $tag_str.=$tag_types[$id_arr[$i]].' ';
        }
        return $tag_str;
  }
  function arrayToStr($pic,$link_str,$dirname){
      $arr=explode(',',$pic);
      for($i=0;$i<count($arr);$i++){
          $arr[$i]=$link_str.$dirname.'/'.$arr[$i];
      }
      return implode(',',$arr);
  }

?>