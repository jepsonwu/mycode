<?php

/**
 *
 * Class Api_TestController
 */
class Api_TestController extends DM_Controller_Api
{
	public function indexAction()
	{
		parent::succReturn(array("test" => "ffsafasasdf"));
	}
}
