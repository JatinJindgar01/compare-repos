<?php 
//TODO: referes to cheetah
include_once 'base_model/class.OrgRole.php';
//TODO: referes to cheetah
include_once 'model_extension/class.CustomerModelExtension.php';
include_once 'apiController/ApiBaseController.php';
include_once 'apiController/ApiEntityController.php';
include_once 'apiController/ApiStoreTillController.php';
include_once 'apiController/ApiOrganizationController.php';
include_once 'apiController/ApiZoneController.php';
include_once 'apiController/ApiConceptController.php';
include_once 'apiController/ApiStoreServerController.php';
include_once 'apiController/ApiSegmentationEngineController.php';
include_once "apiHelper/Errors.php";
include_once "controller/ApiLoyalty.php";
//TODO: referes to cheetah
include_once "model/loyalty.php";
include_once "apiController/ApiStoreController.php";
//TODO: referes to api folder
include_once "module/loyalty.php";
include_once 'business_controller/loyalty/merge/impl/MergeCustomerHandler.php';
include_once "apiController/ApiEMFServiceController.php";
include_once "apiController/ApiPointsEngineServiceController.php";
include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
include_once 'models/inventory/InventoryAttributeValue.php';

$GLOBALS['listener'] = array();

/**
 * @author Suryajith
 */
class ApiCustomerController extends ApiBaseController{
	
	public  $customer_model;
	private $StoreController;
	private $OrgController;
	private $C_config_manager;
	
	var $loyaltyController;
	var $lm;
	var $db;
	private $cm;
	private $mlm;
	private $loyaltyModule;
	private $C_merge_customer_handler;
	
	public function __construct(){
		
		parent::__construct();
		
		$this->customer_model = new CustomerModelExtension();
	
		$this->StoreController = new ApiStoreController();
		$this->OrgController = new ApiOrganizationController();
		$this->C_config_manager = new ConfigManager();
		
		
		$this->loyaltyModule = new LoyaltyModule();
		$this->lm = new ListenersMgr( $this->org_model );
		//$this->cm = new CustomermgmtModule();
		//$this->mlm = new MLMSubModule( $this->loyaltyModule );
		$this->loyaltyController = new LoyaltyController( $this->loyaltyModule );
		$this->db = new Dbase('users');
	
		$this->C_merge_customer_handler = new MergeCustomerHandler();
	}
	
	public function getCustomerDataById($user_id){
		$user_data = $this->customer_model->getUserData($user_id);
		
		return $user_data;
	}
			

/*
 *Populate the items for UpdateApiAction() including 
 * - Extended User Profile data
 * - Custom Field values
 * It also sets the item status for retrieved customer. 
 */ 
function populateCustomerDetails( $user , $register_status, $user_id = false )
{
			//Pushing Extended user profile data 
			$eup =  new ExtendedUserProfile( $user , $this->org_model );
			$eup->read();

			if ( $user_id ) {
				$item_array['user_id'] = (string) $eup->getUserID();
			}

			$item_array[ 'mobile' ] = (string) $eup->getMobile() ;
			$item_array[ 'email' ] = (string) $eup->getEmail() ;
			$item_array[ 'firstname' ] = (string) $eup->getFirstname() ;
			$item_array[ 'lastname' ] = (string) $eup->getLastname() ;
			$item_array[ 'sex' ] = (string) $eup->getSex() ;
			$item_array[ 'birthday' ] = (string) $eup->getBirthday() ;
			$item_array[ 'address' ] = (string) $eup->getAddress() ;
	
			//Pushing External ID of user 
			$loyalty_id = $this->loyaltyController->getLoyaltyId( $this->loyaltyModule->currentorg->org_id , $user->user_id );
			$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForLoyaltyID( $loyalty_id );
			$item_array[ 'external_id' ] = (string) $loyalty_details[ 'external_id' ] ;
	
			//Pushing Custom Field values for customer
			$cf = new CustomFields();
			$assoc_id = $user->user_id;
			$custom_fields_user = $cf->getCustomFieldValuesByAssocId( $this->org_id , 'loyalty_registration' , $assoc_id );
		
			if( $custom_fields_user )
			{
				$field_array = array();
				$item_array[ 'custom_fields' ]['field'] = array();
				
				foreach( $custom_fields_user as $key => $value )
				{
					$field_array[ 'field' ][ 'name' ] = $key;
					$decoded_value = json_decode( $value ) ;
					if($decoded_value === null)
						$field_array[ 'field' ][ 'value' ] = $value;
					else if(is_array($decoded_value)
							&& count($decoded_value) > 0 && $decoded_value[0] === null)
						$field_array[ 'field' ][ 'value' ] = 'null';
					else 
						$field_array[ 'field' ][ 'value' ] = 
							is_array($decoded_value) ? implode(",", $decoded_value) : $value;
					array_push( $item_array[ 'custom_fields' ]['field'] , $field_array[ 'field' ] );
				}
			}

			//Pushing item status for customer
			$item_array[ 'item_status' ] =  $register_status;
			
			return $item_array;
}


/*
 * Get API call with scope='app'
 * 	- Retrieves extended user profile fields for provided user mobile.
 */
public function getUserDetailsForApp( $mobile )
{
		$sql = "SELECT * FROM app_profile WHERE mobile = '$mobile'";
		$db = new Dbase('users'); 
		
		$result = array();
		$user_details = $db->query_firstrow($sql);
		
		if(isset($user_details['mobile']))
		{
			$mobile = $user_details[ 'mobile' ] ;
			$first_name = $user_details[ 'first_name' ];
			$last_name = $user_details[ 'last_name' ];
			$email = $user_details[ 'email' ];
			$address = $user_details[ 'address' ];
			$gender = $user_details[ 'gender' ];
			$birthday = strtotime($user_details[ 'birthday' ]);
			$age_group = $user_details[ 'age_group' ];
			$anniversary = strtotime($user_details[ 'anniversary' ]);
			$spouse_birthday = strtotime($user_details[ 'spouse_birthday' ]);
			$mobile_device = $user_details['mobile_device'];
			$latitude = $user_details['latitude'];
			$longitude = $user_details['longitude'];
			$device_token = $user_details['device_token'];

			$this->logger->debug("Pushing extended user profile details for user_id :".$user_details[ 'user_id' ]   );
			$result[ 'user' ]  = array(
   											 'mobile'=>$mobile , 
   											 'first_name'=>$first_name , 
   											 'last_name'=>$last_name , 
   											 'email'=>$email , 
   											 'address'=>$address , 
   											 'gender'=>$gender , 
   											 'birthday'=>$birthday , 
   											 'age_group'=>$age_group , 
   											 'anniversary'=>$anniversary , 
   											 'spouse_birthday'=>$spouse_birthday , 
   											 'mobile_device'=>$mobile_device , 
   											 'latitude'=>$latitude , 
   											 'longitude'=>$longitude, 
											 'device_token' => $device_token
									 );
		}

 	return 	$result;
}


/*
 * Update API call with scope='app'
 * 	- Updates extended user profile fields provided in xml POST to extd_user_profile table for all orgs provided mobile is registered under.
 * 	  Responds to API call with a GET ( scope='app' ) of updated user. 	   
 */
public function updateUserDetailsForApp( $user )
{
		
		$user_details = $user[ 'root' ][ 'user' ][ 0 ]; 
		
		$mobile = $user_details[ 'mobile' ] ;
		
		if(!Util::checkMobileNumber($mobile)){
			$this->logger->debug("Invalid mobile number: $mobile");
			return;
		}
		
		$this->logger->debug("updating user details: " . print_r($user_details, true));
		
		$first_name = $user_details[ 'first_name' ];
		$last_name = $user_details[ 'last_name' ];
		$email = $user_details[ 'email' ];
		$address = $user_details[ 'address' ];
		$gender = $user_details[ 'gender' ];
		$user_details['birthday'] = date('Y-m-d H:i:s', intval($user_details[ 'birthday' ]));
		$age_group = $user_details[ 'age_group' ];
		$user_details['anniversary'] = date('Y-m-d H:i:s', intval($user_details[ 'anniversary' ]));
		$user_details['spouse_birthday'] = date('Y-m-d H:i:s', intval($user_details[ 'spouse_birthday' ]));
		$mobile_device = $user_details['mobile_device'];
		$latitude = $user_details['latitude'];
		$longitude = $user_details['longitude'];
		$device_token = $user_details['device_token'];

		
		$user_keys = array('first_name', 'last_name', 'email', 'address', 'gender', 'birthday', 'age_group', 
					  'anniversary', 'spouse_birthday', 'mobile_device', 'latitude', 'longitude', 'device_token');
		
		$db = new Dbase('users');
		$sql = "SELECT id FROM app_profile WHERE mobile = '$mobile'";
		$id = $db->query_scalar($sql);
		if($id > 0){  //update 

			$sql = "UPDATE app_profile SET ";
			foreach($user_keys as $k){
				if(strlen(trim($user_details[$k]))){
					$sql .= $k . " = '" . $user_details[$k] . "',";
				}
			}
			
			$sql = rtrim($sql, ',') . " WHERE id = $id";
			$this->logger->debug("Firing update query $sql");
			$id = $db->update($sql);
			
			if($id > 0){
				return $this->getUserDetailsForApp($mobile);
			}
			
		}else{  //Insert a new user in the app_profile
			
			$sql = "INSERT INTO app_profile(mobile, first_name, last_name, email, address, gender, birthday, age_group, anniversary,
					spouse_birthday, mobile_device, latitude, longitude, device_token) 
					VALUES ('$mobile', '$first_name', '$last_name', '$email', '$address', '$gender', '$birthday', '$age_group', '$anniversary',
							'$spouse_birthday', '$mobile_device', '$latitude', '$longitude', '$device_token') ";
			
			$user_id = $db->insert($sql);
			if($user_id > 0){
				$this->logger->debug("User add with id: $user_id");
				return $this->getUserDetailsForApp($mobile);		
			}
		}
}


public function updateCustomerIdentity($data)
{
	include_once 'apiController/ApiRequestController.php';
	$this->logger->debug("Updating identity of the customer: " . print_r($data, true));	
	
	$customer = $data['root']['customer'][0];
	
	$identifier = $customer['identifier'];
	$old_value = $customer['old_value'];
	$new_value = $customer['new_value'];
	
	$response = array(
			'status' => array(
					'success'=>true,
					'code' => ErrorCodes::$api['SUCCESS'],
					'message' => ErrorMessage::$api['SUCCESS']
			)
	);
/**										
<identifier>mobile|email|external_id</identifier>
<old_value>919980616752</old_value>
<new_value>919980616700</old_value>
<updated>true|false</updated>
<item_status>
<success>true</success>
<code>200</code>
<message>success</message>
</item_status>
**/					
					
	$item_response = array(
							'identifier' => $identifier,
							'old_value' => $old_value,
							'new_value' => $new_value,
							'updated' => 'false',
							'item_status' => array(
									'success' => true,
									'code' => ErrorCodes::$customer['ERR_CUSTOMER_IDENTITY_CHANGE_REQUEST_SUCCESS'], 
									'message' => ErrorMessage::$customer['ERR_CUSTOMER_IDENTITY_CHANGE_REQUEST_SUCCESS'],
									)		
						  );
	
	if(strtolower($identifier) == 'mobile')
	{
		$this->logger->debug("Loading by mobile: $old_value");	
		$user = UserProfile::getByMobile($old_value, true);
		if($user->is_merged)
		{
			$old_value=$user->mobile;
			$item_response['old_value']=$old_value;
		}
 
		if($user && !Util::checkMobileNumberNew($new_value)) //supplied value is incorrect
		{	
			$item_response['item_status']['success'] = 'false';
			$item_response['item_status']['code'] = ErrorCodes::$customer['ERR_LOYALTY_INVALID_MOBILE'];
			$item_response['item_status']['message'] = ErrorMessage::$customer['ERR_LOYALTY_INVALID_MOBILE'];
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];
			$response['customers']['customer'] = $item_response;	

			return $response;
		}

	}else if(strtolower($identifier) == 'email'){
		$this->logger->debug("Loading by email: $old_value");	
		$user = UserProfile::getByEmail($old_value);
		if($user->is_merged)
		{
			$old_value=$user->email;
			$item_response['old_value']=$old_value;
		}
		
		if($user && !Util::checkEmailAddress($new_value)) //supplied value is incorrect
		{	
			$item_response['item_status']['success'] = 'false';
			$item_response['item_status']['code'] = ErrorCodes::$customer['ERR_LOYALTY_INVALID_EMAIL'];
			$item_response['item_status']['message'] = ErrorMessage::$customer['ERR_LOYALTY_INVALID_EMAIL'];
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];
			$response['customers']['customer'] = $item_response;	

			return $response;
		}
	}else if(strtolower($identifier) == 'external_id'){
		$this->logger->debug("Loading external_id: $old_value");
		$user = UserProfile::getByExternalId($old_value);
		if($user->is_merged)
		{
			$old_value=$user->external_id;
			$item_response['old_value']=$old_value;
		}
	}else{
		$this->logger->debug("identifier $identifier is invalid"); //throw error

		$item_response['item_status']['success'] = 'false';
		$item_response['item_status']['code'] = ErrorCodes::$customer['ERR_NO_MOBILE-EXT-EMAIL_ID'];
		$item_response['item_status']['message'] = ErrorMessage::$customer['ERR_NO_MOBILE-EXT-EMAIL_ID'];
		$response['status']['success'] = 'false';
		$response['status']['code'] = ErrorCodes::$api['FAIL'];
		$response['status']['message'] = ErrorMessage::$api['FAIL'];
		$response['customers']['customer'] = $item_response;	
		return $response;
	}
	

	$this->logger->debug("user object: " . $user->user_id);	

	if(!$user){
		$this->logger->debug("User object could not be loaded.. some error");
		$item_response['item_status']['success'] = 'false';
		$item_response['item_status']['code'] = ErrorCodes::$customer['ERR_USER_NOT_REGISTERED'];
		$item_response['item_status']['message'] = ErrorMessage::$customer['ERR_USER_NOT_REGISTERED'];
		$response['status']['success'] = 'false';
		$response['status']['code'] = ErrorCodes::$api['FAIL'];
		$response['status']['message'] = ErrorMessage::$api['FAIL'];
		$response['customers']['customer'] = $item_response;	
		
		return $response;
	}
	
	
	//Placing Customer Identity Change Request in Change Request workflow
	///deprecated by Member Care!
/*	try{
			
		$this->logger->debug( "Placing a $identifier change request for user :".$user->user_id );
		$this->logger->debug( "$identifier change request from $old_value to $new_value" );
		$change_request_workflow = new ChangeRequestWorkflowHandler();
		$status = $change_request_workflow->addRequest( $user->user_id, $new_value, $identifier );
		$this->logger->debug( "Change Request Workflow result: ".print_r( $status, true ) );
		if( $status[0] == 'SUCCESS' && $status[1] == ERR_LOYALTY_SUCCESS ){

			$stat = true;
			$this->logger->debug( "$identifier change request added successfully" );
		}else{

			$stat = false;
			$this->logger->error( "Failed to add $identifier change request" );
			$this->logger->error( "$identifier change request status : ".(
					ErrorMessage::$customer[$status[1]] ? ErrorMessage::$customer[$status[1]] : $status[1] ) );
		}
	}catch( Exception $e ){
			
		$this->logger->error( "Caught Exception while placing $identifier change request in Change Request Workflow");
		$this->logger->error( "Exception :".$e->getMessage() );
		$stat = false;
	}
	*/
	
	$this->logger->info("Adding a change identifier for user : ".$user->user_id.", identifier: $identifier, new value: $new_value, old value: $new_value");
	$req_controller=new ApiRequestController();
	$payload=array(
			'type'=>'CHANGE_IDENTIFIER',
			'base_type'=>strtoupper($identifier),
			'user_id'=>$user->user_id,
			'old_value'=>$old_value,
			'new_value'=>$new_value,
			);
	try{
		$this->logger->info("Trying to add request for change identifier. payload : ".print_r($payload,true));
		$ret=$req_controller->addRequest($payload);
		$stat=true;
		$this->logger->info("request controller returned with success request add: ".print_r($ret,true));
	}catch(Exception $e)
	{
		$this->logger->error("Request added failed. caught exception: $e");
		$stat=false;
	}
	
	
	if($stat){
		$item_response['updated'] = 'true';
		$item_response['item_status']['code'] = ErrorCodes::$customer['ERR_CUSTOMER_IDENTITY_CHANGE_REQUEST_SUCCESS'];
		$item_response['item_status']['message'] = ErrorMessage::$customer['ERR_CUSTOMER_IDENTITY_CHANGE_REQUEST_SUCCESS'];
		$response['status']['code'] = ErrorCodes::$api['SUCCESS'];
		$response['status']['message'] = ErrorMessage::$api['SUCCESS'];
		$response['customers']['customer'] = $item_response;
			
		return $response;
	}else{
			
		$item_status_code = ErrorCodes::$customer[$status[1]] ? $status[1] : 'ERR_CUSTOMER_IDENTITY_CHANGE_REQUEST_FAILURE';
		$item_response['item_status']['success'] = 'false';
		$item_response['item_status']['code'] = ErrorCodes::$customer[$item_status_code];
		$item_response['item_status']['message'] = ErrorMessage::$customer[$item_status_code];
		$response['status']['success'] = 'false';
		$response['status']['code'] = ErrorCodes::$api['FAIL'];
		$response['status']['message'] = ErrorMessage::$api['FAIL'];
		$response['customers']['customer'] = $item_response;
			
		return $response;
	}
}
	
	public function register($customer)
	{
		$referrer_code=isset($customer['referral_code'])?$customer['referral_code']:"";
		$status = true;
		try
		{
			if(isset($customer['type']))
			{
				$customer['type']=strtolower($customer['type']) == 'non_loyalty' ? 'non_loyalty' : 'loyalty' ;
			}
			else{
				$customer['type']='loyalty';
			}
			$this->validateCustomerData($customer);
			//checking input data to configurations
			$cm = new ConfigManager();
			
			$this->logger->debug("CustomerController: Creating User Profile object");
			$user = UserProfile::getByData($customer);
	
			$this->logger->debug("CustomerController: Registering user");
			$hasSubscriptions = ($customer["subscriptions"] && $customer["subscriptions"]["subscription"]) ? true : false;
			if($user->loyalty_type=="non_loyalty"){
				$user->registerNonLoyalty($hasSubscriptions ? false :true);
			}
			else {
				$user->register($hasSubscriptions ? false : true);
			}
			//TODO: check which customer gets loaded in case of 
			//already existing mobile and new external id
			$user->load(true);
			//$this->logger->debug("CustomerController: Saving custom fields");
			
			//$user->saveCustomFields();
			$data['user'] = $user;
			$data['org_id'] = $user->org_id;
			$data['date'] = $user->registered_on;

            $registrationEventAttributes = array();
            $registrationEventAttributes["subtype"] = strtoupper($user->loyalty_type);
            $registrationEventAttributes["customerId"] = intval($user->getUserId());
            $registrationEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $registrationTime = strtotime($user->registered_on);
            EventIngestionHelper::ingestEventAsynchronously(intval($user->org_id), "registration",
                "Registration event from the Intouch PHP API's", $registrationTime, $registrationEventAttributes);

			if($hasSubscriptions)
			{
				require_once "business_controller/UserSubscriptionController.php";
				$subscriptions = array();
				if(!$customer["subscriptions"]["subscription"][0])
					$customer["subscriptions"]["subscription"] = array($customer["subscriptions"]["subscription"]);
				
				else 
				{
					// check if trans and Bulk has same status
				}
				foreach ($customer["subscriptions"]["subscription"] as $subscription)
				{
					try {
						$sub_det = $this->saveCustomerSubscriptions($user, $subscription);
					}catch(Exception $e){
						$msg = "Failed to ". ($subscription["is_subscribed"] ==1 ? "subscribe " : "unsubscribe ") .
						$subscription["priority"] . " " . $subscription["channel"]. " due to ";
						if(is_object($e) && $e->getCode())
							Util::addApiWarning($msg .$e->getMessage());
						else
							Util::addApiWarning($msg. ErrorMessage::$customer[ $e->getMessage()]);
					}
				}
			}
			
			//saving Associate Activity.
			if(isset($customer['associate_details']))
			{
				$this->logger->debug("Associate_details: ".print_r($customer['associate_details'], true));
				$this->saveAssociateActivity($customer['associate_details'], $user, ASSOCIATE_ACTIVITY_CUSTOMER_REGISTRATION);
			}
			//updating memcache entry for Organization statistics
			Util::increaseNumberOfCustomersInMemcache($this->org_id);
			
			//$emf = new EventFrameworkCommunicator($data,true);
			//$emf->execute();
			
			$regDate = Util::getMysqlDateTime($user->registered_on);
			$timestamp = strtotime($regDate);
			
			
			if ($customer['current_points'] > 0 && strtolower($customer['current_points_override']) == 'true') {				
				$this->loyaltyController->updateCurrentPoints($user->loyalty_id, (integer) $customer['current_points']);
			}
			
			try {
			
				if(Util::canCallPointsEngine())
				{
					$this->logger->debug("CustomerController: Calling points engine");
					$this->callPointsEngineForUser($user, $timestamp, $referrer_code);
					
				}
			}catch(Exception $e)
			{
				//TODO: check once.
				Util::addApiWarning(ErrorMessage::$customer[$e->getMessage()]);
			}

			//Calling Listenrs.
			$listener_param = array("user_id" => $user->getUserId() );
			$this->logger->debug("LoyaltyRegistrationEvent Start");
			
			$cf = new CustomFields();
			$custom_fields_data = $cf->getCustomFieldValuesByAssocId(
					$this->org_id, LOYALTY_CUSTOM_REGISTRATION, $user->user_id);
			foreach($custom_fields_data AS $name => $value){
			
				$temp_value = json_decode($value, true);
				$temp_value = $temp_value !== NULL ? $temp_value : $value;
				$cfNameUgly = Util::uglify($cf->getFieldName($name));
					
				$listener_param[$cfNameUgly] = is_array($temp_value) && count($temp_value) > 0 ? 
											$temp_value[0] : $value ;
			}
			
			$this->executeLoyaltyRegisterEvents($listener_param);
			$this->logger->debug("LoyaltyRegistrationEvent End");

			// fetch the loyalty details
			try {
				$user->loadLoyaltyFields();
			} catch (Exception $e) {
			}
			ApiLoyaltyTrackerModelExtension::incrementStoreCounterForDay("customer", $this->org_id, $this->user_id);
			
		}catch(Exception $e)
		{
			$error_key = $user->status_code;
			throw new Exception($e->getMessage(), $e->getCode());
		}

		//register user in test and control segment
		$this->logger->debug( "Registering User for Test and Control Segment" );
		if($user->loyalty_type == 'loyalty') {
			$this->createUserTestControlSegmentMapping($user->org_id, $user->user_id);
		}
		
        //add user to solr index
        $this->pushCustomerToSolr($user);
        
		return $user;
	}
	
	private function validateCustomerData( $data, $user = null)
	{
		$registered_on = $data['registered_on'] ? Util::deserializeFrom8601($data['registered_on']) : time();
		if($registered_on > (time() + SECONDS_OF_A_DAY )) //24 hours
		{
			$this->logger->debug($registered_on ." Date is not Alowed(More than registration boundry)");
			throw new Exception("ERR_NO_REGISTRATION_DATE_NOT_IN_BOUNDRY");
		}
			
		$cm = new ConfigManager();
		try
		{
			$min_date = $cm->getKey("CONF_MIN_REGISTRATION_DATE");
		}
		catch(Exception $e)
		{
			$this->logger->debug("can't find CONF_MIN_REGISTRATION_DATE config");
		}
		//if min registration date config not found, it will Deserialize the default date ("1995-01-01 00:00:00UTC")
		$min_date = $min_date? Util::deserializeFrom8601($min_date): Util::deserializeFrom8601("1995-01-01 00:00:00UTC");

		//validating customer's update date with registration date
		if($user != null)
		{
			$this->logger->debug("minimum date for update is $user->registered_on");
			$min_date = Util::deserializeFrom8601($user->registered_on);
			$this->logger->debug("minimum timestamp for update is $min_date");
		}
		
		if($registered_on < $min_date )
		{
			$this->logger->debug("$this->registered_on Date is not Alowed (Less than date boundry)");
			throw new Exception("ERR_NO_REGISTRATION_DATE_NOT_IN_BOUNDRY");
		}
	}

	public function validateNonLoyaltyCustomer($customer)
	{
		if(isset($customer['mobile']) && !empty($customer['mobile']))
		{
			if(!Util::checkMobileNumberNew($customer['mobile'], array(), false)){
				$this->logger->error("Mobile is not valid");
				Util::addApiWarning("Mobile is not valid , ignoring it");
				$customer['mobile'] = '';
			}
			if(!Util::isMobileNumberValid($customer['mobile'])) {
				$this->logger->error("Mobile is not valid");
				Util::addApiWarning("Mobile is not valid , ignoring it");
				$customer['mobile'] = '';
			}
		}
		if(isset($customer['email']) && !empty($customer['email'])
			&& !Util::checkEmailAddress($customer['email']))
		{
			Util::addApiWarning("Email is not valid , ignoring it");
			$customer['email'] = '';
		}
		if(empty($customer['mobile']) && empty($customer['email']))
		{
			$this->logger->debug("No Valid mobile or email passed for non loyalty");
			throw new Exception('ERR_NO_MOBILE_EMAIL_NON_LOYALTY');
		}
		return $customer;
	}
	
	public function validateInputIdentifiers($customer)
	{
		$primary_key = $this->C_config_manager->getKey('CONF_REGISTRATION_PRIMARY_KEY');
		if(isset($customer['mobile']) && !empty($customer['mobile']))
		{
			if(!Util::shouldBypassMobileValidation()
					&& !Util::checkMobileNumberNew($customer['mobile'], array(), false))
			{
				$this->logger->error("Mobile is not valid");
				if($primary_key === REGISTRATION_IDENTIFIER_MOBILE)
					throw new Exception('ERR_LOYALTY_INVALID_MOBILE');
				else
					Util::addApiWarning("Mobile is invalid and primary key is not mobile, ignoring it");
				$customer['mobile'] = '';
			}
			if(!empty($customer['mobile']) && !Util::isMobileNumberValid($customer['mobile']))
			{
				$this->logger->error("Mobile number[".
						$customer['mobile']."] is not valid");
				if($primary_key === REGISTRATION_IDENTIFIER_MOBILE)
					throw new Exception('ERR_LOYALTY_INVALID_MOBILE');
				else
					Util::addApiWarning("Mobile is not valid and mobile is not primary key, ignoring it");
				$customer['mobile'] = '';
			}
		}
		if(isset($customer['email']) && !empty($customer['email']) 
				&& !Util::checkEmailAddress($customer['email']))
		{
			$this->logger->error("Email is not valid");
			if($primary_key === REGISTRATION_IDENTIFIER_EMAIL )
				throw new Exception('ERR_LOYALTY_INVALID_EMAIL');
			else
				Util::addApiWarning("Email is not valid and email is not primary key, ignoring it");
			$customer['email'] = '';
		}
		if(isset($customer['external_id']) && !empty($customer['external_id']))
		{
			$min_length=$this->C_config_manager->getKey('CONF_CLIENT_EXTERNAL_ID_MIN_LENGTH');
			$max_length=$this->C_config_manager->getKey('CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH');
			if(strlen($customer['external_id'])< $min_length 
					|| ($max_length!=0 && strlen($customer['external_id'])>$max_length))
			{
				$this->logger->error("External Id is not valid");
				if($primary_key === REGISTRATION_IDENTIFIER_EXTERNAL_ID )
					throw new Exception('ERR_LOYALTY_INVALID_EXTERNAL_ID');
				else
					Util::addApiWarning("External Id is not valid and External Id is not primary key,".
							" ignoring it");
				$customer['external_id'] = '';
			}
		}
		if(empty($customer['mobile']) && empty($customer['email']) && empty($customer['external_id']))
		{
			$this->logger->debug("No Valid mobile, email or external_id passed");
			throw new Exception('ERR_NO_MOBILE_EXT_EMAIL_ID');
		}
		return $customer;
	}
	
	/**
	 * executes Listener for Registration
	 * @param unknown_type $parms - assoc array , that needs user_id to be set in it.
	 */
	private function executeLoyaltyRegisterEvents($params)
	{
		try{
			$listener_manager = new ListenersMgr($this->currentorg);
			
			$listener_manager->signalListeners("LoyaltyRegistrationEvent", $params);
			
		}catch(Exception $e)
		{
			$this->logger->error("CustomerController: executeLoyaltyRegisterEvents ".$e->getMessage());
		}
	}
	
	private function callPointsEngineForUser($user, $timestamp, $referrer_code="")
	{
		try
		{
			$event_client = new EventManagementThriftClient();
			
			if(Util::canCallEMF())
			{
				try{
					$emf_controller = new EMFServiceController(); 
					//TODO: for now taking commit as false
					$commit = Util::isEMFActive();
					//TODO: check what is $this->user->lifetime_purchases
					$this->logger->debug("Making registrationEvent call to EMF");

					global $non_loyal;
					if($user->loyalty_type == 'non_loyalty')
						$non_loyal=true;
					else
						$non_loyal=false;


//TODO: Need to pass an extra identifier in order to distinguish loyalty and non loyalty customer
					$emf_result = $emf_controller->customerRegistrationEvent(
							$this->org_id, 
							$user->user_id, 
							$this->currentuser->user_id,
							$timestamp,
							$commit,
							$referrer_code,$non_loyal
							);
					
					$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
					$this->lm->issuedVoucherDetails($coupon_ids);
					
					if($commit && $emf_result !== null )
					{
						$event_time_in_millis = $timestamp * 100;
						//Update the old tables from the points engine view
						$pesC = new PointsEngineServiceController();
							
						$pesC->updateForCustomerRegistrationTransaction(
								$this->org_id, $user->user_id,
								$event_time_in_millis);
					}
				}
				catch(Exception $e)
				{
					$this->logger->error("Error while making registrationEvent to EMF: ".$e->getMessage());
					if(Util::isEMFActive())
					{
						$this->logger->error("Rethrowing EMF Exception AS EMF is Active");
						throw $e;
					}
				}
			}
			//Check if EMF is active, then don't make event manager calls
			if(!Util::isEMFActive())
			{
				$event_time_in_millis = $timestamp * 1000;
				$this->logger->debug("CustomerContorller: Trying to contact event manager for customer registration event");

				//TODO: Need to pass an extra identifier in order to distinguish loyalty and non loyalty customer

				$reg_result = $event_client->registrationEvent(
						$this->org_id, $user->user_id,
						$event_time_in_millis);
				$this->logger->debug("CustomerContorller: registration result: " . print_r($reg_result, true));
			
				if($reg_result != null && $reg_result->evaluationID > 0)
				{
					$this->logger->debug("CustomerContorller: Calling commit on evaluation_id: ".$reg_result->evaluationID);
			
					$commit_result = $event_client->commitEvent($reg_result);
					$this->logger->debug("CustomerContorller: Commit result on evaluation_id: ".$commit_result->evaluationID);
					$this->logger->debug("CustomerContorller: Commit result on effects: ".print_r($commit_result, true));
			
					//Update the old tables from the points engine view
					$pesC = new PointsEngineServiceController();
			
					$pesC->updateForCustomerRegistrationTransaction(
							$this->org_id, $user->user_id,
							$event_time_in_millis);
				}
			}
		}catch(Exception $e)
		{
			//TODO: should this throw Exception or should it be Warning?
			//if eventmanager_EventManagerException is thrown,
			// hen error message and code will be attributes instead of functions
			$errorCode = isset($e->statusCode) ? $e->statusCode : $e->getCode();
			$errorMessage = isset($e->errorMessage) ? $e->errorMessage : $e->getMessage();
			$this->logger->error("Error while Points engine call [Code: $errorCode, Message: $errorMessage]");
			$errorMessage = Util::convertPointsEngineErrorCode($errorCode);
			throw new Exception($errorMessage, $errorCode);
		}
	}
	
	public function updateCustomer($customer, $user = false)
	{
		$status = true;
		try
		{
			$this->logger->debug("CustomerController: Creating User Profile object");
			
			$fraud_status=null;
			if(isset($customer['fraud_status']) && !empty($customer['fraud_status']))
				$fraud_status=strtoupper($customer['fraud_status']);

			if($fraud_status && !in_array($fraud_status, $this->getValidFraudStatuses()))
				throw new Exception('ERR_INVALID_FRAUD_STATUS');
			
			$test_control_status=null;
			if(isset($customer['test_control_status']) && !empty($customer['test_control_status']))
				$test_control_status=strtoupper($customer['test_control_status']);
			
			if($test_control_status && !in_array($test_control_status,array('TEST','CONTROL')))
				throw new Exception('ERR_INVALID_TEST_CONTROL_STATUS');
				

			if(!$user)
				$user = UserProfile::getByData($customer);
			
			$status = $user->load(true);
			if($user)
			{
				$existingCustomerDetails = clone $user;
				$tempArr =  $existingCustomerDetails->getCustomFieldsData();
				$currertCustomFieldsArr  = array();
				foreach($tempArr as $cf)
				{
					$currertCustomFieldsArr[$cf["name"]] = $cf["value"];
				}
			}

			if($user -> loyalty_type == "loyalty" && $customer ['type'] == "non_loyalty"){
				$this->logger->debug("Conversion from loyalty to non loyalty is not allowed throwing exception");
				throw new Exception('ERR_LOYALTY_TO_NON_LOYALTY_NOT_ALLOWED');
			}

			if($user->loyalty_type=="non_loyalty" && $customer['type']=="non_loyalty"){
				$this->validateNonLoyaltyCustomer($customer);
			}
			else {
				$this->validateCustomerData($customer, $user);
			}
			
			$hasSubscriptions = is_array($customer["subscriptions"]) && is_array($customer["subscriptions"]["subscription"]) ? true : false;
			if($status)
			{
				$this->logger->debug("CustomerController: Updating user");
				$this->logger->debug("CustomerController: User type ".$user->loyalty_type." and customer type ".$customer['type']);
                if($user->loyalty_type == "non_loyalty" && $customer['type'] == "non_loyalty"){
                    $user->updateNonLoyaltyCustomer($customer,$hasSubscriptions ? false : true);
                }
				else if($user->loyalty_type=="non_loyalty" && $customer['type'] =='loyalty') {
					$user->validateLoyaltyConfigs($customer);
					$user->update($customer,$hasSubscriptions ? false : true,true);
				}
				else {
					$user->update($customer, $hasSubscriptions ? false : true);
				}

					
				$this->logger->debug("CustomerController: Saving custom fields");
				$user->setCustomFields($customer);
				$user->saveCustomFields();
			}

            //ingesting this event as the customer has been updated now.
            $updateEventAttributes = array();
            $updateEventAttributes["subtype"] = strtoupper($user->loyalty_type);
            $updateEventAttributes["newMobileNumber"] = $user->mobile;
            $updateEventAttributes["newEmailID"] = $user->email; //$transaction['transaction_id'];
            $updateEventAttributes["newExternalID"] = $user->external_id;
            $updateEventAttributes["customerId"] = intval($user->user_id);
            $updateEventAttributes["entityId"] = intval($this->currentuser->user_id);
            $updationTime = time();

            EventIngestionHelper::ingestEventAsynchronously(intval($this->org_id), "customerupdate",
                "Customer updation event from the Intouch PHP API's", $updationTime, $updateEventAttributes);

			$status = $user->load(true);
				
			if($hasSubscriptions)
			{
				$this->validateCustomerData($customer, $user);
				
				if(!$customer["subscriptions"]["subscription"][0])
					$customer["subscriptions"]["subscription"] = array($customer["subscriptions"]["subscription"]);

				require_once "business_controller/UserSubscriptionController.php";
				$subscriptions = array();
				
				foreach ($customer["subscriptions"]["subscription"] as $subscription)
				{
					try {
						$sub_det = $this->saveCustomerSubscriptions($user, $subscription);
					}catch(Exception $e){
						$msg = "Failed to ". ($subscription["is_subscribed"] ==1 ? "subscribe " : "unsubscribe ") .
						$subscription["priority"] . " " . $subscription["channel"]. " due to ";
						if(is_object($e) && $e->getCode())
							Util::addApiWarning($msg .$e->getMessage());
						else
							Util::addApiWarning($msg. ErrorMessage::$customer[ $e->getMessage()]);
					}
				}
			}
				
			$this->logger->debug("Load after Update: $status");
//			$this->logger->debug("CustomerController: Calling points engine");
				
			//$emf = new EventFrameworkCommunicator($data,true);
			//$emf->execute();
			if(isset($customer['associate_details']))
			{
				$this->logger->debug("Associate_details: ".print_r($customer['associate_details'], true));
				$this->saveAssociateActivity($customer['associate_details'], $user, ASSOCIATE_ACTIVITY_CUSTOMER_UPDATE);
			}
			
			if($fraud_status)
				$this->updateFraudUser(array(
						'user_id'=>$user->user_id,
						'mobile'=>$user->mobile,
						'status'=>$fraud_status,
						'reason'=>'',
						));
			
			if($test_control_status)
			{
				$segment_client = new SegmentationEngineThriftClient();
				$session_id = $segment_client->createSessionId($_SERVER[UNIQUE_ID], $user->user_id, $this->org_id );
				$user_mapping = $segment_client->getUserSegmentMapping
														(
															$this->org_id ,
															$user->user_id,
															'TestAndControl',
															$session_id
														);
					
				$this->logger->debug('User Segment current Mapping:-'.print_r( $user_mapping , true));

				if( !empty( $user_mapping ) && $user -> loyalty_type =='loyalty'){
					if(count($user_mapping->values)>0) {
						if ($test_control_status == 'TEST') {
							$user_mapping->values[0]->name = 'TEST';
							$user_mapping->values[0]->index = 0;
						} else {
							$user_mapping->values[0]->name = 'CONTROL';
							$user_mapping->values[0]->index = 1;
						}
						$this->logger->debug('User Segment current Mapping After setting values:-' . print_r($user_mapping, true));
						$segment_client->updateUserSegmentMapping($user_mapping, $session_id);
					}
					else{
						$this->createUserTestControlSegmentMapping($user->org_id, $user->user_id);
					}
				}
			}
			$tempArr = $user->getCustomFieldsData();
			$updatedCustomFieldsArr = array();
			foreach($tempArr as $cf)
			{
				$updatedCustomFieldsArr[$cf["name"]] = $cf["value"];
			}
				
			
			$this->callEmfForCustomerUpdate($user, $existingCustomerDetails, $currertCustomFieldsArr, $updatedCustomFieldsArr);
			
			$user->load();
		}catch(Exception $e)
		{
			$error_key = $user->status_code;
			throw new Exception($e->getMessage(), $e->getCode());
		}
        //add user to solr index
        $this->pushCustomerToSolr($user);
        $this->updateCustomerLoyaltyDate($user);

		return $user;
	}
	
	private function updateCustomerLoyaltyDate($user)
	{
		/* Ticket 27978: we have to set auto_update_time in users even when a custom field outside the users table changes */
		$this->logger->info("updating the auto_update_time in users and last_updated in loyalty for the customer update");
		$sql="UPDATE user_management.users SET auto_update_time=NOW() WHERE org_id='$this->org_id' AND id='$user->user_id'";
		$this->db->update($sql);
		
		$sql="UPDATE user_management.loyalty SET last_updated_by='{$this->currentuser->user_id}',last_updated=NOW() WHERE user_id='{$user->user_id}' AND publisher_id='{$this->org_id}'";
		$this->db->update($sql);
	}
	
	    /**
     * @param unknown_type $oldCustomFieldsArr
     * @param unknown_type $newCustomFieldsArr
     * 
     * Make an emf call for upare if rge custom fields
     */
    private function callEmfForCustomerUpdate($user, $oldUser, $oldCustomFieldsArr, $newCustomFieldsArr)
		
    {
	    // get the difference
	    $changedCfArr = array();
	    foreach($newCustomFieldsArr as $key => $value)
	    {
	    	if($value != $oldCustomFieldsArr[$key])
	    	{
	    		$changedCfArr[$key] = array(
	    				"customFieldName" => $key,
	    				"customFieldValue" => $value,
	    				"previousCustomFieldValue" => $oldCustomFieldsArr[$key],
	    				"assocID" => $user->getUserID()
	    		);
	    	}
	    	 
	    }
	    $this->logger->debug("Updated cf fields are ". print_r($changedCfArr, true));
	    $identifierChanged = false;
	    
	    if( $user->first_name != $oldUser->first_name) 		$identifierChanged = true; 
		else if( $user->last_name != $oldUser->last_name)	$identifierChanged = true;
		else if( $user->mobile != $oldUser->mobile)			$identifierChanged = true;
		else if( $user->email != $oldUser->email)			$identifierChanged = true;
		else if( $user->external_id != $oldUser->external_id)	$identifierChanged = true;
	    

	    //TODO : make actual emf call
	    if($changedCfArr || $identifierChanged)
	    {
	    	try {
				global $non_loyal;
				if($user->loyalty_type == 'non_loyalty')
					$non_loyal=true;
				else
					$non_loyal=false;
		    	$emfController = new EMFServiceController();
		    	$emf_result = $emfController->customerUpdateEvent($this->org_id, $user->user_id, $this->currentuser->user_id, 
						$oldUser->first_name, $user->first_name, 
						$oldUser->last_name, $user->last_name,
						$oldUser->mobile, $user->mobile,
						$oldUser->email, $user->email,
						$oldUser->external_id, $user->external_id,
						true, $changedCfArr,$non_loyal
						);
//		    	$coupon_ids = $emfController->extractIssuedCouponIds($emf_result, "PE");
//		    	$lm = new ListenersMgr($currentorg);
//				$lm->issuedVoucherDetails($coupon_ids);
//		    	
		    } catch (Exception $e) {
		    	$this->logger->debug("Emf call has failed with error - ".$e->getMessage());
		    	$this->logger->debug($e->getTraceAsString());
		    }
		    
	    }
	    else
	    {
	    	$this->logger->debug("No cf changes has happened");
	    }
	    //print_r($changedCfArr);
    }
	
	
	public function getCustomers($customer, $get = false)
	{
		$status = false;
		$this->logger->debug("CustomerController: getCustomers start");
	
		try{
			$user = UserProfile::getByData($customer);
			$this->logger->debug("CustomerController: getCustomers UserProfile Created and loading user");
			$user->load($get);
				
			if($user->getUserId() && $user->getUserId() > 0)
				return  $user;
			else
				return false;
		}catch(Exception $e){
			$this->logger->error("CustomerController->getCustomers():  ".$e->getMessage());
			throw new Exception($e->getMessage());
		}
	}
	
	public function getFraudDetails($user_id)
	{
		$sql="SELECT f.status,f.modified AS modified_on, f.entered_by AS marked_by
				FROM user_management.fraud_users f
				WHERE f.org_id=$this->org_id AND f.user_id='$user_id'";
                $key_map = array( "marked_by" => "name");
		$data = $this->db->query($sql);
                $org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
                $data = $org_entity->prepareReport($data, $key_map );
                return $data[0];
	}
	
	public function getTrackerValue($user_id)
	{
		$this->logger->debug("ApiCustomerController: get Tracker value Called for user id ".$user_id);
		$trackermgr = new TrackersMgr($this->currentorg);
		$trackerValueMapping=$trackermgr->getTrackerValueForCustomer($user_id);
		if(!$trackerValueMapping)
			return "";
		return $trackerValueMapping;
	}

	public function getNDNCStatus($user_id)
	{
		$sql="SELECT status FROM user_management.users_ndnc_status 
				WHERE org_id=$this->org_id AND user_id=$user_id";
		$status=$this->db->query_scalar($sql);
		return $status?strtoupper($status):'NONE';
	}
	
	//TODO no key on user_id currently, so only mobile!
	public function getOptinStatus($mobile,$translate=true)
	{
		
		$sql="SELECT is_active FROM user_management.users_optin_status 
				WHERE org_id=$this->org_id AND mobile='$mobile'";
		$status=$this->db->query_scalar($sql);
		if(!$translate)
			return $status;
		return ($status==null)?"NONE":($status=='1'?'OPTED-IN':'OPTED-OUT');
	}
	
	public function updateFraudUser( $params ){
	
		$result = $this->customer_model->updateFraudUser($params);
	
		if( $result ){
	
			//It will send email when fraud status will change of any user
			if( ( $params['current_status'] == 'CONFIRMED' || $params['current_status'] == 'RECONFIRMED' )  &&
					( $params['current_status'] != $params['status'] ) ){
	
				$email = $this->C_config_manager->getKey('CONF_FRAUD_STATUS_CHANGE_SEND_EMAIL');
	
				$email =StringUtils::strexplode(',',$email);
	
				$subject = 'Fraud User Status Update [Customer Mobile : '.$params['mobile'].' ] By UserId : '.$this->user_id;
	
				$message = 'Fraud User Status Update For Customer , User ID : '.$params['user_id'].' AND Mobile : '.$params['mobile']
				.'<br/> Status Changed FROM : <b>'.$params['current_status'].'</b> => TO : <b>'.$params['status'].'</b><br/><br/>';
					
				$customer_details = $this->getCustomerDataById( $params['user_id'] );
	
				$data_str = '<b>Customer Details :</b> <br/><table width=100% border=1>';
				$cnt = 0;
				foreach( $customer_details as $key => $value ){
					if( $cnt == 0 ){
						$data_str .= "<tr>";
					}
					$data_str .= "<th align=left>$key</th><td>$value</td>";
					$cnt++;
					if( $cnt == 2 ){
						$data_str .= "</tr>";
						$cnt = 0;
					}
				}
				$data_str .= '</table>';
	
				if( count($email) > 0 ){
						
					Util::sendEmail( $email , $subject , $message.$data_str , $this->org_id , '' , 1 );
					$this->logger->debug('@Fraud Status Update Email Send END');
				}
			}
		}
		return $result;
	}



	public function getTransactions($identifier_type, $identifier_value,
									$start_date, $end_date, $store_id, $store_name, $store_code, $start_id, $end_id, $sort, $order, $limit = 10, $return_credit_notes=false )
	{
		return $this->getTransactionsFiltered($identifier_type, $identifier_value,$start_date, $end_date, $store_id, $store_name, $store_code, $start_id, $end_id, $sort, $order, null, $limit, $return_credit_notes );

	}


	public function getTransactionsFiltered($identifier_type, $identifier_value,
			$start_date, $end_date, $store_id, $store_name, $store_code, $start_id, $end_id, $sort, $order, $transaction_numbers, $limit = 10, $return_credit_notes=false )
	{
		/*if(!isset($start_id) || empty($start_id) )
			$start_id = 0;

		if(!isset($end_id) || empty($end_id) )
			$end_id = -1;*/
		
		$customer_params[ $identifier_type ] = $identifier_value;

		$user = UserProfile::getByData($customer_params);
		$status = $user->load(true);
		$transactionController = new ApiTransactionController();

		if(!$status || $user->user_id < 0)
			throw new Exception("ERR_USER_NOT_REGISTERED");

		$user_id = $user->user_id;
		$order_by_filter = "ORDER BY billing_time DESC";
		$return_order_by_filter = "ORDER BY returned_on DESC";
		$deleted_outlier_status = " AND ll.outlier_status != 'DELETED' ";
		
		if($sort == 'trans_id' || $sort == 'TRANS_ID')
		{
			$sort = 'id';
			$return_sort = 'returned_on';
		}
		else 
		{
			$sort = 'billing_time';
			$return_sort = 'returned_on';
		}
		
		if($order == 'asc' || $order == 'ASC')
			$order = 'ASC';
		else 
			$order = 'DESC';
		
		$order_by_outer_filter = " ORDER BY $sort $order";

		if($start_id != null && !empty($start_id))
		{
			$transaction_id_filter = " AND ll.id > $start_id ";
			$return_transaction_id_filter = " AND rb.id > $start_id ";
			$order_by_filter = " ORDER BY ll.id ASC ";
			$return_order_by_filter = " ORDER BY rb.id ASC ";
		}
		else if( $end_id != null && !empty($end_id))
		{
			$transaction_id_filter = " AND ll.id < $end_id ";
			$return_transaction_id_filter = " AND rb.id < $end_id ";
			$order_by_filter = " ORDER BY ll.id DESC ";
			$return_order_by_filter = " ORDER BY rb.id DESC ";
		}
		
		if(!empty($store_id))
		{
			$store_id_filter = " AND ll.entered_by = $store_id ";
			$return_store_id_filter = " AND rb.store_id = $store_id ";
		}
		
		if( !empty( $store_name ) || !empty( $store_code ) )
		{
			if( !empty( $store_name ) ){
		
				$store_name_code_filter .= " AND oe.name = '$store_name' ";
			}
				
			if( !empty( $store_code ) )
			{
				$store_name_code_filter .= " AND oe.code = '$store_code' ";
			}
				
		}

		if(!empty($start_date))
		{
			$start_date = Util::deserializeFrom8601($start_date);
			$start_date = Util::getMysqlDate($start_date);
			$start_date_filter = " AND ll.DATE >= '$start_date' ";
			$return_start_date_filter = " AND rb.returned_on >= '$start_date' ";
		}

		if(!empty($end_date))
		{
			$end_date = Util::deserializeFrom8601($end_date);
			$end_date = Util::getMysqlDate($end_date);
			$end_date_filter = " AND ll.DATE <= '$end_date' ";
			$return_end_date_filter = " AND rb.returned_on <= '$end_date' ";
		}

		if(!empty($limit))
			$limit_filter = " LIMIT $limit ";
		else 
			$limit_filter = " LIMIT 10 ";



		if($transaction_numbers!= null && !empty($transaction_numbers))
		{
			$transation_query = str_replace(",","','",$transaction_numbers );

			$transaction_num_filter = " AND ll.bill_number IN ('$transation_query') ";
			$return_transaction_num_filter = " AND rb.bill_number IN ('$transation_query') ";

		}
		
		$return_sql = "SELECT 
						rb.loyalty_log_id,
						rb.id,
						SUM( IF( rbl.id IS NOT NULL , 1, 0 ) ) AS basket_size,
						rb.bill_number AS number,
						rb.amount,
						rb.notes,
						rb.returned_on AS billing_time,
						NULL AS gross_amount,
						NULL AS discount,
						NULL AS points,
						NULL AS issued_points,
						NULL AS redeemed_points,
						NULL AS expired_points,
						rb.store_id AS entered_by,
						NULL AS outlier_status,
						tds.delivery_status,
						'RETURN' as type,
						oe_store.name as store
					FROM returned_bills AS rb
					LEFT OUTER JOIN transaction_delivery_status AS tds 
						ON tds.transaction_id = rb.id 
						AND tds.transaction_type = 'RETURN' 
					LEFT JOIN returned_bills_lineitems AS rbl
						ON rbl.org_id = rb.org_id
						AND rbl.return_bill_id = rb.id
					LEFT JOIN masters.org_entities as oe 
						ON oe.id = rb.store_id
						AND oe.org_id = rb.org_id 
					LEFT JOIN masters.org_entity_relations AS oer
						ON oer.child_entity_id = oe.id 
						AND oer.child_entity_type = oe.type
						AND oer.parent_entity_type = 'STORE'
						AND oer.org_id = oe.org_id
					LEFT JOIN masters.org_entities AS oe_store
						ON oe_store.id = oer.parent_entity_id
						AND oe_store.type = oer.parent_entity_type
						AND oe_store.org_id = oer.org_id
				WHERE rb.org_id = $this->org_id
					AND rb.user_id = $user_id
					AND ((rb.parent_loyalty_log_id IS NULL) OR (rb.parent_loyalty_log_id = 0))
					$return_transaction_id_filter
					$return_store_id_filter
					$return_start_date_filter
					$return_end_date_filter
					$store_name_code_filter
					$return_transaction_num_filter
					GROUP BY rb.id
					$return_order_by_filter
					$limit_filter";

		//TODO: manage multiple coupon issued and redeemed on one transaction. 
		$regular_sql = "SELECT
					NULL AS loyalty_log_id, 
					ll.id, SUM( IF( lbl.id IS NOT NULL , 1, 0 ) ) AS basket_size, ll.bill_number as number,
					ll.bill_amount as amount, ll.notes, ll.date as billing_time,
					ll.bill_gross_amount as gross_amount, ll.bill_discount as discount,
					(ll.points - ll.redeemed) as points,
					ll.points AS issued_points,
					NULL AS redeemed_points,
					ll.expired AS expired_points,
					ll.entered_by,
					ll.outlier_status,
					tds.delivery_status,
					'REGULAR' as type,
					oe_store.name as store
				FROM loyalty_log as ll 
				LEFT OUTER JOIN transaction_delivery_status AS tds 
					ON tds.transaction_id = ll.id 
					AND tds.transaction_type = 'REGULAR' 
				LEFT JOIN masters.org_entities as oe 
					ON oe.id = ll.entered_by 
				LEFT JOIN masters.org_entity_relations AS oer
					ON oer.child_entity_id = oe.id 
					AND oer.child_entity_type = oe.type
					AND oer.parent_entity_type = 'STORE'
					AND oer.org_id = oe.org_id
				LEFT JOIN masters.org_entities AS oe_store
					ON oe_store.id = oer.parent_entity_id
					AND oe_store.type = oer.parent_entity_type
				LEFT JOIN loyalty_bill_lineitems as lbl
					ON ll.id = lbl.loyalty_log_id
					AND ll.org_id = lbl.org_id
				WHERE ll.org_id = $this->org_id
					AND ll.user_id = $user_id
					$deleted_outlier_status
					$transaction_id_filter
					$store_id_filter
					$start_date_filter
					$end_date_filter
					$store_name_code_filter
					$transaction_num_filter
					GROUP BY ll.id
					$order_by_filter
					$limit_filter";

// 					LEFT JOIN loyalty_redemptions AS lr
// 					ON ll.loyalty_id = lr.loyalty_id
// 					AND lr.bill_number = ll.bill_number
// 					AND ll.entered_by = lr.entered_by
// 					AND TRIM(ll.bill_number) != ''
			
		//temporary Solution for getting records in desc order.
		//if($start_id != null && !empty($start_id))
			$sql = "SELECT * FROM (($regular_sql) UNION ($return_sql)) AS temp_table $order_by_outer_filter $limit_filter";
		global $gbl_api_version;
		if(strtolower($gbl_api_version) == 'v1')
		{
			$sql = "SELECT * FROM ($regular_sql) AS temp_table $order_by_outer_filter $limit_filter";
		}
		
		$result = $this->db->query($sql);
		
		$billNumberArr = array();
		foreach($result as $key=>$row)
		{
			if($row["type"] == 'REGULAR')
			{
				$billNumberArr[] = $row["number"]; 
			}
		}
		try {
			
		$pesC = new PointsEngineServiceController();
		$redemptions = $pesC->getPointsRedemptionOfBillNumber($user->user_id, $billNumberArr);
			foreach($redemptions as $row)
			{
				if(in_array($row["bill_number"], $billNumberArr))
				{
					foreach($result as $key=>$bill_row)
					{
						// same bill number and diff less than 5 minutes
						if( $bill_row["type"] == 'REGULAR' && $row["bill_number"] == $bill_row["number"] && 
								abs(strtotime($bill_row["date"]) - strtotime($row["redemption_time"])) < 5*60)
						{
							$result[$key]["redeemed_points"] = $row["points_redeemed"];
							break;
						}
							
					}	
				}
			}
		} catch (Exception $e) {
			Util::addApiWarning("Failed to get point details");
			$this->logger->debug("Failed to ge the redemption details");
		}
		
		
		$this->logger->debug("Mapping of the redemptions completed");
		
		/*if(is_array($result))
		{
			foreach($result as &$trans_row)
			{
				$tran_id = $trans_row['id'];
				
				$count_sql = "SELECT count(*) FROM `loyalty_bill_lineitems`
				WHERE loyalty_log_id = $tran_id AND org_id = $this->org_id";
				$count = $this->db->query_scalar($count_sql);

				$this->logger->debug("Basket Size: ".print_r($count, true));
				$trans_row['basket_size'] = $count;
				$trans_row['store'] = $store_name; // temporary
				if(TransactionController::isReturnedTransaction($trans_row['transaction_number'], $this->org_id, $user_id))
					$trans_row['type'] = "RETURN";
				else
					$trans_row['type'] = "REGULAR";
			}
		}*/
		if(count($result) > 0)
		{
			if(Util::isPointsEngineActive())
			{
				$pesC = new PointsEngineServiceController();
				$points_data = $pesC->getPurchaseHistoryForCustomer($this->currentorg->org_id,$user_id);
				$points_bill_payload=array();
				foreach($result as $trans)
				{
					if($trans['type'] != 'REGULAR')
					{
						$this->logger->debug("Transaction type is return, not fetching points data");
						continue;
					}
					$points_bill_payload[]=array(
							'bill_id'=>$trans['id'],
							'till_id'=>$trans['entered_by'],
							'bill_date'=>$trans['billing_time']
							);
				}
				$points_redemptions=$pesC->getPointsRedeemedByBills($this->currentorg->org_id, $user_id, $points_bill_payload);
				foreach($points_data as $bill_id=>$p_data)
					$points_data[$bill_id]['points_redeemed']=$points_redemptions[$bill_id];
			}
			$new_transactions = array();
			$transaction_ids = array();
			foreach($result as $transaction)
			{
				$temp_transaction = array();
				$transaction_ids[] = $transaction['id'];
				$temp_transaction['id'] = $transaction['id'];
				$temp_transaction['number'] = $transaction['number'];
				$temp_transaction['type'] = strtoupper($transaction['type']);
				$temp_transaction['amount'] = $transaction['amount'];
				$temp_transaction['outlier_status'] = $transaction['outlier_status'];
				$temp_transaction['delivery_status'] = (is_null($transaction['delivery_status'])) ? 
														'DELIVERED' : $transaction['delivery_status'];
				$temp_transaction['notes'] = $transaction['notes'];
				$temp_transaction['billing_time'] = $transaction['billing_time'];
				$temp_transaction['gross_amount'] = $transaction['gross_amount'];
				$temp_transaction['discount'] = $transaction['discount'];
				$temp_transaction['store'] = $transaction['store'];
				$temp_transaction['points'] = $transaction['points'];
				
				$points_details=isset($points_data[$transaction['id']])?
									$points_data[$transaction['id']]:
									array('points'=>0,'points_redeemed'=>0,'points_returned'=>0,'points_expired'=>0,'expiry_date'=>0);
				
				$temp_transaction['points'] = array(
													"issued" => round($points_details['points']-$points_details['points_returned']-$points_details['points_expired'], 3),
													"redeemed" => round($points_details['points_redeemed'], 3),
													'returned'=> round($points_details['points_returned'],3),
													'expired'=>round($points_details['points_expired'], 3),
													'expiry_date'=>$points_details['expiry_date']==0?'':date("c",$points_details['expiry_date']/1000)
												);
				if(strtoupper($transaction['type'])=='RETURN')
					$temp_transaction['points']['returned']=isset($points_data[$transaction['loyalty_log_id']])?$points_data[$transaction['loyalty_log_id']]['points_returned']:0;
				else
					$temp_transaction['points']['returned']=0;
				
				$temp_transaction['coupons'] = array();
				
				$temp_transaction['basket_size'] = $transaction['basket_size'];
				
				$line_items = array();
				switch(strtolower($transaction['type']))
				{
					// fetching lineitem for return as well as regular bill
					case TRANS_TYPE_REGULAR:
						$line_items = $this->loyaltyController->getBillLineitemDetails($transaction['id']);
						break;
					// fetching lineitem for only return bills
					case TRANS_TYPE_RETURN:
						$line_items = $this->getReturnLineitems($transaction['id'], $user_id);
						break;
				}
					
				$ret_line_items = array();
				$item_codes = array();
				foreach($line_items as $row)
				{
					$line_item = array();
					switch($row['type'])
					{
						case 'REGULAR':
							$line_item['type'] = strtoupper(TRANS_TYPE_REGULAR);
                            $line_item['outlier_status'] = $row['outlier_status'];
							$line_item['serial'] = $row['serial'];
							$line_item['item_code'] = $row['item_code'];
							$line_item['description'] = $row['description'];
							$line_item['qty'] = $row['qty'];
							$line_item['rate'] = $row['rate'];
							$line_item['value'] = $row['value'];
							$line_item['discount'] = $row['discount_value'];
							$line_item['amount'] = $row['amount'];
							$line_item['attributes']['attribute'] = array();
							break;
						case TYPE_RETURN_BILL_AMOUNT:
							$temp_transaction['type'] = ($temp_transaction['type'] == 'REGULAR') ? 'MIXED' : $temp_transaction['type'];
							$line_item['type']= strtoupper(TRANS_TYPE_RETURN);
							$line_item['return_type'] = TYPE_RETURN_BILL_AMOUNT;
							$line_item['amount'] = $row['amount'];
							$line_item['transaction_number'] = $row['bill_number'];
							//added extra tags
							$line_item['serial'] = "";
							$line_item['item_code'] = "";
							$line_item['description'] = "[RETURN]";
							$line_item['qty'] = "";
							$line_item['rate'] = "";
							$line_item['value'] = "";
							$line_item['discount'] = "";
							$line_item['attributes'] = array("attribute" => "");
							break;
						case TYPE_RETURN_BILL_FULL:
						case TYPE_RETURN_BILL_LINE_ITEM:
							$temp_transaction['type'] = ($temp_transaction['type'] == 'REGULAR') ? 'MIXED' : $temp_transaction['type'];
							$line_item['type'] = strtoupper(TRANS_TYPE_RETURN);
							$line_item['return_type'] = $row['type'];
							//if return type is still
							if($line_item['return_type'] == TYPE_RETURN_BILL_FULL)
							{
								$line_item['amount'] = $row['amount'];
								$line_item['transaction_number'] = $row['bill_number'];
								//added extra tags
								$line_item['serial'] = "";
								$line_item['item_code'] = "";
								$line_item['description'] = "[RETURN]";
								$line_item['qty'] = "";
								$line_item['rate'] = "";
								$line_item['value'] = "";
								$line_item['discount'] = "";
								$line_item['attributes'] = array("attribute" => "");
								break;
							}
							$line_item['serial'] = $row['serial'];
							$line_item['item_code'] = $row['item_code'];
							$line_item['description'] = "[RETURN] ".$row['description'];
							$line_item['qty'] = $row['qty'];
							$line_item['rate'] = $row['rate'];
							$line_item['value'] = $row['value'];
							$line_item['discount'] = $row['discount_value'];
							$line_item['amount'] = $row['amount'];
							$line_item['transaction_number'] = $row['bill_number'];
							break;
					}
					$item_codes[] = "'". $row['item_code'] ."'";
					$ret_line_items [] = $line_item;
				}
				
				$attributes = array();
				if(count($item_codes) > 0)
					$attributes = $this->loyaltyController->getAttributesForItems($item_codes);
				
				if(count($attributes) > 0)
				{
					foreach($ret_line_items as $key => $line_item)
					{
						$temp_attr = $attributes[$line_item['item_code']];
						if(count($temp_attr) > 0)
						{
							$item_attributes = array();
							foreach($temp_attr as $attr_name => $attr_value)
							{
								$item_attributes[] = array( 'name' => $attr_name , 'value' => $attr_value );
							}
							$ret_line_items[$key]['attributes']['attribute'] = $item_attributes;
						}
					}
				}
				
				$temp_transaction['line_items']['line_item'] = $ret_line_items;
				
				if(strtolower($gbl_api_version) == 'v1')
				{
					if(strtolower($temp_transaction['type']) == TRANS_TYPE_REGULAR)
					{
						$is_returned = $transactionController->isBillReturned($temp_transaction['id'], $user_id);
						if($is_returned)
							$temp_transaction['type'] = strtoupper(TRANS_TYPE_RETURN);
					}
				}
				
				if($return_credit_notes){
					
					try{
						$this->logger->debug("Fetching Credit Notes");
						$credit_note = array();
						$filter = new CreditNoteFilters();
						$filter->ref_id = $temp_transaction['id'];
						$credit_notes = CreditNote::loadAll($this->currentorg->org_id, $filter);
						$ret_credit_notes=array();
						foreach($credit_notes as $note){
							$cr = $note->toArray();
							$ret_credit_notes[] = array('amount' => $cr['amount'], 'number'=> $cr['number'], 'notes'=> $cr['notes']);
						}
						$this->logger->debug("Successfully fetched the credit notes");
					} catch (Exception $e){
						$this->logger->debug("Credit Notes not Found for the transaction");
					}
					
					$temp_transaction['credit_notes']=array();
					
					if(!empty($ret_credit_notes))
						$temp_transaction['credit_notes']['credit_note']=$ret_credit_notes;
					
				}
			  #currency ratio
			  $transactionController = new ApiTransactionController();
              $currency_ratio = $transactionController->getCurrencyRatio($transaction["id"], $transaction["type"]);
	          if($currency_ratio){
	                	$temp_transaction["currency"] = array();
					    $curreny = array("ratio"=>$currency_ratio["ratio"],
	                    "id" => $currency_ratio["transaction_currency"]["supported_currency_id"],
	                    "name" => $currency_ratio["transaction_currency"]["name"],
	                    "symbol" => $currency_ratio["transaction_currency"]["symbol"],
	                    );
	                    $temp_transaction["currency"] = $curreny;
	                    //array_push($new_transactions, $tmp_transaction);
	            }
				array_push($new_transactions, $temp_transaction);
			}
			 
			
			if(count($transaction_ids) > 0)
			{
				$issued_coupons_for_all_trans = $transactionController->getIssuedCouponsForTransactions($transaction_ids);
				$redeemed_coupons_for_all_trans = $transactionController->getRedeemedCouponsForTransactions($transaction_ids);
				
				$this->logger->debug("Came here ");
				foreach($new_transactions as $key => $transaction)
				{
					$issued_coupons = $issued_coupons_for_all_trans[$transaction['id']];
					if( $issued_coupons )
					{
						$temp_issued_coupons = array();
						foreach($issued_coupons as $coupon_row)
						{
							$temp_issued_coupons [] = array(
									"id" => $coupon_row['voucher_id'],
									"code" => $coupon_row['voucher_code']
							);
						}
						$new_transactions[$key]['coupons']['issued'] = array();
						$new_transactions[$key]['coupons']['issued']['coupon'] = $temp_issued_coupons;
					}
					
					$redeemed_coupons = $redeemed_coupons_for_all_trans[$transaction['id']];
					if( $redeemed_coupons )
					{
						$temp_redeemed_coupons = array();
						foreach($redeemed_coupons as $coupon_row)
						{
							$temp_redeemed_coupons [] = array(
									"id" => $coupon_row['id'],
									"validation_code" => $coupon_row['validation_code_used'],
									"redemption_details" => $coupon_row["details"]
							);
						}
						$transaction['coupons']['redeemed'] = array();
						$transaction['coupons']['redeemed']['coupon'] = $temp_redeemed_coupons;
					}
				}
			}
			
		}
		$user_hash = $user->getHash();
		if($result != false)
			$user_hash['transactions'] = $new_transactions;

		return $user_hash;
	}
	
	public function getReturnLineitems($return_bill_id, $user_id)
	{
		$sql = "SELECT
					IFNULL(rbl.id, rb.id) AS id,
					rb.parent_loyalty_log_id AS loyalty_log_id,
					rb.user_id,
					rb.org_id,
					rbl.serial,
					rbl.item_code,
					NULL AS description,
					rbl.rate AS rate,
					rbl.qty AS qty,
					rbl.value AS value,
					rbl.discount_value AS discount_value,
					IFNULL(rbl.amount, rb.amount) AS amount,
					rb.store_id,
					NULL AS inventory_item_id,
					'NORMAL' AS outlier_status,
					returned_on AS updated_on,
					NULL AS mapped_on,
					rb.type,
					rb.bill_number
				FROM returned_bills AS rb
				LEFT JOIN returned_bills_lineitems AS rbl
					ON rbl.return_bill_id = rb.id
					AND rbl.org_id = rb.org_id
				WHERE rb.id = $return_bill_id
					AND rb.user_id = $user_id
					AND	rb.org_id = $this->org_id ORDER BY serial";
		return $this->db->query($sql);
		
	}
	
	public function getCouponRedemptionHistory( $identifier_type, $identifier_value, 
						$start_date, $end_date, $store_code, $start_id, $end_id, $order, $limit, $sort, $version ){
		
		$customer_params[ $identifier_type ] = $identifier_value;
		$limit =  !$limit ? 10 : $limit;
		$user = UserProfile::getByData( $customer_params );
		$status = $user->load( true );
		if( !$status || $user->user_id < 0 ){
			
			throw new Exception( "ERR_USER_NOT_REGISTERED" );
		}
		$user_id = $user->user_id;
		$order_by_filter = "ORDER BY vr.used_date DESC";
		if( strtolower( $sort ) == 'redemption_id' ){
			
			$sort = 'id';
		}else{
			
			$sort = 'redeemed_time';
		}
		if( strtolower( $order ) == 'asc' ){
			
			$order = 'ASC';
		}else{
			
			$order = 'DESC';
		}
		$order_by_outer_filter = " ORDER BY $sort $order";
		if( $start_id != null && !empty( $start_id ) ){
			
			$redemption_id_filter = " AND vr.id > $start_id ";
		}
		if( $end_id != null && !empty( $end_id ) ){
			
			$redemption_end_id_filter = " AND vr.id < $end_id ";
		}
		if(!empty($store_code)){
			
			$store_code_filter = " AND oe.code LIKE '$store_code' ";
		}
		if( !empty( $start_date ) ){
			
			$start_date = Util::deserializeFrom8601( $start_date );
			$start_date = Util::getMysqlDate( $start_date );
			$start_date_filter = " AND vr.used_date >= '$start_date' ";
		}
		if(!empty($end_date)){
			
			$end_date = Util::deserializeFrom8601($end_date);
			$end_date = Util::getMysqlDate($end_date);
			$end_date_filter = " AND vr.used_date <= '$end_date' ";
		}
				
		$limit_filter = " LIMIT $limit ";
		
		$sql = "SELECT  vr.id, v.voucher_code as code, v.voucher_series_id as series_id,
						vs.description, vs.discount_code, vs.discount_type, vs.discount_value,
						vr.bill_number as transaction_number,
						vr.used_date as redeemed_time, 
						oe.code as redeemed_at,
						oes.code as redeemed_store_code, oes.name as redeemed_store_name,
						oe.code as redeemed_till_code, oe.name as redeemed_till_name
				FROM luci.voucher_redemptions as vr
				JOIN luci.voucher as v 
					ON vr.voucher_id = v.voucher_id 
					AND vr.org_id = v.org_id
				JOIN luci.voucher_series as vs
					ON vs.id = vr.voucher_series_id
					AND vs.org_id = vs.org_id
				JOIN masters.org_entities oe 
					ON oe.id = vr.used_at_store
					AND oe.org_id = vr.org_id	
				JOIN masters.org_entity_relations oer 
					ON oer.child_entity_id = oe.id
					AND oer.parent_entity_type = 'STORE'
					AND oe.org_id = oer.org_id
				JOIN masters.org_entities oes
					ON oer.parent_entity_id = oes.id
					AND oes.org_id = oe.org_id 	
				WHERE vr.org_id = $this->org_id
						AND vr.used_by = $user_id
						$redemption_id_filter
						$redemption_end_id_filter
						$store_code_filter
						$start_date_filter
						$end_date_filter
				GROUP BY vr.id
						$order_by_filter
						$limit_filter";

		$sql = "SELECT * FROM ( $sql ) AS temp_table $order_by_outer_filter";

		$result = $this->db->query($sql);
		$user_hash = $user->getHash();
		if($result != false){
			
			foreach ( $result as $k => $res ){
					
				if( $version == 'v1.1' ){
					$result[$k]['redeemed_store'] = array(
							'code' => $res['redeemed_store_code'],
							'name' => $res['redeemed_store_name'],
					);
					$result[$k]['redeemed_till'] = array(
							'code' => $res['redeemed_till_code'],
							'name' => $res['redeemed_till_name'],
					);
				}
				unset( $result[$k]['redeemed_store_code'] );
				unset( $result[$k]['redeemed_store_name'] );
				unset( $result[$k]['redeemed_till_code'] );
				unset( $result[$k]['redeemed_till_name'] );
			}
			$user_hash['coupons'] = $result;
			$count = $this->db->query_firstrow(" SELECT count(*) as count
										FROM luci.voucher_redemptions vr
										WHERE vr.org_id = $this->org_id
										AND vr.used_by = $user_id" );
			$user_hash['coupons_count'] = $count['count'];
		}
		return $user_hash;
	}
	
	public function getPointsRedemptionHistory( $identifier_type, $identifier_value,
			$start_date, $end_date, $store_code, $start_id, $end_id, $order, $limit, $sort, $version ){
	
		// TODO : sorting not implemented
		$this->logger->info("*in api customer getPOINTSREDEMPTIONHISTORY");
		$customer_params[ $identifier_type ] = $identifier_value;
		$limit =  !$limit ? 10 : $limit; 

		$user = UserProfile::getByData( $customer_params );
		$status = $user->load( true );
		if( !$status || $user->user_id < 0 ){
				
			throw new Exception( "ERR_USER_NOT_REGISTERED" );
		}
		$user_id = $user->user_id;
// 		$order_by_filter = "ORDER BY lr.date DESC";
// 		if( strtolower( $sort ) == 'redemption_id' ){
			
// 			$sort = 'id';
// 		}else{
			
// 			$sort = 'redeemed_time';
// 		}
// 		if( strtolower( $order ) == 'asc' ){
				
// 			$order = 'ASC';
// 		}else{
				
// 			$order = 'DESC';
// 		}
// 		$order_by_outer_filter = " ORDER BY $sort $order";
// 		if( $start_id != null && !empty( $start_id ) ){
				
// 			$redemption_id_filter = " AND lr.id > $start_id ";
// 		}
// 		if( $end_id != null && !empty( $end_id ) ){
				
// 			$redemption_id_end_filter = " AND lr.id < $end_id ";
// 		}
// 		if(!empty($store_code)){
				
// 			$store_code_filter = " AND oe.code LIKE '$store_code' ";
// 		}
// 		if( !empty( $start_date ) ){
				
// 			$start_date = Util::deserializeFrom8601( $start_date );
// 			$start_date = Util::getMysqlDate( $start_date );
// 			$start_date_filter = " AND lr.date >= '$start_date' ";
// 		}
	
// 		if(!empty($end_date)){
			
// 			$end_date = Util::deserializeFrom8601($end_date);
// 			$end_date = Util::getMysqlDate($end_date);
// 			$end_date_filter = " AND lr.date <= '$end_date' ";
// 		}
		
// 		$limit_filter = " LIMIT $limit ";
		
		
// 		$sql = "SELECT  lr.id, lr.points_redeemed,
// 						lr.bill_number as transaction_number,
// 						lr.date as redeemed_time,
// 						oe.code as redeemed_at,
// 						oes.code as redeemed_store_code, oes.name as redeemed_store_name,
// 						oe.code as redeemed_till_code, oe.name as redeemed_till_name,
// 						lr.voucher_code AS validation_code, lr.notes
// 				FROM user_management.loyalty_redemptions as lr
// 				JOIN user_management.loyalty as l
// 					ON lr.loyalty_id = l.id
// 					AND lr.user_id = l.user_id
// 					AND lr.org_id = l.publisher_id
// 				JOIN masters.org_entities oe 
// 					ON oe.id = lr.entered_by
// 					AND oe.org_id = lr.org_id
// 				JOIN masters.org_entity_relations oer 
// 					ON oer.child_entity_id = oe.id
// 					AND oer.parent_entity_type = 'STORE'
// 					AND oe.org_id = oer.org_id
// 				JOIN masters.org_entities oes
// 					ON oer.parent_entity_id = oes.id
// 					AND oes.org_id = oe.org_id 
// 				WHERE lr.org_id = $this->org_id
// 					AND lr.user_id = $user_id
// 					$redemption_id_filter
// 					$redemption_id_end_filter
// 					$store_code_filter
// 					$start_date_filter
// 					$end_date_filter
// 				GROUP BY lr.id
// 				$order_by_filter
// 				$limit_filter";
	
// 		$sql = "SELECT * FROM ( $sql ) AS temp_table $order_by_outer_filter";
	
// 		$result = $this->db->query($sql);

		$user_hash = $user->getHash();
		$this->logger->info("calling new PE controller method to get points redemption summary");

		$pesC= new PointsEngineServiceController();
		// commenting this to check new points engine call 

		//***
		$customerRedemptions = $pesC->getCustomerRedemptionSummary($user_id);

		//$customerRedemptions = $pesC->getCustomerRedemptionSummaryFiltered($user_id);

		$count = count ($customerRedemptions);
		$this->logger->info("Points engine Redeemption summary response count".print_r($count, true));
		
		if($start_date )
		{
			$start_date = Util::deserializeFrom8601( $start_date );
			$start_date = Util::getMysqlDate( $start_date );
		}

		if($end_date)
		{
			$end_date = Util::deserializeFrom8601( $end_date );
			$end_date = Util::getMysqlDate( $end_date );
		}
		
		$tillIdsArr = array();
		foreach($customerRedemptions as $key => $row)
		{
			// id filter
			if(($start_id && $key < $start_id) || ($end_id && $key > $end_id))
			{
				unset ($customerRedemptions[$key]);
				continue;
			}
			else if(($start_date && $row["redemption_time"] < $start_date) || ($end_date && $row["redemption_time"] > $end_date))
			{
				unset ($customerRedemptions[$key]);
				continue;
			}
			
			// TO DO : add a filter$store_code_filter and store details
				
			$result[] = array(
					"id" => $key+1,
					"points_redeemed" => $row["points_redeemed"],
					"transaction_number" => $row["bill_number"],
					"validation_code" => $row["validation_code"],
					"redeemed_time" => $row["redemption_time"],
					"redeemed_at" => $row["redeemed_by"],
					"notes" => $row["notes"],
					//"" => $row["points_redemption_time"],
			);
			$tillIdsArr [] = $row["redeemed_by"];
			
		}
		
		if($result != false){

			if( $version == 'v1.1' )
			{
				$sql  = "SELECT oes.code as redeemed_store_code, oes.name as redeemed_store_name,
				oe.code as redeemed_till_code, oe.name as redeemed_till_name, oe.id as till_id
				FROM masters.org_entities oe
				INNER JOIN masters.org_entity_relations oer
				ON oer.child_entity_id = oe.id
				AND oer.parent_entity_type = 'STORE'
				AND oe.org_id = oer.org_id
				INNER JOIN masters.org_entities oes
				ON oe.org_id = oes.org_id and oer.parent_entity_id = oes.id AND oes.type = 'STORE'
				WHERE oe.org_id = $this->org_id AND oe.id in (".implode(",", $tillIdsArr).")";
				$tillDetailsArr = $this->db->query_hash($sql, "till_id", array(
						"redeemed_store_code", "redeemed_store_name", "redeemed_till_code", "redeemed_till_name")
				);
			}
			foreach ( $result as $k => $res ){
				
				if($store_code && $store_code != $tillDetailsArr[$res['redeemed_by']]["redeemed_till_name"] )
				{
					unset ($res[$k]);
					continue;
				}
				
				if( $version == 'v1.1' ){
					$result[$k]['redeemed_store'] = array(
							'code' => $tillDetailsArr[$res["redeemed_at"]]["redeemed_store_code"],
							'name' => $tillDetailsArr[$res['redeemed_at']]["redeemed_store_name"],
					);
					$result[$k]['redeemed_till'] = array(
							'code' => $tillDetailsArr[$res['redeemed_at']]["redeemed_till_code"],
							'name' => $tillDetailsArr[$res['redeemed_at']]["redeemed_till_name"],
					);
				}else
				{
					unset($result[$k]['notes']);
					unset($result[$k]['validation_code']);
				}
				$result[$k]['redeemed_at'] = $tillDetailsArr[$res['redeemed_at']]["redeemed_till_name"];
				unset( $result[$k]['redeemed_store_code'] );
				unset( $result[$k]['redeemed_store_name'] );
				unset( $result[$k]['redeemed_till_code'] );
				unset( $result[$k]['redeemed_till_name'] );
			}
			$user_hash["points"] = array_slice($result, 0, $limit);
// 			$user_hash['points'] = $result;
			
// 			$count = $this->db->query_firstrow(" SELECT count(*) as count
// 										FROM user_management.loyalty_redemptions lr
// 										WHERE lr.org_id = $this->org_id
// 										AND lr.user_id = $user_id" );
			$user_hash['points_count'] = $count;
		}
		$this->logger->info("Returning from Api Customer Controller getPointsRedemptionHistory() to customer redemptions with response".print_r($user_hash, true));
		return $user_hash;
	}

	public function getTrackers($data)
	{
		$result = array();
		$this->logger->debug("Customer Controller:: getTrackers");
		try
		{
			$user = UserProfile::getByData($data);
			if(!$user && $user->user_id < 0 && $user->getLoyaltyId() < 0 )
				throw new Exception("ERR_USER_NOT_REGISTERED");
			$user->load(true);

			$result['user_id'] = $user->getUserId();
			$result['firstname'] = $user->first_name;
			$result['lastname'] = $user->last_name;
			$result['mobile'] = $user->mobile;
			$result['email'] = $user->email;
			$result['external_id'] = $user->external_id;

			$result['trackers'] = array();
			$this->logger->debug("ApiCustomerController: get Tracker value Called for user id ".$user->getUserId());
			$trackermgr = new TrackersMgr($this->currentorg);
			$trackerValueMapping=$trackermgr->getTrackerValueForCustomer($user->getUserId());
			if(!$trackerValueMapping)
				$result['trackers']=null;
			else
				$result['trackers']=$trackerValueMapping;


		}
		catch(Exception $e)
		{
			$this->logger->error("CustomerController::getTrackers() Error: ".$e->getMessage());
			throw $e;
		}
		return $result;
	}
				
	public function getNotes($data, $assoc_ids = array())
	{
		$result = array();
		$this->logger->debug("Customer Controller:: getNotes Start: User[".print_r($user, true)."]");
		try
		{
			$user = UserProfile::getByData($data);
			if(!$user && $user->user_id < 0 && $user->getLoyaltyId() < 0 )
				throw new Exception("ERR_USER_NOT_REGISTERED");
			$user->load(true);
				
			$result['user_id'] = $user->getUserId();
			$result['firstname'] = $user->first_name;
			$result['lastname'] = $user->last_name;
			$result['mobile'] = $user->mobile;
			$result['email'] = $user->email;
			$result['external_id'] = $user->external_id;
				
			$result['notes'] = array();
			$notes = $this->customer_model->getAllNotes($user->user_id, $assoc_ids);
			if(is_array($notes) && count($notes) > 0)
			{
				$result['notes']['note'] = $notes;
			}
			
		}
		catch(Exception $e)
		{
			$this->logger->error("CustomerController::getNotes() Error: ".$e->getMessage());
			throw $e;
		}
		return $result;
	}
	
	public function addOrUpdateNotes($user, $assoc_id, $notes)
	{
		$return_notes = array();
	
		foreach ($notes as $note)
		{
			$added_by = $this->currentuser->user_id;
	
			if(!isset($note['date']) || empty($note['date']))
			{
				$timezone = StoreProfile::getById($added_by)->getStoreTimeZoneLabel();
				$added_on = Util::getCurrentTimeInTimeZone($timezone, null);
				$note['date'] = $added_on;
			}
			if(!isset($note['description']) || empty($note['description']))
			{
				$this->logger->debug("Description is not set in this note, so skipping insertion");
				continue;
			}
			if(isset($note['id']) && !empty($note['id']))
				$return_note = $this->customer_model->updateNote($user->user_id, $assoc_id, $note);
			else
				$return_note = $this->customer_model->addNote($user->user_id, $assoc_id, $note);
			if( $return_note['id'] <= 0 ){
			
				throw new Exception( "ERR_CUSTOMER_NOTES_ADD_FAILED" );
			}
			array_push($return_notes, $return_note);
		}
		
		return $return_notes;
	}
	
	
/**
	 * saves AssociateActivity
	 * @param unknown_type $associate_details
	 */
	private function saveAssociateActivity($associate_details, $user, $type)
	{
		$associate_code = $associate_details['code'];
		$associate_name = $associate_details['name'];
		$date = $user->registered_on;
		
		if(empty($associate_code))
		{
			$this->logger->debug("Associate Code is Blank not adding Activity");
			return ;
		}
		
		$description = "Customer Registered, Name: ".$user->getName()." Mobile: ".$user->mobile;
		
		$associate_model_extension = new ApiAssociateModelExtension();
		$associate_model_extension->loadFromCode($associate_code);

		if($associate_model_extension->assocBelongsToCurrentStore($associate_model_extension->getId(), $this->currentuser->user_id))
		{
			$associate_activity_id = $associate_model_extension->
											saveActivity(
													$type,
													$description,
													$user->user_id,
													Util::getMysqlDateTime($date));
		}
		else 
			$assoc_activity_id = 0;
		
		if($assoc_activity_id > 0)
		{
			$this->logger->debug("Associate Activity Added Successfully");
		}
		else
		{
			$this->logger->error("Associate Activity can't be added");
		}
	}
	
public function getCustomerInteraction($version, $data , $type , $network)
	{
		$result = array();
		$org_id = $this->org_id;
		$this->logger->debug("Customer Controller:: getCustomerInteraction Start: User[".print_r($user, true)."]");
		try
		{
			$user = UserProfile::getByData($data);
			$user->load(true);
			if(!$user && $user->user_id < 0 && $user->getLoyaltyId() < 0 )
				throw new Exception("ERR_USER_NOT_REGISTERED");
			
		
			$result['id'] = $user->getUserId();
			$result['mobile'] = $user->mobile;
			$result['email'] = $user->email;
			$result['external_id'] = $user->external_id;
			$result['interactions']['network'] = array();
		    
			if($network == 'capillary')
			{
				if($type == 'sms' || $type == 'email')
				{	
					
					if($type == 'sms'){
						$type = 'mobile';
					}	
		    		$nsadmin_response = $this->customer_model->getInteraction($org_id , $result[$type]);
					array_push($result['interactions']['network'], array('name' => $network, 'interaction' => $this->getCustomerInteractionSmsEmail( $version, $nsadmin_response,$type )));
					
				}
				else if($type == 'missed_call')
				{
					$missed_call_info = $user->getMissedCall();
					if($missed_call_info === null)
						throw new Exception('ERR_INTERACTION_NOT_FOUND');
					array_push($result['interactions']['network'], array(
							'name' => $network,
							'interaction' => array(
										'type' => 'missed_call',
										'count' => $missed_call_info['count'],
										'last_interaction_time' => $missed_call_info['last_missed_call_time'],
										'used_status' => true
									)
							));
				}else if( $type == 'survey' && $version == 'v1.1' ){
					
					$survey_info = $this->getCustomerInteractionSurvey( $org_id, $user->getUserId() );
					array_push(
							$result['interactions']['network'], 
							array( 
									'name' => $network,
									'interaction' => $survey_info
									) );
				}
				else
				{
					
					$nsadmin_response_email = $this->customer_model->getInteraction($org_id , $result['email']);
					$nsadmin_response_sms = $this->customer_model->getInteraction($org_id , $result['mobile']);
					
					$email_interaction = array();
					$sms_interaction = array();
					$missed_call_interaction = array();
					$survey_interaction = array();
					
					$survey_interaction = $this->getCustomerInteractionSurvey( $org_id, $user->getUserId() );
					$email_interaction = array($this->getCustomerInteractionSmsEmail( $version, $nsadmin_response_email,'email' ));
					$sms_interaction = $this->getCustomerInteractionSmsEmail( $version, $nsadmin_response_sms,'sms' );
					
					$missed_call_info = $user->getMissedCall();
					$missed_call_interaction =array( 
								'type' => 'missed_call',
								'count' => $missed_call_info ? $missed_call_info['count'] : 0,
								'last_interaction_time' => $missed_call_info ? $missed_call_info['last_missed_call_time'] : null,
								'used_status' => $missed_call_info ? true : false
						);
					array_push($email_interaction , $sms_interaction);
					array_push($email_interaction , $missed_call_interaction);
					if( $version == 'v1.1' ){
						
						array_push($email_interaction , $survey_interaction);
					}
					array_push($result['interactions']['network'], array('name' => $network, 'interaction' => $email_interaction));
					
				}
			}
		}
		catch(Exception $e)
		{
			$this->logger->error("CustomerController::getCustomerInteraction() Error: ".$e->getMessage());
			throw $e;
		}
		
		return $result;
		
	}
	
	private function getCustomerInteractionSmsEmail( $version, $nsadmin_response , $types )
	{
		if($types == 'mobile')
		{
			$types = 'sms';
		}
		$result = array();
		$interactions = array();
		
		$interactions['type'] = $types;
		$interactions['messages']['message'] = array();
		$i = 0;
		foreach($nsadmin_response as $response)
		{
			++$i;
			$message['message'] = array();
			$message['message']['id'] = $response->messageId;
			$message['message']['sender'] = $response->sender;
			$message['message']['reciever'] = $response->receiver;
			$message['message']['subject'] = $response->message;
			if( $version == 'v1.1' ){

				$message['message']['sent_time'] = date( 'Y-m-d H:i:s' , $response->sentTimestamp/1000 );

				//added for membercare
				$message['message']['delivered_time'] = ($response->deliveredTimestamp>0)?date("c", $response->deliveredTimestamp/1000 ):"";
				$message['message']['status'] = $response->status;
			}
			array_push($interactions['messages']['message'] , $message['message']);
		}
		$interactions['count'] = $i;
		$result = $interactions;
		return $result;
	}
	
	private function getCustomerInteractionSurvey( $org_id, $user_id ){
		
		include_once 'helper/SurveyManager.php';
		$survey_manager = new SurveyManager();
		
		$interactions['type'] = 'survey';
		$customer_satisfaction = $survey_manager->getCustomerSatisfaction( $org_id, $user_id );
		$interactions['latest_nps_score'] = $customer_satisfaction['recent_nps_score'];
		$interactions['latest_survey_name'] = $customer_satisfaction['survey_name'];
		$interactions['latest_survey_interaction_time'] = $customer_satisfaction['last_updated_on'];

		$interactions['surveys']['survey'] = array();
		$customer_interaction_surveys = $survey_manager->getCustomerSatisfactionHistory( $org_id, $user_id );
		$i = 0;
		foreach( $customer_interaction_surveys as $interaction_survey ){
			
			++$i;
			$sent_by_user = StoreProfile::getById( $interaction_survey['issued_by'] );
			$sent_by = $sent_by_user->first_name." ".$sent_by_user->last_name;
			try{
				
				$survey_url = $survey_manager->getUserFilledSurveyFormLink( 
					$interaction_survey['survey_code'], 
					$interaction_survey['form_code'],
					$interaction_survey['token_code'],
					true );
			}catch( Exception $e ){
				
				$survey_url = '';
				$this->logger->debug( "Exception while fetching survey url : ".$e );
			} 
			
			array_push( 
					$interactions['surveys']['survey'], 
					array( 
							'name' => $interaction_survey['survey_name'],
							'nps_score' => $interaction_survey['nps_score'],
							'sent_by' => $sent_by,
							'response_url' => $survey_url,
							'sent_time' => $interaction_survey['sent_time'],
							'completion_time' => $interaction_survey['completion_time'] ) );
		}
		$interactions['count'] = $i;
		$result = $interactions;
		return $result;
	}
	
	public function getPreferences($data)
	{
		$result = array();
		$this->logger->debug("Customer Controller:: getPreferences Start: User[".print_r($user, true)."]");
		try
		{
			$user = UserProfile::getByData($data);
			$user->load(true);
			if(!$user && $user->user_id < 0 && $user->getLoyaltyId() < 0 )
				throw new Exception("ERR_USER_NOT_REGISTERED");
			
			$result['user_id'] = $user->getUserId();
			$result['mobile'] = $user->mobile;
			$result['email'] = $user->email;
			$result['external_id'] = $user->external_id;
			
			$cf = new CustomFields();
			
			$preferred_store_id=$cf->getCustomFieldValueByFieldName($this->org_id, CUSTOMER_PREFERENCES, $user->getUserId(), 'PREFERRED_STORE' );
			if($preferred_store_id)
			{
				$preferred_store=$this->db->query_firstrow("SELECT * FROM masters.org_entities WHERE id='$preferred_store_id'");
				$result['store']=array(
						'code'=>$preferred_store['code'],
						'name'=>$preferred_store['name']
						);
			}
			
			$custom_fields_data = $cf->getCustomFieldValuesByAssocId( $this->org_id , 'customer_preferences' , $user->getUserId() );
			$field_array = array();
			$fin = array();
			if( $custom_fields_data )
			{
				foreach( $custom_fields_data as $key => $value )
				{
					if($key=="PREFERRED_STORE")
					{
						array_push($fin, array('name'=>$key,'value'=>$preferred_store['name']));
						continue;
					}
					$field_array[ 'field' ][ 'name' ] = $key;
					//$field_array[ 'field' ][ 'value' ] = json_decode( $value ) ;
					$decoded_value = json_decode( $value ) ;
					if($decoded_value === null)
						$field_array[ 'field' ][ 'value' ] = $value;
					else if(is_array($decoded_value)
							&& count($decoded_value) > 0 && $decoded_value[0] === null)
						$field_array[ 'field' ][ 'value' ] = 'null';
					else
						$field_array[ 'field' ][ 'value' ] = 
							is_array($decoded_value) ? implode(",", $decoded_value) : $value;
					array_push( $fin , $field_array[ 'field' ] );
				}
			}
			
			if(is_array($fin) && count($fin) > 0 || $preferred_store_id)
				$result['custom_fields']['field'] =  $fin;
			else 
				throw new Exception('ERR_CUSTOMER_PREFERENCES_NOT_FOUND');
		}
		catch(Exception $e)
		{
			$this->logger->error("CustomerController::getPreferences() Error: ".$e->getMessage());
			throw $e;
		}
		return $result;
	}
	
public function updatePreferences( $data )
	{
		$result = array();
		$this->logger->debug("Customer Controller:: updatePreferences Start: User[".print_r($user, true)."]");
		try
		{
			$user = UserProfile::getByData($data);
			$user->load(true);
			if(!$user && $user->user_id < 0 && $user->getLoyaltyId() < 0 )
				throw new Exception("ERR_USER_NOT_REGISTERED");
		
			$result['user_id'] = $user->getUserId();
			$result['mobile'] = $user->mobile;
			$result['email'] = $user->email;
			$result['external_id'] = $user->external_id;
			
			if(isset($data['store']))
			{
				if(!empty($data['store']['id']))
					$preferred_store=$this->db->query_firstrow("SELECT * FROM masters.org_entities WHERE id='{$data['store']['id']}'");
				if(!empty($data['store']['code']))
					$preferred_store=$this->db->query_firstrow("SELECT * FROM masters.org_entities WHERE code='{$data['store']['code']}'");
				
				$result['store']=array(
						'id'=>$preferred_store['id'],
						'code'=>$preferred_store['code'],
						'name'=>$preferred_store['name'],
						'status'=>'FAILED'
						);
				
				if(!empty($preferred_store))
				{
					$cf = new CustomFields();
					$ps_cf = $this->db->query_firstrow("SELECT * FROM user_management.custom_fields WHERE org_id='$this->org_id' AND name='PREFERRED_STORE'");
					$ps_cf = $cf->getCustomFieldByName($this->org_id, 'PREFERRED_STORE');
					if(empty($ps_cf))
					{
						$sql="INSERT INTO user_management.custom_fields (org_id, name, `type`, `datatype`,
									`scope`, `label`, `default`, `phase`,
									`position`, `rule`, `server_rule`, `regex`, `helptext`, `error`,
									`attrs`, `is_disabled`, `is_compulsory`, `disable_at_server` ,`modified_by`, `last_modified`)
								VALUES ('$this->org_id', 'PREFERRED_STORE', 'select', 'String',
									'customer_preferences', 'Preferred Store', '', '0',
									'0', '', '', '', 
									'', '','', '1', 
									'0', '1' , '{$this->user_id}', NOW()) ";
						$status = $this->db->insert($sql);
					}
					$ps_cf = $this->db->query_firstrow("SELECT * FROM user_management.custom_fields WHERE org_id='$this->org_id' AND name='PREFERRED_STORE'");
					if(!empty($ps_cf))
					{
						$cf->createOrUpdateCustomFieldData($this->org_id, $user->getUserId(), $ps_cf['id'], $preferred_store['id']);
						$result['store']['status']='SUCCESS';
					}
				}
			}
			
			if(isset($data['custom_fields']) && isset($data['custom_fields']['field']))
			{
				if(isset($data['custom_fields']['field']['name']))
				{
					$data['custom_fields']['field'] = array($data['custom_fields']['field']);
				}
				$custom_fields_data = $data['custom_fields']['field'];
				
				$result['custom_fields']['field'] = $custom_fields_data;
				
				$count_custom_field = count( $custom_fields_data );
				
				if( $count_custom_field > 0 )
				{
					$cf = new CustomFields();
					$assoc_id = $user->getUserId();
					$custom_fields = $cf->getCustomFieldsByScope( $this->org_id, 'customer_preferences' );
					$temp_fields = array();
					$valid_cf_for_scope = 0;
					
					foreach($custom_fields_data as &$field)
					{	
						
						foreach ( $custom_fields as $custom_field ){
							
							if( !strcasecmp( strtolower( $field['name'] ), $custom_field['name'] ) ){
								
								$valid_cf_for_scope = 1;
							}
						}
						if( $valid_cf_for_scope ){
							
							$temp_field['field_name'] = $field['name'];
							$temp_field['field_value'] = $field['value'];
							$temp_fields[] =$temp_field; 
							$field['value'] = json_encode($field['value']);
						}else{
								
							$this->logger->error( "Error in updating custom field: ".$field['name'] );
							$this->logger->error( "Custom field ".$field['name']."with value ".$field['value']. 
												   	"does not belong to scope customer_preferences" );
						}
					}
					
					$this->logger->debug("Saving custom fields");
					$count = $cf->addMultipleCustomFieldsForAssocId( $assoc_id , $temp_fields );
					
					$this->logger->debug("Total Count: $count, Updation Count: $temp_fields");
					
					if($count != $count_custom_field){
						
						$this->logger->error( "Some Preferences update failed" );
						throw new Exception('ERR_CUSTOMER_SOME_PREFERENCES_UPDATE_FAILED');
					}
				}
			}
			
		}
		catch(Exception $e)
		{
			$this->logger->error("CustomerController::updatePreferences() Error: ".$e->getMessage());
			throw $e;
		}
		return $result;
	}
	
	public function getNumberOfBillsForUser( $user_id )
	{
		return $this->loyaltyController->getNumberOfBills(false,false,$user_id);
	}
	
	public function getRegisteredCustomersFromList(array $customer_ids)
	{
		$this->logger->debug("Getting Registered Customers id ");
		return $this->customer_model->getRegisteredCustomersFromList($customer_ids);
	}

    //adds customer to solr index which can later be used to search customer
    public function pushCustomerToSolr($user)
    {
        $doc = array('pkey' => $user->user_id.'_'.$user->org_id, 'user_id' => $user->user_id,
            'org_id' => $user->org_id, 'firstname' => $user->first_name,
            'lastname' => $user->last_name, 'email' => $user->email, 'external_id' => $user->external_id,
            'mobile' => $user->mobile, 'loyalty_points' => $user->loyalty_points,
            'lifetime_purchases' => $user->lifetime_purchases, 'lifetime_points' => $user->lifetime_points,
            'slab' => $user->slab_name, 'registered_store' => $user->registered_by,
            'registered_date' => gmdate('Y-m-d\TH:i:s\Z', strtotime($user->registered_on)), 'last_trans_value' => 0);

        $this->logger->debug("Adding doc to solr " );
        try {
            include_once 'apiHelper/search/CustomerSearchClient.php';
            $search_client = new CustomerSearchClient();
            $search_result = $search_client->addDocument($doc);
            $this->logger->debug("Result of solr add : ");
        } catch (Exception $e) {
            $this->logger->info("Failed to add document to solr " . print_r($doc, true));
            $this->logger->debug($e->getMessage());
        }

        $this->logger->debug("Adding user : " . $user->user_id . " from org " . $user->org_id . " to beanstalk");
        $input = array('org_id' => $user->org_id, 'user_ids' => array(strval($user->user_id)));
        try {
            $client = new AsyncClient("customer", "customersearchtube");
            $payload = json_encode($input, true);
            $this->logger->debug("payload for job : " . $payload);

            $j = new Job($payload);
            $j->setContextKey("event_class", "customer");
            $job_id = $client->submitJob($j);
        } catch (Exception $e) {
            $this->logger->error("Error submitting job to beanstalk for solr : " . $e->getMessage());
        }
        if ($job_id <= 0)
            $this->logger->error("Failed to submit job to add user to solr. user_id : " . $user->user_id .
                " org_id : " . $user->org_id);
        return $job_id;
    }

    //Get user's segment mapping for org 
    public function getUserSegmentMapping($org_id, $user_id)
    {
        try {
            include_once 'thrift/segmentation.php';
            $seC = new ApiSegmentationEngineController();
            $customer_segment = $seC->getUserSegmentMapping($user_id);
            /*foreach($customer_segment as $key=>$segment)
            {
            	if(!$segment || !is_object($segment)|| !$segment->values || !$segment->segmentName)
            	{
            		unset($customer_segment[$key]);
            	}
            }*/
            return $customer_segment;
        } catch (Exception $e) {
            $this->logger->error("Exception while calling Segmentation Engine : " . $e->getMessage());
            return false;
        }
    }
    
    //Create Test and Control Segment Mapping for user
    public function createUserTestControlSegmentMapping( $org_id, $user_id ){
    
    	try{
    		 
    		include_once 'thrift/segmentation.php';
    		$seC = new SegmentationEngineThriftClient();
    		$session_id = $seC->createSessionId(
    				Util::getServerUniqueRequestId(),
    				$this->currentuser->user_id,
    				$org_id );
    		$session_id->moduleName = 'API';
    		
    		$user_test_control_segment = $seC->createUserTestControlMapping( $org_id, $user_id, $session_id );
//     		$this->logger->debug( "Segmentation engine call result: ".print_r( $user_test_control_segment, true ) );
    		if( $user_test_control_segment ){
    			 
    			$this->logger->debug( "User :$user_id of Org: $org_id successfully registered for Test and Control Segment" );
    			return true;
    		}else{
    			 
    			$this->logger->debug( "User :$user_id of Org: $org_id failed registering for Test and Control Segment" );
    			return false;
    		}
    	}catch( Exception $e ){
    
    		$this->logger->error( "Exception while calling Segmentation Engine : ".$e->getMessage() );
    		return false;
    	}
    }
    
    
    /**
     * Checks for any update in user profile, and trigger events accordingly
     * @param unknown_type $old_user_data
     * @param unknown_type $updated_user_data
     */
    private function checkUserProfileChangeAndTriggerEvents( $old_user_data, $updated_user_data ){
    
    	//Check for mobile/email/external-id update, and trigger UserProfileChange event
    	$this->logger->debug( "CustomerController: Checking for UserProfile email/external_id/mobile Change" );
    	$listener_manager = new ListenersMgr( $updated_user_data->org );
    	if( $old_user_data->email != $updated_user_data->email ){
    		 
    		$params = array(
    				'user_id' => $old_user_data->user_id,
    				'change_type' => 'email',
    				'old_value' => $old_user_data->email,
    				'new_value' => $updated_user_data->email
    		);
    		$this->logger->debug( "CustomerController: Signalling listeners for UserProfile email change event,
    				with params :".print_r( $params, true ) );
    		$listener_manager->signalListeners( EVENT_USER_PROFILE_CHANGE, $params );
    	}
    	if( $old_user_data->external_id != $updated_user_data->external_id ){
    		 
    		$params = array(
    				'user_id' => $old_user_data->user_id,
    				'change_type' => 'external_id',
    				'old_value' => $old_user_data->external_id,
    				'new_value' => $updated_user_data->external_id
    		);
    		$this->logger->debug( "CustomerController: Signalling listeners for UserProfile external_id change event,
    				with params :".print_r( $params, true ) );
    		$listener_manager->signalListeners( EVENT_USER_PROFILE_CHANGE, $params );
    	}
    	if( $old_user_data->mobile != $updated_user_data->mobile ){
    		 
    		$params = array(
    				'user_id' => $old_user_data->user_id,
    				'change_type' => 'mobile',
    				'old_value' => $old_user_data->mobile,
    				'new_value' => $updated_user_data->mobile
    		);
    		$this->logger->debug( "CustomerController: Signalling listeners for UserProfile mobile change event,
    				with params :".print_r( $params, true ) );
    		$listener_manager->signalListeners( EVENT_USER_PROFILE_CHANGE, $params );
    	}
    }
    
    /**
     * Fetches recommendations for given userId, recommendationCount and parameters
     * @param unknown_type $usersId
     * @param unknown_type $recCount
     * @param unknown_type $parameters
     */
    public function getCustomerRecommendations( $userId, $recCount, $productCount, $parameters ){
    	 
    	include_once 'apiController/ApiSearchController.php';
    	$productSearchController = new ApiSearchController('users');
    	try{
    
    		include_once 'thrift/recommender.php';
    		$this->logger->debug( "Customer Controller: Fetching $recCount recommendations for user : $userId ");
    		$reC = new RecommenderThriftClient();
    		$sessionId = $reC->createSessionId(
    				Util::getServerUniqueRequestId(), $userId, $this->org_id );
    
    		$this->logger->debug( "Calling Recommendation Engine - getProductHierarchyInvtentoryAttributeMappings " );
    		$invetoryMappings = $reC->getProductHierarchyInvtentoryAttributeMappings( $this->org_id, $sessionId );
    		$mappings = array();
    		foreach( $invetoryMappings as $invMapping ){
    
    			$mappings[ $invMapping->productTargetLevel ] = $invMapping->inventoryAttributeName ;
    		}
    		$this->logger->debug( "Fetched ProductHierarchyInvtentoryAttributeMappings : ".print_r( $mappings, true ) );
    
    		$this->logger->debug( "Calling Recommendation Engine - recommend " );
    		$recommendations = $reC->recommend( $this->org_id, 0, $userId, $recCount, $parameters, $sessionId );
    		if( !$recommendations ){
    			 
    			$this->logger->debug( "Error in fetching recommendations - No recommendations found" );
    			return false;
    		}else{
    			$this->logger->debug("recommendations". print_r($recommendations, true));
    			$configs_db = new Dbase('masters');
    			$org_id = $this->org_id;
    			
    			$sql = "SELECT ckv0.id AS id, ckv0.org_id, ckv0.value FROM config_key_values AS ckv0
							JOIN ( SELECT MAX(ckv.id) AS req_id FROM config_key_values ckv
							                    JOIN config_keys ck
							                        ON ckv.key_id = ck.id
							        WHERE ck.name LIKE 'CONF_RECOMMENDATION_ATTRIBUTES_PRIORITY' AND ckv.org_id = $org_id ) ckv_id
							ON ckv0.id = ckv_id.req_id;";
				$res = $configs_db->query( $sql );
				$attr_priority = explode(",", $res[0]['value']);
				$attrPriorityMapping = array();
				foreach ( $attr_priority as $i => $value )
					$attrPriorityMapping[$value] = $i+1;
				
				$this->logger->debug("Attribute priority mapping : ".print_r( $attrPriorityMapping, true) );
    			
    			$recommendationsFormatted = array();
    			foreach( $recommendations as $rec ){
    				 
    				$attributes = explode( "::", $rec->modelTargetValue );
    				$productSearchQuery = "";
    				$attributeValues = array();
    				foreach ( $attributes as $level => $attribute ){
    						
    					if( $mappings[++$level] ){
    						//$mappings[$level] = $attribute;
     						//$attributeValues[$mappings[$level]] = $attribute;
                            // Commenting out for ticket 40767. Please dont blame me
                            //$attributeValues[] = array( 
    						//		'name' => $mappings[$level], 
    						//		'value' => $attribute 
    						//		);
    						if( strcasecmp( $mappings[$level], "item_sku") == 0 ){
    							
    							$productSearchQuery .= "sku";
    						}else{
    							
    							$productSearchQuery .= $mappings[$level];
    						}
    						$productSearchQuery .= ":EXACT:{$attribute}|";
    					}
    				}
    				
    				if ( $rec->modelTargetType->name == 'PRODUCT_ATTRIBUTE' )
	    			{
						$attrAndIdArray = explode( ",", str_replace( array("\"", "{", "}"), "", $rec->modelTargetValue ) );
						$kpriority = 0;
						foreach ( $attrAndIdArray as $each_item )
						{
							list( $t_attr, $t_attr_val ) = explode( ":", $each_item );
							
							$inventoryAttributeEntity = InventoryAttributeValue::loadById($org_id, $t_attr_val);
							
							$this->logger->debug("*getInventoryAttributeName : ".print_r( $inventoryAttributeEntity->getInventoryAttributeName(),true));
							$this->logger->debug("*getValueName : ".print_r( $inventoryAttributeEntity->getValueName(),true));
							if ( $attrPriorityMapping[$t_attr] )
							{
								$this->logger->debug("* config_key_values ffound");
								$attributeValues[] = array(
													'priority' => $attrPriorityMapping[$t_attr],
													'name' => $inventoryAttributeEntity->getInventoryAttributeName(),
													'value' => $inventoryAttributeEntity->getValueName()
												);
								$this->logger->debug("* if block end");
							}
							else {
								$this->logger->debug("* config_key_values not found. defualt being set");
								$kpriority = $kpriority + 10;
								$attributeValues[] = array(
													'priority' => $kpriority ,
													'name' => $inventoryAttributeEntity->getInventoryAttributeName(),
													'value' => $inventoryAttributeEntity->getValueName()
												);
								$this->logger->debug("* else block end");
							}
						}
	    			}
    				$productSearchQuery = rtrim( $productSearchQuery, "|" );
    				$this->logger->debug( "Fetching products for recommendation value ".print_r( $attributeValues, true) );
    				$recommendedProducts = $productSearchController->searchProducts( urlencode( "({$productSearchQuery})"), 0, $productCount );
    				$this->logger->debug( "Fetched recommended products".print_r( $recommendedProducts, true) );
					
					if ($rec->modelTargetType->name == "PRODUCT_ATTRIBUTE")
						$type = "ATTRIBUTE";
					else
						$type = "SKU";
					
    				array_push(
    						$recommendationsFormatted,
    						array(
    								//Type not required,.
    								'type' => $type,
    								'value' => $rec->modelTargetValue,
    								'score' => $rec->score,
    								'attributes' =>  array( 
    										'attribute' => $attributeValues
    										),
    								'products' => array(
    										'product' => $recommendedProducts['product']['results']['item']
    								)
    						)
    				);
    			}
    			$this->logger->debug( "Recommendation fetch successful -
    					Returning with ".count( $recommendationsFormatted )." recommendations" );
    			return $recommendationsFormatted;
    		}
    	}catch( Exception $e ){
    
    		$this->logger->error( "Exception in fetching recommendations for user : $userId" );
    		$this->logger->error( "Exception : ".$e->getMessage() );
    		return false;
    	}
    }
    
    function getFrausUsers($fraud_status  , $order  , 
    		$sort  , $start_id  , $end_id , 
    		$start_date  , $end_date  , $store_id, $limit)
    {
    	$start_id = $start_id !== null ? intval($start_id) : null;
    	$end_id = $end_id !== null ? intval($end_id) : null;
    	
    	//if fraud status is not passed taking all fraud status as filter
    	$valid_fraud_statuses = $this->getValidFraudStatuses();
    	$fraud_status = $fraud_status !== null ? 
    		explode(",", $fraud_status) : array_keys($valid_fraud_statuses);
    	$temp_fraud_status = array();
    	
    	//getting internal fraud status. 
    	foreach ($fraud_status as $status)
    	{
    		if(isset($valid_fraud_statuses[$status]))
    			$temp_fraud_status[] = $valid_fraud_statuses[$status];
    	}
    	$this->logger->debug("Looking for users with fraud statuses " . print_r($temp_fraud_status, true));
    	$sort = ($sort !== null && in_array(strtolower($sort) , array('user_id', 'modified_date')))
    				 ? strtolower($sort) : 'modified_date';
    	
    	$order = ($order !== null && in_array(strtoupper($order) , array('ASC', 'DESC')))
    				 ? strtoupper($order) : 'DESC';
    	
    	$start_date = ($start_date !== null && Util::deserializeFrom8601($start_date) > 0 ) 
    				? Util::getMysqlDateTime(Util::deserializeFrom8601($start_date)) : null; 
    	
    	$end_date = ($end_date !== null && Util::deserializeFrom8601($end_date) > 0 )
    	? Util::getMysqlDateTime(Util::deserializeFrom8601($end_date)) : null;
    	
    	$result = $this->customer_model->getFraudUsers(
    			$this->currentorg->org_id , $temp_fraud_status, $order, 
    			$sort, $start_id, $end_id, 
    			$start_date, $end_date, $store_id, $limit);
        
        $valid_fraud_statuses_transpose = array_flip($valid_fraud_statuses);
        
        foreach($result as &$item)
        {
            $item['status'] = $valid_fraud_statuses_transpose[$item['status']];
        }
    	return $result;
    }
    
    function getValidFraudStatuses()
    {
    	$status_options = array('FRAUD' => 'MARKED',
    							'FRAUD_CONFIRMED' => 'CONFIRMED',
    							'FRAUD_RECONFIRMED' => 'RECONFIRMED',
    							'NOT_FRAUD' => 'NOT_FRAUD',
    							'INTERNAL_CUSTOMER' => 'INTERNAL');
    	return $status_options;
    }
    
    function getCoupons($identifier,$identifier_value,$types=array(),$status='',$start_date='',$end_date='',$series_id='',$order_by='created_date',$sort_order="desc")
    {
    	global $currentorg,$currentuser;

    	if(empty($order_by))
	    	$order_by='created_date';
    	if(empty($sort_order))
	    	$sort_order="desc";
	    	
    	$user=UserProfile::getByData(array($identifier=>$identifier_value));
    	$status = $user->load(true);
    	
    	$start_date=@strtotime($start_date);
    	$end_date=@strtotime($end_date);
    	
    	$sql="SELECT v.voucher_id , 
    				v.voucher_series_id,
					v.voucher_code, 
					vs.description, 
					v.created_date,
					v.created_by,
					IF ( DATE_ADD(v.created_date, INTERVAL vs.valid_days_from_create DAY) < vs.valid_till_date,
                                    DATE(DATE_ADD(v.created_date, INTERVAL vs.valid_days_from_create DAY)),
                                    vs.valid_till_date
                       ) AS valid_till,
                    v.voucher_series_id, 
                    v.bill_number,
                    v.created_by AS issued_till_code,
                    v.created_by AS issued_till_name,
                    e.code AS issued_store_code,
                    e.name AS issued_store_name,
                    vs.valid_till_date,
					vs.valid_days_from_create,
					vs.expiry_strategy_type,
					vs.expiry_strategy_value
                    FROM luci.voucher v
					JOIN luci.voucher_series vs ON v.voucher_series_id = vs.id
    				LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=v.created_by AND er.parent_entity_type='STORE'
    				LEFT OUTER JOIN masters.org_entities e ON e.id=er.parent_entity_id
					WHERE v.issued_to = $user->user_id AND v.org_id = $currentorg->org_id";
    	
    	if(!empty($types))
    		$cond_sql=" AND vs.series_type IN ('".implode("','",$types)."')";
    	
    	if($start_date)
    		$cond_sql=" AND v.created_date>'".DateUtil::getMysqlDateTime(date("Y-m-d",$start_date))."'";
    	
    	if($end_date)
    		$cond_sql=" AND v.created_date>'".DateUtil::getMysqlDateTime(date("Y-m-d",$end_date))."'";
    	
    	if($series_id!=null && !empty($series_id))
    		$cond_sql=" AND v.voucher_series_id=$series_id";
    	
    	$order_sql="ORDER BY $order_by $sort_order";
    	
    	$sql="{$sql} {$cond_sql} GROUP BY v.voucher_id {$order_sql}";
    	
    	$data=$this->db->query($sql);
    	
    	$data = $this->calculateAndRepopulateExpiryDatesForCoupons($data);

        $key_map = array( "issued_till_code" => "code", "issued_till_name" => "name" );
        $org_entity = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY );
        $data = $org_entity->prepareReport( $data, $key_map );
        
    	$coupon_ids=array();
    	foreach($data as $coupon)
    	{
    		$coupon['redeem_count']=0;
    		$coupon['redemptions']=array();
    		$coupon_ids[]=$coupon['voucher_id'];
    		$coupon_data[$coupon['voucher_id']]=$coupon;
    	}
    	$redemptions = array();
    	if(!empty($coupon_ids)){
    	
    		$curr_org_id = $currentorg -> org_id;
    		$sql="SELECT vr.voucher_id,vr.used_at_store,vr.used_date,vr.bill_number,vr.used_date,
    			e.code as store_code, e.name as store_name
    			FROM luci.voucher_redemptions vr
    			LEFT OUTER JOIN masters.org_entity_relations er ON er.child_entity_id=vr.used_at_store AND er.parent_entity_type='STORE'
    			LEFT OUTER JOIN masters.org_entities e ON e.id=er.parent_entity_id
    			WHERE vr.org_id = $curr_org_id AND vr.voucher_id IN (".implode(",",$coupon_ids).")
    			";
    	
    		$redemptions=$this->db->query($sql);
    	}
    	
    	foreach($redemptions as $redeem)
    	{
    		$vid=$redeem['voucher_id'];
    		$coupon_data[$vid]['redeem_count']++;
    		$coupon_data[$vid]['redemptions'][]=$redeem;
    	}
    	
    	$filtered_coupon_data=$coupon_data;
    	foreach(explode(";",$status) as $single_status)
    	{
	    	switch(strtolower(trim($single_status)))
	    	{
	    		case 'unredeemed':
	    			foreach($filtered_coupon_data as $vid=>$c)
	    				if($c['redeem_count']!=0)
	    					unset($filtered_coupon_data[$vid]);
	    			break;
	    		case 'active':
	    			foreach($filtered_coupon_data as $vid=>$c)
	    				if(strtotime($c['valid_till'])<time())
	    					unset($filtered_coupon_data[$vid]);
	    			break;
	    		case 'expired':
	    			foreach($filtered_coupon_data as $vid=>$c)
	    				if(strtotime($c['valid_till'])>time())
	    					unset($filtered_coupon_data[$vid]);
	    			break;
	    		case 'redeemed':
	    			foreach($filtered_coupon_data as $vid=>$c)
	    				if($c['redeem_count']==0)
	    					unset($filtered_coupon_data[$vid]);
	    			break;
	    	}
    	}
    	
    	return array("user"=>array(
    			'firstname'=>$user->first_name,
    			'lastname'=>$user->last_name,
    			'email'=>$user->email,
    			'external_id'=>$user->external_id,
    			'mobile'=>$user->mobile,
    			'id'=>$user->user_id
    			)
    			,'coupons'=>$filtered_coupon_data);
    	
    }
    
    private function calculateAndRepopulateExpiryDatesForCoupons($data){
    	$new_data = array();
    	foreach ($data AS $row){
    		$temp_voucher_details = $row;
    	
    		$coupon_creation_date = $row['created_date'];
    		$valid_till_date = $row['valid_till_date'];
    		$valid_days_from_create = $row['valid_days_from_create'];
    		$expiry_strategy_type = $row['expiry_strategy_type'];
    		$expiry_strategy_value = $row['expiry_strategy_value'];
    	
    		switch($expiry_strategy_type){
    			case "MONTHS_END":
    				$coupon_expiry_date = DateUtil::addMonths($coupon_creation_date, $expiry_strategy_value);
    				//t is the lastday of the month
    				$coupon_expiry_date = date("Y-m-t h:i:s", strtotime($coupon_expiry_date));
    				break;
    			case "MONTHS":
    				$coupon_expiry_date = DateUtil::addMonths($coupon_creation_date, $expiry_strategy_value);
    				break;
    			case "DAYS":
    			default:
    				$coupon_expiry_date = DateUtil::addDays($coupon_creation_date, $valid_days_from_create);
    				break;
    		}
    		//getting end of day
    		$coupon_expiry_date = DateUtil::getDateWithEndTime($coupon_expiry_date);
    		$series_expiry_date = DateUtil::getDateWithEndTime($valid_till_date);
    		
    		$coupon_expiry_date_in_ts = strtotime($coupon_expiry_date);
    		$series_expiry_date_in_ts = strtotime($series_expiry_date);
    	
    		$this->logger->debug("voucher_id: $temp_voucher_details[voucher_id], coupon_expiry_date: $coupon_expiry_date, series_expiry_date: $valid_till_date");
    		if($coupon_expiry_date_in_ts > $series_expiry_date_in_ts){
    			$temp_voucher_details['valid_till'] = $series_expiry_date;
    		} else {
    			$temp_voucher_details['valid_till'] = $coupon_expiry_date;
    		}
    		
    		unset($temp_voucher_details['valid_till_date']);
    		unset($temp_voucher_details['valid_days_from_create']);
    		unset($temp_voucher_details['expiry_strategy_type']);
    		unset($temp_voucher_details['expiry_strategy_value']);
    	
    		$new_data[] = $temp_voucher_details;
    	}
    	return $new_data;
    }
    
    function getTickets($identifier,$identifier_value,$status='',$priority='',$department='',$start_date='',$end_date='',$ticket_code='',$only_current_store='',$reported_from='',$type='')
    {
    	
    	global $currentorg,$currentuser;
    	
    	$user=UserProfile::getByData(array($identifier=>$identifier_value));
    	$user->load(true);
    	 
    	$start_date=@strtotime($start_date);
    	$end_date=@strtotime($end_date);
    	
    	$sql="SELECT tkt.id,tkt.ticket_code, tkt.status, tkt.priority, tkt.department, tkt.issue_code AS subject, tkt.issue_name AS message
    			FROM store_management.issue_tracker tkt
    			WHERE tkt.org_id=$this->org_id AND tkt.customer_id='$user->user_id'
    			";
    	
    	$cond_sql="";
    	
    	if($start_date)
    		$cond_sql.=" AND tkt.created_date>'".DateUtil::getMysqlDateTime(date("Y-m-d",$start_date))."'";
    	
    	if($end_date)
    		$cond_sql.=" AND tkt.created_date>'".DateUtil::getMysqlDateTime(date("Y-m-d",$end_date))."'";
    	 
    	if(!empty($status))
    		$cond_sql.=" AND tkt.status='$status'";
    	
    	if(!empty($priority))
    		$cond_sql.=" AND tkt.priority='$priority'";
    	
    	if(!empty($department))
    		$cond_sql.=" AND tkt.department='$department'";
    	
    	if(!empty($ticket_code))
			$cond_sql.=" AND tkt.ticket_code='$ticket_code'";
    	
    	if($only_current_store)
    	{
    		include_once 'apiController/ApiStoreController.php';
    		$entity_controller=new ApiEntityController('TILL');
    		$store=$entity_controller->getParentEntityByType($this->currentuser->user_id, 'STORE');
    		$store_id=array_pop($store);
    		$store_controller=new ApiStoreController();
    		$this->logger->info("Getting all TILLS of store $store_id");
    		$tills=$store_controller->getStoreTerminalsByStoreId($store_id);
    		$tills=array_merge(array($this->currentuser->user_id),$tills);
    		$this->logger->info("TILLS of store $store_id : ".implode(",",$tills));
    		$cond_sql.=" AND tkt.created_by IN (".implode(",",$tills).")";
    	}
    	
    	if(!empty($type) && in_array(strtoupper($type), array('STORE','CUSTOMER')))
    		$cond_sql.=" AND tkt.type='$type'";
    	
    	if(!empty($reported_from) && in_array(strtoupper($reported_from), array('EMAIL','INTOUCH','CALLCENTER','CLIENT','MICROSITE','SOCIAL')))
 			$cond_sql.=" AND tkt.reported_by='$reported_from'";   		
    	
    	$sql="$sql $cond_sql ORDER BY id DESC";
    	
    	$result=$this->db->query($sql);
    	
    	$ret=array("user"=>array(
    			'firstname'=>$user->first_name,
    			'lastname'=>$user->last_name,
    			'email'=>$user->email,
    			'external_id'=>$user->external_id,
    			'mobile'=>$user->mobile,
    			'id'=>$user->user_id
    	),'tickets'=>array());
    			 
    	$cf_obj=new CustomFields();
    	 
    	foreach($result as $res)
    	{
    		$ticket=array(
    				'code'=>$res['ticket_code'],
    				'status'=>$res['status'],
    				'priority'=>$res['priority'],
    				'department'=>$res['department'],
    				'subject'=>$res['subject'],
    				'message'=>$res['message']
    				);
    		$custom_fields=$cf_obj->getCustomFieldValuesByAssocId($this->org_id, CUSTOMER_CUSTOM_FEEDBACK, $res['id']);
    		$cf_names=array_keys($custom_fields);
    		$cf_values=array_values($custom_fields);
    		$custom_fields=array();
    		foreach($cf_names as $key=>$name)
    		{
    			$value = '';

    			$decodedValue = json_decode($cf_values[$key]) ;
				if ($decodedValue === null) {
					$value = $cf_values[$key];
				} else if (is_array($decodedValue) && count($decodedValue) > 0 && $decodedValue[0] === null) {
					$value = '';
				} else {
					$value = is_array($decodedValue) ? implode(",", $decodedValue) : $cf_values[$key];
				}

    			$custom_fields[] = array(
					'name' => $name, 
					'value' => $value
				);
    		}
    		$ticket['custom_fields']['field']=$custom_fields;
    		$ret['tickets'][]=$ticket;
    	}
    	
    	return $ret;
    	 
    }
    
    function getExpirySchedule($user_id)
    {
    	$this->logger->info("Getting points expiry schedule for $user_id");
    	try{
	    	$controller= new PointsEngineServiceController();
	    	$expiry_schedule_raw=$controller->getPointsExpiryScheduleForCustomer($this->org_id, $user_id);
    	}catch(Exception $e)
    	{
    		$this->logger->error("Exception occured in points engine thrift: $e");
    		$expiry_schedule_raw->pe=array();
    	}
	    	
    	$ret=array();
    	foreach($expiry_schedule_raw->pe as $schedule)
    	{
    		$ret[]=array(
    				'points'=>$schedule->pointsToBeExpired,
    				'expiry_date'=>$schedule->expiryDate/1000
    				);
    	}
    	$this->logger->debug("Expiry schedule : ".print_r($ret,true));
    	
    	return $ret;
    }
    
    function getExpiredPoints($user_id)
    {
    	$this->logger->info("Getting points expired points for $user_id");
    	$controller=new PointsEngineServiceController();
    	
    	try{
    		$raw_deductions=$controller->getDeductionsForCustomer($this->org_id,$user_id);
    	}catch(Exception $e)
    	{
    		$this->logger->error("Exception occured in points engine thrift: $e");
    		return array();
    	}
    	
    	$deductions_array=array();
    	foreach($raw_deductions->pd as $key=>$deduction)
    	{
    		if($deduction->deductionType == 'EXPIRED')
    			array_push($deductions_array,$deduction);
    	}
    	$expiry_array=array();
    	foreach($deductions_array as $key=>$pd)
    	{
    		$key=date('Y-m-d H:i:s',strtotime($pd->pointsDeductedOn))."_".$pd->pointsDeductedById;
    		if(array_key_exists($key, $expiry_array))
    		{
    			$new_pd=$expiry_array[$key];
    			$new_pd->pointsDeducted = $new_pd->pointsDeducted+$pd->pointsDeducted;
    			$new_pd->pointsDeductedCurrencyValue=round($new_pd->pointsDeductedCurrencyValue+$pd->pointsDeductedCurrencyValue,3);
    			$expiry_array[$key]=$new_pd;
    		}
    		else
    			$expiry_array[$key]=$pd;
    	}
    	$this->logger->debug('$expiry_array:'.print_r($expiry_array,true));
    	return $expiry_array;
    }
    
    function getSlabUpgradeHistory($user_id)
    {
    	$entity_controller=new ApiEntityController('TILL');
    	
    	$controller=new PointsEngineServiceController($user_id);
    	
    	$this->logger->info("Getting slab upgrade history for $user_id");

    	try{
    		$raw_history=$controller->getSlabUpgradeHistory($this->org_id, $user_id);
    	}catch(Exception $e)
    	{
    		$this->logger->error("Exception occurred in PE: $e");
    		$raw_history=array();
    	}
    	
    	if(empty($raw_history))
    		return array();
    	
    	$ret=array();
    	foreach($raw_history->slabUpgradeHistoryList as $history)
    	{
    		$store=array_pop($entity_controller->getParentEntityByType($history->tillId, 'STORE'));
    		
    		$store=$this->StoreController->getDetails($store);
    		
    		$simple=array(
    				'from_slab_id'=>$history->fromSlabSerialNo,
    				'to_slab_id'=>$history->toSlabSerialNo,
    				'type'=>($history->fromSlabSerialNo>$history->toSlabSerialNo)?'DOWNGRADE':'UPGRADE',
    				'from_slab'=>$history->fromSlabName,
    				'to_slab'=>$history->toSlabName,
    				'date'=>$history->upgradedDate/1000,
    				'till_id'=>$history->tillId,
    				'store_id'=>$store['id'],
    				'store_name'=>$store['name'],
    				'store_code'=>$store['code'],
    				'notes'=>$history->notes,
    				);
    		$ret[]=$simple;
    	}
    	
    	return $ret;
    }
    
    function getPointsSummary($user_id)
    {
    	$this->logger->info("Getting points summary for customer: $user_id");
    	
    	$controller = new PointsEngineServiceController();
    	
    	try{
    		$ps=$controller->getPointsSummaryForCustomer($this->org_id, $user_id);
    	}catch(Exception $e)
    	{
    		$this->logger->error("exception from pe: ".$e);
    		foreach(array('currentPoints','cumulativePoints','pointsRedeemed','pointsExpired','pointsReturned') as $key)
    			$ps->$key=0;
    	}
    	
    	$ret=array(
    			'current'=>round($ps->currentPoints,3),
    			'cumulative'=>round($ps->cumulativePoints,3),
    			'redeemed'=>round($ps->pointsRedeemed,3),
    			'expired'=>round($ps->pointsExpired,3),
    			'returned'=>round($ps->pointsReturned,3),
    			'adjusted'=>0
    			);
    	
    	$ret['adjusted']=$controller->getAdjustedExpiryPoints(
    					$this->org_id,
						$user_id,
						'EXPIRED',
						$ps->currentPoints,
						$ps->cumulativePoints,
						$ps->pointsRedeemed
    			);
    	
    	$ret['tier_expiry_date'] = date( 'Y-m-d h:i:s', ($ps->slabExpiryDate/1000));
    	
    	return $ret;
    	
    }
    
    function getTierDowngradeRetentionCriteria($user_id){
    	 
    	$this->logger->info("Getting tier retention criteria for customer: $user_id");

    	$ps_cleint = new PointsEngineServiceController();

    	$ret=array();
    	$ret['min_num_visits'] = "NA";
    	$ret['min_purchase'] = "NA";
    	try{

    		if($ps_cleint->getTierDowngradeStatus($this->org_id) === true){
    			$ps=$ps_cleint->getTierDowngradeRetentionCriteria($this->org_id, $user_id);
    			$ret['min_num_visits'] = $ps->visits;
    			$ret['min_purchase'] = $ps->purchase;
    		}
    	}catch(Exception $e){
    		$this->logger->error("exception from pe: ".$e);
    	}
    	 
    	return $ret;
    }

    
    //DONOT CHANGE ANY RETURN DATA, ITS USED DIRECTLY IN API RESPONSE
    function getPromotionPoints($user_id)
    {
    	$this->logger->info("Getting promotion points for customer: $user_id");
    	 
    	$controller = new PointsEngineServiceController();

    	$points_data=$controller->getPromotionPoints($user_id);
    	
    	$till_ids=array();
    	
    	foreach($points_data as $pa)
    	{
    		foreach($pa as $p)
    			$till_ids[]=$p['awarded_by_id'];
    	}
    	
    	$sql="SELECT oer.child_entity_id AS id,oe.name,oe.code FROM masters.org_entity_relations oer
    			JOIN masters.org_entities oe ON oe.id=oer.parent_entity_id
    			WHERE oer.org_id='$this->org_id' AND oer.parent_entity_type='STORE' AND oer.child_entity_id IN (".implode(",",$till_ids).")";
    	$store_data=$this->db->query($sql);
    	$stores=array();
    	
    	foreach($store_data as $s)
    		$stores[$s['id']]=array(
    				'code'=>$s['code'],
    				'name'=>$s['name']
    				);
    	
    	$ret['customer']['item']=array();
    	$ret['transactions']['item']=array();
    	$ret['lineitems']['item']=array();
    	
    	foreach($points_data['customer'] as $pa)
    	{
    		$p=array(
    				'points'=>$pa['points'],
    				'expiry_date'=>date("Y-m-d",$pa['expiry_date']),
    				'issued_at'=>array('code'=>$stores[$pa['awarded_by_id']]['code'],
    						'name'=>$stores[$pa['awarded_by_id']]['name'],
    				),
    				'issued_on'=>date("c",$pa['date'])
    		);
    		$ret['customer']['item'][]=$p;
    	}

    	//   DONOT CHANGE ANY RETURN DATA, ITS USED DIRECTLY IN API RESPONSE!!!!!!!!!!!!
    	   
    	
    	foreach($points_data['transaction'] as $pa)
    	{
    		$p=array(
    				'transaction_id'=>$pa['bill_id'],
    				'points'=>$pa['points'],
    				'expiry_date'=>date("Y-m-d",$pa['expiry_date']),
    				'issued_at'=>array('code'=>$stores[$pa['awarded_by_id']]['code'],
    						'name'=>$stores[$pa['awarded_by_id']]['name'],
    				),
    				'issued_on'=>date("c",$pa['date']),
    		);
    		$ret['transactions']['item'][]=$p;
    	}
    	
    	foreach($points_data['lineitem'] as $pa)
    	{
    		$p=array(
    				'lineitem_id'=>$pa['line_item_id'],
    				'transaction_id'=>$pa['bill_id'],
    				'points'=>$pa['points'],
    				'expiry_date'=>date("Y-m-d",$pa['expiry_date']),
    				'issued_at'=>array('code'=>$stores[$pa['awarded_by_id']]['code'],
    						'name'=>$stores[$pa['awarded_by_id']]['name'],
    				),
    				'issued_on'=>date("c",$pa['date']),
    		);
    		$ret['lineitems']['item'][]=$p;
    	}
    	 
    	return $ret;
    	 
    }

    private function saveCustomerSubscriptions(UserProfile $user, $subscription)
    {
    	$this->validate_subscriptions($subscription);
    
    	$priority=strtoupper($subscription['priority']);
    	$channel=strtoupper($subscription['channel']);
    	$scopes=isset($subscription['scope'])?explode(",",strtoupper($subscription['scope'])):array("ALL");
    	$campaign_id=!empty($subscription['campaign_id'])?$subscription['campaign_id']:-1;
    	$outbox_id=!empty($subscription['outbox_id'])?$subscription['outbox_id']:-1;
    	$client_headers=!empty($subscription['client_headers'])?$subscription['client_headers']:NULL;
    
    	$user_list[]=$user_id=$user->getUserId();
    	$sub_det['user_id']=$user->getUserId();
//     	if($identifier!="id")
//     		$sub_det[$identifier]=$input;
    	$sub_det['channel']=$channel;
    	$sub_det['priority']=$priority;
    	$sub_det['scope']=implode(",",$scopes);
    	$sub_det['is_subscribed']=$subscription["is_subscribed"];
    	$sub_det['item_status']=array();
    
    	$USC=new UserSubscriptionController($this->currentorg->org_id);
    		
    	if($subscription["is_subscribed"]==1)
    		$ret = $USC->subscribeUser($user_id, $channel, $priority, $scopes, '', $campaign_id, $outbox_id, $client_headers);
    	else
    		$ret = $USC->unSubscribeUser($user_id, $channel, $priority, $scopes, '', $campaign_id, $outbox_id, $client_headers);
    		
    	return $sub_det;
    
    }
    
    private function validate_subscriptions($params,$multiple_scopes=true,$optional_params=false)
    {
    	$USC=new UserSubscriptionController($this->currentorg->org_id);
    
    	$query_params=$params;
    
    	$r_channels=$USC->getCommChannels();
    	$r_scopes=$USC->getCommScopes();
    	$r_priority=array('TRANS','BULK');
    
    	if( (!$optional_params && !isset($query_params['channel'])) || (isset($query_params['channel']) && !isset($r_channels[strtoupper($query_params['channel'])])))
    	{
    		$this->logger->error("Invalid channel passed for get/post_subscription");
    		throw new Exception(ErrorMessage::$customer['ERR_INVALID_CHANNEL'], ErrorCodes::$customer['ERR_INVALID_CHANNEL']);
    	}
    
    	if( (!$optional_params && !isset($query_params['priority'])) || (isset($query_params['priority']) && !in_array(strtoupper($query_params['priority']),$r_priority)))
    	{
    		$this->logger->error("Invalid priority passed for get/post_subscription");
    		throw new Exception(ErrorMessage::$customer['ERR_INVALID_PRIORITY'], ErrorCodes::$customer['ERR_INVALID_PRIORITY']);
    	}
    
    	if(isset($query_params['scope']))
    	{
    		$i_scopes=explode(",",$query_params['scope']);
    		if(!$multiple_scopes && count($i_scopes)>1)
    		{
    			$this->logger->error("Multiple scopes are passed which are not allowed");
    			throw new Exception(ErrorMessage::$customer['ERR_MULTIPLE_SCOPE'], ErrorCodes::$customer['ERR_MULTIPLE_SCOPE']);
    		}
    		foreach($i_scopes as $s)
    		{
    			if(!isset($r_scopes[strtoupper($s)]))
    			{
    				$this->logger->error("Invalid scope passed for get/post_subscription");
    				throw new Exception(ErrorMessage::$customer['ERR_INVALID_SCOPE'], ErrorCodes::$customer['ERR_INVALID_SCOPE']);
    			}
    		}
    	}
    
    	if(isset($query_params['is_subscribed']) && $query_params['is_subscribed']!="0" && $query_params['is_subscribed']!="1")
    	{
    		$this->logger->error("Invalid subscription status passed for get/post_subscription");
    		throw new Exception(ErrorMessage::$customer['ERR_INVALID_SUBSCRIPTION_STATUS'], ErrorCodes::$customer['ERR_INVALID_SUBSCRIPTION_STATUS']);
    	}
    
    	if(!empty($query_params['campaign_id']))
    	{
    		if(!is_numeric($query_params['campaign_id']))
    			throw new Exception(ErrorMessage::$customer['ERR_INVALID_CAMPAIGN_ID'], ErrorCodes::$customer['ERR_INVALID_CAMPAIGN_ID']);
    	}
    
    	if(!empty($query_params['outbox_id']))
    	{
    		if(!is_numeric($query_params['outbox_id']))
    			throw new Exception(ErrorMessage::$customer['ERR_INVALID_OUTBOX_ID'], ErrorCodes::$customer['ERR_INVALID_OUTBOX_ID']);
    	}
    
    }
    
}
?>
