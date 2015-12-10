<?php
namespace Lib;

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 15-11-19
 * Time: 下午2:28
 */
class Conf
{
	private $_config = array();

	public static $instance = null;

	static public function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 批量设置
	 * @param $conf
	 */
	public function setConfig($conf, $value = null)
	{
		if (is_array($conf))
			$this->_config = array_merge($conf, array_change_key_case($conf, CASE_UPPER));
		else
			$this->_config[$conf] = $value;
	}

	/**
	 * 获取和设置配置参数 支持批量定义
	 * @param string|array $name 配置变量
	 * @param mixed $value 配置值
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	public function getConfig($name = null, $value = null, $default = null)
	{
		// 无参数时获取所有
		if (empty($name)) {
			return $this->_config;
		}

		// 优先执行设置获取或赋值
		if (is_string($name)) {
			if (!strpos($name, '.')) {
				$name = strtoupper($name);
				if (is_null($value))
					return isset($this->_config[$name]) ? $this->_config[$name] : $default;

				$this->_config[$name] = $value;
				return true;
			}

			// 二维数组设置和获取支持
			$name = explode('.', $name);
			$name[0] = strtoupper($name[0]);
			if (is_null($value))
				return isset($this->_config[$name[0]][$name[1]]) ? $this->_config[$name[0]][$name[1]] : $default;

			$this->_config[$name[0]][$name[1]] = $value;
			return true;
		}

		return null; // 避免非法参数
	}
}