<?php
/**
 *  文章标签
 *
 * @author Jeff
 *
 */
class Model_Column_ArticleFocus extends Zend_Db_Table
{
	protected $_name = 'column_article_focus';
	protected $_primary = 'ArticleFocusID';

	public function addFocus($articleID,$focusID)
	{
		$hasExists = $this->getInfo($articleID,$focusID);
		if(empty($hasExists)){
			$data = array('ArticleID'=>$articleID,'FocusID'=>$focusID);
			$newFocusID = $this->insert($data);
		}
		return true;
	}

	/**
	 *  获取信息
	 * @param int $topicID
	 * @param int $focusID
	 */
	public function getInfo($articleID,$focusID=null)
	{
		$select = $this->select();
		$select->from($this->_name,array('FocusID'))->where('ArticleID = ?',$articleID);
		if(!is_null($focusID)){
			$select->where('FocusID = ?',$focusID);
			return $select->query()->fetch();
		}
		return $select->query()->fetchAll();
	}
	
	public function getRelationArticles($focusIDArr)
	{
        if(empty($focusIDArr)){
            return array();
        }
		$select = $this->select()->setIntegrityCheck(false);
		$select->from('column_article_focus as a',array('ArticleID'=>new Zend_Db_Expr("DISTINCT(a.ArticleID)")))
		->joinInner('column_article as b', 'a.ArticleID = b.AID','Title')->where('a.FocusID in (?)',$focusIDArr)->where('b.Status = ?',1)
		->order('b.AID desc')->limit(5);
		$results = $select->query()->fetchAll();
		return empty($results)?array():$results;
	}
}
