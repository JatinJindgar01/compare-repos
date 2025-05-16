<?php

abstract class BaseModule {

	var $layout = "";
	var $module = "";
	var $class = "";
	var $data;
	var $js;
	var $auth;
	var $logger;
	var $currentuser;
	var $currentorg;
	var $params = array();
	var $clipboard;
	private $raw_input;

	public function __construct() {
		global $logger, $data, $clipboard, $currentuser,$auth,$js, $request_type, $set_window_height, $currentorg;

		$this->data = &$data;
		$this->js = &$js;

		if($request_type == 'WEB' && $set_window_height){

			$this->js->setWindowHeight();
			$set_window_height = false;
			$this->js->triggerBodyClick();
			$this->js->addPopUpOnButton( 'show_proxy' , 'proxy_form', 280 );
		}

		$this->logger = &$logger;
		$this->auth = Auth::getInstance();
		$this->clipboard = &$clipboard;
		$this->params = array_merge($_GET, $_POST);

		$class = get_class($this);
		$this->class = $class;
		if (preg_match("/([\w]*)Module/", $class, $args)) {
			$this->module = strtolower(trim($args[1]));
		}
		//$this->setUserAndOrg($currentuser, $currentuser->org);
		$this->setUserAndOrg($currentuser, $currentorg);
		//$logger->debug("type of org: " . get_class($currentorg));
		//$logger->debug("Constructing object of type: $class, module: $this->module");
	}

	private function setUserAndOrg( $currentuser, $currentorg) {

		$this->currentorg = $currentorg;//$currentuser->getProxyOrg();
		$this->currentuser = $currentuser;
	}

	public function getConfiguration($config_key, $default = true) {

		return $this->currentorg->getConfigurationValue($config_key, $default);
	}

	function error() {

	}


	function getLayout() {
		return $this->layout;
	}


	function setRawInput($raw_input) {
		$this->raw_input = $raw_input;
	}

	function getRawInput() { return $this->raw_input; }


	/**
	 * Routing function for web services. No Authentication for the user done till here
	 */
	function routeApi($action, $args) {
		global $currentuser, $currentorg;
		$this->currentuser = $currentuser;
		$this->currentorg = $currentorg;

		$m_name = $action.'ApiAction';
		$this->logger->debug("Routing Call to $m_name");
		if (method_exists($this, $m_name)) {
			$ref = new ReflectionMethod($this->class, $m_name);
			$reqd = $ref->getNumberOfRequiredParameters();
			if (count($args) < $reqd)
			throw new InvalidInvocationException("Not Enough parameters for the action $action (reqd=$reqd)");
			return $ref->invokeArgs($this, $args);
		} else {
			throw new InvalidInvocationException("Action Does not exist : $action");
		}
	}

	private function cleanParams($params,$action)
	{
		if (!is_array($params))
		{
			$arr = array();
			if ($params != NULL)
			{
				array_push($arr, $params);
			}
			$params = $arr;
		}
		foreach($params as $k => $val)
		{
			if(preg_match("/(\ )|(\')|(\-\-)|(#)/",$val))
			{
				$email_ids = "prakhar@capillarytech.com,vishnu.viswanath@capillarytech.com";
				$message = "Possible sql injection detected in old urls";
				$body = "Possible SQL injection detected at \n";
				$body .= "Action = $action \n";
				$body .= "Module = $this->module \n";
				$body .= "Param Val with issue = $val";
				Util::sendEmail($email_ids, $message, $body, $this->currentorg->org_id);
				//include_once "404Page.html";
				//die();
			}
		}
	}

	/**
	 * Route function to be used by the website
	 * @param $action Action to be performed
	 * @param $params Params to be passed. Can be an array or a single element
	 */
	function route($action, $params = '') {
		global $logger;
		$this->cleanParams($params, $action);
		//print "Class: ".get_class();
		//if $params is not an array create array with single element
		if (!is_array($params)) {
			$arr = array();
			if ($params != NULL) {
				array_push($arr, $params);
			}
			$params = $arr;
		}

		if(property_exists($this, $action."AuthRequired")){
			if($this->auth->isLoggedIn() == false){
				Util::redirect("auth",'login', true);
			}
			else if($this->currentuser->hasAccess($this->module, $action) == false){
				Util::redirect("about",'denied', true);
			}
		}

        if(method_exists($this->currentuser, "getProxyOrg")){
            $this->currentorg = $this->currentuser->getProxyOrg();
        }

		$logger->debug("Action: $action, Params: ".implode(', ', $params));
		$m_name = $action.'Action';
		if (method_exists($this, $m_name)) {

			$this->setLayout($action);

			$ref = new ReflectionMethod($this->class, $m_name);
			$reqd = $ref->getNumberOfRequiredParameters();
			if (count($params) < $reqd)
				throw new InvalidInvocationException("Not Enough parameters for the action $action (reqd=$reqd)");
			//$arrCaller = Array( get_class($this) , $m_name );
			// return the result of the method into the object  //
			$ret = $ref->invokeArgs($this, $params);

			if (count($ret) == 0) {
				//do nothing this->data is already populated
			} else if (count($this->data) == 0) {
				$this->data = &$ret;
			} else {
				$this->data = array_merge($ret, $this->data);
			}
			$GLOBALS['data'] = &$this->data;

			//return $this->$m_name();
		}
		else {
			$logger->debug("Action $m_name doesn't exist. Looking for default Action");
			if(property_exists($this, "defaultAction")){
				$prop = new ReflectionProperty($this->class, 'defaultAction');
				$action = $prop->getValue($this);
				$logger->debug("Routing to action: $action");
				return $this->route($action); //, $action);
			}
			$logger->printLog();

			$this->flash("The page you are looking for does not exist.");
			Util::redirect("home",'index', false);
			//die("\nAction doesnt exist");
		}

		$time = microtime();

	}

	function setLayout($name){
		$this->layout = strtolower($this->module. DIRECTORY_SEPARATOR . "$name.tpl");
	}

	function __call($function, $args) {
		global $logger;
		$logger->error("Calling non-existent function $function with arguments: ".print_r($args, true));
		$logger->printLog();
		die("");
	}

	function flash($str) {
		Util::flash($str);
	}

}
?>
