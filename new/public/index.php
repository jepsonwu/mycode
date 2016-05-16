<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
if(APPLICATION_ENV == 'staging'){
    $pathItem = realpath(APPLICATION_PATH . '/../../dm');
}elseif(APPLICATION_ENV == 'production'){
	 $pathItem = realpath(APPLICATION_PATH . '/../../../production.dm');
}else{
    $pathItem = realpath(APPLICATION_PATH . '/../../dm');
}

set_include_path(implode(PATH_SEPARATOR, array(
    $pathItem,
    realpath(APPLICATION_PATH .'/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()
            ->run();