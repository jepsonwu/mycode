<?php
/**
 *  特殊说说相关
 * @author Jeff
 *
 */
class Model_shuoRelation
{
	/**
	 * 获取特殊说说相关信息
	 */
	public function getRelationInfo($contentType,$relationID)
	{
		$info = array('relationContent'=>'','relationImage'=>'');
		if($contentType == 2){
			$viewModel = new Model_Topic_View();
			$viewImageModel = new Model_Topic_ViewImage();
			$result = $viewModel->getViewInfo($relationID);
			if(!empty($result)){
				$info['relationContent'] = $result['ViewContent'];
				$image = $viewImageModel->getImages($relationID);
				if(!empty($image)){
					$info['relationImage'] = $image[0]['Uri'];
				}else{
					$topicModel = new Model_Topic_Topic();
					$topicInfo = $topicModel->getTopicInfo($result['TopicID']);
					$info['relationImage'] = $topicInfo['BackImage'];
				}
			}
		}
		return $info;
	}

}