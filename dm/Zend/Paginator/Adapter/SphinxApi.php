<?php
require_once 'Zend/Paginator/Adapter/DbSelect.php';
/**
 * sphinx API 分页
 * 2014-2-8 下午3:59:35
 *
 * @author roy
 *        
 */
class Zend_Paginator_Adapter_SphinxApi implements Zend_Paginator_Adapter_Interface {
	private $sphinxResult = null;
	protected $_rowCount = null;
	public function __construct($sr) {
		$this->sphinxResult = $sr;
	}
	
	/*
	 * (non-PHPdoc) @see Countable::count()
	 */
	public function count() {
		$this->_rowCount = $this->sphinxResult ["total_found"];
		return $this->_rowCount;
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Paginator_Adapter_DbSelect::getItems()
	 */
	public function getItems($offset, $itemCountPerPage) {
		if(isset($this->sphinxResult ["matches"])){
			return $this->sphinxResult ["matches"];
		}
		return array();
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Paginator_Adapter_DbSelect::setRowCount()
	 */
	public function setRowCount($rowCount) {
		$this->_rowCount = $rowCount;
		return $this;
	}
}
