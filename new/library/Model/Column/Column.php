<?php
/**
 * 专栏
 *
 * @author Jeff
 *
 */
class Model_Column_Column extends Model_Common_Common
{
	protected $_name = 'column';
	protected $_primary = 'ColumnID';

	/**
	 *  获取信息
	 * @param int $memberID
	 */
	public function getColumnInfo($columnID,$status=NUll)
	{
		$select = $this->select()->where('ColumnID = ?',$columnID);
		if(!empty($status)){
			$select = $select->where('CheckStatus = ?',$status);
		}
		$info = $select->query()->fetch();
		return $info ? $info : array();
	}

	/**
	 *  获取信息
	 * @param int $memberID
	 */
	public function getMyColumnInfo($memberID,$status=0,$field = null)
	{
		$select = $this->select();
		if(is_null($field)){
			$select->from($this->_name);
		}else{
			$select->from($this->_name, is_string($field) ? explode(",", $field) : $field);
		}
		$select->where('MemberID = ?',$memberID);
		if($status){
			$select->where('CheckStatus = ?',1);
		}
		$info = $select->limit(1)->query()->fetch();
		return $info ? $info : array();
	}

	/**
	 * 理财号
	 */
	public function hasTitle($title,$columnID = 0)
	{
		$select = $this->select()->where('Title = ?',$title);
		if($columnID){
			$select->where('ColumnID != ?',$columnID);
		}
		$info = $select->limit(1)->query()->fetch();
		return $info ? $info : array();
	}

	/**
	 * 增加订阅数
	 * @param unknown $column
	 * @param number $increment
	 * @return number
	 */
	public function increaseSubscribeNum($columnID,$increment = 1)
	{
		return $this->update(array('SubscribeNum'=>new Zend_Db_Expr("SubscribeNum + ".$increment)),array('ColumnID = ?'=>$columnID));
	}

	/**
	 * 获取我订阅的专栏
	 * @param unknown $memberID
	 */
	public function getSubscribeColumns($memberID,$lastTime,$pageSize,$crrentMemberID)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
		$requestObj = DM_Controller_Front::getInstance()->getHttpRequest();
		$version = $requestObj->getParam('currentVersion','1.0.0');
		if(version_compare($version,'2.4.3',"<")){
			$columnArr = $redisObj->zRevRangeByScore($cacheKey, '('.$lastTime,'-inf',array('limit' => array(0,$pageSize)));
		}else{
			$columnArr = $redisObj->zRevRangeByScore($cacheKey, '+inf','-inf');
		}
// 		if(empty($columnArr)){
// 			$subscribeModel = new Model_Column_MemberSubscribe();
// 			$columnArr = $subscribeModel->getColumnArr($memberID);
// 		}
		$result = array();
		if(!empty($columnArr)){
			$lastID=end($columnArr);
			$lastTime = $redisObj->zscore($cacheKey,$lastID);
			$select = $this->select();
			$select->from($this->_name,array('ColumnID','Title','Avatar','ArticleNum','SubscribeNum','Description'))->where('ColumnID in (?)',$columnArr)->where('CheckStatus = ?',1);
			$select->order(new Zend_Db_Expr("field(ColumnID,".implode(',',$columnArr).")"));
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				foreach($result as &$val){
					$val['IsSubscribe'] = 1;
					if($memberID!=$crrentMemberID){
						$val['IsSubscribe'] = $this->isSubscribeColumn($crrentMemberID, $val['ColumnID']);
					}
				}
			}
		}
		return array('lastTime'=>$lastTime,'Rows'=>$result);

	}

	/**
	 *  是否已订阅
	 * @param int $memberID
	 * @param int $topicID
	 */
	public function isSubscribeColumn($memberID,$column)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
		$score = $redisObj->zscore($cacheKey,$column);
		return $score ? 1 : 0;
	}

	/**
	 * 获取总的专栏数
	 */
	public function getColumnCount(){
		$select= $this->select()->from($this->_name,'count(1) as num')->where('CheckStatus = ?',1);
		$re = $select->query()->fetch();
		return $re['num'];
	}

	/**
	 * 获取最新专栏
	 */
	public function getNewestColumn()
	{
		$select = $this->select();
		$select->from($this->_name,array('ColumnID','Title','Avatar','SubscribeNum','ArticleNum','Description'))->where('CheckStatus = ?',1)->limit(50);
		$select->order("CheckTime desc");
		$result = $select->query()->fetchAll();
		return !empty($result) ? $result : array();
	}

	/**
	 * 获取最热专栏
	 */
	public function getHotColumn($isGetInfo = true)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = 'HotColumn';
		$columnIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
		if($isGetInfo == true){
			$result = array();
			if(!empty($columnIDArr)){
				$select = $this->select();
				$select->from($this->_name,array('ColumnID','Title','Avatar','SubscribeNum','ArticleNum','Description'))->where('ColumnID in (?)',$columnIDArr)->where('CheckStatus = ?',1);
				$select->order(new Zend_Db_Expr("field(ColumnID,".implode(',',$columnIDArr).")"));
				$result = $select->query()->fetchAll();
			}
			return $result;
		}
		return $columnIDArr;
	}

	/**
	 * 增加阅读数
	 * @param unknown $activityID
	 * @param number $increment
	 * @return number
	 */
	public function increaseReadNum($columnID,$increment=1)
	{
		return $this->update(array('ReadNum'=>new Zend_Db_Expr("ReadNum + ".$increment)),array('ColumnID = ?'=>$columnID));
	}

	/**
	 *增加文章数
	 */
	public function increaseArticleNum($columnID,$increment=1)
	{
		return $this->update(array('ArticleNum'=>new Zend_Db_Expr("ArticleNum + ".$increment)),array('ColumnID = ?'=>$columnID));
	}

	/**
	 * 获取理财号是否有红点
	 */
	public function getColumnNews($memberID)
	{
		$lastTime = Model_Member::staticData($memberID,'lastColumnNewsTime');
		if(empty($lastTime)){
			$lastTime = 0;
		}
		$redisObj = DM_Module_Redis::getInstance();
		$cacheKey = Model_Column_MemberSubscribe::getUserSubscribeKey($memberID);
		$columnArr = $redisObj->zRevRangeByScore($cacheKey, '+inf','-inf');
		$articleModel = new Model_Column_Article();
		$activityModel = New Model_Column_Activity();
		$info = array();
		$newsTitle = '';
		$publishTime = '';
		$columnID = 0;
		if(!empty($columnArr)){
			$info = $articleModel->getMessageArticle($columnArr,$lastTime);
			if(empty($info)){
				$info = $activityModel->getMessageActivity($columnArr,$lastTime);
			}
			foreach($columnArr as $k=>$columnID){
				$redisInfo = self::staticData($columnID);
				if(empty($redisInfo)){
					continue;
				}
				$arr[$k] = $redisInfo;
				$time[$k] = $redisInfo['publishTime'];
			}
			if(!empty($arr)){
				array_multisort($time,SORT_NUMERIC,SORT_ASC,$arr);
				$result = end($arr);
				if(!empty($result)){
					if($result['type']==1){
						$titleInfo= $articleModel->getArticleInfo($result['AID']);
					}else{
						$titleInfo = $activityModel->getActvityInfo($result['AID']);
					}
					$newsTitle = $titleInfo['Title'];
					$publishTime = date('Y-m-d H:i:s',$result['publishTime']);
					$columnID = $titleInfo['ColumnID'];
				}
			}
		}
		$isShowPoint = empty($info)?0:1;
		return array('isShowPoint'=>$isShowPoint,'contentTitle'=>$newsTitle,'publishTime'=>$publishTime,'columnID'=>$columnID);
	}

	/**
	 * 专栏统计数据  获取或设置
	 */
	public static function staticData($columnID,$field = null,$val = null)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$statisticKey = 'Statistic:Column'.$columnID;
		$result = null;
		if(!is_null($field)){
			if(is_array($field)){
				if($val == -1){
					$result = $redisObj->hmset($statisticKey,$field);
				}else{
					$result = $redisObj->hmget($statisticKey,$field);
				}
			}else{
				if(is_null($val)){
					$result = $redisObj->hget($statisticKey,$field);
				}else{
					$result = $redisObj->hset($statisticKey,$field,$val);
				}
			}
		}else{
			$result = $redisObj->hgetall($statisticKey);
		}
		return $result;
	}

	/**
	 * @param unknown $columnID
	 * @param string $field
	 */
	public function getColumnInfoCache($columnID,$field = null)
	{
		$redisObj = DM_Module_Redis::getInstance();
		$key = 'columnInfo:columnID'.$columnID;
		if(!is_null($field)){
			if(is_array($field)){
				$data = $redisObj->hmget($key,$field);
			}else{
				$data = $redisObj->hget($key,$field);
			}
		}else{
			$data = $redisObj->HGETALL($key);
		}
		if(empty($data)){
			$info = $this->getColumnInfo($columnID);
			$data = array('Title'=>$info['Title'],'Avatar'=>$info['Avatar'],'Description'=>$info['Description']);
			$redisObj->hmset($key,$data);
			if(!is_null($field)){
				$data = $data[$field];
			}
		}
		return $data;
	}
}
