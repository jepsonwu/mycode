<?php
class Model_Topic_ViewImage extends Zend_Db_Table
{
	protected $_name = 'view_images';
	protected $_primary = 'ImageID';
	
	/**
	 *  添加图片
	 * @param int $viewID
	 * @param string $uri
	 * @param string $thumbUri
	 * @param string $sortIndex
	 */
	public function addImage($viewID,$uri,$sortIndex = 0)
	{
		$data = array(
				'ViewID' => $viewID,
				'Uri'=>$uri,
				'SortIndex'=>$sortIndex
		);
		return $this->insert($data);
	}
	
	/**
	 *  获取视图图片
	 * @param int $viewID
	 */
	public function getImages($viewID,$inner = false)
	{
		//$request=DM_Controller_Front::getInstance()->getHttpRequest();
		$select = $this->select();
		$images = $select->from($this->_name,array('Uri'))->where('ViewID = ?',$viewID)->query()->fetchAll();
		if(empty($images) && !$inner){
			$viewModel = new Model_Topic_View();
			$vInfo = $viewModel->getViewInfo($viewID);
			if(!empty($vInfo['ParentID'])){
				return $this->getImages($vInfo['ParentID'],true);
			}
		}
		return $images;
	}
}