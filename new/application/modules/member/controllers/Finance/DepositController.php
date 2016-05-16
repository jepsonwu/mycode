<?php
include dirname(__FILE__).'/../Abstract.php';

class Member_Finance_DepositController extends Member_Abstract
{
    public function indexAction()
    {
        $this->view->headTitle($this->getLang()->translate('member.finance.index.amountview'));
    }

}