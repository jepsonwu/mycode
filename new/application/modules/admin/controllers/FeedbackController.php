<?php 
/**
 * 明细管理
 * 
 * @author Kitty
 *
 */
class Admin_FeedbackController extends DM_Controller_Admin
{

     public function indexAction()
    {
    }

    public function listAction()
    {
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		$MemberID= intval($this->_getParam('MemberID',0));
		$start_date= $this->_getParam('start_date');
		$end_date= $this->_getParam('end_date');
		$IsReply =$this->_getParam('IsReply',-1);


		$feedbackModel = new Model_Feedback();
		$select = $feedbackModel->select()->setIntegrityCheck(false);
		$select->from(array('f'=>'feedback'));
		//$select->joinLeft(array('m' => 'message'), "m.FeedBackID = f.FeedBackID", 'Content as Reply');
        if($MemberID>0){
            $select->where("f.MemberID = ?", $MemberID);
        }
		if($IsReply != -1){
			$select->where("f.IsReply = ?", $IsReply);
		}
        if(!empty($start_date)){
			$select->where("f.AddTime >= ?",  date('Y-m-d',strtotime($start_date)));
        }

        if(!empty($end_date)){
			$select->where("f.AddTime <= ?", date('Y-m-d',strtotime($end_date)));
        }
		//获取sql
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
				
		//总条数
		$total = $feedbackModel->getAdapter()->fetchOne($countSql);
		
		//排序
		$sort = $this->_getParam('sort','f.FeedBackID');
		$order = $this->_getParam('order','desc');
		$select->order("$sort $order");
				
		$select->limitPage($pageIndex, $pageSize);
		
		//列表		
		$results = $feedbackModel->fetchAll($select)->toArray();
		$this->escapeVar($results);
		$this->_helper->json(array('total'=>$total,'rows'=>$results));
	}

	public function editAction()
	{
        $feedback_id = $this->_getParam('feedback_id');
        $this->view->feedback_id = $feedback_id;       
        if($this->_request->isPost()){

        	$feedbackModel = new Model_Feedback();
        	$ReplyContent = trim($this->_getParam('ReplyContent',''));
        	$feedback_id = intval($this->_getParam('feedback_id',0));
        	if (empty($ReplyContent)) {
        		$this->returnJson(0,'回复内容不能为空');
        	}
        	if($feedback_id > 0){
    			if($feedbackModel->update(array('ReplyContent'=>$ReplyContent),array('FeedBackID = ?' => $feedback_id))){
    				try{
    					//发送消息
						$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
						$easeModel = new Model_IM_Easemob();
						$content = $ReplyContent;
						$username = array();
						$info = $feedbackModel->getInfo($feedback_id);
						$username[]= $info['MemberID'];

						$ret = $easeModel->yy_hxSend($username, $content,'text','users',array('optionRand'=>1),$sysMemberID);
						$retArr = json_decode($ret,true);
						if(is_array($retArr) && !empty($retArr['data'])){
							foreach($retArr['data'] as $memberID=>$resSign){
								if($resSign == 'success'){
									//修改回复状态
									$feedbackModel->update(array('IsReply'=>1),array('FeedBackID = ?' => $feedback_id));
									$this->returnJson(1,'编辑成功');
								}else{
									$this->returnJson(0,'编辑失败');
								}																	
							}
						}							    				
					}catch(Exception $e){
						var_dump($e->getMessage());   
						exit; 
					}    				
    			}
    		}else{
    			$this->returnJson(0,'参数错误');
    		}
          
        }
	}
}