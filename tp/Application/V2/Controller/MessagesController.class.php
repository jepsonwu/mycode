<?php
namespace V2\Controller;
use \V2\Controller\CommonController;

/**
* message controller
*/
class MessagesController extends CommonController
{
	protected $list_get_html_conf=array(
		"check_user"=>true,
		"check_fields"=>array(
			array("fields","id,message,url,create_time,type",null,0,"in","message,create_time,type"),
		),
	); 

	protected $read_get_html_conf=array(
		"check_user"=>true,
		"check_fields"=>array(
			array("fields","message,url,create_time,type",null,0,"in","message,create_time"),
			array("page","number",null,0,null,1),
			array("listrows","number",null,0,null,6),
			array("type","1,2,3,4",null,1,"in"),
			array("start_time","number",null,0),
			array("end_time","number",null,0),
		),
	);
	/**
	 * [list_get_html 通知列表]
	 * @return [type] [description]
	 */
	public function list_get_html(){
		$result=array();
		$model=M("Message");

		//各取一条
		foreach (array(1,2,3,4) as $type) {
			$where=array(
				"type"=>$type,
			);
			in_array($type, array(1,2))&&$where['user_id']=USER_ID;

			$res=$model->where($where)->field($this->fields)->
			limit(1)->order("create_time desc")->find();

			!empty($res)&&$result[]=$res;
		}

		parent::successReturn($result);
	}

	/**
	 * [read_get_html 通知详情]
	 * @return [type] [description]
	 */
	public function read_get_html(){
		in_array($this->type, array(1,2))&&$where['user_id']=USER_ID;

		//type
		$where['type']=$this->type;

		//create_time
		$this->start_time&&$where['create_time']=array("egt",$this->start_time);
		$this->end_time&&$where['create_time']=array("elt",$this->end_time);

		$model=M("Message");
		$result=parent::_list($model,$where,$this->fields,"create_time desc");

		parent::successReturn($result);
	}
}