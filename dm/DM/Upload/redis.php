<?php
/**
 * redis instance
 **/
class redisInstance
{
    protected  static $_instance = null;

    //redis
    public static function getInstance(){
        if(null === static::$_instance){
            $redis = new Redis();
            $redis->pconnect("127.0.0.1",6380);
            static::$_instance = $redis;
        }
        return static::$_instance;
    }
}
