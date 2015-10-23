<?php
/*
 * php ./file/format_row.php "bt_file=../$bt_file&fields=../$dt_field&bt_tmp=../$bt_file_tmp"
 * php ./format_row.php "bt_file=../data/bt_file_simba_nala&fields=../data/db_filed_simba_nala&bt_tmp=../data/bt_file_tmp_simba_nala&db=simba_nala"
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

$keys = array('bt_file', 'db_primary', 'bt_tmp', 'db', 'db_autoinc', 'map_file', 'upd_file', 'type', 'data_dir');
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
$fields_arr = array();


#只获取INSERT UPDATE 语句
#替换自增id 生成map

#表和主键对照表
$db_primary_arr = array();
if (is_file($db_primary) && is_readable($db_primary) && filesize($db_primary)) {
	$str = file_get_contents($db_primary);
	$arr = explode("\n", $str);

	foreach ($arr as $v) {
		$v = trim($v);
		if ($v) {
			$pos = strpos($v, "=");
			$db_primary_arr[substr($v, 0, $pos)] = substr($v, $pos + 1);
		}
	}
} else {
	echo "db_primary is not found!\n";
	exit;
}

#自增id最大值
$db_autoinc_arr = array();
if (is_file($db_autoinc) && is_readable($db_autoinc) && filesize($db_autoinc)) {
	$str = file_get_contents($db_autoinc);
	$arr = explode("\n", $str);

	foreach ($arr as $v) {
		$v = trim($v);
		if ($v) {
			$pos = strpos($v, "=");
			$inc = substr($v, $pos + 1);
			if ($inc != 'NULL')
				$db_autoinc_arr[substr($v, 0, $pos)] = $inc;
		}
	}
} else {
	echo "db_autoinc is not found!\n";
	exit;
}

$mix_error_file = $data_dir . 'mixed_error_file_' . $db;
file_put_contents($mix_error_file, "");

$dump_error_file = $data_dir . 'dump_error_file_' . $db;
file_put_contents($dump_error_file, "");

$offset = 0;
$maxlen = 1024 * 1024;
$filename = str_replace('\\', '/', $bt_file);

$sub_str = ($type == 'binlog') ? "/*!*/;" : "/*!";
$sub_con = ($type == 'binlog') ? 6 : 3;

if (is_file($filename) && is_readable($filename) && filesize($filename) > 0) {
	file_put_contents($bt_tmp, "use {$db};\n", FILE_APPEND);

	#这里一定要放外面，因为对于mysqldump的格式，插入分了多条，然后正好一条是1M左右，本来tmp是true的，但是这里回到了do循环，又被重置了
	$str = '';
	$tmp = false;

	do {
		$text = file_get_contents($filename, null, null, $offset, $maxlen);
		$arr = explode("\n", $text);
		$str_of = array_pop($arr);

		foreach ($arr as $line) {
			$line = trim($line);

			if ($line) {
				if ((substr($line, 0, $sub_con) == $sub_str) || ($type == 'dump' && $tmp && substr($line, 0, 6) == 'INSERT')) {
					$tmp = false;

					if ($str) {
						#mysqldump
						if (substr($str, -1) == ";")
							$str = substr($str, 0, -1);
						#mysqlbinlog
						if (substr($str, -2) == "*/")
							$str = substr($str, 0, strpos($str, "/*"));

						if (strpos($str, "ON DUPLICATE KEY") == false) {
							$str_tmp = str_replace(
								array(" `",
									'`',
									' '),
								'', $str);

							$fields = $values = array();

							switch (substr($str_tmp, 0, 6)) {
								#两种情况：
								#1.有自增id，插入也有自增id。2.有自增id，插入没有自增id
								#1.通过id更新2.不通过id更新3.只有一种情况无法预测：没有自增id插入，却使用自增id更新

								case 'INSERT':
									$k_pos = strpos($str_tmp, "(");
									$table = substr($str_tmp, strpos($str_tmp, "INTO") + 4, $k_pos - strpos($str_tmp, "INTO") - 4);

									if (isset($db_primary_arr[$table]) && strpos($str_tmp, $db_primary_arr[$table]) != false) {
										//剔除包含逗号的语句
										$v_pos = strpos($str_tmp, "VALUES");
										$fields = array_flip(explode(",", substr($str_tmp, $k_pos + 1, $v_pos - 2 - $k_pos)));
										$field_count = count($fields);

										$key = & $fields["{$db_primary_arr[$table]}"];
										#这里在做一次判断，保证是字段而不是普通字符
										if (isset($key)) {
											$values = explode("),(", substr($str_tmp, $v_pos + 7, -1));

											#binlog 处理方式
											if ($type == 'binlog') {

												foreach ($values as $k => $v) {
													#过滤逗号,|;  mysqlbinlog 都是','  mysqldump 存在,间隔
													$v = substr($v, 1, -1);
													$v = str_replace(
														array("','",
															"'",
															";",
															",",
															"@#@"
														), array("@#@",
														"‘",
														"；",
														"，",
														","
													), $v);

													$v = explode(",", $v);

													if ($field_count == count($v)) {
														$values[$k] = $v;
													} else {
														unset($values[$k]);
														#echo "field count con't match values count\n";
														file_put_contents($mix_error_file, $table . ":" . implode(",", $v) . "\n", FILE_APPEND);
													}
												}

												if (empty($values)) {
													continue;
												}

												foreach ($values as $k => $v) {
													file_put_contents($map_file, $table . '_' . $values[$k][$key] . '='
														. $db_autoinc_arr[$table] . "&", FILE_APPEND);

													$values[$k][$key] = $db_autoinc_arr[$table];
													$db_autoinc_arr[$table] = $db_autoinc_arr[$table] + 1;
												}

												foreach ($values as $k => $v) {
													$values[$k] = "('" . implode("','", $v) . "')";
												}

												$str = "INSERT INTO `{$table}` " . '(`' . implode("`,`", array_flip($fields)) . '`) VALUES'
													. implode(",", $values);
											} else {
												#mysqldump处理方式，默认第一个为主键
												foreach ($values as $k => $v) {
													$old_pos = strpos($v, ",");
													$old_id = substr($v, 0, $old_pos);

													file_put_contents($map_file, $table . '_' . $old_id . '='
														. $db_autoinc_arr[$table] . "&", FILE_APPEND);

													$values[$k] = $db_autoinc_arr[$table] . substr($v, $old_pos);
													$db_autoinc_arr[$table] = $db_autoinc_arr[$table] + 1;

													#原本就值与键无法匹配的
													if ($field_count != count(explode(",", $v))) {
														#echo "field count con't match values count\n";
														file_put_contents($dump_error_file, $table . ":" . $v . "\n", FILE_APPEND);
													}
												}

												$str = "INSERT INTO `{$table}` " . '(`' . implode("`,`", array_flip($fields)) . '`) VALUES('
													. implode("),(", $values) . ")";
											}

											unset($key);
										}
									}
									break;
								case 'UPDATE':
									#这里有一个问题，当字段类型为emun时，row类型的sql是只显示数值对应的数字的。如：
									#trade 表 `status` = '2'  就表示 `status` = 'PAY'
									#这个时候插入是没有问题的，可是修改的时候就无法找到了
									#对于row类型，如果有主键，我们只取主键部分，可以解决了上面的问题，todo 如果没有主键怎么办

									$str_tmp = str_replace("'", "", $str_tmp);
									$table = substr($str_tmp, strpos($str_tmp, "UPDATE") + 6, strpos($str_tmp, "SET") - 6);

									if (isset($db_primary_arr[$table])) {
										$str_tmp = str_replace(array('(', ')'), '', $str_tmp);
										#row类型的语句，肯爹
										if (strpos($str_tmp, "SET{$db_primary_arr[$table]}") != false) {
											$str_tmp = substr($str_tmp, 0, strpos($str_tmp, "SET")) . "SET" .
												substr($str_tmp, strpos($str_tmp, ",") + 1);
											$str = substr($str, 0, strpos($str, "SET")) . "SET " . substr($str, strpos($str, ",") + 1);
										}


										$u_len = strlen($db_primary_arr[$table]);

										$u_pos = strpos($str_tmp, $db_primary_arr[$table] . "=");
										$and_pos = strpos($str_tmp, "AND");
										$and_pos || $and_pos = null;

										if (strpos($str_tmp, $db_primary_arr[$table] . "=") != false) {
											file_put_contents($upd_file, $table . '_' . substr($str_tmp, $u_pos + $u_len + 1, $and_pos - $u_pos - $u_len - 1)
												. '#' . $str . "\n", FILE_APPEND);

											$str = '';
											continue;
										}
									}
									break;
							}
						}

						file_put_contents($bt_tmp, $str . ";\n", FILE_APPEND);
						$str = '';
					}
				}

				if (in_array(substr($line, 0, 6), array('INSERT', 'UPDATE')) || $tmp) {
					$str = $str . $line;
					$tmp = true;
				}
			}
		}

		$offset += $maxlen - strlen($str_of);
		echo round($offset / $maxlen) . "mb\n";
		echo date("H:i:s", time()) . "\n";
	} while (strlen($text) >= $maxlen);
} else {
	echo "bt_file is not found," . $bt_file . "\n";
}

$upd_error_file = $data_dir . 'update_error_file_' . $db;
file_put_contents($upd_error_file, "");

#$maxlen = 1024 * 10;
#Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini
#todo 这里耗时很长，因为id_map文件会月来越大，平均1M数据要2秒

$offset = $offset1 = 0;
if (is_file($upd_file) && is_readable($upd_file) && filesize($upd_file) > 0) {
	echo "doing replace update sql\n";

	if (is_file($map_file) && is_readable($map_file) && filesize($map_file) > 0) {

		do {
			$text = file_get_contents($upd_file, null, null, $offset, $maxlen);
			$arr = explode("\n", $text);
			$str = array_pop($arr);

			foreach ($arr as $v) {
				$u_key = substr($v, 0, strpos($v, "#"));
				$u_id = substr($u_key, strrpos($u_key, "_") + 1); #id
				$u_id_len = strlen($u_id); #id长度
				$u_tmp = true;

				do {
					$text1 = file_get_contents($map_file, null, null, $offset1, $maxlen);
					$str1 = strrpos($text1, "&");
					$text1 = substr($text1, 0, $str1);
					$text2 = explode("&", $text1);
					$arr1 = array();

					foreach ($text2 as $kk => $vv) {
						$t_pos = strpos($vv, "=");
						$arr1[substr($vv, 0, $t_pos)] = substr($vv, $t_pos + 1);
					}

					if (isset($arr1) && $arr1 && isset($arr1[$u_key])) {
						#有两种情况，有主键还有其他的条件，只有主键
						#从开始截取到id即可
						$uu_pos = strpos($v, "#");
						$uu_str = (strpos($v, "='{$u_id}") != false) ? "='{$u_id}" : "={$u_id}";

						$v = substr($v, $uu_pos + 1, strpos($v, $uu_str) - $uu_pos - 1) . "=" . $arr1[$u_key];
						file_put_contents($bt_tmp, $v . ";\n", FILE_APPEND);

						$u_tmp = false;
						break;
					}

					$offset1 += $str1;
				} while (strlen($text1) >= $maxlen);

				if ($u_tmp) {
					#echo "id is not map\n";
					file_put_contents($upd_error_file, $v . "\n", FILE_APPEND);
				}
			}

			$offset += $maxlen - strlen($str);
			echo round($offset / $maxlen) . "mb\n";

		} while (strlen($text) >= $maxlen);

	} else {
		echo "di map file is not found\n";
	}
} else {
	echo "update tmp file is not found\n";
}

echo date("H:i:s", time()) . "\n";

