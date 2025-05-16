<?php

/**
 * 
 * @author prakhar
 *
 */
include_once "controller/ApiStore.php";
class StoreModule extends BaseModule {

	public $db;
	public $storeController;
	public $listenersManager;
	
	function __construct() {
		
		parent::__construct();
		$this->db = new Dbase('stores');	
		$this->lm = new LoyaltyModule();
				
		$this->storeController = new CCMSStoreController($this);
		$this->listenersManager = new ListenersMgr($this->currentorg);
	}
	
	/**
	 * Shitty Function Filled with configs to create form.
	 * You will find only If And Else.
	 * Please try to avoid any modification.
	 * 
	 * If ( your mood is good ){
	 * 		please don't spoil it.
	 * }else if ( your mood is bad ){ 
	 * 		save something for night
	 * }else
	 * 		Your Fucking Wish.
	 * 
	 * @deprecated : keepint it just for future laugh :)
	 * 
	 * DISCLAIMER : Created & Deprecated By P.V
	 * @param int $issue_id
	 * @param int $update_id
	 * @param string $type_selected
	 */
	function addIssueAction($issue_id = false, $update_id = false, $reg_mobile = false){
		return false;// so that no one use this.
		$org_id = $this->currentorg->org_id;
		$org_name = $this->currentorg->name;
		$customFields = new CustomFields();
		
		$lm = new LoyaltyModule();
		$am = new AdministrationModule();
		$issue = $this->storeController->getIssueObject($issue_id, $update_id);

		$on_update = false;
		if($issue_id && !$update_id)
			$on_update = true;	
			
		$edit_read_only = array();
		list($option, $cf_ids) = $this->storeController->getCustomFieldIdsAsOptions($customFields);

		if($reg_mobile){
	
			$this->flash( 'Please Register The Customer!' );
			//$this->js->addPopUpForm('reg_form');
			//$reg_form = new Form('reg_form', 'post');
			//$this->storeController->createRegistrationForm( $reg_form, $reg_mobile );
			//$this->data['reg_form'] = $reg_form;

			//$lm->loyaltyController->validateRegisterForm( $reg_form, $customFields, $this->module, 'addissue' );
		}
		
		$department_label = $this->getConfiguration(CUSTOMER_FEEDBACK_DEPARTMENT_LABEL, 'DEPARTMENT*');
		$priority_label = $this->getConfiguration(CUSTOMER_FEEDBACK_PRIORITY_LABEL, 'PRIORITY*');
		$status_label = $this->getConfiguration(CUSTOMER_FEEDBACK_STATUS_LABEL, 'STATUS*');
		$short_desc_label = $this->getConfiguration(CUSTOMER_FEEDBACK_SHORT_DESC_LABEL, 'Short Description');
		$long_desc_label = $this->getConfiguration(CUSTOMER_FEEDBACK_LONG_DESC_LABEL, 'Long Description');
		$assigned_to_label = $this->getConfiguration(CUSTOMER_FEEDBACK_ASSIGNED_TO_LABEL, 'Assigned To');
		$due_date_label = $this->getConfiguration(CUSTOMER_FEEDBACK_DUE_DATE_LABEL, 'Due Date');
		$priority = json_decode($this->getConfiguration(CONF_TRACKER_PRIORITY_LIST), true);
		$status = json_decode($this->getConfiguration(CONF_TRACKER_STATUS_LIST), true);
		$activity = json_decode($this->getConfiguration(CONF_TRACKER_DEPARTMENT_LIST), true);
		$priority_enabled = $this->currentorg->getConfigurationValue(CONF_IS_PRIORITY_ENABLED, true);
		$due_date_enabled = $this->currentorg->getConfigurationValue(CONF_IS_DUE_DATE_ENABLED, true);
		$assigned_option = $this->storeController->getAssignedOption();
		$status_attrs=array('list_options' => $status );
		
		if($issue_id){

			$selection_form = new Form('selection','post');
			$selection_form->setStyling( false );
			
			$selection_form->addField('checkbox','xls','<h3>Download Tables As Excel</h3>');
			if($selection_form->isValidated()){
				
				$params = $selection_form->parse();
				$xls = $params['xls'];
			}
			//----====----===----/
			$this->data['show_link'] = true;
			$reported_by = $issue->getReportedBy( );
			
			list($table, $compact_table, $overview_table) = $this->getIssuedHistory( $issue_id, $cf_ids, $reported_by );

			$attrs_option = $customFields->getCustomFieldAttrsAsOption( $this->currentorg->org_id, 'customer_feedback' );
			$custom_name = array_keys( $attrs_option );

			//remove headers
			$compact_table->reorderColumns(
								array_merge(
									array(
										"$status_label", "$priority_label", 
										"$department_label", "$assigned_to_label", 
										'last_updated_by',
									), 
									array_merge(
										$custom_name, array("$due_date_label", 'last_updated')
									)
							));
				
			$select_assigned = $issue->getAssignedTo();
			$status_attrs=array('disabled' => 'disabled','list_options' => $status );
			
		}else
			$select_assigned = $this->getConfiguration(SELECT_DEFAULT_STORE_FOR_FEEDBACK, true);

		//Create Forms
		$form = new Form('create_issue','post');
		$form->useHorizontalFormStyle();
		$form->setSeperator(3);
		
		if($issue_id){
			
			$issue->read();
			$id = $issue->getCustomerId();
			$user = UserProfile::getById($id);
		}
		
		//Set status And Department
		$form->addField('select', 'status', $status_label, $issue->getStatus(),$status_attrs);
		$form->addField('select', 'department', $department_label, $issue->getDepartment(), array_merge($edit_read_only,array('list_options' => $activity )));
		
		if($priority_enabled)
			$form->addField('select', 'priority', $priority_label, $issue->getPriority(), array('list_options' => $priority ));	
		else
			$form->addField('separator', 'sep_ass_3', ' ');
		
		if(!$issue_id){

			//Issue Description
			$form->addField('text', 'issue_code', $short_desc_label, $issue->getIssueCode(), '', '/.+/', 'Can not be empty');
			$form->addField('textarea', 'issue_name', $long_desc_label, $issue->getIssueName(), array(rows => 10, cols => 60));
			$form->addField('separator', 'sep_ass_4', ' ');
			
			//Customer Info Or Store Info
			$this->storeController->addCustomerInfoToForm($form);
		}
		$this->storeController->addStoreInfoToForm($form, $issue_id);
		//Set Assigned To.
		$form->addField('select', 'assigned_to', $assigned_to_label, $select_assigned, array('list_options' => $assigned_option));

		if($due_date_enabled)
			$form->addField('datepicker', 'due_date', $due_date_label, ($issue->getDueDate())?($issue->getDueDate()):(date('Y-m-d')));	
		else
			$form->addField('separator', 'sep_ass', ' ');
		$form->addField('separator', 'sep_ass_1', ' ');
		//add stores
		$customFields->addCustomFieldsFormForAssocID($form, $org_id, CUSTOMER_CUSTOM_FEEDBACK, $issue_id, $update_id);

		//Add Ajax Calls
		$this->storeController->addAjaxConditionsForAddIssue($this->lm, $form);
		
		//Validation starts here...
		if($form->isValidated()){
			
			$params = $form->parse();

			if($issue_id)
				$changed = $issue->compare( $params );

			//if changed update changed logs and return
			if($changed){
				
				$update_id = $issue->createLogEntries();
				$customFields->createCustomFieldLog($org_id, $update_id, $issue->getId(), CUSTOMER_CUSTOM_FEEDBACK);
			}
			if($issue_id && !$changed)
				Util::redirect($this->module, "addissue/$issue_id/$update_id", false, 'No Change Was Made');
			
			if(!$issue_id){

				$this->storeController->saveIssueTrackerFields($issue, $params);
				$this->storeController->generateTicketCode($issue);
			}
			
			$issue->setDepartment( $params['department'] );
			$issue->setDueDate($params['due_date']);
			$issue->setResolvedBy($status, $params['status'], $this->currentuser->user_id) ;
			if($params['status'] == '' )
				$params['status']=$issue->getStatus();
			$issue->setStatus( $params['status'] );
			$issue->setAssignedTo( $params['assigned_to'] );
			
			if($priority_enabled)
				$issue->setPriority( $params['priority'] );
			else
				$issue->setPriority( $priority[0] );
			
			$status = $issue->save();
			
			if(!$status)
				Util::redirect($this->module, "addissue/0/0", false, 'Issue Already Registered For The User');
				
			$ret = $this->storeController->signalFeedbackListener(
																	$customFields, $issue, $form, $option,  
																	$on_update, $this->listenersManager 
																);
			if(!$ret)
				$this->logger->debug(" Listener Failed ...");

			$issue_id = $issue->getId();
			Util::redirect($this->module, "addissue/$issue_id", false, 'New Issue/update Was done Successfully');
		}
		
		if($this->data['show_link']){

			$desc_table = clone $overview_table;
			$desc_table->reorderColumns(array($short_desc_label, $long_desc_label));
			$overview_table->removeHeader($short_desc_label);
			$overview_table->removeHeader($long_desc_label);
		}
		
		if($xls){
			
			$spreadsheet = new Spreadsheet();
			$spreadsheet->loadFromTables(array($overview_table, $desc_table, $compact_table))->download("Issue Overview ", 'xls');
		}		
		
		$this->data['table'] = $compact_table;
		$this->data['overview_table'] = $overview_table;
		$this->data['desc_table'] = $desc_table;
		$this->data['start_form'] = $select_form;
		$this->data['form'] = $form;
		
		$this->data['sel_form'] = $selection_form;
	}

	function storeCustomerFeedBackAction( $mobile, $org_id ){

$xml_string = <<<EOXML

	<root>
		<customer_feedback>
			<ticket_id>-1</ticket_id>
			<customer_id>CUSTOMER_ID_NA</customer_id>
			<mobile>{{mobile}}</mobile>
			<status>{{status}}</status>
			<priority>{{priority}}</priority>
			<department>{{department}}</department>
			<short_desc>{{short_desc}}</short_desc>
			<long_desc>{{long_desc}}</long_desc>
		</customer_feedback>
	</root>   
EOXML;

		$config_manager = new ConfigManager( );
		$C_loyalty_module = new LoyaltyModule();
		$loyalty_controller = $C_loyalty_module->loyaltyController;

		$default_priority_all = $config_manager->getKey( 'CONF_TRACKER_PRIORITY_LIST' );
		$default_status_all = $config_manager->getKey( 'CONF_TRACKER_STATUS_LIST' );
		$default_department = $config_manager->getKey( 'CONF_TRACKER_DEPARTMENT_LIST' );

		$status_options = 
			array( 
				'mobile' => $mobile, 'status' => $default_status_all[0],
				'priority' => $default_priority_all[0], 'department' => $default_department[0]
			);
			
		$xml_string = Util::templateReplace( $xml_string, $status_options );
		
		$user_profile = UserProfile::getByMobile( $mobile );
		$customer_id = $user_profile->user_id;
		$bill_details = $loyalty_controller->getBillDetails( false, false, $customer_id, 50 );
		
		$bill_options = 
			array( 'bill_number' => 'N-A', 'bill_date' => 'N-A' );
		
		if( count( $bill_details ) > 0 ){
			
			$bill = $bill_details[0];
			
			$bill_date = $bill['date'];
			$bill_number = $bill['bill_number'];
			$created_at = $bill['entered_by'];
			
			global $currentuser;
			$currentuser = $this->currentuser = StoreProfile::getById( $created_at );
			
			$bill_options = 
				array( 'bill_number' => $bill_number, 'bill_date' => $bill_date );
		}
				
		$short_desc = $config_manager->getKey( CUSTOMER_FEEDBACK_MISSED_CALL_SHORT_DESC );//{{bill_number}}, {{bill_date}}
		$long_desc = $config_manager->getKey( CUSTOMER_FEEDBACK_MISSED_CALL_LONG_DESC );//{{bill_number}}, {{bill_date}}
		
		$short_desc = Util::templateReplace( $short_desc, $bill_options );
		$long_desc = Util::templateReplace( $long_desc, $bill_options );

		$desc_options = 
			array( 'short_desc' => $short_desc, 'long_desc' => $long_desc );
		$xml_string = Util::templateReplace( $xml_string, $desc_options );

		$this->setRawInput( $xml_string );
		$this->storeCustomerFeedBackApiAction();
	}
	
	function storeCustomerFeedBackApiAction(){

$xml_string = <<<EOXML

	<root>
		<customer_feedback>
			<ticket_id>-1</ticket_id>
			<customer_id>CUSTOMER_ID_NA</customer_id>
			<mobile>919899296345</mobile>
			<status>OPEN</status>
			<priority>HIGH</priority>
			<department>INTERNAL</department>
			<short_desc>susi--dsdschecdsdsk the cdffhange</short_desc>
			<long_desc>hi cdsdhanged requirement - susi</long_desc>
		</customer_feedback>
	</root>   
EOXML;

		try{
			
			
			global $url_version;
			
			$url_version = '1.0.0.1';
			$this->logger->debug('Start storeCustomerFeedBack Api For Customer Feedback');
			$xml_string = $this->getRawInput();
			if(Util::checkIfXMLisMalformed($xml_string)){
				$api_status = array(
						'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
						'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
				);
				$this->data['api_status'] = $api_status;
				return;
			}
			$element = Xml::parse( $xml_string );
			$elems = $element->xpath( '/root/customer_feedback' );	
			
			$response = array();

			//include the file
			include_once 'apiController/ApiCcmsController.php';
			
			$config_manager = new ConfigManager( );
			$C_ccms_controller = new ApiCcmsController( );
			$C_admin_user = new AdminUserController( );
			$C_org_controller = new ApiOrganizationController( );
			
			$assigned_to = $config_manager->getKey( SELECT_DEFAULT_STORE_FOR_FEEDBACK );
			
			foreach( $elems as $e ){

				//check if hierarchical assignment is present
				$is_hierarchical_assingment_enabled =
					$config_manager->getKey( CCMS_IS_HIERARCHICAL_ASSIGNMENT_ENABLED );
					
				if( $is_hierarchical_assingment_enabled ){

					$hierarchical_assingment_entity_type =
						$config_manager->getKey( CCMS_HIERARCHICAL_ENTITY_TYPE );
						
					$manager_id = false;
					switch ( $hierarchical_assingment_entity_type ){
						
						case 'ZONE' :

							$hash_details = 
								$C_org_controller->StoreTillController->
									load( $this->currentuser->user_id );
							$store_id = $hash_details['store_id'];
							
							$zones = 
								$C_org_controller->StoreController->getParentZone( $store_id );
								
								
							if( count( $zones ) > 0 ){

								
								$zone_id = $zones[0];//first should be -1 2nd should be root
								$zone_managers =
									$C_org_controller->ZoneController->getManagers( $zone_id );
								
								$manager_id = $zone_managers[0]['id'];
							}
							
							break;
							
						case 'CONCEPT' :
											
							$hash_details = 
								$C_org_controller->StoreTillController->
									load( $this->currentuser->user_id );
							$store_id = $hash_details['store_id'];
							 
							$concepts =
								$C_org_controller->StoreController->getParentConcept( $store_id );
				
								
							if( count( $concepts ) > 0 ){
								
								$concept_id = $concepts[0];//first should be -1 2nd should be root
								$concept_managers =
									$C_org_controller->ConceptController->getManagers( $concept_id );
								
								$manager_id = $concept_managers[0]['id'];
							}
					}
										
					if( $manager_id ) $assigned_to = $manager_id;
				}
								
				$this->logger->debug('Assigned To:'.$assigned_to);				
				if( !empty($e->customer_id) && $e->customer_id != 'CUSTOMER_ID_NA' 
						&& $e->customer_id != -1)
					$customer_id = trim($e->customer_id);
				else{
					$mobile = trim((string)$e->mobile);
					$customer = UserProfile::getByMobile( $mobile );
					if(!$customer)
						throw new Exception("Customer is not registered");
					$customer_id = $customer->getUserId();
				}
				
				$params = 
					array( 
						'status' => (string)$e->status , 
						'priority' => (string)$e->priority , 
						'reported_by' => 'CLIENT' ,
						'department' => (string) $e->department , 
						'issue_code' => (string)$e->short_desc , 
						'issue_name' => (string)$e->long_desc,
						'assigned_to' => $assigned_to , 
						'customer_id' => $customer_id 
					);
								
				//store the custom field information for the tickets
				$custom_fields = array();
					
				foreach( $e->xpath('custom_fields_data/custom_data_item') as $cfd ){
	
					$cf_name = (string) $cfd->field_name;
					$cf_value_json = (string) $cfd->field_value;
					$custom_fields['custom_field__'.$cf_name] = json_decode( $cf_value_json , true ); 						
				}
				
				$params = array_merge( $params , $custom_fields );
				// Call add issue method by passing params 
				$issue_id = $C_ccms_controller->add( $params );
				$this->logger->debug('Ticket Code: '.$issue_id);
				$filter = " AND `it`.`id` = '$issue_id' ";
				
				include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
				$issues = ApiIssueTrackerModelExtension::getAllIssues( $filter , $this->currentorg->org_id );
				$this->logger->debug('Issue Details :'.print_r($issues,true));
				
				$issue_response = array( 'ticket_id' => $issues[0]['ticket_code'] );
				
				$item_status = array(
					
					'key' => 'ERR_FEEDBACK_SUCCESS',
					'message' => 'Operation Successful'
				);
					
				$issue_response['item_status'] = $item_status ;
	
				array_push( $response, $issue_response );
				
				$url_version = '1.0.0.0';
			}
		}catch( Exception $e ){
				
				$response = 
						array(
								'ticket_id' => -1,
								'item_status' =>  array(
									
										'key' => 'ERR_FEEDBACK_UNSUCCESSFULL',
										'message' => $e->getMessage()
								)	
						); 
		}
		$this->data['api_status'] = $response;
	}
	
	function ticketDataApiAction( $customer_id , $ticket_code  , $status ){
		
		try{
			
			global $url_version;
			$url_version = '1.0.0.1';
			$create_table = false;
			
			if( $ticket_code ){
				
				$response = $this->getIssueByTicketCodeApiAction( $ticket_code , true );
				
			}else if( $customer_id ){
				
				$filter = " AND `customer_id` = '$customer_id' ";

				$create_table = true;
				
			}else{
				
				$store_id = $this->currentuser->user_id;
				$filter = " AND `created_by` = '$store_id' ";

				$create_table = true;
			}

			//include the file
			include_once 'apiController/ApiCcmsController.php';
			include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
			
			$C_ccms_controller = new ApiCcmsController();
			$C_config_manager = new ConfigManager();
						
			$url_version = '1.0.0.0';
			
			if( $create_table ){
				
				$issues =
				ApiIssueTrackerModelExtension::getAllIssues( $filter, $this->currentorg->org_id );

				if( count( $issues ) < 1 )
					throw new Exception( 'No Issue Was Registered For The Customer.' );

				
				$default_values = $C_ccms_controller->getLabelConfigs();
				
				$table = new Table();
				$table->importArray( $issues );
				
				$table->removeHeader( 'id' );
				if( !$C_config_manager->getKey('CONF_IS_PRIORITY_ENABLED') )
					$table->removeHeader('priority');
				else
					$table->addHeader( 'priority', $default_values['priority'] );
					
				if( !$C_config_manager->getKey('CONF_IS_DEPARTMENT_ENABLED') )
					$table->removeHeader('department');
				else
					$table->addHeader( 'department', $default_values['department'] );
					
				if( !$C_config_manager->getKey('CONF_IS_STATUS_ENABLED') )
					$table->removeHeader('status');
				else
					$table->addHeader( 'status', $default_values['status'] );
					
				$table->addHeader( 'issue_code', $default_values['issue_code'] );
				$table->addHeader( 'issue_name', $default_values['issue_name'] );

				$am = new AdministrationModule();
				$report_widgets = array(
						'ticket-table'	=> array('widget_name' => "Ticket Summary On, ".date("F 'y")."<br>".
						"Use Ticket Code To See Full Details For A Ticket"
						, 'widget_code' => 'ticket_table', 'widget_data' => $table ),
				);
				
				$response = $am->createWidgetToSendMail( $report_widgets );
			}
						
		}catch(Exception $e){
			
			$response = $e->getMessage();
		}
		
		$this->data['output'] = $response;
	}

	function capillaryPokeAction(){
		
		$form = new Form('poke_form');
		
		$am = new AdministrationModule();
		$option = $am->getCapillaryActiveStoresAsOption();

		$form->addField('select', 'poke_to', 'Poke To', $option);
		$form->addField('textarea', 'msg', 'Poke Message');
		
		if($form->isValidated()){
			
			$params = $form->parse();
			
			$poke_to = $params['poke_to'];
			$msg = $params['msg'];
			
			$id = $this->storeController->updatePokeTable($poke_to, $msg);
			if($id > 0){
				
				Util::redirect('store', 'capillarypoke', false, 'Poked Successfully with message :'.$msg);
			}
			
		}
		
		$this->data['form'] = $form;
	}
	
	//================================== New Api's ===================================//
	
	/**
	 * TODO : nayan will complete this in first half
	 * 
	 * adding and sending back the response
	 */
	public function addIssueApiAction(){
		
$xml_string = <<<EOXML

	<root>
		<customer_feedback>
			<customer_id>9834564</customer_id>
			<mobile>919899296345</mobile>
			<status>OPEN</status>
			<priority>HIGH</priority>
			<department>INTERNAL</department>
			<short_desc>change 3</short_desc>
			<long_desc>fdsfdkgfgfffsddsdsddfdhidff ffdchanged requirement - susi</long_desc>
			<custom_fields_data>
				<custom_data_item>
					<field_name>feedback_check</field_name>
					<field_value>["abcd"]</field_value>
				</custom_data_item>
			</custom_fields_data>
		</customer_feedback>
	</root>   
EOXML;
		
		try{
			global $url_version,$logger;
			$url_version = '1.0.0.1';
			$logger->debug('Start Add Issue Api For Customer Feedback');
			$xml_string = $this->getRawInput();
			if(Util::checkIfXMLisMalformed($xml_string)){
				$api_status = array(
						'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
						'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
				);
				$this->data['api_status'] = $api_status;
				return;
			}
			$element = Xml::parse( $xml_string );
			$elems = $element->xpath( '/root/customer_feedback' );	
			
			$response = array();

			//include the file
			include_once 'apiController/ApiCcmsController.php';
			$config_manager = new ConfigManager( );
			$C_ccms_controller = new ApiCcmsController( );
			$C_admin_user = new AdminUserController( );
			$C_org_controller = new ApiOrganizationController( );
			
			foreach( $elems as $e ){

				$assigned_to = $this->currentorg->getConfigurationValue(SELECT_DEFAULT_STORE_FOR_FEEDBACK, true);

				//check if hierarchical assignment is present
				$is_hierarchical_assingment_enabled =
					$config_manager->getKey( CCMS_IS_HIERARCHICAL_ASSIGNMENT_ENABLED );
					
				if( $is_hierarchical_assingment_enabled ){

					$hierarchical_assingment_entity_type =
						$config_manager->getKey( CCMS_HIERARCHICAL_ENTITY_TYPE );
						
					$manager_id = false;
					switch ( $hierarchical_assingment_entity_type ){
						
						case 'ZONE' :
							
							$hash_details = 
								$C_org_controller->StoreTillController->
									load( $this->currentuser->user_id );
							$store_id = $hash_details['store_id'];
							
							$zones = 
								$C_org_controller->StoreController->getParentZone( $store_id );
							
							if( count( $zones ) > 0 ){

								$zone_id = $zones[0];
								$zone_managers =
									$C_org_controller->ZoneController->getManagers( $zone_id );
								
								$manager_id = $zone_managers[0]['id'];
							}
							
							break;
							
						case 'CONCEPT' :
											
							$hash_details = 
								$C_org_controller->StoreTillController->
									load( $this->currentuser->user_id );
							$store_id = $hash_details['store_id'];
							 
							$concepts =
								$C_org_controller->StoreController->getParentConcept( $store_id );
								
							if( count( $concepts ) > 0 ){
								
								$concept_id = $concepts[0];
								$concept_managers =
									$C_org_controller->ConceptController->getManagers( $concept_id );
								
								$manager_id = $concept_managers[0]['id'];

							}
					}
										
					if( $manager_id ) $assigned_to = $manager_id;
				}
				
				$logger->debug('Assigned To:'.$assigned_to);				
				if( !empty($e->customer_id) && $e->customer_id != 'CUSTOMER_ID_NA' 
						&& $e->customer_id != -1)
					$customer_id = $e->customer_id;
				else{
					$customer = UserProfile::getByMobile( $e->mobile );
					if(!$customer)
						throw new Exception("Customer Is Not Registered With Us");
					$customer_id = $customer->getUserId();
				}
				
				$params = 
					array( 
						'status' => (string)$e->status , 
						'priority' => (string)$e->priority , 
						'reported_by' => 'CLIENT' ,
						'department' => (string)$e->department , 
						'issue_code' => (string)$e->short_desc , 
						'issue_name' => (string)$e->long_desc,
						'assigned_to' => $assigned_to , 
						'customer_id' => $customer_id 
					);
								
				//store the custom field information for the tickets
				$custom_fields = array();
					
				foreach( $e->xpath('custom_fields_data/custom_data_item') as $cfd ){
	
					$cf_name = (string) $cfd->field_name;
					$cf_value_json = (string) $cfd->field_value;
					$custom_fields['custom_field__'.$cf_name] = json_decode( $cf_value_json , true ); 						
				}
				
				$params = array_merge( $params , $custom_fields );
				// Call add issue method by passing params 
				$issue_id = $C_ccms_controller->add( $params );
				$logger->debug('Ticket Code: '.$issue_id);
				$filter = " AND `it`.`id` = '$issue_id' ";
				
				include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
				$issues = ApiIssueTrackerModelExtension::getAllIssues( $filter, $this->currentorg->org_id );
				$logger->debug('Issue Details :'.print_r($issues,true));
				$html_code = $this->getIssueByTicketCodeApiAction( $issues[0]['ticket_code'] , true );
				$logger->debug('Html Code :'.$html_code);
				$issue_response = array( 'ticket_code' => $issues[0]['ticket_code'] ,
										 'html_code' => $html_code );
				
				$item_status = array(
					
					'key' => 'ERR_FEEDBACK_SUCCESS',
					'message' => 'Operation Successful'
				);
					
				$issue_response['item_status'] = $item_status ;
	
				array_push( $response, $issue_response );
				
				$url_version = '1.0.0.0';
			}
		}catch( Exception $e ){
				
				array_push( $response, 
						array(
								'ticket_code' => -1,
								'item_status' =>  array(
									
										'key' => 'ERR_FEEDBACK_UNSUCCESSFULL',
										'message' => $e->getMessage()
								)	
						)
				); 
		}
			
		$this->data['feedback'] = $response;
	}
	
	/**
	 * get The issue display from smarty 
	 * 
	 * @param unknown_type $ticket_code
	 */
	public function getIssueByTicketCodeApiAction( $ticket_code , $status = false ){
		
		try{
			
			//if does get the details 
			global $url_version;
			
			//to load the cheetah configs
			$url_version = '1.0.0.1';
			include_once 'apiController/ApiCcmsController.php';
			include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
			
			$ccms_controller = new ApiCcmsController();
	
			ApiIssueTrackerModelExtension::$static_database = new Dbase( 'stores' );
			$ticket_exists = 
			ApiIssueTrackerModelExtension::isTicketCodeExists( $ticket_code, $this->currentorg->org_id );

			//check for ticket code existance
			if( !$ticket_exists ) throw new Exception( 'Ticket Code Does Not Exists' );
			$default_values = $ccms_controller->getLabelConfigs();
				
			$smarty_variable = $ccms_controller->getCurrentStatusForAllTickets( false, false, $ticket_code );
			$this->logger->debug('Before Return Html : '.print_r($smarty_variable,true));
			//Ugly Fix...
			//This has been done because smarty has its own autoload function
			//which disables the actual autoload.
			$xml = new Xml();
			$xml->serialize();
			require_once 'smarty/Smarty.class.php';
			
			
			$str = html_entity_decode(stripslashes( $default_values['smarty_tags'] ));

			$smarty = new Smarty;
			$smarty->assign( $smarty_variable[0] );

			$head_str = "<style type = 'text/css'>";
			$head_str .= $default_values['smarty_css'];
			$head_str .= "</style>";		
			
			$body_str = '<body>'; 
			$body_str .= $smarty->fetch( 'string:'.$str );
			$body_str .= '</body>';
			
			$html_str = '<html><head>' . $head_str .'</head>'. $body_str . '</html>'; 
			
			if( $status )
				return $html_str;
				
			$this->data['feedback'] = 
			array(
				'ticket_code_exists' => true,
				'response' => $html_str
			);

			//to load the cheetah configs
			$url_version = '1.0.0.0';
			
		}catch( Exception $e ){
			
			$this->data['feedback'] = 
			array(
				'ticket_code_exists' => false,
				'response' => $e->getMessage()
			);
		}
	}
	
	/**
	 * Get Customer Issues
	 */
	public function getIssueApiAction(){
		
		$xml_string = <<<EOXML
								<root>
									<feedback>
										<customer>
											<user_id>18841261</user_id>
											<mobile>[ NA | mobile_number ]</mobile>
											<email>[ NA | email_id ]</email>
											<external_id>[ NA | external_id ]</external_id>
										</customer>
									</feedback>
								</root>  
EOXML;

		$response = array();
		try{

			$xml_string = $this->getRawInput();
			if(Util::checkIfXMLisMalformed($xml_string)){
				$api_status = array(
						'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
						'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
				);
				$this->data['api_status'] = $api_status;
				return;
			}
			$element = Xml::parse( $xml_string );
			
			$elems = $element->xpath( '/root/feedback/customer' );
			$customer = $elems[0];	
			
			$email = $customer->email;			
			$mobile = $customer->mobile;
			$customer_id = $customer->user_id;			
			$external_id = $customer->external_id;

			if( $customer_id != 'NA' && $customer_id != -1 ){
				
				$user = UserProfile::getById( $customer_id );
			}elseif( $mobile != 'NA' ){
				
				$user = UserProfile::getByMobile( $mobile );
			}elseif( $email != 'NA' ){
				
				$user = UserProfile::getByEmail( $email ); 				
			}elseif( $external_id != 'NA' ){
				
				$user = UserProfile::getByExternalId( $external_id );
			}
			
			if( !$user )
				throw new Exception( 'Customer Is Not Registered With Us.' );
				
			$customer_id = $user->user_id;
			
			$filter = " AND `customer_id` = '$customer_id' ";

			//include the file
			include_once 'apiModel/class.ApiIssueTrackerModelExtension.php';
			
			$issues =
			ApiIssueTrackerModelExtension::getAllIssues( $filter, $this->currentorg->org_id );
			
			if( count( $issues ) < 1 )
				throw new Exception( 'No Issue Was Registered For The Customer.' );
				
			foreach( $issues as $issue ){
				
				$issue_response = 
				array(
						'status' => $issue['status'],
						'priority' => $issue['priority'],
						'department' => $issue['department'],
						'issue_code' => $issue['issue_code'],
						'issue_name' => $issue['issue_name'],
						'ticket_code' => $issue['ticket_code']
				);

				$item_status = array(
				
					'key' => 'ERR_FEEDBACK_SUCCESS',
					'message' => 'Operation Successful'
				);
				
				$issue_response['item_status'] = $item_status ;

				array_push( $response, $issue_response );
			}
		}catch( Exception $e ){
			
			$response = 
			array(
					'ticket_code' => -1,
					'item_status' =>  array(
						
							'key' => 'ERR_FEEDBACK_UNSUCCESSFULL',
							'message' => $e->getMessage()
					)	
			); 
		}
		
		$this->data['feedback'] = $response;
	}
	
	function makeIssueInactiveAction( $issue_id, $status, $inactive_status = 0 ){
		
		$form = new Form('update_issue','post');
		
		if( $form->isValidated() ){

			$status = $this->storeController->makeIssueInactiveById($issue_id, strtoupper($status), $inactive_status);
			
			if( $status > 0 )
				$this->flash('Issue ID : '.$issue_id.' was inactivated successfully.');
		}
		
		$this->data['update_issue'] = $form;
	}
	
	/**
	 * 
	 * Customer Feedback api for webclient accept feedback from customer for the store as logged in.
	 */
	public function customerFeedbackApiAction(){
	
	$feedback =<<<FXML
		<root>
		  <customer>
			 <mobile>919909090801</mobile>
			 <email>bmg.goswami@gmail.com</email>
			 <external_id>{{external_id}}</external_id>
			 <custom_fields_data>
				<custom_data_item>
					<field_name>feedback</field_name>
					<field_value>["good"]</field_value>
				</custom_data_item>
				<custom_data_item>
					<field_name>val_for_money</field_name>
					<field_value>["normal"]</field_value>
				</custom_data_item>
			</custom_fields_data>
		  </customer>
      </root>   
FXML;
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		$response = array();
		$store_controller = new ApiStoreController();			
		$customer_controller = new ApiCustomerController();
		$custom = new CustomFields();
						
		try{
				
				$element = Xml::parse( $xml_string );
				$elems = $element->xpath( '/root/customer' );
							
				$customer = $elems[0];
				
				$user_id = -1;
				
				$mobile = $customer->mobile;
				$email  = $customer->email;
				$external_id = $customer->external_id;

				if( !empty( $mobile )){
					$user = UserProfile::getByMobile( $mobile );					
				}else if( !empty( $email )){
					$user = UserProfile::getByEmail( $email );
				}else {
					$user = UserProfile::getByExternalId( $external_id );
				}
				if( $user )
					$user_id = $user->getUserId();
				
				$store_id = $this->currentuser->user_id;
				
				$assoc_id = $store_controller->addStoreFeedback( $user_id , $store_id );

				$this->logger->debug('@@@ASSOC_ID'.$assoc_id );
				
				$custom_fields = array();
				array_push( $custom_fields ,$element->xpath('/root/customer/custom_fields_data/custom_data_item' ) );
				
				$cfd_value = array();
				foreach ( $custom_fields[0] as $row  ){
					array_push( $cfd_value, (array)$row );
				}				

				$this->logger->debug('@@@CFD'.print_r( $cfd_value , true ));
				
				$custom->addCustomFieldDataForAssocId( $assoc_id , $cfd_value );

				$response = array( 'store_id' => $store_id, 
								   'item_staus' => array( 'status' => 'SUCCESS',
														  'message' => 'Feedback Added Successfully' 
														)
									);
		}catch( Exception $e ){
			$response = 
			array(
					'store_id' => $store_id,
					'item_status' =>  array(
						
							'key' => 'FAIL',
							'message' => $e->getMessage()
					)	
			); 
		}
		$this->data['feedback'] = $response;
	}
	
	/**
	 * 
	 * Returns the feedback for the current stores.
	 * @param unknown_type $store_id
	 */
	public function getFeedbackApiAction( $store_id ){
		
		$store_controller = new ApiStoreController();
		
			if( $store_id ){
				
				$store_id = $this->currentuser->user_id;
				
				$result = $store_controller->getStoreFeedbackCount( $store_id , 'TODAY' );
				$response['total_feedback_today'] = $result['no_of_feedback'];
	
				$result = $store_controller->getStoreFeedbackCount( $store_id , 'WEEKLY' );
				$response['total_feedback_this_week'] = $result['no_of_feedback'];
				
				$result = $store_controller->getStoreFeedbackCount( $store_id , 'MONTH' );
				$response['total_feedback_this_month'] = $result['no_of_feedback'];
				
				if( !$result )
					$status = 'FAIL';
	
				$response = array(
									'store_id' => $store_id,
									'success' => true,
									'message' => 'Feedback Retrieved Successfully',
									'feedback' => $response
								);
			}else {			
				
				$response = array(
									'store_id' => $store_id,
									'success' => false,
									'message' => $e->getMessage(),
									'feedback' => 'No Feedback Retrieved!'
									
				);
			}
			
		$this->logger->debug('@@@FEEDBACK RESPONSE'.print_r( $response , true ));
		$this->data['feedback_response'] = $response;
				
	}		
	
}
?>
