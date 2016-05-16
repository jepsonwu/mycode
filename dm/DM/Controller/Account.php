<?php
/**
 * Api通用控制器
 * 
 * @author Kitty
 * @since 2014/05/22
 */
class DM_Controller_Account extends DM_Controller_Action
{
    /**
     * session命名空间 和web同，因为都前台
     * @var string
     */
    const SESSION_NAMESPACE='web';
    protected $memberInfo=null;

    public function init()
    {
        parent::init();
        $this->memberInfo = DM_Module_Account::getInstance()->setSession($this->getSession())->getLoginUser();
    }

    public final function preDispatch()
    {
        $this->_helper->viewRenderer->setNoRender();
    }
    
    /**
     * 验证Csrf验证码
     */
    protected function verifyCsrfCodeOutput()
    {
        if (!$this->verifyCsrfCode()) {
            $this->returnJson(parent::STATUS_FAILURE, $this->getLang()->_("secure.csrf.hack"));
        }else{
            return true;
        }
    }
    

    /**
     * 判断是否登录，直接返回
     */
    protected final function isLoginOutput()
    {
        $isLogin = $this->isLogin();
        if (!$isLogin){
            return $this->returnJson(parent::STATUS_NEED_LOGIN, $this->getLang()->_("api.base.error.notLogin"));
        }else{
            return true;
        }
    }
    
    /**
     * 是否登录
     */
    public function isLogin()
    {
    	return DM_Module_Account::getInstance()->setSession($this->getSession())->isLogin();
    }
    
    /**
     * 检测是否是post请求
     */
    protected function checkPostMethod()
    {
        return $this->isPostOutput();
    }

    /**
     * 分页操作
     * @param Zend_Db_Select $select
     * @param unknown_type $page
     * @param unknown_type $perpage
     * @return multitype:multitype: number
     */
    protected function getResultSet(Zend_Db_Select $select, $page = 1, $perpage = 10)
    {
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($perpage);
        $paginator->setCurrentPageNumber($page);
        $total = $paginator->getTotalItemCount();
        $items = $paginator->getCurrentItems();
        return array(
                'total'		=> $total,
                'rows'	=> iterator_to_array($items)
        );
    }
}
