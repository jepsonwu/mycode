<?php
namespace V3\Controller;

use Think\Controller\RestController;

class CommonController extends RestController
{
	// 用户有效状态
	const STATUS_USER_VALID = 1;

	// REST允许请求的资源 类型列表
	protected $allowType = array('html', 'xml', 'json');

	//是否需要用户授权,默认授权
	protected $check_user = false;

	//是否需要API授权
	protected $authorize = false;

	//是否请求限制
	protected $limit_request = true;

	//request 参数
	protected $request = array();

	//验证成功返回值
	protected $_default = array();

	//系统级参数验证
	//todo app_key
	protected $_api_fields = array(
		array("sign", "require", null, 1),
		array("sign_type", "MD5,RSA", null, 0, "in", "MD5"),
		array("timestamp", "number", null, 1, null),
		array("app_id", "number", null, 1),
		array("encrypt_type", "RSA,MCRYPT", null, 0, "in", "RSA"),//是否存在加密
		array("encrypt_data", "require", null, 0),//加密之后的数据体
	);

	//APP_ID_CONF
	protected $_app_conf = array();

	/**
	 * 请求成功的返回方法
	 * @param $data
	 */
	protected function successReturn($data=null)
	{
		$result['code'] = 0;
		$result['message'] = 'success';
		if (!empty($data)) $result['data'] = $data;
		$this->response($result, 'json');
	}

	/**
	 * 请求失败的返回方法
	 * @param $code_msg
	 */
	protected function failReturn($code_msg)
	{
		$result['code'] = intval($code_msg[0]);
		$result['message'] = $code_msg[1];
		$this->response($result, 'json');
	}

	/**
	 * 单表列表查询
	 * @param $model
	 * @param array $where
	 * @param string $field
	 * @param string $order
	 * @return array
	 */
	protected function _list($model, $where = array(), $field = "", $order = "id desc")
	{
		//使用过滤fields
		empty($field) && $field = $this->fields;

		unset($filter);
		//count
		$count = $model->where($where)->count();
		$result = array();

		if ($count) {
			($this->page - 1) * $this->listrows >= $count && $this->failReturn(C("NOT_PAGE_LIST"));

			$result = $model->where($where)->page($this->page, $this->listrows)->field($field)->
			order($order)->select();

			$result = array(
				'total' => $count,
				'list' => $result
			);
		}
		return $result;
	}

	/**
	 * 多表关联，不建议用
	 * @param $model
	 * @param $join
	 * @param array $where
	 * @param string $field
	 * @param string $order
	 * @return array
	 */
	protected function _list_join($model, $join, $where = array(), $field = "", $order = "id desc")
	{
		//使用过滤fields
		empty($field) && $field = $this->fields;

		//count
		$count = $model->join($join)->where($where)->count();
		$result = array();

		if ($count) {
			($this->page - 1) * $this->listrows >= $count && $this->failReturn(C("NOT_PAGE_LIST"));

			$result = $model->join($join)->where($where)->page($this->page, $this->listrows)->
			field($field)->order($order)->select();

			$result = array(
				'total' => $count,
				'list' => $result
			);
		}

		return $result;
	}

	/**
	 * 查询关联表数据
	 * @param $join_info array("calls"=>array("order_id",array("order_id"=>array($list_key))))关联健值对应主表行记录索引的健值对
	 * @param $result
	 */
	protected function _left_join($join_info, &$result)
	{
		foreach ($join_info as $table_name => $table_info) {
			//通话记录信息
			if (isset($this->_default[$table_name . '_fields'])) {
				array_push($this->_default[$table_name . '_fields'], $table_info[0]);//方便删除
				$model_name = implode("", array_map("ucfirst", explode("_", $table_name)));//设置模型表名
				$where = array($table_info[0] => array("in", array_keys($table_info[1])));//查询条件

				$res = M($model_name)->where($where)->field($this->_default[$table_name . '_fields'])->select();

				//如果子表信息可能没有，需要返回空，这里一定会有
				if ($res) {
					//todo 有些数据不存在
					foreach ($res as $value) {
						$list_key = $table_info[1][$value[$table_info[0]]];
						unset($value[$table_info[0]]);

						foreach ($list_key as $id_key)
							$result[$id_key] = array_merge($result[$id_key], $value);
					}
				} else {
					array_pop($this->_default[$table_name . '_fields']);
					foreach ($result as &$value) {
						$value = array_merge($value, array_combine_str(array_values($this->_default[$table_name . '_fields']), ""));
					}
					unset($value);
				}
			}
		}
	}

	/**
	 *
	 */
	protected function _initialize()
	{
		//记录请求次数 todo

		//获取参数
		$this->request = I(REQUEST_METHOD . ".");
		unset($this->request['ext'], $this->request['method']);

		//获取自定义参数
		$filter_name = ACTION_NAME_REST . "_conf";
		$filter =& $this->$filter_name;

		//系统级参数,包含API签名
		//app_key(后期不同前端有不同应用ID，用于查找secret_key，前期不做)
		//在没有做app_key之前  只能客户端通过公钥加密  服务端私钥解密
		//secret_key(密钥，32位)
		//sign_type(签名类型：md5，hash，mcrypt，rsa  默认md5)  
		//sign(签名，除sign,sign_type和图片之外的所有参数拼接成字符串并且末尾加上密钥用于签名,参数必须排序，去除末尾空格)  
		//timestamp 时间戳，服务端允许和前端的时间误差为15分钟
		//V3  接口版本号，不做 
		if (isset($filter['authorize'])) {
			$filter['authorize'] && $this->authorize();
		} else {
			$this->authorize && $this->authorize();
		}

		//请求次数限制
		if (isset($filter['limit_request'])) {
			$filter['limit_request'] && $this->limitRequest();
		} else {
			$this->limit_request && $this->limitRequest();
		}

		//用户授权
		if (isset($filter['check_user']) && $filter['check_user']) {
			$this->checkUser();
		} elseif ($this->check_user) {
			$this->checkUser();
		}

		//请求参数过滤
		if (isset($filter['check_fields'])) {
			$this->checkFields($filter);
		}

		unset($filter);
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function __get($name)
	{
		return isset($this->_default[$name]) ? $this->_default[$name] : "";
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		return $this->_default[$name] = $value;
	}

	/**
	 * 用户校验  todo token
	 */
	protected function checkUser()
	{
		if (isset($this->request['token']) && !empty($this->request['token'])) {
			// 解密
			$crypt = new \Org\CoolChatCrypt();
			$crypt->instance('DES');
			list($result, $token) = $crypt->decrypt($this->request['token']);
			// 解密成功
			if ($result) {
				$token = json_decode($token, ture);
				// 验证token过期时间
				if (isset($token['endtime']) && $token['endtime'] > time()) {
					// 查询条件
					$user_cond = array(
						'id' => $token['id'],
						'status' => self::STATUS_USER_VALID
					);
					$user_id = M("Users")->where($user_cond)->getField("id");
					!$user_id && $this->failReturn(C("USER_IS_NOT_EXIST"));
					defined("USER_ID") || define("USER_ID", $user_id);
				} else {
					$this->failReturn(C('USER_STATUS_EXPIRED'));
				}
			} else {
				$this->failReturn(C("USER_INVALID"));
			}
		} else {
			$this->failReturn(C("USER_INVALID"));
		}
	}

	/**
	 * API签名认证
	 * API数据解密
	 * @return bool
	 */
	protected function authorize()
	{
		//系统级参数判断
		$res = \Org\Filter::autoValidation($this->request, $this->_api_fields);

		if (is_array($res)) {
			$this->request['sign_type'] = $res['sign_type'];
		} elseif (empty($res)) {
			parent::DResponse(400);
		} else {
			$msg = C($res);
			if ($msg) {
				$this->failReturn($msg);
			} else {
				parent::DResponse(400);
			}
		}

		//403访问受限
		$this->appIdRequest();

		//timestamp判断 15分钟
		if ((time() - $this->request['timestamp']) > C("API_TIMESTAMP"))
			parent::DResponse(418);

		//401未授权
		switch (strtoupper($this->request['sign_type'])) {
			case 'RSA'://todo app_key  获取公钥
				$conf = array("vendor_public_key_path" => $this->_app_conf['VENDOR_PUBLIC_KEY_PATH']);
				break;
			// case "MCRYPT":
			// 	$conf=array("mcrypt_secret_key"=>$app_conf['COOLCHAT_CRYPT_KEY'];
			// 	break;
			default:
				$conf = array("md5_secret_key" => $this->_app_conf['MD5_SECRET_KEY']);
				break;
		}

		$authorize = new \Org\Authorize($conf);
		$res = $authorize->authVerify($this->request);

		!$res && parent::DResponse(401);

		//如果有加密
		$this->decryptRequest();

		return true;
	}

	/**
	 * 403访问受限，授权过期,通过app_key对session_key的判断，todo
	 */
	protected function appIdRequest()
	{
		$this->_app_conf = C("APP_ID." . $this->request['app_id']);
		!$this->_app_conf && parent::DResponse(403);
	}

	/**
	 * request limit
	 */
	protected function limitRequest()
	{
		//419请求过多被限制
		return true;
	}

	/**
	 * 解密数据到$this->request
	 * @return bool
	 */
	protected function decryptRequest()
	{
		if (isset($this->request['encrypt_data']) && $this->request['encrypt_data']) {
			!isset($this->request['encrypt_type']) && ($this->request['encrypt_type'] = "RSA");

			switch (strtoupper($this->request['encrypt_type'])) {
				case 'MCRYPT':
					$conf = array("mcrypt_secret_key" => $this->_app_conf['COOLCHAT_CRYPT_KEY']);
					break;

				default://rsa
					$conf = array("self_private_key_path" => C("SELF_PRIVATE_KEY_PATH"));
					break;
			}

			$authorize = new \Org\Authorize($conf);
			$this->request = $authorize->authDecrypt($this->request);

			//解密错误
			!$this->request && parent::DResponse(420);
		}

		return true;
	}

	/**
	 * @param $filter
	 */
	protected function checkFields(&$filter)
	{
		//处理多表关联的情况
		if (isset($filter['join_table'])) {
			//记录表以及别名的对应关系array("field_name"=>array("table_name","field_name as alas_name"))
			$fields_map = array();
			//记录将要过滤的fields数组array("support_fields","default_fields")
			$fields_filter = array("support" => array(), "default" => array());

			foreach ($filter['check_fields'] as $key => $fields) {
				$sign_fields_filter = array("support" => array(), "default" => array());

				//包含分表定义
				$pos = strpos($fields['0'], ":fields");
				if ($pos !== false) {
					$table_name = substr($fields[0], 0, $pos);

					//处理字段别名关系
					$fields[1] = explode(",", $fields[1]);
					foreach ($fields[1] as $ala_field_map) {
						$ala_pos = strpos($ala_field_map, ":");
						if ($ala_pos !== false) {
							//处理别名
							$ala_field = substr($ala_field_map, $ala_pos + 1);
							$fields_map[$ala_field] = array($table_name, str_replace(":", " AS ", $ala_field_map));

							$sign_fields_filter["support"][] = $ala_field;
						} else {
							$fields_map[$ala_field_map] = array($table_name, $ala_field_map);
							//记录支持过滤字段
							$sign_fields_filter["support"][] = $ala_field_map;
						}
					}

					//默认
					if (isset($fields[2])) {
						//取反
						if ($fields[2]{0} == "!") {
							$sign_fields_filter['default'] = array_diff($sign_fields_filter['support'],
								explode(",", substr($fields[2], 1)));
						} else {
							$sign_fields_filter['default'] = explode(",", $fields[2]);
						}
					} else {
						$sign_fields_filter["default"] = $sign_fields_filter["support"];
					}

					unset($filter['check_fields'][$key]);

					$fields_filter['support'] = array_merge($fields_filter['support'], $sign_fields_filter['support']);
					$fields_filter['default'] = array_merge($fields_filter['default'], $sign_fields_filter['default']);
				}
			}

			//加入fields过滤字段
			$filter['check_fields'][] = array("fields", implode(",", $fields_filter['support']),
				null, 0, "in", implode(",", $fields_filter['default']));
		}

		$res = \Org\Filter::autoValidation($this->request, $filter['check_fields']);

		if (is_array($res)) {
			//处理多表关联
			if (isset($res['fields']) && isset($fields_map)) {
				//array("table_name_fields"=>"table_name as ala,")
				$res['fields'] = explode(",", $res['fields']);
				foreach ($res['fields'] as $field)
					$res[$fields_map[$field][0] . "_fields"][] = $fields_map[$field][1];

				unset($res['fields']);
			}

			$this->_default = $res;
		} elseif (empty($res)) {
			parent::DResponse(400);
		} else {
			$msg = C($res);
			if ($msg) {
				$this->failReturn($msg);
			} else {
				parent::DResponse(400);
			}
		}
	}
}