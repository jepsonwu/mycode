<?php
/**
 * Module基类
 *   
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Module_Base
{
    // 操作成功
    const STATUS_OK = 1;
    // 操作失败
    const STATUS_FAILURE = -1;
    
    protected static function resultArray($flag = true,$msg='',$data = NULL, $extra = NULL) {
        $result = array('flag'=>$flag,'msg'=>$msg);
        if($flag < 0){
            $result['param'] = DM_Controller_Front::getInstance()->getHttpRequest()->getParams();
        }
        if($data!==NULL){
            $result['data'] = $data;
        }
        if($extra!==NULL){
            $result['extra'] = $extra;
        }
        
        return $result;
    }
    
    /**
     * 判断接口是否调用成功
     *
     * @param array $result json接口数据
     * @return boolean
     */
    protected static function isSuccess($result){
        return isset($result['flag']) && $result['flag']>=0;
    }
    
    /**
     * 获取翻译对象
     *
     * @param string $locale
     */
    protected static function getLang($locale=NULL)
    {
        return DM_Controller_Front::getInstance()->getLang($locale);
    }
    
    /**
     * 获取Config对象
     */
    protected static function getConfig()
    {
        return DM_Controller_Front::getInstance()->getConfig();
    }
    
    /**
     * 获取数据库适配器
     *
     * @param string $db The adapter to retrieve. Null to retrieve the default connection
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDb($db=NULL)
    {
        return DM_Controller_Front::getInstance()->getDb($db);
    }
    
    protected function _getParam($paramName, $default = null)
    {
        $value = DM_Controller_Front::getInstance()->getHttpRequest()->getParam($paramName);
        if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }
    
        return $value;
    }
    
    /**
     * 异常报错后的日志保存
     */
    protected static function logExceptionInfo(Exception $e)
    {
        $log=DM_Module_Log::create(DM_Controller_Action::ERROR_LOG_SERVICE);
    
        $log->add("[IP".DM_Controller_Front::getInstance()->getClientIp()."]发现异常：".$e->getMessage(). PHP_EOL. "Params: ".json_encode(self::getRunParamsInfo())."\n". 'INFO: '.$e->getFile().'('.$e->getLine().')'. PHP_EOL. $e->getTraceAsString(). PHP_EOL);
    }
    
    private static function getRunParamsInfo()
    {
        $info=DM_Controller_Front::getInstance()->getHttpRequest()->getParams();
        if (isset($info['error_handler'])){
            unset($info['error_handler']);
        }
        if (isset($_SERVER['REQUEST_URI'])){
            $info['uri']=$_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_REFERER'])){
            $info['referer']=$_SERVER['HTTP_REFERER'];
        }
    
        return $info;
    }
    
    public static function create()
    {
        return new static();
    }
}