<?php
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

$dm_path = realpath(APPLICATION_PATH . '/../../mycode/dm');
set_include_path(implode(PATH_SEPARATOR, array(
	$dm_path,
	realpath(APPLICATION_PATH . '/../library'),
	get_include_path(),
)));

require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()
	->run();