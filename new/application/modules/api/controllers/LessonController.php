<?php
/**
 *  财猪课堂
 * @author Mark
 *
 */
class Api_LessonController extends Action_Api
{
	/**
	 * 获取课堂模块
	 */
	public function getModuleAction()
	{
		$moduleModel = new Model_LessonModule();
		$modules = $moduleModel->getAllModule(1,array('ModuleID','ModuleName','ModulePic'));
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$modules));
	}
	
	/**
	 * 获取课程列表
	 */
	public function getLessonListAction()
	{
		$moduleID = intval($this->_request->getParam('moduleID',0));
		$lastID = intval($this->_request->getParam('lastID',0));
		$pageSize = intval($this->_request->getParam('pagesize',30));
		
		$lessModel = new Model_Lesson();
		
		$select = $lessModel->select()->from('lessons',array('LessonID','LessonTitle','LessonPic'));
		if($moduleID > 0){
			$select->where('ModuleID = ?',$moduleID);
		}
		
		if($lastID > 0){
			$select->where('LessonID < ?',$lastID);
		}
		$select->order('LessonID desc');
		$result = $select->limit($pageSize)->query()->fetchAll();
		
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result));
	}
	
	/**
	 * 获取课程信息
	 */
	public function getLessonInfoAction()
	{
		$lessonID = intval($this->_request->getParam('lessonID',0));
		$lessonModel = new Model_Lesson();
		$select = $lessonModel->select()->from('lessons',array('LessonID','LessonTitle','LessonPic','LessonDes','LessonType','ViewCount'));
		$select->where('LessonID = ?',$lessonID);
		$lessonInfo = $select->query()->fetch();
		
		if(!empty($lessonInfo)){
			$classModel = new Model_LessonClass();
			$lessonInfo['ClassLists'] = $classModel->getClassByLesson($lessonID);
			
            $lessonInfo['ViewCount'] = $lessonInfo['ViewCount']*2;
            
			$favoriteModel = new Model_Favorite();
			$lessonInfo['IsFavorite'] = 0;
			if($this->isLogin()){
				$info = $favoriteModel->getInfo(3, $lessonID, $this->memberInfo->MemberID);
				if(!empty($info)){
					$lessonInfo['IsFavorite'] = 1;
				}
			}
			$lessonModel->increaseViewCount($lessonID);
		}
		
		$this->returnJson(parent::STATUS_OK,'',$lessonInfo);
	}
	
	/**
	 * 获取模块下的课时列表
	 */
	public function getLessonClassAction()
	{
		$moduleID = intval($this->_request->getParam('moduleID',0));
		$lastID = intval($this->_request->getParam('lastID',0));
		$pageSize = intval($this->_request->getParam('pagesize',30));
		
		$lessonClassModel = new Model_LessonClass();
		$select = $lessonClassModel->select()->setIntegrityCheck(false);
		$select->from(array('lc'=>'lesson_class'),array('ClassID','ClassTitle','ClassPic','ClassLink','IsNative'));
		$select->joinInner(array('l'=>'lessons'), "l.LessonID = lc.LessonID",array('LessonID','LessonType'))->where('lc.Status = 1');
		
		if($moduleID > 0){
			$select->joinInner(array('lm'=>'lesson_modules'), 'lm.ModuleID = l.ModuleID');
			$select->where('lm.ModuleID = ?',$moduleID);
		}
		
		if($lastID > 0){
			$select->where('lc.ClassID < ?',$lastID);
		}
		
		$select->order('lc.ClassID desc')->limit($pageSize);
		
		$result = $select->query()->fetchAll();

		$moduleInfo = new stdClass();
		if(!empty($moduleID)){
			$moduleModel = new Model_LessonModule();
			$moduleInfo = $moduleModel->getModuleInfo($moduleID,array('ModuleID','ModuleName','ModulePic'));
		}
		
		$this->returnJson(parent::STATUS_OK,'',array('Rows'=>$result,'ModuleInfo'=>$moduleInfo));
	}
	
	/**
	 * 获取课时信息
	 */
	public function getClassInfoAction()
	{
		try{
			$classID = intval($this->_request->getParam('classID',0));
			$lessonClassModel = new Model_LessonClass();
			$info = $lessonClassModel->getClassInfo($classID);
			if(empty($info)){
				throw new Exception('不存在该课时');
			}
			$lessonModel = new Model_Lesson();
			$lessonModel->increaseViewCount($info['LessonID']);
			$this->returnJson(parent::STATUS_OK,'',$info);
		}catch(Exception $e){
			$this->returnJson(parent::STATUS_FAILURE,$e->getMessage());
		}
		
	}
}