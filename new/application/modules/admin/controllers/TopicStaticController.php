<?php
/**
 * 话题相关统计
 * @author Jeff
 */

class Admin_TopicStaticController extends DM_Controller_Admin {
	
	public function indexAction()
	{
		
	}
	
	public function listAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$pageIndex = $this->_getParam('page',1);
		$pageSize = $this->_getParam('rows',50);
		
		$start_date= $this->_getParam('start_date');
		$end_date= $this->_getParam('end_date');
		$data_type = $this->_getParam('dataType',1);
		$flag = $this->_getParam('flag',1);
		
		$staticModel = new Model_Topic_Static();
		if($data_type == 1){
			$select = $staticModel->select()->from('topic_static','*');
			if(empty($start_date) && empty($end_date)){
				$select->where("CreateDate = ?", date('Y-m-d'));
			}
			if(!empty($start_date)){
				$select->where("CreateDate >= ?", $start_date);
			}
			
			if(!empty($end_date)){
				$select->where("CreateDate <= ?", $end_date);
			}
			//获取sql
			$countSql = $select->__toString();
			$countSql = preg_replace('/SELECT(.*?)FROM/', 'SELECT COUNT(*) AS total FROM', $countSql);
			
			//总条数
			$total = $staticModel->getAdapter()->fetchOne($countSql);
			
			//排序
			$select->order("SID desc");
			
			$select->limitPage($pageIndex, $pageSize);
			$results = $staticModel->fetchAll($select)->toArray();
			//列表
			
		}else{
			$select = $staticModel->select()->from('topic_static',array('NewFollowNum'=>'sum(NewFollowNum)','NewTopicNum'=>'sum(NewTopicNum)',
			'NewViewNum'=>'sum(NewViewNum)','NewReplyNum'=>'sum(NewReplyNum)','NewShareNum'=>'sum(NewShareNum)'));
			$select->where("CreateDate >= ?", $start_date);
			$select->where("CreateDate <= ?", $end_date);
			$total =1 ;
			$results = $staticModel->fetchAll($select)->toArray();
			foreach($results as &$val){
				$val['CreateDate'] = $start_date.' / '.$end_date;
			}
		}
		if($flag == 1){
			$this->escapeVar($results);
			$this->_helper->json(array('total'=>$total,'rows'=>$results));
		}elseif($flag == 2){//导出csv
			$csv = new Model_CExcel();
			$filename = date("Ymd")."话题统计";
			if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
				$filename = urlencode($filename);
			header("Content-type:text/html ;charset=utf-8");
			$title = array('时间', '新增关注数 ', '新增话题数 ', '新增观点数 ', '评论数 ' , '分享数 ');
			$data = array();
			$data[] = $title;
			if (!empty($results)) {
				foreach ($results as $row) {
					$sub = array();
					$sub[] = $row['CreateDate'];
					$sub[] = $row['NewFollowNum'];
					$sub[] = $row['NewTopicNum'];
					$sub[] = $row['NewViewNum'];
					$sub[] = $row['NewReplyNum'];
					$sub[] = $row['NewShareNum'];
					$data[] = $sub;
				}
			}
			$csv->exportCSV($data,$filename);die();
		}else{//导出excel
			DM_PHPExcel::init();
			//加载模板文件
			$inputFileType = 'Excel5';
			$inputFileName = APPLICATION_PATH.'/data/exceltpl/topicStatic.xls';
			$objPHPExcelReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objPHPExcelReader->load($inputFileName);
			//获取活动sheet
			$objPHPExcel->setActiveSheetIndex(0);
			$activeSheet  = $objPHPExcel->getActiveSheet();
			//第一行为标题，数据从第二行开始写入
			 
			foreach ($results as $key=>$item){
				$activeSheet->setCellValue('A'.($key+2), @$item['CreateDate']);
				$activeSheet->setCellValue('B'.($key+2), @$item['NewFollowNum']);
				$activeSheet->setCellValue('C'.($key+2), @$item['NewTopicNum']);
				$activeSheet->setCellValue('D'.($key+2), @$item['NewViewNum']);
				$activeSheet->setCellValue('E'.($key+2), @$item['NewReplyNum']);
				$activeSheet->setCellValue('F'.($key+2), @$item['NewShareNum']);
			}
			
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$filename = date("Ymd")."话题统计.xls";
			if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
				$filename = urlencode($filename);
			header("Content-type:text/html ;charset=utf-8");
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
			header("Content-Type:application/force-download");
			header("Content-Type: application/vnd.ms-excel;");
			header("Content-Type:application/octet-stream");
			header("Content-Type:application/download");
			header("Content-Disposition:attachment;filename=".$filename);
			header("Content-Transfer-Encoding:binary");
			$objWriter->save("php://output");
		}
	}
}