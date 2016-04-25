<?php

namespace Common\Controller;

use Think\Controller;
//
class CommonBaseController extends Controller {

	/**
	 * 退出
	 */
	public function logout() {
		session ( 'stdNow', null );
		redirect ( __APP__.'/' );
	}

}

?>

