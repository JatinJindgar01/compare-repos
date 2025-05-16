<?php

//TODO: referes to cheetah
include_once "base_model/class.OrgSmsCredit.php";
include_once "controller/ApiOrgadmin.php";

class OrgAdminModule extends BaseModule {

	var $db;
	public $master_db;
	var $orgadminController;
	private $user_id;
	private $SecurityManager;

	protected $mem_cache_manager;
	function __construct() {

		global $currentuser;
		parent::__construct();
		
		$this->db = new Dbase('users');
		$this->master_db = new Dbase('masters');
		$this->orgadminController = new OrgAdminController();
		$this->mem_cache_manager = MemcacheMgr::getInstance();
		$this->SecurityManager = new SecurityManager();
		$this->user_id = $currentuser->user_id;
		
		
	}

	private function getSourcesAsOptions() {
		//$org_id = $this->currentorg->org_id;
		$sql = "SELECT id, source FROM `sources`";
		$rtn = array();
		foreach ($this->db->query($sql) as $row) {
			$rtn[$row['source']] = $row['id'];
		}
		return $rtn;
	}
	private function getOrganizationsAsOptions() {
		//$org_id = $this->currentorg->org_id;
		$sql = "SELECT id, name FROM `organizations` WHERE is_inactive = 0 ORDER BY name";
		$rtn = array();
		foreach ($this->db->query($sql) as $row) {
			$rtn[$row['name']] = $row['id'];
		}
		return $rtn;
	}
	function getModulesOptions() {
		$m = array();
		$mods = $this->db->query("SELECT * FROM `store_management`.`modules`");
		foreach ($mods as $mod) {
			$m[$mod['name']] = $mod['code'];
		}
		return $m;
	}

	function getModulesAsOptions() {
		$m = array();
		$mods = $this->db->query("SELECT * FROM `store_management`.`modules`");
		foreach ($mods as $mod) {
			$m[$mod['name']] = $mod['id'];
		}
		return $m;
	}
	
	function getActionsAsOptions($offline_supported = 0) {

		$offline_support_filter = " AND `offline_supported` = '$offline_supported'";
		$sql = "
			SELECT id, name 
			FROM `store_management`.`actions`
			WHERE 1 = 1
				$offline_support_filter
			ORDER BY name
		";
		
		return $this->db->query_hash($sql, 'name', 'id');

	}

	function getOfflineActionsAsOptions() {
		return $this->getActionsAsOptions(1);
	}

	function getOrganizationsOptions() {
		$o = array();
		$orgs = $this->db->query("SELECT * FROM `organizations` WHERE is_inactive = 0 ORDER BY name");
		foreach ($orgs as $org) {
			$o[$org['name']] = $org['id'];
		}
		return $o;
	}
	
	function getOrganizationsNameById( $org_id ) {
		
		$orgs = $this->db->query_scalar("SELECT `name` FROM `organizations` WHERE is_inactive = 0 AND id = '$org_id' ");
		
		return $orgs;
	}

	function addPageGroupAction(){ 
		
		$store_db = new Dbase('stores');
		$module_id = $this->params['module_id'];
		$module_name = $this->params['module_name'];

		$form = new Form('createPageGroup', 'post');
		$form->addField('hidden', 'm_id', '' ,$module_id);
		$form->addField('text', 'm_name', 'Module Name' ,$module_name, array("readonly" => "readonly"));
		$form->addField('text', 'name', 'Page Group Name');
		$form->addField('text', 'code', 'Page Group Code');
		$form->addField('textarea', 'description', 'Description');

		if ($form->isSubmitted()) {

			$params = $form->parse();
			$code = strtolower($params['code']);
			
			$sql = "INSERT into resources 
						(module_id, name, code, description, visibility) 
						VALUES 
						($params[m_id], '$params[name]' , '$code', '$params[description]', 1)
					ON DUPLICATE KEY UPDATE name = '$params[name]' , visibility = 1";
					
			$ret = $store_db->insert( $sql );
			
			if($ret>0)
				$flash = "Added new page group to $params[m_name]";
			
			try{

				$sql = " SELECT code FROM modules WHERE id = $module_id";
				$module_code = $store_db->query_scalar( $sql );
				
				$cache_key = 'oa_'.CacheKeysPrefix::$modManagerKey.'_LOAD_RESOURCE_MODULE_CODE_'.$module_code;
				$this->mem_cache_manager->delete( $cache_key );
				
			}catch( Exception $e ){
				
				$this->logger->error( 'Keys could not be deleted' );
			}
			Util::redirect("orgadmin", "listmodules", false, $flash );
		}


		$this->data['createAction_form'] = $form;
		$sql = "SELECT  modules.name  as moduleName , resources.* 
				FROM modules, resources 
				WHERE resources.module_id = modules.id";
		$this->data['actionsTable'] = $store_db->query_table( $sql, 'box-table-a' );
		
		$this->data['actionsTable']->removeHeader('id');
	}
	
	public function getStoreInfoAction(){
		
		$organizations = $this->getAllOrganizations();
		$org_options = array();
		foreach($organizations as $o)
			$org_options[$o['name']] = $o['id'];			
		
		ksort($org_options);
		
		$form = new Form('deployment','post');
		$form->addField('select','org','Select Organization', array( 0 ), array('list_options' => $org_options) );
		$form->addField( 'checkbox', 'confirm', 'Download As Csv?' , false );
		
		$org_id = 0; 
		if( $form->isValidated() ){
			
			$params = $form->parse();
			
			$org_id = $params['org'];
			$confirm = $params['confirm'];
		}
		
		$this->data['form'] = $form;
		
		$sql = "
				SELECT  `code`, `name`, `client_version_num`, `compile_time`, `svn_revision`, `established_on` 
				FROM `masters`.`store_units` AS `su`
				JOIN `masters`.`org_entities` AS `oe` ON ( `oe`.`id` = `su`.`id` ) 
				WHERE `su`.`org_id` = $org_id AND `oe`.`type` = 'TILL'
			";
		
		$org = new OrgProfile( $org_id );
			
		$this->data['org'] = $org->name;	
		
		$table = $this->db->query_table( $sql, 'Client_Version' );
		
		$this->data['table'] = $table;
		
		if( $confirm ){
			$spreadsheet = new Spreadsheet();
			$spreadsheet->loadFromTable($table)->download( $org->name.'_Client_Version' , 'xls' );
		}
	}
	
	function pageGroupHierarchyAction(){
		
		$store_db = new Dbase('stores');
		global $currentuser;
		$user_id  = $currentuser->user_id;
		
		$modules = $this->getModulesAsOptions();
		$resorces = $this->orgadminController->getpageGroupsAsOptions();
			
		$form = new Form('createPageGroup', 'post');
		
		$form->addField('select', 'm_id', 'Module Name' ,$modules );
		$form->addField('select', 'parent_resource_id', 'Parent Page Group', $resorces );
		$form->addField('select', 'child_resource_id', 'Child Page Group', $resorces );
		
		if ($form->isSubmitted()) {
			$params = $form->parse();

			if( $params['parent_resource_id'] == $params['child_resource_id'] ) 
				Util::redirect( 'orgadmin', 'pagegrouphierarchy', false, ' Parent and Child can not be same' );

			$code = $params['code'];

			$sql = "
						INSERT INTO resources_mapping ( module_id, parent_resource_id, child_resource_id, last_updated_by, last_updated_on ) 
						VALUES 
						('$params[m_id]', '$params[parent_resource_id]', '$params[child_resource_id]', '$user_id' , NOW() )
						ON DUPLICATE KEY UPDATE
						`module_id` = VALUES( `module_id` ),
						parent_resource_id = VALUES( parent_resource_id ),
						child_resource_id = VALUES( child_resource_id ),
						last_updated_by = VALUES( last_updated_by ),
						last_updated_on = VALUES( last_updated_on )
						";
						
			$res = $store_db->insert( $sql );
			if( $res > 0 ){
				$this->flash("Mapping Was Successfully Added");
			}
			else{
				$this->flash("Some error encountered. ");
			}
		}
		$this->data[form] = $form;
	}
	
	function addactionAction(){ 

		$store_db = new Dbase('stores');
		$module_id = $this->params['module_id'];
		$module_name = $this->params['module_name'];
		
		$details = $this->orgadminController->getModulebyId( $module_id );
		$url_version = $details['version'];
		if( $url_version == '1.0.0.1' ){
			
			$page_group_options = $this->orgadminController->getpageGroupsAsOptions( $module_id );
			$this->flash( 'Please Fill The Name Space Directory Of Page & Put it under Suitable Resources');
		}

		$form = new Form('createAction', 'post');
		$form->addField('hidden', 'm_id', '' ,$module_id);
		$form->addField('text', 'm_name', 'Module Name' ,$module_name, array("readonly" => "readonly"));
		$form->addField('text', 'name', 'Action/Page Name');
		$form->addField('text', 'code', 'Action/Page Code');
		if( $url_version == '1.0.0.1' ){

			$form->addField('select', 'resource', 'Page group for the page', $page_group_options );
			$form->addField('text', 'namespace', 'Name Space');
		}
		
		$permission_list = Util::getPermissionsAsOptions($module_id) ;
		$form->addField('multiplebox', 'permission_ids', 'Permissions', array(),array('list_options'=>$permission_list,'multiple' => true));
		$form->addField('text', 'visibility', 'Visibility (0 - invisible, <br /> actions are ordered based on visibility descending)', $a['visibility'], '', '/^\d+$/', 'Visibility can contain only numbers');
		$form->addField('textarea', 'description', 'Description');

		if ($form->isSubmitted()) {
			$params = $form->parse();
			
			if( $url_version == '1.0.0.1' ){
				$code = $params['code'];
				$sql = "INSERT INTO actions (namespace, module_id, resource_id, name, code, description, visibility) 
						VALUES 
						('$params[namespace]', '$params[m_id]', '$params[resource]', 
						'$params[name]' , '$code', '$params[description]','$params[visibility]')";
				
			}else{
				$code = strtolower($params['code']);
				$sql = "INSERT INTO actions (module_id, name, code, description, visibility) 
						VALUES 
						('$params[m_id]', '$params[name]' , '$code', '$params[description]','$params[visibility]')";
			}
			
			$ret = $store_db->insert( $sql );
			
		    foreach($params['permission_ids'] as $pid )
			{
            	$sql = 
              	" INSERT IGNORE INTO action_permissions (action_id, permission_id, is_active ) VALUES ('$ret','$pid',1)";
            	$res = $store_db->update($sql);
			}
			if($ret>0){
				$this->flash("Added new action to $params[m_name]");
			}
			else{
				$this->flash("Some error encountered. Call Robin for $params[m_name]");
			}
			Util::redirect("orgadmin", "listmodules");
			
			//remove all keys related to actions and permissions
			 
			return;

		}


		$this->data['createAction_form'] = $form;
		$this->data['actionsTable'] = $store_db->query_table("SELECT  modules.name  as moduleName , actions.* from modules, actions where actions.module_id = modules.id", 'box-table-b');
		$this->data['actionsTable']->removeHeader('id');
	}

	function deleteactionAction(){
		$store_db = new Dbase('stores');
		$action_id = $this->params['action_id'];
		$action_name = $this->params['action_name'];
		$ret = $store_db->update("DELETE FROM  actions  where id = $action_id");
		$ret = $store_db->update("DELETE FROM  action_permissions  where action_id = $action_id");
		if($ret == true){
			$this->flash("Deleted action $action_name");
		}
		else{
			$this->flash("Some error encountered. Call Robin for $action_name");
		}
		Util::redirect("orgadmin", "index");
	}
	
	function switchactivestatusAction($org_id){
		
		$status = $this->db->update("UPDATE `masters`.`organizations` SET is_active = !is_active WHERE id = $org_id");
		
		Util::redirect('orgadmin', 'index', false, 'Organisation Status set');
	}

	function approveAction( $requested_org_id, $status ){
		
		$org = new OrgProfile( $requested_org_id );
		$org_name = $org->name;
		
		$org_id = $this->currentorg->org_id;
		$user_id = $this->currentuser->user_id;

		$user_name = $this->currentuser->username;
		$user_nice_name = $this->currentuser->getName();
		
		$form = new Form( 'approve' );
		
		$this->data['approve'] = $form;
		if( $status ){

			$from_status = 'ACTIVE';
			$to_status = 'IN ACTIVE';
			$is_activate = false;
			$label = "Are You Sure You Want To Make <div style='color:red'>\"$org_name\" In Active</div>";
		}else{
			
			$from_status = 'IN ACTIVE';
			$to_status = 'ACTIVE';
			$is_activate = true;
			$label = "Are You Sure You Want To Make <div style='color:red'>\"$org_name\" Active</div>";
		}
		$form->addField( 'checkbox', 'confirm', $label, false );
		
		if( $form->isValidated() ){
			
			$params = $form->parse();
			if( $params['confirm'] ){

				$sql = "
						INSERT INTO `audit_logs`.`audit_trail` (
						
							`org_id` ,
							`user_id` ,
							`updated_on` ,
							`tracked_class` ,
							`tracked_item` ,
							`details`
						)
						VALUES (
							'$org_id', 
							'$user_id', 
							NOW(), 
							'OrgProfile', 
							'1' , 
							\"User : $user_nice_name ( username : $user_name ) 
							have changed the Status of organization ( $org_name )from : $from_status TO : $to_status\"
						);
				";
				
				$this->db->update( $sql );

				//delete memcache key for active and inactive org 
				$C_mem_cache_manager = MemcacheMgr::getInstance();
				
				$cache_key = 'oa_'.CacheKeysPrefix::$orgProfileHash.'_GET_ALL_INCLUDE_INACTIVE_'.false;
				
				try{
					
					$C_mem_cache_manager->delete( $cache_key );
					$this->logger->debug( '@@ Memcache Org inactive key deleted successfully' );
				}catch( Exception $e ){
					$this->logger->error( 'Key '.$key.' Could Not Be Deleted .'.$e->getMessage() );
				}
				
				//enable disable conquest
				try{
					include_once 'thrift/conquestdata.php';
					$conquest_data = new ConquestDataThriftClient();
					if( $is_activate === false ){
						$conquest_data->disableConquestByOrgId( $requested_org_id );
					}else{
						$conquest_data->enableConquestByOrgId( $requested_org_id , $org_name );
					}
				}catch( Exception $e ){
					$this->logger->error( 'While enable disable conquest '.$e->getMessage() );
				}
				
				Util::redirect( $this->module, "switchactivestatus/$requested_org_id" );
			}
		}
	}
	
	function indexAction() {
		global $prefix;

		function modifyactivestatus($row){

			if($row['is_active'] == 1){
				
				$text = 'DeActivate';
				$status = 1;
			}else{

				$text = 'Activate';
				$status = 0;
			}
				
			
			$link = Util::genUrl('orgadmin', "approve/$row[id]/$status");
			
			return '<a href="'.$link.'">'.$text.'</a>';
		}
		
		$this->data['orgsTable'] = $this->db->query_table("SELECT * FROM `masters`.`organizations` order by name asc", 'org_table');
		$this->data['orgsTable']->createLink('Credits', Util::genUrl('orgadmin', "administer/{0}"), 'Administer', array(0 => "id"));
		//$this->data['orgsTable']->createLink('Sender', Util::genUrl('orgadmin', 'customsender/{0}'), 'Custom Sender', array(0=>'id'));
		//$this->data['orgsTable']->createLink('Edit', Util::genUrl('orgadmin', 'editorgdetails/{0}'), 'Edit', array(0 => 'id'));
		$this->data['orgsTable']->addFieldByMap('Is Active', 'modifyactivestatus');
		$this->data['orgsTable']->reorderColumns(array('id', 'name', 'phone', 'credits', 'is_active'));
	
		//$this->data['actionsTable']->removeHeader('id');
		$this->data['smsMappingTable'] = $this->db->query_table ("
			SELECT `masters`.sms_mapping.id, `masters`.sms_mapping.type, `masters`.sms_mapping.shortcode, `masters`.sms_mapping.command,
			 `masters`.sms_mapping.whoami, `masters`.organizations.name as orgName, a.name as actionName, `masters`.sms_mapping.`notes`,
			`masters`.organizations.id as 'org_id'
			FROM `masters`.sms_mapping, `masters`.organizations, `store_management`.actions a
			WHERE `masters`.sms_mapping.org_id = `masters`.organizations.id AND `masters`.sms_mapping.action_id = a.id
			ORDER BY `masters`.sms_mapping.org_id, `masters`.sms_mapping.id
		", 'sms_mapping_table');
		$this->data['smsMappingTable'] ->createLink ('Change', Util::genUrl('orgadmin', 'changemapping/{0}'), 'Change', array(0 => 'id'));
		$this->data['smsMappingTable'] ->createLink ('Delete', Util::genUrl('orgadmin', 'deletemapping/{0}'), 'Delete', array(0 => 'id'));
	}
	function deleteAction(){
		
		$flash = "Organisation Deletion Is Not ALlowed";
		Util::redirect( $this->module, 'index', false, $flash );
		
		$org_id = $this->params['org_id'];
		//$ret = $this->db->update("DELETE FROM organizations where id = $org_id");
		if($ret == true){
			$this->flash("Organisation successfully deleted");
		}
		else
		$this->flash( "Some error occurred. Contact System Administrator");
		$this->route("create");

	}
	
	/**
	 * Creates New Organization
	 * with name and address
	 */
	public function createAction() {
		
		global $prefix;
		
		$form = new Form('createOrganization', 'post');
		$form->addField('text', 'organization', 'Organization Name');
		$form->addField('textarea', 'address', 'Head Office Address');
		
		$this->data['createOrganization_form'] = $form;

		if ($form->isValidated()) {
			
			$params = $form->parse();
			$org =  $params['organization'];
			$address = $params['address'];
			
			try{
				
				$this->isOrgNameExists( $org );
				
				$sql = "INSERT INTO organizations ( name, address, is_active , parent_id ) VALUES( '$org', '$address', 1 , -1 )";
				$org_id = $this->master_db->insert( $sql );

				if( $org_id ){

					$this->orgadminController->createDefaultConcept( $org_id );
					$this->orgadminController->createDefaultZone( $org_id );
					$this->orgadminController->createDefaultAdminUser( $org_id );
					$this->orgadminController->createDefaultRoles( $org_id );

					$active_org_hash_cache_key = 'oa_'.CacheKeysPrefix::$orgProfileHash.'_GET_ALL_INCLUDE_INACTIVE_'.false;
					$inactive_org_hash_cache_key = 'oa_'.CacheKeysPrefix::$orgProfileHash.'_GET_ALL_INCLUDE_INACTIVE_'.true;
				
					$this->mem_cache_manager->delete( $active_org_hash_cache_key );
					$this->mem_cache_manager->delete( $inactive_org_hash_cache_key );
				}
				
			}catch( Exception $e ){
				
				$this->flash( 'Organization Created'.$e->getMessage() );
			}
			
			if( $org_id > 0 ){
				
				$this->flash("New Organisation Created");
			}else
				$this->flash("Some error Encountered");
		}
		$this->data['orgsTable'] = $orgstable = $this->master_db->query_table("SELECT * FROM `organizations`", 'box-table-a');
	}
	
	/**
	 * Checks if the org name has already been taken
	 * @param unknown_type $org
	 */
	private function isOrgNameExists( $org ){
		
		$sql = "SELECT `id` FROM `organizations` WHERE `name` = '$org' ";
		
		$id = $this->master_db->query_scalar( $sql );
		
		if( $id )
			throw new Exception( 'Organization Name is already in use!!!' );
	}

	function setorganizationAction(){
		global $prefix;
		$user_id = $this->params['user_id'];
		$user = array();
		if($user_id!=""){
			$user = $this->db->query("SELECT users.*,organizations.name as org_name from users,organizations where users.id = $user_id AND users.org_id = organizations.id");
			if(count($user)==0){
				$this->flash("Invalid user id");
				$this->route("listusers");
				return;
			}
		}

			
		$form = new Form('setOrganisation', 'post');
		$form->addField('hidden', 'user_id', '', $user[0][id]);
		$form->addField('text', 'username', 'Username', $user[0][username], array("readonly" => "readonly"));
		$form->addField('text', 'organisation', 'Current Organization', $user[0][org_name], array("readonly" => "readonly"));
		$form->addField('select', 'org_id', 'Organization', $this->getOrganizationsOptions());
		if ($form->isSubmitted()) {
			$params = $form->parse();
			if ($params[org_id] == '' || $params[user_id] == '') {
				$this->flash("Some error occurred. Pls come back later");
				$this->route('listusers');
				return;
			}

			$ret = $this->db->update("UPDATE users set org_id = $params[org_id] where id = $params[user_id]");
			if($ret == true){
				$this->flash("Organisation Successfully Updated");
			}
			else
			$this->flash("Bloody Error, Call Robin");
			$this->flash(" for user $params[username]");
			$this->route('listusers');
			return;
		}
		$this->data['setOrganisation_form'] = $form;
	}
	function suggestusersAction($username,$limit){
		$arr = $this->db->query("SELECT  id, username as value , TRIM(CONCAT(firstname,' ', lastname)) as info  FROM `users` where username LIKE '%$username%' OR firstname LIKE '%$username%' OR lastname LIKE '%$username%' LIMIT $limit");
		$this->data['results'] = $arr;

	}
	function storesAction(){
			$org_id = $this->currentorg->org_id;
			$this->data['store_table'] = $this->db->query_table("SELECT `o`.`name`, `u`.`username`, `si`.`client_version_num`, `si`.`last_updated` " .
				"FROM stores_zone s " .
				"JOIN organizations o ON `o`.`id` = `s`.`org_id`" .
				"JOIN zones_hierarchy z ON `z`.`zone_id` = `s`.`zone_id` AND `z`.`org_id` = `s`.`org_id` " .
				"JOIN users u on `u`.`id` = `s`.`store_id` AND `u`.`org_id` = `s`.`org_id` " .
				"LEFT OUTER JOIN stores_info si ON `si`.`org_id` = `s`.`org_id` AND `si`.`store_id` = `s`.`store_id` " .
				"ORDER BY `si`.`client_version_num` DESC");				
		}
		
	/**
	 * Generate reports across all organizations. If the Auto-email is set to true, an email is automatically send to mktg-core@capillary
	 * @param $auto_email
	 * @return unknown_type
	 */
	function reportsAction($auto_email = false){
	
		$options = date("Y-m-d");
		$selectForm = new Form('dateSelection','post');
		$selectForm->addField('datepicker', 'date', 'Today',$options);
		$selectForm->addField('checkbox', 'email', 'Email (check to send email)', 'false', $attrs);
		$this->data['select_form'] = $selectForm;
		
		if ($selectForm->isValidated()) {
			$params = $selectForm->parse();
			$options = $params['date'];
		}
		
		//$org_id = $this->currentorg->org_id;
		//$org_name = $this->currentorg->name;
		
		$table = new Table('table');
		$first = array(		
			array(
			
				'org'=>$this->db->query("select id,name as '#ORG' from organizations WHERE is_inactive = 0"),
			)
		);
		$options_date=date('Y-m-d',strtotime($options));
		$options_date_next = Util::getNextDate($options_date); 
		$queries = array(
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',publisher_id as org_id from loyalty " .
						"where `loyalty`.`joined` BETWEEN DATE('$options_date') AND DATE('$options_date_next')  group by publisher_id"),
				'name'=>'#Registration',
				),
			
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',org_id from loyalty_log " .
						"where `loyalty_log`.`date` BETWEEN DATE('$options_date') AND DATE('$options_date_next') " .
						"group by org_id"),
				'name'=>'#Bills',			
				),
				
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',org_id from `loyalty_log` " .
						"join `loyalty` on loyalty.id = loyalty_log.loyalty_id and DATE(`loyalty_log`.`date`) > DATE(`loyalty`.`joined`) " .
						"where `loyalty_log`.`date` BETWEEN DATE('$options_date') AND DATE('$options_date_next') " .
						"group by org_id"), 
				'name'=>'#Repeated_Bills',
			),
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',cv.org_id as org_id " .
						" from luci.voucher as cv " .
						" WHERE `cv`.`created_date` BETWEEN DATE('$options_date') AND DATE('$options_date_next')" .
						" group by cv.org_id"),
						
				'name'=>'#Vouchers',
			),
			array(
			
				'ret'=>$this->db->query("select count( DISTINCT `cv`.`issued_to`) as 'ret_val',cv.org_id as org_id " .
						" from luci.voucher as cv " .
						" WHERE `cv`.`created_date` BETWEEN DATE('$options_date') AND DATE('$options_date_next')" .
						" group by cv.org_id"),
						
				'name'=>'#Vouchers For Unique Users',
			),
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',cv.org_id as org_id from luci.voucher as cv " .
						"join luci.voucher_redemptions AS cvr on cv.voucher_id = cvr.voucher_id " .
						"WHERE `cvr`.`used_date` BETWEEN DATE('$options_date') AND DATE('$options_date_next') " .
						"group by cv.org_id"), 
				'name'=>'#Redemptions',
			
			),
			
			array(
			
				'ret'=>$this->db->query("select count(*) as 'ret_val',vd.org_id as org_id from luci.campaign_referrals as vd " .
						"WHERE `vd`.`created_on` BETWEEN DATE('$options_date') AND DATE('$options_date_next') " .
						"group by vd.org_id"), 
				'name'=>'#Refferals',
			),
			
			array(
			
				'ret'=>$this->db->query("SELECT TRUNCATE(SUM(bill_amount),2) as 'ret_val',org_id from loyalty_log " .
						"WHERE `date` BETWEEN DATE('$options_date') AND DATE('$options_date_next')" .
						"group by org_id"),
				'name'=>'TOTAL SALES',
			),
			array(
			
				'ret'=>$this->db->query("SELECT COUNT(*) as 'ret_val',ls.sender_org as org_id FROM log.sms_out AS ls " .
						"WHERE `ls`.`time` BETWEEN DATE('$options_date') AND DATE('$options_date_next')" .
						"group by ls.sender_org"),
						
				'name'=>'SMS_SENT',
			
			
			)
						
			);
			
			
		function addRow($row,$params){
			
			foreach($params as $param){	
				if($param['org_id']==$row['id'])
						return $param['ret_val'];
			} 
		}
		
		$firstTable=array();
		foreach($first as $f){
			$firstTable = $f['org'];
		}
		$table->importArray($firstTable);
		$act = array();	
		foreach($queries as $query){
			$act = $query['ret'];
			$table->addFieldByMap($query['name'],'addRow',$act);
		}
			$this->data['signups_table']=$table;
				


		if($params['email'] || $auto_email) {
		//	$month = array_flip($options);
		//	$c_month  = $month[$mon];
		//	$c_year = date("Y");
			$e = new Email();
		//	$org_name = $this->currentorg->name;
			//if the report is on current date, put the date, else put the month-year only if for hte past
		//	$date = ($curr_month!=$c_month) ? "$c_month-$c_year" : date('j-M-y');
			$subject = "Intouch Summary on $options_date ";
			$e->emailWidgets(array("mktg-core@capillary.co.in", "anant.choubey@dealhunt.in", 'abhijeet.v@capillary.co.in'), $subject,
			array(
			array('widget_name' => 'New Signups', 'widget_code' => 'signups_table'),
			array('widget_name' => 'Bills Recorded', 'widget_code' => 'bills_table'),
			array('widget_name' => 'SMS Sent', 'widget_code' => 'sms_table')
			), $this
			);

		}


	}

	
	function getcustomfieldsApiAction(){
		
		$org_id = $this->currentorg->org_id;
		
		//$this->data['custom_fields'] = $this->db->query("SELECT `name`, `type`, `label`, `scope`, `default`, `regex`, `error`, `attrs` FROM custom_fields WHERE org_id = '$org_id'");
		
		$cf = new CustomFields();
		$this->data['custom_fields'] = $cf->getCustomFieldsForApi($org_id);
	}

	function listusersAction(){
		global $prefix,$flash,$from;
		$form = new Form('listUsers', 'get');
		$form->addField('text', 'username', 'Search any part of the name');
		$this->js->addAutoSuggest($form->getFieldName('username'), $this->module, "suggestusers");
		$this->data['listUsers_form'] = $form;
		$this->js->ajaxifyForm($form, $this->module, "listusers", "usersTable" );
		
		$append = " AND (id = null)";
		
		if ($form->isSubmitted()) {
			$params = $form->parse();
			if($params[username]==''){
				$flash = "Field Can Not Be Left Blank";
				$from = "/shopbook/orgadmin/listusers";
				Util::redirectReturn();
			}
			$append = " AND (username LIKE '%$params[username]%' OR firstname LIKE '%$params[username]%' OR lastname LIKE '%$params[username]%' )";
			
		}
		$orgstable = $this->db->query_table("SELECT users.*, organizations.name FROM `users`,organizations where users.org_id = organizations.id $append", "usersTable");
		if($orgstable->data()){
			$this->flash(count($orgstable->data())." User Found");
			$orgstable->createLink('Set_Oraganisation', "$prefix/orgadmin/setorganization/?user_id={0}", 'Set Oraganisation', array( 0 => "id"));
			$orgstable->reorderColumns(array('username','firstname','lastname','email','mobile','set_oraganisation'));
			$this->data['usersTable'] = $orgstable;
		}
	}


	function modulesAction($org_id) {
		$this->route('administerorg', $org_id);
		//list modules
		$form = new Form('activate_module', 'post');
		if ($org_id != '') {
			$form->addField('hidden', 'org_id', '', $org_id);
		} else {
			$form->addField('select', 'org_id', 'Organization', $this->getOrganizationsOptions());
		}
		$form->addField('select', 'module', 'Module', $this->getModulesOptions());
		$form->addField('checkbox', 'active', 'Active', true);
		//var_dump($form);
		//die("created form");
        $store_db = new Dbase('stores');
		if ($form->isValidated()) {
			
			$params = $form->parse();
			$org_id = $params['org_id'];
			$module_id =$store_db->query_scalar("SELECT id FROM `modules` WHERE `code` = '$params[module]' LIMIT 1");
			
			if ($org_id == '' || $module_id == '') {
				$this->flash("Some error occurred. Pls come back later");
				$this->route('index');
			}

			if ($params['active']) {
				$sql = "INSERT INTO `active_modules` (org_id, module_id, active, created) VALUES ('$org_id', '$module_id', 1, NOW()) ON DUPLICATE KEY UPDATE active = 1";
				$this->flash($store_db->update($sql) ? "Activated" : "Some error occurred");
			} else {
				$sql = "UPDATE `active_modules` SET active = 0 WHERE `org_id` = '$org_id' AND `module_id` = '$module_id'";
				$this->flash($store_db->update($sql) ? "Deactivated" : "Some error occurred");
			}

			try{

				$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$modManagerKey.'_ACTIVE_MODULE_FOR_ORG_ID_'.$org_id;
				$this->mem_cache_manager->delete( $cache_key );
			}catch( Exception $e ){

				$this->logger->error( 'Key Could Not Be Deleted '.$e->getMessage() );
			}
			
			$this->route('index');

		} else {
			//print "in else";
			$this->data['org_name'] = $org_id;
			$this->data['activate_module_form'] = $form;
		}
		$t =
			
		$this->data['activatedModulesTable'] = $t;

	}

	public function familyConfigurationAction(){
		
		$configureform = new Form( 'family', 'post' );

		$configureform->addField( 'checkbox', 'enable_family', 'Enable Family?', 
			$this->currentorg->getConfigurationValue( CONF_ENABLE_CUSTOMER_FAMILY, false), array( 'help_text' => 'Enable The Family For The Customer...'));
		
		if($configureform->isValidated()){
			
			$this->currentorg->set(CONF_ENABLE_CUSTOMER_FAMILY, $configureform->params['enable_family']);			

			Util::redirect($this->module, 'familyconfiguration', false, 'Successfully Done!!!');
		}
							
		$this->data['form'] = $configureform;
	}
	

	function permissionsAction() {
		$this->data['permissions_table'] = $this->db->query_table("SELECT * FROM `permissions`");
		$store_db = new Dbase('stores');
		$addform = new Form('add_permission', 'post');
		$addform->addField('text', 'permission_name', 'Permission Name', '', '', '/[a-zA-Z_]{6,20}/', 'Permission name can have alphabets and underscore and must be between 6 and 20 characters');
		if ($addform->isValidated()) {
			$params = $addform->parse();
			//check if this permission already exists
			$exists = $store_db->query_scalar("SELECT COUNT(*) FROM permissions WHERE name = '$params[permission_name]'");
			if ($exists) {
				$addform->showErrorMessage('permission_name', 'This permission name already exists');
			}
			else {
				$id = $store_db->insert("INSERT INTO permissions(name, created) VALUES ('$params[permission_name]', NOW())");
				if ($id != -1) $this->flash("Added successfully. ID = $id");
				else $this->flash("Some error occurred. Pls try again later");
			}
		}
		$this->data['add_permission_form'] = $addform;
	}
	function changeactionAction($action_id) { 

		$store_db = new Dbase('stores');
		$a = $store_db->query_firstrow("
			SELECT 
				m.code AS `module`, a.module_id as `module_id` , 
				a.code AS `action`, a.name as 'action_name', a.description as 'description', 
				a.visibility, a.offline_supported,
				a.resource_id
			FROM `actions` a 
			JOIN `modules` m ON m.id = a.module_id 
			WHERE a.id = '$action_id'
		");
		
		$resource_id = $a['resource_id'];
		$module_id = $a['module_id'];
		
		//get all resources in the module
		$sql = "
				SELECT *
				FROM `resources`
				WHERE `module_id` = '$module_id'
		";
		
		$resources = $store_db->query_hash( $sql, 'name', 'id' );
		
		//get already set permission 
		$sql = "
				SELECT *
				FROM `action_permissions`
				WHERE `action_id` = $action_id
					AND `is_active` = 1
		";
		
		$set_permissions = array();
		$permissions = $store_db->query( $sql  );
		foreach( $permissions as $permssion ){
			
			array_push( $set_permissions, $permssion['permission_id'] );
		}
		
		$form = new Form('change_action_permission', 'post');
		$form->addField('text', 'name', 'Action Name', $a['action_name']);
		$form->addField('textarea', 'description', 'Description', $a['description']);
		$form->addField('text', 'visibility', 'Visibility (0 - invisible, <br /> actions are ordered based on visibility descending)', $a['visibility'], '', '/^\d+$/', 'Visibility can contain only numbers');
		//$permission_list = Util::getPermissionsAsOptions($a['module_id']) ;
		$permission_list = $this->SecurityManager->getPermissionsAsOptions( $a['module_id'] );
		$form->addField('multiplebox', 'permission_ids', 'Permissions', $set_permissions,array( 'list_options'=>$permission_list, 'multiple' => true ) );

		$form->addField('select', 'resource', 'Resources', $resource_id,array( 'list_options'=>$resources ) );
		$form->addField('checkbox', 'is_offline_supported', 'Offline Supported ?', $a['offline_supported']);
		if ($form->isValidated()) {
			//assign this action to this permission
			$params = $form->parse();
			$sql = "
				UPDATE `actions` 
				SET `name` = '$params[name]', 
					`visibility` = $params[visibility], 
					`description` = '$params[description]',
					`offline_supported` = '$params[is_offline_supported]',
					`resource_id` = '$params[resource]' 
				WHERE `id` = '$action_id'
			";
			$res = $store_db->update($sql);
			
			//first delete all the previous permissions
			
			//$sql = "DELETE FROM action_permissions where action_id = '$action_id'";
			$sql = "
				UPDATE `action_permissions`
				SET `is_active` = 0
				WHERE `action_id` = $action_id
			";
			$res = $store_db->update($sql);
						
			foreach($params['permission_ids'] as $pid )
			{
            	//$sql = 
              	//" INSERT IGNORE INTO action_permissions (action_id, permission_id, is_active ) VALUES ('$action_id','$pid',1)";
              	$sql = "
              			INSERT INTO `action_permissions`
              			 	( 	action_id,
								permission_id,
								is_active,
								last_updated_on,
								last_updated_by
							)
						VALUES
							(
								$action_id,
								$pid,
								0,
								NOW(),
								$this->user_id
							)
              			ON DUPLICATE KEY UPDATE 
						`is_active` = 1,
						`last_updated_on` = NOW(),
              			`last_updated_by` = $this->user_id
              		 ";
            	$res = $store_db->update($sql);
			}
			
			if ($res) {
				$this->flash("Changed successfully");
			} else {
				$this->flash("Some error occurred. Pls try again later");
			}
		}
		$this->data['action'] = $a;
		$this->data['change_action_permission_form'] = $form;
	}
//-------------------------
//Error management
//----------------------------

	function getErrorDetails($select_filter,$start_date,$end_date,$org_filter,$filter){
	
		$end_date_next = Util::getNextDate($end_date);
		$sql = "SELECT  $select_filter" .
			" FROM `error_description` AS `ed`" .
			" JOIN `store_error` AS `se` ON ( `se`.`error_id` = `ed`.`id` )" .
			" JOIN `users` AS `u` ON ( `se`.`store_id` = `u`.`id` AND `se`.`org_id` = `u`.`org_id`)" .
			" WHERE `se`.`last_updated` BETWEEN DATE('$start_date') AND DATE('$end_date_next') $org_filter " .
			" $filter" ;
			
		return $this->db->query_table($sql);
		
	}
	function getAllErrorSubjects(){
		
		$sql = " SELECT DISTINCT(`info`) FROM `error_description`";
		return $this->db->query($sql);
	}
	function getErrorInfoByErrorId($error_id){
		$sql = " SELECT `info` " .
		" FROM `error_description` AS `ed`" .
		" WHERE `id` = '$error_id'" ;
		
		return $this->db->query_scalar($sql);
		
	}
	function getErrorDescriptionByErrorId($error_id){
		
		$sql = " SELECT `description` " .
		" FROM `error_description` AS `ed`" .
		" WHERE `id` = '$error_id'" ;
		
		return $this->db->query_scalar($sql);

	}	
	
	function errorManagementAction(){
		
		$error_info = $this->getAllErrorSubjects();
		$error_info_options = array();
		foreach($error_info as $ef)
			$error_info_options[substr($ef['info'],0,60)] = $ef['info'];
		$error_info_options['Show For All Error Info'] = -1;
		//get organizations
		$organizations = $this->getAllOrganizations();
		$org_options = array();
		foreach($organizations as $o)
			$org_options[$o['name']] = $o['id'];
		$org_options['Show Recent Errors Across All Organizations'] = -1;
		ksort($org_options);			
		
		$form = new Form('error_management','post');
		
		$form->addField('select','org','Select Organization','-1',array('list_options' => $org_options));
		$form->addField('multiplebox','client_version','Select Client Version','',array('multiple' => true,'list_options' => $this->getClientVersionNumberAsOption()));
        $form->addField('datepicker','compile_time_greater_than','Compilation Time Greater Than');
        $form->addField('datepicker','compile_time_less_than','Compilation Time Less Than');
        $form->addField('text','svn_version_greater_than','svn Version Greater Than');
        $form->addField('text','svn_version_less_than','svn Version Less Than');
	
		$form->addField('select','error_info','Select Any Error Info','-1',array('list_options' => $error_info_options));
		$form->addField('datepicker','start_date','Results From...',date('Y-m-d'));
		$form->addField('datepicker','end_date','Results From...',date('Y-m-d'));
		$form->addField('text','frequency','Select Frequecy',0,array('helptext' => 'Give The Frequecy Of Error'));
		$form->addField('checkbox','show_unique','Tick To Show Only Different Descriptions');
		$form->addField('checkbox','download','Download ?', false);
		
		if($form->isValidated()){
			$params = $form->parse();
			$org_id = $params['org'];
			$start_date = $params['start_date'];
			$end_date = $params['end_date'];
			$frequency = $params['frequency'];
			$show_unique = $params['show_unique'];
			$error_subject = $params['error_info'];
			$client_version = $params['client_version'];
			$start_compile_date = $params['compile_time_greater_than'];
			$end_compile_date = $params['compile_time_less_than'];
			$start_svn_revision = ($params['svn_version_greater_than'])?('1.0.0.'.$params['svn_version_greater_than']):(false);
			$end_svn_revision = ($params['svn_version_less_than'])?('1.0.0.'.$params['svn_version_less_than']):(false);
			
			$org_filter = $group_filter = '';

			$client_versions_num = array();
			foreach($client_version as $cv )
				array_push($client_versions_num,$cv);
			$client_versions_num = implode("','",$client_versions_num);
			if($org_id != '-1'){
				$org_filter .= " AND `u`.`org_id` = '$org_id' ";
			}
			$select_filter = " `u`.`org_id`,`ed`.`id`,`u`.`username` AS `store_name`,`info` AS `error_subject`,`description` AS `error_description`,`version` AS `client_version_num`,DATE(`compile_time`) AS `compile_date`,`svn_revision`,`se`.`last_updated` AS `last_occurance`";
			if($error_subject != '-1'){
				$filter .= " AND `ed`.`info` LIKE '$error_subject' ";
				$error_info_selection = true;
			}
			if($client_versions_num){
				
				$filter .= " AND `ed`.`version` IN ('$client_versions_num') ";
			}
			if($start_compile_date && $end_compile_date){
				$filter .= " AND `ed`.`compile_time` BETWEEN DATE('$start_compile_date') AND DATE('".Util::getNextDate($end_compile_date)."') ";
			}
			if($start_svn_revision && $end_svn_revision){
				
				$filter .= " AND `ed`.`svn_revision` BETWEEN ('$start_svn_revision') AND ('$end_svn_revision')";
			}
			if($show_unique){
				$filter .= " Group By `error_description` " .
						" HAVING COUNT(*) > '$frequency'";
				$select_filter = " `u`.`org_id`,`ed`.`id`,`info` AS `error_subject`,`description` AS `error_description`,COUNT(*) AS `no_of_occurrance`"; 
			}
			
			$table = $this->getErrorDetails("$select_filter",$start_date,$end_date,$org_filter,$filter);

			if($show_unique){
				$table->createLink('View Details', Util::genUrl('orgadmin', "viewErrorDetails/$org_id/{0}/$start_date/$end_date/$error_info_selection"), 'View Details', array( 0 => 'id'));
				$table->createLink('View Binary Details', Util::genUrl('orgadmin', "viewBinaryErrorDetails/{0}/$start_date/$end_date/$error_info_selection"), 'View Binary Details', array( 0 => 'id'));
			}
		}
		$this->data['error_form'] = $form;
		$this->data['error_table'] = $table;

		if($params['download']){			
			$spreadsheet = new Spreadsheet();
			$spreadsheet->loadFromTable($table)->download('error_report', 'xls');
		}
	}
	function viewBinaryErrorDetailsAction($error_id,$start_date,$end_date,$error_info = false){

		if($error_info){
			$error_subject = $this->getErrorInfoByErrorId($error_id);
			$filter .= " AND `ed`.`info` LIKE '$error_subject' ";
		}else{
			$error_desc = $this->getErrorDescriptionByErrorId($error_id);
			$filter = " AND `description` LIKE ('$error_desc')";
		}
		
		$select_filter = " `ed`.`id`,`info` AS `error_subject`,`description` AS `error_description` ,`version` AS `client_version_num`,DATE(`compile_time`) AS `compile_date`,`svn_revision`,`se`.`last_updated` AS `last_occurance`";
		$table = $this->getErrorDetails("$select_filter",$start_date,$end_date,'',$filter);

		$this->data['table'] = $table;
	}
	function viewErrorDetailsAction($org_id,$error_id,$start_date,$end_date,$error_info = false){
		
		if($org_id != '-1'){
			$org_filter .= " AND `u`.`org_id` = '$org_id' ";
		}
		if($error_info){
			$error_subject = $this->getErrorInfoByErrorId($error_id);
			$filter .= " AND `ed`.`info` LIKE '$error_subject' " .
					" Group By `u`.`org_id`,`u`.`id` ";
		}else{
			$error_desc = $this->getErrorDescriptionByErrorId($error_id);
			$filter = " AND `description` LIKE ('$error_desc')" .
				" Group By `u`.`org_id`,`u`.`id` ";
		}

		$select_filter = " `u`.`id` AS `store_id`,`ed`.`id`,`u`.`username` AS `store_name`,`info` AS `error_subject`,`description` AS `error_description` ,COUNT(*) AS `no_of_occurance`";
		$table = $this->getErrorDetails("$select_filter",$start_date,$end_date,$org_filter,$filter);

		$table->createLink('View Full Details', Util::genUrl('orgadmin', "viewErrorHistoryInStore/{0}/{1}/$start_date/$end_date/$error_info"), 'View Full Details', array( 0 => 'id',1 => 'store_id'));
		$this->data['table'] = $table;

	}
	function viewErrorHistoryInStoreAction($error_id,$store_id,$start_date,$end_date,$error_info = false){

		if($error_info){
			$error_subject = $this->getErrorInfoByErrorId($error_id);
			$filter = " AND `u`.`id` = '$store_id' AND `ed`.`info` LIKE '$error_subject' ";
		}else{
			$error_desc = $this->getErrorDescriptionByErrorId($error_id);
			$filter = " AND `u`.`id` = '$store_id' AND `description` LIKE ('$error_desc')";
		}
		$select_filter = " `u`.`username` AS `store_name`,`info` AS `error_subject`,`description` AS `error_description` ,`version` AS `client_version_num`,DATE(`compile_time`) AS `compile_date`,`svn_revision` ,`last_updated` AS `error_date`";
				
		$table = $this->getErrorDetails("$select_filter",$start_date,$end_date,'',$filter);

		$this->data['table'] = $table;
		
	}
//-------------------------
//Deployment management
//----------------
	function getClientVersionNumberAsOption($org_id = ''){
		
		if($org_id != -1)
			$org_filter = " WHERE `org_id` = '$org_id' "; 
		$sql = " SELECT DISTINCT `client_version_num` FROM `stores_info`  $org_filter ";
		$client_version_number = $this->db->query($sql);
		foreach($client_version_number as $cvn){
			$client_version_key = $cvn['client_version_num'];
			$client_version_option[$client_version_key] = $client_version_key;
		}
		return $client_version_option;
	}
	function deploymentManagementAction() {
		
		$organizations = $this->getAllOrganizations();
		$org_options = array();
		foreach($organizations as $o)
			$org_options[$o['name']] = $o['id'];			
		$org_options['All Org'] = -1;
		ksort($org_options);
		$form = new Form('deployment','post');
		
		$form->addField('select','org','Select Organization','-1',array('list_options' => $org_options));	
		$form->addField('multiplebox','client_version','Select Client Version','',array('multiple' => true,'list_options' => $this->getClientVersionNumberAsOption()));
		$form->addField('datepicker','compile_time_greater_than','Compilation Time Greater Than');
		$form->addField('datepicker','compile_time_less_than','Compilation Time Less Than');
		$form->addField('text','svn_version_greater_than','svn Version Greater Than');
		$form->addField('text','svn_version_less_than','svn Version Less Than');

		if($form->isValidated()){
			$params = $form->parse();
			$client_version = $params['client_version'];
			$client_versions_num = array();
			foreach($client_version as $cv )
				array_push($client_versions_num,$cv);
			$client_versions_num = implode("','",$client_versions_num);
			$filter = '';
			$org_id = $params['org'];
			if($org_id && $org_id != -1)
				$filter .= " `u`.`org_id` = ($org_id) ";

			$start_compile_date = $params['compile_time_greater_than'];
			$end_compile_date = $params['compile_time_less_than'];
			$start_svn_revision = ($params['svn_version_greater_than'])?('1.0.0.'.$params['svn_version_greater_than']):(false); 
			$end_svn_revision = ($params['svn_version_less_than'])?('1.0.0.'.$params['svn_version_less_than']):(false);
			
			if($client_versions_num){
				if($filter != '')
					$andFilter = "AND";
				else
					$andFilter = "";
					
				$filter .= " $andFilter `client_version_num` IN ('$client_versions_num')";
			}
			
			if( $start_compile_date &&  $end_compile_date ){
				if($start_compile_date  > $end_compile_date )
					Util::redirect('orgadmin','deploymentmanagement',false,'compile Time Greater Than should be less than compile Time Less Than');
				if($filter != '')
					$andFilter = "AND";
				else
					$andFilter = "";
				$filter .= " $andFilter STR_TO_DATE(`compile_time`,'%d/%m/%Y') BETWEEN ('$start_compile_date') AND ('$end_compile_date')";
			}

			if( $start_svn_revision &&  $end_svn_revision ){
				if($start_svn_revision  > $end_svn_revision )
					Util::redirect('orgadmin','deploymentmanagement',false,'Greater Than Svn revision Should Be Less Than Less than Svn Revision Numebr');
				if($filter != '')
					$andFilter = "AND";
				else
					$andFilter = "";

				$filter .= " $andFilter `svn_revision` BETWEEN '$start_svn_revision' AND '$end_svn_revision' ";
			}
			
			$sql = " SELECT `u`.`id`,`client_version_num`,REPLACE(`svn_revision`,'.','_') AS `svn_revision`,COUNT(*) AS no_of_stores" .
					" FROM `stores_info`" .
					" JOIN `users` AS `u` ON ( `store_id` = `u`.`id`)" .
					" WHERE $filter " .
					" GROUP BY `client_version_num`";
			$table = $this->db->query_table($sql);
			
			$table->createLink('View Details', Util::genUrl('orgadmin', "viewVersionDetails/$org_id/{0}/{1}"), 'View Details', array( 0 => 'id',1 => 'svn_revision'));
		}
		$this->data['deployment'] = $form;
		$this->data['stores_info'] = $table;
	}
	
	private function getvalidintouchclientversionsAsOptions()
	{
		$stores_db = new Dbase('stores');
		$sql = "SELECT `version`, `id` FROM `client_versions` WHERE `client_type` = 'INTOUCH'";
		return $stores_db->query_hash($sql, 'version', 'id');
	}
	
	private function getintouchclientversionstable()
	{
		$stores_db = new Dbase('stores');
		$sql = "SELECT * FROM `client_versions` WHERE `client_type` = 'INTOUCH'";
		return $stores_db->query_table($sql);		
	}
	
	private function setversionforclients($version_id, $org_id, $stores)
	{
		//Set the existing versions to old
		//insert new entries
		$version_added_by = $this->currentuser->user_id;
		$stores_db = new Dbase('stores');
		$sql = "
			UPDATE `client_version_mapping`
			SET `is_active` = 0
			WHERE `org_id` = '$org_id' AND `store_id` IN ('".implode("','", $stores)."')			 
		";
		$stores_db->update($sql);
		
		//Insert the rows
		$sql = "INSERT INTO `client_version_mapping` (`org_id`, `store_id`, `version_id`, `version_set_on`, `is_active`, `version_set_by`) VALUES ";
		$insert_array = array();
		foreach($stores as $s_id)
			array_push($insert_array, "('$org_id', '$s_id', '$version_id', NOW(), 1, '$version_added_by')");
		if(count($stores) > 0)
		{
			$sql .= implode(",", $insert_array);
			return $stores_db->insert($sql);
		}
		return false;
	}
	
	private function addintouchclientversion($version, $change_log)
	{
		$client_type = 'INTOUCH';
		$version_added_by = $this->currentuser->user_id;
		//client type is intouch for now
		$stores_db = new Dbase('stores');
		$sql = "INSERT INTO `client_versions` (`client_type`, `version`, `change_log`, `created_on`, `version_added_by`)
			VALUES ('$client_type', '$version', '$change_log', NOW(), '$version_added_by') 
		";
		return $stores_db->insert($sql);
	}
	
	private function getclientversionmappingtable()
	{
		$stores_db = new Dbase('stores');
		$sql = "
			SELECT s.username, cv.version, cvm.version_set_on
			FROM `client_version_mapping` cvm
			JOIN `client_versions` cv ON cv.id = cvm.version_id
			JOIN `user_management`.`stores` s ON s.org_id = cvm.org_id AND s.store_id = cvm.store_id
			WHERE cvm.is_active = 1
			ORDER BY cvm.`version_set_on` DESC
		";
		return $stores_db->query_table($sql); 	
	}
	
	function clientversionmappingAction()
	{
		
		//Add Version
		$this->data['client_version_mapping'] = $addVersionForm;
		$addForm = new Form('add_version_form', 'post');
		$addForm->addField('text', 'version', 'Version', '', array('help_text' => 'Enter the version number to be used. Eg. Version : 1.0.0.1'));
		$addForm->addField('textarea', 'change_log', 'Change Log', '', array('rows' => 10, 'cols' => 100, 'help_text' => 'Change log for the version'));
		if($addForm->isValidated())
		{
			$params = $addForm->parse();
			$ret = $this->addintouchclientversion($params['version'], $params['change_log']);
			$this->flash("Operation ".($ret ? " Success " : " Failure "));	
		}
		$this->data['add_client_version_form'] = $addForm;
		
		//Versions Table
		$this->data['client_versions_table'] = $this->getintouchclientversionstable();
		
		$form = new Form('mapping_form', 'post');
		
		$form->addField('select', 'org', 'Organization', array('Capillary Technologies' => '0'), array('list_options' => $this->getOrganizationsAsOptions()));
		$form->addField('multiplebox', 'store', 'Store', '', array('list_options' => array(), 'multiple' => true, 'help_text' => 'Select the option All for all'));
		$form->addField('select', 'version', 'Set Version', '', array('list_options' => $this->getvalidintouchclientversionsAsOptions()));
		
		$ajaxController = new AjaxScriptModule();
		$ajaxController->getStoresForOrg($form, 'org', 'store', false, true);
		
		if($form->isValidated())
		{
			$params = $form->parse();
			$version_id = $params['version'];
			$stores = $params['store'];
			$ret = $this->setversionforclients($version_id, $params['org'], $stores);
			$this->flash("Operation ".($ret ? " Success " : " Failure "));
		}
		
		$this->data['client_version_mapping_form'] = $form;
		
		//Client Version Mapping Table
		$this->data['client_version_mapping_table'] = $this->getclientversionmappingtable();
	}
	
	function viewVersionDetailsAction($org_id = '',$store_id = '',$svn_revision = ''){

		if($org_id != -1)
			$org_filter = " AND `u`.`org_id` = '$org_id' ";
			 
		$client_version_num = $this->db->query_scalar(" SELECT `client_version_num` FROM `stores_info` WHERE `store_id` = '$store_id'");
		$filter = " `client_version_num` LIKE '$client_version_num' ";
		
		if($svn_revision){
			$svn_revision = str_replace('_','.',$svn_revision);
			$filter .= " AND `svn_revision` = '$svn_revision' ";
		}
			
		$sql = " SELECT `u`.`id` AS `store_id`,`username` AS `store_name`,`client_version_num`,`compile_time`,`svn_revision`,`last_updated`" .
					" FROM `stores_info`" .
					" JOIN `users` AS `u` ON ( `store_id` = `u`.`id`)" .
					" WHERE $filter $org_filter " ;
					
		$table = $this->db->query_table($sql);
		$this->data['table'] = $table;		
	}

	function creditsAction() {
		$this->data['credits_table'] = $this->db->query_table("SELECT c.org_id as id, o.name, c.value_sms_credits, c.bulk_sms_credits, c.user_credits, c.last_updated FROM org_sms_credits c, organizations o WHERE c.org_id = o.id", 'credit_table');
		$this->data['credits_table']->createLink('Administer', Util::genUrl('orgadmin', "administer/{0}"), 'Administer', array(0 => "id"));
	}

	function callNetcore($feed_id, $sender_ids) {
		$sender_ids = urlencode($sender_ids);
		$create_url = "http://pub.mytoday.com/api/v2/feed/update_feed_attribute.php?user_name=9986362500&feed_id=$feed_id&attribute_name=white_listed_sender_ids&attribute_value=$sender_ids";

		$response = Util::curl_get_request($create_url);
		if (preg_match('/Successfully updated the feed attribute white_listed_sender_ids/', $response)) return null;
		else return $response;
	}

	function updateNetcore() {
		$sender_ids = array();
		$blacklisted = array();
		$result = $this->db->query ("SELECT DISTINCT sender_gsm FROM custom_sender");
		foreach ($result as $row) {
			$value = substr($row['sender_gsm'], 0, 8);
			$response = $this->callNetcore(299946, $value);
			if (preg_match("/BLACKLISTED_SENDER_ID/", $response))
				array_push($blacklisted, $value);
			else if (preg_match("/INVALID_SENDER_ID/", $response))
				return "$response: $value";
			else	array_push($sender_ids, $value);
		}
		$result = $this->db->query ("SELECT DISTINCT sender_cdma FROM custom_sender");
		foreach ($result as $row) {
			$value = substr($row['sender_cdma'], 0, 8);
			$response = $this->callNetcore(299946, $value);
			if (preg_match("/BLACKLISTED_SENDER_ID/", $response)) 
				array_push($blacklisted, $value);
			else if (preg_match("/INVALID_SENDER_ID/", $response))
				return "$response: $value";
			else	array_push($sender_ids, $value);
		}
		$ret = null;
		if (sizeof ($blacklisted))
			$ret = "[BLACKLISTED=".implode(',', $blacklisted)."]";
		$sender_ids = implode(',', $sender_ids);
		$ret .= $this->callNetcore(299946, $sender_ids);
		$ret .= $this->callNetcore(301363, $sender_ids);
		return $ret;
	}

//----------------------------

	
/**
 * show custom sender and editorgdetails...
 * @param OrgProfile $org
 */
	function administerAction($org, $action = '') {
		$org_id = $org;
		$nsadmin = new NSAdminThriftClient();
		$org_sms_model = new OrgSmsCreditModel();

		if ($action == 'delete') {
			$this->db->update("DELETE FROM custom_sender WHERE org_id = '$org'");
		}

		$custom = $this->db->query_firstrow("SELECT * FROM `custom_sender` WHERE `org_id` = '$org'");

		if (empty($custom['sender_cdma'])) $custom['sender_cdma'] = '9874400500';

		$form = new Form('custom_sender_update');
		//even while sending we check if the sender ID is in correct format
		$form->addField('text', 'sender_gsm', 'Sender ID for GSM', $custom['sender_gsm'], '', "/^[0-9a-z ]{1,12}$/i", 'Sender ID for GSM can be alphanumeric and 1-12 characters','',true,true);
		$form->addField('text', 'sender_cdma', 'Sender ID for CDMA', $custom['sender_cdma'], '', '/^[0-9a-z ]{1,12}$/i', 'Sender ID for CDMA can only be 1-12 characters','',true,true);
		$form->addField('text', 'sender_label', 'Sender label for EMAIL', $custom['sender_label'], '', "", '');
		$form->addField('text', 'sender_email', 'Sender EMAIL', $custom['sender_email'] , array('readonly' => true) );
		$form->addField('text', 'replyto_email', 'Reply-to ID for EMAIL', $custom['replyto_email'], '', "", '');
		if ($form->isValidated()) {

			$params = $form->parse();
			$sql = "INSERT INTO `custom_sender` (org_id, sender_gsm, sender_cdma, sender_label, replyto_email,sender_email) VALUES ('$org_id', '$params[sender_gsm]', '$params[sender_cdma]', '$params[sender_label]', '$params[replyto_email]', '$params[sender_email]') ON DUPLICATE KEY UPDATE sender_gsm = '$params[sender_gsm]', sender_cdma = '$params[sender_cdma]', sender_label = '$params[sender_label]', replyto_email = '$params[replyto_email]', sender_email = '$params[sender_email]'";
			if ($this->db->update($sql)) {
                /*
				if (($response = $this->updateNetcore()))
					$this->flash("Sender info update successfully. NetCore updation response: $response");
				else
                */
					$this->flash("Sender info update successfully");
					
			}
			else
			$this->flash("Some problem occurred. Please check back later");

		}

		$custom = $this->db->query_firstrow("SELECT * FROM `custom_sender` WHERE `org_id` = '$org'");
		$table = new Table('box-table-a');
		$table->addHeader('key', 'Property');
		$table->addHeader('info', 'Value');
		$tabdata = array();
		//$tabdata[0] = array('key' => 'Organization', 'info' => $this->currentuser->org_name);
		$tabdata[1] = array('key' => 'Sender ID for GSM Phones', 'info' => $custom['sender_gsm']);
		$tabdata[2] = array('key' => 'Sender ID for CDMA Phones', 'info' => $custom['sender_cdma']);
		$tabdata[3] = array('key' => 'Sender label for EMAIL', 'info' => $custom['sender_label']);
		$tabdata[4] = array('key' => 'Reply-to ID for EMAIL', 'info' => $custom['replyto_email']);
		if( $custom['sender_email'] )
			$tabdata[5] = array('key' => 'Sender EMAIL', 'info' => $custom['sender_email']);
		$table->addData($tabdata);
		$this->data['custom_sender_table'] = $table;

		$this->data['custom_sender_update_form'] = $form;
	
	/**
	 * administer organization...
	 */
		$org = new OrgProfile($org_id);
		$this->data['org_name'] = $org->name;

		# ADD Credits form
		$addcreditsForm = new Form('add_credits', 'post');
		$addcreditsForm->addField('text', 'value_credits', 'Value SMS Credits to Add', 0, '', '/[-\d]+/', 'Only Digits allowed');
		$addcreditsForm->addField('text', 'bulk_credits', 'Bulk SMS credits to Add', 0, '', '/[-\d]+/', 'Only digits allowed');
		$addcreditsForm->addField('text', 'user_credits', 'User Credits to Add', 0, '', '/[-\d]+/', 'Only digits allowed');

		if ($addcreditsForm->isValidated()) {
			$params = $addcreditsForm->parse();
			$me = $this->currentuser->user_id;
			$timezone = StoreProfile::getById($this->currentuser->user_id)->getStoreTimeZoneLabel();
			$currenttime = Util::getCurrentTimeInTimeZone($timezone, null);
			$currenttime = empty($currenttime)? " NOW() " : "'$currenttime'";
		
			$org_sms_model->setBulkSmsCredits( $params['bulk_credits'] );
			$org_sms_model->setValueSmsCredits( $params['value_credits'] );
			$org_sms_model->setUserCredits( $params['user_credits'] );
			$org_sms_model->setOrgId( $org_id );
			$org_sms_model->setLastUpdatedBy( $me );
			$org_sms_model->setLastUpdated( $currenttime );
			$org_sms_model->setCreatedBy( $me );
			
			$res = $org_sms_model->insert();
			
             if( $params['value_credits'] < 0 || $params['bulk_credits'] < 0 || $params['user_credits'] < 0)
             {
             	$this->flash( "Credits should be greater than 0");
             }
             else
             {
				// add it to credit_log table
				$this->flash($res != null ? "Added successfully" : "Adding credits failed");
             }
			if($res)
			{
				if(!$nsadmin->reloadConfig())
				{
					$this->logger->debug("nsadmin: Error in reloading the gateway configurations");
				}
			}
		
		}
          
		$this->data['add_credits_form'] = $addcreditsForm;


		# Activate Modules form
		$form = new Form('activate_module', 'post');
		$form->addField('select', 'module', 'Module', $this->getModulesOptions());
		$form->addField('checkbox', 'active', 'Active', true);
		//var_dump($form);
		//die("created form");
        $store_db= new Dbase('stores');
		if ($form->isValidated()) {
			$params = $form->parse();
			$module_id = $this->db->query_scalar("SELECT id FROM `store_management`.`modules` WHERE `code` = '$params[module]' LIMIT 1");

			if ($org_id == '' || $module_id == '') {
				$this->flash("Some error occurred. Pls come back later");
				$this->route('index');
			}

			if ($params['active']) {
				$sql = "INSERT INTO `store_management`.`active_modules` (org_id, module_id, active, created) VALUES ('$org_id', '$module_id', 1, NOW()) ON DUPLICATE KEY UPDATE active = 1";
				$this->flash($this->db->update($sql) ? "Activated" : "Some error occurred");
			} else {
				$sql = "UPDATE `store_management`.`active_modules` SET active = 0 WHERE `org_id` = '$org_id' AND `module_id` = '$module_id'";
				$this->flash($this->db->update($sql) ? "Deactivated" : "Some error occurred");
			}
			
			try{

				$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$modManagerKey.'_ACTIVE_MODULE_FOR_ORG_ID_'.$org_id;
				$this->mem_cache_manager->delete( $cache_key );
			}catch( Exception $e ){ 
				
				$this->logger->debug( 'ERROR:'.$e->getMessage());
			}
		}

		$current_blog = $this->db->query_scalar("SELECT microsite_id FROM microsite_mapping WHERE org_id = '$org_id'");
		if ($current_blog == false) $current_blog = -1;

		$wpmu_db = new Dbase('wpmu');
		$blogs_options = array();
		$res = $wpmu_db->query('SELECT * FROM wp_blogs');
		$blogs_options["NONE"] = "-1";
		foreach ($res as $row) {
			$name = "BlogID$row[blog_id], SiteID$row[site_id] Path - $row[path]";
			$blogs_options[$name] = $row['blog_id'];
		}

		$blog_form = new Form('set_blog_form', 'post');
		$blog_form->addField('select', 'blog_id', 'Blog ID', $current_blog, array('list_options' => $blogs_options));
		if ($blog_form->isValidated()) {
			$params = $blog_form->parse();
			if ($params['blog_id'] == -1) {
				$sql = "DELETE FROM microsite_mapping WHERE org_id = '$org_id'";
			} else {
				$sql = "INSERT INTO microsite_mapping (org_id, microsite_id, created_by, created_time) "
				. " VALUES ($org_id, $params[blog_id], ".$this->currentuser->user_id.", NOW())";
			}
			$ret = $this->db->update($sql);
			if ($ret) $this->flash("Successful");
			else $this->flash("Some error occurred");
		}
		
		$org_sms_model->load( $org_id );
		$result = $org_sms_model->getHash();
		
		$credit_log = $org_sms_model->getCreditsLog( );
        
		$this->data['blog_form'] = $blog_form;
		$this->data['activate_module_form'] = $form;
		$this->data['credits_table'] = $this->createTableForSMSCredits($result );
		$this->data['credits_log'] = $this->createTableForCreditsLog( $credit_log );
		$this->data['modules_table'] = $this->db->query_table("SELECT m.name AS 'Module' FROM `store_management`.`active_modules` am JOIN `store_management`.`modules` m ON am.module_id = m.id AND am.active = 1 WHERE am.org_id = '$org_id'");

		//edit the organization details...
		$org = new OrgProfile($org_id);
		$form = new Form('org_details', 'post');
		
		$form->addField('text', 'address', 'Address (H.O.)', $org->address);
		//$form->addField('text', 'landline_phone', 'Landline phone number', $org->phone);
		//$form->addField('text', 'manager_name', 'Name of the Manager', $org->manager_name);
		//$form->addField('text', 'manager_mobile', 'Mobile number of Manager', $org->manager_mobile, '', Util::$mobile_pattern, 'Invalid mobile');
		//$form->addField('text', 'manager_email', 'Email address of Manager', $org->manager_email, '', Util::$email_pattern, 'Invalid email');
		//$form->addField('text', 'reporting_mobile', 'Mobile number for reporting', $org->reporting_mobile, '', Util::$mobile_pattern, 'Invalid mobile');
		//$form->addField('text', 'reporting_email', 'Email address for reporting', $org->reporting_email, '', Util::$multiple_email_pattern, 'Invalid email(s)');
		$form->addField('checkbox', 'ndnc', 'NDNC filter', $org->is_ndnc);
		$form->addField('select', 'time_zone', 'Time Zone', Util::getDefaultTimeZoneId( $org_id ), array('list_options' => Util::getSupportedTimeZones(), 'help' => 'Time Zone for the organization'));
		$form->addField('text', 'min_sms_hour', 'Send SMS after ', $org->min_sms_hour, array('help_text' => 'Sms is not sent before this HOUR. 24 Hour. eg: when its 8, so no sms is sent before 8am'));
		$form->addField('text', 'max_sms_hour', 'Send SMS before ', $org->max_sms_hour, array('help_text' => 'Sms is not sent after this HOUR. 24 Hour. eg: when its 21, so no sms is sent after 10pm'));
		
		if ($form->isValidated()) {
			$params = $form->parse();
			$after_sms_val = $params['min_sms_hour'];
			$before_sms_val = $params['max_sms_hour'];
			
			$params['time_zone'] = Util::getTimeZoneDetails($params['time_zone'] );
			
			if( $before_sms_val > $after_sms_val ){

				$mgr_mobile = $params['manager_mobile'];
				Util::checkMobileNumber($mgr_mobile); //always returns true since regex has already verified
				$reporting_mobile = $params['reporting_mobile'];
				Util::checkMobileNumber($reporting_mobile); //same as above
				$sql = "
					UPDATE masters.organizations 
						SET 
						is_ndnc = $params[ndnc],
						time_zone = '$params[time_zone]',
						min_sms_hour = '$params[min_sms_hour]',
						max_sms_hour = '$params[max_sms_hour]' 
					WHERE id = '$org_id' 
				";
				if ($this->db->update($sql)){
					
					$this->flash("Update Successful");
					//invalidate organization
					$cache_key = 'o'.$org_id.'_'.CacheKeysPrefix::$orgProfileKey.'BASE_LOAD_ARRAY_'.$org_id;
					try{
						
						$this->mem_cache_manager->delete( $cache_key );
						
					}catch( Exception $e ){
			
						$this->logger->error( 'Key '.$key.' Could Not Be Deleted .' );
					}
					
					//Update the time zone for all the stores
					
					$sql2 = 
						"
							UPDATE `stores_info` si,
								masters.organizations o 
							SET `time_zone_offset` = o.time_zone 
							WHERE si.org_id = o.id AND si.`time_zone_offset` = o.time_zone AND si.org_id = '$org_id'";
					
					$this->db->update($sql2);
				}
				else
				$this->flash("Some error occurred");
			}else
				$this->flash( 'Send SMS Before Hrs. Must be Greater Then Send SMS After Hrs.' );
		}
		$this->data['edit_form'] = $form;
	
	
	}


	function enablesourceAction(){
		$org_id  = $this->currentorg->org_id;
		$enabled_by = $this->currentuser->user_id;
		$form = new Form('enable_source','post');
		$form->addField('select', 'org_id', 'Organizations', $this->getOrganizationsAsOptions());
		$form->addField('select', 'source_id', 'Sources', $this->getSourcesAsOptions());

		if($form->isValidated())
		{
			$params = $form->parse();

			$sql = "INSERT INTO `enabled_sources` (source_id, org_id, date_enabled, enabled_by) VALUES ('$params[source_id]', '$params[org_id]', NOW(), '$enabled_by')
			ON DUPLICATE KEY UPDATE `source_id` = '$params[source_id]',`date_enabled` = NOW()";

			if ($this->db->update($sql))
			$this->flash("Sources enabled successfully");
			else
			$this->flash("Some problem occurred. Please try again later");
		}
		$this->data['enable_source'] = $form;
		$this->data['source_table'] = $this->db->query_table("SELECT s.source,e.date_enabled,u.username,o.name FROM  sources s join enabled_sources e on s.id = e.source_id join users u on u.id = e.enabled_by join organizations o on o.id = e.org_id ");


	}

	function priorityAction ($messageId) {
		$priority = $this->db->query_scalar ("SELECT priority FROM msging.outboxes WHERE messageId = '$messageId'");
		$priority ++;
		$sql = "UPDATE msging.outboxes SET priority = '$priority' WHERE messageId = '$messageId'";
		if ($this->db->update($sql)) $this->flash("Success"); else $this->flash("Failure");
		Util::redirect("orgadmin", "systemstatus");
	}

	function interruptAction ($messageId) {
		$status = $this->db->query_scalar ("SELECT status FROM msging.outboxes WHERE messageId = '$messageId'");
		if ($status == 'paused') $status = 'opened';
		else $status = 'paused';
		$sql = "UPDATE msging.outboxes SET status = '$status' WHERE messageId = '$messageId'";
		if ($this->db->update($sql)) $this->flash("Success"); else $this->flash("Failure");
		Util::redirect("orgadmin", "systemstatus");
	}

	function cancelAction ($messageId) {
		$sql = "UPDATE msging.outboxes SET status = 'closed' WHERE messageId = '$messageId'";
		if ($this->db->update($sql)) $this->flash("Success"); else $this->flash("Failure");
		Util::redirect("orgadmin", "systemstatus");
	}

	function systemstatusAction() {
		$ndb = new Dbase('nsadmin');
		$arr = array();

		//$statuses = array('INSERTED', 'RETRIEVING', 'DELIVERING');
		$statuses = array('RECEIVED', 'READ', 'SENDING');
		/*foreach ($statuses as $s) {
			$arr["Messages in $s"] = $ndb->query_scalar("SELECT COUNT( * ) FROM messages WHERE STATUS =  '$s'");
		}*/
		/*$statusCnts = $ndb->query_hash("
			SELECT `STATUS` AS st, COUNT(*) AS stCnt 
			FROM messages 
			WHERE STATUS IN ('".implode("','", $statuses)."')
			GROUP BY `STATUS`
			",
			'st', 'stCnt'
		);
		foreach($statusCnts as $st => $stCnt)
			$arr["Messages in $st"] = $stCnt;*/
		
		
		
		//$gws = array('NONE', 'valuefirst', 'fastalerts', 'spicesmpp');
		$gws = array('NONE', 'valuefirst', 'valuefirstbanking', 'valuefirstintnl', 'valuefirstndncbulk', 'valuefirstintnlbulk', 'valuefirstbulk', 'valuefirstndnc', 
					 'solutionsinfini', 'solutionsinfinibulk', 'cardboardfishsmpp', 'cardboardfishsmppbulk' , 'tobeprecisesmpp', 'tobeprecisesmppbulk');
		/*foreach ($gws as $gw) {
			 $cnt = $ndb->query_scalar("SELECT COUNT( * ) FROM messages WHERE (STATUS = 'SENDING' OR STATUS = 'READ') AND gateway =  '$gw'");
			 if($cnt > 0)
			 	$arr["Messages in GW-$gw"] = $cnt;				
		}*/
		/*$gwMsgsCnt = $ndb->query_hash("
			SELECT gateway, COUNT(*) AS gwcnt
			FROM messages 
			WHERE (`STATUS` IN ('SENDING','READ') ) 
			AND `gateway` IN ('".implode("','", $gws)."')
			GROUP BY gateway
			HAVING COUNT(*) > 0
		", 'gateway', 'gwcnt');
		foreach($gwMsgsCnt as $gw => $gwCnt)
			$arr["Messages in GW-$gw"] = $gwCnt;*/
		
		
		$mdb = new Dbase('msging');
		$arr['Messages queued up in MSGING inboxes'] = $mdb->query_scalar("SELECT COUNT(*) FROM inboxes WHERE processedTime IS NULL");

		$tbl = new Table('System Status');
		$tbl->importFromKeyValuePairs($arr, 'K', 'V');

		$this->data['system_status'] = $tbl;

		$interrupt_url = Util::genUrl('orgadmin', 'interrupt');
		$cancel_url = Util::genUrl('orgadmin', 'cancel');
		$priority_url = Util::genUrl('orgadmin', 'priority');
		$outbox_status_sql = "SELECT o.messageId, o.messageText, o.createdTime, COUNT( * ) AS num_queued_in_inboxes,"
						." CONCAT(o.priority,' (<a href=$priority_url/',o.messageId,'>^</a>)') as priority, o.status,"
						." CONCAT('<a href=$interrupt_url/',o.messageId,'>'"
						." ,case when o.status = 'paused' then 'Resume' else 'Pause' END,'</a>') as 'Interrupt',"
						." CONCAT('<a href=$cancel_url/',o.messageId,'>Cancel</a>') as 'Cancel'"
						." FROM  `outboxes` o "
						." LEFT JOIN inboxes i ON o.messageId = i.messageId "
						." WHERE STATUS !=  'closed' "
						." GROUP BY o.messageId ORDER BY o.priority DESC";
		$this->data['outbox_status_table'] = $mdb->query_table($outbox_status_sql);
		
		
		$move_gw_form = new Form('move_gw_form');
		$move_gw_form->addField('select', 'gw_move', 'Gateway to move to INSERTED', '', array('list_options' => array_flip($gws)));
		$move_gw_form->addField('checkbox', 'retrieving_move', 'Move Messages from RETRIEVING to INSERTED');
		if ($move_gw_form->isValidated()) {
			$gw_to_move = $gws[$move_gw_form->params['gw_move']];
			if ($gw_to_move != 'NONE') {
			$sql = "UPDATE messages SET status = 'INSERTED' WHERE status = 'DELIVERING' AND gateway = '$gw_to_move'";
			if ($ndb->update($sql)) $this->flash("Success"); else $this->flash("Failure");
			}
			
			if ($move_gw_form->params['retrieving_move']) {
				$ndb->update("UPDATE messages SET `status` = 'INSERTED' WHERE status = 'RETRIEVING'");
			}
		}
		
		$this->data['move_gw_form'] = $move_gw_form;

		$status = Util::getStatusOfNSAdmin();
		$this->data['nsadmin_status'] = $status;
		$status = Util::getStatusOfMsging();
		$this->data['msging_status'] = $status;
		
		$nsadmin = new Dbase('nsadmin');
		$table = $nsadmin->query_table("SELECT id, message, priority, status, sent_time, received_time,  CONCAT(time_to_sec(timediff(sent_time, received_time)),'s') as time_taken, gateway FROM `messages` WHERE priority = 'HIGH' ORDER BY `id` DESC LIMIT 5");
		$this->data['nsadmin_table'] = $table;
		
		//Very Slow :|
		/*
		$msging =new Dbase('msging');
		$table = $msging->query_table("SELECT t1.*, ROUND(t1.messages/t1.time_taken,2) as rate_per_second 
		FROM 
		(SELECT t.messageId, t.messageText, t.numDeliveries as messages, t.createdTime, messages.sent_time as finished_time, time_to_sec(timediff(messages.sent_time, t.createdTime)) as time_taken
		FROM
		(SELECT outboxes.messageId, messageText, numDeliveries, createdTime, max(inboxes.id) as inbox
		FROM 
		(SELECT * FROM `outboxes` WHERE type='SMS' AND status = 'closed' ORDER BY messageId DESC LIMIT 5) as outboxes 
		JOIN inboxes ON outboxes.messageId = inboxes.messageId GROUP BY outboxes.messageId) as t
		JOIN nsadmin.messages as messages ON t.inbox = messages.inbox_id) as t1 ORDER BY t1.messageId DESC");
		$this->data['msging_table'] = $table;*/
		
		//============================================================================
		//Calculate the RatePerSecond
		$msging = new Dbase('msging');
		//get message ID the last 5 bulk SMS blasts
		$latestMessageIds = $msging->query_firstcolumn("
			SELECT messageId
			FROM `outboxes` 
			WHERE type = 'SMS' 
			AND status = 'closed' 
			ORDER BY messageId DESC 
			LIMIT 5
		");
		
		//get the max 
		$maxInboxIds = $msging->query_firstcolumn("
			SELECT MAX(i.id) as max_inbox_id
			FROM msging.inboxes i 
			WHERE i.messageId IN (".Util::joinForSql($latestMessageIds).")
			GROUP BY i.messageId
		");
		
		$this->data['msging_table'] = $msging->query_table("
			SELECT o.messageId, o.messageText, o.numDeliveries as num_messages, o.createdTime, 
				m.sent_time as finished_time, time_to_sec(timediff(m.sent_time, o.createdTime)) as time_taken, 
				ROUND(o.numDeliveries/time_to_sec(timediff(m.sent_time, o.createdTime)),2) as rate_per_second
			FROM nsadmin.messages m
			JOIN msging.inboxes i ON i.id = m.inbox_id
			JOIN msging.outboxes o ON o.messageId = i.messageId AND o.messageId IN (".Util::joinForSql($latestMessageIds).")
			WHERE m.inbox_id IN (".Util::joinForSql($maxInboxIds).")
			ORDER BY o.messageId DESC
		");		
		//============================================================================
		
	}
	
	
	function deletemappingAction ($id) {
		$ret = $this->db->update("DELETE FROM sms_mapping where id = $id");
		if($ret == true){
			$this->flash("Deleted.");
		}
		else{
			$this->flash("Some error encountered.");
		}
		Util::redirect("orgadmin", "index");
	}
	
	function changemappingAction ($id){
		
		$mapping = $this->db->query_firstrow("SELECT * from sms_mapping WHERE `id` = '$id'");
		$form = new Form('mapping_details', 'post');
		$form->addField('text', 'shortcode', 'Shortcode', $mapping['shortcode']);
		$form->addField('text', 'command', 'Command', $mapping['command']);
		$form->addField('select', 'org_id', 'Organization', $mapping['org_id'], array('list_options' => $this->getOrganizationsAsOptions()));
		$form->addField('select', 'action_id', 'Action', $mapping['action_id'], array('list_options' => $this->getOfflineActionsAsOptions()));
		$form->addField('textarea', 'notes', 'Notes', $mapping['notes']);
		$form->addField('text', 'whoami', 'Who Am I ', $mapping['whoami'], array('help_text' => 'Should be supplied in the request, when some value is provided'));
		if ($form->isValidated()) {
			$params = $form->parse();
			$sql = "UPDATE `sms_mapping` SET `shortcode` = '$params[shortcode]', `command` = '$params[command]', `org_id` = '$params[org_id]', `action_id` = '$params[action_id]', `notes` = '$params[notes]', `whoami` = '$params[whoami]' WHERE `id` = '$id'";
			if ($this->db->update($sql)) {
				$this->flash("Changed successfully");
			} else
				$this->flash("Some error occurred. Pls try again later");
		}
		$this->data['change_mapping'] = $form;
	}

	function createmappingAction () {
		$form = new Form('mapping_details', 'post');
		$form->addField('select', 'type', 'Type', 'SMS', array('list_options' => array('SMS', 'MISSED_CALL')));
		$form->addField('text', 'shortcode', 'Shortcode');
		$form->addField('text', 'command', 'Command');
		$form->addField('select', 'org_id', 'Organization', $this->getOrganizationsAsOptions());
		$form->addField('select', 'action_id', 'Action', $this->getOfflineActionsAsOptions());
		$form->addField('textarea', 'notes', 'Notes');
		$form->addField('text', 'whoami', 'Who Am I ', '', array('help_text' => 'Should be supplied in the request, when some value is provided'));
		if ($form->isValidated()) {
			$params = $form->parse();
			$sql = "INSERT INTO `sms_mapping` (`type`, `shortcode`, `command`, `org_id`, `action_id`, `notes`, `whoami`) VALUES ('$params[type]', '$params[shortcode]', '$params[command]', '$params[org_id]', '$params[action_id]', '$params[notes]', '$params[whoami]')";
			if ($this->db->insert($sql)) {
				$this->flash("Created successfully");
			} else
				$this->flash("Some error occurred. Pls try again later");
		}
		$this->data['create_mapping'] = $form;
		$this->data['mapping_table'] = $this->db->query_table ("
			SELECT sms_mapping.id, type, shortcode, command, whoami, organizations.name as orgName, a.name as actionName, `notes` 
			FROM sms_mapping, organizations, `store_management`.actions  a
			WHERE sms_mapping.org_id = organizations.id AND sms_mapping.action_id = a.id
			ORDER BY sms_mapping.org_id, sms_mapping.id
		");
		$this->data['mapping_table'] ->createLink ('Change', Util::genUrl('orgadmin', 'changemapping/{0}'), 'Change', array(0 => 'id'));
		$this->data['mapping_table'] ->createLink ('Delete', Util::genUrl('orgadmin', 'deletemapping/{0}'), 'Delete', array(0 => 'id'));
	}

	function listmodulesAction(){
		$store_db = new Dbase('stores');
		$this->data['modsTable'] = $store_db->query_table("SELECT * from `modules` where id != 27", 'list_modules');
		$this->data['modsTable']->createLink('addAction', Util::genUrl('orgadmin', 'addaction/?module_id={0}&module_name={1}'), 'Add Action', array( 0 => "id", 1=> "name"));
		$this->data['modsTable']->createLink('addPageGroups', Util::genUrl('orgadmin', 'addpagegroup/?module_id={0}&module_name={1}'), 'Add Page Group', array( 0 => "id", 1=> "name") );
		
		$this->data['actionsTable'] = $store_db->query_table("SELECT actions.id, modules.name  as moduleName, actions.name, actions.code, actions.description, actions.visibility, permissions.name as PermissionName from modules, actions,action_permissions, permissions where actions.module_id = modules.id AND permissions.id = action_permissions.permission_id AND action_permissions.action_id = actions.id AND  modules.id != 27", 'list_actions');
		//$this->data['actionsTable']->createLink('deleteAction', Util::genUrl('orgadmin', 'deleteaction/?action_id={0}&action_name={1}'), 'Delete Action', array( 0 => "id", 1=> "name"));
		$this->data['actionsTable']->createLink('change_permission', Util::genUrl('orgadmin', 'changeaction/{0}'), 'Change', array(0 => 'id'));
		
		$sql = "SELECT resources.id, modules.name  as moduleName, resources.name, 
						resources.code, resources.description, resources.visibility 
				FROM modules, resources 
				WHERE 	resources.module_id = modules.id AND modules.id  != 27";
		
		$this->data['resourcesTable'] = $store_db->query_table( $sql, 'resource' );
		$this->data['resourcesTable']->createLink('change_name', Util::genUrl('orgadmin', 'changepagegroupname/{0}'), 'Change', array(0 => 'id' ));
	}
	
	function changepagegroupnameAction( $id ){

		$sql = "SELECT * 
				FROM `store_management`.resources   
				WHERE `id` = $id";
		
		$data = $this->db->query_firstrow( $sql );
		$form = new Form('form');
		
		$form->addField( 'text', 'resource_name', 'Page Group Name', $data[name], '', '/.+/' );
		$form->addField( 'text', 'resource_visibility', 'Page Group Visibility', $data[visibility], '', '/^\d+$/' );
		
		if( $form->isValidated() ){
			
			$params = $form->parse();
			$name = $params['resource_name'];
			$visibility  = $params['resource_visibility'];
			$sql = "
					UPDATE `store_management`.resources
					SET `name` = '$name', `visibility` = '$visibility'
					WHERE `id` = $id
			";
			
			$status = $this->db->update( $sql );
			
			if( $status ){
				
				try{
	
					
					$sql = " SELECT  modules.code  
							FROM `store_management`.modules, `store_management`.resources 
							WHERE resources.module_id = modules.id AND resources.id = $id";
					$module_code = $this->db->query_scalar( $sql );
					
					$cache_key = 'oa_'.CacheKeysPrefix::$modManagerKey.'_LOAD_RESOURCE_MODULE_CODE_'.$module_code;
					$this->mem_cache_manager->delete( $cache_key );
					
				}catch( Exception $e ){
					
					$this->logger->error( 'Keys could not be deleted' );
				}
				
				Util::redirect( $this->module, 'listmodules', '','Page Group Name Updated' );
			}else{
				
				Util::redirect( $this->module, 'listmodules', '','Page Group Name Could Not Be Updated' );
			}
		}
		
		$this->data['form'] = $form;
	}
	
	function invalidNumbersAction() {
		$me = $this->currentuser->user_id;
		$db = new Dbase('nsadmin');
		$form = new Form('invalid_numbers', 'post');
		$form->addField('textarea', 'invalid_numbers', 'Write Invalid Numbers here (one on every line)', '', array('rows' => 50));
		if ($form->isValidated()) {
			$numbers_list = $form->params['invalid_numbers'];
			
			$nums = explode("\n", $numbers_list);
			
			foreach ($nums as $n) {
				$n = trim($n);
				
				if (Util::checkMobileNumber($n)) {
				echo "Running on $n";
					$sql = "INSERT INTO undelivered_numbers (`mobile`, `added_by`, `added_date`) "
					 . " VALUES ('$n', '$me', NOW())";
				$db->insert($sql);
				}
			}	
		}

		$this->data['invalid_numbers_table'] = $this->db->query_table("SELECT o.name as org_name, COUNT( * ) AS count" 
						." FROM  `users` e "
						." JOIN nsadmin.undelivered_numbers u ON e.mobile = u.mobile "
						." JOIN organizations o ON e.org_id = o.id "
						." GROUP BY org_name HAVING count > 10");
		
		$this->data['invalid_loyalty_table'] = $this->db->query_table("SELECT o.name as org_name, COUNT( * ) AS count" 
						." FROM  `extd_user_profile` e "
						." JOIN nsadmin.undelivered_numbers u ON e.mobile = u.mobile "
						." JOIN organizations o ON e.org_id = o.id "
						." GROUP BY org_name HAVING count > 10");
						
		$this->data['invalid_loyalty_store_wise'] = $this->db->query_table(
						 " SELECT o.name as org_name, s.username as registered_store, e.mobile, l.joined AS joined_date, l.last_updated "
						." FROM  `users` e JOIN loyalty l ON e.id = l.user_id "
						." JOIN nsadmin.undelivered_numbers u ON e.mobile = u.mobile "
						." JOIN organizations o ON o.id = e.org_id "
						." JOIN users s ON l.registered_by = s.id "
						." ORDER BY org_name DESC, registered_store DESC");
						
						
		
		$this->data['invalid_numbers_form'] = $form;
		
		
	}

	/* function that send out PE store addresses in a zip code */
	public function sendStoreAddressSmsAction ($mobile, $org_id, $argument) {
		$argument = str_replace(' ', '', $argument);
		$zipcode = substr($argument, 0, 3).' '.substr($argument, 3, 6);
		$sql = "SELECT address FROM `webstore`.`stores_list` WHERE `address` LIKE '%$zipcode%'";
		$message = $this->db->query_scalar($sql);
		if ($message == false) {
			$zipcode = substr($zipcode, 0, 5);
			$sql = "SELECT address FROM `webstore`.`stores_list` WHERE `address` LIKE '%$zipcode%'";
			$message = $this->db->query_scalar($sql);
			if ($message == false)  $message = "There are no stores located around this zip code.";
		}
		Util::sendSms($mobile, $message, $org_id, MESSAGE_PRIORITY);
		echo ("orgadmin/sendStoreAddressSms: The store address has been sent.\n");
	}
	
	function removecachefileAction($org_id, $module, $action){

		$form = new Form('remove_cache_files', 'post');
		$orgs = $this->getOrganizationsAsOptions();
		$orgs = array_flip($orgs);
		$form->addField('checkbox', 'confirm', "Remove Cache Files for <b>".$orgs[$org_id]." $module/$action </b>? ", false);

		if($form->isValidated()){
			
			$params = $form->parse();
			
			if($params['confirm']){
				
				if(Util::removeCacheFile($org_id, $module, $action)){
					$status = "Removed cache files successfully for ".$orgs[$org_id]." $module/$action";
				}else{
					$status = "Unable to remove cache file";
				}
				
			}else{
				
				$status = "Checkbox unchecked, Did not remove any cache files";
			}
			
			Util::redirect('orgadmin', 'cachefilemanagement', false, $status);
		}	

		$this->data['confirm_form'] = $form;
	}
	
	function cachefilemanagementAction(){

		//Create a table for all the cache files present in the cache directory
		
		//Find all the files in the cache directory
		//Each file is of the form
		//$cachefile = $cachedir . DIRECTORY_SEPARATOR . $currentorg->org_id . '_' . $module . '_' .$action . '_' . $urlhash . '.' . $urlParser->getReturnType();
		$cachedir = $_ENV['DH_WWW'] . DIRECTORY_SEPARATOR . 'cache';
		
		$table_data = array();
		if($cache_dir_handle = opendir($cachedir)){
			
			//Directory opened
			
			//Now fetch all files which with a different module action pair
			$file_hash = array();
			
			$orgs = $this->getOrganizationsAsOptions();
			$orgs = array_flip($orgs);
			
			//Read each file
			while(false !== ($file = readdir($cache_dir_handle))){

				if ($file == "." || $file == "..")
					continue;
				
				//consider only xml files
				$file_name_splits = explode('.', $file);
				if($file_name_splits[count($file_name_splits) - 1] != 'gz')
					continue;
					
				$file_splits = explode('_', $file);
				//[0] = org_id
				//[1] = module
				//[2] = action
				
				//Show one row per orgid,module,action
				if(isset($file_hash[$file_splits[0]][$file_splits[1]][$file_splits[2]])){
					continue;
				}
				
				$file_ctime = filectime("".$cachedir.DIRECTORY_SEPARATOR.$file);
				//calculate recency
				$elapsed = Util::convertSeconds(time() - $file_ctime, true);
				$table_data_row = 	array(
										'org_id' => $file_splits[0],
										'organization' => $orgs[$file_splits[0]],
										'module' => $file_splits[1],
										'action' => $file_splits[2],
										'filesize' => round((filesize("".$cachedir.DIRECTORY_SEPARATOR.$file)/1000000), 2).'MB',				
										'created' => date("Y-m-d H:i:s", $file_ctime),
										'elapsed' => $elapsed
									);
									
				array_push($table_data, $table_data_row);
				
				//add entry in hash
				$file_hash[$file_splits[0]][$file_splits[1]][$file_splits[2]] = 1;
			}
		}
		
		$c_tab = new Table('cache');
		$c_tab->importArray($table_data);
		$c_tab->orderBy('organization');
		$c_tab->createLink('Delete', Util::genUrl('orgadmin', 'removecachefile/{0}/{1}/{2}'), 'remove', array('org_id', 'module', 'action'));
		$this->data['cache_files_table'] = $c_tab;
	}
	
	/*
	 * Function checks for certain configs against their expected value 
	 * and displays the organizations where the value is not as expected 
	 */	
	function configCheckAction(){
		
		$org_id = $this->currentorg->org_id;
		
		//array should contain the configs as key and the expected vaues as the key value
		$configs_array = array('CONF_CLIENT_DISABLE_CONTENT_CACHING' => 0, 
						  		'CONF_CLIENT_IS_SIDEBAR_HIDDEN' => 0,
								'CONF_CLIENT_BACKGROUND_NETWORK_REGISTER' => 1,
								'CONF_CLIENT_BACKGROUND_NETWORK_BILL_SUBMIT' => 1,
								'CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE' => 1,
								'CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE' => 1
						);
						  		
		$configs = "('". implode("','", array_keys($configs_array)). "')";
		
		$sql = "SELECT `od`.`key`, `org`.`name` AS organization, `od`.`org_id`, `od`.`value` " .
				"FROM `org_data` AS `od` " .
				"JOIN `organizations` AS `org` ON `org`.`id` = `od`.`org_id` " .
				"WHERE `od`.`key` IN $configs " .
				"ORDER BY `od`.`key`";
				
		$res = $this->db->query($sql);
		
		$error_orgs = array();
		foreach($res as $row){
			
			if ($row['value'] != $configs_array[$row['key']])
				array_push($error_orgs, $row);
		}
		
		$table = new Table();
		$table->importArray($error_orgs);
		
		$this->data['configs'] = $table;
	}
	
	
    function getLogsAction($tail = 500, $filter = ''){
                // Depricated
                return;
    }
    
    function process_logs(){
        return;
    }
    
	function getActionsForTheModuleAction($module){
                // Deprecated
                return;
	}
	
	function getStoresPerOrgAction($org_id, $include_all_option){
		$db = new Dbase('users');
		$sql = "SELECT store_id AS id, username FROM `stores` WHERE org_id = $org_id";
		$result = $db->query($sql);
		foreach($result as $row)
			$stores[$row['username']] = $row['id'];
		if($include_all_option)
			$stores['All'] = 'all';
		ksort($stores);
		$str = $this->js->constructOptionsForSelectBoxByArray($stores, 'multiple');
		$this->data['info'] = $str;
	}
    
	function getAllOrganizations(){
		
		$sql = " SELECT `name`,`id` FROM `organizations` WHERE `is_inactive` = '0'";
		return $this->db->query($sql);
	}

	function raymondPassword ($store, $password) {
		$store_id = $this->db->query_scalar ("SELECT id FROM users WHERE org_id = 93 and tag = 'org' and username = '$store'");
		$supplied_data['store_id'] = $store_id;
		$r = new Raymond($supplied_data);
		if (!$r->login($supplied_data))
			$r->login($supplied_data);
		return $r->changePassword($password);
	}

	function levisAction() {
		/*
		$password_form = new Form('password', 'post');
		$password_form->addField('text', 'username', 'Username');
		$password_form->addField('text', 'uid', 'UID');
		$password_form->addField('text', 'pwd', 'Password');
		$password_form->addField('text', 'shop_code', 'Shop Code');
		//$password_form->addField('text', 'default_pos', 'Default POS');
		//$password_form->addField('text', 'url', 'URL');
		if ($password_form->isSubmitted()) {
			$params = $password_form->parse();
			$username = $params['username'];
			$uid = $params['uid'];
			$pwd = $params['pwd'];
			$shop_code = $params['shop_code'];
			$default_pos = $params['shop_code'];//$params['default_pos'];
			$url = "clms";//$params['url'];
			if ($this->raymondPassword($username, $pwd)) {
				$this->db->update ("UPDATE raymond_stores SET pwd = '$pwd' WHERE username = '$username'");
				$this->flash("Password changed successfully");
			}
			else $this->flash("Password changing failed. Please try again");
			$this->db->update ("INSERT into raymond_stores (username, uid, pwd, shop_code, default_pos, url) VALUES('$username', '$uid', '$pwd', '$shop_code', '$default_pos', '$url') ON DUPLICATE KEY UPDATE uid = '$uid', pwd = '$pwd', shop_code = '$shop_code', default_pos = '$default_pos', url = '$url'");
		}
		$this->data['password_form'] = $password_form;
		$this->data['password_table'] = $this->db->query_table('SELECT username, uid, pwd, shop_code FROM raymond_stores');
		*/
		$poscode_form = new Form('poscode', 'post');
		$poscode_form->addField('text', 'local_name', 'Local Name');
		$poscode_form->addField('text', 'username', 'Intouch Username');
		if ($poscode_form->isSubmitted()) {
			$params = $poscode_form->parse();
			$local_name = $params['local_name'];
			$username = $params['username'];
			$this->db->update ("UPDATE levis_stores SET username = '$username' WHERE localname = '$local_name'");
		}
		$this->data['poscode_form'] = $poscode_form;
		$this->data['poscode_table'] = $this->db->query_table('SELECT * FROM levis_stores');
	}
    
	function smsLatencyAction ($mobile, $org_id, $argument) {
		$latency =  time() - trim($argument);
		$message = "Was found to be $latency seconds! Please do something.";
		if ($latency < 60) return;
		Util::sendEmail('dev@dealhunt.in,anant.choubey@capillary.co.in', 'SMS Latency', $message, $org_id);
	}		
	
	function dataqualityAction() {

		$form = new Form('data_quality');

		$form->addField('datepicker', 'start_date', 'Start Date', date('Y-m-01'));
		$form->addField('datepicker', 'end_date', 'End Date', date('Y-m-d'));
		$form->addField('checkbox', 'export', 'Export to CSV');

		if ($form->isValidated()) {

			$params = $form->parse();

			$start = Util::getMysqlDate($params['start_date']);
			$end =  Util::getMysqlDate($params['end_date']);

			$sql = "select o.name as organization, s.username as store, count(distinct a.id) as total_bills, "
				 . " count(distinct b.id) as no_of_bills_with_lineitems, " 
				 . " sum(case when b.item_code = '' OR b.item_code = 0 then 1 else 0 end) as item_code_missing, "
				 . " sum(case when b.description = '' OR b.description = 0 then 1 else 0 end) as description_missing, "
				 . " sum(case when b.rate <= 0 then 1 else 0 end) as rate_le_0, "
				 . " sum(case when b.qty <= 0 or qty >= 10 then 1 else 0 end) as qty_le_0_gt_10, "
				 . " sum(case when b.value <= 0 then 1 else 0 end) as value_le_0, " 
				 . " sum(case when b.rate*b.qty <> amount + discount_value then 1 else 0 end) as equation_error, " 
				 . " sum(case when b.amount <=0 then 1 else 0 end) as amount_missing "
				 . " FROM loyalty_log a LEFT JOIN loyalty_bill_lineitems b on b.loyalty_log_id = a.id AND a.org_id = b.org_id "
				 . " JOIN users s ON b.store_id = s.id "
				 . " JOIN organizations o ON b.org_id = o.id "
				 . " and a.date >= '$start' and a.date < DATE_ADD('$end', INTERVAL 1 DAY) "
				 . " group by b.org_id, b.store_id";
			if (!$form->params['export']) $sql .= " LIMIT 200";

			$table = $this->db->query_table($sql);
			
		if($params['export']){
			$spreadsheet = new Spreadsheet();
			$spreadsheet->loadFromTable($table)->download("Data Quality: From ", 'xls',$start.' to '.$end);
		}
		}
	
		$this->data['data_quality_form'] = $form;
		$this->data['data_quality_results'] = $table;
	}
	
	function runpesimulationAction(){

		$form = new Form('simulate_form', 'post');
		$scheme = "
Slab     Total Purchase      Num Visits
		 
Classic	     0- 2500               1
Silver	    2500-5000              2
Gold	    5000-10000             3
Platinum     >10,000	           4
		";
		$form->addField('textarea', 'scheme', 'Scheme', $scheme, array('rows' => '10', 'cols' => '50'));
		$form->addField('datepicker', 'from', 'Bills From', '');
		$form->addField('datepicker', 'till', 'Bills Till', '');
		
		if($form->isValidated()){
			
			$params = $form->parse();
			$from = $params['from'];
			$to = $params['till'];
			Util::redirect('orgadmin', 'peterenglandsimulation/'.$from.'/'.$to, false, '');
			
		}
		
		$reset_form = new Form('reset_form', 'post');
		$reset_form->addField('datepicker', 'from', 'Bills From', '');
		$reset_form->addField('datepicker', 'till', 'Bills Till', '');
		$reset_form->addField('checkbox', 'reset', 'Reset ? ', false, array('help_text' => 'Sets poitns, lifetime_points to 0. Slabs to NULL'));
		
		if($reset_form->isValidated()){
			
			$params = $reset_form->parse();
			if($params['reset']){
				$from = $params['from'];
				$to = $params['till'];
				Util::redirect('orgadmin', 'resetforpesimulation/'.$from.'/'.$to);
			}
		}
		
		$this->data['simulate_form'] = $form;
		$this->data['reset_form'] = $reset_form;
	}
	
	function resetforpesimulationAction($from_date, $to_date){


		//reset all PE data
		$sql = "
			UPDATE loyalty 
				SET slab_name = NULL, slab_number = NULL, loyalty_points = 0, lifetime_points = 0
			WHERE publisher_id = 29 ";//AND `last_updated` BETWEEN DATE('$from_date') AND DATE('$to_date')
		//";
		$this->db->update($sql);

		$sql = "
			UPDATE loyalty_log 
				SET points = 0
			WHERE org_id = 29 ";//AND `date` BETWEEN DATE('$from_date') AND DATE('$to_date')
		//";
		$this->db->update($sql);
		
		Util::redirect('orgadmin', 'runpesimulation', false, 'Done - Loyalty Table Updated');
	}
	
	function peterenglandsimulationAction($from_date, $to_date){

		//================================================================================

		//get everyone who has a bill within the range
		$sql = "SELECT DISTINCT(`user_id`)
				FROM `loyalty_log`
				WHERE `org_id` = 29
					AND `date` BETWEEN DATE('$from_date') AND DATE('$to_date')
			";
		$res = $this->db->query_firstcolumn($sql);

		//set everyone to CLASSIC
		$sql = "
			UPDATE loyalty
				SET slab_name = 'CLASSIC', slab_number = '0', loyalty_points = 0, lifetime_points = 0
			WHERE publisher_id = 29 AND `user_id` IN (".Util::joinForSql($res).")
		";
		$this->db->update($sql);
		
		//TODO 
		//$this->logger->debug("UPDATE QUERY : $sql");
		//================================================================================
		
		
		//================================================================================
		//get bills for customers who have made only a single bill
		//give only 3% of bill amount for the bill and 10 points as the points for the first visit 
		//================================================================================ 
		$sql = "
			SELECT `id` 
			FROM `loyalty_log` 
			WHERE `org_id` = 29 AND `date` BETWEEN DATE('$from_date') AND DATE('$to_date') AND `bill_amount` > 0
			GROUP BY `user_id` 
			HAVING COUNT( * ) = 1
		";
		$res = $this->db->query_firstcolumn($sql);
		
		$batch = array();
		$count = 0;
		$max = count($res);
		foreach($res as $ll_id){
			
			$count++;
			
			array_push($batch, $ll_id);
			
			if(($count % 5000 == 0) || $count == $max){
			
				//set points for everyone who has made only one bill and lifetime purchase is less than 2500		
				$sql = "
					UPDATE loyalty l, loyalty_log ll
						SET l.loyalty_points = CEIL(10 + (0.03 * ll.bill_amount) ), l.lifetime_points = CEIL(10 + (0.03 * ll.bill_amount) ), ll.points = CEIL((0.03 * ll.bill_amount))
					WHERE ll.org_id = 29 AND l.id = ll.loyalty_id AND ll.id IN (".Util::joinForSql($batch).")  			
				";
				
				$this->db->update($sql);
				//TODO 
				//$this->logger->debug("UPDATE QUERY : $sql");
				
				$batch = array();
			}
		}		
		//================================================================================		
		
		
		//================================================================================
		//Get all bills which were made after 1st Apr, where points is still 0, grouped by userid, bill_date
		//	Keep building hash of user_id, maintaining total purchase amount, slab and slab number..  assume initial slab as classic
		//  update slab when applicable..  based on lifetime purchases at the point and also the number of visits
		//	when updating loyalty profile / bill points..  award points for the visit based on the current slab 
		//================================================================================
		$loyalty_profile_hash = array();
		$sql = "
			SELECT user_id, DATE(`date`), GROUP_CONCAT( CONVERT(`id`, CHAR) ) as bill_ids, GROUP_CONCAT( CONVERT(bill_amount, CHAR) ) as bill_amounts 
			FROM `loyalty_log` 
			WHERE `org_id` = 29 AND `date` BETWEEN DATE('$from_date') AND DATE('$to_date') AND points = 0 AND bill_amount > 0
			GROUP BY `user_id`, DATE(`date`)
		";
		$res = $this->db->query($sql);

		$batch = "";
		$count = 0;
		$max = count($res);
		foreach($res as $r){
			
			$count++;
			
			$user_id = $r['user_id'];
			
			$bill_ids = explode(',', $r['bill_ids']);
			$bill_amounts = explode(',', $r['bill_amounts']);
			
			if(!isset($loyalty_profile_hash[$user_id])){
				$loyalty_profile_hash[$user_id]['total_purchases'] = 0;
				$loyalty_profile_hash[$user_id]['current_slab'] = 'CLASSIC';
				$loyalty_profile_hash[$user_id]['total_visits'] = 0;
			}
			
			$visit_points = 0;
			switch($loyalty_profile_hash[$user_id]['current_slab']){
				case 'CLASSIC' : $visit_points = 10;
								 break;
				case 'SILVER'  : $visit_points = 20;
								 break;
				case 'GOLD'  : $visit_points = 30;
								 break;
				case 'PLATINUM'  : $visit_points = 40;
								 break;								 
			}
			
			for($i = 0; $i < count($bill_ids); $i++){				
				
				$bid = $bill_ids[$i];
				
				$percent = '';
				switch($loyalty_profile_hash[$user_id]['current_slab']){
					case 'CLASSIC' : $percent = '0.03';
									 break;
					case 'SILVER'  : $percent = '0.05';
									 break;
					case 'GOLD'    : $percent = '0.07';
									 break;
					case 'PLATINUM': $percent = '0.10';
									 break;								 
				}
				
				//update the total purchase
				$loyalty_profile_hash[$user_id]['total_purchases'] += $bill_amounts[$i];
				
				$sql = "
					UPDATE loyalty l, loyalty_log ll
						SET l.loyalty_points = (l.loyalty_points + CEIL(('$percent' * ll.bill_amount)) + '$visit_points'),
							l.lifetime_points = (l.lifetime_points + CEIL(('$percent' * ll.bill_amount)) + '$visit_points'),
							ll.points = CEIL(('$percent' * ll.bill_amount))
					WHERE ll.org_id = 29 AND l.id = ll.loyalty_id AND ll.id = '$bid'";
				//Add update query to batch
				$batch .= "$sql; "; 
				
				if($visit_points != 0){
					//increment number of visits and check of slab upgrade
					$loyalty_profile_hash[$user_id]['total_visits'] += 1;
				}
					
				$slab_upgrade = false;
				$to_slab = "";
				$to_slab_number = "";
				
				//Slab Upgrade Condition For Peter England
				/*
				 * 
					Slab     Total      Num Visits
							 Purchase
					Classic	 0- 2500     1
					Silver	 2500-5000   2
					Gold	 5000-10000  3
					Platinum >10,000	 4
				 * 
				 * 
				 * */
				
				//check for platinum
				if(
					$loyalty_profile_hash[$user_id]['total_visits'] >= 4 &&
					$loyalty_profile_hash[$user_id]['total_purchases'] >= 10000 &&
					$loyalty_profile_hash[$user_id]['current_slab'] != 'PLATINUM'
				){
					$slab_upgrade = true;
					$to_slab = 'PLATINUM';
					$to_slab_number = '3';
					$loyalty_profile_hash[$user_id]['current_slab'] = $to_slab;
				}
				
				//check for gold
				if(
					$loyalty_profile_hash[$user_id]['total_visits'] >= 3 &&
					$loyalty_profile_hash[$user_id]['total_purchases'] >= 5000 &&
					$loyalty_profile_hash[$user_id]['total_purchases'] < 10000 &&
					$loyalty_profile_hash[$user_id]['current_slab'] != 'GOLD'
				){
					$slab_upgrade = true;
					$to_slab = 'GOLD';
					$to_slab_number = '2';
					$loyalty_profile_hash[$user_id]['current_slab'] = $to_slab;
				}
				
				//check for silver
				if(
					$loyalty_profile_hash[$user_id]['total_visits'] >= 2 &&
					$loyalty_profile_hash[$user_id]['total_purchases'] >= 2500 &&
					$loyalty_profile_hash[$user_id]['total_purchases'] < 5000 &&
					$loyalty_profile_hash[$user_id]['current_slab'] != 'SILVER'
				){
					$slab_upgrade = true;
					$to_slab = 'SILVER';
					$to_slab_number = '1';
					$loyalty_profile_hash[$user_id]['current_slab'] = $to_slab;
				}
				
				if($slab_upgrade){
					$sql = "
						UPDATE loyalty 
							SET slab_name = '$to_slab', slab_number = '$to_slab_number'
						WHERE publisher_id = 29 AND user_id = '$user_id'";						
					$batch .= "$sql; "; 
				}
					
				
				
				$visit_points = 0;				
				
			}
			
			if(($count % 1000 == 0) || $count == $max){

				
				$this->db->multi_query($batch, false);
				//TODO 
				//$this->logger->debug("UPDATE QUERY : $batch");
				
				$batch = "";				
			}
			
		}
		
		//================================================================================

		Util::redirect('orgadmin', 'runpesimulation', false, 'Done - Loyalty Table Updated');
	}
	
	
	function countriesAction($country_id = ''){
		
		$countryDetails = 
			$this->orgadminController->getCountryDetails($country_id);
		
		$form = new Form('createCountryAction', 'post');
		$form->addField('text', 'name', 'Country Name', $countryDetails['name'], $country_id != '' ? array('readonly' => true) : array('help_text' => 'Cannot be changed once created'));
		$form->addField('text', 'short_name', 'Short Name', $countryDetails['short_name'], array('help_text' => 'eg: IND for India'));
		$form->addField('text', 'mobile_country_code', 'Mobile Country Code', $countryDetails['mobile_country_code'], array('help_text' => 'eg: 91 for India'));
		$form->addField('text', 'mobile_regex', 'Mobile Number Regex', $countryDetails['mobile_regex'], array('help_text' => 'Without the country code. eg: [6-9][0-9]{9,9}	for India'));
		$form->addField('text', 'mobile_length', 'Mobile Length CSV', $countryDetails['mobile_length_csv'], array('help_text' => 'eg: 12 for India, Including Country Code. In Case of Multiple, 12,13,14'));
		
		if ($form->isSubmitted()) {
			$params = $form->parse();
			$ret = $this->orgadminController->addOrUpdateCountryDetails(
				$params['name'], $params['short_name'], $params['mobile_country_code'], 
				$params['mobile_regex'], $params['mobile_length'], $country_id
			);
			
			if($ret>0){
				$this->flash("Added/Updated Country Details");
			}
			else{
				$this->flash("Some error encountered");
			}
			
		}

		$this->data['createCountry_form'] = $form;
		
		$countries_table = $this->orgadminController->getAllCountries('query_table');
		$countries_table->createLink ('Edit', Util::genUrl('orgadmin', 'countries/{0}'), 'edit', array(0 => 'id'));		
		$countries_table->removeHeader('id');
		$this->data['countriesTable'] = $countries_table;

		
	}
	
	/*
	 * @author nayan
	 * This method is used to check the current sms credits with gateway.
	 * if $assosiative_array is true then it will accessed from outside scripts else it will access within serverside.
	 * it will return assosiative array of key value pair of credit information and send mail if credit lessthan threshold.  
	 */
	function viewcurrentcreditsAction($assosiative_array=false){
	$sms_accounts=$GLOBALS['cfg']['sms_accounts'];
		$credits_full=array();
		foreach($sms_accounts as $key => $values){
			if($values['is_active'] == true){
   				if($key!='' && $values['password']!= '' && $values['threshold'] != '' && $values['email'] != ''){
					$data="<?xml version='1.0' encoding='ISO-8859-1' ?><!DOCTYPE REQUESTCREDIT SYSTEM 'http://127.0.0.1/psms/dtd/requestcredit.dtd' >"
					."<REQUESTCREDIT USERNAME='".$key."' PASSWORD='".$values['password']."'></REQUESTCREDIT>";	
					$action = "credits";
					$ch = curl_init ("http://api.myvaluefirst.com/psms/servlet/psms.Eservice2");
					if (isset($_ENV['http_proxy'])){
						// proxy ip picked from send.php in portal/gw/send.php
						curl_setopt($ch, CURLOPT_PROXY, "144.16.192.247"); 
						curl_setopt($ch, CURLOPT_PROXYPORT, 8080); 
					}
					curl_setopt_array($ch, array(
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_POST => true,
						CURLOPT_HEADER => true,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_POSTFIELDS => "action=$action&data=$data"
					));
					$this->logger->debug("---------Request Header: ".$data." :End---------");
					$retry = 0 ;
					while($retry < 2){
		                $retry++;
						$output = curl_exec($ch);
						if($output != ''){
							break ;
						}
					}
					
					if($output != ''){
						$this->logger->debug("---------Response Header: ".$output." :End---------");
						
						$response=substr($output,strpos($output,'<?xml'));
						$this->logger->debug("---------Response : ".$response." :End---------");
						
						//Parse credits from response
						$xml_element = Xml::parse($response);
						$arr=array();
						foreach ($xml_element->Credit as $record){
						   $arr['User']=$xml_element['User'];
						   $arr['Limit']=$record['Limit'];
						   $arr['Used']=$record['Used'];
						}
						$credits=array($arr);
						if(!empty($credits)){
							$credits[0]['Remaining']=$credits[0]['Limit']-$credits[0]['Used'];		   	
							
							if($credits[0]['Remaining'] <= $values['threshold']){
								$message="SMS Account Credit Purchase Alarm";
								$body=$key." sms-account has ".$credits[0]['Remaining']." credit balance remaining.";
								$this->logger->debug("---------Inside Condition: ".$body." :End---------");
								$is_send=Util::sendEmail($values['email'],$message, $body,$this->currentorg->org_id);
								$this->logger->debug("---------After Sending: ".$is_send." :End---------");
							}
							array_push($credits_full,$credits[0]);
						}
						$this->logger->debug("Response End");
					}
					else{
						$error="Connection Timeout....Please Retry....";
						$error_flag=true;
					}
					curl_close($ch);
				}
				else{
					$error="Username,Password,Threshold and Email of each sms account should be present in Config file.";
					$error_flag=true;
				}
   			}	
		}
		if($error_flag == true){
			if($assosiative_array == true){
				return $error;
			}
			else{
				$this->flash($error);
			}
		}
		else{
			if($assosiative_array == true){
	   			return $credits_full;
			}
			else{
				$c_tab = new Table('credit');
				$c_tab->importArray($credits_full);
				$this->data['creditlog'] = $c_tab;
			}
		}
 	}
 	
 	
 	/*
 	 * 
 	 * PACKAGE MANAGEMENT
 	 * 
 	 */
 	function purchasablesAction($purchasable_type = '')
 	{
 		//Add purchasable by type
		$addForm = new Form('add_purchasable');
		$addForm->addField('select', 'purchasable_type', 'Add Purchasable Type', '', array('list_options' => PurchasableMgr::getSupportedPurchasableTypes()));
		if($addForm->isValidated())
		{
			$params = $addForm->parse();
			Util::redirect('orgadmin', 'purchasableaddoredit/'.$params['purchasable_type'], false);
		}
		$this->data['add_purchasable_form'] = $addForm;
		
 		
 		//purchasables table
 		$filterForm = new Form('filter_form', 'post');
 		$listOptions = array_merge(array('ALL'), PurchasableMgr::getSupportedPurchasableTypes());
		$filterForm->addField('select', 'purchasable_type', 'Filter by Purchasable Type', '', array('list_options' => $listOptions));
		if($filterForm->isValidated())
		{
			$params = $filterForm->parse();
			if($params['purchasable_type'] == 'ALL')
				$params['purchasable_type'] = '';
			Util::redirect('orgadmin', 'purchasables/'.$params['purchasable_type'], false);			
		}
		$this->data['filter_form'] = $filterForm;
		
		//Table of filtered purchasables
		$purchMgr = new PurchasableMgr();
		$purchasablesTable = $purchMgr->getPurchasablesTable($purchasable_type);		
		$purchasablesTable->createLink('Edit', Util::genUrl('orgadmin', "purchasableaddoredit/{0}/{1}"), 'edit', array(0 => 'type', 1 => 'id'));
		$purchasablesTable->createLink('Relations', Util::genUrl('orgadmin', "purchasablerelations/{0}"), 'relations', array(0 => 'id'));
				
		$this->data['purchasables_table'] = $purchasablesTable;
 	}
 	
 	function purchasableaddoreditAction($purchasable_type, $purchasable_id = '')
 	{

 		$addForm = new Form('add_purchasable_form', 'post');
 		$purchMgr = new PurchasableMgr();
 		$purchMgr->addPurchasableForm($addForm, $purchasable_type, $purchasable_id);
		
		if($addForm->isValidated())
		{			
			$ret = $purchMgr->addOrUpdatePurchasableFromForm($addForm);

			$status = $ret ? "Success" : "Failure";
			Util::redirect('orgadmin', 'purchasables', false, "Operation $status");
		}
		
		$this->data['add_purchasable_form'] = $addForm;
 	}
 	
 	function getPurchasablesForTypeAjaxAction($purchasable_type, $exclude_purchasable_id)
 	{
 		$purchMgr = new PurchasableMgr();
 		$purchasableOptions = $purchMgr->getPurchasablesAsOptionsForType($purchasable_type, $exclude_purchasable_id);
 		$str = $this->js->constructOptionsForSelectBoxByArray($purchasableOptions, 'multiple');
		$this->data['info'] = $str;
 	}
 	
 	function purchasablerelationsAction($purchasable_id, $rel_id = '')
 	{

 		$purchMgr = new PurchasableMgr();
 		
 		//Add relations form
 		$addOrEditRelsForm = new Form('addoreditrelsform', 'post');
 		$purchMgr->addPurchasableRelationForm($addOrEditRelsForm, $purchasable_id, $rel_id);
 		
 		if($addOrEditRelsForm->isValidated())
 		{
 			$ret = $purchMgr->addOrUpdatePurchasableRelationFromForm($addOrEditRelsForm);
 			$status = $ret ? "Success" : "Failure";
			$this->flash($status);	
 		}
 		
 		$this->data['add_relation_form'] = $addOrEditRelsForm;

 		
  		//Show the list of relations for this purchasable
 		$relationsTable = $purchMgr->getRelationsForPurchasableTable($purchasable_id);
 		$relationsTable->createLink('Edit', Util::genUrl('orgadmin', "purchasablerelations/{0}/{1}"), 'edit', array(0 => 'purch_id', 1 => 'relation_id'));
 		$relationsTable->removeHeader('purch_id');
 		$relationsTable->removeHeader('purchasable_from_id');
 		$relationsTable->removeHeader('purchasable_to_id');
 		$relationsTable->removeHeader('params');
 		$relationsTable->removeHeader('purchasable_from_id'); 		
 		$this->data['purchasable_relations_table'] = $relationsTable;
 	}
 	
	function showActionsInPermissionGroupsAction(){
		
		global $currentorg;
		$org_id = $currentorg->org_id;
		
		$db = new Dbase('stores');
		
		$sql = "SELECT name, id FROM modules WHERE `display_order` != -1";
		$module_list = $db->query_hash($sql, 'name', 'id');
		
		$select_form = new Form('');
		$select_form->addField('select', 'module', 'Module', $module_list);
		$select_form->addField('select', 'permission_level', 'Permission Level', array());
		
		$this->js->populateFieldWithAjax(
				$select_form->getFieldName('module'), $select_form->getFieldName('permission_level'), Util::genUrl('orgadmin', 'getpermissionsasparams'),
				array($select_form->getFieldName('module')), array(), 'select'
				);
		
		if($select_form->isValidated()){
			$params = $select_form->parse();
			
			$module_id = $params['module'];
			$permission_level = $params['permission_level'];
			
			$sql = "SELECT a.id, a.name, a.code, m.name as module, a.visibility
					FROM actions a
					JOIN action_permissions ap ON ap.action_id = a.id  
					JOIN permissions p ON ap.permission_id = p.id AND p.name = '$permission_level'
					JOIN modules m ON m.id = a.module_id
					WHERE a.module_id = $module_id";
			
			$result = $db->query_table($sql, 'actions');
			
			$result->createLink('Change Level', Util::genUrl('orgadmin', 'changeaction/{0}'), 'Change Permission', array(0 => 'id'));
			
			$this->data['actions_permissions'] = $result;
		}
		
		$this->data['permissions'] = $select_form;
	}
	
	function getPermissionsAsParamsAction($module_id = false){
		
		$db = new Dbase('stores');
		$perms = array();
		
		$sql = "SELECT p.name, p.id
				FROM permissions p
				JOIN modules m ON p.assoc_module = m.id
				WHERE p.assoc_module = $module_id";
		
		$result = $db->query($sql);
		foreach($result as $row)
			$perms[$row['name']] = $row['name'];
		
		ksort($perms);
		
		$str = $this->js->constructOptionsForSelectBoxByArray($perms, 'single');
		$this->data['info'] = $str;
		
	}
 	
	function createGroupTemplateAction(){
		
		$db = new Dbase('stores');
		
		$form = new Form('create_group_template', 'post');
		$form->addField('text', 'group_template_name', 'Group Template Name', '', '', Util::$username_pattern, 'Group name should be between 5-13 characters and no contain any spaces');
		
		if($form->isValidated()){
			$params = $form->parse();
			
			$group_template_name = $params['group_template_name'];
			$sql = "INSERT INTO group_templates
					(name)
					VALUES ('$group_template_name')";
			
			$group_template_id = $db->insert($sql);
			if($group_template_id)
				Util::redirect('accessmgmt', "configuregrouptemplatepermissions/$group_template_id", false, 'Configure the permissions');
			else
				$this->flash('Could not add the template.');
		}
		$this->data['group_templates'] = $form;
	}
	
	
 	//===================================================================================================
 	function paymentTypesAction(){
 		$form = new Form('payment_type','post');
 		$form->addField('text','type','Payment Type','','','/^[0-9a-zA-Z]+$/','Only Alphanumerics Allowed.','',true,true);
 		$form->addField('textarea','desc','Description');
 		
 		if($form->isValidated()){
 			$params = $form->parse();
 			$type_pay = $params['type'];
 			$desc = $params['desc'];
 			$status = $this->addPaymentType($type_pay,$desc);
 			 			
 			if($status)
 				$this->flash('Payment Type Added Successfully.');
 			else
 				$this->flash('Payment Type is already Exists.');
 		}
 		
 		$type_table = $this->showPaymentTypes();
 		
 		$this->data['form'] = $form;
 		$this->data['type_table'] = $type_table;
 	}
 	
 	private function addPaymentType( $type , $desc ){
 		
		$db = new Dbase('users');
 	
 		$sql = "insert into payment_types(type,description,added_on,added_by)"
 				." VALUES('".$type."','".$desc."',NOW(),".$this->currentuser->user_id.")";
 		
 		return $db->insert($sql);		
 	}
 	
 	private function showPaymentTypes($outtype='table'){

 		$db = new Dbase('users');
 		
 		$sql = "select distinct type,description,added_on from payment_types";
 		
 		if($outtype == 'table')
 			$result = $db->query_table($sql,'payment_type');
 		else
 			$result = $db->query($sql);
 			
 		return $result;
 	}

/*
 * Generates and sends nightly reports based on a store/zonal level to organisations as per set configurations (report time,store/zone) 
 * 
 */
	function nightlyreportsAction()
 	{
		$active_organizations_list=$this->db->query("SELECT `id` FROM `organizations` WHERE `is_inactive`=0");
		
		foreach ($active_organizations_list as $org)
		{
			$current_org = new OrgProfile($org['id']);
			$enable_nightly_reports_value = $current_org->getConfigurationValue('DAILY_NIGHTLY_ENABLE_NIGHTLY_REPORTS',false);
			
			if( ( $enable_nightly_reports_value ) && ( $enable_nightly_reports_value==1 ) ) 
			{
				$admin_store_id = $current_org->getConfigurationValue('DAILY_NIGHTLY_ADMIN_STORE');	
				$daily_nightly_level_value = $current_org->getConfigurationValue('DAILY_NIGHTLY_REPORT_LEVEL');	
				$daily_nightly_report_time = $current_org->getConfigurationValue('DAILY_NIGHTLY_REPORT_TIME');	

				$sql = " SELECT `username` 
						FROM `stores` 
						WHERE `store_id`=".$admin_store_id;

				$admin_store_username = $this->db->query_scalar( $sql );

				$report_time_array = explode( "-", $daily_nightly_report_time );
				
				$report_time_lower_limit=$report_time_array[0];


				$report_time_upper_limit=$report_time_array[1];

				if( ( date( "h:ia" ) >= $report_time_lower_limit ) && ( date( "h:ia" ) < $report_time_upper_limit ) )
				{
					$this->logger->debug("Beginning Daily Nightly Reports Generation for Organisation : ".$current_org->name);	
					
					if($daily_nightly_level_value=='zone')
						shell_exec("(php ".$_ENV['DH_WWW']."/shopbook-refactor/cli.php ".$admin_store_username." administration generatereportsforallzones)& >> ~/cron.nightlyreports.log 2>&1");
					else 
						shell_exec("(php ".$_ENV['DH_WWW']."/shopbook-refactor/cli.php ".$admin_store_username." administration generatereportsforallstores)& >> ~/cron.nightlyreports.log 2>&1");

					$this->logger->debug("Daily Nightly Reports completed for Organisation : ".$current_org->name);	
				}
				else
					$this->logger->debug('Task is scheduled for another time.');
					
				sleep(60);
					
			 }
			
		}

	}
	
	/**
	 * This is used by the CLI to construct
	 * CSV . using download manager. 
	 */
	public function exportCsvFrameworkAction(){

		$C_download_manager = new DownloadManager();
		$C_download_manager->execute();
	}
	
	/**
	 * get out the notification parts and run the cli
	 */
	public function reportNotificationSplitterAction(){

		$min = 0;
		$max = 4;//splits the cron into 10 or say for now 4. might increase in future.
		
		for( $i = 0 ; $i < $max ; $i++ ){
			
			shell_exec( "php ".$_ENV['DH_WWW']."/cheetah/cli.php -1 orgadmin preparereport > /dev/null 2>/dev/null &" );
			echo " php ".$_ENV['DH_WWW']."/cheetah/cli.php -1 orgadmin preparereport ";
			sleep( 15 );
		}
	}
	
	public function coummincateReportAction(){

		include_once 'helper/scheduler/notifications/base/CommunicationHandler.php';
		
		CommunicationHandler::$s_database = new MetaDbase( 'scheduler' );
		CommunicationHandler::execute();
	}
	
	/**
	 * each cron might take two jobs
	 * which again can be increased in future :)
	 * 
	 * Well This will move the new cli for cheetah where no password
	 * and username will be needed.
	 */
	public function prepareReportAction(){
		
		//$org_id = $this->currentorg->org_id;
		
		$this->logger->debug( 'Scheduler Is Inside The Prepare Report ACtion' );
		 
		$db = new MetaDbase( 'scheduler' );
		/* acquire memcache atomic lock */
		$sql = "
				SELECT `id` , `subject` , `org_id`
				FROM `notifications`
				WHERE `status` = 'RUNNING'
				LIMIT 1
		";
		
		
		$notification_details = $db->query( $sql );
		$level = count( $notification_details );
		$org_id = $notification_details[0][ 'org_id' ];
		
		
		if( $level > 0 ){

			$min_id = $notification_details[0]['id'];
			//$max_id = $notification_details[$level-1]['id'];
		}
		
		$subject[ 0 ] = $notification_details[ 0 ][ 'subject' ];
		//$subject[ 1 ] = $notification_details[ $level-1 ][ 'subject' ];
		
		$key_notification_id = 'o'.$org_id.'_'.CacheKeysPrefix::$bnsReportKey."$org_id"."__".$min_id;
		$lock_value = 1 ;	
		$ttl = 60 ;
		$lock_acquired = $this->mem_cache_manager->acquireLock( $key_notification_id , $lock_value , $ttl );
		
		if( !$lock_acquired )
			return ;
			
		$sql = "
				UPDATE `notifications`
				SET `status` = 'EXECUTING'
				WHERE `id` = $min_id
		";
		
		
		if( $min_id )
			$db->update( $sql );
		
		$notification_ids[0] = $min_id;
		//$notification_ids[1] = $max_id;
		$notification_values = array();
		$value = array();
		$i=-1;
		foreach( $notification_ids as $id ){

			$i++;
			if( !$id ) continue;
			
			include_once 'helper/scheduler/notifications/base/NotificationReportHandler.php';
			$this->logger->debug( 'Notification Handler Report execution with id '.$id );
			$key = 'o'.$org_id.'_'.CacheKeysPrefix::$bnsReportKey."$org_id";
			$ttl = CacheKeysTTL::$bnsReportKey;
			$this->logger->debug( " Memcache key for bns : " . $key );
			$key_for_value = $id . "__$subject[$i]";
			try
			{
				$this->logger->debug( " Memcache trying to get key " . $key );
				$notification_values = $this->mem_cache_manager->get( $key );
				$notification_values = json_decode( $notification_values , true );
				$this->logger->debug( " Memcache successfull in get with values " . print_r( $notification_values , true ) );
				$notification_values[ $key_for_value ] = 'EXECUTING';
				$this->logger->debug( " Memcache successfull in get with new notification id " . print_r( $notification_values , true ) );
				$notification_values = json_encode( $notification_values );
				try
				{
					$this->logger->debug( " Memcache trying to replace " );
				  	$this->mem_cache_manager->replace( $key , $notification_values , $ttl );
				}
				catch( Exception $e )
				{
					// what do i do here?
					$this->logger->debug( " Memcache replace failed . In catch of replace " .$e->getMessage() );
				}
				
				
			}
			catch( Exception $e )
			{
				$this->logger->debug( " Memcache get failed. In catch of get " . $e->getMessage() );
				$value[ $key_for_value ] = 'EXECUTING';
				$value = json_encode( $value );
				try
				{
					$this->logger->debug( " Memcache trying to set value in catch of get " . $value );
					$this->mem_cache_manager->set( $key , $value , $ttl );
				}
				catch( Exception $e )
				{
					$this->logger->debug( " Memcache set failed. In catch of set " . $e->getMessage() );
					// what do i do here?
				}
			}
			
			
			$C_notification = new NotificationReportHandler( $id );
			$status = $C_notification->execute();
			
			$this->logger->debug( " Execution status " . $status );
			//$value = array( $key_for_value => "$status" );
			$value[ $key_for_value ] = "$status";
			
		try
			{
				$notification_values = $this->mem_cache_manager->get( $key );
				$notification_values = json_decode( $notification_values , true );
				$this->logger->debug( " Memcache successfull in  get after execute with values " . print_r( $notification_values , true ) );
				$this->logger->debug( " Memcache array merge value : " . print_r( $value , true ) );
				$notification_values = array_merge( $notification_values , $value );
				$this->logger->debug( " Memcache successfull in get with after execute new notification id " . print_r( $notification_values , true ) );
				$notification_values = json_encode( $notification_values );
				try
				{
				  $this->mem_cache_manager->replace( $key , $notification_values , $ttl );
				}
				catch( Exception $e )
				{
					$this->logger->debug( " Memcache replace failed after execution " .$e->getMessage() );
					// what do i do here?
				}
				
				
			}
			catch( Exception $e )
			{
				$this->logger->debug( " Memcache failed in getting after execution " .$e->getMessage() );
				// what do i do here? 
			}
			
				
			$this->mem_cache_manager->releaseLock( $key_notification_id );
			
			if( $min_id == $max_id ) return true;
			
		}
		
	}
	
	public function sendReportMailAction()
	{
		$this->logger->debug( " Inside send email report mail action " );
		$db = new Dbase( 'masters' );
		
		$sql = " 
				 SELECT `id` 
				 FROM `masters`.`organizations` ";
		$org_ids = $db->query_firstcolumn( $sql );
		
		$success = "";
		$failure = "";
		$keys_exists = array();
		foreach( $org_ids as $org_id )
		{
			$key = 'o'.$org_id.'_'.CacheKeysPrefix::$bnsReportKey . "$org_id";
			$this->logger->debug( " Memcache Key " . $key );
			
			try
			{
				$this->logger->debug( " Memcache trying to get key " . $key );
				$notification_values = $this->mem_cache_manager->get( $key );
				array_push( $keys_exists , $key );
				$notification_values = json_decode( $notification_values , true );
				$this->logger->debug( " Memcache successfull in get with values " . print_r( $notification_values , true ) );
				
				foreach( $notification_values as $key => $value )
				{
					if( $value == 'ERROR' || $value == 'EXECUTING' )
					{
						$failure .= " Org : " . $org_id . " <br/> ";
						$failure .= $key . " --> <p color = 'red'>" . $value . "</p><br/> ";
					}
					else
					{
						$success .= " Org : " . $org_id . " <br/> ";
						$success .= $key . " --> <p color = 'green'>" . $value . "</p><br/> ";
					}
				}
			}
			catch( Exception $e )
			{
				$this->logger->debug( " Did not get key " . $key . " with exception " . $e->getMessage() );
			}
			
		}
		
		$this->logger->debug( " Success Report : " . $success . " Failure Report " . $failure );
		$to = array( 'prakhar@capillary.co.in' , 'mahima.sivasankaran@capillary.co.in' );
		
		Util::sendEmail( $to , ' Success Bns reports' , $success , 0 );
		
		Util::sendEmail( $to , ' Failure Bns reports' , $failure , 0 );

		foreach( $keys_exist as $key )
		{
			 
			$this->logger->debug( " Memcache key for bns while trying to delete " . $key );

			try{
				 
				$this->mem_cache_manager->delete( $key );
				$this->logger->debug( " Memcache successful in deleting key " . $key );
			}
			catch( Exception $e )
			{
				$this->logger->debug( " Memcache could not delete key " . $key . " because " . $e->getMessage() );
			}
		}
	  

	}
	/*public function nonloggedinqcrreportAction(){
		
		$sql = "
		
				SELECT 
					`o`.`name` AS `organization_name`,
					SUM( CASE WHEN DATE( `l`.`last_login` ) < DATE( NOW() ) THEN 1 ELSE 0 END ) AS `not_logged_in`,
					SUM( CASE WHEN DATE( `l`.`last_login` ) >= DATE( NOW() ) THEN 1 ELSE 0 END ) AS `logged_in`,
					COUNT( l.id ) AS `total_active_tills`
					
				FROM `masters`.`organizations` AS `o`
				JOIN `masters`.`loggable users` AS `l` ON ( `l`.`org_id` = `o`.`id` )
				WHERE `l`.`type` = 'TILL' AND l.`is_active` = 1 AND `o`.`is_active` = 1
				GROUP BY `o`.`id`
		";
		
		$report_widgets = array(
		
				'report_table'	=> array('widget_name' => "Reports Summary, For Not Logged In Store On ".date("F 'y"), 'widget_code' => 'table' )		
		);
		
		$this->data['table'] = $this->db->query_table( $sql );
		
		$subject = "Consolidated Not Logged In Store Across The Brand";
 
		$recipients =  array( 'qcr@capillary.co.in', 'ajay@capillary.co.in', 'prakhar@capillary.co.in' );
		
		$e = new Email();
		$e->emailWidgets( $recipients, $subject, $report_widgets, $this );				
	}*/
	
	function getBulkMessagesStatusAction(){
		
 		$form = new Form('bulk_status','post');
 		$form->addField('select','org','Organization',$this->getOrganizationsOptions());
 		$form->addField('datepicker', 'start_date', 'Start Date', date('Y-m-01'));
		$form->addField('datepicker', 'end_date', 'End Date', date('Y-m-d'));
		$form->addField('select','priority','Priority', array('BULK','HIGH','DEFAULT') );
 		
 		if($form->isValidated()){
 			
 			$params = $form->parse();
 			$org = $params['org'];
 			$start = Util::getMysqlDate( $params['start_date'] );
			$end =  Util::getMysqlDate( $params['end_date'] );
			$priority = $params['priority'];
			$end = Util::getNextDate( $end );

			if( $start > $end ){
				$this->flash('Invalid Date Range Selected' );
			}else{
				
				$sql = "SELECT `gateway` , `status` , count(*) AS 'Number Of Messages'  
							FROM `nsadmin`.`messages` 
							WHERE `message_class` = 'SMS' 
								AND `sent_time` BETWEEN '$start' AND '$end' 
								AND `priority` = '$priority' 
								AND `sending_org_id` = '$org' 
					    GROUP BY gateway, status";
				
				$table = $this->db->query_table( $sql , 'gateway');
				$this->data['table'] = $table;
			}
 		}
 		$this->data['form'] = $form;
 	}
 	
	function getReportForPendingRequestAction(){
		
		$org_data = $this->getOrganizationsOptions();
		
 		$form = new Form('pending_request','post');
 		$form->addField('select','org','Organization', $org_data );

 		$org = 0;
 		if($form->isValidated()){
 			
 			$params = $form->parse();
 			$org = $params['org'];
 		}
 		
 		$this->data['org_name'] = ucwords( $this->getOrganizationsNameById( $org ) );
 					
		$sql = "SELECT count( DISTINCT m.`user_id`) AS 'Mobile Number Requests' 
						FROM `user_management`.`mobile_number_change_request_log` m
					WHERE m.`changed` = 0 AND m.`type` = 'MOBILE' AND m.`org_id` = '$org' ";
				
		$table = $this->db->query_table( $sql , 'requests');
		$this->data['table'] = $table;
			
 		$this->data['form'] = $form;
 	}
 	
 	/**
 	 * Just to allow sync for the bg sync orgs
 	 * 
 	 * @deprecated : not used in API and unused tables
 	 */
//  	public function bgSyncAllwedAction( $org_id = 0 ){
 		
//  		$form = new Form( 'bg_sync','post' );
//  		$form->addField( 'select', 'org', 'Organization', $this->getOrganizationsOptions() );
 		
//  		if( $form->isSubmitted()  ){
 			
//  			$params = $form->parse();
//  			$org_id = $params['org'];
//  		} 

//  		if( $org_id ){
 			
//  			$sql = "
//  					SELECT m.id, l.username, l.last_login, m.last_updated_on, m.sync AS sync_allowed
//  					FROM `masters`.`master_client_sync` AS `m`
//  					JOIN `masters`.`loggable users` AS `l` ON ( `m`.`org_id` = `l`.`org_id` AND `m`.`store_id` = `l`.`ref_id` AND `type` = 'TILL' )
//  					WHERE `m`.`org_id` = '$org_id'
//  			";
 			
//  			$table = $this->db->query_table( $sql, 'master_config_status' );
//  			$table->createLink( 'allow_sync', Util::genUrl( 'orgadmin', "allowsync/{0}/$org_id" ), 'allow_sync', array( '0' => 'id' ), 'allow_sync' );
//  		}
 		
//  		$this->data['form'] = $form;
//  		$this->data['table'] = $table;
//  	}
 	
 	/**
 	 * @deprecated : table not in use
 	 */
//  	public function allowsyncAction( $id, $org_id ){
 		
//  		$date = date( 'Y-m-d' );
//  		$sql = "
//  				UPDATE masters.master_client_sync
//  				SET 
//  					`sync` = 1,
//  					`last_updated_on` = '$date'
//  				WHERE `id` = '$id' 
//  		";
 		
//  		$this->db->update( $sql );
 		
//  		Util::redirect( 'orgadmin', "bgSyncallwed/$org_id", false, 'You Sync The Store Now' );
//  	}

	
       public function disableOrgGateWayAction($id)	
       {
       
       	$nsadmin = new NSAdminThriftClient();
       	$is_disabled = $nsadmin->disableGateway($id);
      	$url = 'org/gateways/Gateways';
       	if(! $is_disabled) {
       		$url.= "&flash=Please try after a while";
		}
       	Util::redirectByUrl($url);
       }

	/**
	 * show custom sender email
	 * @param OrgProfile $org
	 */
	function setCustomSenderEmailAction(){
		
		$org_id = $this->currentorg->org_id;

		$custom = $this->db->query_firstrow("SELECT * FROM `custom_sender` WHERE `org_id` = '$org_id'");

		//if (empty($custom['sender_email'])) $custom['sender_email'] = 'noreply@intouch-mailer.com';

		$form = new Form('custom_sender_email_update');
		//even while sending we check if the sender ID is in correct format
		$form->addField('hidden', 'sender_gsm', 'Sender ID for GSM', $custom['sender_gsm']);
		$form->addField('hidden', 'sender_cdma', 'Sender ID for CDMA', $custom['sender_cdma']);
		$form->addField('hidden', 'sender_label', 'Sender label for EMAIL', $custom['sender_label']);
		$form->addField('text', 'sender_email', 'Sender EMAIL', $custom['sender_email'], 
						array('help_text' => 'Leave blank. If you want to set this address then get it whitelisted from your administrator.') , 
							  '/^(((([a-z\d][\&\.\-\+_]?)*)[a-z0-9])+)\@(((([a-z\d][\.\-_]?){0,62})[a-z\d])+)\.([a-z\d]{2,6})$/i' , 
							  'Invalid Email Address' , '' );
		$form->addField('hidden', 'replyto_email', 'Reply-to ID for EMAIL', $custom['replyto_email']);
		
		if ($form->isValidated()) {

			$params = $form->parse();
			$sql = "INSERT INTO `custom_sender` (org_id, sender_gsm, sender_cdma, sender_label, replyto_email,sender_email) VALUES ('$org_id', '$params[sender_gsm]', '$params[sender_cdma]', '$params[sender_label]', '$params[replyto_email]', '$params[sender_email]') ON DUPLICATE KEY UPDATE sender_gsm = '$params[sender_gsm]', sender_cdma = '$params[sender_cdma]', sender_label = '$params[sender_label]', replyto_email = '$params[replyto_email]', sender_email = '$params[sender_email]'";
			if ($this->db->update($sql)) {
        		$this->flash("Sender info update successfully");
			}
			else
				$this->flash("Some problem occurred. Please check back later");
		}

		$this->data['custom_sender_email_update'] = $form;
	}
	
/*
	 * Creating a table in the orgadmin/administer page for sms credits of the organization.
	 */
	private function createTableForSMSCredits($res  ,$pageId = '',$style = 'box-table-a', $dump_to_logger = true ){
	
		if ($res) {
			$data = array();
			$table = new Table($name);
			if($pageId != ''){
				$table->setPagination();
				$table->page_id = $pageId;
			}
	
			$table->setStyle($style);
			//add headers
	
			$table->addHeader('org_id');
			$table->addHeader('value_sms_credits');
			$table->addHeader('bulk_sms_credits');
			$table->addHeader('user_credits');
			$table->addHeader('created_by');
			$table->addHeader('added');
				
			array_push($data ,array('org_id' => $res['org_id'],
				                   'value_sms_credits' => $res['value_sms_credits'],
				                   'bulk_sms_credits' => $res['bulk_sms_credits'],
				                   'user_credits' => $res['user_credits'],
				                   'created_by' => $res['created_by'],
				                   'added' =>  $res['last_updated']
				      ));
			 
			$table->addData($data);
	
			return $table;
		}
		else {
			return new Table($name);
		}
	}
	
	/**
	 * assync flow for tasks execution from cron
	 * rt now it is getting called from shell_exec in future
	 * we can call it directly.
	 *
	 * @param unknown $task_id
	 * @param unknown $org_id
	 * @param unknown $identifier
	 * @param unknown $scheduler_state
	 * @return boolean
	 */
	public function asyncRemindSceduledTaskAction( $task_id, $org_id, $identifier ){
	
		$this->logger->debug( "REMIND Task Id : $task_id Org id $org_id Identifier $identifier_id " );
	
		try {
			
			include_once 'helper/scheduler/ScheduledExecutor.php';
	
			$this->logger->info("scheduled_task:");
			$this->logger->info("scheduled_task: $task_id
					going to remind identifier $identifier org id $org_id");
					
			ScheduledExecutor::remindByIdentifier( $identifier, $task_id, $org_id );
			return true;
		} catch (Exception $e) {
	
			$this->logger->debug("Exception: " . print_r($e->getMessage(), true));
			return false;
		}
	}
	
	/**
	 * assync flow for tasks execution from cron
	 * rt now it is getting called from shell_exec in future
	 * we can call it directly.
	 * 
	 * @param unknown $task_id
	 * @param unknown $org_id
	 * @param unknown $identifier
	 * @param unknown $scheduler_state
	 * @return boolean
	 */
	public function asyncSceduledExecutorAction( $task_id, $org_id, $identifier ){

		$this->logger->debug( "Task Id : $task_id Org id $org_id Identifier $identifier_id " );

                try {
                        //file_put_contents("/tmp/shopbook-1234", "function called");
                        include_once 'helper/scheduler/ScheduledExecutor.php';

                        $this->logger->info("scheduled_task:");

                        $issueTimer = new Timer("thrift_process");
                        //Performance Log
                        $memusage_start = memory_get_usage(true)/1000000; //MB
                        $cpuload_start = Util::ServerLoad(); //percentage
                        $perf_log_insert_id = Util::startEntryIntoPerformanceLogs('THRIFT', 'scheduler', 'executeScheduledTask');

                        //error_log("scheduled_task: task_id = $task_id, arguments=".var_export($arguments, true));
                        //$identifier = $arguments['identifier'];
                        //$org_id = $arguments['org_id'];
                        $this->logger->info("scheduled_task: $task_id  
						going to execute identifier $identifier org id $org_id");
                        ScheduledExecutor::runByIdentifier( $identifier, $task_id, $org_id );
                        $this->logger->info("scheduled_task: $task_id finished executing");

                        $time = $issueTimer->getTotalElapsedTime();
                        $this->logger->debug("Returning Back to scheduler Time : $time");

                        return true;
                } catch (Exception $e) {

                        $this->logger->debug("Exception: " . print_r($e->getMessage(), true));
                        return false;
                }
	}
	
	/*
	 * Creating a table in orgadmin/administer page to display credits log of the organization.
	*/
	private function createTableForCreditsLog($res  ,$pageId = '',$style = 'box-table-a', $dump_to_logger = true ){
	
		if ($res) {
			$data = array();
			$table = new Table($name);
			if($pageId != ''){
				$table->setPagination();
				$table->page_id = $pageId;
			}
	
			$table->setStyle($style);
			//add headers
	
			$table->addHeader('org_id');
			$table->addHeader('value_sms_credits');
			$table->addHeader('bulk_sms_credits');
			$table->addHeader('user_credits');
			$table->addHeader('created_by');
			$table->addHeader('added');
				
				
			foreach( $res as $credit_log )
			{
				array_push($data, array(
				'org_id' => $credit_log->orgId,
				'value_sms_credits' => $credit_log->valueCredits,
				'bulk_sms_credits' => $credit_log->bulkCredits,
				'user_credits' => $credit_log->userCredits,
				'created_by' => $credit_log->addedBy,
				'added' =>  date("Y-m-d H:i:s",(int)($credit_log->lastUpdatedAtTimestamp/1000))
				));
			}
			$table->addData($data);
	
			return $table;
		}
		else {
			return new Table($name);
		}
	}
	
}

?>
