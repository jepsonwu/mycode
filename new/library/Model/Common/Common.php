<?php

/**
 * 公共model类
 * User: jepson <jepson@duomai.com>
 * Date: 16-1-20
 * Time: 上午9:37
 */
class Model_Common_Common extends Zend_Db_Table
{
	/**
	 * 根据查询条件获取信息
	 * Row时特殊处理  只有一个子段时返回字符串
	 * @param array $where
	 * @param null $fields
	 * @param string $type Row|Assoc|Pairs($key=>$val)|All
	 * @param string|null $order 如：CID desc,MemberID asc
	 * @param null $limit
	 * @return array|mixed|null
	 */
	public function getInfoMix(array $where = array(), $fields = null, $type = "Row", $order = null, $limit = null)
	{
		$select = $this->select();
		!is_null($fields) && $select->from($this->_name, (array)$fields);

		foreach ($where as $key => $val)
			$select->where($key, $val);

		if (!is_null($order)) {
			$orderByArr = explode(',', $order);
			foreach ($orderByArr as $r) {
				$select->order($r);
			}
		}

		if (!is_null($limit))
			$select->limit($limit);

		$func = "fetch{$type}";
		$info = $this->_db->$func($select);
		//这里row 返回false 其它返回array{} count(1) 不返回false 返回0
		return $info === false ? null :
			($type == 'Row' && count($info) == 1 ? current($info) : $info);
	}

	/**
	 * 根据缓存或取数据
	 * 多个字段返回array()
	 * 单个字段返回false
	 * @param $key |键值
	 * @param array $field |查找字段 默认全部
	 * @param null $field_key |条加字段
	 * @return array|mixed|null|string
	 * @throws Exception
	 */
	public function getInfoMixByCache($key, $field = array(), $field_key = null)
	{
		$cacheKey = "{$this->_name}:{$key}";
		is_null($field_key) && $field_key = $this->_primary;
		$redisObj = DM_Module_Redis::getInstance();

		$field = (array)$field;
		//hGetAll 返回array()  hmGet 返回array("key"=>false)
		$result = empty($field) ? $redisObj->hGetAll($cacheKey) : $redisObj->hmGet($cacheKey, $field);

		if (current($result) === false) {
			$info = $this->getInfoMix(array("{$field_key} =?" => $key));
			if (!is_null($info)) {
				$redisObj->hMSet($cacheKey, $info);
				$redisObj->expire($cacheKey, 7 * 86400);
				$result = empty($field) ? $info : array_intersect_key($info, array_flip($field));
			}
		}

		return count($result) == 1 ? current($result) : $result;
	}

	/**
	 *
	 * @param $key
	 * @throws Exception
	 */
	public function deleteCache($key)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$redisObj->del("{$this->_name}:{$key}");
	}

	/**
	 * 插入多行数据
	 * 返回影响的行数
	 * @param array $data
	 * @return bool|int
	 * @throws Zend_Db_Adapter_Exception
	 */
	public function insertMulti(array $data)
	{
		//filter
		$keys = current($data);
		$values = array();
		$keys = array_keys(is_array($keys) ? $keys : array());
		if (empty($keys))
			return false;

		foreach ($data as $val) {
			if (!is_array($val) || count($val) != count($keys))
				return false;
			else
				$values[] = '"' . implode('","', array_map("addslashes", $val)) . '"';
		}

		//判断字段是否存在

		//create sql
		$sql = "INSERT INTO {$this->_name} (`" . implode("`,`", $keys) . "`) VALUES(" . implode("),(", $values) . ")";
		$result = $this->_db->query($sql);
		if (!$result)
			return false;

		return $result->rowCount();
	}

	/**
	 * 获取主键
	 * @return mixed|
	 */
	public function getPrimary()
	{
		return $this->_primary[1];
	}

	/**
	 * 创建logger对象
	 * @param $dir
	 * @param $filename
	 * @return Zend_Log
	 */
	public function createLogger($dir, $filename)
	{
		$dir = APPLICATION_PATH . "/data/log/{$dir}/";
		!is_dir($dir) && mkdir($dir, 0777, true) && chown($dir, posix_getuid());

		$fp = fopen($dir . date("Y-m-d") . ".{$filename}.log", "a", false);
		$writer = new Zend_Log_Writer_Stream($fp);
		$logger = new Zend_Log($writer);
		return $logger;
	}

	/**
	 * 根据最后查询ID查找待统计数据
	 * 这种方案只适用于自增ID和时间同为递增的  例如创建订单到支付，两者师同为递增的  但是订单结算就不是这样了
	 * @param $key |缓存最后ID的key
	 * @param array $where
	 * @param array $fields
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public function selectByLastID($key, array $where, $fields)
	{
		//加上最后查询ID条件
		$redis = DM_Module_Redis::getInstance();
		$last_id = $redis->get($key);
		$last_id === false && $last_id = 0;

		$primary = $this->getPrimary();
		$where["{$primary} >?"] = $last_id;
		$result = $this->getInfoMix($where, $fields, "All", "{$primary} asc");

		$last_id = 0;
		if (!empty($result)) {
			if (count($fields) == 1) {
				$result = array_map("current", $result);
				$last_id = max($result);
			} else {
				isset($result[count($result) - 1][$primary]) && $last_id = $result[count($result) - 1][$primary];
			}
		}

		//设置最后查询ID
		$last_id > 0 && $redis->set($key, $last_id);

		return $result;
	}

	/**
	 * 生成二维码
	 * @param $content
	 * @return mixed
	 * @throws Exception
	 */
	public function createQrCode($content)
	{
		$filename = APPLICATION_PATH . '/../public/upload/' . md5($content) . '.png';
		DM_Module_QRcode::png($content, $filename, "L", '6', 0);

		$qiniu = new Model_Qiniu();
		$token = $qiniu->getUploadToken();
		$uploadMgr = $qiniu->getUploadManager();

		$filename = realpath($filename);
		list($ret, $err) = $uploadMgr->putFile($token['token'], null, $filename);
		if (!is_null($err))
			throw new Exception("操作失败！");

		unlink($filename);
		return $ret['hash'];
	}
}