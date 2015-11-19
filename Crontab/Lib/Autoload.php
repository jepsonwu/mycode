<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午3:44
 */
class Autoload
{
	public function __construct()
	{
		!spl_autoload_register(array($this, "autoload")) && exit("Autoload register field");
	}

	private function autoload($name)
	{
		$filename = "";

		//命令空间
		if (strpos($name, "\\") !== false) {
			$filename = BASE_PATH . str_replace("\\", "/", $name) . ".php";
		}

		file_exists($filename) && require_once $filename;
	}
}