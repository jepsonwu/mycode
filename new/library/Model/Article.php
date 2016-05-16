<?php
class Model_Article extends DM_Model_Table_Article_Article
{
    protected $_name = 'articles';
    protected $_primary = 'ArticleID';

    /**
     * 获取选定文章基本信息
     */
    public function getDetail($id)
    {
    	$mess = $this->_db->fetchRow("select  ArticleID,CategoryID,Title,TitleEn,Contents,ContentsEn,DataTime,Url,MediaSource,platform from ".$this->_name." where ArticleID = :mid", array('mid'=>$id));
    	return $mess;
    }


    /**
     * 添加
     */
    public function add($ret)
    {
    	//标题和内容过滤
    	$ret['Title'] = str_ireplace('script','',$ret['Title']);
    	$ret['Contents'] = str_ireplace('script','',$ret['Contents']);  	

    	return $this->_db->insert($this->_name,$ret);
    } 

    /**
     * 编辑
     */
    public function edit($ret)
    {
        //标题和内容过滤
        
        $data = array(
                'DataTime'=>date('Y-m-d H:i:s'),
                'CategoryID'=>$ret['CategoryID'],
                'platform'=>$ret['platform']
         );
        
        isset($ret['Title']) && $data['Title'] = str_ireplace('script','',$ret['Title']);
        isset($ret['TitleEn']) && $data['TitleEn'] = str_ireplace('script','',$ret['TitleEn']);
        isset($ret['Contents']) && $data['Contents'] = str_ireplace('script','',$ret['Contents']);
        isset($ret['ContentsEn']) && $data['ContentsEn'] = str_ireplace('script','',$ret['ContentsEn']);
        isset($ret['MediaSource']) && $data['MediaSource'] = str_ireplace('script','',$ret['MediaSource']);
        isset($ret['Url']) && $data['Url'] = str_ireplace('script','',$ret['Url']);
        
        return $this->update($data, array('ArticleID = ? '=>$ret['ArticleID']));
    
    }

    /**
    *推送到首页
    */
    public function isTop($article_id,$is_top = 1)
    {
        return $this->update(array('IsTop' => $is_top),array('ArticleID = ?'=>$article_id));
    }
}