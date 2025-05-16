<?php
//define(LOYALTY_TEMPLATE_ADD, "loyalty_template_add");
//define(LOYALTY_TEMPLATE_REGISTER, "loyalty_template_register");
//define(LOYALTY_TEMPLATE_SENDPOINTS, "loyalty_template_sendpoints");
//define(LOYALTY_TEMPLATE_ADD_DEFAULT, "Thank you, {{name}} for shopping with us. We added {{current_points}} to your balance. Your total loylaty points are {{total_points}}.");
//define(LOYALTY_TEMPLATE_REGISTER_DEFAULT, "{{name}}, Thank You for your interest in our organization. We have registered you on your loyalty program with your mobile number {{mobile_number}}. Look forward to welcome you soon.");
define(LOYALTY_TEMPLATE_SENDPOINTS_DEFAULT, "Your current loyalty points balance is {{total_points}}. Thank you for your interest in our organization.");
define(LOYALTY_REDEMPTION_VOUCHER_SERIES_KEY, 'loyalty_redeem_vch_series_id');
define(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE, 'TEMPLATE_VALIDATION_CODE');
define(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE_DEFAULT, 'Thanks for your interest in redeeming.  Your redemption validation code is {{validation_code}}');

define(MLM_TEMPLATE_REFER_SMS, "TEMPLATE_mlm_refer_sms");
define(MLM_TEMPLATE_REGISTER_SMS, "TEMPLATE_mlm_register_sms"); #welcome sms when a user registers
define(MLM_TEMPLATE_FORWARD_SMS, 'TEMPLATE_mlm_forward_sms'); # SMS to forward to friends
define(MLM_TEMPLATE_REFER_DEFAULT, "Your friend, {{name}}[referral code {{referrer_code}}] has referred you to our exclusive MLM based loyalty program");
define(MLM_TEMPLATE_REGISTER_DEFAULT, "Congratulations, you have been referred by {{parent_name}}. You can forward your referral code {{referral_code}} to your friends");
define(MLM_TEMPLATE_FORWARD_DEFAULT, "Hi, I shopped at {{store_firstname}}. You can use referral code {{referral_code}} to get joinins bonus points - {{fullname}}");
define(MLM_TEMPLATE_STATEMENT_SMS, "TEMPLATE_mlm_statement_sms");
define(MLM_TEMPLATE_STATEMENT_DEFAULT, "Dear {{firstname}}, you are a {{slab_name}} member and have {{loyalty_points}} points. You have {{subtree_size}} friend(s) under you.");
define(MLM_TEMPLATE_SUCCESSFUL_REFERRAL_SMS, 'TEMPLATE_mlm_successful_referral_sms');
define(MLM_TEMPLATE_SUCCESSFUL_REFERRAL_DEFAULT, 'Congratulations, your friend has joined');
define(MLM_TEMPLATE_REFER_EMAIL_BODY, "TEMPLATE_mlm_refer_email_body");
define(MLM_TEMPLATE_REGISTER_EMAIL_BODY, "TEMPLATE_mlm_register_email_body");
define(MLM_TEMPLATE_FORWARD_EMAIL_BODY, 'TEMPLATE_mlm_forward_email_body');
define(MLM_TEMPLATE_STATEMENT_EMAIL_BODY, "TEMPLATE_mlm_statement_email_body");
define(MLM_TEMPLATE_SUCCESSFUL_REFERRAL_EMAIL_BODY, 'TEMPLATE_mlm_successful_referral_email_body');
define(MLM_TEMPLATE_REFER_EMAIL_SUBJ, "TEMPLATE_mlm_refer_email_subj");
define(MLM_TEMPLATE_REGISTER_EMAIL_SUBJ, "TEMPLATE_mlm_register_email_subj");
define(MLM_TEMPLATE_FORWARD_EMAIL_SUBJ, 'TEMPLATE_mlm_forward_email_subj');
define(MLM_TEMPLATE_STATEMENT_EMAIL_SUBJ, "TEMPLATE_mlm_statement_email_subj");
define(MLM_TEMPLATE_SUCCESSFUL_REFERRAL_EMAIL_SUBJ, 'TEMPLATE_mlm_successful_referral_email_subj');

// This is an additional bit added to the points redemption code
// when happening in offline mode.
define(BITS_FOR_OFFLINE_MODE, 1);

define(ERR_LOYALTY_SUCCESS, 0);
define(ERR_LOYALTY_USER_NOT_REGISTERED, -1000);
define(ERR_LOYALTY_DUPLICATE_BILL_NUMBER, -2000);
define(ERR_LOYALTY_INVALID_VOUCHER_CODE, -3000);
define(ERR_LOYALTY_LISTENER, -4000);
define(ERR_LOYALTY_INSUFFICIENT_POINTS, -5000);
define(ERR_LOYALTY_PROFILE_UPDATE_FAILED, -6000);
define(ERR_LOYALTY_REGISTRATION_FAILED, -7000);
define(ERR_LOYALTY_BILL_ADDITION_FAILED, -8000);
define(ERR_LOYALTY_UNKNOWN, -9000);
define(ERR_LOYALTY_REDEMPTION_SERIES_INVALID, -10000);
define(ERR_LOYALTY_INVALID_BILL_NUMBER, -11000);
define(ERR_LOYALTY_INVALID_VALIDATION_CODE, -12000);
define(ERR_LOYALTY_INVALID_BILL_AMOUNT, -13000);
define(ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS, -14000);
define(ERR_LOYALTY_INSUFFICIENT_CURRENT_POINTS, -15000);
define(ERR_LOYALTY_INSUFFICIENT_LIFETIME_POINTS, -16000);
define(ERR_LOYALTY_INSUFFICIENT_LIFETIME_PURCHASE, -17000);
define(ERR_LOYALTY_REDEMPTION_POINTS_NOT_DIVISIBLE, -18000);
define(ERR_LOYALTY_INVALID_MOBILE, -19000);
define(ERR_LOYALTY_COMMUNICATION, -20000);
define(ERR_LOYALTY_INVALID_EXTERNAL_ID, -21000);
define(ERR_LOYALTY_INSUFFICIENT_POINTS_CLMS, -22000);
define(ERR_LOYALTY_UNABLE_TO_FETCH_CLMS_POINTS, -23000);
define(ERR_LOYALTY_FRAUD_USER, -24000);
define(ERR_LOYALTY_BILL_POINTS_USED, -25000);
define(ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL, -26000);
define(ERR_LOYALTY_INVALID_EMAIL, -27000);
define(ERR_LOYALTY_INVALID_CARD_NO, -28000);
define(ERR_LOYALTY_INSUFFICIENT_CARD_CREDIT, -29000);
define(ERR_LOYALTY_INVALID_AMOUNT, -30000);
define(ERR_LOYALTY_INVALID_RETURN_BILL_TIME , -31000);
define(ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_CURRENT_POINTS, -32000);
define(ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_MAX_POINTS, -33000);
define(ERR_LOYALTY_CANNOT_REDEEM_LESS_THAN_MIN_POINTS, -34000);

$GLOBALS["loyalty_error_responses"] = array (
		ERR_LOYALTY_USER_NOT_REGISTERED => 'The User is not registered for the loyalty program',
		ERR_LOYALTY_DUPLICATE_BILL_NUMBER => 'Duplicate Bill number',
		ERR_LOYALTY_INVALID_VOUCHER_CODE => 'Invalid Voucher Code',
		ERR_LOYALTY_LISTENER => 'SMS/Email could not be sent',
		ERR_LOYALTY_UNKNOWN => 'An Unknown error occurred',
		ERR_LOYALTY_INSUFFICIENT_POINTS => 'Insufficient points for performing this operation',
		ERR_LOYALTY_SUCCESS => 'Operation Successful',
		ERR_LOYALTY_PROFILE_UPDATE_FAILED=> 'Updation failed,Please check all the fields',
		ERR_LOYALTY_REGISTRATION_FAILED=> 'Registration failed,Please check all the fields',
		ERR_LOYALTY_REDEMPTION_SERIES_INVALID=> 'Please check with your head office to set a voucher series',
		ERR_LOYALTY_BILL_ADDITION_FAILED=> 'Bill could not be added',
		ERR_LOYALTY_INVALID_BILL_NUMBER=>'Invalid Bill Number',
		ERR_LOYALTY_INVALID_VALIDATION_CODE=>'Invalid Validation Code',
		ERR_LOYALTY_INVALID_BILL_AMOUNT => 'Invalid Bill Amount',
		ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS => 'Redeem points less than minimum points that can be redeemed',
		ERR_LOYALTY_INSUFFICIENT_CURRENT_POINTS => 'Current Loyalty Points less than minimum required',
		ERR_LOYALTY_INSUFFICIENT_LIFETIME_POINTS => 'Lifetime Loyalty Points less than minimum required',
		ERR_LOYALTY_INSUFFICIENT_LIFETIME_PURCHASE => 'Lifetime Purchase less than minimum required',
		ERR_LOYALTY_REDEMPTION_POINTS_NOT_DIVISIBLE => 'Redemption Points not divisible by configuration value',
		ERR_LOYALTY_INVALID_MOBILE => 'Invalid Mobile number',
		ERR_LOYALTY_COMMUNICATION => 'Unable to send information to customer',
		ERR_LOYALTY_INVALID_EXTERNAL_ID => 'Invalid External ID',
		ERR_LOYALTY_INSUFFICIENT_POINTS_CLMS => 'Insufficient Points IN CLMS',
		ERR_LOYALTY_UNABLE_TO_FETCH_CLMS_POINTS => 'Unable to fetch points from CLMS / No Customer with that card no.',
		ERR_LOYALTY_FRAUD_USER => 'Not allowed due to Fraud Check',
		ERR_LOYALTY_BILL_POINTS_USED => 'Not allowed due to Points Used For The Bill',
		ERR_LOYALTY_PERF_COUNTER_UPDATE_FAILED => 'Update of some counters failed',
		ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL => 'Invalid Mobile and Email',
		ERR_LOYALTY_INVALID_CARD_NO => 'Invalid Card no',
		ERR_LOYALTY_INSUFFICIENT_CARD_CREDIT => 'Insufficient credit in card',
		ERR_LOYALTY_INVALID_AMOUNT => 'Invalid amount for recharge/redeem',
		ERR_LOYALTY_INVALID_RETURN_BILL_TIME => 'Invalid Return Bill Time, It should be greater than addbill time',
		ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_CURRENT_POINTS => "Current points are less than points requested for redemption",
		ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_MAX_POINTS => 'Trying to redeem points more than maxinum points that can be redeemed',
		ERR_LOYALTY_CANNOT_REDEEM_LESS_THAN_MIN_POINTS => "Trying to redeem points less than minimum points that can be redeemed"
);

$GLOBALS["loyalty_error_keys"] = array (
		ERR_LOYALTY_USER_NOT_REGISTERED => 'ERR_LOYALTY_USER_NOT_REGISTERED',
		ERR_LOYALTY_DUPLICATE_BILL_NUMBER => 'ERR_LOYALTY_DUPLICATE_BILL_NUMBER',
		ERR_LOYALTY_INVALID_VOUCHER_CODE => 'ERR_LOYALTY_INVALID_VOUCHER_CODE',
		ERR_LOYALTY_LISTENER => 'ERR_LOYALTY_LISTENER',
		ERR_LOYALTY_UNKNOWN => 'ERR_LOYALTY_UNKNOWN',
		ERR_LOYALTY_INSUFFICIENT_POINTS => 'ERR_LOYALTY_INSUFFICIENT_POINTS',
		ERR_LOYALTY_SUCCESS => 'ERR_LOYALTY_SUCCESS',
		ERR_LOYALTY_PROFILE_UPDATE_FAILED => 'ERR_LOYALTY_PROFILE_UPDATE_FAILED',
		ERR_LOYALTY_REGISTRATION_FAILED => 'ERR_LOYALTY_REGISTRATION_FAILED',
		ERR_LOYALTY_REDEMPTION_SERIES_INVALID => 'ERR_LOYALTY_REDEMPTION_SERIES_INVALID',
		ERR_LOYALTY_BILL_ADDITION_FAILED => 'ERR_LOYALTY_BILL_ADDITION_FAILED',
		ERR_LOYALTY_INVALID_BILL_NUMBER => 'ERR_LOYALTY_INVALID_BILL_NUMBER',
		ERR_LOYALTY_INVALID_VALIDATION_CODE => 'ERR_LOYALTY_INVALID_VALIDATION_CODE',
		ERR_LOYALTY_INVALID_BILL_AMOUNT => 'ERR_LOYALTY_INVALID_BILL_AMOUNT',
		ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS => 'ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS',
		ERR_LOYALTY_INSUFFICIENT_CURRENT_POINTS => 'ERR_LOYALTY_INSUFFICIENT_CURRENT_POINTS',
		ERR_LOYALTY_INSUFFICIENT_LIFETIME_POINTS => 'ERR_LOYALTY_INSUFFICIENT_LIFETIME_POINTS',
		ERR_LOYALTY_INSUFFICIENT_LIFETIME_PURCHASE => 'ERR_LOYALTY_INSUFFICIENT_LIFETIME_PURCHASE',
		ERR_LOYALTY_REDEMPTION_POINTS_NOT_DIVISIBLE => 'ERR_LOYALTY_REDEMPTION_POINTS_NOT_DIVISIBLE',
		ERR_LOYALTY_INVALID_MOBILE => 'ERR_LOYALTY_INVALID_MOBILE',
		ERR_LOYALTY_COMMUNICATION => 'ERR_LOYALTY_COMMUNICATION',
		ERR_LOYALTY_INVALID_EXTERNAL_ID => 'ERR_LOYALTY_INVALID_EXTERNAL_ID',
		ERR_LOYALTY_INSUFFICIENT_POINTS_CLMS => 'ERR_LOYALTY_INSUFFICIENT_POINTS_CLMS',
		ERR_LOYALTY_UNABLE_TO_FETCH_CLMS_POINTS => 'ERR_LOYALTY_UNABLE_TO_FETCH_CLMS_POINTS',
		ERR_LOYALTY_FRAUD_USER => 'ERR_LOYALTY_FRAUD_USER',
		ERR_LOYALTY_PERF_COUNTER_UPDATE_FAILED => 'ERR_LOYALTY_PERF_COUNTER_UPDATE_FAILED',
		ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL => 'ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL',
		ERR_LOYALTY_INVALID_CARD_NO => 'ERR_LOYALTY_INVALID_CARD_NO',
		ERR_LOYALTY_INSUFFICIENT_CARD_CREDIT => 'ERR_LOYALTY_INSUFFICIENT_CARD_CREDIT',
		ERR_LOYALTY_INVALID_AMOUNT => 'ERR_LOYALTY_INVALID_AMOUNT',
		ERR_LOYALTY_INVALID_RETURN_BILL_TIME => 'ERR_LOYALTY_INVALID_RETURN_BILL_TIME',
		ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_CURRENT_POINTS => 'ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_CURRENT_POINTS',
		ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_MAX_POINTS => 'ERR_LOYALTY_CANNOT_REDEEM_MORE_THAN_MAX_POINTS',
		ERR_LOYALTY_CANNOT_REDEEM_LESS_THAN_MIN_POINTS => "ERR_LOYALTY_CANNOT_REDEEM_LESS_THAN_MIN_POINTS"
);

/**
 * Stores information about a particular Loyalty Rule
 * - type -> type of the Rule (tagged in Database)
 * - params -> parameters of the rule given as key-value pairs. The effect is given as:
 *   * effect_percentage: Is it given as a percentage of the amount, or as a fixed value
 *   * effect_value: value that is to be used for points calculation (above)
 *   * effect_increment: if true, current calculation is added, else subtracted
 * @author kmehra
 *
 */
require_once "apiHelper/Errors.php";
//TODO: referes to cheetah
include_once "helper/ListenersMgr.php";
include_once "controller/ApiLoyalty.php";
//TODO: referes to cheetah
include_once "model/loyalty.php";
include_once "apiHelper/OTPManager.php";
include_once 'business_controller/points/PointsEngineServiceController.php';
include_once "apiController/ApiCustomerController.php";
include_once "apiController/ApiTransactionController.php";
include_once "apiController/ApiRequestController.php";

/**
 *
 * @author sourav
 *
 */
class LoyaltyModule extends BaseModule {

	var $loyaltyController;
	var $lm;
	private $cm;
	private $mlm;


	public function __construct() {

		parent::__construct();
		$this->lm = new ListenersMgr($this->currentorg);
		$this->mlm = new MLMSubModule($this);
		$this->loyaltyController = new LoyaltyController($this);
	}

	function getResponseErrorMessage($err_code) {
		global $loyalty_error_responses;
		if ($err_code > 0) return "Operation Successful";
		return $loyalty_error_responses[$err_code];
	}

	function getResponseErrorKey($err_code) {
		global $loyalty_error_keys;
		if ($err_code > 0) $err_code = ERR_LOYALTY_SUCCESS;
		return $loyalty_error_keys[$err_code];
	}


	/******************** ACTIONS ************************/

	function optInAction($mobile, $org_id){
		$db = new Dbase('users');
		$res = $db->query_firstrow("SELECT und.id AS ndnc_id, und.user_id FROM users_ndnc_status und
				JOIN masters.organizations o ON ( o.id = und.org_id )
				WHERE und.org_id =$org_id AND und.mobile='$mobile' AND o.optin_active=1");
		if ($res !== NULL)
			$db->insert("INSERT INTO users_optin_status
					(ndnc_status_id, last_updated, user_id, org_id, mobile, added_on) VALUES
					($res[ndnc_id], CURDATE(), $res[user_id], $org_id, '$mobile', NOW())
					ON DUPLICATE KEY UPDATE last_updated = GREATEST(last_updated, VALUES(last_updated)), is_active = 1");
	}

	function optOutAction($mobile, $org_id){
		$db = new Dbase('users');
		$sql = "UPDATE users_optin_status SET is_active = 0 WHERE mobile = '$mobile' AND org_id = $org_id";
		$db->update($sql);
	}



	/**
	 * store sends the phone number with pincode and to get the address details
	 *
	 * command {{org-prefix}}}STRED {{pincode}}
	 *
	 */
	public function getMappedDetailsByPincodeAction( $send_to , $org_id , $message ){

		global $dhiresh_peter_england;
		//parameters from the incomig.php
		$this->logger->info("Start Get MApped Details By Pincode For Customer number $send_to, org_id = $org_id,Pincode = $message");

		$pincode = trim($message);

		$this->logger->info(" Customer mobile : $send_to , Pincode : $pincode ");
			
		$res = ValidationPin::getMappedAddressByPincode( $pincode );
			
		if( count( $res ) > 0 ){

			$msg = $this->currentorg->getConfigurationValue( 'SERVER_PINCODE_ADDRESS' );
			$name = 'Customer';

			foreach( $res as $field ){

				$replace_array =
				array(
						'fullname' =>  ucwords( $name ) ,
						'address' => $field[address] ,
						'phone_number' => $field[phone_number],
						'pincode' => $pincode
				);

				$message_to_send = Util::templateReplace( $msg , $replace_array );

				$this->logger->info("message_to_send : $message_to_send");

				$dhiresh_peter_england .= $message_to_send . '  ';
				Util::sendSms($send_to, $message_to_send, $this->currentorg->org_id, MESSAGE_PRIORITY);
			}
		}else{

			$message_to_send = " No Details Available For this Pincode";
			Util::sendSms($send_to, $message_to_send, $this->currentorg->org_id, MESSAGE_PRIORITY);
		}
	}



	function sendbalanceAction($user_id) {
		$loyalty_id = $this->loyaltyController->getLoyaltyId($this->currentorg->org_id, $user_id);

		if ($loyalty_id == false) {
			$this->flash("User is not registered for loyalty program");
			Util::redirect('loyalty', 'index', '', 'This loyalty user is not registered');
		}
		$org_id = $this->currentorg->org_id;
		$user = UserProfile::getById($user_id);
		$e = new ExtendedUserProfile($user,$this->currentorg);

		$name = $e->getName();
		$balance = $this->loyaltyController->getBalance($loyalty_id);
		$mobile = $e->getMobile();

		if ($this->loyaltyController->sendbalance($name, $mobile, $balance))
			Util::redirect('loyalty', 'index', '', "Points balance for this user is: $balance. Sms has been sent to $mobile");
			
		Util::redirect('loyalty', 'index', '', "Points balance for this user is: $balance. Error sending message to $mobile");
	}


	function getLoyaltyCustomerDetailsApiAction($startdate,$enddate){
		$startdate = str_replace(' ', '+', $startdate);
		$enddate = str_replace(' ', '+', $enddate);
		$startdate = Util::getMysqlDate(Util::deserializeFrom8601($startdate));
		$enddate = Util::getMysqlDate(Util::deserializeFrom8601($enddate));
		$this->data['loyalty_details'] = $this->loyaltyController->getCustomerDetailsByShopDate($startdate,$enddate);
	}

	/**
	 * Redeem points using the Api. While using the api, we don't have to do the folloiwng tests:
	 * 1. Check for voucher code if its valid
	 *
	 * Points check is not done since it is assumed that it will be anyway present (api should check)
	 * @return void - the return string is encoded in the $data
	 */
	function redeemApiAction($xml_string = false) {

		global $currentorg;
		//if($testing == false){
		$xml_string = <<<EOXML
<root>
  <redeem>
    <loyalty_id>2</loyalty_id>
    <points_redeemed>0</points_redeemed>
    <bill_number>ghuu999</bill_number>
    <voucher_code>UN1UQ4</voucher_code>
    <customer_mobile></customer_mobile>
    <customer_email></customer_email>
    <customer_external_id></customer_external_id>
    <notes>Notes</notes>
    <redemption_time>2009-04-13T14:55:25.0000000</redemption_time>
    	<custom_fields_data>
			<custom_data_item>
				<field_name>sport</field_name>
				<field_value>["ckt"]</field_value>
			</custom_data_item>
		</custom_fields_data>
  </redeem>
</root>
EOXML;


		$xml_string = $this->getRawInput();
        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

		$element = simplexml_load_string($xml_string);

		$responses = array();
		$elems = $element->xpath('/root/redeem');

		foreach ($elems as $e)
		{

			$loyalty_id = (string) $e->loyalty_id;
			$customer_mobile = (string) $e->customer_mobile;
			$customer_external_id = (string) $e->customer_external_id;
			$customer_email = (string) $e->customer_email;
			$redeemed_value = 0;

			$user = false;

			if(!$user && (strlen($loyalty_id) > 0))
				$user = UserProfile::getById(
						$this->loyaltyController->getUserIdFromLoyaltyId($loyalty_id));

			if(!$user && (strlen($customer_mobile) > 0))
				$user = UserProfile::getByMobile($customer_mobile);

			if(!$user && (strlen($customer_external_id) > 0))
				$user = UserProfile::getByExternalId($customer_external_id);

			if(!$user && (strlen($customer_email) > 0))
				$user = UserProfile::getByEmail($customer_email);

			$redemption_time = (string)$e->redemption_time;
			if(trim($redemption_time) == '')
			{
				$redemption_time = Util::getCurrentTimeForStore($this->currentuser->user_id);
			}
			$r_time = $redemption_time;

			$points = (int)$e->points_redeemed;
			if($points <= 0)
			{
				$this->logger->debug("Invalid points to be redeemed: $points");
				$ret = ERR_LOYALTY_INSUFFICIENT_REDEMPTION_POINTS;
			}

			if($user){
				//Get the loyalty id for the user id
				$loyalty_id = $this->loyaltyController->getLoyaltyId($this->currentorg->org_id, $user->user_id);

				$ret = $this->loyaltyController->redeemPoints($user, $loyalty_id,
						$e->points_redeemed, $e->bill_number, $e->voucher_code, $e->notes,
						$this->currentuser->user_id, $r_time);
			}
			else
			{
				$ret = ERR_LOYALTY_USER_NOT_REGISTERED;
			}
			//Add the response for the new clients
			$redeem_status = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			//Check if the returned value
			//its an error code if its less than 0 and its the points redemption id otherwise
			$resp = "";
			if($ret > 0 || $ret === true)
			{
				$redemption_id = $ret;

				$resp = "Redeemed Successfully";
				$redeemed_value = (float)$e->points_redeemed;
				//store the custom field information for the points redemption
				$cf_data = array();
				foreach($e->xpath('custom_fields_data/custom_data_item') as $cfd){
					$cf_name = (string) $cfd->field_name;
					$cf_value_json = (string) $cfd->field_value;
					array_push($cf_data, array('field_name' => $cf_name, 'field_value' => $cf_value_json));
				}
				if(count($cf_data) > 0)
				{
					$cf = new CustomFields();
					$assoc_id = $redemption_id;
					$cf->addCustomFieldDataForAssocId($assoc_id, $cf_data);
				}

				//$cm = new ConfigManager($this->currentorg->org_id);
				//$is_enabled = $cm->getKey('CONF_POINTS_ENGINE_ENABLED');
				//$this->logger->debug("pigol : $is_enabled");

				if(Util::canCallPointsEngine())
				{
					try{
						$event_client = new EventManagementThriftClient();
						$org_id = $this->currentorg->org_id;
						$points = (int)$e->points_redeemed;
						$store_id = $this->currentuser->user_id;
						$bill_number = (string)$e->bill_number;

						$timeInMillis = strtotime($r_time);
						if($timeInMillis == -1 || !$timeInMillis )
						{
							throw new Exception("Cannot convert '$r_time' to timestamp", -1, null);
						}
						$timeInMillis = $timeInMillis * 1000;

						
						if(Util::canCallEMF())
						{
							try{
								$emf_controller= new EMFServiceController();

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

								$emf_result = $emf_controller->pointsRedemptionEvent ($org_id, $user->user_id, $points,
										$store_id, $bill_number, $timeInMillis, $commit,
										$loyalty_log_id, $validation_code ="", $e->notes, $reference_id=-1 );

// 								$emf_result = $emf_controller-> pointsRedemptionEvent(
// 										$org_id,
// 										$user->user_id,
// 										$points,
// 										$store_id,
// 										$bill_number,
// 										$timeInMillis,
// 										$commit);
								$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
								$this->lm->issuedVoucherDetails($coupon_ids);
								if($commit && $emf_result !== null )
								{
									//Update the old tables from the EMF view
									$pesC = new PointsEngineServiceController();
									
									$pesC->updateForPointsRedemptionTransaction(
											$org_id, $user->user_id, $loyalty_id, $points,
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
								$loyalty_log_id = $db->query_firstrow("SELECT ll.id as loyalty_log_id FROM loyalty_log as ll
										WHERE ll.org_id = $org_id AND ll.bill_number='$bill_number' and ll.loyalty_id = $loyalty_id
										AND ll.date >= '".  date( 'Y-m-d h:i:s', $timeInMillis - 300 )."' and ll.date <= '".  date( 'Y-m-d h:i:s', $timeInMillis + 300 )."' ");
									
								$loyalty_log_id = $loyalty_log_id ? $loyalty_log_id["loyalty_log_id"] : -1;
							}
							else
								$loyalty_log_id = -1;
								
							$result = $event_client->pointsRedemptionEvent($org_id, $user->user_id, $points,
									$store_id, $bill_number, $timeInMillis, $loyalty_log_id );
								
// 							$result = $event_client-> pointsRedemptionEvent(
// 									$org_id, $user->user_id, $points, $store_id,
// 									$bill_number, $timeInMillis);
// 							$this->logger->debug("result: " . print_r($result, true));
	
							$evaluation_id = $result->evaluationID;
	
							if($result != null && $evaluation_id > 0){
								$event_commit_result = $event_client->commitEvent($result);
								$this->logger->debug("Result of commit: " . print_r($event_commit_result, true));
									
								//Update the old tables from the points engine view
								$pesC = new PointsEngineServiceController();
									
								$pesC->updateForPointsRedemptionTransaction(
										$org_id, $user->user_id, $loyalty_id, $points,
										$timeInMillis, $redemption_id);
							}
						}

					} catch (eventmanager_EventManagerException $ex) {
						//TODO :: create mapping to old error codes
						$this->logger->error("Exception in Points Engine... pull a hair from abhilash's head");
						$this->logger->error("Error code: " . $ex->statusCode . " Message: " . $ex->errorMessage );

						$ret = Util::convertPointsEngineErrorCode($ex->statusCode);
						if(Util::isPointsEngineActive()) {
							$ret = Util::convertPointsEngineErrorCode($ex->statusCode);
							$redeem_status = array(
									'key' => $this->getResponseErrorKey($ret),
									'message' => $this->getResponseErrorMessage($ret)
							);
							$resp = $this->getResponseErrorMessage($ret);
							//$this->deleteRedemptionRecord($redemption_id);
						}

					} catch(Exception $ex){
						$this->logger->error("Error in signalling event for points redemption");
						$this->logger->error("Error Code: " . $ex->getCode() . " Error Message: " . $ex->getMessage());

						if(Util::isPointsEngineActive()) {
								
							$ret = ERR_LOYALTY_UNKNOWN;
								
							$redeem_status = array(
									'key' => $this->getResponseErrorKey($ret),
									'message' => $this->getResponseErrorMessage($ret)
							);
							$resp = $this->getResponseErrorMessage($ret);
							//$this->deleteRedemptionRecord($redemption_id);
						}

					}
				}
			}else
			{
				$resp = $this->getResponseErrorMessage($ret);
			}
				
			//TODO: Get it from points engine later on
			$points_to_currency_ratio = 1;
			if(Util::isPointsEngineActive())
			{
				$pesC = new PointsEngineServiceController(); 
				$temp_points_to_currency_ratio = $pesC->getPointsCurrencyRatio($this->currentorg->org_id);
				if($temp_points_to_currency_ratio > 0)
					$points_to_currency_ratio = $temp_points_to_currency_ratio;
			}
			$points_redeemed_value =
			round($redeemed_value * $points_to_currency_ratio, 2);

			$response = array(
					'bill_number' => (string) $e->bill_number,
					'response' => $resp,
					'response_code' => $ret,
					'redeemed_value' => $redeemed_value,
					'points_redeemed_currency_value' => $points_redeemed_value,
					'points_currency_ratio' => $points_to_currency_ratio,
					'item_status' => $redeem_status
			);
			array_push($responses, $response);
			$this->logger->debug("Redemption Response : ".print_r($response, true));
		}
		$this->data['responses'] = $responses;
	}

	function redeempointsofflineApiAction() {

		global $currentorg;
		$xml_string = <<<EOXML
<root>
  <redeem_offline>
    <loyalty_id>405672</loyalty_id>
    <points_redeemed>50</points_redeemed>
    <bill_number>my_test_bill_1</bill_number>
  </redeem_offline>
</root>
EOXML;


		$xml_string = $this->getRawInput();
        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

		$element = Xml::parse($xml_string)	;

		$responses = array();
		$elems = $element->xpath('/root/redeem_offline');
		foreach ($elems as $e) {

			$loyalty_id = (integer) $e->loyalty_id;
			$points_to_be_redeemed = (double) $e->points_redeemed;
			$bill_number = (string) $e->bill_number;
			$validation_code = ''; //not being sent currently

			$ray_points = 'NA';

			$user = UserProfile::getById($this->loyaltyController->getUserIdFromLoyaltyId((integer) $e->loyalty_id));

			$loyalty_id=$user->getLoyaltyId();
			
			if(!$user)
				$ret = ERR_LOYALTY_USER_NOT_REGISTERED;
			else
				$ret = $this->loyaltyController->isPointsRedeemable($user, $loyalty_id, $points_to_be_redeemed, $bill_number, $validation_code, $this->currentorg->getConfigurationValue(CONF_LOYALTY_IS_REDEMPTION_VALIDATION_REQUIRED, false));

			$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForLoyaltyID($loyalty_id);

			if($ret == ERR_LOYALTY_SUCCESS){  //Loyalty points redeemable in intouch

				//TODO Generalize to work for any organization

				//For raymond, check if the customer if customer has a external id / card
				//if(strlen($loyalty_details['external_id']) == 0)
				//	$ret = ERR_LOYALTY_INVALID_EXTERNAL_ID;

				if($ret == ERR_LOYALTY_SUCCESS){
					//For raymond, check if the customer is in clms
					/*$cl_mgr = new ClustersMgr();
					if(!$cl_mgr->isInRaymondCLMS($user->user_id))
						$ret = ERR_LOYALTY_USER_NOT_REGISTERED;
					*/
				}

				//if(strlen($loyalty_details['external_id']) > 0){
				//For raymond, check if CLMS allows redemption
				$ray_points = $points_to_be_redeemed;//$rh->getCustomerPointsInCLMSByExternalId($loyalty_details['external_id']);
				if(!$ray_points){
					$ret = ERR_LOYALTY_UNABLE_TO_FETCH_CLMS_POINTS;
					$ray_points = 'NA';
				}else{

					if($ray_points >= $points_to_be_redeemed){

						//Redeem the points
						$ret = $this->loyaltyController->redeemPoints($user, $loyalty_id, $points_to_be_redeemed, $bill_number, '', 'Raymond Offline Points Redeem', $this->currentuser->user_id);
						if($ret > 0)
						{
							$redemption_id = $ret;

							$ret = ERR_LOYALTY_SUCCESS;

							$this->logger->debug("Calling points engine for offline points redemption");

							if(Util::canCallPointsEngine())
							{
								try{
									$event_client = new EventManagementThriftClient();
									$org_id = $this->currentorg->org_id;
									$points = $points_to_be_redeemed;
									$store_id = $this->currentuser->user_id;
									$bill_number = $bill_number;

									//$r_time = ($e->redemption_time);
									//$r_time = Util::getMysqlDateTime($r_time);

									$timeInMillis = time();
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
												
											$emf_result = $emf_controller->pointsRedemptionEvent ($org_id, $user->user_id, $points,
													$store_id, $bill_number, $timeInMillis, $commit,
													$loyalty_log_id, $validation_code ="", "Raymond Offline Points Redeem", $reference_id=-1 );

// 											$emf_result = $emf_controller-> pointsRedemptionEvent(
// 													$org_id,
// 													$user->user_id,
// 													$points,
// 													$store_id,
// 													$bill_number,
// 													$timeInMillis,
// 													$commit);
											$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
											$this->lm->issuedVoucherDetails($coupon_ids);
											
											if($commit && $emf_result !== null )
											{
												$pesC = new PointsEngineServiceController();
												
												$pesC->updateForPointsRedemptionTransaction(
														$org_id, $user->user_id, $loyalty_id, $points_to_be_redeemed,
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
											$loyalty_log_id = $db->query_firstrow("SELECT ll.id as loyalty_log_id FROM loyalty_log as ll
													WHERE ll.org_id = $org_id AND ll.bill_number='$bill_number' and ll.loyalty_id = $loyalty_id
													AND ll.date >= '".  date( 'Y-m-d h:i:s', $timeInMillis - 300 )."' and ll.date <= '".  date( 'Y-m-d h:i:s', $timeInMillis + 300 )."' ");

											$loyalty_log_id = $loyalty_log_id ? $loyalty_log_id["loyalty_log_id"] : -1;
										}
										else
											$loyalty_log_id = -1;

										$result = $event_client->pointsRedemptionEvent($org_id, $user->user_id, $points,
												$store_id, $bill_number, $timeInMillis, $loyalty_log_id );

// 										$result = $event_client-> pointsRedemptionEvent(
// 												$org_id, $user->user_id, $points, $store_id,
// 												$bill_number, $timeInMillis);
										$this->logger->debug("result: " . print_r($result, true));
	
										$evaluation_id = $result->evaluationID;
	
										if($result != null && $evaluation_id > 0){
											$event_commit_result = $event_client->commitEvent($result);
											$this->logger->debug("Result of commit: " . print_r($event_commit_result, true));
	
											//Update the old tables from the points engine view
											$pesC = new PointsEngineServiceController();
	
											$pesC->updateForPointsRedemptionTransaction(
													$org_id, $user->user_id, $loyalty_id, $points_to_be_redeemed,
													$timeInMillis, $redemption_id);
										}
									}

								} catch (EventManagerException $ex) {
									//TODO :: create mapping to old error codes
									$this->logger->error("Exception in Points Engine... pull a hair from abhilash's head");
									$this->logger->error("Error code: " . $ex->getCode() . " Message: " . $ex->getMessage());

									$ret = Util::convertPointsEngineErrorCode($ex->getCode());

									// the table is not more in use
									//$this->deleteRedemptionRecord($redemption_id);


								} catch(Exception $ex){
									$this->logger->error("Error in signalling event for points redemption");
									$this->logger->error("Error Code: " . $ex->getCode() . " Error Message: " . $ex->getMessage());
									// the table is not more in use
									//$this->deleteRedemptionRecord($redemption_id);
								}
							}
						}

					}else{
						$ret = ERR_LOYALTY_INSUFFICIENT_POINTS_CLMS;
					}

				}
				//}
			}

			//Add the response for the new clients
			$redeem_status = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			//Add the request
			//$extra = "Points In Raymond CLMS (Before Redemption) : $ray_points";
			//$this->loyaltyController->addOfflinePointsRedeemRequest($user, $loyalty_id, $points_to_be_redeemed, $bill_number, $this->getResponseErrorMessage($ret), $loyalty_details, ($ret == ERR_LOYALTY_SUCCESS), $extra);

			$response = array('loyalty_id' => $loyalty_id, 'points_redeemed' => $points_to_be_redeemed, 'bill_number' => $bill_number, 'item_status' => $redeem_status);
			array_push($responses, $response);
			$this->logger->debug("Offline Redemption Response for (Loyalty Id : $loyalty_id, Bill Number : $bill_number, Points to be redeemed : $points_to_be_redeemed) : ".print_r($response, true));
		}

		$this->data['responses'] = $responses;
	}


	function redeempointsofflinerequestsAction(){

		$pending_table = new Table();
		$pending_table = $this->loyaltyController->getRedeemPointsOfflineRequestsLog(0, 'query_table');
		$pending_table->createLink('Change Status', Util::genUrl('loyalty', 'redeempointsofflinerequestlogstatuschange/{0}/{1}/1'), 'mark_done', array( 0 => 'user_id', 1 => 'request_id'));
		$pending_table->createLink('Redeem', Util::genUrl('loyalty', 'redeem/{0}/{1}/{2}/{3}'), 'redeem points', array(0 => 'user_id', 1=>'points_to_be_redeemed', 2=>'requested_by', 3=>'bill_number'));
		$pending_table->removeHeader('user_id');
		$pending_table->removeHeader('request_id');
		$pending_table->removeHeader('requested_by');
		$this->data['pending_table'] = $pending_table;


		$done_table = new Table();
		$done_table = $this->loyaltyController->getRedeemPointsOfflineRequestsLog(1, 'query_table');
		$done_table->createLink('Change Status', Util::genUrl('loyalty', 'redeempointsofflinerequestlogstatuschange/{0}/{1}/0'), 'mark_pending', array( 0 => 'user_id', 1 => 'request_id'));
		$done_table->removeHeader('request_id');
		$done_table->removeHeader('user_id');
		$done_table->removeHeader('requested_by');
		$this->data['done_table'] = $done_table;
	}





	/**
	 * {{org-command}} {{OPTIONS}}
	 *
	 */
	function responseHandlingIncomingMessageAction($send_to,$org_id){

		global $incoming_sms_command;

		$this->logger->info("customer_number $send_to, org_id = $org_id");

		$mobile = $send_to;

		if (!$mobile || !Util::checkMobileNumber($mobile)){

			$message_to_send = "NOT A VALID MOBILE NUMBER";
			$this->logger->info("message $message_to_send");
			return ;
		}

		//Check if customer with mobile exists
		$user = UserProfile::getByMobile($mobile);

		if (!$user) {

			//register the mobile number into our system
			$auth = Auth::getInstance();
			$auth->registerAutomaticallyByMobile($this->currentorg, $mobile, 'Customer');
			$user = UserProfile::getByMobile($mobile);

			if(!$user)
			{
				$message_to_send = "Unable to register by mobile";
				$this->logger->info("message $message_to_send");
				return ;
			}
		}

		//Signal SMS EVENT with user id
		$lm = new ListenersMgr($this->currentorg);

		$params['user_id'] = $user->user_id;
		$params['sms_code'] = $incoming_sms_command;
		$ret = $lm->signalListeners(EVENT_INCOMING_SMS, $params);


		/*$this->logger->info("customer mobile $mobile ");
		 $loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);

		if (!$loyalty_details){

		$message_to_send = "Dear Customer You Are Not Registered In Our Loyalty Program ";
		$message_to_send = $this->getConfiguration(DEFAULT_SMS_TEMPLATE_FOR_SMS_RESPONSE ,$message_to_send);
		$this->logger->info("message_to_send : $message_to_send");

		$ret = Util::sendSms($send_to, $message_to_send, $org_id, MESSAGE_PRIORITY);
		}else{

		$user_id = $loyalty_details['user_id'];

		$lm = new ListenersMgr($this->currentorg);

		$params['user_id'] = $user_id;
		$params['sms_code'] = $incoming_sms_command;
		$ret = $lm->signalListeners(EVENT_INCOMING_SMS, $params);
		}*/
	}

	function responseHandlingIncomingMissedCallAction( $send_to, $org_id ){
	
		$this->logger->info("customer_number $send_to, org_id = $org_id");
	
		$mobile = $send_to;
	
		if (!$mobile || !Util::checkMobileNumber($mobile)){
	
			$message_to_send = "NOT A VALID MOBILE NUMBER";
			$this->logger->info("message $message_to_send");
			return ;
		}
	
		//Check if customer with mobile exists
		$user = UserProfile::getByMobile($mobile);
	
		if (!$user) {
	
			//register the mobile number into our system
			$auth = Auth::getInstance();
			$auth->registerAutomaticallyByMobile($this->currentorg, $mobile, 'Customer');
			$user = UserProfile::getByMobile($mobile);
	
			if(!$user)
			{
				$message_to_send = "Unable to register by mobile";
				$this->logger->info("message $message_to_send");
				return ;
			}
		}
	
		//Signal MISSED CALL EVENT with user id
		$lm = new ListenersMgr($this->currentorg);
	
		$params['user_id'] = $user->user_id;
		$ret = $lm->signalListeners( EVENT_INCOMING_MISSED_CALL, $params );
	}
	/**
	 * {{OPTIONS}} : ('ALL','NOBULK','NOPERSONALIZED','NONE','')
	 * {{org-prefix}}UNSUB {{OPTIONS}}
	 *
	 */
	function unsubscribeByMobileAction($send_to,$org_id,$dnd_option = ''){
		return; 
// 		global $logger;
// 		$nsadmin_db = new Dbase('nsadmin');

// 		if($dnd_option == '')
// 			$dnd_option = 'ALL';

// 		$logger->info('option given :'.$dnd_option);
// 		switch($dnd_option){

// 			case 'ALL':
// 				$status = true;break;
// 			case 'NOBULK':
// 				$status = true;break;
// 			case 'NOPERSONALIZED':
// 				$status = true;break;
// 			case 'NONE':
// 				$status = true;break;
// 			default :
// 				$status = false;
// 		}

// 		if($status){
// 			$user = UserProfile::getByMobile($send_to);
// 			$loyalty_id = $this->loyaltyController->getLoyaltyId($this->currentorg->org_id, $user->user_id);
// 			if(!$user)
// 				$message_to_send = " You Are Not Registered With Us ";
// 			else if(!$loyalty_id && ($dnd_option == 'ALL' || $dnd_option == 'NOPERSONALIZED'))
// 				$message_to_send = " You Are Not Registered On Our Loyalty Prgram ";
// 			else{
// 				$mobile_dnd_option = $email_dnd_option = $dnd_option;

// 				$res = $this->loyaltyController->setDNDForUser($user->user_id, $email_dnd_option, $mobile_dnd_option);

// 				if($res)
// 					$message_to_send = "Your Request Has Been Approved ";
// 				else
// 					$message_to_send = "Sorry We Are Unable To Process Your Request. Please Try Again Later";
// 			}
// 		}else
// 			$message_to_send = "INVALID OPTION PROVIDED. PLEASE PROVIDE WITH VALID OPTION.";

// 		$logger->info('msg to send : '.$message_to_send );

		$user_id = -1;
		if(is_object($user))
		{
			$user_id = $user->user_id;
		}
		Util::sendSms( $send_to, $message_to_send, $this->currentorg->org_id, 0,
					false, '', false, false, array(), 
					$user_id, $user_id, 'GENERAL' );
	}

	/**
	 * sends back the points info for the user
	 * command for end user : {{org-prefix}}POINTS
	 * command for store : {{org-prefix}}POINTS {{mobile}}
	 *
	 * if points is zero it sends bacl life time purchases
	 */
	function getPointsInfoByMobileAction( $mobile, $org_id, $arguments ){
		//http://localhost/incoming.php?msisdn=9972317657&to=56677&whoami=PlanetFashion&msg=PF
		//parameters from the incomig.php
		$this->logger->info(" org_id = $org_id, mobile = $mobile, arguments = ".print_r( $arguments , true ));
		$status = true;
		
		//Check for mobile number validity
		if ($mobile != false && !Util::checkMobileNumber($mobile)){
			
			$message_to_send = "NOT A VALID MOBILE NUMBER";
			$this->logger->debug("Invalid Mobile Number : $mobile");
			$status = false;
		}
		
		//get User Profile By Mobile number
		$this->logger->info("customer mobile $mobile ");
		$user = UserProfile::getByMobile( $mobile );
		if( !$user ){
			
			$this->logger->debug( "User not found for mobile number: $mobile" );
			$status = false;
		}
		else{
			
			$user_id = $user->user_id;
			$this->logger->debug( "User found with mobile number: $mobile" );
			$this->logger->debug( "User id: $user_id");
		}
		
		if($status){
			try{
				//Check if can call points engine for getting customer points
				if( util::canCallPointsEngine() ){
					
					//fetch customer points summary via points engine
					$this->logger->debug( "Points Engine Active, Fetching customer points" );
					$pesC = new PointsEngineServiceController();
					$customer_points_summary = $pesC->getPointsSummaryForCustomer( $org_id, $user_id );
					
					if( $customer_points_summary ){	
						
						$this->logger->debug( "Successfully fetched Customer Points Summary" );
						$this->logger->debug( "Points Summary For customer: ".$user_id."  ".print_r( $customer_points_summary, true  ) );
						$points = floor( $customer_points_summary->currentPoints );
						$message_to_send = " Dear Customer Your Loyalty Points ";
						$message_to_send .= ( $points > 1 )? "are $points. " : "is $points. "; 
					}
				}else{
					//If points engine inactive then fetch points from database 
					$this->logger->debug( "Points Engine Inactive, Fetching customer points from Loyalty details" );
					$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);
					if (!$loyalty_details){
						
						$message_to_send = "Dear Customer You Are Not Registered In Our Loyalty Program ";
						$this->logger->debug( "User not registered for Loyalty Program, user_id: $user_id" );
					}else{
						
						$points = $loyalty_details['loyalty_points'];
						$this->logger->debug( "Customer Loyalty Points: $points" );
						$message_to_send = " Dear Customer Your Loyalty Points ";
						$message_to_send .= ( $points > 1 )? "are $points. " : "is $points. ";
					}
				}
			}
			catch( Exception $e ){
				//if unable to fetch from points engine, then get points info from user profile
				$this->logger->debug( " Error in fetching customer points via Points Engine" );
				$this->logger->debug( "Caught Exception : $e" );
				$this->logger->debug( "Fetching points from Loyalty details" );
				
				$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);
				if (!$loyalty_details){
						
						$message_to_send = "Dear Customer You Are Not Registered In Our Loyalty Program ";
						$this->logger->debug( "User not registered for Loyalty Program, user_id: $user_id" );
					}else{
						
						$points = $loyalty_details['loyalty_points'];
						$this->logger->debug( "Customer Loyalty Points: $points" );
						$message_to_send = " Dear Customer Your Loyalty Points ";
						$message_to_send .= ( $points > 1 )? "are $points. " : "is $points. ";
					}
			}
			
			$this->logger->info("message_to_send : $message_to_send");
			$ret = Util::sendSms($mobile, "$message_to_send", $org_id, MESSAGE_PRIORITY,
					false, '', false, false, array(),
					$user_id, $user_id, 'POINTS' );
		}
	}
	
	/**
	 * Issues validation code to the end cutomer for redemption
	 *
	 * command : {{org-prefix}}RED {{poitns to be redeemed}}
	 */
	function issueValidationCodeByMobileAction($send_to,$org_id,$message){

		//parameters from the incomig.php
		$this->logger->info("store_number $send_to, org_id = $org_id,message = $message");

		//get user details , mobile ,firstname and lastname
		$amount = $message;
		$mobile = $send_to;
		$this->logger->info("customer mobile : $send_to, amount : $amount");

		$user = UserProfile::getByMobile($mobile);
		$user_id = $user->user_id;
		$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);
		if($loyalty_details){
			$current_points = $loyalty_details['loyalty_points'];
			$v = new ValidationCode();
			$code = $v->issueValidationCode($this->currentorg, $mobile,null, VC_PURPOSE_REDEMPTION, false, $user->user_id);
			$args = array('validation_code' => $code,'total_points' => $current_points);
			$sms_template = Util::valueOrDefault($this->currentorg->get(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE), LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE_DEFAULT);
			$sms = Util::templateReplace($sms_template, $args);
		}else
			$sms = "You Are Not Registered To Our Loyalty Program";
		$this->logger->info("message_to_send : $sms");
		Util::sendSms($mobile, $sms, $this->currentorg->org_id, MESSAGE_PRIORITY, 
						false, '', false, false, array( 'otp' ), 
						$user_id, $user_id, 'VALIDATION' );

	}
	/**
	 * store sends the validation code with points and bill number to redeem the
	 * points
	 *
	 * command {{org-prefix}}}STRED {{customer_mobile}} {{points}}
	 * {{validation_code}} {{bill_number}}
	 *
	 */
	function redeemByMobileAction($send_to,$org_id,$message){

		//parameters from the incomig.php
		$this->logger->info("store_number $send_to, org_id = $org_id,message = $message");

		//get user details , mobile ,firstname and lastname
		$msg_details = StringUtils::strexplode(' ',$message);
		$mobile = $msg_details[0];
		$points = $msg_details[1];
		$validation_code = $msg_details[2];
		$billnumber = $msg_details[3];
		if ($mobile != false && !Util::checkMobileNumber($mobile)){
			$message_to_send = "NOT A VALID MOBILE NUMBER";
		}
		else{
			$this->logger->info("customer mobile : $amount, amount : $amount validation code : $validation_code billnumber = $billnumber ");
			$autorization_check = UserProfile::getByMobile($send_to);
			if(!$autorization_check || $autorization_check->tag != 'org')
				$message_to_send = "NOT AUTHORIZED";
			else{
				$user = UserProfile::getByMobile($mobile);
				$user_id = $user->user_id;

				$v = new ValidationCode();
				$validate_status = $v->checkValidationCode($validation_code, $this->currentorg,$mobile, '', VC_PURPOSE_REDEMPTION, false, $user->user_id);
				$status = $validate_status ? "true" : "false";
				if($status){

					$loyalty_id = $this->loyaltyController->getLoyaltyId($this->currentorg->org_id, $user->user_id);
					$ret = $this->loyaltyController->redeemPoints($user, $loyalty_id, $points, $billnumber, $validation_code, 'validate through mobile', $authorized_user->user_id, time());

					$message_to_send = $ret > 0 ? "$points POints Redeemed Successfully" : $this->getResponseErrorMessage($ret);
				}
			}
		}
		$this->logger->info("message_to_send : $message_to_send");
		$user_id = -1;
		if(is_object($user))
		{
			$user_id = $user->user_id;
		}
		Util::sendSms($send_to, $message_to_send, $this->currentorg->org_id, MESSAGE_PRIORITY,
						false, '', false, false, array(),
						$user_id, $user_id, 'POINTS' );

	}




	private function transformUserData($xml_string)
	{
		$element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/customer');
		$users = array();
		foreach($elems as $key => $val)
		{
			$data = array();
			$data['firstname'] = (string)$val->firstname;
			$data['lastname'] = (string)$val->lastname;
			$data['mobile'] = (string)$val->mobile;
			$data['email'] = (string)$val->email;
			$data['external_id'] = (string)$val->external_id;
			$data['registered_on'] = (string)$val->joined_date;
			
			$data['address'] =(string)$val->address ;
			$data['birthday'] = (string)$val->birthday ;
			$data['sex'] = (string)$val->sex ;
			$data['anniversary'] = (string)$val->anniversary;
			$data['spouse_birthday'] = (string)$val->spouse_birthday  ;
			$data['age_group'] = (string)$val->age_group;
			
			$data['current_points'] = (integer)$val->current_points;
			$data['current_points_override'] = (string) $val->current_points->override;
			
			
			$this->logger->debug("Extracting Custom Field Data");
			$cf = $val->xpath("custom_fields_data/custom_data_item");
			$data['custom_fields']['field'] = array();
			//$this->logger->debug("CustomFields XML: ".print_r($cf, true));
			
			foreach($cf as $k=>$cfd)
			{
				$this->logger->debug("CustomFields: $k [".$cfd->field_name." => ".$cfd->field_value."]");
				$field = array();
				$field['name'] = (string)($cfd->field_name);
				$field['value'] = (string)($cfd->field_value);
				array_push($data['custom_fields']['field'], $field);
			}

			$associate_details = $val->xpath("associate_details");
			if(is_array($associate_details) && is_object($associate_details[0]))
			{
				$data['associate_details']['code'] = (string) $associate_details[0]->code;
				$data['associate_details']['name'] = (string) $associate_details[0]->name;
			}
			array_push($users,$data);
				
		}
		return $users;
	}
	/**
	 * Add or update action for the Api. If the user doesn't exist, it automatically updates it
	 * @return unknown_type
	 */
	function registerApiAction(){
		$org_id = $this->currentorg->org_id;
		$xml_string = <<<EOXML
<root>
	<customer>
		<mobile>917672386623</mobile>
		<firstname>Krishna</firstname>
		<lastname>Mehra</lastname>
		<email></email>
		<address>1, Thakurdas Chakraborty Lane, Near Girish Park Metro Station, Kolkata - 700006</address>
		<birthday>20-01-1984</birthday>
		<anniversary>01-01-2015</anniversary>
		<sex>M</sex>
		<spouse_birthday>01-01-2015</spouse_birthday>
		<external_id>1323qw</external_id>
		<validation_code></validation_code>
		<current_points override="false">230</current_points>
		<referred_by>4</referred_by>
		<joined_date>2009-04-06T09:52Z</joined_date>
		<age_group>18-24</age_group>
		<custom_fields_data>
			<custom_data_item>
				<field_name>sport</field_name>
				<field_value>["ckt"]</field_value>
			</custom_data_item>
		</custom_fields_data>
		<associate_details>
	       <code></code>
	       <name></name>
		</associate_details>
	</customer>
</root>
EOXML;

		$xml_string = $this->getRawInput();
		global $logger;
		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		$this->logger->debug("Input XML from Client: $xml_string");
		$elems = $this->transformUserData($xml_string);
		$logger->debug("RegisterApiAction: elems = ".print_r($elems,true));
		$responses = array();

		$reg_count = 0;
		$err_count = 0;

		$mobile_reg_time = 0;
		$external_reg_time = 0;
		$lp_reg_time = 0;
		$ef_time = 0;

		global $gbl_item_count, $gbl_item_status_codes;
		$gbl_item_count = count($elems);

		$arr_item_status_codes = array();
		 
		$auth = Auth::getInstance();
		$logger->debug("RegisterApiAction: Entering forloop");
		foreach ($elems as $customer) {

			++$reg_count;
			$error_key = ERR_LOYALTY_SUCCESS;
			$new_registration = false;
			try
			{
				$customer_controller = new ApiCustomerController();
				$customer = $customer_controller->validateInputIdentifiers($customer);
				/* Checks, if The User is already registered */
				$is_registered = false;
				try{
					if(!empty($customer['mobile']))
					{
						$user = UserProfile::getByMobile($customer['mobile']);
						$this->logger->debug("Mobile number after UserProfile::getByMobile() ".$customer['mobile']);
						$loyalty_id = -1;
						if($user )
							$loyalty_id = $user->getLoyaltyId();
						//checking if user is registered in loyalty program or not.
						if($user && $user->user_id > 0 && $loyalty_id > 0)
							$is_registered = true;
					}
					
					if(!$is_registered && !empty($customer['external_id']))
					{
						$user = UserProfile::getByExternalId($customer['external_id']);
						if($user && $user->user_id > 0)
							$is_registered = true;
					}

				}catch(Exception $e){
					$is_registered = false;
				}

				if(!empty($customer['external_id']))
				{
					//support client configs
					$cfm=new ConfigManager();
					$min_length=$cfm->getKey('CONF_CLIENT_EXTERNAL_ID_MIN_LENGTH');
					$max_length=$cfm->getKey('CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH');
					if(strlen($customer['external_id'])<$min_length || ($max_length!=0 && strlen($customer['external_id'])>$max_length))
					{
						$this->logger->error("external id not matching client config lengths");
						throw new Exception("ERR_LOYALTY_INVALID_EXTERNAL_ID");
					}
				}
					
				
				if($is_registered)
				{
					$this->logger->debug("Customer is Already Registered, going for Updation");
					//if EMAIL UNIQUE is set then check weather the user is already registered by email.
					//if user is already registered, then throw the exception.
					$dm = new ConfigManager();
					if($dm->getKey("CONF_USERS_IS_EMAIL_UNIQUE"))
					{
						$temp_user = UserProfile::getByEmail($customer['email']);
						if(!empty($customer['email']) && $temp_user && $temp_user->user_id != $user->user_id)
						{
							
							throw new Exception("ERR_DUPLICATE_EMAIL");
						}
					}
					$user = $customer_controller->updateCustomer($customer,$user);
				}
				else
				{
					$this->logger->debug("Customer is not Registered, going for new Registration");
					$user = $customer_controller->register($customer);
				}
			}catch(Exception $e){
				$err_count++;
				$error_key = $e->getMessage();
				$register_status = array(
						//'key' => ErrorCodes::$customer[$error_key],
						'key' => $error_key,
						'message' => ErrorMessage::$customer[$error_key]
				);

				array_push($responses, array('user_id' => -1, 'loyalty_id' => -1,
						'slab_name' => false, 'slab_number' => false, 'loyalty_points' => false,
						'lifetime_points' => false, 'lifetime_purchases' => false,
						'mobile' => (string) $e->mobile,
						'registered_date' => date('Y-m-d H:i:s') ,'last_visit' => date('Y-m-d H:i:s'),
						'response_code' =>  ErrorCodes::$customer[$error_key],
						'response_string' => ErrorMessage::$customer[$error_key],
						'item_status' => $register_status)
				);
				$arr_item_status_codes[] = ErrorCodes::$customer[$error_key];
				continue;
			}
				

				

			//Add the response for the new clients
			$error_key = $user->getStatusCode();
			$key = ($error_key > 0) ? ERR_LOYALTY_SUCCESS : $error_key;
			$register_status = array(
					//'key' => ErrorCodes::$customer[$error_key],
						'key' => $error_key,
					'message' => ErrorMessage::$customer[$error_key]
			);

			$arr_item_status_codes[] = ErrorCodes::$customer[$error_key];
			array_push($responses, array('user_id' => $user->user_id, 'loyalty_id' => $user->loyalty_id,
					'slab_name' => $user->slab_name, 'slab_number' => $user->slab_number,
					'loyalty_points' => $user->loyalty_points,'lifetime_points' => $user->lifetime_points,
					'lifetime_purchases' => $user->lifetime_purchases,'mobile' => (string) $user->mobile, 'mlm_code' => $code,
					'registered_date' => $user->registered_on,'last_visit' => $user->updated_on,
					'response_code' => ErrorCodes::$customer[$error_key], 'response_string' => ErrorMessage::$customer[$error_key],
					'item_status' => $register_status));
		}		
		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		global $time_breakup;
		//reg count, mobile reg time, external id reg time, loyalty reg time, event framework time
		$time_breakup = "rc:$reg_count,mrt:$mobile_reg_time,ert:$external_reg_time,lpt:$lp_reg_time,eft:$ef_time";

		/*if($reg_count == $err_count)
		{
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_FAILURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_FAILURE)
			);
		}
		else if($err_count == 0)
		{
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_SUCCESS),
					'message' => getResponseErrorMessage(ERR_RESPONSE_SUCCESS)
			);
		}
		else if($err_count < $reg_count )
		{
			$api_status = array(
					'key' => 'PARTIAL_SUCCESS',
					'message' => ErrorMessage::$api['PARTIAL_SUCCESS']
			);
		}
		$this->data['api_status'] = $api_status;*/
		
		$this->data['responses'] = $responses;
	}


	/************* ADD POINTS ******************/

	/**
	 *
	 *
	 * Adds The points to the loyalty_log.
	 * It first checks if the bill number is already present, if yes, then it updates the fields in the record,
	 * and finds the difference we have to do in the main loyalty_points DB.
	 *
	 * Secondly it adds the transaction, and signals the listeners
	 *
	 * TODO: Doesn't update the custom fields. Has to be updated separately.
	 *
	 * @param UserProfile $user
	 * @param unknown_type $loyalty_id
	 * @param unknown_type $points
	 * @param unknown_type $bill_amount
	 * @param unknown_type $notes
	 * @param unknown_type $bill_number
	 * @param unknown_type $bill_gross_amount
	 * @param unknown_type $bill_discount
	 * @param unknown_type $entered_by
	 * @param unknown_type $ignore_points
	 * @param unknown_type $datetime
	 * @param unknown_type $ignore_max_bill_amount
	 * @param unknown_type $disable_listeners
	 * @param unknown_type $cancel_bill
	 * @param unknown_type $payment_details
	 * @param unknown_type $custom_field_data
	* @param unknown_type $cashier_details
	* @return string|loyalty
	*/
	function addPoints(
			UserProfile $user, $loyalty_id, $points, $bill_amount,
			$notes, $bill_number, $bill_gross_amount, $bill_discount,
			$entered_by, $ignore_points, $datetime = '',
			$ignore_max_bill_amount = false, $disable_listeners = false,
			$cancel_bill = false, $payment_details = array(), $custom_field_data = array(),
			$cashier_details = array()
	) {

		if ($datetime == '')	$datetime = time();

		$org_id = $this->currentorg->org_id;
		$datetime = Util::getMysqlDateTime($datetime);

		//Do not modify for cancel bill
		if(!$cancel_bill){
			$bill_number = trim($bill_number);
			$bill_number = str_replace('\\', '-', $bill_number);
			$bill_number = str_replace('/', '-', $bill_number);
			$bill_number = strtoupper($bill_number);
		}

		$flag = false;
		$ignore_points_on_bill_number = $this->currentorg->getConfigurationValue(BILL_NUMBER_WISE_IGNORE_POINTS,false);
		if($ignore_points_on_bill_number){

			$starts_with = $this->currentorg->getConfigurationValue(IGNORE_POINTS_FOR_BILL_NUMBER_STARTS_WITH, 'test');
			$this->logger->debug('Starts With  :'.$starts_with);

			$starts_with_array = StringUtils::strexplode(',', $starts_with);
			foreach($starts_with_array as $s){

				$flag = Util::StringBeginsWith($bill_number, $s);

				$this->logger->debug('Checking For:-> '.$s.' ,Bill_number :->'.$bill_number);
				if($flag){
					$this->logger->debug('Flag Set For Tag  :'.$s);
					break;
				}
			}
			$this->logger->debug('Flag Set To Ignore  :'.$flag);

			if($flag)
				$ignore_points = true;
		}
		$this->logger->debug('Ignore  Points:'.$ignore_points);
		if($ignore_points)
			$points = 0;

		//Used to send back information to event manager
		//TODO : Cleanup
		global $event_manager_ignore_points;
		$event_manager_ignore_points = $ignore_points;

		//////// Valid Bill Amount Check   ////////////
		if(
				!$ignore_max_bill_amount && !$cancel_bill &&
				(
						$bill_amount < $this->currentorg->getConfigurationValue(CONF_LOYALTY_MIN_BILL_AMOUNT,'0')
						|| $bill_amount > $this->currentorg->getConfigurationValue(CONF_LOYALTY_MAX_BILL_AMOUNT,'50000')
				)
		){
			$store = StoreProfile::getById($entered_by);
			$store_name = $store->username;
			$org_name = $this->currentorg->name;

			$this->loyaltyController->mailOutlierDetails($user, $loyalty_id, $points, $bill_amount, $notes, $bill_number, $entered_by, $ignore_points, $datetime);

			$this->loyaltyController->insertOutlierBill($user->user_id, $loyalty_id,$points, $bill_amount, $notes, $bill_number, $datetime,$entered_by);
			return ERR_LOYALTY_INVALID_BILL_AMOUNT;
		}

		////////////////////////


		////////////  FRAUD USER OR NOT CHECK  ////////////////
		//Check if we are allowed to add bills by the customer
		$disallow_fraud_statuses = json_decode($this->currentorg->getConfigurationValue(CONF_FRAUD_STATUS_CHECK_BILLING, json_encode(array())), true);
		//get the fraud status for the customer
		$customer_fraud_status = $this->loyaltyController->getFraudStatus($user->user_id);
		if(count($disallow_fraud_statuses) > 0 && strlen($customer_fraud_status) > 0 && in_array($customer_fraud_status, $disallow_fraud_statuses))
			return ERR_LOYALTY_FRAUD_USER;


		//////////////////////////////////////////////////


		$this->logger->debug("points, added = $points,bill, number = $bill_number,balance, amount= $balance");
		# check if the Bill number is already present


		/////// BILL NUMBER REQUIRED CHECK ///////

		if ($this->getConfiguration(CONF_LOYALTY_IS_BILL_NUMBER_REQUIRED) && $bill_number == false) {
			return ERR_LOYALTY_INVALID_BILL_NUMBER;
		}

		//////////////////////


		/**
		 if (!$disable_listeners) {
			if ($this->getConfiguration(CONF_LOYALTY_ENABLE_SLABS))
			{
			list($slab_name, $slab_number) = $this->loyaltyController->getSlabInformationForUser($user);

			$points_before = $this->loyaltyController->getBalance($loyalty_id);
			$params = array('user_id' => $user->user_id, 'bill_amount' => $bill_amount, 'total_points_before_adding' => $points_before, 'current_points' => $points, 'from_slab' => $slab_name,'bill_number' => $bill_number);
			//$this->lm->signalListeners(EVENT_LOYALTY_TRASNACTION_STARTED, $params );
			}
			}
			**/


		$bill_store_unique = $this->currentorg->get(CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE) ? $entered_by : false;
		
		$bill_till_unique = $this->currentorg->get(CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL) ? true : false;

		$trans_num_unique_tills_check=false;
		if($bill_store_unique)
		{
			include_once 'apiController/ApiStoreController.php';
			$store_controller=new ApiStoreController();
			$trans_num_unique_tills_check=array_merge(array($entered_by),$store_controller->getStoreTerminalsByStoreId($store_id));
		}
		elseif($bill_till_unique)
			$trans_num_unique_tills_check=array($entered_by);

		$this->logger->debug("CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_TILL: ".$bill_till_unique?"true":"false");
		$this->logger->debug("CONF_LOYALTY_BILL_NUMBER_UNIQUE_ONLY_STORE: $bill_store_unique");
		$this->logger->debug("CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE: $bill_store_unique");
		$this->logger->debug("Checking bill number uniqueness among tills: ".implode(",",$trans_num_unique_tills_check));
		
		$bill_number_count = $this->loyaltyController->getNumberOfBills($bill_number, $trans_num_unique_tills_check, false, true, false, $datetime );
			
		$difference = $points;
		$lifetime_purchases_difference = $bill_amount;

		$is_update = false;

		$cf = new CustomFields();

		/////////// BILL NUMBER IS NOT UNIQUE ////////////////

		if ($bill_number != '' && (($this->getConfiguration(CONF_LOYALTY_IS_BILL_NUMBER_UNIQUE) && $bill_number_count > 0) || $cancel_bill)) {
			# Find existing points, calculate the difference for updating later

			$row = $this->loyaltyController->getBillDetails($bill_number, $bill_store_unique, false, 1);
			$existing_points = $row['points'];
			$loyalty_log_id = $row['id'];
			$bill_loyalty_id = $row['loyalty_id'];
			$redeemed_points = $row['redeemed'];
			$expired = $row['expired'];

			if( $redeemed_points > 0 || $expired > 0 ){
				$this->logger->debug("Points have been consumed on bill");
				return ERR_LOYALTY_BILL_POINTS_USED;
			}

			if(!$cancel_bill && $loyalty_log_id > 0){
				$this->logger->debug("Not a cancel bill. No updation of bills from now on");
				return ERR_LOYALTY_DUPLICATE_BILL_NUMBER;
			}

			if ($bill_loyalty_id != $loyalty_id) {
				$this->logger->debug("This bill was already generated for another user, so, not processing it...");
				return ERR_LOYALTY_DUPLICATE_BILL_NUMBER;
			}

			if (isset($bill_number)) {
				$difference = $points - $existing_points;
				$lifetime_purchases_difference = $bill_amount - $row['bill_amount'];
			}
			//update points
			$ret1 = $this->loyaltyController->updateBillDetails($loyalty_log_id, $bill_number, $points, $bill_amount, $notes, $datetime, $entered_by);
			if (!$ret1) return ERR_LOYALTY_BILL_ADDITION_FAILED;

			$is_update = true;

			//update all trackers
			//populate the supplied data
			$params_t = array();
			$params_t['user_id'] = $user->user_id;
			$params_t['entered_by'] = $entered_by;
			$params_t['date'] = $datetime;
			$params_t['bill_amount'] = $lifetime_purchases_difference;
			$params_t['loyalty_log_id'] = $loyalty_log_id;
			//For all the bill amount trackers .. store the difference
			$trackermgr = new TrackersMgr($this->currentorg);
			$trackermgr->addDataForAllBillAmountTrackers($params_t);

		}
		else {   ///// BILL NUMBER IS UNIQUE/NEW ////////////

			$loyalty_log_id = $this->loyaltyController->insertBillDetails(
					$loyalty_id, $points, $datetime, $notes, $bill_amount,
					$bill_number, $entered_by, $org_id, $user->user_id,
					$bill_gross_amount, $bill_discount, $payment_details
			);

			$this->logger->debug("added to log, id = ".$loyalty_log_id);

			if ($loyalty_log_id == false) return ERR_LOYALTY_BILL_ADDITION_FAILED;

			//Add the custom field data to bill

			$this->logger->debug("about to add custom fields: " . print_r($custom_field_data, true));

			if( count( $custom_field_data ) > 0 )
				$cf->addCustomFieldDataForAssocId( $loyalty_log_id, $custom_field_data );

			$this->loyaltyController->insertBillPaymentDetails($payment_details, $loyalty_log_id);

			//grv
			$udb = new Dbase('users');
			$res = $udb->query_firstrow("SELECT und.id, und.mobile FROM users_ndnc_status und
					JOIN masters.organizations o ON (o.id = und.org_id)
					WHERE und.user_id = $user->user_id AND und.org_id =$org_id AND o.optin_active = 1");
			if ($res !== NULL)
				$udb->insert("INSERT INTO users_optin_status
						(ndnc_status_id, last_updated, user_id, org_id, mobile, added_on) VALUES
						($res[id], DATE('$datetime'), $user->user_id, $org_id, $res[mobile], NOW())
						ON DUPLICATE KEY UPDATE last_updated = GREATEST(last_updated, VALUES(last_updated)), is_active=1");
		}

		//creating store object
		$shoped_store = StoreProfile::getById( $entered_by );
		$this->logger->debug("existing_points = $existing_points, Difference: $difference");

		# Update points in the main loyalty table
		$ret = $this->loyaltyController->updateLoyaltyDetails($loyalty_id, $difference, $difference, $lifetime_purchases_difference, $datetime, $entered_by);
		$this->logger->debug("updated points, ret=$ret");
		if (!$ret) {
			return ERR_LOYALTY_BILL_ADDITION_FAILED;
		}

		# Signal Listeners
		if (!$disable_listeners && !$is_update) {

			$params = array();

			/*****

			Removing the family shit !!!

			if( $this->currentorg->isFamilyEnabled() ){

			$family = Family::getByMember( $user->user_id );

			$params['family_id'] = $family->id;
			$params['family_head_id'] = $family->family_head;

			$family_details = $this->loyaltyController->getLoyaltyDetailsForUserID( $params['family_head_id'] );
			$params['gross_points'] = $family_details['loyalty_points'] > 0 ? $family_details['loyalty_points'] : 0;
			$params['family_points'] = $family_details['loyalty_points'] > 0 ? $family_details['loyalty_points'] : 0;
			}
			******/

			//// Commenting this whole part out.. has been moved up ////
			/**

			$params['user_id'] = $user->user_id;

			$params['current_points'] = (string)$points;
			$params['bill_points'] = (string)$points;
			$params['bill_amount'] = (string)$bill_amount;
			$params['bill_number'] = $bill_number;
			$params['bill_discount'] = $bill_discount;
			$params['bill_gross_amount'] = $bill_gross_amount;
			$params['bill_diff_gross_discount'] = $bill_gross_amount - $bill_discount;
			$params['bill_diff_amount_discount'] = $bill_amount - $bill_discount;

			$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForLoyaltyID($loyalty_id);
			$params['total_points'] = $loyalty_details['loyalty_points'] > 0 ? $loyalty_details['loyalty_points'] : 0;
			$params['loyalty_points'] = $loyalty_details['loyalty_points'] > 0 ? $loyalty_details['loyalty_points'] : 0;
			$params['lifetime_purchases'] = $loyalty_details['lifetime_purchases'] > 0 ? $loyalty_details['lifetime_purchases'] : 0;
			$params['lifetime_points'] = $loyalty_details['lifetime_points'] > 0 ? $loyalty_details['lifetime_points'] : 0;

			$params['gross_points'] += $params['total_points'];
			$params['loyalty_log_id'] = $loyalty_log_id;
			$params['entered_by'] = $entered_by;
			$params['date'] = $datetime;
			$visits_and_bills = $this->loyaltyController->getNumberOfVisitsAndBillsForUser($user->user_id, $datetime);
			$params['num_of_bills'] = $visits_and_bills['num_of_bills'];
			$params['num_of_visits'] = $visits_and_bills['num_of_visits'];
			$params['num_of_bills_today'] = $visits_and_bills['num_of_bills_today'];
			$params['num_of_bills_n_days'] = $visits_and_bills['num_of_bills_n_days'];
			$params['notes'] = $notes;
			$params['ignore_points'] = $ignore_points;
			$params['shoped_at_store_name'] = $shoped_store->getName();
			$params[ 'cashier_code' ] = (string) $cashier_details[0]->cashier_code;
			$params[ 'cashier_name' ] = (string) $cashier_details[0]->cashier_name;

			$option = $cf->getCustomFieldsByScope( $org_id, LOYALTY_CUSTOM_TRANSACTION );
			foreach($option AS $o){

			$value = $cf->getCustomFieldValueByFieldName( $org_id, LOYALTY_CUSTOM_TRANSACTION, $loyalty_log_id, $o['name'] );
			$value = json_decode($value, true);

			$cfNameUgly = Util::uglify($cf->getFieldName($o['name']));

			$params[$cfNameUgly] = $value[0];
			}

			$option = $cf->getCustomFieldsByScope( $org_id, LOYALTY_CUSTOM_REGISTRATION );
			foreach($option AS $o){

			$value = $cf->getCustomFieldValueByFieldName( $org_id, LOYALTY_CUSTOM_REGISTRATION, $user->user_id, $o['name'] );
			$value = json_decode($value, true);

			$cfNameUgly = Util::uglify($cf->getFieldName($o['name']));

			$params[$cfNameUgly] = $value[0];
			}
				
			//add slab_name and slab_number
			if($this->getConfiguration(CONF_LOYALTY_ENABLE_SLABS)){

			$slab_name = $loyalty_details['slab_name'];
			$slab_number = $loyalty_details['slab_number'];

			//get default slab in case customer does not have any slab
			if(strlen($loyalty_details['slab_name']) == 0){
			$slablist = $this->loyaltyController->getSlabsForOrganization();
			if (count($slablist) > 0) {
			$slab_name = $slablist[0];
			$slab_number = "0";
			}
			}

			//add it to supplied data
			$params['slab_name'] = $slab_name;
			$params['slab_number'] = $slab_number;
			}

			$this->lm->signalListeners(EVENT_LOYALTY_TRASNACTION, $params);
			**/

		}
		return $loyalty_log_id;
	}

	function calculatePointsAction($mobile, $amount) {
		$user = UserProfile::getByMobile($mobile);
		if (!$user) return 0; # not work if its not a registered user
		if ($amount + 0 == 0) return 0; # not work if its not a proper number
		$this->data['info'] = $this->loyaltyController->calculatePoints($user, array('amount' => $amount));
	}


	public function sendVouchersSmsForCustomerApiAction( $user_id = false , $mobile = false ){

		$cm = new CampaignsModule();

		if ($user_id > 0)
			$user = UserProfile::getById( $user_id );
		else
			$user = UserProfile::getByMobile( $mobile );

		$vouchers = Voucher::getVouchers( $this->currentorg->org_id, $user->user_id );
		$cust_name = $user->first_name . ' ' . $user->last_name;

		foreach ( $vouchers as $voucher ){

			$voucher_code = $voucher['voucher_code'];
			$series_id = $voucher['id'];

			$v = Voucher::getVoucherFromCode( $voucher_code, $this->currentorg->org_id );
			if ( $v ){

				$rtn = $v->isRedeemable( $user, $this->currentorg );
				$key = Voucher::getResponseErrorKey( $rtn );

				if( $key == 'VOUCHER_ERR_SUCCESS' ){

					$series_detail =
					$cm->campaignsController->getVoucherSeriesDetailsByOrgAndVchSeries( $series_id );

					$series_detail = $series_detail[0];

					$sms_template = $series_detail['sms_template'];
					$template_arguments = array(
							'voucher_code' => $voucher_code,
							'cust_name' => $cust_name );

					$message = Util::templateReplace( $sms_template, $template_arguments );

					Util::sendSms( $mobile, $message, $this->currentorg->org_id ,0 ,
										false, '', false, false, array(),
										$user->user_id, $user->user_id, 'VOUCHER' );
				}
			}
		}
	}


	private function transformBillData(&$xml_string)
	{
		global $logger;
		$logger->debug("dataTransformer: inside method");
		$element = Xml::parse($xml_string);
		$data = array();
		$bills = $element->xpath('/root/bill');
		$transactions = array();

		$logger->debug("dataTransformer: entering forloop");
		foreach($bills as $b)
		{
			$customer = array();
			$transaction = array();
			$transaction['loyalty_id'] = (string)$b->loyalty_id;
			$transaction['bill_client_id'] = (string) $b->bill_client_id;
			$transaction['transaction_number'] = (string)$b->bill_number;
			$transaction['amount'] = (double)$b->bill_amount;
			$transaction['points'] = (integer)$b->points_added;
			$transaction['notes'] = (string)$b->notes;
			$transaction['billing_time'] = (string)$b->billing_time;
			$transaction['ignore_points'] = (integer)$b->ignore_points;
			$transaction['bill_gross_amount'] = (integer)$b->bill_gross_amount;
			$transaction['gross_amount'] = (integer)$b->bill_gross_amount;
			$transaction['discount'] = (double)$b->bill_discount;
			$c = $b->customer;
			$customer['mobile'] = (string)$c->mobile;
			$customer['email'] = (string)$c->email;
			$customer['external_id'] = (string)$c->external_id;
			$customer['name'] = (string)$c->name;
			
			$customer['customer_mobile'] = (string)$b->customer_mobile;
			$customer['customer_name'] = (string)$b->customer_name;
			
			if(!empty($customer['customer_mobile']))
				$customer['mobile'] = $customer['customer_mobile'];
			if(!empty($customer['customer_name']))
				$customer['name'] = $customer['customer_name'];

			$arr = StringUtils::strexplode(" ", $customer['name']);
			
			if(count($arr) >= 2)
			{ 
				$customer['firstname'] = $arr[0];
				$customer['lastname'] = $arr[1];
			}
			else 
				$customer['firstname'] = $customer['name'];
			
			$cf = $c->xpath("custom_fields_data/custom_data_item");
			$customer['custom_fields']['field'] = array();
				
			if(is_array($cf))
			{
				foreach($cf as $k=>$cfd)
				{
					$this->logger->debug("Customer CustomFields: $k [".$cfd->field_name." => ".$cfd->field_value."]");
					$field = array();
					$field['name'] = (string)($cfd->field_name);
					$field['value'] = (string)($cfd->field_value);
					array_push($customer['custom_fields']['field'], $field);
				}
			}
			
			
			$cf = $b->xpath("custom_fields_data/custom_data_item");
			$transaction['custom_fields']['field'] = array();
			
			$this->logger->debug("Extracting Custom Fields of Transaction");
			if(is_array($cf))
			{
				foreach($cf as $k=>$cfd)
				{
					$this->logger->debug("Bill CustomFields: $k [".$cfd->field_name." => ".$cfd->field_value."]");
					$field = array();
					$field['name'] = (string)($cfd->field_name);
					$field['value'] = (string)($cfd->field_value);
					array_push($transaction['custom_fields']['field'], $field);
				}
			}
			
			$transaction['lineitems']['lineitem'] = array();
			$lineitems = array();

			$this->logger->debug("Extracting Line Items");
			foreach($b->xpath("line_items/line_item") as $li)
			{
				$litem['serial'] = (string)$li->serial;
				$litem['item_code'] = (string)$li->item_code;
				$litem['description'] = (string)$li->description;
				$litem['qty'] = (integer)$li->qty;
				$litem['rate'] = (double)$li->rate;
				$litem['value'] = (double)$li->value;
				$litem['discount_value'] = (double)$li->discount_value;
				$litem['amount'] = (double)$li->amount;
				
				$litem['attributes']['attribute'] = array();
				//$attributes = $li->inventory_info->attribute;
				$attributes = $li->xpath("inventory_info/attribute");
				
				//$this->logger->debug("Inventory Info: ".print_r($attributes, true));
				$this->logger->debug("Extracting Attributes for LineItem: ".$litem['item_code']);
				
				$this->logger->debug("is_array(attribute): ". print_r(is_array($attributes), true)." isset name: ".print_r(isset($attributes->name), true));
				if(!empty($attributes->name))
				{
					$attr = array();
					$attr['name'] = (string)$attributes->name;
					$attr['value'] = (string)$attributes->value;
					array_push($litem['attributes']['attribute'], $attr);
				}
				else if(is_array($attributes)){
					foreach($attributes as $k=>$attribute)
					{
						$this->logger->debug("Bill CustomFields: $k [".$attribute->name." => ".$attribute->value."]");
						$attr = array();
						$attr['name'] = (string)($attribute->name);
						$attr['value'] = (string)($attribute->value);
						array_push($litem['attributes']['attribute'], $attr);
					}
				}
				
				array_push($transaction['lineitems']['lineitem'],$litem);
			}
			//$transaction['lineitems']['lineitem'] = $lineitems;
			$transaction['customer'] = $customer;
			
			$payment_details = array();
			$payment = $b->xpath('payment_details/payment');
			$this->logger->debug("Extracting Payment Details");
			
			if(sizeof($payment) > 0)
			{
				foreach($payment as $p){
					$payment_details[] = array(
							'mode' => (string)$p->type,
							'value' => (float)$p->value
					);
				}
			}
			$transaction['payment_details']['payment'] = $payment_details;
			
			$promo_details = array();
			
			$this->logger->debug("Extracting Promotional Vouchers");
			foreach($b->xpath('promotional_voucher') as $pd){
				$series_id = (string)$pd->voucher_series_id;
				$detail = (string)$pd->voucher_code;
				array_push($promo_details, array('series_id' => $series_id, 'promo_detail' => $detail));
			} 
			$transaction['promotional_voucher'] = $promo_details;
			
			$transaction['cashier_details'] = $b->xpath('cashier_details');
			
			$this->logger->debug("Extracting RedeemedVouchers Vouchers");
			$redeemed_vouchers = $b->xpath('redeemed_voucher/voucher_code');
			$transaction['redeemed_vouchers'] = $redeemed_vouchers;
			
			$associate_details = $b->xpath("associate_details");
			if(is_array($associate_details) && is_object($associate_details[0]))
			{
				$transaction['associate_details']['code'] = (string) $associate_details[0]->code;
				$transaction['associate_details']['name'] = (string) $associate_details[0]->name;
			}
			
			
			array_push($transactions,$transaction);
		}

		//die("assoc array ".print_r($transactions,true));
		return $transactions;
	}
	/*
	 *note :- If xml is getting changed Please check the compatibility with outlier Bill Submition action.
	*
	*/

	function addBillsApiAction() {

        ini_set('memory_limit', '500M');        

		global $logger;

		$xml_string = <<<EOXML
<root>
	<bill>
                ---- use any 1----
                <loyalty_id>6</loyalty_id>
                ---
                <loyalty_id>LOYALTY_ID_NA</loyalty_id>
                <customer_name>prakhar verma</customer_name>
                <customer_mobile>919972317665</customer_mobile>
                <customer>
                        <name></name>
                        <email></email>
                        <mobile></mobile>
                        <external_id>random_foo1</external_id>
                        <custom_fields_data>
                                <custom_data_item>
                                        <field_name>citibank</field_name>
                                        <field_value>n</field_value>
                                </custom_data_item>
                        </custom_fields_data>
                </customer>
                ---- use any 1----
                <bill_number>tethhh14</bill_number>

		<loyalty_id>2737561</loyalty_id>
		<bill_number>Prakhar Verma New-4</bill_number>
		<bill_amount>5000</bill_amount>
		<points_added>6000</points_added>
		<notes>Testing For Outlier</notes>
		<billing_time>2011-02-02T09:52Z</billing_time>
		<ignore_points>0</ignore_points>
		<bill_client_id>___GUID___</bill_client_id>
		<bill_gross_amount>1000</bill_gross_amount>
		<bill_discount>100</bill_discount>
                <custom_fields_data>
                        <custom_data_item>
                                <field_name>add_to_family</field_name>
                                <field_value>1</filed_value>
                        </custom_data_item>
                </custom_fields_data>
		<line_items>
			<line_item>
				<serial>1</serial>
				<item_code>78394957575</item_code>
				<description>Short Desc</description>
				<qty>2</qty>
				<rate>100</rate>
				<value>200</value>
				<discount_value>78394957575</discount_value>
				<amount>78394957575</amount>
			</line_item>
			<line_item>
				<serial>2</serial>
				<item_code>78394957545</item_code>
        <description>Short Desc-2</description>
				<qty>3</qty>
				<rate>200</rate>
				<value>600</value>
				<discount_value>0</discount_value>
				<amount>600</amount>
			</line_item>
		</line_items>
		<promotional_voucher>
			<voucher_series_id>18</voucher_series_id>
			<voucher_code>promotest123</voucher_code>
		</promotional_voucher>
		<redeemed_voucher>
		    	<voucher_code>ASAS222AA</voucher_code>
			<voucher_code>33ASDASDG</voucher_code>
		</redeemed_voucher>
		<associate_details>
	       <code></code>
	       <name></name>
		</associate_details>
	</bill>
</root>
EOXML;

		$logger->debug("addBills: begin");
		global $add_bill_details, $currentorg;

		$xml_string = $this->getRawInput();
		//$logger->debug("addBills: XMLString ".$xml_string);
		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);

		$org_id = $currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$logger->debug("addBills: OrgId $org_id, StoreId $store_id ");
		$response = array('first_element' => array('empty' => 'true'));

		$bill_count = 0;
		$li_count = 0;

		$reg_time = 0;
		$points_calc_time = 0;
		$add_points_time = 0;
		$line_item_time = 0;
		$dvs_time = 0;
		$ef_time = 0;
		
		$transaction_count = 0;
		$error_count = 0;

		$transactions = $this->transformBillData($xml_string);
        //$logger->debug("addBills: Transactions = ".print_r($transactions,true));
        
        unset($xml_string);
        global $gbl_item_count, $gbl_item_status_codes;
		$gbl_item_count = count($transactions);
        
        $arr_item_status_codes = array();
		foreach ($transactions as $k=>$transaction)
		{
			/************************************************/
			$transaction++;
			try{
				++$transaction_count;
				$key = 'ERR_LOYALTY_SUCCESS';
				$transaction['type'] = 'regular';
				//$transaction_flow = new TransactionFlow($transaction);
				//$t = $transaction_flow->addBill();


				if(empty($transaction['loyalty_id']) && $transaction['loyalty_id'] != "LOYALTY_ID_NA"  )
					throw new Exception("ERR_LOYALTY_USER_NOT_REGISTERED");

				$transaction_controller = new ApiTransactionController();
				$transaction_controller->addBills($transaction);

				$key = $transaction_controller->getStatusCode();
				$user = $transaction_controller->getUser();
				//$t->callPointsEngine();
				//$item = $t->generateResponse();

			}catch(Exception $e){
				++$error_count;
				$logger->debug("addBills: Exception = ".$e->getMessage());
				$key = $e->getMessage();
				if($transaction_controller)
					$user = $transaction_controller->getUser();
				else
					$user = UserProfile::getByData(array());
				
			}
			$logger->debug("addBills: Status Code $key ");
			if(!$transaction_controller)
			{
				//$transaction_controller = new ApiTransactionController();
				$transaction_hash = array("user_id" => -1, "new_registration" => 0);
			}
			else 
				$transaction_hash = $transaction_controller->getHash();
			
			$transaction_hash['bill_client_id'] = $transaction['bill_client_id'];
			//$msg = $this->generateErrorMessage($key,$user,$transaction_controller->getHash());
			$msg = $this->generateAddTransactionResponseMessage($key,$user,$transaction_hash);
			$arr_item_status_codes[] = ErrorCodes::$transaction[$key];
			if( Util::isStore21Org( $org_id ) )
			{
				list($num_regs_today, $num_bills_today) = $this->loyaltyController->GetNumRegsAndBillsTodayForStore( $this->currentuser->user_id );

				$store_21_reg['num_regs_today'] = $num_regs_today;
				$store_21_reg['num_bills_today'] = $num_bills_today;

				$response['bills_regs_count'] = $store_21_reg;
			}
			array_push($response,$msg);
		    unset($transaction);		
			$this->logger->debug("addBills: Response ".print_r($response,true));
		} //end of the main for loop

		
		global $time_breakup;

		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		//billcount, line item count, time in registration, time in points calculcation, time in points addition, time in line item addition, time in server dvs,
		//time in event framework
		$time_breakup = "bc:$bill_count,lc:$li_count,rt:$reg_time,pct:$points_calc_time,apt:$add_points_time,lit:$line_item_time,dvst:$dvs_time,eft:$ef_time";

		$this->data['responses'] = $response;
	} //function end


	//KARTIK

	//private function generateErrorMessage($status_code,$user,$transaction)
	private function generateAddTransactionResponseMessage(&$status_code,&$user,&$transaction)
	{
		$this->logger->debug("addBills: Generating old api error msg");
		$response = array();
		$item_status = array();
		$response_code = Util::convertOldErrorCodes($status_code);
		$this->logger->debug("addBills: Response Code $response_code");

		$item_status['key'] = $response_code;
		$item_status['message'] = ErrorMessage::$transaction[$response_code];

        //$this->logger->debug("Transaction: Item Status = ".print_r($item_status,true));

		$response = array('bill_number' => (string)$transaction['bill_number'],
				'user_id' => $user->user_id, 'loyalty_points' => $transaction['loyalty_points'],
				'lifetime_points' => $user->lifetime_points, 'lifetime_purchases' => $user->lifetime_purchases,
				'slab_name' => $user->slab_name, 'slab_number' => $user->slab_number,
				'bill_client_id' => $transaction['bill_client_id'],'response_code' => ErrorCodes::$transaction[$response_code],
				'response' => ErrorMessage::$transaction[$response_code], 'item_status' => $item_status,
                'dvs_vouchers' => $transaction['dvs_vouchers'], 'new_registration' => $transaction['new_registration']
                );


		//$this->logger->debug("Transaction: Response = ".print_r($response,true));
		return $response;
	}


	/***************** CANCEL BILL ********************/

	function cancelBill(UserProfile $user, $bill_number, $entered_time = '') {
		if ($entered_time == '') $entered_time = time();
		$entered_time = Util::getMysqlDateTime($entered_time);
		$org_id = $this->currentorg->org_id;
		# Check if the bill is issued to this user.

		$bill_number = trim($bill_number);

		$bills = $this->loyaltyController->getNumberOfBills($bill_number,false,$user->user_id);

		if ($bills != 1) {
			return ERR_LOYALTY_INVALID_BILL_NUMBER;
		}
		$loyalty_id = $this->loyaltyController->getLoyaltyId($org_id, $user->user_id);

		if ($loyalty_id == -1) return ERR_LOYALTY_USER_NOT_REGISTERED;
		$me = $this->currentuser->user_id;

		//check if the bill is cancelled already
		if ($this->loyaltyController->isBillCancelled($loyalty_id, $bill_number))
			return ERR_LOYALTY_DUPLICATE_BILL_NUMBER;

		//disable listeners, call 'addPoints' in cancel mode
		$ret2 = $this->addPoints(
				$user, $loyalty_id, 0, 0, "**Bill Cancelled**",
				$bill_number, false, false, $me, 0,
				$entered_time, false,true, true
		);

		if($ret2 > ERR_LOYALTY_SUCCESS){

			$returnBillId = $ret2;
				
			//Make a call to event framework
			if(Util::canCallPointsEngine()) {
				$cancel_timer = new Timer("cancel_bill_points_engine");
				$cancel_timer->start();

				try{

					$this->logger->debug("pigol: Trying to contact event manager for cancel bill event");

					//COMPILE
					$event_client = new EventManagementThriftClient();

					$returnDate = Util::getMysqlDateTime($entered_time);
					$timeInMillis = strtotime($returnDate);
					if($timeInMillis == -1 || !$timeInMillis )
					{
						throw new Exception("Cannot convert '$entered_time' to timestamp", -1, null);
					}
					$timeInMillis = $timeInMillis * 1000;
						
					$tillId = $this->currentuser->user_id;
						
					
					if(Util::canCallEMF())
					{
						try{
							$emf_controller = new EMFServiceController();
							$commit = Util::isEMFActive();
							$this->logger->debug("Making cancelBillEvent call to EMF");
							$emf_result = $emf_controller->cancelBillEvent(
									$org_id,
									$user->user_id,
									$returnBillId,
									$tillId,
									$timeInMillis,
									$commit);
							
							
							$coupon_ids = $emf_controller->extractIssuedCouponIds($emf_result, "PE");
							$this->lm->issuedVoucherDetails($coupon_ids);
							
							if($commit && $emf_result !== null )
							{
								$pesC = new PointsEngineServiceController();
								
								$pesC->updateForCancelBillTransaction(
										$org_id, $user->user_id, $returnBillId, $timeInMillis);
							}
						}
						catch(Exception $e)
						{
							$this->logger->error("Error while making cancelBillEvent to EMF: ".$e->getMessage());
							if(Util::isEMFActive())
							{
								$this->logger->error("Rethrowing EMF Exception AS EMF is Active");
								throw $e;
							}
						}
					}
						
					if(!Util::isEMFActive())
					{
						$result = $event_client->cancelBillEvent(
								$org_id, $user->user_id, $returnBillId, $tillId, $timeInMillis);
	
						$evaluation_id = $result->evaluationID;
						$effects_vec = $result->eventEffects;
						$this->logger->debug("evaluation_id: $evaluation_id, effects: " . print_r($effects_vec, true));
	
						//COMMIT
						if($result != null && $evaluation_id > 0) {
							$this->logger->debug("Calling commit on evaluation_id: $evaluation_id");
	
							$commit_result = $event_client->commitEvent($result);
							$this->logger->debug("Commit result on evaluation_id: ".$commit_result->evaluationID);
							$this->logger->debug("Commit result on effects: ".print_r($commit_result, true));
								
							//Update the old tables from the points engine view
							$pesC = new PointsEngineServiceController();
	
							$pesC->updateForCancelBillTransaction(
									$org_id, $user->user_id, $returnBillId, $timeInMillis);
						}
					}

				}catch(Exception $e){

					$this->logger->error("Exception thrown in new bill event, code: " . $e->getCode()
							. " Message: " . $e->getMessage());
				} // end point engine call

				$cancel_timer->stop();

				$ef_time += $cancel_timer->getTotalElapsedTime();

				$this->logger->debug("pigol: cancel bill timer: " . $cancel_timer->getTotalElapsedTime());
				unset($cancel_timer);
			}
				
			$ret = $this->loyaltyController->InsertCancelledBills($user->user_id, $loyalty_id, $bill_number, $me, $entered_time);
				
			if ($ret == 0)
				return ERR_LOYALTY_DUPLICATE_BILL_NUMBER;

			$cancelBillRecordId = $ret;
				
			// There might be points in award points, remove that as well against the bill
			$this->loyaltyController->removeAwardedPointsOnBill($user->user_id, $bill_number, "Bill Cancelled : ");
				
			// There might be points in slab upgrade, remove that as well against the bill
			$this->loyaltyController->removeSlabUpgradePointsOnBill($user->user_id, $bill_number, "Bill Cancelled : ");
		}

		return $ret2;
	}


	function cancelApiAction() {
		$xml_string = <<<EOXML
<root>
  <cancelled_bill>
    <bill_number>ttt6</bill_number>
    <user_id>2322</user_id>
    <loyalty_id>121953</loyalty_id>
    <customer_mobile>447990081111</customer_mobile>
    <customer_external_id>600</customer_external_id>
    <customer_email>saurabh.kumar@dealhunt.in</customer_email>
    <entered_time></entered_time>
  </cancelled_bill>
</root>
EOXML;

		//	if ($testing == false){
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		
		$element = Xml::parse($xml_string);
		//$element = Xml::parse($xml_string);
		//	}else{
		//		$element = Xml::parse($xml_string);
		//	}

		$bills = $element->xpath('/root/cancelled_bill');
		$response = array();
		foreach ($bills as $b) {
			$time = Util::deserializeFrom8601((string) $b->entered_time);
			$user_id = $this->loyaltyController->getUserIdFromLoyaltyId((integer)$b->loyalty_id);
			$user = UserProfile::getById($user_id);

			//try to extract the user from mobile or email or external id
			//Try to fetch by mobile
			$customer_mobile = $b->customer_mobile;
			if(!$user && (strlen($customer_mobile) > 0))
				$user = UserProfile::getByMobile($customer_mobile);

			//try to fetch by external id
			$customer_external_id = $b->customer_external_id;
			if(!$user && (strlen($customer_external_id) > 0))
				$user = UserProfile::getByExternalId($customer_external_id);

			//try to fetch by external id
			$customer_email = $b->customer_email;
			if(!$user && (strlen($customer_email) > 0))
				$user = UserProfile::getByEmail($customer_email);

            $user_id = intval($b->user_id);
            $this->logger->debug("Extracted user id: $user_id");
            if($user === false && $user_id > 0){
                $user = UserProfile::getById($user_id);
                $this->logger->debug("User loaded with id: " . $user->user_id);
            }

			if(!$user)
				$response_code = ERR_LOYALTY_USER_NOT_REGISTERED;
			else
				$response_code = $this->cancelBill($user, (string) $b->bill_number, $time);

			$response_string = $this->getResponseErrorMessage($response_code);

			$key = ($response_code > 0) ? ERR_LOYALTY_SUCCESS : $response_code;
				
			$cancel_status = array(
					'key' => $this->getResponseErrorKey($key),
					'message' => $this->getResponseErrorMessage($key)
			);

			array_push($response, array('bill_number' => (string)$b->bill_number, 'user_id' => $user->user_id, 'response_code' => $response_code, 'response' => $response_string, 'item_status' => $cancel_status));
		}
		$this->data['responses'] = $response;
	}




	function customerNotInterestedApiAction() {
		//for testing...
		$xml_string = <<<EOXML
<root>
  <not_interested_bill>
    <bill_number>BillTestXml</bill_number>
    <bill_amount>950</bill_amount>
    <billing_time>2009-08-04T13:44:37.9531250+05:30</billing_time>
    <not_interested_reason>Some wierd reason</not_interested_reason>
    <bill_client_id>___GUID___</bill_client_id>
    <line_items>
		<line_item>
			<serial>1</serial>
			<item_code>78394957575</item_code>
			<description>Short Desc</description>
			<qty>2</qty>
			<rate>200</rate>
			<value>400</value>
			<discount_value>50</discount_value>
			<amount>350</amount>
		</line_item>
		<line_item>
			<serial>2</serial>
			<item_code>78394957545</item_code>
         	<description>Short Desc-2</description>
			<qty>3</qty>
			<rate>200</rate>
			<value>600</value>
			<discount_value>0</discount_value>
			<amount>600</amount>
		</line_item>
	</line_items>
  </not_interested_bill>
  <not_interested_bill>
    <bill_number>dsf</bill_number>
    <bill_amount>999</bill_amount>
    <billing_time>2009-08-04T13:44:45.7812500+05:30</billing_time>
  </not_interested_bill>
</root>
EOXML;
		//Replace by the actual string through api.
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		$element = Xml::parse($xml_string);
		$bills = $element->xpath('/root/not_interested_bill');
		$response = array();
		foreach ($bills as $b) {
			$billing_time = $b->billing_time ? Util::deserializeFrom8601((string) $b->billing_time) : time();
			$bill_number = (string) $b->bill_number;
			$bill_amount = (double) $b->bill_amount;
			$not_interested_reason = (string) $b->not_interested_reason;
			$bill_client_id = (string) $b->bill_client_id;

			//extract the line items
			$line_items = $b->xpath('line_items/line_item');
			$line_items_extracted = array();
			foreach ($line_items as $li) {
				$line_item = array(
						'serial' => (integer) $li->serial,
						'item_code' => (string)$li->item_code,
						'description' => (string) $li->description,
						'rate' => (string) $li->rate,
						'qty' => (string) $li->qty,
						'value' => (string) $li->value,
						'discount_value' => (string) $li->discount_value,
						'amount' => (string) $li->amount,
				);

				//collect the line items
				array_push($line_items_extracted, $line_item);
			}

			$response_code = $this->loyaltyController->addNotInterestedBill($bill_number, $bill_amount, $not_interested_reason, $billing_time, $this->currentuser->user_id, $line_items_extracted);
			$response_string = $this->getResponseErrorMessage($response_code);

			//Add the response for the new clients
			$not_interested_status = array(
					'key' => $this->getResponseErrorKey($response_code),
					'message' => $this->getResponseErrorMessage($response_code)
			);

			array_push($response, array('bill_client_id' => $bill_client_id, 'bill_number' => $bill_number, 'response_code' => $response_code, 'response_string' => $response_string, 'item_status' => $not_interested_status));
		}

		$this->logger->debug("bill response: " . print_r($response, true));

		$this->data['responses'] = $response;
	}



	function loyaltyTrackerApiAction() {
		//for testing...
		$xml_string = <<<EOXML
<root>
  <loyalty_tracker>
    <number_bills_registered>10</number_bills_registered>
    <total_sales>5393</total_sales>
	<footfall_count>45</footfall_count>
    <captured_regular_bills>2</captured_regular_bills>
    <captured_not_interested_bills>2</captured_not_interested_bills>
    <captured_enter_later_bills>2</captured_enter_later_bills>
	<captured_pending_enter_later_bills>5</captured_pending_enter_later_bills>
    <date_report>2009-08-13T00:00:00+05:30</date_report>
  </loyalty_tracker>
</root>
EOXML;
		//Replace by the actual string through api.

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);
		$loyalty_trackers = $element->xpath('/root/loyalty_tracker');
		$response = array();
		foreach ($loyalty_trackers as $lt) {
			$date = Util::deserializeFrom8601((string) $lt->date_report);
			$num_bills = (string) $lt->number_bills_registered;
			$sales = (string) $lt->total_sales;
			$footfall_count = (string) $lt->footfall_count;

			$captured_regular_bills = (string) $lt->captured_regular_bills;
			$captured_not_interested_bills = (string) $lt->captured_not_interested_bills;
			$captured_enter_later_bills = (string) $lt->captured_enter_later_bills;
			$captured_pending_enter_later_bills = (string) $lt->captured_pending_enter_later_bills;

			$response_code = $this->loyaltyController->addLoyaltyTrackerInfo($num_bills, $sales, $footfall_count, $date,
					$this->currentuser->user_id, $captured_regular_bills, $captured_not_interested_bills,
					$captured_enter_later_bills, $captured_pending_enter_later_bills);
			$response_string = $this->getResponseErrorMessage($response_code);

			$tracker_status = array(
					'key' => $this->getResponseErrorKey($response_code),
					'message' => $this->getResponseErrorMessage($response_code)
			);

			array_push($response, array('number_bills_registered' => $num_bills, 'date_report' => (string) $lt->date_report, 'response_code' => $response_code, 'response_string' => $response_string, 'item_status' => $tracker_status));
		}
		$this->data['responses'] = $response;
	}


	/*********** API METHODS ******************/

	function getCustomerByMobileApiAction($mobile) {

		$customer = $this->loyaltyController->getLoyaltyCustomerByMobile($mobile);

		if (!$customer) {
			$ret = ERR_LOYALTY_INVALID_MOBILE;

			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			return;
		}

		$this->data['customer'] = $customer;
	}

	function getCustomersApiAction() {
		//set 800MB as limit for now
		ini_set('memory_limit', '800M');
		// when the client content caching is NOT DISABLED , get the data in file

		if($this->currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false))
			$this->data['customers']= $this->loyaltyController->getLoyaltyCustomersByOrg();
		else
			$this->loyaltyController->getLoyaltyCustomersByOrgInFile($this->data['xml_file_name']);
	}

	function getCustomersDeltaApiAction() {
		//set 800MB as limit for now
		ini_set('memory_limit', '800M');
		// when the client content caching is NOT DISABLED , get the data in file
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false))
			$this->data['customers']= $this->loyaltyController->getLoyaltyCustomersByOrg(true);
		else
			$this->loyaltyController->getLoyaltyCustomersByOrgInFile($this->data['xml_file_name'], true);

	}



	function issueValidationCodeApiAction($purpose, $mobile, $external_id) {

		global $counter_id;

		if ($mobile == "null") $mobile = "";
		if ($mobile != false && !Util::checkMobileNumber($mobile)) return;

		$v = new ValidationCode();
		$store_id = $this->currentuser->user_id;
		$store_id = $counter_id > 0 ? $counter_id : $store_id;
		$user = UserProfile::getByMobile( $mobile );
		if( $user ){
			
			$otp_manager = new OTPManager();
			$code = $otp_manager->issue( $user->user_id, 'POINTS', $points );
		}
// 		$code = $v->issueValidationCode($this->currentorg, $mobile,$external_id, $purpose, false, $store_id);
		$args = array('validation_code' => $code);
		$sms_template = Util::valueOrDefault($this->currentorg->get(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE), LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE_DEFAULT);
		$sms = Util::templateReplace($sms_template, $args);
		Util::sendSms($mobile, $sms, $this->currentorg->org_id, MESSAGE_PRIORITY, false, '', true, true,
						 array( 'otp' ),-1 , -1, 'VALIDATION' );
		$this->data['validation_code'] = $code;
	}

	function issueValidationCodeNewApiAction() {

		global $counter_id;

		$org_id = $this->currentorg->org_id;
		$xml_string = <<<EOXML
<root>
	<issue_validation_code>
		<email>email@email.com</email>   #ticket 16235
		<mobile>919748088726</mobile>
		<purpose>0</purpose>
		<issue_time>2010-05-11 11:15</issue_time>
		<external_id></external_id>
		<store_id>1235</store_id>
		<additional_bits></additional_bits>
	</issue_validation_code>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/issue_validation_code');
		$responses = array();

		foreach ($elems as $e) {
			$mobile = trim((string) $e->mobile);
			$external_id = trim((string) $e->external_id);
			$purpose = trim((string) $e->purpose);
			$issue_time = trim((string) $e->issue_time);
			$store_id = $this->currentuser->user_id;
			$store_id = $counter_id > 0 ? $counter_id : $store_id;
			$additional_bits = trim((string) $e->additional_bits);

			$points = intval($additional_bits);

			$code = false;
			$ret = ERR_LOYALTY_SUCCESS;

			//$time_in_zone = Util::getOffsettedTimestampInTimeZone($this->currentuser->getStoreTimeZoneLabel(), $issue_time);
			$time_in_zone = Util::getCurrentTimeInTimeZone('Europe/London');

			$time = strtotime($time_in_zone);

			$this->logger->debug("VTU $time $time_in_zone " . date('Y-m-d H:i:s', $time_in_zone));

			if ($mobile == "null") $mobile = "";
			
			$do_issue_code=true;
			$email="";
			$by_email=false;

			if (!empty($mobile) && $mobile != false && !Util::checkMobileNumberNew($mobile,array(),false)){
				if(isset($e->email) && !empty($e->email) )
				{
					$this->logger->info("Mobile is invalid or empty but email is given. setting user retrieval by email");
					$email=trim($e->email);
					$by_email=true;
				}
				else
				{
					$ret = ERR_LOYALTY_INVALID_MOBILE;
					$do_issue_code=false;
				}
			}
			
			if($do_issue_code)
			{
				$v = new ValidationCode();

				if(!$this->currentorg->getConfigurationValue(CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
				{
					$additional_bits = 0;
				}
				
// 				$code = $v->issueValidationCode($this->currentorg, $mobile,$external_id, $purpose, $time, $store_id, $additional_bits);
				if($by_email)
				{
					$this->logger->info("Getting user by email : $email");
					$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByEmail($email,$org_id);
					$user = UserProfile::getByEmail($email);
				}
				else
				{
					$this->logger->info("Getting user by mobile : $mobile");
					$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);
					$user = UserProfile::getByMobile( $mobile );
				}

				if(!$user){
					$ret = ERR_LOYALTY_USER_NOT_REGISTERED;
				} else {

					$otp_manager = new OTPManager();
					$code = $otp_manager->issue( $user->user_id, 'POINTS', $points );
					if( !$code ){
						
						$ret = ERR_LOYALTY_INVALID_VALIDATION_CODE;
					}
					
					$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByMobile($mobile,$org_id);

					$current_points = $loyalty_details['lifetime_points'];
					$lifetime_purchases = $loyalty_details['lifetime_purchases'];
					$loyalty_points = $loyalty_details['loyalty_points'];
					$args = array('validation_code' => $code,'lifetime_points' => $current_points,'lifetime_purchases' => $lifetime_purchases,
							'loyalty_points' => $loyalty_points, 'request_to_redeem_points' => $points );
						
					$this->logger->debug('@@Arguments : '.print_r($args,true));
						
					$sms_template = Util::valueOrDefault($this->currentorg->get(LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE), LOYALTY_TEMPLATE_REDEMPTION_VALIDATION_CODE_DEFAULT);
					$sms = Util::templateReplace($sms_template, $args);
					$this->logger->debug('@@After Template Replacement : '.$sms);
					$user_id = -1;
					if($loyalty_details && isset($loyalty_details['user_id']))
						$user_id = $loyalty_details['user_id'];
					if(!$by_email)
					if(!Util::sendSms($mobile, $sms, $this->currentorg->org_id, MESSAGE_PRIORITY, false, '', true, true,
										 array( 'otp' ), $user_id, $user_id, 'VALIDATION' ))
						$ret = ERR_LOYALTY_COMMUNICATION;
					if($by_email)
					if(!Util::sendEmail($email, "Validation code for points redemption", $sms, $org_id))
						$ret = ERR_LOYALTY_COMMUNICATION;
						
				}
			}

			//Add the response for the new clients
			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			$this->data['validation_code'] = $code;
			break;
		} // end-foreach

	}

	function checkValidationCodeApiAction($purpose, $mobile, $external_id, $code) {
		if ($mobile == "null") $mobile = "";
		if ($mobile != false && !Util::checkMobileNumber($mobile)) return;
		//$user = UserProfile::getByMobile($mobile);
		$v = new ValidationCode();
		$validate_status = $v->checkValidationCode($code, $this->currentorg,$mobile, $external_id, $purpose, false, $this->currentuser->user_id);
		$status = $validate_status ? "true" : "false";
		$this->data['validation_status'] = $status;
	}

	function checkValidationCodeNewApiAction() {
		$org_id = $this->currentorg->org_id;
		$xml_string = <<<EOXML
<root>
	<check_validation_code>
		<mobile>919748088726</mobile>
		<purpose>0</purpose>
		<issue_time>2010-05-11 11:15</issue_time>
		<external_id></external_id>
		<store_id>1235</store_id>
		<code>145Y1V</code>
		<additional_bits></additional_bits>
	</check_validation_code>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/check_validation_code');
		$responses = array();

		$this->data['validation_status'] = "false";

		foreach ($elems as $e) {
			$mobile = trim((string) $e->mobile);
			$external_id = trim((string) $e->external_id);
			$purpose = trim((string) $e->purpose);
			$issue_time = trim((string) $e->issue_time);
			$store_id = $this->currentuser->user_id;
			$additional_bits = trim((string) $e->additional_bits);
			$code = trim((string) $e->code);

			$validate_status = false;
			$ret = ERR_LOYALTY_SUCCESS;

			$time = strtotime($issue_time);
			if ($mobile == "null") $mobile = "";
			if ($mobile != false && !Util::checkMobileNumber($mobile)){
				$ret = ERR_LOYALTY_INVALID_MOBILE;
			}else{
				/* $v = new ValidationCode();

				$validate_status = $v->checkValidationCode($code, $this->currentorg,$mobile,
						$external_id, $purpose, $time, $store_id, $additional_bits);

				if(!$validate_status)
					$ret = ERR_LOYALTY_INVALID_VALIDATION_CODE; */
				
				$user = UserProfile::getByMobile( $mobile );
				
				if(!$user){
					$ret = ERR_LOYALTY_USER_NOT_REGISTERED;
				} else {
				
					$otp_manager = new OTPManager();
					if ( !$additional_bits )
 						$check = $otp_manager->verify( $user->user_id, 'POINTS', $code, "", false );
					else
						$check = $otp_manager->verify( $user->user_id, 'POINTS', $code, $additional_bits, false );
					if(!$check)
						$ret = ERR_LOYALTY_INVALID_VALIDATION_CODE;
				}
			}

			//Add the response for the new clients
			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			$status = $validate_status ? "true" : "false";
			$this->data['validation_status'] = $check;
			break;
		}
	}

	function mlmusersApiAction() {
		$this->data['mlm_users'] = $this->mlm->getMlmUsers();
	}




	function performofflineactionsAction(){

		ini_set('memory_limit', '500M');

		$org_id = $this->currentorg->org_id;

		$mlm_offline_action = false;
		$mlm_award_points_action = false;
		$mlm_compute_trees_action = false;
		$update_slab_info = false;
		$mlm_referral_update_details = false;
		$send_sms_mlm_referral_update_details = false;

		$form = new Form('mlm_offline_actions', 'post');
		$form->addField('checkbox', 'update_slab_info', 'Update \'Null\' Slab Info for Loyalty Users', $update_slab_info);
		$form->addField('checkbox', 'mlm_offline_action', 'MLM Offline Action', $mlm_offline_action);
		$form->addField('checkbox', 'mlm_award_points_action', 'MLM Award Points Action', $mlm_award_points_action);
		$form->addField('checkbox', 'mlm_compute_trees_action', 'MLM Compute Trees Action', $mlm_compute_trees_action);
		$form->addField('checkbox', 'mlm_referral_update_details', 'MLM Referrals Update Details', $mlm_referral_update_details);
		$form->addField('checkbox', 'send_sms_mlm_referral_update_details', 'Send Referral Success SMS to parent / Award points ( MLM Referrals Update Details ) ?', $send_sms_mlm_referral_update_details);


		$this->data['select_form'] = $form;

		if($form->isValidated()){

			$params = $form->parse();
			$mlm_offline_action = $params['mlm_offline_action'];
			$mlm_referral_update_details = $params['mlm_referral_update_details'];
			$send_sms_mlm_referral_update_details = $params['send_sms_mlm_referral_update_details'];
			$mlm_award_points_action = $params['mlm_award_points_action'];
			$mlm_compute_trees_action = $params['mlm_compute_trees_action'];
			$update_slab_info = $params['update_slab_info'];
		}

		if(!$this->loyaltyController->isMLMEnabled()){
			$mlm_offline_action = false;
			$mlm_award_points_action = false;
			$mlm_compute_trees_action = false;
			$mlm_referral_update_details = false;
			$send_sms_mlm_referral_update_details = false;
		}

		$this->data['mlm_offline_action'] = $mlm_offline_action;
		$this->data['mlm_award_points_action'] = $mlm_award_points_action;
		$this->data['mlm_compute_trees_action'] = $mlm_compute_trees_action;
		$this->data['update_slab_info'] = $update_slab_info;
		$this->data['mlm_referral_update_details'] = $mlm_referral_update_details;

		if($mlm_offline_action){
			//output is in $this->data['processed_table']
			$this->mlmofflineAction();
		}

		if($mlm_award_points_action){
			//output is in $this->data['points_table']
			$this->mlmawardpointsAction();
		}

		if($update_slab_info){

			//restrict updating to only those organisations who have slabs enabled
			$orgs = $this->mlm->getDistinctOrgByKey();
			$candidate_orgs = array();
			$orgprofiles = array();
			foreach($orgs as $o){

				$org_id = $o['org_id'];
				$org = new OrgProfile($org_id);
				$use_slabs = $org->getConfigurationValue(CONF_LOYALTY_ENABLE_SLABS, false);
				$slab_list = $org->getConfigurationValue(CONF_LOYALTY_SLAB_LIST, false);
				if($use_slabs && ($slab_list != false)){
					array_push($candidate_orgs, $org_id);
					$orgprofiles[$org_id]['org_profile_object'] = $org;
					$orgprofiles[$org_id][CONF_LOYALTY_ENABLE_SLABS] = $use_slabs;
					$orgprofiles[$org_id][CONF_LOYALTY_SLAB_LIST] = $slab_list;
					//$this->logger->debug("Pushing to candidate orgs : $org_id");
				}
			}
			$orgs = NULL;

			$res = $this->mlm->getLoyaltyUsersDetailsBySlabName($candidate_orgs);
			$count = 0;
			$skip = 0;
			$tab_data = array();

			foreach($res as $r){

				$id = $r['id'];
				$org_id = $r['publisher_id'];
				$user_id = $r['user_id'];

				$org = false;

				$org = $orgprofiles[$org_id]['org_profile_object'];
				$use_slabs = $orgprofiles[$org_id][CONF_LOYALTY_ENABLE_SLABS];
				$slabs_json = $orgprofiles[$org_id][CONF_LOYALTY_SLAB_LIST];

				if($org == false || $id == false || $user_id == false){
					$skip++;
					$this->logger->debug("Skipping for : id = $id, org_id = $org_id, user_id = $user_id");
					continue;
				}

				$slab_name = "NULL";
				$slab_number = "NULL";

				if ($use_slabs) {

					$slablist = json_decode($slabs_json,true);
					if ($slablist == false || !is_array($slablist)) {
						$slablist = array();
					}

					if (count($slablist) > 0) {
						$slab_name = $slablist[0];
						$slab_number = 0;
						$this->updateLoyatyTableBySlab($tab_data,$slab_name,$slab_number,$id,$user_id);
						//update loyalty table
						$count++;
					}else{
						$this->logger->debug("EMPTY SLAB LIST for org id : $org_id, so skipping updation for user : $user_id, loyalty id : $id");
						$skip++;
					}

				}else{
					$this->logger->debug("SLABS DISABLED for org id : $org_id, so skipping updation for user : $user_id, loyalty id : $id");
					$skip++;
				}
			}

			$tab = new Table();
			$tab->importArray($tab_data, array('org_id', 'user_id', 'loyalty_id', 'slab_name', 'slab_number'));
			$this->data['slab_update_table'] = $tab;
			$this->data['slab_update_count'] = $count;
			$this->data['slab_update_skipcount'] = $skip;
		}


		if($mlm_referral_update_details){

			$org_id = $this->currentorg->org_id;

			$res = $this->mlm->getParentsForMlmRefferals();

			$table_out = array();
			foreach($res as $row){
				$user_id = $row['new_user_id'];
				$parent_id = $row['new_parent_id'];
				$referee_name = $row['referee_name'];

				$retArray = $this->mlm->UpdateMlmUsersParentId($parent_id,$user_id);
				$ret = $retArray[0];
				$sql = $retArray[1];
				if(!$ret){
					$row['sms_text'] = "Parent Update Error - SMS not sent";
					$row['query'] = $sql;
					array_push($table_out, $row);
					continue;
				}

				//get the referrer profile
				$referrer = UserProfile::getById($parent_id);

				if ($referrer) {

					//award points only when send sms is also enabled
					if($send_sms_mlm_referral_update_details)
						$this->mlm->referralBonusAwarding($referrer);

					$e_ref = new ExtendedUserProfile($referrer, $this->currentorg);

					//send success message to referrer
					$sup = array(
							'referrer_name' => $e_ref->getFullName(),
							'referee_name' => $referee_name,
					);
					$msg_template = Util::valueOrDefault($this->currentorg->get(MLM_TEMPLATE_SUCCESSFUL_REFERRAL_SMS), MLM_TEMPLATE_SUCCESSFUL_REFERRAL_DEFAULT);
					$msg = Util::templateReplace($msg_template, $sup);

					if(!$send_sms_mlm_referral_update_details)
						$row['sms_text'] = "SMS/AwardPoints Disabled - $msg";
					else{
						$ret = Util::sendSms($referrer->mobile, $msg, $org_id, MESSAGE_PERSONALIZED,
												false, '', false, false, array(),
												$referrer->user_id, $referrer->user_id, 'GENERAL' );
						$sms = $ret ? "SENT - $msg" : "SMS_FAILED - $msg";
						$row['sms_text'] = $sms;
					}
				}else{
					$row['sms_text'] = "Referrer Invalid - SMS not sent";
				}

				$row['query'] = $sql;
				array_push($table_out, $row);
			}

			$headers = array('referee_name', 'new_user_id', 'referee_mobile', 'joined_date', 'parent_name', 'new_parent_id', 'parent_mobile', 'referral_date', 'sms_text');
			$table = new Table();
			$table->importArray($table_out, $headers);
			$this->data['parent_update_table'] = $table;
		}

		//run it after update referral details.. as we may have to update some of the entries..
		if($mlm_compute_trees_action){

			$to_be_updated_parents = array();

			$select_parent = true;

			$res = $this->mlm->getMlmUsers($select_parent);

			foreach($res as $r){
				$parent = "";
				if($r['parent_id'] != '-'){
					//find out the 'root' parent of the user
					$user = $r['user_id'];
					$parent = $r['parent_id'];
					while($parent != '-'){
						$res2 = $this->mlm->getMlmUsersParent($parent);
						$parent = $res2['parent_id'];
						$user = $res2['user_id'];
						$res2 = NULL;
					}
					$parent = $user;

					//add parents to list after checking for duplicates
					if(!in_array($parent, $to_be_updated_parents)){
						array_push($to_be_updated_parents, $parent);
						//$this->logger->debug("Pushing $parent to the to_be_updated list");
					}
				}
			}

			$auto_register = $this->mlm->autoregisterNonReferredUsers();
			$count = 0;
			foreach($to_be_updated_parents as $p){
				$count += $this->mlm->computeTrees($p);
			}

			$this->data['summary'] = "Auto-registered ".count($auto_register)." users\n Proccessed $count users";

			$this->data['compute_trees_table'] = $this->mlm->getMlmUsersDetails($auto_register);
		}
	}



	function mlmreferApiAction($referrer_id, $referee_mobile, $referee_email) {
		$user = UserProfile::getById($referrer_id);
		$ret = false;
		if ($user) {
			$ret = $this->mlm->referFriend($user, $referee_mobile, $referee_email);
		}else{
			$ret = ERR_MLM_INVALID_USER;
		}

		//Add the response for the new clients
		$referral_status = array(
				'key' => MLMSubModule::getResponseErrorKey($ret),
				'message' => MLMSubModule::getResponseErrorMessage($ret)
		);
		$this->data['api_status'] = $referral_status;

		$this->data['response'] = (($ret == ERR_MLM_SUCCESS) ? true : false);
	}



	/**
	 * Send the information about all the custom fields for this organisation
	 * @return unknown_type
	 */
	function getcustomfieldsApiAction(){

		$org_id = $this->currentorg->org_id;
		$cf = new CustomFields();

		$this->data['custom_fields'] = $cf->getCustomFieldsForApi($org_id);
	}

	/**
	 * Get the values for the custom fields
	 * @return array( array('assoc_id' => 'a1', 'scope' => 'scope', 'field_name' => 'cf1_name', 'field_value' => 'cf1_value'), ... )
	 */
	function getcustomfieldsdataApiAction(){

		$org_id = $this->currentorg->org_id;
		$cf = new CustomFields();
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false))
			$this->data['custom_fields_data'] = $cf->getCustomFieldValuesForApi($org_id);
		else
			$cf->getCustomFieldValuesInFile($org_id,$this->data['xml_file_name']);

	}

	/**
	 * Get the values for the custom fields, for the last "WINDOW days", ie updated in the last few days
	 * @return array( array('assoc_id' => 'a1', 'scope' => 'scope', 'field_name' => 'cf1_name', 'field_value' => 'cf1_value'), ... )
	 */
	function getcustomfieldsdatadeltaApiAction(){

		$org_id = $this->currentorg->org_id;
		$cf = new CustomFields();
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false))
			$this->data['custom_fields_data'] = $cf->getCustomFieldValuesForApi($org_id);
		else
			$cf->getCustomFieldValuesInFile($org_id,$this->data['xml_file_name'],false,true);

	}



	/**
	 * Sets the DND option for a customer
	 */
	function unsubscribeApiAction(){

		$xml_string = <<<EOXML
<root>
	<customer_dnd_options>
		<customer_dnd>
			<customer_id>6510</customer_id>
			<email_dnd_option>NONE</email_dnd_option>
			<mobile_dnd_option>NOBULK</mobile_dnd_option>
		</customer_dnd>
		<customer_dnd>
			<customer_id>6273</customer_id>
			<email_dnd_option>NOBULK</email_dnd_option>
			<mobile_dnd_option>NOPERSONALIZED</mobile_dnd_option>
		</customer_dnd>
	</customer_dnd_options>
</root>
EOXML;

		//$element = Xml::parse($xml_string);
		$xml_string = $this->getRawInput();
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		$element = Xml::parse($xml_string);

		$org_id = $this->currentorg->org_id;

		$dnds = $element->xpath('/root/customer_dnd_options/customer_dnd');

		$response = array();

		foreach($dnds as $dnd){
			
			$this->logger->debug("DND options : ".print_r($dnd));

			$user_id = (string) $dnd->customer_id;
			
			$survivor_user_id=UserProfile::checkVictimAccount($user_id,true,true);
			if($survivor_user_id)
				$user_id=$survivor_user_id;
			
			$email_dnd_option = (string) $dnd->email_dnd_option;
			$mobile_dnd_option = (string) $dnd->mobile_dnd_option;

			//TODO
			//the client exchanges the fields and sends it
			//change with version two api
			//TODO-DONE	: Ticket:14173
			//client sends correct params by passing is_new_api
			try {
				if(isset($dnd->is_new_api))
					$res = $this->loyaltyController->setDNDForUser($user_id, $email_dnd_option, $mobile_dnd_option);
				else
					$res = $this->loyaltyController->setDNDForUser($user_id, $mobile_dnd_option, $email_dnd_option);
			} catch (Exception $e) {
				$res = false;
				$this->logger->debug("Updating subscription service failed with errors ". $e->getMessage());
			}
				
			$ret = '';
			if($res){
				$status = "Success";
				$ret = ERR_LOYALTY_SUCCESS;
			} else {
				$status = "Unable to set";
				$ret = ERR_LOYALTY_UNKNOWN;
			}

			//Add the response for the new clients
			$dnd_status = array(
					'key' => $this->getResponseErrorKey($ret),
					'message' => $this->getResponseErrorMessage($ret)
			);

			array_push($response, array('customer_id' => $user_id, 'status' => $status, 'item_status' => $dnd_status));
		}

		$this->data['responses'] = $response;

	}

	/**
	 * Initially made for indian terrain.
	 *
	 * @param $mobile
	 * @param $email
	 */
	public function getpurchasehistoryforcustomerApiAction( $mobile = false, $email = '', $pin = '', $check_pin = true ){

		$this->logger->debug("starting getpurchasehistoryforcustomer $mobile $email $pin");
		$am = new AdministrationModule();
		$org_id = $this->currentorg->org_id;

		//making this change for VTU
		//the mobile field might contain the external id as well
		$user = UserProfile::getByExternalId($mobile);

		if(!Util::checkMobileNumber($mobile) && !Util::checkEmailAddress($email) && !$user){
			$this->logger->error("Both mobile and email are invalid..returning");
			$this->data['api_status'] = array(
					'key' => 'ERR_BOTH_MOBILE_EMAIL_INVALID',
					'message' => 'Both mobile and email are invalid'
			);
			$this->data['bills'] = '';
			return;
		}

		/**
		 * Need to make the verification of the pin code as a configuration
		 * For now leaving it as it is
		 */

		if($org_id == 72){
			if($check_pin && (!$pin || !ValidationPin::verifyPin($mobile, $email, $pin))){
				$this->logger->error("Pin validation failed");
				$this->data['api_status'] = array(
						'key' => 'ERR_INVALID_PIN',
						'message' => 'Invalid pin'
				);
				$this->data['bills'] = '';
				return;
			}
		}

		if( $mobile ){

			$key = 'id';
			$loyalty_details = $this->loyaltyController->getLoyaltyCustomerByMobile( $mobile );

			if(!$loyalty_details) //trying to load by external id
			{
				$key = 'user_id';
				$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByExternalId($mobile, $this->currentorg->org_id);
			}
		}else if ( $email ){

			$key = 'user_id';
			$loyalty_details = $this->loyaltyController->getLoyaltyDetailsByEmail( $email, $org_id );
		}

		if( !is_array( $loyalty_details )  )
			$response_code = ERR_LOYALTY_USER_NOT_REGISTERED;

		$user_id = $loyalty_details[$key];
		if( $user_id ){

			$response_code = 1;
			$store_details = $am->getStoresAsOptions( true, array('org', 'ctr'), true );
			$store_details = array_flip( $store_details );

			$bills = $this->loyaltyController->getBillDetails( false, false, $user_id );
			$response = array();

			foreach ( $bills as $b ){

				$bill_details = array();

				$i = 1;
				$bill_id = $b['id'];
				$lineitems = $this->loyaltyController->getBillLineitemDetails( $bill_id );
				$entered_by = $b['entered_by'];

				$bill_details['bill_number'] = $b['bill_number'];
				$bill_details['amount'] = $b['bill_amount'];
				$bill_details['date'] = $b['date'];
				$bill_details['store']	= $store_details[$entered_by];
				$bill_details['points_awarded'] = $b['points'];
				$bill_details['points_redeemed'] = $b['redeemed'];

				$line_item = array(); $item_codes = array();
				foreach( $lineitems as $lt ){
					//$line_item['attribute_'.$i++] = $lt['description'];
					$item_codes[] = "'" . $lt['item_code'] . "'";
					$line_item[] = array('item_code' => $lt['item_code'], 'rate' => $lt['amount'], 'quantity' => $lt['qty'], 'description' => $lt['description']);
				}

				$item_attributes = $this->loyaltyController->getAttributesForItems($item_codes);

				foreach($line_item as &$lt){
					$lt['attributes'] = $item_attributes[$lt['item_code']];

					$lt['attributes_mapping'] = array();
					if(is_array($lt['attributes']) &&  count($lt['attributes']) > 0)
						foreach($lt['attributes'] as $aname => $aval)
						array_push($lt['attributes_mapping'], array('name' => $aname, 'value' => $aval));
				}

				$bill_details['line_items'] = array();
				foreach($line_item as $l)
					array_push( $bill_details['line_items'], $l );

				array_push( $response, $bill_details );
			}
		}
		$key = ($response_code > 0) ? ERR_LOYALTY_SUCCESS : $response_code;
		$item_status = array(

				'key' => $this->getResponseErrorKey($key),
				'message' => $this->getResponseErrorMessage($key)
		);

		$this->data['item_status'] = $item_status;
		$this->data['bills'] = $response;
	}

	/**
	 *
	 * @param $user_id
	 */
	function purchasehistoryApiAction($user_id){

		$org_id = $this->currentorg->org_id;
		
		$survivor_user_id=UserProfile::checkVictimAccount($user_id,true,true);
		if($survivor_user_id)
			$user_id=$survivor_user_id;

		$is_slab_enabled = $this->currentorg->getConfigurationValue(CONF_LOYALTY_ENABLE_SLABS, false);
		$widgets = array();

		//Add customer details as a widget
		$customer_table = new Table();
		$customer_details = $this->loyaltyController->getLoyaltyDetailsForUserID($user_id);

		$expiry_points_enabled = $this->currentorg->getConfigurationValue(ENABLE_EXPIRY_POINTS_FOR_PURCHASE_HISTORY, false);
		if($expiry_points_enabled)
			$this->loyaltyController->getCustomerExpiryDetails( $user_id, $widgets );


		$loyalty_points_enabled = $this->currentorg->getConfigurationValue(ENABLE_LOYALTY_POINTS_FOR_PURCHASE_HISTORY, true);
		if($loyalty_points_enabled)
			$customer_table_details['current_points'] = $customer_details['loyalty_points'];

		$lifetime_points_enabled = $this->currentorg->getConfigurationValue(ENABLE_LIFETIME_POINTS_FOR_PURCHASE_HISTORY, true);
		if($lifetime_points_enabled)
			$customer_table_details['lifetime_points'] = $customer_details['lifetime_points'];

		$lifetime_purchase_enabled = $this->currentorg->getConfigurationValue(ENABLE_LIFETIME_PURCHASE_FOR_PURCHASE_HISTORY, true);
		if($lifetime_purchase_enabled)
			$customer_table_details['lifetime_purchases'] = $customer_details['lifetime_purchases'];

		$joied_date_enabled = $this->currentorg->getConfigurationValue(ENABLE_JOINED_DATE_FOR_PURCHASE_HISTORY, true);
		if($joied_date_enabled)
			$customer_table_details['joined'] = $customer_details['joined'];

		if($is_slab_enabled)
			$customer_table_details['slab'] = $customer_details['slab_name'];

		$customer_table->importArray( array( $customer_table_details ) );

		$widget = array(
				'widget_name' => "Customer Details",
				'widget_code' => $user_id,
				'widget_data' => $customer_table
		);
		array_push($widgets, $widget);


		if(Util::isBCBGorg($this->currentorg->org_id))
		{
			$points_client = PointsEngineServiceThriftClientFactory::
						getPointsEngineServiceThriftClient();
			$response_list = $points_client->getCustomerPointsSummaries($this->currentorg->org_id, $user_id);
			$display = array();
			$i = 0;
			foreach($response_list as $summary)
			{
				//++$i;
				$points = floor($summary->currentPoints);
				$category_name = $summary->pointCategoryName;
				$display[$category_name] = $points;
			}

			$points_table = new Table();
			$points_table->importArray(array($display));

			$widget = array(
					'widget_name' => "Category Wise Points",
					'widget_code' => "$user_id.points",
					'widget_data' => $points_table
			);
			array_push($widgets, $widget);
		}


		$res = $this->loyaltyController->getPurchaseHistoryForApi($user_id, true);

		$table = new Table();
		$table->importArray($res);

		$widget = array(

				'widget_name' => "Customer Bill Details",
				'widget_code' => $user_id.$org_id,
				'widget_data' => $table
		);

		array_push($widgets, $widget);

		if( $this->currentorg->getConfigurationValue(SHOW_REDEMPTIONS_FOR_PURCHASE_HISTORY, false) ){

			$loyalty_id = $customer_details['id'];

			$redemptions = $this->loyaltyController->getlastfewRedemtion( $loyalty_id, 'query' );
			$redemptions_table = new Table();
			$redemptions_table->importArray( $redemptions );
			$redemptions_table->reorderColumns(
					array(
							'store',
							'bill_number',
							'voucher_code',
							'points_redeemed',
							'date',
							'notes'
					)
			);

			$widget = array(

					'widget_name' => "Customer Redemption Details",
					'widget_code' => $user_id.$org_id.$loyalty_id,
					'widget_data' => $redemptions_table
			);
				
			array_push($widgets, $widget);
		}


		if( $this->currentorg->getConfigurationValue(SHOW_VOUCHER_REDEMPTIONS_FOR_PURCHASE_HISTORY, false) ){

			$loyalty_id = $customer_details['id'];

			$voucher_redemptions = $this->loyaltyController->getVoucherRedemtionHistory( $user_id, 'query' );
			$voucher_redemptions_table = new Table();
			$voucher_redemptions_table->importArray( $voucher_redemptions );

			$widget = array(

					'widget_name' => "Customer Voucher Redemption Details",
					'widget_code' => $user_id.$org_id.$loyalty_id,
					'widget_data' => $voucher_redemptions_table
			);

			array_push($widgets, $widget);
		}

		$am = new AdministrationModule();
		$str = $am->createWidgetToSendMail($widgets);

		$this->data['output'] = $str;
	}

	/*
	 * Api was using get parameters, Will now use POST
	* */
	function fetchCustomerInfoApiAction($mobile = ''){

		global $currentorg;

		/*
		 <root>
		<fetch_customer_info>
		<customer_mobile></customer_mobile>
		<customer_external_id></customer_external_id>
		<customer_email></customer_email>
		</fetch_customer_info>
		</root>
		*/

		$xml_string = $this->getRawInput();
		$user = false;

		if($mobile == '' && strlen($xml_string) > 0){

			//Verify the xml strucutre
			if(Util::checkIfXMLisMalformed($xml_string)){
				$api_status = array(
						'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
						'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
				);
				$this->data['api_status'] = $api_status;
				return;
			}

			//Extract the customer details from the post data
			$element = Xml::parse($xml_string);

			$org_id = $currentorg->org_id;

			$elems = $element->xpath('/root/fetch_customer_info');

			$customer_mobile = "";
			$customer_external_id = "";
			$customer_email = "";
			foreach ($elems as $e)
			{

				$customer_mobile = (string) $e->customer_mobile;
				$customer_external_id = (string) $e->customer_external_id;
				$customer_email = (string) $e->customer_email;

				break;
			}
				
			$input = array();
			if( isset($customer_mobile) && strlen($customer_mobile) > 0 )
				$input['mobile'] = $customer_mobile;
			else if( isset($customer_external_id) && strlen($customer_external_id) > 0 )
				$input['external_id'] = $customer_external_id;
			else if( isset($customer_email) && strlen($customer_email) > 0 )
				$input['email'] = $customer_email;
			else
				$input = null;
			$user = null;
				
			if(is_array($input)){
				/*$flow = new CustomerFlow($input);

				$user = $flow->get($get = true);*/
				$controller = new ApiCustomerController($input);
				try{
					$user = $controller->getCustomers($input,$get = true);
				}catch(Exception $e){
				}
			}
				
			/*			Already Exist Code
			 //Try to fetch the customer using either one of the info
			//Try to fetch by mobile
			if(!$user && (strlen($customer_mobile) > 0))
			{
			$user = UserProfile::getByMobile($customer_mobile);
			}

			//try to fetch by external id
			if(!$user && (strlen($customer_external_id) > 0))
				$user = UserProfile::getByExternalId($customer_external_id);

			//try to fetch by external id
			if(!$user && (strlen($customer_email) > 0))
				$user = UserProfile::getByEmail($customer_email);


			}else
				$user = UserProfile::getByMobile($mobile);
			*/
		}
		if(!$user) {
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_FAILURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_FAILURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$user_id = $user->user_id;

		$e = new ExtendedUserProfile($user,$currentorg);
		$fullname = $e->getFullName();
		$first_name = $e->getFirstName();
		$last_name = $e->getLastName();
		$email = $e->getEmail();
		$mobile = $e->getMobile();

		$customer_details = $this->loyaltyController->getLoyaltyDetailsForUserID($user_id);
		$customer_cf_data = $this->loyaltyController->getCustomFieldDataForJavaClient($user_id);

		$customer_info_string = "Name: $first_name $last_name".'\n'." M: $mobile".'\n '."oints: ".
				$customer_details['loyalty_points'].' \n '."Lifetime Purchases: ".
				$customer_details['lifetime_purchases'];

		$customer_data = array( 'fullname' => "$first_name $last_name",
				'mobile' => $mobile,
				'email' => $email,
				'loyalty_id' => $customer_details['id'],
				'external_id' => $customer_details['external_id'],
				'slab_name' => $customer_details['slab_name'],
				'slab_number' => $customer_details['slab_number'],
				'total_points' => $customer_details['loyalty_points'],
				'current_points' => $customer_details['loyalty_points'],
				'lifetime_points' => $customer_details['lifetime_points'],
				'lifetime_purchases' => $customer_details['lifetime_purchases']);

		$customer_info = array_merge($customer_data, $customer_cf_data);

		$billing_info_string = $this->loyaltyController->getPurchaseHistoryForJavaClient($user_id, true);

		$vouchers = $this->loyaltyController->getVouchersOfUserForJavaClient($user_id);


		$customer_info = array('customer_id' => $user_id, 'customer_info_string' => $customer_info_string, 'customer' => $customer_info, 'billing_info_string' => $billing_info_string, 'vouchers' => $vouchers);

		$this->data['customer_info'] = $customer_info;
	}


	function getCustomerInfoApiAction(){

		//Mobile is sent with country code, so the input data might be 'modified'
		//External id and email is sent as is

		$xml_string = <<<EOXML
<root>
	<customer_id>4</customer_id>
	<fetch_query>XXA8484SJ</fetch_query>
	<fetch_query_mobile>919916649042</fetch_query_mobile>
</root>
EOXML;
		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string);

		$customer = $element->xpath('/root/customer_id');

		$user_id = $customer[0][0];

		global $currentorg;

		$user = false;

		if($user_id > 0)
			$user = UserProfile::getById($user_id);

		//Try to search by mobile
		$fetch_query_mobile = $element->xpath('/root/fetch_query_mobile');
		$customer_mobile = $fetch_query_mobile[0][0];
		if(!$user && (strlen($customer_mobile) > 0))
		{
			$user = UserProfile::getByMobile($customer_mobile);
		}


		$fetch_query = $element->xpath('/root/fetch_query');
		$customer_external_id = $fetch_query[0][0];
		//try to fetch by external id
		if(!$user && (strlen($customer_external_id) > 0))
			$user = UserProfile::getByExternalId($customer_external_id);

		//try to fetch by email
		$customer_email = $fetch_query[0][0];
		if(!$user && (strlen($customer_email) > 0))
			$user = UserProfile::getByEmail($customer_email);


		if(!$user) {
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_FAILURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_FAILURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$e = new ExtendedUserProfile($user,$currentorg);
		$fullname = $e->getFullName();
		$first_name = $e->getFirstName();
		$last_name = $e->getLastName();
		$email = $e->getEmail();

		$user_id = $e->getUserID();

		$customer_details = $this->loyaltyController->getLoyaltyDetailsForUserID($user_id);
		$customer_cf_data = $this->loyaltyController->getCustomFieldData($user_id);
		$vouchers = $this->loyaltyController->getVouchersOfUser($user_id);

		$sql = "SELECT status FROM users_ndnc_status WHERE user_id = $user_id AND org_id = $user->org_id";
		$ndnc = 0; //unknown

		$db = new Dbase('users');
		$ndnc = $db->query_scalar($sql);

		$ndnc = ($ndnc != 'NDNC') ? 0 : 1;

		$customer_data = array( 'firstname' => $first_name,
				'lastname' => $last_name,
				'user_id' => $user_id,
				'email' => $email,
				'mobile' => $user->mobile,
				'loyalty_id' => $customer_details['id'],
				'external_id' => $customer_details['external_id'],
				'slab_name' => $customer_details['slab_name'],
				'slab_number' => $customer_details['slab_number'],
				'total_points' => $customer_details['loyalty_points'],
				'lifetime_points' => $customer_details['lifetime_points'],
				'lifetime_purchases' => $customer_details['lifetime_purchases'],
				'registered_date' => $customer_details['joined'],
				'last_visit' => $customer_details['last_updated'],
				'custom_fields_data' => $customer_cf_data,
				'vouchers' => $vouchers,
				'ndnc_enabled' => $ndnc,
				'profile_image' => 'http://intouch.capillary.co.in/images/naruto.jpg',
				'dob' => '1985-01-24',
				'gender' => $e->getSex(),
				'address' => $e->getAddress(),
				'next_slab' => array(
						'name' => 'PLATINUM',
						'short_message' => "Spend Rs. 5000/- to upgrade to PLATINUM",
						'long_message' => "10% Cash back on all purchases\n5% additional discount during sales\nFree gifts worth Rs. 500/-" )
		);


		if( $this->currentorg->isFamilyEnabled() ){

			$family = Family::getByMember( $user_id );
			$family_head = $family->family_head;

			if( $family_head > 0 ){

				$family_details = $this->loyaltyController->getLoyaltyDetailsForUserID( $family_head );
				$customer_data['family_points'] = $family_details['loyalty_points'];
			}else
				$customer_data['family_points'] = 0;
		}

		$this->data['customer_details'] = $customer_data;

	}

	function updateCustomerInfoApiAction(){

		$xml_string = <<<EOXML
<root>
	<customer_info>
	<customer_id>8689654</customer_id>
	<customer_name>Saurabh Kumar</customer_name>
	<mobile>447990081112</mobile>
	<custom_fields_data>
		<custom_field_item>
			<name>age</name>
			<value>24</value>
		</custom_field_item>
		<custom_field_item>
			<name>gender</name>
			<value>Male</value>
		</custom_field_item>
	</custom_fields_data>
	<email>saurabh.kumar@dealhunt.in</email>
	<externalId>blppko0102</externalId>
	</customer_info>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);

		$org_id = $this->currentorg->org_id;

		$customer = $element->xpath('/root/customer_info');
		$customer_info = $customer[0];

		$ret = $this->loyaltyController->updateCustomerForJavaClient($customer_info);

		if(!$ret){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_FAILURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_FAILURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		//store the custom field information for the customers
		$cf = new CustomFields();
		$cf_hash = $cf->getCustomFields($org_id, 'query_hash', 'name', 'id');
		$user_id = (int) $customer_info->customer_id;

		foreach($customer_info->xpath('custom_fields_data/custom_data_item') as $cfd){
			$assoc_id = $user_id;
			$cf_name = (string) $cfd->field_name;
			$cf_value_json = (string) $cfd->field_value;

			$cf_id = $cf_hash[$cf_name];

			//Add/Update the custom field value
			$cf->createOrUpdateCustomFieldData($org_id, $assoc_id, $cf_id, $cf_value_json);
		}
	}

	function purchasehistoryMobileApiAction($user_id){
		$res = $this->loyaltyController->getPurchaseHistoryForMobileApi($user_id, true);
		$this->data['purchase_history'] = $res;
	}

	/**
	 *
	 * @param $user_id
	 * @param $new_mobile ( -1 ; if external id is provided )
	 * @param $new_external_id
	 */
	function mobilechangeApiAction( $user_id, $new_mobile, $new_external_id = '' ){
		
		include_once 'business_controller/workflows/change_requests/ChangeRequestWorkflowHandler.php';

		$status_message = "";
		$ret = true;
		$status_msg = 'Mobile Number';
		$code = ERR_LOYALTY_SUCCESS;
		$change_type = 'MOBILE';
		$selected_user = UserProfile::getById($user_id);
		if(!$selected_user){

			$ret = false;
			$status_message = "Invalid User Id";
			$code = ERR_USER_NOT_REGISTERED;
		}
		if( $new_mobile != -1 ){
			
			if( !Util::checkMobileNumber($new_mobile) ){
				
				$ret = false;
				$status_message = "Invalid Mobile number";
				$code = ERR_LOYALTY_INVALID_MOBILE;
			}elseif( $selected_user->mobile == $new_mobile ){

				$ret = false;
				$status_message = "Mobile number provided already exists for some other user";
				$code = ERR_DUPLICATE_MOBILE;
			}
		}elseif( $new_mobile == -1 ){

			$change_type = 'EXTERNAL_ID';
			$status_msg = 'External Id ';
			$new_mobile = $new_external_id;

			//support client configs
			$cfm=new ConfigManager();
			$min_length=$cfm->getKey('CONF_CLIENT_EXTERNAL_ID_MIN_LENGTH');
			$max_length=$cfm->getKey('CONF_CLIENT_EXTERNAL_ID_MAX_LENGTH');
			if(strlen($new_external_id)<$min_length || ($max_length!=0 && strlen($new_external_id)>$max_length))
			{
				$ret = false;
				$status_message = "Invalid External ID";
				$code = ERR_LOYALTY_INVALID_EXTERNAL_ID;
			}
				
		}

		//Check if we are allowed to redeem by the customer
		$disallow_fraud_statuses = json_decode($this->currentorg->getConfigurationValue(CONF_FRAUD_STATUS_CHECK_MOBILE_CHANGE, json_encode(array())), true);
		//get the fraud status for the customer
		$customer_fraud_status = $this->loyaltyController->getFraudStatus($user_id);
		if(count($disallow_fraud_statuses) > 0 && strlen($customer_fraud_status) > 0 && in_array($customer_fraud_status, $disallow_fraud_statuses)){

			$ret = false;

			$status_message = "Cannot change $status_msg for customers with fraud status";
			$code = ERR_LOYALTY_FRAUD_USER;
		}

		if($ret){

			try{
				if( $change_type == 'MOBILE' ){

					/* Changing this logic, and plcaing request in Change request workflow
					 $ret = $this->loyaltyController->addMobileChangeRequest($selected_user, $selected_user->mobile, $new_mobile );
				 */
					//Placing Customer Identity Change Request in Change Request workflow


					$this->logger->debug( "Placing a $change_type change request for user :".$selected_user->user_id );
					$this->logger->debug( "$change_type change request from $selected_user->mobile to $new_mobile" );
					//$change_request_workflow = new ChangeRequestWorkflowHandler();
					try{
						$old_mobile = $selected_user->mobile;
						$req_controller=new ApiRequestController();
						$payload=array(
										'type'=>'CHANGE_IDENTIFIER',
										'base_type'=>strtoupper($change_type),
										'user_id'=>$selected_user->user_id,
										'old_value'=>$old_mobile,
										'new_value'=>$new_mobile,
									  );
						$req_controller->addRequest($payload);
						$code = ERR_LOYALTY_SUCCESS;
						/*$status = $change_request_workflow->addRequest( $selected_user->user_id, $new_mobile, $change_type );
						$this->logger->debug( "Change Request Workflow result: ".print_r( $status, true ) );
						$code = $status[1];
						if( $status[0] == 'SUCCESS' && $status[1] == ERR_LOYALTY_SUCCESS ){
						*/
						$stat = true;
						$this->logger->debug( "$change_type change request added successfully" );
						//}else{
						//	$stat = false;
							//$this->logger->error( "Failed to add $change_type change request" );
							//$this->logger->error( "$change_type change request status : ".(
								//	ErrorMessage::$customer[$status[1]] ? ErrorMessage::$customer[$status[1]] : $status[1] ) );
						//}	
					} catch (Exception $e){
						$stat = false;
						$code = ERR_LOYALTY_UNKNOWN;
						$this->logger->error( "Failed to add $change_type change request" );
						//$this->logger->error( "$change_type change request status : ".(
							//ErrorMessage::$customer[$status[1]] ? ErrorMessage::$customer[$status[1]] : $status[1] ) );
					}
					
				}elseif( $change_type == 'EXTERNAL_ID' ){

					$customer_exists = $this->loyaltyController->getLoyaltyDetailsByExternalId( $new_external_id, $this->currentorg->org_id );
					if( $customer_exists['id'] ){

						$status = "External has already been assigned to other user";
						$code = ERR_DUPLICATE_EXTERNAL_ID;
					}else{
						try{
							
						$old_external_id=UserProfile::getExternalId($selected_user->user_id);

						/*$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForUserID( $user_id );

						$external_id = $loyalty_details['external_id'];
						Changing this logic, and plcaing request in Change request workflow
						 $ret = $this->loyaltyController->addMobileChangeRequest($selected_user, $external_id, $new_external_id, $change_type, $status_msg );
						*/
							
						$this->logger->debug( "Placing a $change_type change request for user :".$selected_user->user_id );
						$this->logger->debug( "$change_type change request from $selected_user->external_id to $external_id" );
						//$change_request_workflow = new ChangeRequestWorkflowHandler();
						//$status = $change_request_workflow->addRequest( $selected_user->user_id, $new_external_id, $change_type );
						$req_controller = new ApiRequestController();
						$payload=array(
										'type'=>'CHANGE_IDENTIFIER',
										'base_type'=>strtoupper($change_type),
										'user_id'=>$selected_user->user_id,
										'old_value'=>$old_external_id,
										'new_value'=>$new_external_id,
									  );
						$req_controller->addRequest($payload);
						$this->logger->debug( "Change Request Workflow result: ".print_r( $status, true ) );
						$code = ERR_LOYALTY_SUCCESS;
						//if( $status[0] == 'SUCCESS' && $status[1] == ERR_LOYALTY_SUCCESS ){
								
							$stat = true;
							/*$this->logger->debug( "$change_type change request added successfully" );
						}else{
								
							$stat = false;
							$this->logger->error( "Failed to add $change_type change request" );
							$this->logger->error( "$change_type change request status : ".(
									ErrorMessage::$customer[$status[1]] ? ErrorMessage::$customer[$status[1]] : $status[1] ) );
						}*/
					} catch (Exception $e){
						$stat = false;
						$code = 'Error while adding change request for External ID';
					}
					}

				}
			}catch( Exception $e ){
					
				$this->logger->error( "Caught Exception while placing $change_type change request in Change Request Workflow");
				$this->logger->error( "Exception :".$e->getMessage() );
				$stat = false;
			}

			if(!$stat){
				$status_message = "Unable to make request";
// 				$code = ERR_LOYALTY_UNKNOWN;
			}
			else
				$status_message = 'Change successful';
		}

		//Add the response for the new clients
		$change_status = array(
				'key' => $this->getResponseErrorKey($code) ? 
							$this->getResponseErrorKey($code) : $code,
				'message' => $this->getResponseErrorMessage($code) ? 
							$this->getResponseErrorMessage($code) : ErrorMessage::$customer[$code],
		);

		$this->logger->debug("Mobile Change Request status : $status");

		$this->data['api_status'] = $change_status;
		$this->data['output'] = $status_message;
	}

	private function transformReturnBillInputData($xml_string){
		$element = Xml::parse($xml_string);
		$elems = $element->xpath('/root/returned_bills/bill');
		$transactions = array();
		foreach($elems as $key => $val)
		{
			$bill = array();
            $bill['loyalty_id'] = intval($val->loyalty_id);
            $bill['user_id'] = intval($val->user_id);
				
			$bill['customer'] = array();
			$bill['customer']['mobile'] =(string) $val->customer_mobile;
			$bill['customer']['external_id'] = (string)$val->customer_external_id;
			$bill['customer']['email'] = (string)$val->customer_email;
				
			$bill['transaction_number'] = (string)$val->bill_number;
			$bill['amount'] = (double)$val->amount;
			$bill['credit_note'] = (string)$val->credit_note;
			$bill['billing_time'] = (string)$val->returned_time;
				
			$bill['lineitems']['lineitem'] = array();
			$xml_line_items = $val->xpath("bill_line_items/bill_line_item");
			foreach($xml_line_items as $key => $xml_line_item )
			{
				$this->logger->debug("LineItem[$key] => ".print_r($xml_line_item,true));
				$line_item = array();
				$line_item['item_code'] = (string)$xml_line_item->item_code;
				$line_item['qty'] = (integer)$xml_line_item->quantity;
				$line_item['rate'] = (double)$xml_line_item->rate;

				array_push($bill['lineitems']['lineitem'], $line_item);
			}
			
			$associate_details = $val->xpath("associate_details");
			if(is_array($associate_details) && is_object($associate_details[0]))
			{
				$bill['associate_details']['code'] = (string) $associate_details[0]->code;
				$bill['associate_details']['name'] = (string) $associate_details[0]->name;
			}
			
			array_push($transactions, $bill);
		}
		return $transactions;
	}

	function returnbillApiAction(){

		$xml_string = <<<EOXML
<root>
	<returned_bills>
		<bill>
			<loyalty_id>2</loyalty_id>
	        <customer_mobile>447990081111</customer_mobile>
	        <customer_external_id>600</customer_external_id>
	        <customer_email>saurabh.kumar@dealhunt.in</customer_email>
			<bill_number>N8883</bill_number>
			<amount>600.50</amount>
			<credit_note>C-123</credit_note>
			<returned_time>2010-04-06T09:52Z</returned_time>
			<bill_line_items>
				<bill_line_item>
					<item_code>21</item_code>
					<quantity>1</quantity>
					<rate>100</rate>
				</bill_line_item>
				<bill_line_item>
					<item_code>22</item_code>
					<quantity>1</quantity>
					<rate>100</rate>
				</bill_line_item>
			</bill_line_items>
			<associate_details>
	       		<code></code>
	       		<name></name>
			</associate_details>
		</bill>
		<bill>
			<loyalty_id>6</loyalty_id>
			<customer_mobile>447990081111</customer_mobile>
            <customer_external_id>600</customer_external_id>
            <customer_email>saurabh.kumar@dealhunt.in</customer_email>
			<bill_number>N8786</bill_number>
			<amount>800</amount>
			<credit_note>C-456</credit_note>
			<returned_time>2010-04-06T09:52Z</returned_time>
		</bill>
		
	</returned_bills>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string)	;
		$bills = $this->transformReturnBillInputData($xml_string);
		
		global $gbl_item_count, $gbl_item_status_codes;
		$gbl_item_count = count($bills);

		$arr_item_status_codes = array();
		$responses = array();

		foreach($bills as $b){
			$response_code = "ERR_LOYALTY_SUCCESS";
				
			try{
				
				$b['type'] = 'return';
				$b['return_type'] = TYPE_RETURN_BILL_AMOUNT;
				$transaction_controller = new ApiTransactionController();
				$t = $transaction_controller->returnBills($b);
				
				$user = $t->getUser();
				
				$points = $t->getPoints();
				$balance = $t->loyaltyController->getBalance($user->loyalty_id);
				$user_id = $user->user_id;
				
			}
			catch(Exception $e)
			{
				$this->logger->error("Loyalty::returnBillApiAction() => ".$e->getMessage());
				$response_code = $e->getMessage();
			}
			
			$response_string = ErrorMessage::getTransactionErrorMessage($response_code);
			
			//Add the response for the new clients
			$return_status = array(
					'key' => $response_code,
					'message' => ErrorMessage::getTransactionErrorMessage($response_code)
			);
			
			array_push($responses, array('bill_number' => (string)$b['transaction_number'], 'user_id' => $user->user_id, 
					'loyalty_points_deducted' => $points, 'loyalty_points_balance' => $balance, 'response_code' => $response_code, 
					'response' => $response_string, 'item_status' => $return_status));
			$arr_item_status_codes[] = ErrorCodes::$transaction[$response_code];
		}

		$gbl_item_status_codes = implode(",", $arr_item_status_codes);
		$this->data['responses'] = $responses;
	}


	function returnbillApiActionOld(){

		$xml_string = <<<EOXML
<root>
	<returned_bills>
		<bill>
		    <user_id>1212</user_id>
			<loyalty_id>2</loyalty_id>
	        <customer_mobile>447990081111</customer_mobile>
	        <customer_external_id>600</customer_external_id>
	        <customer_email>saurabh.kumar@dealhunt.in</customer_email>
			<bill_number>N8883</bill_number>
			<amount>600.50</amount>
			<credit_note>C-123</credit_note>
			<returned_time>2010-04-06T09:52Z</returned_time>
			<bill_line_items>
				<bill_line_item>
					<item_code>21</item_code>
					<quantity>1</quantity>
					<rate>100</rate>
				</bill_line_item>
				<bill_line_item>
					<item_code>22</item_code>
					<quantity>1</quantity>
					<rate>100</rate>
				</bill_line_item>
			</bill_line_items>
		</bill>
		<bill>
			<loyalty_id>6</loyalty_id>
			<customer_mobile>447990081111</customer_mobile>
            <customer_external_id>600</customer_external_id>
            <customer_email>saurabh.kumar@dealhunt.in</customer_email>
			<bill_number>N8786</bill_number>
			<amount>800</amount>
			<credit_note>C-456</credit_note>
			<returned_time>2010-04-06T09:52Z</returned_time>
		</bill>
	</returned_bills>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string)	;
		$bills = $element->xpath('/root/returned_bills/bill');
		$responses = array();

		foreach($bills as $b){

			//add each bill as necessary
			$user = false;
			$loyalty_id = $b->loyalty_id;
			if($loyalty_id > 0){
				$user = UserProfile::getById($this->loyaltyController->getUserIdFromLoyaltyId($loyalty_id));
            }

            $user_id = intval($b->user_id);
            $this->logger->debug("Extracted user id: $user_id");
            if($user === false && $user_id > 0){
                $user = UserProfile::getById($user_id);
            }

			//try to extract the user from mobile or email or external id
			//Try to fetch by mobile
			$customer_mobile = $b->customer_mobile;
			if(!$user && (strlen($customer_mobile) > 0))
				$user = UserProfile::getByMobile($customer_mobile);

			//try to fetch by external id
			$customer_external_id = $b->customer_external_id;
			if(!$user && (strlen($customer_external_id) > 0))
				$user = UserProfile::getByExternalId($customer_external_id);

			//try to fetch by external id
			$customer_email = $b->customer_email;
			if(!$user && (strlen($customer_email) > 0))
				$user = UserProfile::getByEmail($customer_email);

			if ($user == false) {
				$response_code = ERR_LOYALTY_USER_NOT_REGISTERED;
			} else {

				$bill_number = (string) $b->bill_number;

				$returned_time = $b->returned_time ? Util::deserializeFrom8601((string) $b->returned_time) : time();
				$returned_time = Util::getMysqlDateTime($returned_time);

				//=======================================================================
				//extract the lineitems
				/*
				<bill_line_item>
				<item_code>21</item_code>
				<quantity>1</quantity>
				<rate>100</rate>
				</bill_line_item>
				*/
				$returned_items = array();
				$line_items = $b->xpath('bill_line_items/bill_line_item');
				foreach ($line_items as $li)
				{
					if(strlen($li->item_code) == 0)
						continue;

					array_push($returned_items,
							array(
									'item_code' => $li->item_code,
									'qty' => $li->quantity,
									'rate' => $li->rate
							)
					);
				}
				//=======================================================================

				$user_id = $user->user_id;
				$amount = round($b->amount);
				$points = $this->loyaltyController->calculatePoints($user, array('amount' => $amount));
				$credit_note = (string) $b->credit_note;
				$log_id = -1;
				$response_code =  $this->loyaltyController->addReturnBill(
						$user_id, $bill_number, $credit_note, $amount, $points,
						$returned_time, $log_id, $returned_items);

				//update points only if response code is positive
				if($response_code == ERR_LOYALTY_SUCCESS){

					//Check if manual return bill is enabled, then send out an email
					if($this->currentorg->getConfigurationValue(CONF_LOYALTY_IS_RETURN_BILL_MANUAL_HANDLING_ENABLED, false))
					{
						$this->loyaltyController->sendReturnBillEmail($user, $bill_number, $amount, $returned_items);
					}
					else
					{

						$points_to_reduce = -1 * $points;
						$amount_to_reduce = -1 * $amount;
						if(!$this->loyaltyController->updateLoyaltyDetails($loyalty_id, $points_to_reduce, $points_to_reduce, $amount_to_reduce, $returned_time, $this->currentuser->user_id))
							$response_code = ERR_LOYALTY_PROFILE_UPDATE_FAILED;
					}

					//populate the supplied data
					$params = array();
					$params['user_id'] = $user_id;
					$params['entered_by'] = $this->currentuser->user_id;
					$params['date'] = $returned_time;
					$params['bill_amount'] = $amount_to_reduce;
					$params['loyalty_log_id'] = $log_id;
					//For all the bill amount trackers .. store a negative amount
					$trackermgr = new TrackersMgr($this->currentorg);
					$trackermgr->addDataForAllBillAmountTrackers($params);
				}

			}

			$response_string = $this->getResponseErrorMessage($response_code);
			$balance = $this->loyaltyController->getBalance($loyalty_id);
			//Add the response for the new clients
			$return_status = array(
					'key' => $this->getResponseErrorKey($response_code),
					'message' => $this->getResponseErrorMessage($response_code)
			);
			array_push($responses, array('bill_number' => (string)$b->bill_number, 'user_id' => $user->user_id, 
					'loyalty_points_deducted' => $points, 'loyalty_points_balance' => $balance, 'response_code' => $response_code, 
					'response' => $response_string, 'item_status' => $return_status));
		}

		$this->data['responses'] = $responses;
	}


	/*
	 */
	function redeemPointsBySMSAction($mobile, $org_id, $argument){

		//argument is <points_to_redeem> <store_id>
		$argument_splits = StringUtils::strexplode(' ', $argument);

		$points_to_redeem = $argument_splits[0];
		$store_id = $argument_splits[1];

		$user = UserProfile::getByMobile($mobile);

		//get the store profile for the store id and check if it belongs to the org
		$store = UserProfile::getById($store_id);

		if($this->currentorg->getConfigurationValue(
				CONF_VALIDATION_IS_STORE_ID_INCLUDED, false))
			$this->currentuser = $store;

		if($store->org_id != $org_id &&
				$this->currentorg->getConfigurationValue(
						CONF_VALIDATION_IS_STORE_ID_INCLUDED, false)
		)
			$msg = "Invalid Store. Store Does not belong to ".$this->currentorg->name;
		else{
			if($user){
				$user_id = $user->user_id;
				$loyalty_id =
				$this->loyaltyController->getLoyaltyId($org_id, $user_id);
				$loyalty_details =
				$this->loyaltyController->getLoyaltyDetailsForLoyaltyID(
						$loyalty_id);
			}else
				$loyalty_details['loyalty_points'] = 0;

			if($points_to_redeem > $loyalty_details['loyalty_points'])
				$msg =  "Cannot Redeem, Current points is ".$loyalty_details['loyalty_points'];
			else{

				$vc = new ValidationCode();
				$additional_bits = 0;
				if($this->currentorg->getConfigurationValue(
						CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
					$additional_bits = $points_to_redeem;
				$code = $vc->issueValidationCode($this->currentorg,
						$user->mobile, UserProfile::getExternalId($user->user_id),
						VC_PURPOSE_REDEMPTION, time(), $this->currentuser->user_id,
						($additional_bits + BITS_FOR_OFFLINE_MODE));

				$ret = $this->loyaltyController->redeemPoints(
						$user, $loyalty_id, $points_to_redeem, '',
						$code, '', $store_id, '', true);

				if($ret != ERR_LOYALTY_SUCCESS){
					$msg = "Redemption failed. ".$this->getResponseErrorMessage($ret);
				}else{
					$msg = "$points_to_redeem points have been redeemed. Your code is $code";
				}
			}
		}
		
		$user_id = -1;
		if(is_object($user))
		{
			$user_id = $user->user_id;
		}
		
		Util::sendSms($mobile, $msg, $org_id, MESSAGE_PRIORITY,
						false, '', false, false, array(),
						$user_id, $user_id, 'POINTS' );
	}


	function redeemPointsOfflineAction($mobile, $org_id, $argument){

		//argument is <points_to_redeem> <store_id>
		$argument_splits = StringUtils::strexplode(' ', $argument);

		$points_to_redeem = $argument_splits[0];
		$store_id = $argument_splits[1];

		$user = UserProfile::getByMobile($mobile);

		//get the store profile for the store id and check if it belongs to the org
		$store = UserProfile::getById($store_id);

		if($this->currentorg->getConfigurationValue(CONF_VALIDATION_IS_STORE_ID_INCLUDED, false))
			$this->currentuser = $store;

		if($store->org_id != $org_id && $this->currentorg->getConfigurationValue(CONF_VALIDATION_IS_STORE_ID_INCLUDED, false))
			$msg = "Invalid Store. Store Does not belong to ".$this->currentorg->name;
		else{
			if($user){
				$user_id = $user->user_id;
				$loyalty_id = $this->loyaltyController->getLoyaltyId($org_id, $user_id);
				$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForLoyaltyID($loyalty_id);
			}else
				$loyalty_details['loyalty_points'] = 0;

			if($points_to_redeem > $loyalty_details['loyalty_points'])
				$msg =  "Cannot Redeem, Current points is ".$loyalty_details['loyalty_points'];
			else{

				//Check if the points are redeemable
				$ret = $this->loyaltyController->isPointsRedeemable($user, $loyalty_id, $points_to_redeem, "OfflineRedeem SMS", '', false);
				if($ret != ERR_LOYALTY_SUCCESS){
					$msg = $this->getResponseErrorMessage($ret);
				}else{
					$vc = new ValidationCode();
					$additional_bits = 0;
					if($this->currentorg->getConfigurationValue(CONF_VALIDATION_INCLUDE_POINTS_IN_REDEMPTION_VALIDATION, false))
						$additional_bits = $points_to_redeem;
					$code = $vc->issueValidationCode($this->currentorg, $user->mobile, UserProfile::getExternalId($user->user_id), VC_PURPOSE_REDEMPTION, time(), $this->currentuser->user_id, $additional_bits);
					$msg = "Your code for $points_to_redeem points redemption is $code";
				}
			}
		}
		
		$user_id = -1;
		if(is_object($user))
		{
			$user_id = $user->user_id;
		}
		Util::sendSms($mobile, $msg, $org_id, MESSAGE_PRIORITY,
						false, '', false, false, array(),
						$user_id, $user_id, 'POINTS' );
	}

	function getfraudusersApiAction(){

		$exclude_status = array('INTERNAL');
		$am = new AdministrationModule();
		$include_status = $am->getAllowedFraudStatuses();
		$this->data['fraud_users'] = $this->loyaltyController->getFraudUsers('api', $exclude_status, $include_status);

	}


	function getcustomerclusterinfoApiAction(){

		$cl_mgr = new ClustersMgr();

		//Cluster meta data
		//convert cluster array to format
		/*
		<attribute>
		<name>MyCluster</name>
		<type>String</type>
		</attribute>
		*/
		$clusters = $cl_mgr->getClusters('query', true);
		$cluster_metadata = array();
		foreach($clusters as $c){
			array_push($cluster_metadata, array('attribute' => array('name' => $c['name'], 'type' => $c['datatype_for_client'])));
		}


		//File name will loaded in api_service.php
		$file_name = $this->data['xml_file_name'];

		//Write the headers to the file
		$this->logger->debug("Using file for Batchwise XML Serialization for Customer Cluster Info: $file_name");

		//Write the headers and the open the root tag
		$fh = fopen($file_name,'w');
		fwrite($fh,"<?xml version='1.0' encoding='ISO-8859-1'?>\n<root>\n");

		//Generate the xml for inventory metadata and write it in to the file
		//Write the opening inventory metadata tag
		fwrite($fh,"<customer_metadata>\n");
		//Write the attribute information to the file
		Util::serializeXMLToFile($cluster_metadata, $fh);
		//Write the closing inventory metadata tag
		fwrite($fh,"</customer_metadata>\n");


		//Get the cluster info for each customer
		//Retrieve the items in batches and convert to xml also in batches
		$all_customer_ids = array();
		if(!$this->currentorg->getConfigurationValue(CONF_CLIENT_IS_CUSTOMER_DYNAMIC_VOUCHERING_ENABLED, false))
		{
			$all_customer_ids = $cl_mgr->getAllCustomerIdsForApi(true);
		}

		$batch_size = 30000;
		$count = 0;
		$total = count($all_customer_ids);
		$batch_customer_ids = array();
		$batch_data = array();



		//Write the opening customer_attributes tag
		fwrite($fh,"<customer_attributes>\n");


		foreach($all_customer_ids as $customer_id){
			$count++;
			array_push($batch_customer_ids, $customer_id);

			if( $count % $batch_size == 0 || $count == $total){

				//get the batch data for the collected item ids
				$cl_mgr->getCustomerClusterAssignmentForApi($batch_data, $batch_customer_ids, true);

				//Write the batch to file
				Util::serializeXMLToFile($batch_data, $fh);

				$this->logger->debug("Serialized upto $count customers cluster info. Batch Size : ".count($batch_data).". Batch number : ".floor($count / $batch_size));

				//clear the batch item ids
				$batch_customer_ids = array();
				$batch_data = array();
			}
		}

		//Write the closing customer_attributes tag
		fwrite($fh,"</customer_attributes>\n");

		//All the data has been generated now close the xml root
		fwrite($fh,"\n</root>");
		fclose($fh);
	}





	function getstoreattributesApiAction(){

		//Store the attributes metadata
		$this->data['store_attributes_metadata'] = $this->loyaltyController->getStoreAttributesForApi();

		//Store the attribute data
		$this->data['store_attributes'] = $this->loyaltyController->getStoreAttributeValuesForApi();

	}


	/**
	 * Gives back the master configurations
	 */
	public function masterconfigurationinfoApiAction(){

		//use file controller
		include_once 'apiController/ApiFileController.php';
		$FileController = new ApiFileController();

		$store_id = $this->currentuser->user_id;

		$output = array();

		//Configuration last updated
		$output['configuration'] = Util::serializeInto8601( $this->currentorg->getLastUpdatedTime() );

		//data providers
		$output['data_providers_file'] = Util::valueOrDefault( $FileController->getDataProviderFileId( $this->currentuser->user_id ), -1 );

		//client log config
		$output['client_log_config_file'] = Util::valueOrDefault( $FileController->getClientLogConfigFileIdForStore($this->currentuser->user_id), -1);

		//printer template
		$printer_templates = array(

				array('tag' => 'printer_dvs_voucher_tpl', 'type' => 'dvs_voucher'),
				array('tag' => 'printer_bill_tpl', 'type' => 'bill'),
				array('tag' => 'printer_customer_tpl', 'type' => 'customer'),
				array('tag' => 'printer_campaign_voucher_tpl', 'type' => 'campaign_voucher'),
				array('tag' => 'printer_points_redemption_tpl', 'type' => 'points_redemption'),
				array('tag' => 'printer_customer_search_tpl', 'type' => 'customer_search')
		);

		foreach($printer_templates as $row){

			$tag = $row['tag'];
			$type = $row['type'];
			$output[$tag] = Util::valueOrDefault( $FileController->getPrinterTemplateFileId( $this->currentuser->user_id, $type ), -1);
		}

		//rules_file
		$rule_packages = array(

				array( 'tag' => 'rules_dvs_issue', 'type' => STORED_FILE_TAG_ISSUE_RULES_PACKAGE ),
				array( 'tag' => 'rules_dvs_redeem', 'type' => STORED_FILE_TAG_REDEEM_RULES_PACKAGE ),
		);

		$am = new AdministrationModule();
		foreach($rule_packages as $row){

			$tag = $row['tag'];
			$type = $row['type'];

			list($latest_version, $latest_file_id) = $am->getLatestRuleInfo($type);

			$file_version = $latest_version;

			$output[$tag] = Util::valueOrDefault($file_version, -1);
		}

		//get the last updated time for custom fields
		$cf_mgr = new CustomFields();
		$output['custom_fields'] = Util::serializeInto8601( $cf_mgr->getCustomFieldsLastModified( $this->currentorg->org_id ) );

		//Inventory Version
		$output['inventory'] = $this->currentorg->getConfigurationValue( CONF_INVENTORY_VERSION, 0 );

		//integration output template
		$integration_templates = $FileController->getIntegrationOutputTemplateTypes();
		foreach($integration_templates as $itype){

			/*
			 * 'type' => STORED_FILE_TAG_INTEGRATION_OUTPUT_POINTS_REDEMPTION,
			* 'key' => CONF_CLIENT_INTEGRATION_OUTPUT__POINTS_REDEMPTION_ENABLED
			* */
			$type = STORED_FILE_TAG_INTEGRATION_OUTPUT_TEMPLATE.'_'.strtolower($itype);

			$itype_to_upper = strtoupper($itype);
			$key = 'CONF_CLIENT_INTEGRATION_OUTPUT_'.$itype_to_upper.'_ENABLED';

			$tag = $type."_tpl";

			if( $this->currentorg->getConfigurationValue($key, false) )
				$output[$tag] = Util::valueOrDefault( $FileController->getIntegrationOutputTemplateFileId( $this->currentuser->user_id, $type ), -1 );
			else
				$output[$tag] = -1;

		}

		//integration post output template
		$integration_post_files = $FileController->getIntegrationPostOutputTypes();
		foreach($integration_post_files as $itype){

			/*
			 * 'type' => STORED_FILE_TAG_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION,
			* 'key' => CONF_CLIENT_INTEGRATION_POST_OUTPUT_POINTS_REDEMPTION_ENABLED
			* */

			$type = STORED_FILE_TAG_INTEGRATION_POST_OUTPUT."_".$itype;
			$tag = strtolower($type)."_file";
			$key = 'CONF_CLIENT_INTEGRATION_POST_OUTPUT_'.strtoupper($itype).'_ENABLED';

			$file_ids_hash = array();
			if($this->currentorg->getConfigurationValue( $key, false ) ){

				$file_ids_hash = $FileController->getPostIntegrationOutputFileIds( $this->currentuser->user_id, $type, 'client_file_name' );
			}

			if(count($file_ids_hash) == 0)
				$file_ids_hash = array('-1' => array( 'filename' => 'NotPresent', 'client_file_monitoring_type' => 'FILE_CHECK'));

			$output_data = array();
			foreach( $file_ids_hash as $file_id => $file_info ){

				$file_name = $file_info['filename'];
				$client_file_monitoring_type = $file_info['client_file_monitoring_type'];

				array_push($output_data,
						array(
								'file_id' => $file_id,
								'file_name' => $file_name,
								'client_file_monitoring_type' => $client_file_monitoring_type
						)
				);
			}

			$output[$tag] = $output_data;
		}

		//Countries modified timestamp
		$output['countries_last_modified'] = Util::serializeInto8601($this->loyaltyController->getCountriesLastModifiedDate());

		//Store Attributes last modified timestamp
		$output['store_attributes_last_modified'] = Util::serializeInto8601($this->loyaltyController->getStoreAttributesLastModifiedDate());

		//customer attributes
		$output['customer_attributes_version'] = $this->currentorg->getConfigurationValue( CONF_CUSTOMER_ATTRIBUTES_VERSION, 1 );

		//Store Server Prefix
		$output['store_server_prefix'] = $this->currentuser->getSSPrefix();

		//Time Zone the Store should use
		$output['time_zone_offset'] = $this->currentuser->getStoreTimeZoneOffset();

		//cron last modified date
		//Set the last modified date
		$clientCronMgr = new ClientCronMgr();
		$output['cron_entries_last_modified'] = $clientCronMgr->getLastModifiedCronEntryDate();

		//Store Tasks Last Modified
		$storeTasksMgr = new StoreTasksMgr();
		$output['store_tasks_last_modified'] = $storeTasksMgr->getStoreTasksLastModified();

		//Task Entries Max Id
		$output['store_tasks_max_entries_id'] = $storeTasksMgr->getStoreTaskEntriesMaxId($store_id);

		//Task Entries Last Modified
		$output['store_tasks_entries_last_modified'] = $storeTasksMgr->getStoreTaskEntriesLastModifiedDate($store_id);

		//Last modified for the purchased features
		$purchMgr = new PurchasableMgr();
		$output['purchased_features_last_modified'] = $purchMgr->getPurchasedFeaturesLastModified();
		
		//Client Test Mode and Debug Level Configuration
		$entity_id = $this->currentuser->user_id;
		$cm = new ConfigManager();
		$output['client_debug_level'] = $cm->getKey( 'CONF_CLIENT_DEBUG_LEVEL' );
		$output['client_test_mode'] = $cm->getKey( 'CONF_CLIENT_TEST_MODE' );
		$output['client_upload_logs'] = $cm->getKey( 'CONF_CLIENT_UPLOAD_LOGS' );

		$this->data['master_configuration'] = $output;
	}

	function getAllowedOutlierStatuses(){
		return array(
				'NORMAL',
				'OUTLIER',
				'INTERNAL',
				'FRAUD'
		);
	}


	public function getCustomerPointsExpiryInfo($user_ids, $org_id){

		//setup the current org
		global $currentorg, $logger;
		$currentorg = new OrgProfile($org_id);
		$this->currentorg = $currentorg;

		$expiry_after_days = $this->currentorg->getConfigurationValue(CONF_LOYALTY_POINTS_EXPIRY_DAYS, 180);

		$hash_of_userid_to_expirytags = array();

		//taking precaution for msging so it doesn't crash
		$default_json['expiry_in_days'] = 'NA';
		$default_json['expiry_date'] = 'NA';
		$default_value = json_encode($default_json);
		foreach($user_ids as $u){
			$hash_of_userid_to_expirytags[$u] = $default_value;
		}

		$result = $this->loyaltyController->getPointExpiryDaysAndDateForUsers($user_ids, $expiry_after_days, $org_id);

		foreach( $result as $r ){

			$json = array();
			$json['expiry_in_days'] = $r['expiry_in_days'];
			$json['expiry_date'] = $r['expiry_date'];

			$hash_of_userid_to_expirytags[$r['user_id']] = json_encode($json);
		}

		$this->logger->debug($hash_of_userid_to_expirytags);

		return $hash_of_userid_to_expirytags;

	}




	function storemissedcallAction($mobile, $org_id, $argument){
		global $logger;
		$mobile_user = UserProfile::getByMobile($mobile);
		$user_id = -1;
		if($mobile_user)
			$user_id = $mobile_user->user_id;

		$logger->debug("Missed Call from $mobile, Org ID : $org_id, User_id : $user_id");
		$this->loyaltyController->storeMissedCallInfo($org_id, $mobile, $user_id);

		if(Util::checkMobileNumber($mobile))
			Util::sendSms($mobile, "Thank you for calling us", $org_id, MESSAGE_PRIORITY, 
							true, '', false, false, array(),
							$user_id, $user_id, 'GENERAL' );

	}

	function getmissedcallnumbersApiAction(){

		$xml_string = <<<EOXML
<root>
	<getmissedcallinfo>
		<after>2010-07-30T09:52Z</after>
	</getmissedcallinfo>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string);

		$response = array();

		$getmissed_calls = $element->xpath('/root/getmissedcallinfo');
		foreach ($getmissed_calls as $g) {
			$after = $g->after ?
			Util::deserializeFrom8601((string) $g->after) : time();

			$after = Util::getMysqlDateTime($after);

			//only one is there
			break;
		}

		$response = $this->loyaltyController->getMissedCallInfo($after);

		$this->data['missed_calls'] = $response;
	}

	function getcountrydetailsApiAction(){

		$oc = new ApiOrganizationController();

		$this->data['countries_details'] = $oc->getSupportedCountriesDetailsAsOptions();
	}

	function uploadperformanceinfoApiAction() {

		$xml_string = <<<EOXML
<?xml version="1.0" encoding="Windows-1252" ?>
<root>
    <performance_counter>
        <client_id>9b2cf86a-a63b-4aad-9166-f354cdb6f3a8</client_id>
        <counter_name>Perform Setup at Startup</counter_name>
        <binary_version>Not_Deployed</binary_version>
        <counter_date>2010-12-20T00:00:00+05:30</counter_date>
        <min_time_taken>198.0113</min_time_taken>
        <max_time_taken>198.0113</max_time_taken>
        <count>1</count>
        <total_time_taken>198.0113</total_time_taken>
    </performance_counter>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		if(Util::checkIfXMLisMalformed($xml_string)){
		
			$this->logger->debug("XML is malformed");
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}
		
		$element = Xml::parse($xml_string);

		$perf_counter_xmls = $element->xpath("/root/performance_counter");

		$res = $this->loyaltyController->insertPerformanceCounter(
				$perf_counter_xmls);
		/*
		 if(!$res) {
		$ret = false;
		$status = "Update of some counters failed";
		$code = ERR_LOYALTY_PERF_COUNTER_UPDATE_FAILED;
		}*/

	}


	public function updateMetroCustomFieldAction ($mobile, $org_id, $argument)
	{
		global $logger;

		$logger->debug("Updating customerconfirmation custom field for $mobile");

		$user = UserProfile::getByMobile ($mobile);

		$user_id = $user->user_id;
		//$loyalty_id = $this->getLoyaltyId($org_id, $user->user_id);
		$cf = new CustomFields();
		$cf_name = "customerconfirmation";
		$value = json_encode(array("true"));

		$cf_input = array(array('field_name' => $cf_name, 'field_value' => $value));
		$updated_count = $cf->addCustomFieldDataForAssocId($user_id, $cf_input);

		/* sends customer sms about his loyalty points */
		$loyalty_details = $user->getLoyaltyDetails();
		$loyalty_points = $loyalty_details['loyalty_points'];
		if($loyalty_points > 0 )
		{
			$message = "Dear member, you have ".$loyalty_points." loyalty points.";
			Util::sendSms($mobile, $message, $org_id, 0, 
							false, '', false, false, array(),
							$user_id, $user_id, 'POINTS' );
		}

		if($updated_count > 0)
		{
			$logger->debug("Updated successfully");
		}
	}


	function getnumberofregistrationsandbillsbystoreApiAction()
	{
		$this->logger->debug("Starting getnumberofregistrationsandbillsbystoreApiAction");

		$xml_string=<<<EOXML
<root>
	<store_id>8730028</store_id>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		if($xml_string){
			$this->logger->debug("Fetched the raw xml input dump: $xml_string");

			if(Util::checkIfXMLisMalformed($xml_string)){

				$this->logger->debug("XML is malformed");
				$api_status = array(
						'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
						'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
				);
				$this->data['api_status'] = $api_status;
				return;
			}

			$this->logger->debug("XML is not malformed..parsing xml");
			$element = Xml::parse($xml_string);
			$this->logger->debug("xml parsed successfully");

			$org_id = $currentorg->org_id;
			$store = $element->xpath('/root');
			$c = $store[0];

			$store_id = $c->store_id;

		}

		$this->logger->debug("Getting Store Bills and Number of Registration for Store Id : ".$store_id);

		list($num_regs_today, $num_bills_today)
		= $this->loyaltyController->GetNumRegsAndBillsTodayForStore( $store_id );

		$this->data['num_regs_today'] = $num_regs_today;
		$this->data['num_bills_today'] = $num_bills_today;
	}

	/**
	 We added this function for Indian Terrain folks
	 The mobile number we are adding 91 if the length of mobile is 10 or less
	 **/

	function addCustomerWebApiAction()
	{
		$this->logger->debug("Starting addCustomerWebApiAction");

		$xml_string=<<<EOXML
<root>
 <customer>
   <name>piyush</name>
   <mobile>9876543210</mobile>
   <email>piyush.goel@capillary.co.in</email>
   <first_name></first_name>
   <last_name></last_name>
   <sex></sex>
   <user_name></user_name>
   <external_id><external_id>
   <age_group></age_group>
   <birthday></birthday>
   <web_registered>true</web_registered>
 </customer>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		$this->logger->debug("Fetched the raw xml input dump: $xml_string");

		if(Util::checkIfXMLisMalformed($xml_string)){

			$this->logger->debug("XML is malformed");
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$this->logger->debug("XML is not malformed..parsing xml");
		$element = Xml::parse($xml_string);
		$this->logger->debug("xml parsed successfully");

		$org_id = $currentorg->org_id;
		$customers = $element->xpath('/root/customer');
		$c = $customers[0];

		//$c->email = '';  //another hack was added here
		//extracting customer info

		$customer_mobile = $c->mobile;
		$customer_email = $c->email;
		$customer_first_name = $c->first_name;
		$customer_last_name = $c->last_name;
		$customer_sex = $c->sex;
		$customer_user_name = $c->user_name;
		$customer_external_id = $c->external_id;
		$customer_age_group = $c->age_group;
		$customer_birthday = $c->birthday;
		$web_registered = $c->web_registered;

		if(trim($c->name) == ''){
			$customer_name = $customer_first_name . ' ' . $customer_last_name;
		}

		//$sms_template = "Thank you for participating in the Predict the Winner Contest. Winners will be contacted through email/ phone";

		$this->logger->debug("Registering customer with name: $customer_name, email: $customer_email, mobile: $customer_mobile");

		//the current API code assumes all numbers come with country and if they dont
		//they are marked as invalid and registration happens with email id. Just adding
		//an extra check
		if(strlen($customer_mobile) <= 10 && preg_match('/^[6-9]/', $customer_mobile)){
			$this->logger->debug("Adding 91 to the country code");
			$customer_mobile = '91' . $customer_mobile;
		}

		/*
		 if($web_registered == 'true' || $web_registered == 'TRUE'){  //should be true
		$this->logger->debug("Customer already registered..sending default sms");
		Util::sendSms($customer_mobile, $sms_template, $this->currentorg->org_id, 1)	;

		$this->data['api_status'] = array(
				'key'=>'ERR_LOYALTY_CUSTOMER_REGISTERED',
				'message'=>'Customer Already Registered'
		);
		$this->data['output'] = "Transaction Successful";
		return;
		}
		*/


		$this->logger->debug("Customer not registered...going with normal flow");

		if(!Util::checkMobileNumber($customer_mobile) && !Util::checkEmailAddress($customer_email)){  //checking mobile validity
			$this->logger->error("Mobile Number $customer_mobile is incorrect and Email address: $customer_email incorrect..sending error code");

			$api_status = array(
			  'key'=>$this->getResponseErrorKey(ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL),
			  'message'=> $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL)
			);
			$this->data['api_status'] = $api_status;
			$this->data['output'] = "Transaction Unsuccessful";
			return;
		}

		//mobile number is valid register him
		$this->logger->debug("Registering customer");
		list($loyalty_id, $user_id, $new_customer) = $this->loyaltyController->registerWebCustomer($customer_mobile, $customer_name, $c);

		$output = "Registered Customer By Mobile/Email";
		$response_code = ERR_LOYALTY_SUCCESS;
		$response_message = $this->getResponseErrorMessage($response_code);
		if(!$user_id)
		{
			$this->logger->error("Error in registering customer");
			$response_code = ERR_LOYALTY_REGISTRATION_FAILED;
			$response_message = $this->getResponseErrorMessage($response_code);
			$output = "Transaction Failed";
		}else
		{

			//if(!$new_customer){
			$this->logger->debug("Customer is old...Firing the loyalty_registration_event...should be handled by the AwardPointsListener");
			/// customer has been registered..hence fire a LoyaltyRegistrationEvent
			$supplied_data['user_id'] = $user_id;
			$supplied_data['store_id'] = $this->currentuser->user_id;

			$ret = $this->lm->signalListeners(EVENT_LOYALTY_REGISTRATION, $supplied_data);
			$this->logger->debug("Signalled the listeners..returning with API status");
		}
		//}

		$this->data['api_status'] = array(
				'key'=>$this->getResponseErrorKey($response_code),
				'message'=>$this->getResponseErrorMessage($response_code)
		);
		$this->data['output'] = $output;
		return;
	}




	function getcustomerprofileApiAction($mobile_no, $email_id = '', $pin_code = '')
	{

		//====================================================================================
		//Validation
		$this->logger->debug("Fetching loyalty details for the customer $mobile_no $email_id $pin_code");
		if(!$mobile_no && !$email_id){
			$this->logger->error("Both mobile and email cannot be empty");

			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey(ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL),
					'message' => $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_MOBILE_AND_EMAIL)
			);
			$this->data['response'] = array();
			return;
		}

		if($mobile_no && !Util::checkMobileNumber($mobile_no)){
			$this->logger->error("Mobile Number is invalid");
			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey(ERR_LOYALTY_INVALID_MOBILE),
					'message' => $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_MOBILE)
			);
			$this->data['response'] = array();
			return;
		}

		if($email_id && !Util::checkEmailAddress($email_id)){
			$this->logger->error("Email Address is invalid");
			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey(ERR_LOYALTY_INVALID_EMAIL),
					'message' => $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_EMAIL)
			);
			$this->data['response'] = array();
			return;
		}

		/**
		 * Verifying the pin code
		 */
		/**
		 * Need to make the verification of the pin code as a configuration
		 * For now leaving it as it is
		 */
		if(!$pin_code || !ValidationPin::verifyPin($mobile_no, $email_id, $pin_code)){
			$this->logger->error("Pin validation failed");
			$this->data['api_status'] = array(
					'key' => 'ERR_INVALID_PIN',
					'message' => 'Invalid pin'
			);
			$this->data['bills'] = '';
			return;
		}


		//Try to get customer either by mobile or email
		$user = false;
		//Try to fetch by mobile
		if(!$user && (strlen($mobile_no) > 0))
			$user = UserProfile::getByMobile($mobile_no);


		//try to fetch by email
		if(!$user && (strlen($email_id) > 0))
			$user = UserProfile::getByEmail($email_id);

		//Check if customer is there or not
		if(!$user){
			$this->logger->error("Customer not registered");
			$this->data['api_status'] = array(
					'key' => $this->getResponseErrorKey(ERR_LOYALTY_USER_NOT_REGISTERED),
					'message' => $this->getResponseErrorMessage(ERR_LOYALTY_USER_NOT_REGISTERED)
			);
			$this->data['response'] = array();
			return;
		}

		//TODO : Validation code checking
		//====================================================================================

		//Send customer info
		$user_id = $user->user_id;
		$loyalty_details = $this->loyaltyController->getLoyaltyDetailsForUserID($user_id);
		$this->data['customer']['firstname'] = $user->first_name;
		$this->data['customer']['lastname'] = $user->last_name;

		/***
		 This is the kachda here !!! Indian Terrain want to update the
		users email id from a getcustomerprofile api call !!! For now
		bluffing them by passing a blank email id as sent with the api call
		****/

		$this->data['customer']['email'] = $email_id;    //$user->email;
		$this->data['customer']['mobile'] = $user->mobile;
		$this->data['customer']['slab_name'] = $loyalty_details['slab_name'];
		$this->data['customer']['loyalty_points'] = $loyalty_details['loyalty_points'];
		$this->data['customer']['lifetime_points'] = $loyalty_details['lifetime_points'];
		$this->data['customer']['lifetime_purchases'] = $loyalty_details['lifetime_purchases'];
		$registered_store = StoreProfile::getById($loyalty_details['registered_by']);
		$this->data['customer']['registered_by'] = $registered_store->getName();
		$customer_cf_data = $this->loyaltyController->getCustomFieldData($user_id);
		//cf_name ==> cf_values_json
		foreach($customer_cf_data as $cf_name => $cf_val_json)
		{
			//Hardcoded cf name checking
			if($cf_name == 'DateofBirth'){
				$this->data['customer']['birthday'] = json_decode($cf_val_json);
				break;
			}

		}
	}

	/**
	 * This API issues a pin code to a user which is valid for a fixed amount of time
	 * as configured by the organization. The pin code is sent by mobile or email to
	 * the customer.
	 *
	 * This api is different than the one above.. above one generates a code using the
	 * mobile no and is time bound using some bit shiftig algo..this one generates a
	 * alphanumeric string and stores in validation_pin table
	 *
	 * @param $mobile
	 * @param $email
	 */

	function generateValidationCodeApiAction($mobile, $email = '')
	{
		$this->logger->debug("issuing validation code for $mobile $email");
		if(!Util::checkMobileNumber($mobile) && !Util::checkEmailAddress($email)){
			$this->logger->error("Both mobile and email are invalid..returning");
			$this->data['api_status'] = array(
					'key' => 'ERR_BOTH_MOBILE_EMAIL_INVALID',
					'message' => 'Both mobile and email are invalid'
			);
			$this->data['validation_code'] = '';
			return;
		}

		$key = 'ERR_RESPONSE_SUCCESS';
		$message = 'Validation code issued';
		try{
			$pin = ValidationPin::generatePin($mobile, $email);
		}catch (Exception $e){
			$this->logger->debug("Error in issuing pin code to the user");
			$key = $e->getMessage();
			$message = $e->getMessage();
			$pin = '';
		}

		$this->data['api_status'] = array(
				'key' => $key,
				'message' => $message
		);
		$this->data['validation_code'] = $pin;
		$this->logger->debug("Returning the pin code");
		return;
	}

	/**
	 * Counter part of the above API..
	 * Catch pigol who named this as verifycode instead of verifyvalidationcode
	 * @param $mobile
	 * @param $email
	 * @param $pin
	 */

	function verifycodeApiAction(){

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $this->logger->debug("Parsing input xml string: $xml_string");
		$e = Xml::parse($xml_string);
		$mobile = $e->mobile;
		$email = $e->email;
		$pin = $e->code;

		$this->logger->debug("Verifying pin for $mobile, $email, $pin");
		if(!Util::checkMobileNumber($mobile) && !Util::checkEmailAddress($email)){
			$this->logger->error("Both mobile and email look invalid...returning");
			$this->logger->error("Both mobile and email are invalid..returning");
			$this->data['api_status'] = array(
					'key' => 'ERR_BOTH_MOBILE_EMAIL_INVALID',
					'message' => 'Both mobile and email are invalid'
			);
			return;
		}

		$this->logger->debug("Verifying pin");
		$key = 'ERR_VERIFICATION_FAILED';
		$message = 'Validation Code Invalid';

		if(ValidationPin::verifyPin($mobile, $email, $pin)){
			$this->logger->debug("Verification code is valid");
			$key = 'ERR_RESPONSE_SUCCESS';
			$message = 'Validation Code Verified';
		}

		$this->data['api_status'] = array(
				'key' => $key,
				'message' => $message
		);
		return;

	}



	function getstorereportbydaterangeApiAction()
	{
		$xml_string = <<<EOXML
<root>
	<from_date>2010-06-13 13:21:24</from_date>
	<to_date>2011-06-19 13:21:24</to_date>
</root>
EOXML;

		$xml_string = $this->getRawInput();

        //Verify the xml strucutre
        if(Util::checkIfXMLisMalformed($xml_string)){
            $api_status = array(
                'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
                'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
            );
            $this->data['api_status'] = $api_status;
            return;
        }

        $element = Xml::parse($xml_string);

		$response = array();

		$report_date_range = $element->xpath('/root');
		$from_date = $report_date_range[0]->from_date;
		$to_date = $report_date_range[0]->to_date;

		//Get the num bills, regs, not interested bills,
		//loyalty sales, non loyalty sales, repeated bills
		$store_report_info = $this->loyaltyController->getstorereportfordaterange($from_date, $to_date);

		$this->data['response'] = $store_report_info;
	}




	/**
	 Checks if a missed call was received from this mobile number
	 in the last few minutes. currently it is 10 min.

	 @param mobile : number from which missed call was received
	 **/

	public function getMissedCallStatusApiAction($mobile)
	{
		$this->logger->debug("finding missed call status for $mobile");

		if(!Util::checkMobileNumber($mobile)  || empty($mobile)){

			$this->logger->error("Mobile number is invalid");
			$this->data['api_status'] = array(
					'key' => 'ERR_BOTH_MOBILE_EMAIL_INVALID',
					'message' => 'Both mobile and email are invalid'
			);
			return;
		}

		$user = UserProfile::getByMobile($mobile);
		$this->logger->debug("org: " . $user->org_id . " currnet: " . $this->currentorg->org_id);
		if(!$user || ($this->currentorg->org_id != $user->org_id)){
			$this->logger->debug("seems the user is not registered");
			$this->data['api_status'] = array(
					'key' => 'ERR_LOYALTY_USER_NOT_REGISTERED',
					'message' => getResponseErrorMessage('ERR_LOYALTY_USER_NOT_REGISTERED')
			);
			return;
		}

		$db = new Dbase('users');
		$sql = "SELECT * FROM sms_in lsi WHERE lsi.from = '$mobile' AND time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
		AND is_used = FALSE ORDER BY id DESC;
		";

		$row = $db->query_firstrow($sql); //just one should suffice...
		$this->logger->debug("count: $count");
		$response = array();
		if(!isset($row['id'])){  //no missed call in the last 10 min...return false
			$response['missed_call'] = 'false';
		}else{
			$response['missed_call'] = 'true';
			//we are marking all unused missed calls as used to prevent the case of multiple missed calls
			//one use of the get missed call api will mark all unused as used so that they cannot be used for fraud
			$sql = "UPDATE sms_in lsi SET lsi.is_used = TRUE WHERE lsi.from = '$mobile' AND
			lsi.is_used = FALSE AND lsi.time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
			$res = $db->update($sql);
			if(!$res){
				$this->logger->debug("Error in flagging the used missed call");
			}
		}

		$this->data['api_status'] = array(
				'key' => 'ERR_RESPONSE_SUCCESS',
				'message' => 'Operation Successful'
		);

		$this->data['response'] = $response;
		$this->logger->debug("Returning with : " . print_r($this->data, true));
	}


	/**
	 * Issues a gift card to a user
	 */

	public function issueGiftCardApiAction()
	{

		$xml_string= <<<EOXML
<root>
  <gift_card>
    <card_no>abcdef</card_no>
    <encoded_card_no>232323asfdsdf</encoded_card_no>
    <amount>1000</amount>
    <issued_on>2011-09-26 23:11:11</issued_on>
    <issued_to>223223</issued_to>
    <bill_no>asdasd</bill_no>
    <guid>wdasdad</guid>
    <name>shahrukh khan</name>
    <mobile>91998078945</mobile>
  </gift_card>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$gift_cards = $element->xpath('/root/gift_card');

		$cfg = new ConfigManager();
		$autogen = $cfg->getKey('GC_AUTOGENERATE_ON_RECHARGE') == 1;

		$api_status = array(
				'key' => 'ERR_LOYALTY_SUCCESS',
				'message' => 'Operation Successful'
		);

		$response = array();
		$db = new Dbase('users');

		$l_mgr = new ListenersMgr($this->currentorg);

		function _mobile2userid($o, $m, $n){// org, mobile, name
			$m=(string)$m; $n=(string)$n;
			list($f, $l) = Util::parseName($n);

			if(Util::checkMobileNumberNew($m)){
				$user = UserProfile::getByMobile( $m, false );
				if(!$user) {
					$auth = Auth::getInstance();
					return $auth->registerAutomaticallyByMobile($o, $m, $f, $l);
				}
				return $user->user_id;
			}
			return false;
		}


		foreach ($gift_cards as $gc)
		{

			$card_no = (string)$gc->card_no;
			$encoded_card_no = (string)$gc->encoded_card_no;
			$amount = (float)$gc->amount;
			$issued_on = (string)$gc->issued_on;
			$issued_to = (string)$gc->issued_to;
			$bill_no = (string)$gc->bill_no;
			$guid = (string)$gc->guid;

			$card_id=0;
			
			$this->logger->debug("input: $card_no, $amount, $issued_on, $issued_to, $bill_no");
			$sql = "SELECT id, issued_on FROM gc_base WHERE (card_no = '$card_no' || encoded_card_no = '$encoded_card_no') AND org_id = $org_id";
			$row = $db->query_firstrow($sql);
			if($row)
				$card_id = $row['id'];

			//SELECT random.no FROM (SELECT 78982907 AS no UNION SELECT 5456 UNION SELECT 7678) AS random WHERE NOT EXISTS (SELECT NULL FROM gc_base gcb WHERE gcb.encoded_card_no = random.no) LIMIT 1
			if($card_id==0 && $autogen){

				$issued_to = _mobile2userid($this->currentorg, $gc->issued_to->mobile, $gc->issued_to->name);
				$gifted_to = _mobile2userid($this->currentorg, $gc->gifted_to->mobile, $gc->gifted_to->name);

				$sql = "INSERT INTO gc_base (encoded_card_no, org_id, added_on) VALUES ('$encoded_card_no', $org_id, NOW())";
				$id = $db->insert($sql);

				if(!($id > 0)){

					$this->logger->debug("Error in adding autogenerated card in gc_base");
					$cr = array(
							'card_no' => $card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in autogenerating card')
					);
					array_push($response, $cr);
					continue;
				}
				$this->logger->debug("checking card existence after autogenerate input: $card_no, $amount, $issued_on, $issued_to, $bill_no");
				$sql = "SELECT id, issued_on FROM gc_base WHERE (card_no = '$card_no' || encoded_card_no = '$encoded_card_no') AND org_id = $org_id";
				$row = $db->query_firstrow($sql);
				if($row)
					$card_id = $row['id'];
			}

			$transaction_id = -1;

			if($card_id > 0) //found the card
			{
				$sql = "UPDATE gc_base SET `current_value` = $amount, `issued_on` = '$issued_on', `issued_at` = $store_id,
				issued_to = $issued_to, `current_user` = " . (($autogen) ? $gifted_to : $issued_to) .", `current_value` = $amount, `lifetime_value` = $amount
				WHERE id = $card_id";
				$res = $db->update($sql);
				if(!$res){
					$cr = array(
							'card_no' => $card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating gc_base')
					);
				}


				if(!empty($bill_no) && $issued_to > 0){
					$issued_on_date=date("Y-m-d",strtotime($issued_on));
					$sql = "SELECT id FROM loyalty_log WHERE org_id = '{$this->currentorg->org_id}' AND bill_number = '$bill_no'
					AND entered_by = $store_id AND `date` > '$issued_on_date' AND user_id = $issued_to";
					$transaction_id = $db->query_firstrow($sql);
				}

				if(empty($transaction_id)){
					$transaction_id = -1;
				}

				$sql = "INSERT INTO gc_transaction_log(card_id, org_id, type, amount, added_on, store_id, user_id,
				prev_value, bill_no, transaction_id) VALUES ($card_id, $org_id, 'CREDIT', $amount, '$issued_on',
				$store_id, " . (($autogen) ? $gifted_to : $issued_to) .", 0, '$bill_no', $transaction_id)";
				$tid = $db->insert($sql);
				if(!($tid > 0)){
					$this->logger->debug("Error in adding entry in gc_transaction_log");
					$cr = array(
							'card_no' => $card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating db')
					);
					array_push($response, $cr);
					continue;
				}

				if($issued_to > 0){
					$user = UserProfile::getById($issued_to);
					$cuser = UserProfile::getById($gifted_to);
					if($user && $cuser){
						$supplied_data = array();
						$supplied_data['issued_to_username'] = $user->first_name . ' ' . $user->last_name;
						$supplied_data['gifted_to_username'] = $cuser->first_name . ' ' . $cuser->last_name;
						$supplied_data['card_no'] = $card_no; //encoded??
						$supplied_data['amount'] = $amount;
						//??$supplied_data['user_id'] = $issued_to;
						$supplied_data['issued_to'] = $issued_to;
						$supplied_data['gifted_to'] = $gifted_to;
						$l_mgr->signalListeners(EVENT_GC_ISSUAL, $supplied_data);
					}
				}

				$this->logger->debug("added successfully");
				$cr = array(
						'card_no' => $card_no,
						'current_value' => $amount,
						'lifetime_value' => $amount,
						'guid' => $guid,
						'item_status' => array('key'=>'ERR_LOYALTY_SUCCESS', 'message'=> 'Operation Successfull')
				);
				array_push($response, $cr);

			}else{  //card not found
				$cr = array(
						'card_no' => $card_no,
						'guid' => $guid,
						'item_status' => array('key' => ERR_LOYALTY_INVALID_CARD_NO, 
												'message'=> $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_CARD_NO)
											   )
						);
				array_push($response, $cr);
			}
		}

		$this->data['api_status'] = $api_status;
		$this->data['response'] = $response;
	}


	/*
	 * Returns the info about the gift card
	*/

	public function getGiftCardInfoApiAction($encoded_card_no, $card_no = "")
	{

		$this->logger->debug("Returns the info about the gift card: $card_no");

		$db = new Dbase('users');
		$org_id = $this->currentorg->org_id;
		$sql = "SELECT * FROM gc_base WHERE org_id = $org_id AND encoded_card_no = '$encoded_card_no'";
		$result = $db->query_firstrow($sql);


		$api_status = array(
				'key' => 'ERR_RESPONSE_SUCCESS',
				'message' => 'Operation Successful'
		);
		$response = array();

		if(isset($result['id'])){
			foreach($result as $k=>$v){
				$response[$k] = $v;
			}

			$this->data['api_status'] = $api_status;
			$this->data['response'] = array('gift_card' => $response);
		}else{
			$this->logger->debug("Card no $encoded_card_no not found");
			$api_status = array(
					'key' => ERR_LOYALTY_INVALID_CARD_NO,
					'message' => $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_CARD_NO)
			);
			$response = array('gift_card' => array());
			$this->data['api_status'] = $api_status;
			$this->data['response'] = $response;
		}

		return;
	}


	public function redeemGiftCardApiAction()
	{
		$xml_string= <<<EOXML
<root>
  <gift_card>
    <card_no>abcdef</card_no>
    <encoded_card_no>adsadad</encoded_card_no>
    <amount>1000</amount>
    <redeemed_on>2011-09-26 23:11:11</redeemed_on>
    <redeemed_by>223223</redeemed_by>
    <bill_no>asdasd</bill_no>
    <guid>adad</guid>
    <name>shahrukh khan</name>
    <mobile>919980616700</mobile>
  </gift_card>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$gift_cards = $element->xpath('/root/gift_card');

		$cfg = new ConfigManager();
		$autogen = $cfg->getKey('GC_AUTOGENERATE_ON_RECHARGE') == 1;

		$api_status = array(
				'key' => 'ERR_LOYALTY_SUCCESS',
				'message' => 'Operation Successful'
		);

		$l_mgr = new ListenersMgr($this->currentorg);

		$response = array();
		$db = new Dbase('users');
		foreach ($gift_cards as $gc)
		{
			$card_no = (string)$gc->card_no;
			$encoded_card_no = (string)$gc->encoded_card_no;
			$amount = (float)$gc->amount;
			$redeemed_on = (string)$gc->redeemed_on;
			$redeemed_by = (string)$gc->redeemed_by;
			$bill_no = (string)$gc->bill_no;
			$guid = (string)$gc->guid;

			$transaction_id = -1;

			if(empty($redeemed_by) || $redeemed_by==0){
				$mobile = (string)$gc->mobile;
				$user = UserProfile::getByMobile($mobile);
				if(!$user){
					$cr = array(
							'card_no' => $card_no,
							'encoded_card_no' => $encoded_card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in finding mobile number')
					);
					array_push($response, $cr);
					continue;
				}
				$redeemed_by = $user->user_id;
			}else{
				$user=UserProfile::getById($redeemed_by);
				if(!$user){
					$cr = array(
							'card_no' => $card_no,
							'encoded_card_no' => $encoded_card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in finding user')
					);
					array_push($response, $cr);
					continue;
				}
			}

			if($amount < 0){
				$cr = array(
						'card_no' => $card_no,
						'guid' => $guid,
						'item_status' => array('key' => ERR_LOYALTY_INVALID_AMOUNT, 'message'=> 'Invalid amount for recharge/redeem')
				);
				array_push($response, $cr);
				continue;
			}

			$this->logger->debug("input: $card_no, $amount, $recharged_by, $recharged_on, $bill_no");
			$sql = "SELECT id, issued_to, current_value, lifetime_value FROM gc_base WHERE encoded_card_no = '$encoded_card_no' AND org_id = $org_id ORDER BY `current_user` DESC";
			$row = $db->query_firstrow($sql);
			$card_id = $row['id'];
			$current_value = $row['current_value'];
			$lifetime_value = $row['lifetime_value'];
			$issued_to = $row['issued_to'];

			if($card_id > 0) //found the card
			{
				if($current_value < $amount){
					$this->logger->debug("Insufficient credits");
					$cr = array(
							'card_no' => $card_no,
							'encoded_card_no' => $encoded_card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_INSUFFICIENT_CARD_CREDIT, 'message'=> 'Insufficient credits in card')
					);
					array_push($response, $cr);
					continue;
				}else{

					$sql = "UPDATE gc_base SET current_value = current_value - $amount WHERE id = $card_id";
					$res = $db->update($sql);
					if(!$res){
						$cr = array(
								'card_no' => $card_no,
								'encoded_card_no' => $encoded_card_no,
								'guid' => $guid,
								'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating gc_base')
						);
						array_push($response, $cr);
					}
				}

				if(!empty($bill_no) && $redeemed_by > 0){
					$redeemed_on_date=date("Y-m-d",strtotime($redeemed_on));
					$sql = "SELECT id FROM loyalty_log WHERE org_id = '{$this->currentorg->org_id}' AND bill_number = '$bill_no'
					AND entered_by = $store_id AND `date` > '$redeemed_on_date' AND user_id = $redeemed_by";
					$transaction_id = $db->query_firstrow($sql);
				}

				if(empty($transaction_id)){
					$transaction_id = -1;
				}

				$sql = "INSERT INTO gc_transaction_log(card_id, org_id, type, amount, added_on, store_id, user_id,
				prev_value, bill_no, transaction_id)
				VALUES (
				'$card_id', '$org_id', 'DEBIT', '$amount', '$redeemed_on', '$store_id', '$redeemed_by', '$current_value', '$bill_no', '$transaction_id'
				)";
				$tid = $db->insert($sql);
				if(!($tid > 0)){
					$this->logger->debug("Error in adding entry in gc_transaction_log");
					$cr = array(
							'card_no' => $card_no,
							'encoded_card_no' => $encoded_card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating db')
					);
					array_push($response, $cr);
					continue;
				}

				$supplied_data = array();
				$supplied_data['card_no'] = $card_no;
				$supplied_data['encoded_card_no'] = $encoded_card_no;
				$supplied_data['amount'] = $amount;
				$balance = $current_value - $amount;
				$supplied_data['balance'] = $balance;

				if($redeemed_by > 0){
					$user = UserProfile::getById($redeemed_by);
					if($user)
					{
						$supplied_data['username'] = $user->first_name . ' ' . $user->last_name;
						$supplied_data['user_id'] = $redeemed_by;
						$l_mgr->signalListeners(EVENT_GC_REDEEM, $supplied_data);
					}
				}


				if($balance <= 0){  //will never go below zero

					if($issued_to > 0){
						$issued_to_user = UserProfile::getById($issued_to);
						if($issued_to_user){
							$supplied_data['issued_to_username'] = $issued_to_user->first_name . ' ' . $issued_to_user->last_name;
							$supplied_data['user_id'] = $issued_to;
							$l_mgr->signalListeners(EVENT_GC_CLOSED, $supplied_data);
						}
					}
				}

				$this->logger->debug("added successfully");
				$cr = array(
						'card_no' => $card_no,
						'encoded_card_no' => $encoded_card_no,
						'guid' => $guid,
						'current_value' => $current_value - $amount,
						'lifetime_value' => $lifetime_value,
						'item_status' => array('key'=>'ERR_LOYALTY_SUCCESS', 'message'=> 'Operation Successfull')
				);
				array_push($response, $cr);

			}else{  //card not found
				$cr = array(
						'card_no' => $card_no,
		    'encoded_card_no' => $encoded_card_no,
						'guid' => $guid,
						'item_status' => array('key' => ERR_LOYALTY_INVALID_CARD_NO, 'message'=> $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_CARD_NO))
				);
				array_push($response, $cr);
			}
		}

		$this->data['api_status'] = $api_status;
		$this->data['response'] = $response;
	}


	public function rechargeGiftCardApiAction()
	{
		$xml_string= <<<EOXML
<root>
  <gift_card>
    <card_no>abcdef</card_no>
    <encoded_card_no>32323fsfssfd</encoded_card_no>
    <amount>1000</amount>
    <recharged_on>2011-09-26 23:11:11</recharged_on>
    <recharged_by>223223</recharged_by>
    <bill_no>asdasd</bill_no>
    <guid>adasda</guid>
    <mobile>919980616000</mobile>
    <name>james bond</name>
  </gift_card>
</root>
EOXML;

		$xml_string = $this->getRawInput();

		//Verify the xml strucutre
		if(Util::checkIfXMLisMalformed($xml_string)){
			$api_status = array(
					'key' => getResponseErrorKey(ERR_RESPONSE_BAD_XML_STRUCTURE),
					'message' => getResponseErrorMessage(ERR_RESPONSE_BAD_XML_STRUCTURE)
			);
			$this->data['api_status'] = $api_status;
			return;
		}

		$element = Xml::parse($xml_string);
		$org_id = $this->currentorg->org_id;
		$store_id = $this->currentuser->user_id;
		$gift_cards = $element->xpath('/root/gift_card');

		$api_status = array(
				'key' => 'ERR_LOYALTY_SUCCESS',
				'message' => 'Operation Successful'
		);

		$response = array();

		$l_mgr = new ListenersMgr($this->currentorg);

		$db = new Dbase('users');
		foreach ($gift_cards as $gc)
		{
			$card_no = (string)$gc->card_no;
			$encoded_card_no = (string)$gc->encoded_card_no;
			$amount = (float)$gc->amount;
			$recharged_on = (string)$gc->recharged_on;
			$recharged_by = (string)$gc->recharged_by;
			$bill_no = (string)$gc->bill_no;
			$guid = (string)$gc->guid;

			$transaction_id = -1;

			if($amount < 0){
				$cr = array(
						'card_no' => $card_no,
		    'encoded_card_no' => $encoded_card_no,
						'guid' => $guid,
						'item_status' => array('key' => ERR_LOYALTY_INVALID_AMOUNT, 'message'=> 'Invalid amount for recharge/redeem')
				);
				array_push($response, $cr);
				continue;
			}

			$this->logger->debug("input: $card_no, $amount, $recharged_by, $recharged_on, $bill_no");
			$sql = "SELECT id, current_value, lifetime_value, issued_to, issued_at FROM gc_base WHERE encoded_card_no = '$encoded_card_no' AND org_id = $org_id";
			$row = $db->query_firstrow($sql);
			$card_id=0;
			if($row)
				$card_id = $row['id'];
			$current_value = $row['current_value'];
			$lifetime_value = $row['lifetime_value'];
			$issued_to = $row['issued_to'];
			$issued_at = $row['issued_at'];

			$cfg = new ConfigManager();
			$autogen = $cfg->getKey('GC_AUTOGENERATE_ON_RECHARGE') == 1;
			
			if($card_id==0 && $autogen) //gift card is not there..register the gift card
			{
				$this->logger->debug("gift card $card_no not in the system.. adding it");
				$org_id = $this->currentorg->org_id;
				$store_id = $this->currentuser->user_id;
				$sql = "INSERT INTO gc_base(`card_no`, `org_id`, `added_on`, `added_by`, `issued_to`, `current_user`, `issued_on`, `issued_at`, `current_value`, `lifetime_value`, `encoded_card_no`)
				VALUES ('$card_no', $org_id, '$recharged_on', $store_id, $recharged_by, $recharged_by, '$recharged_on', $store_id, $amount, $amount, '$encoded_card_no')";
				$card_id = $db->insert($sql);

				$row = $db->query_firstrow("SELECT id, current_value, lifetime_value, issued_to, issued_at FROM gc_base WHERE id = $card_id");
				$card_id = $row['id'];
				$current_value = $row['current_value'];
				$lifetime_value = $row['lifetime_value'];
				$issued_at = $row['issued_at'];
				$issued_to = $row['issued_to'];

				$transaction_id = 0;
				if(!empty($bill_no) && $redeemed_by > 0){
					$recharged_on_date=date("Y-m-d",strtotime($recharged_on));
					$sql = "SELECT id FROM loyalty_log WHERE org_id = '{$this->currentorg->org_id}' AND bill_number = '$bill_no'
					AND entered_by = $store_id AND `date` > '$recharged_on_date' AND user_id = $recharged_by";
					$transaction_id = $db->query_firstrow($sql);
				}

				if(empty($transaction_id)){
					$transaction_id = -1;
				}

				$sql = "INSERT INTO gc_transaction_log(card_id, org_id, type, amount, added_on, store_id, user_id,
				prev_value, bill_no, transaction_id)
				VALUES (
				'$card_id', '$org_id', 'CREDIT', '$amount', '$recharged_on', '$store_id', '$recharged_by', 0, '$bill_no', '$transaction_id'
				)";
				$tid = $db->insert($sql);
				if(!($tid > 0)){
					$this->logger->debug("Error in adding entry in gc_transaction_log");
					$cr = array(
							'card_no' => $card_no,
							'encoded_card_no' => $encoded_card_no,
							'guid' => $guid,
							'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating db')
					);
					array_push($response, $cr);
					continue;
				}

				if($recharged_by > 0){
					$user = UserProfile::getById($recharged_by);
					if($user){
						$supplied_data = array();
						$supplied_data['issued_to_username'] = $user->first_name . ' ' . $user->last_name;
						$supplied_data['card_no'] = $card_no;
						$supplied_data['encoded_card_no'] = $encoded_card_no;
						$supplied_data['amount'] = $amount;
						$supplied_data['user_id'] = $recharged_by;
						$l_mgr->signalListeners(EVENT_GC_ISSUAL, $supplied_data);
					}
				}

				$this->logger->debug("added successfully");
				$cr = array(
						'card_no' => $card_no,
						'encoded_card_no' => $encoded_card_no,
						'guid' => $guid,
						'current_value' => $amount,
						'lifetime_value' => $amount,
						'item_status' => array('key'=>'ERR_LOYALTY_SUCCESS', 'message'=> 'Operation Successfull')
				);
				array_push($response, $cr);
				continue;

			}else if($card_id > 0) //card has been found

			{
				/*
				 if($issued_at <= 0 && $issued_to <= 0){ //getting issued to the user for the first time
				$issued_update = ", issued_to = $recharged_by, issued_on = '$recharged_on', issued_at = $store_id";
				}*/

				$this->logger->info("card found without autogenerate. Card Exists");
				 
				$cm = new ConfigManager($org_id);
				$recharge_on_same_bill = $cm->getKey('CONF_GC_MULTIPLE_RECHARGE_ON_BILL');

				/*
				 If multiple recharge on same bill is not allowed
				see if recharge has been done on this bill or not
				*/
				if(!$recharge_on_same_bill)
				{
					 
					$this->logger->debug("no duplicates allowed");

					$recharge_time = Util::getMysqlDateTime(Util::deserializeFrom8601($recharged_on));
					$this->logger->debug("recharge time: $recharge_time");
					$store_id = $this->currentuser->user_id;
					$sql = "SELECT * FROM gc_transaction_log WHERE card_id = $card_id AND type = 'CREDIT'
					AND bill_no = '$bill_no' AND added_on = '$recharge_time' AND store_id = '$store_id' AND org_id='{$this->currentorg->org_id}'
					";

					$result = $db->query_firstrow($sql);
					if(intval($result['id']) > 0)
					{
						$this->logger->debug("recharge has already been done on bill $bill_no, card = $card_id, card_num: $encoded_card_no");
						$cr = array(
								'card_no' => $card_no,
								'encoded_card_no' => $encoded_card_no,
								'guid' => $guid,
								'item_status' => array('key' => 'ERR_DUPLICATE_RECHARGE_ON_BILL', 'message'=> 'Recharge has already been done on this bill')
						);
						array_push($response, $cr);
						continue;
					}else{

						$this->logger->debug("No credit has been done on bill $bill_no, card = $card_id, card_num: $encoded_card_no");
					}
				}


                       		$sql = "UPDATE gc_base SET current_value = current_value + $amount, lifetime_value = lifetime_value + $amount
              WHERE id = $card_id";
                       		if(!$db->update($sql)){
                       			$this->logger->debug("Error in updating the current/lifetimevalue");
                       			$cr = array(
                    'card_no' => $card_no,
		    'encoded_card_no' => $encoded_card_no,	
                    'guid' => $guid,
                    'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating db')      
                       			);
                       			array_push($response, $cr);
                       			continue;

                       		}

                       		if(!empty($bill_no) && $recharged_by > 0){
                       			$recharged_on_date=date("Y-m-d",strtotime($recharged_on));
                       			$sql = "SELECT id FROM loyalty_log WHERE org_id = '{$this->currentorg->org_id}' AND bill_number = '$bill_no'
                 AND entered_by = $store_id AND `date` > '$recharged_on_date' AND user_id = $recharged_by";
                       			$transaction_id = $db->query_firstrow($sql);
                       		}

                       		if(empty($transaction_id)){
                       			$transaction_id = -1;
                       		}

                       		$sql = "INSERT INTO gc_transaction_log(card_id, org_id, type, amount, added_on, store_id, user_id,
             prev_value, bill_no, transaction_id)  
             VALUES ( 
             '$card_id', '$org_id', 'CREDIT', '$amount', '$recharged_on', '$store_id', '$recharged_by', '$current_value', '$bill_no', '$transaction_id'
                    )";
             $tid = $db->insert($sql);
             if(!($tid > 0)){
             	$this->logger->debug("Error in adding entry in gc_transaction_log");
             	$cr = array(
                         'card_no' => $card_no,
			 'encoded_card_no' => $encoded_card_no,
                         'guid' => $guid, 
                         'item_status' => array('key' => ERR_LOYALTY_UNKNOWN, 'message'=> 'Error in updating db')      
             	);
             	array_push($response, $cr);
             	continue;
             }

             $balance = $current_value + $amount;
             if($recharged_by > 0 )
             {
             	$user = UserProfile::getById($recharged_by);
             	if($user)
             	{
             		$supplied_data = array();
             		$supplied_data['username'] = $user->first_name . ' ' . $user->last_name;
             		$supplied_data['card_no'] = $card_no;
             		$supplied_data['encoded_card_no' ] = $encoded_card_no;
             		$supplied_data['amount'] = $amount;
             		$supplied_data['balance'] = $balance;
             		$supplied_data['user_id'] = $recharged_by;
             		$l_mgr->signalListeners(EVENT_GC_RECHARGE, $supplied_data);
             	}
             }

             $this->logger->debug("added successfully");
             $cr = array(
                  'card_no' => $card_no,
                  'guid' => $guid,
		  'encoded_card_no' => $encoded_card_no,	
                  'current_value' => $current_value + $amount,
                  'lifetime_value' => $lifetime_value + $amount,
                  'item_status' => array('key'=>'ERR_LOYALTY_SUCCESS', 'message'=> 'Operation Successfull')
             );
             array_push($response, $cr);

                       	}else{  //card not found
                       		$cr = array(
                    'card_no' => $card_no,
		    'encoded_card_no' => $encoded_card_no,	
                    'guid' => $guid,
                    'item_status' => array('key' => ERR_LOYALTY_INVALID_CARD_NO, 
                                           'message'=> $this->getResponseErrorMessage(ERR_LOYALTY_INVALID_CARD_NO)
                       		)
                       		);
                       		array_push($response, $cr);
                       	}
                       }

                       $this->data['api_status'] = $api_status;
                       $this->data['response'] = $response;
	}
	



}


include_once 'apiController/ApiCustomerController.php';

class CustomerController extends ApiCustomerController{
		
	public function __construct(){
		parent::__construct();
	}
}


?>
