<?php
class Zend_View_Helper_Config extends Zend_View_Helper_Abstract
{
	public function config($partion, $value = null, $filename = 'configs')
	{
        $config = Duomai_Registry::config($filename, $partion);
        if($value !== null){
            return isset($config[$value]) ? $config[$value] : '';
        }else{
            return $config;
        }	
	}
}