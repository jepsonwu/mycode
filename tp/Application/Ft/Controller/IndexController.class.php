<?php
namespace Ft\Controller;
use Ft\Controller\CommonController;
class IndexController extends CommonController {
			public function _initialize(){
				/* if(ACTION_NAME =='login' or ACTION_NAME =='reg' or ACTION_NAME =='send_verifyCode' ){
					
				}else{
					if(empty($_SESSION['stdNow'])){
						json_echo(1,'没有登录！');
						exit();
					}
				} */
				//$data=I("param.");
			}
			
			// 首页
			public function index() {
				$this->display ();
				echo 'sss';
				ft_err("测试可用");
			}	
			
			//测试注册---------------
			public function reg() {
				$this->display();
			}
			
			//测试登录---------------
			public function login1() {
				$this->display();
			}
			
			//测试修改密码---------------
			public function changePwd() {
				$this->display();
			}
			//测试完善用户信息---------------
			public function updateInfo() {
				$this->display();
			}
			
			//注册时发送验证码
			public function send_verifyCode() {
				$data = I("param.");
				//
				D("Ft","Logic")->send_verifyCode($data['mobile']);
			}
			
			//注册时发送验证码(Test)
			public function send_verifyCode_test() {
				$data = I("param.");
				//
				D("Ft","Logic")->send_verifyCode_test($data['mobile']);
			}
			
			//注册 存入信息(Test)
			public function register_test() {
				$data = I("param.");
			
				if (empty($data)) {
					json_echo(1, '请填写注册信息！');
					exit;
				}
				//
				$stu_data['create_time'] = time();
				//
				$lgc = D("Ft","Logic");
			
				//验证码验证
				$verify_code = session('REGISTER_VERIFY_CODE');
				$mobile = session('REGISTER_MOBILE');
				if ($verify_code != $data['verify_code']) {
					json_echo(1,'验证码错误');
					exit;
				}
				// 手机号码验证
				if ($mobile != $data['mobile']) {
					json_echo(1,'手机号码不正确');
					exit;
				}
			
				//成功后存储信息
				$stu_data['password'] = pwd_hash($data['password']);
				$map['mobile'] = $data['mobile'];
			
				//储存学生基本信息表
				$lgc->info_save('Users', $map, $stu_data);
			
				json_echo(0, '注册成功！');
			}
			
			//修改密码时发送验证码
			public function password_verifyCode() {
				$data = I("param.");
				//
				D("Ft","Logic")->password_verifyCode($data['mobile']);
			}
			
			//注册 存入信息
			public function register() {
				$data = I("param.");
				
				if (empty($data)) {
					json_echo(1, '请填写注册信息！');
					exit;
				}
				//
				$stu_data['create_time'] = time();
				//
				$lgc = D("Ft","Logic");
				
				//验证码验证
				$verify_code = session('REGISTER_VERIFY_CODE');
				$mobile = session('REGISTER_MOBILE');
				if ($verify_code != $data['verifyCode']) {
					json_echo(1,'验证码错误');
					exit;
				}
				// 手机号码验证
				if ($mobile != $data['mobile']) {
					json_echo(1,'手机号码不正确');
					exit;
				}
				
				//成功后存储信息
				$stu_data['password'] = pwd_hash($data['password']);
				$map['mobile'] = $data['mobile'];	
				
				//储存学生基本信息表
				$lgc->info_save('Users', $map, $stu_data);

				json_echo(0, '注册成功！');
			}
			
			//登录
			public function login() {
				$data = I("param.");
				//
				if ( empty($data['mobile']) or empty($data['password'])){
					json_echo(1,'请输入手机号码或者密码！');
					exit;
				}
				//
				D("Ft","Logic")->login($data['mobile'],$data['password']);
			}
			
			//头像上传
			public function saveAvatar() {
				//
				$data = I('param.');
				//
				if ($_FILES['file']['error'] > 0 or empty($data['mobile'])) {
					 json_echo(1,'fail');
					exit();
				}
				//
				$rs = M("Students")->where("mobile=".$data['mobile'])->field('id')->find();
				if(!$rs){
					json_echo(1,'fail');
					exit();
				}
				//
				$id=$rs['id'];
				//存储头像
				$path = './Uploads/Ft/';
				$virtualPath = $path.$id.'.jpg';
				move_uploaded_file($_FILES['file']["tmp_name"], $virtualPath);
				$result["avatar"] = "http://www.abc360.com/".$id.'.jpg';
				//
				json_echo(0,'success',$result);
			}
			
			//APP检查更新
			public function appUpdate() {
				$data = I('param.');
				//
				$app['verCode']= C('verCode');
				$app['verInfo']= C('verInfo');
				//
				json_echo(0,'success',$app);
			}
			
			//用户反馈
			public function feedback() {
				$data = I('param.');
				$user_id = session('user_id');
				if (empty($user_id)) {
					json_echo(1,'请重新登录！');
					exit;
				}
				if (empty($data["content"])) {
					json_echo(1,'请输入内容！');
					exit;
				}
				
				$map=array('create_time' =>time(),'content' =>$data['content'],'sid' =>$user_id);
				
				if (M("Feedback")->add($map)) {
					json_echo(0,'反馈成功！');
				}
			}
			
			// 微信第三方登录接口
			public function thirdLogin() {
				$data = I('param.');
				if (empty($data)) {
					json_echo(1, '微信接口错误！');
					exit;
				}
				$lgc = D("Ft","Logic");	
				$data['create_time'] = time();
				$map['token'] = $data['token'];
				D('Ft', 'Logic')->thirdLogin_save('Students', $map, $data);
				
				$rs = M('Students')->where($map)->find();
				if(!$rs['voipAccount']){
					$api_rs=D('Ft', 'Logic')->create_voipAccount($rs['id'].time());
					$api_rs=json_decode(json_encode($api_rs),true);
					//voip账号信息入库
					$voip_info=array('voipAccount' => $api_rs['SubAccount']['voipAccount'],'voipPassword' => $api_rs['SubAccount']['voipPwd'],'subAccountSid' => $api_rs['SubAccount']['subAccountSid'],'subToken' => $api_rs['SubAccount']['subToken']);
					$lgc->info_save('Students', array('id'=>$rs['id']), $voip_info);
					$rs['voipAccount']=$voip_info['voipAccount'];
					$rs['voipPassword']=$voip_info['voipPassword'];
					$rs['subAccountSid']=$voip_info['subAccountSid'];
					$rs['subToken']=$voip_info['subToken'];
				}

				$result = array(
						'userid' => $rs['id'],
						'usertoken' => $rs['token'],
						'isPush' => $rs['isPush'],
						'avatar' => $rs['avatar'],
						'information' => array(
								'nickname' => $rs['nickname'],
								'gender' => $rs['gender'],
								'birth' => $rs['birth'],
								'location' => $rs['location'],
								'mail' => $rs['mail'],
								'job' => $rs['job']
						),
						'voipAccount' => $rs['voipAccount'],
						'voipPassword' => $rs['voipPassword'],
						'subAccountSid' => $rs['subAccountSid'],
						'subToken' => $rs['subToken']
				);	
				json_echo(0, 'success', $result);
			}
			
			// 第三方用户绑定手机
			public function thirdBind() {
				$data = I('param.');
				$moblie = $data['mobile'];
				$token = $data['token'];
				
				if (empty($token)) {
					json_echo(1, 'token丢失！');
					exit;
				}
				
				if (empty($moblie)) {
					json_echo(1, '请输入手机号码！');
					exit;
				}
				
				if(!preg_match("/1[34578]{1}\d{9}$/",$mobile)){
					json_echo(1,'请正确输入手机号码！');
					exit;
				}
				
				// 发送验证码
				$verifyCode = \Org\Util\String::randString ( 6, 1 );
				$data['verifyCode'] = $verifyCode;
				
				$rs = M('Students')->where("mobile=$mobile")->find();
				$lgc = D('Ft', 'Logic');
				
				if (!$rs) { // 手机号未注册
					$lgc->info_save('Students', "token=$token", $data);
				} else { // 手机号已注册
					$lgc->info_save('Students', "mobile=$moblie", $data);
				}
				
				json_echo(0, 'success');
			}
			
			//获取用户学习信息
			public function getStudyInfo() {
				$data = I('param.');
				
				$map['sid'] = $data['id'];
				
				$lgc = D('Ft', 'Logic');
				
				$info = $lgc->info_get('StudentStudyInfo', $map, 'points, level, totaldays, totalcourses');
			}
			
			//更新用户学习信息
			public function updateStudyInfo() {
				$data = I('param.');
				
				if (!$data['id']) {
					json_echo(1, '学生id丢失！');
					exit;
				}
				
				$map['sid'] = $data['id'];
			
				D('Ft', 'Logic')->info_save('StudentStudyInfo', $map, $data);
				
				json_echo(0,'success');
			}
			
			//获取用户历史课程
			public function getCourseHistory() {
				$data = I('param.');
				
				$map['sid'] = $data['sid'];
				
				//历史课程
				$courseHistory = D('Ft', 'Logic')->info_get('StudentCourseHistory', $map, 'cname');
			}
			
			//学生成绩上传
			public function gradeUpload() {
				$data = I('param');
				
				$map['sid'] = $data['sid'];
				$lgc = D('Ft', 'Logic');
				//
				$lgc->info_save('StudentCourseHistory', $map, $data);
				
				json_echo(0, '成绩上传成功！');
			}
			
			//完善用户详细信息
			public function infoUpdate() {
				$data = I('param.');
				$id = session('user_id');
				
				if ( empty($id) ) {
					json_echo(1, '学生id丢失！');
					exit;
				}
				
				// 存储图片
				// 二进制数据流
				$stream = file_get_contents('php://input') ? file_get_contents('php://input') : gzuncompress ( $GLOBALS ['HTTP_RAW_POST_DATA'] );
				// 若数据流不为空，则进行保存操作
				if ( ! empty($stream) ) {
					$path = BASE_PATH.'/Uploads/Ft/'.$id;
					if (!file_exists($path)){ 
						mkdir ($path);
					}
					$img_file = $path.'/'.time().$id.'.jpg';
					// 写入数据流，保存文件
					$file = fopen ( $img_file, 'w+' );
					$arr=explode('&', $stream);
					$arr=explode('=',$arr[0]);
					$str=urldecode($arr[1]);

					$byte = str_replace(' ','',$str);   //处理数据 
					$byte = str_ireplace("<",'',$byte);
					$byte = str_ireplace(">",'',$byte);
					$byte=pack("H*",$byte);      //16进制转换成二进制
					
					fwrite ( $file, $byte);
					fclose ( $file );
					
					// 返回文件地址
					$user_info['avatar'] = 'http://'.$_SERVER['HTTP_HOST'].'/Uploads/Ft/'.$id.'/'.time().$id.'.jpg';
				}
				
				if (isset($data['nickname'])) {
					$user_info['name'] = $data['nickname'];
				}
				if (isset($data['gender'])) {
					$user_info['gender'] = $data['gender'];
				}
				if (isset($data['location'])) {
					$user_info['location'] = $data['location'];
				}
				if (isset($data['birth'])) {
					$user_detail_info['birth'] = $data['birth'];
				}
				if (isset($data['job'])) {
					$user_detail_info['job'] = $data['job'];
				}
				if (isset($data['mail'])) {
					$user_detail_info['mail'] = $data['mail'];
				}
				// 实例化users表
				$user_model = M('Users');
				$user_model->startTrans();
				$user_result = $user_model->where('id=' . $id)->save($user_info);
				if ($user_result !== false) {
					$user_detail_model = M('UserDetail');
					$user_detail_result = $user_detail_model->where('user_id='. $id)->save($user_detail_info);
					if ($user_detail_result !== false) {
						$user_model->commit();
						$model = new \Think\Model();
						$user_query_info = $model->query("select ft_users.id,ft_users.name,ft_users.gender,
								ft_user_detail.birth,ft_users.location,ft_user_detail.job,ft_user_detail.mail,ft_users.avatar
								from ft_users left join ft_user_detail on ft_users.id=ft_user_detail.user_id where ft_users.id=".$id);
						json_echo(0, '修改信息成功！', $user_query_info);
					} else {
						$user_model->rollback();
						json_echo(1, '修改信息失败!');
					}
				} else {
					json_echo(1, '修改信息失败!');
					exit;
				}
			}
			
			
			//获取信息
			public function getInfo() {
				$data = I('param.');
				//
				$map['id'] = $data['sid'];
				//
				D("Ft","Logic")->info_get("Students",$map,'');
			}

			//找回密码
			public function  findPassword(){
				$data=I('param.');
				if (empty($data["new_password"]) || empty($data['mobile'])) {
					json_echo(1,'参数不完整!');
					exit;
				}	
				$map['mobile'] = $data['mobile'];
				$rs = M("Users")->where($map)->find();
				if($rs){
					$in['password'] = pwd_hash($data['new_password']);
					D("Ft","Logic")->info_save("Users", $map, $in);
					json_echo(0, '密码重置成功！');
				}else{
					json_echo(1,'该用户不存在!');					
				}
			}

			//修改密码
			public function changePassword() {
				$data = I('param.');
				//
				if (empty($data["old_password"]) or empty($data["new_password"])) {
					json_echo(1,'请填写密码！');
					exit;
				}
				//
				$map['id'] = session('user_id');
				if (empty($map['id'])) {
					json_echo(1,'请重新登录');
					exit;
				}
				//
				$rs = M("Users")->where($map)->field("password")->find();

				if ($rs['password'] == pwd_hash($data['old_password'])) {
					$in['password'] = pwd_hash($data['new_password']);
					D("Ft","Logic")->info_save("Users", $map, $in);
					json_echo(0, '修改密码成功！');
				} else {
					json_echo(1,'密码错误');
					exit;
				}
			}
			
			// 获取某一分类下所有课程
			public function getCateCourse() {
				$data = I('param.');
				
				if (($data['categoryid'])==null) {
					json_echo(0, '请输入分类id');
					exit;
				}
				
				// 分页
				$page = 0;
				$listRows = 6;
				if ($data['page'] > 0) {
					$page = intval($data['page']);
				}
				if ($data['listRows'] > 0) {
					$listRows = intval($data['listRows']);
				}
				
				$map['_string'] = "INSTR(CONCAT(',',categoryIds,','),',".$data['categoryid'].",')";
				
				// 是否为推荐课程
				if ($data['recommend'] === '1') {
					$map['recommend'] = intval($data['recommend']);
				}
			
				$field = 'id,chName,enName,categoryIds,pic,intro,dirname';
				
				// 默认按照时间降序排序
				$order = "create_time desc";
				 
				// $rs = D('Ft', 'Logic')->info_get('Courses', $map, $field);
				if(intval($data['categoryid'])==0)	$map=array();
				$rs = D('Ft', 'Logic')->get_CateCourse('Series', $map, $field, $page, $listRows, $order);
			}
			public function getCateCourseTest(){
				$data = I('param.');
				
				if (($data['categoryid'])==null) {
					json_echo(0, '请输入分类id');
					exit;
				}
				
				// 分页
				$page = 0;
				$listRows = 6;
				if ($data['page'] > 0) {
					$page = intval($data['page']);
				}
				if ($data['listRows'] > 0) {
					$listRows = intval($data['listRows']);
				}
				
				$map['_string'] = "INSTR(CONCAT(',',categoryIds,','),',".$data['categoryid'].",')";
				$map['status']=1;
				
				// 是否为推荐课程
				if ($data['recommend'] === '1') {
					$map['recommend'] = intval($data['recommend']);
				}
				
				$field = 'id,chName,enName,categoryIds,pic,intro,dirname';
				
				// 默认按照时间降序排序
				$order = "create_time desc";
				 
				// $rs = D('Ft', 'Logic')->info_get('Courses', $map, $field);
				if(intval($data['categoryid'])==0){
					$map=array();
					$map['status']=1;
				}
				if(intval($data['categoryid'])==-1){
					$map=array();
				}
				$rs = D('Ft', 'Logic')->get_CateCourse('Series', $map, $field, $page, $listRows, $order);
			}	
			// 获取所有分类
			public function getAllCate() {
				$rs = D('Ft', 'Logic')->info_get('Series', '', 'id, title');
			}
			
			// 获取某一大课中的所有小课
			public function getTopicCourse() {
				/* $data = I('param.');
				
				if (empty($data['topicid'])) {
					json_echo(0, '请输入大课id');
					exit;
				}
				
				$map['topicid'] = $data['topicid'];
				$field = 'id,title,en_title,img,path';
				
				$rs = D('Ft', 'Logic')->info_get('Courses', $map, $field); */
				
				$data = I('param.');
				
				$series_id = $data['topicId'];
				
				if ( empty($series_id) ) {
					json_echo(1, '大课id丢失！');
					exit;
				}
				
				$series = M('Series')->where('id = %d', $series_id)->find();
				
				if ( empty( $series ) ) {
					json_echo(1, '无此记录');
					exit;
				}
				
				$demos = M('Demos')->where('series_id = %d', $series_id)->field('id,chName,enName,dirname')->select();
				
				// if ( $demos ) {
				// 	foreach ( $demos as $key => $value ) {
				// 		$demos[$key] = array(
				// 				'courseId' => $value['id'],
				// 				'chName' => $value['chName'],
				// 				'enName' => $value['enName'],
				// 				'json' => 'task.json',
				// 				'resourcePath' => 'http://www.abc360.com/happygo/Uploads/'.$series['dirname'].'/'.$value['dirname'].'.zip',
				// 		);
				// 	}
				// }
				
				foreach ( $demos as $key => $value ) {
					$demos[$key] = array(
							'courseId' => $value['id'],
							'chName' => $value['chName'],
							'enName' => htmlspecialchars_decode($value['enName']),
							'json' => 'task.json',
							// 'resourcePath' => 'http://www.hapigou.com/Uploads/'.$series['dirname'].'/'.$value['dirname'].'.zip',
							'resourcePath' => UPLOADS_URL.'/Uploads/'.$series['dirname'].'/'.$value['dirname'].'.zip',
							'zipSize' =>  sprintf("%.2f",(filesize(BASE_PATH.'/Uploads/'.$series['dirname'].'/'.$value['dirname'].'.zip')/1024/1024)).'M'
					);
				}
				
				$series_arr = array(
						// "directory" => $series['directory'],
						// "name" => $series['name'],
						// "topicId" => $series['topicId'],
						// "tag" => $series['tag'],
						// "intro" => $series['intro'],
						"tasks" => $demos
				);
				json_echo(0, 'success', $series_arr);
			}
			
			// 学生选课
			public function selectCourse() {
				$data = I('param.');
				$map['sid'] = $data['sid'];
				$map['series_id'] = $data['topicid'];
				$lgc = D('Ft', 'Logic');
				if(!$data['sid'] || !$data['topicid']){
					json_echo(1,'参数不完整');exit();
				}
				$model = M('StudentCourse');
				$result = $model->where($map)->find();
				if ($result) {
					json_echo(1, '已收藏！');
				} else {
					$model->add($map);
					json_echo(0, '收藏成功！');
				}
			}
			
			// 学生取消选课
			public function cancelCourse() {
				$data = I('param.');
			
				$map['sid'] = $data['sid'];
				$map['series_id'] = $data['topicid'];
			
				D('Ft', 'Logic')->info_delete('StudentCourse', $map, $data);
				json_echo(0, '取消成功！');
			}
			
			// 同步数据
			public function sync() {
				$data = I('param.');
				//$data = json_decode($data);
				
				$lgc = D('Ft', 'Logic');
				$std_crs_his_mdl = M('StudentCourseHistory');
				$note_mdl = M('Notes');
				$sid = $data['sid'];
				
				if (empty($sid)) {
					json_echo(0, '学生id丢失！');
				}
				
				// 同步学生学习信息
				$study_info_arr = array(
						'sid' => $sid,
						'points' => $data['user']['points'],
						'level' => $data['user']['level'],
						'totaldays' => $data['user']['totaldays'],
						'totalcourses' => $data['user']['totalcourses'],
				);
				$lgc->info_save('StudentStudyInfo', 'sid='.$sid, $study_info_arr);
				
				// 同步学生上课信息
				$course_arrs = $data['history'];
				foreach ($course_arrs as $course_arr) {
					$course_data = array(
							'sid' => $sid,
							'tid' => $course_arr['tid'],
							't_title' => $course_arr['t_title'],
							't_entitle' => $course_arr['t_entitle'],
							't_tags' => $course_arr['t_tags'],
							't_img' => $course_arr['t_img'],
							't_intro' => $course_arr['t_intro'],
							'cid' => $course_arr['cid'],
							'c_title' => $course_arr['c_title'],
							'c_entitle' => $course_arr['c_entitle'],
							'c_tags' => $course_arr['c_tags'],
							'c_img' => $course_arr['c_img'],
							'c_json' => $course_arr['c_json'],
							'score' => $course_arr['score'],
					);
					$std_crs_his_mdl->add($course_data);
				}
						
				// 同步学习笔记信息
				$note_arrs = $data['note'];
				foreach ($note_arrs as $note_arr) {
					$note_data = array(
							'sid' => $sid,
							'cid' => $note_arr['cid'],
							'title' => $note_arr['title'],
							'introduce' => $note_arr['introduce'],
							'meaning' => $note_arr['meaning']
					);
					$note_mdl->add($note_data);
				}
				
				json_echo(0, 'success', $data);
				
			}
			
			// 评价老师
			public function commentTeacher() {
				$data = I('param.');
				
				if (empty($data)) {
					json_echo(1, '请输入评价信息');
					exit;
				}
				
				if ( false !== M('TeacherComments')->add($data)) {
					json_echo(0, 'success');
				} else {
					json_echo(1, 'fail');
				}
			}
			
			// 匹配在线未上课的教师
			public function match() {
				$data = I('param.');
				
				if (empty($data['sid'])) {
					json_echo(1, '学生id丢失！');
					exit;
				}
				
				$map['status'] = 1;
				$rs = D('Ft', 'Logic')->th_info_get('Teachers', $map, 'id,name,avatar,voipAccount,voipPassword,subAccountSid,subToken');
				// json_echo(0, 'success', $rs);
			}
			
}
?>