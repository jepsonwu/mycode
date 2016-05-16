<?php
/**
 * 
 * @author Mark
 *
 */
class Model_Member extends Zend_Db_Table
{
    protected $_name = 'members';
    protected $_primary = 'MemberID';
   
    
    /**
     * 初始化数据库
     */
    public function __construct()
    {
        $udb = DM_Controller_Front::getInstance()->getDb('udb');
        $this->_setAdapter($udb);
    }
    
    /**
     *   检测手机格式
     * @param string $mobile
     */
    public static function checkMobileFormat($mobile)
    {
    	return preg_match('/^1[\d]{10}$/', $mobile);
    }

    /*
     *获取最热达人
     */
    public function getHotMasterList($isGetInfo = true)
    {
        $redisObj = DM_Module_Redis::getInstance();
        $cacheKey = 'HotMember';
        $memberIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
        if($isGetInfo == true){
            $result = array();
            if(!empty($memberIDArr)){
                $select = $this->select();
                $select->from($this->_name,array('MemberID','UserName','Avatar'))->where('MemberID in (?)',$memberIDArr)->where('Status = ?',1);
                $select->order(new Zend_Db_Expr("field(MemberID,".implode(',',$memberIDArr).")"));
                $result = $select->query()->fetchAll();
            }
            return $result;
        }
        return $memberIDArr;
    }

    /*
     *获取最新达人
     */
    public function getRecentMasterList($isGetInfo = true)
    {   
        $redisObj = DM_Module_Redis::getInstance();
        $cacheKey = 'RecentMember';
        $memberIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
        if($isGetInfo == true){
            $result = array();
            if(!empty($memberIDArr)){
                $select = $this->select();
                $select->from($this->_name,array('MemberID','UserName','Avatar'))->where('MemberID in (?)',$memberIDArr)->where('Status = ?',1);
                $select->order(new Zend_Db_Expr("field(MemberID,".implode(',',$memberIDArr).")"));
                $result = $select->query()->fetchAll();
            }
            return $result;
        }
        return $memberIDArr;
    }

    /*
     *获取理财师
     */
    public function getFinancialPlannerList($isGetInfo = true)
    {   
        $redisObj = DM_Module_Redis::getInstance();
        $cacheKey = 'FinancialPlanner';
        $memberIDArr = $redisObj->zRevRangeByScore($cacheKey,'+inf','-inf');
        if($isGetInfo == true){
            $result = array();
            if(!empty($memberIDArr)){
                $select = $this->select()->setIntegrityCheck(false);;
                $select->from($this->_name.' as m',array('MemberID','UserName','Avatar'))->where('m.MemberID in (?)',$memberIDArr)->where('m.Status = ?',1);
                $db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->db->dbname;
                $select->joinLeft($db.'.member_authenticate as ma','ma.MemberID = m.MemberID',array('AuthenticateID'))->where('ma.AuthenticateType = ?',2)->where('ma.Status= ?',1);

                $select->order(new Zend_Db_Expr("field(m.MemberID,".implode(',',$memberIDArr).")"));
                $result = $select->query()->fetchAll();
            }
            return $result;
        }
        return $memberIDArr;
    }
    
    /**
     * 会员统计数据  获取或设置
     */
    public static function staticData($memberID,$field = null,$val = null)
    {
    	return DM_Model_Account_Members::staticData($memberID,$field,$val);
    }
    
    /**
     * 统一处理update
     */
    public function update(array $data,$where)
    {
    	$memberModel = new DM_Model_Account_Members();
    	return $memberModel->update($data, $where);
    }

    public function getAllMembers()
    {
        return $this->select()->from($this->_name,array('MemberID'))->where('Status = ?',1)->query()->fetchAll();
    }
    
    /**
     * 获取名人排行榜
     */
    public function getFamousPerson($currentMemberID)
    {
    	$redisObj = DM_Module_Redis::getInstance();
    	//去达人和理财师的合集（两者不能共存 所有不存在重复）
        $redisObj->zunion('FamousPerson',array('FinancialPlanner','HotMember'));
        $memberIDArr = $redisObj->zRevRangeByScore('FamousPerson','+inf','-inf',array('limit' => array(0,50)));
        $result =array();
        if(!empty($memberIDArr)){
        	$authenticateModel =new Model_Authenticate();
        	$memberFollowModel = new Model_MemberFollow();
        	$memberNoteModel = new Model_MemberNotes();
        	$qualificationModel = new Model_Qualification();
        	$bestModel = new Model_Best_Best();
        	$memberModel = new DM_Model_Account_Members();
        	foreach($memberIDArr as $k=>$memberID){
        		//获取是否是理财师
        		$authenticateInfo = $authenticateModel->getInfoByMemberID($memberID,1,'AuthenticateID,AuthenticateType');
        		if(!empty($authenticateInfo) && $authenticateInfo['AuthenticateType'] == 2){//理财师
        			$result[$k]['Type'] = 1;
        			$qualificationInfo = $qualificationModel->getDisplayQualification($authenticateInfo['AuthenticateID']);
        			if(empty($qualificationInfo)){
        				$qualificationInfo = $qualificationModel->getInfoByqualificationID($authenticateInfo['AuthenticateID'],1,1,'FinancialQualificationID desc','FinancialQualificationID,FinancialQualificationType');
        			}
        			$result[$k]['Qualification'] = !empty($qualificationInfo)? array($qualificationInfo) : array();
        		}else{
        			$result[$k]['Type'] = 2;
        			$bestInfo = $bestModel->getBestInfoByMemberID(array($memberID), array(2,3));
        			$bestTitleArr = array();
        			if(!empty($bestInfo)){
        				$bestTitleArr = $bestInfo[$memberID];
        			}
        			$result[$k]['BestTitle'] = !empty($bestTitleArr)?$bestTitleArr:array();
        		}
        		$result[$k]['MemberID'] = $memberID;
        		$result[$k]['UserName'] = $memberModel->getMemberInfoCache($memberID,'UserName');
        		$result[$k]['RelationCode'] = $memberFollowModel->getRelation($memberID,$currentMemberID);
        		$result[$k]['NoteName'] = $memberNoteModel->getNoteName($currentMemberID, $memberID);
        		$result[$k]['Avatar'] = $memberModel->getMemberAvatar($memberID);
        		
        	}
        }
        return $result;
    }
}