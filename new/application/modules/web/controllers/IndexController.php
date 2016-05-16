<?php
include_once dirname(__FILE__).'/Abstract.php';

class Web_IndexController extends Action_Web {
	
	/**
	 * 主页
	 */
	public function indexAction() {
		//exit('Home page of caizhu');
	    //$this->getSession()->time=time();
	    //print_r($_SESSION);
	    //清除缓存
	    //DM_Controller_Front::getInstance()->cleanLangCache();
	    //echo DM_Controller_Front::getInstance()->getLang()->_('site.title', time());
	    
	    //获取配置
	    //print_r(DM_Controller_Front::getInstance()->getConfig());
	    
	    //获取缓存
	    //print_r(DM_Controller_Front::getInstance()->getCache()->save('fdsafdsafdsafdsafdsafdsa', 'hello'));
        //print_r(DM_Controller_Front::getInstance()->getCache()->load('hello'));
	    
	    //echo "We'll comming soon ...";
	    Zend_Layout::getMvcInstance()->disableLayout();
		$authenticateModel =new Model_Authenticate();
		$member_id=$this->memberInfo->MemberID;
		$status=-1;
		$authenticateType = 0;
		if($member_id>0){
			$authenticateInfo = $authenticateModel->getInfoByMemberID($member_id);
			if(!empty($authenticateInfo)){
				$status = $authenticateInfo['Status'];
				$authenticateType =$authenticateInfo['AuthenticateType'];
			}		
		}

		$bestModel = new Model_Best_Best();
		$bestCount = $bestModel->countBestByMemberID($member_id,2);
		$this->view->status= $status;
		$this->view->authenticateType=$authenticateType;
		$this->view->columnStatus= $this->columnStatus;
		$this->view->bestCount = $bestCount;

	}

	/**
	 * [downAction 下载]
	 * @return [type] [description]
	 */
	public function downAction()
	{
 		Zend_Layout::getMvcInstance()->disableLayout();
	}

	/**
	 * [downAction 客服]
	 * @return [type] [description]
	 */
	public function kefuAction()
	{
		$this->view->headTitle("财猪财务管家 - 客服");
    	$page= $this->_getParam('page', 1);
        $pagesize = $this->_getParam('pagesize',4);
        $rowcount=0;
        $this->view->pagesize = $pagesize;
		$CategoryID = $this->_getParam('CategoryID',2);
		$articleModel = new DM_Model_Table_Article_Article();
        $select = $articleModel->select()->setIntegrityCheck(false);
        $titleName = $this->getLocale() == 'en' ? 'TitleEn as Title' : 'Title';
        $contents = $this->getLocale() == 'en' ? 'ContentsEn as Contents' : 'Contents';
        $select->from(array('a' => 'articles'),array('ArticleID',$titleName,$contents,'CategoryID','DataTime'));
        $select->joinLeft(array('c' => 'article_categorys'), "a.CategoryID=c.CategoryID", 'Name');
        $select->where('a.CategoryID = ?',$CategoryID);

        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        if($rowcount){
            $adapter->setRowCount(intval($rowcount));
        }
        $paginator = new Zend_Paginator($adapter);

        $paginator->setCurrentPageNumber($page)->setItemCountPerPage($pagesize);
        foreach ($paginator as  &$value) {
        	$date= getdate($value['DataTime']);
        	$value['month'] = $date['month'];
        	$value['day'] = $date['mday'];
        	$contents = strip_tags($value['Contents']);
        	$len = mb_strlen($contents,'utf-8');
        	if($len<=50){
				$value['Contents'] = $contents;
        	}else{
        		$value['Contents'] =mb_substr($contents,0,50,'utf-8').'...';
        	}
        }
        $this->view->data_list = $paginator;
	}


	/**
	 * [aboutAction 关于财猪]
	 * @return [type] [description]
	 */
	public function aboutAction()
	{
		$this->view->headTitle("财猪财务管家 - 关于财猪");
	}

	/**
	 * [contactAction 联系我们]
	 * @return [type] [description]
	 */
	public function contactAction()
	{
		$this->view->headTitle("财猪财务管家 - 联系我们");
	}

	/**
	 * [joinAction 加入我们]
	 * @return [type] [description]
	 */
	public function joinAction()
	{
		$this->view->headTitle("财猪财务管家 - 加入我们");
	}
	

	/**
	 * 测试提交更新
	 */
	public function submitAction()
	{
	    $url = 'http://book.caizhu.com/api/sync/submit-update';
	    
	    $data = array(

	              'invoice'=>array(
	    	                  array(  'InvoiceID'=>1,
	    	                  	      'Title'=>'早餐',
	    	                          'Amount'=>100,
	    	                          'RDate'=>'2015-03-10',
	    	                          'RTime'=>'2015-03-10 08:12:12',
	    	                          'MainType'=>2,
	    	                          'SubType'=>0,
	    	                          'AAType'=>0,
	    	                          'AccountBookId'=>1,
	    	                          'MicroTime'=>9963623,
	    	                          'Status'=>1,
  	    	                          'ProcessStatus'=>1,
	    	                          'FirstRemind'=>'2015-03-10 08:12:12',
  	    	                          'LastRemind'=>'2015-03-10 08:12:12',
	    	                          'UpdateTime'=>9900,
	    	                          'RelationMicrotime'=>0,
	    	                          'Remark'=>'ssssdfd',

	                                   ),
	                         array(  'InvoiceID'=>2,
	    	                  	      'Title'=>'晚餐',
	    	                          'Amount'=>100,
	    	                          'RDate'=>'2015-03-10',
	    	                          'RTime'=>'2015-03-10 21:12:12',
	    	                          'MainType'=>1,
	    	                          'SubType'=>0,
	    	                          'AAType'=>0,
	    	                          'AccountBookId'=>1,
	    	                          'MicroTime'=>9963624,
	    	                          'Status'=>2,
  	    	                          'ProcessStatus'=>1,
	    	                          'FirstRemind'=>'2015-03-10 08:12:12',
  	    	                          'LastRemind'=>'2015-03-10 08:12:12',
	    	                          'UpdateTime'=>9900,
	    	                          'RelationMicrotime'=>996623,
	    	                          'Remark'=>'ssssdfd',
	                         )
	               ),
	            
	          );   
	    $fields = array(
	    	'deviceNo'=>'aaaaaaaaaaaaaa2336',
	       'data'=>json_encode($data)
	    );
// 	    echo json_encode($data);exit;
// 	    echo http_build_query($fields);
// 	    $ret = file_get_contents($url.'?deviceNo='.$fields['deviceNo'].'&data='.$fields['data']);
	    echo $url.'?deviceNo='.$fields['deviceNo'].'&data='.$fields['data'];exit;
	    //var_dump($ret);exit;
	    $ret = Model_Tools::curl($url,$fields);
	    echo $ret;
	    exit;
	}
	
	/**
	 * 测试获取更新
	 */
	public function getAction()
	{
	    $url = 'http://book.caizhu.com/api/sync/get-update';
	   
	    $fields = array('deviceNo'=>'E1CE3E31-1195-4AF0-BDA1-CA6A62FC10B0');
 	    echo $url.'?deviceNo='.$fields['deviceNo'];exit;
	    $ret = Model_Tools::curl($url,$fields);
	    //print_r(json_decode($ret,true));
	    echo $ret;exit;
	}

    /**
	 * 测试确认更新
	 */
	public function confirmAction()
	{
	    $url = 'http://financing.com/api/sync/update-confirm';
	    
	    $confirmData = array('invoice'=>array(1,2),);
	    //echo json_encode($confirmData);exit;
	    $fields = array('deviceNo'=>'aaaaaaaaaaaaaa23ss','confirmData'=>json_encode($confirmData));
 	    echo $url.'?deviceNo='.$fields['deviceNo'].'&confirmData='.$fields['confirmData'];exit;
	    $ret = Model_Tools::curl($url,$fields);
	    //print_r(json_decode($ret,true));
	    echo $ret;exit;
	}
}