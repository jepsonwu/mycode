<?php

/**
 * 类型
 */

class Model_Types extends Zend_Db_Table {
    protected $_name = 'types';
    protected $_primary = array(1 => 'TypeID');

    public function getTable() {
        return $this->_name;
    } 

    public function getTypes($where = null, $orderBy = null, $limit = null, $offset = null) {
        // $profiler = $this->getAdapter()->getProfiler();
        // $profiler->setEnabled(true);
        if (is_numeric($where)) {
            $data = $this->fetchAll($this->_primary[1] . '=' . $where, $orderBy, $limit, $offset)->toArray();
        } else {
            $data = $this->fetchAll($where, $orderBy, $limit, $offset)->toArray();
        } 
        // $query = $profiler->getLastQueryProfile();
        // var_dump($query->getQuery());
        return empty($data) ? null : $data;
    } 

    public function addTypes($typeName = '', $typeRemark = '') {
        if (!trim($typeName)) {
            throw new Exception("类型名称不能为空");
        } 

        if ($this->insert(array('typeName' => $typeName,
                        'TypeRemark' => $typeRemark,
                        'AddTime' => date('Y-m-d H:i:s'))
                    )) {
            return true;
        } else {
            return false;
        } 
    } 

    public function updateTypes($where = null, $typeName = '', $typeRemark = '') {
        if (!trim($typeName)) {
            throw new Exception('类型名称不能为空');
        } 
        if (!$where) {
            throw new Exception('必须指定更新条件');
        } 
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where;
        } 
        if ($this->update(array('TypeName' => $typeName, 'TypeRemark' => $typeRemark, 'UpdateTime' => date('Y-m-d H:i:s')), $where)) {
            return true;
        } else {
            return false;
        } 
    } 

    public function getTypesTotal($where = 1) {
        $ret = $this->select()->from($this->_name, 'count(1) as totalCount')->where($where)->query()->fetch();
        return empty($ret) ? 0 : $ret[totalCount];
    } 

    /**
     * 
     * @param array $ |string $where SQL WHERE clause(s).
     */
    public function delTypes($where = null) {
        if (!$where) {
            return false;
        } 
        if (is_numeric($where)) {
            $where = $this->_primary[1] . '=' . $where;
        } 
        if ($this->delete($where)) {
            if (is_numeric($where)) {
                $modelTypeAndTag = new Model_TypeAndTag();
                $modelTypeAndTag->delTagAndType(null, $where);
            } 
            return true;
        } else {
            return false;
        } 
    } 
} 
