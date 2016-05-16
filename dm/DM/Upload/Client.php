<?php
/**
 * User: Star
 * Date: 14-5-26
 * Time: 下午4:32
 */

class DM_Upload_Client{

    /** @var null 访问口令 */
    protected $_accessKey = null;
    /** @var null 储存空间 */
    protected $_bucket = null;
    /** @var null 上传成功后的回调URL */
    protected $_callback = null;
    /** @var null 缩略图尺寸 */
    protected $_thumb = null;
    protected $_waterType = null;
    protected $_waterPlace = null;

    /**
     * 获取auth key
     *
     * @param $accessKey
     * @param $secretKey
     * @return bool
     */
    public function getAuthKey($accessKey,$secretKey){
        //TODO auth
        return false;
    }


    /**
     * 初始化空间配置，上传、获取都需要用
     *
     * @param $accessKey
     * @param $bucket   文件空间
     * @param $thumbSizes 缩略尺寸 array 可选
     * @param $waterType 水印方式，0无水印，1主图，2缩略图，3主图加缩略图 可选
     * @param $waterPlace 水印位置 可选
     * @param $callback 上传后的回调url 可选
     */
    public function setOptions($accessKey = null,$bucket = null,$thumbSizes = null,$waterType = null,$waterPlace = null,$callback = null){
        $this->_accessKey = $accessKey;
        $this->_bucket = $bucket;
        $this->_callback = $callback;
        $this->_thumb = $thumbSizes;
        $this->_waterType = $waterType;
        $this->_waterPlace = $waterPlace;
    }

    public function setAccessKey($accessKey){
        $this->_accessKey = $accessKey;
    }

    public function setBucket($bucket){
        $this->_bucket = $bucket;
    }

    public function setCallBack($callBack){
        $this->_callback = $callBack;
    }

    private function checkParams(){
        if($this->_accessKey === null){
            throw new Exception("upload params error");
        }
        if($this->_bucket === null){
            throw new Exception("upload params error");
        }
    }

    private function curl($fields,$url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //禁止ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $response = curl_exec($ch);
        if (curl_errno($ch))
        {
            throw new Exception(curl_error($ch),0);
        }
        else
        {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode)
            {
                return "http status code exception : ".$httpStatusCode;
            }
        }
        curl_close($ch);
        return $response;
    }


    public function uploadFile($filePath,$uploadUrl){
        $this->checkParams();
        $fields = array(
            'access_key'    =>  $this->_accessKey,
            'bucket'        =>  $this->_bucket,
            'callback'      =>  $this->_callback,
            'file'          =>  '@'.$filePath
        );
        
        if (class_exists('\CURLFile')) {
            $fields['file'] = new \CURLFile(realpath($filePath));
        } else {
            $fields['file'] =  '@' . realpath($filePath);
        }
        
        if(null !== $this->_thumb){
            $fields['thumbs'] = json_encode($this->_thumb);
        }
        if(null !== $this->_waterType){
            $fields['watertype'] = $this->_waterType;
        }
        if(null !== $this->_waterPlace){
            $fields['waterplace'] = $this->_waterPlace;
        }
        return $this->curl($fields,$uploadUrl);
    }

    public function deleteFile($fileName,$deleteUrl){
        $this->checkParams();
        $fields = array(
            'access_key'    =>  $this->_accessKey,
            'bucket'        =>  $this->_bucket,
            'callback'      =>  $this->_callback,
            'file'          =>  $fileName
        );
        return $this->curl($fields,$deleteUrl);
    }

}
