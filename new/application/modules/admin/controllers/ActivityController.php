<?php
class Admin_ActivityController extends DM_Controller_Admin
{
    public function indexAction()
    {
        
    }

    /**
     * 获取已经做过的活动列表
     */
    public function activityListAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
        $activityName= $this->_getParam('activityName','');
        $start_date= $this->_getParam('start_date');
        $end_date= $this->_getParam('end_date');
        $model_obj = new Model_ActivityTemplate();
        //获取列表
        $activityList = array();
        $total = 0;
        $list = $model_obj->getActivityList($activityName,$start_date,$end_date,$pageIndex,$pageSize,$total);
        foreach($list as $row){
            if($row['TemplateType']>0){
                $checkResult = $this->checkActivity($row['Path']);
                if(!$checkResult){
                    continue;
                }
                $templateInfo = $model_obj->getTemplateType($row['TemplateType']);
                $row['TemplateType'] = $templateInfo[0]['TemplateName'];
            }
            $activityList[] = $row;
        }
        $this->escapeVar($activityList);
        $this->_helper->json(array('total'=>$total,'rows'=>$activityList));
    }
    
    /**
     * 设置为模板
     */
    /*public function setAction()
    {
        $activity_id = intval($this->_getParam('id',0));
        $activityModel = new Model_ActivityTemplate();
        $activityInfo = $activityModel->getActivityInfo($activity_id);
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            if(empty($activityInfo)){
                $this->returnJson(0,"该活动不存在");
            }
            if($activity_id <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
    		$templateName = $this->_getParam('TemplateName','');
            if(empty($templateName)){
                $this->returnJson(0,'模板名称不能为空!');
            }
            $templateType = intval($this->_getParam('templateType',0));
            if($templateType<=0){
                $this->returnJson(0,'请选择模板系列!');
            }
            
            if($activityInfo['IsTemplate']==1){
                $this->returnJson(1);
            }
            $result = $activityModel->setActivityTemplate($activity_id,$templateName,$templateType);
            if(!$result){
                $this->returnJson(0,"设置活动模板失败");
            }
    		$this->returnJson(1);
    	}
        $this->view->activity_id = $activity_id;
        $this->view->templateType = $activityInfo['Tid'];
    }*/
    
    /**
     * 删除活动
     */
    public function delAction()
    {
        $activity_id = intval($this->_getParam('id',0));
    	$this->_helper->viewRenderer->setNoRender();
        if($activity_id <= 0 ){
            $this->returnJson(0,'参数错误!');
        }
        $activityModel = new Model_ActivityTemplate();
        $activityInfo = $activityModel->getActivityInfo($activity_id);
        if(empty($activityInfo)){
            $this->returnJson(0,"该活动不存在");
        }
        $result = $activityModel->delActivity($activity_id);
        if(!$result){
            $this->returnJson(0,"删除活动失败");
        }
        $this->returnJson(1);
    }
    
    /**
     * 模板管理页面
     */
    public function manageAction(){
        $activityModel = new Model_ActivityTemplate();
        $TemplateTypeList = $activityModel->getTemplateType(0);
        $this->view->TemplateTypeList = $TemplateTypeList;
    }
    
    /**
     * 添加活动页面
     */
    public function addAction(){
        $activityModel = new Model_ActivityTemplate();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
            $params = array();
            $params['activityName'] = $this->_getParam('activityName','');
            $useTemplate = intval($this->_getParam('useTemplate',0));
            if($useTemplate==0){
                $templateType = 0;
                $params['templateId'] = 0;
                $params['activityLink'] = $this->_getParam('activityLink','');
            }else{
                $templateType = intval($this->_getParam('templateType',0));
                if($templateType <= 0 ){
                    $this->returnJson(0,'模板系列参数错误!');
                }
                $templateId = intval($this->_getParam('template',0));
                if($templateId <= 0 ){
                    $this->returnJson(0,'模板编号参数错误!');
                }
                $checkTemplate = $activityModel->checkTemplate($templateType,$templateId);//验证模板参数是否正确
                if(empty($checkTemplate)){
                    $this->returnJson(0,'模板不存在!'.$templateType.'-'.$templateId);
                }
                $checkResult = $this->checkActivity($checkTemplate['Path']);//验证模板目录是否存在
                if(!$checkResult){
                    $this->returnJson(0,'模板文件已删除!');
                }
                if($templateType==1){//谁是主讲人系列
                    $params['title'] = $this->_getParam('title','');
                    $params['journal'] = $this->_getParam('journal','');
                    $params['datetime'] = $this->_getParam('datetime','');
                    $params['account'] = $this->_getParam('speakerAccount','');
                    $params['depict'] = $this->_getParam('describe','');
                    //验证主讲人是否存在
                    $member_model = new DM_Model_Account_Members();
                    $accountInfo = $member_model->getByUsername($params['account']);
                    if(empty($accountInfo)){
                        $this->returnJson(0,"主讲人不存在!");
                    }
                    $params['memberId'] = $accountInfo['MemberID'];
                    //处理图片
                    $upload_image = isset($_FILES['image'])?$_FILES['image']:'';
                    if (empty($upload_image)) {
                        $this->returnJson(0,"图片不能为空!");
                    }
                    $fileName = "title_".time();
                    $toPath = APPLICATION_PATH.'/../public/hd/'.$checkTemplate['Path'];
                    $upImageRes = $this->processUpload(1,$upload_image,$toPath."/imgs",$fileName);
                    if($upImageRes['code']>0){
                        $this->returnJson(0,$upImageRes['msg']);
                    }
                    $params['imgsName'] = $fileName;
                }elseif($templateType==2){//今日明星
                    $params['account'] = $this->_getParam('star','');
                    $params['depict'] = $this->_getParam('depict','');
                    $params['article_link'] = $this->_getParam('article_link','');
                    $params['abstract'] = $this->_getParam('abstract','');
                    //验证明星账号是否存在
                    $member_model = new DM_Model_Account_Members();
                    $accountInfo = $member_model->getByUsername($params['account']);
                    if(empty($accountInfo)){
                        $this->returnJson(0,"明星账号不存在!");
                    }
                    $params['memberId'] = $accountInfo['MemberID'];
                }
                $params['templateId'] = $templateId;
            }
    		
            $db = $activityModel->getAdapter();
            $db->beginTransaction();
            //添加活动
            $activyId = $activityModel->insertActivy($templateType,$params);
            if(!$activyId){
                $db->rollBack();
                $this->returnJson(0,"保存活动数据失败!");
            }
            $db->commit();
    		$this->returnJson(1);
    	}
        $TemplateTypeList = $activityModel->getTemplateType(0);
        $this->view->TemplateTypeList = $TemplateTypeList;
    }

    /**
     * 获取模板列表，根据模板系列类型
     */
    public function getTemplateListAction(){
        $this->_helper->viewRenderer->setNoRender();
        $templateType = intval($this->_getParam('templateType',1));
        $activityModel = new Model_ActivityTemplate();
        $templateList = $activityModel->getTemplateListById($templateType);
        $this->returnJson(parent::STATUS_OK, '', array('rows'=>$templateList));
    }
    
    /**
     * 获取模板列表
     */
    public function templateListAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
        $templateType = $this->_getParam('templateType','');
        $templateId = $this->_getParam('templateId',0);
        $model_obj = new Model_ActivityTemplate();
        //获取列表
        $templateList = array();
        $total = 0;
        $list = $model_obj->getTemplateList($templateType,$templateId,$pageIndex,$pageSize,$total);
        foreach($list as $row){
            $checkResult = $this->checkActivity($row['Path']);
            if(!$checkResult){
                continue;
            }
            if($row['TemplateType']>0){
                $templateInfo = $model_obj->getTemplateType($row['TemplateType']);
                $row['TemplateType'] = $templateInfo[0]['TemplateName'];
            }
            $templateList[] = $row;
        }
        $this->escapeVar($templateList);
        $this->_helper->json(array('total'=>$total,'rows'=>$templateList));
    }
    
    /**
     * 添加活动页面
     */
    public function templateAddAction(){
        $activityModel = new Model_ActivityTemplate();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$templateType = $this->_getParam('templateType',"");
            $templateName = $this->_getParam('templateName',"");
            if(empty($templateName)){
                $this->returnJson(0,'模板名称不能为空!');
            }
            //处理模板压缩包
            $upload_template = isset($_FILES['templateFile'])?$_FILES['templateFile']:'';
            if (empty($upload_template)) {
                $this->returnJson(0,"模板不能为空!");
            }
            $toPath = APPLICATION_PATH.'/../public/hd/';
            $uploadRes = $this->processUpload(2,$upload_template,$toPath,"template");
            if($uploadRes['code']>0){
                $this->returnJson(0,$uploadRes['msg']);
            }
            //将模板解压
            $zip = new ZipArchive;
            $zip->open($toPath."template.zip");
            $zip->extractTo($toPath."template"); 
            $zip->close();
            $datetime = date("YmdHis");
            //遍历目录，解决压缩包含有多层的问题，并复制想要的内容到指定目录下面
            $filePath = $this->getCopyFromPath($toPath."template");
            $this->copyPath($filePath,$toPath.$datetime);
            $this->delDir($toPath."template");
            unlink($toPath."template.zip");
            $db = $activityModel->getAdapter();
            $db->beginTransaction();
            //添加活动
            $templateId = $activityModel->insertTemplate($templateType,$templateName,$datetime);
            if(!$templateId){
                $db->rollBack();
                $this->delDir($toPath.$datetime);
                $this->returnJson(0,"保存模板数据失败!");
            }
            $db->commit();
    		$this->returnJson(1);
    	}
        $TemplateTypeList = $activityModel->getTemplateType(0);
        $this->view->TemplateTypeList = $TemplateTypeList;
    }

    /**
     * 验证活动是否存在
     */
    public function checkActivity($path)
    {
        if(empty($path)){
            return false;
        }
        $activityFile = APPLICATION_PATH.'/../public/hd/'.$path.'/index.html';
        return file_exists($activityFile);
    }
    
    /**
     * 删除活动目录
     */
    public function delDir($dir) {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while($file = readdir($dh)) {
          if($file!="." && $file!="..") {
            $fullpath = $dir."/".$file;
            if(!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                $this->delDir($fullpath);
            }
          }
        }
        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 复制目录
     * $fromPath 要辅助的目录
     * $toPath 复制到的位置
     */
    public function copyPath($fromPath,$toPath){
        if(!is_dir($fromPath)){
            return false;
        }
        $from_files = scandir($fromPath);
        //如果不存在目标目录，则尝试创建
        if(!file_exists($toPath)){
            @mkdir($toPath,0777);   
        }
        
        if(!empty($from_files)){
            foreach ($from_files as $file){
                if($file == '.' || $file == '..' ){
                    continue;
                }

                if(is_dir($fromPath.'/'.$file)){//如果是目录，则调用自身
                    $this->copyPath($fromPath.'/'.$file,$toPath.'/'.$file);
                }else{//直接copy到目标文件夹
                    copy($fromPath.'/'.$file,$toPath.'/'.$file);
                }
            }
        }
        return true;
    }
    
    //获取压缩包中含有index的真实目录
    public function getCopyFromPath($fromPath){
        $path = "";
        if(!is_dir($fromPath)){
            return $path;
        }
        $from_files = scandir($fromPath);
        if(!empty($from_files)){
            foreach ($from_files as $file){
                if($file == '.' || $file == '..' ){
                    continue;
                }
                if(is_dir($fromPath.'/'.$file)){//如果是目录，则调用自身
                    $path = $this->getCopyFromPath($fromPath.'/'.$file);
                }else{//直接copy到目标文件夹
                    if(strstr($file, 'index.phtml') || strstr($file, 'index.html')){
                        $path = realpath($fromPath);
                        break;
                    }
                }
            }
        }
        return $path;
    }

    /**
     * 处理上传
     * $fileType 文件类型，1图片，2压缩文件
     */
    protected function processUpload($fileType,$uploadFile,$path,$fileName)
    {
        $src = '';
        if(isset($uploadFile)){
            $upFile = $uploadFile;
            if($fileType==1){
                $allowTypes = array('image/jpeg'=>'jpeg','image/gif'=>'gif','image/png'=>'png');
                if(!array_key_exists($upFile['type'],$allowTypes)){
                    return array('code'=>1,'msg'=>'格式不正确');
                }
            }
            $fileType = basename($uploadFile['name']);
            //构造文件名
            if(!file_exists($path)){
                mkdir($path,0777,true);
            }
            $fullPath = $path."/".$fileName.".".pathinfo($fileType, PATHINFO_EXTENSION);
            if(is_uploaded_file($upFile['tmp_name'])){
                if(!move_uploaded_file($upFile['tmp_name'], $fullPath)){
                    return array('code'=>1,'msg'=>'移动文件出错啦');
                }
            }else{
                return array('code'=>1,'msg'=>'出错啦');
            }
        }
        return array('code'=>0);
    }
    
    /**
     * 导出活动数据
     */
    public function exportActivityDataAction(){
        header("Content-type: text/html; charset=utf-8");
        $activityModel = new Model_ActivityTemplate();
        $this->_helper->viewRenderer->setNoRender();
        $ActivityId = $this->_getParam('ActivityId',0);
        $searchKey = $this->_getParam('searchKey',"");
        $searchVal = $this->_getParam('searchVal',"");
        $walletModel = new Model_Wallets();
        $select = $activityModel->select()->setIntegrityCheck(false);
        $select->from("admin_activity_submit_data_tmp",array('AddTime','Params'))->where('ActivityId=?',$ActivityId);
        if(!empty($searchKey)){
            $select->where("Params like '%\"".$searchKey."\":\"".$searchVal."\"%'");
        }
        //echo $select->__toString();die();
        $res = $select->query()->fetchAll();
        $list = array();
        $key_arr = array();
        foreach ($res as $row){
            $r = array();
            $params = (array)json_decode($row['Params']);
            foreach($params as $k=>$v){
                if($k=='_' || $k=='callback' || $k=='TemplateType' || $k=='api/activity/submit-data' || $k=='_callback' || $k=='id' || $k=='url'){
                    continue;
                }
                if(!in_array($k, $key_arr)){
                    $key_arr[] = $k;
                }
                $r[$k] = $v;
            }
            $list[] = $r;
        }
        $csv = new Model_CExcel();
        $filename = "活动数据(".date("Ymd").")";
        if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
            $filename = urlencode($filename);
        $title = $key_arr;
        $data = array();
        $data[] = $title;
        foreach ($list as $row) {
            $sub = array();
            foreach($key_arr as $rr){
                $content = (isset($row[$rr])?$row[$rr]:'')."\t";
                $content = preg_replace("/(\n)/" ,'' ,$content);
                $sub[] = $content;
            }
            $data[] = $sub;
        }
        $csv->exportCSV($data,$filename);die();
    }
}