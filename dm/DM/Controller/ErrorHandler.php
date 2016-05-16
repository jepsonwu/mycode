<?php
/**
 * 单例，共用资源获取器
*
* @author Bruce
* @since 2014/05/22
*/
class DM_Controller_ErrorHandler extends Zend_Controller_Plugin_Abstract 
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $module = $request->getModuleName();
        $errorHandler = Zend_Controller_Front::getInstance()->getPlugin('Zend_Controller_Plugin_ErrorHandler');
        $errorHandler->setErrorHandlerModule($module);
    }
    
}