<?php
/**
 * @date  2013-05-20.
 * @author   Carlton
 */
class DM_Model_Table_Article_Article extends DM_Model_Table {

    protected $_name = 'articles';
    protected $_primary = 'ArticleID';
    private   $_artCate = 'article_categorys';
    
    /**
     * 初始化数据库
     */
    public function __construct($db = null)
    {
        if(empty($db)){
            $db = DM_Controller_Front::getInstance()->getDb('db');
        }
        $this->_setAdapter($db);
    }


    /**
     * 获取文章列表
     */
    public function getPageList($search, $order,$lang = '')
    {
        $select = $this->select()->setIntegrityCheck(false);
        $titleName = $lang == 'en' ? 'TitleEn as Title' : 'Title';
        $select->from(array('a' => $this->_name),array('ArticleID',$titleName,'CategoryID','DataTime'));
        $select->joinLeft(array('c' => $this->_artCate), "a.CategoryID=c.CategoryID", '');
        if(isset($search['Title'])){
            $select->where("a.Title like ?", "%{$search['Title']}%");
        }
        if(isset($search['CategoryID'])){
        	$children = $this->getOrderCate($search['CategoryID']);
        	$cate_ids = array($search['CategoryID']);
        	foreach($children as $item){
        		$cate_ids[] = $item['CategoryID'];
        	}
        	$select->where("a.CategoryID in(?)", $cate_ids);
        }
        if(isset($search['limit'])){
        	$select->limit((int)$search['limit']);
        }
        if(isset($order)){
            $select->order($order);
        }else{
            $select->order("c.Orders desc");
        }
        return $select;
    }
   
    /**
     * 获取选定文章基本信息
     */
    public function getDetail($id)
    {
    	$mess = $this->_db->fetchRow("select  ArticleID,CategoryID,Title,TitleEn,Contents,ContentsEn,DataTime from ".$this->_name." where ArticleID = :mid", array('mid'=>$id));
    	return $mess;
    }    

    public function getArtCateList($category_id,$limit = null)
    {
        $category_id = (int)$category_id;
    	if($limit){
    	    $limit = (int)$limit;
    		$sql = "select * from articles where CategoryID = '{$category_id}' order by DataTime desc limit {$limit}";
    	}else{
    		$sql = "select * from articles where CategoryID = '{$category_id}' order by DataTime desc";
    	}
    	return $this->_db->fetchAll($sql);
    }
    /**
     * 根据分类id获得分类信息
     */
    public function getOneCate($id)
    {
    	$artCate = $this->_db->fetchRow("select Name,NameEn,CategoryID,PID from ".$this->_artCate." where CategoryID = :mid", array('mid'=>$id));
    	return $artCate;
    }

    /**
     * 获取下一级分类
     * $id 为父类id
     */
    public function getArtCateAdmin($id)
    {
        return $this->_db->fetchAll("select CategoryID,Name,NameEn,Orders,PID from ".$this->_artCate." where PID = :mid order by Orders asc", array('mid'=>$id));
    }

    /**
     * 添加
     */
    public function add($ret)
    {
    	//标题和内容过滤
    	$ret['Title'] = str_ireplace('script','',$ret['Title']);
    	$ret['Contents'] = str_ireplace('script','',$ret['Contents']);
    	
    	$ret['DataTime'] = time();

    	return $this->_db->insert($this->_name,$ret);
    }
    
    /**
     * 编辑
     */
    public function edit($ret)
    {
    	//标题和内容过滤
    	
        $data = array(
                'DataTime'=>time(),
                'CategoryID'=>$ret['CategoryID'],
         );
        
    	isset($ret['Title']) && $data['Title'] = str_ireplace('script','',$ret['Title']);
    	isset($ret['TitleEn']) && $data['TitleEn'] = str_ireplace('script','',$ret['TitleEn']);
    	isset($ret['Contents']) && $data['Contents'] = str_ireplace('script','',$ret['Contents']);
    	isset($ret['ContentsEn']) && $data['ContentsEn'] = str_ireplace('script','',$ret['ContentsEn']);
    	
    	return $this->update($data, array('ArticleID = ? '=>$ret['ArticleID']));
    
    }
    /**
     * 改变浏览量
     */
    public function changeView($id,$time,$view){
    	$data = array(
    			'TotalView'=>$view,
    			'EditDate'=>$time,
    	);
    	return $this->update($data, array('ArticleID = ? '=>$id));
    }
    /**
     * 删除
     */
    public function delete($id){
    	return $this->_db->delete($this->_name,array('ArticleID = ? '=>$id));
    }

    
    /**
     * 插入分类集
     */
    public function array_insert($myarray,$value,$position=0)
    {
    	$fore=($position==0)?array():array_splice($myarray,0,$position);
    	foreach($value as $item){
    		$fore[]=$item;
    	}    	 
    	$ret=array_merge($fore,$myarray);
    	return $ret;
    }
    /**
     * 按序输出分类
     */
    public function getOrderCate($id=0,$tier=1)
    {
    	$r1 = $this->getArtCateAdmin($id);
    	$tier++;
    	for($i=0;$i<count($r1);$i++){
    		$r2 = $this->getArtCateAdmin($r1[$i]['CategoryID']);
    		$r1[$i]['children']=count($r2);
    		$r1[$i]['tier']=$tier-1 ;
    		if($r2){    			
    			$r2 = $this->getOrderCate($r1[$i]['CategoryID'],$tier);
    			//把$r2插入$r1
    			$r1 = $this->array_insert($r1,$r2,$i+1);
    			$i=$i+count($r2);
    		}
    	}
		return $r1;    	
    }
    /**
     * 前台输出分类
     */
    public function getFrontCate($lang = '',$pid=0)
    {
    	$res = $this->getOrderCate($pid);
    	foreach($res as $k=>&$item){
    		if($lang == 'en'){
    		    $item['Name'] = $item['NameEn'];		    
    		}
    		unset($item['NameEn']);
    		$res[$k]['Name']=$item['Name'];
    	}
    	return $res;
    }
    /**
     * 后台输出分类
     */
    public function getAdminCate()
    {
    	$res = $this->getOrderCate();
      	    foreach($res as $k=>$item){
   			$str='';
   			for($i=1;$i<$item['tier'];$i++){
   				$str='&nbsp;&nbsp;'.$str;
   				if($i==$item['tier']-1)$str.='|-';
   			}
   			$res[$k]['Name']=$str.$item['Name'];
   		}
    	return $res;
    }
    /**
     * 独立页面
     */
    public function getIndependent()
    {
    	$res  = $this->_db->fetchRow("select * from ".$this->_artCate." where CateName = :mname", array('mname'=>'独立页面'));
    	$res2  = $this->_db->fetchAll("select * from ".$this->_name." where  ID = :mid", array('mid'=>$res['CategoryID']));
    	return $res2;
    }
    /**
     * 添加分类
     */
    public function addCate($ret)
    {
    	$res = $this->getArtCateAdmin($ret['PID']);
    	$orders = 1;
    	for($i=0;$i<count($res);$i++){
    		$orders = $orders > $res[$i]['Orders']?$orders:$res[$i]['Orders']+1;
    	}
    	$ret['Orders'] = $orders;
    	return   $this->_db->insert($this->_artCate,$ret);
    }
    /**
     * 修改分类
     */
    public function editCate($ret)
    {
    	return $this->_db->update($this->_artCate,$ret, array('CategoryID = ? '=>$ret['CategoryID']));
    }
    /**
     * 删除分类
     */
    public function deleteCate($id){
    	return $this->_db->delete($this->_artCate,array('CategoryID = ? '=>$id));
    }

    /**
     * 
     */
    public function getArticleList($search, $order, $page, $pagesize, $count=false)
    {
    	$select = $this->select()->setIntegrityCheck(false);
    	if ($count) {
    		$select->from(array('a' => $this->_name),'count(*)');
    	}else {
    		$select->from(array('a' => $this->_name),array("ArticleID","Title","EditDate","TotalView"));
    	}
    	
    	$select->joinLeft(array('c' => $this->_artCate), "a.id=c.CategoryID", 'c.*');
    	if(isset($search['Title'])){
    		$select->where("a.Title like ?", "%{$search['Title']}%");
    	}
    	if(isset($search['id'])){
    		$children = $this->getOrderCate($search['id']);
    		$cate_ids = array($search['id']);
    		foreach($children as $item){
    			$cate_ids[] = $item['CategoryID'];
    		}
    		$select->where("a.ID in(?)", $cate_ids);
    	}
    	if(isset($order)){
    		$select->order($order);
    	}else{
    		$select->order("c.Orders desc");
    	}
    	if(!$count) {
    		$select->limitPage($page, $pagesize);
    		return $this->_db->fetchAll($select);
    	}else{
    		return $this->_db->fetchOne($select);
    	}
    	
    }
    
    public function getArtByCateID($ID,$lang){
      if($lang == "en"){
    		$arr = $this->_db->fetchRow("select TitleEn ,ContentsEn from ".$this->_name." where  ArticleID = :mid", array('mid'=>$ID)); 			
    				$arr['Title'] = $arr['TitleEn'];
    			    unset($arr['TitleEn']);
    			    $arr['Contents'] = $arr["ContentsEn"];
    			    unset($arr['ContentsEn']);
    		return $arr;
      }else{
    		return $this->_db->fetchRow("select Title ,Contents from ".$this->_name." where  ArticleID = :mid", array('mid'=>$ID));
 
      }
    }

    function getPageHtml($page, $pages, $url){
    	//最多显示多少个页码
    	$_pageNum = 3;
    	//当前页面小于1 则为1
    	$page = $page<1?1:$page;
    	//当前页大于总页数 则为总页数
    	$page = $page > $pages ? $pages : $page;
    	//页数小当前页 则为当前页
    	$pages = $pages < $page ? $page : $pages;
    	 
    	//计算开始页
    	$_start = $page - floor($_pageNum/2);
    	$_start = $_start<1 ? 1 : $_start;
    	//计算结束页
    	$_end = $page + floor($_pageNum/2);
    	$_end = $_end>$pages? $pages : $_end;
    	 
    	//当前显示的页码个数不够最大页码数，在进行左右调整
    	$_curPageNum = $_end-$_start+1;
    	//左调整
    	if($_curPageNum<$_pageNum && $_start>1){
    		$_start = $_start - ($_pageNum-$_curPageNum);
    		$_start = $_start<1 ? 1 : $_start;
    		$_curPageNum = $_end-$_start+1;
    	}
    	//右边调整
    	if($_curPageNum<$_pageNum && $_end<$pages){
    		$_end = $_end + ($_pageNum-$_curPageNum);
    		$_end = $_end>$pages? $pages : $_end;
    	}
    	 
    	$_pageHtml = '';
    	
    	for ($i = $_start; $i <= $_end; $i++) {
    		if($i == $page){
    			$_pageHtml .= '<li class="active"><a>'.$i.'</a></li>';
    		}else{
    			$_pageHtml .= '<li><a href="'.$url.'?page='.$i.'">'.$i.'</a></li>';
    		}
    	}
    	/*if($_end == $pages){
    	 $_pageHtml .= '<li><a title="最后一页">&raquo;</a></li>';
    	}else{
    	$_pageHtml .= '<li><a  title="最后一页" href="'.$url.'&page='.$pages.'">&raquo;</a></li>';
    	}*/
    	return $_pageHtml;
    }

}
