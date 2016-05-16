<?php
include(dirname(__FILE__)."/redis.php");
$bucket = "haidai";//test
$list = "img:wait:list:";
$hash = "img:detail:hash:";
$desUser = "star";
$desIp = "122.224.97.132";
$desDir = "/var/www/html/upload/haidai";
$tmpList = "img:tmp:list";

function mylog($data,$isError = 0,$bucket = "test"){
    $pid = posix_getpid();
    $logPath = "/var/www/html/upload/log/".$bucket."/";
    if(!$isError)
        $typeInfo = " (infomation) ";
    else
        $typeInfo = " (error) ";
    file_put_contents($logPath.date("Ymd").".log","[".date("H:i:s")."] [{$pid}] {$typeInfo}".$data.PHP_EOL,FILE_APPEND);
}
    
if(count($argv) > 1 && $argv[1] == 'tmp'){
    mylog("tmp sync start",0,$bucket);
    $redis = redisInstance::getInstance();
    $redis->select(7);
    $tmpLen = $redis->lLen($tmpList);
    if($tmpLen > 0){
        for($i = 0;$i < $tmpLen;$i++){
            $redis->rpoplpush($tmpList,$list.$bucket);
        }
    }
    mylog("tmp sync end,total {$tmpLen}",0,$bucket);
}else{
    exit;
}

try{
    while(true){
        mylog("scp start",0,$bucket);
        $redis = redisInstance::getInstance();
        $redis->select(7);
        $len = $redis->lLen($list.$bucket);
        // mylog("list len {$len}",0,$bucket);
        if($len == 0){
            sleep(1);
            continue;
        }
        $imgName = $redis->rpoplpush($list.$bucket,$tmpList);
        // mylog("list pop {$imgName}",0,$bucket);
        $imgs = $redis->hget($hash.$bucket,$imgName);
        if(empty($imgs)){
            $redis->lRem($tmpList,$imgName,1);
            continue;
        }
        $imgsArr = json_decode($imgs,true);
        if(empty($imgsArr)){
            $redis->lRem($tmpList,$imgName,1);
            continue;
        }
        preg_match("/\/(\d{8})\//",$imgsArr[0],$match);
        $dir = $match[1];
        $files = implode(" ",$imgsArr);
        mylog("img hash {$files}, dir {$dir},scp command:scp {$files} {$desUser}@{$desIp}:{$desDir}/{$dir}",0,$bucket);
        system("scp {$files} {$desUser}@{$desIp}:{$desDir}/{$dir}",$re);
        if($re > 0){
            mylog("scp [error] {$re} img hash {$imgName}",1,$bucket);
            continue;
        }
        $redis->hdel($hash.$bucket,$imgName);
        $redis->lRem($tmpList,$imgName,1);
        mylog("scp end and del hash {$imgName}",0,$bucket);
    }
}catch(Exception $e){
$redis->close();
    mylog($e->getMessage(),1,$bucket);
    exit;
}
?>
