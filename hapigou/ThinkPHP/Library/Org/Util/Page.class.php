<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Page.class.php 2601 2012-01-15 04:59:14Z liu21st $

namespace Org\Util;

class Page {
    // 分页栏每页显示的页数
    public $rollPage = 5;
    // 页数跳转时要带的参数
    public $parameter;
    // 默认列表每页显示行数
    public $listRows = 20;
    // 起始行数
    public $firstRow;
    // 分页总页面数
    protected $totalPages;
    // 总行数
    protected $totalRows;
    // 当前页数
    public $nowPage;
    // 分页的栏的总页数
    protected $coolPages;
    // 分页显示定制
    protected $config =	array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' <dl class="pg_left">%totalRow% %header%　<input type="text" value="%listRows%" /> 条/页　%nowPage%/%totalPage% 页</dl> <dl class="pg_right">%upPage%%downPage%%first%%prePage%%linkPage%　%nextPage%%end%直达 <input type="text" /> 页</dl>');

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     +----------------------------------------------------------
     */
    public function __construct($totalRows,$listRows='',$parameter='') {
        $this->totalRows = $totalRows;
        $this->parameter = $parameter;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = !empty($_REQUEST[C('VAR_PAGE')])?intval($_REQUEST[C('VAR_PAGE')]):1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
    }
	//
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    /**
     +----------------------------------------------------------
     * 分页显示输出（后台）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show() {
        if(0 == $this->totalRows) return '';
        $p = C('VAR_PAGE');
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a p='".$upRow."'>".$this->config['prev']."</a>　";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a p='".$downRow."'>".$this->config['next']."</a>　";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a p='".$preRow."'>上".$this->rollPage."页</a>　";
            $theFirst = "<a p='1'>".$this->config['first']."</a>　";
        }
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a p='".$nextRow."'>下".$this->rollPage."页</a>　";
            $theEnd = "<a p='".$theEndRow."'>".$this->config['last']."</a>　";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a p='".$page."'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }
        $pageStr = str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%', '%listRows%', '%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$this->listRows,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$this->config['theme']
        );
        return $pageStr;
    }

    // 前台
    public function show_front() {
    	if(0 == $this->totalRows) return '';
    	$p = C('VAR_PAGE');
    	$nowCoolPage = ceil($this->nowPage/$this->rollPage);
    	$url = $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'&':"?").$this->parameter;
    	$parse = parse_url($url);
    	if(isset($parse['query'])) {
    		parse_str($parse['query'],$params);
    		unset($params[$p]);
    		$url = $parse['path'].'?'.http_build_query($params);
    	}
    	//上下翻页字符串
    	$upRow = $this->nowPage-1;
    	$downRow = $this->nowPage+1;
    	if ($upRow>0){
    		$upPage = "<li><a href='".$url."&".$p."=$upRow'><img src='".__APP__."/Public/Home/images/nclys_01.png' border='0' /></a></li>";
    	}
    	else {
    		$upPage = "";
    	}
    	if ($downRow <= $this->totalPages){
    		$downPage = "<li><a href='".$url."&".$p."=$downRow'><img src='".__APP__."/Public/Home/images/nclys_02.png' border='0' /></a></li>";
    	}
    	else {
    		$downPage = "";
    	}
    	// << < > >>
    	if ($nowCoolPage == 1) {
    		$theFirst = "";
    		$prePage = "";
    	}
    	else {
    		$preRow = $this->nowPage-$this->rollPage;
    		$prePage = "<a href='".$url."&".$p."=$preRow' >上".$this->rollPage."页</a>";
    		$theFirst = "<a href='".$url."&".$p."=1' >1</a>";
    	}
    	if ($nowCoolPage == $this->coolPages){
    		$nextPage = "";
    		$theEnd = "";
    	}
    	else {
    		$nextRow = $this->nowPage+$this->rollPage;
    		$theEndRow = $this->totalPages;
    		$nextPage = "<a href='".$url."&".$p."=$nextRow' >下".$this->rollPage."页</a>";
    		$theEnd = "<a href='".$url."&".$p."=$theEndRow' >".$theEndRow."</a>";
    	}
    	
    	// 1 2 3 4 5
    	$linkPage = "";
    	for($i=1;$i<=$this->rollPage;$i++){
    		$page=($nowCoolPage-1)*$this->rollPage+$i;
    		if($page!=$this->nowPage){
    			if($page<=$this->totalPages){
    				$linkPage .= "<li><a href='".$url."&".$p."=$page'>".$page."</a></li>";
    			}
    			else {
    				break;
    			}
    		}
    		else {
    			if ($this->totalPages != 1){
    				$linkPage .= "<li><a class='on'>".$page."</a></li>";
    			}
    		}
    	}
    	$pageStr = $upPage.$linkPage.$downPage;
    	//$pageStr = "<div class='page'>".$upPage."&nbsp;".$theFirst."&nbsp;".$linkPage."&nbsp;".$theEnd."&nbsp;".$downPage."</div>";
    	//$pageStr .= "<div class='page_tj'>本类别&nbsp;共&nbsp;<span>".$this->totalRows."</span>&nbsp;条信息&nbsp;当前是第&nbsp;".$this->nowPage."&nbsp;页（共<span>".$this->totalPages."</span>页）</div>";
    	return $pageStr;
    }
    

}