<?php
/**
 *广告位相关
 * @author Jeff
 *
 */
class Model_ActivityTemplate extends Zend_Db_Table
{
	protected $_name = 'admin_activity_list';
	protected $_primary = 'Lid';
	
    /**
     * 获取已设置活动列表
     * $activityName 活动名称
     * $start_date 活动时间-开始
     * $end_date 活动时间-结束
     * $pageIndex 当前页，从1开始
     * $pageSize 每页条数
     */
    public function getActivityList($activityName,$start_date,$end_date,$pageIndex,$pageSize,&$total=0){
        $select = $this->select();
        $select->from($this->_name,array('Lid','TemplateType','TemplateName','ActivityName','Path','CreateTime'=>"DATE_FORMAT(CreateTime,'%Y-%m-%d %H:%i')"))->where("Status=1");
        if(!empty($start_date)){
            $start_date = date('Y-m-d 00:00:00',strtotime($start_date));
            $select->where("CreateTime>=?",$start_date);
        }
        if(!empty($end_date)){
            $end_date = date('Y-m-d 23:59:59',strtotime($end_date));
            $select->where("CreateTime<=?",$end_date);
        }
        if(!empty($activityName)){
            $select->where("ActivityName like ?","%".$activityName."%");
        }
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());
                
        //总条数
        $total = $select->getAdapter()->fetchOne($countSql);
        $res = $select->order('Lid desc')->limitPage($pageIndex,$pageSize)->query()->fetchAll();
        return $res;
    }
    
    /**
     * 获取模板系列
     * $TemplateType 模板系列ID,为0时查询全部
     */
    public function getTemplateType($TemplateType=0){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from('admin_template_type',array('Tid','TemplateName'));
        if($TemplateType>0){
            $select->where("Tid=?",$TemplateType);
        }
        return $select->order('Tid desc')->query()->fetchAll();
    }
    
    /**
     * 获取模板列表，根据模板系列类型
     */
    public function getTemplateListById($templateType=0,$templateId=0){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from("admin_template_list",array('TemplateType','id','templateName'=>'TemplateName','Path'))->where("Status=1");
        if($templateType>0){
            $select->where("TemplateType=?",$templateType);
        }
        if($templateId>0){
            $select->where("id=?",$templateId);
        }
        return $select->order('TemplateType desc')->query()->fetchAll();
    }
    
    /**
     * 获取模板列表
     */
    public function getTemplateList($templateType,$templateId,$pageIndex,$pageSize,&$total=0){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from("admin_template_list",array('id','TemplateType','TemplateName','ActivityNum','Path','CreateTime'=>"DATE_FORMAT(CreateTime,'%Y-%m-%d %H:%i')"))->where("Status=1");
        if($templateType>0){
            $select->where("TemplateType=?",$templateType);
        }
        if($templateId>0){
            $select->where("id=?",$templateId);
        }
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $select->__toString());
                
        //总条数
        $total = $select->getAdapter()->fetchOne($countSql);
        $res = $select->order('id desc')->limitPage($pageIndex,$pageSize)->query()->fetchAll();
        return $res;
    }


    /**
     * 验证模板参数是否正确
     */
    public function checkTemplate($templateType,$templateId){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from('admin_template_list',array('Path'))->where("id=?",$templateId)->where("Status=1 and TemplateType=?",$templateType);
        $res = $select->query()->fetch();
        return $res;
    }

    /**
     * 获取单个活动的详情
     * $activity_id 活动ID
     */
    public function getActivityInfo($activity_id){
        $select = $this->select();
        $select->from($this->_name,array('Lid','TemplateType','ActivityName','TemplateName','Path','TemplateId'))->where("Status=1")->where("Lid=?",$activity_id);
        $res = $select->query()->fetch();
        return $res;
    }
            
	/**
     * 删除活动
     * $activity_id 活动ID
     */
    public function delActivity($activity_id){
        $res = $this->getActivityInfo($activity_id);
        $templateId = $res['TemplateId'];
        $this->_db->update("admin_template_list",array('ActivityNum'=>new Zend_Db_Expr('ActivityNum - 1')),array('id=?'=>$templateId));
        return $this->update(array('Status'=>0),array('Lid=?'=>$activity_id));
    }
    
    /**
     * 添加新活动
     * $templateType 模板系列编号,0表示不使用模板
     * $params 页面所需参数
     */
    public function insertActivy($templateType,$params){
        if($templateType==0){
            $TemplateName = "";
            $path = $params['activityLink'];
        }else{
            $res = $this->getTemplateListById(0,$params['templateId']);
            $TemplateName = $res[0]['templateName'];
            $path = $res[0]['Path'];
        }
        
        $activity_id = $this->insert(array('TemplateType'=>$templateType,'ActivityName'=>$params['activityName'],'TemplateId'=>$params['templateId'],'Path'=>$path,'TemplateName'=>$TemplateName,'Status'=>1));
        if(!$activity_id){
            return false;
        }
        $conf_id = $this->_db->insert('admin_activity_params_conf',array('Lid'=>$activity_id,'Params'=>json_encode($params)));
        if(!$conf_id){
            return false;
        }

        if($templateType){
            $this->_db->update("admin_template_list",array('ActivityNum'=>new Zend_Db_Expr('ActivityNum + 1')),array('id=?'=>$params['templateId']));
        }
        return $activity_id;
    }
    
    /**
     * 插入模板
     * $templateType 模板系列
     * $templateName 模板名称
     * $templatePath 模板路径
     */
    public function insertTemplate($templateType,$templateName,$templatePath){
        $templateId = $this->_db->insert('admin_template_list',array('TemplateType'=>$templateType,'TemplateName'=>$templateName,'Status'=>1,"Path"=>$templatePath));
        if(!$templateId){
            return false;
        }
        return $templateId;
    }
    
    /**
     * 获取活动的配置
     */
    public function getActivityConf($activityId){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from("admin_activity_params_conf",array('Params'))->where("Lid=?",$activityId);
        $res = $select->query()->fetch();
        if(empty($res)){
            return null;
        }
        return $res['Params'];
    }
    
    /**
     * 验证是否可以提交数据
     */
    public function checkSignStatus($activityId,$TemplateType,$memberID){
        if($TemplateType==3){//课程打卡
            $select = $this->select()->setIntegrityCheck(false);
            $select->from("admin_activity_submit_data",array('Params'))->where("MemberID=?",$memberID)->where("ActivityID=?",$activityId)->where('AddTime>?',date("Y-m-d 00:00:00"));
            $res = $select->query()->fetch();
            if(empty($res)){
                return array('code'=>0);
            }
            return array('code'=>1,'msg'=>'财猪已经记录过你的听课打卡啦！<br/>明天继续！');
        }
        return array('code'=>0);
    }
    
    /**
     * 保存活动提交的数据
     */
    public function submitActivityData($activityId,$memberID,$params=array()){
        $ctivity_submit = $this->_db->insert('admin_activity_submit_data',array('MemberID'=>$memberID,'ActivityID'=>$activityId,'Params'=>json_encode($params)));
        if(!$ctivity_submit){
            return array('code'=>1);
        }
        if($params['TemplateType']==3){//课程打卡
            $select = $this->select()->setIntegrityCheck(false);
            $select->from("admin_activity_submit_data",array('total'=>'count(*)'))->where("MemberID=?",$memberID)->where("ActivityID=?",$activityId);
            $res = $select->query()->fetch();
            if(isset($params['topicID'])){
                $viewModel = new Model_Topic_View();
                $viewModel->addView($memberID,$params['topicID'],"#打卡#迈出修炼成理财达人的第一步，我已坚持学习".$res['total']."天，2016，为成为更有财的自己努力！");
            }
            return array('code'=>0,'msg'=>$res['total']);
        }
        return array('code'=>0,'msg'=>'');
    }
}