<?php
/**
 * 多语言翻译辅助器
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Helper_Translate
{
    /**
     * @var Zend_Translate
     */
    private $translate = null;
    
    public function __construct($translate)
    {
        if (!$translate instanceof Zend_Translate){
            throw new DM_Exception_Lang('Param translate must be instanceof Zend_Translate.');
        }
        
        $this->translate=$translate;
    }
    
    public function _()
    {
        $params=func_get_args();
        return call_user_func_array(array($this, 'translate'), $params);
    }
    
    /**
     * 多语言翻译，自动支持参数
     * 
     * @param string $messageId 消息ID
     * @return string
     */
    public function translate($messageId)
    {
        $stringResult=$this->translate->_($messageId);

        if ($stringResult!==$messageId) {
            $params=func_get_args();
            //第一个参数是$token，剔除
            array_shift($params);
        
            if (count($params)>0) {
                $stringResult=vsprintf($stringResult, $params);
            }
        
            return $stringResult;
        }else{
            return $stringResult;
        }
    }
    
    /**
     * Calls all methods from the adapter
     */
    public function __call($method, array $options)
    {
        return call_user_func_array(array($this->translate, $method), $options);
    }
}
