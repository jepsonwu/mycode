<?php
/**
 * 广告位相关
 *
 * @author Jeff
 */
class Model_WordsAds extends Zend_Db_Table {
	protected $_name = 'words_ads';
	protected $_primary = 'AID';

	public function getAds($where = null, $orderBy = null, $limit = null, $offset = null) {
		if (is_numeric($where)) {
			$where = $this->_name . '.' . $this->_primary . '=' . $where;
		}
		$data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
		return empty($data) ? null : $data;
	}

	public function getAdsTotal($where = null) {
		if (is_numeric($where)) {
			$where = $this->_name . '.' . $this->_primary . '=' . $where;
		}
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name, array('total' => 'count(*)'))->where($where);
		// die($select->__toString());
		$data = $select->query()->fetch();
		return isset($data['total']) ? intval($data['total']) : 0;
	}

	/**
	 * 添加广告位
	 * @param int $barID 广告位ID
	 */
	public function addAds($data = array()) {
		if (empty($data)) {
			return false;
		}
		$row = $this->createRow($data);
		if ($row->save()) {
			return true;
		} else {
			return false;
		}
	}

	public function updateAds($ID = null, $data = array()) {
		if( !$ID ) {
			return false;
		}
		if( empty($data) ) {
			return false;
		}
		$row = $this->fetchRow('AID = '.$ID);
		if( $row ) {
			foreach ($data as $k => $v) {
				isset($row->$k) && ($row->$k = $v);
			}
			if( $row->save() ) {
				return true;
			} else {
				return false;
			}
		} else {
			throw new Exception('广告不存在');
		}
	}

	public function delAds($where = null) {
		if( !$where ) {
			return false;
		}
		if( is_numeric($where) ) {
			$where = $this->_primary . '=' . $where;
		}
		if( $this->update(array('Status'=>0), $where)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getAdsShowType() {
		return array(1=>'话题主页');
	}
	
	public function getCount($ShowType)
	{
		$select = $this->select()->from($this->_name,'count(1) as num')->where('ModuleType = ?',$ShowType)->where('Status = ?',1);
		$info = $select->query()->fetch();
		return $info['num'];
	}
	
	public function getAdsList($moduleType)
	{
		$select = $this->select()->from($this->_name,array('AID','Title','Link'))->where('Status = ?',1)->where('ModuleType = ?',$moduleType);
		$result = $select->order('Sort ASC')->order('AID ASC')->query()->fetchAll();
		return $result;
	}
}
