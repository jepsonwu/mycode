<?php
/**
 * 专栏文章
 *
 * @author Jeff
 *
 */
class Model_Column_Article extends Zend_Db_Table
{
	protected $_name = 'column_article';
	protected $_primary = 'AID';
	
	
	/**
	 * 所有文章列表（财猪首页）
	 * @param unknown $memberID
	 * @param unknown $page
	 * @param unknown $pagesize
	 * @param unknown $lastBarNum
	 */
	public function allArticles($memberID,$page,$pagesize,$lastBarNum)
	{
		$articleInfo = array();
		$result = array();
		$select = $this->select();
		$select->from($this->_name,array('ContentType'=>new Zend_Db_Expr(2),'AID','MemberID','ColumnID','Title','Cover','PublishTime'))->where('Status = ?',1);
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
		//总条数
		$total = $this->getAdapter()->fetchOne($countSql); 
		$select->order("AID desc")->limitPage($page, $pagesize);
		$articleInfo = $select->query()->fetchAll();
		$memberModel = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
		$columnModel = new Model_Column_Column();
		$maxNum = $lastBarNum;
		if(!empty($articleInfo)){
			if($page==1 && $memberID>0){
				Model_Member::staticData($memberID,'lastNewArticleTime',time());
			}
			$adsModel = new Model_Ads();
			$adsCount = 1;
			if($lastBarNum==0 && count($articleInfo)>2){
				$adsCount = intval((count($articleInfo)-2) / 5)+2;
			}else{
				$adsCount = intval((count($articleInfo)) / 5)+1;
			}
			$ads = $adsModel->getAdsList($lastBarNum,$adsCount,4);
			$adsArr = $ads['ads'];
			$maxNum = $ads['maxNum'];
			foreach($articleInfo as $key=>&$val){
				if(!empty($adsArr)){
					if($lastBarNum == 0){
						if(($key-2) % 5 === 0 ){
							if(!empty($adsArr[intval(($key-2) / 5)+1])){
								$result[] = $adsArr[intval(($key-2) / 5)+1];
							}
						}
					}else{
						if(($key) % 5 === 0 ){
							if(!empty($adsArr[intval(($key) / 5)])){
								$result[] = $adsArr[intval(($key) / 5)];
							}
						}
					}
				}
				$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
				$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
				$result[] = $val;
				
			}
		}
		return array('lastBarNum'=>$maxNum,'Rows'=>$result);
	}
	/**
	 * 获取我关注人的文章
	 * @param unknown $memberID
	 * @param unknown $lastArticleID
	 * @param unknown $pagesize
	 * @param unknown $lastBarNum
	 */
	public function followedMemberArticle($memberID,$lastArticleID,$pagesize,$lastBarNum)
	{
		$articleInfo = array();
		$result = array();
		$maxNum = $lastBarNum;
		//获取我关注的人
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'User:Follow:MemberID:'.$memberID;
		$followArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		if(!empty($followArr)){
			$select = $this->select();
			$select->from($this->_name,array('ContentType'=>new Zend_Db_Expr(2),'AID','MemberID','Title','Cover','PublishTime'))->where('Status = ?',1);
			if($lastArticleID>0){
				$select->where('AID < ?',$lastArticleID);
			}
			$select->where("MemberID in (?)",$followArr);
			$select->order("AID desc")->limit($pagesize);
			$articleInfo = $select->query()->fetchAll();
			$memberModel = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
			if(!empty($articleInfo)){
				if($lastArticleID==0){
					Model_Member::staticData($memberID,'lastFollowedMemberArticleID',$articleInfo[0]['AID']);
				}
				$adsModel = new Model_Ads();
				$adsCount = intval(count($articleInfo) / 5)+1;
				$ads = $adsModel->getAdsList($lastBarNum,$adsCount,3);
				$adsArr = $ads['ads'];
				$maxNum = $ads['maxNum'];
				foreach($articleInfo as $key=>&$val){
					if(!empty($adsArr)){
						if($key % 5 === 0 && $key!=0){
							if(!empty($adsArr[intval($key / 5)])){
								$result[] = $adsArr[intval($key / 5)];
							}
						}
					}
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$result[] = $val;
				}
			}
		}
		return array('lastBarNum'=>$maxNum,'Rows'=>$result);
	}
	
	/**
	 * 我订阅专栏的文章
	 * @param unknown $memberID
	 * @param unknown $lastArticleID
	 * @param unknown $pagesize
	 */
	public function followedColumnArticle($memberID,$lastArticleID,$pagesize)
	{
		$articleInfo = array();
		$result = array();
		//获取我关注的专栏
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
		$columnArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		$subscribeNum = 0;
		$columnModel = new Model_Column_Column();
		$totalColumnNum = $columnModel->getColumnCount();
		if(!empty($columnArr)){
			$subscribeNum = count($columnArr);
			$select = $this->select();
			$select->from($this->_name,array('AID','MemberID','Title','Cover','PublishTime'))->where('Status = ?',1);
			if($lastArticleID>0){
				$select->where('AID < ?',$lastArticleID);
			}
			$select->where("ColumnID in (?)",$columnArr);
			$select->order("AID desc")->limit($pagesize);
			$articleInfo = $select->query()->fetchAll();
			$memberModel = new DM_Model_Account_Members();
			$memberNoteModel = new Model_MemberNotes();
			if(!empty($articleInfo)){
				if($lastArticleID==0){
					Model_Member::staticData($memberID,'lastFollowedColumnArticleID',$articleInfo[0]['AID']);
				}
				foreach($articleInfo as $key=>&$val){
					$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
					$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
					$result[] = $val;
				}
			}
		}
		return array('SubscribeNum'=>$subscribeNum,'ColumnNum'=>$totalColumnNum,'Rows'=>$result);
	}
	
	/**
	 * 获取头条文章
	 * @param unknown $pageIndex
	 * @param unknown $pageSize
	 */
	public function getTopArticle($pageIndex, $pageSize,$memberID,$isAds,$lastBarNum)
	{
		$articleInfo = array();
		$select = $this->select();
		$select->from($this->_name,array('ContentType'=>new Zend_Db_Expr(2),'AID','MemberID','ColumnID','Title','Cover','PublishTime'))->where('Status = ?',1);
		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
		
		//总条数
		$total = $this->getAdapter()->fetchOne($countSql);
		$select->order("AID desc")->limitPage($pageIndex, $pageSize);
		$articleInfo = $select->query()->fetchAll();
		$memberModel = new DM_Model_Account_Members();
		$memberNoteModel = new Model_MemberNotes();
		$columnModel = new Model_Column_Column();
		$result =array();
		$maxNum = $lastBarNum;
		if(!empty($articleInfo)){
			if($isAds){
				$adsModel = new Model_Ads();
				$adsCount = intval(count($articleInfo) / 5)+1;
				$ads = $adsModel->getAdsList($lastBarNum,$adsCount,7);
				$adsArr = $ads['ads'];
				$maxNum = $ads['maxNum'];
			}
			foreach($articleInfo as $key=>&$val){
				if($isAds && !empty($adsArr)){
					if($key % 5 === 0 && !($key == 0 && $lastBarNum == 0)){
						if(!empty($adsArr[intval($key / 5)])){
							$result[] = $adsArr[intval($key / 5)];
						}
					}
				}
				$val['NoteName'] = $memberNoteModel->getNoteName($memberID, $val['MemberID']);
				$val['UserName'] = $memberModel->getMemberInfoCache($val['MemberID'],'UserName');
				$val['ColumnTitle'] = $columnModel->getColumnInfoCache($val['ColumnID'],'Title');
				$result[] = $val;
			}
		}
		return array('LastBarNum'=>$maxNum,'Total'=>$total,'Rows'=>$result);
	}
	
	/**
	 * 给文章点赞
	 * @param unknown $articleID
	 * @param unknown $memberID
	 */
	public function addPraise($articleID, $memberID)
	{
		if(!$this->isPraised($articleID, $memberID)){
			//增加赞数
			$this->increasePraiseNum($articleID);
			//保存会员赞信息至Redis
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'Article:Praise:MemberID:'.$memberID;
			$redisObj->zadd($cacheKey,time(),$articleID);
		}
	}
	
	public function unPraise($articleID, $memberID)
	{
		$this->increasePraiseNum($articleID,-1);
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Article:Praise:MemberID:'.$memberID;
		$redisObj->zrem($cacheKey,$articleID);
		return true;
	}
	
	/**
	 *  获取赞的数量
	 * @param int $viewID
	 */
	public function getPraisedNum($articleID)
	{
		$select = $this->select();
		$praiseNum = $select->from($this->_name,'PraiseNum')->where('AID = ?',$articleID)->query()->fetchColumn();
		return $praiseNum ? $praiseNum : 0;
	}
	
	/**
	 *  增加赞
	 * @param int $viewID
	 * @param int $increament
	 */
	public function increasePraiseNum($articleID,$increament = 1)
	{
		if($increament>0){
			return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('AID = ?'=>$articleID));
		}else{
			return $this->update(array('PraiseNum'=>new Zend_Db_Expr("PraiseNum + ".$increament)),array('AID = ?'=>$articleID,'PraiseNum > ?'=>0));
		}
	}
	
	/**
	 *  是否已赞过
	 * @param int $viewID
	 * @param int $memberID
	 */
	public function isPraised($articleID,$memberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'Article:Praise:MemberID:'.$memberID;
		$score = $redisObj->zscore($cacheKey,$articleID);
		return $score ? 1 : 0;
	}
	
	/**
	 * 增加阅读数
	 * @param unknown $activityID
	 * @param number $increment
	 * @return number
	 */
	public function increaseReadNum($articleID,$increment=1)
	{
		return $this->update(array('ReadNum'=>new Zend_Db_Expr("ReadNum + ".$increment)),array('AID = ?'=>$articleID));
	}
	
	/**
	 * 增加评论数
	 * @param unknown $articleID
	 * @param number $increament
	 * @return number
	 */
	public function increaseCommentNum($articleID,$increament = 1)
	{
		return $this->update(array('CommentNum'=>new Zend_Db_Expr("CommentNum + ".$increament)),array('AID = ?'=>$articleID));
	}
	
	/**
	 * 获取某个人发布的文章数
	 */
	public function getArticleNum($memberID)
	{
		$info = $this->select()->from($this->_name,'count(1) as num')->where('MemberID = ?',$memberID)->where('Status = ?',1)->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * @param 我订阅的专栏是否产生文章
	 */
	public function hasNewFollowedColumnArticles($memberID)
	{
		$lastArticleID = Model_Member::staticData($memberID,'lastFollowedColumnArticleID');
		$info = array();
		if($lastArticleID !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
		    $columnArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		    if(!empty($columnArr)){
		    	$info = $this->select()->from($this->_name,array('AID'))->where("ColumnID in (?)",$columnArr)
		    	->where('Status = ?',1)->where('AID > ?',$lastArticleID)->limit(1)->query()->fetch();
		    }
		}
		return empty($info) ? 0 : 1;
	}
	
	/**
	 * 我关注的人是否发布新文章
	 */
	public function hasNewFollowedMemberArticles($memberID)
	{
		$lastArticleID = Model_Member::staticData($memberID,'lastFollowedMemberArticleID');
		$info = array();
		if($lastArticleID !== false){
			$redisObj = DM_Module_Redis::getInstance();
			$cacheKey = 'User:Follow:MemberID:'.$memberID;
			$followArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		    if(!empty($followArr)){
		    	$info = $this->select()->from($this->_name,array('AID'))->where("MemberID in (?)",$followArr)
		    	->where('Status = ?',1)->where('AID > ?',$lastArticleID)->limit(1)->query()->fetch();
		    }
		}
		return empty($info) ? 0 : 1;
	}
	
	/**
	 * 获取草稿箱文章数量
	 * @param unknown $type
	 */
	public function getDraftCount($memberID,$column)
	{
		$select= $this->select()->from($this->_name,'count(1) as num')->where('Status = ?',2)->where('MemberID = ?',$memberID);
		$select->where('ColumnID = ?',$column);
		$re = $select->query()->fetch();
		return $re['num'];
	}
	
	public function hasNewArticles($memberID)
	{
		$lastArticleTime = Model_Member::staticData($memberID,'lastNewArticleTime');
		$info = array();
		if($lastArticleTime !== false){
			$info = $this->select()->from($this->_name,array('AID'))
			->where('Status = ?',1)->where('UNIX_TIMESTAMP(PublishTime) > ?',$lastArticleTime)->limit(1)->query()->fetch();
		}
		return empty($info) ? 0 : 1;
	}
	
	public function getArticleInfo($articleID)
	{
		$info = $this->select()->where('AID = ?',$articleID)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 获取理财号最新的文章
	 * @param unknown $columnID
	 */
	public function getNewArticle($columnID,$limit)
	{
		$info = $this->select()->from($this->_name,array('ContentType'=>new Zend_Db_Expr(1),'AID','MemberID','Title','Cover','PublishTime','Description'))->where('ColumnID = ?',$columnID)
		->where('Status = ?',1)->order('AID desc')->limit($limit)->query()->fetchAll();
		return $info ? $info : array();
	}
	
	/**
	 * 获取我订阅的理财号有没有产生新的文章
	 */
	public function getMessageArticle($columnArr,$lastTime)
	{
		$info = $this->select()->from($this->_name,array('AID'))->where("ColumnID in (?)",$columnArr)
		->where('Status = ?',1)->where('UNIX_TIMESTAMP(PublishTime) > ?',$lastTime)->limit(1)->query()->fetch();
		return $info ? $info : array();
	}
	
	/**
	 * 定时发布文章
	 */
	public function publishArticle()
	{
		$articleList = $this->select()->where('Status = ?',2)->where('IsTimedPublish = ?',1)
		->where('UNIX_TIMESTAMP(PublishTime) <= ?',time())->order('PublishTime asc')->query()->fetchAll();
		if(!empty($articleList)){
			$db = $this->getAdapter();
			$focusModel = new Model_Column_ArticleFocus();
			foreach($articleList as $val){
				$db->beginTransaction();
				$paramArr = array(
					'MemberID'=>$val['MemberID'],
					'ColumnID'=>$val['ColumnID'],
					'Title'=>$val['Title'],
					'Author'=>$val['Author'],
					'Cover'=>$val['Cover'],
					'Content'=>$val['Content'],
					'Type'=>$val['Type'],
					'Status'=>1,
					'CreateTime'=>$val['CreateTime'],
					'PublishTime'=>$val['PublishTime'],
					'QrcodeUrl'=>$val['QrcodeUrl'],
					'ArticleLink'=>$val['ArticleLink'],
					'Description'=>$val['Description'],
					'IsCharge'=>$val['IsCharge'],
					'Cost'=>$val['Cost'],
					'IsTimedPublish'=>0
				);
				$result = $this->delete(array('AID = ?'=>$val['AID']));
				if(!$result){
					$db->rollBack();
					continue ;
				}
				$insertID = $this->insert($paramArr);
				if(!$insertID){
					$db->rollBack();
					continue ;
				}
				$re = $focusModel->update(array('ArticleID'=>$insertID), array('ArticleID = ?'=>$val['AID']));
				if(!$re){
					$db->rollBack();
					continue ;
				}
				$db->commit();
			}
		}
	}
}
