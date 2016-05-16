<?php
class Admin_MessageController extends DM_Controller_Admin
{

     public function indexAction()
    {
        // $Model = new DM_Model_Table_Article_Article();
        // $pList = $Model->getAdminCate();
        // $this->view->cate=$pList;
    }

    /**
     * 列表
     */
    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $page = (int)$this->_getParam('page',1);
        $pageSize = (int)$this->_getParam('rows',50);

        $search['Title'] = trim($this->_getParam('Title'));
        $search['MessageType'] = trim($this->_getParam('MessageType'));
        $search['Sender'] = trim($this->_getParam('Sender'));
        $search['start_date'] = trim($this->_getParam('start_date'));
        $search['end_date'] = trim($this->_getParam('end_date'));
        $messageModel = new Model_Message();

        $select = $messageModel->select()->from('message');
        if(isset($search['Title']) && $search['Title']){
            $select->where("Title like ?", "%{$search['Title']}%");
        }
        if(isset($search['MessageType']) && $search['MessageType']){
            $select->where("MessageType = ?", $search['MessageType']);
        }
        if(isset($search['Sender']) && $search['Sender']){
            $select->where("Sender = ?", $search['Sender']);
        }
        if(isset($search['start_date'])&&$search['start_date']){
            $select->where("UpdateTime >= ?", "{$search['start_date']}");
        }
        if(isset($search['end_date'])&&$search['end_date']){
            $select->where("UpdateTime <= ?", "{$search['end_date']}");
        }

        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
        $totalCount = $messageModel->getAdapter()->fetchOne($countSql);

        $select->limitPage($page, $pageSize);
        $messageList = $select->query()->fetchAll();

        $this->_helper->json(array('total'=>$totalCount,'rows'=>$messageList));
    }
    /**
     * 添加
     */
    public function addAction()
    {   
        $Model = new Model_Message();
        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            $start_date = trim($this->_getParam('start_date'));
            if(empty($start_date)){
                $start_date = date('Y-m-d');
            }
            $ret['StartDate'] = $start_date;
            $end_date = trim($this->_getParam('end_date'));
            if(empty($end_date)){
                $end_date = date('Y-m-d');
            }
            $ret['EndDate'] = $end_date;
            if(!trim($ret['MessageType'])){
                $this->returnJson(0,'消息类型不能为空');
            }

            if(!trim($ret['Title'])){
                $this->returnJson(0,'标题不能为空');
            }

            if(!trim($ret['Content'])){
                $this->returnJson(0,'消息内容不能为空');
            }
            $ret['Sender'] = '客服';

            $return = $Model->add($ret);
            if($return){
                $this->returnJson(1,'添加成功');
            }else{
                $this->returnJson(0,'添加失败');
            }

        }
    }

    /**
     * 修改
     */
    public function editAction()
    {

        $model = new Model_Message();

        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            if(empty($ret['MessageType'])){
                $this->returnJson(0,'消息类型不能为空');
            }
            if(empty($ret['Title'])){
                $this->returnJson(0,'标题不能为空');
            }
            if(empty($ret['Content'])){
                $this->returnJson(0,'消息内容不能为空');
            }

            $return = $model->edit($ret);
            if($return){
                $this->returnJson(1,'修改成功');
            }else{
                $this->returnJson(0,'修改失败');
            }
            
        }

        $edit_id = $this->_getParam('edit_id');
        $message = $model->getInfo($edit_id);
        $this->view->message = $message;
    }

    /**
     * 删除
     */
    public function delAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_getParam('message_id');
        $messageModel  = new Model_Message();

        $info = $messageModel->delete($id);
        $flag = 0;
        $msg = '';
        if($info){
            $flag = 1;
        }else{
            $msg = '删除失败';
        }
        $this->returnJson($flag,$msg);
    }

}