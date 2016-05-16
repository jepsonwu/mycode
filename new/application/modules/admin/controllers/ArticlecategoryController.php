<?php 
/**
 * 文章分类管理
 * 
 * @author Carlton
 *
 */
class Admin_ArticlecategoryController extends DM_Controller_Admin
{

     public function indexAction()
    {

    }

    /**
     * 列表
     */
    public function listAction()
    {
        $model = new DM_Model_Table_Article_Article;
        $res = $model->getAdminCate();
        $this->_helper->json($res);
    }

    /**
     * 添加
     */
    public function addAction()
    {
           $model = new DM_Model_Table_Article_Article();
           
           $res = $model->getAdminCate();
        $this->view->cate=$res;
        
        if($this->_request->isPost()){
            $ret = $this->_getParam('ret');
            if(!trim($ret['Name'])){
                $this->returnJson(0,'名称不能为空');
            }else{
                $ret['Name'] = str_ireplace('script','',$ret['Name']);
                $return = $model->addCate($ret);
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
            $edit_id=$this->_getParam('EditID');
            $model = new DM_Model_Table_Article_Article();
            $detail = $model->getOneCate($edit_id);
            $res = $model->getAdminCate();
            $this->view->cate = $res;
            $this->view->detail = $detail;
            if($this->_request->isPost()){
                $ret = $this->_getParam('ret');
                if(!trim($ret['Name'])){
                    $this->returnJson(0,'名称不能为空');
                }else{
                    $ret['Name'] = str_ireplace('script','',$ret['Name']);
                    $return = $model->editCate($ret);
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
        $id = $this->_getParam('category_id');
        $categoryModel=new DM_Model_Table_Article_Category();
        $category=$categoryModel->getCategoryByPid($id);
        if(count($category)>0){
            $this->returnJson(0,'请先删除子分类！');
        }
        $articleModel = new DM_Model_Table_Article_Article();
        $info = $articleModel->deleteCate($id);
        $flag = 0;
        $msg = '';
        if($info){
            $flag = 1;    
        }else{
            $msg = '删除失败';
        }
        $this->returnJson($flag,$msg);
    }

    /**
     * 上移
     */
    public function upAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_getParam('CategoryID');
        $cateModel = new DM_Model_Table_Article_Category();
        $info = $cateModel->up($id, 'PID');
        $flag = 0;
        $msg = '';
        if($info){
            $flag = 1;
        }else{
            $msg = '无法移动';
        }
        $this->returnJson($flag,$msg);
    }
    /**
     * 下移
     */
    public function downAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_getParam('CategoryID');
        $cateModel = new DM_Model_Table_Article_Category();
        $info = $cateModel->down($id, 'PID');
        $flag = 0;
        $msg = '';
        if($info){
            $flag = 1;
        }else{
            $msg = '无法移动';
        }
        $this->returnJson($flag,$msg);
    }
    
    
}