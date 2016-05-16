<?php

/**
 * 问财咨询服务模块
 * User: hale
 * Date: 16-3-11 17:30
 */
class Api_CounselController extends Action_Api
{
	public function init()
	{
        
		parent::init();
	}

	/**
	 * 获取市级列表
	 * type 1热门城市，2按照首字母
	 * first_letter 城市首字母，多个首字母时用英文逗号分开，如：A,B,C,D，空表示查询全部
	 */
	protected $getCityListConf = array(
		array("type", "1,2,3", "请选择查询类型！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "2"),
		array("first_letter", "/^([a-zA-Z],?)+$/", "首字母格式不正确！", DM_Helper_Filter::EXISTS_VALIDATE)
	);

	public function getCityListAction()
	{
		$regionModel = new Model_Region();
		if ($this->_param['type'] == 1)
			parent::succReturn($regionModel->hot_city);

		$where = array("Level =?" => $regionModel::REGION_CITY);
		isset($this->_param['first_letter']) &&
		$where['FirstLetter IN(?)'] = array_map("strtoupper", explode(",", trim($this->_param['first_letter'], ",")));
        if($this->_param['type'] == 2){
            $result = $regionModel->getInfoMix($where, array("Code", "Name","RealCity",'FirstLetter'), "All", "FirstLetter asc,Code ASC");
        }else{
            $counselModel = new Model_Counsel_Counsel();
            $select = $counselModel->select()->setIntegrityCheck(false);

			//desc 显示多少个字
			$select->from("counsel_support_region as a", array());
			//查询支持的城市名称
			$select->joinInner("counsel as b", "a.CID=b.CID", null);
			$select->joinLeft("region as c", "a.Code=c.Code", array("c.Code","c.Name","c.FirstLetter"));
            $select->where('c.Name IS NOT NULL');
            $select->group("a.Code");
            $select->order('c.FirstLetter asc');
            $select->order('a.Code asc');
            $result = $counselModel->fetchAll($select)->toArray();
            $this->escapeVar($result);
        }
		parent::succReturn(array('Rows'=>(is_null($result) ? array() : $result)));
	}

	/**
	 * 创建咨询活动
	 */
	protected $addConf = array(
		array("title", "1,50", "请输入有效的咨询主题!", DM_Helper_Filter::MUST_VALIDATE, "length"),
		array("duration", "/^[\d]+(\.[0,5])?$/", "请输入有效的咨询时长!", DM_Helper_Filter::MUST_VALIDATE),
		array("price", "/^([1-9]{1}\d*(.(\d){1,2})?)|(0.(\d){1,2})$/", "请输入有效的咨询费用!", DM_Helper_Filter::MUST_VALIDATE),
		array("city", "/^([\d]{6},?){1,3}$/", "请输入三个以内的城市!", DM_Helper_Filter::MUST_VALIDATE),//最多三个城市
		array("desc", "1,1000", "请输入有效的咨询详情!", DM_Helper_Filter::MUST_VALIDATE, "length"),
        array("formType", "1,2,3", "请输入有效的数据类型!", DM_Helper_Filter::EXISTS_VALIDATE, "in",1),
	);

	public function addAction()
	{
		$this->isLoginOutput();
		$counselModel = new Model_Counsel_Counsel();
        $supportModel = new Model_Counsel_CounselSupportRegion();
		$counselModel->getAdapter()->beginTransaction();
		try {
			$memberID = $this->memberInfo->MemberID;

			//理财师认证
			$this->isFinancial($memberID);
            
            $count = $counselModel->getInfoMix(array("DataType=?" => 1,'MemberID=?'=>$memberID), "count(1)");
            $isFirst = $count?0:1;
            
            if($this->_getParam('cid',0)>0){
                $counselModel->delete(array('CID=?'=>$this->_getParam('cid',0)));
                $supportModel->delete(array('CID=?'=>$this->_getParam('cid',0)));
            }
            
			//创建咨询主题
			$data = array(
				'MemberID' => $memberID,
				'Title' => $this->_param['title'],
				'Duration' => $this->_param['duration'],
				'Price' => $this->_param['price'],
				'Desc' => $this->_param['desc'],
				'Status' => $counselModel::COUNSEL_STATUS_TRUE,
                'DataType' => $this->_param['formType']
			);
			$cid = $counselModel->insert($data);
			if ($cid === false)
				throw new Exception("创建失败！");

			//添加支持城市
			$regionModel = new Model_Region();
			$this->_param['city'] = explode(",", trim($this->_param['city'], ","));
			$count = $regionModel->getInfoMix(array("Code IN(?)" => $this->_param['city']), "count(1)");
			if (is_null($count) || count($this->_param['city']) != $count)
				throw new Exception("不存在该城市！");

			//添加支持城市
			$data = array();
			foreach ($this->_param['city'] as $city) {
				$data[] = array(
					"CID" => $cid,
					"Code" => $city
				);
			}
			$result = $supportModel->insertMulti($data);
			if ($result === false)
				throw new Exception("创建失败！");

			//创建问财统计数据
			$stateModel = new Model_Counsel_CounselSellerState();
			$result = $stateModel->getInfoMix(array("MemberID =?" => $memberID), "count(1)");
			if ($result == 0) {
				$result = $stateModel->insert(array("MemberID" => $memberID));
				if ($result === false)
					throw new Exception("创建失败！");
			}

			$counselModel->getAdapter()->commit();
			$info = $counselModel->isValidCounsel($cid,'*');
			$counselModel->newCouselInfo($info);
			parent::succReturn(array("cid" => $cid,'isFirst'=>$isFirst));
		} catch (Exception $e) {
			$counselModel->getAdapter()->rollBack();
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 编辑咨询活动的状态
	 * 1-正常  2-隐藏
	 */
	protected $editConf = array(
		array("cid", "number", "主题ID格式不正确！", DM_Helper_Filter::MUST_VALIDATE),
		array("status", "1,2", "修改状态不正确！", DM_Helper_Filter::EXISTS_VALIDATE, "in", "2")
	);

	public function editAction()
	{
		try {
			$this->isLoginOutput();
			$memberID = $this->memberInfo->MemberID;

			$counselModel = new Model_Counsel_Counsel();
			$counsel_info = $counselModel->getInfoMix(array("CID =?" => $this->_param['cid']), array("CID", "MemberID", "Status"));
			if (is_null($counsel_info) || $counsel_info['MemberID'] != $memberID)
				throw new Exception("不存在该主题！");

			$status = 0;
			switch ($this->_param['status']) {
				case 1:
					if ($counsel_info['Status'] != $counselModel::COUNSEL_STATUS_HIDE)
						throw new Exception("编辑失败！");
					$status = $counselModel::COUNSEL_STATUS_TRUE;
					break;
				case 2:
					if ($counsel_info['Status'] != $counselModel::COUNSEL_STATUS_TRUE)
						throw new Exception("编辑失败！");
					$status = $counselModel::COUNSEL_STATUS_HIDE;
					break;
			}

			$result = $counselModel->update(array(
				"Status" => $status,
				"UpdateTime" => date('Y-m-d H:i:s')
			), array("CID =?" => $counsel_info['CID']));
			if ($result === false)
				throw new Exception('编辑失败');

			parent::succReturn(array());
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 我的咨询服务列表
	 * PC端
	 */
	protected $myCounselConf = array(
		array("page", "number", "请选择当前页！", DM_Helper_Filter::EXISTS_VALIDATE, null, "0"),
		array("pagesize", "number", "请选择每页条数！", DM_Helper_Filter::EXISTS_VALIDATE, null, "30"),
	);

	public function myCounselAction()
	{
		$this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;

		try {
			$counselModel = new Model_Counsel_Counsel();
            $supportModel = new Model_Counsel_CounselSupportRegion();
			$select = $counselModel->select()->setIntegrityCheck(false);

			//desc 显示多少个字
			$select->from("counsel", array("CID", "Title", "Duration", "Price", "Desc", "Status",
				"ConsultTotal", "CommentTotal", "Score", "CreateTime"));
            
			$select->where("MemberID =?", $member_id);
			$select->where("Status !=?", $counselModel::COUNSEL_STATUS_CLOSE);
            $select->where("DataType =1");
            
            $countSql = $select->__toString();
            $countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

            //总条数
            $total = $counselModel->getAdapter()->fetchOne($countSql);
            
			$result = $this->listResults($counselModel, $select, "CID", true, "CID",false);
            $list = array();
            foreach($result as $row){
                $row['SupportCity'] = $supportModel->getCityListByCID($row['CID']);
                $row['CreateTime'] = date('Y.m.d',strtotime($row['CreateTime']));
                $list[] = $row;
            }
			parent::succReturn(array("Rows" => $list,'Total'=>$total));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 咨询服务列表 APP
	 * 支持城市筛选
	 * 创建者筛选
	 */
	protected $listConf = array(
		array("last_id", "number", "请选择最后查询ID！", DM_Helper_Filter::EXISTS_VALIDATE, null, "0"),
		array("pagesize", "number", "请选择每页条数！", DM_Helper_Filter::EXISTS_VALIDATE, null, "30"),
		array("city", "/^[\d]{6}$/", "咨询城市格式不正确！", DM_Helper_Filter::EXISTS_VALIDATE),
		array("member_id", "number", "创建者ID不正确！", DM_Helper_Filter::EXISTS_VALIDATE)
	);

	public function listAction()
	{
		//$this->isLoginOutput();
        $cur_member_id = isset($this->memberInfo->MemberID)?$this->memberInfo->MemberID:0;
        $member_id = isset($this->_param['member_id']) ? $this->_param['member_id']:0;
		try {
			$counselModel = new Model_Counsel_Counsel();
            $supportModel = new Model_Counsel_CounselSupportRegion();
            
			//支持城市筛选
			$is_city = false;
			$cids = array();
			if (isset($this->_param['city'])) {
				$is_city = true;
				$select = $supportModel->select()->setIntegrityCheck(false);
				$select->from("counsel_support_region as r", array("r.CID"));
				$select->joinLeft("counsel as c", "r.CID=c.CID and c.DataType=1", null);
				$select->where("c.status =?", $counselModel::COUNSEL_STATUS_TRUE);
				!empty($member_id) && $select->where("c.MemberID =?", $member_id);

				$select->where("r.Code =?", $this->_param['city']);
				$cids = $this->listResults($supportModel, $select, "r.CID", true, "r.CID",false);
				if (empty($cids)) {
					parent::succReturn(array("Rows" => array()));
				} else {
					$cids = array_map("current", $cids);
				}
			}

			$select = $counselModel->select()->setIntegrityCheck(false);
			$user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;

			//desc 显示多少个字
			$select->from("counsel as c", array("c.CID", "c.Title", "c.Desc", "c.Duration", "c.Price",
				"c.Score", "c.ConsultTotal", "c.CommentTotal", "c.CreateTime",'c.MemberID'));
			//形象头衔 资质
			$select->joinLeft("financial_planner_info as f", "c.MemberID=f.MemberID", array("f.Photo", "f.QualificationType"));
            $select->where("c.DataType=1");
			if ($is_city) {
				if (empty($cids)) {
					$result = array();
				} else {
					$select->order("c.CID desc");
					$select->where("c.CID IN(?)", $cids);
					$result = $counselModel->fetchAll($select)->toArray();
				}
			} else {
                !empty($member_id) && $select->where("c.MemberID =?", $member_id);
				$select->where("c.Status =?", $counselModel::COUNSEL_STATUS_TRUE);
				$result = $this->listResults($counselModel, $select, "c.CID", true, "c.CID",false);
			}
            $list = array();
            $columnModel = new Model_Column_Column();
            $memberModel = new DM_Model_Account_Members();
            foreach($result as $row){
                $columnInfo = $columnModel->getMyColumnInfo($row['MemberID'],1,'Title');
                $row['ColumnTitle'] = empty($columnInfo)?'':$columnInfo['Title'];
                $memberInfo = $memberModel->getById($row['MemberID']);
                $row['RealName'] = $memberInfo['RealName'];
                $row['SupportCity'] = $supportModel->getCityListByCID($row['CID']);
                $row['Desc'] = trim($row['Desc']);
                $list[] = $row;
            }
            
         if(!empty($result) && $cur_member_id > 0){
            $counselModel->updateLastIDCache($cur_member_id, $result[0]['CID']);	
            }
			parent::succReturn(array("Rows" => $list));
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 * 获取单个有效咨询主题的详细信息
	 * 包含支持城市信息、理财师信息、评论信息
	 */
	protected $detailConf = array(
		array("cid", "number", "主题ID格式不正确！", DM_Helper_Filter::MUST_VALIDATE),
	);

	public function detailAction()
	{
		//$this->isLoginOutput();
		$member_id = isset($this->memberInfo->MemberID)?$this->memberInfo->MemberID:0;

		try {
			$counselModel = new Model_Counsel_Counsel();
			$counsel_info = $counselModel->getInfoMix(array("CID =?" => $this->_param['cid'],
				"Status !=?" => $counselModel::COUNSEL_STATUS_CLOSE));
			if (empty($counsel_info))
				throw new Exception("不存在该主题！");

			//获取咨询城市
			$supportModel = new Model_Counsel_CounselSupportRegion();
			$counsel_info['SupportCity'] = $supportModel->getCityListByCID($this->_param['cid']);

			//获取理财师 理财号  形象照 资质
            $columnModel = new Model_Column_Column();
            $columnInfo = $columnModel->getMyColumnInfo($counsel_info['MemberID'],1,'Title');
            $counsel_info['ColumnTitle'] = empty($columnInfo)?'':$columnInfo['Title'];
            
            $memberModel = new DM_Model_Account_Members();
            $memberInfo = $memberModel->getById($counsel_info['MemberID']);
            $counsel_info['RealName'] = $memberInfo['RealName'];

			$financialModel = new Model_Financial_FinancialPlannerInfo();
			$financial_info = $financialModel->getInfoMix(array("MemberID =?" => $counsel_info['MemberID']), array("Photo", "QualificationType"));
			$counsel_info = array_merge($counsel_info, $financial_info);

			//咨询主题评价
			$commentModel = new Model_Counsel_CounselOrderComment();
			$commentsInfo = $commentModel->getCommentsByCID($this->_param['cid'], $member_id);
            $counsel_info['Comments'] = $commentsInfo['list'];
            $counsel_info['CommentListNum'] = $commentsInfo['total'];
			parent::succReturn($counsel_info);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	protected $getFinancialInfoConf = array(
		array("member_id", "number", "请选择理财师！", DM_Helper_Filter::MUST_VALIDATE),
	);

	/**
	 * 理财师服务主页
	 */
	public function getFinancialInfoAction()
	{
		//$this->isLoginOutput();
        $member_id = isset($this->memberInfo->MemberID)?$this->memberInfo->MemberID:0;

		try {
			//理财师基本信息
			$financial_info = $this->isFinancial($this->_param['member_id'], array("Photo", "City", "AuthenticateID"));
			$regionModel = new Model_Region();
			$financial_info['City'] = $regionModel->getInfoMix(array("Code =?" => $financial_info['City']), "Name");
            $columnModel = new Model_Column_Column();
            $columnInfo = $columnModel->getMyColumnInfo($this->_param['member_id'],1,'Title');
            $financial_info['ColumnTitle'] = empty($columnInfo)?'':$columnInfo['Title'];
            
            $memberModel = new DM_Model_Account_Members();
            $memberInfo = $memberModel->getById($this->_param['member_id']);
            $financial_info['RealName'] = $memberInfo['RealName'];
            
			//理财师资质列表
			$qualificationModel = new Model_Qualification();
			$select = $qualificationModel->select()->setIntegrityCheck(false);
			$select->from("financial_qualification", "GROUP_CONCAT(FinancialQualificationType) AS Qualification");
			$select->where("CheckStatus =?", 1);
			$select->where("AuthenticateID =?", $financial_info['AuthenticateID']);
			$select->group("AuthenticateID");
			$qualification_info = $qualificationModel->fetchRow($select);
			if (is_null($qualification_info)) {
				$financial_info['Qualification'] = "";
			} else {
				$qualification_info = $qualification_info->toArray();
				$financial_info['Qualification'] = $qualification_info['Qualification'];
			}
			unset($financial_info['AuthenticateID']);

			//理财师问财统计数据
			$stateModel = new Model_Counsel_CounselSellerState();
			$state_info = $stateModel->getInfoMix(array("MemberID =?" => $this->_param['member_id']), array("ConsultNum", "CommentNum", "ReceiveAverageTime"));
            if(empty($state_info)){
                $state_info = array("ConsultNum"=>0, "CommentNum"=>0, "ReceiveAverageTime"=>0);
            }
			$financial_info = array_merge($financial_info, $state_info);
            if($financial_info['ReceiveAverageTime']>0){
                $financial_info['ReceiveAverageTime'] = $financial_info['ReceiveAverageTime'] / 3600;
                $financial_info['ReceiveAverageTime'] = $financial_info['ReceiveAverageTime']<1?1:number_format($financial_info['ReceiveAverageTime'],1,'.','');
            }

			//理财师发布的有效主题 5条
			$counselModel = new Model_Counsel_Counsel();
			$counsel_info = $counselModel->getInfoMix(array("MemberID =?" => $this->_param['member_id'], "Status =?" => $counselModel::COUNSEL_STATUS_TRUE),
				array("CID", "Title", "Desc", "ConsultTotal", "Score"), "All", "CreateTime desc", "5");
			$financial_info['Counsel'] = $counsel_info;
            
            //总条数
            $total = $counselModel->getAdapter()->fetchOne("select count(*) as total from counsel where MemberID =".$this->_param['member_id']." and Status =".$counselModel::COUNSEL_STATUS_TRUE);
            
            $financial_info['CounselListNum'] = $total;

			//理财师的评论 2条
			$commentModel = new Model_Counsel_CounselOrderComment();
			$commentsInfo = $commentModel->getCommentsBySeller($this->_param['member_id'], $member_id);
            $financial_info['Comments'] = $commentsInfo['list'];
            $financial_info['CommentListNum'] = $commentsInfo['total'];
			parent::succReturn($financial_info);
		} catch (Exception $e) {
			parent::failReturn($e->getMessage());
		}
	}

	/**
	 *
	 * 理财师判断，是否可以发布问财
	 * @param $member_id
	 * @param array $fields
	 * @return bool
	 * @throws Exception
	 */
	protected function isFinancial($member_id, $fields = array("MemberID"))
	{
		$financialModel = new Model_Financial_FinancialPlannerInfo();
		$result = $financialModel->getInfoMix(array("MemberID =?" => $member_id), $fields);
		if (is_null($result))
			throw new Exception("请先完善理财师的详细资料！");

		return $result;
	}
    
    /**
     * 获取问财账号详情
     */
    public function capitalInfoAction()
    {
        $this->isLoginOutput();
		$member_id = $this->memberInfo->MemberID;
        $stateModel = new Model_Counsel_CounselSellerState();
        $state_info = $stateModel->getInfoMix(array("MemberID =?" => $member_id), array("Settlement", "WaitSettlement"));
        $settlement = empty($state_info)?0.00:$state_info['Settlement'];
        $waitSettlement = empty($state_info)?0.00:$state_info['WaitSettlement'];
        parent::succReturn(array("Settlement" => $settlement,'WaitSettlement'=>$waitSettlement));
    }
}