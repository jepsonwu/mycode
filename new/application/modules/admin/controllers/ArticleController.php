<?php
class Admin_ArticleController extends DM_Controller_Admin
{

     public function init()
    {
        $Model = new DM_Model_Table_Article_Article();
        $pList = $Model->getAdminCate();
        $cate=array();
        foreach ($pList as &$item) {
            if($item['CategoryID']==2 || $item['PID']==2 ){
                $cate[]=$item;
            }
        }
        $this->view->cate=$cate;
    }


    /**
     * 文章列表
     * (non-PHPdoc)
     * @see Zend_Controller_Action::__call()
     */
    public function __call($method,$params)
    {
        $this->_helper->viewRenderer->setNoRender();
        if(substr($method,0,7)== 'article'){
            //提现
            $fileName = substr($method,0,-6);
            $tmpfileName = '/article/'.$fileName.'.phtml';
            echo $this->view->render($tmpfileName);
        }
    }


    /**
     * 列表
     */
    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $page = (int)$this->_getParam('page',1);
        $pageSize = (int)$this->_getParam('rows',50);
        $search['cate_id'] = trim($this->_getParam('cate_id'));
        $search['title'] = trim($this->_getParam('title'));
        $search['start_date'] = trim($this->_getParam('start_date'));
        $search['end_date'] = trim($this->_getParam('end_date'));
        

        $articleModel = new DM_Model_Table_Article_Article();

        $select = $articleModel->select()->setIntegrityCheck(false);
        $select->from(array('a' => 'articles'));
        $select->joinLeft(array('c' => 'article_categorys'), "a.CategoryID=c.CategoryID");

        //获取类型
        $search['CategoryID'] = intval($this->_getParam('CategoryID',0));
        if($search['CategoryID']>0){
            $select->where('(a.CategoryID = ? or c.PID = ?)',$search['CategoryID']);
        }

        if(isset($search['title'])&&$search['title']){
            $select->where("a.Title like ?", "%{$search['title']}%");
        }
        if(isset($search['cate_id'])&&$search['cate_id']){
            $children = $articleModel->getOrderCate($search['cate_id']);
            $cate_ids = array();
            foreach($children as $item){
                $cate_ids[] = $item['cate_id'];
            }
            if(count($cate_ids)){
                $id = "({$search['cate_id']},".implode(',',$cate_ids).")";
                $select->where("a.CategoryID in {$id}");
            }
            else{
                $select->where("a.CategoryID = ?", "{$search['cate_id']}");
            }
        }
        if(isset($search['start_date'])&&$search['start_date']){
            $select->where("a.DataTime >= ?", "{$search['start_date']}");
        }
        if(isset($search['end_date'])&&$search['end_date']){
            $select->where("a.DataTime <= ?", "{$search['end_date']}");
        }

        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
        $totalCount = $articleModel->getAdapter()->fetchOne($countSql);

        $select->limitPage($page, $pageSize);
        $articleList = $select->query()->fetchAll();
        foreach($articleList as $key=>&$item){
            $cate_name = $articleModel->getOneCate($item['CategoryID']);
            $articleList[$key]['Name'] = $cate_name['Name'];
            //$item['DataTime'] = date('Y-m-d H:i:s',$item['DataTime']);
        }
        $this->_helper->json(array('total'=>$totalCount,'rows'=>$articleList));
    }
    /**
     * 添加
     */
    public function addAction()
    {   
        $Model = new DM_Model_Table_Article_Article();
        $articleModel = new Model_Article();
        $pList = $Model->getAdminCate();
        $CategoryID = intval($this->_getParam('CategoryID'),0);

        if($CategoryID >0){
            foreach ($pList as $key => &$item) {
                if(!($item['CategoryID'] ==$CategoryID || $item['PID']==$CategoryID)){
                    unset($pList[$key]);
                }
            }
        }
        $this->view->cate=$pList;
        $this->view->CategoryID = $CategoryID;
        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            if(!trim($ret['Title'])){
                $this->returnJson(0,'标题不能为空');
            }else{
                $return = $articleModel->add($ret);
                if($return){
                    $this->returnJson(1,'添加成功');
                }else{
                    $this->returnJson(0,'添加失败');
                }
            }
        }
    }
    /**
     * 修改
     */
    public function editAction()
    {
            $edit_id=$this->_getParam('edit_id');
            $lang = $this->_getParam('lang',1);
            $model = new DM_Model_Table_Article_Article();
            $articleModel = new Model_Article();
            $article=$articleModel->getDetail($edit_id);
            $cate1=$model->getOneCate($article['CategoryID']);
            $pList = $model->getAdminCate();
            $CategoryID = intval($this->_getParam('CategoryID'),0);
            if($CategoryID >0){
                foreach ($pList as $key => &$item) {
                    if(!($item['CategoryID'] ==$CategoryID || $item['PID']==$CategoryID)){
                        unset($pList[$key]);
                    }
                }
            }
            $this->view->cate=$pList;
            $this->view->cate1=$cate1;
            $this->view->article = $article;
            $this->view->lang = $lang;
            
            if($this->_request->isPost()){
                $ret = $this->_getParam('ret');
                if(empty($ret['Title']) && empty($ret['TitleEn'])){
                    $this->returnJson(0,'标题不能为空');
                }else{
                    $return = $articleModel->edit($ret);
                    if($return){
                        $this->returnJson(1,'修改成功');
                    }else{
                        $this->returnJson(0,'修改失败');
                    }
                }
            }
    }

    /**
     * 删除
     */
    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_getParam('article_id');
        $articleModel = new DM_Model_Table_Article_Article();
        $info = $articleModel->delete($id);
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