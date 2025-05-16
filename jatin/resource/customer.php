<?php
require_once "resource.php";
require_once "apiController/ApiCustomerController.php";
require_once 'helper/MemcacheMgr.php';

/**
 * Handles all customer related api calls.
 * 	- Registering a customer
 *  - Updating a customer
 *  - Fetching details of a customer
 *
 *  Additional methods for clienteling api
 *  - Search
 *  - Interaction
 *  - Transactions
 *  - Notes
 *
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/customer",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class CustomerResource extends BaseResource{

	private $config_mgr;
	private $listener_mgr;

	function __construct()
	{
		parent::__construct();
		$this->config_mgr = new ConfigManager($this->currentorg->org_id);
	}


	public function process($version, $method, $data, $query_params, $http_method)
	{

		//$this->logger->("POST data: "+$data);
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
                    #throw new Exception(ErrorMessage::$api["FAIL"] . ", " . " ratelimit ", ErrorCodes::$api["FAIL"]);
					$result = $this->add($version, $data, $query_params);
					break;
				case 'update' :
                    #throw new Exception(ErrorMessage::$api["FAIL"] . ", " . " ratelimit ", ErrorCodes::$api["FAIL"]);
					$result = $this->update($version, $data, $query_params);
					break;
				case 'get' :

					$result = $this->get($version, $query_params);
					break;

				case 'update_identity' :
					$result = $this->update_identity($data);
					break;

				case 'search' :
					$result = $this->search($version, $query_params);
					break;

				case 'interaction':
					$result = $this->interaction($version, $query_params);
					break;

				case 'transactions':
					$result = $this->transactions($version, $query_params);
					break;

				case 'redemptions':
					$result = $this->redemptions($version, $query_params);
					break;

				case 'notes':
					$result = $this->notes($version, $data, $query_params, $http_method);
					break;

				case 'preferences':
					$result = $this->preferences($version, $data, $query_params, $http_method);
					break;

				case 'subscriptions':
					$result = $this->subscriptions($version, $data, $query_params, $http_method);
					break;

				case 'recommendations':
					$result = $this->recommendations( $version, $query_params );
					break;

				case 'referrals':
					$result = $this->referrals($version, $data, $query_params, $http_method);
					break;

				case 'coupons':
					$result=$this->coupons($version, $data, $query_params,$http_method);
					break;

				case 'tickets':
					$result=$this->tickets($version, $data, $query_params,$http_method);
					break;

				case 'trackers':
					$result = $this->trackers($version, $data, $query_params, $http_method);
					break;

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
	 * Adds a new customer in the system
	 *
	 * @param $data
	 * @param $query_params
	 */

        /**
         * @SWG\Model(
         * id = "CustomerRequest",
         * @SWG\Property(name = "customer", type = "array", items = "$ref:CustomerRegistrationDetails" )
         * )
         */

		/**
         * @SWG\Model(
         * id = "CustomerRegistrationDetails",
         * @SWG\Property(name = "user_id", type = "integer" ),
         * @SWG\Property(name = "mobile", type = "string" ),
         * @SWG\Property(name = "external_id", type = "string" ),
         * @SWG\Property(name = "email", type = "string" ),
         * @SWG\Property(name = "firstname", type = "string" ),
         * @SWG\Property(name = "lastname", type = "string" ),
         * @SWG\Property(name = "registered_on", type = "string" ),
         * @SWG\Property(name = "fraud_status", enum= "['MARKED','CONFIRMED','RECONFIRMED','NOT_FRAUD','INTERNAL']", required = false ),
         * @SWG\Property(name = "referral_code", type = "string"),
         * @SWG\Property(name = "custom_fields", type = "array", items =  "$ref:CustomFieldsList"),
         * @SWG\Property(name = "subscriptions", type = "array", items =  "$ref:SubscriptionList"),
         * @SWG\Property(name = "associate_details", type="AssocDetails"),
         * @SWG\Property(name = "type", enum= "['loyalty','non_loyalty']", required = false ),
         * @SWG\Property(name = "source", enum= "['instore','e-comm','newsletter']", required = false ),
         * description = "Atleast one of email/mobile/external id is needed"
         * )
         */

        /**
         * @SWG\Model(
         * id = "AssocDetails",
         * @SWG\Property(name = "code", type = "string" ),
         * @SWG\Property(name = "name", type = "string")
         * )
         */
		/**
         * @SWG\Model(
         * id = "CustomFieldsList",
         * @SWG\Property(name = "field", type = "CustomField", description = "org specific custom fields are created and the same can be provided for each customer" )
         * )
         * */

		/**
         * @SWG\Model(
         * id = "CustomField",
		 * @SWG\Property(name = "name", type = "string", required = true ),
		 * @SWG\Property(name = "value", type = "string", required = true )
         * )
         * */

		/**
         * @SWG\Model(
         * id = "SubscriptionList",
         * @SWG\Property(name = "subscription", type = "Subscription", description = "Subscription details for each channel and priority" )
         * )
         * */
	
		/**
         * @SWG\Model(
         * id = "Subscription",
		 * @SWG\Property(name = "priority", enum= "['TRANS', 'BULK']", required = true ),
		 * @SWG\Property(name = "scope", enum= "['ALL']", required = true ),
		 * @SWG\Property(name = "channel", enum = "['SMS', 'EMAIL']", required = true ),
		 * @SWG\Property(name = "is_subscribed", enum = "[1,0]", required = true )
         * )
         * */
	
		/**
         * @SWG\Model(
         * id = "RegistrationRoot",
         * @SWG\Property(
         * name = "root",
         * type = "CustomerRequest"
         * )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/customer/add.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Add customer",
         * @SWG\Parameter(
         * name = "request",
         * paramType="body",
         * type="RegistrationRoot"),
         * @SWG\Parameter(name = "user_id", paramType = "query", enum="['true', 'false']", type="string")
        * ))
        */
	private function add($version, $data, $query_params)
	{
		$should_return_user_id = strtolower($query_params['user_id']) == 'true' ? true : false;
		$status_code = 'SUCCESS';
		$customer_count = 0;
		$error_count = 0 ;
		$org_id = $this->org_id;
		$responses = array();
		$items['customer' ] = array();


		#$this->logger->debug("CustomerResource: Input data" . print_r($data, true));

		$customers =$data[ 'root' ][ 'customer' ];
		if(!isset($customers[0]))
		{
			$customers = array($customers);
		}
		global $gbl_item_count, $gbl_item_status_codes;
		$gbl_item_count = count($customers);
		$customer_count = $gbl_item_count;

		$arr_item_status_codes = array();
		if(is_array($customers))
		{
			foreach ( $customers as $customer )
			{

				try{

					//check batch limit
					ApiUtil::checkBatchLimit();

					$customerController = new ApiCustomerController();
					Util::resetApiWarnings();

					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $customer['mobile'],
					'email' => $customer['email'],
					'external_id' => $customer['external_id']
					) );
					global $gbl_country_code;
					if(isset($customer['country_code']))
					{
						$gbl_country_code = trim($customer['country_code']);
					}
					else
					{
						$gbl_country_code = '';
					}

                    if(isset($customer['type']))
                    {
                        $customer['type']=strtolower($customer['type']) == 'non_loyalty' ? 'non_loyalty' : 'loyalty' ;
                    }
                    else{
						$customer['type']='loyalty';
					}

					if(isset($customer['source']))
                    {
						$reg_source=strtolower($customer['source']);
						if($reg_source != "e-comm" && $reg_source !="newsletter")
							$customer['source']='instore';
                    }
					else{
						$customer['source']='instore';
					}

                    if($customer['type']=="loyalty") {
                        $customer = $customerController->validateInputIdentifiers($customer);
                    }
                    else{
                        $customer=$customerController->validateNonLoyaltyCustomer($customer);
                    }
					$is_registered = false;
					try{
						if($version == 'v1.1')
						{
							try{
								$user = UserProfile::getByData($customer);
                                //Here load can be changed for non loyalty, but keeping it same won;t give any issue
								$user->load(true);
								$is_registered = true;
							}
							catch(Exception $e)
							{
								$is_registered = false;
							}
						}

					}catch(Exception $e){
						$is_registered = false;
					}

					if($version == "v1")
					{
						$cm = new ConfigManager();
						try{
							if($customer['email'] && !$cm->getKey("CONF_USERS_IS_EMAIL_REQUIRED")){
								$userTest = UserProfile::getByEmail($customer['email']);
								if($userTest && $userTest->getLoyaltyId() >0)
								{
									$this->logger->debug("Register => Email id is already registered by some other user making it empty");
									Util::addApiWarning("Email $customer[email] is already occupied by some other User, ignoring it");
									$customer['email'] = '';
								}
							}
						}catch(Exception $e){
						}
						try{
							$userTest = null;
							if($customer['mobile'] && !$cm->getKey("CONF_USERS_IS_MOBILE_REQUIRED")){
								$userTest = UserProfile::getByMobile($customer['mobile']);
								// campaign registered user
								if($userTest && $userTest->getLoyaltyId() >0 )
								{
									$this->logger->debug("Register => Mobile id is already registered by some other user making it empty");
									Util::addApiWarning("Mobile $customer[mobile] is already occupied by some other User, ignoring it");
									$customer['mobile'] = '';
								}
							}
						}catch(Exception $e){
						}

					}

					if($version == 'v1.1' && $is_registered)
					{
						$this->logger->debug("Customer is Already Registered, going for Updation");
						//if EMAIL UNIQUE is set then check weather the user is already registered by email.
						//if user is already registered, then throw the exception.
						$cm = new ConfigManager();
							
						// Removed as the config is no more in use
						// 						if($cm->getKey("CONF_USERS_IS_EMAIL_UNIQUE"))
						// 						{
						// 							$temp_user = UserProfile::getByEmail($customer['email']);
						// 							if(!empty($customer['email']) && $temp_user && $temp_user->user_id != $user->user_id)
						// 							{
						// 								$this->logger->error("Email is Duplicate");
							// 								throw new Exception("ERR_DUPLICATE_EMAIL");
							// 							}
							// 						}
								
							$user = $customerController->updateCustomer($customer,$user);
							Util::saveLogForApiInputDetails(
							array(
							'mobile' => $user->mobile,
							'email' => $user->email,
							'external_id' => $user->external_id,
							'user_id' => $user->user_id
							) );
							
							ApiCacheHandler::triggerEvent("customer_update", $user->user_id);
							ApiUtil::mcUserUpdateCacheClear($user->user_id);
							
							/*
							try{
								$cache = MemcacheMgr::getInstance();
								$all_keys = $cache->getMembersOfSet(CacheKeysPrefix::$mc_admin_user_cache.$user->user_id);
								if(count($all_keys)>0)
									$cache->deleteMulti($all_keys);
							} catch ( Exception $e){
								$this->logger->debug("Error deleting customer profile cache set by mc");
							}
							 */ 
							
					}
					else
					{
						$this->logger->debug("Customer is not Registered, going for new Registration");
						$user = $customerController->register($customer);
						Util::saveLogForApiInputDetails(
						array(
						'mobile' => $user->mobile,
						'email' => $user->email,
						'external_id' => $user->external_id,
						'user_id' => $user->user_id
						) );
					}

					$error_key = $user->status_code;

					$success = ( in_array($error_key, array('ERR_LOYALTY_SUCCESS', 'ERR_CUSTOM_FIELD', 'INVALID_CUSTOM_FIELD'))
							? 'true' : 'false');
					$error_count = ( in_array($error_key, array('ERR_LOYALTY_SUCCESS', 'ERR_CUSTOM_FIELD', 'INVALID_CUSTOM_FIELD'))
							? $error_count : $error_count+1 );

					$register_status = array( 'success' => $success ,
							'code' => ErrorCodes::getCustomerErrorCode( $error_key ) ,
							'message' => ErrorMessage::getCustomerErrorMessage( $error_key ) );

					$user_id = '';

					if($should_return_user_id)
						$user_id = $user->user_id;

					// TODO : set the subscription status
					$effect = $GLOBALS['listener'];
					$effect['coupon_code'] = ApiUtil::trimCouponCode($effect['coupon_code']);

					$item = array( 'mobile' => (string) $user->mobile ,
							'email' => (string) $user->email ,
							'external_id' => (string) $user->external_id ,
							'registered_on' => (string) $user->registered_on,
							'item_status' => $register_status ,
							'side_effects' => array( 'effect' =>  $effect) );

					if($should_return_user_id)
						$item['user_id'] = $user_id;
					if(strtolower($version) == 'v1.1')
					{
						$item['firstname'] = $user->first_name;
						$item['lastname'] = $user->last_name;
						$item['lifetime_points'] = $user->lifetime_points;
						$item['lifetime_purchases'] = $user->lifetime_purchases;
						$item['loyalty_points'] = $user->loyalty_points;
						$item['current_slab'] = $user->slab_name;
						$item['tier_expiry_date'] = $user->slab_expiry_date;
						$item['updated_on'] = $user->updated_on;
						$item['type'] =$user->loyalty_type;
						$item['source']=$user->source;
					}

					$GLOBALS[ 'listener' ] = array();

				}catch(Exception $e)
				{
					$error_count++ ;
					$this->logger->error("Error while customer/add: ".$e->getMessage());
					$error_key = $e->getMessage();
					if($error_key=="ERR_BATCH_LIMIT_EXCEEDED")
						unset($user);
					$success = $error_key == 'ERR_LOYALTY_SUCCESS' ? 'true' : 'false';
					$register_status = array( 'success' => $success ,
							'code' => ErrorCodes::getCustomerErrorCode( $error_key ),
							'message' => ErrorMessage::getCustomerErrorMessage( $error_key ) );

					$item = array( 'mobile' => (string) $customer[ 'mobile' ] ,
							'email' => (string) $customer[ 'email' ] ,
							'external_id' => (string) $customer[ 'external_id' ] ,
							'item_status' => $register_status ,
							'side_effects' => $GLOBALS[ 'listener' ] );

					if(strtolower($version) == 'v1.1'
							&& $is_registered && isset($user))
					{
						$item = array(
								'mobile' => $user->mobile,
								'email' => $user->email,
								'external_id' => $user->external_id,
								'item_status' => $register_status,
								'side_effect' => $GLOBALS['listener'],
								'user_id' => '',
								'firstname' => $user->first_name,
								'lastname' => $user->last_name,
								'lifetime_points' => $user->lifetime_points,
								'lifetime_purchases' => $user->lifetime_purchases,
								'loyalty_points' => $user->loyalty_points,
								'current_slab' => $user->current_slab,
								'updated_on' => $user->updated_on,
							    'type' =>$user->loyalty_type,
							    'source' => $user->source
						);
					}
					/*
					 * adding user_id incase if customer update fails
					* requirement from Ticket:9977
					*
					* if customer/add returns no user_id (in case of failure),
					* in capillary client we are deleting that customer assuming that customer is not registered,
					* but in case of customer update failure,
					* we are not returning user_id of existing customer.
					* which leads to customer delete in client.
					*
					* so returning user_id if customer update is failed.
					*/
					if($should_return_user_id && strtolower($version)=='v1.1' && $is_registered && $user)
					{
						$this->logger->debug("customer updation failed, returning user_id");
						$item = array_merge($item, array("user_id" => $user->user_id));
					}
					else
					{
						unset($item['user_id']);
					}

				}
				if(strtolower($version) == 'v1')
				{
					if(isset($item['registered_on']))
						unset($item['registered_on']);

					if(isset($item['side_effects']['effect']))
					{
						$effects = $item['side_effects']['effect'];
						if(count($effects) > 0)
						{
							$new_effects = array();
							foreach ($effects as $effect)
							{
								$temp_effect = $effect;
								if( $temp_effect['type'] == SIDE_EFFECT_TYPE_SLAB ){

									continue;
								}
								if(isset($temp_effect['discount_value']))
									unset($temp_effect['discount_value']);
								$new_effects[] = $temp_effect;
							}
							$item['side_effects']['effect'] = $new_effects;
						}
					}
				}
				else if(strtolower($version) == 'v1.1')
				{
					if(isset($item['side_effects']['effect']))
					{
						$effects = $item['side_effects']['effect'];
						if(count($effects) > 0)
						{
							$new_effects = array();
							foreach ($effects as $effect)
							{
								$temp_effect = array();
								if(isset($effect['type']))
								{
									if($effect['type'] == SIDE_EFFECT_TYPE_VOUCHER)
									{
										$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_COUPON;
										$temp_effect['coupon_type'] = $effect['coupon_type'];
										$temp_effect['coupon_code'] = $effect['coupon_code'];
										$temp_effect['description'] = $effect['description'];
										$temp_effect['valid_till'] = $effect['valid_till'];
										$temp_effect['id'] = $effect['coupon_id'];
									}
									else if( $effect['type'] == SIDE_EFFECT_TYPE_DVS)
									{
										$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_COUPON;
										$temp_effect['coupon_type'] = SIDE_EFFECT_RESPONSE_TYPE_DVS;
										$temp_effect['coupon_code'] = $effect['coupon_code'];
										$temp_effect['discount_code'] = $effect['discount_code'];
										$temp_effect['discount_value'] = $effect['discount_value'];
										$temp_effect['description'] = $effect['description'];
										$temp_effect['valid_till'] = $effect['valid_till'];
										$temp_effect['id'] = $effect['id'];
									}
									else if( $effect['type'] == SIDE_EFFECT_TYPE_POINTS)
									{
										$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_POINTS;
										$temp_effect['awarded_points'] = $effect['awarded_points'];
										$temp_effect['total_points'] = $effect['total_points'];
									}
									else if( $effect['type'] == SIDE_EFFECT_TYPE_SLAB )
									{
										$temp_effect['type'] = SIDE_EFFECT_RESPONSE_TYPE_SLAB;
										$temp_effect['previous_slab_name'] = $effect['previous_slab_name'];
										$temp_effect['previous_slab_number'] = $effect['previous_slab_number'];
										$temp_effect['upgraded_slab_name'] = $effect['upgraded_slab_name'];
										$temp_effect['upgraded_slab_number'] = $effect['upgraded_slab_number'];
									}
								}
								$new_effects[] = $temp_effect;
							}
							$item['side_effects']['effect'] = $new_effects;
						}
					}
				}
				$apiWarnings = Util::getApiWarnings();
				if($apiWarnings !== null)
					$item['item_status']['message'] .= ", $apiWarnings";
				array_push( $items[ 'customer' ] , $item );

				$arr_item_status_codes[] = $register_status['code'];
			}
		} // End of foreach customer
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);

		if( $error_count > 0 )
			$status_code = ( $customer_count == $error_count )? 'FAIL' : 'PARTIAL_SUCCESS';

		$status_success = ( $status_code == 'FAIL' )? 'false' : 'true';

		$result = array( 'status' =>  array( 'success' => $status_success ,'code' => ErrorCodes::$api[ $status_code ],
				'message' => ErrorMessage::$api[ $status_code ] ),
				'customers' => $items );
		//echo print_r($result,true);
		return $result;

	}

		/**
         * @SWG\Api(
         * path="/customer/update.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Add/Update customer",
		* nickname = "customer update",
         * @SWG\Parameter(
         * name = "request",
         * paramType="body",
         * type="RegistrationRoot")
        * ))
        */
	
	/**
	 * Updates a customer in the system
	 *
	 * @param $data
	 * @param $query_params
	 */

	private function update($version, $data, $query_params)
	{
		$should_return_user_id = strtolower( $query_params['user_id'] ) == 'true' ? true : false;
		$customerController = new ApiCustomerController();

		if( $query_params[ 'scope' ] == 'app' ){
			return $customerController->updateUserDetailsForApp( $data );
		}else{

			//commented for api merging
			//return $customerController->updateCustomer( $data );

			$status_code = 'SUCCESS';
			$customer_count = 0;
			global $error_count;
			$error_count = 0;
			$org_id = $this->org_id;
			$responses = array();
			$items[ 'customer' ] = array();
			$customers = $data[ 'root' ][ 'customer' ];
			if(!isset($customers[0]))
			{
				$customers = array($customers);
			}
			$auth = Auth::getInstance();
			$cm = new ConfigManager($org_id);

			global $gbl_item_count, $gbl_item_status_codes;
			$gbl_item_count = count($customers);
			$arr_item_status_codes = array();
			foreach ( $customers as $key => $customer )
			{
				Util::resetApiWarnings();
				Util::saveLogForApiInputDetails(
				array(
				'mobile' => $customer['mobile'],
				'email' => $customer['email'],
				'external_id' => $customer['external_id']
				) );
				$this->logger->debug("Customer::UpdateCustomer => ".print_r($customer,true));
				$customer_count++;
				$error_key = 'ERR_UPDATE_SUCCESS';
				try
				{
					if(isset($customer['gender']))
						$customer['sex'] = $customer['gender'];
					global $gbl_country_code;
					if(isset($customer['country_code']))
					{
						$gbl_country_code = trim($customer['country_code']);
					}
					else
					{
						$gbl_country_code = '';
					}

					if(isset($customer['type']))
					{
						$customer['type']=strtolower($customer['type']) == 'non_loyalty' ? 'non_loyalty' : 'loyalty' ;
					}
					else{
						$customer['type']='loyalty';
					}

					$controller = new ApiCustomerController();
					$customer = $controller->validateInputIdentifiers($customer);
					$user = $controller->updateCustomer($customer);
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $user->mobile,
					'email' => $user->email,
					'external_id' => $user->external_id,
					'user_id' => $user->user_id
					) );
					
					//triggering the cache clear
					ApiCacheHandler::triggerEvent("customer_update", $user->user_id);
					ApiUtil::mcUserUpdateCacheClear($user->user_id);
					/*
					try{
						$cache = MemcacheMgr::getInstance();
						$all_keys = $cache->getMembersOfSet(CacheKeysPrefix::$mc_admin_user_cache.$user->user_id);
						if(count($all_keys)>0)
							$cache->deleteMulti($all_keys);
					} catch ( Exception $e){
						$this->logger->debug("Error deleting customer profile cache set by mc");
					}
					*/
					//$user = new UserProfile($customer);

					if ( $user )
					{

						$success = ( $error_key == 'ERR_UPDATE_SUCCESS' ) ? 'true' : 'false';
						$error_count = ( $error_key == 'ERR_UPDATE_SUCCESS' ) ? $error_count : $error_count+1 ;


						$register_status = array(
								'success' => $success ,
								'code' => ErrorCodes::getCustomerErrorCode( $error_key ) ,
								'message' => ErrorMessage::getCustomerErrorMessage( $error_key ) );

						$item_array = $controller->populateCustomerDetails( $user , $register_status );
						//unsetting 'sex' for unifing get, add and update response.


						$item_array = array_merge($item_array, $user->getHash());
						$item_array['tier_expiry_date'] = $user->slab_expiry_date;

						if($should_return_user_id)
							$item_array['user_id'] = $item_array['id'];

						if(isset($item_array['id']))
							unset($item_array['id']);

						if($version == 'v1')
						{
							unset($item_array['sex']);
						}
						else if($version == 'v1.1')
						{
							$item_array['gender'] = $item_array['sex'];
							unset($item_array['sex']);
							if(isset($customer['fraud_status']))
								$item_array['fraud_status']=$customer['fraud_status'];
							if(isset($customer['test_control_status']))
								$item_array['test_control_status']=$customer['test_control_status'];
						}
							
						$strWarnings = Util::getApiWarnings();
						if($strWarnings !== null)
						{
							$item_array['item_status']['message'] .= ", $strWarnings";
						}
						array_push( $items[ 'customer' ] , $item_array );
					}

				}catch(Exception $e)
				{
					$error_count++ ;
					$register_status = array(
							'success' => 'false',
							'code' => ErrorCodes::getCustomerErrorCode( $e->getMessage() ),
							'message' => ErrorMessage::getCustomerErrorMessage( $e->getMessage() )
					);
					$strWarnings = Util::getApiWarnings();
					if($strWarnings !== null)
					{
						$register_status['message'] .= ", $strWarnings";
					}
					array_push( $items[ 'customer' ] , array( 'mobile' => (string) $customer[ 'mobile' ] ,
					'email' => (string) $customer[ 'email' ] , 'external_id' => (string) $customer[ 'external_id' ] ,
					'item_status' => $register_status  )
					);
				}
				$arr_item_status_codes[] = $register_status['code'];
			} //END OF foreach customer
			$gbl_item_status_codes = implode(",", $arr_item_status_codes);
			if( $error_count > 0 )
				$status_code = ( $customer_count == $error_count )? 'FAIL' : 'PARTIAL_SUCCESS';

			$status_success = ( $status_code == 'FAIL' )? 'false' : 'true';

			$result = array(
					'status' =>  array( 'success' => $status_success ,'code' => ErrorCodes::$api[ $status_code ],
							'message' => ErrorMessage::$api[ $status_code ] ),
					'customers' => $items
			);

			return $result;

		}
	}

	/**
	 * Fetches a list of customers
	 *
	 * @param $query_params
	 */

        /**
         * @SWG\Api(
         * path="/customer/get.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get customer details based on identity",
         *    @SWG\Parameter(
         *    name = "id",
         *    type = "integer",
         *    paramType = "query",
         *    description = "Customer id"
         *    ),
         *    @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *	  description = "Customer mobile number"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         *	  description = "Customer email id"
         *    ),
         *    @SWG\Parameter(
         *    name = "external_id",
         *    type = "string",
         *    paramType = "query",
         *	  description = "Customer mobile number"
         *    ),
         *    @SWG\Parameter(
         *    name = "scope",
         *    type = "string",
         *    paramType = "query",
         *	  description = "Customer scope"
         *    ),
         *    @SWG\Parameter(
         *    name = "user_id",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. If true, returns user_id in response. Default false"
         *    ),
         *    @SWG\Parameter(
         *    name = "segments",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true doesn't return segments"
         *    ),
         *    @SWG\Parameter(
         *    name = "subscriptions",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns all subscriptions for the customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "member_care_access",
         *    type = "boolean",
         *    paramType = "query"
         *    ),
         *    @SWG\Parameter(
         *    name = "next_slab",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. If true, returns next slab information"
         *    ),
         *    @SWG\Parameter(
         *    name = "ndnc_status",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Set as true to get ndnc status"
         *    ),
         *    @SWG\Parameter(
         *    name = "optin_status",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Set as true to get optin status"
         *    ),
         *    @SWG\Parameter(
         *    name = "expiry_schedule",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns expiry schedule"
         *    ),
         *    @SWG\Parameter(
         *    name = "slab_history",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns slab history"
         *    ),
         *    @SWG\Parameter(
         *    name = "expired_points",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns expired points"
         *    ),
         *    @SWG\Parameter(
         *    name = "points_summary",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns points summary"
         *    ),
         *    @SWG\Parameter(
         *    name = "promotion_points",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Setting as true returns promotion points"
         *    ),
         *    @SWG\Parameter(
         *    name = "membership_retention_criteria",
         *    type = "boolean",
         *    paramType = "query",
         *	  description = "Optional. Set true to get membership retention criteria"
         *    )
        * ))
        */
	private function get($version, $query_params)
	{

		$should_return_user_id = strtolower($query_params['user_id']) == 'true' ? true : false;
		$should_return_segments = isset($query_params['segments']) && in_array(strtolower($query_params['segments']), array('true' , 1) )  ? true : false;
		$should_return_subscriptions = in_array(strtolower($query_params['subscriptions']), array('true' , 1) )? true : false;

		$should_check_member_care_access = in_array(strtolower($query_params["member_care_access"]), array(1, "1", true, "true"), true) ? true : false;
		

		$customerController = new ApiCustomerController();

		if( $query_params[ 'scope' ] == 'app' ){
			$mobile = $query_params['mobile'];
			if(!(trim($mobile))){
				$this->logger->debug("No mobile number passed as input: $mobile");
				return;
			}
			return $customerController->getUserDetailsForApp( $mobile );
		}else{

			$status_code = 'SUCCESS';
			$org_id = $this->org_id;
			$responses = array();
			$items[ 'customer' ] = array();

			$auth = Auth::getInstance();
			/*****/
			$customers = $query_params;
			if( $customers[ 'mobile' ] )
				$identifier = 'mobile';
			elseif( $customers[ 'external_id' ] )
			$identifier = 'external_id';
			elseif( $customers[ 'email' ] )
			$identifier = 'email';
			else if($customers['id'])
				$identifier = 'id';
			else
			{
				$error_key = 'ERR_NO_IDENTIFIER';
				$this->logger->error("No Identifier Found for customer get");
			}

			$identifier = trim($identifier);
			if(empty($identifier)){
				$this->logger->error("Invalid input, identifier is empty");
				throw new Exception(ErrorMessage::$api['INVALID_INPUT'], ErrorCodes::$api['INVALID_INPUT']);
			}

			$customer_array = StringUtils::strexplode( ',' , $customers[ $identifier ] );
			global $error_count;
			$error_count = 0;

			global $gbl_item_count, $gbl_item_status_codes;
			$gbl_item_count = count($customer_array);
			$customer_count = $gbl_item_count;

			$arr_item_status_codes = array();

			$identifier_count = 0;
			if(isset($customers['mobile']))
				$identifier_count++;
			if(isset($customers['external_id']))
				$identifier_count++;
			if(isset($customers['email']))
				$identifier_count++;
			if(isset($customers['id']))
				$identifier_count++;

			Util::resetApiWarnings();
			foreach ( $customer_array as $key => $customer_identifier )
			{
				try
				{
					if($identifier_count > 1)
					{
						$this->logger->debug("found multiple identifiers, not treating as batch request");

						$input = array();
						if(isset($customers['mobile']))
						{
							$input['mobile'] = explode(",", $customers['mobile']);
							$input['mobile'] = $input['mobile'][0];
						}
						if(isset($customers['external_id']))
						{
							$input['external_id'] = explode(",", $customers['external_id']);
							$input['external_id'] = $input['external_id'][0];
						}
						if(isset($customers['email']))
						{
							$input['email'] = explode(",", $customers['email']);
							$input['email'] = $input['email'][0];
						}
						if(isset($customers['id']))
						{
							$input['id'] = explode(",", $customers['id']);
							$input['id'] = $input['id'][0];
						}
					}
					else
					{
						$input[$identifier] = $customer_identifier;
					}
					$loyalty_err_key = 'ERR_GET_SUCCESS';

					$controller = new ApiCustomerController();
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $input['mobile'],
					'email' => $input['email'],
					'external_id' => $input['external_id']
					) );
					$user = $controller->getCustomers($input, true);

					if(!$user)
						throw new Exception('ERR_USER_NOT_REGISTERED');

					
					if($should_check_member_care_access){
						if($this->currentuser->getType() == "ADMIN_USER"){
							try{
								$all_tills = ApiUtil::getTillsForAdminUser($this->currentuser->getId(), $this->currentuser->org_id);
								if(!in_array($user->registered_by, $all_tills))
									throw new Exception('ERR_USER_PERMISSION_DENIED');
							} catch( Exception $e){
								
								if ($e->getMessage() == "ERR_USER_PERMISSION_DENIED"){
									$this->logger->debug("Permission denied for the current user to view the customer");
									throw new Exception('ERR_USER_PERMISSION_DENIED');
								}
								else
									$this->logger->debug("User is admin user and has full access over the org");
							}
							
						}
					}
					$item_array = $user->getHash();
					$store_timezone = $this->currentuser->getStoreTimeZoneLabel();
					if( !isset( $store_timezone ) )
					{
						$store_timezone=$this->currentorg->getOrgTimeZoneLabel();
						$this->logger->debug("Store timezone not set. using org timezone: $store_timezone");
					}

					$item_array['updated_on'] = Util::convertOneTimezoneToAnotherTimezone(
							$item_array['updated_on'],
							date_default_timezone_get(),
							$store_timezone );

					if($version == 'v1.1')
					{
						if($user->is_merged)
							$item_array['survivor_account_retrieved']='true';

						$item_array['gender'] = $user->sex;
						$item_array['registered_by'] = $user->registered_by;
						$item_array['registered_store']=array('code'=>'','name'=>'');
						$item_array['registered_till']=array('code'=>'','name'=>'');
						$item_array['fraud_details']=array(
								'status'=>'NONE',
								'marked_by'=>'',
								'modified_on'=>''
						);
						$fraud_details=$controller->getFraudDetails($user->user_id);
						if(!empty($fraud_details))
						{
							$item_array['fraud_details']['status']=$fraud_details['status'];
							$item_array['fraud_details']['marked_by']=$fraud_details['marked_by'];
							$item_array['fraud_details']['modified_on']=$fraud_details['modified_on'];
						}

						if(isset($query_params['tracker_info']) && strtolower($query_params['tracker_info']=="true"))
						{
							$item_array['trackers']=$controller->getTrackerValue($user->user_id);
						}

						if(isset($query_params['ndnc_status']) && strtolower($query_params['ndnc_status'])=="true")
							$item_array['ndnc_status']=$controller->getNDNCStatus($user->user_id);
							
						if(isset($query_params['optin_status']) && strtolower($query_params['optin_status'])=="true")
							$item_array['optin_status']=$controller->getOptinStatus($user->mobile);
							
						if(isset($query_params['expiry_schedule']) && strtolower($query_params['expiry_schedule'])=="true")
						{
							$expiry_schedule=$controller->getExpirySchedule($user->user_id);
							$item_array['expiry_schedule']['schedule']=array();
							$today = strtotime("today 00:00:00");
							$pointsExpArr = array();
							foreach($expiry_schedule as $sch)
							{
								if($sch['points']==0 || $today > $sch['expiry_date'])
									continue;
								$dateString = date('Y-m-d',$sch['expiry_date']);
								if($pointsExpArr[$dateString])
									$pointsExpArr[$dateString]["points"] += $sch['points'];
								else 
									$pointsExpArr[$dateString] = array( "points" => $sch['points'], "expiry_date" =>$dateString);
							}
							ksort($pointsExpArr);
							if($pointsExpArr)
								$item_array['expiry_schedule']['schedule'] = array_values($pointsExpArr);
							unset($pointsExpArr);
							if(empty($item_array['expiry_schedule']['schedule']))
								unset($item_array['expiry_schedule']['schedule']);
						}
							
						if(isset($query_params['expired_points']) && strtolower($query_params['expired_points'])=="true")
						{
							$expired_points=$controller->getExpiredPoints($user->user_id);
							$item_array['expired_points']['points']=array();
							$pointsExpArr = array();
							foreach($expired_points as $exp_points)
							{
								if($exp_points->pointsDeducted > 0 )
								{
									$dateString = date('Y-m-d',strtotime($exp_points->pointsDeductedOn));
									if($pointsExpArr[$dateString])
										$pointsExpArr[$dateString]["points"] += $exp_points->pointsDeducted;
									else 
										$pointsExpArr[$dateString] = array( "points" => $exp_points->pointsDeducted, "expired_on" =>$dateString);
									
									//$item_array['expired_points']['item'][]=array(
									///	'points'=>$exp_points->pointsDeducted,
									//	'expired_on'=>date("Y-m-d",strtotime($exp_points->pointsDeductedOn))
									//);
								}
							}
							krsort($pointsExpArr);
							if($pointsExpArr)
								$item_array['expired_points']['item'] = array_values($pointsExpArr);
							unset($pointsExpArr);
							if(empty($item_array['expired_points']['points']))
								unset($item_array['expired_points']['points']);
						}
							
						if(isset($query_params['slab_history']) && strtolower($query_params['slab_history'])=="true")
						{
							$slab_history=$controller->getSlabUpgradeHistory($user->user_id);
							$item_array['slab_history']['history']=array();
							foreach($slab_history as $history)
							{
								$item_array['slab_history']['history'][]=array(
										'to'=>$history['to_slab'],
										'from'=>$history['from_slab'],
										'store'=>array(
												'code'=>$history['store_code'],
												'name'=>$history['store_name']
										),
										'type'=>$history['type'],
										'changed_on'=>date('c',$history['date']),
										'notes'=>$history['notes']
								);
							}
							if(empty($item_array['slab_history']['history']))
								unset($item_array['slab_history']['history']);
						}
							
						if(isset($query_params['points_summary']) && strtolower($query_params['points_summary'])=="true")
						{
							$ps=$controller->getPointsSummary($user->user_id);
							$item_array['points_summary']=array(
									'expired'=>$ps['expired'],
									'redeemed'=>$ps['redeemed'],
									'adjusted'=>$ps['adjusted'],
									'returned'=>$ps['returned']
							);
							$item_array['tier_expiry_date'] = $ps['tier_expiry_date'];
						}

							
						if(isset($query_params['promotion_points']) && strtolower($query_params['promotion_points'])=="true")
							$item_array['promotion_points']=$controller->getPromotionPoints($user->user_id);
						
						if(isset($query_params['membership_retention_criteria']) && strtolower($query_params['membership_retention_criteria'])=="true")
							$item_array['membership_retention_criteria']=$controller->getTierDowngradeRetentionCriteria($user->user_id);
                					
					}

					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $user->mobile,
					'email' => $user->email,
					'external_id' => $user->external_id,
					'user_id' => $user->user_id
					) );

					if(strtolower($query_params['next_slab']) == 'true')
					{
						try{

							$pesC = new PointsEngineServiceController();

							$summary = $pesC->getPointsSummaryForCustomer($this->currentorg->org_id, $user->getUserId());
							$item_array['next_slab'] = $summary->nextSlabName;
							$item_array['next_slab_serial_number'] = $summary->nextSlabSerialNumber;
							$item_array['next_slab_description'] = $summary->nextSlabDescription;
						}
						catch(Exception $e)
						{
							$this->logger->debug("Error While getting Points Summary: ".$e->getMessage());
							$item_array['next_slab'] = '';
							$item_array['next_slab_serial_number'] = 0;
							$item_array['next_slab_description'] = "Info Not Available";
						}
					}
					if($should_return_user_id)
						$item_array['user_id'] = $item_array['id'];

					//removing fields that we don't want to expose
					if(isset($item_array['id']))
						unset($item_array['id']);
					if(isset($item_array['username']))
						unset($item_array['username']);

					$this->logger->info("CustomerResource:- Customer Item: ".print_r($item_array, true));
					//Customer Net Promoter score
					$survey_manager = new SurveyManager();
					$customer_satisfaction_details = $survey_manager->getCustomerSatisfaction( $this->currentorg->org_id, $user->user_id );
					$item_array['current_nps_status'] = $customer_satisfaction_details['recent_nps_score'];

					$item_array[ 'custom_fields' ]['field'] = array();
					$custom_fields_array = $user->getCustomFieldsData();
					$item_array[ 'segments' ] = array();
					$item_array[ 'custom_fields' ]['field'] = array_merge($item_array[ 'custom_fields' ]['field'], $custom_fields_array);
					$item_array[ 'transactions' ][ 'transaction' ] = array();
					$transactions = $user->getlastfewTransactions();
					$item_array[ 'transactions' ][ 'transaction' ] =  array_merge($item_array[ 'transactions' ][ 'transaction' ],$transactions);

					//Pushing coupons for user
					$item_array[ 'coupons' ][ 'coupon' ] = array();
					$coupons = $user->getIssuedVouchers();
					$this->logger->debug("CustomerResource:- GetIssuedVouchers Result: ".print_r($coupons,true));
					if(isset($coupons) && count($coupons)>0)
						$item_array['coupons']['coupon'] = array_merge($item_array[ 'coupons' ][ 'coupon' ],$coupons);

					$notes = $controller->getNotes($input);
					$item_array['notes'] = $notes['notes'];

					if($should_return_subscriptions)
					{
						require_once "business_controller/UserSubscriptionController.php";
						$USC=new UserSubscriptionController($this->currentorg->org_id);
						$subscriptionDetailsArr = array("subscription"=>array( array("user_id" => $user->user_id) ));
						$subscription_status=$USC->getAllSubscriptions($user->user_id);
						$subscription_details=$this->formatSubscriptionGet($subscription_status, $subscriptionDetailsArr);
						$item_array["subscription"] = $subscriptionDetailsArr["subscription"];
					}

					$register_status = array(
							'success' => $loyalty_err_key == 'ERR_GET_SUCCESS' ? 'true' : 'false'  ,
							'code' => ErrorCodes::getCustomerErrorCode( $loyalty_err_key ) ,
							'message' => ErrorMessage::getCustomerErrorMessage( $loyalty_err_key )
					);
					if($user->is_merged)
						$register_status['message'].=', Survivor account is retrieved for the requested merge victim';
					$item_array['item_status'] = $register_status;

					if($version == 'v1')
					{
						unset( $item_array[ 'segments' ] );
						unset( $item_array['current_nps_status'] );

						//unsettin created date in coupons for v1
						if( isset( $item_array['coupons']['coupon'] ) ){

							foreach( $item_array['coupons']['coupon'] as $key => $coupon ){

								unset( $item_array['coupons']['coupon'][$key]['created_date'] );
							}
						}
					}
					else if($version == 'v1.1')
					{

						if($should_return_segments)
						{
							//Get User Segment Mapping
							$this->logger->debug( "Calling Segmentation engine: getUserMappings with user_ids "
							.print_r( $user->user_id, true )
							." org_id "
							.$this->currentorg->org_id );
							$user_mapping = $customerController->getUserSegmentMapping( $this->currentorg->org_id, $user->user_id );
							if( !$user_mapping ){

								$this->logger->error( "Error while getting Customer Segment mapping" );
							}else{

								// 						$this->logger->debug( "Segmentation engine call result: ".print_r( $user_mapping, true ) );
								$i=0;
								$user_segments = array();

								//building segment tag
								$segment_mapping_array = $user_mapping->mapping;
								$segments_array = array();
								foreach ( $segment_mapping_array as $segment ){

									$segment_tag = array(
										'name' => $segment->segmentName,
										'type' => $segment->segmentType
									);
									$segment_values = array();
									foreach ( $segment->values as $value )
									array_push( $segment_values, array( 'name' => $value->name, 'description' => $value->description ) );
									$segment_tag['values']['value'] = $segment_values;
									array_push( $segments_array, $segment_tag );
								}
							}

							//adding segments tag to each customer response
							$item_array['segments'] = array();
							$item_array['segments']['segment'] = $segments_array ;
						}
						else 
							unset($item_array['segments'] );

						if($item_array['registered_by'] > 0)
						{
							$storeTillController = new ApiStoreTillController();
							$store_info = $storeTillController->getInfoDetails($item_array['registered_by']);
							if(isset($store_info[0]) && isset($store_info[0]['parent_store']))
							{
								$item_array['registered_by'] = $store_info[0]['parent_store'];
								$item_array['registered_store']=array(
										'code'=>$store_info[0]['parent_code'],
										'name'=>$store_info[0]['parent_store'],
								);
								$item_array['registered_till']=array(
										'code'=>$store_info[0]['code'],
										'name'=>$store_info[0]['name'],
								);
									
							}
							else
							{
								$item_array['registered_by'] = '';
							}
						}
						else
						{
							$item_array['registered_by'] = "";
						}

						if(isset($item_array['transactions']['transaction']) && count($item_array['transactions']['transaction']) > 0)
						{
							$transactions = $item_array['transactions']['transaction'];
							$new_transactions = array();
							foreach($transactions as $transaction)
							{
								$temp_transaction = array();
								$temp_transaction['id'] = $transaction['id'];
								$temp_transaction['number'] = $transaction['bill_number'];
								$row = ApiTransactionController::isReturnedTransaction($transaction['id'], $this->currentorg->org_id, $user->user_id);
								$temp_transaction['type'] = isset($row['id']) && $row['id'] > 0 ? 'RETURN' : 'REGULAR';
								$temp_transaction['created_date'] = $transaction['created_date'];
								$temp_transaction['store'] = $transaction['store'];

								$new_transactions [] =$temp_transaction;
							}
							$item_array['transactions']['transaction'] = $new_transactions;
						}
					}

					$arr_item_status_codes[] = $register_status['code'];
				}catch(Exception $e){

					$error_key = $e->getMessage();
					if(!isset($error_key))
						$error_key = 'ERR_NOT_REGISTERED';
					$error_count++;

					if( !$customer_identifier )
						$error_key = 'ERR_NO_IDENTIFIER';
					$this->logger->debug("Exception Key: "+$error_key);
					$register_status = array(
							'success' => 'false',
							'code' => ErrorCodes::getCustomerErrorCode( $error_key ),
							'message' => ErrorMessage::getCustomerErrorMessage( $error_key )
					);

					$warnings=Util::getApiWarnings();
					if(!empty($warnings))
						$register_status['message'].=", $warnings";
						
					$item_array = array( $identifier => (string) $customer_identifier ,
							'item_status' => $register_status  );

				}
				array_push( $items['customer'] , $item_array );
			} //END OF foreach customer

			$gbl_item_status_codes = implode(",", $arr_item_status_codes);
			$api_status = ( $error_count == $customer_count ) ?
			'FAIL' : ( ( $error_count == 0 ) ? 'SUCCESS' : 'PARTIAL_SUCCESS' );
			$api_success = ( $error_count == $customer_count ) ? 'false' : 'true';

			$result = array(
					'status' =>  array( 'success' => $api_success ,'code' => ErrorCodes::$api[ $api_status ],
							'message' => ErrorMessage::$api[ $api_status ] ),
					'customers' => $items
			);

			return $result;

			//return $customerController->getCustomers( $query_params );
		}
	}


	/**
	 * Fetches a list of customers
	 *
	 * @param $query_params
	 */
	
	/**
	 * @SWG\Model(
	 * id = "CustomerIdentityDetails",
	 * @SWG\Property(name = "identifier", type = "string", required = true ),
	 * @SWG\Property(name = "old_value", type = "string" , required = true),
	 * @SWG\Property(name = "new_value", type = "string" , required = true),
	 * description = "identifier can be any of the 'mobile', 'email', 'external_id'. "
	 * )
	 */

	/**
	 * @SWG\Model(
	 * id = "CustomerUpdateIdneitytRequest",
	 * @SWG\Property(name = "customer", type = "array", items = "$ref:CustomerIdentityDetails" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "UpdateIdentityRoot",
	 * @SWG\Property(
	 * name = "root",
	 * type = "CustomerUpdateIdneitytRequest"
	 * )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/customer/update_identity.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Updates Identity of customer",
		* nickname = "customer update identity",
		* @SWG\Parameter(
		* name = "request",
		* paramType="body",
		* type="UpdateIdentityRoot")
		* ))
		*/
	private function update_identity($data)
	{
		$this->logger->debug("Updating identity of customer: " . print_r($data, true));

		$customer_cntrl = new ApiCustomerController();
		return $customer_cntrl->updateCustomerIdentity($data);
	}

	/**
	 * Searches the Customer depending on the query parameters.
	 * @param array $query_params
	 */
	
	/**
	 * @SWG\Api(
	 * path="/customer/search.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Advanced search for customers",
	 *    @SWG\Parameter(
	 *    name = "q",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "query string for search, This follows specific query grammer"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "start",
	 *    type = "integer",
	 *    paramType = "query",
	 * description = "Customer id from which customer search need to do"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "rows",
	 *    type = "integer",
	 *    paramType = "query",
	 * description = "Number of customers that needs to be returned in response"
	 *    )
	 * ))
	 */
	private function search($version, $query_params)
	{
		include_once 'apiController/ApiSearchController.php';
		$search_cntrl = new ApiSearchController('users');
		$query = $query_params['q'];
		$start = $query_params['start'];
		$rows = $query_params['rows'];
		include_once 'helper/StringUtils.php';
		
		$should_check_member_care_access = in_array(strtolower($query_params["member_care_access"]), array(1, "1", true, "true"), true) ? true : false;
        
		if($should_check_member_care_access){
			 
			if($this->currentuser->getType() == "ADMIN_USER"){
				try{
					$all_tills = ApiUtil::getTillsForAdminUser($this->currentuser->getId(), $this->currentuser->org_id);
				} catch(Exception $e) {
					$this->logger->debug("User is a org level user");
					$is_primary = ( StringUtils::strriposition($query, BaseQueryBuilder::$PRIMARY_SEARCH) === false ) ? false : true;
					$results = $search_cntrl->searchCustomers($query, $start, $rows, $is_primary);
					return $results;
				}
				
				$start_till = 0;
				$offset = 500;
				$final_results = array();
				$final_results["count"] = 0 ;
				$final_results["results"] = array();
				$final_results["results"]["item"] = array();
				
				while(True){
					if($start_till>=count($all_tills))
						break;
					if(count($final_results["results"]["item"])>10)
						break;
					$batch_query = '';
					$batch_query = "(" . $query;
					$batch_query .="|registered_store:IN:";
					
					$arr = array_slice($all_tills, $start_till, $offset);
					
					foreach($arr as $till){
						$batch_query .= "$till;";
					}
					
					$batch_query = rtrim($batch_query, ";");
					$batch_query .= ")";	
					
					$is_primary = ( StringUtils::strriposition($batch_query, BaseQueryBuilder::$PRIMARY_SEARCH) === false ) ? false : true;

					$results = $search_cntrl->searchCustomers($batch_query, $start, $rows, $is_primary);
					$final_results["start"] = $start;
					
					$final_results['count'] = $final_results['count'] + $results['customer']['count'];
            		$final_results["results"]["item"] = array_merge($final_results["results"]["item"], $results['customer']["results"]["item"]); 
					
					$start_till = $start_till + $offset;
					
				}
				$final_results["rows"] = count($final_results["results"]["item"]);
				$response = array(
                            'status' =>  array( 'success' => 'true' ,'code' => ErrorCodes::$api[ 'SUCCESS' ],
                            'message' => ErrorMessage::$api[ 'SUCCESS' ] 
                                              ),
                            'customer' => $final_results
                             );
				return $response;
				//batching needs to be added		 
			}
		}
		else{
			$is_primary = ( StringUtils::strriposition($query, BaseQueryBuilder::$PRIMARY_SEARCH) === false ) ? false : true;
	
			$results = $search_cntrl->searchCustomers($query, $start, $rows, $is_primary);
			return $results;
		}

	}

	  /**
         * @SWG\Api(
         * path="/customer/interaction.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get communcations set out to customer",
        *     nickname = "Customer Communications",
         *    @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *    description = "Mobile number of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "external_id",
         *    type = "string",
         *    paramType = "query",
         * description = "External Id of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         * description = "Email of customer"
         *    )
        * ))
        */
	
	/**
	 *
	 * @param array $query_params
	 */
	private function interaction($version, $query_params)
	{

		$api_status_code = "SUCCESS";
		$customer_cntrl = new ApiCustomerController();

		$customers = $query_params;

		if($customers[ 'network' ])
			$network = $customers['network'];

		if($customers[ 'type' ])
			$type = $customers['type'];

        $identifier = $this->getCustomerIdentifierType($customers);

		try
		{
			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $customers['mobile'],
			'email' => $customers['email'],
			'external_id' => $customers['external_id']
			) );
			$result = $customer_cntrl->getCustomerInteraction($version, array($identifier => $customers[ $identifier ]),$type , $network);
			if(!$result)
				throw new Exception('ERR_LOYALTY_NOT_REGISTERED');

			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $result['mobile'],
			'email' => $result['email'],
			'external_id' => $result['external_id'],
			'user_id' => $result['id']
			) );

			$api_status = array(
					"success" => "true",
					"code" => ErrorCodes::$api[$api_status_code],
					"message" => ErrorMessage::$api[$api_status_code]
			);

		}
		catch(Exception $e){

			$error_key = $e->getMessage();
			$this->logger->error( "CustomerResource::series()  Error: ".ErrorMessage::$coupon[ $e->getMessage() ]);
			throw new Exception( ErrorMessage::$api['FAIL'] .", ". ErrorMessage::$customer[$error_key], ErrorCodes::$api['FAIL']);
		}

		return 	array(
				"status" => $api_status,
				"customer" => $result
		);
	}

	/**
	 * Returns the purchase history summary.
	 * The customer get will return just basic details.
	 * This will return history based on further input parameters
	 * @param array $query_params
	 */
        
        /**
         * @SWG\Api(
         * path="/customer/transactions.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get bills of a customer",
         * @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *    description = "Mobile number of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "external_id",
         *    type = "string",
         *    paramType = "query",
         * description = "External Id of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         * description = "Email of customer"
         *    )))
        **/
        
	private function transactions($version, $query_params)
	{
		$api_status_code = 'SUCCESS';
		$customerController = new ApiCustomerController();

		$customer = $query_params;
		$identifier = $this->getCustomerIdentifierType($customer);

		$start_date = $query_params['start_date'];
		$end_date = $query_params['end_date'];
		$store_id = $query_params['store_id'];
		$store_name = $query_params['store_name'];
		$store_code = $query_params['store_code'];
		$start_id = $query_params['start_id']; // Transaction id from where to start fetching of the transactions
		$end_id = $query_params['end_id']; // Transaction id from where to start fetching of the transactions
		$limit = $query_params['limit'];
		$sort = $query_params['sort'];
		$order = $query_params['order'];
		$should_return_user_id = isset($query_params['user_id']) &&
		strtolower($query_params['user_id']) == 'true' ? true : false;

		$transaction_numbers = $query_params['trans_nos'];
		
		$return_credit_notes=isset($query_params['credit_notes']) && strtolower($query_params['credit_notes'])=='true'?true:false;

		$this->logger->debug("Parameters from Client:
				IdentifierType: $identifier ,
				IdentifierValue: ". $customer[$identifier].	",
				StartDate: $start_date ,
				EndDate: $end_date ,
				StoreId: $store_id ,
				StoreName: $store_name,
				StoreCode: $store_code,
				Start Id: $start_id,
				End Id: $end_id,
				Limit: $limit,
				TransationFilter: $transaction_numbers");
		try
		{
			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $customer['mobile'],
			'email' => $customer['email'],
			'external_id' => $customer['external_id']
			) );

			$user_hash = $customerController->getTransactionsFiltered($identifier, $customer[$identifier],
					$start_date, $end_date, $store_id, $store_name, $store_code, $start_id, $end_id, $sort, $order,$transaction_numbers, $limit, $return_credit_notes);

			$store_timezone = $this->currentuser->getStoreTimeZoneLabel();
			if( !isset( $store_timezone ) )
			{
				$this->logger->debug("Store timezone not set. using org timezone: $store_timezone");
				$store_timezone=$this->currentorg->getOrgTimeZoneLabel();
			}

			$user_hash['registered_on'] = Util::convertOneTimezoneToAnotherTimezone(
					$user_hash['registered_on'],
					date_default_timezone_get(),
					$store_timezone );

			$this->logger->info("User Hash: ".print_r($user_hash, true));
			$rows = count($user_hash['transactions']);
			if( $rows > 0)
				$start = $user_hash['transactions'][0]['id'];
			else
				$start = "";

			$count = $customerController->getNumberOfBillsForUser($user_hash['id']);

			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $user_hash['mobile'],
			'email' => $user_hash['email'],
			'external_id' => $user_hash['external_id'],
			'user_id' => $user_hash['id']
			) );
			$customer = array();
			if($should_return_user_id)
				$customer = array("user_id" => $user_hash['id']);

			$transactions = array();
			if(strtolower($version) == 'v1')
			{
				foreach($user_hash['transactions'] AS $transaction)
				{
					$count = 0;
					$temp_transaction = array();
					$temp_transaction['id'] = $transaction['id'];
					$temp_transaction['number'] = $transaction['number'];
					$temp_transaction['type'] = $transaction['type'];
                    $temp_transaction['outlier_status'] = $transaction['outlier_status'];
					$temp_transaction['amount'] = $transaction['amount'];
					$temp_transaction['notes'] = $transaction['notes'];
					$temp_transaction['billing_time'] = $transaction['billing_time'];
					$temp_transaction['gross_amount'] = $transaction['gross_amount'];
					$temp_transaction['discount'] = $transaction['discount'];
					$temp_transaction['store'] = $transaction['store'];
					$temp_transaction['points'] = $transaction['points'];
					$temp_transaction['coupons'] = $transaction['coupons'];
					$temp_transaction['basket_size'] = $transaction['basket_size'];
					$temp_transaction['line_items']['line_item'] = array();
					foreach($transaction['line_items']['line_item'] AS $lineitem)
					{
						unset($lineitem['type']);
						$temp_transaction['line_items']['line_item'][] = $lineitem;
						$count++;
					}
					$transactions[] = $temp_transaction;
				}
			}
			else
			{
				$transactions = $user_hash['transactions'];
			}

			$customer = array_merge($customer,
					array(
							"mobile" => $user_hash['mobile'],
							"email" => $user_hash['email'],
							"external_id" => $user_hash['external_id'],
							"firstname" => $user_hash['firstname'],
							"lastname" => $user_hash['lastname'],
							"lifetime_points" => $user_hash['lifetime_points'],
							"lifetime_purchases" => $user_hash['lifetime_purchases'],
							"loyalty_points" => $user_hash['loyalty_points'],
							"registered_on" => $user_hash['registered_on'],
							"updated_on" => $user_hash['updated_on'],
							"current_slab" => $user_hash['current_slab'],
							"type" =>$user_hash['type'],
							"source" =>$user_hash['source'],
							"count" => $count,
							"start" => $start,
							"rows" => $rows,
							"transactions" => array(
									"transaction" => $transactions
							)
					)
			);
		}
		catch(Exception $e)
		{
			$this->logger->error("ERROR: ".$e->getMessage());
			throw new Exception( ErrorMessage::$api['FAIL'] .", ". ErrorMessage::$customer[$e->getMessage()], ErrorCodes::$api['FAIL']);
		}


		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"],
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);

		return 	array(
				"status" => $api_status,
				"customer" => $customer
		);
	}

	/**
         * @SWG\Api(
         * path="/customer/redemptions.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get redemptions done by a customer",
        *     nickname = "Customer Redemptions",
         *    @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *    description = "Mobile number of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "external_id",
         *    type = "string",
         *    paramType = "query",
         * description = "External Id of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         * description = "Email of customer"
         *    )
        * ))
        */
	
	private function redemptions( $version, $query_params )
	{

		$this->logger->info("in customer redemptions");
		$api_status_code = 'SUCCESS';
		$customerController = new ApiCustomerController();

		$customer = $query_params;
		$identifier = $this->getCustomerIdentifierType($customer);

		if( isset( $query_params['type'] ) &&
				!in_array( strtolower( $query_params['type'] ), array( 'coupons', 'points' ) ) ){

			$error_key = 'ERR_NO_IDENTIFIER';
			throw new Exception(
					ErrorMessage::$api['INVALID_INPUT'],
					ErrorCodes::$api['INVALID_INPUT']
			);
		}
		$type = $query_params['type'];
		$start_date = $query_params['start_date'];
		$end_date = $query_params['end_date'];
		$start_id = $query_params['start_id'];
		$end_id = $query_params['end_id'];
		$limit = $query_params['limit'];
		$order = $query_params['order'];
		$sort = $query_params['sort'];
		$store_code = $query_params['store_code'];
		$coupons_start_id = $query_params['coupons_start_id'];
		$coupons_end_id = $query_params['coupons_end_id'];
		$coupons_limit = $query_params['coupons_limit'];
		$points_start_id = $query_params['points_start_id'];
		$points_end_id = $query_params['points_end_id'];
		$points_limit = $query_params['points_limit'];

		$should_return_user_id = isset( $query_params['user_id'] ) &&
		strtolower( $query_params['user_id'] ) == 'true' ? true : false;

		$this->logger->debug( "Parameters from Client:
				IdentifierType: $identifier ,
				IdentifierValue: ". $customer[$identifier].	",
				StartDate: $start_date ,
				EndDate: $end_date ,
				Start Id: $start_id,
				End Id: $end_id,
				Store code: $store_code,
				Limit: $limit,
				Order: $order,
				Sort: $sort
				Coupons Start Id: $coupons_start_id,
				Points Start Id: $points_start_id,
				Coupons End Id: $coupons_end_id,
				Points End Id: $points_end_id,
				Coupons Limit: $coupons_limit,
				Points Limit:  $points_limit" );
		try
		{
			$this->logger->info("checking redemptions details");

			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $customer['mobile'],
			'email' => $customer['email'],
			'external_id' => $customer['external_id']
			) );
			if( strtolower( $type ) == 'coupons' ){

				$user_hash = $customerController->getCouponRedemptionHistory( $identifier, $customer[$identifier],
						$start_date, $end_date, $store_code, $start_id, $end_id, $order, $limit, $sort, $version );
				$rows = count( $user_hash['coupons'] );
			}elseif( strtolower( $type ) == 'points' ){
				$user_hash = $customerController->getPointsRedemptionHistory( $identifier, $customer[$identifier],
						$start_date, $end_date, $store_code, $start_id, $end_id, $order, $limit, $sort, $version );
				$rows = count( $user_hash['points'] );
			}else{

				$coupon_redemption = $customerController->getCouponRedemptionHistory( $identifier, $customer[$identifier],
						$start_date, $end_date, $store_code, $coupons_start_id, $coupons_end_id, $order, $coupons_limit, $sort, $version );
				$points_redemption = $customerController->getPointsRedemptionHistory( $identifier, $customer[$identifier],
						$start_date, $end_date, $store_code, $points_start_id, $points_end_id, $order, $points_limit, $sort, $version );
				$user_hash = $coupon_redemption;
				$user_hash['points'] = $points_redemption['points'];
				$user_hash['points_count'] = $points_redemption['points_count'];
				$rows = count( $coupon_redemption['coupons'] ) + count( $points_redemption['points'] );
			}
			$this->logger->info( "User Hash: ".print_r( $user_hash, true ) );
			if( $rows > 0)
				$start = $user_hash[$type][0]['id'];
			else
				$start = "";

			$customer = array();
			if( $should_return_user_id )
				$customer = array( "user_id" => $user_hash['id'] );

			Util::saveLogForApiInputDetails(
			array(
			'mobile' => $user_hash['mobile'],
			'email' => $user_hash['email'],
			'external_id' => $user_hash['external_id'],
			'user_id' => $user_hash['id']
			) );
			$customer = array_merge(
					$customer,
					array(
							"mobile" => $user_hash['mobile'],
							"email" => $user_hash['email'],
							"external_id" => $user_hash['external_id'],
							"firstname" => $user_hash['firstname'],
							"lastname" => $user_hash['lastname'],
							"count" => $user_hash[$type.'_count'],
							"start" => $start,
							"rows" => $rows,
					)
			);

			foreach ($user_hash['coupons'] as &$coupon) {
				$coupon['code'] = ApiUtil::trimCouponCode($coupon['code']);
			}

			if( $type ){

				$customer['redemptions'] = array(
						$type =>  array(
								rtrim( $type, 's' ) => $user_hash[$type] )
				);
			}else{

				unset( $customer['start'] );
				unset( $customer['count'] );
				$customer['coupons_count'] = $user_hash['coupons_count'];
				$customer['points_count'] = $user_hash['points_count'];
				if( $rows > 0){

					$customer['coupons_start_id'] = $user_hash['coupons'][0]['id'];
					$customer['points_start_id'] = $user_hash['points'][0]['id'];
				}
				else{

					$customer['coupons_start_id'] = "";
					$customer['points_start_id'] = "";
				}

				$customer['redemptions'] = array(
						'coupons' => array( 'coupon' => $user_hash['coupons'] ),
						'points' => array( 'point' => $user_hash['points'] )
				);
			}
		}
		catch(Exception $e){

			$this->logger->error( "ERROR: ".$e->getMessage() );
			throw new Exception(
					ErrorMessage::$api['FAIL'] .", ".
					ErrorMessage::$customer[$e->getMessage()],
					ErrorCodes::$api['FAIL']
			);
		}
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"],
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		return 	array(
				"status" => $api_status,
				"customer" => $customer
		);
	}


		/**
         * @SWG\Model(
         * id = "NotesRequest",
         * @SWG\Property(name = "customers", type = "CustomerNotesRequest" )
         * )
         */
		
		/**
         * @SWG\Model(
         * id = "CustomerNotesRequest",
         * @SWG\Property(name = "customer", type = "array", items = "$ref:CustomerNotes" )
         * )
         */

         /**
         * @SWG\Model(
         * id = "CustomerNotes",
         * @SWG\Property(name = "user_id", type = "integer" ),
         * @SWG\Property(name = "mobile", type = "string" ),
         * @SWG\Property(name = "external_id", type = "string" ),
         * @SWG\Property(name = "email", type = "string" ),
		 * @SWG\Property(name = "associate_id", type = "integer" ), 
         * @SWG\Property(name = "notes", type = "note" ),
         * description = "Any one of the customer identifier need to be specified"
         * )
         */
         
          /**
         * @SWG\Model(
         * id = "note",
         * @SWG\Property(name = "note", type = "array", items = "$ref:notelist" )
         * )
         */
          
          /**
         * @SWG\Model(
	         * id = "notelist",
	         * @SWG\Property(name = "id", type = "integer", required = false ),
	         * @SWG\Property(name = "date", type = "string", required = true ),
	         * @SWG\Property(name = "description", type = "string", required = true )
		  *)
		  */

        /**
         * @SWG\Model(
         * id = "NotesRoot",
         * @SWG\Property( name = "root", type = "NotesRequest" )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/customer/notes.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Add/update notes for the customer(s)",
         * @SWG\Parameter(
         * name = "request",paramType="body", type="NotesRoot")
        * ))
        */
	private function notes($vesion, $data, $query_params, $http_method)
	{
		$http_method = strtolower($http_method);
		if($http_method == 'get')
		{
			$this->logger->debug("Found GET Request, trying to fetch the Notes");
			return $this->getNotes($query_params);
		}
		else if ($http_method == 'post')
		{
			$this->logger->debug("Found POST Request, trying to Add or Update the Notes");
			return $this->addNotes($data);
		}
	}

		/**
         * @SWG\Api(
         * path="/customer/notes.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Get notes for a customer",
        *     nickname = "Customer Notes",
         *    @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *    description = "Mobile number of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "external_id",
         *    type = "string",
         *    paramType = "query",
         * description = "External Id of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         * description = "Email of customer"
         *    )
        * ))
        */
	
	private function getNotes($query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$should_return_user_id = $query_params['user_id'] == 'true' ? true : false;
		$customerController = new ApiCustomerController();

		$api_status_code = 'SUCCESS';
		$org_id = $this->org_id;

		$customers = $query_params;
        $identifier = $this->getCustomerIdentifierType($customers);

		$associate_ids = array();
		if(isset($query_params['associate_id']) && !empty($query_params['associate_id'])  )
		{
			$this->logger->debug("associate_id : ".$query_params['associate_id']);
			$associate_ids = explode("," , $query_params['associate_id'] );
		}


		$customer_array = StringUtils::strexplode( ',' , $customers[ $identifier ] );
		$customers = array();
		global $error_count;
		$error_count = 0;
		if(is_array($customer_array))
		{
			foreach($customer_array as $value)
			{
				$item_status_code = "ERR_CUSTOMER_NOTES_SUCCESS_RETRIEVED";
				try
				{
					$input[$identifier] = $value;
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $input['mobile'],
					'email' => $input['email'],
					'external_id' => $input['external_id']
					) );
					$customer = $customerController->getNotes( array( $identifier => $value) , $associate_ids);
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $customer['mobile'],
					'email' => $customer['email'],
					'external_id' => $customer['external_id'],
					'user_id' => $customer['user_id']
					) );
				}
				catch(Exception $e)
				{

					$this->logger->error("CustomerResource::getNotes() ERROR => ".$e->getMessage());
					$item_status_code = $e->getMessage();

					$temp_identifier = ($identifier == 'id')? 'user_id' : $identifier;
					$customer[ $temp_identifier ] = $value;

					$error_count++;
				}
				$customer['item_status'] = array(
						"success" => ErrorCodes::$customer[$item_status_code] ==
						ErrorCodes::$customer["ERR_CUSTOMER_NOTES_SUCCESS_RETRIEVED"] ? true : false,
						"code" => ErrorCodes::$customer[$item_status_code],
						"message" => ErrorMessage::$customer[$item_status_code]
				);
				$arr_item_status_codes[] = $customer['item_status']['code'];
				array_push($customers, $customer);
			}
		}

		if(count($customer_array) == $error_count )
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		return array(
				"status" => $api_status,
				"customer" => $customers
		);
	}

	private function addNotes($data)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$this->logger->debug("INPUT DATA: ".print_r($data, true));
		$customers = $data['root']['customer'];
		if(!isset($customers[0]))
		{
			$customers = array($customers);
		}
		$api_status_code = "SUCCESS";
		$customerController = new ApiCustomerController();
		global $error_count;
		$error_count = 0;
		$return_customers = array();
		if(is_array($customers))
		{
			foreach($customers as $customer)
			{
				Util::saveLogForApiInputDetails(
				array(
				'mobile' => $customer['mobile'],
				'email' => $customer['email'],
				'external_id' => $customer['external_id'],
				'user_id' => $customer['user_id']
				) );
				$item_status_code = "ERR_CUSTOMER_NOTES_SUCCESS_ADDED";
				$temp_customer = array();
				global $gbl_country_code;
				if(isset($customer['country_code']))
				{
					$gbl_country_code = $customer['country_code'];
				}
				else
				{
					$gbl_country_code = '';
				}
				try{
					if(isset($customer['user_id']))
						$customer['id'] = $customer['user_id'];

					$user = UserProfile::getByData($customer);
					$user->load(true);

					if(! (isset($customer['notes']) && isset($customer['notes']['note'])) )
					{
						$this->logger->debug("Didn't pass Notes");
						$this->logger->error( "Error in adding Customer Notes " );
						throw new Exception( "ERR_CUSTOMER_NOTES_ADD_FAILED" );
					}

					$notes = $customer['notes']['note'];
					if(isset($notes['description']) || isset($notes['date']) || isset($notes['id']))
					{
						$notes = array( 0 => $notes );
					}
					//for now setting assoc id as -1
					$assoc_id = -1;

					if(isset($customer['assoc_id']) && is_numeric($customer['assoc_id']))
						$assoc_id = $customer['assoc_id'];
					else if(isset($customer['associate_id']) && is_numeric($customer['associate_id']))
						$assoc_id = $customer['associate_id'];

					//$success = $customerController->addNotesInBatch($user, $assoc_id, $notes);
					$return_notes = $customerController->addOrUpdateNotes($user, $assoc_id, $notes);
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $user->mobile,
					'email' => $user->email,
					'external_id' => $user->external_id,
					'user_id' => $user->user_id
					) );

					$temp_customer['user_id'] = $user->user_id;
					$temp_customer['mobile'] = $user->mobile;
					$temp_customer['email'] = $user->email;
					$temp_customer['external_id'] = $user->external_id;
					$temp_customer['notes']['note'] = $return_notes;
					if( count( $return_notes ) != count( $notes ) ){

						$this->logger->error( "Some Customer Notes addition failed" );
						throw new Exception( "ERR_CUSTOMER_SOME_NOTES_ADD_FAILED" );
					}else if( !$return_notes ){

						$this->logger->error( "Error in adding Customer Notes " );
						throw new Exception( "ERR_CUSTOMER_NOTES_ADD_FAILED" );
					}
				}
				catch(Exception $e)
				{
					$this->logger->debug("CustomerResource::adddNotes() ERROR: ".$e->getMessage());
					$item_status_code = $e->getMessage();
					$temp_customer['user_id'] = $customer['user_id'];
					$temp_customer['mobile'] = $customer['mobile'];
					$temp_customer['email'] = $customer['email'];
					$temp_customer['external_id'] = $customer['external_id'];
					$error_count++;
				}

				$temp_customer['item_status'] = array(
						"success" => ErrorCodes::$customer[$item_status_code] ==
						ErrorCodes::$customer["ERR_CUSTOMER_NOTES_SUCCESS_ADDED"] ? true : false,
						"code" => ErrorCodes::$customer[$item_status_code],
						"message" => ErrorMessage::$customer[$item_status_code]
				);
				$arr_item_status_codes[] = $temp_customer['item_status']['code'];
				array_push($return_customers, $temp_customer);
			}
		}

		if(count($customers) == $error_count )
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);

		return array(
				"status" => $api_status,
				"customer" => $return_customers
		);
	}

	
	/**
	 * @SWG\Api(
	 * path="/customer/preferences.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetches customer preferences",
	 *     nickname = "Get Customer preferences",
	 *    @SWG\Parameter(name = "mobile",type = "string",paramType = "query", description = "Mobile number of the customer" ),
	 *    @SWG\Parameter(name = "email",type = "string",paramType = "query", description = "Customer's email id" ),
	 *    @SWG\Parameter(name = "external_id",type = "string",paramType = "query", description = "Customer's external_id" ),
	 *    @SWG\Parameter(name = "id",type = "string",paramType = "query", description = "Customer id" ),
	 *    @SWG\Parameter(name = "user_id",type = "boolean",paramType = "query", description = "Optional. If set as true, return user_id in response" )
	 *    )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "Field",
	 * @SWG\Property( name = "name", type = "string" ),
	 * @SWG\Property( name = "value", type = "string" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Store",
	 * @SWG\Property( name = "code", type = "string", description = "Store code" ),
	 * @SWG\Property( name = "id", type = "string", description = "Store id" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Customer",
	 * @SWG\Property( name = "mobile", type = "string" ),
	 * @SWG\Property( name = "email", type = "string" ),
	 * @SWG\Property( name = "external_id", type = "string" ),
	 * @SWG\Property( name = "user_id", type = "string" ),
	 * @SWG\Property( name = "local_id", type = "string" ),
	 * @SWG\Property( name = "store", type = "Store" ),
	 * @SWG\Property( name = "custom_fields", type = "array", items = "$ref:Field" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "PreferenceRoot",
	 * @SWG\Property( name = "root", type = "Customer" )
	 * )
	 */
	/**
	 * @SWG\Api(
	 * path="/customer/preferences.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Updates customer preferences",
	 *     nickname = "Post Preferences",
	 *	   @SWG\Parameter(name = "request", paramType="body", type="PreferenceRoot")
	 * ))
	 */
	private function preferences($version, $data, $query_params, $http_method)
	{
		$http_method = strtolower($http_method);
		$result= array();
		if($http_method == 'get')
		{
			$result = $this->getPreferences($query_params);
		}
		else if ($http_method == 'post')
		{
			$result = $this->savePreferences($data);
		}

		return $result;
	}

	private function getPreferences($query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$should_return_user_id = $query_params['user_id'] == 'true' ? true : false;
		$customerController = new ApiCustomerController();

		$api_status_code = 'SUCCESS';
		$org_id = $this->org_id;

		$customers = $query_params;
        $identifier = $this->getCustomerIdentifierType($customers);

		if(isset($customers[ $identifier ]))
			$customer_array = StringUtils::strexplode( ',' , $customers[ $identifier ] );

		$customers = array();
		global $error_count, $gbl_item_count;
		$error_count = 0;
		$gbl_item_count = count($customer_array);
		if(is_array($customer_array))
		{
			foreach($customer_array as $value)
			{
				$item_status_code = "ERR_CUSTOMER_PREFERENCES_SUCCESS_RETRIEVED";
				try
				{
					$new_identifier = ($identifier == 'id')? 'user_id' : $identifier;
					Util::saveLogForApiInputDetails( array( $new_identifier => $value ) );
					$customer = $customerController->getPreferences( array( $identifier => $value ));
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $customer['mobile'],
					'email' => $customer['email'],
					'external_id' => $customer['external_id'],
					'user_id' => $customer['user_id']
					) );
				}
				catch(Exception $e)
				{

					$this->logger->error("CustomerResource::getPreferences() ERROR => ".$e->getMessage());
					$item_status_code = $e->getMessage();

					$temp_identifier = ($identifier == 'id')? 'user_id' : $identifier;
					$customer[ $temp_identifier ] = $value;

					$error_count++;
				}
				$customer['item_status'] = array(
						"success" => ErrorCodes::$customer[$item_status_code] ==
						ErrorCodes::$customer["ERR_CUSTOMER_PREFERENCES_SUCCESS_RETRIEVED"] ? true : false,
						"code" => ErrorCodes::$customer[$item_status_code],
						"message" => ErrorMessage::$customer[$item_status_code]
				);
				$arr_item_status_codes[] = $customer['item_status']['code'];
				array_push($customers, $customer);
			}
		}

		if(count($customer_array) == $error_count )
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";

		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);

		$gbl_item_status_codes = implode(",", $arr_item_status_codes);

		if(is_array($customer) && count($customers) > 0)
			$customers = array("customer" => $customers);

		return array(
				"status" => $api_status,
				"customers" => $customers
		);
	}

	private function savePreferences($data)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$this->logger->debug("INPUT DATA: ".print_r($data, true));
		$customers = $data['root']['customer'];
		if(!isset($customers[0]))
		{
			$customers = array($customers);
		}
		$api_status_code = "SUCCESS";
		$customerController = new ApiCustomerController();
		global $error_count, $gbl_item_count;
		$error_count = 0;
		$gbl_item_count = count($customers);
		$return_customers = array();
		if(is_array($customers))
		{
			foreach($customers as $customer)
			{
				$customer['id']=$customer['user_id'];
				$item_status_code = "ERR_CUSTOMER_PREFERENCES_UPDATION_SUCCESS";
				$temp_customer = array();
				global $gbl_country_code;
				if(isset($customer['country_code']))
				{
					$gbl_country_code = $customer['country_code'];
				}
				else
				{
					$gbl_country_code = '';
				}
				try{
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $customer['mobile'],
					'email' => $customer['email'],
					'external_id' => $customer['external_id'],
					'user_id' => $customer['user_id']
					) );
					$temp_customer = $customerController->updatePreferences($customer);
					Util::saveLogForApiInputDetails(
					array(
					'mobile' => $temp_customer['mobile'],
					'email' => $temp_customer['email'],
					'external_id' => $temp_customer['external_id'],
					'user_id' => $temp_customer['user_id']
					) );
				}
				catch(Exception $e)
				{
					$this->logger->debug("CustomerResource::adddNotes() ERROR: ".$e->getMessage());
					$item_status_code = $e->getMessage();
					$temp_customer = $customer;
					$error_count++;
				}

				$temp_customer['item_status'] = array(
						"success" => ErrorCodes::$customer[$item_status_code] ==
						ErrorCodes::$customer["ERR_CUSTOMER_PREFERENCES_UPDATION_SUCCESS"] ? true : false,
						"code" => ErrorCodes::$customer[$item_status_code],
						"message" => ErrorMessage::$customer[$item_status_code]
				);
				$arr_item_status_codes[] = $temp_customer['item_status']['code'];
				array_push($return_customers, $temp_customer);
			}
		}

		if(count($customers) == $error_count )
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";

		if(!$customers || !is_array( $customers ))
			$api_status_code = "INVALID_INPUT";
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] ==
				ErrorCodes::$api["SUCCESS"] ? true : false,
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);

		return array(
				"status" => $api_status,
				"customer" => $return_customers
		);
	}


	/**
	 * @SWG\Api(
	 * path="/customer/subscriptions.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get subscriptions for a customer",
	 *     nickname = "Get Customer subscriptions",
	 *    @SWG\Parameter(name = "mobile",type = "string",paramType = "query", description = "Mobile number of customer"  ),
	 *    @SWG\Parameter(name = "external_id",type = "string",paramType = "query", description = "External Id  of customer"  ),
	 *    @SWG\Parameter(name = "email",type = "string",paramType = "query", description = "Email of customer"  )
	 *    )
	 * ))
	 */

		/**
         * @SWG\Model(
         * id = "SubscriptionSetList",
         * @SWG\Property(name = "subscription", type = "SubscriptionSet", description = "Subscription details for each channel and priority for customer" )
         * )
         * */
	
		/**
         * @SWG\Model(
         * id = "SubscriptionSet",
         * @SWG\Property(name = "mobile", type = "string" ),
         * @SWG\Property(name = "external_id", type = "string" ),
         * @SWG\Property(name = "email", type = "string" ),
		 * @SWG\Property(name = "priority", enum= "['TRANS', 'BULK']", required = true ),
		 * @SWG\Property(name = "scope", enum= "['ALL']", required = true ),
		 * @SWG\Property(name = "channel", enum = "['SMS', 'EMAIL']", required = true ),
		 * @SWG\Property(name = "is_subscribed", enum = "[1,0]", required = true )
         * )
         * */
	
		/**
         * @SWG\Model(
         * id = "SubscriptionRoot",
         * @SWG\Property( name = "root", type = "array", items = "$ref:SubscriptionSetList" )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/customer/subscriptions.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Set the subscription status for customer(s)",
         * @SWG\Parameter(
         * name = "request",paramType="body", type="SubscriptionRoot")
        * ))
        */
	
	private function subscriptions($version, $data, $query_params, $http_method)
	{
		require_once "business_controller/UserSubscriptionController.php";

		switch(strtolower($http_method))
		{
			case 'get':
				$result=$this->get_subscriptions($data,$query_params);
				break;
			case 'post':
				$result=$this->post_subscriptions($data,$query_params);
				break;
		}

		return $result;
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

	private function post_subscriptions($data,$query_params)
	{

		$this->logger->debug("CustomerResource post_subscriptions input : ".print_r($data,true).print_r($query_params,true));

		$USC=new UserSubscriptionController($this->currentorg->org_id);
		if(!isset($data['root']['subscription']))
		{
			$this->logger->error("Invalid input in post_subscription");
			throw new Exception(ErrorMessage::$api['INVALID_INPUT'],ErrorCodes::$api['INVALID_INPUT']);
		}


		$resp=array();
		$success_ids=0;

		$inp_subscriptions=isset($data['root']['subscription'])?$data['root']['subscription']:$data['root']['customer'];

		foreach($inp_subscriptions as $subscription)
		{

			try{

				$sub_det=array('user_id'=>0);

				if(!isset($subscription['is_subscribed']))
					throw new Exception(ErrorMessage::$api['INVALID_INPUT'], ErrorCodes::$api['INVALID_INPUT']);

				$is_subscribed=$subscription['is_subscribed'];

				$call_identifier=null;
				Util::saveLogForApiInputDetails(
				array(
				'mobile' => $subscription['mobile'],
				'email' => $subscription['email'],
				'external_id' => $subscription['external_id'],
				'user_id' => $subscription['user_id']
				) );
				foreach(array('mobile'=>'Mobile','email'=>'Email','user_id'=>'Id','external_id'=>'ExternalId') as $identifier=>$i_call_identifier)
				{
					if(isset($subscription[$identifier]) && !empty($subscription[$identifier]))
					{
						$call_identifier=$i_call_identifier;
						break;
					}
				}

				if(!$call_identifier)
				{
					$this->logger->error("No identifiers passed for post_subscription");
					throw new Exception('ERR_SUBSC_NO_IDENTIFIER');
				}

				$call="getBy$call_identifier";
				$input=trim($subscription[$identifier]);
				$user=UserProfile::$call($input);
				if(!$user)
					throw new Exception('ERR_NOT_REGISTERED');
				$sub_det['is_subscribed']=$is_subscribed;
				
				ApiCacheHandler::triggerEvent("customer_update", isset($user->user_id)?$user->user_id:'');
				
				ApiUtil::mcUserUpdateCacheClear($user->user_id);
				/*
				try{
					$cache = MemcacheMgr::getInstance();
					$all_keys = $cache->getMembersOfSet(CacheKeysPrefix::$mc_admin_user_cache.$user->user_id);
					if(count($all_keys)>0)
						$cache->deleteMulti($all_keys);
				} catch ( Exception $e){
					$this->logger->debug("Error deleting customer profile cache set by mc");
				}
				*/
				$sub_det = $this->saveCustomerSubscriptions($user, $subscription, $identifier, $input);
					
				if( $user )
					Util::saveLogForApiInputDetails( array( $identifier => $input, 'user_id' => $user->getUserId() ) );
					
				$sub_det['item_status']=array(
						'success' => 'true',
						'code'=>ErrorCodes::getCustomerErrorCode('ERR_POST_SUBSCRIPTION_SUCCESS'),
						'message'=>ErrorMessage::getCustomerErrorMessage('ERR_POST_SUBSCRIPTION_SUCCESS'),
				);

				$sub_det['is_subscribed']=$is_subscribed;

				$success_ids++;

			}catch(Exception $e){
				if($e->getCode())
					$sub_det['item_status']=array(
							'success' => 'false',
							'code' => $e->getCode(),
							'message' => $e->getMessage()
					);
				else
					$sub_det['item_status']=array(
							'success' => 'false',
							'code' => ErrorCodes::getCustomerErrorCode( $e->getMessage()),
							'message' => ErrorMessage::getCustomerErrorMessage( $e->getMessage() )
					);
				
				if($identifier!="id")
					$sub_det[$identifier]=$input;
				$sub_det['channel']=strtoupper($subscription['channel']);
				$sub_det['priority']=strtoupper($subscription['priority']);
				$sub_det['scope']= strtoupper($subscription['scope']);
				$sub_det['is_subscribed']=$subscription["is_subscribed"];
			}

			$resp[]=$sub_det;

		}


		$error_key='SUCCESS';
		if($success_ids==0)
			$error_key='FAIL';
		elseif($success_ids!=count($data['root']['subscription']))
		$error_key='PARTIAL_SUCCESS';


		$status_success = ( $error_key == 'FAIL' )? 'false' : 'true';

		$result=array('status' => array( 'success' => $status_success ,'code' => ErrorCodes::$api[ $error_key ],
				'message' => ErrorMessage::$api[ $error_key ]
		),
				'subscriptions'=>array('subscription'=>$resp));

		$this->logger->debug("CustomerResource post_subscriptions output : ".print_r($result,true));

		return $result;


	}

	private function get_subscriptions($data,$query_params)
	{
		$this->logger->debug("Data passed ".print_r($data,true));
		$this->logger->debug("Query params: ".print_r($query_params,true));
		$this->logger->debug("CustomerResource get_subscriptions input : ".print_r($data,true).print_r($query_params,true));

		$USC=new UserSubscriptionController($this->currentorg->org_id);

		$this->validate_subscriptions($query_params,false,true);

		$channel=isset($query_params['channel'])?strtoupper($query_params['channel']):false;
		$priority=isset($query_params['priority'])?strtoupper($query_params['priority']):false;
		$scope_i=$scope=isset($query_params['scope'])?strtoupper($query_params['scope']):false;
		$is_subscribed=isset($query_params['is_subscribed'])?$query_params['is_subscribed']:null;

		if($is_subscribed!=null)
			$is_subscribed=$is_subscribed?1:0;

		$user_list=array();
		$this->logger->debug("User List: ".print_r($user_list,true));
		$resp['subscription']=array();
        if($query_params['user_id'])
            $query_params['id'] = $query_params['user_id'];
        $identifier = $this->getCustomerIdentifierType($query_params);
        $this->logger->debug("Identifier: ".print_r($identifier,true));
		$inputs=explode(",",$query_params[$identifier]);
		$this->logger->debug("Inputs : ".print_r($identifier,true));
		$success_ids=0;

		foreach($inputs as $input)
		{
			$user_det=array('user_id'=>0);
			$user_det[$identifier]=$input;
			$user_det['channel']=array();

			try{
				$this->logger->debug("In try ");
				Util::saveLogForApiInputDetails( array( $identifier => $input ) );
                $customerController = new ApiCustomerController();
                try {
                    $user = $customerController->getCustomers(array($identifier => $input));
                }
                catch (Exception $ex){
                    throw new Exception('ERR_NOT_REGISTERED');
                }
				if(!$user)
					throw new Exception('ERR_NOT_REGISTERED');

				$user_list[]=$user->getUserId();
				$user_det['user_id']=$user->getUserId();
				$call="get$call_identifier";
				if($identifier!="id")
					$user_det[$identifier]=$user->$identifier;
				$user_det['item_status']=array();
				$success_ids++;

			}catch(Exception $e){
				$user_det['item_status']=array(
						'success' => 'false',
						'code' => ErrorCodes::getCustomerErrorCode( $e->getMessage()),
						'message' => ErrorMessage::getCustomerErrorMessage( $e->getMessage() )
				);
			}
			$resp['subscription'][]=$user_det;

		}


		$subscription_status=array();
		$this->logger->debug("subscription status: ".print_r($subscription_status,true));
		if(!empty($user_list))
			$subscription_status=$USC->getAllSubscriptions($user_list,$channel,$priority,$scope_i);
			$this->logger->debug("subscription status: ".print_r($subscription_status,true));

		if( $user )
			Util::saveLogForApiInputDetails( array( $identifier => $input, 'user_id' => $user->getUserId() ) );
			
		$subscription_details=$this->formatSubscriptionGet($subscription_status, $resp);
			$this->logger->debug("subscription details: ".print_r($subscription_details,true));
		foreach($subscription_details as $id=>$status)
		{
			$item_status=array(
					'success' => 'true',
					'code'=>ErrorCodes::getCustomerErrorCode('ERR_GET_SUBSCRIPTION_SUCCESS'),
					'message'=>ErrorMessage::getCustomerErrorMessage('ERR_GET_SUBSCRIPTION_SUCCESS'),
			);
			foreach($resp['subscription'] as $i=>$user)
				if($user['user_id']==$id)
				$resp['subscription'][$i]['item_status']=$item_status;
		}

		$error_key='SUCCESS';
		if($success_ids==0)
			$error_key='FAIL';
		elseif($success_ids!=count($inputs))
		$error_key='PARTIAL_SUCCESS';

		$status_success = ( $error_key == 'FAIL' )? 'false' : 'true';

		$result=array('status' => array( 'success' => $status_success ,'code' => ErrorCodes::$api[ $error_key ],
				'message' => ErrorMessage::$api[ $error_key ]
		),
				'subscriptions'=>$resp);

		$this->logger->debug("@@@diablo *customer/get_subscriptions output* : ".print_r($result,true));

		return $result;

	}

	private function formatSubscriptionGet($subscription_status, &$resp)
	{
		$USC=new UserSubscriptionController($this->currentorg->org_id);
		
		foreach($subscription_status as $sub)
		{

			$user_id=$sub['user_id'];
			$channel=$sub['channel'];
			$scope=$sub['scope'];
			$priority=$sub['priority'];
			$is_sub=$sub['is_subscribed'];
			$user_preference = $sub['user_preference'];
			
			if(!isset($subscription_details[$user_id]))
				$subscription_details[$user_id]=array();
			if(!isset($subscription_details[$user_id][$channel]))
				$subscription_details[$user_id][$channel]=array();
			if(!isset($subscription_details[$user_id][$channel][$priority]))
				$subscription_details[$user_id][$channel][$priority]=array();
			if(!isset($subscription_details[$user_id][$channel][$priority][$is_sub]))
				$subscription_details[$user_id][$channel][$priority][$is_sub]=array();
			if(!isset($subscription_details[$user_id][$channel][$priority][!$is_sub]))
				$subscription_details[$user_id][$channel][$priority][!$is_sub]=array();
			$subscription_details[$user_id][$channel][$priority][$is_sub][]=$scope;

		}
		
		$scopes_list=$scope_i?array(strtoupper($scope_i)):array_keys($USC->getCommScopes());
		foreach($subscription_details as $userid=>$ssub)
			foreach($ssub as $channel=>$sssub)
			foreach($sssub as $priority=>$ssssub)
			foreach($ssssub as $is_sub=>$scopes)
			if(in_array("ALL",$scopes))
			$subscription_details[$userid][$channel][$priority][$is_sub]=array_diff($scopes_list, $subscription_details[$userid][$channel][$priority][!$is_sub]);


		//elaborated big xml begins
		foreach($resp['subscription'] as $i=>$user)
		{
			$user_id=$user['user_id'];
			$sub_det=array('user_id'=>$user_id,"$identifier"=>$u);

			foreach($subscription_details[$user_id] as $channel=>$sub)
			{
				$channel_det=array('name'=>$channel,'priority'=>array());
				foreach($sub as $priority=>$prior)
				{
					$priority_det=array('name'=>$priority,'subscribed'=>array(),'unsubscribed'=>array() );
					foreach($prior as $is_sub=>$scopes)
						$priority_det[$is_sub==1?'subscribed':'unsubscribed']=implode(",",$scopes);
					$channel_det['priority'][]=$priority_det;
				}
				$sub_det['channel'][]=$channel_det;
			}

			$resp['subscription'][$i]['channel']=$sub_det['channel'];

		}
		
		foreach ( $resp['subscription'] as $i => $user )
			foreach ( $user as $details => $data )
				foreach ( $data as $j => $fields )
					foreach ( $fields['priority'] as $k => $units )
					{
						foreach ( $subscription_status as $m => $arr )
						{
							if ( ($user['user_id'] == $arr['user_id']) && ( $fields['name'] == $arr['channel'] ) )
							{
								if ( $units['name'] == $arr['priority'] )
								{
									$resp['subscription'][$i]['channel'][$j]['priority'][$k]['user_preference'] = $arr['user_preference'];
								}
							}
						}
					}
		
		
		return $subscription_details;
	}
	
	/**
	 * @SWG\Model(
	 * id = "ProductAttributeModel",
	 * @SWG\Property( name = "name", type = "string", description = "Attribute Name" ),
	 * @SWG\Property( name = "value", type = "string", description = "Attribute Value" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "ProductModel",
	 * @SWG\Property( name = "id", type = "integer", description = "Product id" ),
	 * @SWG\Property( name = "price", type = "float", description = "Product price" ),
	 * @SWG\Property( name = "org_id", type = "integer" ),
	 * @SWG\Property( name = "description", type = "string", description = "Product description" ),
	 * @SWG\Property( name = "sku", type = "string", description = "Item sku" ),
	 * @SWG\Property( name = "img_url", type = "string", description = "Image location" ),
	 * @SWG\Property( name = "price", type = "string", description = "Product price" ),
	 * @SWG\Property( name = "attributes", type = "array", items = "$ref:ProductAttributeModel" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "AttributeModel",
	 * @SWG\Property( name = "priority", type = "integer", description = "Attribute priority" ),
	 * @SWG\Property( name = "name", type = "string", description = "Attribute Name" ),
	 * @SWG\Property( name = "value", type = "string", description = "Attribute Value" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "Recommendation",
	 * @SWG\Property( name = "type", type = "string" ),
	 * @SWG\Property( name = "value", type = "string" ),
	 * @SWG\Property( name = "score", type = "integer" ),
	 * @SWG\Property( name = "attributes", type = "array", items = "$ref:AttributeModel" ),
	 * @SWG\Property( name = "products", type = "array", items = "$ref:ProductModel" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "ItemStatus",
	 * @SWG\Property( name = "success", type = "boolean", description = "Customer Recommendation success status" ),
	 * @SWG\Property( name = "code", type = "integer", description = "Recommendation success status code" ),
	 * @SWG\Property( name = "message", type = "string", description = "Status message" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CustomerRecommendationsModel",
	 * @SWG\Property( name = "mobile", type = "string" ),
	 * @SWG\Property( name = "email", type = "string" ),
	 * @SWG\Property( name = "external_id", type = "string" ),
	 * @SWG\Property( name = "firstname", type = "string" ),
	 * @SWG\Property( name = "lastname", type = "string" ),
	 * @SWG\Property( name = "count", type = "integer" ),
	 * @SWG\Property( name = "recommendations", type = "array", items = "$ref:Recommendation" ),
	 * @SWG\Property( name = "status", type = "ItemStatus" )
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CustomerRecommendationsResponseModel",
	 * @SWG\Property( name = "status", type = "ItemStatus", description = "Customer Recommendation api status" ),
	 * @SWG\Property( name = "customer", type = "CustomerRecommendationsModel", description = "Customer Recommendation entity" )
	 * )
	 */
	
	/**
	 * @SWG\Api(
	 * path="/customer/recommendations.{format}",
	 * @SWG\Operation(
	 *     method="GET", 
	 *     summary="Get product recommendations for customer",
	 *     nickname = "Customer Redemptions",
	 *    @SWG\Parameter(
	 *    name = "identifier",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Customer identifier value (email, mobile, id, external_id)"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "limit",
	 *    type = "integer",
	 *    paramType = "query",
	 * 	  description = "Expected number of recommendations. Default 10"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "product_limit",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Number of products to be returned per recommmendation. Default 3"
	 *    ),
	 *    @SWG\ResponseMessage(
	 *    code = 200, 
	 *    message = "SUCCESS", 
	 *    responseModel = "CustomerRecommendationsResponseModel"
	 *    )
	 * ))
	 */
	private function recommendations( $version, $query_params ){

		$this->logger->debug( "Customer-Recommendations fetch: Start " );
		$customerController = new CustomerController();
		$should_return_user_id = strtolower( $query_params['user_id'] ) == 'true' ? true : false;
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_RECOMMENDATION_FETCH_SUCCESS";
		$org_id = $this->org_id;

        $identifier = $this->getCustomerIdentifierType($query_params);
		try{

			//TODO: Need to add pagintion support and sorting

			/* $start_id = $query_params['start_id'];
			 $end_id = $query_params['end_id'];
			$order = isset( $query_params['order'] ) ? $query_params['order'] : "ASC";
			$sort = "score"; */

			$new_identifier = ($identifier == 'id')? 'user_id' : $identifier;
			Util::saveLogForApiInputDetails( array( $new_identifier => $query_params[$identifier] ) );
			$customer = array();
			$limit = isset( $query_params['limit'] ) ? $query_params['limit'] : 10 ;
			$products_limit = isset( $query_params['product_limit'] ) ? $query_params['product_limit'] : 3;

			$this->logger->debug( "Fetching customer with identifier : ".$identifier );
			$user = $customerController->getCustomers( array($identifier => $query_params[$identifier]), true );
			if( !$user ){

				$this->logger->error( "User not found with given mobile / email / external_id" );
				throw new Exception( "ERR_USER_NOT_FOUND" );
			}else{

				$recommendations = $customerController->getCustomerRecommendations( $user->user_id, $limit, $products_limit, $parameters );
				Util::saveLogForApiInputDetails(
				array(
				'mobile' => $user->mobile,
				'email' => $user->email,
				'external_id' => $user->external_id,
				'user_id' => $user->user_id
				) );

				//TODO: Fetch product or category info based on id recieved from recommendation engine
				$rows = is_array( $recommendations ) ? count( $recommendations ) : 0;
				$start = 1;
				if( $should_return_user_id ){

					$customer = array( "user_id" => $user->user_id );
				}
				$customer = array_merge(
						$customer,
						array(
								"mobile" => $user->mobile,
								"email" => $user->email,
								"external_id" => $user->external_id,
								"firstname" => $user->first_name,
								"lastname" => $user->last_name,
								"count" => $rows,
								"recommendations" => array(
										"recommendation" => $recommendations
								)
						)
				);
			}
		}catch( Exception $e ){

			$this->logger->error( "Exception occured while fetching customer recommendations" );
			$this->logger->error( "Exception: ".$e->getMessage() );
			$item_status_code = $e->getMessage();
			$api_status_code = "FAIL";
			$customer = array(
					'mobile' => $query_params['mobile'],
					'email' => $query_params['email'],
					'external_id' => $query_params['external_id'],
					'user_id' => $query_params['id']
			);
		}
		$api_status = array(
				"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api["SUCCESS"],
				"code" => ErrorCodes::$api[$api_status_code],
				"message" => ErrorMessage::$api[$api_status_code]
		);
		$item_status = array(
				"success" => ErrorCodes::$customer[$item_status_code] == ErrorCodes::$customer["ERR_RECOMMENDATION_FETCH_SUCCESS"],
				"code" => ErrorCodes::$customer[$item_status_code],
				"message" => ErrorMessage::$customer[$item_status_code]
		);
		$customer = array_merge( $customer, array( "item_status" => $item_status ) );
		return 	array(
				"status" => $api_status,
				"customer" => $customer,
		);
	}

	/**
	 * Checks if the system supports the version passed as input
	 *
	 * @param $version
	 */

	public function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}
		return false;
	}

	public function checkMethod($method)
	{
		if(in_array(strtolower($method), array('add', 'update', 'get',
				'update_identity',
				'search', 'interaction', 'transactions', 'notes', 'preferences', 'redemptions',
				'subscriptions', 'recommendations',
				'referrals',
				'coupons','tickets','trackers'
		)))
		{
			return true;
		}
		return false;
	}

	
	/**
	 * @SWG\Api(
	 * path="/customer/referrals.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get referral statistics for a customer",
	 *     nickname = "Get Customer referrals",
	 *    @SWG\Parameter(name = "mobile",type = "string",paramType = "query", description = "Mobile number of customer"  ),
	 *    @SWG\Parameter(name = "external_id",type = "string",paramType = "query", description = "External Id  of customer"  ),
	 *    @SWG\Parameter(name = "email",type = "string",paramType = "query", description = "Email of customer"  ),
	 *    @SWG\Parameter(name = "campaign_token",type = "string",paramType = "query", description = "Campaign token to get the statistics for. Its optional and don't pass to get the default campaign "  ),
	 *    @SWG\Parameter(name = "start_date",type = "string",paramType = "query", description = "Start date filter"  ),
	 *    @SWG\Parameter(name = "end_date",type = "string",paramType = "query", description = "End date filter"  ),
	 *    @SWG\Parameter(name = "store_code",type = "string",paramType = "query", description = "Pass 'all' to get stats for all stores. Default is current store"  ),
	 *    @SWG\Parameter(name = "only_referral_code",type = "string",paramType = "query", description = "Pass 'true' to retrieve only referral code - No stats will be returned"  )
	 *    )
	 * ))
	 */
	
	/**
	 * @SWG\Model(
	 * id = "ReferralSetList",
	 * @SWG\Property(name = "campaign_token", type = "string", description = "Campaign token for the referral campaign to select. Pass empty to set default campaign" ),
	 * @SWG\Property(name = "referral_type", type = "array", items="$ref:ReferralSet",required=true )
	 * )
	 * */
	
	/**
	 * @SWG\Model(
	 * id="ReferralSet",
	 * @SWG\Property(name="type",type="string",required=true),
	 * @SWG\Property(name="referral",type="array",items="$ref:ReferralSingleSet",required=true)
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "ReferralSingleSet",
	 * @SWG\Property(name = "id", type = "string" ),
	 * @SWG\Property(name = "name", type = "string",required=true ),
	 * @SWG\Property(name = "identifier", type = "string",required=true ),
	 * @SWG\Property(name = "invited_on", type="string")
	 * )
	 * */
	
	/**
	 * @SWG\Model(
	 * id="CustomerSetList",
	 * @SWG\Property(name="customer",type="array",items="$ref:CustomerSet",required=true)
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id="CustomerSet",
	 * @SWG\Property(name="email",type="string"),
	 * @SWG\Property(name="mobile",type="string"),
	 * @SWG\Property(name="id",type="string"),
	 * @SWG\Property(name="external_id",type="string"),
	 * @SWG\Property(name="referrals",type="ReferralSetList",required=true)
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "ReferralRoot",
	 * @SWG\Property( name = "root", type = "CustomerSetList")
	 * )
	 */
	
	/**
	 * @SWG\Api(
	 * path="/customer/referrals.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Refer customers for a referral campaign",
	 * @SWG\Parameter(
	 * name = "request",paramType="body", type="ReferralRoot")
	 * ))
	 */
	private function referrals($version, $data, $query_params, $http_method)
	{

		require_once 'apiController/ApiReferralController.php';

		if($http_method=="GET")
			return $this->get_referrals($version, $query_params);
		else if ($http_method=="POST")
			return $this->post_referrals($data, $query_params);

	}


	private function post_referrals($data,$query_params)
	{

		$this->logger->debug("CustomerResource post_referrals input : ".print_r($data,true).print_r($query_params,true));

		global $currentorg;

		$customers=$data['root']['customer'];

		if (empty($customers)) {
			return array( 
				'status' => array( 
					'success' => false,
					'code' => ErrorCodes::$customer['ERR_REFERRAL_INVALID_INPUT'], 
					'message' => ErrorMessage::$customer['ERR_REFERRAL_INVALID_INPUT']
				), 
				'customers' => array(
					'customer' => $customers
				)
			);
		}
		if(!isset($customers[0]))
			$customers=array($customers);

		$req_count=count($customers);
		$success_count=0;
		$resp=array();

		$this->logger->info("intializing the referral controller");
		$referral_ctrlr=new ApiReferralController();

		foreach($customers as $customer)
		{

			$cust_resp=array();

			try{

				Util::saveLogForApiInputDetails(
				array(
				'mobile' => $customer['mobile'],
				'email' => $customer['email'],
				'external_id' => $customer['external_id'],
				'id' => $customer['id'],
				'user_id' => $customer['user_id']
				) );

                $identifier = $this->getCustomerIdentifierType($customer);

				$identifier_value=$customer[$identifier];

				$inp_referrals=$customer['referrals'];

				$invitees_payload=array();

				$campaign_token=$inp_referrals['campaign_token'];
				if(empty($campaign_token))
					$campaign_token="-1";

				$inp_referrals['referral_type']=isset($inp_referrals['referral_type'][0])?$inp_referrals['referral_type']:array($inp_referrals['referral_type']);

				foreach($inp_referrals['referral_type'] as $referral_type)
				{

					$type=strtoupper($referral_type['type']);
					if(!isset($referral_type['referral'][0]))
						$referral_type['referral']=array($referral_type['referral']);
					foreach($referral_type['referral'] as $referral)
						if(isset($referral['identifier']))
						$invitees_payload[]=array('ref_id'=>$referral['id'],'type'=>$type,'name'=>$referral['name'],'identifier'=>$referral['identifier'],'invited_on'=>$referral['invited_on']);
				}

				$this->logger->debug("Invitees payload : ".print_r($invitees_payload,true).", campaign token : $campaign_token, $identifier: $identifier_value");

				$invitees_resp=$referral_ctrlr->invite($identifier, $identifier_value, $invitees_payload,$campaign_token);

			}catch(Exception $e)
			{

				$error_key=$e->getMessage();
				$status=array('success'=>'false',
						'code' => ErrorCodes::getCustomerErrorCode( $error_key ) ,
						'message' => ErrorMessage::getCustomerErrorMessage( $error_key )
				);
				foreach(array("mobile","email","external_id","id") as $id)
					if(isset($customer[$id]))
					$cust_resp[$id]=$customer[$id];
				$cust_resp['item_status']=$status;
				$resp[]=$cust_resp;
				continue;
			}

			$cust_resp=array(
					'email'=>$invitees_resp['customer']['email'],
					'mobile'=>$invitees_resp['customer']['mobile'],
					'external_id'=>$invitees_resp['customer']['external_id'],
					'firstname'=>$invitees_resp['customer']['firstname'],
					'lastname'=>$invitees_resp['customer']['lastname'],
			);

			if((isset($query_params['user_id']) && $query_params['user_id']=="true") || $identifier=="id")
				$cust_resp['id']=$invitees_resp['customer']['id'];

			$ref_resp=array();
			foreach($invitees_resp['invitees'] as $invitee)
			{
//                                if($invitee->inviteeMeta->errorCode == 1012){
//                                    $invitee->inviteeMeta->errorCode .= "99";
//                                }
                                
                                if($invitee['code'] == 1012)
                                        $invitee['code'] .= "99";

                                $this->logger->info("invitee code is :",$invitee['code']);

                                if(!isset($ref_resp[$invitee['type']]))
					$ref_resp[$invitee['type']]=array();

				$inv['id']=$invitee['ref_id'];
				$inv['name']=$invitee['name'];
				$inv['identifier']=$invitee['identifier'];
				$inv['invited_on']=$invitee['invited_on'];
				$inv['status']=array(
						'success'=>$invitee['success']?"true":"false",
						'code'=>$invitee['code'],
						'message'=>$invitee['message']
				);

				$ref_resp[$invitee['type']][]=$inv;
			}

			$cust_resp['referrals']['referral_type']=array();
			foreach($ref_resp as $type=>$ref_data)
				$cust_resp['referrals']['referral_type'][]=array(
						'type'=>$type,
						'referral'=>$ref_data
				);

			$cust_resp['item_status']=array(
					'success'=>"true",
					'code'=>ErrorCodes::$customer['ERR_REFERRAL_INVITE_SUCCESS'],
					'message'=>ErrorMessage::$customer['ERR_REFERRAL_INVITE_SUCCESS']
			);

			$success_count++;
			$resp[]=$cust_resp;

		}

		$success="false";
		if($success_count==0)
			$status_code="FAIL";
		elseif($success_count!=count($customers))
		$status_code="PARTIAL_SUCCESS";
		else
		{
			$status_code="SUCCESS";
			$success="true";
		}

		$result=array('status'=>array('success'=>$success,'code'=>ErrorCodes::$api[$status_code],'message'=>ErrorMessage::$api[$status_code]),'customers'=>array('customer'=>$resp));

		$this->logger->debug("CustomerResource post_referrals output : ".print_r($result,true));

		return $result;

	}

	private function get_referrals($version, $query_params)
	{

		require_once "apiModel/class.ApiVoucherModelExtension.php";

		$referral_ctrlr=new ApiReferralController();

		//by default campaign
		$campaign_token="-1";

		$customer=$query_params;
        $identifier = $this->getCustomerIdentifierType($customer);

		if(isset($customer['campaign_token']) && !empty($customer['campaign_token']))
			$campaign_token=$customer['campaign_token'];

		$start_date = $query_params['start_date'];
		$end_date = $query_params['end_date'];

		$get_only_referral_code=isset($query_params['only_referral_code'])&&strtolower($query_params['only_referral_code'])=="true"?true:false;

		$store_code = $query_params['store_code'];
		$get_all_stores=strtolower($store_code)=="all"?true:false;

		$should_return_user_id = isset($query_params['user_id']) &&
		strtolower($query_params['user_id'])=='true'?true:false;

		$resp=array($identifier=>$customer[$identifier]	);

		try{

			$customer_stats=$referral_ctrlr->getStats($identifier, $customer[$identifier], $campaign_token, $start_date, $end_date, $store_code, $get_all_stores, $get_only_referral_code);

		}catch(Exception $e)
		{

			$error_key=$e->getMessage();
			$status=array('success'=>'false',
					'code' => ErrorCodes::getCustomerErrorCode( $error_key ) ,
					'message' => ErrorMessage::getCustomerErrorMessage( $error_key )
			);
			$resp['item_status']=$status;
			return array('status'=>array('success'=>'false','code'=>ErrorCodes::$api['FAIL'],'message'=>ErrorMessage::$api['FAIL']),'customer'=>$resp);
		}

		$resp=$customer_stats['customer'];
		if((isset($query_params['user_id']) && $query_params['user_id']=="true") || $identifier=="id")
			$cust_resp['id']=$customer_stats['customer']['id'];
		else
			unset($resp['id']);

		$resp['referral_code']=$customer_stats['referral_code'];


		$stats=$customer_stats['stats'];

		$stats_resp=array('invitees'=>array(),'referees'=>array(),'incentives'=>array());
		foreach($stats['invitees'] as $referral_type=>$invitee_ref_type)
		{

			if(!isset($stats_resp['invitees']['referral_type']))
				$stats_resp['invitees']['referral_type']=array();

			$invitees=$invitee_ref_type;

			$ref_resp=array('type'=>$referral_type);
			$ref_resp['invitee']=array();
			foreach($invitees as $invitee)
			{
				$inv=array('identifier'=>$invitee['identifier']);
				$inv['name']=$invitee['name'];
				$inv['invited_on']=$invitee['invited_on'];
				$inv['till']['code']=$invitee['store_code'];
				$inv['till']['name']=$invitee['store_name'];
				$ref_resp['invitee'][]=$inv;
			}
			$stats_resp['invitees']['referral_type'][]=$ref_resp;
		}

		foreach($stats['referees'] as $event_type=>$referee_eve_type)
		{

			if(!isset($stats_resp['referees']['referee']))
				$stats_resp['referees']['referee']=array();

			foreach($referee_eve_type as $referee)
			{

				$ref=array('event_type'=>$event_type);

				if($should_return_user_id)
					$ref['user_id']=$referee['user_id'];

				$usr=UserProfile::getById($referee['user_id']);
				$ref['firstname']=$usr->first_name;
				$ref['lastname']=$usr->last_name;
				$ref['mobile']=$usr->mobile;
				$ref['email']=$usr->email;
				$ref['external_id']=UserProfile::getExternalId($referee['user_id']);
				$ref['added_on']=date("Y-m-d H:i:s",strtotime($referee['added_on']));

				$stats_resp['referees']['referee'][]=$ref;

			}

		}

		foreach($stats['vouchers'] as $event_type=>$eve_vouchers)
		{
			if(!isset($stats_resp['incentives']['event_type']))
				$stats_resp['incentives']['event_type']=array();

			$ref=array('name'=>$event_type,'coupons'=>array('coupon'=>array()));

			foreach($eve_vouchers as $voucher)
			{

				$coupon=new ApiVoucherModelExtension();
				$coupon->loadById($voucher['voucher_id']);

				$ref['coupons']['coupon'][]=array(
						'code'=>$coupon->getVoucherCode(),
						'value'=>$coupon->getCouponValue(),
						'valid_till'=>$coupon->getExpiryDate(),
						'redemption_info'=>$coupon->getRedemptionInfo()
				);

			}

			$stats_resp['incentives']['event_type'][]=$ref;

		}

		return array(
				'status'=>array(
						'success'=>"true",
						'code'=>ErrorCodes::$api['SUCCESS'],
						'message'=>ErrorMessage::$api['SUCCESS']
				),
				'customer'=>
				array_merge($resp,$stats_resp,array('item_status'=>
						array("success"=>"true",
								"code"=>ErrorCodes::$customer['ERR_REFERRAL_STATS_SUCCESS'],
								'message'=>ErrorMessage::$customer['ERR_REFERRAL_STATS_SUCCESS']
						)
				))
		);



	}


	private function saveCustomerSubscriptions(UserProfile $user, $subscription, $identifier, $input)
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
		if($identifier!="id")
			$sub_det[$identifier]=$input;
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

	private function translateToSendingRule($prioritys,$status)
	{
		$this->logger->info("prioritys for translating : ".implode(",",$prioritys));
		$prioritys=array_unique($prioritys);
		$sendingrule="";
		$this->logger->info("translating to sending rule for the prioritys : (".implode(",",$prioritys)."), status : $status");
		if(count($prioritys)==1)
		{
			$priority=array_pop($prioritys);
			$priority=strtoupper($priority);
			if(($priority=="TRANS" && !$status) || ($priority=="BULK" && $status))
				$sendingrule='NOPERSONALIZED';
			if(($priority=="TRANS" && $status) || ($priority=="BULK" && !$status))
				$sendingrule='NOBULK';
		}else
		{
			if($status)
				$sendingrule="NONE";
			else
				$sendingrule="ALL";
		}
		$this->logger->debug("sendingrule : $sendingrule");
		return $sendingrule;
	}

	
	/**
	 * @SWG\Api(
	 * path="/customer/coupons.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Fetches coupons issued to the customer",
	 *     nickname = "Get Transaction redemptions",
	 *    @SWG\Parameter(name = "email", type = "string", paramType = "query", description = "Customer email id" ),
	 *    @SWG\Parameter(name = "mobile", type = "string", paramType = "query", description = "Mobile number of the customer" ),
	 *    @SWG\Parameter(name = "external_id", type = "string", paramType = "query", description = "Customer external id" ),
	 *    @SWG\Parameter(name = "id", type = "integer", paramType = "query", description = "Customer id" ),
	 *    @SWG\Parameter(name = "start_date", type = "string", paramType = "query", description = "Filters coupons issued after this date" ),
	 *    @SWG\Parameter(name = "end_date", type = "string", paramType = "query", description = "Filters coupons issued before this date" ),
	 *    @SWG\Parameter(name = "status", type = "string",  enum = "['redeemed', 'expired', 'active']", paramType = "query", description = "Coupon status. Multiple status separated by semicolon (;) " ),
	 *    @SWG\Parameter(name = "series_id", type = "string", paramType = "query", description = "Returns coupons belonging to this series only" ),
	 *    @SWG\Parameter(name = "type", type = "string", enum = "['CAMPAIGN', 'DVS', 'ALLIANCE']", paramType = "query", description = "Filters coupons of this type" ),
	 *    @SWG\Parameter(name = "order_by", type = "string", enum = "['created_date', 'amount', 'valid_till']", paramType = "query" ),
	 *    @SWG\Parameter(name = "sort_order", type = "string", enum = "['asc', 'desc']", paramType = "query" )
	 *    )
	 * )
	 */
	private function coupons($version, $data, $query_params,$http_method)
	{
		$this->logger->info("customer coupons : (query params :".implode(" - ",$query_params).")");
			
		$controller=new ApiCustomerController();
			
		$identifier=null;
		//find the identifier;
        $identifier = $this->getCustomerIdentifierType($query_params);
			
		if(isset($query_params['type']) && !empty($query_params['type']))
			$types=explode(",",$types);
			
		foreach(array('start_date','end_date','series_id','order_by','sort_order') as $inp)
		{
			if(isset($query_params[$inp]) && !empty($query_params[$inp]))
				$$inp=$query_params[$inp];
			else
				$$inp="";
		}
			
		$identifier_values=explode(",",$query_params[$identifier]);
			
		$count=count($identifier_values);
		$success_count=0;
			
		$resp=array();
			
		foreach($identifier_values as $identifier_value)
		{

			$single_resp=array();
			$single_resp[$identifier]=$identifier_value;

			try{
				$cp_data=$controller->getCoupons($identifier, $identifier_value, $types,$status,$start_date,$end_date,$series_id,$order_by,$sort_order);
					
				$single_resp=$cp_data['user'];
				if(!isset($query_params['user_id']) || $query_params['user_id']!="true")
					unset($single_resp['id']);
					
				$single_resp['coupons']=array();
					
				$coupons=array();
				foreach($cp_data['coupons'] as $coupon)
				{
					$server_timezone = date_default_timezone_get();

					$created_store = StoreProfile::getById($coupon['created_by']);
					$created_timezone = $created_store->getStoreTimeZoneLabel();
					if(!$created_timezone)
						$created_timezone = $this->currentorg->getOrgTimeZoneLabel();

					$store_timezone = $this->currentuser->getStoreTimeZoneLabel();
					if( !isset( $store_timezone ) )
						$store_timezone = $this->currentorg->getOrgTimeZoneLabel();

					$coupon['created_date'] = Util::convertOneTimezoneToAnotherTimezone($coupon['created_date'], $created_timezone, $store_timezone);

					date_default_timezone_set($store_timezone);
						
					$coup=array(
							'id'=>$coupon['voucher_id'],
							'series_id'=>$coupon['voucher_series_id'],
							'series_name'=>$coupon['description'],
							'redemption_count'=>$coupon['redeem_count'],
							'created_date'=>date("c",strtotime($coupon['created_date'])),
							'valid_till'=>$coupon['valid_till'],
							'code' => ApiUtil::trimCouponCode($coupon['voucher_code']),
							'transaction_number'=>$coupon['bill_number'],
							'issued_at'=>array(
									'code'=>empty($coupon['issued_store_code'])?$coupon['issued_till_code']:$coupon['issued_store_code'],
									'name'=>empty($coupon['issued_store_name'])?$coupon['issued_till_name']:$coupon['issued_store_name']
							),
							'redemptions'=>array()
					);



					$redeems=array();

					foreach($coupon['redemptions'] as $redemp)
					{
						$used_store = StoreProfile::getById($redemp['used_at_store']);
						$used_timezone = $used_store->getStoreTimeZoneLabel();
						if(!$used_timezone)
							$used_timezone = $this->currentorg->getOrgTimeZoneLabel();
						$redemp['used_date'] = Util::convertOneTimezoneToAnotherTimezone($redemp['used_date'], $used_timezone, $store_timezone);
							
						$redeems[]=array(
								'date'=>date("c",strtotime($redemp['used_date'])),
								'transaction_number'=>$redemp['bill_number'],
								'redeemed_at'=>array(
										'code'=>$redemp['store_code'],
										'name'=>$redemp['store_name']
								)
						);
					}
					date_default_timezone_set($server_timezone);
					if(!empty($redeems))
						$coup['redemptions']['redemption']=$redeems;

					$coupons[]=$coup;

				}
					
				if(!empty($coupons))
					$single_resp['coupons']['coupon']=$coupons;

				$success_count++;
					
				$status=array(
						'success'=>'true',
						'code'=>ErrorCodes::$customer['ERR_COUPONS_SUCCESS'],
						'message'=>ErrorMessage::$customer['ERR_COUPONS_SUCCESS']
				);
					
			}catch(Exception $e)
			{
				$error_key=$e->getMessage();
				$status = array(
						'success' => 'true' ,
						'code' => ErrorCodes::getCustomerErrorCode( $error_key ),
						'message' => ErrorMessage::getCustomerErrorMessage( $error_key )
				);
					
			}

			$single_resp['item_status']=$status;
			$resp[]=$single_resp;

		}
			
		if($count==$success_count)
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
			);
		elseif($success_count==0)
		$api_status=array(
				'success'=>'false',
				'code'=>ErrorCodes::$api['FAIL'],
				'message'=>ErrorMessage::$api['FAIL']
		);
		else
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['PARTIAL_SUCCESS'],
					'message'=>ErrorMessage::$api['PARTIAL_SUCCESS']
			);
			
		return array(
				'status'=>$api_status,
				'customers'=>array('customer'=>$resp)
		);
			
	}
	
	
	/**
	 * @SWG\Api(
	 * path="/customer/tickets.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get/Search tickets raised",
	 *     nickname = "Get CCMS Tickets",
	 *    @SWG\Parameter(name = "mobile",type = "string",paramType = "query", description = "Mobile number of customer"  ),
	 *    @SWG\Parameter(name = "external_id",type = "string",paramType = "query", description = "External Id  of customer"  ),
	 *    @SWG\Parameter(name = "email",type = "string",paramType = "query", description = "Email of customer"  ),
	 *    @SWG\Parameter(name = "status",type = "string",paramType = "query", description = "Status filter for the ticket search"  ),
	 *    @SWG\Parameter(name = "start_date",type = "string",paramType = "query", description = "Start date filter"  ),
	 *    @SWG\Parameter(name = "end_date",type = "string",paramType = "query", description = "End date filter"  ),
	 *    @SWG\Parameter(name = "priority",type = "string",paramType = "query", description = "Priority filter"  ),
	 *    @SWG\Parameter(name = "department",type = "string",paramType = "query", description = "Department filter"  ),
	 *    @SWG\Parameter(name = "ticket_code",type = "string",paramType = "query", description = "Get the specific ticket by code"  ),
	 *    @SWG\Parameter(name = "only_current_store",type = "string",paramType = "query", description = "Get the tickets raised from the current store. Default- false"  ),
	 *    @SWG\Parameter(name = "reported_from",type = "string",paramType = "query", description = "Reported from filter"  ),
	 *    @SWG\Parameter(name = "type",type = "string",paramType = "query", description = "Ticket type filter"  )
	 *    )
	 * ))
	 */
	
	/**
	 * @SWG\Api(
	 * path="/customer/tickets.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Create a ticket for user",
	 *     nickname = "Create Ticket",
	 * @SWG\Parameter(
	 * name = "request",paramType="body", type="TicketRoot")
	 * ))
	 */
	
	/**
	 * @SWG\Model(
	 * id="TicketSet",
	 * @SWG\Property(name="status",type="string",required=true),
	 * @SWG\Property(name="priority",type="string",required=true),
	 * @SWG\Property(name="department",type="string",required=true),
	 * @SWG\Property(name="subject",type="string",required=true),
	 * @SWG\Property(name="message",type="string",required=true),
	 * @SWG\Property(name="custom_fields",type="CustomFieldSet")
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id="CustomFieldSet",
	 * @SWG\Property(name="field",type="array",items="$ref:CustomField")
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id="CustomField",
	 * @SWG\Property(name="name",type="string",required=true),
	 * @SWG\Property(name="value",type="string",required=true)
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id="CustomerSet",
	 * @SWG\Property(name="email",type="string"),
	 * @SWG\Property(name="mobile",type="string"),
	 * @SWG\Property(name="external_id",type="string"),
	 * @SWG\Property(name="id",type="integer"),
	 * @SWG\Property(name="ticket",type="TicketSet")
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id="CustomerSetList",
	 * @SWG\Property(name="customer",type="array",items="$ref:CustomerSet")
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id="TicketRoot",
	 * @SWG\Property(name="root",type="CustomerSetList",required=true)
	 * )
	 */
	
	private function tickets($version, $data, $query_params,$http_method)
	{
			
		require_once "apiController/ApiCcmsController.php";

		switch(strtolower($http_method))
		{
			case 'get':
				$result=$this->get_tickets($version, $data, $query_params,$http_method);
				break;
			case 'post':
				$result=$this->post_tickets($version, $data, $query_params,$http_method);
				break;
		}
			
		return $result;
			
	}

	private function get_tickets($version, $data, $query_params,$http_method)
	{
			
		$this->logger->info("customer tickets GET : (query params :".implode(" - ",$query_params).")");
			
		$controller=new ApiCustomerController();
			
		$identifier=null;
		//find the identifier;
        $identifier = $this->getCustomerIdentifierType($query_params);
			
		if(isset($query_params['type']) && !empty($query_params['type']))
			$types=explode(",",$types);
			
		foreach(array('status','priority','department','ticket_code','only_current_store','reported_from','type','start_date','end_date') as $inp)
		{
			if(isset($query_params[$inp]) && !empty($query_params[$inp]))
				$$inp=$query_params[$inp];
			else
				$$inp="";
		}
			
		if(strtolower($only_current_store)=="true")
			$only_current_store=true;
		else
			$only_current_store=false;
			
			
		$identifier_values=explode(",",$query_params[$identifier]);
			
		$count=count($identifier_values);
			
		$success_count=0;
			
		foreach($identifier_values as $identifier_value)
		{

			$single_resp=array();
			$single_resp[$identifier]=$identifier_value;

			try{
					
					
				$tkt_data=$controller->getTickets($identifier,$identifier_value,$status,$priority,$department,$start_date,$end_date,$ticket_code,$only_current_store,$reported_from,$type);
					
				$single_resp=$tkt_data['user'];
				if(!isset($query_params['user_id']) || $query_params['user_id']!="true")
					unset($single_resp['id']);
					
				$single_resp['tickets']=array();
					
				if(!empty($tkt_data['tickets']))
					$single_resp['tickets']['ticket']=$tkt_data['tickets'];
					
				$success_count++;
					
				$item_status=array(
						'success'=>'true',
						'code'=>ErrorCodes::$customer['ERR_TICKET_GET_SUCCESS'],
						'message'=>ErrorMessage::$customer['ERR_TICKET_GET_SUCCESS']
				);
					
			}catch(Exception $e)
			{
				$error_code=$e->getMessage();
				$item_status=array(
						'success'=>'false',
						'code'=>ErrorCodes::$customer[$error_code],
						'message'=>ErrorMessage::$customer[$error_code]
				);
			}

			$single_resp['item_status']=$item_status;

			$resp[]=$single_resp;

		}
			
			
		if($count==$success_count)
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
			);
		elseif($success_count==0)
		$api_status=array(
				'success'=>'false',
				'code'=>ErrorCodes::$api['FAIL'],
				'message'=>ErrorMessage::$api['FAIL']
		);
		else
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['PARTIAL_SUCCESS'],
					'message'=>ErrorMessage::$api['PARTIAL_SUCCESS']
			);
			
		return array(
				'status'=>$api_status,
				'customers'=>array('customer'=>$resp)
		);
			
			
			
	}


	private function post_tickets($version, $data, $query_params,$http_method)
	{
			
		$data=$data['root'];
			
		$count=count($data['customer']);
		$success_count=0;
			
		$this->logger->info("customer tickets POST : customer count: $count, version: $version, query params:".implode(" - ",$query_params));
			
		$controller=new ApiCcmsController();
		$C_org_controller = new ApiOrganizationController( );
			
		$assigned_to=$this->config_mgr->getKey('SELECT_DEFAULT_STORE_FOR_FEEDBACK');
			
		$is_hierarchal_assignment_enabled=$this->config_mgr->getKey(CCMS_IS_HIERARCHICAL_ASSIGNMENT_ENABLED);
		if($is_hierarchal_assignment_enabled)
		{
				
			$hierarchal_assignment_entity_type=$this->config_mgr->getKey( CCMS_HIERARCHICAL_ENTITY_TYPE );
			$this->logger->info("Hierarchal assignedment is enabled. entity type is $hierarchal_assignment_entity_type");

			$manager_id = false;
			switch($hierarchal_assignment_entity_type)
			{
					
				case 'ZONE' :
					$hash_details=$C_org_controller->StoreTillController->load($this->currentuser->user_id);
					$store_id=$hash_details['store_id'];
					$zones=$C_org_controller->StoreController->getParentZone( $store_id );
					if( count( $zones ) > 0 ){
						$zone_id=$zones[0];
						$zone_managers=$C_org_controller->ZoneController->getManagers($zone_id);
						$manager_id = $zone_managers[0]['id'];
					}
					break;
						
				case 'CONCEPT' :
					$hash_details=$C_org_controller->StoreTillController->load($this->currentuser->user_id);
					$store_id=$hash_details['store_id'];
					$concepts=$C_org_controller->StoreController->getParentConcept( $store_id );
					if(count($concepts)>0){
						$concept_id=$concepts[0];
						$concept_managers=$C_org_controller->ConceptController->getManagers( $concept_id );
						$manager_id=$concept_managers[0]['id'];
					}

			}
				
			if( $manager_id )
			{
				$assigned_to = $manager_id;
				$this->logger->info("assigning to manager");
			}
		}
			
		$this->logger->debug("selected assigned_to : $assigned_to");
			
		if(empty($assigned_to))
                    throw new Exception('ERR_TICKET_EMPTY_ASSIGNED_TO');
                
		$resp=array();
			
		$success_count=0;
		$count=count($data['customer']);
			
		foreach($data['customer'] as $payload)
		{

			$cust_resp=$payload;

			try{

                try {
                    $identifier = $this->getCustomerIdentifierType($payload);
                }
                catch(Exception $ex)
                {
                    throw new Exception('ERR_NO_IDENTIFIER');
                }
				$identifier_value=$payload[$identifier];
					
				$user=UserProfile::getByData(array($identifier=>$identifier_value));
				$status = $user->load(true);

				$cust_resp=array(
						'firstname'=>$user->first_name,
						'lastname'=>$user->last_name,
						'email'=>$user->email,
						'external_id'=>$user->external_id,
						'mobile'=>$user->mobile,
						'id'=>$user->user_id
				);
				if(!isset($query_params['user_id']) || strtolower($query_params['user_id'])!="true")
					unset($cust_resp['id']);
					
				$cust_resp['ticket']=array_merge(array('code'=>''),$payload['ticket']);
					
					
				$tkt=$payload['ticket'];
					
				$map=array(
						'status'=>'status',
						'priority'=>'priority',
						'department'=>'department',
						'issue_code'=>'subject',
						'issue_name'=>'message',
						'ticket_code'=>'code',
				);
					
				$tkt_payload=array(
						'assigned_to'=>$assigned_to,
						'customer_id'=>$user->user_id,
						'reported_by'=>'CLIENT'
				);
					
				foreach($map as $key=>$value)
					$tkt_payload[$key]=$tkt[$value];
					
				if(empty($tkt_payload['issue_code']))
					throw new Exception('ERR_TICKET_EMPTY_SUBJECT');
					
				$custom_fields=array();
				if(!isset($tkt['custom_fields']['field'][0]))
					$tkt['custom_fields']['field']=array($tkt['custom_fields']['field']);
				foreach($tkt['custom_fields']['field'] as $cf)
				{
					$cf_name = (string)$cf['name'];
					$cf_value_json = (string) $cf['value'];
					if(json_decode($cf_value_json)==null)
						$cf_value_json='"'.$cf_value_json.'"';
					$custom_fields['custom_field__'.$cf_name] = json_decode( $cf_value_json , true );
				}
				$tkt_payload = array_merge( $tkt_payload , $custom_fields );
					
				$controller->add($tkt_payload);
					
				$cust_resp['ticket']['code']=$controller->ticket_code;
					
				$this->logger->debug("ticket payload: ".print_r($tkt_payload,true));
					
				$cust_resp['item_status']=array(
						'success'=>'true',
						'code'=>ErrorCodes::$customer['ERR_TICKET_ADD_SUCCESS'],
						'message'=>ErrorMessage::$customer['ERR_TICKET_ADD_SUCCESS']
				);
					
				$success_count++;
					
			}catch(Exception $e)
			{
					
				$this->logger->error("Exception thrown: ".$e->getMessage());
					
				$error_code=$e->getMessage();
				if($e->getMessage()=="Issue With The Same Code Has Been Registered For The Customer")
					$error_code='ERR_TICKET_CODE_EXISTS';
					
				$error_code=isset(ErrorCodes::$customer[$error_code])?$error_code:'ERR_TICKET_ADD_FAILED';
					
				$cust_resp['item_status']=array(
						'success'=>'true',
						'code'=>ErrorCodes::$customer[$error_code],
						'message'=>ErrorMessage::$customer[$error_code]
				);
					
			}

			$resp[]=$cust_resp;

		}
			
		if($count==$success_count && $count!=0)
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['SUCCESS'],
					'message'=>ErrorMessage::$api['SUCCESS']
			);
		elseif($success_count==0)
		$api_status=array(
				'success'=>'false',
				'code'=>ErrorCodes::$api['FAIL'],
				'message'=>ErrorMessage::$api['FAIL']
		);
		else
			$api_status=array(
					'success'=>'true',
					'code'=>ErrorCodes::$api['PARTIAL_SUCCESS'],
					'message'=>ErrorMessage::$api['PARTIAL_SUCCESS']
			);
			
			
		return array(
				'status'=>$api_status,
				'customers'=>array('customer'=>$resp)
		);
			
	}


	/** Get trackers for a particular user
	 * @param $version
	 * @param $data
	 * @param $query_params
	 * @param $http_method
	 * @return array
	 */


	private function trackers($version, $data, $query_params, $http_method)
	{
		$http_method = strtolower($http_method);
		if ($http_method == 'get') {
			$this->logger->debug("Found GET Request, trying to fetch the trackers");
			return $this->getTrackers($query_params);
		} else if ($http_method == 'post') {
			$this->logger->debug("Found POST Request, which is not supported for trackers");
			$e = new UnsupportedMethodException(ErrorMessage::$api['UNSUPPORTED_OPERATION'],
				ErrorCodes::$api['UNSUPPORTED_OPERATION']);
			throw $e;
		}
	}


	private function getTrackers($query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$should_return_user_id = $query_params['user_id'] == 'true' ? true : false;
		$customerController = new ApiCustomerController();

		$api_status_code = 'SUCCESS';
		$org_id = $this->org_id;

		$customers = $query_params;
		$identifier = $this->getCustomerIdentifierType($customers);


		$customer_array = StringUtils::strexplode( ',' , $customers[ $identifier ] );
		$customers = array();
		global $error_count;
		$error_count = 0;
		if(is_array($customer_array))
		{
			foreach($customer_array as $value)
			{
				$item_status_code = "ERR_CUSTOMER_TRACKERS_SUCCESS_RETRIEVED";
				try
				{
					$input[$identifier] = $value;
					Util::saveLogForApiInputDetails(
						array(
							'mobile' => $input['mobile'],
							'email' => $input['email'],
							'external_id' => $input['external_id']
						) );
					$customer = $customerController->getTrackers( array( $identifier => $value));
					Util::saveLogForApiInputDetails(
						array(
							'mobile' => $customer['mobile'],
							'email' => $customer['email'],
							'external_id' => $customer['external_id'],
							'user_id' => $customer['user_id']
						) );
				}
				catch(Exception $e)
				{

					$this->logger->error("CustomerResource::getTrackers() ERROR => ".$e->getMessage());
					$item_status_code = $e->getMessage();

					$temp_identifier = ($identifier == 'id')? 'user_id' : $identifier;
					$customer[ $temp_identifier ] = $value;

					$error_count++;
				}
				$customer['item_status'] = array(
					"success" => ErrorCodes::$customer[$item_status_code] ==
					ErrorCodes::$customer["ERR_CUSTOMER_TRACKERS_SUCCESS_RETRIEVED"] ? true : false,
					"code" => ErrorCodes::$customer[$item_status_code],
					"message" => ErrorMessage::$customer[$item_status_code]
				);
				$arr_item_status_codes[] = $customer['item_status']['code'];
				array_push($customers, $customer);
			}
		}

		if(count($customer_array) == $error_count )
			$api_status_code = "FAIL";
		else if($error_count > 0)
			$api_status_code = "PARTIAL_SUCCESS";
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$api_status = array(
			"success" => ErrorCodes::$api[$api_status_code] ==
			ErrorCodes::$api["SUCCESS"] ? true : false,
			"code" => ErrorCodes::$api[$api_status_code],
			"message" => ErrorMessage::$api[$api_status_code]
		);
		return array(
			"status" => $api_status,
			"customer" => $customers
		);
	}

}
