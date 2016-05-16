<?php
/**
 * 广告位管理
 */

class Admin_AdsBarController extends DM_Controller_Admin {
    public function indexAction() {
        $model = new Model_Ads();
        $this->view->showType = $model->getAdsShowType();
    } 
    public function listAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $pageIndex = $this->_getParam('page', 1);
        $pageSize = $this->_getParam('rows', 50);
        $model = new Model_AdsBar();
        $where = 'ads_bar.ShowType >2';
        if ($start_date = $this->_getParam('start_date', '')) {
            $where .= ' AND UNIX_TIMESTAMP(ads_bar.CreateTime) >=' . strtotime($start_date);
        } 
        if ($end_date = $this->_getParam('end_date', '')) {
            $where .= ' AND UNIX_TIMESTAMP(ads_bar.CreateTime) <=' . strtotime($end_date);
        } 
        if (($ShowType = trim($this->_request->getParam('ShowType'))) != '') {
            $where .= ' AND ads_bar.ShowType=' . intval($ShowType);
        }
        if( $BarNum = (int) $this->_request->getParam('BarNum') ) {
            $where .= ' AND ads_bar.BarNum='.$BarNum;
        }
        try {
            $result = $model->getAdsBars($where, null, $pageSize, $pageSize * ($pageIndex - 1));
            $total = $model->getAdsBarTotal($where);
            $this->_helper->json(array('total' => $total, 'rows' => $result ? $result : array()));
        } 
        catch (Exception $e) {
            $this->returnJson(parent :: STATUS_FAILURE, $e->getMessage());
        } 
    } 

    public function addAction() {
        $modelAdsBar = new Model_AdsBar();
        if( $this->_request->isPost() ) {
            $this->_helper->viewRenderer->setNoRender();
            $data = array();
            if( $BarNum = (int) $this->_request->getParam('BarNum', '') ) {
                $data['BarNum'] = $BarNum;
            } else {
                $this->returnJson(parent::STATUS_FAILURE, '广告位代号不能为空');
            }
            if( $AdsType = trim($this->_request->getParam('AdsType')) ) {
                $data['AdsType'] = (int) $AdsType;
            } else {
                $this->returnJson(parent::STATUS_FAILURE, '请选择广告类型');
            }
            if( $ShowType = trim($this->_request->getParam('ShowType')) ) {
                $data['ShowType'] = (int) $ShowType;
            } else {
                $this->returnJson(parent::STATUS_FAILURE, '请选择展示类型');
            }
            if( $id = (int) $this->_request->getParam('id') ) {
                // 编辑
                try {
                    if( $modelAdsBar->updateAdsBar($id, $data) ) {
                        $this->returnJson(parent::STATUS_OK, '修改成功');
                    } else {
                        $this->returnJson(parent::STATUS_FAILURE, '修改失败');
                    }
                } catch (Exception $e) {
                    $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
                }
            } else {
                // 添加
                try {
                    if( $modelAdsBar->addAdsBar($data) ) {
                        $this->returnJson(parent::STATUS_OK, '添加成功');
                    } else {
                        $this->returnJson(parent::STATUS_FAILURE, '添加失败');
                    }
                } catch (Exception $e) {
                    $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
                }
            }
        } else {
            try {
                $model = new Model_Ads();
                if( $id = (int) $this->_request->getParam('id') ) {
                    // 显示要编辑的数据表单
                    if( $this->view->info = $modelAdsBar->getAdsBars($id) ) {
                        $this->view->info = current($this->view->info);
                        $this->view->showType = $model->getAdsShowType();
                    }
                } else {
                    // 显示添加会员表单
                    $this->view->showType = $model->getAdsShowType();
                }
            } catch (Exception $e) {
                $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
            }
        }
    }

    public function removeAction() {
        if( !$id = (int) $this->_request->getParam('id') ) {
            $this->returnJson(parent::STATUS_FAILURE, '广告位ID不能为空');
        }
        try {
            $model = new Model_AdsBar();
            if( $model->delAdsBar($id) ) {
                $this->returnJson(parent::STATUS_OK, '删除成功');
            } else {
                $this->returnJson(parent::STATUS_FAILURE, '删除失败');
            }
        } catch (Exception $e) {
            $this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
        }
    }
} 
