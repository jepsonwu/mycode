<?php
/**
 * 发布说说的图片处理
 * @author johnny 2015-07-10
 */

class Model_ShuoImage extends Zend_Db_Table {
	protected $_name = 'shuo_images';
	protected $_primary = array( 1 => 'ImageID');

	/**
	 * 获取某条说说的图片URL
	 * @param Int $shuoID 说说ID
	 * @return array | null 返回图片的数组
	 */
	public function getImageURLs($shuoID = null,$isFormat = 0) {
		if( !$shuoID = (int) $shuoID ) {
			throw new Exception('说说ID不能为空');
		}
		$data = $this->fetchAll('ShuoID='.$shuoID, array('SortIndex ASC', 'ImageID ASC'))->toArray();
		if($isFormat){
			$tmp = array();
			foreach ($data as $item){
				$tmp[] = $item['Url'];
			}
			$data = $tmp;
		}
		return empty($data) ? null : $data;
	}

	/**
	 * 添加说说图片URL入库
	 * @param Int $shuoID
	 * @param string $URL
	 * @return boolean
	 */
	public function addImageURL($shuoID = null, $URL = '') {
		if( !$shuoID = (int) $shuoID ) {
			throw new Exception('说说ID不能为空');
		}
		if( !($URL = trim($URL))  || !($URLArray = explode(',', $URL)) || !($URLArray = array_filter($URLArray, 'trim')) ) {
			// throw new Exception('图片不能为空');
			return array();
		}
		$success = array();
		foreach ($URLArray as $key => $value) {
			if( $this->insert(array('ShuoID'=>$shuoID, 'Url'=>$value, 'SortIndex'=>$key)) ) {
				$success[$key] = $value;
			}
		}
		return $success;
	}

	/**
	 * 删除说说图片URL记录
	 * @param Int | string | array | select obj $where
	 * @return boolean
	 */
	public function delImageURLs($where = null) {
		if( !$where ) {
			return false;
		}
		if( is_numeric($where) ) {
			$where = $this->_primary[1] . '=' . $where;
		}
		if( $this->delete($where) ) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 *获取相册
	 *@param $memberID
	 */
	public function getAlbum($memberID)
	{
		$select = $this->select()->setIntegrityCheck(false);
		$select->from($this->_name.' as si',array('ImageID','Url'));
		$select->joinLeft('shuoshuo as s', 's.ShuoID = si.ShuoID ','')->where('s.MemberID = ?',$memberID)->where('s.Status =1');
		$res = $select->order('si.CreateTime desc')->limit(3)->query()->fetchAll();
		return $res ? $res : array();
	}
}