<?php
include_once dirname(__FILE__).'/Abstract.php';

class Web_DraftController extends Action_Web
{
	public function init()
	{
		parent::init();
		$this->hasColumn();
		//header('Content-type: text/html');
	}
	
	/**
	 * 文章草稿箱
	 */
	public function articleAction()
	{
		$this->view->headTitle("财猪 - 草稿箱");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$columnID = $this->columnID;
		$memberID = $this->memberInfo->MemberID;
		$rowcount = 0;
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article',array('AID','Title','Content','Cover','PublishTime','ReadNum','PraiseNum','CreateTime','IsCharge','IsTimedPublish'))
		->where('columnID = ?',$columnID)->where('MemberID = ?',$memberID)->where('Status = ?',2)->order('AID desc');
		//echo $select->__toString();exit;
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
		//总条数
		$total = $articleModel->getAdapter()->fetchOne($countSql);
		
// 		$dataList = $select->order('AID desc')->limitPage($page, $pageSize)->query()->fetchAll();
		if(!empty($paginator)){
			foreach ($paginator as  &$value) {
				$contents = strip_tags($value['Content']);
				$len = mb_strlen($contents,'utf-8');
				if($len<=50){
					$value['Content'] = $contents;
				}else{
					$value['Content'] =mb_substr($contents,0,50,'utf-8').'...';
				}
				$value['PublishTime'] = date('Y-m-d',strtotime($value['PublishTime']));
				$value['CreateTime'] = date('Y-m-d',strtotime($value['CreateTime']));
			}
		}
		$draftNum = $this->getDraftNumAction($memberID,$columnID);
		$this->view->draftNum = $draftNum;
		$this->view->dataList = $paginator;
	}
	
	/**
	 * 活动草稿箱
	 */
	public function activityAction()
	{
		$this->view->headTitle("财猪 - 草稿箱");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$columnID = $this->columnID;
		$memberID = $this->memberInfo->MemberID;
		$rowcount = 0;
		$activityModel = new Model_Column_Activity();
		$select = $activityModel->select()->from('column_activity',array('AID','Title','StartTime','Province','City','LimitNum','IsCharge'))
		->where('columnID = ?',$columnID)->where('MemberID = ?',$memberID)->where('Status = ?',2)->order('AID desc');
		
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
			
		//总条数
		$total = $activityModel->getAdapter()->fetchOne($countSql);
			
        $draftNum = $this->getDraftNumAction($memberID,$columnID);
		$this->view->draftNum = $draftNum;
		$this->view->dataList = $paginator;
	}
	
	/**
	 * 获取草稿箱文章
	 */
	public function getDraftArticleAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article',array('AID','Title','CreateTime'))
		->where('MemberID = ?',$memberID)->where('Status = ?',2);
		$articleInfo = $select->order('AID desc')->limit(10)->query()->fetchAll();
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$articleInfo));
	}
    
    /**
     * 询财服务草稿箱
     */
    public function counselAction(){
        $this->view->headTitle("财猪 - 草稿箱");
		$memberID = $this->memberInfo->MemberID;
        $columnID = $this->columnID;
        
        $draftNum = $this->getDraftNumAction($memberID,$columnID);
		$this->view->draftNum = $draftNum;
    }
    
    public function getDraftNumAction($memberID,$columnID){
        $data = array('articleNum'=>0,'activityNum'=>0,'counselNum'=>0);
        
        $articleModel = new Model_Column_Article();
        $data['articleNum'] = $articleModel->getDraftCount($memberID,$columnID);
        
        $activityModel = new Model_Column_Activity();
        $data['activityNum'] = $activityModel->getDraftCount($memberID,$columnID);
        
        $counselModel = new Model_Counsel_Counsel();
        $select = $counselModel->select()->setIntegrityCheck(false)->from("counsel",'count(CID) as num')->where('DataType = 2')->where('Status = 1')->where('MemberID = ?',$memberID);
        $re = $select->query()->fetch();
		$data['counselNum'] = $re['num'];
        return $data;
    }
    
    public function getDraftCounselListAction(){
        $this->_helper->viewRenderer->setNoRender();
		$member_id = $this->memberInfo->MemberID;
        $pageSize = intval($this->_getParam('pageSize',10));
        $page = intval($this->_getParam('page',0));
		try {
			$counselModel = new Model_Counsel_Counsel();
            $supportModel = new Model_Counsel_CounselSupportRegion();
			$select = $counselModel->select()->setIntegrityCheck(false);

			//desc 显示多少个字
			$select->from("counsel", array("CID", "Title", "Duration", "Price", "Desc", "Status",
				"ConsultTotal", "CommentTotal", "Score", "CreateTime"));
            
			$select->where("MemberID =?", $member_id);
			$select->where("Status !=?", $counselModel::COUNSEL_STATUS_CLOSE);
            $select->where("DataType =2");
            
            $countSql = $select->__toString();
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

            //总条数
            $total = $counselModel->getAdapter()->fetchOne($countSql);
            
            $select->limitPage($page, $pageSize);
            
			$result = $this->listResults($counselModel, $select, "CID", true, "CID",false);
            $list = array();
            foreach($result as $row){
                $row['SupportCity'] = $supportModel->getCityListByCID($row['CID']);
                $row['CreateTime'] = date('Y.m.d',strtotime($row['CreateTime']));
                $list[] = $row;
            }
			//parent::succReturn(array("Rows" => $list,'Total'=>$total));
            $this->returnJson(1,'',array("Rows" => $list,'Total'=>$total));
		} catch (Exception $e) {
			//parent::failReturn($e->getMessage());
            $this->returnJson(0, $e->getMessage());
		}
    }
    
    public function delDraftCounselAction(){
        $cid = intval($this->_getParam('cid',0));
        $counselModel = new Model_Counsel_Counsel();
        $counselModel->update(array('Status'=>0), array('CID=?'=>$cid,'DataType=?'=>2));
        $this->returnJson(1,'');
    }
}