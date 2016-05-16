<?php
/**
 * 系统礼物管理
 * Jeff
 */
class Admin_GiftController extends DM_Controller_Admin
{
	public function indexAction() {
		
	}
	public function listAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$pageIndex = $this->_getParam('page', 1);
		$pageSize = $this->_getParam('rows', 50);
		$giftName = $this->_getParam('giftName','');
		$model = new Model_Gift();
		try {
			$select = $model->select()->from('gifts','*');
			if(!empty($giftName)){
				$select = $select->where('GiftName like ?','%'.$giftName.'%');
			}
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
			
			//总条数
			$total = $model->getAdapter()->fetchOne($countSql);
			$result = $select->order('GiftID desc')->limitPage($pageIndex, $pageSize)->query()->fetchAll();
			//print_r($result);exit;
			$this->_helper->json(array('total' => $total, 'rows' => $result ? $result : array()));
		}
		catch (Exception $e) {
			$this->returnJson(parent :: STATUS_FAILURE, $e->getMessage());
		}
	}
	
public function addAction() {
		$model = new Model_Gift();
		$this->view->status = array(0=>'无效',1=>'有效');
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender();
			$data = array();
			$data['GiftName'] = $this->_request->getParam('giftName', '');
			$data['Price'] = $this->_request->getParam('price', '');
			$data['Unit'] = $this->_request->getParam('Unit', '');
			$data['Type'] = intval($this->_request->getParam('giftType'));
			$Status = intval($this->_request->getParam('Status'));
			$data['Status'] = $Status;
			
			$upload_image = isset($_FILES['icon'])?$_FILES['icon']:'';
			if(empty($data['GiftName'])){
				$this->returnJson(parent::STATUS_FAILURE, '礼物名称不能为空');
			}
			if(empty($data['Unit'])){
				$this->returnJson(parent::STATUS_FAILURE, '礼物单位不能为空');
			}
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
					$iconImg = "http://img.caizhu.com/".$ret['hash'];
					unlink($file);
				}
				$data['Cover']	= $iconImg;
			}
			if( $id = (int) $this->_request->getParam('id') ) {
				// 编辑
				try {
					$re = $model->update($data, array('GiftID = ?'=>$id));
					if($re === false) {
						$this->returnJson(parent::STATUS_FAILURE, '修改失败');
					} else {
						$this->returnJson(parent::STATUS_OK, '修改成功');
					}
				} catch (Exception $e) {
					$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
				}
			} else {
				if(empty($upload_image)){
					$this->returnJson(parent::STATUS_FAILURE, '礼物icon不能为空');
				}
				// 添加
				try {
					$inserID = $model->insert($data);
					if($inserID) {
						$this->returnJson(parent::STATUS_OK, '添加成功');
					} else {
						$this->returnJson(parent::STATUS_FAILURE, '添加失败');
					}
				} catch (Exception $e) {
					$this->returnJson(parent::STATUS_FAILURE, $e->getMessage());
				}
			}
		} else {
			if( $id = (int) $this->_request->getParam('id') ) {
				$info = $model->select()->from('gifts','*')->where('GiftID = ?',$id)->query()->fetch();
				$this->view->info = $info;
			} else {
				// 显示添加会员表单
			}
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
}