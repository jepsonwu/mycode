<?php
class Admin_AuthenticateController extends DM_Controller_Admin
{
	public function indexAction()
	{
		//echo 3;
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
        $searchType= trim($this->_getParam('searchType',''));
        $searchTypeValue= $this->_getParam('searchTypeValue');

        $AuthenticateType= $this->_getParam('AuthenticateType',0);
        $Status= $this->_getParam('Status',-1);
        $start_date= $this->_getParam('start_date');
        $end_date= $this->_getParam('end_date');

        $viewModel = new Model_Topic_View();
        $select = $viewModel->select()->setIntegrityCheck(false);
        $select->from('member_authenticate as ma');
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m','ma.MemberID = m.MemberID','UserName');
         
        if(!empty($searchType) && !empty($searchTypeValue)){
            if($searchType == 'AuthenticateID'){
                $select->where("ma.".$searchType."=?", $searchTypeValue);
            }elseif ($searchType == 'UserName') {
                $select->where("m.".$searchType." = ?", $searchTypeValue);
            }  
        }

        if($AuthenticateType >0){
            $select->where("ma.AuthenticateType = ?", $AuthenticateType);
        }

        if($Status!=-1){

            $select->where("ma.Status = ?",$Status);        
        }
        
        if(!empty($start_date)){
            $select->where("ma.DataTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("ma.DataTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $viewModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','AuthenticateID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $viewModel->fetchAll($select)->toArray();
        // $reportModel = new Model_IM_MessageReport();
        // foreach ($results as  &$item) {
        //     $reportList = $reportModel->getReportListByType($item['ViewID'],2);
        //     $item['ReportNum'] = count($reportList);
        // }
        
        $qualificationModel = new Model_Qualification();
        foreach ($results as &$item) {
            $qualifiteChecking =$qualificationModel->getInfoByqualificationID($item['AuthenticateID'],null,0);
            $item['qualifiteChecking'] = count($qualifiteChecking)>0?count($qualifiteChecking):0;        
        }
        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

    /**
     * 审核
     */
    public function checkAction()
    {
        $authenticate_id = intval($this->_getParam('authenticate_id',0));
        $status = intval($this->_getParam('status',0));

        $authenticateModel = new Model_Authenticate();
        $qualificationModel = new Model_Qualification();
        $authenticateInfo = $authenticateModel->getInfoByID($authenticate_id);

        $this->view->authenticate_id = $authenticate_id;
        $this->view->status = $status;
        $this->view->authenticateInfo =$authenticateInfo;


        if($this->getRequest()->isPost()){
            $this->_helper->viewRenderer->setNoRender();
            $AuthenticateID = intval($this->_getParam('AuthenticateID',0));
            $Status = intval($this->_getParam('Status',0));
            $Remark = trim($this->_getParam('Remark',''));
   
            if($AuthenticateID <= 0){
                $this->returnJson(0,'参数错误！');
            }
            
            if(!in_array($Status,array(1,2))){
                $this->returnJson(0,'状态参数错误！');
            }
            
            if(empty($Remark) && $Status != 1){
                $this->returnJson(0,'请输入理由！');
            }

  
            $authenticateInfo = $authenticateModel->getInfoByID($AuthenticateID);
            $memberModel = new DM_Model_Account_Members();
            $memberInfo = $memberModel->getById($authenticateInfo['MemberID']);

            if($Status == 1){
                if(!empty($memberInfo['RealName']) && $authenticateInfo['OperatorName'] != $memberInfo['RealName']){
                    $this->returnJson(false, "运营者身份信息与银行卡信息不一致，请拒绝后通知用户重新提交！");
                }
                if(!empty($memberInfo['IDCard']) && $authenticateInfo['IDCard'] != $memberInfo['IDCard']){
                    $this->returnJson(false, "运营者身份信息与银行卡信息不一致，请拒绝后通知用户重新提交！");
                }
            }
            

            if($authenticateInfo['AuthenticateType']==2){         
                $qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1);
                if(empty($qualificationInfo) && $Status == 1){
                    $this->returnJson(0,'没有资质信息，无法通过审核！');
                }
                if($Status==1 && $qualificationInfo['CheckStatus']!=1){
                    $this->returnJson(0,'请通过资质之后再审核主体！');
                }
            }

            $dataParam = array(
                    'Status'=>$Status,
                    'DataTime'=>date('Y-m-d H:i:s',time()),
                    'Remark'=>$Remark,
                );
            if($Status==2){
                $dataParam['FailuresNum']=$authenticateInfo['FailuresNum']+1;
            }

            if($authenticateModel->update(array('Status'=>$Status,'Remark'=>$Remark,'DataTime'=>date('Y-m-d H:i:s',time())), array('AuthenticateID = ?'=>$AuthenticateID))){

                $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
                $easeModel = new Model_IM_Easemob();

                if($authenticateInfo['AuthenticateType']==2 && $Status==2){
                    $qualificationModel->update(array('CheckStatus'=>$Status,'DataTime'=>date('Y-m-d H:i:s',time())),array('FinancialQualificationID = ?'=>$qualificationInfo['FinancialQualificationID']));
                }
                if($Status==1){
                    $mModel = new Model_Member();
                    if(empty($memberInfo['RealName'])){
                        $memberModel->update(array('RealName' => $authenticateInfo['OperatorName']), array('MemberID = ?' => $authenticateInfo['MemberID']));
                    }
                    if(empty($memberInfo['IDCard'])){
                        $memberModel->update(array('IDCard' => $authenticateInfo['IDCard']), array('MemberID = ?' => $authenticateInfo['MemberID']));
                    }
                    $content = '您提交的帐号主体信息已通过审核，请登录财猪网（电脑端）进行后续操作。';
                }elseif($Status==2){
                    $content = '您提交的帐号主体信息未通过审核，请登录财猪网（电脑端）进行修改。';
                }
                $tmpMemberID = array();
                $tmpMemberID[]=$authenticateInfo['MemberID'];
                $ret = $easeModel->yy_hxSend($tmpMemberID, $content,'text','users',array('optionRand'=>1),$sysMemberID);
            } 
            $this->returnJson(1,'操作成功！');
        }
    }


    /**
     * 理财师资质列表
     */
    public function qualificationListAction()
    {
        $authenticate_id = intval($this->_getParam('authenticate_id',0));
        $this->view->authenticate_id= $authenticate_id;
        if($this->_request->isPost()){  
            if($authenticate_id <= 0){
                $this->returnJson(0,'参数错误！');
            }
            $qualificationModel = new Model_Qualification();

            $select = $qualificationModel->select()->from('financial_qualification')->where('AuthenticateID = ?',$authenticate_id);
            $results = $qualificationModel->fetchAll($select)->toArray();
            $total = count($results);
            $this->escapeVar($results);
            $this->_helper->json(array('total'=>$total,'rows'=>$results));
        }
    }


    /**
     * 理财师资质审核
     */
    public function checkQualificationAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = intval($this->_getParam('id',0));
        $status = intval($this->_getParam('status',0));
        $remark = trim($this->_getParam('remark',''));
        if($id <= 0){
            $this->returnJson(0,'参数错误');
        }
        
        if(!in_array($status,array(1,2))){
            $this->returnJson(0,'状态参数错误');
        }
        
        $qualificationModel = new Model_Qualification();
        $qualificationInfo = $qualificationModel->getInfoByID($id);
        if(empty($qualificationInfo)){
             $this->returnJson(0,'没有对应的信息！');       
        }

        $authenticateModel = new Model_Authenticate();
        $authenticateInfo = $authenticateModel->getInfoByID($qualificationInfo['AuthenticateID']);
        if(empty($authenticateInfo)){
            $this->returnJson(0,'主体信息不存在！'); 
        }

        if($status==1 && $authenticateInfo['Status']==2){
            $this->returnJson(0,'主体被拒绝，需重新提交申请！'); 
        }

        if($qualificationModel->update(array('CheckStatus'=>$status,'Remark'=>$remark,'DataTime'=>date('Y-m-d H:i:s',time())), array('FinancialQualificationID = ?'=>$id))){         
            if($authenticateInfo['Status']==1){
                $sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
                $easeModel = new Model_IM_Easemob();
                if($status==1){
                    $content = '您提交的从业资质已经通过审核。';
                }elseif($status==2){
                    $content = '您提交的从业资质未通过审核，请登录财猪网（电脑端）重新提交';
                }
                $tmpMemberID = array();
                $tmpMemberID[]=$authenticateInfo['MemberID'];
                $ret = $easeModel->yy_hxSend($tmpMemberID, $content,'text','users',array('optionRand'=>1),$sysMemberID);
            }

        }

        $this->returnJson(1,'操作成功！');
    } 
}