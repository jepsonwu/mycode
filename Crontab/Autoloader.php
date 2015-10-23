<?php
/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:08
 */
function autoload($name)
{
	$filename = "";

	//命令空间
	if (strpos($name, "\\") !== false) {
		$filename = BASE_PATH . str_replace("\\", "/", $name) . ".php";
	}

	file_exists($filename) && require_once $filename;
}

!spl_autoload_register("autoload") && exit("autoload register field");