<?php
namespace Lib;

use Lib\Spl\RecursiveArrayAccess;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:28
 */
class Conf extends RecursiveArrayAccess
{
	public static $instance = null;

	static public function getInstance(array $conf)
	{
		is_null(self::$instance) &&
		self::$instance = new self($conf);

		return self::$instance;
	}

	protected function __construct($conf)
	{
		parent::__construct($conf);
	}
}