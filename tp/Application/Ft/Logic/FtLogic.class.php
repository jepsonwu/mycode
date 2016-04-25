<?php
namespace Ft\Logic;
use Think\Model;
use Org\CreatAccount;
use Org\Sms;
class FtLogic extends Model {
		/**
		 * 注册时发送验证码
		 * @param int mobile
		 * @return json
		 */
		public function send_verifyCode($mobile){
				// 手机号码为空
				if (empty($mobile)){
						json_echo(1,'请输入手机号码！');
						exit;
				}
				// 手机号码验证
				if(!preg_match("/1[34578]{1}\d{9}$/",$mobile)){
					json_echo(1,'请正确输入手机号码！');
					exit;
				}
				// 生成验证码
				$verifyCode = \Org\Util\String::randString ( 6, 1 );
				// 验证码与手机号存入session
				session('REGISTER_VERIFY_CODE', $verifyCode);
				session('REGISTER_MOBILE', $mobile);
				
				$sms=new Sms();
				//判断是否第一次生成
				$model = M("Users");
				$rs = $model->where("mobile=$mobile")->find(); //手机号已注册激活
				
				if ($rs) {
					json_echo (1, '该手机已被注册！');
					exit;
				} else {
					$sms->sendSMS($mobile, array($verifyCode),17192); //发送验证码短信
					json_echo(0,$verifyCode);
				}
		}
		
		/**
		 * 注册时发送验证码(Test)
		 */
		public function send_verifyCode_test($mobile){
			// 手机号码为空
			if (empty($mobile)){
				json_echo(1,'请输入手机号码！');
				exit;
			}
			// 手机号码验证
			if(!preg_match("/1[34578]{1}\d{9}$/",$mobile)){
				json_echo(1,'请正确输入手机号码！');
				exit;
			}
			// 生成验证码
			$verifyCode = \Org\Util\String::randString ( 6, 1 );
			// 验证码与手机号存入session
			session('REGISTER_VERIFY_CODE', $verifyCode);
			session('REGISTER_MOBILE', $mobile);
		
			$sms=new Sms();
			//判断是否第一次生成
			$model = M("Users");
			$rs = $model->where("mobile=$mobile")->find(); //手机号已注册激活
		
			if ($rs) {
				json_echo (1, '该手机已被注册！');
				exit;
			} else {
				$sms->sendSMS($mobile, array($verifyCode),17192); //发送验证码短信
				json_echo(0,$verifyCode);
			}
		}
		
		/**
		 * 修改密码时发送验证码
		 * @param int mobile
		 * @return json
		 */
		public function password_verifyCode($mobile){
			//
			if (empty($mobile)){
				json_echo(1,'请输入手机号码！');
				exit;
			}
			//
			if(!preg_match("/1[34578]{1}\d{9}$/",$mobile)){
				json_echo(1,'请正确输入手机号码！');
				exit;
			}
			
			// 验证手机号是否存在
			$result = M('Users')->where('mobile=' . $mobile)->find();
			if (empty($result)) {
				json_echo(1,'用户不存在');
				exit;
			} else {
				// 生成验证码
				$verifyCode = \Org\Util\String::randString ( 6, 1 );
				session('PASSWORD_VERIFY_CODE', $verifyCode);
				$sms=new Sms();
				$sms->sendSMS($mobile, array($verifyCode),17192);
				json_echo(0,$verifyCode);
			}
		}
		
		/**
		 * 验证码认证
		 * @param int mobile
		 * @param int verifyCode
		 * @return bool
		 */
		public function verify_verifyCode($mobile,$verifyCode){
			$rs=M("Students")->where("mobile=$mobile")->field('verifyCode')->find();
			if(!$rs){
				return false;
			}
			if($rs['verifyCode']!=$verifyCode){
				return false;
			}
			return true;
		}
		/**
		 * 登录
		 * @param mobile int 
		 * @param password string
		 * @return json 
		 */
		public function login($mobile,$password) {
			$user_info = M("Users")
				->where("mobile=$mobile")
				->field("id,password,name,avatar,gender,location,token")
				->find();
			
			if (!$user_info) {
				json_echo(1,'用户不存在！');
				exit;
			}
			
			if ($user_info['password']!=pwd_hash($password)) {
				json_echo(1,'密码错误！');
				exit;
			}

			// 把学生ID存入session
			session('user_id', $user_info['id']);
			
			// 学生详细信息
			$user_detail_info = M("UserDetail")->where("user_id=" . $user_info['id'])->field("birth,mail,job,is_push")->find();

			$result = array(
					'userid' => $user_info['id'],
					'usertoken' => $user_info['token'],
					'isPush' => $user_detail_info['is_push'],
					'avatar' => $user_info['avatar'],
					'information' => array(
							'nickname' => $user_info['name'],
							'gender' => $user_info['gender'],
							'birth' => $user_detail_info['birth'],
							'location' => $user_info['location'],
							'mail' => $user_detail_info['mail'],
							'job' => $user_detail_info['job']
					)
			);	
			json_echo(0,'登录成功！',$result);
		}
		
		/**
		 * 教师登录
		 * @param username string
		 * @param password string
		 * @return array
		 */
		public function teacherLogin($username,$password) {
			$rs = M("Teachers")
			->where("username='{$username}'")
			->field("id,password,name,avatar,countOfGate,voipAccount,voipPassword,subAccountSid,subToken")
			->find();
			if (!$rs) {
				json_echo(1,'用户不存在！');
				exit;
			}
				
			if ($rs['password']!=pwd_hash($password)) {
				json_echo(1,'密码错误！');
				exit;
			}
				
			unset($rs['password']);
				
			return $rs;
		}

		/**
		 * 保存数据
		 * @param $table string 表 
		 * @param $map array 存储条件
		 * @param $data array 存储内容
		 * @return json
		 */
		public function info_save($table,$map,$data) {
			$model = M($table);
			//如果没有数据，则新增数据
			if (!$model->where($map)->find()) {
				$model->add($map);
			}
			
			if ($model->where($map)->save($data) === false) {
				json_echo(1,'fail');
				exit;
			}
			//json_echo(0,'success');
		}

		public function thirdLogin_save($table,$map,$data){
			$model = M($table);
			
			//如果没有数据，则新增数据
			if (!$model->where($map)->find()) {
				$sid=$model->add($map);

				$api_rs=$this->create_voipAccount($sid.time());
				$api_rs=json_decode(json_encode($api_rs),true);
				$data['voipAccount']=$api_rs['SubAccount']['voipAccount'];
				$data['voipPassword']=$api_rs['SubAccount']['voipPwd'];
				$data['subAccountSid']=$api_rs['SubAccount']['subAccountSid'];
				$data['subToken']=$api_rs['SubAccount']['subToken'];
			}
			
			if ($model->where($map)->save($data) === false) {
				json_echo(1,'fail');
				exit;
			}
		}
		
		/**
		 * 获取信息
		 * @param $table string 表 
		 * @param $map array 查询条件
		 * @param $field array 返回内容
		 * @return json
		 */
		public function info_get($table,$map,$field) {
			$model = M($table);
			
			$count = $model->where($map)->count();
			
			if ($count == 1) {
				$rs = $model->where($map)->field($field)->find();
			} else {
				$rs = $model->where($map)->field($field)->select();
			}
			if (!$rs) {
				json_echo(1,'暂无记录');
				exit;
			}
			
			json_echo(0,'success',$rs);
		}
		
		/**
		 * [get_CateCourse 获取某一分类的所有大课程(api变动)]
		 * @param $table string 表 
		 * @param $map array 查询条件
		 * @param $field array 返回内容
		 * @return json
		 */
		public function get_CateCourse($table, $map, $field, $page, $listRows, $order){
			$model = M($table);
			
			if ($page > 0) {
				$rs = $model->where($map)->field($field)->page($page, $listRows)->order($order)->select();
			} else {
				$rs = $model->where($map)->field($field)->order($order)->select();
			}
			
			if (!$rs) {
				json_echo(1,'暂无记录');
				exit;
			}
			foreach($rs as $key => $val){
				$result['topics'][$key]['topicId']=$val['id'];
				$result['topics'][$key]['name']=$val['chName'];
				$result['topics'][$key]['directory']=$val['enName'];

				$result['topics'][$key]['tag']=rtn_str_tag($val['categoryIds']);
				$result['topics'][$key]['pic']=arrayToStr($val['pic'],UPLOADS_URL.'/Uploads/',$val['dirname']);
				$result['topics'][$key]['intro']=$val['intro'];
			}
			json_echo(0,'success',$result);
		}

		/**
		 * 获取信息老师信息
		 * @param $table string 表 
		 * @param $map array 查询条件
		 * @param $field array 返回内容
		 * @return json
		 */
		public function th_info_get($table,$map,$field) {
			$model = M($table);
			
			$count = $model->where($map)->count();
			
			if ($count == 1) {
				$rs = $model->where($map)->field($field)->find();
			} else {
				$rs = $model->where($map)->field($field)->limit(2)->select();
			}
			if (!$rs) {
				json_echo(1,'暂无记录');
				exit;
			}
			foreach ($rs as $key => $value) {
				$th_infos['ths_infos'][$key]=$value;
			}
			json_echo(0,'success',$th_infos);
		}

		/**
		 * 删除数据
		 * @param $table string 表
		 * @param $map array 查询条件
		 * @return json
		 */
		public function info_delete($table, $map) {
			$result = M($table)->where($map)->delete();
			if (!$result) {
				json_echo(1,'删除失败！');
				exit;
			}
			
			//json_echo(0,'success');
		}

		/**
		 * [create_voipAccount 云通讯，创建子账号]
		 * @param  [type] $friendlyName [子账户名称]
		 * @return [type]               [description]
		 */
		public function  create_voipAccount($friendlyName){
			$rest=new CreatAccount();
			return $rest->creat_account($friendlyName);
		}
		
		
}