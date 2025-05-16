<?php

define(ERR_CAMPAIGNS_INVALID_REFERRER_ID, -1000);
define(ERR_CAMPAIGNS_INVALID_VOUCHER_SERIES_ID, -2000);
define(ERR_CAMPAIGNS_NO_VOUCHER_SENT_TO_REFERRER, -3000);
define(ERR_CAMPAIGNS_REFERRAL_FAILURE, -4000);
define(ERR_CAMPAIGNS_REFERRAL_DUPLICATE, -5000);
define(ERR_CAMPAIGNS_REFEREE_EXISTS, -6000);
define(ERR_CAMPAIGNS_REFEREE_REFERRER_SAME, -7000);
define(ERR_CAMPAIGNS_MAX_REFERRALS_REFEREE, -8000);
define(ERR_CAMPAIGNS_REFERRAL_SERIES_EXPIRED, -9000);
define(ERR_CAMPAIGNS_INVALID_REFEREE_MOBILE, -10000);
define(ERR_CAMPAIGNS_REFERRAL_SUCCESS, 1000);

$GLOBALS["campaigns_error_responses"] = array (
ERR_CAMPAIGNS_INVALID_REFERRER_ID => "Could not find UserProfile for referrer. Invalid referrer id",
ERR_CAMPAIGNS_INVALID_VOUCHER_SERIES_ID => "Invalid voucher series",
ERR_CAMPAIGNS_NO_VOUCHER_SENT_TO_REFERRER => "No voucher issued to the referrer from this voucher series",
ERR_CAMPAIGNS_REFERRAL_FAILURE => "Referral Failed",
ERR_CAMPAIGNS_REFERRAL_DUPLICATE => "Referral already made",
ERR_CAMPAIGNS_REFEREE_EXISTS => "Cannot refer already existing customer",
ERR_CAMPAIGNS_REFEREE_REFERRER_SAME => "Referrer and Refereee are the same",
ERR_CAMPAIGNS_MAX_REFERRALS_REFEREE => 'This person has already been referred',
ERR_CAMPAIGNS_REFERRAL_SERIES_EXPIRED => "Referral Series Expired",
ERR_CAMPAIGNS_INVALID_REFEREE_MOBILE => "Referee Mobile is Invalid",
ERR_CAMPAIGNS_REFERRAL_SUCCESsS => "Referral Sent"
);

$GLOBALS["campaigns_error_keys"] = array (
ERR_CAMPAIGNS_INVALID_REFERRER_ID => "ERR_CAMPAIGNS_INVALID_REFERRER_ID",
ERR_CAMPAIGNS_INVALID_VOUCHER_SERIES_ID => "ERR_CAMPAIGNS_INVALID_VOUCHER_SERIES_ID",
ERR_CAMPAIGNS_NO_VOUCHER_SENT_TO_REFERRER => "ERR_CAMPAIGNS_NO_VOUCHER_SENT_TO_REFERRER",
ERR_CAMPAIGNS_REFERRAL_FAILURE => "ERR_CAMPAIGNS_REFERRAL_FAILURE",
ERR_CAMPAIGNS_REFERRAL_DUPLICATE => "ERR_CAMPAIGNS_REFERRAL_DUPLICATE",
ERR_CAMPAIGNS_REFEREE_EXISTS => "ERR_CAMPAIGNS_REFEREE_EXISTS",
ERR_CAMPAIGNS_REFEREE_REFERRER_SAME => "ERR_CAMPAIGNS_REFEREE_REFERRER_SAME",
ERR_CAMPAIGNS_MAX_REFERRALS_REFEREE => "ERR_CAMPAIGNS_MAX_REFERRALS_REFEREE",
ERR_CAMPAIGNS_REFERRAL_SERIES_EXPIRED => "ERR_CAMPAIGNS_REFERRAL_SERIES_EXPIRED",
ERR_CAMPAIGNS_INVALID_REFEREE_MOBILE => "ERR_CAMPAIGNS_INVALID_REFEREE_MOBILE",
ERR_CAMPAIGNS_REFERRAL_SUCCESS => "ERR_CAMPAIGNS_REFERRAL_SUCCESS"
);

include_once "business_controller/points/PointsEngineServiceController.php";
include_once "business_controller/emf/EMFServiceController.php";



/**
 * The Filter Class For Loyalty Form
 * The BaseFilterParams is the base class for all base filter parameters  
 * @author Prakhar
 * 
 * 
 * $filter_type is
 * associated with the name of class and type of filter name.
 */
include_once "controller/ApiParent.php";
include_once "controller/ApiCampaigns.php";

	
/**
 * Main starting point of the Campaigns module
 * @author v-kmehra
 *
 */
class CampaignsModule extends BaseModule {
	/**
	 * Contains a hash of the registered campaigns 
	 * @var array
	 */
	private $registered_campaigns;
	/**
	 * The wizard object that is used to guide the user
	 * @var unknown_type
	 */
	private $wizard;
	private $ajaxModule;
	
	var $campaignsController;

	function __construct() {
		parent::__construct();
		global $logger;

		$this->campaignsController = new CampaignsController($this);

	}


	/**
	 *  Sends summary of vouchers
	 * 
	 */
	
	public function sendVoucherSummaryAction($mobile, $org_id, $argument)
	{
		$this->logger->debug("Sending voucher summary $mobile, $org_id, arguments: " . print_r($argument, true));
    $org = new OrgProfile($org_id);
    $user = UserProfile::getByMobile($mobile);		
    
    $this->logger->debug("found user: " . $user->user_id); 	
    if(!$user){
      $this->logger->debug("User not found with mobile $mobile");
      return;
    }
    
    //Select information of all unredeemed vouchers
    $sql = "SELECT v.voucher_id, v.voucher_series_id, v.voucher_code FROM voucher v
            LEFT OUTER JOIN voucher_redemptions vr ON v.voucher_id = vr.voucher_id
            AND v.org_id = vr.org_id AND v.current_user = vr.used_by WHERE 
            vr.voucher_id IS NULL AND v.issued_to = $user->user_id AND v.org_id = $org->org_id		
            ORDER BY v.voucher_id DESC
           ";
    $this->logger->debug($sql);
     $db = new Dbase('campaigns');	
   $result = $db->query($sql);    

    $this->logger->debug("result: " . print_r($result, true));	
    $sms_text = "";
    
    foreach($result as $k=>$row)
    {
       $voucher_id = $row['voucher_id'];
       $voucher_series_id = $row['voucher_series_id'];
       $voucher_code = $row['voucher_code'];

       $this->logger->debug("processing voucher_id: $voucher_id, $voucher_series_id, $voucher_code"); 
       $v = Voucher::getVoucherFromId($voucher_id);
       $expiry_date = strtotime($v->getExpiryDate());
       $today = time();
      
       $this->logger->debug("expiry date: $expiry_date and today $today");
       if($today < $expiry_date)          
       {
          $this->logger->debug("Voucher is not expired");
          $vs = new VoucherSeries($voucher_series_id);
          $description = $vs->description;

          $sms_text .= "$vs->description,code:$voucher_code;"; 
       }
    }  

		$this->logger->debug("Clubbed description: $sms_text");
  	
    $mobile = $user->mobile;
    Util::sendSms($mobile, $sms_text, $org->org_id, MESSAGE_PRIORITY,
    		false, '', false, false, array(),
    		$user->user_id, $user->user_id, 'VOUCHER'  );
	}
	
	


	/********** API ACTIONS *************/
	
	public function voucherSeriesApiAction($vs_id = false) {

		$res = $this->campaignsController->getVoucherSeriesDetailsByOrg('query', $vs_id, true);

        $user_agent = "clienteling";
        $clienteling = false;

        if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), $user_agent)){
            $clienteling = true;
        }


        if(!$clienteling){
		//TODO
		//Hack to save client bug, must be removed later :|
		//sending some random voucher series
		    if(!$res || count($res) == 0){
			    $res = $this->campaignsController->getDummyVoucherSeriesDetails();
		    }
        }
			
		$this->data['voucher_series'] = $res;	

    }
	
	public function vouchersApiAction($vch_code = '') {
		ini_set("memory_limit", "800M");

		$isContentCachingDisabled = $this -> currentorg -> getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false);
		$this -> logger -> debug("Voucher code? '$vch_code'"); 
		$this -> logger -> debug("CONF_CLIENT_DISABLE_CONTENT_CACHING? '$isContentCachingDisabled'"); 
		if ($vch_code != '' || $isContentCachingDisabled) {
			
			require_once "helper/Util.php";
			require_once "apiHelper/ApiUtil.php";
			$orgId = $this -> currentorg -> org_id;
			
			$isLuciFlowEnabled = Util::isLuciFlowEnabled();	
			if ($isLuciFlowEnabled) {
				
				$response = ApiUtil :: luciGetCoupon($orgId, "coupon_code", array($vch_code));
				$success = $response -> success;
				if (!$success) {
					$this -> data['vouchers'] = array();
				} else {
					//$luciCoupons = $response -> coupons;
					$luciCouponResponse = $response -> coupons[0];

					//foreach ($luciCoupons as $luciCouponResponse) {
						$loadedCoupon = $luciCouponResponse -> coupon;
						if (! empty($loadedCoupon -> couponSeriesId)) {
							$result = ApiUtil :: luciGetCouponSeries($orgId, $loadedCoupon -> couponSeriesId);
						
							if ($result -> success) {
								$loadedSeries = $result -> couponSeries;
							}
						}
						$redemptionInfo = $loadedCoupon -> redeemedCoupons;
						if (! empty($redemptionInfo)) {
							$redemption = $redemptionInfo [0];
						} 

						$coupon = array(
							'voucher_id' => $loadedCoupon -> id, 
							'voucher_code' => $loadedCoupon -> couponCode, 
							'pin_code' => $loadedCoupon -> pin_code, 
							'created_date' => ApiUtil :: luciDateToStr($loadedCoupon -> issuedDate, 'Y-m-d H:i:s'), 
							'issued_to' => $loadedCoupon -> issuedToUserId, 
							'current_user' => $loadedCoupon -> issuedToUserId, //issuedById, 
							'voucher_series_id' => $loadedCoupon -> couponSeriesId, 
							'used' => !empty($redemption -> redemptionId) ? 1 : 0, 
							'used_by' => !empty($redemption -> redeemedByUserId) ? $redemption -> redeemedByUserId : -1, 
							'amount' => $loadedCoupon -> billAmount, 
							'max_allowed_redemptions' => $loadedSeries -> max_redemptions_in_series_per_user
						);
						$this -> data['vouchers'][] = $coupon;
					//}
				}
			} else {
				$this->data['vouchers']=  $this->campaignsController->getVoucherDetailsFromOrgIdAndVchCode($vch_code);
			}
		} else {
			$this->campaignsController->getVoucherDetailsFromOrgInFile($this->data['xml_file_name']);
		}
	}
	
	public function vouchersDeltaApiAction() {
		ini_set("memory_limit", "500M");
		if($this->currentorg->getConfigurationValue(CONF_CLIENT_DISABLE_CONTENT_CACHING, false))	
			$this->data['vouchers']=  $this->campaignsController->getVoucherDetailsFromOrgIdAndVchCode('',true);
		else
			$this->campaignsController->getVoucherDetailsFromOrgInFile($this->data['xml_file_name'],true);
	}
	
	public function fetchVouchersApiAction($user_id, $vch_code = '') {
		if ($vch_code != '')
			$res = $this->campaignsController->getVoucherDetailsFromOrgIdAndVchCode($vch_code);
		else {
			$lm = new LoyaltyModule();
			$res = $lm->loyaltyController->getissuedvouchers($user_id);
		}
		$this->data['vouchers'] = $res;
	}

	public function issueVoucherApiAction($vch_series_id, $user_id) {
		$org_id = $this->currentorg->org_id;
		$user = UserProfile::getById($user_id);
		$ret = VOUCHER_ERR_SUCCESS;
		if ($user == false) {
			$vch = false;
			$ret = VOUCHER_ERR_INVALID_USER;
		} else {
			$vch= Voucher::issueVoucher($vch_series_id, $user, $this->currentorg, $this->currentuser->user_id);
			if ($vch != false) {
				
				$v = Voucher::getVoucherFromCode($vch, $org_id);				
				$vs = $v->getVoucherSeries();
				$sms_template = $vs->getSMSTemplate();
				if($sms_template == "")
					$sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";
		
				$eup = new ExtendedUserProfile($user, $this->currentorg);	
					
				$data = array('voucher_code' => $v->getVoucherCode(), 'cust_name' => $eup->getName());
		
				$smstext = Util::templateReplace($sms_template, $data);				
		
				Util::sendSms($user->mobile, $smstext, $org_id, MESSAGE_PRIORITY,
						false, '', false, false, array(),
						$user->user_id, $user->user_id, 'VOUCHER' );

			}else{
				$ret = VOUCHER_ERR_UNABLE_TO_CREATE;
			}
			
		}
		
		//Add the response for the new clients
		$this->data['api_status'] = array(
			'key' => Voucher::getResponseErrorKey($ret),
			'message' => Voucher::getResponseErrorMessage($ret)
		);
		$this->data['voucher_code'] = $vch?$vch:"";
		
		$this->data['response_code'] = $vch?$vch:"";
		
		$this->data['response'] = $vch != false ? "Successfully delivered"  : "Voucher issuing unsuccessful";
	}
	
	
	public function issueDVSVoucherApiAction($vch_series_id, $user_id, $bill_number, $created_time = '') {
		
		$org_id = $this->currentorg->org_id;
		$user = UserProfile::getById($user_id);
		$ret = VOUCHER_ERR_SUCCESS;
		
		if ($user == false) {
			
			$vch = false;
			$ret = VOUCHER_ERR_INVALID_USER;
			
		} else {
			
			$created_time = '';
			$created_time = $created_time != '' ? Util::deserializeFrom8601($created_time) : '';
			
			$vch= Voucher::issueVoucher($vch_series_id, $user, $this->currentorg, $this->currentuser->user_id, NULL, $created_time, false, '', $bill_number);
			if ($vch != false) {
				
				$v = Voucher::getVoucherFromCode($vch, $org_id);				
				$vs = $v->getVoucherSeries();
				$sms_template = $vs->getSMSTemplate();
				if($sms_template == "")
					$sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";
		
				$eup = new ExtendedUserProfile($user, $this->currentorg);	
					
				$data = array('voucher_code' => $v->getVoucherCode(), 'cust_name' => $eup->getName());
		
				$smstext = Util::templateReplace($sms_template, $data);			
				
				if(!$vs->isSmsSendingDisabled())
				{		
					//Get time delay for sms if any
					$time_delay_secs = $this->currentorg->getConfigurationValue(CONF_DVS_SMS_DELAY, 0);
					$scheduled_time = date("Y-m-d H:i:s", strtotime("+$time_delay_secs seconds"));
					$this->logger->debug("SMS $smstext , scheduled at $scheduled_time, delayed by $time_delay_secs seconds");
					Util::sendSms($user->mobile, $smstext, $org_id, MESSAGE_PRIORITY, 
							true, $scheduled_time, false, false, array(),
							$user->user_id, $user->user_id, 'VOUCHER'  );
				}
				else
				{
					$this->logger->debug("Not sending sms as its disabled for the series : $smstext");
				}
			}else{
				
				$ret = VOUCHER_ERR_UNABLE_TO_CREATE;
				
			}
		}
		
				
		//Add the response for the new clients
		$this->data['api_status'] = array(
			'key' => Voucher::getResponseErrorKey($ret),
			'message' => Voucher::getResponseErrorMessage($ret)
		);
		$this->data['voucher_code'] = $vch;
		
		$this->data['response_code'] = $vch;
		
		$this->data['response'] = $vch != false ? "Successfully delivered"  : "Voucher issuing unsuccessful";
	}


	/*
	 * For backward compatibility the first voucher is code is sent with the response
	 * tag response_code. This is because earlier, the API was single supported.
	 */
	public function issueDVSVoucherNewApiAction() {
		
		//$vch_series_id, $user_id, $bill_number, $created_time = ''

		// This is a dummy line written to ensure that the Voucher class has 
		// been initialized by PHP. Else it doesn't recognize the constants.
		new Voucher();

		$org_id = $this->currentorg->org_id;
		$xml_string = <<<EOXML
<root>
	<dvs_voucher>
		<voucher_series_id>278</voucher_series_id>
		<issued_to>5</issued_to>
		<bill_no>ABC124</bill_no>
		<created_date>2010-07-30T09:52Z</created_date>
		<voucher_client_id>___GUID___</voucher_client_id> <!-- This field is returned back to the client without any manipulation and is solely for use by the client. -->
		<issued_at_counter_id />
		<issued_by_store_server>1</issued_by_store_server>
		<voucher_code>s11-278-abcdef</voucher_code>
		<rule_map>["test1"]</rule_map>
	</dvs_voucher>
	<dvs_voucher>
		<voucher_series_id>278</voucher_series_id>
		<issued_to>4</issued_to>
		<bill_no>ABC125</bill_no>
		<created_date>2010-07-31T09:52Z</created_date>
		<voucher_client_id>___GUID___</voucher_client_id> <!-- This field is returned back to the client without any manipulation and is solely for use by the client. -->
		<issued_at_counter_id />
		<issued_by_store_server>0</issued_by_store_server>
		<voucher_code />
		<rule_map>["test"]</rule_map>
	</dvs_voucher>
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
		$elems = $element->xpath('/root/dvs_voucher');
		$responses = array();
	
		$ret = VOUCHER_ERR_SUCCESS;

		$first_run = true;
		$first_voucher_code = '';

		//user_id1 => array(
		//		'messages' => sms1, sms2,
		//		'mobile' => 9997978655,
		//		'scheduled_time' => 2010-07-29 18:45:00
		//	)
		$clubbed_messages_to_be_sent = array();
		$is_sms_clubbing_enabled = $this->currentorg->getConfigurationValue(CONF_VOUCHER_CLUB_MULTIPLE_SMS, false);

		//Get time delay for sms if any
		$time_delay_secs = $this->currentorg->getConfigurationValue(CONF_DVS_SMS_DELAY, 0);

		foreach ($elems as $e) {
			
			$vch_series_id = trim((string) $e->voucher_series_id);
			$user_id = trim((string) $e->issued_to);
			$bill_number = trim((string) $e->bill_no);
			$created_time = trim((string) $e->created_date); 
			$voucher_client_id = trim((string) $e->voucher_client_id);
			$issued_at_counter_id = trim((string) $e->issued_at_counter_id);
			$issued_at_counter_id = $issued_at_counter_id > 0 ? $issued_at_counter_id : -1;
			$issued_voucher_code = trim((string) $e->voucher_code);
			$issued_by_store_server = (((integer) $e->issued_by_store_server) == 1);
			$rule_map = trim((string) $e->rule_map);

			$user = UserProfile::getById($user_id);
			if ($user == false) {
				$vch = false;
				$ret = VOUCHER_ERR_INVALID_USER;
			} else {
				$created_time = '';
				$created_time = $created_time != '' ? Util::deserializeFrom8601($created_time) : '';
				//if issued by the store server, insert without any checks
				if($issued_by_store_server){
					$vch = Voucher::issueVoucherByStoreServer(
						$vch_series_id, $user, $this->currentorg, 
						$this->currentuser->user_id, $issued_voucher_code,
						$issued_at_counter_id, $bill_number, $created_time, $rule_map);	
				}else{				
					$vch = Voucher::issueVoucher($vch_series_id, $user, $this->currentorg, $this->currentuser->user_id, NULL, $created_time, false, '', $bill_number, '', $issued_at_counter_id, $rule_map);
				}
				
				if ($vch != false) {
					
					$v = Voucher::getVoucherFromCode($vch, $org_id);
                                        $voucher_id = $v->getVoucherId();
					$vs = $v->getVoucherSeries();
					$sms_template = $vs->getSMSTemplate();
					
					//use the short sms template in case clubbing is enabled
					if($is_sms_clubbing_enabled)
						$sms_template = $vs->getShortSMSTemplate();

					if($sms_template == "")
						$sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}";
			
					$eup = new ExtendedUserProfile($user, $this->currentorg);	
						
					$store_name = $this->currentuser->first_name . " " . $this->currentuser->last_name;
					$data = array('voucher_code' => $v->getVoucherCode(), 'cust_name' => $eup->getName(), 'voucher_expiry_date' => $v->getExpiryDate(),
                                                        'bill_store_name' => $store_name, 'store_number' => $this->currentuser->mobile);
			
					$smstext = Util::templateReplace($sms_template, $data);				

					$scheduled_time = date("Y-m-d H:i:s", strtotime("+$time_delay_secs seconds"));
					$this->logger->debug("SMS $smstext , scheduled at $scheduled_time, delayed by $time_delay_secs seconds");

					//Collect all the smses if clubbing is enabled, other wise send immeadiately
					if(!$vs->isSmsSendingDisabled()){
						if(!$is_sms_clubbing_enabled){
							Util::sendSms($user->mobile, $smstext, $org_id, MESSAGE_PRIORITY, 
												true, $scheduled_time, true, true, array(), 
												$user->user_id, $user->user_id, 'VOUCHER'  );
						}else{
	
							//Collect all the vouchers
	
							//user_id1 => array(
							//		'messages' => sms1, sms2,
							//		'mobile' => 9997978655,
							//		'scheduled_time' => 2010-07-29 18:45:00
							//	)
	
							if(!isset($clubbed_messages_to_be_sent[$user_id]))
								$clubbed_messages_to_be_sent[$user_id] = array();
	
							//Store the clubbed sms..
							$clubbed_messages_to_be_sent[$user_id]['mobile'] = $user->mobile;
							$clubbed_messages_to_be_sent[$user_id]['scheduled_time'] = $scheduled_time;
							if(!isset($clubbed_messages_to_be_sent[$user_id]['messages']))
								$clubbed_messages_to_be_sent[$user_id]['messages'] = array();
							array_push($clubbed_messages_to_be_sent[$user_id]['messages'], $smstext);
						}
					}else
					{
						$this->logger->debug("SMS $smstext , not being sent as its disabled by the series");
					}
					
					if( $issued_by_store_server ){

						$lm = new ListenersMgr( $this->currentorg );
						
						$params['user_id'] = $user_id;
						$params['voucher_code'] = $vch;
						$params['voucher_series_id'] = $vch_series_id;
						$params['description'] = $vs->description;
					
						$lm->signalListeners( EVENT_LOYALTY_ISSUE_VOUCHER, $params );
					}
					
					
				}else{
					$ret = VOUCHER_ERR_UNABLE_TO_CREATE;
				}
			}

			if ($first_run) {
				$first_voucher_code = $vch;
				$first_run = false;
			}

			array_push($responses, 
				array(
					'voucher_code' => $vch,
					'voucher_client_id' => $voucher_client_id,
                                        'voucher_id' => $voucher_id,
					'item_status' => array( 
						'key' => Voucher::getResponseErrorKey($ret),
						'message' => Voucher::getResponseErrorMessage($ret)
					)
				)
			);
			
		}

		$this->data['response_code'] = $first_voucher_code;
		$this->data['responses'] = $responses;

		//Send out all the collected smses
		if($is_sms_clubbing_enabled){

			//user_id1 => array(
			//		'messages' => sms1, sms2,
			//		'mobile' => 9997978655,
			//		'scheduled_time' => 2010-07-29 18:45:00
			//	)

			foreach ($clubbed_messages_to_be_sent as $user_id => $clubbed_sms) {

				$mobile = $clubbed_sms['mobile'];
				$scheduled_time = $clubbed_sms['scheduled_time'];
				$smstext = implode(', ', $clubbed_sms['messages']);

				Util::sendSms($mobile, $smstext, $org_id, MESSAGE_PRIORITY, 
								false, $scheduled_time, false, false, array(), 
								$user_id, $user_id, 'VOUCHER'  );					

			}

		}

	}
	

	
	
	public function redeemvouchernewApiAction()
	{

$xml_string = <<<EOXML
<root>
	<voucher_redeem>
		<voucher_code>3M274GZN</voucher_code>
                <voucher_series_id>-1</voucher_series_id>
		<customer_id>22</customer_id>
		<customer_mobile>919903365411</customer_mobile>
		<customer_external_id></customer_external_id>
		<customer_email>aneesh.boddu@gmail.com</customer_email>
		<bill_number>123</bill_number>
		<bill_amount>1000</bill_amount>
		<validation_code/>
		<custom_fields_data>
			<custom_data_item>
				<field_name/>
				<field_value/>
			</custom_data_item>
		</custom_fields_data>
	</voucher_redeem>
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
		
		$responses = array();
		
		$element = Xml::parse($xml_string);
		
		$org_id = $currentorg->org_id;
		
		$elems = $element->xpath('/root/voucher_redeem');
		
		foreach ($elems as $e) 
		{

			$user_id = (string) $e->customer_id;
			$mobile = (string) $e->customer_mobile;
			$customer_external_id = (string) $e->customer_external_id;
			$customer_email = (string) $e->customer_email;
			$voucher_code = (string) $e->voucher_code;
			$bill_number = (string) $e->bill_number;
			$validation_code = (string) $e->validation_code;			
                        $voucher_series_id = (string)$e->voucher_series_id;			
                        $bill_amount = (double)$e->bill_amount; 
      		
                        $disc_code = "";
			
			$rtn = "";
			
			//Try to extract the user from user_id/customer_mobile/customer_email/customer_external_id
			$user = false;
			
			if(!$user && (strlen($user_id) > 0))
				$user = UserProfile::getById($user_id);
			
			if(!$user && (strlen($mobile) > 0))
				$user = UserProfile::getByMobile($mobile);				
			
			if(!$user && (strlen($customer_external_id) > 0))
				$user = UserProfile::getByExternalId($customer_external_id);
				
			if(!$user && (strlen($customer_email) > 0))
				$user = UserProfile::getByEmail($customer_email);	
		
			//check if the user is fraud user or not
			if( $user ){

				$is_fraud = $this->campaignsController->isFraudUser( $user->user_id );
			}
	
			//extract custom field data
			//Should be sent as array( array('field_name' => 'asdsad', 'field_value' => 'json value'), .. )
			$cf_data = array();
			foreach($e->xpath('custom_fields_data/custom_data_item') as $cfd)
			{
				$cf_name = (string) $cfd->field_name;
				$cf_value_json = (string) $cfd->field_value;

				array_push(
					$cf_data, array('field_name' => $cf_name, 'field_value' => $cf_value_json)
				);
			}

			$vch = new Voucher(); //This is to include the #defined keys :|
			if(!$user)
			{	
				$rtn = VOUCHER_ERR_INVALID_USER;
			}elseif( $is_fraud ){

				$rtn = VOUCHER_ERR_FRAUD_USER;
			}else{
			
				//$vch = new Voucher(); //This is to include the #defined keys :|
				
				$mobile = $user->mobile;
				$user_id = $user->user_id;
				
				//Make the voucher redemption call
				$rtn = $this->campaignsController->redeemVoucher($voucher_code, $user_id, $disc_code, 
							$mobile, $bill_number, $validation_code, $cf_data, $voucher_series_id, $bill_amount);

				if($rtn == VOUCHER_ERR_SUCCESS)
				{
		        	if($voucher_code != 'INVALIDATED'){
		        		$vch = Voucher::getVoucherFromCode($voucher_code, $this->currentorg->org_id);
		        		$vs = $vch->getVoucherSeries();
		        	}else{
		       			$vs = new VoucherSeries($voucher_series_id);
		      		}
          
					$series_code = $vs->getSeriesCode();
					$voucher_value = $vs->getDiscountValue();
					if($vs->getDiscountType() == 'ABS')
						$is_absolute = "True";
					else
						$is_absolute = "False";
					
					if($vch && $vch->getVoucherId() > 0) {
						//CONTACT EVENT MANAGEMENT
						$this->signalVoucherRedemptionEventToEventManager(
							$this->currentorg->org_id,
							$user_id,
							$vs->id,
							$vch->getVoucherId(),
							$this->currentuser->user_id
						);
					}
				}
			}	

			$response = array
			(
				'customer_id' => $user_id,
				'customer_mobile' => $mobile,
				'voucher_code' => $voucher_code,
				'bill_number' => $bill_number,
				'response_code' => $rtn,
				'min_bill_amount' => is_object($vs)? $vs->getMinBillAmount(): 0,
				'max_bill_amount' => is_object($vs)? $vs->getMaxBillAmount(): 0,
				'discount_code' => $disc_code,
				'series_code' => $series_code,
				'voucher_value' => $voucher_value,
				'is_absolute' => $is_absolute,
				'item_status' => array(
					'key' => Voucher::getResponseErrorKey($rtn),
					'message' => Voucher::getResponseErrorMessage($rtn)
				)
				
			);
			
                        if($rtn == VOUCHER_ERR_ALREADY_USED)
                        {        
                           if($this->currentorg->org_id == 314)     
                           {     
                                $trans_id = (string)$e->trans_id;                               
                                $vch = Voucher::getVoucherFromCode($voucher_code, $this->currentorg->org_id);
                                $res= $vch->getRedemptionInfo();

				$this->logger->debug("result: " . print_r($res, true));					
			
                                $bill_number = $res['bill_number'];
                                $bill_amount = $res['bill_amount'];
                                
                                $used_by = $res['used_by'];
                                $u = UserProfile::getById($used_by);
                                if($u)
                                {
                                   $customer_mobile = (string)$u->mobile;
                                   $customer_email = (string)$u->email;     
                                }        
 
				$vs = $vch->getVoucherSeries();
				$series_code = $vs->getSeriesCode();
				$voucher_value = $vs->getDiscountValue();
				if($vs->getDiscountType() == 'ABS')
					$is_absolute = "True";
				else
					$is_absolute = "False";

                                $response['customer_id'] = $u->user_id;
                                $response['bill_number'] = $bill_number;
                                $response['bill_amount'] = $bill_amount;
                                $response['customer_mobile'] = $customer_mobile;
                                $response['trans_id'] = $trans_id;
				$response['voucher_value'] = $voucher_value;
				$response['is_absolute'] = $is_absolute;
                                $response['redemption_date'] = $res['used_date'];
                                $response['min_bill_amount'] = $vs->getMinBillAmount();
                                $response['max_bill_amount'] = $vs->getMaxBillAmount();

                           }     
                        }
                
			array_push($responses, $response);
			//end of for loop
		}
		
		$this->data['responses'] = $responses;
		
	}	

	public function signalVoucherRedemptionEventToEventManager(
		$org_id, $user_id, $redeemedVoucherSeriesId, $redeemedVoucherId,
		$store_id)
	{

		//points engine
		global $currentorg;
		$is_enabled = Util::canCallPointsEngine();
	
		if($is_enabled) {
	
			try{
	
				$this->logger->debug("pigol: Trying to contact event manager for voucher redemption event");
				
				$red_time = Util::getMysqlDateTime(time());
				
				
				//TODO: what is $voucherValue?????
				if(Util::canCallEMF())
				{
					try{
						$emf_service_controller = new EMFServiceController();
						$commit = Util::isEMFActive();
						$voucherValue = null;
						$this->logger->debug("Making voucherRedemptionEvent call to EMF");
						$emf_result = $emf_service_controller->voucherRedemptionEvent(
								$org_id,
								$user_id,
								$redeemedVoucherSeriesId,
								$redeemedVoucherId,
								$store_id,
								$red_time,
								$voucherValue,
								$commit);
						
						$coupon_ids = $emf_service_controller->extractIssuedCouponIds($emf_result, "PE");
						$lm = new ListenersMgr($currentorg);
						$lm->issuedVoucherDetails($coupon_ids);
						
						if($commit && $emf_result !== null )
						{
							$pesC = new PointsEngineServiceController();
								
							$pesC->updateForVoucherRedemptionTransaction(
									$org_id, $user_id, (time() * 1000));
						}
					}
					catch(Exception $e)
					{
						$this->logger->error("Error while making voucherRedemptionEvent to EMF: ".$e->getMessage());
						if(Util::isEMFActive())
						{
							$this->logger->error("Rethrowing EMF Exception AS EMF is Active");
							throw $e;
						}
					}
				}
				
				if(!Util::isEMFActive())
				{
					//COMPILE
					$event_client = new EventManagementThriftClient();
					$result = $event_client->voucherRedemptionEvent(
						$org_id, $user_id, $redeemedVoucherSeriesId,
						$redeemedVoucherId, $store_id, $red_time);
		
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
						
						$pesC->updateForVoucherRedemptionTransaction(
							$org_id, $user_id, (time() * 1000));
					}
				}
	
			}catch(Exception $e){
	
				$this->logger->error("Exception thrown in voucher redemption event, code: " . $e->getCode()
				. " Message: " . $e->getMessage());
			} // end point engine call
		}
		
	}


	function resendvoucherApiAction($voucher_code, $customer_id){
		
		$response_code = true;
		$response_string = "Voucher sent";
		
		$org_id = $this->currentorg->org_id;
		//$v = Voucher::getVoucherFromId($voucher_id);
		$v = Voucher::getVoucherFromCode($voucher_code, $org_id);
		$customer = UserProfile::getById($customer_id);
		
		$ret = VOUCHER_ERR_SUCCESS;
		
		if(!$v){
			$response_code = false;
			$ret = VOUCHER_ERR_INVALID_VOUCHER_CODE;
		}else if($v->getIssuedTo() != $customer_id){
				$response_code = false;
				$ret = VOUCHER_ERR_NOT_ISSUED_TO_CUSTOMER;	
			  }
			  
		if(!$customer){
			$response_code = false;
			$ret = VOUCHER_ERR_INVALID_USER;
		}
		
		if($response_code){
            /*
			$vs = $v->getVoucherSeries();
			$sms_template = $vs->getSMSTemplate();
			if($sms_template == "")
				$sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}, which expires on {{voucher_expiry_date}}";
		    */
            $sms_template = "Hello {{cust_name}}, your voucher code is {{voucher_code}}, which expires on {{voucher_expiry_date}}";
			$eup = new ExtendedUserProfile($customer, $this->currentorg);
				
			$data = array('voucher_code' => $v->getVoucherCode(), 'cust_name' => $eup->getName(), 'voucher_expiry_date' => $v->getExpiryDate());
		
			$smstext = Util::templateReplace($sms_template, $data);	

			$this->logger->debug("Resending voucher : $smstext");
		
			if(!Util::sendSms($customer->mobile, $smstext, $org_id, 0,
								false, '', false, false, array(), 
								$customer->user_id, $customer->user_id, 'VOUCHER' )){
				$response_code = false;
				$ret = VOUCHER_ERR_COMMUNICATION;
			}
		}
		
		//Add the response for the new clients
		$this->data['api_status'] = array(
			'key' => Voucher::getResponseErrorKey($ret),
			'message' => Voucher::getResponseErrorMessage($ret)
		);
		
		$response = array();
		array_push($response, array('response_code' => ( ($response_code == true) ? 1 : -1)));
		$this->data['responses'] = $response;
	}

}

?>
