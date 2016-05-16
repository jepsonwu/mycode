<?php
require_once APPLICATION_PATH.'/thirdPart/qiniu/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\Qiniu\Storage;

class Model_Qiniu
{
	private $_uploadManager = null;
	private $_auth = null;
	
	public function __construct()
	{
		$config = DM_Controller_Front::getInstance()->getConfig();
		$settings = $config->qiniu;
		$accessKey = $settings->accessKey;
		$secretKey = $settings->secretKey;
		
		$this->_auth = new Auth($accessKey,$secretKey);
		$this->_uploadManager = new UploadManager();
	}
	
	/**
	 * 上传凭证
	 * @see \Qiniu\Auth::uploadToken()
	 */
	public function getUploadToken($bucket = 'czspace')
	{
		$redisObj = DM_Module_Redis::getInstance();
		$uploadTokenKey = 'CAIZHU_QINIU_UPLOAD_TOKEN';
		$tokenInfo = $redisObj->hGetAll($uploadTokenKey);
		if(false ===$tokenInfo || empty($tokenInfo['token']) || $tokenInfo['createTime'] + 1500 < time()){
			$tokenInfo = array();
			$tokenInfo['createTime'] = time();
			$token = $this->_auth->uploadToken($bucket);
			$tokenInfo['token'] = $token;
			$redisObj->hmset($uploadTokenKey,$tokenInfo);
		}
		return $tokenInfo;		
	}
	
	/**
	 *  获取上传对象
	 * @return \Qiniu\Storage\UploadManager
	 */
	public function getUploadManager()
	{
		return $this->_uploadManager;
	}
}