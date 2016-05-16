<?php
class Model_Tools 
{
    
   public static function curl($url,$fields=null)
   {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //禁止ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if(!empty($fields)){
            if(is_array($fields)){
                $fields = http_build_query($fields);
                }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch))
        {
            throw new Zend_Exception(curl_error($ch),0);
        }
        else
        {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode)
            {
                return "http status code exception : ".$httpStatusCode;
            }
        }
        curl_close($ch);
        return $response;
    }
	
	//将对象转换成数组
	public static function object_to_array($obj) {
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		if (!empty($_arr)) {
			foreach ($_arr as $key => $val) {
				$val = (is_array($val) || is_object($val)) ? Model_Tools::object_to_array($val) : $val;
				$arr[$key] = $val;
			}
			return $arr;
		} else {
			return array();
		}
	}
    
    /**
	* 截取字符串，UTF-8,可以截取中文
	* $sourcestr 源字符串
	* $len 长度,负数表示从后面截取
	**/
	public static function cut_str($sourcestr,$len){ 
		$word_arr = array();
		$i = 0;
		$str_length = strlen($sourcestr);//字符串的字节数 
		while($i<=$str_length){ 
			$temp_str = substr($sourcestr,$i,1); 
			$ascnum = Ord($temp_str);//得到字符串中第$i位字符的ascii码 
			if($ascnum>=224){//如果ASCII位高与224
				$str = substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符 
				$i = $i+3; //实际Byte计为3
			}elseif($ascnum>=192){//如果ASCII位高与192，
				$str = substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符 
				$i = $i+2; //实际Byte计为2
			}elseif($ascnum>=65 && $ascnum<=90){//如果是大写字母，
				$str = substr($sourcestr,$i,1); 
				$i = $i+1; //实际的Byte数仍计1个
			}else{//其他情况下，包括小写字母和半角标点符号， 
				$str = substr($sourcestr,$i,1); 
				$i = $i+1; //实际的Byte数计1个
			}
			$str!='' && $word_arr[] = $str;
		}
		$returnstr = '';
		$n = 0;
		$str_count = count($word_arr);
		if($len>0){
			$start = 0;
			$end = $len;
		}else{
			$len = 0-$len;
			$start = $str_count-$len;
			$end = $str_count;
		}
		
		if($len>=$str_count){
			$returnstr = implode('',$word_arr);
		}else{
			for($n=$start;$n<$end;$n++){
				$returnstr .= $word_arr[$n];
			}
		}
		return $returnstr; 
	}
}