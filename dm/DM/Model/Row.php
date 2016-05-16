<?php
/**
 * 数据库行对象基类
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Model_Row extends Zend_Db_Table_Row_Abstract
{
    // 操作成功
    const STATUS_OK = 1;
    // 操作失败
    const STATUS_FAILURE = -1;
    
    protected function resultArray($flag = true,$msg='',$data = NULL, $extra = NULL) {
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
     * 异常报错后的日志保存
     */
    protected function logExceptionInfo(Exception $e)
    {
        $log=DM_Module_Log::create(DM_Controller_Action::ERROR_LOG_SERVICE);
    
        $log->add("[IP".DM_Controller_Front::getInstance()->getClientIp()."]发现异常：".$e->getMessage(). PHP_EOL. "Params: ".json_encode($this->getRunParamsInfo())."\n". 'INFO: '.$e->getFile().'('.$e->getLine().')'. PHP_EOL. $e->getTraceAsString(). PHP_EOL);
    }
    
    private function getRunParamsInfo()
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
    
    /**
     * 判断接口是否调用成功
     *
     * @param array $result json接口数据
     * @return boolean
     */
    protected function isSuccess($result){
        return isset($result['flag']) && $result['flag']>=0;
    }
    
    /**
     * @return DM_Model_Row
     */
    public static function create()
    {
        return new static();
    }
}
