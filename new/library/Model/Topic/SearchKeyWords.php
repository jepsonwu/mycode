<?php
/**
 * 搜索关键字
 * @author Mark
 */
class Model_Topic_SearchKeyWords extends Zend_Db_Table
{
	protected $_name = 'search_keywords';
	protected $_primary = 'KeyWordsID';
	
	
	/**
	 * 更新关键字信息
	 * @param int $topicID
	 * @param string $keyWords
	 */
	public function recordKeyWords($searchType,$keyWords)
	{
		$info = $this->getKeyWordsInfo($searchType, $keyWords);
		if(empty($info)){
			$ret = $this->insert(array('SearchType'=>$searchType,'KeyWords'=>$keyWords));
		}else{
			$ret = $this->update(array('SearchCounts'=>new Zend_Db_Expr(' SearchCounts + 1 ')),array('KeyWordsID = ?'=>$info['KeyWordsID']));
		}
		return $ret;
	}
	
	/**
	 * 获取关键字信息
	 * @param int $topicID
	 * @param string $keyWords
	 */
	public function getKeyWordsInfo($searchType,$keyWords)
	{
		$info = $this->select()->where('SearchType = ?',$searchType)->where('KeyWords = ?',$keyWords)->query()->fetch();
		return $info ? $info : array();
	}
}