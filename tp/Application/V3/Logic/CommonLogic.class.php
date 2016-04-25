<?php
namespace V3\Logic;

use Think\Model;

/**
 *
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-11
 * Time: 下午2:28
 */
class CommonLogic extends Model
{
	/**
	 * 定义当前error_code 常量 统一管理 可以更改常量名
	 * @param $code
	 */
	protected function define_code($code)
	{
		define("ERROR_CODE", $code);
	}
}
