<?php
class Web_Abstract extends DM_Controller_Web{

    public function setActiveHeaderTab($tab){
        $this->view->activeTab=$tab;
    }
}