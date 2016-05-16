<?php
/**
 * File         : ZendRedis.php
 * Author       : Star
 * Date         : 14-6-10
 * Time         : 下午5:20
 * Description  : zend redis class
 */

class DM_Module_ZendRedis extends DM_Module_Redis {

    protected static $_instance = null;

    public static function getInstance(){
        if(self::$_instance!==null) return self::$_instance;         
        $config = Zend_Registry::get('config');
        if(is_object($config))
            $config = $config->toArray();
        $redisConfig = $config['redis'];
        if(self::checkConfig($redisConfig)){
            self::$_instance = new self($redisConfig);
            return self::$_instance;
        }else{
            throw new Exception("redis config error.");
        }
    }

}