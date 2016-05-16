<?php
/**
 * 文章Api 接口
 * 
 *
 */
class Api_ArticleController extends DM_Controller_Account
{
    /**
     * @Privilege ping::category-menu 获取分类子菜单
     */
    // public function categoryMenuAction(){
    //     $model = new DM_Model_Table_Article_Article();
    //     $cateData = $model->getOrderCate();
    
    //     $locale=$this->getLocale();
    //     foreach($cateData as $k=>&$item){
    //         $str='';
    //         for($i=1;$i<$item['tier'];$i++){
    //             $str='&nbsp;&nbsp;'.$str;
    //         }
    //         if($locale == 'en'){
    //             $item['Name'] = $item['NameEn'];
    //         }
    //         $cateData[$k]['name']=$str.$item['Name'];
    //     }
    
    //     $this->returnJson(self::STATUS_OK,'',$cateData);
    // }
    /**
     * 文章分类菜单
     *
     * @Privilege ping::category-list 文章分类菜单
     */
    public function categoryListAction()
    {
        $category_id = (int) $this->_getParam ('categoryID',2); // 默认常见问题（id）
        $locale=$this->getLocale();
        $model = new DM_Model_Table_Article_Article ();
        $category_info=$model->getFrontCate($locale,$category_id);  
        foreach($category_info as $key=>&$item){
            // if($item['Name']=='独立页面'){
            //     if($this->_lang == 'en'){
            //         $sql = 'select ArticleID,TitleEn as Title from articles where CategoryID = :category_id';
            //     }else{
            //         $sql = 'select ArticleID,Title from articles where CategoryID = :category_id';
            //     }
            //     $cate_independ= $model->getAdapter()->fetchAll($sql,array('category_id'=>$item['CategoryID']));
            //     unset($category_info[$key]);
            // }
            unset($item['PID']);
            unset($item['children']);
            unset($item['tier']);
        }
        $data['categoryList']=$category_info;
        if(!empty($cate_independ)){
            $data['categoryDetail']=$cate_independ;
        }
        $this->returnJson(self::STATUS_OK,'',$data);
    }

    /**
     * 文章列表
     *
     * @Privilege ping::article-list 文章列表
     */
    public function articleListAction()
    {
        $category_id = (int) $this->_getParam ('categoryID',1); // 默认更新日志（id）
        //$title = trim($this->_getParam ('title',''));
        $pageIndex= $this->_getParam('page', 1);
        $pageSize = $this->_getParam('pagesize', 10);
    
        $articleModel = new DM_Model_Table_Article_Article ();
        $search['CategoryID'] = $category_id;
        //$search['Title'] = $title;
        $select = $articleModel->getPageList($search, $order = "ArticleID desc",$this->getLocale());
    
        if($category_id == 1){
        	$platform = intval($this->_request->getParam('platform',2));
        	$select->where('platform = ?',$platform);
        }
        
        //获取sql
        $countSql = $select->__toString();
        $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
        //总条数
        $total = $articleModel->getAdapter()->fetchOne($countSql);
        
        $results = $select->limitPage($pageIndex, $pageSize)->query()->fetchAll();

        $results=DM_Helper_String::htmlEncode($results);
        $cate_name=$articleModel->getOneCate($category_id);
        if($this->getLocale() == 'en'){
            $cate_name = $cate_name['NameEn'];
        }else{
            $cate_name=$cate_name['Name'];
        }
        $this->returnJson(parent::STATUS_OK,'',array('total'=>$total,'CategoryID'=>$category_id,'CategoryName'=>$cate_name,'rows'=>$results));
    }
    

    /**
     * 点赞
     *
     * @Privilege ping::article-detail 文章详细页面
     */
    public function likeNumAction()
    {
        $article_id =(int)$this->_getParam ("ArticleID");
        $articleModel = new DM_Model_Table_Article_Article ();
        if(empty($article_id)){
            $this->returnJson(self::STATUS_FAILURE,$this->getLang()->_("article.detail.error.idEmpty"));
        }
        $sql ='update articles set LikeNum=LikeNum+1 where ArticleID = :ArticleID';
        $res = $articleModel->getAdapter()->query($sql,array('ArticleID'=>$article_id));
        $select = $articleModel->select();
        $likeNum = $select->from('articles','LikeNum')->where('ArticleID = ?',$article_id)->query()->fetch();
        $this->returnJson(self::STATUS_OK,'',$likeNum);
    }
    
    /**
     * 详情页面
     */
    public function detailAction()
    {
    	Zend_Layout::startMvc()->disableLayout();
    	$id = intval($this->_request->getParam('id',0));
    	$mtype = intval($this->_request->getParam('mtype',0));
    	if(empty($id) || !in_array($mtype,array(1,2,3,4))){
    		exit('Params error!');
    	}
    	
    	$title = '';
    	$content = '';
    	$showTime = '';
    	$isFeedBack = 0;
        $url = '';
    	
    	$feedContents = '';
    	if($mtype == 1){
    			$messageModel = new Model_Message();
    			$messageInfo = $messageModel->getInfo($id);
    			if(empty($messageInfo)){
    				exit('不存在该信息!');
    			}else{

    				$title = $messageInfo['Title'];
    				$content = $messageInfo['Content'];
    				$showTime = $messageInfo['StartDate'];
    				$isFeedBack = $messageInfo['IsFeedback'];
    				$feedId = $messageInfo['FeedBackID'];
                    $url = $messageInfo['Url'];
    				if($feedId > 0){
    					$feedBackModel = new Model_Feedback();
    					$backInfo = $feedBackModel->select()->from('feedback')->where('FeedBackID = ?',$feedId)->query()->fetch();
    					if(!empty($backInfo)){
    						$feedContents = $backInfo['Content'];
    					}
    				}
                    $messageModel->updateViewNumber($id,$messageInfo['ViewNumbers']);
    			}
    	}elseif($mtype == 2 || $mtype == 3){
    		$articleModel = new DM_Model_Table_Article_Article();
    		$messageInfo = $articleModel->select()->from('articles')->where('ArticleID = ?',$id)->query()->fetch();
    		if(empty($messageInfo)){
    			exit('不存在该信息!');
    		}else{
    			$title = $messageInfo['Title'];
    			$content = $messageInfo['Contents'];
    			$showTime = $messageInfo['DataTime'];
    		}
    	}elseif($mtype == 4){
    		$feedBackModel = new Model_Feedback();
    		$backInfo = $feedBackModel->select()->from('feedback')->where('FeedBackID = ?',$id)->query()->fetch();
    		if(empty($backInfo)){
    			exit('不存在该信息!');
    		}else{
    			$title = $backInfo['Content'];
    			$content = $backInfo['ReplyContent'];
    			$showTime = $backInfo['AddTime'];
    		}
    	}
    	
    	$this->view->title = $title;
    	$this->view->content = $content;
    	$this->view->showTime = $showTime;
    	$this->view->mtype = $mtype;
    	$this->view->isFeedBack = $isFeedBack;
    	$this->view->feedContents = $feedContents;
        $this->view->url = $url;
    	
    	echo $this->view->render('/article/detail.phtml');
    }
}
