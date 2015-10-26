<?php
namespace Lib\Ipc;
/**
 *进程通信驱动抽象类
 * User: jepson <jepson@abc360.com>
 * Date: 15-10-23
 * Time: 下午4:13
 */
abstract class IpcAbstract
{
	//读取数据
	abstract public function read($key);

	//写入数据
	abstract public function write($key, $value, $size);

	//删除数据
	abstract public function delete($key);
}