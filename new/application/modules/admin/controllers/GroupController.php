<?php
/**
 * 群组管理
 */

class Admin_GroupController extends DM_Controller_Admin {

	public function indexAction() {

	}

	public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $pageIndex = (int) $this->_getParam('page', 1);
        $pageSize = (int) $this->_getParam('rows', 50);

        $where = 'true';
        $model = new Model_IM_Group();
        if( $keyWords = trim($this->_request->getParam('keyWords')) ) {
            if( $searchType = trim($this->_request->getParam('searchType')) ) {
                switch ($searchType) {
                    case '群组ID':
                        $where .= ' AND group.AID='.intval($keyWords);
                        break;
                                    
                    case '群号':
                        $where .= ' AND group.GroupID like '.$this->getDb()->quote('%'.$keyWords.'%');
                        break;

                    case '群组名称':
                        $where .= ' AND group.GroupName like '.$this->getDb()->quote('%'.$keyWords.'%');
                        break;

                    case '群主':
                        $where .= ' AND group.OwnerID='.intval($keyWords);
                        break;

                    case '群组所在地':
                        $where .= ' AND concat(group.City, group.Province) like '.$this->getDb()->quote('%'.$keyWords.'%');
                        break; 

                  case '群标签':
                        $where .= ' AND focus.FocusName like '.$this->getDb()->quote('%'.$keyWords.'%');
                        break;

                  case '群介绍':
                        $where .= ' AND group.Description like '.$this->getDb()->quote('%'.$keyWords.'%');
                        break;
                    
                    default:
                        break;
                }
            }
        }
        if( $start_date = trim($this->_getParam('start_date', '')) ) {
            $where .= ' AND UNIX_TIMESTAMP(group.CreateTime) >= '.strtotime($start_date);
        }
        if( $end_date = trim($this->_getParam('end_date', '')) ) {
        	$where .= ' AND UNIX_TIMESTAMP(group.CreateTime) <= '.strtotime($end_date);
        }
        if( ($isOpen = trim($this->_request->getParam('isOpen'))) != '' ) {
        	$where .= ' AND group.IsPublic = '.intval($isOpen);
        }
        $data = $model->getGroups($where, null, $pageSize, ($pageIndex - 1) * $pageSize);
        $total = $model->getGroups($where, null, null, null, true);
        $this->_helper->json(array('rows'=>$data ? $data : array(), 'total'=>$total));
	}

    /**
     * 暂时不添加锁定操作
     * @return [type] [description]
     */
	public function lockAction() {
        $this->_helper->viewRenderer->setNoRender();
        if( !$id = (int) $this->_request->getParam('id') ) {
            $this->returnJson(parent::STATUS_FAILURE, 'ID不能为空');
        }
        $model = new Model_IM_Group();
	}

    /**
     * 暂时不添加解锁操作
     * @return [type] [description]
     */
	public function unlockAction() {
        $this->_helper->viewRenderer->setNoRender();
        if( !$id = (int) $this->_request->getParam('id') ) {
            $this->returnJson(parent::STATUS_FAILURE, 'ID不能为空');
        }
	}

	public function membersAction() {
        if( $GroupID = @number_format($this->_request->getParam('id'), 0, '', '') ) {
            $model = new Model_IM_Group();
            if( !$model->getInfo($GroupID) ) {
                $this->view->error = '群组不存在';
                return;
            }
            $this->view->groupID = $GroupID;
            $this->view->pageIndex = $pageIndex = (int) $this->_request->getParam('pageIndex', 1);
            $this->view->pageSize = $pageSize = (int) $this->_request->getParam('pageSize', 20);
            $modelIMGroupMember = new Model_IM_GroupMember();
            $this->view->total = $modelIMGroupMember->select()->setIntegrityCheck(false)->from('group_member', array('total'=>'count(*)'))->where('GroupID='.$GroupID)->query()->fetch();
            $this->view->total = current($this->view->total);
            $this->view->members = $modelIMGroupMember->fetchAll('GroupID='.$GroupID, 'AID DESC', $pageSize, ($pageIndex - 1) * $pageSize)->toArray();
        } else {
            $this->view->error = 'ID不能为空';
        }
	}

    public function memberInfoAction() {
        if( $memberID = (int) $this->_request->getParam('id') ) {
            $model = new DM_Model_Account_Members();
            if( !$info = $model->getById($memberID)->toArray() ) {
                $this->view->error = '会员不存在';
                return;
            }
            $this->view->memberInfo = $info;
        } else {
            $this->view->error = 'ID不能为空';
        }
    }

	public function announcementAction() {
        if( $GroupID = @number_format($this->_request->getParam('id'), 0, '', '') ) {
            $model = new Model_IM_Group();
            if( !$model->getInfo($GroupID) ) {
                $this->view->error = '群组不存在';
                return;
            }
            $this->view->groupID = $GroupID;
            $this->view->pageIndex = $pageIndex = (int) $this->_request->getParam('pageIndex', 1);
            $this->view->pageSize = $pageSize = (int) $this->_request->getParam('pageSize', 20);
            $modelIMGroupAnnouncement = new Model_IM_GroupAnnouncement();
            $this->view->total = $modelIMGroupAnnouncement->select()->setIntegrityCheck(false)->from('group_announcement', array('total'=>'count(*)'))->where('GroupID='.$GroupID)->query()->fetch();
            $this->view->total = current($this->view->total);
            $this->view->announcements = $modelIMGroupAnnouncement->getAnnouncements('GroupID='.$GroupID, 'AnnouncementID DESC', $pageSize, ($pageIndex - 1) * $pageSize);
        } else {
            $this->view->error = 'ID不能为空';
        }
	}

    /**
     * 广告链接
     */
    public function adsLinkAction()
    {
        $group_id = $this->_getParam('group_id','');
        $h5Url = $this->_request->getScheme().'://'.$this->_request->getHttpHost()."/api/public/group-info/groupID/";
        $schemaUrl = "caizhu://caizhu/groupInfo?id=";
        
        $model = new Model_IM_Group();
        $groupInfo = $model->getInfo($group_id);   
        
        $this->view->h5Url=$h5Url.$groupInfo['AID'];
        $this->view->schemaUrl = $schemaUrl.$groupInfo['AID'];
    }
}