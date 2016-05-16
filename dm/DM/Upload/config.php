<?php
/**
 * User: Star
 * Date: 14-5-26
 * Time: 下午4:11
 * To change this template use File | Settings | File Templates.
 */

define("SDK_VER","1.0");

$env = getenv("UPLOAD_ENV");
if($env == "local"){
    define("DM_UP_HOST",'http://system.duomai.cm/DM/Upload/sampleServerUpload.php');//local
    define("DM_DEL_HOST",'http://system.duomai.cm/DM/Upload/sampleServerDelete.php');//local
}elseif($env == "130"){
    define("DM_UP_HOST",'http://upfile.haimi.com/sampleServerUpload.php');//130 daigou
    define("DM_DEL_HOST",'http://upfile.haimi.com/sampleServerDelete.php');//130
}elseif($env == "guolei"){
    define("DM_UP_HOST",'http://upload.duomai.com/sampleServerUpload.php');//132
    define("DM_DEL_HOST",'http://upload.duomai.com/sampleServerDelete.php');//132
}else{
    exit("error env");
}

define("DM_ACCESS_KEY",'dm system duomai.com');
define("DM_SECRET_KEY",'<Dont send your secret key to anyone>');


