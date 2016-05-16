<?php

/**
 *
 * User: jepson <jepson@duomai.com>
 * Date: 16-3-16
 * Time: 下午7:28
 */
class Model_Region extends Model_Common_Common
{
	protected $_name = 'region';
	protected $_primary = 'Code';

	//Level 省市区
	const REGION_PROVINCE = 0;
	const REGION_CITY = 1;
	const REGION_DISTRICT = 2;

	//热门城市
	public $hot_city = array(
		array('Code'=>"110000",'Name'=>"北京"),
        array('Code'=>"510100",'Name'=>"成都"),
        array('Code'=>"430100",'Name'=>"长沙"),
        array('Code'=>"210200",'Name'=>"大连"),
        array('Code'=>"440100",'Name'=>"广州"),
        array('Code'=>"330100",'Name'=>"杭州"),
        array('Code'=>"320100",'Name'=>"南京"),
        array('Code'=>"310000",'Name'=>"上海"),
        array('Code'=>"440300",'Name'=>"深圳"),
        array('Code'=>"210100",'Name'=>"沈阳"),
        array('Code'=>"320500",'Name'=>"苏州"),
        array('Code'=>"120000",'Name'=>"天津"),
        array('Code'=>"420100",'Name'=>"武汉"),
        array('Code'=>"350200",'Name'=>"厦门"),
        array('Code'=>"610100",'Name'=>"西安")
	);
}