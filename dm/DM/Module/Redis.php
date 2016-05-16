<?php
/**
 * File         : Redis.php
 * Author       : Star
 * Date         : 14-6-10
 * Time         : 下午4:22
 * Description  : redis class
 */


class DM_Module_Redis {

    protected $_connect;

    /**
     * 连接redis
     *
     * @param $redisConfig
     * @throws Exception
     */
    public function __construct($redisConfig){
        if(self::checkConfig($redisConfig)){
            $this->_connect = new Redis();
            $this->_connect->connect($redisConfig['host'],$redisConfig['port']);
            if(!empty($redisConfig['auth'])){
                $this->_connect->auth($redisConfig['auth']);
            }
            if(!empty($redisConfig['db'])){
                $this->_connect->select($redisConfig['db']);
            }
            return $this->_connect;
        }else{
            throw new Exception("redis config error.");
        }
    }

    /**
     * 魔术调用
     *
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method,$args){
        if(method_exists($this->_connect,$method)){
            return call_user_func_array(array($this->_connect,$method),$args);
        }else{
            throw new Exception("can not find method {$method} in redis");
        }
    }

    /**
     * 检查host、port项
     *
     * @param $redisConfig
     * @return bool
     */
    protected static function checkConfig($redisConfig){
        if(empty($redisConfig['host'])){
            return false;
        }
        if(empty($redisConfig['port'])){
            return false;
        }
        return true;
    }
    
    /**
     *   获取redis 连接
     * @param string $name
     * @throws Exception
     * @return Ambigous <DM_Module_Redis>
     */
    public static function getInstance($name = 'default')
    {
        static $_instance = array();
        if(!isset($_instance[$name])){
            $configObj = DM_Controller_Front::getInstance()->getConfig();
            $config = $configObj->get('redis')->get($name);
            is_object($config) && $config = $config->toArray();
            if(self::checkConfig($config)){
                $_instance[$name] = new self($config);
            }else{
                throw new Exception('Please check your redis config!');
            }
        }
        return $_instance[$name];
    }
}
