<?php
/**
 * application  functions
 */

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

if (!function_exists('array_column')) {
	function array_column($input, $column_key, $index_key = null)
	{
		if ($index_key !== null) {
			// Collect the keys
			$keys = array();
			$i = 0; // Counter for numerical keys when key does not exist

			foreach ($input as $row) {
				if (array_key_exists($index_key, $row)) {
					// Update counter for numerical keys
					if (is_numeric($row[$index_key]) || is_bool($row[$index_key])) {
						$i = max($i, (int)$row[$index_key] + 1);
					}

					// Get the key from a single column of the array
					$keys[] = $row[$index_key];
				} else {
					// The key does not exist, use numerical indexing
					$keys[] = $i++;
				}
			}
		}

		if ($column_key !== null) {
			// Collect the values
			$values = array();
			$i = 0; // Counter for removing keys

			foreach ($input as $row) {
				if (array_key_exists($column_key, $row)) {
					// Get the values from a single column of the input array
					$values[] = $row[$column_key];
					$i++;
				} elseif (isset($keys)) {
					// Values does not exist, also drop the key for it
					array_splice($keys, $i, 1);
				}
			}
		} else {
			// Get the full arrays
			$values = array_values($input);
		}

		if ($index_key !== null) {
			return array_combine($keys, $values);
		}

		return $values;
	}

	if (!function_exists("http_build_str")) {
		function http_build_str($array = array(), $prefix = "", $sep = "&")
		{
			$query_string = "";
			foreach ($array as $key => $val)
				$query_string .= $prefix . $key . "=" . $val . $sep;

			return $query_string ? substr($query_string, 0, -1) : false;
		}
	}
}
