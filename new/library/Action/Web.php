<?php
class Action_Web extends DM_Controller_Web
{
	public $columnID = 0;
	public $lastLoginTime = 0;
	
	protected $_fullHost = '';
	protected $columnStatus = -1;
	
    public function init()
    {
        parent::init();
        //Zend_Layout::getMvcInstance()->disableLayout();
        Zend_Layout::startMvc(array('layoutPath' => APPLICATION_PATH . "/modules/web/views/scripts/layout"));
        if($this->isLogin()){
        	$this->memberInfo = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
        	$this->view->memberInfo = $this->memberInfo;
        	$columnModel = new Model_Column_Column();
        	$columnInfo = $columnModel->getMyColumnInfo($this->memberInfo->MemberID);
        	if(!empty($columnInfo)){
	        	$this->columnID = $columnInfo['ColumnID'];
	        	$this->columnStatus = $columnInfo['CheckStatus'];
	        	$redisObj = DM_Module_Redis::getInstance();
	        	$lastLoginTime = $redisObj->get('lastLoginTime:Member:'.$this->memberInfo->MemberID);
	        	$key = Model_Column_MemberSubscribe::getSubscribeKey($this->columnID);
	        	$columnInfo['newSubscribeNum'] = $redisObj->zcount($key,'('.$lastLoginTime,'+inf');
	        	$this->view->headColumnInfo = $columnInfo;
        	}
        	//帐号主题和资质
        	$authenticateModel =new Model_Authenticate();
        	$qualificationModel = new Model_Qualification();
        	$subject='';
        	$qualification=array();
        	$authenticateType = 1;
        	$authenticateInfo = $authenticateModel->getInfoByMemberID($this->memberInfo->MemberID,1);
        	if(!empty($authenticateInfo)){
        		$authenticateType = $authenticateInfo['AuthenticateType'];
        		if($authenticateType == 1){
        			$subject = $authenticateInfo['OperatorName'];
        		}elseif($authenticateType == 2){
        			$subject = $authenticateInfo['OperatorName'];
        			$qualification = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],null,1);
        		}elseif($authenticateType==3){
        			$subject = $authenticateInfo['BusinessName'];
        		}elseif($authenticateType==4){
        			$subject = $authenticateInfo['OrganizationName'];
        		}
        	}
        	$this->view->subject = $subject;
        	$this->view->qualification = $qualification;
        	$this->view->authenticateType = $authenticateType;
        }else{
        	$redirectUrl = DM_Controller_Front::getInstance()->getConfig()->system->login_url;
        	$this->_redirect($redirectUrl);
        }
        
        $schema = $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
        $this->_fullHost = $schema.'://'.$_SERVER['HTTP_HOST'];
    }
    
    /**
     * 是否登录
     */
    public function isLogin()
    {
    	return DM_Module_Account::getInstance()->setSession($this->getSession())->isLogin();
    }
    
    public function hasColumn()
    {
    	$columnModel = new Model_Column_Column();
    	$columnInfo = $columnModel->getMyColumnInfo($this->memberInfo->MemberID,1);
    	if(empty($columnInfo)){
    		$redirectUrl = $this->_fullHost.'/column/add';
    		$this->_redirect($redirectUrl);
    	}
    }
    
    /**
     * 处理图片上传
     * @return string
     */
    protected function processUpload($upload_image,$limit)
    {
    	$src = '';
    	if(isset($upload_image)){
    		$upFile = $upload_image;
    		$allowTypes = array('image/jpeg'=>'jpeg','image/jpg'=>'jpg','image/png'=>'png','image/pjpeg'=>'jpg','image/x-png'=>'png','image/gif'=>'gif');
    		if(!array_key_exists($upFile['type'],$allowTypes)){
    			$this->returnJson(0,'图片只能是jpeg,jpg,png格式！');
    		}
    			
    		if($upFile['size']>5*1024*1024){
    			$this->returnJson(0,'图片不能大于5M！');
    		}
    		//文件类型
    		$fileType = $allowTypes[$upFile['type']];
    		//构造文件名
    		$curTimestamp = time();
    		$dir = '/focus/';
    		if(!file_exists(APPLICATION_PATH.'/../public/upload'.$dir)){
    			mkdir(APPLICATION_PATH.'/../public/upload'.$dir,0775,true);
    		}
    		$fileName = $dir.$curTimestamp.'_'.rand(10000,99999).'.'.$fileType;
    		$fullPath = APPLICATION_PATH.'/../public/upload'.$fileName;
    
    		if(is_uploaded_file($upFile['tmp_name'])){
    			if(!move_uploaded_file($upFile['tmp_name'], $fullPath)){
    				$this->returnJson(0,'移动文件出错啦');
    			}
    			//$src = 'http://'.$this->_request->getHttpHost().$fileName;
    			$src = $fileName;
    		}else{
    			$this->returnJson(0,'出错啦');
    		}
    	}
    
    	return $src;
    }
    
    /**
     * 把草稿数字加入redis
     * @param unknown $type
     * @param unknown $infoID
     * @param string $val
     */
    protected  function getDraftNum($type,$memberID,$val = null)
    {
    	$redisObj = DM_Module_Redis::getInstance();
    	$num = '';
    	if($type == 1){//文章
    		$cacheKey = 'ArticleDraftNum::MemberID'.$memberID;
    	}else{//活动
    		$cacheKey = 'ActiviryDraftNum::MemberID'.$memberID;
    	}
    	if(is_null($val)){
    		$num = $redisObj->get($cacheKey);
    		if(empty($num)){
    			$redisObj->set($cacheKey,1);
    		}
    	}else{
    		$redisObj->INCR($cacheKey);
    	}
    	return empty($num)?1:$num;
    }
    
    /**
     * 是否是系统内部用户
     * @param unknown $memberID
     */
    public function isSystemMember($memberID)
    {
    	$systemMembers = array(8527,11294,6131,22453);
    	if(in_array($memberID, $systemMembers)){
    		return 1;
    	}
    	return 0;
    }
    
    /**
	 * list获取结果集
	 * @param $model
	 * @param $select
	 * @param $sort
	 * @param bool|true $desc
	 * @param null $primary
	 * @return array
	 */
	protected function listResults($model, $select, $sort, $desc = true, $primary = null, $isEscape = true)
	{
		$results = array();

		//解析查询条件
//		$select = $this->parseListWhere($select);

		//关闭视图
		$this->disableView();

		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

		//总条数
		$total = $model->getAdapter()->fetchOne($countSql);
		if ($total) {
			//排序
			$select->order("{$sort} " . ($desc ? "desc" : "asc"));

			//列表
			$results = $model->fetchAll($select)->toArray();
			$isEscape && $this->escapeVar($results);
		}

		return $results;
	}
}