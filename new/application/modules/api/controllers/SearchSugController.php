<?php
/**
 * 搜索关键字
 *  
 * @author Mark
 */
class Api_SearchSugController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	
	// /**
	//  * 获取搜索推荐列表
	//  */
	// public function getSuggestAction()
	// {
	// 	try{
	// 		$keywords = trim($this->_request->getParam('keyWords',''));
	// 		if(empty($keywords)){
	// 			throw new Exception('关键字参数不能为空！');	
	// 		}
			
	// 		$searchType = $this->_request->getParam('searchType',0);
	// 		if(empty($topicID)){
	// 			throw new Exception('未选择指定话题！');
	// 		}
			
	// 		$searchKeyModel = new Model_Topic_SearchKeyWords();
			
	// 		$select = $searchKeyModel->select()->from('view_search_keywords',array('KeyWords'));
	// 		$select->where('SearchType = ?',$searchType)->where('KeyWords like ? ','%'.$keywords.'%')->order('SearchCounts desc')->limit(5);
	// 		$results = $select->query()->fetchAll();
	// 		$this->returnJson(parent::STATUS_OK,'',array('keyList'=>$results));			
	// 	}catch(Exception $e){
	// 		$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
	// 	}
	// }
	
	/**
	 * 热门搜索词列表
	 */
	public function hotSearchAction()
	{
		try{
			$searchType = $this->_request->getParam('searchType','');
			if(empty($searchType)){
				throw new Exception('未选择搜索类型！');
			}
			if(!in_array($searchType, array('member', 'group', 'topic', 'view','column','article','activity'))){
				throw new Exception('搜索类型错误！');
			}
			
			$searchKeyModel = new Model_Topic_SearchKeyWords();
				
			$select = $searchKeyModel->select()->from('search_keywords',array('KeyWords'));
			$select->where('SearchType = ?',$searchType)->order('SearchCounts desc')->limit(10);
			$results = $select->query()->fetchAll();
			$this->returnJson(parent::STATUS_OK,'',array('keyList'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());	
		}
	}
	
}