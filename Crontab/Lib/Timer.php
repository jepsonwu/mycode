<?php
namespace Lib;
/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-12-4
 * Time: 上午11:14
 */
class Timer
{
	public static $instance = null;

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 设置一个闹钟
	 * @param $func
	 * @param $timeouts
	 */
	function timer($func, $timeouts)
	{
		$base = event_base_new();
		$event = event_new();

		event_set($event, 0, EV_TIMEOUT, $func);
		event_base_set($event, $base);
		event_add($event, $timeouts);

		event_base_loop($base);
	}
}