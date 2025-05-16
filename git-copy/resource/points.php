<?php

require_once "resource.php";
include_once 'business_controller/points/PointsEngineServiceController.php';
include_once 'apiHelper/OTPManager.php';

/**
 * Handles all points related api calls.*
 *
 * @author pigol
 */

/**
 * @SWG\Resource(
 *     apiVersion="1.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/points",
 *     basePath="http://{{INTOUCH_ENDPOINT}}/v1.1"
 * )
 */
class PointsResource extends BaseResource{

	private $config_mgr;

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

				case 'redeem' :

					$result = $this->redeem( $version, $data, $query_params );
					break;

				case 'validationcode' :
					$result = $this->validationCode($query_params);
					break;

				case 'isredeemable':
					$result = $this->isRedeemable($query_params, $data, $version);
					break;

				case 'revert' :
					$result = $this->revert($data);
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
	 * Reverts the points which were redeemed against a bill
	 * If a bill is not found then the points are not reverted.
	 *
	 * public because we may call it from cancel bill api as well
	 *
	 */

	public function revert($data)
	{
		global $gbl_item_status_codes;
		$this->logger->debug("Starting points reversion, data: " . print_r($data, true));

		$input = $data['root']['revert'][0];
		$mobile = $input['customer']['mobile'];
		$email = $input['customer']['email'];
		$external_id = $input['customer']['external_id'];
		$transaction_number = (string)$input['transaction_number'];
		$redemption_time = (string)$input['redemption_time'];

		$this->logger->debug("mobile: $mobile, points: $points, transaction: $transaction_number time: $redemption_time");

		$response = array(
						'status' => array('success'=>'true', 'code'=>200, 'message'=>'Operation Successful'),
					);

		$revert = array(
							 'customer' => array('mobile'=>$mobile, 'email' => $email, 'external_id'=>$external_id),
							 'transaction_number' => $transaction_number,
							 'loyalty_points' => 0,
							 'item_status' => array('success'=>'true', 'code' => ErrorCodes::$points['ERR_POINTS_SUCCESS'],
													'message' => "Points reverted successfully"
											)
						);

		if((!Util::shouldBypassMobileValidation() && !Util::checkMobileNumberNew($mobile, array(), false))
				|| !Util::isMobileNumberValid($mobile)){
			$this->logger->error("Invalid mobile number: $mobile");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$revert['item_status']['success'] = 'false';
			$revert['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_IDENTIFIERS'];
			$revert['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_IDENTIFIERS'];

			$response['points']['revert'] = $revert;
			$gbl_item_status_codes = $response['points']['revert']['item_status']['code'];
			return $response;
		}

		/**
		if($points <= 0){
			$this->logger->error("Invalid points: $points");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAILE'];

			$revert['item_status']['success'] = 'false';
			$revert['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_POINTS'];
			$revert['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_POINTS'];

			$response['points']['revert'] = $revert;
			return $response;
		}**/


		$this->logger->debug("Doing the points revert request");

		$user = UserProfile::getByMobile($mobile);

		if(!$user){  //user does not exist
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$revert['points_reverted'] = 0;
			$revert['loyalty_points'] = 0;
			$revert['item_status']['success'] = 'false';
			$revert['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_IDENTIFIERS'];
			$revert['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_IDENTIFIERS'];

			$response['points']['revert'] = $revert;
			$gbl_item_status_codes = $response['points']['revert']['item_status']['code'];
			return $response;
		}

		$details = $user->getLoyaltyDetails();

		$revertable = $this->isRevertable($user, $transaction_number, $redemption_time);
		if($revertable !== TRUE)
		{
			$this->logger->error("Points cannot be reverted $revertable");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$revert['points_reverted'] = 0;
			$revert['loyalty_points'] = $details['loyalty_points'];
			$revert['item_status']['success'] = 'false';
			$revert['item_status']['code'] = ErrorCodes::$points[$revertable];
			$revert['item_status']['message'] = ErrorMessage::$points[$revertable];

			$response['points']['revert'] = $revert;
			$gbl_item_status_codes = $response['points']['revert']['item_status']['code'];
			return $response;
		}

		$db = new Dbase('users');

		$org_id = $this->currentorg->org_id;
		$user_id = $user->user_id;
		$store_id = $this->currentuser->user_id;
		$loyalty_id = $user->getLoyaltyId();


// 		$sql = "SELECT SUM(points_redeemed) AS points FROM loyalty_redemptions WHERE org_id = $org_id AND entered_by = $store_id
// 		        AND bill_number = '$transaction_number' AND user_id = $user->user_id
// 		       ";

// 		$points = $db->query_scalar($sql);

// 		$sql = "INSERT INTO awarded_points_log(org_id, user_id, loyalty_id, awarded_points, redeemed_points,
// 											expired_points, ref_bill_number, notes, awarded_by, awarded_time)
// 				VALUES ($org_id, $user_id, $loyalty_id, $points, 0, 0, '$transaction_number', 'POINTS_REVERTED',
// 						$store_id, NOW()
// 					   )
// 			   ";

// 		$id = $db->insert($sql);

		//if(($id > 0))
		if($loyalty_id > 0 )
		{
			$sql = "UPDATE loyalty 
					SET last_updated_by = $store_id
			     	AND last_updated = NOW() WHERE id = $loyalty_id and publisher_id = $org_id
			       ";
			//loyalty_points = loyalty_points + $points, 
			$db->update($sql);
			$this->logger->debug("updated");

			$details = $user->getLoyaltyDetails();
			$revert['loyalty_points'] = $details['loyalty_points'];
			$revert['points_reverted'] = $points;
			$response['points']['revert'] = $revert;
			$gbl_item_status_codes = $response['points']['revert']['item_status']['code'];
			return $response;
		}else{
			$this->logger->error("Error in updating..server side error");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$revert['points_reverted'] = 0;
			$revert['loyalty_points'] = $details['loyalty_points'];
			$revert['item_status']['success'] = 'false';
			$revert['item_status']['code'] = ErrorCodes::$api['SYSTEM_ERROR'];
			$revert['item_status']['message'] = ErrorMessage::$api['SYSTEM_ERROR'];

			$response['points']['revert'] = $revert;
			$gbl_item_status_codes = $response['points']['revert']['item_status']['code'];
			return $response;
		}
		return $response;
	}


	private function isRevertable($user, $transaction_number, $redemption_time)
	{
		// this function alwasy allows revert - :)
		return true;
		
// 		$this->logger->debug("checking if points are revertable $points $transaction_number $redemption_time");

// 		$org_id = $this->currentorg->org_id;
// 		$store_id = $this->currentuser->user_id;

// 		$sql = "SELECT COUNT(*) AS count FROM loyalty_log WHERE org_id = $org_id AND entered_by = $store_id
// 				AND bill_number = '$transaction_number' AND user_id = $user->user_id
// 		       ";
// 		$db = new Dbase('users');
// 		$count = $db->query_scalar($sql);

// 		$this->logger->info("Count of bills matching the criterion: $count");

// 		if(!($count == 1)){
// 			$this->logger->error("Bill not found");
// 			return 'ERR_POINTS_BILL_NOT_FOUND';
// 		}

// 		$sql = "SELECT SUM(points_redeemed) AS points FROM loyalty_redemptions WHERE org_id = $org_id AND entered_by = $store_id
// 		        AND bill_number = '$transaction_number' AND user_id = $user->user_id
// 		       ";

// 		$points_redeemed  = $db->query_scalar($sql);

// 		if(!($points_redeemed > 0))
// 		{
// 			$this->logger->error("No points redeemed on this bill");
// 			return 'ERR_NO_POINTS_REDEEMED_ON_BILL';
// 		}


// 		$sql = "SELECT COUNT(*) FROM awarded_points_log WHERE org_id = $org_id AND user_id = $user->user_id
// 				AND ref_bill_number = '$transaction_number' AND notes = 'POINTS_REVERTED'
// 		       ";

// 		$count = $db->query_scalar($sql);
// 		if($count > 0)
// 		{
// 		   $this->logger->error("Points already reverted for this bill");
// 		   return 'ERR_POINTS_ALREADY_REVERTED_ON_BILL';
// 		}

// 		$this->logger->debug("All conditions match..reverting points");
// 		return true;
	}


	/**
	 * Checks whether the points the guy is trying to redeem
	 * can actually be redeemed or not.
	 */
        
        /**
         * @SWG\Api(
         * path="/points/isredeemable.{format}",
        * @SWG\Operation(
        *     method="GET", summary="Check if points are redeemable",
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
         *    ),
         *    @SWG\Parameter(
         *      name = "points",
         *      type = "integer",
         *      paramType = "query",
         *      required = true,
         * description = "Points to be redeemed"
         *    ),
         *    @SWG\Parameter(
         *      name = "validation_code",
         *      type = "string",
         *      description = "Validation code",
         *      required = true,
         *      paramType = "query"
         *    )
        * ))
        */
	private function isRedeemable($query_params, $data, $version)
	{
		global $gbl_item_status_codes, $currentorg;
		$this->logger->debug("Checking for points redemption validity, input: " . print_r($query_params, true));

		$mobile=$email=$external_id="";
		if(isset($query_params['mobile']))
			$mobile = $query_params['mobile'];
		if(isset($query_params['email']))
			$email = $query_params['email'];
		if(isset($query_params['external_id']))
			$external_id=$query_params['external_id'];
		$points = (int)$query_params['points'];
		$validation_code = $query_params['validation_code'];
                $use_missed_call_for_validation = false;
                if(isset($query_params['validation_type']))
                {
                    if($query_params['validation_type'] == 'MISSED_CALL')
                        $use_missed_call_for_validation = true;
                }
                
                if(isset($query_params['skip_validation']))
                {
                    if($query_params['skip_validation'] == 'true')
                        $skip_validation = true;
                }
                
                $userAgent = trim(strtolower($_SERVER['HTTP_USER_AGENT']));
                $special_user_agents = array(
                		"clienteling_net_v5.5.6.7", 
                		"storecenter_net_v1.0.7.8"
                );
                if(in_array( $userAgent, $special_user_agents)) {
                	$skip_validation = true;
                	$this->logger->info("These clients has cant pass me oTp");
                }
                

		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $query_params['mobile'],
						'email' => $query_params['email'],
						'external_id' => $query_params['external_id']
				) );
		$this->logger->debug("mobile: $mobile, email: $email, external_id: $external_id, points: $points, code: $validation_code");

		$response = array(
						'status' => array('success'=>'true', 'code'=>200, 'message'=>'Operation Successful'),
					);

		$redeemable = array(
							 'mobile' => $mobile,
							 'email' => $email,
							 'external_id' => $external_id,
							 'points' => $points,
							 'is_redeemable' => 'true',
							 'points_redeem_value' => 0,
							 'points_currency_ratio' => 0,
							 'item_status' => array('success'=>'true', 'code' => ErrorCodes::$points['ERR_POINTS_SUCCESS'],
													'message' => ErrorMessage::$points['ERR_POINTS_SUCCESS']
											)
							);
		$this->logger->debug("points redeemable: "  .print_r($redeemable, true));

        //Fetching Currency Points Ratio from Points engine
        if(Util::isPointsEngineActive())
        {
	        $pointsEngineServiceController = new PointsEngineServiceController();
	        $currencyPointsRatio = $pointsEngineServiceController->
	        			getPointsCurrencyRatio($this->currentorg->org_id);
        }
        else
        {
        	$this->logger->debug("Points Engine is not Active, using points to currency ratio as 1");
        	$currencyPointsRatio = 1;
        }
        $redeemable['points_currency_ratio'] = $currencyPointsRatio;

        $this->logger->debug("points redeemable: "  .print_r($redeemable, true));

        if( $version == 'v1' ){

			unset( $redeemable['points_redeem_value'] );
			unset( $redeemable['points_currency_ratio'] );
			unset($redeemable['email']);
			unset($redeemable['external_id']);
		}

		if(!empty($mobile))
		if((!Util::shouldBypassMobileValidation() && !Util::checkMobileNumberNew($mobile, array(), false))
				|| !Util::isMobileNumberValid($mobile)){
			$this->logger->error("Invalid mobile number: $mobile");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_IDENTIFIERS'];
			$redeemable['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_IDENTIFIERS'];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}

		if($points <= 0){
			$this->logger->error("Invalid points: $points");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAILE'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_POINTS'];
			$redeemable['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_POINTS'];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}

		$this->logger->debug("Doing the points redeem request validation");

		try {
            $user = $this->getUser($mobile, $email, $external_id);
        }
        catch(Exception $ex)
        {
            $user = false;
        }
        if($user)
        {
            $redeemable['mobile'] = $user->mobile;
            $redeemable['email'] = $user->email;
            $redeemable['external_id'] = $user->external_id;
        }

		if(!$user){  //user does not exist
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points['ERR_INVALID_IDENTIFIERS'];
			$redeemable['item_status']['message'] = ErrorMessage::$points['ERR_INVALID_IDENTIFIERS'];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}
		else
                {
                    // Log redemption
                    ApiUtil::logRedemption($currentorg->org_id, $this->currentuser->user_id, $user->user_id, 'POINTS', 'ISREDEEMABLE', $points, $skip_validation);
                }

                
		if( self::isFraudUser( $user->user_id ) )
		{
			$this->logger->error("user is fraud	, points isredeemable is false");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points['ERR_LOYALTY_FRAUD_USER'];
			$redeemable['item_status']['message'] = ErrorMessage::$points['ERR_LOYALTY_FRAUD_USER'];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}
                
                
		try{
			$this->isPointsRedemptionAllowedForUser($user->user_id);
		}catch(Exception $e)
		{
			$this->logger->error("user is blocked, points isredeemable is false");
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points[$e->getMessage()];
			$redeemable['item_status']['message'] = ErrorMessage::$points[$e->getMessage()];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}

		$loyalty_id = $user->getLoyaltyId();
		$lm = new LoyaltyModule();
		$loyalty_cntrl = new LoyaltyController($lm, $this->currentuser);

		//Check if we are allowed to redeem by the customer
		$disallow_fraud_statuses = json_decode($this->currentorg->getConfigurationValue(CONF_FRAUD_STATUS_CHECK_REDEMPTION, json_encode(array())), true);
		//get the fraud status for the customer
		$customer_fraud_status = $loyalty_cntrl->getFraudStatus($user->user_id);
		if(count($disallow_fraud_statuses) > 0 && strlen($customer_fraud_status) > 0 && in_array($customer_fraud_status, $disallow_fraud_statuses))
		{
			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points[ERR_LOYALTY_FRAUD_USER];
			$redeemable['item_status']['message'] = ErrorMessage::$points[ERR_LOYALTY_FRAUD_USER];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}

		//PE call to get points to currency ratio
		$this->logger->info("getting points currency ratio for PE");
		$pes = new PointsEngineServiceController();

		$points_redeem_value = $points;

		$points_currency_ratio = 1;
		if(Util::isPointsEngineActive())
		{
			$points_currency_ratio = $pes->getPointsCurrencyRatio($this->currentorg->org_id);
		}
		$this->logger->debug("PE points to currency ratio : $points_currency_ratio");
		if( $points_currency_ratio > 0 )
			$points_redeem_value = round($points*$points_currency_ratio, 2);

		$redeemable['points_currency_ratio']=$points_currency_ratio;
		$redeemable['points_redeem_value']=$points_redeem_value;
		if( $version == 'v1'){

			unset( $redeemable['points_redeem_value'] );
			unset( $redeemable['points_currency_ratio'] );
		}
                
		$status = $loyalty_cntrl->isPointsRedeemable($user, $loyalty_id, $points, "", $validation_code, true, false, '', array(), false, $use_missed_call_for_validation, false, 0, "", $skip_validation, true);
		$this->logger->debug("statys: $status");
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $user->mobile,
						'email' => $user->email,
						'external_id' => $user->external_id,
						'user_id' => $user->user_id
				) );

		if(!($status == ERR_LOYALTY_SUCCESS))
		{
			$this->logger->debug("Points are not redeemable: $status");

			$response['status']['success'] = 'false';
			$response['status']['code'] = ErrorCodes::$api['FAIL'];
			$response['status']['message'] = ErrorMessage::$api['FAIL'];

			$redeemable['is_redeemable'] = 'false';
			$redeemable['item_status']['success'] = 'false';
			$redeemable['item_status']['code'] = ErrorCodes::$points[$status];
			$redeemable['item_status']['message'] = ErrorMessage::$points[$status];

			$response['points']['redeemable'] = $redeemable;
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}else{  //points can be redeemed

			$response['status']['success'] = 'true';
			$response['status']['code'] = ErrorCodes::$api['SUCCESS'];
			$response['status']['message'] = ErrorMessage::$api['SUCCESS'];

			$redeemable['is_redeemable'] = 'true';
			$redeemable['item_status']['success'] = 'true';
			$redeemable['item_status']['code'] = ErrorCodes::$points['ERR_POINTS_SUCCESS'];
			$redeemable['item_status']['message'] = "Points can be redeemed";

			$response['points']['redeemable'] = $redeemable;

			$this->logger->debug("response: " . print_r($response, true));
			$gbl_item_status_codes = $response['points']['redeemable']['item_status']['code'];
			return $response;
		}

	}

	/**
	 * Enables redemption of points from InTouch System.
	 * @param $data
	 */
        
        /**
         * @SWG\Model(
         * id = "Customer",
         * @SWG\Property(
         * name = "mobile",
         * type = "string"
         * ),
         * @SWG\Property(
         * name = "email",
         * type = "string"
         * ),
         * @SWG\Property(
         * name = "external_id",
         * type = "string"
         * )
         * )
         */
        
        /**
         * @SWG\Model(
         * id = "Redemption",
         * @SWG\Property(
         *      name = "points_redeemed",
         *      type = "inetger"
         * ),
         * @SWG\Property(
         * name = "customer",
         * type = "Customer"
         * ),
         * @SWG\Property(
         * name = "transaction_number",
         * type = "string"
         * ),
         * @SWG\Property(
         * name = "validation_code",
         * type = "string"
         * )
         * )
         **/
        
        /**
         * @SWG\Model(
         * id = "RedemptionRequest",
         * @SWG\Property(
         * name = "redeem",
         * type = "array",
         * items="$ref:Redemption"
         * )
         * )
         */
        
        /**
         * @SWG\Model(
         * id = "RedemptionRoot",
         * @SWG\Property(
         * name = "root",
         * type = "RedemptionRequest"
         * )
         * )
         */
        
        /**
         * @SWG\Api(
         * path="/points/redeem.{format}",
        * @SWG\Operation(
        *     method="POST", summary="Redeem points",
         * @SWG\Parameter(
         * name = "request",
         * paramType="body",
         * type="RedemptionRoot")
        * ))
        */
	private function redeem( $version, $data, $query_params )
	{
		global $currentorg;
                $use_missed_call_for_validation = false;
                if(isset($query_params['validation_type']))
                {
                    if($query_params['validation_type'] == 'MISSED_CALL')
                        $use_missed_call_for_validation = true;
                }
                if(isset($query_params['skip_validation']))
                {
                    if($query_params['skip_validation'] == 'true')
                        $skip_validation = true;
                }
                
		$this->logger->debug("Redeeming points with data: " . print_r($data, true));
		$this->logger->debug("redemption info: " . json_encode($data));
		$redemption_info = $data['root']['redeem'][0];
		$points = $redemption_info['points_redeemed'];
		$transaction_number = $redemption_info['transaction_number'];
		$validation_code = $redemption_info['validation_code'];
		$mobile = $redemption_info['customer']['mobile'];
		$email = $redemption_info['customer']['email'];
		$external_id = $redemption_info['customer']['external_id'];
        $customerId = $redemption_info['customer']['id'];
        $notes = $redemption_info['notes'];
		$time  = $redemption_info['redemption_time'];
		if(trim($time) == '')
		{
			$time = Util::getCurrentTimeForStore($this->currentuser->user_id);
		}
        global $gbl_country_code, $gbl_item_status_codes;
        
        if(strtotime($time)===false){
			$response = array();
			$response['status'] = array('success' => 'false','code' => ErrorCodes::$api['FAIL'],'message' => ErrorMessage::$api['FAIL']);

			$response['responses']['points'] = array('mobile' => $mobile, 'email' => $email, 'external_id' => $external_id,'user_id' => $customerId,
								'item_status' => array('success' => 'false','code' => ErrorCodes::$points['ERR_PE_INVALID_REDEMPTION_TIME'],
													   'message' => ErrorMessage::$points['ERR_PE_INVALID_REDEMPTION_TIME']
													  ));
			$gbl_item_status_codes = $response['responses']['points']['item_status']['code'];
			return $response;
		}

		if(isset($redemption_info['customer']['country_code']))
		{
			$gbl_country_code = $redemption_info['customer']['country_code'];
		}
		$custom_fields = $redemption_info['custom_fields']['fields'];

		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $redemption_info['customer']['mobile'],
						'email' => $redemption_info['customer']['email'],
						'external_id' => $redemption_info['customer']['external_id'],
						'transaction_number' => $redemption_info['transaction_number']
				) );
		if($points <= 0){
			$response = array();
			$response['status'] = array('success' => 'false','code' => ErrorCodes::$api['FAIL'],'message' => ErrorMessage::$api['FAIL']);

			$response['responses']['points'] = array('points_redeemed' => $points,
								'item_status' => array('success' => 'false','code' => ErrorCodes::$points['ERR_INVALID_POINTS'],
													   'message' => ErrorMessage::$points['ERR_INVALID_POINTS']
													  ));
			$gbl_item_status_codes = $response['responses']['points']['item_status']['code'];
			return $response;
		}


		if(( (!Util::shouldBypassMobileValidation() && !Util::checkMobileNumberNew($mobile, array(), false))
				|| !Util::isMobileNumberValid($mobile) )
				&& !Util::checkEmailAddress($email) && empty($external_id) && empty($customerId))
		{
			$response = array();
			$response['status'] = array('success' => 'false','code' => ErrorCodes::$api['FAIL'],'message' => ErrorMessage::$api['FAIL']);

			$response['responses']['points'] = array('mobile' => $mobile, 'email' => $email, 'external_id' => $external_id,'user_id' => $customerId,
								'item_status' => array('success' => 'false','code' => ErrorCodes::$points['ERR_INVALID_IDENTIFIERS'],
													   'message' => ErrorMessage::$points['ERR_INVALID_IDENTIFIERS']
													  ));
			$gbl_item_status_codes = $response['responses']['points']['item_status']['code'];
			return $response;
		}


		try{
			$mem_cache_mgr = MemcacheMgr::getInstance();
            $user = $this->getUser($mobile, $email, $external_id,true,false,$customerId);
			$loyalty_id = $user->getLoyaltyId();
			
			$this->isPointsRedemptionAllowedForUser($user->user_id);
			
			//trigger cache update
			ApiCacheHandler::triggerEvent("points_update", isset($user->user_id)?$user->user_id:"");
			ApiUtil::mcUserUpdateCacheClear($user->user_id);
			//
			$lm = new LoyaltyModule();
			$loyalty_cntrl = new LoyaltyController($lm, $this->currentuser);
                        ApiUtil::logRedemption($currentorg->org_id, $this->currentuser->user_id, $user->user_id, 'POINTS', 'REDEEM', $points, $skip_validation);
			$ret = $loyalty_cntrl->redeemPoints($user, $loyalty_id, $points, $transaction_number, $validation_code,
							    $notes, $this->currentuser->user_id, $time, false, $use_missed_call_for_validation, $skip_validation);

			Util::saveLogForApiInputDetails(
								array(
										'mobile' => $user->mobile,
										'email' => $user->email,
										'external_id' => $user->external_id,
										'user_id' => $user->user_id
										) );
            try{

                //init mem chache mgr
                $mem_cache_pe_lock_key = 'PE_'.$this->currentorg->org_id."_".$this->currentuser->user_id."_".$loyalty_id;

                $sendLockMail = 0;
                while( !$mem_cache_mgr->acquireLock( $mem_cache_pe_lock_key, true ) ){

                    $sendLockMail++;
                    $this->logger->debug( "$mem_cache_pe_lock_key has already aquire lock on some
							other thread. Waiting for a sec" );
                    if( $sendLockMail == 1 ){

                        $mem_lock_org_id = $this->currentorg->org_id;
                        $mem_lock_store_id = $this->currentuser->user_id;
                        $mem_lock_store_name = $this->currentuser->username;
                        $sendEmailBody = " Duplicate Request For
						Org : $mem_lock_org_id
						Store : $mem_lock_store_id
						Store Name : $mem_lock_store_name
						Loyalty id : ".$loyalty_id;

                        Util::sendEmail( 'nagios-alerts@dealhunt.in', 'DUPLICATE POINTS REDEMPTION REQUEST',
                            $sendEmailBody, $mem_lock_org_id );
                    }
                    sleep( 1 );
                }
                $sendLockMail = 0;

            }catch( Exception $e ){

                $this->logger->debug( "Mem Cache Not Running" );
            }


                        if($ret > 0 && Util::canCallPointsEngine())
                        {
                              $points_engine_status = $this->redeemFromPointsEngine($user->user_id, $loyalty_id, $points, $ret,
                                                                       $this->currentuser->user_id, $transaction_number, $time, $user, $validation_code, $notes);

                              if(!$points_engine_status)
                              {
                                $this->logger->error("Error in redeeming points from points engine");
                                throw new Exception( "ERR_IN_POINTS_ENGINE" );
                              }
                        }
	$mem_cache_mgr->releaseLock( $mem_cache_pe_lock_key );

			if($ret > 0){
				$cf_data = array();
				//single custom field
				//iska kuch karna padega
				if(isset($custom_fields['field']['name'])){
					$name = $custom_fields['field']['name'];
					$value = $custom_fields['field']['value'];
					array_push($cf_data, array('field_name'=>$name, 'field_value'=>$value));
				}else{
					foreach($custom_fields['field'] as $k=>$v){
						array_push($cf_data, array('field_name'=>$v['name'], 'field_value'=>$v['value']));
					}
				}

				if(count($cf_data) > 0){
					$cf = new CustomFields();
					$cf->addCustomFieldDataForAssocId($ret, $cf_data);
				}
			}else{ //error.. These are old error codes.. need a proper mapping to the new ones
				$this->logger->debug("Error in points redemption: $ret");
				$code = (string)$ret;
				$response['status'] = array(
									'success' => 'false',
									'code' => ErrorCodes::$api['FAIL'],
									'message' => ErrorMessage::$api['FAIL']
				);

				$response['responses']['points'] = array(
												  'points_redeemed' => $points,
												  'item_status' => array(
																	 'success'	=> 'false',
												  					 'code' => ErrorCodes::$points[$code],
																	 'message' => ErrorMessage::$points[$code]
																	 )
												);
				if( $version == 'v1.1' ){

					$response['responses']['points']= array_merge(
														array(
																'mobile' => $user->mobile,
																'email' => $user->email,
																'external_id' => $user->external_id,
                                                                'user_id' => $user->user_id
																),
														$response['responses']['points'] );
				}
				return $response;
			}

			//Invalidating validation code since redemption is successful
			$otp_manager = new OTPManager();
			$otp_manager->invalidate( $user->user_id, $validation_code, 'POINTS' );
                        //Invalidate missed calls
                        $loyalty_cntrl->isAuthorizedByMissedCall($user, true);

			$response['status'] = array(
									'success' => 'true',
									'code' => ErrorCodes::$api['SUCCESS'],
									'message' => ErrorMessage::$api['SUCCESS']
								);
//			$loyalty_details = $user->getLoyaltyDetails();

            try {
                $pointsEngineServiceController = new PointsEngineServiceController();

                //Fetching latest points of customer from PE
                $summary = $pointsEngineServiceController->getPointsSummaryForCustomer($this->currentorg->org_id, $user->getUserId());

                $balance = floor($summary->currentPoints); //$loyalty_details['loyalty_points'];   //TODO : remove floor once client can handle double

//                $this->logger->debug("details: " . print_r($loyalty_details, true));
                //Fetching Currency Points Ratio from Points engine
                $redeemed_value = $points;
                $currencyPointsRatio = $pointsEngineServiceController->
                    getPointsCurrencyRatio($this->currentorg->org_id);
            } catch (Exception $pe) {
                $this->logger->debug("Failed to get info from PE");
                $this->logger->debug("PE Exception : " + $pe->getMessage());
            }
			if( $currencyPointsRatio > 0 )
				$redeemed_value = round($points * $currencyPointsRatio, 2);
			$response['responses']['points'] = array(
												  'points_redeemed' => $points,
												  'redeemed_value' => $redeemed_value,
												  'balance' => $balance,
												  'item_status' => array(
																	 'success'	=> 'true',
												  					 'code' => ErrorCodes::$points['ERR_POINTS_SUCCESS'],
																	 'message' => 'Points Redeemed'
																 )
												);
			if( $version == 'v1.1' ){

				$response['responses']['points']= array_merge(
						array(
								'mobile' => $user->mobile,
								'email' => $user->email,
								'external_id' => $user->external_id,
                                'user_id' => $user->user_id
						),
						$response['responses']['points'] );
				$lm = new ListenersMgr($currentorg);
				$response['responses']['side_effects'] = $lm->getSideEffectsAsResponse($GLOBALS['listener']);
				$GLOBALS['listener'] = array();
			}
			$gbl_item_status_codes = $response['responses']['points']['item_status']['code'];
			return $response;

		}catch(Exception $e){
			$mem_cache_mgr->releaseLock( $mem_cache_pe_lock_key );
			$this->logger->error("Caught an unexpected exception, Code:" . $e->getCode()
								 . " Message: " . $e->getMessage()
								);
			$item_status_code = $e->getMessage();

			$item_status_code = ( empty( $item_status_code ) ) ? Util::convertPointsEngineErrorCode( $e->statusCode ) : $item_status_code;
			$this->logger->error( "PointsResource::redeem() Exception ".$item_status_code);

			if( !isset( ErrorCodes::$points[$item_status_code] ) ){

				$this->logger->error("$item_status_code is not defined as Error Code making it more generic");
				$item_status_code = 'ERR_POINTS_FAIL';
			}

			$response['status'] = array(
					'success' => 'false',
					'code' => ErrorCodes::$api['FAIL'],
					'message' => ErrorMessage::$api['FAIL']
			);

			$response['responses']['points'] = array(
					'points_redeemed' => $points,
					'redeemed_value' => '0',
					'item_status' => array(
							'success'	=> 'false',
							'code' => ErrorCodes::$points[$item_status_code],
							'message' => ErrorMessage::$points[$item_status_code]
					)
			);
			if( $version == 'v1.1' ){

				if( $user ){

					$mobile = $user->mobile;
					$email = $user->email;
					$external_id = $user->external_id;
					$customerId=$user->user_id;
				}
				$response['responses']['points']= array_merge(
						array(
								'mobile' => $mobile,
								'email' => $email,
								'external_id' => $external_id,
                                'user_id' =>$customerId
						),
						$response['responses']['points'] );
			}
			$gbl_item_status_codes = $response['responses']['points']['item_status']['code'];
			return $response;
		}

	}


        private function redeemFromPointsEngine($user_id, $loyalty_id, $points, $redemption_id, $store_id, $bill_number, $redemption_time, $user, $validation_code, $notes)
        {
        		global $currentorg;
                if(Util::canCallPointsEngine())
                {
                        try{
                                $event_client = new EventManagementThriftClient();
                                $org_id = $this->currentorg->org_id;
                                //$points = $points;
                                $store_id = $this->currentuser->user_id;
                                $bill_number = $bill_number;

                                if(trim($redemption_time) == '')
                                {
                                	$redemption_time = Util::getCurrentTimeForStore($store_id);
                                }
                                $timeInMillis = Util::deserializeFrom8601($redemption_time);

                                if($timeInMillis == -1 || !$timeInMillis )
                                {
                                        throw new Exception("Cannot convert '$r_time' to timestamp", -1, null);
                                }
                                $timeInMillis = $timeInMillis * 1000;


								if(Util::canCallEMF())
								{
									try{
										$emf_controller = new EMFServiceController();
										$commit = Util::isEMFActive();
										$this->logger->debug("Making pointsRedemptionEvent call to EMF");
										
										if($bill_number)
										{
											$db = new Dbase('users');
											$loyalty_log_id = $db->query_firstrow("SELECT ll.id as loyalty_log_id FROM loyalty_log as ll
													WHERE ll.org_id = $org_id AND ll.bill_number='$bill_number' and ll.loyalty_id = $loyalty_id
													AND ll.date >= '".  date( 'Y-m-d h:i:s', $timeInMillis - 300 )."' and ll.date <= '".  date( 'Y-m-d h:i:s', $timeInMillis + 300 )."' ");

											$loyalty_log_id = $loyalty_log_id ? $loyalty_log_id["loyalty_log_id"] : null;
										}
										else
											$loyalty_log_id = null;
										
										$emf_result = $emf_controller->pointsRedemptionEvent ($org_id, $user_id, $points,
												$store_id, $bill_number, $timeInMillis, $commit,
												$loyalty_log_id, $validation_code, $notes, $reference_id=-1 );
										
// 										$emf_result = $emf_controller-> pointsRedemptionEvent(
// 												$org_id,$user_id,$points,$store_id,$bill_number,$timeInMillis,$commit);
										
										
										$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
										$lm = new ListenersMgr($currentorg);
										$lm->issuedVoucherDetails($coupon_ids);
										
										if($commit && $emf_result !== null )
										{
											$pesC = new PointsEngineServiceController();

											$pesC->updateForPointsRedemptionTransaction(
													$org_id, $user_id, $loyalty_id, $points,
													$timeInMillis, $redemption_id);
										}
									}
									catch(Exception $e)
									{
										$this->logger->error("Error while making pointsRedemptionEvent to EMF: ".$e->getMessage());
										if(Util::isEMFActive())
										{
											$this->logger->error("Rethrowing EMF Exception AS EMF is Active");
											throw $e;
										}
									}
								}
								if(!Util::isEMFActive())
								{
									if($bill_number)
									{
										$db = new Dbase('users');
										$loyalty_log_id = $db->query_first("SELECT ll.id as loyalty_log_id FROM loyalty_log as ll
												WHERE ll.org_id = $org_id AND ll.bill_number='$bill_number' and ll.loyalty_id = $loyalty_id
												AND ll.date >= '".  date( 'Y-m-d h:i:s', $timeInMillis - 300 )."' and ll.date <= '".  date( 'Y-m-d h:i:s', $timeInMillis + 300 )."' ");
											
										$loyalty_log_id = $loyalty_log_id ? $loyalty_log_id["loyalty_log_id"] : -1;
									}
									else
										$loyalty_log_id = -1;
										
									$result = $event_client->pointsRedemptionEvent($org_id, $user_id, $points,
										$store_id, $bill_number, $timeInMillis, $loyalty_log_id );
									
// 	                                $result = $event_client->pointsRedemptionEvent(
// 	                                                $org_id, $user_id, $points, $store_id,
// 	                                                $bill_number, $timeInMillis);
	                                $this->logger->debug("Points Engine call result: " . print_r($result, true));

	                                $evaluation_id = $result->evaluationID;

	                                if($result != null && $evaluation_id > 0){
	                                        $event_commit_result = $event_client->commitEvent($result);
	                                        $this->logger->debug("Result of commit: " . print_r($event_commit_result, true));

	                                        //Update the old tables from the points engine view
	                                        $pesC = new PointsEngineServiceController();

	                                        //threw exception in case of no redemption and success response from points engine
	                                        $customerSummary = $pesC->getPointsSummaryForCustomer($org_id, $user_id);
	                                        $old_points = $user->loyalty_points;
	                                        $new_points = $customerSummary->currentPoints;

	                                        if(! (floor($new_points) < $old_points))
	                                        {
	                                        	$this->logger->debug("Old Current Points: $old_points,
	                                        			New Current Points: $new_points,
	                                        			Points to be redeemed: $points");
	                                        	$this->logger->error("Points couldn't be redeemed from points engine and reponse was success, throwing error");
	                                        	throw new Exception("Points couldn't be redeemed");
	                                        }

	                                        $pesC->updateForPointsRedemptionTransaction(
	                                                        $org_id, $user_id, $loyalty_id, $points,
	                                                        $timeInMillis, $redemption_id);
	                                }
								}

                        }

            catch (emf_EMFException $emfEx) {
			
				$this->logger->error("Pointsresource : Exception in Points Engine");
				//convert EMF Error codes is not there because all error codes are same as Points Engine
				$errorCode = Util::convertPointsEngineErrorCode( $emfEx->statusCode );
				// return the new eror code 
				//if(ErrorCodes::$points[-($emfEx->statusCode)])
				{
					throw new Exception($emfEx->errorMessage, $emfEx->statusCode);
				}
					
			}
                        
			
                        catch (eventmanager_EventManagerException $ex) {
                                //TODO :: create mapping to old error codes
                                $this->logger->error("Exception in Points Engine... pull a hair from abhilash's head");
                                $this->logger->error("Error code: " . $ex->statusCode . " Message: " . $ex->errorMessage );


                                if(Util::isPointsEngineActive()) {
                                        //$this->deleteRedemptionRecord($redemption_id);
                                        throw $ex;
                                }

                        } catch(Exception $ex){
                                $this->logger->error("Error in signalling event for points redemption");
                                $this->logger->error("Error Code: " . $ex->getCode() . " Error Message: " . $ex->getMessage());

                                if(Util::isPointsEngineActive()) {

                                        //$this->deleteRedemptionRecord($redemption_id);
                                        throw $ex;
                                }

                        }
                return true;
        }
        }


        /**
         * @SWG\Api(
         * path="/points/validationcode.{format}",
         * @SWG\Operation(
         *     method="GET", summary="Issues validation code for redeeming points",
         *    @SWG\Parameter(
         *    name = "mobile",
         *    type = "string",
         *    paramType = "query",
         *    description = "Mobile number of customer"
         *    ),
         *    @SWG\Parameter(
         *    name = "email",
         *    type = "string",
         *    paramType = "query",
         *	  description = "Email of customer"
         *    ),
         *    @SWG\Parameter(
         *      name = "points",
         *      type = "integer",
         *      paramType = "query",
         * 		description = "Points to be redeemed"
         *    )
         * ))
         */
	private function validationCode($data)
	{
                $communication_channel = $this->config_mgr->getKey('OTP_COMMUNICATION_CHANNEL');
                
                $is_otp_sent_by_email = false;
                $is_otp_sent_by_sms = false;
                $ret = true;
                
                $this->logger->debug("OTP communication channel configured is $communication_channel");
		$should_return_user_id = $data['user_id'] == 'true'? true : false;
	  	global $currentorg, $currentuser, $gbl_item_status_codes;
		$this->logger->debug("Starting the validationcode generation: " . print_r($data, true));
		Util::saveLogForApiInputDetails(
				array(
						'mobile' => $data['mobile'],
						'email' => $data['email'],
						'external_id' => $data['external_id'],
                       'user_id' => $data['id']
				) );

		$response = array();


		$points_redeemed = (int)$data['points'];

        try {
            $user = $this->getUser($data['mobile'], $data['email'], $data['external_id'],true,false,$data['id']);
        }
        catch(Exception $ex)
        {
            $user = false;
        }

		if($currentorg->getConfigurationValue(CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
			$additional_bits = $points_redeemed;

		if($user == false){

			$this->logger->debug("User not found for mobile: $mobile or email: $email for this organization");
			$error_key = 'ERR_USER_NOT_FOUND';
			$status = 'FAIL';
			$response = array(
					'status'=>array('success'=>false,'code'=>ErrorCodes::$api[$status],'message'=>ErrorMessage::$api[$status]),
					'validation_code' => array('code'=>array(
							'user_id' => -1,
							'mobile' => $data['mobile'],
							'email' => $data['email'],
							'external_id' => $data['external_id'],
							'points' =>  $points_redeemed,
							'item_status' => array('success'=>false,'code'=>ErrorCodes::$points[$error_key],'message'=>ErrorMessage::$points[ $error_key ])
					)
					)
			);
			if(!$should_return_user_id)
			{
				unset($response['validation_code']['code']['user_id']);
			}
			return $response;

		}else{
			
			$user->load( true );
			ApiCacheHandler::triggerEvent("otp",$user->user_id);
			$otp_manager = new OTPManager();
			$code = $otp_manager->issue( $user->user_id, 'POINTS', $points_redeemed );
			/* $code = $v->issueValidationCode($currentorg, $mobile,$external_id, 2, time(), $currentuser->user_id, $additional_bits); */

			Util::saveLogForApiInputDetails(
					array(
							'mobile' => $user->mobile,
							'email' => $user->email,
							'external_id' => $user->external_id,
							'user_id' => $user->user_id
					) );
			$eup = new ExtendedUserProfile($user, $currentorg);

			$loyalty_details = $user->getLoyaltyDetails();
			$lifetime_purchases = $loyalty_details['lifetime_purchases'];
			$custController = new ApiCustomerController();
			$ps = $custController -> getPointsSummary($user -> user_id);
			$current_points = $ps['cumulative'];
			$loyalty_points = $ps['current'];
			$args = array('validation_code' => $code,'lifetime_points' => $current_points,'lifetime_purchases' => $lifetime_purchases,
					'loyalty_points' => $loyalty_points,'request_to_redeem_points'=>$points_redeemed);
			$sms_template = Util::valueOrDefault($this->config_mgr->getKey(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE));
			if(empty($sms_template))
                            $sms_template = Util::valueOrDefault($currentorg->get(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE), "Dear Customer, thank you for visiting us. your Code is {{validation_code}}");
                        $sms = Util::templateReplace($sms_template, $args);

//			commenting out the hardcoded template #16370
//			$sms = "Dear Customer, thank you for visiting us. your Code is $code";
                        $mobile = $user->mobile == "" ? $eup->getMobile() : $user->mobile;
                        if($communication_channel === 'SMS' || $communication_channel === 'BOTH')
                        {
                            if(!Util::sendSms($mobile, $sms, $currentorg->org_id, MESSAGE_PRIORITY, false, '', true, true, array( 'otp' ),
									$user->user_id, $user->user_id, "VALIDATION"))
                            {
				$ret = false;
                            }
                            else
                            {
                                $is_otp_sent_by_sms = true;
                            }
                        }

                        $email = "Dear Customer, The validation code for your points redemption request is: $code. You will also receive the validation code by SMS on your registered mobile number shortly. <br>
Regards,
$currentorg->name Team
                                ";

                        $subject = "Validation code for points redemption request";
                        $user_email = $user->email == "" ? $eup->getEmail() : $user->email;
                        
                        if($communication_channel === 'EMAIL' || $communication_channel === 'BOTH')
                        {
                            $this->logger->debug("Trying to send validation code by email");
                            if(!Util::sendEmail($user_email, $subject, $email, $currentorg->org_id, '', 0,
                        		array( -1 ), array(), 0, true, $user->user_id,  $user->user_id, 'VALIDATION'))
                            {
                                $ret = false;
                            }
                            else
                            {
                                $is_otp_sent_by_email = true;
                            }
                        }

                        // Check for success
                        if($ret === true)
                        {
                            $response_code = 200;
                            $response_message = 'Operation Successful';
                            $item_status_code = 200;
                            if($communication_channel == 'BOTH')
                                $item_status_message = 'Validation Code Issued by SMS and Email';
                            if($communication_channel == 'EMAIL')
                                $item_status_message = 'Validation Code Issued by Email';
                            if($communication_channel == 'SMS')
                                $item_status_message = 'Validation Code Issued by SMS';
                        }
                        
                        // Check for partial success
                        else if($is_otp_sent_by_email || $is_otp_sent_by_sms)
                        {
                            $ret = true;
                            $response_code = 200;
                            $response_message = 'Operation Successful';
                            $item_status_code = 200;
                            if(!$is_otp_sent_by_email)
                                $item_status_message = "Validation Code Issued by SMS only";
                            if(!$is_otp_sent_by_sms)
                                $item_status_message = "Validation Code Issued by Email only";        
                        }
                        
                        // Check for failure
                        else
                        {
                            $response_code = 500;
                            $response_message = 'Operation Unsuccessful';
                            $item_status_code = ErrorCodes::$points['ERR_OTP_COMMUNICATION_FAILURE'];
                            if($communication_channel == 'BOTH')
                                $item_status_message = str_replace("{{CHANNEL}}", "Email/SMS", ErrorMessage::$points['ERR_OTP_COMMUNICATION_FAILURE']);
                            if($communication_channel == 'EMAIL')
                                $item_status_message = str_replace("{{CHANNEL}}", "Email", ErrorMessage::$points['ERR_OTP_COMMUNICATION_FAILURE']);
                            if($communication_channel == 'SMS')
                                $item_status_message = str_replace("{{CHANNEL}}", "SMS", ErrorMessage::$points['ERR_OTP_COMMUNICATION_FAILURE']);
                        }
                        
                        
			$response = array(
					'status'=>array('success'=>$ret,'code'=>$response_code,'message'=>$response_message),
					'validation_code' => array('code'=>array(
							'user_id' => $user->user_id,
							'mobile' => $mobile,
							'email' => $user->email,
							'external_id' => $user->external_id,
							'points' =>  $points_redeemed,
							'item_status' => array('success'=>$ret,'code'=>$item_status_code,'message'=>$item_status_message)
							)
						)
					);
			if(!$should_return_user_id)
			{
				unset($response['validation_code']['code']['user_id']);
			}
			$gbl_item_status_codes = $response['responses']['validation_code']['item_status']['code'];
			return $response;
		}

	$this->logger->debug("validation code: $code");
	return $code;
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

	/**
	 * Checks if the provided method is supported or not.
	 *
	 * @see BaseResource::checkMethod()
	 */

	protected function checkMethod($method)
	{
		if(in_array(strtolower($method), array( 'redeem', 'validationcode', 'isredeemable', 'revert'))){
			return true;
		}
		return false;
	}
	
	protected function isPointsRedemptionAllowedForUser($user_id)
	{
		
		$this->logger->info("Checking... is points redemption/issue allowed for customer");
		
		//check mobile realloc request pending state
		include_once 'apiModel/class.ApiChangeIdentifierRequestModelExtension.php';
		$request_model=new ApiChangeIdentifierRequestModelExtension();
		$blocked=$request_model->isMobileReallocPendingForOldCustomer($user_id);
		if($blocked)
		{
			$this->logger->info("mobile realloc request pending for user... points stuff not allowed");
			throw new Exception('ERR_POINTS_BLOCKED_CUSTOMER');
		}
		
		$this->logger->info("points stuff is allowed");
		return true;
		
	}

}
