<?php
class Admin_ReplyController extends DM_Controller_Admin
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
        $searchTypeValue= $this->_getParam('searchTypeValue','');

        $CheckStatus= $this->_getParam('CheckStatus',-1);
        $start_date= $this->_getParam('start_date');
        $end_date= $this->_getParam('end_date');

        $replyModel = new Model_Topic_Reply();
        $select = $replyModel->select()->setIntegrityCheck(false);
        $select->from('view_replies as vr');
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m','vr.MemberID = m.MemberID','UserName');
         
        if(!empty($searchType) && !empty($searchTypeValue)){
            if($searchType == 'ViewID'){
                $select->where("vr.".$searchType."= ?", $searchTypeValue);
            }elseif ($searchType == 'ReplyContent') {
                $select->where("vr.".$searchType." like ?", "%{$searchTypeValue}%");
            }elseif ($searchType == 'UserName') {
                $select->where("m.".$searchType."= ?", $searchTypeValue);
            }
            
        }

        if($CheckStatus!=-1){
            $select->where("vr.Status = ?", $CheckStatus);
        }
        if(!empty($start_date)){
            $select->where("vr.CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("vr.CreateTime <= ?", $end_date);
        }


        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $replyModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','ReplyID');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $replyModel->fetchAll($select)->toArray();

        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

    /**
     * 审核
     */
    public function checkAction()
    {
        $reply_id = intval($this->_getParam('reply_id',0));
        $status = intval($this->_getParam('status',0));
        $this->view->reply_id = $reply_id;
        $this->view->status = $status;

        if($this->getRequest()->isPost()){
            $this->_helper->viewRenderer->setNoRender();
            $replyID = intval($this->_getParam('replyID',0));
            $status = intval($this->_getParam('Status',0));
            $remark = trim($this->_getParam('remark',''));
            
            if($replyID <= 0){
                $this->returnJson(0,'参数错误！');
            }
            
            if(!in_array($status,array(0,1))){
                $this->returnJson(0,'状态参数错误！');
            }
            
            $replyModel = new Model_Topic_Reply();
            $replyModel->update(array('Status'=>$status,'Remark'=>$remark), array('ReplyID = ?'=>$replyID));
        
            $this->returnJson();
        }
    } 

}