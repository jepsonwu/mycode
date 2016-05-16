<?php
include dirname(__FILE__).'/../Abstract.php';

class Member_Finance_RecordController extends Member_Abstract
{
    public function indexAction()
    {
        $this->view->headTitle(/*'资金记录'*/ $this->getLang()->translate('member.finance.record.page'));

    }
    
    private function initKvCurrency()
    {
        $currencies = $this->api('finance/get-currencies');
        if (!$currencies['flag'] || empty($currencies['data'])){
            throw new Zend_Exception($currencies['msg']);
        }
        $currencies=$currencies['data'];
    
        $kvCurrency=array();
        foreach ($currencies as $key=>$coin){
            $kvCurrency[$coin['type_id']]=$coin['en_name'];
        }
    
        $this->view->kvCurrency=$kvCurrency;
    }

    public function listAction()
    {

        $page = $this->_getParam('page',1);
        $pagesize = $this->_getParam('pagesize',10);
        $search= $this->_getParam('search');
        $search['amount_type']=isset($search['amount_type'])?implode(',',$search['amount_type']):'';
        $search['type_id']=isset($search['type_id'])?implode(',',$search['type_id']):'';
        $search['page']=$page;
        $search['pageSize']=$pagesize;
        $record = $this->api('finance/get-amount-log',$search);

        $pageCount=isset($record['data']['total'])?$record['data']['total']:0;
        if(isset($record['flag']) && $record['flag']){
            if(count($record['data']['list'])>0){
                $this->view->pageData =  $record['data']['list'];
            }
        }

        $adapter = new Zend_Paginator_Adapter_Null((int)$pageCount);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setCurrentPageNumber((int)$page)->setItemCountPerPage((int)$pagesize);
        $this->view->pageList = $paginator;

    }
    
    public function creditAction()
    {
       $this->view->headTitle(/*'充值记录'*/ $this->getLang()->translate('member.finance.record.charge.records'));

       $this->initKvCurrency();
    }
    
    public function creditlistAction()
    {
        $page = $this->_getParam('page',1);
        $pagesize = $this->_getParam('pagesize',10);
        $search= $this->_getParam('search');
        $search['type_id']=isset($search['type_id'])?implode(',',$search['type_id']):'';
        $search['page']=$page;
        $search['pageSize']=$pagesize;
        $record = $this->api('finance/get-amount-credit-log',$search);
        $pageCount=isset($record['data']['total'])?$record['data']['total']:0;
        if(isset($record['flag']) && $record['flag']){
            if(count($record['data']['list'])>0){
                $this->view->pageData =  $record['data']['list'];
//                 $totalCount = 0;
//                 $totalPrice = 0;
//                 $totalFee = 0;
//                 foreach($record['data']['list'] as $item){
//                     $totalCount = $item['amount'];
//                     $totalPrice = $item['total_price'];
//                     $totalFee = $item['fee'];
//                 }
//                 $totalData = array('totalCount'=>$totalCount,'totalPrice'=>$totalPrice,'totalFee'=>$totalFee);
//                 $this->view->totalData = $totalData;
            }
        }
        
        $adapter = new Zend_Paginator_Adapter_Null((int)$pageCount);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setCurrentPageNumber((int)$page)->setItemCountPerPage((int)$pagesize);
        $this->view->pageList = $paginator;
    }
}