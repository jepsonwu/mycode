<?php
namespace Ft\Controller;
use Ft\Controller\CommonController;
class TeacherController extends CommonController {
			
	// 登录
	public function login() {
		$data = I('param.');
		if (empty($data['username']) or empty($data['password'])) {
			json_echo(1, '请输入用户名或密码！');
			exit;
		}
		$rs = D('Ft', 'Logic')->teacherLogin($data['username'], $data['password']);
		if(!$rs['voipAccount'] && !$rs['voipPassword']){
			$lgc = D("Ft","Logic");
			//创建voip子账户
			$api_rs=D('Ft', 'Logic')->create_voipAccount($rs['id'].time());
			$api_rs=json_decode(json_encode($api_rs),true);

			//voip账号信息入库
			$voip_info=array('voipAccount' => $api_rs['SubAccount']['voipAccount'],'voipPassword' => $api_rs['SubAccount']['voipPwd'],'subAccountSid' => $api_rs['SubAccount']['subAccountSid'],'subToken' => $api_rs['SubAccount']['subToken']);
			$lgc->info_save('Teachers', array('id'=>$rs['id']), $voip_info);
			$rs['voipAccount']=$voip_info['voipAccount'];
			$rs['voipPassword']=$voip_info['voipPassword'];
			$rs['subAccountSid']=$voip_info['subAccountSid'];
			$rs['subToken']=$voip_info['subToken'];
		}
		$result = array(
				'id' => $rs['id'],
				'avatar' => $rs['avatar'],
				'countOfGate' => $rs['countOfGate'],
				'name' => $rs['name'],
				'voipAccount' => $rs['voipAccount'],
				'voipPassword' => $rs['voipPassword'],
				'subAccountSid' => $rs['subAccountSid'],
				'subToken' => $rs['subToken']
		);

		json_echo(0, 'success', $result);
	}
	
	// 页面获取tips
	public function getTips() {
		$data = I('param.');
		
		if (empty($data['id'])) {
			json_echo(1, '教师id丢失！');
			exit;
		}
		
		$time = $data['time'];
		
		// 如果没有指定time时间，则返回所有tips
		if (empty($time)) {
			$rs = M('FrameDetail')->where("type=1")->field('id,demo_id,title,intro')->order('create_time')->select();
		} else {
			$rs = M('FrameDetail')->where("type=1 AND create_time > $time")->field('id,demo_id,title,intro')->order('create_time')->select();
		}
		// echo M('FrameDetail')->getLastSql();exit();
		
		//获取大课i、小课id
		$series_demos=M('Series')->join('ft_demos ON ft_demos.series_id=ft_series.id')->where('ft_series.status=1')->field('ft_series.id,ft_demos.id did')->select();
		//组合、处理json数据
		foreach ($rs as $val) {
			foreach ($series_demos as $sval) {
				if($sval['did']==$val['demo_id']){
					unset($val['demo_id']);
					$nrs['notes'][$sval['id']][]=$val;
				}
			}
		}
		json_echo(0, 'success', $nrs);
	}
	
	// 教师上线接口
	public function online() {
		$data = I('param.');
		
		if (empty($data['id'])) {
			json_echo(1, '教师id丢失！');
			exit;
		}
		
		$map['id'] = $data['id'];
		$data['status'] = 1;
		D('Ft', 'Logic')->info_save('Teachers', $map, $data);
		
		json_echo(0, 'success');
	}
	
	// 教师开始课程接口
	public function startClass() {
		$data = I('param.');
		
		if (empty($data['id'])) {
			json_echo(1, '教师id丢失！');
			exit;
		}
		
		$map['id'] = $data['id'];
		$tdata['status'] = 2;
		D('Ft', 'Logic')->info_save('Teachers', $map, $tdata);
		
		json_echo(0, 'success');
	}
	
	// 闯关成绩
	public function classLevel() {
		$data = I('param.');
		
		if (empty($data['id']) or empty($data['sid']) or empty($data['level'])) {
			json_echo(1, '字段丢失！');
			exit;
		}
		
		$data['create_time'] = time();
		M('ClassLevel')->add($data);
		M('Teachers')->save(array('id' =>$data['id'],'status' => 1));
		
		json_echo(0, 'success');
	}

	//老师下线
	public function offLine(){
		$data = I('param.');
		if (empty($data['id'])) {
			json_echo(1, '字段丢失！');
			exit;
		}
		M('Teachers')->save(array('id' =>$data['id'],'status' => 0));
		json_echo(0, 'success');
	}
	
}
?>