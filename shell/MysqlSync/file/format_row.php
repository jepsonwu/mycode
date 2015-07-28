<?php
/*
 *
 * php ./format_row.php "bt_file=../data/bt_file_simba_nala&fields=../data/db_filed_simba_nala&bt_tmp=../data/bt_file_tmp_simba_nala&db=simba_nala"
 *
 *
 */
if (php_sapi_name() != 'cli') {
	exit('cron must run in cli mode!');
}

set_time_limit(0);

$loadedExt = array_map('strtolower', get_loaded_extensions());
foreach (array('mbstring') as $ext) {
	if (!in_array($ext, $loadedExt)) {
		exit("EXIT_NO_{$ext}_EXT");
	}
}
unset($loadedExt, $ext);

mb_internal_encoding('UTF-8');

if (!isset($argv[1]))
	exit("EXIT_NO_QUERY_STRING");

$keys = array('bt_file', 'fields', 'bt_tmp', 'db');
parse_str($argv[1], $value);

foreach ($keys as $key) {
	if (!isset($value[$key])) {
		exit("EXIT_NO_{$key}_PAR");
	} else {
		$$key = trim($value[$key]);
		global $$key;
	}
}

ini_set('memory', 2048);
echo date("H:i:s", time()) . "\n";
$fields_arr = $fields_arr_key = array();


#INSERT SET 加上逗号
#UPDAE SET WHERE 对换 加上逗号

if (is_file($fields) && is_readable($fields) && filesize($fields) > 0) {

	$fields = file_get_contents($fields);
	$arr = explode("\n", $fields);
	foreach ($arr as $v) {
		$v = trim($v);
		if (strlen($v) > 0) {
			$pos = strpos($v, "#");
			$e_pos = strpos($v, "=");
			$fields_arr[substr($v, 0, $pos)][substr($v, $pos + 1, $e_pos - $pos - 1)] = substr($v, $e_pos + 1);
		}
	}

	echo "fields done\n";

	$offset = 0;
	$maxlen = 1024 * 1024;
	$filename = str_replace('\\', '/', $bt_file);

	if (is_file($filename) && is_readable($filename) && filesize($filename) > 0) {

		do {
			$text = file_get_contents($filename, null, null, $offset, $maxlen);
			$arr = explode("\n", $text);
			$str = array_pop($arr);

			$str = '';

			foreach ($arr as $line) {
				if (substr($line, 0, 3) == "###") {
					#特殊字符过滤：字符串里的'|负数接()|\x字符
					#,|;|
					$line = trim(substr($line, 4));
					$line = str_replace(array("\\x", ",", ";"), array("", "，", "；"), $line);

					switch (substr($line, -1)) {
						case "'":
							$line = substr($line, 0, strpos($line, "'") + 1) .
								str_replace("'", "‘", substr($line, strpos($line, "'") + 1, -1)) .
								"'";
							break;
						case ")":
							$a_pos = strpos($line, "=");
							$line = substr($line, 0, $a_pos) . "='" . substr($line, $a_pos + 1, strpos($line, "(") - 1 - $a_pos) . "'";
							break;
						default:
							$line = preg_replace("/(@[0-9]+=)(.*)/", "\$1'\$2'", $line);
							break;
					}

					if (in_array(substr($line, 0, 6), array('UPDATE', 'INSERT')) && strlen($str) > 0) {
						exec_sql();
						$str = '';
					}


					$str .= " " . trim($line);
				} else {
					if (strlen($str) > 0) {
						exec_sql();
					}
					$str = '';
				}
			}

			$offset += $maxlen - strlen($str);
			echo round($offset / $maxlen) . "mb\n";
			echo date("H:i:s", time()) . "\n";
		} while (strlen($text) >= $maxlen);
	} else {
		echo "bt_file is not error," . $bt_file . "\n";
	}

} else {
	echo "fields file is error," . $fields . "\n";
}
unset($fields_arr);

echo date("H:i:s", time()) . "\n";


function exec_sql()
{
	global $str, $fields_arr, $db, $bt_tmp;

	$pos = strpos($str, "`.`");
	$table = substr($str, $pos + 3, strrpos($str, "`") - $pos - 3); #多个UPDATE 连在一起

	if (isset($fields_arr[$table])) {
		$values = array_values($fields_arr[$table]);
		$fields_arr_tmp = $fields_arr[$table];
		krsort($fields_arr_tmp);


		$keys = $values1 = $values2 = array();
		foreach ($fields_arr_tmp as $k => $v) {
			$keys[] = " @{$k}=";
			$values1[] = ",`" . $v . "`=";
			$values2[] = " AND `" . $v . "`=";
		}

		$str = trim($str);

		if (strlen($str) > 0) {
			switch (substr($str, 0, 6)) {
				case "INSERT":
					$str = str_replace($keys, ',', $str);

					$str = str_replace(array("`{$db}`.", "SET,"), array('', "(`" . implode("`,`", $values) . "`)VALUES("), $str) . ")";
					break;
				case "UPDATE":
					$s_pos = strpos($str, "SET");
					$w_pos = strpos($str, "WHERE");
					$str = substr($str, 0, $w_pos) . str_replace($keys, $values1, substr($str, $s_pos))
						. " " . str_replace($keys, $values2, substr($str, $w_pos, $s_pos - $w_pos));

					$str = str_replace(array("`{$db}`.", "SET,", "WHERE AND"), array('', "SET ", "WHERE"), $str);
					break;
			}

			file_put_contents($bt_tmp, $str . "\n/*!*/;\n", FILE_APPEND);
		}
	} else {
		file_put_contents("/data0/simba/tools/shell/MysqlSync/data/no_field_sql", $str . "\n/*!*/;\n", FILE_APPEND);
	}

}