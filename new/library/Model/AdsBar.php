<?php
/**
 * 广告位相关
 * 
 * @author Jeff 
 */
class Model_AdsBar extends Zend_Db_Table {
    protected $_name = 'ads_bar';
    protected $_primary = 'AdsBarID';

    /**
     * 获取广告位
     */
    public function getAdsbar($lastBarNum, $count, $adsType) {
        $select = $this->select()->from($this->_name, array('AdsBarID', 'BarNum','AdsType'))->where('BarNum > ?', $lastBarNum)->where('ShowType = ?', $adsType);
        $barNum = $select->order('BarNum asc')->limit($count)->query()->fetchAll();
        return $barNum;
    } 

    public function getAdsBars($where = null, $orderBy = null, $limit = null, $offset = null) {
        if (is_numeric($where)) {
            $where = $this->_name . '.' . $this->_primary . '=' . $where;
        } 
        $data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
        return empty($data) ? null : $data;
    } 

    public function getAdsBarTotal($where = null) {
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
    public function addAdsBar($data = array()) {
        if (empty($data)) {
            return false;
        } 
        if( !isset($data['BarNum']) || !isset($data['ShowType']) ) {
            throw new Exception('广告位号或展示类型不能为空');
        } else {
            $data['BarNum'] = (int) $data['BarNum'];
            $data['ShowType'] = (int) $data['ShowType'];
        }
        if( $this->getAdsBars('BarNum='.$data['BarNum'].' AND ShowType='.$data['ShowType']) ) {
            throw new Exception('广告位已存在');
        }
        $row = $this->createRow($data);
        if ($row->save()) {
            return true;
        } else {
            return false;
        } 
    } 

    public function updateAdsBar($ID = null, $data = array()) {
        if( !$ID ) {
            return false;
        }
        if( empty($data) ) {
            return false;
        }
        $row = $this->fetchRow('AdsBarID = '.$ID);
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
            throw new Exception('广告位不存在');
        }
    }

    public function delAdsBar($where = null) {
        if (!$where) {
            return false;
        } 
        if (is_numeric($where)) {
            $where = $this->_primary . '=' . $where;
        } 
        if ($this->delete($where)) {
            return true;
        } else {
            return false;
        } 
    } 
} 
