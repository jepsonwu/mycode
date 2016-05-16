<?php
/**
 * 日常相关统计
 * @author Jeff
 */

class Admin_DayStaticController extends DM_Controller_Admin {

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
		$data_type = $this->_getParam('searchType',1);
		$flag = intval($this->_getParam('flag',1));
		$staticModel = new Model_DayStatic();
		if($data_type == 1){
			$select = $staticModel->select()->from('day_static','*');
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
			$select = $staticModel->select()->from('day_static',array('ActivatNum'=>'sum(ActivatNum)','RegisterNum'=>'sum(RegisterNum)',
					'StartMemberNum'=>'sum(StartMemberNum)','StartNum'=>'sum(StartNum)'));
			$select->where("CreateDate >= ?", $start_date);
			$select->where("CreateDate <= ?", $end_date);
			$total =1 ;
			$results = $staticModel->fetchAll($select)->toArray();
			foreach($results as &$val){
				$val['CreateDate'] = $start_date.' / '.$end_date;
			}
		}
		if($flag ==1){
			$this->escapeVar($results);
			$this->_helper->json(array('total'=>$total,'rows'=>$results));
		}elseif($flag == 2){//导出csv
			$csv = new Model_CExcel();
			$filename = date("Ymd")."日常统计";
			if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
				$filename = urlencode($filename);
			header("Content-type:text/html ;charset=utf-8");
			$title = array('时间', '激活量', '注册量', '启动人数', '启动次数');
			$data = array();
			$data[] = $title;
			if (!empty($results)) {
				foreach ($results as $row) {
					$sub = array();
					$sub[] = $row['CreateDate'];
					$sub[] = $row['ActivatNum'];
					$sub[] = $row['RegisterNum'];
					$sub[] = $row['StartMemberNum'];
					$sub[] = $row['StartNum'];
					$data[] = $sub;
				}
			}
			$csv->exportCSV($data,$filename);die();
		}else{//导出excel
			DM_PHPExcel::init();
			//加载模板文件
			$inputFileType = 'Excel5';
			$inputFileName = APPLICATION_PATH.'/data/exceltpl/dayStatic.xls';
			$objPHPExcelReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objPHPExcelReader->load($inputFileName);
			//获取活动sheet
			$objPHPExcel->setActiveSheetIndex(0);
			$activeSheet  = $objPHPExcel->getActiveSheet();
			//第一行为标题，数据从第二行开始写入
			 
			foreach ($results as $key=>$item){
				$activeSheet->setCellValue('A'.($key+2), @$item['CreateDate']);
				$activeSheet->setCellValue('B'.($key+2), @$item['ActivatNum']);
				$activeSheet->setCellValue('C'.($key+2), @$item['RegisterNum']);
				$activeSheet->setCellValue('D'.($key+2), @$item['StartMemberNum']);
				$activeSheet->setCellValue('E'.($key+2), @$item['StartNum']);
			}
			
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$filename = date("Ymd")."日常统计.xls";
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