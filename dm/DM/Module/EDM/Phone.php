<?php
/**
 * EDM Phone类
 * 
 * 使用方法：
 * 在application.ini添加配置，key问star要。
 * ;短信发送配置 
    message.key = '597c6abe14d76f8b8dda73304b1762db'
    message.url = 'sms.duomai.com/phone/send'
 * 
 * 目前短信发送主要是star维护的，具体咨询star。
 * 
 * @author 
 * @since 2014/05/28
 */
class DM_Module_EDM_Phone extends DM_Module_Base
{
    /**
     * 发送手机短信
     * 
     * @param string $mobile
     * @param string $message
     * @return json
     */
    public static function send($phone, $message)
    {
        $messageConfig = DM_Controller_Front::getInstance()->getConfig()->message;
        
        $name = !empty($messageConfig->name) ? $messageConfig->name : '';
        
        $mobileArray = array(
                'key'=>$messageConfig->key,
                'phone'=>$phone,
                'message'=>$message,
                'name'=>$name
        );
        return DM_Controller_Front::getInstance()->curl($messageConfig->url,$mobileArray);
    }
}