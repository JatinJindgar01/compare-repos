<?php
//delete
require_once "resource.php";
//TODO: referes to cheetah 
require_once "helper/coupons/CouponManager.php";
//TODO: referes to cheetah 
require_once "apiHelper/voucher.php";
require_once "apiModel/class.ApiVoucherSeriesModelExtension.php";
require_once "apiModel/class.ApiVoucherModelExtension.php";
require_once "helper/Util.php";
require_once "services/luci/service.php";

/**
 * Handles all coupon related api calls.*
 *  
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/coupon",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class CouponResource extends BaseResource{
	
	private $config_mgr;
	private $listener_mgr;
	
	function __construct()
	{
		parent::__construct();
		$this->config_mgr = new ConfigManager($this->currentorg->org_id);
		//$this->listener_mgr = new ListenersMgr();	
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
				
				case 'resend' : 
					
					$result = $this->resend($query_params); 
					break;	
					
				case 'redeem' : 
					
					$result = $this->redeem($version, $data);
					break;
					
				case 'get' :

					$result = $this->get($version, $query_params);
					break;
					
				case 'isredeemable' :
					$result = $this->isRedeemable($query_params);	
					break;
					
				case 'series' :
					$result = $this->series($query_params,$version);	
					break;
					

				case 'issue' :
					
					$result = $this->issue($version, $data, $query_params);
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
	 * Resends a coupon to a customer
	 * 
	 * @param $data
	 * @param $query_params :-
	 * 	  code : voucher_code to be resent
	 * 	   id  : id of the voucher to be resent
	 * 	  mobile : customer mobile		 
	 * 	  email : customer email
	 * 	  external_id : external id of the customer	
	 */
	/**
	 * @SWG\Api(
	 * path="/coupon/resend.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Resend a coupon code",
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Coupon ID"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "code",
	 *    type = "string",
	 *    paramType = "query",
	 * description = "Coupon code to resend"
	 *    )
	 * ))
	 */
	
	private function resend($query_params)
	{
		global $gbl_item_status_codes;
		$this->logger->debug("Resending voucher : $code");
		$voucher_code = $query_params['code'];
		$voucher_id = $query_params['id'];
		
		$this->logger->debug("Resend voucher: $voucher_code, $voucher_id");
		
		if(empty($voucher_code) && empty($voucher_id)){
			$this->logger->debug("Both voucher_code & voucher_id empty");
			throw new InvalidInputException(ErrorMessage::$coupon['ERR_INVALID_INPUT'], ErrorCodes::$coupon['ERR_INVALID_INPUT']);
		}
		
		$coupon_key = ''; $coupon_identifier = '';
		try{
			$coupon_model = new ApiVoucherModelExtension();
			
			if(!empty($voucher_code)){
				$this->logger->debug("Loading by voucher_code $voucher_code");
				$coupon_model->loadByCode($voucher_code);
				$coupon_key = 'code';
				$coupon_identifier = $voucher_code;
			}else{
				$this->logger->debug("Loading by voucher_id $voucher_id");
				$coupon_model->loadById($voucher_id);
				$coupon_key = 'id';
				$coupon_identifier = $voucher_id;
			}
				
			if(!($coupon_model->getVoucherId() > 0) || ($coupon_model->getOrgId() != $this->currentorg->org_id)){
				$this->logger->error("Invalid coupon code $voucher_code");
				$response['status'] = array(
									'success' => 'false',
									'code' => ErrorCodes::$api['FAIL'],
									'message' => ErrorMessage::$api['FAIL']	
								);	
												
				$response['coupons']['coupon'] = array(
												  $coupon_key => $coupon_identifier,
												  'item_status' => array(
																	 'success'	=> 'false',
												  					 'code' => ErrorCodes::$coupon['ERR_INVALID_COUPON_CODE'],
																	 'message' => ErrorMessage::$coupon['ERR_INVALID_COUPON_CODE']
																 )
												);
				$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
				return $response;								 
			}
			
			/**
			if($user->user_id != $coupon_model->getIssuedTo()){
				$this->logger->error("Coupon issued to different user: $user->user_id");
				throw new InvalidCouponUserException(ErrorMessage::$coupon['ERR_INVALID_COUPON_USER'], ErrorCodes::$coupon['ERR_INVALID_COUPON_USER']);
			}**/
			$voucher_code = $coupon_model->getVoucherCode();
			$user_id = $coupon_model->getCurrentUser();
			
			Util::saveLogForApiInputDetails(
					array(
							'user_id' => $user_id,
							'coupon_code' => $voucher_code
					) );
			$campaignModule = new CampaignsModule();
			$campaignModule->resendvoucherApiAction($voucher_code, $user_id);
			$old_api_response_key = $campaignModule->data['api_status']['key'];
			if($old_api_response_key != 'VOUCHER_ERR_SUCCESS')
			{
				$response = array();
				$response['status'] = array(
						'success' => 'false',
						'code' => ErrorCodes::$api['FAIL'],
						'message' => ErrorMessage::$api['FAIL']
				);
				$new_api_error_code = $old_api_response_key;
				$response['coupons']['coupon'] = array(
						$coupon_key => $coupon_identifier,
						'item_status' => array(
								'success'	=> 'false',
								'code' => ErrorCodes::$coupon[$new_api_error_code],
								'message' => ErrorMessage::$coupon[$new_api_error_code]
						)
				);
				$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
				return $response;
			}
			//TODO: throw Exception from $campaignModule->data['api_status']
			
			
			/* 
			$user = UserProfile::getById($user_id);
			$coupon_series = $coupon_model->getCouponSeries();
			$sms_template = $coupon_series->getSMSTemplate();
			//if($sms_template == "")
		        $org_name = $this->currentorg->name; 
			$sms_template = "Dear {{cust_name}}, Your voucher code for {{description}} redeemable at $org_name is {{voucher_code}}. T&C apply.";
		       //"Dear {{cust_name}},Thanks for visiting us,you have received {{description}} Use this code:{{voucher_code}} Valid till {{voucher_expiry_date}}.TnC";
		
			$eup = new ExtendedUserProfile($user, $this->currentorg);
				
			$data = array('voucher_code' => $coupon_model->getVoucherCode(), 'cust_name' => $eup->getName(), 'voucher_expiry_date' => $coupon_model->getExpiryDate(),
                                      'description' => $coupon_series->getDescription()  
                                     );
		
			$smstext = Util::templateReplace($sms_template, $data);	

			$this->logger->debug("Resending voucher : $smstext");
		
			if(!Util::sendSms($user->mobile, $smstext, $this->currentorg->org_id)){
				$this->logger->debug("Error in sending coupon through sms");
				//throw new Exception(ErrorMessage::$coupon['ERR_SMS_SENDING_ERROR'], ErrorCodes::$coupon['ERR_SMS_SENDING_ERROR']);
			}
			  */
			
			//$key = $campaignModule->data['api_status']['key'];
			
			$response['status'] = array(
									'success' => 'true',
									'code' => ErrorCodes::$api['SUCCESS'],
									'message' => ErrorMessage::$api['SUCCESS']	
								);	
								
			$response['coupons']['coupon'] = array(
												  $coupon_key => $coupon_identifier,
												  'item_status' => array(
																	 'success'	=> 'true',
												  					 'code' => ErrorCodes::$coupon['ERR_COUPON_SUCCESS'],
																	 'message' => 'Coupon Resent'
																 )
												); 
			$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
			return $response;
			
		}catch(Exception $e){
			$this->logger->debug("Caught exception: 
								  msg : " . $e->getMessage() .
								 "code : " . $e->getCode());
			throw $e;
		}
	}
	
	/**
	 * Redeems a voucher for a customer
	 * 
	 * @param $data
	 * @param $query_params
	 */
    /**
     * @SWG\Model(
     * id = "TransactionDetails",
     * @SWG\Property(name = "transaction_number", type = "string" ),
     * @SWG\Property(name = "transaction_amount", type = "integer" )
     * )
     */
	/**
	 * @SWG\Model(
	 * id = "CouponRedeemDetails",
	 * @SWG\Property(name = "code", type = "string" ),
	 * @SWG\Property(name = "validation_code", type = "string" ),
	 * @SWG\Property(name = "customer", type = "CustomerDetails" ),
     * @SWG\Property(name = "transaction", type = "TransactionDetails" )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "CustomFieldsList",
	 * @SWG\Property(name = "field", type = "CustomField", description = "Org specific custom fields involved in coupon redemption" )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "CustomField",
	 * @SWG\Property(name = "name", type = "string", required = true ),
	 * @SWG\Property(name = "value", type = "string", required = true )
	 * )
	 */
	
	/**
	 * @SWG\Model(
	 * id = "CouponRedeemRequest",
	 * @SWG\Property(name = "coupon", type = "array", items = "$ref:CouponRedeemDetails")
	 * )
	 */

	/**
	 * @SWG\Model(
	 * id = "CouponRedeemRoot",
	 * @SWG\Property(
	 * name = "root",
	 * type = "CouponRedeemRequest"
	 * )
	 * )
	 */
	
	/**
	 * @SWG\Api(
	 * path="/coupon/redeem.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Redeem a coupon",
	 * @SWG\Parameter(
	 * name = "request",
	 * paramType="body",
	 * type="CouponRedeemRoot")
	 * ))
	 */	
	private function redeem($version, $data)
	{
		global $gbl_item_status_codes, $currentorg;
		$this->logger->debug("Redeeming voucher with params: " . print_r($data, true));
		$coupon = $data['root']['coupon'][0];
		$validation_code = $coupon['validation_code'];
		$voucher_code = $coupon['code'];
		$mobile = $coupon['customer']['mobile'];
		$email = $coupon['customer']['email'];
		$external_id = $coupon['customer']['external_id'];
        $customerId = $coupon['customer']['id'];
        $offlineUsedDate = $coupon ['redemption_time'];
		
		$customFieldsToSave = array();
		$customFields = $coupon ['custom_fields']['field'];
		if (! empty($customFields)) {
			$customFieldsToSave = $customFields;
		}

		global $apiWarnings; 
		global $gbl_country_code;
		if(isset($coupon['customer']['country_code']))
		{
			$gbl_country_code = $coupon['customer']['country_code'];
		}
		$transaction_number = isset($coupon['transaction']['number']) 
									? $transaction_number = $coupon['transaction']['number'] 
									: $transaction_number = $coupon['transaction']['transaction_number'];
		$transaction_amount = $coupon['transaction']['amount'];   
		
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $coupon['customer']['mobile'],
						'email' => $coupon['customer']['email'],
						'external_id' => $coupon['customer']['external_id'],
                        'user_id'=> $coupon['customer']['id'],
                        'transaction_number' => $transaction_number,
						'coupon_code' => $voucher_code
				) );
		$response = array();
		
	try{
            $user = $this->getUser($mobile, $email, $external_id,true,false,$customerId);
			$this->isCouponTransactionAllowedForUser($user->user_id);
			
		}catch( Exception $e ){
			
			$item_status_code = $e->getMessage();
			if( !isset( ErrorCodes::$coupon[$item_status_code] ) ){
			
				$this->logger->error( "$item_status_code is not defined as Error Code making it more generic" );
				$item_status_code = 'ERR_COUPON_FAIL';
					
			}
			$this->logger->debug( "Caught Exception while fetching User :".$e->getMessage() );
			$response = array(
					'status' => array(
							'success' => false,
							'code' => ErrorCodes::$api['FAIL'],
							'message' => ErrorMessage::$api['FAIL']
							),
					'coupons' => array( 
							'coupon' => array(
									'code' => $voucher_code,
									'item_status' => array(
											'success' => 'false',
											'code' => ErrorCodes::$coupon[$item_status_code],
											'message' => ErrorMessage::$coupon[$item_status_code]
											)
									)
							)
					);
			return $response;
		}

		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $user->mobile,
						'email' => $user->email,
						'external_id' => $user->external_id,
						'user_id' => $user->user_id,
						'transaction_number' => $transaction_number,
						'coupon_code' => $voucher_code
				) );

		//triggering the cache update
		ApiCacheHandler::triggerEvent("coupon_update", isset($user->user_id)?$user->user_id:"");
				
		$isLuciFlowEnabled = Util::isLuciFlowEnabled();
		if ($isLuciFlowEnabled) {
			return $this -> newReedem($voucher_code, $offlineUsedDate, $user, $transaction_number, 
				$transaction_amount, $customFieldsToSave, $coupon['transaction']);
		}

		$coupon_mgr = new CouponManager();
		$c = $coupon_mgr->loadByCode($voucher_code);
		if (! $c) {
			$this->logger->debug("Couldn't load the voucher by code $voucher_code");
			$response['status'] = array(
									'success' => 'false',
									'code' => ErrorCodes::$api['FAIL'],
									'message' => ErrorMessage::$api['FAIL']	
									);
			if($version == 'v1.1')
			{
				$response['coupons']['coupon'] = array(
						'code' => $voucher_code,
						'item_status' => 
						array('success' => 'false',
								'code' => ErrorCodes::$coupon['ERR_INVALID_INPUT'],
								'message'=>ErrorMessage::$coupon['ERR_INVALID_INPUT'])
				);
			}
			else
			{
	 			$response['coupons']['coupon'] = 
	 					array(
 						'code' => $voucher_code,
 						'item_status' => array(
 							'status' => 'false', 
		 					'code' => ErrorCodes::$coupon['ERR_INVALID_INPUT'],
		 					'message'=>ErrorMessage::$coupon['ERR_INVALID_INPUT'])
					);									
			}
			$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
		    return $response;	
		}
		
		//Check if min bill and max bill is set and if amount is passed
		$coupon_series_info = $coupon_mgr->C_coupon_series_manager->getDetails();
		if( ($coupon_series_info['min_bill_amount'] || 
				$coupon_series_info['max_bill_amount'] ) && 
					( $coupon['transaction']['amount'] < 0 ||
						!is_numeric( $coupon['transaction']['amount'] ) ) ){		
			$response = array(
					'status' => array(
							'success' => false,
							'code' => ErrorCodes::$api['FAIL'],
							'message' => ErrorMessage::$api['FAIL']
					),
					'coupons' => array(
							'coupon' => array(
									'code' => $voucher_code,
									'item_status' => array(
											'success' => 'false',
											'code' => ErrorCodes::$coupon['VOUCHER_ERR_INVALID_BILL_AMOUNT'],
											'message' => ErrorMessage::$coupon['VOUCHER_ERR_INVALID_BILL_AMOUNT']
									)
							)
					)
			);
			return $response;
		}
		
		$usedDate = null;
		if (! empty($offlineUsedDate)) {
			$canBeRedeemedOffline = $coupon_series_info['offline_redeem_type'] == 1 ? true : false;
			if ($canBeRedeemedOffline) {
				$redemptionDate = date ('Y-m-d H:i:s', Util::deserializeFrom8601($offlineUsedDate));

				/* The correct dates may not be provided by the following two methods. Hence, letting it be.
				$issualDate = $coupon_mgr -> C_voucher_model_extension -> getCreatedDate("%Y-%m-%d");
				$expiryDate = $coupon_mgr -> C_voucher_model_extension -> getExpiryDate("%Y-%m-%d");

				if ($redemptionDate < $issualDate) {
					$apiWarnings -> addWarning("Voucher's redemption-time(offline) is older than voucher's issual-time");
				} else if ($redemptionDate > $expiryDate) {
					$apiWarnings -> addWarning("Voucher's redemption-time(offline) is past the voucher's expiry-date");
				} else {
					$usedDate = $redemptionDate;
				}*/
				$usedDate = $redemptionDate;
			}
		}
			
		$user_id = $user->getUserId();
		$disc_code = '';
		$customer_resp=array(   //clean mobile number and all customer details in response; Ticket 14120
					'mobile'=>$user->mobile,
					'email'=>$user->email,
					'external_id'=>$user->external_id,
                    'user_id' =>$user->user_id
				);
		$cm = new CampaignsModule();
		
		$campaignController = new CampaignsController($cm);
		$response_code = $campaignController->redeemVoucher( 
				$voucher_code, $user_id, $disc_code, $mobile, $transaction_number ,
				$validation_code, $customFieldsToSave, -1, $transaction_amount, $usedDate);

        if($response_code == VOUCHER_ERR_SUCCESS) {
            // ingest event here, since the redemption id has been generated
            $redemptionEventAttributes = array();
            $redemptionEventAttributes["customerId"] = intval($user_id);
            $redemptionEventAttributes["billNumber"] = $transaction_number;
            $redemptionEventAttributes["redemptionId"] = "";
            $redemptionEventAttributes["voucherCode"] = $voucher_code;
            $redemptionEventAttributes["transactionAmt"] = $transaction_amount;
            $redemptionEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $eventTime = $usedDate ? Util::deserializeFrom8601($usedDate) : time();
            EventIngestionHelper::ingestEventAsynchronously(intval($this->currentorg->org_id), "couponredemption",
                "Coupon redemption event from the Intouch PHP API's", $eventTime, $redemptionEventAttributes);
        }

		$key = Voucher::getResponseErrorKey($response_code);
		$this->logger->debug("CouponResource::redeemVoucher(): [ResponseCode=>Key] $response_code => $key ");
		
		if($response_code != VOUCHER_ERR_SUCCESS){
			//need to handle the error messages of vouchers
			$this->logger->error("Error in redeeming of voucher: $response_code");
			$response['status'] = array(
									'success' => 'false',
									'code' => ErrorCodes::$api['FAIL'],
									'message' => ErrorMessage::$api['FAIL']	
									);
 			$response['coupons']['coupon'] = array(
 													'code' => $voucher_code,
 													'item_status' => array(
 															'success' => 'false', 
 															'code' => ErrorCodes::$coupon[$key],
 															'message'=>ErrorMessage::$coupon[$key]
 												  		)
 												  );
 			$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
		    return $response;
		}
		
		$coupon_model = new ApiVoucherModelExtension();
		$coupon_model->loadByCode($voucher_code);
		$cm->signalVoucherRedemptionEventToEventManager(
				$this->currentuser->org_id, $user_id, $coupon_model->getVoucherSeriesId(), 
				$coupon_model->getVoucherId(), $this->currentuser->user_id);
		$is_absolute = $coupon_model->isAbsolute();
	
		$key = Voucher::getResponseErrorKey($response_code);
		$this->logger->debug("CouponResource::redeemVoucher(): [ResponseCode=>Key] $response_code => $key ");
		
		$body = array(
					  'code' => $voucher_code,
					  'customer' => $customer_resp,
					  'transaction' => $coupon['transaction'],
					  'discount_code' => $disc_code,
					  'series_code' => $coupon_model->getVoucherSeriesId(),
					  'is_absolute' => $is_absolute,
					  'coupon_value' => $coupon_model->getCouponValue() ,
					  'item_status' => array(
								  		'success' => 'true',
								  		'code' => ErrorCodes::$coupon[$key],
								  		'message' => ErrorMessage::$coupon[$key]
				)
		);
		
		if( $version =='v1.1' )
		{
			$body['transaction']['number'] = $transaction_number;
			unset($body['transaction']['transaction_number']);
		}

		$response['status'] = array(
									'success' => 'true',
									'code' => ErrorCodes::$api['SUCCESS'],
									'message' => ErrorMessage::$api['SUCCESS']	
		);
		$warnings = $apiWarnings -> getWarnings();
		if ($warnings != null) {
			$response['status']['message'] .= ", $warnings";
		}
		$response['coupons']['coupon'] = $body;
		
		$lm = new ListenersMgr($currentorg);
		$response['side_effects'] = $lm->getSideEffectsAsResponse($GLOBALS['listener']);
		$GLOBALS['listener'] = array();
			
		$gbl_item_status_codes = $response['coupons']['coupon']['item_status']['code'];
		return $response;
			
	}
	
	
	/**
	 * @SWG\Api(
	 * path="/coupon/isredeemable.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Check coupon redeemable status",
	 *    @SWG\Parameter(
	 *    name = "mobile",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Email ID of the customer"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "email",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Email ID of the customer"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "external_id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "External ID of the customer"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "code",
	 *    type = "string",
	 *    paramType = "query",
	 * description = "Coupon code"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "details",
	 *    type = "boolean",
	 *    paramType = "query",
	 * description = "Fetch coupon details if true"
	 *    )
	 * ))
	 */
	
	private function isRedeemable($params)
	{
		global $gbl_item_status_codes;
		$mobile = $params['mobile'];
		$email = $params['email'];
		$external_id = $params['external_id'];
		$code = $params['code'];
		$details = $params['details'];
		$voucher_code = $code;
	
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $params['mobile'],
						'email' => $params['email'],
						'external_id' => $params['external_id'],
						'coupon_code' => $code
				) );
		$this->logger->debug("Input parameters to isRedeemable: $mobile, $code, $details");
		
		try{
            $user = $this->getUser($mobile, $email, $external_id);
		}catch(Exception $e){
			$this->logger->debug("User not found");
			$err='ERR_USER_NOT_FOUND';
			if($e->getMessage()=='ERR_LOYALTY_FRAUD_USER')
				$err='ERR_LOYALTY_FRAUD_USER';
			$response = array('status'=>array('success'=>false, 'code'=>ErrorCodes::$api['FAIL'], 'message'=>ErrorMessage::$api['FAIL']),
							  'coupons'=>array('redeemable'=> array(
																'mobile' => $mobile,
                                                                'customer' => array(
                                                                    'mobile' => $mobile,
                                                                    'email' => $email,
                                                                    'external_id' => $external_id),
							  									'code'=>$voucher_code, 
																'is_redeemable' => 'false',
							  									'item_status'=>array('success'=>'false',
																					 'code'=>ErrorCodes::$coupon[$err], 
																					 'message' => ErrorMessage::$coupon[$err]	
																					)
																	)
											)
							);
			$gbl_item_status_codes = $response['coupons']['redeemable']['item_status']['code'];
			return $response;
		}

		try{
			$this->isCouponTransactionAllowedForUser($user->user_id);
		}catch(Exception $e)
		{
			$this->logger->error("user is blocked, coupon isredeemable is false");
			$response = array('status'=>array('success'=>false, 'code'=>ErrorCodes::$api['FAIL'], 'message'=>ErrorMessage::$api['FAIL']),
							  'coupons'=>array('redeemable'=> array(
																'mobile' => $user->mobile,
                                                              'customer' => array(
                                                                  'mobile' => $user->mobile,
                                                                  'email' => $user->email,
                                                                  'external_id' => $user->external_id),
							  									'code'=>$voucher_code, 
																'is_redeemable' => 'false',
							  									'item_status'=>array('success'=>'false',
																					 'code'=>ErrorCodes::$coupon[$e->getMessage()], 
																					 'message' => ErrorMessage::$coupon[$e->getMessage()]	
																					)
																	)
											)
							);
			$gbl_item_status_codes = $response['coupons']['redeemable']['item_status']['code'];
			return $response;
		}
		
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $user->mobile,
						'email' => $user->email,
						'external_id' => $user->external_id,
						'user_id' => $user->user_id,
						'coupon_code' => $code
				) );

		$isLuciFlowEnabled = Util::isLuciFlowEnabled();
		if ($isLuciFlowEnabled) {
			return $this -> newIsReedemable($code, $user, $details);
		} else {

			$coupon_mgr = new CouponManager();
			if(!$coupon_mgr->loadByCode($code)){
				$this->logger->debug("Couldn't load the voucher by code $code");
				$response['status'] = array(
										'success' => 'false',
										'code' => ErrorCodes::$api['FAIL'],
										'message' => ErrorMessage::$api['FAIL']	
				);
				$response['coupons']['redeemable'] = array(
														'mobile' => $user->mobile,
	                                                    'customer' => array(
	                                                        'mobile' => $user->mobile,
	                                                        'email' => $user->email,
	                                                        'external_id' => $user->external_id),
	 													'code' => $voucher_code,
														'is_redeemable' => 'false',
	 													'item_status' => array('status' => 'false',
	                                                        'success' => 'false',
	 													'code' => ErrorCodes::$coupon['ERR_INVALID_INPUT'],
	 													'message'=>ErrorMessage::$coupon['ERR_INVALID_INPUT'])
				);
				$gbl_item_status_codes = $response['coupons']['redeemable']['item_status']['code'];
				return $response;
			}

			//populate the voucher series details also in the response
			if( strtolower($details) == 'extended')
			{
				$coupon_details = $coupon_mgr->getDetails();
	            $series_details = $this->series(array("id" => $coupon_details['voucher_series_id']));
	            $series_details = $series_details["series"]["items"]["item"][0];
	            unset($series_details['item_status']);
			}

	        else if(strtolower($details) == 'true')
	        {
	            $coupon_details = $coupon_mgr->getDetails();
	            $coupon_series = new CouponSeriesManager();

	            $series_details = $coupon_series->getCouponSeries($coupon_details['voucher_series_id']);
	            $coupon_series->loadById($coupon_details['voucher_series_id']);
	            $coupon_series_details = $coupon_series->getDetails();

	            $series_details = array(
	                                          'description' => $coupon_series_details['description'],
	                                          'discount_code' => $coupon_series_details['discount_code'],
	                                          'valid_till' => $coupon_series_details['valid_till_date'],
	                                          'discount_type' => $coupon_series_details['discount_type'],
	                                          'discount_value' => $coupon_series_details['discount_value'],
	                                          'discount_on' => $coupon_series_details['discount_on'],
	                                          'detailed_info' => $coupon_series_details['info']
	            );
	        }
			

			$user_id = $user->getUserId();
			
			//Removing this logic, and checking isRedeemable from coupon manager as done during actual redemption
			/* $campaignModule = new CampaignsModule();
			$campaignModule->isVchRedeemableApiAction($voucher_code, $user_id);
			$return = $campaignModule->data['response_code']; */
			
			$return = $coupon_mgr->isRedeemable( $user );
		}
		
		if($return == VOUCHER_ERR_SUCCESS) // voucher is redeemable
		{
									   
			$response['status'] = array(
									'success' => 'true',
									'code' => ErrorCodes::$api['SUCCESS'],
									'message' => ErrorMessage::$api['SUCCESS']	
									);
			$response['coupons']['redeemable'] = array(
													'mobile' => $user->mobile,
                                                    'customer' => array(
                                                        'mobile' => $user->mobile,
                                                        'email' => $user->email,
                                                        'external_id' => $user->external_id),
 													'code' => $voucher_code,
													'is_redeemable' => 'true',
 													'item_status' => array('status' => 'true',
                                                                    'success' => 'true',
 																	'code' => ErrorCodes::$coupon['ERR_COUPON_SUCCESS'],
 																	'message'=>ErrorMessage::$coupon['ERR_COUPON_SUCCESS']
																	)
												);
			if(strtolower($details) == true || strtolower($details) == 'extended'){
				$response['coupons']['redeemable']['series_info'] = $series_details;										
			}
			$gbl_item_status_codes = $response['coupons']['redeemable']['item_status']['code'];
			return $response;									   
									   
		}else{    //voucher is not redeemable
			$this->logger->debug("return value: $return");
			$key = Voucher::getResponseErrorKey($return);
			$response['status'] = array(
									'success' => 'false',
									'code' => ErrorCodes::$api['FAIL'],
									'message' => ErrorMessage::$api['FAIL']	
									);
			$response['coupons']['redeemable'] = array(
													'mobile' => $user->mobile,
                                                    'customer' => array(
                                                        'mobile' => $user->mobile,
                                                        'email' => $user->email,
                                                        'external_id' => $user->external_id),
 													'code' => $voucher_code,
													'is_redeemable' => 'false',
 													'item_status' => array('status' => 'false',
                                                                    'success' => 'false',
 																	'code' => ErrorCodes::$coupon[$key],
 																	'message'=> ErrorMessage::$coupon[$key]
																	)
												);
			if(strtolower($details) == true || strtolower($details) == 'extended'){
				$response['coupons']['redeemable']['series_info'] = $series_details;										
			}
			$gbl_item_status_codes = $response['coupons']['redeemable']['item_status']['code'];
			return $response;									   
		}
	}

	
	/**
	 * @SWG\Model(
	 * id = "CouponDetails",
	 * @SWG\Property(name = "series_id", type = "string" ),
	 * @SWG\Property(name = "customer", type = "CustomerDetails" )
	 * )
	 */

	/**
	 * @SWG\Model(
	 * id = "CustomerDetails",
	 * @SWG\Property(name = "mobile", type = "string" ),
	 * @SWG\Property(name = "external_id", type = "string" ),
	 * @SWG\Property(name = "email", type = "string" ),
	 * description = "Any one of the customer identifier need to be specified"
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CouponRequest",
	 * @SWG\Property(name = "coupon", type = "array", items = "$ref:CouponDetails")
	 * )
	 */
	/**
	 * @SWG\Model(
	 * id = "CouponRoot",
	 * @SWG\Property(
	 * name = "root",
	 * type = "CouponRequest"
	 * )
	 * )
	 */
	
	/**
	 * @SWG\Api(
	 * path="/coupon/issue.{format}",
	 * @SWG\Operation(
	 *     method="POST", summary="Issue a coupon",
	 * @SWG\Parameter(
	 * name = "request",
	 * paramType="body",
	 * type="CouponRoot")
	 * ))
	 */
	
	private function issue($version, $data, $query_params)
	{
		global $gbl_item_status_codes, $currentuser;
		$should_return_user_id = isset($query_params['user_id']) && 
								strtolower($query_params['user_id']) == 'true' ? true : false;
		
		$this->logger->debug( "Issuing voucher with params: " . print_r( $data, true ) );
		$coupon_data = $data['root']['coupon'][0];
		$voucher_series_id = $coupon_data['series_id'];
		$mobile = $coupon_data['customer']['mobile'];
		$email = $coupon_data['customer']['email'];
		$external_id = $coupon_data['customer']['external_id'];
        $customerId = $coupon_data['customer']['id'];
        $issued_time = Util::getCurrentTimeForStore( $currentuser->user_id );
		
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $coupon_data['customer']['mobile'],
						'email' => $coupon_data['customer']['email'],
						'external_id' => $coupon_data['customer']['external_id'],
                        'user_id' => $coupon_data['customer']['id'],
				) );
		global $gbl_country_code;
		if(isset($coupon_data['customer']['country_code']))
		{
			$gbl_country_code = $coupon_data['customer']['country_code'];
		}
		$api_status_code = "SUCCESS";
		$item_status_code = "ERR_COUPON_SUCCESS";
	
		try{

            $user = $this->getNonLoyaltyUser($mobile, $email, $external_id, true,$customerId);
			
			if( empty( $voucher_series_id ) ){
				
				$this->logger->error( "Voucher Series Id Empty" );
				throw new Exception( "ERR_INVALID_SERIES_ID" );
			}
			
			$this->isCouponTransactionAllowedForUser($user->user_id);
			
			$coupon_identifier = '';
			$coupon_model = new ApiVoucherSeriesModelExtension();
				
			if( !empty( $voucher_series_id ) ){
				
				$this->logger->debug( "Loading by voucher_series_id $voucher_series_id" );
				$coupon_model->load( $voucher_series_id );
				$coupon_identifier = $voucher_series_id;
				$coupon_key = 'id';
			}
			
			if( !($coupon_model->getId()>0 ) ){
				
				$this->logger->error( "Invalid  Vocher Series Id $voucher_series_id" );
				throw new Exception( "ERR_INVALID_SERIES_ID" );
			}
			
			if( $coupon_model->getOrgId() != $this->currentorg->org_id ){
				
				$this->logger->error( "Invalid Organisation for Series ID $voucher_series_id" );
				throw new Exception( "ERR_INVALID_SERIES_ID" );
			}
			
			$isLuciFlowEnabled = Util::isLuciFlowEnabled();
			$couponValidTill = null;
			if ($isLuciFlowEnabled) {
				$orgId = $this -> currentorg -> org_id;
				$storeUnitId = $this -> currentuser -> getId();
				$userId = $user -> user_id;

				$response = ApiUtil :: newIssueCoupon($orgId, $userId, $voucher_series_id, $storeUnitId);

				$success = $response -> success;
				if ($success) {
					$vch = $response -> coupon -> couponCode;

					$couponValidTill = $this -> luciDateToStr($response -> coupon -> expiryDate);
				} else {
					/* Special handling for scenario where "user already has  a coupon" 
					and voucher_series.do_not_resend_existing_voucher = 1 
						OR 
					there's a generic error */
					if ($response -> luciExceptionCode == "619" || $response -> exceptionCode == "VOUCHER_ERR_UNKNOWN") {
						throw new Exception('ERR_COUPON_FAIL');
					} else {
						throw new Exception($response -> exceptionCode);
					}
				}
			} else {

				$cm = new CouponManager();
				$vch = $cm->issue( 
						$voucher_series_id, 
						array( 
								'user_id' => $user->user_id, 
								'series_id' => $voucher_series_id,
								'created_date' => $issued_time 
								) );
			}
                        
			$user->external_id = UserProfile::getExternalId($user->user_id);
                        
			Util::saveLogForApiInputDetails(
					array(
							'mobile' => $user->mobile,
							'email' => $user->email,
							'external_id' => $user->external_id,
							'user_id' => $user->user_id
					) );
					
			//triggering the cache update
			ApiCacheHandler::triggerEvent("coupon_update", isset($user->user_id)?$user->user_id:"");
			
			if(!$vch){
				$this->logger->error( "Voucher Issual Unsuccessful" );
				throw new Exception( "ERR_COUPON_FAIL" );
			}
			else{
				$this->logger->debug( "Voucher Issual Successful" );
			}
			
			
			$coupon_model_extension = new ApiVoucherModelExtension();
			$coupon_model_extension->loadByCode($vch);
			
			$coupon = array(
					'code' => $vch,
					'series_id' => $voucher_series_id,
					'description' => $coupon_model->getDescription(),
					'discount_code' => $coupon_model->getDiscountCode(),
					'valid_till' => ! empty($couponValidTill) ? $couponValidTill : 
						$coupon_model_extension->getExpiryDate("%Y-%m-%d"),
					'discount_type' => $coupon_model->getDiscountType(),
					'discount_value' => $coupon_model->getDiscountValue(),
					'discount_on' => $coupon_model->getDiscountOn(),
					'detailed_info' => $coupon_model->getInfo(),
					'customer' => array(
							'mobile' => $user->mobile,
							'email' => $user->email,
							'external_id' => $user->external_id
					)
			);
			if($should_return_user_id)
			{
				$coupon['customer']['user_id'] = $user->user_id;
			}
			if($version == 'v1.1')
			{
				$temp_coupon = array();
				$temp_coupon['code'] = $coupon['code'];
				$temp_coupon['valid_till'] = $coupon['valid_till'];  //verify
				$temp_coupon['series_info'] = array();
				$temp_coupon['series_info']['id'] = $coupon['series_id'];
				$temp_coupon['series_info']['description'] = $coupon['description'];
				$temp_coupon['series_info']['discount_code'] = $coupon['discount_code'];
				$temp_coupon['series_info']['valid_till'] = $coupon_model->getValidTillDate();
				$temp_coupon['series_info']['discount_type'] = $coupon['discount_type'];
				$temp_coupon['series_info']['discount_value'] = $coupon['discount_value'];
				$temp_coupon['series_info']['discount_on'] = $coupon['discount_on'];
				$temp_coupon['series_info']['detailed_info'] = $coupon['detailed_info'];
				$temp_coupon['series_info']['customer'] = $coupon['customer'];
			}
		}catch( Exception $e ){
			
			$this->logger->error( "Caught exception:
													msg : " . $e->getMessage() .
												   "code : " . $e->getCode());
			$api_status_code = "FAIL";
			$item_status_code = $e->getMessage();
			$coupon = array(
					'code' => "",
					'series_id' => $voucher_series_id,
					'customer' => array(
							'mobile' => $mobile,
							'email' => $email,
							'external_id' => $external_id
					)
			);
		}
		
		$api_status = array(
							"success" => ErrorCodes::$api[$api_status_code] == ErrorCodes::$api['SUCCESS'] ? true : false,
							"code" => ErrorCodes::$api[$api_status_code],
							"message" => ErrorMessage::$api[$api_status_code]
							);
		
		$item_status = array(
							"success" => ErrorCodes::$coupon[$item_status_code] ==
								ErrorCodes::$coupon['ERR_COUPON_SUCCESS'] ? true : false,
							"code" => ErrorCodes::$coupon[$item_status_code],
							"message" => ErrorMessage::$coupon[$item_status_code]
							);
				
		$coupon['item_status'] = $item_status;
		$gbl_item_status_codes = $item_status['code'];
		return array(
					"status" => $api_status,
					"coupon" => $coupon
					);
	}
	

	/**
	 * Retrieves the user object from the mobile, email, external_id
	 * 
	 * @param $mobile
	 * @param $email
	 * @param $external_id
	 * @throws UserNotFoundException
	 */
	/*
	private function getUser($mobile, $email, $external_id)
	{
		if(!empty($mobile)){
			$user = UserProfile::getByMobile($mobile);
		}else if(!empty($email)){
			$user = UserProfile::getByEmail($email);
		}else if(!empty($external_id)){
			$user = UserProfile::getByExternalId($external_id);
		}	
		
		//user not found
		if(!$user){
			$this->logger->debug("User not found for $mobile, $email, $external_id");
			throw new UserNotFoundException(ErrorMessage::$coupon['ERR_USER_NOT_FOUND'], ErrorCodes::$coupon['ERR_USER_NOT_FOUND']);
		}
		
		$this->logger->debug("user found with id: " . $user->user_id);
		return $user;
	}
	
	*/
	/**
	 * Fetches details of a coupon
	 * 
	 * @param $query_params
	 */
	
	/**
	 * @SWG\Api(
	 * path="/coupon/get.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get coupon details",
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Coupon ID (csv is supported)"
	 *    ),
	 *    @SWG\Parameter(
	 *    name = "code",
	 *    type = "string",
	 *    paramType = "query",
	 * description = "Coupon code (csv is supported)"
	 *    )
	 * ))
	 */
	
	private function get($version, $query_params)
	{
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$pCustomerId = null; 

		$should_return_user_id = $query_params['user_id'] == 'true'? true :false;
		$codes = trim($query_params['code']);
		$ids = trim($query_params['id']);
		$this->logger->debug("Fetching coupons with $codes, $ids");
		
		$inputCustomerIds = trim($query_params['customer_id']);
		if (! empty($inputCustomerIds)) {
			$temp = explode(',', $inputCustomerIds);
			$pCustomerId = $temp[0];
		}
		$coupon_codes = explode(',', $codes);
		$coupon_ids = explode(',', $ids);
		$coupon_key = '';
		$coupons = array();
		
		$this->logger->debug("coupon_codes: " . print_r($coupon_codes, true));
		$this->logger->debug("coupon_ids: " . print_r($coupon_ids, true));
		
		if(strlen($codes)){
			$coupon_key = 'coupon_code';
			$coupons = $coupon_codes;
		}else if(strlen($ids)){
			$coupon_key = 'coupon_id';
			$coupons = $coupon_ids;
		}else{
			$this->logger->debug("Neither coupon codes nor coupon ids passed");
			throw new InvalidInputException(ErrorMessage::$coupon['ERR_INVALID_INPUT'], ErrorCodes::$coupon['ERR_INVALID_INPUT']);
		}

                
		$isLuciFlowEnabled = Util::isLuciFlowEnabled();
		if ($isLuciFlowEnabled) {
			return $this -> newGet($coupon_key, $coupons, $should_return_user_id, $version, $pCustomerId);
		}
		
		$this->logger->debug("Processing for $coupon_key " . print_r($coupons, true));
		$coupon_count = 0;
        global $error_count;
        $error_count = 0;
		
		$coupon_responses = array();
		foreach($coupons as $k=>$c)
		{
			++$coupon_count;
			$response = array();
			$this->logger->debug("Loading voucher for $coupon_key : $c");
			$coupon = new ApiVoucherModelExtension();
			if($coupon_key == 'coupon_code'){
				$coupon->loadByCode($c);
				$coupon_key = 'code';
			}else{
				$coupon->loadById($c);
				$coupon_key = 'id';
			}
			
			if((!$coupon) || !($coupon->getVoucherId() > 0)){
				$this->logger->debug("Error in fetching the coupon");
				$response[$coupon_key] = ApiUtil::trimCouponCode($c);
				$response['item_status']['status'] = 'false';
				$response['item_status']['code'] = ErrorCodes::$coupon['ERR_INVALID_INPUT'];
				$response['item_status']['message'] = ErrorMessage::$coupon['ERR_INVALID_INPUT'];
				$coupon_responses[] = $response;
				++$error_count;
				$arr_item_status_codes[] = $response['item_status']['code'];
				continue;
			}
			
			$luciCouponCode = $coupon -> getVoucherCode();
			$response['code'] = ApiUtil::trimCouponCode($luciCouponCode);
			$response['id'] = $coupon->getVoucherId();
			$response['valid_till'] = $coupon->getExpiryDate();
			$response['issued_on'] = $coupon->getCreatedDate();
			
			$issued_to = $coupon->getIssuedTo();
			$user = UserProfile::getById($issued_to);
			if(!$user){
				$this->logger->debug("Something is really screwed up !! user not found for $coupon_key $c");
				$response[$coupon_key] = ApiUtil::trimCouponCode($c);
				$response['item_status']['status'] = 'false';
				$response['item_status']['code'] = ErrorCodes::$api['SYSTEM_ERROR'];
				$response['item_status']['message'] = ErrorMessage::$api['SYSTEM_ERROR'];
				$coupon_responses[] = $response;
				++$error_count;
				$arr_item_status_codes[] = $response['item_status']['code'];
				continue;	
			}
			
                        $external_id = UserProfile::getExternalId($issued_to);
			$this->logger->debug("populating customer object");
			$response['customer'] = array(
											'name' => $user->first_name . ' ' . $user->last_name,
											'mobile' => $user->mobile,
											'email' => $user->email,
											'external_id' => $external_id, 
											'id' => $issued_to
										);			
			if($should_return_user_id)
			{
				$response['customer']['user_id'] = $issued_to;
			}
			$this->logger->debug("done populating customer object");
			$is_absolute = ( $coupon->isAbsolute() == true) ? 'true' : 'false';
			$coupon_value = $coupon->getCouponValue();
			
			$response['is_absolute'] = $is_absolute;
			$response[ 'value' ] = $coupon_value;
		
			$this->logger->debug("done populating customer object");
		
			$redemption_details = $coupon->getRedemptionInfo();
			$response['redemption_info'] = $redemption_details;
		
			$this->logger->debug("done populating customer object");
			
			if($version=="v1.1")
			{
				$response['series_id']=$coupon->getVoucherSeriesId();
				$response['pincode']=$coupon->getPinCode();
				$db=new Dbase("masters",true);
				$till_id=$coupon->getCreatedBy();
				$sql="SELECT oe.code,oe.name FROM masters.org_entity_relations oer
						JOIN masters.org_entities oe ON oe.id=oer.parent_entity_id
						WHERE oer.child_entity_id='$till_id' AND oer.parent_entity_type='STORE' AND oer.org_id='{$this->currentorg->org_id}'";
				$iss_store=$db->query_firstrow($sql);
				if(empty($iss_store))
				{
					$sql="SELECT code,name FROM masters.org_entities
							WHERE id='$till_id' AND org_id='{$this->currentorg->org_id}'";
					$iss_store=$db->query_firstrow($sql);
				}
				
				$response['issued_store']=array(
						'code'=>$iss_store['code'],
						'name'=>$iss_store['name']
						);
				$sql="SELECT vr.bill_number,ll.id as transaction_id,vr.used_by,oe.code,oe.name,
						u.firstname,u.lastname,u.email,u.mobile 
						FROM luci.voucher_redemptions vr
						JOIN masters.org_entities oe ON oe.id=vr.used_at_store
						LEFT OUTER JOIN user_management.users u on u.id=vr.used_by
						LEFT OUTER JOIN user_management.loyalty_log ll ON ll.bill_number=vr.bill_number AND ll.org_id='{$this->currentorg->org_id}'
						WHERE vr.org_id='{$this->currentorg->org_id}' AND vr.voucher_id='{$response['id']}'
					";
				$redm=$db->query_firstrow($sql);
				$response['redemption_info']['redeemed_by']=array(
						'firstname'=>$redm['firstname'],
						'lastname'=>$redm['lastname'],
						'email'=>$redm['email'],
						'mobile'=>$redm['mobile']
						);
				$response['redemption_info']['store']=array(
						'code'=>$redm['code'],
						'name'=>$redm['name']
						);
				$response['redemption_info']['transaction']=array(
						'id'=>$redm['transaction_id'],
						'bill_number'=>$redm['bill_number']
						);
				
			}

			$response['item_status']['status'] = 'true';
			$response['item_status']['code'] = ErrorCodes::$coupon['ERR_COUPON_SUCCESS'];
			$response['item_status']['message'] = ErrorMessage::$coupon['ERR_COUPON_SUCCESS'];

			$arr_item_status_codes[] = $response['item_status']['code'];
			$this->logger->debug("Coupon with $coupon_key $c retreived successfully");
			$coupon_responses[] = $response;
		}
		
		if( $version == 'v1.1' ){
			
			$item = 0;
			//Changing status to success tag for each item statues
			foreach ( $coupon_responses as $response ){
				
				$success = array('success' => $response['item_status']['status']);
				unset( $response['item_status']['status'] );
				$response['item_status'] = array_merge( $success, $response['item_status'] );
				$coupon_responses[$item++]['item_status'] = $response['item_status']; 
			}
		}
		
		$this->logger->debug("coupon_count $coupon_count error_count $error_count");
		$status = 'SUCCESS';
		if($error_count == $coupon_count){
			$status = 'FAIL';
		}else if(($error_count < $coupon_count) && ($error_count > 0)){
			$status = 'PARTIAL_SUCCESS';
		}
		
		$this->logger->debug("status of the call: $status");
		$api_response = array(
								'status' => array( 'success' => ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false',
												   'code' => ErrorCodes::$api[$status],
												   'message' => ErrorMessage::$api[$status] 
												 ),
								'coupons' => array( 'coupon' => $coupon_responses )				 	
							 );					 
		return $api_response;					 
	}
	
	/**
	 * @SWG\Api(
	 * path="/coupon/series.{format}",
	 * @SWG\Operation(
	 *     method="GET", summary="Get coupon series details",
	 *    @SWG\Parameter(
	 *    name = "id",
	 *    type = "string",
	 *    paramType = "query",
	 *    description = "Coupon Series ID (csv is supported)"
	 *    )
	 * ))
	 */
	private function series( $query_params , $version='v1'){
		global $gbl_item_status_codes;
		$arr_item_status_codes = array();
		$api_status_code = 'SUCCESS';
		$coupon_series_manager = new CouponSeriesManager();
		
		$response = array();
		
		if(isset($query_params['id']) && $query_params['id']){
			$series_id = trim($query_params['id']);
			
			$series_ids = StringUtils::strexplode(',', $series_id);
			
			$series_count = count($series_ids);
			if($series_count>0)
				$series = $coupon_series_manager->getCouponSeries( $series_id );
			
			
            global $error_count;
            $error_count = 0;
			$scope_count = 0;
			$series_result = array();
			
			foreach($series as $series_info)
			{
				$series_result[$series_info['id']] = $series_info;
			}
			
			$series_info = array();
			
			foreach($series_ids as $series_id){
				
				try
				{
					if(isset($series_result[$series_id]))	
					{
						$error_key = "ERR_COUPON_SERIES_SUCCESS";
						++$scope_count;
						$series_info = array_merge($series_result[$series_id],array( 
												'coupons' => array(
																'issued' => $series_result[$series_id]['num_issued'],
																'redeemed' => $series_result[$series_id]['num_redeemed']	
																)
												)
								);
						
						if($version=="v1.1")
						{
							$series_info['campaign_name']="";
							if($series_info['campaign_id']!='-1')
								$series_info['campaign_name']=$this->getCampaignName($series_info['campaign_id']);
						}

						$series_info['offline_redemption_enabled'] = $series_info['offline_redeem_type'] == 1 ? true : false;
						unset($series_info['offline_redeem_type']);
						unset($series_info['num_issued']);
						unset($series_info['num_redeemed']);
						unset($series_info['org_id']);
						unset($series_info['terms_and_condition']);
						unset($series_info['signal_redemption_event']);
						unset($series_info['sync_to_client']);
						unset($series_info['show_pin_code']);
					}
					
					else{
							throw new Exception( "ERR_INVALID_SERIES_ID" );
					}
					
				}	
					catch( Exception $e ){
							
						++$error_count;
						$error_key = $e->getMessage();
						$this->logger->error( "CouponResource::series()  Error: ".ErrorMessage::$coupon[ $e->getMessage() ]);
							
						$series_info = array( 'id' => $series_id );
					}
					
					$series_info[ 'item_status' ][ 'success' ] = ( $error_key == "ERR_COUPON_SERIES_SUCCESS" ) ? 'true' : 'false' ;
					$series_info[ 'item_status' ][ 'code' ] = ErrorCodes::$coupon[ $error_key ];
					$series_info[ 'item_status' ][ 'message' ] = ErrorMessage::$coupon[ $error_key ] ;
					$arr_item_status_codes[] = $series_info['item_status']['code']; 
					array_push($response, $series_info);
					
				}
					
					$status = 'SUCCESS';
					if( $series_count == $error_count ){
							
						$status = 'FAIL';
					}
					else if(  $error_count > 0 ){
							
						$status = 'PARTIAL_SUCCESS';
					}
					
		}
		
		else{
				$this->logger->debug( "Getting Voucher List(exclude expired) for org: ".$this->currentorg->org_id );
				
				if(isset($query_params['expired']))
				{
					$expired = $query_params['expired'] == "true" ? true:false;
				}
				
				$series = $coupon_series_manager->getActiveCouponSeries( $expired );
				
				foreach($series as $series_info){
				
					$series_info = array_merge($series_info,array( 
												'coupons' => array(
																'issued' => $series_info['num_issued'],
																'redeemed' => $series_info['num_redeemed']	
																)
												)
								);
					
					if($version=="v1.1")
					{
						$series_info['campaign_name']="";
						if($series_info['campaign_id']!='-1')
							$series_info['campaign_name']=$this->getCampaignName($series_info['campaign_id']);
					}
					$series_info['offline_redemption_enabled'] = $series_info['offline_redeem_type'] == 1 ? true : false;
					unset($series_info['offline_redeem_type']);
	
					unset($series_info['num_issued']);
					unset($series_info['num_redeemed']);
					unset($series_info['org_id']);
					unset($series_info['terms_and_condition']);
					unset($series_info['signal_redemption_event']);
					unset($series_info['sync_to_client']);
					unset($series_info['show_pin_code']);
					
					array_push($response, $series_info);
				}
			
			
				$status = 'SUCCESS';
		}
			
			$api_status = array(
					"success" => ($status == 'SUCCESS' || $status == 'PARTIAL_SUCCESS') ? 'true' : 'false',
					"code" => ErrorCodes::$api[$status],
					"message" => ErrorMessage::$api[$status]
			);
		$coupon_product_manager=new CouponProductManager();

        foreach($response as &$cpn_series)
        {
            $cpn_series["products"] = null;
            $cpn_series["brands"] = null;
            $cpn_seires["categories"] = null;

        	$prodIds = $coupon_product_manager->getProductsForVoucherSeries($cpn_series["id"]);
        	if(isset($prodIds["sku"]))
        	{
        		$skus = $this->skuIdArrayToDetailsArray($prodIds["sku"]);
        		if(!empty($skus))
        		{
        			$cpn_series["products"]["product"] = $skus;
        		}	
            }
        	else
        	{
        		if(isset($prodIds["brand"]))
        	    {
        			$brands = $this->brandIdArrayToDetailsArray($prodIds["brand"]);
                    if(!empty($brands))
        			{
                        $cpn_series["brands"]["brand"] = $brands;
        			}
                }

                if(isset($prodIds["category"]))
        	    {
        	    	$cats = $this->categoryIdArrayToDetailsArray($prodIds["category"]);
        	    	if(!empty($cats))
        	    	{
        	    		$cpn_series["categories"]["category"] = $cats;
        	    	}
                }
       	    }

            if($cpn_series['valid_with_discounted_item'] == 1)
                $cpn_series['valid_with_discounted_item'] = true;
            if($cpn_series['valid_with_discounted_item'] == 0)
                $cpn_series['valid_with_discounted_item'] = false;
        }

        $gbl_item_status_codes = implode(",", $arr_item_status_codes);
			return array(
						"status" => $api_status,
						"series" => array("items" => array('item' => $response))
				);
	
		
  }

    private function categoryIdArrayToDetailsArray($ids)
    {
        $ret = array();
        
        if(! empty($ids))
        {
            $inv = new ApiInventoryController();
            try {
                foreach ($ids as $id) {
                    $cat = $inv->getCategories(array("id" => $id));
                    if(!empty($cat))
                    {
                        $cat = $cat[0];
                        $ret[] = array("name" => $cat["code"], "label" => $cat["name"], "level" => count($cat["category_hierarchy"]));
                    }
                }
            }
            catch(Exception $e){
                $this->logger->debug("No category found");
            }
        }
        return $ret;
    }


    private function brandIdArrayToDetailsArray($ids)
    {
        $inv = new ApiInventoryController();
        $ret = array();
        
        if(! empty($ids)) {
            try {
                foreach ($ids as $id) {
                    
                   $cat = $inv->getBrands(array("id" => $id));
                   if(!empty($cat))
                   {
                       $cat = $cat[0];
                       $ret[] = array("name" => $cat["code"], "label" => $cat["name"], "level" => count($cat["brand_hierarchy"]));
                   }
                }
            }
            catch(Exception $e){
                $this->logger->debug("No brand found");
            }
        }
        return $ret;
    }
    private function skuIdArrayToDetailsArray($ids)
    {
    	$inv = new ApiInventoryController();
        $ret = array();
        
        if(! empty($ids)) {
            try {
                foreach ($ids as $id) {
                    $sku = $inv->getProductById($id);
                   if(!empty($sku))
                   {
                       $ret[] = array("sku" => $sku["sku"],"ean" => $sku["item_ean"], "description" =>$sku["description"]);
                   }
                  
                }
            }
            catch(Exception $e){
                $this->logger->debug("No sku found");
            }
        }
        return $ret;
    }
    /**
	 * Checks if the system supports the version passed as input
	 * 
	 * @param $version
	 */
	
	protected function checkVersion($version)
	{
		if(in_array(strtolower($version), array('v1', 'v1.1'))){
			return true;
		}	
		return false;
	}
	
	protected function checkMethod($method)
	{
		if(in_array(strtolower($method), array('resend', 'redeem', 'get', 'isredeemable','issue','series'))){

			return true;
		}
		return false;
	}
	
	private function getCampaignName($id)
	{
		$db=new Dbase('campaigns');
		return $db->query_scalar("SELECT name FROM campaigns.campaigns_base WHERE id='$id' AND org_id = ". $this->currentorg->org_id);
	}
	
	protected function isCouponTransactionAllowedForUser($user_id)
	{
		
		$this->logger->info("Checking... is coupon redemption/issue allowed for customer");
		
		//check mobile realloc request pending state
		include_once 'apiModel/class.ApiChangeIdentifierRequestModelExtension.php';
		$request_model=new ApiChangeIdentifierRequestModelExtension();
		$blocked=$request_model->isMobileReallocPendingForOldCustomer($user_id);
		if($blocked)
		{
			$this->logger->error("mobile realloc request pending for user... coupon stuff not allowed");
			throw new Exception('ERR_COUPON_BLOCKED_CUSTOMER');
		}
		
		$this->logger->info("coupons stuff is allowed");
		return true;
		
	}
	
	private function newIsReedemable($code, $user, $details) {
		$success = false; 
		$errorCode = null;
		$respSeries = array(); 
		$apiResponse = array();
		$loadedCoupon = null; 
		$loadedSeries = null;

		global $currentorg, $currentuser;
		$orgId = $currentorg -> org_id;
		$storeUnitId = $currentuser -> getId();
		$userId = $user -> user_id;

		$ls = new LuciService();
		$response = $ls -> isRedeemable($orgId, $code, $storeUnitId, $userId);

		$success = $response -> success;
		if (!$success) {

			$errorCode = $response -> exceptionCode; 
			$this -> logger -> error("Error in isRedeemable for voucher via Luci-service: " . print_r($response, true));

			/* When VOUCHER_ERR_INVALID_VOUCHER_CODE, the older function 
				returned ERR_INVALID_INPUT */
			if ($errorCode == "VOUCHER_ERR_INVALID_VOUCHER_CODE") {
				$errorCode = 'ERR_INVALID_INPUT';
			} 

			$apiResponse['status'] = array(
				'success' 		=> 'false',
				'code' 			=> ErrorCodes::$api['FAIL'],
				'message' 		=> ErrorMessage::$api['FAIL']	
			);
			$apiResponse['coupons']['redeemable'] = array(
				'code' 			=> $code,
				'customer' 		=> array(
					'mobile' 	=> $user -> mobile, 
					'email' 	=> $user -> email,
					'external_id' => $user -> external_id
				),
				'is_redeemable' => 'false',
				'item_status' 	=> array(
					'success' 	=> 'false',
					'code' 		=> ErrorCodes::$coupon[$errorCode],
					'message'	=> ErrorMessage::$coupon[$errorCode]
				)
			);
		} else {
			
			$loadedCoupon = $response -> coupon;
			$redemptionsLeft = $loadedCoupon -> redemptionsLeft;
			$redemptionCountDetails = $loadedCoupon -> redemptionCountDetails;

			if (strtolower($details) == 'true' || strtolower($details) == 'extended') {
				if (!empty($loadedCoupon -> couponSeriesId)) {
					$result = $ls -> getCouponSeriesById($orgId, $loadedCoupon -> couponSeriesId, true);
					
					if ($result -> success) {
						$loadedSeries = $result -> couponSeries;

						if (strtolower($details) == 'extended') {
							$respSeries = json_decode(json_encode($loadedSeries), true);

							$respSeries['valid_till_date'] = $this -> luciDateToStr($respSeries['valid_till_date']);
							$respSeries['created'] = $this -> luciDateToStr($respSeries['created']);
							$respSeries['last_used'] = $this -> luciDateToStr($respSeries['last_used']);
							$respSeries['dvs_expiry_date'] = $this -> luciDateToStr($respSeries['dvs_expiry_date']);
							$respSeries['redemption_valid_from'] = 
								$this -> luciDateToStr($respSeries['redemption_valid_from']);
							$respSeries['transferrable'] = (int) $respSeries['transferrable'];
							$respSeries['any_user'] = (int) $respSeries['any_user'];
							$respSeries['same_user_multiple_redeem'] = (int) $respSeries['same_user_multiple_redeem'];
							$respSeries['allow_referral_existing_users'] = (int) $respSeries['allow_referral_existing_users'];
							$respSeries['multiple_use'] = (int) $respSeries['multiple_use'];
							$respSeries['is_validation_required'] = (int) $respSeries['is_validation_required'];
							$respSeries['disable_sms'] = $respSeries['disable_sms'] ? 1 : 0;
							$respSeries['dvs_enabled'] = $respSeries['dvs_enabled'] ? 1 : 0;
							$respSeries['allow_multiple_vouchers_per_user'] = 
								$respSeries['allow_multiple_vouchers_per_user'] ? 1 : 0;
							$respSeries['do_not_resend_existing_voucher'] = 
								$respSeries['do_not_resend_existing_voucher'] ? 1 : 0;
							$respSeries['offline_redeem_type'] == 1 ? true : false;
							$respSeries['old_flow_enabled'] = $respSeries['old_flow'] ? 1 : 0;
							unset($respSeries['old_flow']);
							$respSeries['coupons'] = array(
								'issued' => $respSeries['num_issued'], 
								'redeemed' => $respSeries['num_redeemed']
							);
							unset($respSeries['num_issued']); 
							unset($respSeries['num_redeemed']);
							$respSeries['offline_redemption_enabled'] = 
								$respSeries['offline_redeem_type'] == 1 ? true : false;
							unset($respSeries['offline_redeem_type']);

							$productInfo = $this -> fetchInventoryInfoForCouponSeries($loadedCoupon -> couponSeriesId, $respSeries['productInfo']);
							$respSeries = array_merge($respSeries, $productInfo);
							//unset($respSeries['productInfo']);
						} else {
							$respSeries = array(
								'description' => $loadedSeries -> description, 
								'discount_code' => $loadedSeries -> discount_code,
								'valid_till' => $this -> luciDateToStr($loadedCoupon -> expiryDate), 
								'discount_type' => $loadedSeries -> discount_type,
								'discount_value' => $loadedSeries -> discount_value,
								'discount_on' => $loadedSeries -> discount_on,
								'detailed_info' => $loadedSeries -> info, 
								'max_redemptions_in_series_per_user' => $loadedSeries -> max_redemptions_in_series_per_user
							);
						}
					}
				}
			}

			$apiResponse['status'] = array(
				'success' 	=> 'true',
				'code' 		=> ErrorCodes::$api['SUCCESS'],
				'message' 	=> ErrorMessage::$api['SUCCESS']
			);
			$apiResponse['coupons']['redeemable'] = array(
				'mobile' 	=> $user -> mobile,
				'customer' 	=> array(
					'mobile' => $user -> mobile, 
					'email' => $user -> email,
					'external_id' => $user -> external_id
				),
				'code' => $code,
				'is_redeemable' => 'true',
				'item_status' => array(
					'status' => 'true',
					'success' => 'true',
					'code' => ErrorCodes::$coupon['ERR_COUPON_SUCCESS'],
					'message'=>ErrorMessage::$coupon['ERR_COUPON_SUCCESS']
				)
			);
			if (!is_null($redemptionsLeft)) {
				$apiResponse['coupons']['redeemable']['redemptions_left'] = $redemptionsLeft;
			}
			if (!is_null($redemptionCountDetails) && !is_null($redemptionCountDetails[0])) {
				$redemptionCountForUser = $redemptionCountDetails[0];
				$apiResponse['coupons']['redeemable']['no_of_redemptions_by_user'] = $redemptionCountForUser -> redemptionCount;
			}
			if (!is_null($loadedSeries)) {
				$apiResponse['coupons']['redeemable']['series_info'] = $respSeries;
			}
		} 

		/* $gbl_item_status_codes?? To log in performance-logs and apache access logs */
		$gbl_item_status_codes = $apiResponse['coupons']['redeemable']['item_status']['code'];
		return $apiResponse;
	}

	private function newReedem($code, $redemptionTime, $user, 
		$transactionNo, $transactionAmt, $cfs, $transactionPayload) {
		$success = false; 
		$errorCode = null;
		$respSeries = array(); 
		$apiResponse = array();
		$loadedCoupon = null; 
		$loadedSeries = null;
		$offlineTime = null;
		if (empty($redemptionTime)) {
			$redemptionTime = date('Y-m-d H:i:s');
		}
		$offlineTime = date('Y-m-d H:i:s', Util::deserializeFrom8601($redemptionTime));
		$this -> logger -> debug("Evaluated redemptionTime: '$offlineTime'");

		global $currentorg, $currentuser;
		$orgId = $currentorg -> org_id;
		$storeUnitId = $currentuser -> getId();
		$userId = $user -> user_id;

		$ls = new LuciService();
		//@TODO Load transaction ID from transaction number and pass along to Luci
		$response = $ls -> redeem($orgId, $code, $offlineTime, 
			$storeUnitId, $userId, $transactionNo, $transactionAmt);

		$success = $response -> success;
		if (!$success) {

			$errorCode = $response -> exceptionCode; 
			if ($errorCode == 'VOUCHER_ERR_UNKNOWN') {
				$errorCode = 'ERR_COUPON_FAIL';
			}
			$this -> logger -> error("Error in redeeming of voucher via Luci-service: " . print_r($response, true));

			$apiResponse['status'] = array(
				'success' 	=> 'false',
				'code' 		=> ErrorCodes::$api['FAIL'],
				'message' 	=> ErrorMessage::$api['FAIL']	
			);
			$apiResponse['coupons']['coupon'] = array(
				'code' 		=> $code,
				'item_status' 	=> array(
					'success' 	=> 'false',
					'code' 		=> ErrorCodes::$coupon[$errorCode],
					'message'	=> ErrorMessage::$coupon[$errorCode]
				)
			);
		} else {

			$loadedCoupon = $response -> coupon;

			if (!empty($loadedCoupon -> couponSeriesId)) {
				$result = $ls -> getCouponSeriesById($orgId, $loadedCoupon -> couponSeriesId);
				
				if ($result -> success) {
					$loadedSeries = $result -> couponSeries;
				}
			}

			$redemptionDetails = $loadedCoupon -> redeemedCoupons [0];
			$redemptionId = $redemptionDetails -> redemptionId;

            // ingest event here, since the redemption id has been generated
            $redemptionEventAttributes = array();
            $redemptionEventAttributes["customerId"] = intval($userId);
            $redemptionEventAttributes["billNumber"] = $transactionNo;
            $redemptionEventAttributes["redemptionId"] = $redemptionId;
            $redemptionEventAttributes["voucherCode"] = $code;
            $redemptionEventAttributes["transactionAmt"] = $transactionAmt;
            $redemptionEventAttributes["entityId"] = intval($this->currentuser->user_id); // $transaction[""];

            $eventTime = $redemptionTime ? Util::deserializeFrom8601($redemptionTime) : time();
            EventIngestionHelper::ingestEventAsynchronously( $orgId, "couponredemption",
                "Coupon redemption event from the Intouch PHP API's", $eventTime, $redemptionEventAttributes);

            $this -> saveCustomFields('voucher_redemption', $cfs, $redemptionId);

			$cm = new CampaignsModule();
			$cm -> signalVoucherRedemptionEventToEventManager(
				$orgId, $userId, $loadedSeries -> id, $loadedCoupon -> id, $storeUnitId);

			$lm = new ListenersMgr($currentorg);
			$sideEffects = $lm -> getSideEffectsAsResponse($GLOBALS['listener']);
			$GLOBALS['listener'] = array();

			if( $version =='v1.1' ) {
				$transactionPayload['number'] = $transactionPayload['transaction_number'];
				unset($transactionPayload['transaction_number']);
			}

			$apiResponse['status'] = array(
				'success' 	=> 'true',
				'code' 		=> ErrorCodes::$api['SUCCESS'],
				'message' 	=> ErrorMessage::$api['SUCCESS']
			);
			$apiResponse['coupons']['coupon'] = array(
				'code' 			=> $code,
				'customer' 		=> array(
					'mobile' 		=> $user -> mobile, 
					'email' 		=> $user -> email,
					'external_id' 	=> $user -> external_id
				),
				'transaction' 	=> $transactionPayload,
				'series_code' 	=> $loadedSeries -> id,
				'is_absolute' 	=> $loadedSeries -> discount_type == 'ABS' ? true : false,
				'coupon_value' 	=> $loadedSeries -> discount_value,
				'discount_code' => $loadedSeries -> discount_code,
				'item_status' 	=> array(
					'success' 		=> 'true',
					'code' 			=> ErrorCodes::$coupon['ERR_COUPON_SUCCESS'],
					'message' 		=> ErrorMessage::$coupon['ERR_COUPON_SUCCESS']
				), 
				'side_effects'	=> $sideEffects
			);
			if (!is_null($loadedSeries)) {
				$apiResponse['coupons']['coupon']['series_info'] = $respSeries;
			}

			global $apiWarnings; 
			$warnings = $apiWarnings -> getWarnings();
			if ($warnings != null) {
				$apiResponse['status']['message'] .= ", $warnings";
			}
		} 

		/* $gbl_item_status_codes?? To log in performance-logs and apache access logs */
		$gbl_item_status_codes = $apiResponse['coupons']['coupon']['item_status']['code'];

		return $apiResponse;
	}

	public function newGet($couponIdType, $couponIdentifiers, $displayUserId, $version, $inputCustomerId = null) {
		$success = false; 
		$errorCode = null;
		$response = null;
		$apiResponse = array();
		
		$couponIdentifiers = array_unique($couponIdentifiers);
		$couponCount = sizeof($couponIdentifiers);

		global $currentorg, $currentuser;
		$orgId = $currentorg -> org_id;

		$response = ApiUtil :: luciGetCoupon($orgId, $couponIdType, $couponIdentifiers, $inputCustomerId);
		if(!($response -> success) && (strcmp(($response -> exceptionCode), "ERR_INVALID_INPUT")== 0)){
                    $this -> logger -> error("Error in get for voucher via Luci-service, calling luciGetCoupon without customerId: " );
                    $response = ApiUtil :: luciGetCoupon($orgId, $couponIdType, $couponIdentifiers); 
                    $this -> logger -> info(" Luci response when no customer id is passed is ". print_r($response, true));
                }
		$success = $response -> success;
		if (!$success) {

			$errorCode = $response -> exceptionCode; 
			$this -> logger -> error("Error in get for voucher via Luci-service: " . print_r($response, true));

			$apiResponse['status'] = array(
				'success' 	=> 'false',
				'code' 		=> ErrorCodes::$api['FAIL'],
				'message' 	=> ErrorMessage::$api['FAIL']	
			);
			foreach ($couponIdentifiers as $couponIdentifier) {
				$apiResponse['coupons']['coupon'][] = array(
					'id'			=> $couponIdType == "coupon_id" ? $couponIdentifier : -1, 
					'code' 			=> $couponIdType == "coupon_code" ? $couponIdentifier : '',
					'item_status' 	=> array(
						'success' 	=> 'false',
						'code' 		=> ErrorCodes::$coupon[$errorCode],
						'message'	=> ErrorMessage::$coupon[$errorCode]
					)
				);
			}

			/*$arr_item_status_codes[] = $apiResponse['coupons']['item_status']['code'];*/
			/* $gbl_item_status_codes?? To log in performance-logs and apache access logs */
			$gbl_item_status_codes[] = $apiResponse['coupons']['coupon']['item_status']['code'];
		} else {

	        $loadedCoupon = null; 
			$loadedSeries = null;
			$respCoupons = array();
			$apiCoupon = array(); 
	        $loadedIds = array();
	        $loadedCodes = array();
	        /*$arr_item_status_codes[] = array();*/
	        global $error_count;
	        $error_count = 0;
			
			$loadedCoupons = $response -> coupons;
			foreach ($loadedCoupons as $loadedCouponResp) {
				
				$loadedCoupon = $loadedCouponResp -> coupon;

				$loadedCouponId = $loadedCoupon -> id;
				$loadedIds [] = $loadedCouponId; 
				$loadedCouponCode = trim($loadedCoupon -> couponCode);
				$loadedCodes [] = $loadedCouponCode;

				if (! $loadedCouponResp -> success) {

					$errorCode = $loadedCouponResp -> exceptionCode;

					$apiCoupon = array(
						'id' 		=> $loadedCouponId,
						'code' 		=> $loadedCouponCode,
						'item_status' 	=> array(
							'success' 	=> 'false',
							'code' 		=> ErrorCodes::$coupon[$errorCode],
							'message'	=> ErrorMessage::$coupon[$errorCode]
						)
					);
					$respCoupons[] = $apiCoupon;

					++$error_count;
					/*$arr_item_status_codes[] = $apiCoupon['item_status']['code'];*/
					continue;					
				} 

				$issuedToUserId = $loadedCoupon -> issuedToUserId;
				$issuedToUser = UserProfile::getById($issuedToUserId);
				if (!$issuedToUser) {
					$this -> logger -> debug("User not found for " + 
						"coupon with ID '$loadedCouponId' and code 'loadedCouponCode'");

					$apiCoupon = array(
						'id' 		=> $loadedCoupon -> id,
						'code' 		=> $loadedCoupon -> couponCode,
						'item_status' 	=> array(
							'success' 	=> 'false',
							'code' 		=> ErrorCodes::$api['SYSTEM_ERROR'],
							'message'	=> ErrorMessage::$api['SYSTEM_ERROR']
						)
					);
					$respCoupons[] = $apiCoupon;

					++$error_count;
					/*$arr_item_status_codes[] = $apiCoupon['item_status']['code'];*/
					continue;
				}

				if (! empty($loadedCoupon -> couponSeriesId)) {
					require_once "services/luci/service.php";

					$ls = new LuciService();
					$result = $ls -> getCouponSeriesById($orgId, $loadedCoupon -> couponSeriesId);
				
					if ($result -> success) {
						$loadedSeries = $result -> couponSeries;
					}
				}

				$apiCoupon = array(
					'id' 				=> $loadedCouponId,
					'code' 				=> $loadedCouponCode,
					'valid_till' 		=> $this -> luciDateToStr($loadedCoupon -> expiryDate, 'd-m-Y'), 
					'issued_on' 		=> $this -> luciDateToStr($loadedCoupon -> issuedDate, 'Y-m-d H:i:s'), 
					'valid_from'		=> $this -> luciDateToStr($loadedSeries -> redemption_valid_from, 'Y-m-d H:i:s'), 
					'series_id'			=> $loadedCoupon -> couponSeriesId, 
					'is_absolute'		=> $loadedSeries -> discount_type == 'ABS' ? true : false, 
					'value'				=> $loadedSeries -> discount_value, 
					'customer' 			=> array(
						'id'			=> $issuedToUserId, 
						'name' 			=> $issuedToUser -> first_name . ' ' . $issuedToUser -> last_name,
						'mobile' 		=> $issuedToUser -> mobile,
						'email' 		=> $issuedToUser -> email,
						'external_id' 	=> UserProfile::getExternalId($issuedToUserId)
					), 
					'item_status' 		=> array(
						'success' 		=> 'true',
						'code' 			=> ErrorCodes::$coupon['ERR_COUPON_SUCCESS'],
						'message'		=> ErrorMessage::$coupon['ERR_COUPON_SUCCESS']
					)
				); 
				$redemptionInfo = $loadedCoupon -> redeemedCoupons;
				if (! empty($redemptionInfo)) {
					$redemption = $redemptionInfo [0];
					$apiCoupon['redemption_info'] = array(
						"redeemed" 		=>  "true",
						"id" 			=> $redemption -> redemptionId, 
			            "redeemed_on"	=> $this -> luciDateToStr($redemption -> redemptionDate, 'Y-m-d H:i:s'),
			            "redeemed_by" 	=> $redemption -> redeemedByUserId, 
			            "redeemed_at" 	=> $redemption -> redeemedAtStore
		            );
				} 
				if ($displayUserId) {
					$apiCoupon['customer']['user_id'] = $issuedToUserId;
				}

				if ($version == "v1.1") {

					$apiCoupon['pincode'] = 'NULL';
					
					$entityId = $loadedCoupon -> issuedById;
					if (! empty($entityId)) {
						$issuedAtStore = $this -> fetchParentStore($orgId, $entityId);
						$apiCoupon['issued_store'] = array(
							'code' => $issuedAtStore['code'],
							'name' => $issuedAtStore['name']
						);
					}

					$redemption = $this -> fetchExtraRedemptionInfo($orgId, $loadedCouponId);
					if (! empty($redemption)) {
						$apiCoupon['redemption_info']['redeemed_at'] = $redemption['code'];

						$newRedemptionInfo = array(
							'transaction' => array( 
								'id' 		=> $redemption['transaction_id'],
								'bill_number' => $redemption['bill_number']
							), 
							'store' => array(
								'code' 		=> $redemption['code'],
								'name' 		=> $redemption['name']
							), 
							'redeemed_by' => array(
								'firstname' => $redemption['firstname'],
								'lastname' 	=> $redemption['lastname'],
								'email' 	=> $redemption['email'],
								'mobile' 	=> $redemption['mobile']
							)
						);
						$apiCoupon['redemption_info'] = 
							array_merge($apiCoupon['redemption_info'], $newRedemptionInfo);
					}
				}

				$respCoupons[] = $apiCoupon;

				/*$arr_item_status_codes[] = $apiCoupon['item_status']['code'];*/
			}
                        
			if ($couponIdType == "coupon_code") {
				$this -> logger -> info("Before 1: " . print_r($couponIdentifiers, true));
				$this -> logger -> info("Before 2: " . print_r($loadedCodes, true));
				$upperCaseCouponIdentifiers = array_map('strtoupper', $couponIdentifiers);
				$upperCaseCouponCodes = array_map('strtoupper', $loadedCodes);
				$this -> logger -> info("After 1: " . print_r($upperCaseCouponIdentifiers, true));
				$this -> logger -> info("After 2: " . print_r($upperCaseCouponCodes, true));
				$differential = array_diff($upperCaseCouponIdentifiers, $upperCaseCouponCodes);
			} else {
				$differential = array_diff($couponIdentifiers, $loadedIds);
			} 
			if (! empty($differential)) {
				foreach ($differential as $couponIdentifier) {
					++$error_count;

					$apiCoupon = array(
						'id' 		=> $couponIdType == "coupon_id" ? $couponIdentifier : -1,
						'code' 		=> $couponIdType == "coupon_code" ? $couponIdentifier : '',
						'item_status' 	=> array(
							'success' 	=> 'false',
							'code' 		=> ErrorCodes::$coupon['ERR_INVALID_INPUT'],
							'message'	=> ErrorMessage::$coupon['ERR_INVALID_INPUT']
						)
					);
					$respCoupons[] = $apiCoupon;
				}	
			}
			$this -> logger -> debug("Coupon count: $couponCount; Error count: $error_count");
			$status = 'SUCCESS';
			$statusStr = 'true';
			if ($error_count == $couponCount){
				$status = 'FAIL';
				$statusStr = 'false';
			} else if(($error_count < $couponCount) && ($errorCount > 0)){
				$status = 'PARTIAL_SUCCESS';
			}

			$apiResponse = array(
				'status' => array(
					'success' 	=> $statusStr,
					'status' 	=> $statusStr, 
					'code' 		=> ErrorCodes::$api[$status],
					'message' 	=> ErrorMessage::$api[$status]
				), 
				'coupons'  => array(
					'coupon' => $respCoupons
				)
			);
		} 

		/* $gbl_item_status_codes?? To log in performance-logs and apache access logs */
		/*$gbl_item_status_codes = $apiResponse['coupons'][]['item_status']['code'];*/
		return $apiResponse;
	}

	private function fetchParentStore($orgId, $tillId) {
		$parentStore = null;

		$db = new Dbase("masters", true);
		$sql = "SELECT oe.id, oe.code, oe.name 
				FROM masters.org_entity_relations oer
					JOIN masters.org_entities oe 
						ON oe.id = oer.parent_entity_id
				WHERE oer.child_entity_id = '$tillId' 
					AND oer.parent_entity_type = 'STORE' 
					AND oer.org_id = '$orgId'";
		$parentStore = $db -> query_firstrow($sql);

		
		return $parentStore;
	}

	private function fetchTillInfo($orgId, $tillId) {
		$db = new Dbase("masters", true);

		$sql = "SELECT code, name 
				FROM masters.org_entities
				WHERE id = '$tillId' 
					AND org_id = '$orgId'";
		return $db -> query_firstrow($sql);
	}

	private function fetchExtraRedemptionInfo($orgId, $couponId) {
		$redemption = null;

		$db = new Dbase("masters", true);
		$sql = "SELECT vr.bill_number, ll.id as transaction_id, 
				vr.used_by, oe.code, oe.name,
				u.firstname, u.lastname, u.email, u.mobile 
				FROM luci.voucher_redemptions vr
					JOIN masters.org_entities oe ON oe.id = vr.used_at_store
					LEFT OUTER JOIN user_management.users u on u.id = vr.used_by
					LEFT OUTER JOIN user_management.loyalty_log ll 
						ON ll.bill_number = vr.bill_number 
							AND ll.org_id = '{$orgId}'
				WHERE vr.org_id = '{$orgId}' 
					AND vr.voucher_id = '{$couponId}'";
		$result = $db -> query_firstrow($sql);

		if (! empty($result)) {
			$redemption = $result;
		}
		return $redemption;
	}

	private function saveCustomFields($scope, $customFields, $assocId) {
		$this -> logger -> debug("Saving '$scope' custom fields");

		$cfSaver = new CustomFields();
		if (! empty($customFields)) {
			
			$cfs = array();
			array_walk($customFields, function (&$cf,$key) use (&$cfs) {
			    $newCF = array(
			    	'field_name' => $cf['name'], 
			    	'field_value' => $cf['value']
			    );
			    $cfs[] = $newCF;
			});

			$lastUpdatedId = $cfSaver -> addMultipleCustomFieldsForAssocId($assocId, $cfs, $scope);
			$this -> logger -> debug("Last Updated Id is: $last_updated_id");
		}
	}

	private function luciDateToStr($date, $format = "Y-m-d") {
		$timestamp = $date / 1000;
		
		$dateTimeObj = new DateTime();
		$dateTimeObj -> setTimestamp($timestamp);	

		return $dateTimeObj -> format($format);
	}

	private function fetchInventoryInfoForCouponSeries($couponSeriesId, $productInfo) {
		$result = array();
		$result["products"] = null;
		$result["brands"] = null;
		$result["categories"] = null;

		/*$coupon_product_manager = new CouponProductManager();
		$products = $coupon_product_manager->getProductsForVoucherSeries($couponSeriesId);

		Transforming the luci-response to work with the existing functions: */
		$products = array();
		foreach ($productInfo as $productI) {
			$productTypeStr = ProductType::$__names[$productI['productType']];
			$productType = strtolower($productTypeStr);
			$products[$productType] = $productI['productIds'];
		}
		
		if (isset($products["sku"])) { 
			$skus = $this -> skuIdArrayToDetailsArray($products["sku"]); 
			if (!empty($skus)) { 
				$result["products"]["product"] = $skus;
			}
		} else {
			if (isset($products["brand"])) {
				$brands = $this -> brandIdArrayToDetailsArray($products["brand"]);
				if (!empty($brands)) {
					$result["brands"]["brand"] = $brands;
				}
			}

			if (isset($products["category"])) {
				$categories = $this -> categoryIdArrayToDetailsArray($products["category"]); 
				if (!empty($categories)) {
					$result["categories"]["category"] = $categories;
				}
			}
		}

		return $result;
	}
}
