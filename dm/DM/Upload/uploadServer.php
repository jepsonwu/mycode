<?php
/**
 * User: Star
 * Date: 14-5-26
 * Time: 下午4:31
 *
 * eg:
 *
 * 客户端设置
DM_UP_HOST      上传url
DM_DEL_HOST"    删除url
DM_ACCESS_KEY   访问key
 *
 *
 * 服务端设置
 * 设置允许上传类型、定义好对应bucket的文件夹目录，上传使用upload方法，删除使用delete方法。
$accessKey = $_POST['access_key'];
if($accessKey !== $setting){
    echo json_encode(array('flag'=>false,'msg'=>'access key error!'));
    exit;
}
$allowTypes = array("image/png","image/jpeg","image/gif");
switch($_POST['bucket']){
case "test":
$path = "/var/www/html/duomai-cpc/public/testupload/";
break;
default:
$path = "";
}

$test = new uploadServer($path,$allowTypes,2097152,$_POST['thumbs']);
//$test->upload($_FILES['file']);
 *
 * 上传成功返回格式：
 * {"flag":true,"main_file":"2856e10261b07562b7691781a551a67d.jpg","thumb":["2856e10261b07562b7691781a551a67d.jpg200_200.jpg",...]}
 *失败：
 * {"flag":false,"msg":"error msg"}
 *
 *
$test->delete($_POST['file']);
 * 删除成功：
 * {"flag":true,"files":["2856e10261b07562b7691781a551a67d.jpg","2856e10261b07562b7691781a551a67d.jpg100_100.jpg","2856e10261b07562b7691781a551a67d.jpg200_200.jpg"]}
 *
 * 失败：
 * {"flag":false,"msg":"error msg"}
 */
include(dirname(__FILE__)."/redis.php");
class uploadServer{

    protected $_allowTypes = array();
    protected $_maxSize = 2097152;//default is 2MB=1024*1024*2B
    protected $_error = 0;
    protected $_thumbs = array();
    protected $_domain = null;
    protected $_water = null;
    protected $_waterType = null;
    protected $_waterPlace = null;
    protected $_root = null;
    protected $_date = null;
    protected $_bucket = null;

    /**
     * mime type的映射表，需要应用时不断完善
     * @var array
     */
    protected $_mimeTypes = array(
        "image/png" =>  'png',
        "image/jpeg"=>  'jpeg',
        "image/gif" =>  'gif',
        "application/msword"    =>  'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   =>  'docx',
        'application/pdf'   =>  'pdf',
        'image/bmp' =>  'bmp',
        'image/tiff'    =>  'tiff',
    );
    /**
     * 初始化，允许上传类型和文件大小
     *
     * @param null $allowTypes
     * @param null $allowSize
     */
    public function __construct($path,$allowTypes = null,$allowSize = null,$thumbs = array(),$domain = null,$water = null,$watertype = null,$waterPlace = null){
        if(!empty($allowTypes)){
            $this->_allowTypes = $allowTypes;
        }
        if(!$allowSize){
            $this->_maxSize = $allowSize;
        }
        $this->_bucket = $_POST['bucket'];
        $this->_domain = $domain;
        $this->_thumbs = $thumbs;
        $this->_root = $path;
        $this->_path = $this->getPath($path);
        $this->_water = $water;
        $this->_waterType = $watertype;
        $this->_waterPlace = $waterPlace;
    }

    public function getPath($path = null)
    {
        $this->_date = date("Ymd");
        $path = $path.$this->_date."/";
        if(!is_dir($path)){
            mkdir($path);
        }
        return $path;
    }

    public function setThumbs($thumbs){
        $this->_thumbs = $thumbs;
    }

    public function setWater($water){
        $this->_water = $water;
    }

    public function setWaterType($waterType){
        $this->_waterType = $waterType;
    }

    /**
     * 检查上传类型
     *
     * @param $checkType
     * @return bool
     * @throws Exception
     */
    private function checkType($checkType){
        if(empty($this->_allowTypes)){
            return true;
        }else{
            if(!in_array($checkType,$this->_allowTypes)){
                throw new Exception("文件类型{$checkType}不允许");
            }
        }
    }

    /**
     * 检查文件尺寸
     *
     * @param $checkSize
     * @throws Exception
     */
    private function checkSize($checkSize){
        if($checkSize > $this->_maxSize){
            throw new Exception("文件超出大小");
        }
    }

    /**
     * 检查上传错误编号
     *
     * @param $errorNum
     * @return bool
     * @throws Exception
     */
    private function checkError($errorNum){
        switch ($errorNum){
            case 0 :
                return true;
                break;
            case 1 :
            case 2 :
                throw new Exception("文件超出大小");
                break;
            case 3 :
                throw new Exception("错误上传文件");
                break;
            case 4 :
                throw new Exception("没有选择文件");
                break;
            default :
                throw new Exception("系统错误");
                break;
        }
    }

    /**
     * 通过mime type 获取文件后缀名
     * 自建映射关系
     */
    private function getType($mimeType){
        return $this->_mimeTypes[$mimeType];
    }

    /**
     * 上传文件
     *
     * @param $fileInfo
     * @return string
     */
    public function upload($fileInfo,$newName = null){
        try{
            $data = array();
            $data['flag'] = true;
            $rdata = array();
            $mimeType = mime_content_type($fileInfo['tmp_name']);
            $this->checkError($fileInfo['error']);
            $this->checkType($mimeType);
            $this->checkSize($fileInfo['size']);
            $info = $this->getType($mimeType);
            if(empty($newName)){
                $time = microtime();
                $newName = md5($time.mt_rand()).".".$info;
            }
            if(!is_uploaded_file($fileInfo['tmp_name'])){
                throw new Exception("非法上传文件");
            }
            if(move_uploaded_file($fileInfo['tmp_name'], $this->_path.$newName)){
                $data['main_file'] = $this->_domain.$this->_date."/".$newName;
                $rdata[] = $this->_path.$newName;
                $image = new Image();
                if($this->_waterType == 3){
                    $image->watermarkImage($this->_path.$newName,$this->_waterPlace,$this->_water);
                }
                if(!empty($this->_thumbs)){
                    $thumbs = @json_decode($this->_thumbs);
                    if(is_array($thumbs) && !empty($thumbs)){
                        foreach($thumbs as $thumb){
                            $returnFileName = $newName.$thumb[0]."_".$thumb[1].".".$info;
                            $thumbFileName = $this->_path.$returnFileName;
                            $data['thumb'][] = $this->_domain.$this->_date."/".$returnFileName;
                            $rdata[] = $thumbFileName;
                            $image->resizeImage($this->_path.$newName,$thumb[0],$thumb[1],0,$thumbFileName);
                            if($this->_waterType == 2){
                                $image->watermarkImage($thumbFileName,$this->_waterPlace,$this->_water);
                            }
                        }
                    }
                }
                if($this->_waterType == 1){
                    $image->watermarkImage($this->_path.$newName,$this->_waterPlace,$this->_water);
                }
                $env = getenv("env");
                if($env == "local" || $env == "130"){
                    $redis = redisInstance::getInstance();
                    $redis->select(7);
                    $redis->lPush("img:wait:list:".$this->_bucket,$newName);
                    $redis->hSet("img:detail:hash:".$this->_bucket,$newName,json_encode($rdata));
                    $redis->close();
                    unset($redis);
                }
                echo json_encode($data);
                exit;
            }
        }catch(Exception $e){
            $data['flag'] = false;
            $data['msg'] = $e->getMessage();
            echo json_encode($data);
            exit;
        }
    }

    /**
     * 删除文件
     *
     * @param $fileName json格式
     */
    public function delete($fileName){
        try{
            $data = array(
                'flag'  =>  true
            );
            $fileNameArr = @json_decode($fileName,true);
            $fileNameArr = array_values($fileNameArr);
            if(empty($fileNameArr)){
                throw new Exception("file array is null");
            }
            if(count($fileNameArr) > 1){
                foreach($fileNameArr as $curFile){
                    $curFile = trim($curFile);
                    //匹配是否带域名
                    if(preg_match(preg_quote("#".$this->_domain."#"),$curFile)){
                        $curFile = substr($curFile,strlen($this->_domain));
                        if($curFile[0] == "/")
                            $curFile = substr($curFile,1);
                    }
                    $dataArr = explode("/",$curFile);
                    if(count($dataArr) > 1){
                        chdir($this->_root.$dataArr[0]);
                        $curFile = $dataArr[1];
                    }else{
                        $curFile = $dataArr[0];
                    }
                    if(preg_match("[/\.\./|\?|\*]",$curFile)){
                        throw new Exception("file name preg false");
                    }
                    if(strlen($curFile) < 32){
                        throw new Exception("file name rule error");
                    }
                    $files = glob($curFile."*");
                    if(empty($files)){
                        $data['files'][] = $curFile;
                    }else{
                        $status = true;
                        foreach($files as $file){
                            if(!unlink($file)){
                                $status = false;
                            }
                        }
                        if($status){
                            $data['files'][] = $curFile;
                        }
                    }
                }
            }else{
                chdir($this->_root);
                $files = glob($fileNameArr[0]."*");
                $curFile = trim($fileNameArr[0]);
                //匹配是否带域名
                if(preg_match(preg_quote("#".$this->_domain."#"),$curFile)){
                    $curFile = substr($curFile,strlen($this->_domain));
                    if($curFile[0] == "/")
                        $curFile = substr($curFile,1);
                    $dataArr = explode("/",$curFile);
                    if(count($dataArr) > 1){
                        chdir($dataArr[0]);
                        $curFile = $dataArr[1];
                    }else{
                        $curFile = $dataArr[0];
                    }
                }
                if(preg_match("[/\.\./|\?|\*]",$curFile)){
                    throw new Exception("file name preg false");
                }
                if(strlen($curFile) < 32){
                    throw new Exception("file name rule error");
                }
                $files = glob($curFile."*");
                if(empty($files)){
                    $data['files'][] = $fileNameArr[0];
                }else{
                    foreach($files as $file){
                        $status = true;
                        if(!unlink($file)){
                            $status = false;
                        }
                    }
                    if($status){
                        $data['files'][] = $fileNameArr[0];
                    }
                }

            }

            echo json_encode($data);
        }catch(Exception $e){
            $data['flag'] = false;
            $data['msg'] = $e->getMessage();
            echo json_encode($data);
        }
    }

}

class Image
{
    //图片类型
    public $type;
    //实际宽度
    public $width;
    //实际高度
    public $height;
    //改变后的宽度
    public $resize_width;
    //改变后的高度
    public $resize_height;
    //是否裁图
    public $cut;
    //源图象
    public $srcimg;
    //目标图象地址
    public $dstimg;
    //临时创建的图象
    public $im;

    public function resizeImage($img, $wid, $hei,$c,$dstpath)
    {
        try{
            $this->srcimg = $img;
            $this->resize_width = $wid;
            $this->resize_height = $hei;
            $this->cut = $c;
            //图片的类型

            $imgInfo = getImagesize($img);

            $this->type = $imgInfo[2];

            //初始化图象
            $this->initi_img();
            //目标图象地址
            $this->dstimg = $dstpath;
            $this->width = $imgInfo[0];
            $this->height = $imgInfo[1];
            //生成图象
            $this->newimg();
            ImageDestroy ($this->im);
        }catch(Exception $e){
            return $e->getMessage();
        }
    }
    /*
     * 生成水印图
     */
    public function watermarkImage($groundImage,$waterPos=0,$waterImage="")
    {
        try{
            $isWaterImage = FALSE;
            $formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
    
            //读取水印文件
            if(!empty($waterImage) && file_exists($waterImage)){
                $isWaterImage = TRUE;
                $water_info = getimagesize($waterImage);
                $water_w = $water_info[0];//取得水印图片的宽
                $water_h = $water_info[1];//取得水印图片的高 
                switch($water_info[2])//取得水印图片的格式
                {
                    case 1:$water_im = imagecreatefromgif($waterImage);break;
                    case 2:$water_im = imagecreatefromjpeg($waterImage);break;
                    case 3:$water_im = imagecreatefrompng($waterImage);break;
                    default:die($formatMsg);
                }
            }
            //读取背景图片
            if(!empty($groundImage) && file_exists($groundImage)){
                $ground_info = getimagesize($groundImage);
                $ground_w = $ground_info[0];//取得背景图片的宽
                $ground_h = $ground_info[1];//取得背景图片的高
                switch($ground_info[2]){//取得背景图片的格式
                    case 1:$ground_im = imagecreatefromgif($groundImage);break;
                    case 2:$ground_im = imagecreatefromjpeg($groundImage);break;
                    case 3:$ground_im = imagecreatefrompng($groundImage);break;
                    default:die($formatMsg);
                }
            }else{
                die("需要加水印的图片不存在！");
            }
            //水印位置
            if($isWaterImage){//图片水印
                $w = $water_w;
                $h = $water_h;
                $label = "图片的";
            }
        
            if( ($ground_w<$w) || ($ground_h<$h) ){
                echo "需要加水印的图片的长度或宽度比水印".$label."还小，无法生成水印！";
                return;
            }
            switch($waterPos){
                case 0://随机
                    $posX = rand(0,($ground_w - $w));
                    $posY = rand(0,($ground_h - $h));
                    break;
                case 1://1为顶端居左
                    $posX = 0;
                    $posY = 0;
                    break;
                case 2://2为顶端居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = 0;
                    break;
                case 3://3为顶端居右
                    $posX = $ground_w - $w;
                    $posY = 0;
                    break;
                case 4://4为中部居左
                    $posX = 0;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 5://5为中部居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 6://6为中部居右
                    $posX = $ground_w - $w;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 7://7为底端居左
                    $posX = 0;
                    $posY = $ground_h - $h;
                    break;
                case 8://8为底端居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = $ground_h - $h;
                    break;
                case 9://9为底端居右
                    $posX = $ground_w - $w - 10;   // -10 是距离右侧10px 可以自己调节
                    $posY = $ground_h - $h - 10;   // -10 是距离底部10px 可以自己调节
                    break;
                default://随机
                    $posX = rand(0,($ground_w - $w));
                    $posY = rand(0,($ground_h - $h));
                    break;
            }
        
            //设定图像的混色模式
            imagealphablending($ground_im, true);
            if($isWaterImage){//图片水印
                imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w,$water_h);//拷贝水印到目标文件 
            }
        
            //生成水印后的图片
            @unlink($groundImage);
            switch($ground_info[2]){//取得背景图片的格式
                case 1:imagegif($ground_im,$groundImage);break;
                case 2:imagejpeg($ground_im,$groundImage);break;
                case 3:imagepng($ground_im,$groundImage);break;
                default:die($errorMsg);
            }
        
            //释放内存
            if(isset($water_info)) unset($water_info);
            if(isset($water_im)) imagedestroy($water_im);
            unset($ground_info);
            imagedestroy($ground_im);
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    private function newimg()
    {
        //改变后的图象的比例
        $resize_ratio = ($this->resize_width)/($this->resize_height);
        //实际图象的比例
        $ratio = ($this->width)/($this->height);
        if(($this->cut)=="1")
            //裁图
        {
            if($ratio>=$resize_ratio)
                //高度优先
            {
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width,$this->resize_height, (($this->height)*$resize_ratio), $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
            if($ratio<$resize_ratio)
                //宽度优先
            {
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, (($this->width)/$resize_ratio));
                ImageJpeg ($newimg,$this->dstimg);
            }
        }
        else
            //不裁图
        {
            if($ratio>=$resize_ratio)
            {
                $newimg = imagecreatetruecolor($this->resize_width,($this->resize_width)/$ratio);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width)/$ratio, $this->width, $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
            if($ratio<$resize_ratio)
            {
                $newimg = imagecreatetruecolor(($this->resize_height)*$ratio,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, ($this->resize_height)*$ratio, $this->resize_height, $this->width, $this->height);
                ImageJpeg ($newimg,$this->dstimg);
            }
        }
    }
    //初始化图象
    private function initi_img()
    {
        $creator = "imagecreatefrom";
        $mimeType = explode("/",image_type_to_mime_type($this->type));
        if(preg_match("/bmp/",$mimeType[1])){
            throw new Exception("img type is not right");
        }else{
            $creator = $creator.$mimeType[1];
        }
        $this->im = $creator($this->srcimg);
    }
}

