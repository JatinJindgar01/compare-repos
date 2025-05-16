<?php

require_once "resource.php";

/**
 * Handles all Feedback related api calls. 
 * @author pigol
 */

$GLOBALS['listener'] = array();


class FeedbackResource extends BaseResource{
	
	private $config_mgr;
	//private $listener_mgr;
	private $storeController;
	private $storeModule;
	
	function __construct()
	{
		parent::__construct();
		$this->config_mgr = new ConfigManager($this->currentorg->org_id);
	//	$this->listener_mgr = new ListenersMgr();	
		$this->storeModule = new StoreModule();
	}
	
	
	public function process($version, $method, $data, $query_params, $http_method)
	{
		if(!$this->checkVersion($version))
		{
			$this->logger->error("Unsupported Version : $version");
			$e = new UnsupportedVersionException(ErrorMessage::$api['UNSUPPORTED_VERSION'], ErrorCodes::$api['UNSUPPORTED_VERSION']);
			throw $e;		
		}
		
		if(!$this->checkMethod($method)){
			$this->logger->error("Unsupported Method: $method");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'], ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}
			
		$result = array();
		try{
			switch(strtolower($method)){
				
				case 'add' : 
					
					$result = $this->add($data, $query_params); 
					break;	
		
				case 'get' :

					$result = $this->get($query_params);
					break;
					
				case 'update' : 

					$result = $this->update($data, $query_params);
					
			
				default :
					$this->logger->error("Should not be reaching here");
					
			}
		}catch(Exception $e){ //We will be catching a hell lot of exceptions as this stage
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode() 
								 . " Message: " . $e->getMessage()
								);
			throw $e;	
		}	
			
		return $result;
	}
	
	/**
	 * Adds a new feedback in the system
	 * 
	 * @param $data
	 * @param $query_params
	 */
	
	private function add($data)
	{

		global $loyalty_error_responses;	
		global $loyalty_error_keys;
		
		
		$this->logger->debug( "Adding new feedback in the system: " . print_r( $data , true ) );

		$org_id = $this->currentorg->org_id;
		$customers = $data[ 'root' ][ 'feedback' ];
		 
		foreach( $customers as $c )
		{
				$issue = new IssueTracker( $org_id , false );

				if( $c[ 'customer' ][ 'mobile' ] )
					$customer=UserProfile::getByMobile( $c[ 'customer' ][ 'mobile' ] );
				
				if( !$customer && $c[ 'customer' ][ 'external_id' ]  )
					$customer = UserProfile::getByExternalId( $c[ 'customer' ][ 'external_id' ] );

				if( !$customer && $c[ 'customer' ][ 'email' ] )
					$customer = UserProfile::getByEmail( $c[ 'customer' ][ 'email' ] );
					
					
				if( !$customer )
				{
					$item_status = array( 'key' => $loyalty_error_keys[ ERR_LOYALTY_USER_NOT_REGISTERED ],
											  'message' => $loyalty_error_responses[ ERR_LOYALTY_USER_NOT_REGISTERED ]);

					$this->logger->debug( 'User not registered for the identifier' );
					
				}
				else // Customer is loyalty registered
				{
					$issue->setCustomerId( $customer->getUserId() );
					$issue->setPriority( $c[ 'priority' ] );
					$issue->setCreatedDate( date( 'Y-m-d' ) );
					$issue->setAssignedBy( $this->storeModule->currentuser->user_id );
					$issue->setReportedBy( 'CLIENT' );
					$issue->setDepartment( $c[ 'department' ] );
					$issue->setIssueCode( $c[ 'title' ] );
					$issue->setIssueName( $c[ 'description' ] );
					$issue->setAssignedTo( $this->storeModule->currentorg->getConfigurationValue( SELECT_DEFAULT_STORE_FOR_FEEDBACK , true ) );
					$issue->setType( 'CUSTOMER' );
					
					$issue_ticket = $this->storeModule->currentorg->getConfigurationValue( CONF_ISSUE_TICKET , true );
				
					if( $issue_ticket )
					{
						$ticket_code_length = $this->storeModule->currentorg->getConfigurationValue( CONF_TICKET_CODE_LENGTH , 8 );
						$alphanumeric = $this->storeModule->currentorg->getConfigurationValue( CONF_TICKET_CODE_IS_ALPHANUMERIC , true );
		
						do
						{
							$ticket_code = Util::generateRandomCode( $ticket_code_length , $alphanumeric );
							$ticket_code = Util::makeReadable( $ticket_code );
						}
						while( $issue->ticketCodeExists( $ticket_code ) );
		
						$issue->setTicketCode( $ticket_code );
					}
					else
						$issue->setTicketCode(-1);
		
					$issue->save();
					
					if( $issue->getId( ) )
					{
						// Successfully got the ticket_id
	
						$ticket_id = $issue->getId(); 		
	
						$item_status = array( 
											 'success'=> true , 
										 	 'code' => getResponseErrorKey( ERR_RESPONSE_SUCCESS ),
											 'message' => getResponseErrorMessage( ERR_RESPONSE_SUCCESS )
   										    );
				
						// Store the custom field information for the tickets
						$cf = new CustomFields();
						$cf_hash = $cf->getCustomFields( $org_id , 'query_hash' , 'name' , 'id' );

						if( $c[ 'custom_fields' ][ 'field' ][ 'name' ] == NULL )
						{
							foreach($c[ 'custom_fields' ][ 'field' ] as $cfd)
							{
								$assoc_id = $issue->getId();
								$cf_name = (string) $cfd[ 'name' ];
								$cf_value_json = (string) $cfd[ 'value' ];
								$cf_id = $cf_hash[ $cf_name ];
								// Add-Update the custom field value
								if( $cf_hash[ $cf_name ] )
									$cf->createOrUpdateCustomFieldData( $org_id , $assoc_id , $cf_id , $cf_value_json );
							}
						}
					 	else
					 	{
					 		$cfd = $c[ 'custom_fields' ][ 'field' ];
							$assoc_id = $issue->getId();
							$cf_name = (string) $cfd[ 'name' ];
							$cf_value_json = (string) $cfd[ 'value' ];
							$cf_id = $cf_hash[ $cf_name ];
							// Add-Update the custom field value
							if( $cf_hash[ $cf_name ] )
								$cf->createOrUpdateCustomFieldData( $org_id , $assoc_id , $cf_id , $cf_value_json );
					 	}
	
				
					} // Successfully getting ticket-id 
					
					$form = new Form();
					$option = $cf->getCustomFieldsByScope( $org_id , CUSTOMER_CUSTOM_FEEDBACK );
					$ret = $this->storeModule->storeController->signalFeedbackListener(
					$cf, $issue, $form, $option,
					false, $this->storeModule->listenersManager);
					if(!$ret)
						$this->logger->debug(" Listener Failed ...");

				}
				// Populating Response String
				$feedback[ 'item' ] = array();
				array_push( $feedback[ 'item' ] , array( 
														'ticket_id' => $ticket_id ,
														'item_status' => $item_status , 
														'side_effect' => array( 'effect' => $GLOBALS[ 'listener' ] ) )
												  	   );
					
		} // End of foreach Customer		
	
		$result = array(
						'status' =>  array( 'success' => 'true' ,'code' => '200',
						'message' => 'Operation Successful' ),
						'response' => $feedback
			   		   );
	
		return $result;
	
	
	}
	
	
	/**
	 * Fetches the details of a feedback
	 * 
	 * @param $query_params
	 */
	
	private function get($query_params)
	{
		$db = new Dbase( 'users' );
		
		$org_id = $this->currentorg->org_id;
		$ticket_array = explode( ',' , $query_params[ 'ticket_id' ] );		
	
		$feedback[ 'item' ] = array();
		
		foreach ( $ticket_array as $key => $ticket_id ) 
		{
			$sql = "SELECT it.`id`, it.`org_id`, it.`status`, it.`priority`, it.`department`,
						   CONCAT(u2.firstname,' ',u2.lastname) as `assigned_to`, it.`issue_code`, 
						   it.`issue_name`, CONCAT(u5.firstname,' ',u5.lastname) as `customer_id`, it.`ticket_code`, 
						   CONCAT(u1.firstname,' ',u1.lastname) as `assigned_by`, it.`due_date`, it.`created_date`,
						   CONCAT(u3.firstname,' ',u3.lastname) as`reported_by`, it.`type`, 
						   CONCAT(u4.firstname,' ',u4.lastname) as `resolved_by`, it.`last_updated`
					FROM `store_management`.`issue_tracker` it 
					LEFT OUTER JOIN user_management.users u1 ON u1.id = it.assigned_by
					LEFT OUTER JOIN user_management.users u2 ON u2.id = it.`assigned_to`
					LEFT OUTER JOIN user_management.users u3 ON u3.id = it.`reported_by`
					LEFT OUTER JOIN user_management.users u4 ON u4.id = it.`resolved_by`
					LEFT OUTER JOIN user_management.users u5 ON u5.id = it.`customer_id`
					WHERE it.`id`=$ticket_id";
			
			$result = $db->query( $sql );

			if( $result )
			{
				$success = 'true';
				$code = '200';
				$message = 'Feedback Details Retrieved';
			}
			else
			{
				$success = 'false';
				$code = 'FAIL';
				$message = "Invalid ticket id : $ticket_id ";
			}
			
			$item_status = array( 
								 'success'=> $success , 
							 	 'code' => $code,
								 'message' => $message
							     );
			
			$item = $result[0];
			$item[ 'item_status' ] = $item_status;
			
			$sql = "SELECT `status`, `assigned_by`, `last_updated`
					FROM `store_management`.`issue_tracker_log` 
					WHERE `tracker_id`=$ticket_id AND `org_id` = $org_id ORDER BY `id` DESC";
			
			$result = $db->query( $sql );
			$log_result = array();
		
			if( $result )
			{
				foreach( $result as $res )
				{
					array_push( $log_result , $res );
				}
				
			}
			$item[ 'log_entries' ][ 'log' ] = $log_result;
			
			array_push( $feedback[ 'item' ] , $item );
			
		}

		$final_result = array(
							 'status' =>  array( 'success' => true ,'code' => 200 ,
												 'message' => 'Operation Successful' ),
							 'response' => $feedback
			   				 );
	
		return $final_result;
	
	}
	
	/**
	 * Updates a transaction in the system
	 * @param $data
	 * @param $query_params
	 */
	
	private function update($data, $query_params)
	{
		global $loyalty_error_responses;	
		global $loyalty_error_keys;
		
		$db = new Dbase( 'users' );
		
		$this->logger->debug("Updating feedback in the system: " . print_r($data, true));

		$org_id = $this->currentorg->org_id;
		$customers = $data[ 'root' ][ 'feedback' ];
		 
		foreach( $customers as $c )
		{
				if( $c[ 'customer' ][ 'mobile' ] )
					$customer=UserProfile::getByMobile( $c[ 'customer' ][ 'mobile' ] );
				
				if( !$customer && $c[ 'customer' ][ 'external_id' ]  )
					$customer = UserProfile::getByExternalId( $c[ 'customer' ][ 'external_id' ] );

				if( !$customer && $c[ 'customer' ][ 'email' ] )
					$customer = UserProfile::getByEmail( $c[ 'customer' ][ 'email' ] );
					
					
				if( !$customer )
				{
					$item_status = array( 'key' => $loyalty_error_keys[ ERR_LOYALTY_USER_NOT_REGISTERED ],
										  'message' => $loyalty_error_responses[ ERR_LOYALTY_USER_NOT_REGISTERED ]
										);

					$this->logger->debug( 'User not registered for the identifier' );
					
				}
				else // Customer is loyalty registered
				{
					$issue = new IssueTracker( $org_id , $c[ 'id' ] );
					
					$issue->read();

					if( $issue->getId() )
					{
						$item_status = array( 
											 'success'=> true , 
										 	 'code' => getResponseErrorKey( ERR_RESPONSE_SUCCESS ),
											 'message' => getResponseErrorMessage( ERR_RESPONSE_SUCCESS )
	   									    );
						
						$issue->setCustomerId( $customer->getUserId() );
						$issue->setAssignedBy( $this->storeModule->currentuser->user_id );
			
						if( $c[ 'status' ] )
							$issue->setStatus($c[ 'status' ]);
						if( $c[ 'priority' ] )
							$issue->setPriority($c[ 'priority' ]);
						if( $c[ 'department' ] )	
							$issue->setDepartment($c[ 'department' ]);
						if( $c[ 'title' ] )
							$issue->setIssueCode($c[ 'title' ]);
						if( $c[ 'description' ] )
							$issue->setIssueName($c[ 'description' ]);
						
						$issue->save();
					//	$issue->createLogEntries();
						
						// Store the custom field information for the tickets
						$cf = new CustomFields();
						$cf_hash = $cf->getCustomFields( $org_id , 'query_hash' , 'name' , 'id' );
	
						if( $c[ 'custom_fields' ][ 'field' ][ 'name' ] == NULL )
						{
								foreach( $c[ 'custom_fields' ][ 'field' ] as $cfd )
								{
									$assoc_id = $issue->getId();
									$cf_name = (string) $cfd[ 'name' ];
									$cf_value_json = (string) $cfd[ 'value' ];
									$cf_id = $cf_hash[ $cf_name ];
									
									// Add-Update the custom field value
									if( $cf_hash[ $cf_name ] )
										$cf->createOrUpdateCustomFieldData( $org_id , $assoc_id , $cf_id , $cf_value_json );
								}
						}
						else
						{
						 		$cfd = $c[ 'custom_fields' ][ 'field' ];
								$assoc_id = $issue->getId();
								$cf_name = (string) $cfd[ 'name' ];
								$cf_value_json = (string) $cfd[ 'value' ];
								$cf_id = $cf_hash[ $cf_name ];

								// Add-Update the custom field value
								if( $cf_hash[ $cf_name ] )
									$cf->createOrUpdateCustomFieldData( $org_id , $assoc_id , $cf_id , $cf_value_json );
						}

						$form = new Form();
						$option = $cf->getCustomFieldsByScope( $org_id , CUSTOMER_CUSTOM_FEEDBACK );
						$ret = $this->storeModule->storeController->signalFeedbackListener(	$cf, $issue, $form, $option,
																							false, $this->storeModule->listenersManager
																						  );
						if(!$ret)
							$this->logger->debug(" Listener Failed ...");
					}  // End of if conditional for id
					else // Could not find a corresponding ticket for the id
					{
						$item_status = array( 
											 'success'=> false , 
										 	 'code' => 'ERR_INVALID_TICKET_ID',
											 'message' => 'Ticket ID is not valid'
	   									    );
						
					}
				}
				// Populating response array
				$feedback[ 'item' ] = array();
				array_push( $feedback[ 'item' ] , array( 
														'ticket_id' => $c[ 'id' ] ,
														'item_status' => $item_status , 
														'side_effect' => array( 'effect' => $GLOBALS[ 'listener' ] ) )
												  	   );
					
		} // End of foreach customer		
	
		$result = array(
						'status' =>  array( 'success' => 'true' ,'code' => '200',
											'message' => 'Operation Successful' ),
						'response' => $feedback
			   		   );
	
		return $result;
	}
	
	
	
	/**
	 * Checks if the system supports the version passed as input
	 * 
	 * @param $version
	 */
	
	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1'))){
			return true;
		}	
		return false;
	}
	
	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('add', 'update', 'get'))){
			return true;
		}
		return false;
	}
	
}
