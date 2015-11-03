<?php

namespace Common\Controller;

use Think\Controller;
//
class ApiBaseController extends Controller {

	protected $request_vars;
	protected $data;
	protected $http_accept;
	protected $method;

	public function __construct()
	{
		$this->request_vars      = array();
		$this->data              = '';
		$this->http_accept       = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
		// get our verb
		$request_method = strtolower($_SERVER['REQUEST_METHOD']);
		// we'll store our data here
		$data			= array();

		switch ($request_method)
		{
			// gets are easy...
			case 'get':
				$data = $_GET;
				break;
			// so are posts
			case 'post':
				$data = $_POST;
				break;
			// here's the tricky bit...
			case 'put':
				// basically, we read a string from PHP's special input location,
				// and then parse it out into an array via parse_str... per the PHP docs:
				// Parses str  as if it were the query string passed via a URL and sets
				// variables in the current scope.
				parse_str(file_get_contents('php://input'), $put_vars);
				$data = $put_vars;
				break;
		}

		// store the method
		$this->setMethod($request_method);

		// set the raw data, so we can access it if needed (there may be
		// other pieces to your requests)
		$this->setRequestVars($data);

		if(isset($data['data']))
		{
			// translate the JSON to an Object for use however you want
			$this->setData(json_decode($data['data']));
		}
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function setMethod($method = 'get')
	{
		$this->method = $method;
	}

	public function setRequestVars($request_vars)
	{
		$this->request_vars = $request_vars;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function getHttpAccept()
	{
		return $this->http_accept;
	}

	public function getRequestVars()
	{
		return $this->request_vars;
	}



}

?>

