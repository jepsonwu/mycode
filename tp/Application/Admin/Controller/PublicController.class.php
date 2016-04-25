<?php

namespace Admin\Controller;

use Think\Controller;

class PublicController extends Controller {

	// 检查用户是否登录
	protected function checkUser() {
		if (! isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
			redirect( U ( MODULE_NAME.'/Public/relogin' ) );
		}
	}
	
	// 用户登录页面
	public function login() {
		//
		if (! isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
			$this->display ();
		} else {
			$this->redirect ( 'Index/index/' );
		}
	}
	
	// 重新登录
	public function relogin() {
		//
		$this->display ();
	}
	
	// 用户登出
	public function logout() {
		if (isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
			unset ( $_SESSION [C ( 'USER_AUTH_KEY' )] );
			unset ( $_SESSION [C ( 'ADMIN_AUTH_KEY' )] );
			unset ( $_SESSION ['bkgd'] );
			unset ( $_SESSION [C ( 'SEARCH_PARAMS' )] );
			unset ( $_SESSION [C ( 'SEARCH_PARAMS_STR' )] );
			unset ( $_SESSION [C ( 'SEARCH_PARAMS_PREV_STR' )] );
		}
		redirect ( PHP_FILE . C ( 'USER_AUTH_GATEWAY' ) );
	}
	
	// 登录检测
	public function checkLogin() {
		//
		$post = I('post.');
		//
		if (empty ( $post['account'] )) {
			$this->ajaxReturn ( make_rtn('帐号必须！') );
		} 
		elseif (empty ( $post['password'] )) {
			$this->ajaxReturn ( make_rtn('密码必须！') );
		} 
		elseif (empty ( $post['verify'] )) {
			$this->ajaxReturn ( make_rtn('验证码必须！') );
		}

		// 生成认证条件
		$map = array ();
		// 支持使用绑定帐号登录
		$map ['account'] = $post['account'];
		$map ["status"] = array ( 'gt', 0 );
		if ( session(C ( 'DB_PREFIX' ) . 'verify') != md5 ( $post['verify'] )) {
			$this->ajaxReturn ( make_rtn('验证码错误！') );
		}

		$authInfo = \Org\Util\Rbac::authenticate($map);

		// 使用用户名、密码和状态的方式进行认证
		if (empty ( $authInfo ) ) {
			$this->ajaxReturn ( make_rtn('帐号不存在或已禁用！') );
		} 
		else {
			if ($authInfo ['password'] != pwd_hash ( $_POST ['password'] )) {
				$this->ajaxReturn ( make_rtn('密码错误！') );
			}
			$_SESSION [C ( 'USER_AUTH_KEY' )] = $authInfo ['id'];
			$_SESSION ['bkgd'] ['email'] = $authInfo ['email'];
			$_SESSION ['bkgd'] ['ccid'] = $authInfo ['ccid'];
			$_SESSION ['bkgd'] ['nickname'] = $authInfo ['nickname'];
			$_SESSION ['bkgd'] ['adviser'] = $authInfo ['adviser'];
			$_SESSION ['bkgd'] ['department'] = $authInfo ['department'];
			$_SESSION ['bkgd'] ['bg_listRows'] = C ( "BG_LIST_ROWS" );
			$_SESSION ['bkgd'] ['roles'] = getUserRoles($authInfo ['id']);
			if ( in_array(1, $_SESSION ['bkgd'] ['roles'])) {
				$_SESSION [C ( 'ADMIN_AUTH_KEY' )] = true;
			}
			// 查看学员电话权限
			if ( $_SESSION [C ( 'ADMIN_AUTH_KEY' )]
					|| in_array(101, $_SESSION ['bkgd'] ['roles'] )  ) {
				$_SESSION ['bkgd'] ['show_std_mobile'] = 1;
			}
			// 保存登录信息
			$data = array (
					'id' => $authInfo ['id'],
					'last_login_time' => time (),
					'login_count' => array('exp', 'login_count+1'),
					'last_login_ip' => get_client_ip ()
			);
			D ( 'RbacUser' )->save ( $data );
			// 缓存访问权限
			\Org\Util\Rbac::saveAccessList ();
			$_SESSION['bkgd']['_ACCESS_LIST']['ADMINCN'] = $_SESSION['bkgd']['_ACCESS_LIST']['ADMIN'];
			//
			$this->ajaxReturn ( make_rtn('登录成功！', true) );
		} 
	}
	
	// 更换密码
	public function changePwd() {
		$this->checkUser ();
		// 对表单提交处理进行处理或者增加非表单数据
		if (md5 ( $_POST ['verify'] ) != $_SESSION [C ( 'DB_PREFIX' ) . 'verify']) {
			$this->error ( '验证码错误！' );
		}
		$map = array ();
		$map ['password'] = pwd_hash ( $_POST ['oldpassword'] );
		if (isset ( $_POST ['account'] )) {
			$map ['account'] = $_POST ['account'];
		} elseif (isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
			$map ['id'] = $_SESSION [C ( 'USER_AUTH_KEY' )];
		}
		// 检查用户
		$User = D ( "RbacUser" );
		if (! $User->where ( $map )->field ( 'id' )->find ()) {
			$this->error ( '旧密码不符或者用户名错误！' );
		} else {
			$User->password = pwd_hash ( $_POST ['password'] );
			$User->save ();
			$this->success ( '密码修改成功！' );
		}
	}
	
	// 显示个人资料
	public function profile() {
		$this->checkUser ();
		$User = D ( "RbacUser" );
		$vo = $User->getById ( $_SESSION [C ( 'USER_AUTH_KEY' )] );
		$this->assign ( 'vo', $vo );
		$this->display ();
		return;
	}
	
	// 验证码
	public function verify() {
		$type = isset ( $_GET ['type'] ) ? $_GET ['type'] : 'gif';
		\Org\Util\Image::buildImageVerify ( 4, 1, $type, 50, 27, C ( 'DB_PREFIX' ) . 'verify' );
	}
	
	// 修改资料
	public function change() {
		$this->checkUser ();
		$User = D ( "RbacUser" );
		if (! $User->create ()) {
			$this->error ( $User->getError () );
		}
		$result = $User->save ();
		if (false !== $result) {
			$this->success ( '资料修改成功！' );
		} else {
			$this->error ( '资料修改失败!' );
		}
	}
	
	// 上传图片
	public function uploadPic() {
		$upload = new \Think\Upload ();
		// 设置上传文件大小（10M）
		$upload->maxSize = 10485760;
		// 设置上传文件类型
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		// 禁止自动使用子目录
		$upload->autoSub = false;
		// 上传文件的保存规则
		$upload->saveName = 'uniqid';
		//上传根目录
		// $upload->rootPath = BASE_PATH.'/Uploads/';
		// 设置附件上传目录
		$upload->savePath = $_REQUEST ["folder"] . '/';
		$info = $upload->upload ();
		if (! $info) {
			// 捕获上传异常
			$data = "0|" . $upload->getError ();
			$this->ajaxReturn ( $data, 'EVAL' );
		} else {
			// 取得成功上传的文件信息
			foreach($info as $file){
				$savepath = BASE_PATH.'/Uploads/'.$file['savepath'];
				$savename = $file['savename'];
			}
			$filename = $savepath.$savename;
			$rqst = I('request.');
			if ($rqst ["imgRedraw"] == 1) {
				if (isset ( $rqst ["width"] ) || isset ( $rqst ["height"] )) {
					\Org\Util\Image::img_resize ( $filename, $filename, '', 0, 0, $rqst ["width"], $rqst ["height"] );
				}
			}
			if (isset ( $rqst ["width_s"] ) || isset ( $rqst ["height_s"] )) {
				\Org\Util\Image::img_resize ( $filename, $savepath . 's/' . $savename, '', 0, 0, $rqst ["width_s"], $rqst ["height_s"] );
			}
			$size = getimagesize ( $filename );
			//
			$idNow = $rqst ["id"];
			if (empty ( $idNow )) {
				$idNow = "pic";
			}
			//
			$data = "1|图片上传成功！|" . $idNow . "|" . $savename . "|" . $size [0] . "×" . $size [1];
			$this->ajaxReturn ( $data, 'EVAL' );
		}
	}
	
	// 上传附件
	public function uploadAtt() {
		$upload = new \Think\Upload ();
		// 设置上传文件大小（20M）
		$upload->maxSize = 20485760;
		// 设置上传文件类型
		$upload->allowExts = explode ( ',', 'pdf,doc,docx,xls,xlsx,zip,rar,txt,flv,mp3,mp4' );
		//上传根目录
		$upload->rootPath = BASE_PATH.'/Uploads/';
		// 设置附件上传目录
		$upload->savePath = $_REQUEST ["folder"] . '/';
		// 设置上传文件规则
		$upload->saveRule = uniqid;
		if (! $upload->upload ()) {
			// 捕获上传异常
			$data = "0|" . $upload->getErrorMsg ();
			$this->ajaxReturn ( $data, 'EVAL' );
		} else {
			// 取得成功上传的文件信息
			$uploadList = $upload->getUploadFileInfo ();
			//
			$idNow = $_REQUEST ["id"];
			if (empty ( $idNow )) {
				$idNow = "att";
			}
			$size = $uploadList [0] ['size'] / 1024;
			if ($size > 1000) {
				$size = (round ( $size / 1024 * 10 ) / 10) . "M";
			} else {
				$size = (round ( $size * 10 ) / 10) . "K";
			}
			//
			$data = "1|资料上传成功！|" . $idNow . "|" . $uploadList [0] ['savename'] . '|' . $size;
			$this->ajaxReturn ( $data, 'EVAL' );
		}
	}
	
	// 清空缓存
	public function clearCache($path = null) {
    	if ( empty($path) ) {
    		$path=RUNTIME_PATH;
    	}
    	//
    	$dh = opendir($path);
    	if ( $dh ) {     //打开Cache文件夹；
    		while ( ($file = readdir($dh)) !== false ) {    //遍历Cache目录，
    			if ( $file!="." && $file!=".." && $file!="Logs" ) {
    				$fullpath=$path."/".$file;
    				if( !is_dir($fullpath) ) {
    					if ( unlink($fullpath) ) {
    						echo "<span style='color:blue'>file</span> ".$fullpath." <span style='color:green'>clear OK!</span><br />";
    					}
    					else {
    						echo "<span style='color:blue'>file</span> ".$fullpath." <span style='color:red'>clear faild!</span><br />";
    					}
    				}
    				else {
    					$this->clearCache($fullpath);
    				}
    			}
    		}
    		closedir($dh);
    	}
    	//删除当前文件夹：
    	if (rmdir($path)) {
    		echo "<span style='color:blue'>dir</span>&nbsp;&nbsp;".$path." <span style='color:green'>clear OK!</span><br />";
    	}
    	else {
    		echo "<span style='color:blue'>dir</span>&nbsp;&nbsp;".$path." <span style='color:red'>clear faild!</span><br />";
    	}
    	// 清数据缓存
    	clear_all_cache();
	}
	
}

?>