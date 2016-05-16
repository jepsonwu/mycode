<?php
include_once dirname(__FILE__).'/Abstract.php';
class Web_ArticleController extends Action_Web
{
	public function init()
	{
		parent::init();
	    if($this->action_name!='upload-file'){
			$this->hasColumn();
		}
		//header('Content-type: text/html');
	}
	/**
	 * 文章列表页面
	 */
	public function indexAction()
	{
		$this->view->headTitle("财猪 - 文章");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$columnID = $this->columnID;
		$rowcount = 0;
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article',array('AID','Title','Content','Cover','PublishTime','ReadNum','PraiseNum','MemberID','IsCharge','IsTimedPublish','CommentNum'))
		->where('columnID = ?',$columnID)->where('Status = ?',1)->order('AID desc');
		
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);

		//总条数
		$total = $articleModel->getAdapter()->fetchOne($countSql);
		
		//$articleInfo = $select->order('AID desc')->limitPage($page, $pageSize)->query()->fetchAll();
		if(!empty($paginator)){
			foreach ($paginator as  &$value) {
				$contents = strip_tags($value['Content']);
				$len = mb_strlen($contents,'utf-8');
				if($len<=50){
					$value['Content'] = $contents;
				}else{
					$value['Content'] =mb_substr($contents,0,50,'utf-8').'...';
				}
				$value['PublishTime'] = date('Y.m.d H:i',strtotime($value['PublishTime']));
			}
		}
		$this->view->total = $total;
		$this->view->articleInfo = $paginator;
	}
	
	/**
	 * 文章详情
	 */
	public function detailAction()
	{
		$this->view->headTitle("财猪 - 文章详情");
		$articleID = intval($this->_getParam('articleID',0));
		if($articleID<1){
			$this->returnJson(parent::STATUS_FAILURE,'该文章不存在！');
		}
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article',array('AID','MemberID','Title','Content','PublishTime','ReadNum','PraiseNum','ShareNum','Author','Type','IsCharge','IsTimedPublish','Cost','Status','CommentNum'))
		->where('AID = ?',$articleID)->where('Status != ?',0);
		$dataList = $select->query()->fetch();
		$memberModel = new DM_Model_Account_Members();
		$dataList['UserName'] = $memberModel->getMemberInfoCache($dataList['MemberID'],'UserName');
		$dataList['PublishTime'] = date('Y.m.d H:i',strtotime($dataList['PublishTime']));
		$this->view->dataList = $dataList;
	}
	
	/**
	 * 删除文章
	 */
	public function deleteAction()
	{
		$articleID = intval($this->_getParam('articleID',0));
		if($articleID<1){
			$this->returnJson(parent::STATUS_FAILURE,'参数错误！');
		}
		$articleModel = new Model_Column_Article();
		$articleInfo = $articleModel->getArticleInfo($articleID);
		if(empty($articleInfo)){
			$this->returnJson(parent::STATUS_FAILURE,'该文章已不存在！');
		}
		if($this->memberInfo->MemberID != $articleInfo['MemberID']){
			$this->returnJson(parent::STATUS_FAILURE,'您无权修改该文章！');
		}
		$articleModel->update(array('Status'=>0), array('AID = ?'=>$articleID));
		if($articleInfo['Status'] == 1)
		{
			$columnModel = new Model_Column_Column();
			$columnModel->increaseArticleNum($articleInfo['ColumnID'],-1);
		}
		$this->returnJson(parent::STATUS_OK,'删除成功！');
	}
	
	/**
	 * 创建文章
	 */
	public function addAction()
	{
		
		$this->view->headTitle("财猪 - 创建文章");
		$focusModel = new Model_Focus();
		$fieldsArr = array('FocusID','FocusName');
		$articleID = intval($this->_getParam('articleID',0));
		$select = $focusModel->select()->from('focus',$fieldsArr)->where('IsTopicFocus = ?',1);
		$focusArr = $select->query()->fetchAll();
		$articleModel = new Model_Column_Article();
		$select = $articleModel->select()->from('column_article',array('AID','Title','CreateTime'))
		->where('MemberID = ?',$this->memberInfo->MemberID)->where('Status = ?',2);
		$draftInfo = $select->order('AID desc')->limit(10)->query()->fetchAll();
		if(!empty($draftInfo)){
			foreach($draftInfo as &$val){
				$val['CreateTime'] = date('Y.m.d',strtotime($val['CreateTime']));
			}
		}
		$isSystemMember = $this->isSystemMember($this->memberInfo->MemberID);
		$this->view->focusArr = $focusArr;
		$this->view->draftInfo = $draftInfo;
		$this->view->articleID = $articleID;
		$this->view->isSystemMember = $isSystemMember;

	}
	
	public function createAction()
	{
		$memberID = $this->memberInfo->MemberID;
		$columnID = $this->columnID;
		$articleID = intval($this->_getParam('articleID',0));
		$articleModel = new Model_Column_Article();
		$status = intval($this->_getParam('formType',1));//1发布，2保存草稿箱，3预览
		$title = trim($this->_getParam('title',''));
		$author = trim($this->_getParam('author',''));
		$focusID = trim($this->_getParam('focusID',''));
		$coverUrl = trim($this->_getParam('cover',''));
		$type = intval($this->_getParam('articleType',1));
		$content = trim($this->_getParam('articleContent',''));
		$articleLink = trim($this->_getParam('articleLink',''));
		$description = trim($this->_getParam('description',''));
		$isCharge = intval($this->_getParam('isCharge',0));//1收费 0 不收费
		$cost = trim($this->_getParam('cost',0));
		$isTimedPublish = intval($this->_getParam('isTimedPublish',0));//是否定时发布
		$publishTime = $this->_getParam('publishTime','');
		$isNormal = intval($this->_getParam('isNormal',0));//0正常保存 1不正常保存
		$increaseNum = 0;
		$focusArr = array();
		if(!$isNormal){
			if(empty($title)){
				$this->returnJson(parent::STATUS_FAILURE,'标题不能为空！');
			}
			if(mb_strlen($title,'utf-8')>50){
				$this->returnJson(parent::STATUS_FAILURE,'标题最多50个字符！');
			}
			if(empty($coverUrl)){
				$this->returnJson(parent::STATUS_FAILURE,'请上传头像！');
			}
			if(empty($focusID)){
				$this->returnJson(parent::STATUS_FAILURE,'请选择文章标签！');
			}
			$focusArr = explode(",", $focusID);
			if(count($focusArr)>3){
				$this->returnJson(parent::STATUS_FAILURE,'最多只能选择3个文章标签！');
			}
			if(mb_strlen($description,'utf-8')>100){
				$this->returnJson(parent::STATUS_FAILURE,'文章简介不能大于100个字符！');
			}
			if(empty($content)){
				$this->returnJson(parent::STATUS_FAILURE,'文章内容不能为空');
			}
			if($isCharge && !$cost){
				$this->returnJson(parent::STATUS_FAILURE,'请输入费用！');
			}
			if($isTimedPublish && empty($publishTime)){
				$this->returnJson(parent::STATUS_FAILURE,'请选择发布时间！');
			}
			if($isCharge && $cost<1){
				if(!preg_match('/^\d+(\.\d{2})?$/',$cost)){
					$this->returnJson(parent::STATUS_FAILURE,'金额最多只能是两位小数！');
				}
				if(intval($cost*100)<1){
					$this->returnJson(parent::STATUS_FAILURE,'金额不能小于0.01元');
				}
				//$this->returnJson(parent::STATUS_FAILURE,'收费金额只支持非0整数(单位：元)');
			}
		}else{
			if(empty($content)){
				$this->returnJson(parent::STATUS_FAILURE,'文章内容不能为空！');
			}
			if(empty($title)){
				$num = $this->getDraftNum(1,$memberID);
				$title = "草稿".$num;
				$increaseNum = 1;
			}
			if(empty($focusID)){
				$focusArr = explode(",", $focusID);
				if(count($focusArr)>3){
					$this->returnJson(parent::STATUS_FAILURE,'最多只能选择3个文章标签！');
				}
			}
		}
		if(empty($description)){
			$description = mb_substr(DM_Helper_Utility::DeleteHtml($content),0,100,'utf-8');
		}
		if(!empty($publishTime)){
			if(strtotime($publishTime)<=time()){
				$this->returnJson(parent::STATUS_FAILURE,'定时发布时间不能早于当前时间！');
			}
			$status = 2;
		}else{
			$publishTime = $status == 1?date("Y-m-d H:i:s"):'0000-00-00 00:00:00';
		}

		$paramArr = array(
				'MemberID'=>$memberID,
				'ColumnID'=>$columnID,
				'Title'=>$title,
				'Author'=>$author,
				'Cover'=>$coverUrl,
				'Content'=>$content,
				'Type'=>$type,
				'Status'=>$status,
				'PublishTime'=>$publishTime,
				'ArticleLink'=>$articleLink,
				'Description'=>$description,
				'IsCharge'=>$isCharge,
				'Cost'=>$cost,
				'IsTimedPublish'=>$isTimedPublish
		);
		$oldStatus = 0;
		$db = $articleModel->getAdapter();
		$db->beginTransaction();
		if(!$articleID){
			$articleID= $articleModel->insert($paramArr);
			$isInsert = 1;
		}else{
			if($status == 1){
				$info = $articleModel->getArticleInfo($articleID);
				$oldStatus = $info['Status'];
				if($oldStatus == 1){
					unset($paramArr['PublishTime']);
					$insertID = $articleID;
					$articleModel->update($paramArr, array('AID = ?'=>$articleID));
				}else{
					$re = $articleModel->delete(array('AID = ?'=>$articleID));
					if(!$re){
						$db->rollBack();
						$this->returnJson(parent::STATUS_FAILURE,'失败！');
					}
					$insertID= $articleModel->insert($paramArr);
					if(!$insertID){
						$db->rollBack();
						$this->returnJson(parent::STATUS_FAILURE,'失败！');
					}
				}
				$articleID = $insertID;
			}else{
				if($status == 3){
					unset($paramArr['PublishTime']);
					unset($paramArr['Status']);
				}
				$articleModel->update($paramArr, array('AID = ?'=>$articleID));
			}
			$focusModel = new Model_Column_ArticleFocus();
			$focusModel->delete(array('ArticleID = ?'=>$articleID));
			$isInsert = 0;
		}
		//专栏文章数加1
		if($status == 1 && $oldStatus != 1)
		{
			$columnModel = new Model_Column_Column();
			$columnModel->increaseArticleNum($columnID);
		}
		$qrcodeUrl = '';
		if($articleID > 0 ){	
			if($isInsert>0){
				$import_path = APPLICATION_PATH.'/../public/upload/';
				$filename = $articleID.'Article'.time().'.png';
				$url = $this->_fullHost."/api/public/article-detail?articleID=".$articleID."&isPreview=1";
				DM_Module_QRcode::png($url,$import_path.$filename,"L",'6',0);
				$qiniu = new Model_Qiniu();
				$token = $qiniu->getUploadToken();
				$uploadMgr = $qiniu->getUploadManager();
	
				$file = realpath($import_path.$filename);
				list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
				if ($err !== null) {
					var_dump($err);
				} else {
					$qrcodeUrl = "http://img.caizhu.com/".$ret['hash'];
					unlink($file);
				}
				$articleModel->update(array('QrcodeUrl'=>$qrcodeUrl), array('AID = ?'=>$articleID));
			}else{
				$info = $articleModel->getArticleInfo($articleID);
				$qrcodeUrl = $info['QrcodeUrl'];
			}
			$focusModel = new Model_Column_ArticleFocus();
			if(!empty($focusArr)){
				foreach ($focusArr as $value) {
					$focusModel->addFocus($articleID,$value);
				}
			}
		}
		if($status==1 && $oldStatus != 1){
			$field = array('type'=>1,'AID'=>$articleID,'publishTime'=>time());
			$columnModel::staticData($columnID,$field,-1);
		}
		$db->commit();
		$data = array('articleID'=>$articleID,'qrCodeUrl'=>$qrcodeUrl);
		$msg = $isTimedPublish ? '文章已存入草稿箱，将于'.$publishTime.'定时发表':'创建成功';
		$this->returnJson(parent::STATUS_OK,$msg,$data);
	}
	
	public function uploadFileAction()
	{
		$cover = isset($_FILES['imgFile'])?$_FILES['imgFile']:'';
		if (!empty($_FILES['imgFile']['error'])) {
			switch($_FILES['imgFile']['error']){
				case '1':
					$error = '图片大小不能超过5M。';
					break;
				case '2':
					$error = '超过表单允许的大小。';
					break;
				case '3':
					$error = '图片只有部分被上传。';
					break;
				case '4':
					$error = '请选择图片。';
					break;
				case '6':
					$error = '找不到临时目录。';
					break;
				case '7':
					$error = '写文件到硬盘出错。';
					break;
				case '8':
					$error = 'File upload stopped by extension。';
					break;
				case '999':
				default:
					$error = '未知错误。';
			}
			$this->returnJson(parent::STATUS_FAILURE,$error);
		}
		
		$limit = intval($this->_getParam('limit',0));
		if(empty($cover)){
			$this->returnJson(parent::STATUS_FAILURE,'图片不能为空！');
		}
		
		if($_FILES['imgFile']['size'] > 5*1024*1024){
			$this->returnJson(parent::STATUS_FAILURE,'图片大小不能超过5M');
		}
		
		$image_src = $this->processUpload($cover,$limit);
		$qiniu = new Model_Qiniu();
		$token = $qiniu->getUploadToken();
		$uploadMgr = $qiniu->getUploadManager();
		
		$file = realpath(APPLICATION_PATH.'/../public/upload'.$image_src);
		list($ret, $err) = $uploadMgr->putFile($token['token'], null, $file);
		if ($err !== null) {
			var_dump($err);
		} else {
			$urlImg = "http://img.caizhu.com/".$ret['hash'];
			unlink($file);
		}
		$coverUrl	= $urlImg;
		
		$this->returnJson(parent::STATUS_OK,'上传成功',array('url'=>$coverUrl));
	}
	
	/**
	 * 获取文章信息
	 */
	public function getArticleInfoAction()
	{
		$articleID = intval($this->_getParam('articleID',0));
		if($articleID<1){
			$this->returnJson(parent::STATUS_FAILURE,'文章ID不能为空！');
		}
		$articleModel = new Model_Column_Article();
		$foucusModel = new Model_Column_ArticleFocus();
		$info = $articleModel->getArticleInfo($articleID);
		$focusArr = $foucusModel->getInfo($articleID);
		$focuIDs = array();
		if(!empty($focusArr)){
			foreach($focusArr as $val){
				$focuIDs[]=$val['FocusID'];
			}
		}
		$info['FocusIDs'] = $focuIDs;
		$this->returnJson(parent::STATUS_OK,'成功',$info);
	}
	
	/**
	 * 修改文章收费金额
	 */
	public function modifyMoneyAction()
	{
		$articleID = intval($this->_getParam('articleID',0));
		if($articleID<1){
			$this->returnJson(parent::STATUS_FAILURE,'文章ID不能为空！');
		}
		$money = $this->_getParam('money',0);
		if(!preg_match('/^\d+(\.\d{2})?$/',$money)){
			$this->returnJson(parent::STATUS_FAILURE,'金额最多只能是两位小数！');
		}
// 		if($money<1){
// 			$this->returnJson(parent::STATUS_FAILURE,'收费金额只支持非0整数(单位：元)');
// 		}
		$articleModel = new Model_Column_Article();
		$articleInfo = $articleModel->getArticleInfo($articleID);
		if(empty($articleInfo)){
			$this->returnJson(parent::STATUS_FAILURE,'该文章已不存在！');
		}
		if($this->memberInfo->MemberID != $articleInfo['MemberID']){
			$this->returnJson(parent::STATUS_FAILURE,'您无权修改该文章！');
		}
		$articleModel->update(array('Cost'=>$money), array('AID = ?'=>$articleID));
		$this->returnJson(parent::STATUS_OK,'修改成功！');
	}
	
	public function commentListAction()
	{
		$this->view->headTitle("财猪 - 文章评论");
		$page = intval($this->_getParam('page',1));
		$pageSize = intval($this->_getParam('pageSize',10));
		$memberID = $this->memberInfo->MemberID;
		$rowcount = 0;
		$commentModel = new Model_Column_ArticleComment();
		$select = $commentModel->select()->setIntegrityCheck(false);
		$select->from('column_article_comment as c',array('c.CommentID','c.CommentContent','c.ArticleID','c.MemberID','c.Status','c.CreateTime'))
		->joinInner('column_article as a', 'c.articleID = a.AID',array("a.Title","a.Status as IsDel"))
		->where('a.MemberID = ?',$memberID)->where('c.Status != ?',0)->where('a.Status = ?',1)->where('RelationCommentID = ?',0)->order('c.CommentID desc');
		
		$adapter = new Zend_Paginator_Adapter_DbSelect($select);
		if($rowcount){
			$adapter->setRowCount(intval($rowcount));
		}
		$paginator = new Zend_Paginator($adapter);
		
		$paginator->setCurrentPageNumber($page)->setItemCountPerPage($pageSize);
		
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
		//总条数
		$total = $commentModel->getAdapter()->fetchOne($countSql);
		
		if(!empty($paginator)){
			$memberModel = new DM_Model_Account_Members();
			foreach ($paginator as  &$value) {
				$value['CommentContent'] = json_encode($value['CommentContent']);
				$value['ReplyList'] = $commentModel->getAuthorReply($value['CommentID']);
				$value['UserName'] = $memberModel->getMemberInfoCache($value['MemberID'],'UserName');
				$value['Avatar'] = $memberModel->getMemberAvatar($value['MemberID']);
				$value['CreateTime'] = Model_Topic_View::changeDateStyle($value['CreateTime']);
				
			}
		}
		$this->view->total = $total;
		$this->view->commentInfo = $paginator;
	}
}