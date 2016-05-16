<?php
/**
 * 文章分类
 * Class Model_Artile_Category
 */

class DM_Model_Table_Article_Category extends DM_Model_Table {

    protected $_name = 'article_categorys';
    protected $_primary = 'CategoryID';
    protected $_content = 'articles';

    public function getArticleListByCategory($cat_name)
    {
        $select = $this->select()->setIntegrityCheck(false);
        $select->from(array('cat'=>$this->_name),array());
        $select->joinLeft(array('con' => $this->_content), "cat.CateID=con.ID", array('title','ArticleID'))
                      ->where("cat.CateName = ?", $cat_name)
                      ->limit(5)
                      ->order('con.EditDate');
        return $this->_db->fetchAll($select);
    }

    public function getCategoryByPid($pid){
        $pid = (int)$pid;
        $sql = "select CategoryID,Name from {$this->_name}
                where PID = '{$pid}'";
        $cat_info = $this->_db->fetchAll($sql);
        foreach($cat_info as $key=>$item){
            $item['CategoryID'] = (int)$item['CategoryID'];
            $sql = "select ArticleID,Title from {$this->_content}
                where CategoryID = '{$item['CategoryID']}' order by DataTime desc limit 5";
            $content = $this->_db->fetchAll($sql);
            $article[$key]['cate_list']=$content;
        }
        return $article;

    }
    
    /**
     * 向上移动
     *
     * @param type $id
     * @param type $pid_key
     * @param type $order_field
     * @return type
     */
    public function up($id, $parent_field = null, $order_field = "Orders")
    {
    	$select = $this->select();
    	$primary = 'CategoryID';
    	$select->where("{$primary} = ?", $id);
    	$info = $this->_db->fetchRow($select);
    	$select->reset();
    	if($parent_field){
    		if(is_array($parent_field)){
    			foreach($parent_field as $field){
    				$select->where("{$field} = ?", $info[$field]);
    			}
    		}else{
    			$select->where("{$parent_field} = ?", $info[$parent_field]);
    		}
    	}
    	$select->where("{$order_field} < ?", $info[$order_field])
    	->order("{$order_field} desc")
    	->limit(1);
    	$newinfo = $this->_db->fetchRow($select);
    	if(empty($newinfo)){
    		return false;
    	}
    	$sql = "update {$this->_name} set {$order_field} = '{$newinfo[$order_field]}'
    	where {$primary} = '{$info[$primary]}'";
    	$this->_db->query($sql);
    	$sql = "update {$this->_name} set {$order_field} = '{$info[$order_field]}'
    	where {$primary} = '{$newinfo[$primary]}'";
    	$this->_db->query($sql);
    	return true;
    }
    
    	/**
    	* 向下移动
    	*
    	* @param type $id
    	* @param type $pid_key
    	* @param type $order_field
    	* @return type
    	*/
    	public function down($id, $parent_field = NULL, $order_field = "Orders")
    	{
    	$select = $this->select();
    	$primary = 'CategoryID';
    	$select->where("{$primary} = ?", $id);
    	$info = $this->_db->fetchRow($select);
    	$select->reset();
    	if($parent_field){
    	if(is_array($parent_field)){
    	foreach($parent_field as $field){
    	$select->where("{$field} = ?", $info[$field]);
    	}
    	}else{
    	$select->where("{$parent_field} = ?", $info[$parent_field]);
    	}
    	}
    	$select->where("{$order_field} > ?", $info[$order_field])
    	->order("{$order_field} asc")
    	->limit(1);
    	$newinfo = $this->_db->fetchRow($select);
    	if(empty($newinfo)){
    	return false;
    	}
    	$sql = "update {$this->_name} set {$order_field} = '{$newinfo[$order_field]}'
    	where {$primary} = '{$info[$primary]}'";
    	$this->_db->query($sql);
    	$sql = "update {$this->_name} set {$order_field} = '{$info[$order_field]}'
    	where {$primary} = '{$newinfo[$primary]}'";
    	$this->_db->query($sql);
    	return true;
    	}
    
    	/**
    	 * 获得排第一的分类
    	 */
    public function getDefaultCate($pid)
    {
    	$select = $this->_db->select();
    	$select->from($this->_name,'CategoryID')->order('Orders Asc')->where('PID = ?',$pid);
    	$cate_info = $this->_db->fetchAll($select);
    	$subcate_id = '';
    	foreach ($cate_info as $info_v){
    		$subcate_id .= $info_v['CategoryID'].',';
    	}
    	$subcate_ids = substr($subcate_id, 0,-1);
    	return $subcate_ids;
    }
}