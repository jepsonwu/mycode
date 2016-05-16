<?php
/**
 *  关注点
 * @author Mark
 *
 */
class Api_FocusController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 关注点列表
	 */
	public function listAction()
	{
		try{
			$focusType = intval($this->_request->getParam('focusType',0));
			if($focusType <= 0 || $focusType > 5){
				throw new Exception('类型参数错误');
			}
			
			
			$focusModel = new Model_Focus();
			$fieldsArr = array('FocusID','FocusName','FocusImg');
			$select = $focusModel->select()->from('focus',$fieldsArr);
			switch ($focusType){
				case 1:
					$select->where('IsBeforeRegisterFocus = 1');
					break;
				case 2:
					$select->where('IsRegistedFocus = 1');
					break;
				case 3:
					$select->where('IsGroupFocus = 1');
					break;
				case 4:
					$select->where('IsTopicFocus = 1');
					break;
				case 5:
					break;
			}
			
			$result = $select->query()->fetchAll();
			$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 保存关注点
	 */
	public function saveAction()
	{
		try{
			$memberFocusModel = new Model_MemberFocus();
			$memberModel = new DM_Model_Account_Members();
			$userName = trim($this->_request->getParam('userName',''));
			$avatar = trim($this->_request->getParam('avatar',''));

			$memberInfo = $memberModel->getById($this->memberInfo->MemberID);
			if(empty($memberInfo['UserName'])){
				
				if(empty($userName)){
					throw new Exception('用户名不能为空');
				}
				
				if(!DM_Helper_Validator::checkUsername($userName)){
					throw new Exception($this->getLang()->_("api.user.msg.username.format"));
				}
	
	            $sensitiveModel = new DM_Model_Account_Sensitive();
	            $sensitive = $sensitiveModel->getInfo($userName);

	            $filter = $sensitiveModel->filter($userName);
	            if (!empty($sensitive) || $filter ==1) {
	                $this->returnJson(parent::STATUS_FAILURE,'请不要使用敏感词汇注册！');
	            }
	            
				$infoTmp = $memberModel->getByUsername($userName);
				if(!empty($infoTmp) && $infoTmp['MemberID'] != $this->memberInfo->MemberID){
					throw new Exception('该用户名已被注册！');
				}
				$memberModel->updateInfo($this->memberInfo->MemberID,array('UserName'=>$userName));
			}
			
			if(!empty($avatar)){
				$memberModel->updateInfo($this->memberInfo->MemberID,array('Avatar'=>$avatar));
			}
			
			$memberModel->deleteCache($this->memberInfo->MemberID);
			
			$focusID = trim($this->_request->getParam('focusID',''));
			$focusIDArr = explode(',',$focusID);
			
			$focusModel = new Model_Focus();
			$topicModel = new Model_Topic_Topic();
			$topicFollowModel = new Model_Topic_Follow;
			if(!empty($focusID) && !empty($focusIDArr) && count($focusIDArr) >= 1){
				foreach($focusIDArr as $fID){
 					$memberFocusModel->addFocus($this->memberInfo->MemberID, $fID);
 					//关注和关注点同名的话题
 					$focusInfo = $focusModel->getInfo($fID);
					if(!empty($focusInfo)){
						$name = $focusInfo['FocusName'];
						$topicInfo = $topicModel->hasExist($name,1);
						if(!empty($topicInfo)){
							$topicFollowModel->addFollow($topicInfo['TopicID'],$this->memberInfo->MemberID);
						}
					}
				}
			}else{
				throw new Exception('请选择关注点');
			}
			
			$this->returnJson(parent::STATUS_OK,'');
			
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
}