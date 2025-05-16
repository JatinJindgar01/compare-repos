<?php
include_once 'thrift/authentication_service.php';
class AuthModule extends BaseModule {

	var $db;
	var $auth;
	var $defaultAction = "profile";


	public function __construct() {
		parent::__construct();
		$this->db = new Dbase('users');
		$this->auth = Auth::getInstance();

	}

	var $logoutAuthRequired = true;


	/******************* API ACTION ***************/
	/**
	 * Download the credentials for this user.
	 * The login is already done in the api service so is not repeated here
	 * @return unknown_type
	 */
	function credentialsApiAction() {
		$u = StoreProfile::getById($this->currentuser->user_id);
		$ret = array();
		$ret['username'] = $u->username;
		$ret['first_name'] = $u->first_name;
		$ret['last_name'] = $u->last_name;
		$ret['org_name'] = $u->org->name;
		$ret['user_id'] = $u->user_id;
		$ret['org_id'] = $u->org->org_id;
		$ret['server_time'] = Util::serializeInto8601(time());
		$ret['store_server_prefix'] = $u->getSSPrefix();
		$this->data['user'] = $ret;
	}

	
}
?>
