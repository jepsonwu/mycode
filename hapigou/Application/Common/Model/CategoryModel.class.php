<?php

namespace Common\Model;

use Think\Model;

class CategoryModel extends Model {
	
	/**
	 * 设置目录缓存
	 */
	public function setCatCache() {
		//
		$cats = $this->order('id')->getField('id, cat_name, level, prt_id, sort, slug');
		S( 'cats', $cats, C('ONEDAY') );
    	//
    	return $cats;
	}
	
}

?>