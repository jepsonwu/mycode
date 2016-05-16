<?php
class DM_Router_Cli extends Zend_Controller_Router_Abstract
{
	public function route (Zend_Controller_Request_Abstract $dispatcher)
	{
		$getopt = new Zend_Console_Getopt (array());
		$arguments = $getopt->getRemainingArgs();
		$controller = "";
		$action = "";
		$params = array();

		if ($arguments) {

			foreach($arguments as $index => $command) {

				$details = explode("=", $command);

				if($details[0] == "controller") {
					$controller = $details[1];
				} else if($details[0] == "action") {
					$action = $details[1];
				} else {
					$params[$details[0]] = $details[1];
				}
			}

			if($action == "" || $controller == "") {
				die("Missing Controller and Action Arguments == You should have:
                     php script.php controller=[controllername] action=[action]");
			}
			$dispatcher->setControllerName($controller);
			$dispatcher->setActionName($action);
			$dispatcher->setParams($params);

			return $dispatcher;
		}
		echo "Invalid command.\n", exit;
		echo "No command given.\n", exit;
	}

	public function assemble ($userParams, $name = null, $reset = false, $encode = true)
	{
		throw new Exception("Assemble isnt implemented ", print_r($userParams, true));
	}
}
