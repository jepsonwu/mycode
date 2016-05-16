<?php

/**
 * @author Bise
 * @copyright  
 * 扩展生成Excel报表
 *
 */
class Model_CExcel {
    /**
     * 导出为CSV
     * @param array $sheetdata 数据
     * @param string $filename 文件名
     */
    public static function exportCSV($sheetdata, $filename='tmp'){
        $filename = $filename.'.csv';//文件名
        //$filename = Common::convert2gbk($filename);
        $csvStr = '';

        if(!empty($sheetdata)){
            foreach($sheetdata as $sheet){
                $csvStr .= implode(',',$sheet)."\n";
            }
        }else{
            $csvStr = "无记录";
        }
        $csvStr = self::convert2gbk($csvStr);
      	header("Content-type: text/csv charset=utf-8");
        header("Content-Disposition: attachment; filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $csvStr;
    }


    /**
     * 导出为EXCEL
     * @param array $sheetdata 数据
     * @param string $filename 文件名
     */
    public static function exportXLS($sheetdata, $filename='tmp'){
        $filename = (preg_match("/MSIE/is",$_SERVER['HTTP_USER_AGENT']) > 0) ? urlencode($filename.'.xls') : $filename.'.xls';
        $sheet = Bls();//Bls为服务器端扩展.
        $i = 0;
        foreach($sheetdata as $k => $v){//循环sheet
            $sheet->sheet($k,$i);//sheet标题,$i从0开始的sheet索引
            $j = 0;
            foreach($v as $_v){//循环行
                $k = 0;
                foreach ($_v as $__v){//循环列
                    $sheet->cell($j,$k,$__v,0,$i);
                    $k++;
                }
                $j++;
            }
            $i++;
        }

        $tmp = tempnam(__DIR__.'/logs','xls');
        $sheet->write($tmp);
        $sheet->close();
        unset($sheetdata);
        $sheet = null;
        header('Content-Description: File Transfer');
        header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream', false);
        header('Content-Type: application/download', false);
        header('Content-Type: application/vnd.ms-excel', false);
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Content-Transfer-Encoding: binary');
        echo file_get_contents($tmp);
        @unlink($tmp);
    }

    /*
     * @desc根据数据输出
     * @param array $sheetdata 格式为 array('sheetname'=>array())
     * 'sheetname'=>array(
             array('时间(时:分:秒)','百分比(%)','消费(￥)','日期(年-月-日)','创建时间(年-月-日 时:分:秒)','备注'),
             array('#t#100','#%#23.4','#$#1234','#d#'.(strtotime('2010-10-11')/86400+70*365+20),'#T#'.(strtotime()/86400+70*365+20),'备注信息')
         )
     * @param string $filename 输出的文件名
     */
    public static function exportData(array $sheetdata,$filename = 'tmp'){
        if(!extension_loaded('bise'))
            self::exportCSV($sheetdata, $filename);
        else
            self::exportXLS($sheetdata, $filename);

        exit;
    }

    /**
     * @desc 格式化输出数据{时间、百分率}
     * @param $indicator 指标名称
     * @param $data 处理数据
     */
    public static function formatData($indicator,$data){
        $dateArr = array('rdate');
        $datetimeArr = array();
        $timeArr = array();
        $rateArr = array('clickrate');
        $rmbArr = array('cost','sumcost','avgcost','budget','cpm');
        if(in_array($indicator, $timeArr)){
            $formatData = is_numeric($data)?'#t#'.$data:$data;//处理时间
        }
        elseif(in_array($indicator, $rateArr)){
            $formatData = is_numeric($data)?'#%#'.$data:$data;//处理百分数
        }
        elseif(in_array($indicator, $rmbArr)){
            $formatData = is_numeric($data)?'#$#'.$data:$data;
        }
        elseif(in_array($indicator, $dateArr)){
            $formatData = '#d#'.(strtotime($data)/86400+70*365+20);//处理日期
        }
        elseif(in_array($indicator, $datetimeArr)){
            $formatData = '#T#'.(strtotime($data)/86400+70*365+20);//处理日期+时间
        }
        else{
            $formatData = is_numeric($data)?intval($data):$data;
        }
        return $formatData;
    }
    
    /**
     * @desc UTF-8转换成GB2312
     * @desc 处理字符串或者一维数组
     * @param $str
     */
    public static function convert2gbk($str){
    	if(is_array($str)){
    		foreach($str as $r){
    			$arr[]=mb_convert_encoding($r,'GBK','utf-8');
    		}
    		return $arr;
    	}else{
    		return mb_convert_encoding($str,'GBK','utf-8');
    	}
    }


}

?>