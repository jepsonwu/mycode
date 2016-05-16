<?php
error_reporting(0);
include("sampleServerBuckets.php");
include("uploadServer.php");
$allowTypes = array("image/png","image/jpeg","image/gif");
//针对tm商标项目的其他文件类型
if($_POST['bucket'] == "tm"){
    array_push($allowTypes,"application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document","application/pdf","image/bmp","image/tiff");
}
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
$waterTypeArr = array(
    0,//无水印
    1,//只水印主图片
    2,//只水印缩略图
    3,//水印主图和缩略图
);
//需要加水印的图片空间
if($_POST['bucket'] == 'haidai'){
    if(isset($_POST['watertype']) && $_POST['watertype'] && in_array($_POST['watertype'],$waterTypeArr)){
        if(isset($_POST['waterplace']) && $_POST['waterplace']){
            $server = new uploadServer($path,$allowTypes,4194304,$_POST['thumbs'],$buckets[$_POST['bucket']]['domain'],$buckets[$_POST['bucket']]['water']['img'],$_POST['watertype'],$_POST['waterplace']);
        }else{
            $server = new uploadServer($path,$allowTypes,4194304,$_POST['thumbs'],$buckets[$_POST['bucket']]['domain'],$buckets[$_POST['bucket']]['water']['img'],$_POST['watertype'],$buckets[$_POST['bucket']]['water']['place']);
        }
    }else{
        $server = new uploadServer($path,$allowTypes,4194304,$_POST['thumbs'],$buckets[$_POST['bucket']]['domain'],$buckets[$_POST['bucket']]['water']['img'],intval($buckets[$_POST['bucket']]['watertype']),$buckets[$_POST['bucket']]['water']['place']);
    }
}else{
    $server = new uploadServer($path,$allowTypes,4194304,$_POST['thumbs'],$buckets[$_POST['bucket']]['domain']);
}
$server->upload($_FILES['file']);
