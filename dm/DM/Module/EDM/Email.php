<?php
/**
 * EDM Email类
 * 
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Module_EDM_Email extends DM_Module_Base
{
    /**
     * 发送邮件
     * 
     * @param string $toEmail 收件人
     * @param string $toName 收件人昵称
     * @param string $title 标题
     * @param string $content 内容
     */
    public static function send($toEmail, $toName, $title, $content)
    {
        if (!self::checkemail($toEmail)){
            return false;
        }
        
        $config=DM_Controller_Front::getInstance()->getConfig()->settings->mail;
        $config = $config->toArray();
    
        $transport = new Zend_Mail_Transport_Smtp ( $config['host'], $config);
        $mail = new Zend_Mail('utf-8');
    
        $htmlCon=str_ireplace(array('{$title}', '{$content}'), array($title, $content), file_get_contents($config['tpldir'].DM_Controller_Front::getInstance()->getLocale().'.phtml'));

        $mail->setBodyHtml($htmlCon);
        $mail->setFrom($config['from'], $config['name']);
        $mail->addTo($toEmail, $toName);
        $mail->setSubject($title);
        return $mail->send($transport);
    }

    /**
     * 发送邮件
     *
     * @param string $toEmail 收件人
     * @param string $toName 收件人昵称
     * @param string $title 标题
     * @param string $content 内容
     */
    public static function sendNormal($toEmail, $toName, $title, $content)
    {
        if (!self::checkemail($toEmail)){
            return false;
        }

        $config=DM_Controller_Front::getInstance()->getConfig()->settings->mail;
        $config = $config->toArray();

        $transport = new Zend_Mail_Transport_Smtp ( $config['host'], $config);
        $mail = new Zend_Mail('utf-8');

        $mail->setBodyHtml($content);
        $mail->setFrom($config['from'], $config['name']);
        $mail->addTo($toEmail, $toName);
        $mail->setSubject($title);
        return $mail->send($transport);
    }
    
    /**
     * 检测邮箱格式
     */
    private static function checkemail($email)
    {
        if(preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/',$email)){
            list($username,$domain)=@split('@',$email);
            if(!checkdnsrr($domain,'MX')) {
                return false;
            }
            return true;
        }
        return false;
    }
}