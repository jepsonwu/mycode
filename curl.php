<?php
echo '<pre>';
parse_str(file_get_contents("php://input"),$argvs);
$argvs=array_merge($_GET,$_POST,$argvs);

$type=$_SERVER['REQUEST_METHOD'];
echo $type;

// print_r($argvs);