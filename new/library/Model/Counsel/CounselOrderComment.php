<?php

/**
 * 问财订单评论
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-10
 * Time: 下午4:42
 */
class Model_Counsel_CounselOrderComment extends Model_Common_Common
{
	protected $_name = 'counsel_order_comment';
	protected $_primary = 'OCID';

	public function getCommentsByCID($cid, $member_id, $limit = null, $order = null)
	{
		return $this->getComments(array("c.CID =?" => $cid), $member_id, $limit, $order);
	}

	public function getCommentsBySeller($seller_id, $member_id, $limit = null, $order = null)
	{
		return $this->getComments(array("c.SellerID =?" => $seller_id), $member_id, $limit, $order);
	}

	/**
	 * 根据主题ID获取备注信息
	 *
	 * @param $where
	 * @param $member_id
	 * @param null $limit
	 * @param null $order
	 * @return array
	 *
	 */
	protected function getComments($where, $member_id, $limit = null, $order = null)
	{
		mb_internal_encoding("UTF-8");

		$user_db = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;

		$select = $this->select()->setIntegrityCheck(false);
		$select->from("{$this->_name} as c", array("c.SellerID", "c.BuyerID", "c.Comment", "c.Score", "c.CreateTime", 'c.ReplyComment', 'c.UpdateTime'));
		$select->joinLeft("{$user_db}.members as m", "c.BuyerID = m.MemberID", array("m.UserName"));

		foreach ($where as $key => $val)
			$select->where($key, $val);

		$countSql = $select->__toString();
		$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);

		//总条数
		$total = $this->getAdapter()->fetchOne($countSql);

		is_null($limit) && $limit = 2;
		$select->limit($limit);

		is_null($order) && $order = "c.CreateTime DESC";
		$select->order($order);

		$result = $this->_db->fetchAll($select);

		//特殊处理
		if (!empty($result)) {
			//防止打乱顺序
			for ($i = 0; $i < count($result); $i++) {
				$val =& $result[$i];
				//日期处理
//				$val['CreateTime'] = substr($val['CreateTime'], 0, strpos($val['CreateTime'], " "));
//				$date_info = getdate();
//				$date_info['year'] <= intval(substr($val['CreateTime'], 0, strpos($val['CreateTime'], "-"))) &&
//				$val['CreateTime'] = substr($val['CreateTime'], strpos($val['CreateTime'], "-") + 1);

				//财猪号处理
				if ($member_id != $val['SellerID'] && $member_id != $val['BuyerID'])
					$val['UserName'] = mb_substr($val['UserName'], 0, 1) . "***" .
						(mb_strlen($val['UserName']) <= 2 ? "" : mb_substr($val['UserName'], -1));
			}
		}

		return array('total' => $total, 'list' => $result);
	}
}