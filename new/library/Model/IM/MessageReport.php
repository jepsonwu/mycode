<?php

/**
 * 消息举报功能
 * 
 * @author johnny 
 */

class Model_IM_MessageReport extends Zend_Db_Table {
    protected $_name = 'message_report';
    protected $_primary = 'ReportID';

    /**
     * 
     * @param int $memberID 被举报者ID
     * @param int $byMemberID 举报者ID
     * @param string $content 被举报的消息内容
     * @param string $reason 举报的原因
     * @return boolean
     */
    public function Report2DB($memberID = 0, $byMemberID = null, $content = '', $reason = '',$infoType = 1,$infoID = 0,$reasonDetail = '',$imageUrl= '')
    {
//         if (!$memberID = (int) $memberID) {
//             throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noMemberID'));
//         } 
        $modelAccountMembers = new DM_Model_Account_Members();

//         if (null == $modelAccountMembers->getById($memberID)) {
//             throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noMember'));
//         } 
        if (!$byMemberID) {
            throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noByMemberID'));
        } 
        if (null == $modelAccountMembers->getById(intval($byMemberID))) {
            throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noByMember'));
        } 
        if (trim($content) == '') {
            //throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noContent'));
        } 
        if (trim($reason) == '') {
            throw new Exception(DM_Controller_Front :: getInstance()->getLang()->_('api.system.msg.report.noReason'));
        } 

        if ($this->insert(array('Content' => $content,
                        'Reason' => $reason,
                        'MemberID' => (int) $memberID,
                        'ByMemberID' => (int) $byMemberID,
                        'Status' => 0,
                        'CreateTime' => date('Y-m-d H:i:s'),
                        'UpdateTime' => '0000-00-00 00:00:00',
        				'InfoType'=>$infoType,
        				'InfoID'=>$infoID,
        				'ReasonDetail'=>$reasonDetail,
        				'ImageUrl'=>$imageUrl
                        )
                    )) {
            if($infoType == 2){
                $viewModel = new Model_Topic_View();
                $viewModel->update(array('ReportNum' => new Zend_Db_Expr('ReportNum + 1')),array('ViewID = ?'=>$infoID));
            }
            
            return true;
        } else {
            return false;
        } 
    } 

    /**
     * 根据举报类型获取举报列表
     * @param int $infoID 被举报信息ID
     * @param int $infoType 举报类型
     */
    public function getReportListByType($infoID,$intoType)
    {
        $select = $this->select()->setIntegrityCheck(false);
        $fields = array('ByMemberID','CreateTime','Reason');
        $select->from($this->_name.' as mr',$fields)->where('mr.InfoID = ?',$infoID)->where('mr.InfoType = ?',$intoType);
        $udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
        $select->joinLeft($udb.'.members as m', 'm.MemberID = mr.ByMemberID','UserName');
        $res = $select->order('mr.CreateTime desc')->query()->fetchAll();
        return !empty($res) ? $res :array();
    }
} 
