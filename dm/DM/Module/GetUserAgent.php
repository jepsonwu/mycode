<?php
class DM_Module_GetUserAgent
{
	public static function getBrowser(){
	    //bin环境下可能不存在
	    if (!isset($_SERVER['HTTP_USER_AGENT'])) return '';
	    
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if(strpos($agent, 'iphone') !== false || strpos($agent, 'ipod')!==false){
			$browser = 'iphone';
		} elseif(strpos($agent, 'ipad') !== false) {
			$browser = 'ipad';
		} elseif(strpos($agent, 'android') !== false) {
			$browser = 'android';
		} elseif(strpos($agent, 'symbianos') !== false) {
			$browser = 'symbianos';
		} elseif(strpos($agent, 'windows phone') !== false) {
			$browser = 'wp';
		} else {
			$browser = 'other';
		}
		return $browser;
	}
	
	public static function isMobile()
	{
	    $ua=self::getBrowser();
	    if ($ua=="iphone" || $ua=="ipad" || $ua=="android" || $ua=="symbianos" || $ua=="wp"){
	        return true;
	    }
	    
	    return false;
	}
	
	public static function isAndroid()
	{
	    $ua=self::getBrowser();
	    if ($ua=="android"){
	        return true;
	    }
	     
	    return false;
	}
	
	public static function isIOS()
	{
	    $ua=self::getBrowser();
	    if ($ua=="iphone" || $ua=="ipad"){
	        return true;
	    }
	     
	    return false;
	}
	
	/**
	 * 判断是否微信浏览器
	 * 
	 * @return boolean
	 */
	public static function isWeixin()
	{
	   return isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
	}
	

}