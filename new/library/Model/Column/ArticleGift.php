<?php
/**
 * 文章打赏相关
* @author Jeff
*
*/
class Model_Column_ArticleGift extends Zend_Db_Table
{
	protected $_name = 'column_article_gifts';
	protected $_primary = 'RecodeID';


	/**
	 *  获取文章礼物统计列表
	 * @param int $viewID
	 */
	public function getStaticGifts($articleID,$type=1)
	{
		$select = $this->select()->setIntegrityCheck(false)->from('column_article_gifts as a',array('SUM(Amount) as TotalAmount','SUM(GiftNum) as TotalNum'))
		->joinLeft('gifts as b', 'a.GiftID = b.GiftID',array('GiftID','GiftName','Type','Cover','Unit'))->where('a.ArticleID = ?',$articleID)->where('a.Type = ?',$type)
		->group('a.GiftID')->order(array('b.Type asc','TotalNum desc'));
		$info = $select->query()->fetchAll();
		return empty($info)?array():$info;
	}

	/**
	 * 获取某个文章收到的礼物
	 * @param unknown $lastID
	 * @param unknown $pagesize
	 */
	public function getArticleGifts($articleID,$lastID,$pagesize,$type=1){
		$select = $this->select()->setIntegrityCheck(false)->from('column_article_gifts as a',array('RecodeID','Amount','GiftNum','GiftMemberID','CreateTime'))
		->joinLeft('gifts as b', 'a.GiftID = b.GiftID',array('GiftID','GiftName','Type','Cover','Unit'))->where('a.ArticleID = ?',$articleID)->where('a.Type = ?',$type);
		if($lastID){
			$select = $select->where('RecodeID < ?',$lastID);
		}
		$info = $select->order('RecodeID desc')->limit($pagesize)->query()->fetchAll();
		return empty($info)?array():$info;
	}
	
	/**
	 * 获取送取金额最多的前5个人
	 * @param unknown $viewID
	 * @param unknown $limit
	 */
	public function getExpensiveGift($articleID,$limit){
		$select = $this->select()->from($this->_name,array('SUM(Amount) as TotalAmount','GiftMemberID'))
		->where('ArticleID = ?',$articleID)->group('GiftMemberID')->where('Type = ?',1)->order('TotalAmount desc')->order('RecodeID desc')->limit($limit);
		$info = $select->query()->fetchAll();
		return empty($info)?array():$info;
	}
	
	/**
	 * 获取某个人是否付费阅读
	 */
	public function isPay($articleID,$memberID)
	{
		$info = $this->select()->from($this->_name,array('RecodeID'))->where('ArticleID = ?',$articleID)->where('GiftMemberID = ?',$memberID)
		->where('Type = ?',2)->query()->fetch();
		return empty($info)?array():$info;
	}
	
	/**
	 * 获取某个文章的打赏人数
	 * @param unknown $articleID
	 */
	public function getSendGiftNum($articleID)
	{
		$info = $this->select()->from($this->_name ,array('count(distinct GiftMemberID) as num'))->where('ArticleID = ?',$articleID)->where('Type = ?',1)
		->query()->fetch();
		return $info['num'];
	}
	
	/**
	 * 获取某个文章的打赏次数
	 * @param unknown $articleID
	 */
	public function getGiftNum($articleID)
	{
		$num = $this->select()->from($this->_name,array('count(1) as num'))->where('ArticleID = ?',$articleID)->where('Type = ?',1)->query()->fetch();
		return $num['num'];
	}
}