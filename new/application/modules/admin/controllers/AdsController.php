<?php

class Admin_AdsController extends DM_Controller_Admin {

	public function indexAction() {
		$model = new Model_Ads();
        $this->view->showType = $model->getAdsShowType();
        $this->view->status = $model->getAdsStatus();
        $this->view->platform = $model->getAdsPlatform();
	}
	
	public function listAction() {
		$this->_helper->viewRenderer->setNoRender();
		$pageIndex = (int) $this->_getParam('page', 1);
        $pageSize = (int) $this->_getParam('rows', 50);
        $where = 'true';
        if( $start_date = $this->_getParam('start_date', '') ) {
        	$where .= ' AND UNIX_TIMESTAMP(ads.CreateTime) >='.strtotime($start_date);
        }
        if( $end_date = $this->_getParam('end_date', '') ) {
        	$where .= ' AND UNIX_TIMESTAMP(ads.CreateTime) <='.strtotime($end_date);
        }
        if( ($showType = trim($this->_request->getParam('showType'))) != '' ) {
        	$where .= ' AND ads_bar.ShowType='.intval($showType);
        }
        if( ($status = trim($this->_request->getParam('status'))) != '' ) {
        	$where .= ' AND ads.Status='.intval($status);
        }
        if( ($AdsTitle = trim($this->_request->getParam('adsTitle'))) != '' ) {
        	$where .= ' AND ads.AdsTitle like '.$this->getDb()->quote('%'.$AdsTitle.'%');
        }
        $order = " AdsID desc";
		$model = new Model_Ads();
		$data = $model->getAdss($where, $order, $pageSize, $pageSize * ($pageIndex - 1));
		$totalCount = $model->getAdssTotal($where);
		if( $data ) {
			$this->_helper->json(array('total'=>$totalCount,'rows'=>$data));
		} else {
			$this->_helper->json(array('total'=>0, 'rows'=>array()));
		}
	}

	public function addAction() {
		$model = new Model_Ads();
		$this->view->status = $model->getAdsStatus();
		$this->view->showType = $model->getAdsShowType();
		$this->view->platform = $model->getAdsPlatform();
		//$adsDisplayModel = new Model_AdsDisplay();
		$displayModel = new Model_DisplayChannel();
		$this->view->display = $displayModel->getDisplayChannel();
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender();
			$data = array();
			$data['AdsTitle'] = $this->_request->getParam('AdsTitle', '');
			$data['Name'] = $this->_request->getParam('Name', '');
			$data['MemberID'] = intval($this->_request->getParam('MemberID', 0));
			$displayArr = $this->_request->getParam('displayArr',array());
			$allChecked = $this->_request->getParam('allChecked',false);
			if(!empty($displayArr) && $allChecked===false){
				$data['DisplayChannel']=implode(',', $displayArr);
			}else{
				$data['DisplayChannel']='';
			}
			if( $AdsTitle = $this->_request->getParam('AdsTitle', '') ) {
				$data['AdsTitle'] = $AdsTitle;
			} else {
				$this->returnJson(0, '标题不能为空');
			}
			// if( $Name = $this->_request->getParam('Name', '') ) {
			// 	$data['Name'] = $Name;
			// } else {
			// 	$this->returnJson(0, '名称不能为空');
			// }
			$img = '';
			if( $ValidFrom = trim($this->_request->getParam('ValidFrom')) ) {
				$data['ValidFrom'] = date('Y-m-d H:i:s', strtotime($ValidFrom));
			} else {
				$this->returnJson(0, '有效期开始时间不能为空');
			}
			if( $ValidEnd = trim($this->_request->getParam('ValidEnd')) ) {
				$data['ValidEnd'] = date('Y-m-d H:i:s', strtotime($ValidEnd));
			} else {
				$this->returnJson(0, '有效期结束时间不能为空');
			}
			$Status = intval($this->_request->getParam('Status'));
			$data['Status'] = $Status;
			
			if( $AdsBarID = trim($this->_request->getParam('AdsBar')) ) {
				$modelAdsBar = new Model_AdsBar();
				if( $modelAdsBar->getAdsBars($AdsBarID) == null ) {
					$this->returnJson(0, '广告位ID不存在');
				}
				$data['AdsBarID'] = (int) $AdsBarID;
                if($data['AdsBarID']==13 && $Status!=2){
                    $where = ' ads_bar.ShowType=8 and ads.Status!=2';
                    $totalCount = $model->getAdssTotal($where);
                    if($totalCount>1){
                        $this->returnJson(0, '财猪课堂广告只能添加一个！');
                    }
                }
			} else {
				$this->returnJson(0, '广告位ID不能为空');
			}
			$AdsLink = trim($this->_request->getParam('AdsLink',null));
			if( !is_null($AdsLink)) {
				$data['AdsLink'] = $AdsLink;
			}
			$upload_image = isset($_FILES['image'])?$_FILES['image']:'';
			if (!empty($upload_image)) {
				//$this->returnJson(0,'请上传gu图片！');
				$image_src = $this->processUpload($upload_image);
					
				$qiniu = new Model_Qiniu();
				$token = $qiniu->getUploadToken();
				$uploadMgr = $qiniu->getUploadManager();
					
				$file = realpath(APPLICATION_PATH.'/../public/upload'.$image_src);
				list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
				if ($err !== null) {
					var_dump($err);
				} else {
					//var_dump($ret);
					$adsImg = "http://img.caizhu.com/".$ret['hash'];
					unlink($file);
					$url = $adsImg.'?imageInfo';
					$adsImgInfo = $this->curl($url);
					$imageDetail = json_decode($adsImgInfo,true);
				}
				$data['ImgWidth'] = $imageDetail['width'];
				$data['ImgHeight'] = $imageDetail['height'];
				$data['AdsImg']	= $adsImg;
			}
			$upload_logo = isset($_FILES['logo'])?$_FILES['logo']:'';
			if (!empty($upload_logo)) {
				//$this->returnJson(0,'请上传gu图片！');
				$logo_src = $this->processUpload($upload_logo);
					
				$qiniu = new Model_Qiniu();
				$token = $qiniu->getUploadToken();
				$uploadMgr = $qiniu->getUploadManager();
					
				$file = realpath(APPLICATION_PATH.'/../public/upload'.$logo_src);
				list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
				if ($err !== null) {
					var_dump($err);
				} else {
					//var_dump($ret);
					$adsLogo = "http://img.caizhu.com/".$ret['hash'];
					unlink($file);
				}
				$data['Logo']	= $adsLogo;
			}
			$platform = intval($this->_request->getParam('platform'));
			$data['Platform'] = $platform;
	
			if( $id = (int) $this->_request->getParam('id') ) {
				// 编辑
				try {
					if( $model->updateAds($id, $data) ) {
						$this->returnJson(parent::STATUS_OK, '修改成功');
					} else {
						$this->returnJson(0, '修改失败');
					}
				} catch (Exception $e) {
					$this->returnJson(0, $e->getMessage());
				}
			} else {
				if(empty($upload_image)){
					$this->returnJson(0, '广告图片不能为空');
				}
				// 添加
				try {
					if( $model->addAds($data) ) {
						$this->returnJson(parent::STATUS_OK, '添加成功');
					} else {
						$this->returnJson(0, '添加失败');
					}
				} catch (Exception $e) {
					$this->returnJson(0, $e->getMessage());
				}
			}
		} else {
			$modelAdsBar = new Model_AdsBar();
			$this->view->adsBar = $modelAdsBar->getAdsBars('showType >=3',array('ShowType asc','BarNum asc'));
			if( $id = (int) $this->_request->getParam('id') ) {
				// 显示要编辑的数据表单
				if( $this->view->info = $model->getAdss($id) ) {
					$this->view->info = current($this->view->info);
					if(!empty($this->view->info['DisplayChannel'])){
						$this->view->displayChecked = explode(',',$this->view->info['DisplayChannel']);
					}
				}
				//$this->view->displayChecked = $adsDisplayModel->getListByAdsID($id);
			} else {
				// 显示添加会员表单
			}
		}
	}

	public function removeAction() {
		if( !$id = (int) $this->_request->getParam('id') ) {
			$this->returnJson(0, '广告ID不能为空');
		}
		try {
			$model = new Model_Ads();
			if( $model->delAds($id) ) {
				$this->returnJson(parent::STATUS_OK, '删除成功');
			} else {
				$this->returnJson(0, '删除失败');
			}
		} catch (Exception $e) {
			$this->returnJson(0, $e->getMessage());
		}
	}
	
	/**
	 * 处理图片上传
	 * @return string
	 */
	protected function processUpload($upload_image)
	{
		$src = '';
		if(isset($upload_image)){
			$upFile = $upload_image;
			$allowTypes = array('image/jpeg'=>'jpeg','image/gif'=>'gif','image/png'=>'png');
			if(!array_key_exists($upFile['type'],$allowTypes)){
				$this->returnJson(0,'图片格式不正确');
			}
	
			//文件类型
			$fileType = $allowTypes[$upFile['type']];
			//构造文件名
			$curTimestamp = time();
			$dir = '/focus/';
			if(!file_exists(APPLICATION_PATH.'/../public/upload'.$dir)){
				mkdir(APPLICATION_PATH.'/../public/upload'.$dir,0775,true);
			}
			$fileName = $dir.$curTimestamp.'_'.rand(10000,99999).'.'.$fileType;
			$fullPath = APPLICATION_PATH.'/../public/upload'.$fileName;
	
			if(is_uploaded_file($upFile['tmp_name'])){
				if(!move_uploaded_file($upFile['tmp_name'], $fullPath)){
					$this->returnJson(0,'移动文件出错啦');
				}
				//$src = 'http://'.$this->_request->getHttpHost().$fileName;
				$src = $fileName;
			}else{
				$this->returnJson(0,'出错啦');
			}
		}
	
		return $src;
	}

	public function curl($url)
	{
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);		
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$output  = curl_exec($ch);
		curl_close($ch);		 
		return $output ;
	}
	
}
