<?php
/**
 * 广告位管理
 * @author Jeff
 */

class Admin_WordsAdsController extends DM_Controller_Admin {
	public function indexAction() {
		$model = new Model_WordsAds();
		$this->view->showType = $model->getAdsShowType();
	}
	public function listAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('rows', 50);
		$model = new Model_WordsAds();
		$where = 'words_ads.Status = 1';
		if ($start_date = $this->_getParam('start_date', '')) {
			$where .= ' AND UNIX_TIMESTAMP(words_ads.CreateTime) >=' . strtotime($start_date);
		}
		if ($end_date = $this->_getParam('end_date', '')) {
			$where .= ' AND UNIX_TIMESTAMP(words_ads.CreateTime) <=' . strtotime($end_date);
		}
		if (($ShowType = trim($this->_request->getParam('ShowType'))) != '') {
			$where .= ' AND words_ads.ModuleType=' . intval($ShowType);
		}
		$orderBy = 'words_ads.Sort asc';
		try {
			$result = $model->getAds($where, $orderBy, $pageSize, $pageSize * ($pageIndex - 1));
			
			$total = $model->getAdsTotal($where);
			$this->_helper->json(array('total' => $total, 'rows' => $result ? $result : array()));
		}
		catch (Exception $e) {
			$this->returnJson(0, $e->getMessage());
		}
	}

	public function addAction() {
		$modelAds = new Model_WordsAds();
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender();
			$data = array();
			if( $Title = trim($this->_request->getParam('Title', '')) ) {
				$data['Title'] = $Title;
			} else {
				$this->returnJson(0, '广告标题不能为空');
			}
			if(mb_strlen($Title,'UTF-8') > 20){
				$this->returnJson(0,'广告标题不能超过20个字符！');
			}
			if( $Link = trim($this->_request->getParam('Link')) ) {
				$data['Link'] = $Link;
			} else {
				$this->returnJson(0, '广告链接不能为空');
			}
			if( $ShowType = trim($this->_request->getParam('ShowType',1)) ) {
				$data['ModuleType'] = (int) $ShowType;
			} 
			if($Sort = intval($this->_request->getParam('Sort',0))){
				$data['Sort'] = $Sort;
			}
			if( $id = (int) $this->_request->getParam('id') ) {
				// 编辑
				try {
					if( $modelAds->updateAds($id, $data) ) {
						$this->returnJson(parent::STATUS_OK, '修改成功');
					} else {
						$this->returnJson(0, '修改失败');
					}
				} catch (Exception $e) {
					$this->returnJson(0, $e->getMessage());
				}
			} else {
				// 添加
				$count = $modelAds->getCount($ShowType);
				if($count>=3){
					$this->returnJson(0, '每个模块下最多添加3个广告');
				}
				try {
					if($modelAds->addAds($data) ) {
						$this->returnJson(parent::STATUS_OK, '添加成功');
					} else {
						$this->returnJson(0, '添加失败');
					}
				} catch (Exception $e) {
					$this->returnJson(0, $e->getMessage());
				}
			}
		} else {
			try {
				$model = new Model_WordsAds();
				if( $id = (int) $this->_request->getParam('id') ) {
					// 显示要编辑的数据表单
					if( $this->view->info = $modelAds->getAds($id) ) {
						$this->view->info = current($this->view->info);
						$this->view->showType = $model->getAdsShowType();
					}
				} else {
					// 显示添加会员表单
					$this->view->showType = $model->getAdsShowType();
				}
			} catch (Exception $e) {
				$this->returnJson(0, $e->getMessage());
			}
		}
	}

	public function removeAction() {
		if( !$id = (int) $this->_request->getParam('id') ) {
			$this->returnJson(0, '广告ID不能为空');
		}
		try {
			$model = new Model_WordsAds();
			if( $model->delAds($id)) {
				$this->returnJson(parent::STATUS_OK, '删除成功');
			} else {
				$this->returnJson(0, '删除失败');
			}
		} catch (Exception $e) {
			$this->returnJson(0, $e->getMessage());
		}
	}
}
