<?php
/**
 * application  functions
 */

/**
 * 输出http头
 * @param null $num
 */
function https($num = null)
{
	!$num && $num = 404;
	$http_header = array(
		100 => "HTTP/1.1 100 Continue",
		101 => "HTTP/1.1 101 Switching Protocols",
		200 => "HTTP/1.1 200 OK",
		201 => "HTTP/1.1 201 Created",
		202 => "HTTP/1.1 202 Accepted",
		203 => "HTTP/1.1 203 Non-Authoritative Information",
		204 => "HTTP/1.1 204 No Content",
		205 => "HTTP/1.1 205 Reset Content",
		206 => "HTTP/1.1 206 Partial Content",
		300 => "HTTP/1.1 300 Multiple Choices",
		301 => "HTTP/1.1 301 Moved Permanently",
		302 => "HTTP/1.1 302 Found",
		303 => "HTTP/1.1 303 See Other",
		304 => "HTTP/1.1 304 Not Modified",
		305 => "HTTP/1.1 305 Use Proxy",
		307 => "HTTP/1.1 307 Temporary Redirect",
		400 => "HTTP/1.1 400 Bad Request",
		401 => "HTTP/1.1 401 Unauthorized",
		402 => "HTTP/1.1 402 Payment Required",
		403 => "HTTP/1.1 403 Forbidden",
		404 => "HTTP/1.1 404 Not Found",
		405 => "HTTP/1.1 405 Method Not Allowed",
		406 => "HTTP/1.1 406 Not Acceptable",
		407 => "HTTP/1.1 407 Proxy Authentication Required",
		408 => "HTTP/1.1 408 Request Time-out",
		409 => "HTTP/1.1 409 Conflict",
		410 => "HTTP/1.1 410 Gone",
		411 => "HTTP/1.1 411 Length Required",
		412 => "HTTP/1.1 412 Precondition Failed",
		413 => "HTTP/1.1 413 Request Entity Too Large",
		414 => "HTTP/1.1 414 Request-URI Too Large",
		415 => "HTTP/1.1 415 Unsupported Media Type",
		416 => "HTTP/1.1 416 Requested range not satisfiable",
		417 => "HTTP/1.1 417 Expectation Failed",
		500 => "HTTP/1.1 500 Internal Server Error",
		501 => "HTTP/1.1 501 Not Implemented",
		502 => "HTTP/1.1 502 Bad Gateway",
		503 => "HTTP/1.1 503 Service Unavailable",
		504 => "HTTP/1.1 504 Gateway Time-out"
	);

	header(isset($http_header[$num]) ? $http_header[$num] : $http_header[404]);
}

/**
 * 测试中断输出,支持字符串、数组、对象
 * @param $argv
 * @param bool $done
 */
function pre($argv, $done = true)
{
	header("Content-type:text/html;charset=" . C("DEFAULT_CHARSET"));
	echo '<pre>';

	if (is_string($argv))
		echo $argv;
	else
		print_r($argv);

	$done && exit();
}

/**
 * 到处excel表格,
 * @param $field  array("字段key","表头")
 * @param $data
 * @param string $filename
 * @param null $save_dir 是否存储，提高导出效率
 * @param string $type xlsx,xls类型
 * @return bool
 */
function export_excel($field, $data, $filename = "download_excel", $save_dir = null, $type = "5")
{
	//获取标题和内容
	if (!is_array($data) || !is_array($field))
		return false;
	else
		$data = array_values($data);

	//save_dir
	$tmp = true;
	$save_name = "";
	if ($save_dir) {
		!is_dir($save_dir) && mkdir($save_dir, 0777, true);
		$save_dir = trim($save_dir, "/") . "/";

		$save_name = $save_dir . md5(serialize($field) . serialize($data));
		is_file($save_name) && is_readable($save_name) && $tmp = false;
	}

	//type
	$excel_map = array(
		"2007" => "xlsx",
		"5" => "xls"
	);

	!isset($excel_map[$type]) && $type = "2005";

	if ($tmp) {
		//new
		vendor("PHPExcel.Classes.PHPExcel");
		$php_excel = new PHPExcel();

		//设置sheet标题
		$php_excel->getActiveSheet()->setTitle($filename);

		$php_excel->setActiveSheetIndex(0);

		$i = 1;
		foreach ($field as $key => $val) {
			//合并列，设置背景颜色,设置字体颜色

			//列名
			if ($i <= 26) {
				$col = chr($i + 64);
			} elseif ($i % 26 == 0) {
				$col = chr(intval(floor($i / 26)) + 63) . chr(90);
			} else {
				$col = chr(intval(floor($i / 26)) + 64) . chr($i % 26 + 64);
			}

			//设置字体大小
			$php_excel->getActiveSheet()->getStyle($col . "1")->getFont()->setSize(12);

			//设置列的宽度自动
			$php_excel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

			//设置列
			$php_excel->getActiveSheet()->setCellValue($col . "1", iconv(C("DEFAULT_CHARSET"), "utf-8", $val));

			for ($j = 0; $j < count($data); $j++) {
				$php_excel->getActiveSheet()->setCellValue($col . ($j + 2), iconv(C("DEFAULT_CHARSET"), "utf-8", isset($data[$j][$key]) ? $data[$j][$key] : ""));
			}

			$i++;
		}

		//输出xlsx 2007
		$obj_write = PHPExcel_IOFactory::createWriter($php_excel, "Excel{$type}");

		$save_dir && $obj_write->save($save_name);
	}

	//输出
	//清空输出缓存区
	ob_clean();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	header("Content-Type:application/force-download");
	header("Content-Type: application/vnd.ms-excel;");
	header("Content-Type:application/octet-stream");
	header("Content-Type:application/download");
	header("Content-Disposition:attachment;filename=" . $filename . "." . $excel_map[$type]);
	header("Content-Transfer-Encoding:binary");

	if ($tmp)
		$obj_write->save("php://output");
	else {
		echo file_get_contents($save_name);
	}
}
