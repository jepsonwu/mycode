<?php
return array(
	"LOAD_EXT_FILE" => "functions",
	"LOAD_EXT_CONFIG" => "",

	"UPLOAD_ATT_CONF" => array(
		"maxSize" => 1048576,
		"exts" => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'flv', 'mp3', 'mp4', 'swf'),
		"rootPath" => PUBLIC_UPLOAD_PATH,
		"saveName" => array("uniqid"),
	),

	"UPLOAD_PIC_CONF" => array(
		"maxSize" => 1048576,
		"exts" => array("jpg", "gif", "jpeg", "png"),
		"rootPath" => PUBLIC_UPLOAD_PATH,
		"saveName" => array("uniqid"),
	),

	//url 模式
	'URL_MODEL'=>2,
);