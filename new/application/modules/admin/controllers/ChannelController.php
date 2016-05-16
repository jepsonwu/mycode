<?php
/**
 * 频道管理 kitty
 */
class Admin_ChannelController extends DM_Controller_Admin {
    
    public function indexAction()
    {
    } 

    /**
     * 获取类型列表
     */
    public function listAction()
    { 
        //关闭视图
        $this->_helper->viewRenderer->setNoRender();
        $pageIndex = $this->_getParam('page',1);
        $pageSize = $this->_getParam('rows',50);
        
        $channelName = trim($this->_getParam('ChannelName',''));
        $start_date = $this->_getParam('start_date');
        $end_date = $this->_getParam('end_date');

        $channelModel = new Model_Channel();
        $select = $channelModel->select()->setIntegrityCheck(false);
        $select->from('channel');
        //$select->joinLeft('channel_focus as cf', 'cf.FocusID = f.FocusID','');
 
        if(!empty($channelName)){
            $select->where("ChannelName like ?", "%{$channelName}%");
        }

        if(!empty($start_date)){
            $select->where("CreateTime >= ?", $start_date);
        }

        if(!empty($end_date)){
            $select->where("CreateTime <= ?", $end_date);
        }

        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
                
        //总条数
        $total = $channelModel->getAdapter()->fetchOne($countSql);
        
        //排序
        $sort = $this->_getParam('sort','Sort');
        $order = $this->_getParam('order','desc');
        $select->order("$sort $order");
                
        $select->limitPage($pageIndex, $pageSize);
        
        //列表        
        $results = $channelModel->fetchAll($select)->toArray();
        $channelFocusModel = new Model_ChannelFocus();
        foreach ($results as &$item) {
            $focusIDs = $channelFocusModel->getChannelFocusID($item['ChannelID']);
            if(!empty($focusIDs)){
                $focusModel = new Model_Focus();
                foreach ($focusIDs as $focusID) {
                   $focusInfo = $focusModel->getInfo($focusID);
                   $item['FocusName'][] =$focusInfo['FocusName'];
                }
                $item['FocusName'] = implode(', ', $item['FocusName']);    
            }
        }

        $this->escapeVar($results);
        $this->_helper->json(array('total'=>$total,'rows'=>$results));
    } 

    public function addAction()
    {
        if ($this->_request->isPost()) {
            try {
                $channelName = trim($this->_request->getParam('ChannelName', ''));
                $sort = intval($this->_request->getParam('Sort', 0));
                if (empty($channelName)) {
                    $this->returnJson(parent :: STATUS_FAILURE, '频道名称不能为空!');
                } 
                $channelModel = new Model_Channel();
                $channelID = $channelModel->addChannel($channelName,$sort);

                if($channelID >0){
                    $focusIDArr = $this->_getParam('focusIDArr',array());
                    if( count($focusIDArr) >0 ) {
                        $channelFocusModel = new Model_ChannelFocus();
                        $focusIDs = $channelFocusModel->getChannelFocusID($channelID);
                        foreach ($focusIDs as $focusID) {
                            if(!in_array($focusID, $focusIDArr)){
                                $channelFocusModel->removeChannelFocus($focusID,$channelID);
                            }
                        }
                        foreach ($focusIDArr as $focusID) {
                            $channelFocusModel->addChannelFocus($focusID,$channelID);
                        }
                    }
                    $this->returnJson(1, '添加成功！');
                }else{
                    $this->returnJson(0, '添加失败，请修改后再试！');
                }
            } 
            catch (Exception $e) {
                $this->returnJson(parent :: STATUS_FAILURE, $e->getMessage());
            } 
        }
        $focusModel = new Model_Focus();
        $focusList = $focusModel->getFocusList();
        $this->view->focusList = $focusList;
    } 

    public function editAction()
    {
        $channel_id = intval($this->_getParam('channel_id',0));
        $channelModel = new Model_Channel();

        $focusModel = new Model_Focus();
        $channelFocusModel = new Model_ChannelFocus();
        $focusList = $focusModel->getFocusList();
        $this->view->focusList = $focusList;
        $selectFocus = $channelFocusModel->getChannelFocusID($channel_id);
        $this->view->selectFocus = $selectFocus;

        if($this->getRequest()->isPost()){
            $this->_helper->viewRenderer->setNoRender();       
            try {
                $channel_id = intval($this->_getParam('ChannelID',0));
                if($channel_id <= 0 ){
                    $this->returnJson(0,'参数错误!');
                }
                $ChannelName = trim($this->_getParam('ChannelName',''));
                if(empty($ChannelName)){
                    $this->returnJson(0,'频道名称不能为空!');
                }
                $sort = intval($this->_request->getParam('Sort', 0));
                $return = $channelModel->update(array('ChannelName'=>$ChannelName,'Sort'=>$sort),array('ChannelID =?'=>$channel_id));

                $focusIDArr = $this->_getParam('focusIDArr',array());
                if( count($focusIDArr) >0 ) {
                    $focusIDs = $channelFocusModel->getChannelFocusID($channel_id);
                    foreach ($focusIDs as $focusID) {
                        if(!in_array($focusID, $focusIDArr)){
                            $channelFocusModel->removeChannelFocus($focusID,$channel_id);
                        }
                    }
                    foreach ($focusIDArr as $focusID) {
                        $channelFocusModel->addChannelFocus($focusID,$channel_id);
                    }
                }
           
            } catch (Exception $e) {
                $this->returnJson(0, $e->getMessage());
            }
            $this->returnJson(1);
        }
         $channelInfo = $channelModel->find($channel_id)->toArray();
         $this->escapeVar($channelInfo);
         $this->view->channelInfo = $channelInfo[0];
    }

    public function removeAction()
    {

        try {
            $channel_id = intval($this->_getParam('id',0));
            if($channel_id <= 0 ){
                $this->returnJson(0,'参数错误!');
            }
            $channelModel = new Model_Channel();
            if ($channelModel->removeChannel($channel_id)) {
                $this->returnJson(parent :: STATUS_OK, '删除成功');
            } else {
                $this->returnJson(parent :: STATUS_FAILURE, '删除失败');
            } 
        } 
        catch(Exception $e) {
            $this->returnJson(parent :: STATUS_FAILURE, $e->getMessage());
        } 
    } 
} 
