<?php
require_once 'Zend/Paginator/Adapter/DbSelect.php';
/**
 * sphinx Query 分页
 * 2014-2-8 下午3:59:35
 *
 * @author roy
 *        
 */
class Zend_Paginator_Adapter_DbSelectSphinxSe extends Zend_Paginator_Adapter_DbSelect {
	private $sphinxResult = null;
	
	/**
	 * 
	 * 执行sphinx  的查询
	 * @param Zend_Db_Select $select        	
	 * @param int $offset        	
	 * @param int $itemCountPerPage        	
	 * @return mixed
	 */
	private function getSphinxQuery($select,$offset=0,$itemCountPerPage=0) {
		$sphinxQuery = $select->__toString ();
		
		if($itemCountPerPage||$offset){
			$sphinxQuery.=" limit ".$offset.",".$itemCountPerPage;
		}
		
		//过滤掉别名的显示,sphinx 不支持sql中的别名
		$from = $select->getPart ( Zend_Db_Select::FROM );
		$tableName = "";
		foreach ( $from as $tableInfo ) {
			$tableName = $tableInfo ["tableName"];
			break;
		}
		$sphinxQuery = str_replace ( "`" . $tableName . "`.", "", $sphinxQuery );
		$db = $select->getAdapter ();
		
		$stmt = $db->query ( $sphinxQuery );
		$this->sphinxResult = $stmt->fetchAll ();
		if ($this->_rowCount === null) {
			//分页操作
			$this->_rowCount = intval($this->_select->getAdapter ()->query ( "SHOW META LIKE 'total_found'" )->fetchColumn(1));
		}
		return $this->sphinxResult;
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Paginator_Adapter_DbSelect::getCountSelect()
	 */
	public function getCountSelect() {
		if ($this->_countSelect !== null) {
			return $this->_countSelect;
		}
		$rs = $this->getSphinxQuery ( $this->_select );
		if ($this->_rowCount === null) {
			//分页操作
			$this->_rowCount = intval($this->_select->getAdapter ()->query ( "SHOW META LIKE 'total_found'" )->fetchColumn(1));
		}
		$this->_countSelect = $this->_rowCount;
		return $this->_rowCount;
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Paginator_Adapter_DbSelect::getItems()
	 */
	public function getItems($offset, $itemCountPerPage) {
		$rs =   $this->getSphinxQuery ( $this->_select ,$offset, $itemCountPerPage);
		return $rs;
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Paginator_Adapter_DbSelect::setRowCount()
	 */
	public function setRowCount($rowCount) {
		if ($rowCount instanceof Zend_Db_Select) {
			$rs = $this->getSphinxQuery ( $this->_select );
			if ($this->_rowCount === null) {
				$meta = $this->_select->getAdapter ()->query ( "SHOW META" )->fetchAll ();
				foreach ( $meta as $info ) {
					if ($info ["Variable_name"] == "total_found") {
						$this->_rowCount = $info ["Value"];
					}
				}
			}
		} else if (is_integer ( $rowCount )) {
			$this->_rowCount = $rowCount;
		} else {
			/**
			 *
			 * @see Zend_Paginator_Exception
			 */
			require_once 'Zend/Paginator/Exception.php';
			
			throw new Zend_Paginator_Exception ( 'Invalid row count' );
		}
		
		return $this;
	}
}
