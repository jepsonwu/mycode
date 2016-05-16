<?php

/**
 * 咨询订单列表
 * User: kitty
 */
class Admin_CounselOrderController extends DM_Controller_Admin
{

	public function indexAction()
	{

	}

	//查询条件
	protected $list_where = array(
		"eq" => array("SellerID", "ValidStatus", "BuyerID", "OrderStatus", "SellerStatus", "OID"),
		"bet" => array("Start_CreateTime", "End_CreateTime"),
		"like" => array("OrderNo"),
	);

	/**
	 * 咨询服务列表
	 */
	public function listAction()
	{
		$orderModel = new Model_Counsel_CounselOrder();
		$select = $orderModel->select();
		$this->_helper->json($this->listResults($orderModel, $select, "OID"));
	}

	//验证参数
	protected $filter_fields = array(
		"oid" => array("oid", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"eid" => array("eid", "number", "参数错误！", DM_Helper_Filter::MUST_VALIDATE),
		"postpone_time" => array("postpone_time", "1,7", '请选择延期天数!', DM_Helper_Filter::MUST_VALIDATE, "between"),
		"commission" => array("commission", "0,100", '结算比例格式错误，百分比：0-100!', DM_Helper_Filter::MUST_VALIDATE, "between"),
		"reason" => array("reason", "10,200", "请填写原因，10到200个字！", DM_Helper_Filter::MUST_VALIDATE, "length"),
	);

	/**
	 * 延长结算
	 */
	public function postponeSettlementAction()
	{
		$orderModel = new Model_Counsel_CounselOrder();
		if ($this->isPost()) {
			$this->filterParam(array("oid", "postpone_time", "reason"));

			try {
				$this->_param['postpone_time'] = $this->_param['postpone_time'] * 86400;
				$orderModel->changeStatus($this->_param['oid'], $orderModel::ORDER_CHANGE_SELLER_POSTPONE,
					$this->_param, $this->_auth_info->AdminID, $orderModel::ORDER_FILTER_SYSTEM);

				$this->succJson("延长成功！");
			} catch (Exception $e) {
				$this->failJson($e->getMessage());
			}
		} else {
			$this->filterParam(array('oid'));
			$order_info = $orderModel->getInfoMix(array("OID =?" => $this->_param['oid']), array("OID", "OrderNo"));
			$this->view->order_info = $order_info;
		}
	}

	/**
	 * 拒绝结算
	 */
	public function refusedSettlementAction()
	{
		$orderModel = new Model_Counsel_CounselOrder();
		if ($this->isPost()) {
			$this->filterParam(array("oid", "reason"));

			try {
				$orderModel->changeStatus($this->_param['oid'], $orderModel::ORDER_CHANGE_SELLER_REFUSED,
					$this->_param, $this->_auth_info->AdminID, $orderModel::ORDER_FILTER_SYSTEM);

				$this->succJson("拒绝成功！");
			} catch (Exception $e) {
				$this->failJson($e->getMessage());
			}
		} else {
			$this->filterParam(array('oid'));
			$order_info = $orderModel->getInfoMix(array("OID =?" => $this->_param['oid']), array("OID", "OrderNo"));
			$this->view->order_info = $order_info;
		}
	}

	/**
	 * 人工结算  按比例结算
	 */
	public function finishSettlementAction()
	{
		$orderModel = new Model_Counsel_CounselOrder();
		if ($this->isPost()) {
			$this->filterParam(array("oid", "reason", "commission"));

			try {
				$orderModel->changeStatus($this->_param['oid'], $orderModel::ORDER_CHANGE_SELLER_SERVICE_DONE,
					$this->_param,
					$this->_auth_info->AdminID, $orderModel::ORDER_FILTER_SYSTEM);

				$this->succJson("结算成功！");
			} catch (Exception $e) {
				$this->failJson($e->getMessage());
			}
		} else {
			$this->filterParam(array('oid'));
			$order_info = $orderModel->getInfoMix(array("OID =?" => $this->_param['oid']), array("OID", "OrderNo"));
			$this->view->order_info = $order_info;
		}
	}

	protected $except_list_where = array(
		"eq" => array("OID", "Type", "AdminID"),
		"bet" => array("Start_CreateTime", "End_CreateTime"),
	);

	public function exceptionalAction()
	{

	}

	/**
	 * 异常订单列表
	 */
	public function exceptionalListAction()
	{
		$exceptModel = new Model_Counsel_CounselOrderExceptional();
		$select = $exceptModel->select();
		$this->_helper->json($this->listResults($exceptModel, $select, "EID", true, $this->except_list_where));
	}

	public function exceptionalCloseAction()
	{
		$exceptModel = new Model_Counsel_CounselOrderExceptional();

		if ($this->isPost()) {
			$this->filterParam(array("eid", "reason", "commission"));
			$exceptModel->getAdapter()->beginTransaction();

			try {
				$oid = $exceptModel->getInfoMix(array("EID =?" => $this->_param['eid']), "OID");
				$result = $exceptModel->delete(array("EID =?" => $this->_param['eid']));
				if ($result === false)
					throw new Exception("操作失败！");

				$orderModel = new Model_Counsel_CounselOrder();
				$orderModel->changeStatus($oid, $orderModel::ORDER_CHANGE_MEET_OVERDUE,
					$this->_param,
					$this->_auth_info->AdminID, $orderModel::ORDER_FILTER_SYSTEM);

				$exceptModel->getAdapter()->commit();
				$this->succJson("操作成功！");
			} catch (Exception $e) {
				$exceptModel->getAdapter()->rollBack();
				$this->failJson($e->getMessage());
			}
		} else {
			$this->filterParam(array('eid'));
			$except_info = $exceptModel->getInfoMix(array("EID =?" => $this->_param['eid']), array("EID", "OID"));
			$this->view->except_info = $except_info;
		}

	}
}