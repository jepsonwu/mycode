<?php
/**
 * User: Star
 * Date: 14-5-26
 * Time: 下午4:31
 * To change this template use File | Settings | File Templates.
 */

define("DM_UPLOAD_ROOT",dirname(__FILE__));
include(DM_UPLOAD_ROOT."/config.php");
include(DM_UPLOAD_ROOT."/Client.php");

$sample = new DM_Upload_Client();
// $sample->setOptions(DM_ACCESS_KEY,"tm",array(array(200,200)),1);
$sample->setOptions(DM_ACCESS_KEY,"haidai");
// $sample->setOptions(DM_ACCESS_KEY,"haidai",array(),1);
// $sample->setOptions(DM_ACCESS_KEY,"haidai",array(array(100,100)),1);
$data = $sample->uploadFile(DM_UPLOAD_ROOT."/test.jpg",DM_UP_HOST);
echo($data);
// $deleteData = $sample->deleteFile(json_encode(array("http://upload-haimi.duomai.com/20141120/147ccfdac887bcac4f4f47f0a3bf7e65.jpeg")),DM_DEL_HOST);
// $deleteData = $sample->deleteFile(json_encode(array("5858e732c3768f55a5bbc2202ed4d1d9.jpeg","83f5042dcb704029a1568413d945578e.jpeg")),DM_DEL_HOST);
// echo ($deleteData);
