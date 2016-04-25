<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
* 
*/
class OrdersModel extends RelationModel
{
	// public function __construct($conf){
	// 	$this->_link=array_merge($this->_link,$conf);

	// 	parent::__construct();
	// }

	protected $_link=array(
		"users"=>array(
			"mapping_type"=>self::HAS_ONE,
			"class_name"=>"Users",
			"mapping_key"=>"sid",
			"foreign_key"=>"id",
			"as_fields"=>"name,gender,avatar,nationality",
		),
		"calls"=>array(
			"mapping_type"=>self::HAS_ONE,
			"class_name"=>"Calls",
			"mapping_key"=>"order_id",
			"foreign_key"=>"order_id",
			"as_fields"=>"called_time",
		),
	);
}