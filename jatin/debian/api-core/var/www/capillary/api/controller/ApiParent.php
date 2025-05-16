<?php

/**
 * This is the Base Controller class - will contain any functions that are common 
 * to multiple Controllers
 * @author Krishna Mehra
 *
 */
class ApiParentController {
	
	protected $db;
	var $currentorg;
	var $currentuser;
	var $logger;
	public $user_id;
	
	protected $org_id;
	
	public function __construct($dbname) {
		
		global $logger, $currentuser, $currentorg;
		
		$this->logger = &$logger;
		
		$this->db = new Dbase($dbname);
		
		$this->currentuser = &$currentuser;
		$this->user_id = $currentuser->user_id;
		
		$this->currentorg = $currentorg;//$this->currentuser->getProxyOrg();
		$this->org_id = $this->currentorg->org_id;
	}
	
	function flash($str) {
		Util::flash($str);
	}
	
}
?>