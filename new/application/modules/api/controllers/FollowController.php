<?php
/**
 * 关注
 * @author Jeff
 *
 */
class Api_FollowController extends Action_Api
{
	public function init()
	{
		parent::init();
		$this->isLoginOutput();
	}
	
	/**
	 * 我的关注列表
	 */
	public function myFollowListAction()
	{
		try{
			$lastTime= $this->_getParam('lastTime', 0);
			$pageSize = intval($this->_getParam('pagesize', 10));
			$memberID = $this->memberInfo->MemberID;
			$model = new Model_MemberFollow();
			$results = $model->getFollowedMembers($memberID,$lastTime,$pageSize);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 搜索关注/粉丝
	 */
	public function searchMembersAction()
	{
		try{
			$keyWords = trim($this->_getParam('keyWords'),'');
			$memberID = $this->memberInfo->MemberID;
			$pageIndex= $this->_getParam('page', 1);
			$pagesize = intval($this->_getParam('pagesize',10));
			$searchType = intval($this->_getParam('searchType',0));
			if(!in_array($searchType, array(1,2))){//1关注，2粉丝
				throw new Exception('搜索类型参数错误！');
			}
			if(empty($keyWords)){
				throw new Exception('关键字不能为空！');
			}
			$model = new Model_MemberFollow();
			$select = $model->select()->setIntegrityCheck(false);
			$select->from('member_follow as mf',array('Content'));
			$udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
			if($searchType == 1){//搜关注
				$select->joinInner($udb.'.members as m', 'm.MemberID = mf.FollowedMemberID',array('MemberID','UserName','Avatar'));
				if(strlen($keyWords) == 11 && is_numeric($keyWords)){
					$select->where("m.MobileNumber = ?",$keyWords);
				}else{//先按财猪号搜索 搜不到再按备注名搜索
					$select->joinleft('member_notes as n', "n.MemberID = $memberID and n.OtherMemberID = mf.FollowedMemberID",array('NoteName'));
					$select->where("(m.UserName like ?  or n.NoteName like '%$keyWords%')",'%'.$keyWords.'%');
				}
				$select->where('mf.MemberID = ? ',$memberID);
			}else{//搜粉丝
				$select->joinInner($udb.'.members as m', 'm.MemberID = mf.MemberID',array('MemberID','UserName','Avatar'));
				$select->where('mf.FollowedMemberID = ? ',$memberID);
				if(strlen($keyWords) == 11 && is_numeric($keyWords)){
					$select->where("m.MobileNumber = ?",$keyWords);
				}else{
					$select->where("m.UserName like ?",'%'.$keyWords.'%');
				}
			}
// 			$countSql = $select->__toString();
// 			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(1) AS total FROM', $countSql);
// 			//总条数
// 			$total = $model->getAdapter()->fetchOne($countSql);
			$results = $select->order('mf.FollowID desc')->limitPage($pageIndex,$pagesize)->query()->fetchAll();
			if(!empty($results)){
				$viewModel = new Model_Topic_View();
				$shuoshuoModel = new Model_Shuoshuo();
				$memberNoteModel = new Model_MemberNotes();
				foreach ($results as $key=>&$val){
					$val['RelationCode'] = $model->getRelation($val['MemberID'], $memberID);
					if($val['RelationCode'] == 3){
						unset($results[$key]);
						continue;
					}
					if($searchType == 2){
						$val['ViewCount']  = $viewModel->getViewCount($val['MemberID']);
						$val['ShuoshuoCount'] = $shuoshuoModel->getShuosCount($val['MemberID']);
						$val['NoteName'] = '';
					}else{
						$val['ViewCount']  = 0;
						$val['ShuoshuoCount'] = 0;
						$val['NoteName'] = $memberNoteModel->getNoteName($memberID,$val['MemberID']);
					}
				}
			}
			$results = array_values($results);
			$total = count($results);
			$this->returnJson(parent::STATUS_OK,'',array('Total'=>$total,'Rows'=>$results));
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 我的粉丝列表
	 */
	public function myFansListAction()
	{
		try{
			$lastTime= $this->_getParam('lastTime', 0);
			$pageSize = intval($this->_getParam('pagesize', 10));
			$memberID = $this->memberInfo->MemberID;
			$model = new Model_MemberFollow();
			$results = $model->getFansList($memberID,$lastTime,$pageSize);
			$this->returnJson(parent::STATUS_OK,'',$results);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 关注
	 */
	public function followAction()
	{
		try{
			$memberID = intval($this->_getParam('memberID',0));
			$content = trim($this->_getParam('content',''));
			if($memberID<1){
				throw new Exception('参数无效!');
			}
			if($memberID != $this->memberInfo->MemberID){
				$model = new Model_MemberFollow();
				$model->addFollow($memberID, $this->memberInfo->MemberID,$content);
			}
			$this->returnJson(parent::STATUS_OK,'关注成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 取消关注
	 */
	public function unFollowAction()
	{
		try{
			$memberID = intval($this->_getParam('memberID',0));
			if($memberID<0){
				throw new Exception('参数无效!');
			}
			$model = new Model_MemberFollow();
			$model->unFollow($memberID, $this->memberInfo->MemberID);
			$this->returnJson(parent::STATUS_OK,'取消关注成功！');
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
	}
	
	/**
	 * 获取关注数，粉丝数
	 */
	public function getStatisticsAction()
	{
		try{
			$memberID = $this->memberInfo->MemberID;
			$model = new Model_MemberFollow();
			$result = $model->getStatistic($memberID);
			$this->returnJson(parent::STATUS_OK,'',$result);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
}