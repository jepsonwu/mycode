<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

class SysInfoController extends CommonController {
	/*
	 * 服务器信息
	 */
	public function index() {
		//
		$info = array (
				'操作系统' => PHP_OS,
				'运行环境' => $_SERVER ["SERVER_SOFTWARE"] . ' Mysql/' . mysql_get_server_info(),
				'PHP运行方式' => php_sapi_name (),
				'ThinkPHP版本' => THINK_VERSION,
				'单文件上传限制' => ini_get ( 'upload_max_filesize' ),
				'表单上传限制' => ini_get ( 'post_max_size' ),
				'执行时间限制' => ini_get ( 'max_execution_time' ) . ' 秒111',
				'服务器时间' => date ( "Y年n月j日 H:i:s" ),
				'北京时间' => gmdate ( "Y年n月j日 H:i:s", time () + 8 * 3600 ),
				'服务器域名/IP' => $_SERVER ['SERVER_NAME'] . ' [ ' . gethostbyname ( $_SERVER ['SERVER_NAME'] ) . ' ]',
				'剩余空间' => round ( (@disk_free_space ( "." ) / (1024 * 1024)), 2 ) . 'M',
				'register_globals' => get_cfg_var ( "register_globals" ) == "1" ? "ON" : "OFF",
				'magic_quotes_gpc' => (1 === get_magic_quotes_gpc ()) ? 'YES' : 'NO',
				'magic_quotes_runtime' => (1 === get_magic_quotes_runtime ()) ? 'YES' : 'NO' 
		);
		$this->assign ( 'info', $info );
		//
		$this->display ();
	}
	
}

?>