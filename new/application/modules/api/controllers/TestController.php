<?php

/**
 *
 * Class Api_TestController
 */
class Api_TestController extends Action_Api
{
	public function indexAction()
	{
		parent::succReturn(array("test" => "ffsafasasdf"));
	}
}
