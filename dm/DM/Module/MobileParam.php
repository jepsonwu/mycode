<?php
/**
 * 移动设备参数
 * 
 * @author Bruce
 *
 * 系统统一约定前台设备在api中需要传回的参数
 * 
 * 样例： platform:"ANDROID", osVersion: "4.2.1", deviceName:"xiaomi 4", deviceID: "123456789000000", pushID:"555555555555555", appName:'haimi", currentVersion:'2.0.0', language:'zh', channel:'WEBSITE'
 * 
    platform: string 平台 ANDROID安卓,IOS苹果,WP微软,WAP手机网,WEIXIN微信浏览器,WEB网页版,OTHER其他 当前支持这几个平台
    osVersion: string 平台系统版本号 IOS7.1类似
    deviceName: string 设备型号 比如iphone5/nexus 5/xiaomi 4
    deviceID: string 设备ID
    pushID: string 推送ID
    appName: string haimi/mibao 应用名称
    currentVersion: string 应用当前版本号 当前有
    language：系统语言 当前有
    channel：渠道，推广渠道来源
    net: string，用户网络状态，值：wifi；2G；3G；4G
    
    2015.03.18 默认值从NULL改为字符串空值
 */
class DM_Module_MobileParam {
    const PLATFORM_IOS="IOS";
    const PLATFORM_ANDROID="ANDROID";
    const PLATFORM_WP="WP";
    const PLATFORM_OTHER="OTHER";
    
    public static $allowedPlatforms=array('ANDROID','IOS','WP','WAP','WEIXIN','WEB','OTHER');
    
    public static function getPlatform()
    {
        return self::get('platform');
    }
    
    public static function getOSVersion()
    {
        return self::get('osVersion');
    }
    
    public static function getDeviceName()
    {
        return self::get('deviceName');
    }
    
    public static function getDeviceID()
    {
        return self::get('deviceID');
    }
    
    public static function getPushID()
    {
        return self::get('pushID');
    }
    
    public static function getAppName()
    {
        return self::get('appName');
    }
    
    public static function getCurrentVersion()
    {
        return self::get('currentVersion');
    }
    
    public static function getLanguage()
    {
        return self::get('language');
    }
    
    public static function getChannel()
    {
        return self::get('channel');
    }
    
    public static function getNet()
    {
        return self::get('net');
    }
    
    private static function get($param)
    {
        if (isset($_REQUEST[$param])){
            return $_REQUEST[$param];
        }else{
            return '';
        }
    }
}