<?php

/**
 * Controller class to which all import widgets communicate
 * author Suganya TS
 */
include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
include_once 'apiController/ApiBaseController.php';
//TODO: referes to cheetah
include_once 'helper/CustomFields.php';
include_once 'business_controller/AdminUserController.php';
include_once 'apiController/ApiOrganizationController.php';

class  ApiCcmsController extends ApiBaseController{

	private $config_mgr;
	private $C_admin_user;
	private $C_custom_fields;
	public $issueTrackerModel;
	private $C_listeners_manager;
	private $C_organization_controller;
	
	public $ticket_code;

	public function __construct(){

		parent::__construct();

		$this->issueTrackerModel = new ApiIssueTrackerModelExtension();
		$this->config_mgr = new ConfigManager();

		$this->C_custom_fields = new CustomFields();
		$this->C_admin_user = new AdminUserController();
		$this->C_listeners_manager = new ListenersMgr( $this->org_model );
		$this->C_organization_controller = new ApiOrganizationController(  );
	}

	/**
	 * return custom field associated with organization
	 */
	public function getCustomFieldAsOptions(){

		$custom_field_data = $this->C_custom_fields->getCustomFieldsByScope( $this->org_id, CUSTOMER_CUSTOM_FEEDBACK );
		$cf_options = array();
		foreach ($custom_field_data as $cf){

			$name = $cf['name'];
			$cf_options[$name] = $cf['id'];
		}
		return $cf_options;
	}

	/**
	 *
	 * @param unknown_type $filter: the filter
	 */
	public function searchIssueOnFilter( $filter ){

		$labels = $this->getLabelConfigs();

		return $this->issueTrackerModel->searchIssueOnFilter( $filter, $labels, false );
	}

	/**
	 *
	 * @param $status
	 * @param $department
	 */
	public function getIssuesCreatorOverview( ){

		$status = $this->config_mgr->getKey( 'CONF_TRACKER_STATUS_LIST' );

		return $this->issueTrackerModel->getIssuesCreatorOverview( $status );
	}
	
	/**
	 *
	 * @param $status
	 * @param $department
	 */
	public function getIssuesOverview($status, $department){

		$labels = $this->getLabelConfigs();

		return $this->issueTrackerModel->getIssuesOverview( $status, $department, $labels['department'] );

	}
	
	/**
	 * params for masters data
	 * @param unknown_type $params
	 */
	public function validateMasterData( $params ){

		$filter .= '';

		$start_created_date = $params['start_created_date'];
		$end_created_date = $params['end_created_date'];
			
		if( $end_created_date > $start_created_date ){

			$filter .= " AND `created_date` BETWEEN '$start_created_date' AND '$end_created_date' ";
		}
		else if ( !$end_created_date && !$start_created_date ){

		}
		else{
			throw new Exception('dates are not correct');
		}
			
		$start_last_updated_date = $params['start_last_updated_date'];
		$end_last_updated_date = $params['end_last_updated_date'];
			
		if( $end_last_updated_date < $start_last_updated_date ){

			throw new Exception( 'Last Updated End Date Must be greater than the start date ');
		} else if ( !$end_last_updated_date && !$start_last_updated_date ){

		}
		else {

			$filter .= " AND `last_updated` BETWEEN '$start_last_updated_date' AND '$end_last_updated_date' ";
		}
			
		$assigned_to = $params['assigned_to'];
		if( $params['assigned_to'] != '-1' )
			$filter .= " AND `assigned_to` = '$assigned_to' ";

		if( $params['include_inactive'] )
			$filter .= ' AND '.' `it`.`is_active` = 1 ';

		return $filter;

	}

	/**
	 * Create filters based on the params provided
	 * @param unknown_type $params:
	 */
	public function validateParams( $params ){
		//make filters
		$filter .= '';

		if($params['status'] != 'All' && $params['status'])
			$filter .= " AND `status` = '".$params['status']."' ";

		if($params['priority'] != 'All' && $params['priority']  )
			$filter	.= " AND `priority` = '".$params['priority']."'" ;

		if($params['department'] != 'All' && $params['department'] )
			$filter .= " AND `department` = '".$params['department']."'" ;

		if($params[$issue_code] != '')
			$filter .= " AND `issue_code` LIKE '%".$params[$issue_code]."%' ";

		if($params['customer_id'] != ''){

			$user = UserProfile::getByMobile($params['customer_id']);
			$customer_id = $user->user_id;
			$filter .= " AND `customer_id` = '".$customer_id."'";
		}
		if($params['ticket_code'] != '')
			$filter .= " AND `ticket_code` LIKE '".$params['ticket_code']."'" ;

		if($params['assigned_to'] != -1)
			$filter .= " AND `assigned_to` = '".$params['assigned_to']."'" ;

		if($params['assigned_by'] != -1)
			$filter .= " AND `assigned_to` = '".$params['assigned_by']."'" ;

		if($params['created_date_from'] != '' && $params['created_date_till'] != '')
			$filter .= " AND `created_date` BETWEEN '".$params['created_date_from']."' AND '".$params['created_date_till']."'" ;

		if($params['due_date_from'] != '' && $params['due_date_till'] != '')
			$filter .= " AND `due_date` BETWEEN '".$params['due_date_from']."' AND '".$params['due_date_till']."'" ;

		return $filter;
	}

	/**
	 *
	 * @return Ambigous <string, unknown>
	 */
	public function getCustomFeedbackOptions(){

		$fields = $this->C_custom_fields->getCustomFieldsByScope($this->org_id, CUSTOMER_CUSTOM_FEEDBACK);

		$purchased_at = array();
		$purchased_at['None'] = false;
		foreach ($fields as $row) {
			$purchased_at[Util::beautify($row['name'])] = $row['name'];
		}
		return $purchased_at ;
	}

	/**
	 * @return All The UI elements thats is enabled
	 * or that has ti be shown on the page
	 */
	public function getEnabledUIElements(){


	}

	/**
	 * returns the formlabels and default value for Customer feedback label
	 */

	public function customerFeedbackLabelLabels(){

		$conf_feedback_label = array(
				'department'=>'CUSTOMER_FEEDBACK_DEPARTMENT_LABEL',
				'priority'=>'CUSTOMER_FEEDBACK_PRIORITY_LABEL',
				'status'=>'CUSTOMER_FEEDBACK_STATUS_LABEL',
				'issue_code'=>'CUSTOMER_FEEDBACK_SHORT_DESC_LABEL',
				'long_desc'=>'CUSTOMER_FEEDBACK_LONG_DESC_LABEL',
				'mobile'=>'CUSTOMER_FEEDBACK_MOBILE_LABEL',
				'customer_name'=>'CUSTOMER_FEEDBACK_CUSTOMER_NAME_LABEL',
				'email'=>'CUSTOMER_FEEDBACK_EMAIL_LABEL',
				'assigned_to'=>'CUSTOMER_FEEDBACK_ASSIGNED_TO_LABEL',
				'due_date'=>'CUSTOMER_FEEDBACK_DUE_DATE_LABEL',
				'is_critical'=>'CUSTOMER_FEEDBACK_CRITICAL_LABEL'
		);
			
		return $conf_feedback_label;
	}

	/**
	 * Returns The Hash map For All The Configs
	 * UI label configs
	 *
	 * @return
	 * array(
	 *
	 * 		'department' => VALUE,
	 * 		'....
	 * )
	 */
	public function getLabelConfigs(){

		$default_value=array();

		$default_value['CONF_TICKET_CODE_LENGTH']=8;

		$customer_feedback_label = $this->config_mgr->getKey('CONF_FEEDBACK_LABEL');

		$default_value['department']= Util::valueOrDefault($customer_feedback_label['department'],
				'DEPARTMENT*');
		$default_value['priority']= Util::valueOrDefault($customer_feedback_label['priority'], 'PRIORITY*');
		$default_value['status']= Util::valueOrDefault($customer_feedback_label['status'], 'STATUS*');
		$default_value['issue_code']= Util::valueOrDefault($customer_feedback_label['issue_code'], 'Short Description');
		$default_value['long_desc']= Util::valueOrDefault($customer_feedback_label['long_desc'], 'Long Description');
		$default_value['customer_name']= Util::valueOrDefault($customer_feedback_label['name'], 'Customer Name');
		$default_value['mobile']= Util::valueOrDefault($customer_feedback_label['mobile'], 'Mobile No.');
		$default_value['email']= Util::valueOrDefault($customer_feedback_label['email'], 'Email Id');
		$default_value['assigned_to']= Util::valueOrDefault($customer_feedback_label['assigned_to'], 'Assigned To');
		$default_value['due_date']= Util::valueOrDefault($customer_feedback_label['due_date'], 'Due Date*');
		$default_value['is_critical']= Util::valueOrDefault($customer_feedback_label['is_critical'], 'Is Critical?');

		$default_value['CUSTOMER_FEEDBACK_PURCHASED_AT_MOBILE'] = Util::valueOrDefault($this->config_mgr->getKey('CUSTOMER_FEEDBACK_PURCHASED_AT_MOBILE'),
				'Mobile No.');

		$default_value['SERVER_ESCALATE_TYPE'] = Util::valueOrDefault($this->config_mgr->getKey('SERVER_ESCALATE_TYPE'),
				'DEFAULT');

		$default_value['SERVER_FEEDBACK_EMAIL_VIEW'] = Util::valueOrDefault($this->config_mgr->getKey('SERVER_FEEDBACK_EMAIL_VIEW'),
				'1');

		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PRIORITY'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PRIORITY'),
				'1');
		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_STATUS'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_STATUS'),
				'1');
		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_DEPARTMENT'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_DEPARTMENT'),
				'1');
		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_HOST'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_HOST'),
				'1');
		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PORT'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PORT'),
				'1');

		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_USERNAME'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_USERNAME'),
				'username');
		$default_value['DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PASSWORD'] = Util::valueOrDefault($this->config_mgr->getKey('DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PASSWORD'),
				'1');

		$default_value['smarty_tags'] = stripcslashes( $this->config_mgr->getKey('CONF_CCMS_SMARTY_TAGS') );
		$default_value['smarty_css'] = stripcslashes( $this->config_mgr->getKey('CONF_CCMS_SMARTY_CSS') );

		$default_value['customer_id'] = Util::valueOrDefault($this->config_mgr->getKey('CUSTOMER_FEEDBACK_MOBILE_LABEL'),'Mobile No*');

		$default_value['CUSTOMER_FEEDBACK_ISSUE_REMINDER']= '2';
		$default_value['CONF_CLIENT_IS_FEEDBACK_ENABLED']=0;


		return $default_value;
	}
	/**
	 * @method Fetches all the columns in the issue_tracker table
	 * and the custom fields for ccms for the organisation.
	 */
	public function getReportColumms()
	{
		//Fetching issue tracker columns.
		$cols = $this->issueTrackerModel->getReportColumns();

		//Fetching custom fields.
		$cf=new CustomFields();
		$custom_fields = $cf->getCustomFieldsByScope($this->org_id, CUSTOMER_CUSTOM_FEEDBACK);

		$col_array=array();

		foreach($cols as $column)
			$col_array[$column['Field']]=$column['Field'];
		foreach($custom_fields as $cfield)
			$col_array[$cfield['label']]=$cfield['name'];
			
		return $col_array;

	}

	/**
	 * Escalate the issues
	 *
	 * The configs to be used are :
	 *
	 * 1 ) SERVER_ESCALATE_TYPE  : so that we can find out if the type of
	 * 	escalation that is needed. [ default | custom_field | hierarchical ]
	 *
	 * 2 ) SERVER_ESCALATE_PARAMS : this is the key which actually has the configurations
	 */
	public function escalate(){

		//escalation type
		$escalation_type = $this->config_mgr->getKey( SERVER_ESCALATE_TYPE );

		//the escalation params set for the organization
		$escalation_params = $this->config_mgr->getKey( SERVER_ESCALATE_PARAMS );

		switch( $escalation_type ){

			case 'default' :

				return $this->escalateIssuesByDefaultSettings( $escalation_params );
				break;

			case 'custom_field' :

				$this->escalateIssuesByCustomSettings( $escalation_params );
				break;

			case 'hierarchical' :
				
				return $this->escalateIssuesByHierarchicalSettings( $escalation_params );
				break;
			default :

				throw new Exception( 'Type Of Escalation is not defined' );
		}
	}

	/**
	 * Critical issues
	 *
	 * TODO : It is Type of escalation which has to be moved under escalation later on.
	 * The configs to be used are :
	 *
	 * 1 ) SERVER_CRITICAL_PARAMS : this is the key which actually has the configurations
	 */
	public function critical(){

		//the critical params set for the organization
		$critical_params = $this->config_mgr->getKey( SERVER_CRITICAL_PARAMS );

		$this->logger->debug( 'Critical Params : '.print_r($critical_params,true) );

		if( count( $critical_params ) < 1 ){
			throw new Exception( 'Watchers are not set for the critical issues' );
		}

		$this->criticalIssuesByWatchersSettings( $critical_params );
	}

	/**
	 *
	 * @param unknown_type $escalation_params CONTRACT(
	 *
	 * [0] =>
	 * 	 array(
	 *
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, too whome the escalation should happen
	 * 	 ),
	 * [1] =>
	 * 	 array(
	 *
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, too whome the escalation should happen
	 * 	 )...
	 * )
	 */
	private function escalateIssuesByDefaultSettings( $escalation_params ){

		$this->logger->info( 'The Escalation Will Happen By The Default Settings' );

		$this->logger->debug( 'The escalation params are : '.print_r( $escalation_params, true ) );

		foreach( $escalation_params as $params ){

			$days_to_check = $params['days'];
			$escalate_to = $params['escalate_to'];

			$this->logger->debug( 'days to check for escalation : '.$days_to_check );
			$this->logger->debug( 'escalate to  : '.$escalate_to );

			$not_updated_issues =
			$this->issueTrackerModel->getNotUpdatedIssuesByDays( $days_to_check );

			//escalate the issues
			return $this->escalateIssue( $not_updated_issues, $escalate_to );
		}
	}

	/**
	 *
	 * Based on the reference to the org roles the managers would
	 * be taken out for the store/admin user. 
	 * How does it work ?
	 * 1 ) Store Till : If the ticket has been created from
	 * 			the till all the managers that lies under the given manager list
	 * 			for the particular level will be intimated.
	 * 			e.g; If for 
	 * 				Level 1 : Store Manager has to be intimated then the mail
	 * 				will go to all the users who are manager for that store.
	 * 				Level 2 : Zone Manager has to be intimated then the mail
	 * 				will go to all the users who are zonal manager in the region where
	 * 				store lies store.
	 * 2 ) ADMIN USER : Here The hierarchy of organization is taken.
	 * 			e.g;
	 * 			Level 1 : Tickets creator manager will be notified.
	 * 			Level 2 : manager of manager of ticket creator will be notified :P
	 * 
	 * @param unknown_type $escalation_params CONTRACT(
	 *
	 * [0] =>
	 * 	 array(
	 *
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, The id consisting reference to org roles  
	 * 	 ),
	 * [1] =>
	 * 	 array(
	 *
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, The id consisting reference to org roles
	 * 	 )...
	 * )
	 */
	private function escalateIssuesByHierarchicalSettings( $escalation_params ){

		$this->logger->info( 'The Escalation Will Happen By The Hierarichical Settings' );

		$this->logger->debug( 'The escalation params are : '.print_r( $escalation_params, true ) );

		$C_loggable_user = new LoggableUserModelExtension();

		$statuses_list = $this->config_mgr->getKey( 'CONF_TRACKER_STATUS_LIST' );
		$last_index = count( $statuses_list ) - 1;
		$closed_status = $statuses_list[$last_index];
		foreach( $escalation_params as $params ){

			$days_to_check = $params['days'];
			$store_till_escalation = $params['store_till'];
			$admin_user_escalation = $params['admin_user'];

			$this->logger->debug( 'days to check for escalation : '.$days_to_check );
			$this->logger->debug( '$store_till_escalation  : '.$store_till_escalation );
			$this->logger->debug( '$admin_user_escalation  : '.$admin_user_escalation );

			$not_updated_issues =
				$this->issueTrackerModel->getIssuesByDaysClosedStatus( $days_to_check, $closed_status );
			
			//TODO : forget bulk lets add memcache
			foreach ( $not_updated_issues as $issue ){

				$created_by = $issue['created_by'];
				$loggable_user_id = 
					LoggableUserModelExtension::getIdByRefId( $created_by );
				$C_loggable_user->load( $loggable_user_id );
				$this->logger->debug( "Created By : $created_by & user type ".
					$C_loggable_user->getType());
				switch( $C_loggable_user->getType() ){
	
					case 'TILL' :
						
						//get out the org role type
						$role_type = 
							$this->org_model->getRoleById( $store_till_escalation );
							
						$this->logger->debug( "Roll Type ".$role_type );
							
						//style one in comment will be used
						$this->raiseStoreTillEscalationForHierarchicalStructure( 
							$issue, $role_type );
							
						break;
					
					case 'ADMIN_USER' :
						
						//style two in comment will be used
						$this->raiseAdminTillEscalationForHierarchicalStructure( 
							$issue, $admin_user_escalation, array( $issue['created_by'] ), 0 );
						break;
				}
			}
		}
	}
	
	/**
	 * Admin User escalation will first get out all the managers 
	 * for the particular user ids.
	 * if any manager lies in the org role assigned to be notified
	 * 	for the particular level then escalation would be issued.
	 * else manager's manager will be taken out and same process will
	 * 	be used.
	 * 
	 * @param unknown_type $issues
	 * @param unknown_type $role_type
	 */
	private function raiseAdminTillEscalationForHierarchicalStructure( 
							$issue, $org_role_id, array $manager_ids, $iteration = 0 ){

		$managers = AdminUserController::getManagers( $manager_ids, $this->org_id );
		$this->logger->info( "raiseAdminTillEscalationForHierarchicalStructure");
		$manager_ids = array();
		$escalation_list = array();
		$escalation_email_list = array();
		foreach( $managers as $manager ){

			if( $iteration > 20 ){
				
				$this->logger->error( "ESCALATION : SOME INFINITE LOOP FOUND" );
				return;
			}
			
			array_push( $manager_ids, $manager['id'] );
			if( $manager['role_id'] == $org_role_id ){
				
				array_push( $escalation_list, $manager['id'] );
				array_push( $escalation_email_list, $manager['email'] );
			}

			//if no escalation list is found go to top hierarchy
			if( count( $escalation_list ) < 1 ){

				$this->raiseAdminTillEscalationForHierarchicalStructure( $issue, 
					$org_role_id, $manager_ids, ++$iteration );
			}
			
		}
		
		if( count( $escalation_email_list ) < 1 ) return;			
		$email_list = implode( ',', $escalation_email_list );
		$this->escalateIssue( array( $issue ), $email_list );

		//update the ticket to parent manager
		$escalated_id = $manager_ids[0];
		$this->issueTrackerModel->load( $issue['id'] );
		$params = $this->issueTrackerModel->getHash();
		
		try{
			
			if( $this->issueTrackerModel->getAssignedTo() != $escalated_id ){
				
				$params['assigned_to'] = $escalated_id;
				$this->update( $issue['id'], $params );
			}
				
				
		}catch ( Exception $e ){

			$this->logger->debug( " Exception Thrown While Update ticket ".$e->getMessage() );
		}
				
		return ;
	}
	
	/**
	 * This is invloved in issuing the mail for the 
	 * non updated tickets in ccms
	 * 
	 * @param unknown_type $not_updated_issues
	 */
	private function raiseStoreTillEscalationForHierarchicalStructure( 
		$issue, $role_type){
	
		$created_by = $issue['created_by'];
		$this->C_organization_controller->StoreTillController->load( $created_by );
		
		switch ( $role_type ){
			
			case 'ORG' :
				
				$managers =
					$this->C_organization_controller->getAlreadyOrgPOCEmailHash( 'id' );
					
				$this->logger->debug( "Escalation Managers ".print_r( $managers, true ) );
				break;
				
			case 'ZONE' :

				$parent_zones = 
					$this->C_organization_controller->StoreTillController->
					getParentZone( );
					
				$managers =
					ZoneController::getUsersEmailMapByZoneIds( 
						$this->org_id, $parent_zones );
					
				break;
				
			case 'CONCEPT' :
				
				$parent_concepts = 
					$this->C_organization_controller->StoreTillController->
					getParentConcept( );
				
				$managers =
					 ApiConceptController::getUsersEmailMapByConceptIds( 
						$this->org_id, $parent_concepts );
					
				break;
				
			case 'STORE' :
				
				$parent_stores = 
					$this->C_organization_controller->StoreTillController->
					getParentStore( );
					
				$managers =
					storeController::getUsersEmailMapByStoreIds( 
						$this->org_id, $parent_stores );
				
				break;
		}

		if( count( $managers ) < 1 ) return;
		$manager_emails = array_values( $managers );
		$manager_emails_csv = implode( ',', $manager_emails );
		
		//update the ticket to parent manager
		$manager_ids = array_keys( $managers );
		$escalated_id = $manager_ids[0];
		$this->issueTrackerModel->load( $issue['id'] );
		$params = $this->issueTrackerModel->getHash();
		
		try{
			
			if( $this->issueTrackerModel->getAssignedTo() != $escalated_id ){
				
				$params['assigned_to'] = $escalated_id;
				$this->update( $issue['id'], $params );
			}
							
		}catch ( Exception $e ){

			$this->logger->debug( " Exception Thrown While Update ticket ".$e->getMessage() );
		}
		
		$this->escalateIssue( array( $issue ), $manager_emails_csv );
	}
	
	/**
	 * It will proccess critical issues
	 * @param unknown_type $critical_params CONTRACT(
	 * {"watchers":["0","4","25449","103718","538557"]}
	 * )
	 */
	private function criticalIssuesByWatchersSettings( $critical_params ){

		$this->logger->info( 'The Critical Issue Proccess Start' );

		$this->logger->debug( 'The Critical params are : '.print_r( $critical_params , true ) );

		$watchers_list = $critical_params['watchers'];

		$not_updated_issues = $this->issueTrackerModel->getNotUpdatedIssuesAfterDDForCritical();

		$not_updated_issues_on = $this->issueTrackerModel->getNotUpdatedIssuesOnDDForCritical();
			
		//critical escalates the issues
		$this->processCriticalIssue( $not_updated_issues , $not_updated_issues_on , $watchers_list );

		$this->logger->debug( 'FINISH' );
	}

	/**
	 * process critical issue by signaling listener to the manager
	 *
	 * @param unknown_type $not_updated_issues
	 * @param unknown_type $watchers
	 */
	private function processCriticalIssue( $not_updated_issues , $not_updated_issues_on , $watchers ){

		$this->logger->debug( 'Not Updated Issues For critical: '.print_r( $not_updated_issues, true ) );
		$this->logger->debug( 'Not Updated Issues For critical On: '.print_r( $not_updated_issues_on, true ) );

		if( is_array( $not_updated_issues ) ){

			foreach( $not_updated_issues as $issue_to_escalate ){

				$params = array(
						'user_id' => $issue_to_escalate['customer_id'],
						'store_id' => $this->user_id,
						'ticket_id' => $issue_to_escalate['id'],
						'watchers' => $watchers,
						'assigned_to' => $issue_to_escalate['assigned_to'],
						'due_date' => $issue_to_escalate['due_date'],
						'on_update' => false,
						'send_sms' => false,
						'critical_type' => 'error'
				);
					
				$ret = $this->C_listeners_manager->signalListeners( EVENT_CUSTOMER_FEEDBACK, $params );
			}
		}

		if( is_array( $not_updated_issues_on ) ){

			foreach( $not_updated_issues_on as $issue_to_escalate ){

				$params = array(
						'user_id' => $issue_to_escalate['customer_id'],
						'store_id' => $this->user_id,
						'ticket_id' => $issue_to_escalate['id'],
						'watchers' => $watchers,
						'assigned_to' => $issue_to_escalate['assigned_to'],
						'due_date' => $issue_to_escalate['due_date'],
						'on_update' => false,
						'send_sms' => false,
						'critical_type' => 'warning'
				);
					
				$ret = $this->C_listeners_manager->signalListeners( EVENT_CUSTOMER_FEEDBACK, $params );
			}
		}

		$this->logger->debug( 'Processing of critical issue is done, Time To Return' );
		return true;
	}

	/**
	 * @param unknown_type $escalation_params CONTRACT(
	 *  [{"":"2","custom_field_value":"yes","days":"3","escalate_to":"33@gmail.com"},
	 * [0] =>
	 * 	 array(
	 *
	 * 		'custom_field' => id, { this is the id of custom fields },
	 * 		'custom_field_value' => 'The Value Of The custom Field That will shoot that'
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, too whome the escalation should happen
	 * 	 ),
	 * [1] =>
	 * 	 array(
	 *
	 * 		'custom_field' => id, { this is the id of custom fields },
	 * 		'custom_field_value' => 'The Value Of The custom Field That will shoot that'
	 * 		'days' => VALUE , number of days for lapsed updates
	 * 		'escalate_to' => VALUE, too whome the escalation should happen
	 * 	 )...
	 * )
	 */
	private function escalateIssuesByCustomSettings( $escalation_params ){

		$this->logger->info( 'The Escalation Will Happen By The Custom Field Settings' );

		$this->logger->debug( 'The escalation params are : '.print_r( $escalation_params ) );

		foreach( $escalation_params as $params ){

			$custom_field_id = $params['custom_field'];
			$custom_field_value = $params['custom_field_value'];
			$days_to_check = $params['days'];
			$escalate_to = $params['escalate_to'];

			$this->logger->debug( 'Custom Field Id for escalation : '.$custom_field_id );
			$this->logger->debug( 'Custom Field Value for escalation : '.$custom_field_value );
			$this->logger->debug( 'days to check for escalation : '.$days_to_check );
			$this->logger->debug( 'escalate to  : '.$escalate_to );

			$not_updated_issues =
			$this->C_custom_fields->getNotUpdatedKeyForValueByDays( $key, $value, $days_to_check );

			//escalate the issues
			$this->escalateIssue( $not_updated_issues, $escalate_to );
		}
	}

	/**
	 * Escalate the issue by signaling listener to the manager
	 *
	 * @param unknown_type $not_updated_issues
	 * @param unknown_type $escalate_to
	 */
	private function escalateIssue( $not_updated_issues, $escalate_to ){

		$this->logger->debug( 'Not Updated Issues : '.print_r( $not_updated_issues, true ) );

		$now = date( 'Y-m-d H:i:s' );
		if( is_array( $not_updated_issues ) ){

			foreach( $not_updated_issues as $issue_to_escalate ){

				$params = array(

						'user_id' => $issue_to_escalate['customer_id'],
						'store_id' => $this->user_id,
						'ticket_id' => $issue_to_escalate['id'],
						'escalate_email' => $escalate_to,
						'on_update' => false,
						'send_sms' => false,
						'now' => $now 
				);
					
				//add to escalate table
				$this->issueTrackerModel->addEscalationRecord( $params );
				
				$ret = $this->C_listeners_manager->signalListeners( EVENT_CUSTOMER_FEEDBACK, $params );
			}
		}

		$this->logger->debug( 'Escalation Done Time To Return' );
		return true;
	}

	/**
	 * get the revision log that needs to be put into the database
	 *
	 * @param unknown_type $params
	 */
	private function getIssueRevisionLog( $params ){

		$changed_flag = false;
		$revision_log = array();
		$issue_details = $this->issueTrackerModel->getHash();

		foreach( $issue_details as $key => $value ){

			//if the form params is not there
			//dont check further
			if( !isset( $params[$key] ) ) continue;

			$form_value = $params[$key];

			if( $form_value != $value ){

				$changed_flag = true;

				//TODO : take it from the config key and store in label
				$default_configs = $this->getLabelConfigs();
				$label = $default_configs[$key];
				if( $key == 'assigned_to' ){

					$this->C_admin_user->load( $value );
					$user_details = $this->C_admin_user->getDetails();

					$value =

					$user_details['title'] .' '.
					$user_details['first_name'] . ' ' .
					$user_details['last_name'];

					$this->C_admin_user->load( $form_value );
					$user_details = $this->C_admin_user->getDetails();

					$form_value =

					$user_details['title'] .' '.
					$user_details['first_name'] . ' ' .
					$user_details['last_name'];
				}

				array_push( $revision_log, array( 'label' => $label, 'from' => $value, 'to' => $form_value ) );
			}
		}

		return array( $changed_flag, $revision_log );
	}

	/**
	 * Generate the ticket for the customer who is relying on
	 * the mail.
	 *
	 * Please register the issue for the customer and reply instantly with the ticket code.
	 */
	public function generateTicketByEmail(){

		$e = new Email();

		//This will be the same across all the templates
		$body_to_reply = $this->config_mgr->getKey( CUSTOMER_FEEDBACK_EMAIL_BODY_TEMPLATE );
		$assigned_to = $this->config_mgr->getKey( SELECT_DEFAULT_STORE_FOR_FEEDBACK );

		$number_of_emails =
		$this->config_mgr->getKey( SERVER_FEEDBACK_EMAIL_VIEW );

		//json decoded keys
		$imap_host_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_HOST );
		$imap_port_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PORT );
		$user_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_USERNAME );
		$pass_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PASSWORD );

		$default_priority_all = $this->config_mgr->getKey(DEFAULT_CUSTOMER_FEEDBACK_EMAIL_PRIORITY );
		$default_status_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_STATUS );
		$default_department_all = $this->config_mgr->getKey( DEFAULT_CUSTOMER_FEEDBACK_EMAIL_DEPARTMENT );

		$this->logger->debug( 'all the config has been taken up number of email conigured : '.count( $number_of_emails ) );
		for( $i = 0 ; $i < count( $number_of_emails ) ; $i++ ){

			try{

				$user = $user_all[$i];
				$pass = $pass_all[$i];
				$imap_host = $imap_host_all[$i];
				$imap_port = $imap_port_all[$i];

				//connect to email
				$this->logger->debug( 'Connecting with the email with configs, user : '.$user.' pass '
						.$pass.' host '.$imap_host.' port '.$imap_port );

				$conn = $e->login( $imap_host, $imap_port, $user, $pass );

				//get status
				$status = $e->getStatus( $conn );

				$this->logger->debug( 'status for messages : '.$status );

				//ach of the mail
				foreach($status as $s){

					$getList = $e->getList( $conn, $s );
					$msg_no = $e->getMsgNoByUid( $conn, $s );

					$header = $e->getHeaderInfo( $conn, $msg_no );
					$sender_name = $e->getSenderName( $header );
					$this->logger->debug(" Feedback sender name... $sender_name");

					$sender_email = $e->getSenderMailId( $header );
					$this->logger->debug( " Feedback sender email... $sender_email" );

					$storeController = new ApiStoreController();
					$mailer_customer_id = $this->issueTrackerModel->storeEmailAndName( $sender_name, $sender_email );
					$this->logger->debug(" Feedback customer id... $mailer_customer_id");

					$subject = str_replace( "'", '', stripcslashes( $e->getSubject( $getList ) ) );
					$body = str_replace( "'", '', stripcslashes( $e->getBody( $conn, $s ) ) );

					//create the params to add the new issue
					$email_params['status'] = $default_status_all[$i];
					$email_params['priority'] = $default_priority_all[$i];
					$email_params['department'] = $default_department_all[$i];
					$email_params['assigned_to'] = $assigned_to;
					$email_params['assigned_by'] = $this->user_id;
					$email_params['due_date'] = Util::getDateByDays( false, 10 );
					$email_params['issue_code'] = $subject;
					$email_params['issue_name'] = $body;
					$email_params['customer_id'] = $mailer_customer_id;
					$email_params['reported_by'] = 'EMAIL';

					$this->add( $email_params );

					$this->logger->debug( 'ticket created informing customer about it' );
					$ticket_code = $this->issueTrackerModel->getTicketCode();
					$sender = $e->getSender( $header );

					if( strlen( $body_to_reply ) > 10 )
						Util::sendEmail( $sender, $subject.' - '.$ticket_code, $body_to_reply, $this->org_id );

				}
			}catch( Exception $e ){
					
				$this->logger->error( 'Emailer ticket could not be created '.$e->getMessage() );
			}
		}
	}

	/**
	 * @param unknown_type $issue_id
	 */
	private function compareIssueValues( $issue_id, $params ){

		//check the fields have changed or not for the custom fields
		list( $custom_value_has_changed, $custom_revision_log ) =
		$this->C_custom_fields->getRevisionString( $params, $this->org_id, 'customer_feedback', $issue_id );

		//check the fields have changed or not for the basic table
		list( $issue_value_has_changed, $issue_revision_log ) =
		$this->getIssueRevisionLog( $params );

		if( !$issue_value_has_changed && !$custom_value_has_changed )
			throw new Exception( 'No Value Was Changed.' );
			
		return array_merge( $custom_revision_log, $issue_revision_log );
	}

	/**
	 * get The Customer Email For The Issue For
	 * Signalling The Listener
	 *
	 * @param unknown_type $customer_id
	 * @param unknown_type $reported_by
	 */
	private function getCustomerEmail( $customer_id, $reported_by ){

		if( $reported_by == 'EMAIL' ){

			$details =
			$this->issueTrackerModel->getIncomingEmailDetailsById( $customer_id );

			$email = $details['email'];
			$send_sms = false ;
		}else{
			$user = UserProfile::getById( $customer_id );
			$eup = new ExtendedUserProfile( $user, $this->currentorg );

			$email = $eup->getEmail();
			$send_sms = true;
		}

		return array( $email, $send_sms );
	}

	/**
	 * Signals The Listeners
	 *
	 * @param $send_sms
	 * @param $on_update
	 */
	private function signalListeners( $on_update = false ){

		$customer_id = $this->issueTrackerModel->getCustomerId();
		$reported_by = $this->issueTrackerModel->getReportedBy();
		$issue_id = $this->issueTrackerModel->getId();

		$C_admin_user = new AdminUserController();
		$C_admin_user->load( $this->issueTrackerModel->getAssignedTo() );
		
		$has_map_admin_user = $C_admin_user->getDetails();
		$assigned_to_name =	
			$has_map_admin_user['first_name'] . ' ' . $has_map_admin_user['last_name'];
	
		$C_store = StoreProfile::getById( $this->issueTrackerModel->getCreatedBy() );
		$created_by_name = $C_store->first_name . ' ' . $C_store->last_name;
	
		//get customer email
		//if incoming mailer types the send sms should
		//be false
		list( $customer_email, $send_sms ) =
		$this->getCustomerEmail( $customer_id, $reported_by );

		$params = array(

				'user_id' => $customer_id,
				'store_id' => $this->user_id,
				'ticket_id' => $issue_id,
				'customer_email' => $customer_email,
				'on_update' => $on_update,
				'send_sms' => $send_sms,
				'assigned_to_name' => $assigned_to_name,
				'created_by_name' => $created_by_name
		);

		$option = $this->getCustomFieldAsOptions();
		//create custom field for signalling listener
		foreach( $option AS $name => $id ){

			$value = $this->C_custom_fields->getCustomFieldValueByFieldName(
					$this->org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $name
			);

			$value = json_decode($value, true);
			$this->logger->debug('Custom Logger: '.print_r($option,true));
			$this->logger->debug('Custom Row Item Logger: '.print_r($value,true));
			$params['custom_'.$name] = $value[0];
		}

		$ret = $this->C_listeners_manager->signalListeners( EVENT_CUSTOMER_FEEDBACK, $params );
	}

	/**
	 *
	 * @param unknown_type $issue_id
	 * @param unknown_type $params
	 * CONTRACT(
	 *
	 * 	'status' => VALUE,
	 * 	'priority' => VALUE,
	 * 	'department' => VALUE,
	 * 	'assigned_to' => VALUE,
	 * 	'assigned_by' => VALUE,
	 * 	'due_date' => VALUE,
	 * )
	 */
	public function update( $issue_id, $params ){
		if( !$issue_id )
			throw new Exception( 'Issue Code Has Not Been Provided For The Update' );
			
		//Step 1 If changed for any update the fields
		$revision_param = $this->compareIssueValues( $issue_id, $params );

		//update the issue log
		$issue_log_id = $this->issueTrackerModel->updateIssueLog( $issue_id );

		if( !$issue_log_id )
			throw new Exception( 'Issue History Could Not Be update. Update Failed.' );

		//update the custom field data log
		$custom_filed_log_id = $this->C_custom_fields->
		createCustomFieldLog( $this->org_id, $issue_log_id, $issue_id, CUSTOMER_CUSTOM_FEEDBACK );

		if( !$custom_filed_log_id )
			throw new Exception( 'Custom Field Could Not Be Updated. Update Failed.' );
			
		//update the issue
		$this->issueTrackerModel->setStatus( $params['status'] );
		$this->issueTrackerModel->setPriority( $params['priority'] );
		$this->issueTrackerModel->setDepartment( $params['department'] );
		$this->issueTrackerModel->setAssignedTo( $params['assigned_to'] );
		$this->issueTrackerModel->setAssignedBy( $this->user_id );
		$this->issueTrackerModel->setDueDate( $params['due_date'] );
		$this->issueTrackerModel->setLastUpdated( date('Y-m-d H:i:s') );

		$status = $this->issueTrackerModel->update( $issue_id );

		if( !$status )
			throw new Exception( 'Issue Could Not Be Updated.' );

		//update the custom field
		$this->C_custom_fields->processCustomFieldsFormForAssocID
		( false, $this->org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $params );

		//update the revision log
		$this->updateRevisionLog( $issue_id, $issue_log_id, $revision_param );

		//Step 5 Once Updated call listeners
		$this->signalListeners( true );
	}

	/**
	 * Updates the revision log
	 *
	 * @param $issue_id
	 * @param $issue_log_id
	 * @param $revision_log
	 */
	private function updateRevisionLog( $issue_id, $issue_log_id, $revision_log ){

		$revision_string = json_encode( $revision_log );

		$updated =
		$this->issueTrackerModel->updateRevisionLog( $issue_id, $issue_log_id, $revision_string );

		if( !$updated )
			throw new Exception( 'Issue Revision Could Not Be Updated .' );
	}

	/**
	 * Generates the ticket code
	 * @param unknown_type $issue
	 */
	private function generateTicketCode(){

		$ticket_code = -1;
		$issue_ticket = $this->config_mgr->getKey( CONF_ISSUE_TICKET );

		if( $issue_ticket ){

			$ticket_code_length = ( int ) $this->config_mgr->getKey( CONF_TICKET_CODE_LENGTH );
			if( $ticket_code_length < 6 ) $ticket_code_length = 8;

			$alphanumeric = $this->config_mgr->getKey( CONF_TICKET_CODE_IS_ALPHANUMERIC );

			do{

				$ticket_code = Util::generateRandomCode( $ticket_code_length, $alphanumeric );
				$ticket_code = Util::makeReadable( $ticket_code );

			}while( ApiIssueTrackerModelExtension::isTicketCodeExists( $ticket_code, $this->org_id ) );
		}

		return $ticket_code;
	}

	/**
	 *
	 * @param unknown_type $issue_id
	 * @param unknown_type $params
	 * CONTRACT(
	 *
	 * 	'status' => VALUE,
	 * 	'priority' => VALUE,
	 * 	'department' => VALUE,
	 * 	'assigned_to' => VALUE,
	 * 	'assigned_by' => VALUE,
	 * 	'due_date' => VALUE,
	 * 	'issue_code' => VALUE,
	 * 	'issue_name' => VALUE,
	 * 	'customer_id' => VALUE[ MOBILE | user_id | incoming_email_id ],
	 * 	'ticket_code' => VALUE,
	 * 	'reported_by' => VALUE[ EMAIL | CLIENT | INTOUCH | MICROSITE ],
	 *
	 * 	'custom_fields' => VALUE
	 * )
	 */
	public function add( $params ){

		//1 : Generate Ticket
		$ticket_code = $this->generateTicketCode();
		
		$this->ticket_code=$ticket_code;

		//2 : does customer exists in the database
		$params['customer_id'] =
		$this->isCustomerExists( $params['reported_by'], $params['customer_id'] );

		//3 : does this customer has already asked for the
		$this->isIssueCodeExistsForCustomer( $params['issue_code'], $params['customer_id'], $params['reported_by'] );

		//add the issue
		$this->issueTrackerModel->setOrgId( $this->org_id );
		$this->issueTrackerModel->setStatus( $params['status'] );
		$this->issueTrackerModel->setPriority( $params['priority'] );
		$this->issueTrackerModel->setDepartment( $params['department'] );
		$this->issueTrackerModel->setAssignedTo( $params['assigned_to'] );
		$this->issueTrackerModel->setAssignedBy( $params['assigned_by'] );
		$this->issueTrackerModel->setDueDate( $params['due_date'] );

		$this->issueTrackerModel->setIssueCode( $params['issue_code'] );
		$this->issueTrackerModel->setIssueName( $params['issue_name'] );
		$this->issueTrackerModel->setCustomerId( $params['customer_id'] );
		$this->issueTrackerModel->setTicketCode( $ticket_code );
		$this->issueTrackerModel->setType( 'CUSTOMER' );
		$this->issueTrackerModel->setCreatedDate( date( 'Y-m-d' ) );
		$this->issueTrackerModel->setLastUpdated( date( 'Y-m-d' ) );

		$this->issueTrackerModel->setAssignedBy( $this->user_id );
		$this->issueTrackerModel->setReportedBy( $params['reported_by'] );
		$this->issueTrackerModel->setIsActive( true );
		$this->issueTrackerModel->setCreatedBy( $this->user_id );

		if( $params['is_critical'] )
			$this->issueTrackerModel->setMarkCriticalOn( date('Y-m-d H:i:s') );

		$issue_id = $this->issueTrackerModel->insert();

		if( !$issue_id )
			throw new Exception( 'Issue Could Not Be Added!!!' );
			
		//add the custom fields
		//update the custom field
		$this->C_custom_fields->processCustomFieldsFormForAssocID
		( false, $this->org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $params );

		//signal listeners
		$this->signalListeners();

		return $issue_id;
	}

	/**
	 * Check if the customer exists in our database.
	 *
	 * The check will be done in the two ways.
	 * As the CCMS configs the issue can be raised in two ways
	 *
	 * 1) By EMAIL : customer id will be checked in incoming mail table
	 * 2) By APIs/INTOUCH : customer id will be checked in INTOUCH user table
	 *
	 * @param unknown_type $reported_by[ EMAIL | CLIENT | INTOUCH | MICROSITE ]
	 */
	private function isCustomerExists( $reported_by, $customer_id ){

		if( $reported_by == 'EMAIL' ){

			$user_id =
			$this->issueTrackerModel->isIncomingEmailCustomerExists( $customer_id );
		}elseif( $reported_by == 'INTOUCH' || $reported_by == 'SOCIAL'){

			$user_id =
			$this->isMobileExists( $customer_id );
		}else{

			$user_id =
			$this->isUserExists( $customer_id );
		}

		return $user_id;
	}

	/**
	 * @param unknown_type $customer_id
	 */
	private function isMobileExists( $mobile ){

		$user = UserProfile::getByMobile( $mobile );

		if( !$user ){

			throw new Exception( 'Customer Is Not Registered With Us' );
		}

		return $user->user_id;
	}

	/**
	 * @param unknown_type $user_id
	 */
	private function isUserExists( $user_id ){

		$user = UserProfile::getById( $user_id );

		if( !$user ){

			throw new Exception( 'Customer Is Not Registered With Us' );
		}

		return $user->user_id;
	}

	/**
	 *
	 * @param unknown_type $filter
	 */
	public function getCurrentStatusForAllTickets( $filter, $ticket_id = false , $ticket_code = false, $user_id=false, $limit = false) {

		$master_set = $this->issueTrackerModel->getCurrentStatusForAllTickets( $filter, $ticket_id, $ticket_code, $user_id, $limit );

		$custom_fields = new CustomFields();
		$cf_attrs = $custom_fields->getCustomFieldAttrsAsOption( $this->org_id, 'customer_feedback' );

		$custom_value = array();
		foreach( $master_set as &$row ){

			$store_data = array( 'ticket_code' => $row['ticket_code'] );

			//custom field name and the counts are in group concat format
			$cf_names = explode('^*^', $row['Custom-Field-Name']);
			$cf_value = explode('^*^', $row['Custom-Field-Value']);
			$cf_type = explode('^*^',  $row['Custom-Field-Type']);

			unset( $row['Custom-Field-Name'] );
			unset( $row['Custom-Field-Value'] );
			unset( $row['Custom-Field-Type'] );

			for( $i = 0; $i < count( $cf_names ); $i++ ) {

				//Store as ret_val_<cf_index>
				$custom_values = json_decode( $cf_value[$i], true );

				if( !$custom_values && strlen( $cf_value[$i] ) > 0 ){

					$cf_value[$i] = str_replace( array( "\r\n", "\n", "\r"), ' ', $cf_value[$i] );
					$custom_values = json_decode( $cf_value[$i], true );
				}
				$cf_value[$i] = $custom_values;
					
				$index = 'ret_val_'.$i;
					
				$value_to_show = $cf_value[$i];
				if( $cf_type[$i] == 'select' ){

					$name = $cf_names[$i];
					$attrs = json_decode( $cf_attrs[$name], true );
					if( is_array( $attrs ) )
						$attrs = array_flip( $attrs );

					if( $value_to_show == 'NA' ){
						//skip it
					}else if( is_array( $value_to_show ) ){
							
						$values = array();
						foreach( $value_to_show as $value ){

							array_push( $values, $attrs[$value] );
						}
							
						$value_to_show = $values;
					}else{
							
						$value_to_show = $attrs[$value];
					}
				}
					
				if( is_array( $value_to_show ) )
					$value_to_show = implode( ',' , $value_to_show );

				$store_data[$index] =  $value_to_show;
			}

			array_push( $custom_value, $store_data );
			$custom_field_names = $cf_names;
		}

		foreach($master_set as &$row){

			foreach( $custom_value as $value ){

				if( $value['ticket_code'] == $row['ticket_code'] ){

					$return_val_all = array();

					for( $i = 0; $i < count( $custom_field_names ); $i++ ){

						$val = "ret_val_".$i;
						$field_name = $custom_field_names[$i];
						$row[$field_name] = $value[$val];

					}
				}
			}
		}

		return  $master_set ;
	}


	/**
	 * Customer is not registered
	 *
	 * @param unknown_type $customer_id
	 */
	private function isIncomingEmailCustomerExists( $customer_id ){

		$user_id =
		$this->issueTrackerModel->isIncomingEmailCustomerExists( $customer_id );

		if( !$user_id ){

			throw new Exception( 'Customer Is Not Registered With Us' );
		}

		return $user_id;
	}

	/**
	 * @param unknown_type $issue_code
	 * @param unknown_type $customer_id
	 */
	private function isIssueCodeExistsForCustomer( $issue_code, $customer_id, $reported_by ){

		$issue_id =
		$this->issueTrackerModel->isIssueCodeExistsForCustomer( $issue_code, $customer_id, $reported_by );

		if( $issue_id  ){

			throw new Exception( 'Issue With The Same Code Has Been Registered For The Customer' );
		}
	}

	/**
	 * Get Issue Detail by issue id
	 * @param unknown_type $issue_id
	 */
	public function getIssueInfoByIssueId( $issue_id ){

		$this->issueTrackerModel->load( $issue_id );
		
		$filter = "AND `it`.`id` = $issue_id";
		return $this->getCurrentStatusForAllTickets( $filter );
	}

	/**
	 * 
	 * Checks if user can edit the ticket
	 */
	public function isIssueEditableByUser( ){
	
		if( !$this->issueTrackerModel->getId() )
			throw new Exception( "Tracker Class Not Loaded" );
			
		$edit_allowed = true;
		$superior_editable =
			$this->config_mgr->getKey( CCMS_CAN_ONLY_SUPERIORS_EDIT );
			
		if( $superior_editable ){

			$this->C_admin_user->load( $this->currentuser->user_id );
			$details = $this->C_admin_user->getDetails( );
			$current_user_role_id = $details['role_id'];

			$issue_details = $this->issueTrackerModel->getHash();
			$assigned_to_user_id = $issue_details['assigned_to'];
			
			$this->C_admin_user->load( $assigned_to_user_id );
			$details = $this->C_admin_user->getDetails();
			$assigned_to_user_role_id = $details['role_id'];
			
			$org_controller = new ApiOrganizationController();
			$parent_role_ids =
				$org_controller->getParentRoles( $current_user_role_id );

			//check if assigned to user has more authority than current user
			if( in_array( $assigned_to_user_role_id, $parent_role_ids ) ){
				
				$edit_allowed = false;
			}

			//check for peers
			if( 
				( $current_user_role_id == $assigned_to_user_role_id ) 
				&&
				( $this->currentuser->user_id != $assigned_to_user_id )
			){
				$edit_allowed = false; 
			}
		
		}
		
		return $edit_allowed;
	}
	
	/**
	 * get issue Revision log details
	 */
	public function getRevisionLogs( $issue_id ){

		return $this->issueTrackerModel->getRevisionDetails( $issue_id );
	}

	/**
	 * upload Tickets With CustomFieldMapping and ticket codes
	 * @param unknown_type $params
	 * @param unknown_type $file
	 * @return multitype:
	 */
	public function uploadTicketWithCustomFieldMapping( $params, $file ){

		$col_mapping = array();
		$col_mapping['ticket_code'] = 0;

		$number_of_tags = $params['custom_tag_count'];

		$this->logger->debug('params: '.$params);

		//Mapping Custom Field.
		for( $i = 1; $i <= $number_of_tags; $i++){

			$key_select = $params['custom_select_tag_'.$i];
			$col_mapping[$key_select] = intval($params['custom_text_tag_'.$i])-1;
		}

		$spreadsheet = new Spreadsheet();
		$setting = array();
		$setting['header_rows_ignore'] = 1;
		$not_valid_ticket = array();

		while(($processed_data = $spreadsheet->LoadCSVLowMemory
								(
								  $file, $file_batch_size, false,
								  $col_mapping, $setting ) ) != false 
				                                 ){
	    	
			foreach( $processed_data as &$row ){

				try{

					$filter = " AND `it`.`ticket_code` =  "."'".$row['ticket_code']."'";
					$res = ApiIssueTrackerModelExtension::
					getAllIssues( $filter , $this->org_id );

					$result = $res[0];
					
					//Loading custom field value.
					$custom = $this->C_custom_fields->
									getCustomFieldValuesByAssocId
									(
										$this->org_id,CUSTOMER_CUSTOM_FEEDBACK , $result['id']
									);
					$this->logger->debug('custom_field:'.print_r($custom , true));

					//Converting json_encoded value to string
					$custom_keys = array_keys($custom);
					for($i = 0; $i < count($custom_keys);$i++){
						
						$k = $custom_keys[$i];
						$c = json_decode($custom[$k]);
						$custom[$k] = $c[0];
					}
					$attrs = $this->C_custom_fields->getCustomFieldAttrsAsOption($this->org_id, CUSTOMER_CUSTOM_FEEDBACK);
					
					//Mapping with CSV File.
					for( $i = 1; $i <= $number_of_tags; $i++){

							$key_select = 'custom_field__'.$params['custom_select_tag_'.$i];
							$result[$key_select] = $row[$params['custom_select_tag_'.$i]];
							$custom[$params['custom_select_tag_'.$i]] = $row[$params['custom_select_tag_'.$i]];
							
							//IF Custom Field Value Entered in CSV is Not Match With attrs
							$b = (array)json_decode($attrs[$params['custom_select_tag_'.$i]]);
							if( (!empty($b)) && !in_array($custom[$params['custom_select_tag_'.$i]], $b) )
								Throw New Exception("Custom Field Value Do not Match ".
													 $params['custom_select_tag_'.$i]. 
													 " It Must be ".implode(',', $b)
										           );
							
							//If Custom Field Value not present
							if( !$row[$params['custom_select_tag_'.$i]] )
								Throw New Exception('Custom Field Value Not Present');
					}

					for($i = 0; $i < count($custom_keys);$i++){
						
						$k = $custom_keys[$i];
						$result['custom_field__'.$k] = $custom[$k];
					}				
					
					$result['reported_by'] = 'INTOUCH';
					$this->logger->debug('Result Upload Issue:'.print_r($result,true));

						if( $result ){
							
							$this->issueTrackerModel->load( $result['id'] );
							$this->update( $result['id'] , $result );
						}
					}catch(Exception $e){

						$custom = array();
						for( $i = 1; $i <= $number_of_tags; $i++){

							$key_select = $params['custom_select_tag_'.$i];
							
							array_push( $custom , array( $key_select =>
								     	(!$row[$params['custom_select_tag_'.$i]] ) ?	
										'NA' :$row[$params['custom_select_tag_'.$i]] )
									);
						}
						
						array_push( $not_valid_ticket, array( 'ticket_code'=> ( 
								          !$row['ticket_code'] ) ? 'NA' : $row['ticket_code'],
								          'custom' => $custom ,'error'=>( !$row['ticket_code'] ) ? 
								          'Ticket code Not Present' :$e->getMessage())
						          );

						$this->logger->debug('Not uploaded tickets :'.
								 			  print_r($not_valid_ticket,true)
								    );
					}
				}
				return $not_valid_ticket;
		}
		return 'No Data Available';
	}
}
