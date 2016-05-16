<?php
/**
 * Ip库
 */
class DM_Module_Ip
{
    /**
     * 根据ip获取国家名称
     * @param  $ip 默认为请求ip
     * @return string
     */
    public function getCountry($ip=null){
        $ip = $ip?$ip:$_SERVER['REMOTE_ADDR'];
        $tag = current(explode('.',$ip));
        if('192'== $tag ||'127'== $tag) {
            return 'LAN';
        }
        include(realpath(dirname(__FILE__) . '/Ip/geoip.inc.php'));
        $gi = geoip_open(realpath(dirname(__FILE__) . '/Ip/GeoIP.dat'),GEOIP_STANDARD);
        $country_code = geoip_country_code_by_addr($gi, $ip);// 获取国家代码
        //$country_name = geoip_country_name_by_addr($gi, $ip);// 获取国家名称
        geoip_close($gi);
        return $country_code;
    }
    /**
     * 根据ip获取语言
     * @param  $ip 
     * @return string
     */
    public function getLanguage($ip=null)
    {
        $country_code = $this->getCountry($ip);
        if(in_array($country_code,array('CN','TW','LAN'))){
            return 'zh';
        }
        return 'en';
    }

    
}