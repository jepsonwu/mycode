<?php
include("sampleServerBuckets.php");
include("uploadServer.php");
$allowTypes = array("image/png","image/jpeg","image/gif");
$path = $buckets[$_POST['bucket']]['path'];
if(!$path){
        echo json_encode(array('flag'=>false,'msg'=>'no path'));
        exit;
}
$accessKey = $_POST['access_key'];
if($accessKey !== $accessLocalKey){
    echo json_encode(array('flag'=>false,'msg'=>'access key error!'));
    exit;
}
$server =@ new uploadServer($path,$allowTypes,2097152,$_POST['thumbs'],$buckets[$_POST['bucket']]['domain']);
$server->delete($_POST['file']);
