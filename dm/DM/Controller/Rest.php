<?php

/**
 * restfull
 * User: jepson <jepson@duomai.com>
 * Date: 16-5-16
 * Time: 下午3:08
 */
abstract class DM_Controller_Rest extends DM_Controller_Common
{
	// 当前请求类型
	protected $_method = '';
	// REST允许的请求类型列表
	protected $_allow_method = array('GET', 'POST', 'PUT', 'DELETE');
	// REST默认请求类型
	protected $_default_method = 'get';
	// 当前请求的资源类型
	protected $_request_type = null;
	// REST允许请求的资源类型列表
	protected $_allow_request_type = array('html', 'xml', 'json');
	// 默认的资源类型
	protected $_default_request_type = 'json';
	//API版本号
	protected $_api_version = "";


	public function init()
	{
		//资源类型检测
		$this->_type = $this->getAcceptType();
		is_null($this->_request_type) && $this->_type = $this->_default_request_type;
		!in_array($this->_request_type, $this->_allow_request_type) && $this->sendHttpError(405);

		// 请求方式检测
		$method = strtoupper($this->getRequest()->getMethod());
		!in_array($method, $this->_allow_method) && $this->sendHttpError(405);
		$this->_method = $method;

		//处理头部，例如API版本 客户端版本
		$version =& $_SERVER[strtoupper($_SERVER['REQUEST_SCHEME']) . "_VERSION"];
		isset($version) && $this->_api_version = 'v' . intval($version);
		unset($version);

		parent::init();
	}

	/**
	 * 获取当前请求的Accept头信息
	 * @return int|null|string
	 */
	protected function getAcceptType()
	{
		$type = array(
			'html' => 'text/html,application/xhtml+xml,*/*',
			'xml' => 'application/xml,text/xml,application/x-xml',
			'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
			'js' => 'text/javascript,application/javascript,application/x-javascript',
			'css' => 'text/css',
			'rss' => 'application/rss+xml',
			'yaml' => 'application/x-yaml,text/yaml',
			'atom' => 'application/atom+xml',
			'pdf' => 'application/pdf',
			'text' => 'text/plain',
			'png' => 'image/png',
			'jpg' => 'image/jpg,image/jpeg,image/pjpeg',
			'gif' => 'image/gif',
			'csv' => 'text/csv'
		);

		foreach ($type as $key => $val) {
			$array = explode(',', $val);
			foreach ($array as $k => $v) {
				if (stristr($_SERVER['HTTP_ACCEPT'], $v)) {
					return $key;
				}
			}
		}
		return null;
	}

	/**
	 * 请求错误
	 * @param $code
	 */
	protected function sendHttpError($code)
	{
		$this->sendHttpStatus($code);
		//todo 输出数据
		exit();
	}
}